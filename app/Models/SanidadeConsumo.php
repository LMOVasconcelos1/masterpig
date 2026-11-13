<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanidadeConsumo extends Model
{
    use HasFactory;

    protected $table = 'sanidade_consumos';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'sanidade_id',
        'fase',
        'quantidade',
        'lote',
        'lote_fabricacao',
        'data_inicial',
        'data_final',
    ];

    protected $casts = [
        'quantidade' => 'decimal:2',
        'data_inicial' => 'date',
        'data_final' => 'date',
    ];

    public function sanidade()
    {
        return $this->belongsTo(Sanidade::class, 'sanidade_id');
    }
}
