<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RacaoAcerto extends Model
{
    use HasFactory;

    protected $table = 'racao_acertos';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'data_acerto',
        'racao_id',
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

    public function racao(): BelongsTo
    {
        return $this->belongsTo(Racao::class, 'racao_id');
    }
}
