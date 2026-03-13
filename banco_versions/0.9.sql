-- Banco version 0.9 - Registro de cio (Gestação)
--
-- Execute uma vez por banco/tenant.
--

CREATE TABLE IF NOT EXISTS `gestacao_cio` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `femea_id` bigint unsigned NOT NULL,
  `data` date NOT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_gc_femea_data` (`femea_id`,`data`),
  KEY `idx_gc_data` (`data`),
  CONSTRAINT `fk_gestacao_cio_femea` FOREIGN KEY (`femea_id`) REFERENCES `femea` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
