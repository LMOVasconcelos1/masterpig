<?php

use Illuminate\Support\Facades\Route;

// ATENÇÃO: este arquivo NÃO está registrado em `bootstrap/app.php` (apenas `web`
// e `commands` são carregados), portanto a rota abaixo não está ativa hoje.
// Mantido seguro de forma defensiva: exige sessão autenticada REAL.
//
// Problema original: o middleware `auth:sanctum` só era adicionado se o pacote
// Sanctum existisse — e ele NÃO está no composer.json. Resultado: quando ativa,
// a rota ficava 100% pública e expunha nomes/perfis de usuários do tenant.
// Não dependemos mais de Sanctum: usamos `web` + `auth` (guard de sessão padrão).
Route::middleware(['web', 'auth'])->get('/mobile/bootstrap', \App\Http\Controllers\MobileBootstrapController::class);
