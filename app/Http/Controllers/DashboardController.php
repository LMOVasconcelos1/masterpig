<?php

namespace App\Http\Controllers;

use App\Models\Femea;
use App\Models\Macho;
use App\Models\Racao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $estoqueRacoes = 0;
        $leitoasAtivas = 0;
        $matrizesAtivas = 0;
        $machosAtivos = 0;
        $estoqueTotalAnimais = 0;
        $saidasLeitoas = ['morte' => 0, 'descarte' => 0, 'venda' => 0];
        $saidasMatrizes = ['morte' => 0, 'descarte' => 0, 'venda' => 0];
        $saidasMachos = ['morte' => 0, 'descarte' => 0, 'venda' => 0];
        $entradasFemeas = [
            'leitoa' => 0,
            'matriz_vazia' => 0,
            'matriz_gestante' => 0,
        ];
        $inconsistenciasPlantel = [];

        if (Schema::hasTable('racao') && Schema::hasColumn('racao', 'estoque')) {
            $estoqueRacoes = (float) Racao::sum('estoque');
        }

        if (Schema::hasTable('femea')) {
            $femeasAtivasQuery = Femea::query();

            if (Schema::hasTable('femea_movimento')) {
                $femeasAtivasQuery->whereDoesntHave('movimentos', function ($q) {
                    $q->whereIn('acao', ['morte', 'descarte', 'venda']);
                });
            }

            $leitoasAtivas = (clone $femeasAtivasQuery)->where('tipo_compra', 'leitoa')->count();
            $matrizesAtivas = (clone $femeasAtivasQuery)->whereIn('tipo_compra', ['matriz_vazia', 'matriz_gestante'])->count();
        }

        if (Schema::hasTable('macho')) {
            $machosAtivosQuery = Macho::query();

            if (Schema::hasTable('macho_movimento')) {
                $machosAtivosQuery->whereDoesntHave('movimentos', function ($q) {
                    $q->whereIn('acao', ['morte', 'descarte', 'venda']);
                });
            }

            $machosAtivos = (clone $machosAtivosQuery)->count();
        }

        if (Schema::hasTable('femea') && Schema::hasTable('femea_movimento')) {
            $rows = DB::table('femea_movimento as fm')
                ->join('femea as f', 'f.id', '=', 'fm.femea_id')
                ->where('fm.acao', 'compra')
                ->select('f.tipo_compra', DB::raw('COUNT(*) as total'))
                ->groupBy('f.tipo_compra')
                ->get();

            foreach ($rows as $row) {
                if (isset($entradasFemeas[$row->tipo_compra])) {
                    $entradasFemeas[$row->tipo_compra] = (int) $row->total;
                }
            }

            $saidas = DB::table('femea_movimento as fm')
                ->join('femea as f', 'f.id', '=', 'fm.femea_id')
                ->whereIn('fm.acao', ['morte', 'descarte', 'venda'])
                ->select('f.tipo_compra', 'fm.acao', DB::raw('COUNT(*) as total'))
                ->groupBy('f.tipo_compra', 'fm.acao')
                ->get();

            foreach ($saidas as $row) {
                $acao = (string) $row->acao;
                $tipo = (string) $row->tipo_compra;
                $total = (int) $row->total;

                if ($tipo === 'leitoa' && isset($saidasLeitoas[$acao])) {
                    $saidasLeitoas[$acao] = $total;
                }

                if (($tipo === 'matriz_vazia' || $tipo === 'matriz_gestante') && isset($saidasMatrizes[$acao])) {
                    $saidasMatrizes[$acao] += $total;
                }
            }
        }

        if (Schema::hasTable('macho') && Schema::hasTable('macho_movimento')) {
            $rows = DB::table('macho_movimento as mm')
                ->join('macho as m', 'm.id', '=', 'mm.macho_id')
                ->whereIn('mm.acao', ['morte', 'descarte', 'venda'])
                ->select('mm.acao', DB::raw('COUNT(*) as total'))
                ->groupBy('mm.acao')
                ->get();

            foreach ($rows as $row) {
                $acao = (string) $row->acao;
                if (isset($saidasMachos[$acao])) {
                    $saidasMachos[$acao] = (int) $row->total;
                }
            }
        }

        $estoqueTotalAnimais = $leitoasAtivas + $matrizesAtivas + $machosAtivos;

        return view('dashboard', [
            'estoqueRacoes' => $estoqueRacoes,
            'estoqueTotalAnimais' => $estoqueTotalAnimais,
            'leitoasAtivas' => $leitoasAtivas,
            'matrizesAtivas' => $matrizesAtivas,
            'machosAtivos' => $machosAtivos,
            'entradasFemeas' => $entradasFemeas,
            'inconsistenciasPlantel' => $inconsistenciasPlantel,
            'saidasLeitoas' => $saidasLeitoas,
            'saidasMatrizes' => $saidasMatrizes,
            'saidasMachos' => $saidasMachos,
        ]);
    }
}
