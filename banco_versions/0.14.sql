-- =============================================================================
-- MasterPig - Versão 0.14 - Insumos: Nutrição (Compras de Ração)
-- =============================================================================
-- Execute este script no BANCO DO TENANT (ex: mp00155232000119)
-- Compatível com MySQL 8.x e MariaDB 10.6+
-- Objetivo: criar a tabela `racao_compras` que registra as compras de ração
--           e permite atualizar o estoque e o custo médio de cada ração.
-- =============================================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

-- -----------------------------------------------------------------------------
-- 1) Cria a tabela `racao_compras`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `racao_compras` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_compra` date NOT NULL,
  `racao_id` bigint unsigned NOT NULL,
  `quantidade` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Quantidade em KG',
  `valor_unitario` decimal(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Valor por KG',
  `valor_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `nota_fiscal` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fornecedor_id` bigint unsigned DEFAULT NULL,
  `lote_fabricacao` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_racao_compras_racao` (`racao_id`),
  KEY `fk_racao_compras_fornecedor` (`fornecedor_id`),
  KEY `idx_racao_compras_data` (`data_compra`),
  CONSTRAINT `fk_racao_compras_racao` FOREIGN KEY (`racao_id`) REFERENCES `racao` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_racao_compras_fornecedor` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
