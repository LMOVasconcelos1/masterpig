<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PigCycleService
{
    public const CALENDAR_GREGORIAN = 'gregoriano';
    public const CALENDAR_1000_DAYS = '1000_dias';
    public const PIG_BASE_DATE = '1968-12-31';

    public static function getCalendarType(): string
    {
        // Proteção total: nunca propagar erro de DB/Schema nem conexão indisponível
        try {
            if (!Schema::hasTable('meta')) {
                return self::CALENDAR_1000_DAYS;
            }

            $type = DB::table('meta')->where('chave', 'criterio_calendario_tipo')->value('valor');
            return in_array($type, [self::CALENDAR_GREGORIAN, self::CALENDAR_1000_DAYS], true) ? $type : self::CALENDAR_1000_DAYS;
        } catch (\Throwable) {
            return self::CALENDAR_1000_DAYS;
        }
    }

    public static function toPigDay(?\DateTimeInterface $date): ?int
    {
        if (!$date) return null;
        $date = Carbon::instance($date);
        $base = Carbon::parse(self::PIG_BASE_DATE)->startOfDay();
        // Dia PIG Absoluto = quantidade de dias desde 31/12/1968
        $absoluteDay = (int) $base->diffInDays($date->startOfDay(), false);
        
        // Dia PIG = últimos 3 dígitos do Dia PIG Absoluto
        return $absoluteDay % 1000;
    }

    public static function toPigAbsoluteDay(?\DateTimeInterface $date): ?int
    {
        if (!$date) return null;
        $date = Carbon::instance($date);
        $base = Carbon::parse(self::PIG_BASE_DATE)->startOfDay();
        return (int) $base->diffInDays($date->startOfDay(), false);
    }

    /**
     * Converte um número de Dia PIG de volta para um objeto Carbon.
     * Lógica simples: encontrar o dia absoluto mais recente com esses últimos 3 dígitos.
     */
    public static function fromPigDay(int $day): Carbon
    {
        $base = Carbon::parse(self::PIG_BASE_DATE)->startOfDay();
        $today = Carbon::today();

        $day = $day % 1000;
        if ($day < 0) $day += 1000;

        $currentAbsoluteDay = (int) $base->diffInDays($today, false);

        $currentThousand = floor($currentAbsoluteDay / 1000) * 1000;
        $targetAbsoluteDay = $currentThousand + $day;

        if ($targetAbsoluteDay > $currentAbsoluteDay) {
            $targetAbsoluteDay -= 1000;
        }

        return $base->copy()->addDays($targetAbsoluteDay);
    }

    /**
     * Realiza o parse de uma string de data vinda do filtro,
     * tratando como Dia PIG ou data DD/MM/AAAA conforme a config.
     * Também aceita formato ISO (YYYY-MM-DD) diretamente, já que o frontend converte antes de enviar.
     */
    public static function parseFilterDate(?string $input): ?Carbon
    {
        if (!$input || trim($input) === '') return null;
        $input = trim($input);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $input)->startOfDay();
            } catch (\Exception $e) {
                return null;
            }
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $input)) {
            try {
                return Carbon::createFromFormat('d/m/Y', $input)->startOfDay();
            } catch (\Exception $e) {
                return null;
            }
        }

        $type = self::getCalendarType();

        if ($type === self::CALENDAR_1000_DAYS) {
            if (preg_match('/^\d{1,4}$/', $input)) {
                $day = (int) $input;
                if ($day <= 0) return null;
                return self::fromPigDay($day);
            }

            $digits = (int) preg_replace('/\D/', '', $input);
            if ($digits > 0) {
                return self::fromPigDay($digits);
            }
        }

        try {
            return Carbon::parse($input)->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getCycleDurations(): array
    {
        $type = self::getCalendarType();

        if ($type === self::CALENDAR_1000_DAYS) {
            return [
                'gestacao' => 114,
                'lactacao' => 21,
                'intervalo' => 7,
                'recria' => self::metaInt('meta_creche_recria_dias', 63),
                'terminacao' => self::metaInt('meta_terminacao_ciclo_dias', 70),
                'cio' => 3,
            ];
        }

        return [
            'gestacao' => self::metaInt('meta_gestacao_periodo_gestacao', self::metaInt('criterio_dias_gestacao', 114)),
            'lactacao' => self::metaInt('criterio_dias_lactacao_min', 21),
            'intervalo' => self::metaInt('meta_gestacao_intervalo_desmame_cobertura', self::metaInt('criterio_dias_intervalo_desmame_cio', 7)),
            'recria' => self::metaInt('meta_creche_recria_dias', 63),
            'terminacao' => self::metaInt('meta_terminacao_ciclo_dias', 70),
            'cio' => self::metaInt('meta_gestacao_montas_por_cobertura', self::metaInt('criterio_dias_cio', 3)),
        ];
    }

    /**
     * Cache em memória (por request) das metas inteiras lidas do banco.
     * Evita repetir N mil queries por request no dashboard (100 animais × 3 keys = 300 queries → agora 3).
     *
     * @var array<string, int|null>
     */
    private static array $metaIntCache = [];

    private static function metaInt(string $key, int $default): int
    {
        // 1) Hit em cache por request (RAM) → SEM query SQL.
        if (array_key_exists($key, self::$metaIntCache)) {
            $cached = self::$metaIntCache[$key];
            return $cached === null ? $default : $cached;
        }

        // Proteção total: qualquer erro de conexão / tabela inexistente → default
        try {
            if (! Schema::hasTable('meta')) {
                self::$metaIntCache[$key] = null;
                return $default;
            }

            $raw = DB::table('meta')->where('chave', $key)->value('valor');
            if ($raw === null || trim((string) $raw) === '') {
                self::$metaIntCache[$key] = null;
                return $default;
            }

            $valor = (int) $raw;
            self::$metaIntCache[$key] = $valor;
            return $valor;
        } catch (\Throwable) {
            self::$metaIntCache[$key] = null;
            return $default;
        }
    }

    public static function formatDisplayDate(?\DateTimeInterface $date, ?\DateTimeInterface $unused = null): string
    {
        if (!$date) return '-';
        $date = Carbon::instance($date);
        
        $type = self::getCalendarType();
        if ($type === self::CALENDAR_1000_DAYS) {
            return (string) self::toPigDay($date);
        }

        return $date->format('d/m/Y');
    }

    public static function calculateCycle(Carbon $coverageDate, ?Carbon $referenceDate = null): array
    {
        $durations = self::getCycleDurations();
        $ref = $referenceDate ?: Carbon::today();
        $type = self::getCalendarType();

        $expectedBirthDate = (clone $coverageDate)->addDays($durations['gestacao']);
        $weaningDate = (clone $expectedBirthDate)->addDays($durations['lactacao']);
        $nextCioDate = (clone $weaningDate)->addDays($durations['intervalo']);
        $endCioDate = (clone $nextCioDate)->addDays($durations['cio']);
        
        $rearEndDate = (clone $weaningDate)->addDays($durations['recria']);
        $slaughterDate = (clone $rearEndDate)->addDays($durations['terminacao']);

        $totalDaysElapsed = $coverageDate->diffInDays($ref, false);
        $phase = 'concluido';
        $phaseLabel = 'Concluído';
        $nextPhaseLabel = '-';
        $previstaEm = null;

        if ($ref->lt($expectedBirthDate)) {
            $phase = 'gestacao';
            $phaseLabel = 'Gestação';
            $nextPhaseLabel = 'Parto';
            $previstaEm = $expectedBirthDate;
        } elseif ($ref->lt($weaningDate)) {
            $phase = 'lactacao';
            $phaseLabel = 'Lactação';
            $nextPhaseLabel = 'Desmame';
            $previstaEm = $weaningDate;
        } elseif ($ref->lt($nextCioDate)) {
            $phase = 'intervalo';
            $phaseLabel = 'Intervalo desmame-cio';
            $nextPhaseLabel = 'Cio pós-desmame';
            $previstaEm = $nextCioDate;
        } elseif ($ref->lte($endCioDate)) {
            $phase = 'cio';
            $phaseLabel = 'Cio pós-desmame';
            $nextPhaseLabel = 'Cobertura';
            $previstaEm = $nextCioDate;
        }

        return [
            'coverageDate' => $coverageDate,
            'expectedBirthDate' => $expectedBirthDate,
            'weaningDate' => $weaningDate,
            'nextCioDate' => $nextCioDate,
            'endCioDate' => $endCioDate,
            'rearEndDate' => $rearEndDate,
            'slaughterDate' => $slaughterDate,
            'currentPhase' => $phase,
            'currentPhaseLabel' => $phaseLabel,
            'nextPhaseLabel' => $nextPhaseLabel,
            'previstaEm' => $previstaEm,
            'totalDaysElapsed' => $totalDaysElapsed,
            // Display formatted values
            'displayExpectedBirth' => self::formatDisplayDate($expectedBirthDate, $coverageDate),
            'displayWeaning' => self::formatDisplayDate($weaningDate, $coverageDate),
            'displayNextCio' => self::formatDisplayDate($nextCioDate, $coverageDate),
            'displayPrevistaEm' => self::formatDisplayDate($previstaEm, $coverageDate),
        ];
    }

    public static function getPhaseAlerts(array $cycle, string $animalId): array
    {
        $alerts = [];
        $now = Carbon::today();
        $prepartoAlertaDias = max(1, self::metaInt('criterio_preparto_alerta_dias', 5));

        // Alerta pré-parto (X dias antes, configurável por meta de admin)
        $diffParto = $now->diffInDays($cycle['expectedBirthDate'], false);
        if ($diffParto >= 0 && $diffParto <= $prepartoAlertaDias) {
            $alerts[] = "[{$animalId}]: parto previsto em {$diffParto} dias ? preparar maternidade";
        }

        // Alerta desmame (No dia)
        if ($now->isSameDay($cycle['weaningDate'])) {
            $alerts[] = "[{$animalId}]: desmame hoje ? retornar ao galpão de gestação";
        }

        // Alerta cobertura (No dia)
        if ($now->isSameDay($cycle['nextCioDate'])) {
            $alerts[] = "[{$animalId}]: janela de cobertura aberta";
        }

        // Alerta abate (7 dias antes)
        $diffAbate = $now->diffInDays($cycle['slaughterDate'], false);
        if ($diffAbate >= 0 && $diffAbate <= 7) {
            $alerts[] = "[Lote]: abate previsto em {$diffAbate} dias";
        }

        return $alerts;
    }
}
