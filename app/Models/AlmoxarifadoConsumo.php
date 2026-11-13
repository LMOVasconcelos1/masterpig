<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlmoxarifadoConsumo extends Model
{
    use HasFactory;

    protected $table = 'almoxarifado_consumos';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'almoxarifado_id',
        'fase',
        'quantidade',
        'lote',
        'lote_fabricacao',
        'data_consumo',
    ];

    protected $casts = [
        'quantidade' => 'decimal:2',
        'data_consumo' => 'date',
    ];

    public function almoxarifado()
    {
        return $this->belongsTo(Almoxarifado::class, 'almoxarifado_id');
    }
}
