<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\TenantDatabase;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplica as colunas extras da tabela "usuario" (RBAC: perfil, permissoes, nome)
 * no banco do tenant.
 *
 * ⚠️ ESTE MIDDLEWARE RODA APENAS SE:
 *    (1) Já passou por EnsureTenantSelected  →  conexão default JA É 'tenant'
 *    (2) A URL NÃO é de login / logout / autenticação / erros
 *
 * Se rodarmos Schema:: ANTES da conexão tenant existir, ou na request de
 * login em que TenantDatabase::ensureCanConnect tenta abrir probe, corrompemos
 * o handle PDO e o sistema mostra:
 *   "Não foi possível conectar ao banco deste CNPJ."
 *
 * Roda apenas 1 vez por processo PHP (static $executou) — não repete em cada request.
 */
class ApplyUserSchema
{
    /** @var bool Indica se a migração já foi executada neste processo PHP */
    private static bool $executou = false;

    /** @var int timeout máximo em segundos (aborta se passar) */
    private const MAX_SEGUNDOS = 8;

    public function handle(Request $request, Closure $next): Response
    {
        if (self::deveExecutarAgora($request)) {
            $this->aplicar();
        }

        return $next($request);
    }

    // ==============
    // Proteções
    // ==============

    private static function deveExecutarAgora(Request $request): bool
    {
        if (self::$executou === true) return false;

        // Proteção 1: não rodar em endpoints que envolvem login/auth/erro
        $uri = (string) ($request->server->get('REQUEST_URI') ?? '');
        $blacklist = [
            '/login',          // tela / submit de login
            '/logout',         // logout
            'password/',       // recuperação de senha
            'sanctum',         // API tokens
            '/_debugbar',
            '/livewire',
            '/api/',
            'broadcasting',
            '/up',             // health check
        ];
        foreach ($blacklist as $needle) {
            if (stripos($uri, $needle) !== false) {
                return false;
            }
        }

        // Proteção 2: só rodar se SESSÃO já tiver tenant_db (login já feito com sucesso).
        // LoginRequest / EnsureTenantSelected aplicam sessão ANTES; se não tiver, skip.
        try {
            $sess = $request->hasSession() ? $request->session() : null;
            if ($sess !== null) {
                $tenantDb = $sess->get('tenant_db');
                $tenantUser = $sess->get('tenant_user');
                if (! is_string($tenantDb) || $tenantDb === ''
                    || ! is_string($tenantUser) || $tenantUser === '') {
                    return false;
                }
            }
        } catch (\Throwable) {
            // Sessão indisponível / sem inicializar — skip total
            return false;
        }

        // Proteção 3: só rodar se conexão DEFAULT já for 'tenant'
        try {
            $conn = (string) config('database.default');
            $tenantConn = TenantDatabase::tenantConnectionName();
            if ($conn !== $tenantConn) {
                return false;
            }
        } catch (\Throwable) {
            return false;
        }

        return true;
    }

    // ==============
    // Aplicação real
    // ==============

    private function aplicar(): void
    {
        $start = microtime(true);
        $timeout = static function () use ($start): bool {
            return (microtime(true) - $start) > self::MAX_SEGUNDOS;
        };

        try {
            if ($timeout()) { self::$executou = true; return; }
            if (! Schema::hasTable('usuario')) { self::$executou = true; return; }

            $temPerfil = Schema::hasColumn('usuario', 'perfil');
            $temPermissoes = Schema::hasColumn('usuario', 'permissoes');
            $temNome = Schema::hasColumn('usuario', 'nome');

            if ($timeout()) { self::$executou = true; return; }

            if (! $temPerfil) {
                try {
                    Schema::table('usuario', static function ($table) {
                        $table->string('perfil', 32)->nullable()->default('operador')->after('usuario');
                    });
                } catch (\Throwable) {}
            }

            if ($timeout()) { self::$executou = true; return; }

            if (! $temPermissoes) {
                try {
                    Schema::table('usuario', static function ($table) {
                        $table->json('permissoes')->nullable()->after('perfil');
                    });
                } catch (\Throwable) {}
            }

            if ($timeout()) { self::$executou = true; return; }

            if (! $temNome) {
                try {
                    Schema::table('usuario', static function ($table) {
                        $table->string('nome')->nullable()->after('id');
                    });
                } catch (\Throwable) {}
            }

            if ($timeout()) { self::$executou = true; return; }

            // Normaliza perfis nulos/vazios para 'operador' — até 200 registros
            try {
                if (Schema::hasColumn('usuario', 'perfil')) {
                    DB::table('usuario')
                        ->where(static function ($q) {
                            $q->whereNull('perfil')->orWhere('perfil', '');
                        })
                        ->limit(200)
                        ->update(['perfil' => 'operador']);
                }
            } catch (\Throwable) {}

            if ($timeout()) { self::$executou = true; return; }

            // Preenche nome Nulo com o campo usuario (login) — até 200
            try {
                if (Schema::hasColumn('usuario', 'nome') && Schema::hasColumn('usuario', 'usuario')) {
                    DB::table('usuario')
                        ->whereNull('nome')
                        ->whereNotNull('usuario')
                        ->limit(200)
                        ->update(['nome' => DB::raw('usuario')]);
                }
            } catch (\Throwable) {}

            self::$executou = true;
        } catch (\Throwable) {
            // Em caso de qualquer erro, marca como executado para não travar novamente
            // neste processo PHP.
            self::$executou = true;
        }
    }
}
