-- Banco version 0.7 - Logs de critérios
--
-- Execute uma vez por banco/tenant.

CREATE TABLE IF NOT EXISTS `criterio_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `modulo` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `evento` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `referencia_id` bigint unsigned DEFAULT NULL,
  `usuario_id` bigint unsigned DEFAULT NULL,
  `femea_id` bigint unsigned DEFAULT NULL,
  `warnings` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `dados` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ocorrido_em` timestamp NULL DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_criterio_log_evento` (`evento`),
  KEY `idx_criterio_log_ocorrido_em` (`ocorrido_em`),
  KEY `idx_criterio_log_usuario` (`usuario_id`),
  KEY `idx_criterio_log_femea` (`femea_id`),
  CONSTRAINT `fk_criterio_log_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_criterio_log_femea` FOREIGN KEY (`femea_id`) REFERENCES `femea` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

