<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlmoxarifadoVenda extends Model
{
    use HasFactory;

    protected $table = 'almoxarifado_vendas';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'data_venda',
        'almoxarifado_id',
        'quantidade',
        'valor_unitario',
        'valor_total',
        'comprador',
    ];

    protected $casts = [
        'data_venda' => 'date',
        'quantidade' => 'decimal:2',
        'valor_unitario' => 'decimal:2',
        'valor_total' => 'decimal:2',
    ];

    public function almoxarifado()
    {
        return $this->belongsTo(Almoxarifado::class, 'almoxarifado_id');
    }
}
