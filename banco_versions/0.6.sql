-- Banco version 0.6 - Meta.valor como texto (suporte a metas/criterios/listas)
--
-- Motivo:
-- - A tabela meta é usada para armazenar números (metas), listas (utilitários) e textos (critérios).
-- - Em bancos antigos, meta.valor era DECIMAL e causa erro ao salvar valores como 'sim'/'nao' ou JSON.
--
-- Execute uma vez por banco/tenant.

ALTER TABLE `meta` MODIFY COLUMN `valor` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;

