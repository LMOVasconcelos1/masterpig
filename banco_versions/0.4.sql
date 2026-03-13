-- Banco version 0.4 - Usuário responsável nos lançamentos de Gestação
--
-- Execute uma vez por banco/tenant.

ALTER TABLE `gestacao_cobertura` ADD COLUMN `usuario_id` bigint unsigned DEFAULT NULL AFTER `femea_id`;
ALTER TABLE `gestacao_cobertura` ADD KEY `idx_gc_usuario` (`usuario_id`);
ALTER TABLE `gestacao_cobertura` ADD CONSTRAINT `fk_gc_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

ALTER TABLE `gestacao_perda` ADD COLUMN `usuario_id` bigint unsigned DEFAULT NULL AFTER `femea_id`;
ALTER TABLE `gestacao_perda` ADD KEY `idx_gp_usuario` (`usuario_id`);
ALTER TABLE `gestacao_perda` ADD CONSTRAINT `fk_gp_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE SET NULL;

