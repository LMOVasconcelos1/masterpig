-- =============================================================================
-- MasterPig - Versão 0.12 - Módulo de Terminação (Fase de Engorda/Acabamento)
-- =============================================================================
-- Execute este script no BANCO DO TENANT (ex: mp00155232000119)
-- Compatível com MySQL 8.x e MariaDB 10.6+
-- Charset/collation: mesmo padrão do restante do sistema (utf8mb4_unicode_ci)
-- =============================================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

-- -----------------------------------------------------------------------------
-- 1) TABELA PRINCIPAL: terminacao_lotes
--    Equivalente a creche_lotes, com campos adicionais para terminação
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `terminacao_lotes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `caracteristicas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `situacao` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aberto' COMMENT 'aberto / fechado',
  `data_entrada` date DEFAULT NULL COMMENT 'Data do primeiro alojamento no lote',
  `quantidade_inicial` int unsigned DEFAULT 0 COMMENT 'Qtde inicial no momento da criação do lote',
  `origem` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'creche / compra / transferencia / outro',
  `creche_lote_id` bigint unsigned DEFAULT NULL COMMENT 'FK opcional para creche_lotes (lote de origem quando veio da creche)',
  `galpao` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `localizacao` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Baia/Setor principal',
  `meta_dias_terminacao` int unsigned DEFAULT 90 COMMENT 'Meta de dias na terminação (padrão 90 dias)',
  `meta_peso_abate_kg` decimal(6,2) DEFAULT 115.00 COMMENT 'Peso meta de abate em KG',
  `data_fechamento` date DEFAULT NULL,
  `usuario_id` bigint unsigned DEFAULT NULL COMMENT 'Usuário que criou o lote',
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_terminacao_lotes_nome` (`nome`),
  KEY `idx_terminacao_lotes_situacao` (`situacao`),
  KEY `idx_terminacao_lotes_data_entrada` (`data_entrada`),
  KEY `idx_terminacao_lotes_origem_creche` (`creche_lote_id`),
  CONSTRAINT `fk_terminacao_lotes_creche` FOREIGN KEY (`creche_lote_id`)
    REFERENCES `creche_lotes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lotes de terminação (fase de engorda/acabamento)';

