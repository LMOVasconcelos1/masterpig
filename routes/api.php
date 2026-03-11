<?php

use Illuminate\Support\Facades\Route;

$middleware = ['api'];
if (class_exists('Laravel\\Sanctum\\Sanctum')) {
    $middleware[] = 'auth:sanctum';
}

Route::middleware($middleware)->get('/mobile/bootstrap', \App\Http\Controllers\MobileBootstrapController::class);
