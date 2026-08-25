<?php

namespace App\Http\Controllers;

use App\Services\PigCycleService;
use App\Services\TerminacaoService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TerminacaoController extends Controller
{
    /**
     * Converte uma data vinda do input do formulário (Dia PIG, DD/MM/AAAA ou Y-m-d ISO)
     * para string 'Y-m-d' (formato de gravação no BD).
     * Retorna null se vazio ou inválido.
     */
    private function parseInputDate(?string $input): ?string
    {
        if ($input === null || trim($input) === '') {
            return null;
        }
        $carbon = PigCycleService::parseFilterDate($input);
        if ($carbon) {
            return $carbon->format('Y-m-d');
        }
        return null;
    }

    public function index()
    {
        $stats = [
            'lotes_abertos' => 0,
            'estoque_animais' => 0,
            'hospital' => 0,
            'desclassificados' => 0,
            'mortalidade_taxa' => 0.0,
            'vendidos_periodo' => 0,
            'mortes_periodo' => 0,
        ];

        $lotesResumo = [];
        $lotesCadastrados = [];
        $inconsistencias = [];
        $causas = [];
        $fornecedores = [];
        $entradas = [];
        $mortes = [];
        $transferencias = [];
        $vendas = [];
        $pesos = [];
        $crecheLotes = [];

        $metaMortalidade = TerminacaoService::getMetaFloat('meta_terminacao_mortalidade_pct', 3.0);
        $metaDias = TerminacaoService::getMetaInt('meta_terminacao_dias_permanencia', 90);
        $metaPesoAbate = TerminacaoService::getMetaFloat('meta_terminacao_peso_abate_kg', 115.00);
        $metaDiasSemMovimento = TerminacaoService::getMetaInt('meta_terminacao_dias_sem_movimento', 15);
        $metaLoteResidualPct = TerminacaoService::getMetaFloat('meta_terminacao_lote_residual_pct', 10.0);

        if (Schema::hasTable('terminacao_lotes')) {
            $lotesCadastrados = DB::table('terminacao_lotes')
                ->orderBy('nome')
                ->get(['id', 'nome', 'caracteristicas', 'situacao', 'galpao', 'localizacao'])
                ->map(fn ($r) => (array) $r)
                ->all();

            $stats['lotes_abertos'] = DB::table('terminacao_lotes')->where('situacao', 'aberto')->count();
        }

        if (Schema::hasTable('creche_lotes')) {
            $crecheLotes = DB::table('creche_lotes')
                ->orderBy('nome')
                ->get(['id', 'nome', 'situacao'])
                ->map(fn ($r) => ['id' => (int) $r->id, 'nome' => (string) $r->nome, 'situacao' => (string) $r->situacao])
                ->all();
        }

        if (Schema::hasTable('causa')) {
            $causas = DB::table('causa')
                ->where('situacao', 1)
                ->orderBy('nome')
                ->get(['id', 'codigo', 'nome'])
                ->map(fn ($r) => (array) $r)
                ->all();
        }

        if (Schema::hasTable('fornecedor')) {
            $fornecedores = DB::table('fornecedor')
                ->orderBy('nome')
                ->get(['id', 'nome'])
                ->map(fn ($r) => ['id' => (int) $r->id, 'nome' => (string) $r->nome])
                ->all();
        }

        $estoqueTotal = 0;
        $totalEntradasEstoque = 0;
        $totalMortesEstoque = 0;

        if (Schema::hasTable('terminacao_lotes')) {
            $lotesAbertos = DB::table('terminacao_lotes')->where('situacao', 'aberto')->pluck('id')->all();
            foreach ($lotesAbertos as $loteId) {
                $saldoInfo = TerminacaoService::calcularSaldoLote((int) $loteId);
                $estoqueTotal += $saldoInfo['saldo'];
                $totalEntradasEstoque += $saldoInfo['entradas'];
                $totalMortesEstoque += $saldoInfo['mortes'];
            }
        }

        $stats['estoque_animais'] = $estoqueTotal;
        $stats['mortalidade_taxa'] = $totalEntradasEstoque > 0
            ? round(($totalMortesEstoque / $totalEntradasEstoque) * 100, 2)
            : 0.0;

        $inconsistencias = TerminacaoService::buildInconsistencias();

        if (Schema::hasTable('terminacao_entradas') && Schema::hasTable('terminacao_lotes')) {
            $rows = DB::table('terminacao_entradas as e')
                ->join('terminacao_lotes as l', 'l.id', '=', 'e.lote_id')
                ->leftJoin('fornecedor as f', 'f.id', '=', 'e.fornecedor_id')
                ->orderByDesc('e.data_entrada')
                ->limit(200)
                ->get([
                    'e.id', 'e.data_entrada', 'e.lote_id', 'l.nome as lote_nome',
                    'e.localizacao', 'e.baia', 'e.quantidade', 'e.peso_total',
                    'e.peso_medio', 'e.data_nascimento', 'e.valor_compra',
                    'e.origem', 'e.fornecedor_id', 'f.nome as fornecedor_nome',
                    'e.nota_fiscal',
                ]);

            $entradas = $rows->map(function ($r) {
                $dataEntrada = empty($r->data_entrada) ? null : Carbon::parse($r->data_entrada)->startOfDay();
                $dataNasc = empty($r->data_nascimento) ? null : Carbon::parse($r->data_nascimento)->startOfDay();
                return [
                    'id' => (int) $r->id,
                    'data_entrada' => PigCycleService::formatDisplayDate($dataEntrada),
                    'lote' => (string) ($r->lote_nome ?? ''),
                    'localizacao' => trim(implode(' - ', array_filter([(string) ($r->localizacao ?? ''), (string) ($r->baia ?? '')]))),
                    'quantidade' => (int) $r->quantidade,
                    'peso_total' => $r->peso_total === null ? null : (float) $r->peso_total,
                    'peso_medio' => $r->peso_medio === null ? null : (float) $r->peso_medio,
                    'data_nascimento' => PigCycleService::formatDisplayDate($dataNasc),
                    'valor_compra' => $r->valor_compra === null ? null : (float) $r->valor_compra,
                    'origem' => (string) ($r->origem ?? ''),
                    'fornecedor' => (string) ($r->fornecedor_nome ?? ''),
                    'nota_fiscal' => (string) ($r->nota_fiscal ?? ''),
                ];
            })->all();
        }

        if (Schema::hasTable('terminacao_mortes') && Schema::hasTable('terminacao_lotes')) {
            $rows = DB::table('terminacao_mortes as m')
                ->join('terminacao_lotes as l', 'l.id', '=', 'm.lote_id')
                ->leftJoin('causa as c', 'c.id', '=', 'm.causa_id')
                ->orderByDesc('m.data_morte')
                ->limit(200)
                ->get([
                    'm.id', 'm.data_morte', 'm.lote_id', 'l.nome as lote_nome',
                    'm.localizacao', 'm.baia', 'm.quantidade', 'm.causa_id',
                    'm.causa', 'c.nome as causa_nome', 'm.origem_identificacao',
                    'm.peso_medio', 'm.tipo_morte',
                ]);

            $mortes = $rows->map(function ($r) {
                $data = empty($r->data_morte) ? null : Carbon::parse($r->data_morte)->startOfDay();
                $causaFinal = (string) ($r->causa_nome ?? $r->causa ?? '');
                return [
                    'id' => (int) $r->id,
                    'data_morte' => PigCycleService::formatDisplayDate($data),
                    'lote' => (string) ($r->lote_nome ?? ''),
                    'localizacao' => trim(implode(' - ', array_filter([(string) ($r->localizacao ?? ''), (string) ($r->baia ?? '')]))),
                    'quantidade' => (int) $r->quantidade,
                    'causa' => $causaFinal,
                    'origem_identificacao' => (string) ($r->origem_identificacao ?? ''),
                    'peso_medio' => $r->peso_medio === null ? null : (float) $r->peso_medio,
                    'tipo_morte' => (string) ($r->tipo_morte ?? ''),
                ];
            })->all();
        }

        if (Schema::hasTable('terminacao_transferencias') && Schema::hasTable('terminacao_lotes')) {
            $rows = DB::table('terminacao_transferencias as t')
                ->join('terminacao_lotes as lo', 'lo.id', '=', 't.lote_origem_id')
                ->leftJoin('terminacao_lotes as ld', 'ld.id', '=', 't.lote_destino_id')
                ->orderByDesc('t.data_transferencia')
                ->limit(200)
                ->get([
                    't.id', 't.data_transferencia', 't.lote_origem_id', 'lo.nome as origem_nome',
                    't.lote_destino_id', 'ld.nome as destino_nome',
                    't.localizacao_origem', 't.baia_origem',
                    't.localizacao_destino', 't.baia_destino',
                    't.quantidade', 't.peso_total', 't.peso_medio', 't.tipo', 't.motivo',
                ]);

            $transferencias = $rows->map(function ($r) {
                $data = empty($r->data_transferencia) ? null : Carbon::parse($r->data_transferencia)->startOfDay();
                $origemLoc = trim(implode(' - ', array_filter([(string) ($r->localizacao_origem ?? ''), (string) ($r->baia_origem ?? '')])));
                $destinoLoc = trim(implode(' - ', array_filter([(string) ($r->localizacao_destino ?? ''), (string) ($r->baia_destino ?? '')])));
                return [
                    'id' => (int) $r->id,
                    'data_transferencia' => PigCycleService::formatDisplayDate($data),
                    'lote_origem' => (string) ($r->origem_nome ?? ''),
                    'lote_destino' => (string) ($r->destino_nome ?? ''),
                    'localizacao_origem' => $origemLoc,
                    'localizacao_destino' => $destinoLoc,
                    'quantidade' => (int) $r->quantidade,
                    'peso_medio' => $r->peso_medio === null ? null : (float) $r->peso_medio,
                    'tipo' => (string) ($r->tipo ?? ''),
                    'motivo' => (string) ($r->motivo ?? ''),
                ];
            })->all();
        }

        if (Schema::hasTable('terminacao_vendas') && Schema::hasTable('terminacao_lotes')) {
            $rows = DB::table('terminacao_vendas as v')
                ->join('terminacao_lotes as l', 'l.id', '=', 'v.lote_id')
                ->leftJoin('fornecedor as f', 'f.id', '=', 'v.comprador_id')
                ->orderByDesc('v.data_venda')
                ->limit(200)
                ->get([
                    'v.id', 'v.data_venda', 'v.lote_id', 'l.nome as lote_nome',
                    'v.localizacao', 'v.quantidade', 'v.peso_total_kg', 'v.peso_medio_kg',
                    'v.peso_frigorifico_kg', 'v.rendimento_carcaca_pct',
                    'v.valor_unitario', 'v.valor_total',
                    'v.comprador_id', 'f.nome as comprador_nome',
                    'v.frigorifico_nome', 'v.nota_fiscal_saida', 'v.tipo_saida',
                ]);

            $vendas = $rows->map(function ($r) {
                $data = empty($r->data_venda) ? null : Carbon::parse($r->data_venda)->startOfDay();
                return [
                    'id' => (int) $r->id,
                    'data_venda' => PigCycleService::formatDisplayDate($data),
                    'lote' => (string) ($r->lote_nome ?? ''),
                    'localizacao' => (string) ($r->localizacao ?? ''),
                    'quantidade' => (int) $r->quantidade,
                    'peso_medio_kg' => $r->peso_medio_kg === null ? null : (float) $r->peso_medio_kg,
                    'peso_total_kg' => $r->peso_total_kg === null ? null : (float) $r->peso_total_kg,
                    'rendimento_pct' => $r->rendimento_carcaca_pct === null ? null : (float) $r->rendimento_carcaca_pct,
                    'valor_total' => $r->valor_total === null ? null : (float) $r->valor_total,
                    'valor_unitario' => $r->valor_unitario === null ? null : (float) $r->valor_unitario,
                    'comprador' => (string) ($r->comprador_nome ?? $r->frigorifico_nome ?? ''),
                    'nota_fiscal' => (string) ($r->nota_fiscal_saida ?? ''),
                    'tipo_saida' => (string) ($r->tipo_saida ?? ''),
                ];
            })->all();
        }

        if (Schema::hasTable('terminacao_pesos') && Schema::hasTable('terminacao_lotes')) {
            $rows = DB::table('terminacao_pesos as p')
                ->join('terminacao_lotes as l', 'l.id', '=', 'p.lote_id')
                ->orderByDesc('p.data_pesagem')
                ->limit(200)
                ->get([
                    'p.id', 'p.data_pesagem', 'p.lote_id', 'l.nome as lote_nome',
                    'p.localizacao', 'p.baia', 'p.quantidade_amostra', 'p.quantidade_lote',
                    'p.peso_total_kg', 'p.peso_medio_kg', 'p.peso_minimo_kg', 'p.peso_maximo_kg',
                    'p.idade_dias', 'p.gpd_medio', 'p.tipo_pesagem',
                ]);

            $pesos = $rows->map(function ($r) {
                $data = empty($r->data_pesagem) ? null : Carbon::parse($r->data_pesagem)->startOfDay();
                return [
                    'id' => (int) $r->id,
                    'data_pesagem' => PigCycleService::formatDisplayDate($data),
                    'lote' => (string) ($r->lote_nome ?? ''),
                    'localizacao' => trim(implode(' - ', array_filter([(string) ($r->localizacao ?? ''), (string) ($r->baia ?? '')]))),
                    'quantidade_amostra' => $r->quantidade_amostra === null ? null : (int) $r->quantidade_amostra,
                    'quantidade_lote' => $r->quantidade_lote === null ? null : (int) $r->quantidade_lote,
                    'peso_medio_kg' => (float) $r->peso_medio_kg,
                    'peso_minimo_kg' => $r->peso_minimo_kg === null ? null : (float) $r->peso_minimo_kg,
                    'peso_maximo_kg' => $r->peso_maximo_kg === null ? null : (float) $r->peso_maximo_kg,
                    'gpd_medio' => $r->gpd_medio === null ? null : (float) $r->gpd_medio,
                    'tipo_pesagem' => (string) ($r->tipo_pesagem ?? ''),
                ];
            })->all();
        }

        if (Schema::hasTable('terminacao_lotes') && Schema::hasTable('terminacao_entradas')) {
            $lotesAbertosIds = DB::table('terminacao_lotes')
                ->where('situacao', 'aberto')
                ->orderBy('nome')
                ->pluck('id')
                ->all();

            $lotesResumo = array_map(function ($loteId) use ($metaDias) {
                $lote = DB::table('terminacao_lotes')->where('id', $loteId)->first();
                if (!$lote) {
                    return null;
                }
                $saldoInfo = TerminacaoService::calcularSaldoLote((int) $loteId);
                $dataEntrada = $lote->data_entrada ? Carbon::parse($lote->data_entrada)->startOfDay() : null;
                $dias = $dataEntrada ? TerminacaoService::calcularDiasAlojamento($dataEntrada) : 0;
                $progresso = $metaDias > 0 ? min(100, (int) round(($dias / $metaDias) * 100)) : 0;
                $ultPeso = TerminacaoService::ultimaPesagem((int) $loteId);

                return [
                    'id' => (int) $lote->id,
                    'identificacao' => (string) $lote->nome,
                    'data_abertura' => $dataEntrada ? PigCycleService::formatDisplayDate($dataEntrada) : '-',
                    'data_abertura_raw' => $dataEntrada?->toDateString(),
                    'quantidade' => (int) $saldoInfo['saldo'],
                    'quantidade_inicial' => (int) ($lote->quantidade_inicial ?: $saldoInfo['entradas']),
                    'dias_alojamento' => $dias,
                    'meta_dias' => $metaDias,
                    'progresso_pct' => $progresso,
                    'localizacao' => trim(implode(' - ', array_filter([(string) ($lote->galpao ?? ''), (string) ($lote->localizacao ?? '')]))),
                    'mortalidade_pct' => $saldoInfo['mortalidade_pct'],
                    'ultimo_peso_kg' => $ultPeso['peso_medio_kg'] ?? null,
                    'tag' => 'TERMINAÇÃO',
                ];
            }, $lotesAbertosIds);

            $lotesResumo = array_values(array_filter($lotesResumo));
        }

        return view('terminacao', [
            'stats' => $stats,
            'lotes' => $lotesResumo,
            'lotesCadastrados' => $lotesCadastrados,
            'crecheLotes' => $crecheLotes,
            'causas' => $causas,
            'fornecedores' => $fornecedores,
            'entradas' => $entradas,
            'mortes' => $mortes,
            'transferencias' => $transferencias,
            'vendas' => $vendas,
            'pesos' => $pesos,
            'inconsistencias' => $inconsistencias,
            'metaMortalidade' => $metaMortalidade,
            'metaDias' => $metaDias,
            'metaPesoAbate' => $metaPesoAbate,
            'metaDiasSemMovimento' => $metaDiasSemMovimento,
            'metaLoteResidualPct' => $metaLoteResidualPct,
            'calendarioTipo' => PigCycleService::getCalendarType(),
            'hojeIso' => Carbon::today()->format('Y-m-d'),
        ]);
    }

    public function storeLote(Request $request)
    {
        $request->merge([
            'nome' => trim((string) $request->input('nome', '')),
            'caracteristicas' => $request->input('caracteristicas') === null ? null : trim((string) $request->input('caracteristicas')),
            'galpao' => $request->input('galpao') === null ? null : trim((string) $request->input('galpao')),
            'localizacao' => $request->input('localizacao') === null ? null : trim((string) $request->input('localizacao')),
            'observacoes' => $request->input('observacoes') === null ? null : trim((string) $request->input('observacoes')),
            'origem' => trim((string) $request->input('origem', 'creche')),
        ]);

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'caracteristicas' => ['nullable', 'string', 'max:1000'],
            'data_entrada' => ['nullable', 'string', 'max:30'],
            'quantidade_inicial' => ['nullable', 'integer', 'min:0'],
            'origem' => ['nullable', 'string', 'max:30'],
            'creche_lote_id' => ['nullable', 'integer'],
            'galpao' => ['nullable', 'string', 'max:80'],
            'localizacao' => ['nullable', 'string', 'max:120'],
            'meta_dias_terminacao' => ['nullable', 'integer', 'min:1'],
            'meta_peso_abate_kg' => ['nullable', 'numeric', 'min:0'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (!Schema::hasTable('terminacao_lotes')) {
            return back()->withInput()->withErrors(['nome' => "Tabela 'terminacao_lotes' não existe no banco do tenant. Rode o SQL 0.12.sql."]);
        }

        try {
            $insertData = [
                'nome' => $validated['nome'],
                'caracteristicas' => $validated['caracteristicas'] ?? null,
                'situacao' => 'aberto',
                'data_entrada' => $this->parseInputDate($validated['data_entrada'] ?? null),
                'quantidade_inicial' => $validated['quantidade_inicial'] ?? 0,
                'origem' => $validated['origem'] ?? null,
                'creche_lote_id' => isset($validated['creche_lote_id']) && $validated['creche_lote_id'] > 0 ? (int) $validated['creche_lote_id'] : null,
                'galpao' => $validated['galpao'] ?? null,
                'localizacao' => $validated['localizacao'] ?? null,
                'meta_dias_terminacao' => $validated['meta_dias_terminacao'] ?? TerminacaoService::getMetaInt('meta_terminacao_dias_permanencia', 90),
                'meta_peso_abate_kg' => $validated['meta_peso_abate_kg'] ?? TerminacaoService::getMetaFloat('meta_terminacao_peso_abate_kg', 115.00),
                'observacoes' => $validated['observacoes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('terminacao_lotes', 'usuario_id') && auth()->check()) {
                $insertData['usuario_id'] = auth()->id();
            }

            DB::table('terminacao_lotes')->insert($insertData);
        } catch (QueryException $e) {
            if ((string) $e->getCode() === '23000') {
                return back()->withInput()->withErrors(['nome' => 'Já existe um lote de terminação com esse nome.']);
            }
            throw $e;
        }

        return redirect()->route('terminacao')->with('success', 'Lote de terminação criado com sucesso!');
    }

    public function storeEntrada(Request $request)
    {
        $validated = $request->validate([
            'data_entrada' => ['required', 'string', 'max:30'],
            'lote_id' => ['required', 'integer', 'min:1'],
            'localizacao' => ['nullable', 'string', 'max:120'],
            'baia' => ['nullable', 'string', 'max:60'],
            'quantidade' => ['required', 'integer', 'min:1'],
            'peso_total' => ['nullable', 'numeric', 'min:0'],
            'peso_medio' => ['nullable', 'numeric', 'min:0'],
            'data_nascimento' => ['nullable', 'string', 'max:30'],
            'origem' => ['nullable', 'string', 'max:30'],
            'creche_lote_id' => ['nullable', 'integer'],
            'creche_compra_id' => ['nullable', 'integer'],
            'valor_compra' => ['nullable', 'numeric', 'min:0'],
            'valor_unitario' => ['nullable', 'numeric', 'min:0'],
            'fornecedor_id' => ['nullable', 'integer'],
            'nota_fiscal' => ['nullable', 'string', 'max:120'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (!Schema::hasTable('terminacao_entradas')) {
            return back()->withInput()->withErrors(['data_entrada' => "Tabela 'terminacao_entradas' não existe."]);
        }
        if (!Schema::hasTable('terminacao_lotes')) {
            return back()->withInput()->withErrors(['lote_id' => "Tabela 'terminacao_lotes' não existe."]);
        }

        $existeLote = DB::table('terminacao_lotes')->where('id', $validated['lote_id'])->exists();
        if (!$existeLote) {
            return back()->withInput()->withErrors(['lote_id' => 'Lote de terminação inválido.']);
        }

        $pesoTotal = isset($validated['peso_total']) ? (float) $validated['peso_total'] : null;
        $pesoMedio = isset($validated['peso_medio']) ? (float) $validated['peso_medio'] : null;
        if ($pesoTotal === null && $pesoMedio !== null) {
            $pesoTotal = round($pesoMedio * (int) $validated['quantidade'], 2);
        }
        if ($pesoMedio === null && $pesoTotal !== null && (int) $validated['quantidade'] > 0) {
            $pesoMedio = round($pesoTotal / (int) $validated['quantidade'], 2);
        }

        $insert = [
            'data_entrada' => $this->parseInputDate($validated['data_entrada'] ?? null),
            'lote_id' => (int) $validated['lote_id'],
            'localizacao' => $validated['localizacao'] ?? null,
            'baia' => $validated['baia'] ?? null,
            'quantidade' => (int) $validated['quantidade'],
            'peso_total' => $pesoTotal,
            'peso_medio' => $pesoMedio,
            'data_nascimento' => $this->parseInputDate($validated['data_nascimento'] ?? null),
            'origem' => $validated['origem'] ?? 'creche',
            'creche_lote_id' => isset($validated['creche_lote_id']) && $validated['creche_lote_id'] > 0 ? (int) $validated['creche_lote_id'] : null,
            'creche_compra_id' => isset($validated['creche_compra_id']) && $validated['creche_compra_id'] > 0 ? (int) $validated['creche_compra_id'] : null,
            'valor_compra' => isset($validated['valor_compra']) ? (float) $validated['valor_compra'] : null,
            'valor_unitario' => isset($validated['valor_unitario']) ? (float) $validated['valor_unitario'] : null,
            'fornecedor_id' => isset($validated['fornecedor_id']) && $validated['fornecedor_id'] > 0 ? (int) $validated['fornecedor_id'] : null,
            'nota_fiscal' => $validated['nota_fiscal'] ?? null,
            'observacoes' => $request->input('observacoes') === null ? null : trim((string) $request->input('observacoes')),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('terminacao_entradas', 'usuario_id') && auth()->check()) {
            $insert['usuario_id'] = auth()->id();
        }

        DB::table('terminacao_entradas')->insert($insert);

        return redirect()->route('terminacao')->with('success', 'Entrada registrada com sucesso!');
    }

    public function storeMorte(Request $request)
    {
        $validated = $request->validate([
            'data_morte' => ['required', 'string', 'max:30'],
            'lote_id' => ['required', 'integer', 'min:1'],
            'localizacao' => ['nullable', 'string', 'max:120'],
            'baia' => ['nullable', 'string', 'max:60'],
            'quantidade' => ['required', 'integer', 'min:1'],
            'causa_id' => ['nullable', 'integer'],
            'causa' => ['nullable', 'string', 'max:255'],
            'origem_identificacao' => ['nullable', 'string', 'max:255'],
            'peso_medio' => ['nullable', 'numeric', 'min:0'],
            'tipo_morte' => ['nullable', 'string', 'max:30'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (!Schema::hasTable('terminacao_mortes')) {
            return back()->withInput()->withErrors(['data_morte' => "Tabela 'terminacao_mortes' não existe."]);
        }
        if (!Schema::hasTable('terminacao_lotes')) {
            return back()->withInput()->withErrors(['lote_id' => "Tabela 'terminacao_lotes' não existe."]);
        }

        $existeLote = DB::table('terminacao_lotes')->where('id', $validated['lote_id'])->exists();
        if (!$existeLote) {
            return back()->withInput()->withErrors(['lote_id' => 'Lote de terminação inválido.']);
        }

        $insert = [
            'data_morte' => $this->parseInputDate($validated['data_morte'] ?? null),
            'lote_id' => (int) $validated['lote_id'],
            'localizacao' => $validated['localizacao'] ?? null,
            'baia' => $validated['baia'] ?? null,
            'quantidade' => (int) $validated['quantidade'],
            'causa_id' => isset($validated['causa_id']) && $validated['causa_id'] > 0 ? (int) $validated['causa_id'] : null,
            'causa' => $validated['causa'] ?? null,
            'origem_identificacao' => $validated['origem_identificacao'] ?? null,
            'peso_medio' => isset($validated['peso_medio']) ? (float) $validated['peso_medio'] : null,
            'tipo_morte' => $validated['tipo_morte'] ?? 'natural',
            'observacoes' => $request->input('observacoes') === null ? null : trim((string) $request->input('observacoes')),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('terminacao_mortes', 'usuario_id') && auth()->check()) {
            $insert['usuario_id'] = auth()->id();
        }

        DB::table('terminacao_mortes')->insert($insert);

        return redirect()->route('terminacao')->with('success', 'Morte registrada com sucesso!');
    }

    public function storeTransferencia(Request $request)
    {
        $validated = $request->validate([
            'data_transferencia' => ['required', 'string', 'max:30'],
            'lote_origem_id' => ['required', 'integer', 'min:1'],
            'lote_destino_id' => ['nullable', 'integer', 'min:1'],
            'localizacao_origem' => ['nullable', 'string', 'max:120'],
            'baia_origem' => ['nullable', 'string', 'max:60'],
            'localizacao_destino' => ['nullable', 'string', 'max:120'],
            'baia_destino' => ['nullable', 'string', 'max:60'],
            'quantidade' => ['required', 'integer', 'min:1'],
            'peso_total' => ['nullable', 'numeric', 'min:0'],
            'peso_medio' => ['nullable', 'numeric', 'min:0'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'tipo' => ['nullable', 'string', 'max:30'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (!Schema::hasTable('terminacao_transferencias')) {
            return back()->withInput()->withErrors(['data_transferencia' => "Tabela 'terminacao_transferencias' não existe."]);
        }

        $insert = [
            'data_transferencia' => $this->parseInputDate($validated['data_transferencia'] ?? null),
            'lote_origem_id' => (int) $validated['lote_origem_id'],
            'lote_destino_id' => isset($validated['lote_destino_id']) && $validated['lote_destino_id'] > 0 ? (int) $validated['lote_destino_id'] : null,
            'localizacao_origem' => $validated['localizacao_origem'] ?? null,
            'baia_origem' => $validated['baia_origem'] ?? null,
            'localizacao_destino' => $validated['localizacao_destino'] ?? null,
            'baia_destino' => $validated['baia_destino'] ?? null,
            'quantidade' => (int) $validated['quantidade'],
            'peso_total' => isset($validated['peso_total']) ? (float) $validated['peso_total'] : null,
            'peso_medio' => isset($validated['peso_medio']) ? (float) $validated['peso_medio'] : null,
            'motivo' => $validated['motivo'] ?? null,
            'tipo' => $validated['tipo'] ?? 'baia',
            'observacoes' => $request->input('observacoes') === null ? null : trim((string) $request->input('observacoes')),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('terminacao_transferencias', 'usuario_id') && auth()->check()) {
            $insert['usuario_id'] = auth()->id();
        }

        DB::table('terminacao_transferencias')->insert($insert);

        return redirect()->route('terminacao')->with('success', 'Transferência registrada com sucesso!');
    }

    public function storeVenda(Request $request)
    {
        $validated = $request->validate([
            'data_venda' => ['required', 'string', 'max:30'],
            'lote_id' => ['required', 'integer', 'min:1'],
            'localizacao' => ['nullable', 'string', 'max:120'],
            'quantidade' => ['required', 'integer', 'min:1'],
            'peso_total_kg' => ['nullable', 'numeric', 'min:0'],
            'peso_medio_kg' => ['nullable', 'numeric', 'min:0'],
            'peso_frigorifico_kg' => ['nullable', 'numeric', 'min:0'],
            'rendimento_carcaca_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'valor_unitario' => ['nullable', 'numeric', 'min:0'],
            'valor_total' => ['nullable', 'numeric', 'min:0'],
            'comprador_id' => ['nullable', 'integer'],
            'frigorifico_nome' => ['nullable', 'string', 'max:200'],
            'motorista_nome' => ['nullable', 'string', 'max:200'],
            'placa_caminhao' => ['nullable', 'string', 'max:20'],
            'nota_fiscal_saida' => ['nullable', 'string', 'max:120'],
            'chave_nfe' => ['nullable', 'string', 'max:80'],
            'tipo_saida' => ['nullable', 'string', 'max:30'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (!Schema::hasTable('terminacao_vendas')) {
            return back()->withInput()->withErrors(['data_venda' => "Tabela 'terminacao_vendas' não existe."]);
        }

        $insert = [
            'data_venda' => $this->parseInputDate($validated['data_venda'] ?? null),
            'lote_id' => (int) $validated['lote_id'],
            'localizacao' => $validated['localizacao'] ?? null,
            'quantidade' => (int) $validated['quantidade'],
            'peso_total_kg' => isset($validated['peso_total_kg']) ? (float) $validated['peso_total_kg'] : null,
            'peso_medio_kg' => isset($validated['peso_medio_kg']) ? (float) $validated['peso_medio_kg'] : null,
            'peso_frigorifico_kg' => isset($validated['peso_frigorifico_kg']) ? (float) $validated['peso_frigorifico_kg'] : null,
            'rendimento_carcaca_pct' => isset($validated['rendimento_carcaca_pct']) ? (float) $validated['rendimento_carcaca_pct'] : null,
            'valor_unitario' => isset($validated['valor_unitario']) ? (float) $validated['valor_unitario'] : null,
            'valor_total' => isset($validated['valor_total']) ? (float) $validated['valor_total'] : null,
            'comprador_id' => isset($validated['comprador_id']) && $validated['comprador_id'] > 0 ? (int) $validated['comprador_id'] : null,
            'frigorifico_nome' => $validated['frigorifico_nome'] ?? null,
            'motorista_nome' => $validated['motorista_nome'] ?? null,
            'placa_caminhao' => $validated['placa_caminhao'] ?? null,
            'nota_fiscal_saida' => $validated['nota_fiscal_saida'] ?? null,
            'chave_nfe' => $validated['chave_nfe'] ?? null,
            'tipo_saida' => $validated['tipo_saida'] ?? 'abate',
            'observacoes' => $request->input('observacoes') === null ? null : trim((string) $request->input('observacoes')),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('terminacao_vendas', 'usuario_id') && auth()->check()) {
            $insert['usuario_id'] = auth()->id();
        }

        DB::table('terminacao_vendas')->insert($insert);

        return redirect()->route('terminacao')->with('success', 'Venda / Saída para abate registrada com sucesso!');
    }

    public function storePeso(Request $request)
    {
        $validated = $request->validate([
            'data_pesagem' => ['required', 'string', 'max:30'],
            'lote_id' => ['required', 'integer', 'min:1'],
            'localizacao' => ['nullable', 'string', 'max:120'],
            'baia' => ['nullable', 'string', 'max:60'],
            'quantidade_amostra' => ['nullable', 'integer', 'min:1'],
            'quantidade_lote' => ['nullable', 'integer', 'min:0'],
            'peso_total_kg' => ['nullable', 'numeric', 'min:0'],
            'peso_medio_kg' => ['required', 'numeric', 'min:0'],
            'peso_minimo_kg' => ['nullable', 'numeric', 'min:0'],
            'peso_maximo_kg' => ['nullable', 'numeric', 'min:0'],
            'desvio_padrao' => ['nullable', 'numeric', 'min:0'],
            'idade_dias' => ['nullable', 'integer', 'min:0'],
            'gpd_medio' => ['nullable', 'numeric', 'min:0'],
            'tipo_pesagem' => ['nullable', 'string', 'max:30'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (!Schema::hasTable('terminacao_pesos')) {
            return back()->withInput()->withErrors(['data_pesagem' => "Tabela 'terminacao_pesos' não existe."]);
        }

        $insert = [
            'data_pesagem' => $this->parseInputDate($validated['data_pesagem'] ?? null),
            'lote_id' => (int) $validated['lote_id'],
            'localizacao' => $validated['localizacao'] ?? null,
            'baia' => $validated['baia'] ?? null,
            'quantidade_amostra' => $validated['quantidade_amostra'] ?? null,
            'quantidade_lote' => $validated['quantidade_lote'] ?? null,
            'peso_total_kg' => isset($validated['peso_total_kg']) ? (float) $validated['peso_total_kg'] : null,
            'peso_medio_kg' => (float) $validated['peso_medio_kg'],
            'peso_minimo_kg' => isset($validated['peso_minimo_kg']) ? (float) $validated['peso_minimo_kg'] : null,
            'peso_maximo_kg' => isset($validated['peso_maximo_kg']) ? (float) $validated['peso_maximo_kg'] : null,
            'desvio_padrao' => isset($validated['desvio_padrao']) ? (float) $validated['desvio_padrao'] : null,
            'idade_dias' => $validated['idade_dias'] ?? null,
            'gpd_medio' => isset($validated['gpd_medio']) ? (float) $validated['gpd_medio'] : null,
            'tipo_pesagem' => $validated['tipo_pesagem'] ?? 'amostra',
            'observacoes' => $request->input('observacoes') === null ? null : trim((string) $request->input('observacoes')),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('terminacao_pesos', 'usuario_id') && auth()->check()) {
            $insert['usuario_id'] = auth()->id();
        }

        DB::table('terminacao_pesos')->insert($insert);

        return redirect()->route('terminacao')->with('success', 'Pesagem registrada com sucesso!');
    }

    public function showLote(int $id)
    {
        if (!Schema::hasTable('terminacao_lotes')) {
            abort(404);
        }

        $lote = DB::table('terminacao_lotes')->where('id', $id)->first();
        if (!$lote) {
            abort(404);
        }

        $lotes = DB::table('terminacao_lotes')
            ->orderBy('nome')
            ->get(['id', 'nome'])
            ->map(fn ($r) => ['id' => (int) $r->id, 'nome' => (string) $r->nome])
            ->all();

        $saldoInfo = TerminacaoService::calcularSaldoLote($id);
        $dataEntrada = $lote->data_entrada ? Carbon::parse($lote->data_entrada)->startOfDay() : null;
        $diasNaFase = $dataEntrada ? TerminacaoService::calcularDiasAlojamento($dataEntrada) : 0;
        $metaDias = (int) ($lote->meta_dias_terminacao ?: TerminacaoService::getMetaInt('meta_terminacao_dias_permanencia', 90));
        $metaPesoAbate = (float) ($lote->meta_peso_abate_kg ?: TerminacaoService::getMetaFloat('meta_terminacao_peso_abate_kg', 115.00));
        $previsaoFechamento = $dataEntrada ? $dataEntrada->copy()->addDays($metaDias) : null;
        $progressoPct = $metaDias > 0 ? min(100, (int) round(($diasNaFase / $metaDias) * 100)) : 0;

        $movimentacoes = [];

        if (Schema::hasTable('terminacao_entradas')) {
            $entradas = DB::table('terminacao_entradas')->where('lote_id', $id)->get();
            $movimentacoes = array_merge($movimentacoes, $entradas->map(function ($e) {
                $data = empty($e->data_entrada) ? null : Carbon::parse($e->data_entrada)->startOfDay();
                $desc = 'Entrada';
                if (!empty($e->origem)) {
                    $desc .= ' (' . $e->origem . ')';
                }
                if (!empty($e->nota_fiscal)) {
                    $desc .= ' - NF: ' . $e->nota_fiscal;
                }
                return [
                    'data_raw' => $data?->toDateString(),
                    'data' => PigCycleService::formatDisplayDate($data),
                    'tipo' => 'entrada',
                    'tipo_label' => 'Entrada',
                    'quantidade' => (int) ($e->quantidade ?? 0),
                    'peso_total' => $e->peso_total === null ? null : (float) $e->peso_total,
                    'localizacao' => trim(implode(' - ', array_filter([(string) ($e->localizacao ?? ''), (string) ($e->baia ?? '')]))),
                    'descricao' => $desc,
                ];
            })->all());
        }

        if (Schema::hasTable('terminacao_mortes')) {
            $mortes = DB::table('terminacao_mortes')->where('lote_id', $id)->get();
            $movimentacoes = array_merge($movimentacoes, $mortes->map(function ($m) {
                $data = empty($m->data_morte) ? null : Carbon::parse($m->data_morte)->startOfDay();
                $desc = 'Morte';
                if (!empty($m->causa)) {
                    $desc .= ': ' . $m->causa;
                }
                return [
                    'data_raw' => $data?->toDateString(),
                    'data' => PigCycleService::formatDisplayDate($data),
                    'tipo' => 'saida',
                    'tipo_label' => 'Morte',
                    'quantidade' => (int) ($m->quantidade ?? 0),
                    'peso_total' => null,
                    'localizacao' => trim(implode(' - ', array_filter([(string) ($m->localizacao ?? ''), (string) ($m->baia ?? '')]))),
                    'descricao' => $desc,
                ];
            })->all());
        }

        if (Schema::hasTable('terminacao_transferencias')) {
            $transfOut = DB::table('terminacao_transferencias')->where('lote_origem_id', $id)->get();
            $transfIn = DB::table('terminacao_transferencias')->where('lote_destino_id', $id)->get();

            $movimentacoes = array_merge($movimentacoes, $transfOut->map(function ($t) {
                $data = empty($t->data_transferencia) ? null : Carbon::parse($t->data_transferencia)->startOfDay();
                $dest = $t->lote_destino_id ? ('Lote #' . $t->lote_destino_id) : 'Baia ' . ($t->baia_destino ?: $t->localizacao_destino ?: '-');
                return [
                    'data_raw' => $data?->toDateString(),
                    'data' => PigCycleService::formatDisplayDate($data),
                    'tipo' => 'saida',
                    'tipo_label' => 'Transf. Saída',
                    'quantidade' => (int) ($t->quantidade ?? 0),
                    'peso_total' => null,
                    'localizacao' => trim(implode(' - ', array_filter([(string) ($t->localizacao_origem ?? ''), (string) ($t->baia_origem ?? '')]))),
                    'descricao' => "Transferência para {$dest}",
                ];
            })->all());

            $movimentacoes = array_merge($movimentacoes, $transfIn->map(function ($t) {
                $data = empty($t->data_transferencia) ? null : Carbon::parse($t->data_transferencia)->startOfDay();
                $orig = 'Lote #' . $t->lote_origem_id;
                return [
                    'data_raw' => $data?->toDateString(),
                    'data' => PigCycleService::formatDisplayDate($data),
                    'tipo' => 'entrada',
                    'tipo_label' => 'Transf. Entrada',
                    'quantidade' => (int) ($t->quantidade ?? 0),
                    'peso_total' => null,
                    'localizacao' => trim(implode(' - ', array_filter([(string) ($t->localizacao_destino ?? ''), (string) ($t->baia_destino ?? '')]))),
                    'descricao' => "Transferência de {$orig}",
                ];
            })->all());
        }

        if (Schema::hasTable('terminacao_vendas')) {
            $vendas = DB::table('terminacao_vendas')->where('lote_id', $id)->get();
            $movimentacoes = array_merge($movimentacoes, $vendas->map(function ($v) {
                $data = empty($v->data_venda) ? null : Carbon::parse($v->data_venda)->startOfDay();
                $desc = 'Venda / Abate';
                if (!empty($v->frigorifico_nome)) {
                    $desc .= ' - ' . $v->frigorifico_nome;
                }
                if (!empty($v->nota_fiscal_saida)) {
                    $desc .= ' NF: ' . $v->nota_fiscal_saida;
                }
                return [
                    'data_raw' => $data?->toDateString(),
                    'data' => PigCycleService::formatDisplayDate($data),
                    'tipo' => 'saida',
                    'tipo_label' => 'Venda',
                    'quantidade' => (int) ($v->quantidade ?? 0),
                    'peso_total' => $v->peso_total_kg === null ? null : (float) $v->peso_total_kg,
                    'localizacao' => (string) ($v->localizacao ?? ''),
                    'descricao' => $desc,
                ];
            })->all());
        }

        if (Schema::hasTable('terminacao_pesos')) {
            $pesos = DB::table('terminacao_pesos')->where('lote_id', $id)->get();
            $movimentacoes = array_merge($movimentacoes, $pesos->map(function ($p) {
                $data = empty($p->data_pesagem) ? null : Carbon::parse($p->data_pesagem)->startOfDay();
                $desc = "Pesagem: {$p->peso_medio_kg}kg médio";
                if (!empty($p->gpd_medio)) {
                    $desc .= " | GPD: {$p->gpd_medio}g/dia";
                }
                return [
                    'data_raw' => $data?->toDateString(),
                    'data' => PigCycleService::formatDisplayDate($data),
                    'tipo' => 'info',
                    'tipo_label' => 'Pesagem',
                    'quantidade' => 0,
                    'peso_total' => $p->peso_total_kg === null ? null : (float) $p->peso_total_kg,
                    'localizacao' => trim(implode(' - ', array_filter([(string) ($p->localizacao ?? ''), (string) ($p->baia ?? '')]))),
                    'descricao' => $desc,
                    'extra' => [
                        'peso_medio_kg' => (float) $p->peso_medio_kg,
                        'gpd_medio' => $p->gpd_medio === null ? null : (float) $p->gpd_medio,
                    ],
                ];
            })->all());
        }

        usort($movimentacoes, function ($a, $b) {
            $da = (string) ($a['data_raw'] ?? '');
            $db = (string) ($b['data_raw'] ?? '');
            if ($da !== $db) {
                return strcmp($da, $db);
            }
            $ordem = ['entrada' => 0, 'info' => 1, 'saida' => 2];
            $oa = $ordem[$a['tipo'] ?? 'info'] ?? 1;
            $ob = $ordem[$b['tipo'] ?? 'info'] ?? 1;
            return $oa <=> $ob;
        });

        $saldoCalc = 0;
        foreach ($movimentacoes as $i => $mov) {
            $q = (int) ($mov['quantidade'] ?? 0);
            if ($mov['tipo'] === 'entrada') {
                $saldoCalc += $q;
            } elseif ($mov['tipo'] === 'saida') {
                $saldoCalc -= $q;
            }
            $movimentacoes[$i]['saldo'] = max(0, $saldoCalc);
        }

        $pesosHistorico = [];
        if (Schema::hasTable('terminacao_pesos')) {
            $pesosHistorico = DB::table('terminacao_pesos')
                ->where('lote_id', $id)
                ->orderBy('data_pesagem')
                ->get(['data_pesagem', 'peso_medio_kg', 'gpd_medio'])
                ->map(fn ($p) => [
                    'data' => empty($p->data_pesagem) ? null : Carbon::parse($p->data_pesagem)->startOfDay(),
                    'peso_medio_kg' => (float) $p->peso_medio_kg,
                    'gpd_medio' => $p->gpd_medio === null ? null : (float) $p->gpd_medio,
                ])->all();
        }

        $ultPeso = TerminacaoService::ultimaPesagem($id);

        return view('terminacao.lote', [
            'lote' => [
                'id' => (int) $lote->id,
                'nome' => (string) $lote->nome,
                'caracteristicas' => (string) ($lote->caracteristicas ?? ''),
                'situacao' => (string) ($lote->situacao ?? ''),
                'origem' => (string) ($lote->origem ?? ''),
                'galpao' => (string) ($lote->galpao ?? ''),
                'localizacao' => (string) ($lote->localizacao ?? ''),
                'observacoes' => (string) ($lote->observacoes ?? ''),
                'data_fechamento' => $lote->data_fechamento ? Carbon::parse($lote->data_fechamento) : null,
            ],
            'lotes' => $lotes,
            'resumo' => [
                'data_abertura' => $dataEntrada ? PigCycleService::formatDisplayDate($dataEntrada) : '-',
                'data_abertura_raw' => $dataEntrada?->toDateString(),
                'previsao_fechamento' => $previsaoFechamento ? PigCycleService::formatDisplayDate($previsaoFechamento) : '-',
                'previsao_fechamento_raw' => $previsaoFechamento?->toDateString(),
                'localizacao' => trim(implode(' - ', array_filter([(string) ($lote->galpao ?? ''), (string) ($lote->localizacao ?? '')]))) ?: '-',
                'saldo_animais' => $saldoInfo['saldo'],
                'progresso_pct' => $progressoPct,
            ],
            'metricas' => [
                'entrada' => $saldoInfo['total_entradas'],
                'saida_mortes' => $saldoInfo['mortes'],
                'saida_transferencias' => $saldoInfo['transferencias_saida'],
                'saida_vendas' => $saldoInfo['vendas'],
                'saida_total' => $saldoInfo['total_saidas'],
                'mortalidade_pct' => $saldoInfo['mortalidade_pct'],
                'dias_na_fase' => $diasNaFase,
                'meta_dias_na_fase' => $metaDias,
                'idade_media_entrada' => TerminacaoService::calcularIdadeMedia($id),
                'peso_medio_entrada' => TerminacaoService::calcularPesoMedioEntrada($id),
                'ultimo_peso_kg' => $ultPeso['peso_medio_kg'] ?? null,
                'gpd_medio' => $ultPeso['gpd_medio'] ?? null,
                'meta_peso_abate_kg' => $metaPesoAbate,
                'peso_proj_saida' => null,
            ],
            'movimentacoes' => $movimentacoes,
            'pesos_historico' => $pesosHistorico,
        ]);
    }

    public function fecharLote(Request $request, int $id)
    {
        if (!Schema::hasTable('terminacao_lotes')) {
            return back()->withErrors(['erro' => "Tabela 'terminacao_lotes' não existe."]);
        }

        $lote = DB::table('terminacao_lotes')->where('id', $id)->first();
        if (!$lote) {
            abort(404);
        }

        DB::table('terminacao_lotes')->where('id', $id)->update([
            'situacao' => 'fechado',
            'data_fechamento' => now()->format('Y-m-d'),
            'updated_at' => now(),
        ]);

        return redirect()->route('terminacao')->with('success', 'Lote fechado com sucesso!');
    }

    public function transferirDaCreche(Request $request)
    {
        $validated = $request->validate([
            'creche_lote_id' => ['required', 'integer', 'min:1'],
            'terminacao_lote_id' => ['nullable', 'integer', 'min:1'],
            'data_entrada' => ['required', 'string', 'max:30'],
            'quantidade' => ['required', 'integer', 'min:1'],
            'peso_total' => ['nullable', 'numeric', 'min:0'],
            'peso_medio' => ['nullable', 'numeric', 'min:0'],
            'localizacao' => ['nullable', 'string', 'max:120'],
            'baia' => ['nullable', 'string', 'max:60'],
            'novo_lote_nome' => ['nullable', 'string', 'max:120'],
            'data_nascimento' => ['nullable', 'string', 'max:30'],
        ]);

        if (!Schema::hasTable('creche_lotes') || !Schema::hasTable('terminacao_entradas') || !Schema::hasTable('terminacao_lotes')) {
            return back()->withInput()->withErrors(['erro' => 'Tabelas necessárias não existem no banco. Rode o SQL da creche e terminação.']);
        }

        $crecheLote = DB::table('creche_lotes')->where('id', $validated['creche_lote_id'])->first();
        if (!$crecheLote) {
            return back()->withInput()->withErrors(['creche_lote_id' => 'Lote da creche não encontrado.']);
        }

        $terminacaoLoteId = (int) ($validated['terminacao_lote_id'] ?? 0);
        if ($terminacaoLoteId <= 0 && !empty($validated['novo_lote_nome'])) {
            $novoNome = trim((string) $validated['novo_lote_nome']);
            $existe = DB::table('terminacao_lotes')->where('nome', $novoNome)->exists();
            if ($existe) {
                return back()->withInput()->withErrors(['novo_lote_nome' => 'Já existe lote de terminação com este nome.']);
            }
            $terminacaoLoteId = (int) DB::table('terminacao_lotes')->insertGetId([
                'nome' => $novoNome,
                'situacao' => 'aberto',
                'data_entrada' => $this->parseInputDate($validated['data_entrada'] ?? null),
                'quantidade_inicial' => (int) $validated['quantidade'],
                'origem' => 'creche',
                'creche_lote_id' => (int) $validated['creche_lote_id'],
                'galpao' => $validated['localizacao'] ?? null,
                'localizacao' => $validated['baia'] ?? null,
                'meta_dias_terminacao' => TerminacaoService::getMetaInt('meta_terminacao_dias_permanencia', 90),
                'meta_peso_abate_kg' => TerminacaoService::getMetaFloat('meta_terminacao_peso_abate_kg', 115.00),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($terminacaoLoteId <= 0) {
            return back()->withInput()->withErrors(['terminacao_lote_id' => 'Selecione um lote de terminação ou informe um nome para criar novo.']);
        }

        $dataNascimento = $validated['data_nascimento'] ?? null;
        if (!$dataNascimento && Schema::hasTable('creche_compras')) {
            $avg = DB::table('creche_compras')
                ->where('lote_id', $validated['creche_lote_id'])
                ->whereNotNull('data_nascimento')
                ->selectRaw('AVG(DATEDIFF(CURDATE(), data_nascimento)) as idade_media')
                ->value('idade_media');
            if ($avg) {
                $dataNascimento = Carbon::today()->subDays((int) $avg)->format('Y-m-d');
            }
        }

        $pesoTotal = isset($validated['peso_total']) ? (float) $validated['peso_total'] : null;
        $pesoMedio = isset($validated['peso_medio']) ? (float) $validated['peso_medio'] : null;
        if ($pesoTotal === null && $pesoMedio !== null) {
            $pesoTotal = round($pesoMedio * (int) $validated['quantidade'], 2);
        }
        if ($pesoMedio === null && $pesoTotal !== null && (int) $validated['quantidade'] > 0) {
            $pesoMedio = round($pesoTotal / (int) $validated['quantidade'], 2);
        }

        DB::table('terminacao_entradas')->insert([
            'data_entrada' => $this->parseInputDate($validated['data_entrada'] ?? null),
            'lote_id' => $terminacaoLoteId,
            'localizacao' => $validated['localizacao'] ?? null,
            'baia' => $validated['baia'] ?? null,
            'quantidade' => (int) $validated['quantidade'],
            'peso_total' => $pesoTotal,
            'peso_medio' => $pesoMedio,
            'data_nascimento' => $dataNascimento ? ($this->parseInputDate($dataNascimento) ?? $dataNascimento) : null,
            'origem' => 'creche',
            'creche_lote_id' => (int) $validated['creche_lote_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('terminacao')->with('success', 'Transferência Creche ? Terminação realizada com sucesso!');
    }
}
