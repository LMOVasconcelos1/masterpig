<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ÍNDICES CRÍTICOS DE PERFORMANCE — evitam timeout "Maximum execution time"
     * no Dashboard após login (queries femea_movimento / macho_movimento).
     *
     * Cenário sem estes índices (antes):
     *   whereNotExists / whereNotIn em femea_movimento  → full scan N×M
     *   MAX(data) GROUP BY femea_id                     → filesort 100% tabela
     *
     * Com índices (depois):
     *   Mesmas queries → index range scan / loose index scan (ms).
     */
    public function up(): void
    {
        $idx = static function (string $table, string $columns, string $name): void {
            try {
                $exists = DB::selectOne(
                    "SELECT COUNT(1) as c FROM information_schema.STATISTICS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = ?
                       AND INDEX_NAME = ?",
                    [$table, $name]
                );
                if ($exists && (int) ($exists->c ?? 0) > 0) {
                    return;
                }
                DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$name}` ({$columns})");
            } catch (\Throwable) {
                // Falha individual de 1 índice NÃO deve abortar a migration.
                // Tenant pode ter tabela parcial / versão antiga do schema.
            }
        };

        // ===== femea / femea_movimento =====
        $idx('femea',            'tipo_compra',                                         'femea_tipo_idx');
        $idx('femea_movimento',  'femea_id, acao',                                      'fm_femea_acao_idx');
        $idx('femea_movimento',  'acao, data',                                          'fm_acao_data_idx');
        $idx('femea_movimento',  'femea_id',                                            'fm_femea_id_idx');

        // ===== macho / macho_movimento =====
        $idx('macho_movimento',  'macho_id, acao',                                      'mm_macho_acao_idx');
        $idx('macho_movimento',  'acao, data',                                          'mm_acao_data_idx');
        $idx('macho_movimento',  'macho_id',                                            'mm_macho_id_idx');

        // ===== gestação (cobertura / cio / salta cio) =====
        $idx('gestacao_cobertura', 'femea_id, data',                                    'gc_femea_data_idx');
        $idx('gestacao_cobertura', 'macho_id, data',                                    'gc_macho_data_idx');
        $idx('gestacao_cio',       'femea_id, data',                                    'gcio_femea_data_idx');
        $idx('gestacao_salta_cio', 'femea_id, data',                                    'gsc_femea_data_idx');

        // ===== maternidade =====
        $idx('maternidade_parto',   'femea_id',                                         'mp_femea_id_idx');
        $idx('maternidade_parto',   'data',                                             'mp_data_idx');
        $idx('maternidade_desmame', 'parto_id',                                         'md_parto_id_idx');
        if (DB::selectOne("SHOW COLUMNS FROM maternidade_desmame LIKE 'data'") !== null) {
            $idx('maternidade_desmame', 'data',                                         'md_data_idx');
        }

        // ===== meta (chave única / lookup 1 linha) =====
        try {
            $exists = DB::selectOne(
                "SELECT COUNT(1) as c FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'meta' AND INDEX_NAME = 'meta_chave_uq'"
            );
            if (! $exists || (int) ($exists->c ?? 0) === 0) {
                DB::statement("ALTER TABLE `meta` ADD UNIQUE INDEX `meta_chave_uq` (chave)");
            }
        } catch (\Throwable) {}

        // ===== terminação — todas tabelas filhas usam lote_id como FK =====
        $tabelasTerminacao = [
            ['terminacao_entradas',     'lote_id',           'te_lote_idx',  ['data_entrada']],
            ['terminacao_mortes',       'lote_id',           'tm_lote_idx',  ['data_morte']],
            ['terminacao_transferencias','lote_origem_id',   'ttr_orig_idx', ['data_transferencia']],
            ['terminacao_transferencias','lote_destino_id',  'ttr_dest_idx', null],
            ['terminacao_vendas',       'lote_id',           'tv_lote_idx',  ['data_venda']],
            ['terminacao_pesos',        'lote_id',           'tp_lote_idx',  ['data_pesagem']],
        ];
        foreach ($tabelasTerminacao as [$t, $col, $name, $extras]) {
            try {
                if (! DB::selectOne("SHOW TABLES LIKE '{$t}'")) {
                    continue;
                }
            } catch (\Throwable) {
                continue;
            }
            $idx($t, $col, $name);
            if (is_array($extras)) {
                foreach ($extras as $eC) {
                    $idx($t, $eC, $name.'_'.str_replace('_', '', $eC));
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $drop = static function (string $table, string $name): void {
            try {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
            } catch (\Throwable) {}
        };

        $drop('femea','femea_tipo_idx');
        $drop('femea_movimento','fm_femea_acao_idx');
        $drop('femea_movimento','fm_acao_data_idx');
        $drop('femea_movimento','fm_femea_id_idx');
        $drop('macho_movimento','mm_macho_acao_idx');
        $drop('macho_movimento','mm_acao_data_idx');
        $drop('macho_movimento','mm_macho_id_idx');
        $drop('gestacao_cobertura','gc_femea_data_idx');
        $drop('gestacao_cobertura','gc_macho_data_idx');
        $drop('gestacao_cio','gcio_femea_data_idx');
        $drop('gestacao_salta_cio','gsc_femea_data_idx');
        $drop('maternidade_parto','mp_femea_id_idx');
        $drop('maternidade_parto','mp_data_idx');
        $drop('maternidade_desmame','md_parto_id_idx');
        try { DB::statement("ALTER TABLE `meta` DROP INDEX `meta_chave_uq`"); } catch (\Throwable) {}
        $drop('terminacao_entradas','te_lote_idx');
        $drop('terminacao_mortes','tm_lote_idx');
        $drop('terminacao_transferencias','ttr_orig_idx');
        $drop('terminacao_transferencias','ttr_dest_idx');
        $drop('terminacao_vendas','tv_lote_idx');
        $drop('terminacao_pesos','tp_lote_idx');
    }
};