-- -----------------------------------------------------------------------------
-- 2) TABELA: terminacao_entradas
--    Equivalente a creche_compras. Registra entradas de animais no lote:
--    - Transferência da creche (origem = 'creche', creche_compra_id opcional)
--    - Compra direta (origem = 'compra', fornecedor_id + nota_fiscal)
--    - Transferência interna entre lotes (origem = 'transferencia')
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `terminacao_entradas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_entrada` date NOT NULL,
  `lote_id` bigint unsigned NOT NULL COMMENT 'FK para terminacao_lotes',
  `localizacao` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `baia` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantidade` int unsigned NOT NULL,
  `peso_total` decimal(10,2) DEFAULT NULL COMMENT 'Peso total (kg) dos animais desta entrada',
  `peso_medio` decimal(6,2) DEFAULT NULL COMMENT 'Peso médio por cabeça (kg)',
  `data_nascimento` date DEFAULT NULL COMMENT 'Data média de nascimento (usada para cálculo de idade)',
  `origem` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'creche' COMMENT 'creche / compra / transferencia / outro',
  `creche_lote_id` bigint unsigned DEFAULT NULL COMMENT 'Lote de origem na creche (se houver)',
  `creche_compra_id` bigint unsigned DEFAULT NULL COMMENT 'Linha específica de creche_compras (opcional)',
  `valor_compra` decimal(12,2) DEFAULT NULL COMMENT 'Valor total da compra (se for entrada por compra)',
  `valor_unitario` decimal(10,2) DEFAULT NULL COMMENT 'Preço unitário por cabeça (se compra)',
  `fornecedor_id` bigint unsigned DEFAULT NULL,
  `nota_fiscal` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serie_nf` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chave_nfe` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_id` bigint unsigned DEFAULT NULL,
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_terminacao_entradas_lote` (`lote_id`),
  KEY `idx_terminacao_entradas_data` (`data_entrada`),
  KEY `idx_terminacao_entradas_origem` (`origem`),
  KEY `idx_terminacao_entradas_creche_lote` (`creche_lote_id`),
  KEY `idx_terminacao_entradas_fornecedor` (`fornecedor_id`),
  CONSTRAINT `fk_terminacao_entradas_lote` FOREIGN KEY (`lote_id`)
    REFERENCES `terminacao_lotes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_terminacao_entradas_creche_lote` FOREIGN KEY (`creche_lote_id`)
    REFERENCES `creche_lotes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_terminacao_entradas_fornecedor` FOREIGN KEY (`fornecedor_id`)
    REFERENCES `fornecedor` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Entradas de animais nos lotes de terminação';

-- -----------------------------------------------------------------------------
-- 3) TABELA: terminacao_mortes
--    Saídas por mortalidade. Mesmo padrão de creche_mortes + causa_id opcional
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `terminacao_mortes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_morte` date NOT NULL,
  `lote_id` bigint unsigned NOT NULL,
  `localizacao` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `baia` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantidade` int unsigned NOT NULL,
  `causa_id` bigint unsigned DEFAULT NULL COMMENT 'FK para tabela causa (preferencial, usar ao invés de texto)',
  `causa` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Causa em texto livre (compatibilidade)',
  `origem_identificacao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Identificação do animal, baia hospitalar, etc.',
  `peso_medio` decimal(6,2) DEFAULT NULL COMMENT 'Peso médio dos animais mortos (kg)',
  `tipo_morte` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'natural' COMMENT 'natural / acidente / eutanásia / outro',
  `usuario_id` bigint unsigned DEFAULT NULL,
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_terminacao_mortes_lote` (`lote_id`),
  KEY `idx_terminacao_mortes_data` (`data_morte`),
  KEY `idx_terminacao_mortes_causa` (`causa_id`),
  CONSTRAINT `fk_terminacao_mortes_lote` FOREIGN KEY (`lote_id`)
    REFERENCES `terminacao_lotes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_terminacao_mortes_causa` FOREIGN KEY (`causa_id`)
    REFERENCES `causa` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Mortes / mortalidade nos lotes de terminação';

-- -----------------------------------------------------------------------------
-- 4) TABELA: terminacao_transferencias
--    Movimentações INTERNAS: entre baias, galpões ou lotes de terminação
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `terminacao_transferencias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_transferencia` date NOT NULL,
  `lote_origem_id` bigint unsigned NOT NULL COMMENT 'Lote de onde os animais saem',
  `lote_destino_id` bigint unsigned DEFAULT NULL COMMENT 'Lote para onde vão (NULL = mesmo lote, só mudou de baia)',
  `localizacao_origem` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `baia_origem` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `localizacao_destino` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `baia_destino` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantidade` int unsigned NOT NULL,
  `peso_total` decimal(10,2) DEFAULT NULL,
  `peso_medio` decimal(6,2) DEFAULT NULL,
  `motivo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ex: agrupamento, hospital, desclassificação',
  `tipo` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'baia' COMMENT 'baia / lote / hospital / desclassificado',
  `usuario_id` bigint unsigned DEFAULT NULL,
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_terminacao_transferencias_data` (`data_transferencia`),
  KEY `idx_terminacao_transferencias_origem` (`lote_origem_id`),
  KEY `idx_terminacao_transferencias_destino` (`lote_destino_id`),
  KEY `idx_terminacao_transferencias_tipo` (`tipo`),
  CONSTRAINT `fk_terminacao_transferencias_origem` FOREIGN KEY (`lote_origem_id`)
    REFERENCES `terminacao_lotes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_terminacao_transferencias_destino` FOREIGN KEY (`lote_destino_id`)
    REFERENCES `terminacao_lotes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Transferências entre baias e/ou lotes de terminação';

