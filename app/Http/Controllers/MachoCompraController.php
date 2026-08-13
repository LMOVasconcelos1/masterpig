<?php

namespace App\Http\Controllers;

use App\Models\Macho;
use App\Services\PigCycleService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MachoCompraController extends Controller
{
    public function index(Request $request)
    {
        if (! Schema::hasTable('macho') || ! Schema::hasTable('macho_movimento')) {
            return response()->json([
                'items' => [],
                'message' => 'Tabelas de machos ainda não foram criadas no banco.',
            ]);
        }

        $limit = max(1, min(5000, (int) $request->query('limit', 200)));

        $rows = DB::table('macho_movimento as mm')
            ->join('macho as m', 'm.id', '=', 'mm.macho_id')
            ->leftJoin('raca as r', 'r.id', '=', 'm.raca_id')
            ->leftJoin('fornecedor as fo', 'fo.id', '=', 'm.fornecedor_id')
            ->where('mm.acao', 'compra')
            ->orderByDesc('mm.data')
            ->select([
                'mm.id',
                'mm.data',
                'm.id_primaria',
                'm.id_secundaria',
                'r.nome as raca_nome',
                'm.data_nascimento',
                'fo.nome as fornecedor_nome',
                'm.peso_compra',
                'm.valor_compra',
            ])
            ->limit($limit)
            ->get();

        $items = $rows->map(function ($row) {
            $idadeDias = null;

            if (! empty($row->data_nascimento)) {
                $idadeDias = Carbon::parse($row->data_nascimento)->diffInDays(Carbon::parse($row->data));
            }

            return [
                'id' => $row->id,
                'acao' => 'compra',
                'data' => PigCycleService::formatDisplayDate(Carbon::parse($row->data)),
                'id_primaria' => $row->id_primaria,
                'id_secundaria' => $row->id_secundaria,
                'raca' => $row->raca_nome,
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
        if (! Schema::hasTable('macho') || ! Schema::hasTable('macho_movimento')) {
            return response()->json([
                'message' => 'Tabelas de machos ainda não foram criadas no banco.',
            ], 422);
        }

        $request->merge([
            'id_primaria' => trim((string) $request->input('id_primaria', '')),
            'id_secundaria' => $request->input('id_secundaria') === null ? null : trim((string) $request->input('id_secundaria')),
            'localizacao' => $request->input('localizacao') === null ? null : trim((string) $request->input('localizacao')),
            'baia' => $request->input('baia') === null ? null : trim((string) $request->input('baia')),
            'caracteristicas' => $request->input('caracteristicas') === null ? null : trim((string) $request->input('caracteristicas')),
        ]);

        $validated = $request->validate([
            'id_primaria' => ['required', 'string', 'max:50', 'unique:macho,id_primaria'],
            'id_secundaria' => ['nullable', 'string', 'max:50', 'unique:macho,id_secundaria'],
            'data_compra' => ['required', 'string', 'max:30'],
            'data_nascimento' => ['nullable', 'string', 'max:30'],
            'raca_id' => ['required', 'exists:raca,id'],
            'valor_compra' => ['nullable', 'numeric', 'min:0'],
            'peso_compra' => ['nullable', 'numeric', 'min:0'],
            'fornecedor_id' => ['nullable', 'exists:fornecedor,id'],
            'caracteristicas' => ['nullable', 'string'],
            'localizacao' => ['nullable', 'string', 'max:120'],
            'baia' => ['nullable', 'string', 'max:60'],
        ]);

        $validated['data_compra'] = $this->parseInputDate($validated['data_compra']);
        $validated['data_nascimento'] = $this->parseInputDate($validated['data_nascimento'] ?? null);

        $dataCompra = Carbon::parse($validated['data_compra'])->startOfDay();
        $dataNasc = empty($validated['data_nascimento']) ? null : Carbon::parse($validated['data_nascimento'])->startOfDay();

        if ($dataNasc && $dataNasc->gt($dataCompra)) {
            return response()->json([
                'message' => 'Data de nascimento não pode ser maior que a data de compra.',
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($validated) {
                $macho = Macho::create($validated);

                DB::table('macho_movimento')->insert([
                    'macho_id' => $macho->id,
                    'acao' => 'compra',
                    'data' => $macho->data_compra,
                    'valor' => $macho->valor_compra,
                    'peso' => $macho->peso_compra,
                    'fornecedor_id' => $macho->fornecedor_id,
                    'criado_em' => now(),
                    'atualizado_em' => now(),
                ]);

                return $macho;
            });
        } catch (QueryException $e) {
            if ((string) $e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Já existe um macho cadastrado com essa identificação.',
                ], 422);
            }
            throw $e;
        }

        return response()->json([
            'message' => 'Compra de macho registrada com sucesso!',
            'id' => $result->id,
        ], 201);
    }

    private function parseInputDate(?string $input): ?string
    {
        if ($input === null || trim($input) === '') return null;
        $carbon = PigCycleService::parseFilterDate($input);
        return $carbon ? $carbon->format('Y-m-d') : null;
    }
}
