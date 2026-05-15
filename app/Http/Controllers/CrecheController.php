<?php

namespace App\Http\Controllers;

use App\Services\PigCycleService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CrecheController extends Controller
{
    public function index()
    {
        $stats = [
            'lotes_abertos' => 0,
            'estoque_animais' => 0,
            'hospital' => 0,
            'desclassificados' => 0,
            'mortalidade_taxa' => 0.0,
        ];

        $lotesResumo = [];
        $lotesCadastrados = [];
        $inconsistencias = [];
        $causas = [];
        $compras = [];
        $mortes = [];

        if (Schema::hasTable('creche_lotes')) {
            $lotesCadastrados = DB::table('creche_lotes')
                ->orderBy('nome')
                ->get(['id', 'nome', 'caracteristicas', 'situacao'])
                ->map(fn ($r) => (array) $r)
                ->all();

            $stats['lotes_abertos'] = DB::table('creche_lotes')->where('situacao', 'aberto')->count();
        }

        if (Schema::hasTable('causa')) {
            $causas = DB::table('causa')
                ->where('situacao', 1)
                ->orderBy('nome')
                ->get(['id', 'codigo', 'nome'])
                ->map(fn ($r) => (array) $r)
                ->all();
        }

        if (Schema::hasTable('creche_compras')) {
            $entradas = (int) (DB::table('creche_compras')->sum('quantidade') ?? 0);
            $saidas = 0;
            if (Schema::hasTable('creche_mortes')) {
                $saidas = (int) (DB::table('creche_mortes')->sum('quantidade') ?? 0);
            }
            $stats['estoque_animais'] = max(0, $entradas - $saidas);
        }

        if (Schema::hasTable('creche_compras') && Schema::hasTable('creche_lotes')) {
            $rows = DB::table('creche_compras as c')
                ->join('creche_lotes as l', 'l.id', '=', 'c.lote_id')
                ->leftJoin('fornecedor as f', 'f.id', '=', 'c.fornecedor_id')
                ->orderByDesc('c.data_compra')
                ->limit(200)
                ->get([
                    'c.id',
                    'c.data_compra',
                    'c.data_nascimento',
                    'c.lote_id',
                    'l.nome as lote_nome',
                    'c.localizacao',
                    'c.quantidade',
                    'c.peso_total',
                    'c.valor_compra',
                    'c.nota_fiscal',
                    'c.fornecedor_id',
                    'f.nome as fornecedor_nome',
                ]);

            $compras = $rows->map(function ($r) {
                $dataCompra = empty($r->data_compra) ? null : Carbon::parse($r->data_compra)->startOfDay();
                $dataNasc = empty($r->data_nascimento) ? null : Carbon::parse($r->data_nascimento)->startOfDay();

                return [
                    'id' => (int) $r->id,
                    'data_compra' => PigCycleService::formatDisplayDate($dataCompra),
                    'lote' => (string) ($r->lote_nome ?? ''),
                    'localizacao' => (string) ($r->localizacao ?? ''),
                    'quantidade' => (int) $r->quantidade,
                    'peso_total' => (float) $r->peso_total,
                    'data_nascimento' => PigCycleService::formatDisplayDate($dataNasc),
                    'valor_compra' => $r->valor_compra === null ? null : (float) $r->valor_compra,
                    'fornecedor' => (string) ($r->fornecedor_nome ?? ''),
                    'nota_fiscal' => (string) ($r->nota_fiscal ?? ''),
                ];
            })->all();
        }

        if (Schema::hasTable('creche_mortes') && Schema::hasTable('creche_lotes')) {
            $rows = DB::table('creche_mortes as m')
                ->join('creche_lotes as l', 'l.id', '=', 'm.lote_id')
                ->orderByDesc('m.data_morte')
                ->limit(200)
                ->get([
                    'm.id',
                    'm.data_morte',
                    'm.lote_id',
                    'l.nome as lote_nome',
                    'm.localizacao',
                    'm.quantidade',
                    'm.causa',
                    'm.origem_identificacao',
                ]);

            $mortes = $rows->map(function ($r) {
                $data = empty($r->data_morte) ? null : Carbon::parse($r->data_morte)->startOfDay();

                return [
                    'id' => (int) $r->id,
                    'data_morte' => PigCycleService::formatDisplayDate($data),
                    'lote' => (string) ($r->lote_nome ?? ''),
                    'localizacao' => (string) ($r->localizacao ?? ''),
                    'quantidade' => (int) $r->quantidade,
                    'causa' => (string) ($r->causa ?? ''),
                    'origem_identificacao' => (string) ($r->origem_identificacao ?? ''),
                ];
            })->all();
        }

        if (Schema::hasTable('creche_lotes') && Schema::hasTable('creche_compras')) {
            $comprasAgg = DB::table('creche_compras')
                ->selectRaw('lote_id, MIN(data_compra) as data_abertura, COALESCE(SUM(quantidade), 0) as quantidade')
                ->groupBy('lote_id');

            $mortesAgg = null;
            if (Schema::hasTable('creche_mortes')) {
                $mortesAgg = DB::table('creche_mortes')
                    ->selectRaw('lote_id, COALESCE(SUM(quantidade), 0) as mortes')
                    ->groupBy('lote_id');
            }

            $rows = DB::table('creche_lotes as l')
                ->leftJoinSub($comprasAgg, 'c', 'c.lote_id', '=', 'l.id');

            $qtyExpr = 'GREATEST(0, COALESCE(c.quantidade, 0))';
            if ($mortesAgg) {
                $rows->leftJoinSub($mortesAgg, 'm', 'm.lote_id', '=', 'l.id');
                $qtyExpr = 'GREATEST(0, COALESCE(c.quantidade, 0) - COALESCE(m.mortes, 0))';
            }

            $rows = $rows
                ->where('l.situacao', 'aberto')
                ->groupBy('l.id', 'l.nome', 'c.data_abertura', 'c.quantidade')
                ->orderBy('l.nome')
                ->get([
                    'l.id',
                    'l.nome',
                    'c.data_abertura',
                    DB::raw($qtyExpr . ' as quantidade'),
                ]);

            $lotesResumo = $rows->map(function ($r) {
                $dataAbertura = empty($r->data_abertura) ? null : Carbon::parse($r->data_abertura)->startOfDay();
                $dias = $dataAbertura ? $dataAbertura->diffInDays(Carbon::today()) : 0;

                return [
                    'id' => (int) $r->id,
                    'identificacao' => (string) $r->nome,
                    'data_abertura' => $dataAbertura ? $dataAbertura->format('d/m/Y') : '-',
                    'quantidade' => (int) $r->quantidade,
                    'dias_alojamento' => (int) $dias,
                ];
            })->all();
        }

        return view('creche', [
            'stats' => $stats,
            'lotes' => $lotesResumo,
            'lotesCadastrados' => $lotesCadastrados,
            'causas' => $causas,
            'compras' => $compras,
            'mortes' => $mortes,
            'inconsistencias' => $inconsistencias,
        ]);
    }

    public function storeLote(Request $request)
    {
        $request->merge([
            'nome' => trim((string) $request->input('nome', '')),
            'caracteristicas' => $request->input('caracteristicas') === null ? null : trim((string) $request->input('caracteristicas')),
        ]);

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:120', 'unique:creche_lotes,nome'],
            'caracteristicas' => ['nullable', 'string', 'max:1000'],
        ]);

        if (!Schema::hasTable('creche_lotes')) {
            return back()
                ->withInput()
                ->withErrors(['nome' => "Tabela 'creche_lotes' não existe."]);
        }

        try {
            DB::table('creche_lotes')->insert([
                'nome' => $validated['nome'],
                'caracteristicas' => $validated['caracteristicas'] ?? null,
                'situacao' => 'aberto',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $e) {
            if ((string) $e->getCode() === '23000') {
                return back()
                    ->withInput()
                    ->withErrors(['nome' => 'Já existe um lote cadastrado com esse nome.']);
            }
            throw $e;
        }

        return redirect()->route('creche')->with('success', 'Lote cadastrado com sucesso!');
    }

    public function storeCompra(Request $request)
    {
        $validated = $request->validate([
            'data_compra' => ['required', 'date'],
            'lote_id' => ['required', 'integer'],
            'localizacao' => ['nullable', 'string', 'max:120'],
            'quantidade' => ['required', 'integer', 'min:1'],
            'peso_total' => ['required', 'numeric', 'min:0'],
            'data_nascimento' => ['required', 'date'],
            'valor_compra' => ['nullable', 'numeric', 'min:0'],
            'fornecedor_id' => ['nullable', 'integer'],
            'nota_fiscal' => ['nullable', 'string', 'max:120'],
        ]);

        if (!Schema::hasTable('creche_compras')) {
            return back()
                ->withInput()
                ->withErrors(['data_compra' => "Tabela 'creche_compras' não existe."]);
        }

        if (Schema::hasTable('creche_lotes')) {
            $exists = DB::table('creche_lotes')->where('id', $validated['lote_id'])->exists();
            if (!$exists) {
                return back()
                    ->withInput()
                    ->withErrors(['lote_id' => 'Lote inválido.']);
            }
        }

        DB::table('creche_compras')->insert([
            'data_compra' => Carbon::parse($validated['data_compra'])->startOfDay()->format('Y-m-d'),
            'lote_id' => (int) $validated['lote_id'],
            'localizacao' => $validated['localizacao'] ?? null,
            'quantidade' => (int) $validated['quantidade'],
            'peso_total' => (float) $validated['peso_total'],
            'data_nascimento' => Carbon::parse($validated['data_nascimento'])->startOfDay()->format('Y-m-d'),
            'valor_compra' => isset($validated['valor_compra']) ? (float) $validated['valor_compra'] : null,
            'fornecedor_id' => isset($validated['fornecedor_id']) ? (int) $validated['fornecedor_id'] : null,
            'nota_fiscal' => $validated['nota_fiscal'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('creche')->with('success', 'Compra registrada com sucesso!');
    }

    public function storeMorte(Request $request)
    {
        $validated = $request->validate([
            'lote_id' => ['required', 'integer'],
            'localizacao' => ['nullable', 'string', 'max:120'],
            'data_morte' => ['required', 'date'],
            'quantidade' => ['required', 'integer', 'min:1'],
            'causa' => ['required', 'string', 'max:255'],
            'origem_identificacao' => ['nullable', 'string', 'max:255'],
        ]);

        if (!Schema::hasTable('creche_mortes')) {
            return back()
                ->withInput()
                ->withErrors(['data_morte' => "Tabela 'creche_mortes' não existe."]);
        }

        if (Schema::hasTable('creche_lotes')) {
            $exists = DB::table('creche_lotes')->where('id', $validated['lote_id'])->exists();
            if (!$exists) {
                return back()
                    ->withInput()
                    ->withErrors(['lote_id' => 'Lote inválido.']);
            }
        }

        DB::table('creche_mortes')->insert([
            'lote_id' => (int) $validated['lote_id'],
            'localizacao' => $validated['localizacao'] ?? null,
            'data_morte' => Carbon::parse($validated['data_morte'])->startOfDay()->format('Y-m-d'),
            'quantidade' => (int) $validated['quantidade'],
            'causa' => $validated['causa'],
            'origem_identificacao' => $validated['origem_identificacao'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('creche')->with('success', 'Morte registrada com sucesso!');
    }

    public function showLote(int $id)
    {
        if (!Schema::hasTable('creche_lotes')) {
            abort(404);
        }

        $lote = DB::table('creche_lotes')
            ->where('id', $id)
            ->first(['id', 'nome', 'caracteristicas', 'situacao']);

        if (!$lote) {
            abort(404);
        }

        $lotes = DB::table('creche_lotes')
            ->orderBy('nome')
            ->get(['id', 'nome'])
            ->map(fn ($r) => ['id' => (int) $r->id, 'nome' => (string) $r->nome])
            ->all();

        $entradasQtd = 0;
        $entradasPesoTotal = 0.0;
        $mortesQtd = 0;
        $saldo = 0;

        $dataAbertura = null;
        $previsaoFechamento = null;
        $dataMediaNascimento = null;
        $localizacao = null;

        $idadeMediaEntrada = null;
        $pesoMedioEntrada = null;

        $metaDiasNaFase = 42;
        $movimentacoes = [];

        if (Schema::hasTable('creche_compras')) {
            $compras = DB::table('creche_compras')
                ->where('lote_id', $id)
                ->get(['id', 'data_compra', 'data_nascimento', 'quantidade', 'peso_total', 'localizacao', 'nota_fiscal']);

            $entradasQtd = (int) $compras->sum('quantidade');
            $entradasPesoTotal = (float) $compras->sum('peso_total');

            $minData = $compras->min('data_compra');
            $dataAbertura = $minData ? Carbon::parse($minData)->startOfDay() : null;

            $localizacao = DB::table('creche_compras')
                ->where('lote_id', $id)
                ->whereNotNull('localizacao')
                ->where('localizacao', '<>', '')
                ->orderByDesc('data_compra')
                ->orderByDesc('id')
                ->value('localizacao');

            $weightedBirthTs = 0.0;
            $weightedBirthQty = 0;
            $weightedAgeDays = 0.0;
            $weightedAgeQty = 0;

            foreach ($compras as $c) {
                $q = (int) ($c->quantidade ?? 0);
                if ($q <= 0) {
                    continue;
                }

                if (!empty($c->data_nascimento)) {
                    $birth = Carbon::parse($c->data_nascimento)->startOfDay();
                    $weightedBirthTs += ($birth->getTimestamp() * $q);
                    $weightedBirthQty += $q;
                }

                if (!empty($c->data_compra) && !empty($c->data_nascimento)) {
                    $compra = Carbon::parse($c->data_compra)->startOfDay();
                    $birth = Carbon::parse($c->data_nascimento)->startOfDay();
                    $weightedAgeDays += ($birth->diffInDays($compra) * $q);
                    $weightedAgeQty += $q;
                }
            }

            if ($weightedBirthQty > 0) {
                $avgTs = (int) round($weightedBirthTs / $weightedBirthQty);
                $dataMediaNascimento = Carbon::createFromTimestamp($avgTs)->startOfDay();
            }

            if ($weightedAgeQty > 0) {
                $idadeMediaEntrada = round($weightedAgeDays / $weightedAgeQty, 2);
            }

            if ($entradasQtd > 0) {
                $pesoMedioEntrada = round($entradasPesoTotal / $entradasQtd, 2);
            }

            if ($dataAbertura) {
                $previsaoFechamento = $dataAbertura->copy()->addDays($metaDiasNaFase);
            }

            $movimentacoes = array_merge($movimentacoes, $compras->map(function ($c) {
                $data = empty($c->data_compra) ? null : Carbon::parse($c->data_compra)->startOfDay();
                $qtd = (int) ($c->quantidade ?? 0);
                $peso = $c->peso_total === null ? null : (float) $c->peso_total;
                $origem = (string) ($c->nota_fiscal ?? '');

                return [
                    'data_raw' => $data?->toDateString(),
                    'data' => PigCycleService::formatDisplayDate($data),
                    'tipo' => 'entrada',
                    'tipo_label' => 'Entrada',
                    'quantidade' => $qtd,
                    'peso_total' => $peso,
                    'localizacao' => (string) ($c->localizacao ?? ''),
                    'descricao' => $origem !== '' ? $origem : 'Entrada',
                ];
            })->all());
        }

        if (Schema::hasTable('creche_mortes')) {
            $mortesQtd = (int) (DB::table('creche_mortes')->where('lote_id', $id)->sum('quantidade') ?? 0);

            $mortes = DB::table('creche_mortes')
                ->where('lote_id', $id)
                ->get(['id', 'data_morte', 'quantidade', 'localizacao', 'causa', 'origem_identificacao']);

            $movimentacoes = array_merge($movimentacoes, $mortes->map(function ($m) {
                $data = empty($m->data_morte) ? null : Carbon::parse($m->data_morte)->startOfDay();
                $qtd = (int) ($m->quantidade ?? 0);
                $causa = (string) ($m->causa ?? '');
                $origem = (string) ($m->origem_identificacao ?? '');
                $desc = $causa !== '' ? $causa : 'Morte';
                if ($origem !== '') {
                    $desc .= ' - ' . $origem;
                }

                return [
                    'data_raw' => $data?->toDateString(),
                    'data' => PigCycleService::formatDisplayDate($data),
                    'tipo' => 'saida',
                    'tipo_label' => 'Saída',
                    'quantidade' => $qtd,
                    'peso_total' => null,
                    'localizacao' => (string) ($m->localizacao ?? ''),
                    'descricao' => $desc,
                ];
            })->all());
        }

        $saldo = max(0, $entradasQtd - $mortesQtd);
        $mortalidadePct = $entradasQtd > 0 ? round(($mortesQtd / $entradasQtd) * 100, 2) : 0.0;
        $diasNaFase = $dataAbertura ? (int) $dataAbertura->diffInDays(Carbon::today()) : 0;

        usort($movimentacoes, function ($a, $b) {
            $da = (string) ($a['data_raw'] ?? '');
            $db = (string) ($b['data_raw'] ?? '');
            if ($da !== $db) {
                return strcmp($da, $db);
            }
            $oa = ($a['tipo'] ?? '') === 'entrada' ? 0 : 1;
            $ob = ($b['tipo'] ?? '') === 'entrada' ? 0 : 1;
            return $oa <=> $ob;
        });

        $saldoCalc = 0;
        foreach ($movimentacoes as $i => $mov) {
            $q = (int) ($mov['quantidade'] ?? 0);
            if (($mov['tipo'] ?? '') === 'entrada') {
                $saldoCalc += $q;
            } else {
                $saldoCalc -= $q;
            }
            $movimentacoes[$i]['saldo'] = max(0, $saldoCalc);
        }

        return view('creche.lote', [
            'lote' => [
                'id' => (int) $lote->id,
                'nome' => (string) $lote->nome,
                'caracteristicas' => (string) ($lote->caracteristicas ?? ''),
                'situacao' => (string) ($lote->situacao ?? ''),
            ],
            'lotes' => $lotes,
            'resumo' => [
                'data_abertura' => $dataAbertura ? PigCycleService::formatDisplayDate($dataAbertura) : '-',
                'previsao_fechamento' => $previsaoFechamento ? PigCycleService::formatDisplayDate($previsaoFechamento) : '-',
                'data_media_nascimento' => $dataMediaNascimento ? PigCycleService::formatDisplayDate($dataMediaNascimento) : '-',
                'localizacao' => $localizacao ? (string) $localizacao : '-',
                'saldo_animais' => $saldo,
            ],
            'metricas' => [
                'entrada' => $entradasQtd,
                'idade_media_entrada' => $idadeMediaEntrada,
                'peso_medio_entrada' => $pesoMedioEntrada,
                'consumo_racao' => 0.0,
                'consumo_racao_cab' => null,
                'mortalidade_pct' => $mortalidadePct,
                'dias_na_fase' => $diasNaFase,
                'meta_dias_na_fase' => $metaDiasNaFase,
                'saida' => $mortesQtd,
                'idade_media_saida' => null,
                'peso_medio_saida' => null,
                'peso_proj_saida' => null,
            ],
            'movimentacoes' => $movimentacoes,
        ]);
    }
}
