<?php

namespace App\Http\Controllers;

use App\Models\Femea;
use App\Models\Macho;
use App\Models\Racao;
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

        $cfg = [
            'dias_ate_cio' => $this->metaInt('criterio_dias_ate_cio', 21),
            'cio_dias' => $this->metaInt('criterio_dias_cio', 3),
            'gestacao_dias' => $this->metaInt('criterio_dias_gestacao', 114),
            'lactacao_min_dias' => $this->metaInt('criterio_dias_lactacao_min', 21),
            'lactacao_max_dias' => $this->metaInt('criterio_dias_lactacao_max', 28),
            'intervalo_desmame_cio_dias' => $this->metaInt('criterio_dias_intervalo_desmame_cio', 5),
            'maturidade_min_dias' => $this->metaInt('criterio_maturidade_idade_min_dias', 151),
        ];

        $today = Carbon::today();

        $query = DB::table('femea as f')
            ->select([
                'f.id',
                'f.id_primaria',
                'f.id_secundaria',
                'f.tipo_compra',
                'f.localizacao',
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

        $rows = $query->get();
        $items = [];
        $logged = 0;

        foreach ($rows as $row) {
            $femeaId = (int) $row->id;
            $lastCob = empty($row->last_cobertura) ? null : Carbon::parse($row->last_cobertura)->startOfDay();
            $lastCio = empty($row->last_cio) ? null : Carbon::parse($row->last_cio)->startOfDay();
            $lastSalta = empty($row->last_salta_cio) ? null : Carbon::parse($row->last_salta_cio)->startOfDay();

            $prevCio = null;
            $base = null;

            if ($lastCob) {
                $prevCio = (clone $lastCob)
                    ->addDays($cfg['gestacao_dias'] + $cfg['lactacao_min_dias'] + $cfg['intervalo_desmame_cio_dias']);
                $base = 'pós-desmame';
            } else {
                $lastEvento = $lastCio;
                if ($lastEvento === null || ($lastSalta !== null && $lastSalta->gt($lastEvento))) {
                    $lastEvento = $lastSalta;
                }
                if ($lastEvento) {
                    $prevCio = (clone $lastEvento)->addDays(max(1, $cfg['dias_ate_cio']));
                    $base = 'ciclo';
                }
            }

            if (! $prevCio) {
                $tipo = (string) $row->tipo_compra;
                if ($tipo === 'leitoa' && ! empty($row->data_nascimento)) {
                    $nasc = Carbon::parse($row->data_nascimento)->startOfDay();
                    $maturityStart = (clone $nasc)->addDays(max(0, $cfg['maturidade_min_dias']));
                    $prevCio = (clone $maturityStart)->addDays(max(1, $cfg['dias_ate_cio']));
                    $base = 'maturidade';
                }
            }

            if (! $prevCio) {
                continue;
            }

            $janelaFim = (clone $prevCio)->addDays(max(0, $cfg['cio_dias']));
            $prevIso = $prevCio->toDateString();
            $fimIso = $janelaFim->toDateString();

            $inWindow = $today->betweenIncluded($prevCio, $janelaFim);

            $temRegistro = false;
            if (Schema::hasTable('gestacao_cio')) {
                $temRegistro = DB::table('gestacao_cio')
                    ->where('femea_id', $femeaId)
                    ->whereBetween('data', [$prevIso, $fimIso])
                    ->exists();
            }
            if (! $temRegistro && Schema::hasTable('gestacao_salta_cio')) {
                $temRegistro = DB::table('gestacao_salta_cio')
                    ->where('femea_id', $femeaId)
                    ->whereBetween('data', [$prevIso, $fimIso])
                    ->exists();
            }

            if ($temRegistro) {
                continue;
            }

            $tipoLabel = $this->tipoLabel($row->tipo_compra);
            $prevBr = $prevCio->format('d/m/Y');
            $fimBr = $janelaFim->format('d/m/Y');

            if ($inWindow) {
                $diff = max(0, (int) $today->diffInDays($prevCio));
                $problema = "Fêmea sem registro de cio há {$diff} dias ({$tipoLabel}).";
            } else {
                $problema = "Fêmea com cio previsto em {$prevBr} (janela até {$fimBr}) sem registro de Cio/Salta cio ({$tipoLabel}).";
            }

            $ultimaOperacao = '-';
            if (! empty($row->ultima_acao)) {
                $ultimaOperacao = (string) $row->ultima_acao;
                if (! empty($row->ultima_data)) {
                    $ultimaOperacao .= ' - '.Carbon::parse($row->ultima_data)->format('d/m/Y');
                }
            }

            $items[] = [
                'tipo' => 'cio_previsto_sem_registro',
                'femea_id' => $femeaId,
                'id_primaria' => (string) $row->id_primaria,
                'id_secundaria' => $row->id_secundaria === null ? null : (string) $row->id_secundaria,
                'localizacao' => $row->localizacao === null ? null : (string) $row->localizacao,
                'ultima_operacao' => $ultimaOperacao,
                'problema' => $problema,
            ];

            if ($logged < 100) {
                $this->logInconsistenciaCioPrevisto($femeaId, [
                    'previsto_em' => $prevIso,
                    'janela_fim' => $fimIso,
                    'base' => $base,
                    'tipo_compra' => (string) $row->tipo_compra,
                ], $problema);
                $logged++;
            }
        }

        return $items;
    }

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
        $inconsistenciasPlantel = $this->buildInconsistenciasPlantel();

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
