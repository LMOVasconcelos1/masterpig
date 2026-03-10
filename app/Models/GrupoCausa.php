<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoCausa extends Model
{
    use HasFactory;

    protected $table = 'grupo_causa';

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = ['nome'];

    public function causas()
    {
        return $this->hasMany(Causa::class);
    }
}
