<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PigCycleService
{
    public const CALENDAR_GREGORIAN = 'gregoriano';
    public const CALENDAR_1000_DAYS = '1000_dias';
    public const PIG_BASE_DATE = '1969-01-01';

    public static function getCalendarType(): string
    {
        if (!Schema::hasTable('meta')) {
            return self::CALENDAR_1000_DAYS;
        }

        $type = DB::table('meta')->where('chave', 'criterio_calendario_tipo')->value('valor');
        // Default to 1000_dias as requested
        return in_array($type, [self::CALENDAR_GREGORIAN, self::CALENDAR_1000_DAYS]) ? $type : self::CALENDAR_1000_DAYS;
    }

    public static function toPigDay(?Carbon $date): ?int
    {
        if (!$date) return null;
        $base = Carbon::parse(self::PIG_BASE_DATE)->startOfDay();
        // Dia 1 = 01/01/1969. So diff in days + 1.
        $absoluteDay = (int) $base->diffInDays($date->startOfDay(), false) + 1;
        
        // Aplicar ciclo de 1000 dias com offset para corresponder ao padrão do integrador
        // Fórmula: ((absoluto - 3) % 1000) + 1
        return (($absoluteDay - 3) % 1000) + 1;
    }

    /**
     * Converte um número de Dia PIG de volta para um objeto Carbon.
     */
    public static function fromPigDay(int $day): Carbon
    {
        return Carbon::parse(self::PIG_BASE_DATE)->startOfDay()->addDays($day - 1);
    }

    /**
     * Realiza o parse de uma string de data vinda do filtro, 
     * tratando como Dia PIG ou data DD/MM/AAAA conforme a config.
     */
    public static function parseFilterDate(?string $input): ?Carbon
    {
        if (!$input || trim($input) === '') return null;

        $type = self::getCalendarType();

        if ($type === self::CALENDAR_1000_DAYS) {
            $day = (int) preg_replace('/\D/', '', $input);
            if ($day <= 0) return null;
            return self::fromPigDay($day);
        }

        // Tenta parse de data gregoriana no formato brasileiro
        try {
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $input)) {
                return Carbon::createFromFormat('d/m/Y', $input)->startOfDay();
            }
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
                'recria' => 63,
                'terminacao' => 70,
                'cio' => 3, // Default cio duration
            ];
        }

        // Gregorian durations from meta table or defaults
        return [
            'gestacao' => self::metaInt('meta_gestacao_periodo_gestacao', self::metaInt('criterio_dias_gestacao', 114)),
            'lactacao' => self::metaInt('criterio_dias_lactacao_min', 21),
            'intervalo' => self::metaInt('meta_gestacao_intervalo_desmame_cobertura', self::metaInt('criterio_dias_intervalo_desmame_cio', 7)),
            'recria' => 63,
            'terminacao' => 70,
            'cio' => self::metaInt('meta_gestacao_montas_por_cobertura', self::metaInt('criterio_dias_cio', 3)),
        ];
    }

    private static function metaInt(string $key, int $default): int
    {
        if (!Schema::hasTable('meta')) {
            return $default;
        }

        $raw = DB::table('meta')->where('chave', $key)->value('valor');
        if ($raw === null || trim((string) $raw) === '') {
            return $default;
        }

        return (int) $raw;
    }

    public static function formatDisplayDate(?Carbon $date, ?Carbon $unused = null): string
    {
        if (!$date) return '-';
        
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

        // Alerta pré-parto (5 dias antes)
        $diffParto = $now->diffInDays($cycle['expectedBirthDate'], false);
        if ($diffParto >= 0 && $diffParto <= 5) {
            $alerts[] = "[{$animalId}]: parto previsto em {$diffParto} dias — preparar maternidade";
        }

        // Alerta desmame (No dia)
        if ($now->isSameDay($cycle['weaningDate'])) {
            $alerts[] = "[{$animalId}]: desmame hoje — retornar ao galpão de gestação";
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
