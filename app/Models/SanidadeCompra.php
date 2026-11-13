<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanidadeCompra extends Model
{
    use HasFactory;

    protected $table = 'sanidade_compras';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'data_compra',
        'sanidade_id',
        'quantidade',
        'valor_unitario',
        'valor_total',
        'nota_fiscal',
        'fornecedor',
        'lote_fabricacao',
    ];

    protected $casts = [
        'data_compra' => 'date',
        'quantidade' => 'decimal:2',
        'valor_unitario' => 'decimal:2',
        'valor_total' => 'decimal:2',
    ];

    public function sanidade()
    {
        return $this->belongsTo(Sanidade::class, 'sanidade_id');
    }
}
