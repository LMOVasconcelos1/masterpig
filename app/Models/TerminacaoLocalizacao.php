<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TerminacaoLocalizacao extends Model
{
    use HasFactory;

    protected $table = 'terminacao_localizacoes';

    protected $fillable = [
        'tipo',
        'codigo',
        'nome',
        'capacidade_cabecas',
        'situacao',
        'ordenacao',
        'caracteristicas',
    ];

    protected $casts = [
        'capacidade_cabecas' => 'integer',
        'ordenacao' => 'integer',
    ];
}
