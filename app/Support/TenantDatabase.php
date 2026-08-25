<?php

namespace App\Support;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class TenantDatabase
{
    public static function tenantConnectionName(): string
    {
        return 'tenant';
    }

    public static function baseConnectionName(): string
    {
        return (string) (config('masterpig.base_connection') ?: config('database.default'));
    }

    public static function normalizeCnpj(?string $cnpj): string
    {
        return preg_replace('/\D+/', '', (string) $cnpj) ?? '';
    }

    public static function tenantPrefix(): string
    {
        return strtolower((string) (config('masterpig.tenant_prefix') ?: 'mp'));
    }

    public static function databaseNameFromCnpj(string $cnpjDigits): string
    {
        return self::tenantPrefix().$cnpjDigits;
    }

    public static function usernameFromCnpj(string $cnpjDigits): string
    {
        return self::tenantPrefix().$cnpjDigits;
    }

    public static function password(): ?string
    {
        $value = config('masterpig.db_password');

        return $value === null ? null : (string) $value;
    }

    public static function ensureCanConnect(string $databaseName, string $username): void
    {
        // =============================================================
        // ⏱️  TIME LIMIT — evita FatalError "Maximum execution time"
        // =============================================================
        // Hostoo / Hostinger / 108.181.92.77: primeira conexão TCP
        // pode demorar ~30s (firewall anti-flood) + retries.
        // Aumentamos PARA 180s aqui.
        // =============================================================
        $limitIni = (int) ini_get('max_execution_time');
        if ($limitIni > 0 && $limitIni < 180) {
            @set_time_limit(180);
            @ini_set('max_execution_time', '180');
        }

        $baseConnection = self::baseConnectionName();
        if (! in_array($baseConnection, ['mysql', 'mariadb'], true)) {
            throw ValidationException::withMessages([
                'cnpj' => 'Conexão de banco não suportada.',
            ]);
        }

        $baseConfig = config("database.connections.$baseConnection");
        if (! is_array($baseConfig)) {
            throw ValidationException::withMessages([
                'cnpj' => 'Configuração de banco inválida.',
            ]);
        }

        $probeConnection = 'tenant_probe';
        $probeConfig = $baseConfig;
        $probeConfig['database'] = $databaseName;
        $probeConfig['username'] = $username;
        $probeConfig['password'] = self::password() ?? ($probeConfig['password'] ?? null);
        $pdoTimeoutConst = defined('PDO::ATTR_TIMEOUT') ? \PDO::ATTR_TIMEOUT : 2;
        $probeOptions = $probeConfig['options'] ?? [];
        $probeConfig['options'] = is_array($probeOptions)
            ? ($probeOptions + [$pdoTimeoutConst => 50])
            : [$pdoTimeoutConst => 50];
        Config::set("database.connections.$probeConnection", $probeConfig);

        // =============================================================
        // 🔁 RETRY DIRETO (sem warm-up separado)
        // =============================================================
        // 5 tentativas diretas de conexão COM o banco/usuário CORRETO
        // do CNPJ. Warm-up removido pois a Hostoo/anti-flood pode
        // interpretar múltiplas conexões rápidas como ataque.
        // Sleeps CRESCENTES (0.5s → 1s → 1.5s → 2s) dão tempo ao
        // firewall anti-flood de liberar a rota TCP.
        // =============================================================
        $probeException = null;
        $maxTries = 5;
        $sleeps     = [0, 500_000, 1_000_000, 1_500_000, 2_000_000];
        for ($tentativa = 1; $tentativa <= $maxTries; $tentativa++) {
            try {
                DB::purge($probeConnection);
                DB::connection($probeConnection)->selectOne('SELECT 1');
                $probeException = null;
                break;
            } catch (Throwable $e) {
                $probeException = $e;
                if ($tentativa < $maxTries) {
                    $sleepUs = (int) ($sleeps[$tentativa] ?? 1_000_000);
                    usleep($sleepUs);
                }
            }
        }

        if ($probeException !== null) {
            $e = $probeException;
            report($e);

            $message = (string) $e->getMessage();
            $code    = $e->getCode();

            if (str_contains($message, 'Unknown database')) {
                throw ValidationException::withMessages([
                    'cnpj' => 'Banco de dados do CNPJ não existe.',
                ]);
            }

            if (str_contains($message, 'Access denied')) {
                throw ValidationException::withMessages([
                    'cnpj' => 'Usuário do banco do CNPJ sem acesso ou senha incorreta.',
                ]);
            }

            $preview = trim($message);
            if ($preview === '') {
                $preview = get_class($e);
            }
            $preview = mb_strimwidth($preview, 0, 200, '…');

            throw ValidationException::withMessages([
                'cnpj' => 'Erro conexão ('.$maxTries.' tentativas · cód. '.(is_scalar($code) ? $code : '—').'): '.$preview,
            ]);
        }
    }

    public static function applyDatabase(string $databaseName, string $username): void
    {
        $baseConnection = self::baseConnectionName();
        if (! in_array($baseConnection, ['mysql', 'mariadb'], true)) {
            throw ValidationException::withMessages([
                'cnpj' => 'Conexão de banco não suportada.',
            ]);
        }

        $baseConfig = config("database.connections.$baseConnection");
        if (! is_array($baseConfig)) {
            throw ValidationException::withMessages([
                'cnpj' => 'Configuração de banco inválida.',
            ]);
        }

        $tenantConnection = self::tenantConnectionName();
        $tenantConfig = $baseConfig;
        $tenantConfig['database'] = $databaseName;
        $tenantConfig['username'] = $username;
        if (self::password() !== null) {
            $tenantConfig['password'] = self::password();
        }

        Config::set("database.connections.$tenantConnection", $tenantConfig);
        DB::purge($tenantConnection);

        try {
            DB::reconnect($tenantConnection);
            DB::setDefaultConnection($tenantConnection);
        } catch (Throwable $e) {
            report($e);
            $message = (string) $e->getMessage();
            $code    = $e->getCode();
            $preview = trim($message);
            if ($preview === '') $preview = get_class($e);
            $preview = mb_strimwidth($preview, 0, 200, '…');
            throw ValidationException::withMessages([
                'cnpj' => 'Erro ao ativar conexão tenant (cód. '.(is_scalar($code) ? $code : '—').'): '.$preview,
            ]);
        }
    }
}
