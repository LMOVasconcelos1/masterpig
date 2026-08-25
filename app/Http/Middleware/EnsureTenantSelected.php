<?php

namespace App\Http\Middleware;

use App\Support\TenantDatabase;
use Closure;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
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
                Cookie::queue(Cookie::forget($guard->getRecallerName()));
            }
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->to(route('login', [], false))->withErrors([
                'cnpj' => 'Selecione um CNPJ válido para acessar o sistema.',
            ]);
        }

        if ($requiresTenant) {
            try {
                TenantDatabase::applyDatabase($db, $user);
            } catch (Throwable $e) {
                report($e);

                if ($guard instanceof SessionGuard) {
                    Cookie::queue(Cookie::forget($guard->getRecallerName()));
                }
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Se o applyDatabase já lançou ValidationException com mensagem REAL → REPASSA (não sobrescreve)
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    $errors = $e->errors();
                    $msgReal = (string) ($errors['cnpj'][0] ?? $e->getMessage());
                    return redirect()->to(route('login', [], false))->withErrors([
                        'cnpj' => $msgReal,
                    ]);
                }

                $message = (string) $e->getMessage();
                $preview = trim($message);
                if ($preview === '') $preview = get_class($e);
                $preview = mb_strimwidth($preview, 0, 200, '…');
                $codigo  = is_scalar($e->getCode()) ? (string) $e->getCode() : '—';
                return redirect()->to(route('login', [], false))->withErrors([
                    'cnpj' => 'Falha middleware tenant (cód. '.$codigo.'): '.$preview,
                ]);
            }
        }

        return $next($request);
    }
}
