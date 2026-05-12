<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaternidadeDesmame extends Model
{
    use HasFactory;

    protected $table = 'maternidade_desmame';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'parto_id',
        'data',
        'quantidade',
        'peso_medio',
        'lote_destino',
        'localizacao_destino',
        'destino_matriz',
        'baia_matriz',
        'peso_matriz',
        'escore_corporal',
        'caracteristicas_desmame',
        'funcionario',
        'observacao',
    ];

    protected $casts = [
        'data' => 'date',
        'peso_medio' => 'decimal:2',
        'peso_matriz' => 'decimal:2',
    ];

    public function parto()
    {
        return $this->belongsTo(MaternidadeParto::class, 'parto_id');
    }
}
