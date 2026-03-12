<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'usuario';

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'criado_em';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'atualizado_em';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome',
        'email',
        'cpf',
        'usuario',
        'perfil',
        'senha',
        'foto_perfil',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'senha',
        'lembrete_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verificado_em' => 'datetime',
            'senha' => 'hashed',
        ];
    }

    /**
     * Get the name of the password attribute.
     *
     * @return string
     */
    public function getAuthPasswordName()
    {
        return 'senha';
    }

    /**
     * Get the name of the email verification date column.
     *
     * @return string
     */
    public function getEmailVerifiedAtColumn()
    {
        return 'email_verificado_em';
    }

    /**
     * Mark the given user's email as verified.
     *
     * @return bool
     */
    public function markEmailAsVerified()
    {
        return $this->forceFill([
            $this->getEmailVerifiedAtColumn() => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Determine if the user has verified their email address.
     *
     * @return bool
     */
    public function hasVerifiedEmail()
    {
        return true;
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new \Illuminate\Auth\Notifications\VerifyEmail);
    }

    /**
     * Get the name of the "remember me" token column.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'lembrete_token';
    }

    public function getFotoPerfilUrlAttribute(): ?string
    {
        $path = (string) ($this->foto_perfil ?? '');

        if ($path === '') {
            return null;
        }

        return route('profile.photo', ['path' => $path], false);
    }
}
