<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerminacaoMorte extends Model
{
    use HasFactory;

    protected $table = 'terminacao_mortes';

    protected $fillable = [
        'data_morte',
        'lote_id',
        'localizacao',
        'baia',
        'quantidade',
        'causa_id',
        'causa',
        'origem_identificacao',
        'peso_medio',
        'tipo_morte',
        'usuario_id',
        'observacoes',
    ];

    protected $casts = [
        'data_morte' => 'date',
        'quantidade' => 'integer',
        'peso_medio' => 'decimal:2',
    ];

    public function lote(): BelongsTo
    {
        return $this->belongsTo(TerminacaoLote::class, 'lote_id');
    }

    public function causaRel(): BelongsTo
    {
        return $this->belongsTo(Causa::class, 'causa_id');
    }
}
