-- Banco version 0.5 - Presença de cio na Cobertura (Gestação)
--
-- Execute uma vez por banco/tenant.

ALTER TABLE `gestacao_cobertura` ADD COLUMN `presenca_cio` enum('sim','nao') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `hora`;

