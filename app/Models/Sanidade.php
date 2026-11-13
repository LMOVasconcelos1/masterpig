<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sanidade extends Model
{
    use HasFactory;

    protected $table = 'sanidade';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'codigo',
        'nome',
        'tipo',
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
        return $this->hasMany(SanidadeCompra::class, 'sanidade_id');
    }

    public function consumos()
    {
        return $this->hasMany(SanidadeConsumo::class, 'sanidade_id');
    }

    public function vendas()
    {
        return $this->hasMany(SanidadeVenda::class, 'sanidade_id');
    }

    public function acertos()
    {
        return $this->hasMany(SanidadeAcerto::class, 'sanidade_id');
    }
}
