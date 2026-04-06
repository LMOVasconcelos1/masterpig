-- Query para criar tabela de configuracoes
-- Execute esta query diretamente no seu banco de dados

CREATE TABLE configuracoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    granja VARCHAR(255) NOT NULL DEFAULT 'MasterPig',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir registro padrão
INSERT INTO configuracoes (granja, created_at, updated_at) 
VALUES ('Sem nome', NOW(), NOW());
