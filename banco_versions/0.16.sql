-- =============================================================================
-- Migracao 0.16 - Modulo de Sanidade (medicamentos, vacinas e desinfetantes)
-- =============================================================================

-- Tabela base de itens de sanidade
CREATE TABLE IF NOT EXISTS `sanidade` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('medicamento','vacina','desinfetante') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `grupo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estoque` decimal(10,2) NOT NULL DEFAULT '0.00',
  `custo_por_unidade` decimal(10,2) DEFAULT NULL,
  `fornecedor_id` bigint unsigned DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sanidade_codigo_unique` (`codigo`),
  KEY `sanidade_tipo_index` (`tipo`),
  KEY `fk_sanidade_fornecedor` (`fornecedor_id`),
  CONSTRAINT `fk_sanidade_fornecedor` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compras de sanidade
CREATE TABLE IF NOT EXISTS `sanidade_compras` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_compra` date NOT NULL,
  `sanidade_id` bigint unsigned NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `nota_fiscal` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fornecedor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lote_fabricacao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sanidade_compras_sanidade_id_foreign` (`sanidade_id`),
  CONSTRAINT `sanidade_compras_sanidade_id_foreign` FOREIGN KEY (`sanidade_id`) REFERENCES `sanidade` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consumos de sanidade
CREATE TABLE IF NOT EXISTS `sanidade_consumos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sanidade_id` bigint unsigned NOT NULL,
  `fase` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `lote` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lote_fabricacao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_inicial` date NOT NULL,
  `data_final` date DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sanidade_consumos_sanidade_id_foreign` (`sanidade_id`),
  CONSTRAINT `sanidade_consumos_sanidade_id_foreign` FOREIGN KEY (`sanidade_id`) REFERENCES `sanidade` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vendas de sanidade
CREATE TABLE IF NOT EXISTS `sanidade_vendas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_venda` date NOT NULL,
  `sanidade_id` bigint unsigned NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `comprador` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sanidade_vendas_sanidade_id_foreign` (`sanidade_id`),
  CONSTRAINT `sanidade_vendas_sanidade_id_foreign` FOREIGN KEY (`sanidade_id`) REFERENCES `sanidade` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Acertos de estoque de sanidade
CREATE TABLE IF NOT EXISTS `sanidade_acertos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_acerto` date NOT NULL,
  `sanidade_id` bigint unsigned NOT NULL,
  `estoque_anterior` decimal(10,2) NOT NULL,
  `contagem` decimal(10,2) NOT NULL,
  `diferenca` decimal(10,2) NOT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sanidade_acertos_sanidade_id_foreign` (`sanidade_id`),
  CONSTRAINT `sanidade_acertos_sanidade_id_foreign` FOREIGN KEY (`sanidade_id`) REFERENCES `sanidade` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
