-- Banco version 0.3 - Complementos da Cobertura (Gestação)
--
-- Execute uma vez por banco/tenant.

ALTER TABLE `gestacao_cobertura` ADD COLUMN `peso_matriz` decimal(10,2) DEFAULT NULL AFTER `baia`;
ALTER TABLE `gestacao_cobertura` ADD COLUMN `caracteristicas` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `peso_matriz`;
ALTER TABLE `gestacao_cobertura` ADD COLUMN `observacoes` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `caracteristicas`;

