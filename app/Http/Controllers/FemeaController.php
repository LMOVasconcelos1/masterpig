<?php

namespace App\Http\Controllers;

use App\Models\Femea;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class FemeaController extends Controller
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

        return (int) $raw;
    }

    public function show(Femea $femea)
    {
        if (! Schema::hasTable('femea')) {
            abort(404);
        }

        $femea->load(['raca', 'fornecedor']);

        // Buscar Metas para Comparação
        $metas = [
            'total_vivos' => $this->metaInt('meta_parto_vivos', 12),
            'lactacao_dias' => $this->metaInt('meta_lactacao_dias', 21),
            'intervalo_desmame_cio' => $this->metaInt('meta_intervalo_desmame_cio', 5),
        ];

        // Buscar Histórico de Performance da Fêmea
        $performance = [];
        if (Schema::hasTable('maternidade_parto')) {
            $performance = DB::table('maternidade_parto as mp')
                ->leftJoin('maternidade_desmame as md', 'mp.id', '=', 'md.parto_id')
                ->where('mp.femea_id', $femea->id)
                ->select([
                    'mp.data as data_parto',
                    'mp.total_vivos',
                    'mp.total_mortos',
                    'mp.total_mumificados',
                    'md.data as data_desmame',
                    'md.quantidade as qtd_desmamados'
                ])
                ->orderBy('mp.data', 'asc')
                ->get();
        }

        // Média do Plantel para Comparação
        $mediaPlantel = [
            'total_vivos' => 0,
            'total_desmamados' => 0
        ];
        if (Schema::hasTable('maternidade_parto')) {
            $mediaPlantel['total_vivos'] = DB::table('maternidade_parto')->avg('total_vivos') ?? 0;
        }
        if (Schema::hasTable('maternidade_desmame')) {
            $mediaPlantel['total_desmamados'] = DB::table('maternidade_desmame')->avg('quantidade') ?? 0;
        }

        // Resumo de Eventos Reprodutivos
        $resumoEventos = [
            'cios' => Schema::hasTable('gestacao_cio') ? DB::table('gestacao_cio')->where('femea_id', $femea->id)->count() : 0,
            'coberturas' => Schema::hasTable('gestacao_cobertura') ? DB::table('gestacao_cobertura')->where('femea_id', $femea->id)->count() : 0,
            'salta_cios' => Schema::hasTable('gestacao_salta_cio') ? DB::table('gestacao_salta_cio')->where('femea_id', $femea->id)->count() : 0,
            'perdas' => Schema::hasTable('gestacao_perda') ? DB::table('gestacao_perda')->where('femea_id', $femea->id)->count() : 0,
        ];

        // Idade e Tempo de Granja
        $idadeSemanas = $femea->data_nascimento ? (int) $femea->data_nascimento->diffInWeeks(now()) : null;
        $tempoGranjaMeses = $femea->data_compra ? (int) $femea->data_compra->diffInMonths(now()) : null;

        return view('admin.plantel.femeas.show', compact('femea', 'performance', 'metas', 'mediaPlantel', 'resumoEventos', 'idadeSemanas', 'tempoGranjaMeses'));
    }
}
