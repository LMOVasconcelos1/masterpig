<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TerminacaoLote extends Model
{
    use HasFactory;

    protected $table = 'terminacao_lotes';

    protected $fillable = [
        'nome',
        'caracteristicas',
        'situacao',
        'data_entrada',
        'quantidade_inicial',
        'origem',
        'creche_lote_id',
        'galpao',
        'localizacao',
        'meta_dias_terminacao',
        'meta_peso_abate_kg',
        'data_fechamento',
        'usuario_id',
        'observacoes',
    ];

    protected $casts = [
        'data_entrada' => 'date',
        'data_fechamento' => 'date',
        'meta_peso_abate_kg' => 'decimal:2',
        'quantidade_inicial' => 'integer',
        'meta_dias_terminacao' => 'integer',
    ];

    public function crecheLote(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CrecheLote::class, 'creche_lote_id');
    }

    public function entradas(): HasMany
    {
        return $this->hasMany(TerminacaoEntrada::class, 'lote_id');
    }

    public function mortes(): HasMany
    {
        return $this->hasMany(TerminacaoMorte::class, 'lote_id');
    }

    public function transferenciasOrigem(): HasMany
    {
        return $this->hasMany(TerminacaoTransferencia::class, 'lote_origem_id');
    }

    public function transferenciasDestino(): HasMany
    {
        return $this->hasMany(TerminacaoTransferencia::class, 'lote_destino_id');
    }

    public function vendas(): HasMany
    {
        return $this->hasMany(TerminacaoVenda::class, 'lote_id');
    }

    public function pesos(): HasMany
    {
        return $this->hasMany(TerminacaoPeso::class, 'lote_id');
    }
}
