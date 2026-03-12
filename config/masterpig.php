<?php

return [
    'db_password' => env('MASTERPIG_DB_PASSWORD', env('DB_PASSWORD')),
    'tenant_prefix' => env('MASTERPIG_TENANT_PREFIX', 'mp'),
    'app_url' => env('MASTERPIG_APP_URL', env('APP_URL', 'http://app.mastersui.com.br')),
    'base_connection' => env('MASTERPIG_BASE_CONNECTION', env('DB_CONNECTION', 'mysql')),
    'enforce_perfil_permissions' => env('MASTERPIG_ENFORCE_PERFIL_PERMISSIONS', false),
];
