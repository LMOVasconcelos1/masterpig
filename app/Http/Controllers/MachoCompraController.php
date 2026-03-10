<?php

namespace App\Http\Controllers;

use App\Models\Macho;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MachoCompraController extends Controller
{
    public function index()
    {
        if (!Schema::hasTable('macho') || !Schema::hasTable('macho_movimento')) {
            return response()->json([
                'items' => [],
                'message' => 'Tabelas de machos ainda não foram criadas no banco.',
            ]);
        }

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
            ->limit(5000)
            ->get();

        $items = $rows->map(function ($row) {
            $idadeDias = null;

            if (!empty($row->data_nascimento)) {
                $idadeDias = Carbon::parse($row->data_nascimento)->diffInDays(Carbon::parse($row->data));
            }

            return [
                'id' => $row->id,
                'acao' => 'compra',
                'data' => Carbon::parse($row->data)->format('d/m/Y'),
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
        if (!Schema::hasTable('macho') || !Schema::hasTable('macho_movimento')) {
            return response()->json([
                'message' => 'Tabelas de machos ainda não foram criadas no banco.',
            ], 422);
        }

        $validated = $request->validate([
            'id_primaria' => ['required', 'string', 'max:50', 'unique:macho,id_primaria'],
            'id_secundaria' => ['nullable', 'string', 'max:50', 'unique:macho,id_secundaria'],
            'data_compra' => ['required', 'date'],
            'data_nascimento' => ['nullable', 'date'],
            'raca_id' => ['required', 'exists:raca,id'],
            'valor_compra' => ['nullable', 'numeric', 'min:0'],
            'peso_compra' => ['nullable', 'numeric', 'min:0'],
            'fornecedor_id' => ['nullable', 'exists:fornecedor,id'],
            'caracteristicas' => ['nullable', 'string'],
            'localizacao' => ['nullable', 'string', 'max:120'],
            'baia' => ['nullable', 'string', 'max:60'],
        ]);

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

        return response()->json([
            'message' => 'Compra de macho registrada com sucesso!',
            'id' => $result->id,
        ], 201);
    }
}
