<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Verifica e aplica automaticamente as migrações do diretório banco_versions
 * no banco de dados do tenant logado.
 *
 * As versões aplicadas são registradas na tabela `mp_schema_versions` para
 * evitar reexecução. A versão 0.1 (dump completo) é considerada já aplicada,
 * pois o banco de dados do tenant já existe antes do login.
 */
class SchemaUpdater
{
    public const TRACKING_TABLE = 'mp_schema_versions';

    /**
     * Versão base (dump completo) — sempre considerada aplicada, pois o
     * banco de dados já existe quando o usuário faz login.
     */
    public const BASE_VERSION = '0.1';

    /**
     * Garante que a tabela de rastreamento existe.
     */
    public function ensureTrackingTable(): void
    {
        if (! Schema::hasTable(self::TRACKING_TABLE)) {
            Schema::create(self::TRACKING_TABLE, function ($table) {
                $table->string('version', 20)->primary();
                $table->timestamp('applied_at')->nullable();
            });
        }
    }

    /**
     * Retorna a lista de versões disponíveis no diretório banco_versions,
     * ordenadas numericamente (ex: 0.2, 0.3, ..., 0.17).
     *
     * @return array<int, array{version: string, file: string}>
     */
    public function availableVersions(): array
    {
        $dir = base_path('banco_versions');
        if (! is_dir($dir)) {
            return [];
        }

        $files = glob($dir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
        $versions = [];

        foreach ($files as $file) {
            $name = basename($file, '.sql');
            // Pula a versão base (dump completo)
            if ($name === self::BASE_VERSION) {
                continue;
            }
            if (preg_match('/^[\d.]+$/', $name)) {
                $versions[] = [
                    'version' => $name,
                    'file' => $file,
                ];
            }
        }

        // Ordena numericamente (0.2, 0.3, ..., 0.10, 0.11, ...)
        usort($versions, static function ($a, $b) {
            return version_compare($a['version'], $b['version']);
        });

        return $versions;
    }

    /**
     * Retorna as versões já aplicadas.
     *
     * @return array<string>
     */
    public function appliedVersions(): array
    {
        $this->ensureTrackingTable();

        try {
            return DB::table(self::TRACKING_TABLE)
                ->pluck('version')
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Retorna as versões pendentes (disponíveis mas não aplicadas).
     *
     * @return array<int, array{version: string, file: string}>
     */
    public function pendingVersions(): array
    {
        $applied = $this->appliedVersions();
        $appliedMap = array_flip($applied);

        return array_values(array_filter(
            $this->availableVersions(),
            static fn ($v) => ! isset($appliedMap[$v['version']])
        ));
    }

    /**
     * Verifica se há migrações pendentes.
     */
    public function hasPending(): bool
    {
        return count($this->pendingVersions()) > 0;
    }

    /**
     * Aplica a próxima migração pendente.
     *
     * @return array{version: string, success: bool, message: string, statements: int}
     */
    public function applyNext(): array
    {
        $pending = $this->pendingVersions();

        if (empty($pending)) {
            return [
                'version' => '',
                'success' => true,
                'message' => 'Nenhuma migração pendente.',
                'statements' => 0,
            ];
        }

        $next = $pending[0];

        return $this->applyVersion($next['version'], $next['file']);
    }

    /**
     * Aplica todas as migrações pendentes.
     *
     * @return array<int, array{version: string, success: bool, message: string}>
     */
    public function applyAll(): array
    {
        $results = [];

        while ($this->hasPending()) {
            $result = $this->applyNext();
            $results[] = $result;

            if (! $result['success']) {
                break;
            }
        }

        return $results;
    }

    /**
     * Aplica uma versão específica.
     *
     * @return array{version: string, success: bool, message: string, statements: int}
     */
    public function applyVersion(string $version, string $file): array
    {
        if (! file_exists($file)) {
            return [
                'version' => $version,
                'success' => false,
                'message' => 'Arquivo de migração não encontrado.',
                'statements' => 0,
            ];
        }

        $sql = file_get_contents($file);
        if ($sql === false || trim($sql) === '') {
            return [
                'version' => $version,
                'success' => false,
                'message' => 'Arquivo de migração vazio.',
                'statements' => 0,
            ];
        }

        $statements = $this->splitStatements($sql);
        $executed = 0;

        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '') {
                continue;
            }

            try {
                DB::unprepared($stmt);
                $executed++;
            } catch (Throwable $e) {
                // Ignora erros de "já existe" (tabela/coluna já criada)
                // para garantir idempotência.
                $msg = $e->getMessage();
                if ($this->isAlreadyExistsError($msg)) {
                    continue;
                }

                return [
                    'version' => $version,
                    'success' => false,
                    'message' => mb_strimwidth($msg, 0, 300, '…'),
                    'statements' => $executed,
                ];
            }
        }

        // Registra a versão como aplicada
        try {
            DB::table(self::TRACKING_TABLE)->updateOrInsert(
                ['version' => $version],
                ['applied_at' => now()]
            );
        } catch (Throwable $e) {
            return [
                'version' => $version,
                'success' => false,
                'message' => 'Erro ao registrar migração: '.mb_strimwidth($e->getMessage(), 0, 200, '…'),
                'statements' => $executed,
            ];
        }

        return [
            'version' => $version,
            'success' => true,
            'message' => 'Migração aplicada com sucesso.',
            'statements' => $executed,
        ];
    }

    /**
     * Divide o SQL em instruções individuais, removendo comentários.
     *
     * @return array<int, string>
     */
    private function splitStatements(string $sql): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $sql) ?: [];
        $clean = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            // Remove linhas de comentário
            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }
            // Remove comentários inline (-- )
            if (str_contains($trimmed, '--')) {
                $trimmed = trim(explode('--', $trimmed, 2)[0]);
            }
            if ($trimmed !== '') {
                $clean[] = $trimmed;
            }
        }

        $joined = implode("\n", $clean);

        // Divide por ponto e vírgula, preservando conteúdo
        $statements = preg_split('/;\s*(?:\n|$)/', $joined) ?: [];

        return array_values(array_filter(
            array_map('trim', $statements),
            static fn ($s) => $s !== ''
        ));
    }

    /**
     * Verifica se a mensagem de erro indica que a tabela/coluna já existe.
     */
    private function isAlreadyExistsError(string $message): bool
    {
        $patterns = [
            'already exists',
            'Duplicate column name',
            'Duplicate key name',
            'Duplicate foreign key',
            'Duplicate entry',
            'Can\'t DROP',
            'check that column/key exists',
            'Unknown column',
            'Cannot add foreign key constraint',
        ];

        foreach ($patterns as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}
