CREATE TABLE IF NOT EXISTS premium_pesquisas (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id   INT UNSIGNED NOT NULL,
    instituto     VARCHAR(150) NOT NULL,
    tipo          ENUM('estadual','municipal') NOT NULL,
    cd_municipio  INT NULL DEFAULT NULL,
    nm_municipio  VARCHAR(120) NULL DEFAULT NULL,
    data_pesquisa DATE NOT NULL,
    pct_candidato DECIMAL(5,2) NOT NULL,
    observacoes   TEXT DEFAULT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_campaign (campaign_id),
    KEY idx_data (data_pesquisa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
