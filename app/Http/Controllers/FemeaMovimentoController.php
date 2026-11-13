<?php

namespace App\Http\Controllers;

use App\Models\Femea;
use App\Services\PigCycleService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FemeaMovimentoController extends Controller
{
    public function mortes()
    {
        return $this->listarPorAcao('morte');
    }

    public function descartes()
    {
        return $this->listarPorAcao('descarte');
    }

    public function vendas()
    {
        return $this->listarPorAcao('venda');
    }

    public function destroy(int $id)
    {
        if (! Schema::hasTable('femea_movimento')) {
            return response()->json([
                'message' => 'Tabela femea_movimento não existe no banco.',
            ], 422);
        }

        $resultado = DB::transaction(function () use ($id) {
            if (Schema::hasTable('configuracoes') && Schema::hasColumn('configuracoes', 'limite_femeas')) {
                DB::table('configuracoes')->orderBy('id')->lockForUpdate()->first();
            }

            $row = DB::table('femea_movimento')
                ->where('id', $id)
                ->lockForUpdate()
                ->first(['id', 'femea_id', 'acao']);
            if (! $row) {
                return ['status' => 'not_found'];
            }

            $acao = (string) ($row->acao ?? '');
            if (! in_array($acao, Femea::INATIVIDADES, true)) {
                return ['status' => 'not_deletable'];
            }

            $ultimoMovimentoId = DB::table('femea_movimento')
                ->where('femea_id', $row->femea_id)
                ->orderByDesc('id')
                ->value('id');
            $reativaFemea = (int) $ultimoMovimentoId === (int) $row->id;

            if ($reativaFemea) {
                $acaoAnterior = DB::table('femea_movimento')
                    ->where('femea_id', $row->femea_id)
                    ->where('id', '<>', $row->id)
                    ->orderByDesc('id')
                    ->value('acao');
                $reativaFemea = $acaoAnterior === null || ! in_array((string) $acaoAnterior, Femea::INATIVIDADES, true);
            }

            if ($reativaFemea) {
                $limite = Femea::getLimiteAtivas();
                $ativas = Femea::countAtivas();
                if ($limite !== null && $ativas + 1 > $limite) {
                    return [
                        'status' => 'limit_reached',
                        'message' => "A exclusão reativaria esta fêmea e ultrapassaria o limite de {$limite} fêmeas ativas.",
                    ];
                }
            }

            DB::table('femea_movimento')->where('id', $row->id)->delete();

            return ['status' => 'deleted'];
        });

        if ($resultado['status'] === 'not_found') {
            return response()->json(['message' => 'Lançamento não encontrado.'], 404);
        }
        if ($resultado['status'] === 'not_deletable') {
            return response()->json(['message' => 'Este tipo de lançamento não pode ser excluído.'], 422);
        }
        if ($resultado['status'] === 'limit_reached') {
            return response()->json(['message' => $resultado['message']], 422);
        }

        return response()->json([
            'message' => 'Lançamento excluído com sucesso!',
        ]);
    }

    private function listarPorAcao(string $acao)
    {
        if (! Schema::hasTable('femea') || ! Schema::hasTable('femea_movimento')) {
            return response()->json([
                'items' => [],
                'message' => 'Tabelas do plantel ainda não foram criadas no banco.',
            ]);
        }

        $hasCausaId = Schema::hasColumn('femea_movimento', 'causa_id') && Schema::hasTable('causa');

        $query = DB::table('femea_movimento as fm')
            ->join('femea as f', 'f.id', '=', 'fm.femea_id')
            ->where('fm.acao', $acao)
            ->orderByDesc('fm.data')
            ->select([
                'fm.id',
                'fm.data',
                'f.id as femea_id',
                'f.id_primaria',
                'f.tipo_compra',
                'f.ciclos_ate_compra',
                'fm.observacoes',
            ]);

        if ($hasCausaId) {
            $query->leftJoin('causa as c', 'c.id', '=', 'fm.causa_id')
                ->addSelect('c.nome as causa_nome');
        }

        $rows = $query->get();

        $items = $rows->map(function ($row) use ($hasCausaId, $acao) {
            $causa = $hasCausaId ? ($row->causa_nome ?? null) : null;
            if (! $causa) {
                $causa = $row->observacoes ?? '-';
            }

            return [
                'id' => $row->id,
                'acao' => $acao,
                'data' => PigCycleService::formatDisplayDate(Carbon::parse($row->data)),
                'femea_id' => $row->femea_id,
                'id_primaria' => $row->id_primaria,
                'tipo' => $row->tipo_compra,
                'ciclo' => $row->ciclos_ate_compra,
                'causa' => $causa,
            ];
        })->values();

        return response()->json([
            'items' => $items,
        ]);
    }
}
