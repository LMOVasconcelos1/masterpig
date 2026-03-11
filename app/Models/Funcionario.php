<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Funcionario extends Model
{
    use HasFactory;

    protected $table = 'funcionario';

    public $timestamps = false;

    protected $fillable = [
        'nome',
        'usuario',
        'senha',
    ];

    protected $hidden = [
        'senha',
    ];
}

