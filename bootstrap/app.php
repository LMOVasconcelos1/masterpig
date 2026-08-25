<?php

use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\CheckAdmin::class,
        ]);

        $middleware->web(prepend: [
            \App\Http\Middleware\EnsureLocalSessionCookie::class,
        ]);

        $middleware->prependToPriorityList(
            AuthenticatesRequests::class,
            \App\Http\Middleware\EnsureTenantSelected::class,
            \App\Http\Middleware\ApplyUserSchema::class,
        );

        $middleware->web(append: [
            \App\Http\Middleware\EnsureTenantSelected::class,
            \App\Http\Middleware\ApplyUserSchema::class,
            \App\Http\Middleware\InjectDashboardNotifications::class,
            \App\Http\Middleware\EnforcePermissions::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sessão expirada. Atualize a página e tente novamente.',
                ], 419);
            }

            return redirect()
                ->to(route('login', [], false))
                ->with('status', 'Sessão expirada. Faça login novamente.');
        });
    })->create();
