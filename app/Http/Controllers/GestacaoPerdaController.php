<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GestacaoPerdaController extends Controller
{
    public function index(Request $request)
    {
        if (! Schema::hasTable('gestacao_perda')) {
            return response()->json([
                'items' => [],
                'message' => 'Tabela gestacao_perda não existe no banco.',
            ]);
        }

        $limit = max(1, min(200, (int) $request->query('limit', 50)));

        $hasUsuarioTable = Schema::hasTable('usuario');
        $hasUsuarioId = Schema::hasColumn('gestacao_perda', 'usuario_id');

        $select = [
            'gp.id',
            'gp.tipo',
            'gp.data',
            'gp.hora',
            'gp.localizacao',
            'gp.baia',
            'gp.observacoes',
            'f.id_primaria',
            'f.id_secundaria',
        ];

        if ($hasUsuarioId && $hasUsuarioTable) {
            $select[] = 'u.nome as usuario_nome';
        } else {
            $select[] = DB::raw('gp.funcionario as usuario_nome');
        }

        $items = DB::table('gestacao_perda as gp')
            ->join('femea as f', 'f.id', '=', 'gp.femea_id')
            ->when($hasUsuarioId && $hasUsuarioTable, function ($q) {
                $q->leftJoin('usuario as u', 'u.id', '=', 'gp.usuario_id');
            })
            ->orderByDesc('gp.data')
            ->orderByDesc('gp.hora')
            ->select($select)
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'matriz' => (string) $row->id_primaria,
                    'matriz_secundaria' => $row->id_secundaria === null ? null : (string) $row->id_secundaria,
                    'tipo' => (string) $row->tipo,
                    'data' => Carbon::parse($row->data)->format('d/m/Y'),
                    'hora' => $row->hora === null ? null : (string) $row->hora,
                    'usuario' => $row->usuario_nome === null ? null : (string) $row->usuario_nome,
                    'localizacao' => $row->localizacao === null ? null : (string) $row->localizacao,
                    'baia' => $row->baia === null ? null : (string) $row->baia,
                    'observacoes' => $row->observacoes === null ? null : (string) $row->observacoes,
                ];
            })
            ->values();

        return response()->json([
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('gestacao_perda') || ! Schema::hasTable('femea')) {
            return response()->json([
                'message' => 'Tabelas de gestação ainda não foram criadas no banco.',
            ], 422);
        }

        $validated = $request->validate([
            'femea_id' => ['required', 'exists:femea,id'],
            'usuario_id' => ['required', 'exists:usuario,id'],
            'tipo' => ['required', 'in:aborto,repeticao_cio,falsa_prenhez'],
            'data' => ['required', 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'localizacao' => ['nullable', 'string', 'max:120'],
            'baia' => ['nullable', 'string', 'max:60'],
            'observacoes' => ['nullable', 'string', 'max:500'],
        ]);

        $usuarioNome = DB::table('usuario')->where('id', (int) $validated['usuario_id'])->value('nome');
        $usuarioNome = is_string($usuarioNome) ? trim($usuarioNome) : '';
        if ($usuarioNome === '') {
            return response()->json([
                'message' => 'Usuário inválido.',
            ], 422);
        }

        $payload = [
            'femea_id' => (int) $validated['femea_id'],
            'usuario_id' => Schema::hasColumn('gestacao_perda', 'usuario_id') ? (int) $validated['usuario_id'] : null,
            'tipo' => (string) $validated['tipo'],
            'data' => Carbon::parse($validated['data'])->toDateString(),
            'hora' => empty($validated['hora']) ? null : (string) $validated['hora'],
            'localizacao' => $validated['localizacao'] ?? null,
            'baia' => $validated['baia'] ?? null,
            'observacoes' => $validated['observacoes'] ?? null,
            'criado_em' => now(),
            'atualizado_em' => now(),
        ];

        if (! Schema::hasColumn('gestacao_perda', 'usuario_id') && Schema::hasColumn('gestacao_perda', 'funcionario')) {
            $payload['funcionario'] = $usuarioNome;
        }

        DB::table('gestacao_perda')->insert($payload);

        return response()->json([
            'message' => 'Perda reprodutiva registrada com sucesso!',
        ], 201);
    }
}
