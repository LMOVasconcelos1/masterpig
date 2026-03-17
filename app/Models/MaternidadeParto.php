<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaternidadeParto extends Model
{
    use HasFactory;

    protected $table = 'maternidade_parto';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'femea_id',
        'cobertura_id',
        'lote',
        'data',
        'hora_inicio',
        'hora_termino',
        'total_vivos',
        'total_mortos',
        'total_mumificados',
        'observacao',
    ];

    protected $casts = [
        'data' => 'date',
    ];

    public function femea()
    {
        return $this->belongsTo(Femea::class, 'femea_id');
    }

    public function desmame()
    {
        return $this->hasOne(MaternidadeDesmame::class, 'parto_id');
    }

    public function adocoesOrigem()
    {
        return $this->hasMany(MaternidadeAdocao::class, 'parto_origem_id');
    }

    public function adocoesDestino()
    {
        return $this->hasMany(MaternidadeAdocao::class, 'parto_destino_id');
    }
}
