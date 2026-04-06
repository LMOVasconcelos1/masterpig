<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CioCountingService
{
    /**
     * Calcula o número do cio atual para uma fêmea
     * 
     * @param int $femeaId ID da fêmea
     * @param Carbon|null $lastCobertura Data da última cobertura (se existir)
     * @param bool $includeCurrent Se deve incluir o cio atual na contagem
     * @return int Número do cio atual
     */
    public static function calcularNumeroCioAtual(int $femeaId, ?Carbon $lastCobertura = null, bool $includeCurrent = false): int
    {
        if (!Schema::hasTable('gestacao_cio')) {
            return 1;
        }

        $query = DB::table('gestacao_cio')->where('femea_id', $femeaId);
        
        // Se houve cobertura, conta apenas cios APÓS a cobertura
        if ($lastCobertura) {
            $query->where('data', '>', $lastCobertura->toDateString());
        }
        
        $countCios = (int) $query->count();
        
        // Se não encontrou cios, é o primeiro cio
        return $countCios > 0 ? $countCios : 1;
    }
    
    /**
     * Calcula o número do cio para uma data específica (sequencial por data)
     * 
     * @param int $femeaId ID da fêmea
     * @param Carbon $dataCio Data do cio a ser contado
     * @param Carbon|null $lastCobertura Data da última cobertura (se existir)
     * @return int Número do cio na data especificada (sequencial)
     */
    public static function calcularNumeroCioPorData(int $femeaId, Carbon $dataCio, ?Carbon $lastCobertura = null): int
    {
        if (!Schema::hasTable('gestacao_cio')) {
            return 1;
        }

        // Busca todos os cios em ordem cronológica até a data especificada
        $query = DB::table('gestacao_cio')
            ->where('femea_id', $femeaId)
            ->where('data', '<=', $dataCio->toDateString())
            ->orderBy('data', 'asc');
        
        // Se houve cobertura, considera apenas cios APÓS a cobertura
        if ($lastCobertura) {
            $query->where('data', '>', $lastCobertura->toDateString());
        }
        
        // Conta quantos cios existem até esta data (incluindo o atual)
        $countCios = $query->count();
        
        // Se não encontrou cios, é o primeiro cio
        return $countCios > 0 ? $countCios : 1;
    }
    
    /**
     * Obtém todos os cios de uma fêmea com numeração sequencial correta
     * 
     * @param int $femeaId ID da fêmea
     * @param Carbon|null $lastCobertura Data da última cobertura (se existir)
     * @return array Array de cios com numeração sequencial
     */
    public static function obterCiosComNumeracao(int $femeaId, ?Carbon $lastCobertura = null): array
    {
        if (!Schema::hasTable('gestacao_cio')) {
            return [];
        }

        $query = DB::table('gestacao_cio')
            ->where('femea_id', $femeaId)
            ->orderBy('data', 'asc');
        
        // Se houve cobertura, considera apenas cios APÓS a cobertura
        if ($lastCobertura) {
            $query->where('data', '>', $lastCobertura->toDateString());
        }
        
        $cios = $query->get(['id', 'data', 'peso']);
        
        $result = [];
        $numeroSequencial = 1;
        
        foreach ($cios as $cio) {
            $result[] = [
                'id' => $cio->id,
                'data' => $cio->data,
                'peso' => $cio->peso,
                'numero_cio' => $numeroSequencial,
                'numero_cio_label' => $numeroSequencial . 'º cio'
            ];
            $numeroSequencial++;
        }
        
        return $result;
    }
    
    /**
     * Verifica se a fêmea está em período de cio
     * 
     * @param int $femeaId ID da fêmea
     * @param Carbon|null $lastCio Data do último cio
     * @param int $duracaoCio Duração do cio em dias (default: 3)
     * @return bool True se está em período de cio
     */
    public static function estaEmCio(int $femeaId, ?Carbon $lastCio = null, int $duracaoCio = 3): bool
    {
        if (!$lastCio) {
            return false;
        }
        
        $now = Carbon::today();
        $fimCio = (clone $lastCio)->addDays($duracaoCio);
        
        return $now->betweenIncluded($lastCio, $fimCio);
    }
    
    /**
     * Calcula a data prevista para o próximo cio
     * 
     * @param Carbon|null $lastEventoCio Data do último evento de cio (cio ou salta-cio)
     * @param int $diasAteCio Dias até o próximo cio (default: 21)
     * @return Carbon|null Data previda para o próximo cio
     */
    public static function calcularProximoCio(?Carbon $lastEventoCio, int $diasAteCio = 21): ?Carbon
    {
        if (!$lastEventoCio) {
            return null;
        }
        
        return (clone $lastEventoCio)->addDays(max(1, $diasAteCio));
    }
    
    /**
     * Gera resumo estatístico dos cios de uma fêmea
     * 
     * @param int $femeaId ID da fêmea
     * @param Carbon|null $lastCobertura Data da última cobertura
     * @return array Array com estatísticas dos cios
     */
    public static function gerarResumoCios(int $femeaId, ?Carbon $lastCobertura = null): array
    {
        $resumo = [
            'total_cios' => 0,
            'cios_pos_cobertura' => 0,
            'numero_cio_atual' => 1,
            'proximo_cio' => null,
            'esta_em_cio' => false,
            'dias_desde_ultimo_cio' => null,
        ];
        
        if (!Schema::hasTable('gestacao_cio')) {
            return $resumo;
        }
        
        // Total de cios da fêmea
        $resumo['total_cios'] = DB::table('gestacao_cio')
            ->where('femea_id', $femeaId)
            ->count();
        
        // Cios após a última cobertura
        if ($lastCobertura) {
            $resumo['cios_pos_cobertura'] = DB::table('gestacao_cio')
                ->where('femea_id', $femeaId)
                ->where('data', '>', $lastCobertura->toDateString())
                ->count();
        }
        
        // Número do cio atual
        $resumo['numero_cio_atual'] = self::calcularNumeroCioAtual($femeaId, $lastCobertura);
        
        // Último cio
        $ultimoCio = DB::table('gestacao_cio')
            ->where('femea_id', $femeaId)
            ->orderBy('data', 'desc')
            ->first(['data']);
            
        if ($ultimoCio) {
            $dataUltimoCio = Carbon::parse($ultimoCio->data);
            $resumo['dias_desde_ultimo_cio'] = $dataUltimoCio->diffInDays(Carbon::today());
            $resumo['esta_em_cio'] = self::estaEmCio($femeaId, $dataUltimoCio);
            $resumo['proximo_cio'] = self::calcularProximoCio($dataUltimoCio);
        }
        
        return $resumo;
    }
}
