-- =============================================================================
-- Migracao 0.17 - Modulo de Almoxarifado (materiais)
-- =============================================================================

-- Tabela base de materiais do almoxarifado
CREATE TABLE IF NOT EXISTS `almoxarifado` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `grupo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estoque` decimal(10,2) NOT NULL DEFAULT '0.00',
  `custo_por_unidade` decimal(10,2) DEFAULT NULL,
  `fornecedor_id` bigint unsigned DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `almoxarifado_codigo_unique` (`codigo`),
  KEY `fk_almoxarifado_fornecedor` (`fornecedor_id`),
  CONSTRAINT `fk_almoxarifado_fornecedor` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compras de material
CREATE TABLE IF NOT EXISTS `almoxarifado_compras` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_compra` date NOT NULL,
  `almoxarifado_id` bigint unsigned NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `nota_fiscal` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fornecedor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lote_fabricacao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `almoxarifado_compras_almoxarifado_id_foreign` (`almoxarifado_id`),
  CONSTRAINT `almoxarifado_compras_almoxarifado_id_foreign` FOREIGN KEY (`almoxarifado_id`) REFERENCES `almoxarifado` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consumos de material
CREATE TABLE IF NOT EXISTS `almoxarifado_consumos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `almoxarifado_id` bigint unsigned NOT NULL,
  `fase` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `lote` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lote_fabricacao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_consumo` date NOT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `almoxarifado_consumos_almoxarifado_id_foreign` (`almoxarifado_id`),
  CONSTRAINT `almoxarifado_consumos_almoxarifado_id_foreign` FOREIGN KEY (`almoxarifado_id`) REFERENCES `almoxarifado` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vendas de material
CREATE TABLE IF NOT EXISTS `almoxarifado_vendas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_venda` date NOT NULL,
  `almoxarifado_id` bigint unsigned NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `comprador` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `almoxarifado_vendas_almoxarifado_id_foreign` (`almoxarifado_id`),
  CONSTRAINT `almoxarifado_vendas_almoxarifado_id_foreign` FOREIGN KEY (`almoxarifado_id`) REFERENCES `almoxarifado` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Acertos de estoque de material
CREATE TABLE IF NOT EXISTS `almoxarifado_acertos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_acerto` date NOT NULL,
  `almoxarifado_id` bigint unsigned NOT NULL,
  `estoque_anterior` decimal(10,2) NOT NULL,
  `contagem` decimal(10,2) NOT NULL,
  `diferenca` decimal(10,2) NOT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `almoxarifado_acertos_almoxarifado_id_foreign` (`almoxarifado_id`),
  CONSTRAINT `almoxarifado_acertos_almoxarifado_id_foreign` FOREIGN KEY (`almoxarifado_id`) REFERENCES `almoxarifado` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
