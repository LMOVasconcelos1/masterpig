<?php

namespace App\Http\Controllers;

use App\Models\Causa;
use App\Models\Macho;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MachoMorteController extends Controller
{
    public function store(Request $request)
    {
        if (! Schema::hasTable('macho') || ! Schema::hasTable('macho_movimento')) {
            return response()->json([
                'message' => 'Tabelas de machos ainda não foram criadas no banco.',
            ], 422);
        }

        $validated = $request->validate([
            'macho_id' => ['required', 'exists:macho,id'],
            'data_morte' => ['required', 'date'],
            'causa_id' => ['required', 'exists:causa,id'],
        ]);

        $macho = Macho::findOrFail($validated['macho_id']);

        $lastAcao = DB::table('macho_movimento')
            ->where('macho_id', $macho->id)
            ->orderByDesc('id')
            ->value('acao');

        if (is_string($lastAcao) && in_array($lastAcao, ['morte', 'descarte', 'venda'], true)) {
            return response()->json([
                'message' => 'O macho já está inativo e não pode receber novo lançamento.',
            ], 422);
        }

        $causa = Causa::with('grupoCausa')->findOrFail($validated['causa_id']);

        $grupoNome = mb_strtolower($causa->grupoCausa?->nome ?? '');
        if (! str_contains($grupoNome, 'morte')) {
            return response()->json([
                'message' => 'Selecione uma causa do tipo morte.',
            ], 422);
        }

        $payload = [
            'macho_id' => $macho->id,
            'acao' => 'morte',
            'data' => Carbon::parse($validated['data_morte'])->format('Y-m-d'),
            'valor' => null,
            'peso' => null,
            'fornecedor_id' => null,
            'observacoes' => $causa->nome,
        ];

        if (Schema::hasColumn('macho_movimento', 'causa_id')) {
            $payload['causa_id'] = $causa->id;
        }

        DB::table('macho_movimento')->insert($payload);

        return response()->json([
            'message' => 'Morte registrada com sucesso!',
        ], 201);
    }
}
