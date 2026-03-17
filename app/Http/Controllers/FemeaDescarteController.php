<?php

namespace App\Http\Controllers;

use App\Models\Causa;
use App\Models\Femea;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FemeaDescarteController extends Controller
{
    public function store(Request $request)
    {
        if (! Schema::hasTable('femea') || ! Schema::hasTable('femea_movimento')) {
            return response()->json([
                'message' => 'Tabelas do plantel ainda não foram criadas no banco.',
            ], 422);
        }

        $validated = $request->validate([
            'femea_id' => ['required', 'exists:femea,id'],
            'data_descarte' => ['required', 'date'],
            'causa_id' => ['required', 'exists:causa,id'],
        ]);

        $femea = Femea::findOrFail($validated['femea_id']);

        $lastAcao = DB::table('femea_movimento')
            ->where('femea_id', $femea->id)
            ->orderByDesc('id')
            ->value('acao');

        if (is_string($lastAcao) && in_array($lastAcao, ['morte', 'descarte', 'venda'], true)) {
            return response()->json([
                'message' => 'A fêmea já está inativa e não pode receber novo lançamento.',
            ], 422);
        }

        $causa = Causa::with('grupoCausa')->findOrFail($validated['causa_id']);

        $grupoNome = mb_strtolower($causa->grupoCausa?->nome ?? '');
        if (! str_contains($grupoNome, 'descarte')) {
            return response()->json([
                'message' => 'Selecione uma causa do tipo descarte.',
            ], 422);
        }

        $payload = [
            'femea_id' => $femea->id,
            'acao' => 'descarte',
            'data' => Carbon::parse($validated['data_descarte'])->format('Y-m-d'),
            'valor' => null,
            'peso' => null,
            'fornecedor_id' => null,
            'observacoes' => $causa->nome,
        ];

        if (Schema::hasColumn('femea_movimento', 'causa_id')) {
            $payload['causa_id'] = $causa->id;
        }

        DB::table('femea_movimento')->insert($payload);

        return response()->json([
            'message' => 'Descarte registrado com sucesso!',
        ], 201);
    }
}
