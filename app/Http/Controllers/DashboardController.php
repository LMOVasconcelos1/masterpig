<?php

namespace App\Http\Controllers;

use App\Models\Femea;
use App\Models\Macho;
use App\Models\Racao;
use App\Services\PigCycleService;
use App\Services\TerminacaoService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
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

        $n = (int) $raw;

        return $n < 0 ? $default : $n;
    }

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

    private function logInconsistenciaCioPrevisto(int $femeaId, array $dados, string $warning): void
    {
        if (! Schema::hasTable('criterio_log')) {
            return;
        }

        $today = Carbon::today()->toDateString();
        $exists = DB::table('criterio_log')
            ->where('evento', 'cio_previsto_sem_registro')
            ->where('femea_id', $femeaId)
            ->whereDate('ocorrido_em', $today)
            ->exists();

        if ($exists) {
            return;
        }

        $now = now();

        DB::table('criterio_log')->insert([
            'modulo' => 'plantel',
            'evento' => 'cio_previsto_sem_registro',
            'referencia_id' => null,
            'usuario_id' => null,
            'femea_id' => $femeaId,
            'warnings' => json_encode([$warning], JSON_UNESCAPED_UNICODE),
            'dados' => json_encode($dados, JSON_UNESCAPED_UNICODE),
            'ocorrido_em' => $now,
            'criado_em' => $now,
            'atualizado_em' => $now,
        ]);
    }

    private function buildInconsistenciasPlantel(): array
    {
        if (! Schema::hasTable('femea')) {
            return [];
        }

        $durations = PigCycleService::getCycleDurations();
        $cfg = [
            'dias_ate_cio' => $this->metaInt('criterio_cio_intervalo_min', 21),
            'cio_dias' => $this->metaInt('criterio_janela_cio', 5),
            'gestacao_dias' => $durations['gestacao'],
            'lactacao_min_dias' => $durations['lactacao'],
            'intervalo_desmame_cio_dias' => $durations['intervalo'],
            'preparto_alerta_dias' => $this->metaInt('criterio_preparto_alerta_dias', 5),
            'maturidade_min_dias' => $this->metaInt('meta_selecao_idade_selecao', 150),
            'matriz_vazia_max' => $this->metaInt('criterio_matriz_vazia_max_dias', 250),
            'macho_parado_max' => $this->metaInt('criterio_inconsistencia_macho_parado_max', 60),
        ];

        $today = Carbon::today();
        $items = [];

        // 1. Inconsistências de Fêmeas
        $query = DB::table('femea as f')
            ->select([
                'f.id',
                'f.id_primaria',
                'f.id_secundaria',
                'f.tipo_compra',
                'f.localizacao',
                'f.data_cobertura as data_cobertura_cadastro',
                'f.criado_em',
            ])
            ->orderBy('f.id_primaria')
            ->limit(2000);

        if (Schema::hasColumn('femea', 'data_nascimento')) {
            $query->addSelect(['f.data_nascimento']);
        } else {
            $query->addSelect([DB::raw('NULL as data_nascimento')]);
        }

        if (Schema::hasTable('femea_movimento')) {
            $query->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('femea_movimento as fm_in')
                    ->whereColumn('fm_in.femea_id', 'f.id')
                    ->whereIn('fm_in.acao', ['morte', 'descarte', 'venda']);
            });

            $lastMov = DB::table('femea_movimento')
                ->selectRaw('MAX(id) as last_id, femea_id')
                ->groupBy('femea_id');

            $query->leftJoinSub($lastMov, 'lm', function ($join) {
                $join->on('lm.femea_id', '=', 'f.id');
            });

            $query->leftJoin('femea_movimento as fm', 'fm.id', '=', 'lm.last_id')
                ->addSelect(['fm.acao as ultima_acao', 'fm.data as ultima_data']);
        } else {
            $query->addSelect([DB::raw('NULL as ultima_acao'), DB::raw('NULL as ultima_data')]);
        }

        if (Schema::hasTable('gestacao_cobertura')) {
            $lastCob = DB::table('gestacao_cobertura')
                ->selectRaw('MAX(data) as last_data, femea_id')
                ->groupBy('femea_id');
            $query->leftJoinSub($lastCob, 'lc', function ($join) {
                $join->on('lc.femea_id', '=', 'f.id');
            })->addSelect(['lc.last_data as last_cobertura']);
        } else {
            $query->addSelect([DB::raw('NULL as last_cobertura')]);
        }

        if (Schema::hasTable('gestacao_cio')) {
            $lastCio = DB::table('gestacao_cio')
                ->selectRaw('MAX(data) as last_data, femea_id')
                ->groupBy('femea_id');
            $query->leftJoinSub($lastCio, 'lci', function ($join) {
                $join->on('lci.femea_id', '=', 'f.id');
            })->addSelect(['lci.last_data as last_cio']);
        } else {
            $query->addSelect([DB::raw('NULL as last_cio')]);
        }

        if (Schema::hasTable('gestacao_salta_cio')) {
            $lastSalta = DB::table('gestacao_salta_cio')
                ->selectRaw('MAX(data) as last_data, femea_id')
                ->groupBy('femea_id');
            $query->leftJoinSub($lastSalta, 'lsc', function ($join) {
                $join->on('lsc.femea_id', '=', 'f.id');
            })->addSelect(['lsc.last_data as last_salta_cio']);
        } else {
            $query->addSelect([DB::raw('NULL as last_salta_cio')]);
        }

        $lastPartos = [];
        if (Schema::hasTable('maternidade_parto')) {
            $lastPartosQuery = DB::table('maternidade_parto')
                ->select('id', 'femea_id', 'data')
                ->whereIn('id', function($q) {
                    $q->selectRaw('MAX(id)')
                        ->from('maternidade_parto')
                        ->groupBy('femea_id');
                })
                ->get();
            foreach ($lastPartosQuery as $lp) { $lastPartos[$lp->femea_id] = $lp; }
        }

        $desmames = [];
        if (Schema::hasTable('maternidade_desmame')) {
            $desmames = DB::table('maternidade_desmame')->pluck('parto_id', 'parto_id')->toArray();
        }

        $rows = $query->get();
        $logged = 0;

        foreach ($rows as $row) {
            $femeaId = (int) $row->id;
            $lastCob = empty($row->last_cobertura) ? null : Carbon::parse($row->last_cobertura)->startOfDay();
            if (!$lastCob && !empty($row->data_cobertura_cadastro)) {
                $lastCob = Carbon::parse($row->data_cobertura_cadastro)->startOfDay();
            }
            $lastCio = empty($row->last_cio) ? null : Carbon::parse($row->last_cio)->startOfDay();
            $lastSalta = empty($row->last_salta_cio) ? null : Carbon::parse($row->last_salta_cio)->startOfDay();

            // Verificação de Matriz Vazia Prolongada
            if (!$lastCob) {
                $baseDate = $lastCio ?: ($lastSalta ?: Carbon::parse($row->criado_em));
                $daysVazia = $today->diffInDays($baseDate);
                if ($daysVazia > $cfg['matriz_vazia_max']) {
                    $items[] = [
                        'tipo' => 'matriz_vazia_prolongada',
                        'femea_id' => $femeaId,
                        'id_primaria' => (string) $row->id_primaria,
                        'localizacao' => $row->localizacao ?: '-',
                        'ultima_operacao' => 'Vazia há ' . $daysVazia . ' dias',
                        'problema' => "Matriz ultrapassou o limite de dias vazia ({$cfg['matriz_vazia_max']} dias).",
                    ];
                }
            }

            // Cio Previsto (Só alerta se houver registro de cio anterior e tiver passado o mínimo definido nas metas)
            $prevCio = null;
            if ($lastCob) {
                $prevCio = (clone $lastCob)->addDays($cfg['gestacao_dias'] + $cfg['lactacao_min_dias'] + $cfg['intervalo_desmame_cio_dias']);
            } elseif ($lastCio) {
                // Só calcula cio previsto se houver registro de cio anterior E tiver passado o mínimo em dias
                $diasDesdeUltimoCio = $today->diffInDays($lastCio);
                if ($diasDesdeUltimoCio >= max(1, $cfg['dias_ate_cio'])) {
                    $prevCio = (clone $lastCio)->addDays(max(1, $cfg['dias_ate_cio']));
                }
            }

            if ($prevCio && $today->gte($prevCio)) {
                // Se o último cio/salta registrado for igual ou posterior ao previsto, então já foi atendido.
                $hasFutureRegistry = ($lastCio && $lastCio->gte($prevCio->startOfDay())) || ($lastSalta && $lastSalta->gte($prevCio->startOfDay()));

                if (!$hasFutureRegistry) {
                    $items[] = [
                        'tipo' => 'cio_previsto_sem_registro',
                        'femea_id' => $femeaId,
                        'id_primaria' => (string) $row->id_primaria,
                        'localizacao' => $row->localizacao ?: '-',
                        'ultima_operacao' => empty($row->ultima_acao) ? '-' : (string) $row->ultima_acao,
                        'problema' => "Sem registro de Cio/Salta cio previsto para " . PigCycleService::formatDisplayDate($prevCio),
                    ];
                }
            }

            // Parto Atrasado
            if ($lastCob) {
                $expected = (clone $lastCob)->addDays($cfg['gestacao_dias']);
                if ($today->gt($expected)) {
                    $hasParto = isset($lastPartos[$femeaId]) && Carbon::parse($lastPartos[$femeaId]->data)->gte($lastCob);
                    if (!$hasParto) {
                        $items[] = [
                            'tipo' => 'parto_atrasado',
                            'femea_id' => $femeaId,
                            'id_primaria' => (string) $row->id_primaria,
                            'localizacao' => $row->localizacao ?: '-',
                            'ultima_operacao' => 'Cobertura (' . PigCycleService::formatDisplayDate($lastCob) . ')',
                            'problema' => "Parto previsto para " . PigCycleService::formatDisplayDate($expected) . " não registrado.",
                        ];
                    }
                }
            }

            // Desmame Atrasado
            if (isset($lastPartos[$femeaId])) {
                $lp = $lastPartos[$femeaId];
                if (!isset($desmames[$lp->id])) {
                    $expectedDesmame = Carbon::parse($lp->data)->addDays($cfg['lactacao_min_dias']);
                    if ($today->gt($expectedDesmame)) {
                        $items[] = [
                            'tipo' => 'desmame_atrasado',
                            'femea_id' => $femeaId,
                            'id_primaria' => (string) $row->id_primaria,
                            'localizacao' => $row->localizacao ?: '-',
                            'ultima_operacao' => 'Parto (' . PigCycleService::formatDisplayDate(Carbon::parse($lp->data)) . ')',
                            'problema' => "Desmame previsto para " . PigCycleService::formatDisplayDate($expectedDesmame) . " não registrado.",
                        ];
                    }
                }
            }
        }

        // 2. Inconsistências de Machos (Macho Parado)
        if (Schema::hasTable('macho')) {
            // Otimizado: Busca data de última cobertura para todos os machos de uma vez
            $lastUsedDates = [];
            if (Schema::hasTable('gestacao_cobertura')) {
                $lastUsedDates = DB::table('gestacao_cobertura')
                    ->selectRaw('macho_id, MAX(data) as last_data')
                    ->whereNotNull('macho_id')
                    ->groupBy('macho_id')
                    ->pluck('last_data', 'macho_id')
                    ->toArray();
            }

            $machos = DB::table('macho as m')
                ->select(['m.id', 'm.id_primaria', 'm.localizacao', 'm.criado_em'])
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))->from('macho_movimento as mm')->whereColumn('mm.macho_id', 'm.id')->whereIn('mm.acao', ['morte', 'descarte', 'venda']);
                })
                ->get();

            foreach ($machos as $macho) {
                $lastUsedRaw = $lastUsedDates[$macho->id] ?? null;
                $lastUsed = $lastUsedRaw ? Carbon::parse($lastUsedRaw) : null;
                
                // Se nunca usou, baseia-se na data de criação do registro
                $baseDate = $lastUsed ?: Carbon::parse($macho->criado_em);
                $daysIdle = $today->diffInDays($baseDate);

                if ($daysIdle > $cfg['macho_parado_max']) {
                    $items[] = [
                        'tipo' => 'macho_parado',
                        'femea_id' => $macho->id,
                        'id_primaria' => 'Macho: ' . $macho->id_primaria,
                        'localizacao' => $macho->localizacao ?: '-',
                        'ultima_operacao' => $lastUsed ? 'Última cobertura em ' . PigCycleService::formatDisplayDate($lastUsed) : 'Nunca utilizado',
                        'problema' => "Macho sem atividade há {$daysIdle} dias (Meta: {$cfg['macho_parado_max']} dias).",
                    ];
                }
            }
        }

        return $items;
    }

    public function __invoke()
    {
        // Proteção: Dashboard tem muitas queries de agregação — evita o timeout
        // "Maximum execution time of 30 seconds exceeded" no primeiro acesso.
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }
        @ini_set('max_execution_time', '120');

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
        $inconsistenciasPlantel = $this->buildInconsistenciasPlantel();

        $statsTerminacao = [
            'lotes_abertos' => 0,
            'estoque_animais' => 0,
            'mortalidade_taxa' => 0.0,
            'vendidos_30d' => 0,
        ];
        $inconsistenciasTerminacao = [];
        try {
            $terminacaoSvc = app(TerminacaoService::class);
            if (Schema::hasTable('terminacao_lotes')) {
                $statsTerminacao = $terminacaoSvc->calcularStatsGerais();
                $inconsistenciasTerminacao = $terminacaoSvc->buildInconsistencias();
            }
        } catch (\Throwable $e) {
        }

        if (Schema::hasTable('racao') && Schema::hasColumn('racao', 'estoque')) {
            $estoqueRacoes = (float) Racao::sum('estoque');
        }

        if (Schema::hasTable('femea')) {
            $leitoasAtivas = 0;
            $matrizesAtivas = 0;

            try {
                // Otimização: whereDoesntHave() é N+1 / NOT EXISTS muito lento.
                // Coleta os IDs que JÁ TEM movimento de saída UMA ÚNICA VEZ,
                // depois aplica um WHERE id NOT IN (array de IDs) = 2 queries.
                $idsFemeasComSaida = [];
                if (Schema::hasTable('femea_movimento')) {
                    $idsFemeasComSaida = DB::table('femea_movimento')
                        ->whereIn('acao', ['morte', 'descarte', 'venda'])
                        ->distinct()
                        ->pluck('femea_id')
                        ->all();
                }

                // Query 1: fêmeas ATIVAS (sem saída) → count em 2 grupos
                $femeaStats = Femea::query()
                    ->when(! empty($idsFemeasComSaida), static function ($q) use ($idsFemeasComSaida) {
                        $q->whereNotIn('id', $idsFemeasComSaida);
                    })
                    ->select('tipo_compra', DB::raw('COUNT(*) as total'))
                    ->groupBy('tipo_compra')
                    ->pluck('total', 'tipo_compra');

                $leitoasAtivas = (int) ($femeaStats['leitoa'] ?? 0);
                $matrizesAtivas = (int) ($femeaStats['matriz_vazia'] ?? 0) + (int) ($femeaStats['matriz_gestante'] ?? 0);
            } catch (\Throwable) {
                $leitoasAtivas = 0;
                $matrizesAtivas = 0;
            }
        }

        if (Schema::hasTable('macho')) {
            $machosAtivos = 0;
            try {
                $idsMachosComSaida = [];
                if (Schema::hasTable('macho_movimento')) {
                    $idsMachosComSaida = DB::table('macho_movimento')
                        ->whereIn('acao', ['morte', 'descarte', 'venda'])
                        ->distinct()
                        ->pluck('macho_id')
                        ->all();
                }

                $machosAtivos = (int) Macho::query()
                    ->when(! empty($idsMachosComSaida), static function ($q) use ($idsMachosComSaida) {
                        $q->whereNotIn('id', $idsMachosComSaida);
                    })
                    ->count();
            } catch (\Throwable) {
                $machosAtivos = 0;
            }
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
        $criterioMaturidadeMin = $this->metaInt('criterio_maturidade_idade_min_dias', 151);
        $calendarioTipo = PigCycleService::getCalendarType();

        return view('dashboard', [
            'estoqueRacoes' => $estoqueRacoes,
            'estoqueTotalAnimais' => $estoqueTotalAnimais,
            'leitoasAtivas' => $leitoasAtivas,
            'matrizesAtivas' => $matrizesAtivas,
            'machosAtivos' => $machosAtivos,
            'entradasFemeas' => $entradasFemeas,
            'inconsistenciasPlantel' => $inconsistenciasPlantel,
            'inconsistenciasTerminacao' => $inconsistenciasTerminacao,
            'statsTerminacao' => $statsTerminacao,
            'saidasLeitoas' => $saidasLeitoas,
            'saidasMatrizes' => $saidasMatrizes,
            'saidasMachos' => $saidasMachos,
            'criterioMaturidadeMin' => $criterioMaturidadeMin,
            'calendarioTipo' => $calendarioTipo,
        ]);
    }
}
