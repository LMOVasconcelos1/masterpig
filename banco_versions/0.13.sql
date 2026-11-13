-- =============================================================================
-- MasterPig - Versão 0.13 - Limite de Fêmeas Ativas por Cliente
-- =============================================================================
-- Execute este script no BANCO DO TENANT (ex: mp00155232000119)
-- Compatível com MySQL 8.x e MariaDB 10.6+
-- Objetivo: adicionar a chave "limite_femeas_ativas" na tabela `meta` que
-- controla a quantidade máxima de fêmeas ativas permitidas.
--
-- Regra de negócio:
--   NULL ou 0 → sem limite (plano antigo / ilimitado)
--   Valor > 0 → bloqueia qualquer entrada de fêmea que deixe o total de
--               ativas (soma de fêmeas cuja ÚLTIMA ação não seja morte,
--               descarte ou venda) maior que o limite.
-- =============================================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

-- -----------------------------------------------------------------------------
-- 1) Garante que a tabela `meta` exista e insere a chave (apenas se ausente).
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO `meta` (`chave`, `valor`, `criado_em`, `atualizado_em`)
VALUES (
    'limite_femeas_ativas',
    NULL,
    NOW(),
    NOW()
);

/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
