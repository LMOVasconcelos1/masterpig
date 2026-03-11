<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\NewRegistrationMail;
use App\Models\User;
use App\Support\TenantDatabase;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $cnpjDigits = TenantDatabase::normalizeCnpj($request->input('cnpj'));
        if (! preg_match('/^\d{14}$/', $cnpjDigits)) {
            throw ValidationException::withMessages([
                'cnpj' => 'CNPJ inválido.',
            ]);
        }

        $tenantDb = TenantDatabase::databaseNameFromCnpj($cnpjDigits);
        $tenantUser = TenantDatabase::usernameFromCnpj($cnpjDigits);
        TenantDatabase::ensureDatabaseExists($tenantDb, $tenantUser);
        TenantDatabase::applyDatabase($tenantDb, $tenantUser);

        $request->validate([
            'cnpj' => ['required', 'string'],
            'nome' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'string', 'max:14', 'unique:usuario,cpf'],
            'usuario' => ['required', 'string', 'max:255', 'unique:usuario,usuario'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:usuario,email'],
            'perfil' => ['required', 'string', 'in:consultor,operador,administrador'],
            'senha' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'nome' => $request->nome,
            'cpf' => $request->cpf,
            'usuario' => $request->usuario,
            'email' => $request->email,
            'perfil' => $request->perfil,
            'senha' => Hash::make($request->senha),
        ]);

        $request->session()->put('tenant_cnpj', $cnpjDigits);
        $request->session()->put('tenant_db', $tenantDb);
        $request->session()->put('tenant_user', $tenantUser);

        try {
            Mail::to('masterpig@mastersui.com.br')->send(new NewRegistrationMail([
                'cnpj' => $cnpjDigits,
                'database' => $tenantDb,
                'db_user' => $tenantUser,
                'nome' => $user->nome,
                'email' => $user->email,
                'cpf' => $user->cpf,
                'usuario' => $user->usuario,
                'perfil' => $user->perfil,
                'created_at' => now()->format('Y-m-d H:i:s'),
            ]));
        } catch (Throwable $e) {
            report($e);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', [], false));
    }
}
