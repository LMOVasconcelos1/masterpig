<?php

namespace App\Http\Controllers;

use App\Models\Causa;
use App\Models\Macho;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MachoVendaController extends Controller
{
    public function store(Request $request)
    {
        if (!Schema::hasTable('macho') || !Schema::hasTable('macho_movimento')) {
            return response()->json([
                'message' => 'Tabelas de machos ainda não foram criadas no banco.',
            ], 422);
        }

        $validated = $request->validate([
            'macho_id' => ['required', 'exists:macho,id'],
            'data_venda' => ['required', 'date'],
            'causa_id' => ['required', 'exists:causa,id'],
            'valor_venda' => ['nullable', 'numeric', 'min:0'],
            'peso_venda' => ['nullable', 'numeric', 'min:0'],
            'comprador' => ['nullable', 'string', 'max:255'],
        ]);

        $macho = Macho::findOrFail($validated['macho_id']);
        $causa = Causa::with('grupoCausa')->findOrFail($validated['causa_id']);

        $grupoNome = mb_strtolower($causa->grupoCausa?->nome ?? '');
        if (!str_contains($grupoNome, 'venda')) {
            return response()->json([
                'message' => 'Selecione uma causa do tipo venda.',
            ], 422);
        }

        $payload = [
            'macho_id' => $macho->id,
            'acao' => 'venda',
            'data' => Carbon::parse($validated['data_venda'])->format('Y-m-d'),
            'valor' => $validated['valor_venda'] ?? null,
            'peso' => $validated['peso_venda'] ?? null,
            'fornecedor_id' => null,
            'observacoes' => $causa->nome,
        ];

        if (Schema::hasColumn('macho_movimento', 'causa_id')) {
            $payload['causa_id'] = $causa->id;
        }

        if (Schema::hasColumn('macho_movimento', 'comprador')) {
            $payload['comprador'] = $validated['comprador'] ?? null;
        } elseif (!empty($validated['comprador'])) {
            $payload['observacoes'] = $payload['observacoes'] . ' | Comprador: ' . $validated['comprador'];
        }

        DB::table('macho_movimento')->insert($payload);

        return response()->json([
            'message' => 'Venda registrada com sucesso!',
        ], 201);
    }
}

