DROP TABLE IF EXISTS premium_forecast_runs;
DROP TABLE IF EXISTS premium_agenda;
DROP TABLE IF EXISTS premium_campaign_leaders;
DROP TABLE IF EXISTS premium_campaign_settings;
DROP TABLE IF EXISTS premium_campaigns;
DROP TABLE IF EXISTS premium_users;
DROP TABLE IF EXISTS premium_region_municipios;

CREATE TABLE premium_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME DEFAULT NULL,
    trial_started_at DATETIME DEFAULT NULL,
    trial_ends_at DATETIME DEFAULT NULL,
    UNIQUE KEY uniq_email (email),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE premium_campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    campaign_name VARCHAR(190) NOT NULL,
    candidate_name VARCHAR(190) NOT NULL,
    candidate_cargo VARCHAR(60) NOT NULL,
    candidate_number INT UNSIGNED DEFAULT NULL,
    candidate_photo_path VARCHAR(255) DEFAULT NULL,
    baseline_year SMALLINT NOT NULL DEFAULT 2022,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    baseline_panel_hidden TINYINT(1) NOT NULL DEFAULT 0,
    settings_panel_hidden TINYINT(1) NOT NULL DEFAULT 0,
    current_municipio VARCHAR(120) DEFAULT NULL,
    current_region VARCHAR(120) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_user_status (user_id, status),
    KEY idx_candidate (candidate_name),
    KEY idx_baseline_year (baseline_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE premium_campaign_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    settings_json LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_campaign (campaign_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE premium_campaign_leaders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    region_name VARCHAR(120) NOT NULL,
    municipality VARCHAR(120) NOT NULL,
    leader_name VARCHAR(190) NOT NULL,
    leader_cargo VARCHAR(60) NOT NULL,
    leader_party VARCHAR(20) DEFAULT NULL,
    source_sq_candidato VARCHAR(50) DEFAULT NULL,
    source_nr_votavel INT DEFAULT NULL,
    source_turno TINYINT NOT NULL DEFAULT 1,
    leader_votes_2024 INT NOT NULL DEFAULT 0,
    margin_percent DECIMAL(6,2) NOT NULL DEFAULT 0,
    transfer_rate DECIMAL(6,2) NOT NULL DEFAULT 40.00,
    aligned_with_executive TINYINT(1) NOT NULL DEFAULT 0,
    visibility_score DECIMAL(6,2) NOT NULL DEFAULT 50.00,
    investment_score DECIMAL(6,2) NOT NULL DEFAULT 50.00,
    size_class ENUM('small','medium','large') NOT NULL DEFAULT 'medium',
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_campaign (campaign_id),
    KEY idx_municipality (municipality),
    KEY idx_region (region_name),
    KEY idx_source_sq (source_sq_candidato)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE premium_agenda (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    priority ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    status ENUM('open','doing','done','archived') NOT NULL DEFAULT 'open',
    municipality VARCHAR(120) DEFAULT NULL,
    leader_name VARCHAR(190) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_campaign_status (campaign_id, status),
    KEY idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE premium_forecast_runs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    baseline_total INT NOT NULL DEFAULT 0,
    projected_total INT NOT NULL DEFAULT 0,
    scenario_key VARCHAR(20) NOT NULL DEFAULT 'base',
    payload_json LONGTEXT NOT NULL,
    result_json LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_campaign (campaign_id),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE premium_region_municipios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    region_name VARCHAR(120) NOT NULL,
    municipality VARCHAR(120) NOT NULL,
    is_polo TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    UNIQUE KEY uniq_municipality (municipality),
    KEY idx_region (region_name),
    KEY idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO premium_users (name, email, password_hash, status)
VALUES (
    'Administrador Premium',
    'premium@eleicoes.local',
    '$2y$10$fIXO4PFGGluoVFjFIHF4C.k7UTqXtD46BojG8g47zIpcYs7LebYsi',
    'active'
);
