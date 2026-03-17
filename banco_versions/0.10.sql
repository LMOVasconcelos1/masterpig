-- Banco version 0.10 - Maternidade (Partos, Desmames, Adoções)
--
-- Execute uma vez por banco/tenant.
--

CREATE TABLE IF NOT EXISTS `maternidade_parto` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `femea_id` bigint unsigned NOT NULL,
  `cobertura_id` bigint unsigned DEFAULT NULL,
  `lote` varchar(50) DEFAULT NULL,
  `data` date NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_termino` time DEFAULT NULL,
  `total_vivos` int NOT NULL DEFAULT 0,
  `total_mortos` int NOT NULL DEFAULT 0,
  `total_mumificados` int NOT NULL DEFAULT 0,
  `observacao` text DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mp_femea_data` (`femea_id`,`data`),
  KEY `idx_mp_data` (`data`),
  CONSTRAINT `fk_maternidade_parto_femea` FOREIGN KEY (`femea_id`) REFERENCES `femea` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `maternidade_desmame` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parto_id` bigint unsigned NOT NULL,
  `data` date NOT NULL,
  `quantidade` int NOT NULL,
  `peso_medio` decimal(8,2) DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_md_parto_data` (`parto_id`,`data`),
  KEY `idx_md_data` (`data`),
  CONSTRAINT `fk_maternidade_desmame_parto` FOREIGN KEY (`parto_id`) REFERENCES `maternidade_parto` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `maternidade_adocao` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parto_origem_id` bigint unsigned NOT NULL,
  `parto_destino_id` bigint unsigned NOT NULL,
  `quantidade` int NOT NULL,
  `data` date NOT NULL,
  `observacao` text DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ma_origem` (`parto_origem_id`),
  KEY `idx_ma_destino` (`parto_destino_id`),
  KEY `idx_ma_data` (`data`),
  CONSTRAINT `fk_maternidade_adocao_origem` FOREIGN KEY (`parto_origem_id`) REFERENCES `maternidade_parto` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_maternidade_adocao_destino` FOREIGN KEY (`parto_destino_id`) REFERENCES `maternidade_parto` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionando coluna femea_id_primaria na tabela femea_movimento
ALTER TABLE `femea_movimento` ADD COLUMN `femea_id_primaria` VARCHAR(50) DEFAULT NULL AFTER `femea_id`;

