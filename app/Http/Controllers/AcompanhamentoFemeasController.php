<?php

namespace App\Http\Controllers;

use App\Services\PigCycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcompanhamentoFemeasController extends Controller
{
    private function tipoLabel(?string $tipo): string
    {
        $key = trim((string) $tipo);

        return match ($key) {
            'leitoa' => 'Leitoa',
            'matriz_vazia' => 'Matriz vazia',
            'matriz_gestante' => 'Matriz gestante',
            default => $key === '' ? '-' : $key,
        };
    }

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

    private function buildSchedule(?Carbon $cobertura, ?Carbon $lastCio, ?Carbon $lastSaltaCio, array $cfg): array
    {
        if ($cobertura) {
            $cycle = PigCycleService::calculateCycle($cobertura);

            return [
                [
                    'fase' => 'Cobertura',
                    'data' => PigCycleService::formatDisplayDate($cobertura, $cobertura),
                ],
                [
                    'fase' => 'Parto (previsto)',
                    'data' => $cycle['displayExpectedBirth'],
                ],
                [
                    'fase' => 'Lactação (início previsto)',
                    'data' => $cycle['displayExpectedBirth'],
                ],
                [
                    'fase' => 'Desmame (previsto)',
                    'data' => $cycle['displayWeaning'],
                ],
                [
                    'fase' => 'Cio pós-desmame (previsto)',
                    'data' => $cycle['displayNextCio'],
                ],
                [
                    'fase' => 'Nova cobertura (janela)',
                    'data' => $cycle['displayNextCio'],
                ],
                [
                    'fase' => 'Fim do cio (previsto)',
                    'data' => PigCycleService::formatDisplayDate($cycle['endCioDate'], $cobertura),
                ],
            ];
        }

        if ($lastCio) {
            $durations = PigCycleService::getCycleDurations();
            $fimCio = (clone $lastCio)->addDays($durations['cio']);
            $nextCio = (clone $lastCio)->addDays(max(1, (int) ($cfg['dias_ate_cio'] ?? 21)));

            return [
                [
                    'fase' => 'Cio',
                    'data' => PigCycleService::formatDisplayDate($lastCio),
                ],
                [
                    'fase' => 'Fim do cio (previsto)',
                    'data' => PigCycleService::formatDisplayDate($fimCio),
                ],
                [
                    'fase' => 'Próximo cio (previsto)',
                    'data' => PigCycleService::formatDisplayDate($nextCio),
                ],
            ];
        }

        if ($lastSaltaCio) {
            $durations = PigCycleService::getCycleDurations();
            $fimCio = (clone $lastSaltaCio)->addDays($durations['cio']);
            $nextCio = (clone $lastSaltaCio)->addDays(max(1, (int) ($cfg['dias_ate_cio'] ?? 21)));

            return [
                [
                    'fase' => 'Salta cio',
                    'data' => PigCycleService::formatDisplayDate($lastSaltaCio),
                ],
                [
                    'fase' => 'Fim do cio (previsto)',
                    'data' => PigCycleService::formatDisplayDate($fimCio),
                ],
                [
                    'fase' => 'Próximo cio (previsto)',
                    'data' => PigCycleService::formatDisplayDate($nextCio),
                ],
            ];
        }

        return [];
    }

    private function computeFase(array $row, ?Carbon $lastCobertura, ?Carbon $lastCio, ?Carbon $lastSaltaCio, array $cfg): array
    {
        $now = Carbon::today();
        $fId = $row['id'];

        // Número do cio é baseado APENAS em registros reais de CIO (gestacao_cio).
        // Salta cio não incrementa o número do cio.
        $countCios = 0;
        if (Schema::hasTable('gestacao_cio')) {
            $q = DB::table('gestacao_cio')->where('femea_id', $fId);
            if ($lastCobertura) {
                $q->where('data', '>', $lastCobertura->toDateString());
            }
            $countCios = (int) $q->count();
        }

        $cioAtualLabel = ($countCios <= 0 ? 1 : $countCios) . 'º cio';
        $cioProximoLabel = ($countCios + 1) . 'º cio';
        $coberturaCiclosMin = (int) ($cfg['cobertura_ciclos_min'] ?? 3);
        $coberturaIdadeMinDias = (int) ($cfg['cobertura_idade_min_dias'] ?? 210);
        $cioCoberturaNumero = $coberturaCiclosMin + 1;

        if ($lastCobertura) {
            $cycle = PigCycleService::calculateCycle($lastCobertura);

            return [
                'fase_anterior' => 'Cobertura',
                'fase' => $cycle['currentPhaseLabel'],
                'proxima_fase' => $cycle['nextPhaseLabel'],
                'prevista_em' => $cycle['displayPrevistaEm'],
            ];
        }

        $tipo = (string) ($row['tipo_compra'] ?? '');
        $nasc = empty($row['data_nascimento']) ? null : Carbon::parse($row['data_nascimento']);
        $idade = $nasc ? $nasc->diffInDays($now) : null;
        $idadeOkCobertura = $idade !== null && $idade >= $coberturaIdadeMinDias;
        $cioOkCobertura = $countCios >= $cioCoberturaNumero;
        $podeCobrirAgora = $idadeOkCobertura && $cioOkCobertura;

        if ($lastSaltaCio) {
            $durations = PigCycleService::getCycleDurations();
            $cioFim = (clone $lastSaltaCio)->addDays($durations['cio']);
            if ($now->betweenIncluded($lastSaltaCio, $cioFim)) {
                $nextCio = (clone $lastSaltaCio)->addDays(max(1, (int) ($cfg['dias_ate_cio'] ?? 21)));

                return [
                    'fase_anterior' => 'Cio',
                    'fase' => 'Salta cio',
                    'proxima_fase' => $cioProximoLabel,
                    'prevista_em' => $nextCio->format('d/m/Y'),
                ];
            }
        }

        if ($lastCio) {
            $durations = PigCycleService::getCycleDurations();
            $cioFim = (clone $lastCio)->addDays($durations['cio']);
            if ($now->betweenIncluded($lastCio, $cioFim)) {
                if ($podeCobrirAgora) {
                    return [
                        'fase_anterior' => 'Maturidade reprodutiva',
                        'fase' => $cioAtualLabel,
                        'proxima_fase' => 'Cobertura',
                        'prevista_em' => $lastCio->format('d/m/Y'),
                    ];
                }

                $nextCio = (clone $lastCio)->addDays(max(1, (int) ($cfg['dias_ate_cio'] ?? 21)));
                $nextLabel = ($countCios + 1) . 'º cio';

                return [
                    'fase_anterior' => 'Maturidade reprodutiva',
                    'fase' => $cioAtualLabel,
                    'proxima_fase' => $nextLabel,
                    'prevista_em' => $nextCio->format('d/m/Y'),
                ];
            }
        }

        $lastEventoCio = $lastCio;
        if ($lastEventoCio === null || ($lastSaltaCio !== null && $lastSaltaCio->gt($lastEventoCio))) {
            $lastEventoCio = $lastSaltaCio;
        }

        if ($lastEventoCio) {
            $nextCio = (clone $lastEventoCio)->addDays(max(1, $cfg['dias_ate_cio']));

            if ($idadeOkCobertura && $countCios >= $cioCoberturaNumero) {
                return [
                    'fase_anterior' => $cioAtualLabel,
                    'fase' => $cioAtualLabel,
                    'proxima_fase' => 'Cobertura',
                    'prevista_em' => $nextCio->format('d/m/Y'),
                ];
            }

            return [
                'fase_anterior' => $cioAtualLabel,
                'fase' => $cioAtualLabel,
                'proxima_fase' => $cioProximoLabel,
                'prevista_em' => $nextCio->format('d/m/Y'),
            ];
        }

        if ($tipo === 'leitoa' && $idade !== null) {
            if ($idade < $cfg['leitoa_max_dias']) {
                $prev = (clone $nasc)->addDays($cfg['leitoa_max_dias']);

                return [
                    'fase_anterior' => 'Nascimento',
                    'fase' => 'Leitoa',
                    'proxima_fase' => 'Maturidade reprodutiva',
                    'prevista_em' => $prev->format('d/m/Y'),
                ];
            }
        }

        if ($idade !== null) {
            $matMin = $cfg['maturidade_min_dias'];
            $matMax = $cfg['maturidade_max_dias'];
            if ($idade >= $matMin && $idade <= $matMax) {
                $maturityStart = (clone $nasc)->addDays($matMin);
                $nextCio = (clone $maturityStart)->addDays(max(1, $cfg['dias_ate_cio']));
                return [
                    'fase_anterior' => 'Leitoa',
                    'fase' => 'Maturidade reprodutiva',
                    'proxima_fase' => $cioProximoLabel,
                    'prevista_em' => $nextCio->format('d/m/Y'),
                ];
            }
        }

        if ($tipo === 'matriz_gestante') {
            return [
                'fase_anterior' => 'Cobertura',
                'fase' => 'Gestação',
                'proxima_fase' => 'Parto',
                'prevista_em' => '-',
            ];
        }

        $previstaEm = '-';
        if ($nasc) {
            $maturityStart = (clone $nasc)->addDays($cfg['maturidade_min_dias']);
            $nextCio = (clone $maturityStart)->addDays(max(1, $cfg['dias_ate_cio']));
            $previstaEm = $nextCio->format('d/m/Y');
        }

        return [
            'fase_anterior' => $tipo === 'leitoa' ? 'Leitoa' : '-',
            'fase' => 'Maturidade reprodutiva',
            'proxima_fase' => $cioProximoLabel,
            'prevista_em' => $previstaEm,
        ];
    }

    public function index(Request $request)
    {
        if (! Schema::hasTable('femea')) {
            return response()->json([
                'items' => [],
                'message' => 'Tabela femea não existe no banco.',
            ]);
        }

        $limit = max(1, min(2000, (int) $request->query('limit', 500)));

        $cfg = [
            'dias_ate_cio' => $this->metaInt('criterio_dias_ate_cio', 21),
            'cio_dias' => $this->metaInt('criterio_dias_cio', 3),
            'gestacao_dias' => $this->metaInt('criterio_dias_gestacao', 114),
            'lactacao_min_dias' => $this->metaInt('criterio_dias_lactacao_min', 21),
            'lactacao_max_dias' => $this->metaInt('criterio_dias_lactacao_max', 28),
            'intervalo_desmame_cio_dias' => $this->metaInt('criterio_dias_intervalo_desmame_cio', 5),
            'cobertura_idade_min_dias' => $this->metaInt('criterio_cobertura_idade_min_dias', 210),
            'cobertura_ciclos_min' => $this->metaInt('criterio_cobertura_ciclos_min', 3),
            'leitoa_min_dias' => $this->metaInt('criterio_leitoa_idade_min_dias', 150),
            'leitoa_max_dias' => $this->metaInt('criterio_leitoa_idade_max_dias', 150),
            'maturidade_min_dias' => $this->metaInt('criterio_maturidade_idade_min_dias', 151),
            'maturidade_max_dias' => $this->metaInt('criterio_maturidade_idade_max_dias', 220),
        ];

        $query = DB::table('femea')
            ->select([
                'id',
                'id_primaria',
                'id_secundaria',
                'tipo_compra',
                'data_nascimento',
                'data_compra',
                'data_cobertura',
                'localizacao',
                'baia',
            ])
            ->orderBy('id_primaria')
            ->limit($limit);

        if (Schema::hasTable('femea_movimento')) {
            $query->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('femea_movimento as fm')
                    ->whereColumn('fm.femea_id', 'femea.id')
                    ->whereIn('fm.acao', ['morte', 'descarte', 'venda']);
            });
        }

        $rows = $query->get();

        $ids = $rows->pluck('id')->map(fn ($v) => (int) $v)->values()->all();

        $lastCoberturas = [];
        if (! empty($ids) && Schema::hasTable('gestacao_cobertura')) {
            $lastCoberturas = DB::table('gestacao_cobertura')
                ->whereIn('femea_id', $ids)
                ->selectRaw('femea_id, MAX(data) as last_data')
                ->groupBy('femea_id')
                ->pluck('last_data', 'femea_id')
                ->toArray();
        }

        $lastCios = [];
        if (! empty($ids) && Schema::hasTable('gestacao_cio')) {
            $lastCios = DB::table('gestacao_cio')
                ->whereIn('femea_id', $ids)
                ->selectRaw('femea_id, MAX(data) as last_data')
                ->groupBy('femea_id')
                ->pluck('last_data', 'femea_id')
                ->toArray();
        }

        $lastSaltas = [];
        if (! empty($ids) && Schema::hasTable('gestacao_salta_cio')) {
            $lastSaltas = DB::table('gestacao_salta_cio')
                ->whereIn('femea_id', $ids)
                ->selectRaw('femea_id, MAX(data) as last_data')
                ->groupBy('femea_id')
                ->pluck('last_data', 'femea_id')
                ->toArray();
        }

        $items = $rows->map(function ($row) use ($lastCoberturas, $lastCios, $lastSaltas, $cfg) {
            $id = (int) $row->id;
            $lastCob = isset($lastCoberturas[$id]) && $lastCoberturas[$id] ? Carbon::parse($lastCoberturas[$id]) : null;
            $lastCio = isset($lastCios[$id]) && $lastCios[$id] ? Carbon::parse($lastCios[$id]) : null;
            $lastSalta = isset($lastSaltas[$id]) && $lastSaltas[$id] ? Carbon::parse($lastSaltas[$id]) : null;

            $fase = $this->computeFase([
                'id' => $id,
                'tipo_compra' => $row->tipo_compra,
                'data_nascimento' => $row->data_nascimento,
            ], $lastCob, $lastCio, $lastSalta, $cfg);

            return [
                'id' => $id,
                'id_primaria' => (string) $row->id_primaria,
                'id_secundaria' => $row->id_secundaria === null ? null : (string) $row->id_secundaria,
                'tipo' => $this->tipoLabel($row->tipo_compra),
                'tipo_key' => (string) $row->tipo_compra,
                'fase' => $fase['fase'],
                'proxima_fase' => $fase['proxima_fase'],
                'prevista_em' => $fase['prevista_em'],
                'ultima_cobertura' => PigCycleService::formatDisplayDate($lastCob, $lastCob),
                'ultimo_cio' => PigCycleService::formatDisplayDate($lastCio, $lastCob),
                'ultimo_salta_cio' => PigCycleService::formatDisplayDate($lastSalta, $lastCob),
            ];
        })->values();

        return response()->json([
            'items' => $items,
        ]);
    }

    public function show(int $id)
    {
        if (! Schema::hasTable('femea')) {
            abort(404);
        }

        $row = DB::table('femea')
            ->where('id', $id)
            ->select([
                'id',
                'id_primaria',
                'id_secundaria',
                'tipo_compra',
                'data_nascimento',
                'data_compra',
                'localizacao',
                'baia',
            ])
            ->first();

        if (! $row) {
            abort(404);
        }

        $cfg = [
            'dias_ate_cio' => $this->metaInt('criterio_dias_ate_cio', 21),
            'cio_dias' => $this->metaInt('criterio_dias_cio', 3),
            'gestacao_dias' => $this->metaInt('criterio_dias_gestacao', 114),
            'lactacao_min_dias' => $this->metaInt('criterio_dias_lactacao_min', 21),
            'lactacao_max_dias' => $this->metaInt('criterio_dias_lactacao_max', 28),
            'intervalo_desmame_cio_dias' => $this->metaInt('criterio_dias_intervalo_desmame_cio', 5),
            'cobertura_idade_min_dias' => $this->metaInt('criterio_cobertura_idade_min_dias', 210),
            'cobertura_ciclos_min' => $this->metaInt('criterio_cobertura_ciclos_min', 3),
            'leitoa_min_dias' => $this->metaInt('criterio_leitoa_idade_min_dias', 150),
            'leitoa_max_dias' => $this->metaInt('criterio_leitoa_idade_max_dias', 150),
            'maturidade_min_dias' => $this->metaInt('criterio_maturidade_idade_min_dias', 151),
            'maturidade_max_dias' => $this->metaInt('criterio_maturidade_idade_max_dias', 220),
        ];

        $lastCob = null;
        if (Schema::hasTable('gestacao_cobertura')) {
            $d = DB::table('gestacao_cobertura')->where('femea_id', $id)->max('data');
            $lastCob = $d ? Carbon::parse($d) : null;
        }

        $lastCio = null;
        if (Schema::hasTable('gestacao_cio')) {
            $d = DB::table('gestacao_cio')->where('femea_id', $id)->max('data');
            $lastCio = $d ? Carbon::parse($d) : null;
        }

        $lastSalta = null;
        if (Schema::hasTable('gestacao_salta_cio')) {
            $d = DB::table('gestacao_salta_cio')->where('femea_id', $id)->max('data');
            $lastSalta = $d ? Carbon::parse($d) : null;
        }

        $fase = $this->computeFase([
            'id' => (int) $row->id,
            'tipo_compra' => $row->tipo_compra,
            'data_nascimento' => $row->data_nascimento,
        ], $lastCob, $lastCio, $lastSalta, $cfg);

        $schedule = $this->buildSchedule($lastCob, $lastCio, $lastSalta, $cfg);

        return response()->json([
            'item' => [
                'id' => (int) $row->id,
                'id_primaria' => (string) $row->id_primaria,
                'id_secundaria' => $row->id_secundaria === null ? null : (string) $row->id_secundaria,
                'tipo' => $this->tipoLabel($row->tipo_compra),
                'tipo_key' => (string) $row->tipo_compra,
                'data_nascimento' => empty($row->data_nascimento) ? null : Carbon::parse($row->data_nascimento)->format('d/m/Y'),
                'data_compra' => empty($row->data_compra) ? null : Carbon::parse($row->data_compra)->format('d/m/Y'),
                'localizacao' => $row->localizacao === null ? null : (string) $row->localizacao,
                'baia' => $row->baia === null ? null : (string) $row->baia,
                'fase' => $fase['fase'],
                'proxima_fase' => $fase['proxima_fase'],
                'prevista_em' => $fase['prevista_em'],
                'ultimo_cio' => PigCycleService::formatDisplayDate($lastCio, $lastCob),
                'ultimo_salta_cio' => PigCycleService::formatDisplayDate($lastSalta, $lastCob),
                'ultima_cobertura' => PigCycleService::formatDisplayDate($lastCob, $lastCob),
                'calendario' => $schedule,
            ],
        ]);
    }
}
