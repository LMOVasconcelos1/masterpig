<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Racao extends Model
{
    use HasFactory;

    protected $table = 'racao';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'codigo',
        'nome',
        'classificacao',
        'tipo_racao_id',
        'fase_animal',
        'estoque',
        'proteina_bruta',
        'energia_metabolizavel',
        'fibra',
        'lisina',
        'calcio',
        'fosforo',
        'fornecedor_id',
        'marca',
        'custo_por_kg',
        'unidade_compra',
        'peso_embalagem',
    ];

    protected $casts = [
        'estoque' => 'decimal:2',
        'proteina_bruta' => 'decimal:2',
        'energia_metabolizavel' => 'decimal:2',
        'fibra' => 'decimal:2',
        'lisina' => 'decimal:2',
        'calcio' => 'decimal:2',
        'fosforo' => 'decimal:2',
        'custo_por_kg' => 'decimal:2',
        'peso_embalagem' => 'decimal:2',
    ];

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function tipoRacao()
    {
        return $this->belongsTo(TipoRacao::class, 'tipo_racao_id');
    }
}
