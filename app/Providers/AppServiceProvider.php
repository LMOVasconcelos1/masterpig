<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $explicitAppUrl = env('MASTERPIG_APP_URL');
        $shouldForceAppUrl = ! app()->environment('local')
            || (is_string($explicitAppUrl) && trim($explicitAppUrl) !== '');

        if ($shouldForceAppUrl) {
            $appUrl = (string) (config('masterpig.app_url') ?: config('app.url'));
            URL::forceRootUrl($appUrl);
            $scheme = parse_url($appUrl, PHP_URL_SCHEME);
            if (is_string($scheme) && $scheme !== '') {
                URL::forceScheme($scheme);
            }
        }

        //
        // ⚠️ APP SERVICE PROVIDER VAZIO (como era ANTES)
        // - NUNCA colocar View::composer com DB/Schema/Auth aqui (boot
        //   roda ANTES de middlewares e LoginRequest).
        // - Notificações do layout dashboard são injetadas pelo MIDDLEWARE
        //   InjectDashboardNotifications que roda NO FINAL do pipeline web,
        //   DEPOIS de EnsureTenantSelected + ApplyUserSchema.
        //
    }
}
