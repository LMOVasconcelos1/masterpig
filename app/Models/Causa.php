<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Causa extends Model
{
    use HasFactory;

    protected $table = 'causa';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'codigo',
        'nome',
        'situacao',
        'grupo_causa_id',
    ];

    protected $casts = [
        'situacao' => 'boolean',
    ];

    public function grupoCausa()
    {
        return $this->belongsTo(GrupoCausa::class, 'grupo_causa_id');
    }
}
