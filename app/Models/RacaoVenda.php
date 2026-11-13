<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RacaoVenda extends Model
{
    use HasFactory;

    protected $table = 'racao_vendas';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'data_venda',
        'racao_id',
        'quantidade',
        'valor_unitario',
        'valor_total',
        'comprador',
    ];

    protected $casts = [
        'data_venda' => 'date',
        'quantidade' => 'decimal:2',
        'valor_unitario' => 'decimal:4',
        'valor_total' => 'decimal:2',
    ];

    public function racao(): BelongsTo
    {
        return $this->belongsTo(Racao::class, 'racao_id');
    }
}
