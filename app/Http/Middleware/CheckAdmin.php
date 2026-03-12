<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('masterpig.enforce_perfil_permissions', false)) {
            return $next($request);
        }

        if (Auth::check() && Auth::user()->perfil === 'administrador') {
            return $next($request);
        }

        abort(403, 'Acesso não autorizado. Você não tem permissão de administrador.');
    }
}
