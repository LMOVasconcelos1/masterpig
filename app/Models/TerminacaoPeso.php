<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerminacaoPeso extends Model
{
    use HasFactory;

    protected $table = 'terminacao_pesos';

    protected $fillable = [
        'data_pesagem',
        'lote_id',
        'localizacao',
        'baia',
        'quantidade_amostra',
        'quantidade_lote',
        'peso_total_kg',
        'peso_medio_kg',
        'peso_minimo_kg',
        'peso_maximo_kg',
        'desvio_padrao',
        'idade_dias',
        'gpd_medio',
        'tipo_pesagem',
        'usuario_id',
        'observacoes',
    ];

    protected $casts = [
        'data_pesagem' => 'date',
        'quantidade_amostra' => 'integer',
        'quantidade_lote' => 'integer',
        'peso_total_kg' => 'decimal:2',
        'peso_medio_kg' => 'decimal:2',
        'peso_minimo_kg' => 'decimal:2',
        'peso_maximo_kg' => 'decimal:2',
        'desvio_padrao' => 'decimal:2',
        'idade_dias' => 'integer',
        'gpd_medio' => 'decimal:3',
    ];

    public function lote(): BelongsTo
    {
        return $this->belongsTo(TerminacaoLote::class, 'lote_id');
    }
}
