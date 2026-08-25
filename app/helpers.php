<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Helpers globais do MasterPig (RBAC)
|--------------------------------------------------------------------------
|
| ⚠️ IMPORTANTE:
|   Este arquivo é carregado via composer autoload.files ANTES do Laravel
|   inicializar sessão / middlewares / facades.
|   Por isso: NÃO USE FACADES (Auth, Schema, DB, etc.) NO NÍVEL SUPERIOR
|   DO ARQUIVO. Toda facade DEVE ficar DENTRO das funções, que só são
|   chamadas após o pipeline de middlewares do Laravel.
|
|   Isso evita corromper o handle PDO antes que TenantDatabase configure
|   a conexão do tenant (banco do CNPJ) e cause o erro:
|      "Não foi possível conectar ao banco deste CNPJ."
|
*/

if (! function_exists('perm')) {
    /**
     * Helper global para checar permissão do usuário autenticado.
     *
     * Uso (views Blade / controllers):
     *   @if(perm('plantel.lancamentos.femeas', true)) ... @endif
     *
     * @param  string  $chave   Exemplo: 'plantel.lancamentos.compras.leitoas'
     * @param  bool    $escrita True = ação de criar/editar/excluir; False = só visualizar
     */
    function perm(string $chave, bool $escrita = false): bool
    {
        static $service = null;

        try {
            if ($service === null) {
                // Carrega o service apenas na 1ª chamada, não no autoload.
                $service = \App\Services\PermissaoService::class;
            }

            return $service::check(null, $chave, $escrita);
        } catch (\Throwable) {
            return false;
        }
    }
}

if (! function_exists('perfil_usuario')) {
    /**
     * Retorna o perfil do usuário autenticado (string).
     *
     * Possíveis valores retornados:
     *   'administrador' | 'operador' | 'leitor'
     *
     * Default (falha / não autenticado): 'operador'
     */
    function perfil_usuario(): string
    {
        try {
            // Auth facade SÓ carregado aqui, dentro da função (não no autoload)
            $auth = \Illuminate\Support\Facades\Auth::class;

            if (! $auth::check()) {
                return 'operador';
            }

            /** @var object|null $u */
            $u = $auth::user();
            if ($u === null) {
                return 'operador';
            }

            return (string) ($u->perfil ?? 'operador');
        } catch (\Throwable) {
            return 'operador';
        }
    }
}

if (! function_exists('is_admin')) {
    /**
     * Helper rápido: retorna true se o usuário autenticado é Administrador.
     */
    function is_admin(): bool
    {
        return perfil_usuario() === \App\Services\PermissaoService::PERFIL_ADMIN;
    }
}
