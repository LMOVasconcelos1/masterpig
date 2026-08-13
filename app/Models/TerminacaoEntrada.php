<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerminacaoEntrada extends Model
{
    use HasFactory;

    protected $table = 'terminacao_entradas';

    protected $fillable = [
        'data_entrada',
        'lote_id',
        'localizacao',
        'baia',
        'quantidade',
        'peso_total',
        'peso_medio',
        'data_nascimento',
        'origem',
        'creche_lote_id',
        'creche_compra_id',
        'valor_compra',
        'valor_unitario',
        'fornecedor_id',
        'nota_fiscal',
        'serie_nf',
        'chave_nfe',
        'usuario_id',
        'observacoes',
    ];

    protected $casts = [
        'data_entrada' => 'date',
        'data_nascimento' => 'date',
        'quantidade' => 'integer',
        'peso_total' => 'decimal:2',
        'peso_medio' => 'decimal:2',
        'valor_compra' => 'decimal:2',
        'valor_unitario' => 'decimal:2',
    ];

    public function lote(): BelongsTo
    {
        return $this->belongsTo(TerminacaoLote::class, 'lote_id');
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }
}
