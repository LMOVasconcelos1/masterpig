<?php

namespace App\Http\Middleware;

use App\Support\TenantDatabase;
use Closure;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureTenantSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        $db = $request->session()->get('tenant_db');
        $user = $request->session()->get('tenant_user');

        $guard = Auth::guard('web');
        $authKey = $guard instanceof SessionGuard ? $guard->getName() : ('login_web_'.sha1(SessionGuard::class));
        $hasAuthSession = $request->session()->has($authKey);
        $hasRememberCookie = $guard instanceof SessionGuard
            ? $request->cookies->has($guard->getRecallerName())
            : false;
        $requiresTenant = $hasAuthSession || $hasRememberCookie;

        if ($requiresTenant && (! is_string($db) || $db === '' || ! is_string($user) || $user === '')) {
            if ($guard instanceof SessionGuard) {
                $guard->logout();
            }
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'cnpj' => 'Selecione um CNPJ válido para acessar o sistema.',
            ]);
        }

        if ($requiresTenant) {
            try {
                TenantDatabase::applyDatabase($db, $user);
            } catch (Throwable $e) {
                report($e);

                if ($guard instanceof SessionGuard) {
                    $guard->logout();
                }
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'cnpj' => 'Não foi possível conectar ao banco deste CNPJ.',
                ]);
            }
        }

        return $next($request);
    }
}
