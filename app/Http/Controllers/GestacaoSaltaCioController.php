<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
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

        $data = Carbon::parse($validated['data'])->toDateString();
        $femeaId = (int) $validated['femea_id'];

        $exists = DB::table('gestacao_salta_cio')
            ->where('femea_id', $femeaId)
            ->where('data', $data)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Salta cio já registrado para essa data.',
            ]);
        }

        $sqlEnum = "ALTER TABLE `femea_movimento` MODIFY COLUMN `acao` ENUM('compra', 'morte', 'descarte', 'venda', 'cio', 'salta_cio', 'cobertura', 'parto', 'desmame', 'morte_leitao') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;";

        try {
            DB::transaction(function () use ($femeaId, $data, $sqlEnum) {
                DB::table('gestacao_salta_cio')->insert([
                    'femea_id' => $femeaId,
                    'data' => $data,
                    'criado_em' => now(),
                    'atualizado_em' => now(),
                ]);

                if (! Schema::hasTable('femea_movimento')) {
                    return;
                }

                $idPrimaria = DB::table('femea')->where('id', $femeaId)->value('id_primaria');
                $payload = [
                    'femea_id' => $femeaId,
                    'acao' => 'salta_cio',
                    'data' => $data,
                    'valor' => null,
                    'peso' => null,
                    'fornecedor_id' => null,
                    'observacoes' => null,
                ];
                if (Schema::hasColumn('femea_movimento', 'femea_id_primaria')) {
                    $payload['femea_id_primaria'] = $idPrimaria;
                }
                if (Schema::hasColumn('femea_movimento', 'criado_em')) {
                    $payload['criado_em'] = now();
                }
                if (Schema::hasColumn('femea_movimento', 'atualizado_em')) {
                    $payload['atualizado_em'] = now();
                }

                DB::table('femea_movimento')->insert($payload);
            });
        } catch (QueryException $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Data truncated') && str_contains($msg, 'acao')) {
                return response()->json([
                    'message' => "Para registrar o histórico no plantel, é necessário atualizar o ENUM de `femea_movimento.acao`.",
                    'sql' => $sqlEnum,
                ], 422);
            }
            throw $e;
        }

        return response()->json([
            'message' => 'Salta cio registrado com sucesso!',
        ], 201);
    }

    public function destroy(int $id)
    {
        if (! Schema::hasTable('gestacao_salta_cio')) {
            return response()->json([
                'message' => 'Tabela gestacao_salta_cio não existe no banco.',
            ], 422);
        }

        $exists = DB::table('gestacao_salta_cio')->where('id', $id)->exists();
        if (! $exists) {
            return response()->json([
                'message' => 'Registro não encontrado.',
            ], 404);
        }

        DB::table('gestacao_salta_cio')->where('id', $id)->delete();

        return response()->json([
            'message' => 'Registro excluído com sucesso!',
        ]);
    }
}
