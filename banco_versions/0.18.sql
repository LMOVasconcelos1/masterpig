-- Migracao 0.18 - Limite licenciado de femeas por granja

ALTER TABLE `configuracoes`
    ADD COLUMN `limite_femeas` INT UNSIGNED NULL DEFAULT NULL AFTER `granja`;
