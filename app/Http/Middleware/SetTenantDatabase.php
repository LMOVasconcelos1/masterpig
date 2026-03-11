<?php

namespace App\Http\Middleware;

use App\Support\TenantDatabase;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantDatabase
{
    public function handle(Request $request, Closure $next): Response
    {
        $db = $request->session()->get('tenant_db');
        $user = $request->session()->get('tenant_user');

        if (is_string($db) && $db !== '' && is_string($user) && $user !== '') {
            TenantDatabase::applyDatabase($db, $user);
        }

        return $next($request);
    }
}
