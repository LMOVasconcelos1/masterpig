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
        Config::set("database.connections.$probeConnection", $probeConfig);

        DB::purge($probeConnection);

        try {
            DB::connection($probeConnection)->selectOne('SELECT 1');
        } catch (Throwable $e) {
            report($e);

            $message = $e->getMessage();
            if (is_string($message) && str_contains($message, 'Unknown database')) {
                throw ValidationException::withMessages([
                    'cnpj' => 'Banco de dados do CNPJ não existe.',
                ]);
            }

            if (is_string($message) && str_contains($message, 'Access denied')) {
                throw ValidationException::withMessages([
                    'cnpj' => 'Usuário do banco do CNPJ sem acesso ou senha incorreta.',
                ]);
            }

            throw ValidationException::withMessages([
                'cnpj' => 'Não foi possível conectar ao banco deste CNPJ.',
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
        DB::reconnect($tenantConnection);
        DB::setDefaultConnection($tenantConnection);
    }
}
