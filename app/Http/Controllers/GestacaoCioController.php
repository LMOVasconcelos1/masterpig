<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GestacaoCioController extends Controller
{
    private function metaInt(string $key, int $default): int
    {
        if (! Schema::hasTable('meta')) {
            return $default;
        }

        $raw = DB::table('meta')->where('chave', $key)->value('valor');
        if ($raw === null || trim((string) $raw) === '') {
            return $default;
        }

        $n = (int) $raw;

        return $n < 0 ? $default : $n;
    }

    public function index(Request $request)
    {
        if (! Schema::hasTable('gestacao_cio')) {
            return response()->json([
                'items' => [],
                'message' => 'Tabela gestacao_cio não existe no banco.',
            ]);
        }

        $limit = max(1, min(200, (int) $request->query('limit', 50)));

        $items = DB::table('gestacao_cio as gc')
            ->join('femea as f', 'f.id', '=', 'gc.femea_id')
            ->orderByDesc('gc.data')
            ->select([
                'gc.id',
                'gc.data',
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
        if (! Schema::hasTable('gestacao_cio') || ! Schema::hasTable('femea')) {
            return response()->json([
                'message' => 'Tabelas de gestação ainda não foram criadas no banco.',
            ], 422);
        }

        $validated = $request->validate([
            'femea_id' => ['required', 'exists:femea,id'],
            'data' => ['required', 'date'],
        ]);

        $row = DB::table('femea')
            ->where('id', (int) $validated['femea_id'])
            ->select(['id', 'tipo_compra', 'data_nascimento'])
            ->first();

        if (! $row) {
            return response()->json([
                'message' => 'Fêmea inválida.',
            ], 422);
        }

        $tipo = (string) $row->tipo_compra;
        if (! in_array($tipo, ['leitoa', 'matriz_vazia'], true)) {
            return response()->json([
                'message' => 'Registro de cio é permitido apenas para leitoas ou matrizes em período de cio.',
            ], 422);
        }

        if (empty($row->data_nascimento)) {
            return response()->json([
                'message' => 'Data de nascimento é obrigatória para registrar cio.',
            ], 422);
        }

        $data = Carbon::parse($validated['data'])->startOfDay();
        $nascimento = Carbon::parse($row->data_nascimento)->startOfDay();
        $idadeDias = $nascimento->diffInDays($data);

        $matMinDias = max(0, $this->metaInt('criterio_maturidade_idade_min_dias', 151));
        $diasAteCio = max(1, $this->metaInt('criterio_dias_ate_cio', 21));

        if ($tipo === 'leitoa' && $idadeDias < $matMinDias) {
            return response()->json([
                'message' => "Leitoa ainda não atingiu maturidade reprodutiva ({$idadeDias} dias, mínimo {$matMinDias}).",
            ], 422);
        }

        $exists = DB::table('gestacao_cio')
            ->where('femea_id', (int) $validated['femea_id'])
            ->where('data', $data->toDateString())
            ->exists();

        $lastCio = DB::table('gestacao_cio')
            ->where('femea_id', (int) $validated['femea_id'])
            ->where('data', '<=', $data->toDateString())
            ->max('data');

        if ($lastCio) {
            $last = Carbon::parse($lastCio)->startOfDay();
            if ($last->diffInDays($data) < $diasAteCio) {
                return response()->json([
                    'message' => "Novo cio só pode ser registrado após {$diasAteCio} dias do anterior.",
                ], 422);
            }
        }

        if (! $exists) {
            DB::table('gestacao_cio')->insert([
                'femea_id' => (int) $validated['femea_id'],
                'data' => $data->toDateString(),
                'criado_em' => now(),
                'atualizado_em' => now(),
            ]);

            if ($tipo === 'leitoa' && $idadeDias >= $matMinDias && Schema::hasTable('femea') && Schema::hasColumn('femea', 'tipo_compra')) {
                DB::table('femea')->where('id', (int) $validated['femea_id'])->update([
                    'tipo_compra' => 'matriz_vazia',
                    'atualizado_em' => now(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Cio registrado com sucesso!',
        ], 201);
    }

    public function destroy(int $id)
    {
        if (! Schema::hasTable('gestacao_cio')) {
            return response()->json([
                'message' => 'Tabela gestacao_cio não existe no banco.',
            ], 422);
        }

        $exists = DB::table('gestacao_cio')->where('id', $id)->exists();
        if (! $exists) {
            return response()->json([
                'message' => 'Registro não encontrado.',
            ], 404);
        }

        DB::table('gestacao_cio')->where('id', $id)->delete();

        return response()->json([
            'message' => 'Registro excluído com sucesso!',
        ]);
    }
}