-- -----------------------------------------------------------------------------
-- 5) TABELA: terminacao_vendas
--    Saídas finais: venda para abate / frigorífico. Principal saída do sistema.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `terminacao_vendas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_venda` date NOT NULL,
  `lote_id` bigint unsigned NOT NULL,
  `localizacao` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Baia/galpão de onde saiu',
  `quantidade` int unsigned NOT NULL,
  `peso_total_kg` decimal(12,2) DEFAULT NULL COMMENT 'Peso total enviado (balança da granja)',
  `peso_medio_kg` decimal(6,2) DEFAULT NULL COMMENT 'Peso médio por cabeça',
  `peso_frigorifico_kg` decimal(12,2) DEFAULT NULL COMMENT 'Peso recebido pelo frigorífico (opcional)',
  `rendimento_carcaca_pct` decimal(5,2) DEFAULT NULL COMMENT '% de rendimento (informado frigorífico)',
  `valor_unitario` decimal(10,2) DEFAULT NULL COMMENT 'Preço por kg vivo ou por cabeça',
  `valor_total` decimal(14,2) DEFAULT NULL COMMENT 'Valor total da venda',
  `comprador_id` bigint unsigned DEFAULT NULL COMMENT 'FK fornecedor (reutiliza tabela para cadastro de frigoríficos)',
  `frigorifico_nome` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nome do frigorífico (texto livre)',
  `motorista_nome` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placa_caminhao` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nota_fiscal_saida` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chave_nfe` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_saida` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'abate' COMMENT 'abate / venda_vivo / doacao / outro',
  `usuario_id` bigint unsigned DEFAULT NULL,
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_terminacao_vendas_data` (`data_venda`),
  KEY `idx_terminacao_vendas_lote` (`lote_id`),
  KEY `idx_terminacao_vendas_tipo` (`tipo_saida`),
  KEY `idx_terminacao_vendas_comprador` (`comprador_id`),
  CONSTRAINT `fk_terminacao_vendas_lote` FOREIGN KEY (`lote_id`)
    REFERENCES `terminacao_lotes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_terminacao_vendas_comprador` FOREIGN KEY (`comprador_id`)
    REFERENCES `fornecedor` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Vendas / Saídas para abate de lotes de terminação';

-- -----------------------------------------------------------------------------
-- 6) TABELA: terminacao_pesos
--    Controle de pesagens ao longo do tempo (para cálculo de GPD, conversão etc.)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `terminacao_pesos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_pesagem` date NOT NULL,
  `lote_id` bigint unsigned NOT NULL,
  `localizacao` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `baia` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantidade_amostra` int unsigned DEFAULT NULL COMMENT 'Qtde de animais pesados (amostra)',
  `quantidade_lote` int unsigned DEFAULT NULL COMMENT 'Qtde total de animais no lote na data',
  `peso_total_kg` decimal(12,2) DEFAULT NULL,
  `peso_medio_kg` decimal(6,2) NOT NULL COMMENT 'Peso médio obtido na pesagem',
  `peso_minimo_kg` decimal(6,2) DEFAULT NULL,
  `peso_maximo_kg` decimal(6,2) DEFAULT NULL,
  `desvio_padrao` decimal(6,2) DEFAULT NULL,
  `idade_dias` int unsigned DEFAULT NULL COMMENT 'Idade média dos animais no dia da pesagem',
  `gpd_medio` decimal(6,3) DEFAULT NULL COMMENT 'Ganho de peso diário médio desde a última pesagem (g/cabeça/dia)',
  `tipo_pesagem` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'amostra' COMMENT 'amostra / total / individual',
  `usuario_id` bigint unsigned DEFAULT NULL,
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_terminacao_pesos_data` (`data_pesagem`),
  KEY `idx_terminacao_pesos_lote` (`lote_id`),
  CONSTRAINT `fk_terminacao_pesos_lote` FOREIGN KEY (`lote_id`)
    REFERENCES `terminacao_lotes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Controle de pesagens dos lotes de terminação';

