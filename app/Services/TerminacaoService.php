<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TerminacaoService
{
    public static function getMeta(string $chave, $default = null)
    {
        // Proteção total externa (fora do Schema::) — evita até o hasTable abrir conexão prematura
        try {
            if (!Schema::hasTable('meta')) {
                return $default;
            }
            try {
                $row = DB::table('meta')->where('chave', $chave)->first();
                if (!$row) {
                    return $default;
                }
                $valor = $row->valor ?? null;
                if ($valor === null || $valor === '') {
                    return $default;
                }
                return $valor;
            } catch (\Throwable) {
                return $default;
            }
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function getMetaInt(string $chave, int $default): int
    {
        $v = self::getMeta($chave, (string) $default);
        return is_numeric($v) ? (int) $v : $default;
    }

    public static function getMetaFloat(string $chave, float $default): float
    {
        $v = self::getMeta($chave, (string) $default);
        return is_numeric($v) ? (float) $v : $default;
    }

    public static function calcularDiasAlojamento(?Carbon $dataEntrada): int
    {
        if (!$dataEntrada) {
            return 0;
        }
        return (int) $dataEntrada->startOfDay()->diffInDays(Carbon::today());
    }

    public static function calcularSaldoLote(int $loteId): array
    {
        $entradas = 0;
        $mortes = 0;
        $transferenciasSaida = 0;
        $transferenciasEntrada = 0;
        $vendas = 0;
        $outrasSaidas = 0;

        if (Schema::hasTable('terminacao_entradas')) {
            $entradas = (int) (DB::table('terminacao_entradas')
                ->where('lote_id', $loteId)
                ->sum('quantidade') ?? 0);
        }

        if (Schema::hasTable('terminacao_mortes')) {
            $mortes = (int) (DB::table('terminacao_mortes')
                ->where('lote_id', $loteId)
                ->sum('quantidade') ?? 0);
        }

        if (Schema::hasTable('terminacao_transferencias')) {
            $transferenciasSaida = (int) (DB::table('terminacao_transferencias')
                ->where('lote_origem_id', $loteId)
                ->sum('quantidade') ?? 0);
            $transferenciasEntrada = (int) (DB::table('terminacao_transferencias')
                ->where('lote_destino_id', $loteId)
                ->sum('quantidade') ?? 0);
        }

        if (Schema::hasTable('terminacao_vendas')) {
            $vendas = (int) (DB::table('terminacao_vendas')
                ->where('lote_id', $loteId)
                ->sum('quantidade') ?? 0);
        }

        $totalEntradas = $entradas + $transferenciasEntrada;
        $totalSaidas = $mortes + $transferenciasSaida + $vendas + $outrasSaidas;
        $saldo = max(0, $totalEntradas - $totalSaidas);

        $mortalidadePct = $entradas > 0 ? round(($mortes / $entradas) * 100, 2) : 0.0;

        return [
            'entradas' => $entradas,
            'mortes' => $mortes,
            'transferencias_saida' => $transferenciasSaida,
            'transferencias_entrada' => $transferenciasEntrada,
            'vendas' => $vendas,
            'outras_saidas' => $outrasSaidas,
            'total_entradas' => $totalEntradas,
            'total_saidas' => $totalSaidas,
            'saldo' => $saldo,
            'mortalidade_pct' => $mortalidadePct,
        ];
    }

    public static function calcularIdadeMedia(int $loteId): ?float
    {
        if (!Schema::hasTable('terminacao_entradas')) {
            return null;
        }
        $rows = DB::table('terminacao_entradas')
            ->where('lote_id', $loteId)
            ->whereNotNull('data_nascimento')
            ->whereNotNull('data_entrada')
            ->get(['data_nascimento', 'data_entrada', 'quantidade']);

        if ($rows->count() === 0) {
            return null;
        }

        $weightedDays = 0.0;
        $weightedQty = 0;
        foreach ($rows as $r) {
            $q = (int) ($r->quantidade ?? 0);
            if ($q <= 0) {
                continue;
            }
            $nasc = Carbon::parse($r->data_nascimento)->startOfDay();
            $entr = Carbon::parse($r->data_entrada)->startOfDay();
            $weightedDays += ($nasc->diffInDays($entr) * $q);
            $weightedQty += $q;
        }

        return $weightedQty > 0 ? round($weightedDays / $weightedQty, 2) : null;
    }

    public static function calcularPesoMedioEntrada(int $loteId): ?float
    {
        if (!Schema::hasTable('terminacao_entradas')) {
            return null;
        }
        $row = DB::table('terminacao_entradas')
            ->where('lote_id', $loteId)
            ->selectRaw('COALESCE(SUM(quantidade), 0) as qtd, COALESCE(SUM(peso_total), 0) as peso')
            ->first();

        $qtd = (int) ($row->qtd ?? 0);
        $peso = (float) ($row->peso ?? 0);
        if ($qtd <= 0 || $peso <= 0) {
            return null;
        }
        return round($peso / $qtd, 2);
    }

    public static function ultimaPesagem(int $loteId): ?array
    {
        if (!Schema::hasTable('terminacao_pesos')) {
            return null;
        }
        $row = DB::table('terminacao_pesos')
            ->where('lote_id', $loteId)
            ->orderByDesc('data_pesagem')
            ->orderByDesc('id')
            ->first([
                'id', 'data_pesagem', 'peso_medio_kg', 'peso_total_kg',
                'quantidade_amostra', 'quantidade_lote', 'gpd_medio',
            ]);

        if (!$row) {
            return null;
        }
        return [
            'id' => (int) $row->id,
            'data_pesagem' => empty($row->data_pesagem) ? null : Carbon::parse($row->data_pesagem)->startOfDay(),
            'peso_medio_kg' => (float) $row->peso_medio_kg,
            'gpd_medio' => $row->gpd_medio === null ? null : (float) $row->gpd_medio,
        ];
    }

    public static function dataUltimaMovimentacao(int $loteId): ?Carbon
    {
        $datas = [];

        $tabelas = [
            ['table' => 'terminacao_entradas', 'date_col' => 'data_entrada'],
            ['table' => 'terminacao_mortes', 'date_col' => 'data_morte'],
            ['table' => 'terminacao_transferencias', 'date_col' => 'data_transferencia'],
            ['table' => 'terminacao_vendas', 'date_col' => 'data_venda'],
            ['table' => 'terminacao_pesos', 'date_col' => 'data_pesagem'],
        ];

        foreach ($tabelas as $t) {
            if (!Schema::hasTable($t['table'])) {
                continue;
            }
            try {
                $max = DB::table($t['table'])
                    ->where('lote_id', $loteId)
                    ->max($t['date_col']);
                if ($max) {
                    $datas[] = Carbon::parse($max)->startOfDay();
                }
            } catch (\Throwable) {
            }
        }

        if (count($datas) === 0) {
            return null;
        }

        usort($datas, fn ($a, $b) => $a?->timestamp <=> $b?->timestamp);
        return end($datas);
    }

    public static function calcularStatsGerais(): array
    {
        $stats = [
            'lotes_abertos' => 0,
            'estoque_animais' => 0,
            'hospital' => 0,
            'desclassificados' => 0,
            'mortalidade_taxa' => 0.0,
            'vendidos_periodo' => 0,
            'vendidos_30d' => 0,
            'mortes_periodo' => 0,
        ];

        try {
            if (!Schema::hasTable('terminacao_lotes')) {
                return $stats;
            }

            // ⏱️ Timeout — previne FatalError em bases grandes
            $startGerais = microtime(true);
            $timeoutGerais = static function () use ($startGerais): bool {
                return (microtime(true) - $startGerais) > 25;
            };

            $lotesAbertosIds = DB::table('terminacao_lotes')
                ->where('situacao', 'aberto')
                ->pluck('id')
                ->all();
            $stats['lotes_abertos'] = count($lotesAbertos);

            if ($timeoutGerais()) { return $stats; }

            // 🔥 OTIMIZAÇÃO — FIM DO N+1:
            // Antes: 5 queries × cada lote (50 lotes = 250 queries)
            // Agora: 6 queries TOTAIS usando SUM(CASE) agrupado por lote_id
            // + WHERE lote_id IN (lista) = 1 passada por tabela.
            $estoqueTotal = 0;
            $totalEntradasEstoque = 0;
            $totalMortesEstoque = 0;

            if (! empty($lotesAbertosIds)) {
                $somasPorLote = [];

                try {
                    if (Schema::hasTable('terminacao_entradas')) {
                        $rows = DB::table('terminacao_entradas')
                            ->whereIn('lote_id', $lotesAbertosIds)
                            ->select('lote_id', DB::raw('COALESCE(SUM(quantidade),0) as total'))
                            ->groupBy('lote_id')
                            ->pluck('total', 'lote_id');
                        foreach ($rows as $lid => $v) {
                            $somasPorLote[(int)$lid]['entradas'] = (int)$v;
                        }
                    }
                } catch (\Throwable) {}

                if ($timeoutGerais()) { return $stats; }

                try {
                    if (Schema::hasTable('terminacao_mortes')) {
                        $rows = DB::table('terminacao_mortes')
                            ->whereIn('lote_id', $lotesAbertosIds)
                            ->select('lote_id', DB::raw('COALESCE(SUM(quantidade),0) as total'))
                            ->groupBy('lote_id')
                            ->pluck('total', 'lote_id');
                        foreach ($rows as $lid => $v) {
                            $somasPorLote[(int)$lid]['mortes'] = (int)$v;
                        }
                    }
                } catch (\Throwable) {}

                if ($timeoutGerais()) { return $stats; }

                try {
                    if (Schema::hasTable('terminacao_transferencias')) {
                        $rowsOut = DB::table('terminacao_transferencias')
                            ->whereIn('lote_origem_id', $lotesAbertosIds)
                            ->select('lote_origem_id as lid', DB::raw('COALESCE(SUM(quantidade),0) as total'))
                            ->groupBy('lote_origem_id')
                            ->pluck('total', 'lid');
                        foreach ($rowsOut as $lid => $v) {
                            $somasPorLote[(int)$lid]['transf_out'] = (int)$v;
                        }
                        $rowsIn = DB::table('terminacao_transferencias')
                            ->whereIn('lote_destino_id', $lotesAbertosIds)
                            ->select('lote_destino_id as lid', DB::raw('COALESCE(SUM(quantidade),0) as total'))
                            ->groupBy('lote_destino_id')
                            ->pluck('total', 'lid');
                        foreach ($rowsIn as $lid => $v) {
                            $somasPorLote[(int)$lid]['transf_in'] = (int)$v;
                        }
                    }
                } catch (\Throwable) {}

                if ($timeoutGerais()) { return $stats; }

                try {
                    if (Schema::hasTable('terminacao_vendas')) {
                        $rows = DB::table('terminacao_vendas')
                            ->whereIn('lote_id', $lotesAbertosIds)
                            ->select('lote_id', DB::raw('COALESCE(SUM(quantidade),0) as total'))
                            ->groupBy('lote_id')
                            ->pluck('total', 'lote_id');
                        foreach ($rows as $lid => $v) {
                            $somasPorLote[(int)$lid]['vendas'] = (int)$v;
                        }
                    }
                } catch (\Throwable) {}

                // Totaliza em memória (array pequeno — 1 passo)
                foreach ($lotesAbertosIds as $lid) {
                    $lid = (int) $lid;
                    $s = $somasPorLote[$lid] ?? [];
                    $entradas         = (int) ($s['entradas']     ?? 0);
                    $mortes           = (int) ($s['mortes']       ?? 0);
                    $transfOut        = (int) ($s['transf_out']   ?? 0);
                    $transfIn         = (int) ($s['transf_in']    ?? 0);
                    $vendas           = (int) ($s['vendas']       ?? 0);
                    $totalEntradas = $entradas + $transfIn;
                    $totalSaidas   = $mortes + $transfOut + $vendas;
                    $saldo         = max(0, $totalEntradas - $totalSaidas);
                    $estoqueTotal         += $saldo;
                    $totalEntradasEstoque += $entradas;
                    $totalMortesEstoque   += $mortes;
                }
            }

            $stats['estoque_animais'] = $estoqueTotal;
            $stats['mortalidade_taxa'] = $totalEntradasEstoque > 0
                ? round(($totalMortesEstoque / $totalEntradasEstoque) * 100, 2)
                : 0.0;

            if ($timeoutGerais()) { return $stats; }

            try {
                if (Schema::hasTable('terminacao_vendas')) {
                    $inicio30d = Carbon::today()->subDays(30)->startOfDay()->format('Y-m-d');
                    $stats['vendidos_30d'] = (int) DB::table('terminacao_vendas')
                        ->where('data_venda', '>=', $inicio30d)
                        ->sum('quantidade');
                    $stats['vendidos_periodo'] = $stats['vendidos_30d'];
                }
                if (Schema::hasTable('terminacao_mortes')) {
                    $inicio30d = Carbon::today()->subDays(30)->startOfDay()->format('Y-m-d');
                    $stats['mortes_periodo'] = (int) DB::table('terminacao_mortes')
                        ->where('data_morte', '>=', $inicio30d)
                        ->sum('quantidade');
                }
            } catch (\Throwable) {
            }

            return $stats;
        } catch (\Throwable) {
            return $stats;
        }
    }

    public static function buildInconsistencias(): array
    {
        $alertas = [];
        try {
            if (!Schema::hasTable('terminacao_lotes')) {
                return $alertas;
            }

            $startI = microtime(true);
            $timeoutI = static function () use ($startI): bool {
                static $cnt = 0;
                if ((++$cnt % 20) !== 0) return false;
                return (microtime(true) - $startI) > 20;
            };

            $limiteDiasSemMov = self::getMetaInt('meta_terminacao_dias_sem_movimento', 15);
            $limiteMortalidadePct = self::getMetaFloat('meta_terminacao_mortalidade_pct', 3.0);
            $limiteResidualPct = self::getMetaFloat('meta_terminacao_lote_residual_pct', 10.0);
            $metaDias = self::getMetaInt('meta_terminacao_dias_permanencia', 90);

            if ($timeoutI()) { return $alertas; }

            // 🔥 OTIMIZAÇÃO FIM DO N+1:
            // Agrupa agregados em 6 queries TOTAIS (não 10/lote).
            $lotes = DB::table('terminacao_lotes')
                ->where('situacao', 'aberto')
                ->orderByDesc('id')
                ->limit(100)
                ->get(['id', 'nome', 'data_entrada', 'quantidade_inicial']);

            if ($lotes->isEmpty()) { return $alertas; }

            $lotesIds = $lotes->pluck('id')->all();
            $saldos = [];
            $ultimasMovs = [];

            try {
                if (Schema::hasTable('terminacao_entradas')) {
                    $rows = DB::table('terminacao_entradas')
                        ->whereIn('lote_id', $lotesIds)
                        ->selectRaw('lote_id, COALESCE(SUM(quantidade),0) as soma, COALESCE(MAX(data_entrada),\'\') as mx')
                        ->groupBy('lote_id')
                        ->get();
                    foreach ($rows as $r) {
                        $saldos[(int)$r->lote_id]['entradas'] = (int)$r->soma;
                        if (!empty($r->mx)) $ultimasMovs[(int)$r->lote_id][] = (string)$r->mx;
                    }
                }
                if ($timeoutI()) { return $alertas; }

                if (Schema::hasTable('terminacao_mortes')) {
                    $rows = DB::table('terminacao_mortes')
                        ->whereIn('lote_id', $lotesIds)
                        ->selectRaw('lote_id, COALESCE(SUM(quantidade),0) as soma, COALESCE(MAX(data_morte),\'\') as mx')
                        ->groupBy('lote_id')
                        ->get();
                    foreach ($rows as $r) {
                        $saldos[(int)$r->lote_id]['mortes'] = (int)$r->soma;
                        if (!empty($r->mx)) $ultimasMovs[(int)$r->lote_id][] = (string)$r->mx;
                    }
                }
                if ($timeoutI()) { return $alertas; }

                if (Schema::hasTable('terminacao_transferencias')) {
                    $rowsOut = DB::table('terminacao_transferencias')
                        ->whereIn('lote_origem_id', $lotesIds)
                        ->selectRaw('lote_origem_id as lid, COALESCE(SUM(quantidade),0) as soma, COALESCE(MAX(data_transferencia),\'\') as mx')
                        ->groupBy('lote_origem_id')
                        ->get();
                    foreach ($rowsOut as $r) {
                        $saldos[(int)$r->lid]['transf_out'] = (int)$r->soma;
                        if (!empty($r->mx)) $ultimasMovs[(int)$r->lid][] = (string)$r->mx;
                    }
                    $rowsIn = DB::table('terminacao_transferencias')
                        ->whereIn('lote_destino_id', $lotesIds)
                        ->selectRaw('lote_destino_id as lid, COALESCE(SUM(quantidade),0) as soma, COALESCE(MAX(data_transferencia),\'\') as mx')
                        ->groupBy('lote_destino_id')
                        ->get();
                    foreach ($rowsIn as $r) {
                        $saldos[(int)$r->lid]['transf_in'] = (int)$r->soma;
                        if (!empty($r->mx)) $ultimasMovs[(int)$r->lid][] = (string)$r->mx;
                    }
                }
                if ($timeoutI()) { return $alertas; }

                if (Schema::hasTable('terminacao_vendas')) {
                    $rows = DB::table('terminacao_vendas')
                        ->whereIn('lote_id', $lotesIds)
                        ->selectRaw('lote_id, COALESCE(SUM(quantidade),0) as soma, COALESCE(MAX(data_venda),\'\') as mx')
                        ->groupBy('lote_id')
                        ->get();
                    foreach ($rows as $r) {
                        $saldos[(int)$r->lote_id]['vendas'] = (int)$r->soma;
                        if (!empty($r->mx)) $ultimasMovs[(int)$r->lote_id][] = (string)$r->mx;
                    }
                }
                if ($timeoutI()) { return $alertas; }

                if (Schema::hasTable('terminacao_pesos')) {
                    $rows = DB::table('terminacao_pesos')
                        ->whereIn('lote_id', $lotesIds)
                        ->selectRaw('lote_id, COALESCE(MAX(data_pesagem),\'\') as mx')
                        ->groupBy('lote_id')
                        ->get();
                    foreach ($rows as $r) {
                        if (!empty($r->mx)) $ultimasMovs[(int)$r->lote_id][] = (string)$r->mx;
                    }
                }
            } catch (\Throwable) {}

            if ($timeoutI()) { return $alertas; }

            $hoje = Carbon::today();

            foreach ($lotes as $lote) {
                if ($timeoutI()) { return $alertas; }
                if (count($alertas) >= 80) { break; }

                $loteId = (int) $lote->id;
                $s = $saldos[$loteId] ?? [];
                $entradas         = (int) ($s['entradas']     ?? 0);
                $mortes           = (int) ($s['mortes']       ?? 0);
                $transfOut        = (int) ($s['transf_out']   ?? 0);
                $transfIn         = (int) ($s['transf_in']    ?? 0);
                $vendas           = (int) ($s['vendas']       ?? 0);
                $totalEntradas = $entradas + $transfIn;
                $totalSaidas   = $mortes + $transfOut + $vendas;
                $saldo         = max(0, $totalEntradas - $totalSaidas);
                $mortalidade   = $entradas > 0 ? round(($mortes / $entradas) * 100, 2) : 0.0;

                $dataEntrada = $lote->data_entrada ? Carbon::parse($lote->data_entrada)->startOfDay() : null;
                $dias = $dataEntrada ? self::calcularDiasAlojamento($dataEntrada) : 0;

                if ($dias > $metaDias && $saldo > 0) {
                    $alertas[] = [
                        'tipo' => 'atrasado',
                        'severidade' => 'warning',
                        'titulo' => "Lote {$lote->nome}: ultrapassou meta de {$metaDias} dias",
                        'detalhe' => "Está com {$dias} dias na terminação e ainda tem {$saldo} animal(is).",
                        'acao' => 'Avaliar venda/abate ou justificar permanência.',
                        'lote_id' => $loteId,
                        'lote_nome' => $lote->nome,
                    ];
                }

                if ($mortalidade > $limiteMortalidadePct && $entradas > 0) {
                    $alertas[] = [
                        'tipo' => 'mortalidade',
                        'severidade' => 'danger',
                        'titulo' => "Lote {$lote->nome}: mortalidade acima da meta",
                        'detalhe' => "Taxa atual: {$mortalidade}% (meta: {$limiteMortalidadePct}%).",
                        'acao' => 'Investigar causas e registrar cada morte com causa correta.',
                        'lote_id' => $loteId,
                        'lote_nome' => $lote->nome,
                    ];
                }

                $ultimaMov = null;
                $arr = $ultimasMovs[$loteId] ?? [];
                if (! empty($arr)) {
                    rsort($arr);
                    $ultimaMov = Carbon::parse($arr[0])->startOfDay();
                }
                $diasSemMov = $ultimaMov ? (int) $hoje->diffInDays($ultimaMov) : $dias;
                if ($diasSemMov > $limiteDiasSemMov && $saldo > 0) {
                    $alertas[] = [
                        'tipo' => 'sem_movimento',
                        'severidade' => 'warning',
                        'titulo' => "Lote {$lote->nome}: sem lançamentos há {$diasSemMov} dias",
                        'detalhe' => 'Última movimentação registrada em ' . ($ultimaMov ? PigCycleService::formatDisplayDate($ultimaMov) : '-'),
                        'acao' => 'Registrar pesagem, morte, transferência ou ao menos uma observação.',
                        'lote_id' => $loteId,
                        'lote_nome' => $lote->nome,
                    ];
                }

                $qtdInicial = (int) ($lote->quantidade_inicial ?: $entradas);
                if ($qtdInicial > 10 && $saldo > 0 && $saldo < ($qtdInicial * ($limiteResidualPct / 100))) {
                    $alertas[] = [
                        'tipo' => 'residual',
                        'severidade' => 'info',
                        'titulo' => "Lote {$lote->nome}: lote residual pequeno",
                        'detalhe' => "Saldo atual: {$saldo} de {$qtdInicial} inicial (abaixo de {$limiteResidualPct}%).",
                        'acao' => 'Considere agrupar com outro lote ou fazer venda/abate.',
                        'lote_id' => $loteId,
                        'lote_nome' => $lote->nome,
                    ];
                }

                if ($saldo <= 0 && $dias > 3) {
                    $alertas[] = [
                        'tipo' => 'zerado',
                        'severidade' => 'info',
                        'titulo' => "Lote {$lote->nome}: sem animais há {$dias} dias",
                        'detalhe' => 'Saldo atual é zero, mas lote continua como "aberto".',
                        'acao' => 'Fechar o lote (situação = fechado) com data de fechamento.',
                        'lote_id' => $loteId,
                        'lote_nome' => $lote->nome,
                    ];
                }
            }
        } catch (\Throwable) {}

        return $alertas;
    }
}
