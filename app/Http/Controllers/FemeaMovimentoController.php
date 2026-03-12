<?php

namespace App\Http\Controllers;

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
                'data' => Carbon::parse($row->data)->format('d/m/Y'),
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
