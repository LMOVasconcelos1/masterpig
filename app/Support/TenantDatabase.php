<?php

namespace App\Support;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class TenantDatabase
{
    public static function normalizeCnpj(?string $cnpj): string
    {
        return preg_replace('/\D+/', '', (string) $cnpj) ?? '';
    }

    public static function tenantPrefix(): string
    {
        return (string) (config('masterpig.tenant_prefix') ?: 'mp');
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

    public static function ensureDatabaseExists(string $databaseName, string $username): void
    {
        $connection = (string) config('database.default');

        if (! in_array($connection, ['mysql', 'mariadb'], true)) {
            return;
        }

        $baseConfig = config("database.connections.$connection");
        if (! is_array($baseConfig)) {
            throw ValidationException::withMessages([
                'cnpj' => 'Configuração de banco inválida.',
            ]);
        }

        $probeConnection = 'tenant_probe';
        $probeConfig = $baseConfig;
        $probeConfig['database'] = 'information_schema';
        $probeConfig['username'] = $username;
        $probeConfig['password'] = self::password() ?? ($probeConfig['password'] ?? null);
        Config::set("database.connections.$probeConnection", $probeConfig);

        DB::purge($probeConnection);

        try {
            $row = DB::connection($probeConnection)->selectOne(
                'SELECT SCHEMA_NAME AS name FROM SCHEMATA WHERE SCHEMA_NAME = ? LIMIT 1',
                [$databaseName]
            );
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'cnpj' => 'Não foi possível validar o banco para este CNPJ.',
            ]);
        }

        if (! $row) {
            throw ValidationException::withMessages([
                'cnpj' => 'Nenhum banco encontrado para este CNPJ.',
            ]);
        }
    }

    public static function applyDatabase(string $databaseName, string $username): void
    {
        $connection = (string) config('database.default');

        if (! in_array($connection, ['mysql', 'mariadb'], true)) {
            return;
        }

        Config::set("database.connections.$connection.database", $databaseName);
        Config::set("database.connections.$connection.username", $username);
        if (self::password() !== null) {
            Config::set("database.connections.$connection.password", self::password());
        }
        DB::purge($connection);
        DB::reconnect($connection);
        DB::setDefaultConnection($connection);
    }
}
