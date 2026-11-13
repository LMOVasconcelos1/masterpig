-- Migracao 0.19 - Acoes de cobertura nos movimentos do plantel

ALTER TABLE `femea_movimento`
    MODIFY COLUMN `acao` ENUM(
        'compra',
        'morte',
        'descarte',
        'venda',
        'cio',
        'salta_cio',
        'cobertura',
        'parto',
        'desmame',
        'morte_leitao'
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

ALTER TABLE `macho_movimento`
    MODIFY COLUMN `acao` ENUM(
        'compra',
        'morte',
        'descarte',
        'venda',
        'cobertura'
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
