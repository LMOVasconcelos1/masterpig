<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class RetencaoFemeasController extends Controller
{
    public function index(Request $request)
    {
        $dataInicial = $request->query('data_inicial');
        $dataFinal = $request->query('data_final');
        $racaId = $request->query('raca_id');
        $tipoEntrada = $request->query('tipo_entrada', 'leitoas');

        if (!$dataInicial || !$dataFinal) {
            return response()->json([
                'message' => 'Data inicial e final são obrigatórias.',
            ], 422);
        }

        // Converter datas DD/MM/YYYY para YYYY-MM-DD
        $dataInicialIso = $this->parseBrDate($dataInicial);
        $dataFinalIso = $this->parseBrDate($dataFinal);

        if (!$dataInicialIso || !$dataFinalIso) {
            return response()->json([
                'message' => 'Formato de data inválido. Use DD/MM/AAAA.',
            ], 422);
        }

        if (!Schema::hasTable('femea')) {
            return response()->json([
                'message' => 'Tabela femea não existe.',
            ], 422);
        }

        // Buscar fêmeas que entraram no período
        $query = DB::table('femea as f')
            ->leftJoin('raca as r', 'r.id', '=', 'f.raca_id')
            ->where('f.data_compra', '>=', $dataInicialIso)
            ->where('f.data_compra', '<=', $dataFinalIso);

        if ($racaId) {
            $query->where('f.raca_id', $racaId);
        }

        // Filtrar por tipo de entrada
        if ($tipoEntrada === 'leitoas') {
            $query->where('f.tipo_compra', 'leitoa');
        } elseif ($tipoEntrada === 'ciclo1') {
            $query->where(function ($q) {
                $q->where('f.tipo_compra', 'leitoa')
                  ->orWhere(function ($q2) {
                      $q2->where('f.tipo_compra', 'matriz_vazia')
                          ->where('f.ciclos_ate_compra', 1);
                  });
            });
        }

        $femeas = $query->select([
            'f.id',
            'f.id_primaria',
            'f.tipo_compra',
            'f.data_compra',
            'r.nome as raca_nome',
        ])->get();

        $totalEntradas = $femeas->count();

        if ($totalEntradas === 0) {
            return response()->json([
                'total_entradas' => 0,
                'retidas' => 0,
                'taxa_retencao' => 0,
                'media_ciclos' => 0,
                'por_ordem_parto' => [],
            ]);
        }

        // Calcular retenção por ordem de parto
        $porOrdemParto = [];
        $totalRetidas = 0;
        $totalCiclos = 0;
        $femeasComParto = [];

        // Verificar se tabela de partos existe
        if (Schema::hasTable('maternidade_parto')) {
            $allFemeaIds = $femeas->pluck('id')->toArray();
            $allPartos = DB::table('maternidade_parto')
                ->whereIn('femea_id', $allFemeaIds)
                ->orderBy('data')
                ->get();
            $partosPorFemea = $allPartos->groupBy('femea_id');

            foreach ($femeas as $femea) {
                $partos = $partosPorFemea[$femea->id] ?? collect();

                $numPartos = $partos->count();
                $totalCiclos += $numPartos;

                if ($numPartos > 0) {
                    $femeasComParto[] = [
                        'id' => $femea->id,
                        'num_partos' => $numPartos,
                    ];
                }
            }

            // Calcular retenção por ordem de parto
            // Uma fêmea é "retida" no ciclo N se ela teve pelo menos N+1 partos
            $maxOrdem = 0;
            foreach ($femeasComParto as $femea) {
                $maxOrdem = max($maxOrdem, $femea['num_partos']);
            }

            for ($ordem = 1; $ordem <= $maxOrdem; $ordem++) {
                $entradas = 0;
                $retidas = 0;

                foreach ($femeasComParto as $femea) {
                    // Entradas: fêmeas que chegaram a ter pelo menos este número de partos
                    if ($femea['num_partos'] >= $ordem) {
                        $entradas++;
                    }
                    // Retidas: fêmeas que tiveram mais partos após este (ou seja, foram retidas)
                    if ($femea['num_partos'] > $ordem) {
                        $retidas++;
                    }
                }

                $porOrdemParto[$ordem] = [
                    'entradas' => $entradas,
                    'retidas' => $retidas,
                ];
            }

            // Total de fêmeas retidas: fêmeas que tiveram pelo menos 1 parto
            $totalRetidas = count($femeasComParto);
        }

        // Calcular taxa de retenção
        $taxaRetencao = $totalEntradas > 0 ? round(($totalRetidas / $totalEntradas) * 100, 1) : 0;
        $mediaCiclos = $totalEntradas > 0 ? round($totalCiclos / $totalEntradas, 1) : 0;

        // Formatar dados por ordem de parto
        $resultadoPorOrdem = [];
        ksort($porOrdemParto);
        foreach ($porOrdemParto as $ordem => $dados) {
            $taxa = $dados['entradas'] > 0 ? round(($dados['retidas'] / $dados['entradas']) * 100, 1) : 0;
            $resultadoPorOrdem[] = [
                'ordem' => $ordem,
                'entradas' => $dados['entradas'],
                'retidas' => $dados['retidas'],
                'taxa' => $taxa,
            ];
        }

        return response()->json([
            'total_entradas' => $totalEntradas,
            'retidas' => $totalRetidas,
            'taxa_retencao' => $taxaRetencao,
            'media_ciclos' => $mediaCiclos,
            'por_ordem_parto' => $resultadoPorOrdem,
        ]);
    }

    private function parseBrDate($value)
    {
        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return $v;
        }
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $v, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }
        return null;
    }
}