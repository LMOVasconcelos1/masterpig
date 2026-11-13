-- =============================================================================
-- MasterPig - Versão 0.15 - Insumos: Nutrição (Consumos, Vendas e Acertos)
-- =============================================================================
-- Execute este script no BANCO DO TENANT (ex: mp00155232000119)
-- Compatível com MySQL 8.x e MariaDB 10.6+
-- Objetivo: criar as tabelas de consumos, vendas e acertos de estoque de ração.
-- =============================================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

-- -----------------------------------------------------------------------------
-- 1) Cria a tabela `racao_consumos`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `racao_consumos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_consumo` date NOT NULL,
  `racao_id` bigint unsigned NOT NULL,
  `quantidade` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Quantidade em KG',
  `fase` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Fase dos animais (Gestação, Lactação, Creche, etc.)',
  `lote` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lote_fabricacao` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_racao_consumos_racao` (`racao_id`),
  KEY `idx_racao_consumos_data` (`data_consumo`),
  CONSTRAINT `fk_racao_consumos_racao` FOREIGN KEY (`racao_id`) REFERENCES `racao` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2) Cria a tabela `racao_vendas`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `racao_vendas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_venda` date NOT NULL,
  `racao_id` bigint unsigned NOT NULL,
  `quantidade` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Quantidade em KG',
  `valor_unitario` decimal(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Valor por KG',
  `valor_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `comprador` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_racao_vendas_racao` (`racao_id`),
  KEY `idx_racao_vendas_data` (`data_venda`),
  CONSTRAINT `fk_racao_vendas_racao` FOREIGN KEY (`racao_id`) REFERENCES `racao` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3) Cria a tabela `racao_acertos`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `racao_acertos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_acerto` date NOT NULL,
  `racao_id` bigint unsigned NOT NULL,
  `estoque_anterior` decimal(10,2) NOT NULL DEFAULT 0.00,
  `contagem` decimal(10,2) NOT NULL DEFAULT 0.00,
  `diferenca` decimal(10,2) NOT NULL DEFAULT 0.00,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_racao_acertos_racao` (`racao_id`),
  KEY `idx_racao_acertos_data` (`data_acerto`),
  CONSTRAINT `fk_racao_acertos_racao` FOREIGN KEY (`racao_id`) REFERENCES `racao` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
