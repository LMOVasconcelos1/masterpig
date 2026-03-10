<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FemeaMovimento extends Model
{
    use HasFactory;

    protected $table = 'femea_movimento';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'femea_id',
        'acao',
        'data',
        'valor',
        'peso',
        'fornecedor_id',
        'observacoes',
    ];

    protected $casts = [
        'data' => 'date',
        'valor' => 'decimal:2',
        'peso' => 'decimal:2',
    ];

    public function femea()
    {
        return $this->belongsTo(Femea::class, 'femea_id');
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }
}

