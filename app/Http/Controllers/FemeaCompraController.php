<?php

namespace App\Http\Controllers;

use App\Models\Femea;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FemeaCompraController extends Controller
{
    public function index(Request $request)
    {
        if (! Schema::hasTable('femea') || ! Schema::hasTable('femea_movimento')) {
            return response()->json([
                'items' => [],
                'message' => 'Tabelas do plantel ainda não foram criadas no banco.',
            ]);
        }

        $limit = max(1, min(5000, (int) $request->query('limit', 200)));

        $query = DB::table('femea_movimento as fm')
            ->join('femea as f', 'f.id', '=', 'fm.femea_id')
            ->leftJoin('raca as r', 'r.id', '=', 'f.raca_id')
            ->leftJoin('fornecedor as fo', 'fo.id', '=', 'f.fornecedor_id')
            ->where('fm.acao', 'compra')
            ->orderByDesc('fm.data')
            ->select([
                'fm.id',
                'fm.data',
                'f.tipo_compra',
                'f.id_primaria',
                'f.id_secundaria',
                'r.nome as raca_nome',
                'f.ciclos_ate_compra',
                'f.data_nascimento',
                'fo.nome as fornecedor_nome',
                'f.peso_compra',
                'f.valor_compra',
            ]);

        if ($request->filled('tipo_compra')) {
            $query->where('f.tipo_compra', $request->tipo_compra);
        }

        $rows = $query->limit($limit)->get();

        $items = $rows->map(function ($row) {
            $idadeDias = null;
            if (! empty($row->data_nascimento)) {
                $idadeDias = Carbon::parse($row->data_nascimento)->diffInDays(Carbon::parse($row->data));
            }

            return [
                'id' => $row->id,
                'acao' => 'compra',
                'data' => Carbon::parse($row->data)->format('d/m/Y'),
                'tipo' => $row->tipo_compra,
                'id_primaria' => $row->id_primaria,
                'id_secundaria' => $row->id_secundaria,
                'raca' => $row->raca_nome,
                'ciclo' => $row->ciclos_ate_compra,
                'idade_dias' => $idadeDias,
                'fornecedor' => $row->fornecedor_nome,
                'peso' => $row->peso_compra,
                'valor' => $row->valor_compra,
            ];
        })->values();

        return response()->json([
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('femea') || ! Schema::hasTable('femea_movimento')) {
            return response()->json([
                'message' => 'Tabelas do plantel ainda não foram criadas no banco.',
            ], 422);
        }

        $validated = $request->validate([
            'tipo_compra' => ['required', 'in:leitoa,matriz_vazia,matriz_gestante'],
            'id_primaria' => ['required', 'string', 'max:50', 'unique:femea,id_primaria'],
            'id_secundaria' => ['nullable', 'string', 'max:50', 'unique:femea,id_secundaria'],
            'data_compra' => ['required', 'date'],
            'data_nascimento' => ['nullable', 'date'],
            'ciclos_ate_compra' => ['nullable', 'integer', 'min:0'],
            'data_cobertura' => ['nullable', 'date'],
            'raca_id' => ['required', 'exists:raca,id'],
            'valor_compra' => ['nullable', 'numeric', 'min:0'],
            'peso_compra' => ['nullable', 'numeric', 'min:0'],
            'fornecedor_id' => ['nullable', 'exists:fornecedor,id'],
            'caracteristicas' => ['nullable', 'string'],
            'localizacao' => ['nullable', 'string', 'max:120'],
            'baia' => ['nullable', 'string', 'max:60'],
        ]);

        if ($validated['tipo_compra'] === 'matriz_gestante' && empty($validated['data_cobertura'])) {
            return response()->json([
                'message' => 'Data de cobertura é obrigatória para matriz gestante.',
            ], 422);
        }

        if (($validated['tipo_compra'] === 'matriz_vazia' || $validated['tipo_compra'] === 'matriz_gestante') && $validated['ciclos_ate_compra'] === null) {
            return response()->json([
                'message' => 'Ciclos até a compra é obrigatório para matriz vazia e gestante.',
            ], 422);
        }

        if (empty($validated['data_nascimento']) && isset($validated['ciclos_ate_compra'])) {
            $dias = (int) $validated['ciclos_ate_compra'] * 21;
            $validated['data_nascimento'] = Carbon::parse($validated['data_compra'])->subDays($dias)->format('Y-m-d');
        }

        $result = DB::transaction(function () use ($validated) {
            $femea = Femea::create($validated);

            DB::table('femea_movimento')->insert([
                'femea_id' => $femea->id,
                'acao' => 'compra',
                'data' => $femea->data_compra,
                'valor' => $femea->valor_compra,
                'peso' => $femea->peso_compra,
                'fornecedor_id' => $femea->fornecedor_id,
                'criado_em' => now(),
                'atualizado_em' => now(),
            ]);

            return $femea;
        });

        return response()->json([
            'message' => 'Compra registrada com sucesso!',
            'id' => $result->id,
        ], 201);
    }
}
