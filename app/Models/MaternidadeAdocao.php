<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaternidadeAdocao extends Model
{
    use HasFactory;

    protected $table = 'maternidade_adocao';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'parto_origem_id',
        'parto_destino_id',
        'quantidade',
        'data',
        'observacao',
    ];

    protected $casts = [
        'data' => 'date',
    ];

    public function partoOrigem()
    {
        return $this->belongsTo(MaternidadeParto::class, 'parto_origem_id');
    }

    public function partoDestino()
    {
        return $this->belongsTo(MaternidadeParto::class, 'parto_destino_id');
    }
}
