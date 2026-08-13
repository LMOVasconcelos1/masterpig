<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerminacaoTransferencia extends Model
{
    use HasFactory;

    protected $table = 'terminacao_transferencias';

    protected $fillable = [
        'data_transferencia',
        'lote_origem_id',
        'lote_destino_id',
        'localizacao_origem',
        'baia_origem',
        'localizacao_destino',
        'baia_destino',
        'quantidade',
        'peso_total',
        'peso_medio',
        'motivo',
        'tipo',
        'usuario_id',
        'observacoes',
    ];

    protected $casts = [
        'data_transferencia' => 'date',
        'quantidade' => 'integer',
        'peso_total' => 'decimal:2',
        'peso_medio' => 'decimal:2',
    ];

    public function loteOrigem(): BelongsTo
    {
        return $this->belongsTo(TerminacaoLote::class, 'lote_origem_id');
    }

    public function loteDestino(): BelongsTo
    {
        return $this->belongsTo(TerminacaoLote::class, 'lote_destino_id');
    }
}
