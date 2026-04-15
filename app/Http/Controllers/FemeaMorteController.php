<?php

namespace App\Http\Controllers;

use App\Models\Causa;
use App\Models\Femea;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Controller responsável pelo gerenciamento de mortes de fêmeas
 * Registra o óbito de fêmeas no sistema e controla as validações
 */
class FemeaMorteController extends Controller
{
    /**
     * Registra a morte de uma fêmea no sistema
     * Valida se a fêmea pode receber o registro e se a causa é do tipo morte
     * Insere o registro na tabela femea_movimento com acao 'morte'
     * @param Request $request Dados da requisição contendo femea_id, data_morte e causa_id
     * @return \Illuminate\Http\JsonResponse Resposta JSON com sucesso ou erro
     */
    public function store(Request $request)
    {
        // Verifica se as tabelas necessárias existem no banco
        if (! Schema::hasTable('femea') || ! Schema::hasTable('femea_movimento')) {
            return response()->json([
                'message' => 'Tabelas do plantel ainda não foram criadas no banco. Entre em contato com o suporte.',
            ], 422);
        }

        // Valida os dados de entrada
        $validated = $request->validate([
            'femea_id' => ['required', 'exists:femea,id'],
            'data_morte' => ['required', 'date'],
            'causa_id' => ['required', 'exists:causa,id'],
        ]);

        // Busca a fêmea no banco
        $femea = Femea::findOrFail($validated['femea_id']);

        // Verifica a última ação da fêmea para evitar duplicidade
        $lastAcao = DB::table('femea_movimento')
            ->where('femea_id', $femea->id)
            ->orderByDesc('id')
            ->value('acao');

        // Impede registro se a fêmea já estiver inativa
        if (is_string($lastAcao) && in_array($lastAcao, ['morte', 'descarte', 'venda'], true)) {
            return response()->json([
                'message' => 'A fêmea já está inativa e não pode receber novo lançamento.',
            ], 422);
        }

        // Busca a causa com seu grupo para validação
        $causa = Causa::with('grupoCausa')->findOrFail($validated['causa_id']);

        // Verifica se a causa pertence ao grupo 'morte'
        $grupoNome = mb_strtolower($causa->grupoCausa?->nome ?? '');
        if (! str_contains($grupoNome, 'morte')) {
            return response()->json([
                'message' => 'Selecione uma causa do tipo morte.',
            ], 422);
        }

        // Prepara o payload para inserção na tabela de movimentos
        $payload = [
            'femea_id' => $femea->id,
            'femea_id_primaria' => $femea->id_primaria,
            'acao' => 'morte',
            'data' => Carbon::parse($validated['data_morte'])->format('Y-m-d'),
            'valor' => null,
            'peso' => null,
            'fornecedor_id' => null,
            'observacoes' => $causa->nome,
        ];

        // Adiciona causa_id se a coluna existir na tabela
        if (Schema::hasColumn('femea_movimento', 'causa_id')) {
            $payload['causa_id'] = $causa->id;
        }

        // Insere o registro de morte na tabela de movimentos
        DB::table('femea_movimento')->insert($payload);

        return response()->json([
            'message' => 'Morte registrada com sucesso!',
        ], 201);
    }
}
