<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracao extends Model
{
    use HasFactory;

    protected $table = 'configuracoes';

    protected $fillable = [
        'granja',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Obtém a configuração atual da granja
     */
    public static function getGranjaAtual(): string
    {
        $config = static::first();
        return $config ? $config->granja : 'MasterPig';
    }

    /**
     * Atualiza o nome da granja
     */
    public static function atualizarGranja(string $nomeGranja): void
    {
        static::updateOrCreate(
            ['id' => 1],
            ['granja' => $nomeGranja, 'updated_at' => now()]
        );
    }
}
