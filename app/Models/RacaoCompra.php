<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RacaoCompra extends Model
{
    use HasFactory;

    protected $table = 'racao_compras';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'data_compra',
        'racao_id',
        'quantidade',
        'valor_unitario',
        'valor_total',
        'nota_fiscal',
        'fornecedor_id',
        'lote_fabricacao',
    ];

    protected $casts = [
        'data_compra' => 'date',
        'quantidade' => 'decimal:2',
        'valor_unitario' => 'decimal:4',
        'valor_total' => 'decimal:2',
    ];

    public function racao(): BelongsTo
    {
        return $this->belongsTo(Racao::class, 'racao_id');
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }
}
