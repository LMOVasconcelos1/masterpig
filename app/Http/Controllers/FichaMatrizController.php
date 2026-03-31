<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FichaMatrizController extends Controller
{
    public function show($id)
    {
        if (!Schema::hasTable('femea')) {
            return response()->json([
                'message' => 'Tabela femea não existe no banco.',
            ], 422);
        }

        $femea = DB::table('femea as f')
            ->leftJoin('raca as r', 'r.id', '=', 'f.raca_id')
            ->where('f.id', $id)
            ->select([
                'f.id',
                'f.id_primaria',
                'f.id_secundaria',
                'f.tipo_compra',
                'r.nome as raca_nome',
            ])
            ->first();

        if (!$femea) {
            return response()->json([
                'message' => 'Fêmea não encontrada.',
            ], 404);
        }

        // Buscar ciclos reprodutivos (partos)
        $ciclos = [];
        $totalDiasGestacao = 0;
        $totalDiasLactacao = 0;
        $totalNascidosTotais = 0;
        $totalNascidosVivos = 0;
        $totalDesmamados = 0;
        $totalMortalidade = 0;
        $countCiclos = 0;

        if (Schema::hasTable('maternidade_parto')) {
            $partos = DB::table('maternidade_parto as mp')
                ->leftJoin('maternidade_desmame as md', 'md.parto_id', '=', 'mp.id')
                ->leftJoin('gestacao_cobertura as gc', 'gc.id', '=', 'mp.cobertura_id')
                ->where('mp.femea_id', $id)
                ->select([
                    'mp.id as parto_id',
                    'mp.data as data_parto',
                    'mp.total_vivos',
                    'mp.total_mortos',
                    'mp.total_mumificados',
                    'gc.data as data_cobertura',
                    'md.data as data_desmame',
                    'md.quantidade as desmamados',
                ])
                ->orderBy('mp.data')
                ->get();

            foreach ($partos as $parto) {
                $countCiclos++;
                
                // Calcular dias de gestação
                $diasGestacao = 0;
                if ($parto->data_cobertura && $parto->data_parto) {
                    $diasGestacao = \Carbon\Carbon::parse($parto->data_cobertura)
                        ->diffInDays(\Carbon\Carbon::parse($parto->data_parto));
                }
                $totalDiasGestacao += $diasGestacao;

                // Calcular dias de lactação
                $diasLactacao = 0;
                if ($parto->data_parto && $parto->data_desmame) {
                    $diasLactacao = \Carbon\Carbon::parse($parto->data_parto)
                        ->diffInDays(\Carbon\Carbon::parse($parto->data_desmame));
                }
                $totalDiasLactacao += $diasLactacao;

                // Nascidos totais
                $nascidosTotais = ($parto->total_vivos ?? 0) + ($parto->total_mortos ?? 0) + ($parto->total_mumificados ?? 0);
                $totalNascidosTotais += $nascidosTotais;

                // Nascidos vivos
                $totalNascidosVivos += ($parto->total_vivos ?? 0);

                // Desmamados
                $totalDesmamados += ($parto->desmamados ?? 0);

                // Mortalidade (nascidos vivos - desmamados)
                $mortalidadeCiclo = ($parto->total_vivos ?? 0) - ($parto->desmamados ?? 0);
                $totalMortalidade += max(0, $mortalidadeCiclo);

                $ciclos[] = [
                    'parto_id' => $parto->parto_id,
                    'data_parto' => $parto->data_parto ? \Carbon\Carbon::parse($parto->data_parto)->format('d/m/Y') : '-',
                    'data_cobertura' => $parto->data_cobertura ? \Carbon\Carbon::parse($parto->data_cobertura)->format('d/m/Y') : '-',
                    'data_desmame' => $parto->data_desmame ? \Carbon\Carbon::parse($parto->data_desmame)->format('d/m/Y') : '-',
                    'dias_gestacao' => $diasGestacao,
                    'dias_lactacao' => $diasLactacao,
                    'nascidos_totais' => $nascidosTotais,
                    'nascidos_vivos' => $parto->total_vivos ?? 0,
                    'desmamados' => $parto->desmamados ?? 0,
                    'mortalidade' => max(0, $mortalidadeCiclo),
                ];
            }
        }

        // Calcular médias
        $mediaDiasGestacao = $countCiclos > 0 ? round($totalDiasGestacao / $countCiclos, 1) : 0;
        $mediaDiasLactacao = $countCiclos > 0 ? round($totalDiasLactacao / $countCiclos, 1) : 0;

        // Determinar status
        $status = 'Ativa';
        if (Schema::hasTable('femea_movimento')) {
            $ultimaMovimentacao = DB::table('femea_movimento')
                ->where('femea_id', $id)
                ->whereIn('acao', ['morte', 'descarte', 'venda'])
                ->orderByDesc('data')
                ->first();

            if ($ultimaMovimentacao) {
                $status = 'Inativa (' . ucfirst($ultimaMovimentacao->acao) . ')';
            }
        }

        return response()->json([
            'id' => $femea->id,
            'id_primaria' => $femea->id_primaria,
            'id_secundaria' => $femea->id_secundaria,
            'tipo' => $femea->tipo_compra,
            'raca' => $femea->raca_nome,
            'status' => $status,
            'total_ciclos' => $countCiclos,
            'dias_gestacao' => $mediaDiasGestacao,
            'dias_lactacao' => $mediaDiasLactacao,
            'nascidos_totais' => $totalNascidosTotais,
            'nascidos_vivos' => $totalNascidosVivos,
            'desmamados' => $totalDesmamados,
            'mortalidade' => $totalMortalidade,
            'ciclos' => $ciclos,
        ]);
    }
}