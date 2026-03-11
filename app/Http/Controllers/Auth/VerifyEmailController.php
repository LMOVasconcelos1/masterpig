<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TenantDatabase;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        $publicUrl = rtrim((string) (config('masterpig.public_url') ?: config('masterpig.app_url') ?: config('app.url')), '/');

        $cnpjDigits = TenantDatabase::normalizeCnpj($request->query('cnpj'));
        if (! preg_match('/^\d{14}$/', $cnpjDigits)) {
            return redirect()->route('login')->withErrors([
                'cnpj' => 'CNPJ inválido.',
            ]);
        }

        $tenantDb = TenantDatabase::databaseNameFromCnpj($cnpjDigits);
        $tenantUser = TenantDatabase::usernameFromCnpj($cnpjDigits);
        try {
            TenantDatabase::ensureCanConnect($tenantDb, $tenantUser);
            TenantDatabase::applyDatabase($tenantDb, $tenantUser);

            $user = User::on(TenantDatabase::tenantConnectionName())->findOrFail($id);

            if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Link de verificação inválido.',
                ]);
            }

            if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors([
                'email' => 'Não foi possível verificar o e-mail.',
            ]);
        }

        return redirect()->to($publicUrl.'/login')->with('status', 'E-mail verificado com sucesso. Faça login para continuar.');
    }
}
