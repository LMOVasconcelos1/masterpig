<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanidadeAcerto extends Model
{
    use HasFactory;

    protected $table = 'sanidade_acertos';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'data_acerto',
        'sanidade_id',
        'estoque_anterior',
        'contagem',
        'diferenca',
    ];

    protected $casts = [
        'data_acerto' => 'date',
        'estoque_anterior' => 'decimal:2',
        'contagem' => 'decimal:2',
        'diferenca' => 'decimal:2',
    ];

    public function sanidade()
    {
        return $this->belongsTo(Sanidade::class, 'sanidade_id');
    }
}
