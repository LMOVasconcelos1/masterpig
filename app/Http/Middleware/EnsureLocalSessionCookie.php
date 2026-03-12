<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureLocalSessionCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = Str::lower($request->getHost());

        if ($host === 'localhost' || $host === '127.0.0.1' || app()->environment('local')) {
            config([
                'session.domain' => null,
                'session.secure' => false,
            ]);
        }

        return $next($request);
    }
}
