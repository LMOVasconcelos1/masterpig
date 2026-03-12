<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'cpf' => (string) fake()->unique()->numerify('###########'),
            'usuario' => fake()->unique()->userName(),
            'perfil' => 'administrador',
            'senha' => static::$password ??= Hash::make('password'),
            'lembrete_token' => Str::random(10),
        ];
    }
}