-- -----------------------------------------------------------------------------
-- 7) TABELA: terminacao_localizacoes  (OPCIONAL, se quiser controle fino)
--    Cadastro estruturado de galpões/baias (evita digitação livre em localizacao)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `terminacao_localizacoes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tipo` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'baia' COMMENT 'galpao / baia / setor / hospital / desclassificado',
  `codigo` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: G1-B3, HOSP-01, etc.',
  `nome` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: Galpão 1 - Baia 3',
  `capacidade_cabecas` int unsigned DEFAULT NULL,
  `situacao` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo' COMMENT 'ativo / inativo / manutencao',
  `ordenacao` int unsigned DEFAULT 0,
  `caracteristicas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_terminacao_localizacoes_codigo` (`codigo`),
  KEY `idx_terminacao_localizacoes_tipo` (`tipo`),
  KEY `idx_terminacao_localizacoes_situacao` (`situacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cadastro de galpões, baias e setores da terminação';

-- =============================================================================
-- 8) METAS E PARÂMETROS (inserts na tabela `meta` já existente)
--    Estrutura REAL da tabela `meta` (padrão dump 0.1.sql):
--       id | chave (UNIQUE) | valor | criado_em | atualizado_em
--    NÃO EXISTE a coluna 'descricao' (ela foi removida / nunca existiu).
--    UNIQUE KEY na coluna `chave` ? ON DUPLICATE KEY UPDATE funciona.
-- =============================================================================

-- Meta 1: Taxa de mortalidade ACEITÁVEL na terminação (%)
INSERT INTO `meta` (`chave`, `valor`, `criado_em`, `atualizado_em`)
VALUES (
  'meta_terminacao_mortalidade_pct',
  '3.0',
  NOW(), NOW()
)
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`), `atualizado_em` = NOW();

-- Meta 2: Dias padrão de permanência na terminação (meta padrão por lote)
INSERT INTO `meta` (`chave`, `valor`, `criado_em`, `atualizado_em`)
VALUES (
  'meta_terminacao_dias_permanencia',
  '90',
  NOW(), NOW()
)
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`), `atualizado_em` = NOW();

-- Meta 3: Peso meta de abate (kg)
INSERT INTO `meta` (`chave`, `valor`, `criado_em`, `atualizado_em`)
VALUES (
  'meta_terminacao_peso_abate_kg',
  '115.00',
  NOW(), NOW()
)
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`), `atualizado_em` = NOW();

-- Meta 4: Peso de ENTRADA na terminação (kg) - usado para cálculos
INSERT INTO `meta` (`chave`, `valor`, `criado_em`, `atualizado_em`)
VALUES (
  'meta_terminacao_peso_entrada_kg',
  '25.00',
  NOW(), NOW()
)
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`), `atualizado_em` = NOW();

-- Meta 5: Limite de dias SEM MOVIMENTAÇÃO para alerta de inconsistência
INSERT INTO `meta` (`chave`, `valor`, `criado_em`, `atualizado_em`)
VALUES (
  'meta_terminacao_dias_sem_movimento',
  '15',
  NOW(), NOW()
)
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`), `atualizado_em` = NOW();

-- Meta 6: Tamanho mínimo residual do lote (%) para alerta "lote residual"
INSERT INTO `meta` (`chave`, `valor`, `criado_em`, `atualizado_em`)
VALUES (
  'meta_terminacao_lote_residual_pct',
  '10',
  NOW(), NOW()
)
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`), `atualizado_em` = NOW();

-- =============================================================================
-- RESTAURA CONFIGURAÇÕES
-- =============================================================================
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- =============================================================================
-- FIM DO SCRIPT
-- =============================================================================
-- Próximos passos após rodar este SQL:
--   1. Confirmar que as 7 tabelas foram criadas no banco do tenant
--   2. Verificar se os 6 registros foram inseridos na tabela `meta`
--   3. Rodar também os arquivos de migration Laravel (se usar migration no seu fluxo)
--   4. Continuar com a criação dos Controllers, Models, Rotas e Views
-- =============================================================================
