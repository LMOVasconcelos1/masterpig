<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrecheCompra extends Model
{
    use HasFactory;

    protected $table = 'creche_compras';

    protected $fillable = [
        'data_compra',
        'lote_id',
        'localizacao',
        'quantidade',
        'peso_total',
        'data_nascimento',
        'valor_compra',
        'fornecedor_id',
        'nota_fiscal',
    ];

    protected $casts = [
        'data_compra' => 'date',
        'data_nascimento' => 'date',
        'quantidade' => 'integer',
        'peso_total' => 'decimal:2',
        'valor_compra' => 'decimal:2',
    ];

    public function lote(): BelongsTo
    {
        return $this->belongsTo(CrecheLote::class, 'lote_id');
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }
}
