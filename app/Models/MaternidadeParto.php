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
        'data_termino',
        'hora_inicio',
        'hora_termino',
        'total_vivos',
        'total_mortos',
        'total_mumificados',
        'qtd_femeas_vivas',
        'qtd_machos_vivos',
        'peso_total_leitoes_kg',
        'localizacao_matriz_parto',
        'baia',
        'peso_matriz_kg',
        'funcionario_nome',
        'tipo_intervencao',
        'escore_corporal',
        'caracteristicas_parto',
        'observacao',
    ];

    protected $casts = [
        'data'                     => 'date',
        'data_termino'             => 'date',
        'peso_matriz_kg'           => 'decimal:2',
        'peso_total_leitoes_kg'    => 'decimal:2',
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
