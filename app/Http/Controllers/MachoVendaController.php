<?php

namespace App\Http\Controllers;

use App\Models\Causa;
use App\Models\Macho;
use App\Services\PigCycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MachoVendaController extends Controller
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
            'data_venda' => ['required', 'string', 'max:30'],
            'causa_id' => ['required', 'exists:causa,id'],
            'valor_venda' => ['nullable', 'numeric', 'min:0'],
            'peso_venda' => ['nullable', 'numeric', 'min:0'],
            'comprador' => ['nullable', 'string', 'max:255'],
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
        if (! str_contains($grupoNome, 'venda')) {
            return response()->json([
                'message' => 'Selecione uma causa do tipo venda.',
            ], 422);
        }

        $payload = [
            'macho_id' => $macho->id,
            'acao' => 'venda',
            'data' => $this->parseInputDate($validated['data_venda']),
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
        } elseif (! empty($validated['comprador'])) {
            $payload['observacoes'] = $payload['observacoes'].' | Comprador: '.$validated['comprador'];
        }

        DB::table('macho_movimento')->insert($payload);

        return response()->json([
            'message' => 'Venda registrada com sucesso!',
        ], 201);
    }

    private function parseInputDate(?string $input): ?string
    {
        if ($input === null || trim($input) === '') return null;
        $carbon = PigCycleService::parseFilterDate($input);
        return $carbon ? $carbon->format('Y-m-d') : null;
    }
}
