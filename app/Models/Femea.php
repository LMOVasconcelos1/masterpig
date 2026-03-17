<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Femea extends Model
{
    use HasFactory;

    protected $table = 'femea';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'id_primaria',
        'id_secundaria',
        'tipo_compra',
        'data_nascimento',
        'data_compra',
        'ciclos_ate_compra',
        'data_cobertura',
        'raca_id',
        'valor_compra',
        'peso_compra',
        'fornecedor_id',
        'caracteristicas',
        'localizacao',
        'baia',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'data_compra' => 'date',
        'data_cobertura' => 'date',
        'valor_compra' => 'decimal:2',
        'peso_compra' => 'decimal:2',
        'ciclos_ate_compra' => 'integer',
    ];

    public function raca()
    {
        return $this->belongsTo(Raca::class, 'raca_id');
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function movimentos()
    {
        return $this->hasMany(FemeaMovimento::class, 'femea_id');
    }
}
