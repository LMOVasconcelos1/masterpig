<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RacaoConsumo extends Model
{
    use HasFactory;

    protected $table = 'racao_consumos';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'data_consumo',
        'racao_id',
        'quantidade',
        'fase',
        'lote',
        'lote_fabricacao',
    ];

    protected $casts = [
        'data_consumo' => 'date',
        'quantidade' => 'decimal:2',
    ];

    public function racao(): BelongsTo
    {
        return $this->belongsTo(Racao::class, 'racao_id');
    }
}
