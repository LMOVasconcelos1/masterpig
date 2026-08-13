<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerminacaoVenda extends Model
{
    use HasFactory;

    protected $table = 'terminacao_vendas';

    protected $fillable = [
        'data_venda',
        'lote_id',
        'localizacao',
        'quantidade',
        'peso_total_kg',
        'peso_medio_kg',
        'peso_frigorifico_kg',
        'rendimento_carcaca_pct',
        'valor_unitario',
        'valor_total',
        'comprador_id',
        'frigorifico_nome',
        'motorista_nome',
        'placa_caminhao',
        'nota_fiscal_saida',
        'chave_nfe',
        'tipo_saida',
        'usuario_id',
        'observacoes',
    ];

    protected $casts = [
        'data_venda' => 'date',
        'quantidade' => 'integer',
        'peso_total_kg' => 'decimal:2',
        'peso_medio_kg' => 'decimal:2',
        'peso_frigorifico_kg' => 'decimal:2',
        'rendimento_carcaca_pct' => 'decimal:2',
        'valor_unitario' => 'decimal:2',
        'valor_total' => 'decimal:2',
    ];

    public function lote(): BelongsTo
    {
        return $this->belongsTo(TerminacaoLote::class, 'lote_id');
    }

    public function comprador(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class, 'comprador_id');
    }
}
