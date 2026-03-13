<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GestacaoSaltaCioController extends Controller
{
    public function index(Request $request)
    {
        if (! Schema::hasTable('gestacao_salta_cio')) {
            return response()->json([
                'items' => [],
                'message' => 'Tabela gestacao_salta_cio não existe no banco.',
            ]);
        }

        $limit = max(1, min(200, (int) $request->query('limit', 50)));

        $items = DB::table('gestacao_salta_cio as gsc')
            ->join('femea as f', 'f.id', '=', 'gsc.femea_id')
            ->orderByDesc('gsc.data')
            ->select([
                'gsc.id',
                'gsc.data',
                'f.id_primaria',
                'f.id_secundaria',
            ])
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'matriz' => (string) $row->id_primaria,
                    'matriz_secundaria' => $row->id_secundaria === null ? null : (string) $row->id_secundaria,
                    'data' => Carbon::parse($row->data)->format('d/m/Y'),
                ];
            })
            ->values();

        return response()->json([
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('gestacao_salta_cio') || ! Schema::hasTable('femea')) {
            return response()->json([
                'message' => 'Tabelas de gestação ainda não foram criadas no banco.',
            ], 422);
        }

        $validated = $request->validate([
            'femea_id' => ['required', 'exists:femea,id'],
            'data' => ['required', 'date'],
        ]);

        DB::table('gestacao_salta_cio')->insert([
            'femea_id' => (int) $validated['femea_id'],
            'data' => Carbon::parse($validated['data'])->toDateString(),
            'criado_em' => now(),
            'atualizado_em' => now(),
        ]);

        return response()->json([
            'message' => 'Salta cio registrado com sucesso!',
        ], 201);
    }
}
