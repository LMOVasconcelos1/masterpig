<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlmoxarifadoAcerto extends Model
{
    use HasFactory;

    protected $table = 'almoxarifado_acertos';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'data_acerto',
        'almoxarifado_id',
        'estoque_anterior',
        'contagem',
        'diferenca',
    ];

    protected $casts = [
        'data_acerto' => 'date',
        'estoque_anterior' => 'decimal:2',
        'contagem' => 'decimal:2',
        'diferenca' => 'decimal:2',
    ];

    public function almoxarifado()
    {
        return $this->belongsTo(Almoxarifado::class, 'almoxarifado_id');
    }
}
