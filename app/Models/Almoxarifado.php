<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Almoxarifado extends Model
{
    use HasFactory;

    protected $table = 'almoxarifado';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'codigo',
        'nome',
        'grupo',
        'estoque',
        'custo_por_unidade',
        'fornecedor_id',
    ];

    protected $casts = [
        'estoque' => 'decimal:2',
        'custo_por_unidade' => 'decimal:2',
    ];

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function compras()
    {
        return $this->hasMany(AlmoxarifadoCompra::class, 'almoxarifado_id');
    }

    public function consumos()
    {
        return $this->hasMany(AlmoxarifadoConsumo::class, 'almoxarifado_id');
    }

    public function vendas()
    {
        return $this->hasMany(AlmoxarifadoVenda::class, 'almoxarifado_id');
    }

    public function acertos()
    {
        return $this->hasMany(AlmoxarifadoAcerto::class, 'almoxarifado_id');
    }
}
