-- Versão 0.11 - Correção do ENUM da tabela femea_movimento
-- Adicionando 'cio' e 'salta_cio' ao ENUM da coluna 'acao'

-- Atualizar o ENUM da coluna 'acao' para incluir 'cio' e 'salta_cio'
ALTER TABLE `femea_movimento` MODIFY COLUMN `acao` ENUM('compra', 'morte', 'descarte', 'venda', 'cio', 'salta_cio') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

-- Adicionar índice para as novas ações se não existir
ALTER TABLE `femea_movimento` ADD INDEX IF NOT EXISTS `idx_femea_movimento_acao_cio` (`acao`) WHERE `acao` IN ('cio', 'salta_cio');
