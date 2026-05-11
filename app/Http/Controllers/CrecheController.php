<?php

namespace App\Http\Controllers;

use App\Services\PigCycleService;
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
            $estoque = (int) (DB::table('creche_compras')->sum('quantidade') ?? 0);
            $stats['estoque_animais'] = max(0, $estoque);
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
            $rows = DB::table('creche_lotes as l')
                ->leftJoin('creche_compras as c', 'c.lote_id', '=', 'l.id')
                ->where('l.situacao', 'aberto')
                ->groupBy('l.id', 'l.nome')
                ->orderBy('l.nome')
                ->get([
                    'l.id',
                    'l.nome',
                    DB::raw('MIN(c.data_compra) as data_abertura'),
                    DB::raw('COALESCE(SUM(c.quantidade), 0) as quantidade'),
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
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'caracteristicas' => ['nullable', 'string', 'max:1000'],
        ]);

        if (!Schema::hasTable('creche_lotes')) {
            return back()
                ->withInput()
                ->withErrors(['nome' => "Tabela 'creche_lotes' não existe."]);
        }

        DB::table('creche_lotes')->insert([
            'nome' => $validated['nome'],
            'caracteristicas' => $validated['caracteristicas'] ?? null,
            'situacao' => 'aberto',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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
}
