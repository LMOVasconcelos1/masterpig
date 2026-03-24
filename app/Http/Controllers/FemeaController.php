<?php

namespace App\Http\Controllers;

use App\Models\Femea;
use App\Services\PigCycleService;
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

    private function ultimaMovimentacao(int $femeaId): array
    {
        if (! Schema::hasTable('femea_movimento')) {
            return [
                'acao' => null,
                'data' => null,
                'status' => 'Ativo',
            ];
        }

        $row = DB::table('femea_movimento')
            ->where('femea_id', $femeaId)
            ->orderByDesc('id')
            ->select(['acao', 'data'])
            ->first();

        $acao = $row?->acao === null ? null : (string) $row->acao;
        $data = empty($row?->data) ? null : Carbon::parse($row->data)->format('d/m/Y');
        $inativo = $acao !== null && in_array($acao, ['morte', 'descarte', 'venda'], true);

        return [
            'acao' => $acao,
            'data' => $data,
            'status' => $inativo ? 'Inativo' : 'Ativo',
        ];
    }

    public function index()
    {
        if (! Schema::hasTable('femea')) {
            abort(404);
        }

        $mostrarInativas = request()->boolean('inativas');

        $query = DB::table('femea as f')
            ->select([
                'f.id',
                'f.id_primaria',
                'f.id_secundaria',
                'f.tipo_compra',
                'f.localizacao',
                'f.baia',
                'f.data_compra',
            ])
            ->orderBy('f.id_primaria')
            ->limit(5000);

        if (Schema::hasTable('raca')) {
            $query->leftJoin('raca as r', 'r.id', '=', 'f.raca_id')->addSelect(['r.nome as raca_nome']);
        } else {
            $query->addSelect([DB::raw('NULL as raca_nome')]);
        }

        if (Schema::hasTable('fornecedor')) {
            $query->leftJoin('fornecedor as fo', 'fo.id', '=', 'f.fornecedor_id')->addSelect(['fo.nome as fornecedor_nome']);
        } else {
            $query->addSelect([DB::raw('NULL as fornecedor_nome')]);
        }

        if (Schema::hasTable('femea_movimento')) {
            $last = DB::table('femea_movimento')
                ->selectRaw('MAX(id) as last_id, femea_id')
                ->groupBy('femea_id');

            $query->leftJoinSub($last, 'lm', function ($join) {
                $join->on('lm.femea_id', '=', 'f.id');
            });

            $query->leftJoin('femea_movimento as fm', 'fm.id', '=', 'lm.last_id')
                ->addSelect([
                    'fm.acao as ultima_acao',
                    'fm.data as ultima_data',
                ]);

            if (! $mostrarInativas) {
                $query->where(function ($q) {
                    $q->whereNull('fm.acao')
                        ->orWhereNotIn('fm.acao', ['morte', 'descarte', 'venda']);
                });
            }
        }

        $items = $query->get()->map(function ($row) {
            $acao = empty($row->ultima_acao) ? null : (string) $row->ultima_acao;
            $data = empty($row->ultima_data) ? null : PigCycleService::formatDisplayDate(Carbon::parse($row->ultima_data));
            $inativo = $acao !== null && in_array($acao, ['morte', 'descarte', 'venda'], true);

            return [
                'id' => (int) $row->id,
                'id_primaria' => (string) $row->id_primaria,
                'id_secundaria' => $row->id_secundaria === null ? null : (string) $row->id_secundaria,
                'tipo' => (string) $row->tipo_compra,
                'raca' => $row->raca_nome === null ? '-' : (string) $row->raca_nome,
                'fornecedor' => $row->fornecedor_nome === null ? '-' : (string) $row->fornecedor_nome,
                'localizacao' => $row->localizacao === null ? '-' : (string) $row->localizacao,
                'baia' => $row->baia === null ? '-' : (string) $row->baia,
                'data_compra' => empty($row->data_compra) ? '-' : PigCycleService::formatDisplayDate(Carbon::parse($row->data_compra)),
                'ultima_operacao' => $acao === null ? '-' : $acao . ($data ? " - {$data}" : ''),
                'status' => $inativo ? 'Inativo' : 'Ativo',
            ];
        })->values();

        return view('admin.plantel.femeas.index', [
            'items' => $items,
            'mostrarInativas' => $mostrarInativas,
        ]);
    }

    public function show(Femea $femea)
    {
        if (! Schema::hasTable('femea')) {
            abort(404);
        }

        $with = [];
        if (Schema::hasTable('raca')) {
            $with[] = 'raca';
        }
        if (Schema::hasTable('fornecedor')) {
            $with[] = 'fornecedor';
        }
        if (! empty($with)) {
            $femea->load($with);
        }
        $mov = $this->ultimaMovimentacao($femea->id);

        $metas = [
            'total_vivos' => $this->metaInt('meta_parto_vivos', 12),
            'lactacao_dias' => $this->metaInt('meta_lactacao_dias', 21),
            'intervalo_desmame_cio' => $this->metaInt('meta_intervalo_desmame_cio', 5),
        ];

        $performance = [];
        if (Schema::hasTable('maternidade_parto')) {
            $performance = DB::table('maternidade_parto as mp')
                ->leftJoin('maternidade_desmame as md', 'mp.id', '=', 'md.parto_id')
                ->where('mp.femea_id', $femea->id)
                ->select([
                    'mp.id as parto_id',
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

        $resumoEventos = [
            'cios' => Schema::hasTable('gestacao_cio') ? DB::table('gestacao_cio')->where('femea_id', $femea->id)->count() : 0,
            'coberturas' => Schema::hasTable('gestacao_cobertura') ? DB::table('gestacao_cobertura')->where('femea_id', $femea->id)->count() : 0,
            'salta_cios' => Schema::hasTable('gestacao_salta_cio') ? DB::table('gestacao_salta_cio')->where('femea_id', $femea->id)->count() : 0,
            'perdas' => Schema::hasTable('gestacao_perda') ? DB::table('gestacao_perda')->where('femea_id', $femea->id)->count() : 0,
        ];

        $idadeDias = $femea->data_nascimento ? (int) $femea->data_nascimento->diffInDays(now()) : null;
        $tempoGranjaDias = $femea->data_compra ? (int) $femea->data_compra->diffInDays(now()) : null;

        $lastCobertura = null;
        if (Schema::hasTable('gestacao_cobertura')) {
            $row = DB::table('gestacao_cobertura')->where('femea_id', $femea->id)->orderByDesc('data')->first();
            if ($row) {
                $lastCobertura = Carbon::parse($row->data);
            }
        }

        $calendarType = PigCycleService::getCalendarType();
        $cycle = $lastCobertura ? PigCycleService::calculateCycle($lastCobertura) : null;
        $alerts = $cycle ? PigCycleService::getPhaseAlerts($cycle, $femea->id_primaria) : [];
        $diasNoCiclo = $cycle ? (int) $cycle['totalDaysElapsed'] : null;

        return view('admin.plantel.femeas.show', compact('femea', 'performance', 'metas', 'mediaPlantel', 'resumoEventos', 'idadeDias', 'tempoGranjaDias', 'mov', 'cycle', 'alerts', 'diasNoCiclo', 'calendarType'));
    }
}
