<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrecheLote extends Model
{
    use HasFactory;

    protected $table = 'creche_lotes';

    protected $fillable = [
        'nome',
        'caracteristicas',
        'situacao',
    ];

    public function compras(): HasMany
    {
        return $this->hasMany(\App\Models\CrecheCompra::class, 'lote_id');
    }

    public function terminacaoLotes(): HasMany
    {
        return $this->hasMany(TerminacaoLote::class, 'creche_lote_id');
    }
}
