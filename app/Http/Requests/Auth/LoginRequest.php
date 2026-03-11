<?php

namespace App\Http\Requests\Auth;

use App\Support\TenantDatabase;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cnpj' => ['required', 'string'],
            'identificador' => ['required', 'string'],
            'senha' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $cnpjDigits = TenantDatabase::normalizeCnpj($this->input('cnpj'));
        if (! preg_match('/^\d{14}$/', $cnpjDigits)) {
            throw ValidationException::withMessages([
                'cnpj' => 'CNPJ inválido.',
            ]);
        }

        $tenantDb = TenantDatabase::databaseNameFromCnpj($cnpjDigits);
        $tenantUser = TenantDatabase::usernameFromCnpj($cnpjDigits);
        TenantDatabase::ensureDatabaseExists($tenantDb, $tenantUser);
        TenantDatabase::applyDatabase($tenantDb, $tenantUser);

        $this->session()->put('tenant_cnpj', $cnpjDigits);
        $this->session()->put('tenant_db', $tenantDb);
        $this->session()->put('tenant_user', $tenantUser);

        $identificador = $this->input('identificador');
        $senha = $this->input('senha');

        // Determina o campo de login (email, cpf ou usuario)
        $loginField = filter_var($identificador, FILTER_VALIDATE_EMAIL) ? 'email' : (is_numeric($identificador) ? 'cpf' : 'usuario');

        $credentials = [
            $loginField => $identificador,
            'password' => $senha,
        ];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'identificador' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'identificador' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        $cnpj = TenantDatabase::normalizeCnpj($this->input('cnpj'));

        return Str::transliterate(Str::lower($cnpj.'|'.$this->input('identificador')).'|'.$this->ip());
    }
}
