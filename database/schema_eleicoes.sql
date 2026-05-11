-- ============================================================
-- Schema Consolidado - Eleições Sergipe
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- ============================================================
-- TABELAS DE VOTAÇÃO 2022
-- ============================================================

DROP TABLE IF EXISTS votacao_2022;

CREATE TABLE votacao_2022 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    municipio VARCHAR(100),
    cod_municipio INT,
    zona INT,
    cargo VARCHAR(50),
    cod_cargo INT,
    nr_turno INT DEFAULT 1,
    sq_candidato BIGINT,
    nr_candidato INT,
    nm_candidato VARCHAR(200),
    nm_urna_candidato VARCHAR(200),
    sg_partido VARCHAR(20),
    nr_partido INT,
    nm_partido VARCHAR(100),
    qt_votos_nominais INT,
    qt_votos_validos_zona INT,
    situacao_turno VARCHAR(50),
    situacao_candidatura VARCHAR(50),
    ano_eleicao INT DEFAULT 2022,
    INDEX idx_municipio (municipio),
    INDEX idx_cargo (cargo),
    INDEX idx_partido (sg_partido),
    INDEX idx_candidato (nm_candidato),
    INDEX idx_turno (nr_turno)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELAS DE VOTAÇÃO 2018
-- ============================================================

DROP TABLE IF EXISTS votacao_2018;

CREATE TABLE votacao_2018 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    municipio VARCHAR(100),
    cod_municipio INT,
    zona INT,
    cargo VARCHAR(50),
    cod_cargo INT,
    nr_turno INT DEFAULT 1,
    sq_candidato BIGINT,
    nr_candidato INT,
    nm_candidato VARCHAR(200),
    nm_urna_candidato VARCHAR(200),
    sg_partido VARCHAR(20),
    nr_partido INT,
    nm_partido VARCHAR(100),
    qt_votos_nominais INT,
    qt_votos_validos_zona INT,
    situacao_turno VARCHAR(50),
    situacao_candidatura VARCHAR(50),
    ano_eleicao INT DEFAULT 2018,
    INDEX idx_municipio (municipio),
    INDEX idx_cargo (cargo),
    INDEX idx_partido (sg_partido),
    INDEX idx_candidato (nm_candidato),
    INDEX idx_turno (nr_turno)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELAS DE VOTAÇÃO 2024 (Serqipe)
-- ============================================================

DROP TABLE IF EXISTS votacao_secao_2024_se;

CREATE TABLE votacao_secao_2024_se (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dt_geracao DATE DEFAULT NULL,
    hh_geracao TIME DEFAULT NULL,
    ano_eleicao SMALLINT NOT NULL,
    cd_tipo_eleicao SMALLINT DEFAULT NULL,
    nm_tipo_eleicao VARCHAR(80) DEFAULT NULL,
    nr_turno TINYINT NOT NULL,
    cd_eleicao INT DEFAULT NULL,
    ds_eleicao VARCHAR(120) DEFAULT NULL,
    dt_eleicao DATE DEFAULT NULL,
    tp_abrangencia CHAR(1) DEFAULT NULL,
    sg_uf CHAR(2) DEFAULT NULL,
    sg_ue VARCHAR(10) DEFAULT NULL,
    nm_ue VARCHAR(100) DEFAULT NULL,
    cd_municipio INT NOT NULL,
    nm_municipio VARCHAR(120) NOT NULL,
    nr_zona SMALLINT NOT NULL,
    nr_secao SMALLINT NOT NULL,
    cd_cargo SMALLINT NOT NULL,
    ds_cargo VARCHAR(30) NOT NULL,
    nr_votavel INT NOT NULL,
    nm_votavel VARCHAR(200) NOT NULL,
    qt_votos INT NOT NULL,
    nr_local_votacao INT DEFAULT NULL,
    sq_candidato BIGINT DEFAULT NULL,
    nm_local_votacao VARCHAR(200) DEFAULT NULL,
    ds_local_votacao_endereco VARCHAR(255) DEFAULT NULL,
    tipo_voto VARCHAR(20) NOT NULL,
    KEY idx_turno_cargo (nr_turno, ds_cargo),
    KEY idx_turno_cargo_municipio (nr_turno, ds_cargo, cd_municipio),
    KEY idx_turno_cargo_zona (nr_turno, ds_cargo, cd_municipio, nr_zona),
    KEY idx_turno_cargo_votavel (nr_turno, ds_cargo, nr_votavel),
    KEY idx_turno_cargo_municipio_nome (nr_turno, ds_cargo, nm_municipio, nr_zona, nr_secao),
    KEY idx_turno_cargo_zona_only (nr_turno, ds_cargo, nr_zona),
    KEY idx_tipo_voto (tipo_voto),
    KEY idx_nm_votavel (nm_votavel(100)),
    KEY idx_secao (cd_municipio, nr_zona, nr_secao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS resumo_votacao_2024_se;

CREATE TABLE resumo_votacao_2024_se (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nr_turno TINYINT NOT NULL,
    ds_cargo VARCHAR(30) NOT NULL,
    cd_municipio INT NOT NULL,
    nm_municipio VARCHAR(120) NOT NULL,
    nr_zona SMALLINT NOT NULL,
    nr_votavel INT NOT NULL,
    nm_votavel VARCHAR(200) NOT NULL,
    tipo_voto VARCHAR(20) NOT NULL,
    total_votos INT NOT NULL,
    secoes_com_votos INT NOT NULL,
    UNIQUE KEY uniq_resumo (nr_turno, ds_cargo, cd_municipio, nr_zona, nr_votavel, tipo_voto),
    KEY idx_rank (nr_turno, ds_cargo, tipo_voto, total_votos),
    KEY idx_municipio (nr_turno, ds_cargo, cd_municipio, tipo_voto),
    KEY idx_votavel (nr_turno, ds_cargo, nr_votavel),
    KEY idx_municipio_nome (nr_turno, ds_cargo, nm_municipio),
    KEY idx_zona (nr_turno, ds_cargo, nr_zona)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS resumo_municipio_2024_se;

CREATE TABLE resumo_municipio_2024_se (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nr_turno TINYINT NOT NULL,
    ds_cargo VARCHAR(30) NOT NULL,
    cd_municipio INT NOT NULL,
    nm_municipio VARCHAR(120) NOT NULL,
    total_votos INT NOT NULL,
    total_zonas INT NOT NULL,
    total_secoes INT NOT NULL,
    total_votaveis INT NOT NULL,
    votos_candidato INT NOT NULL,
    votos_legenda INT NOT NULL,
    votos_branco INT NOT NULL,
    votos_nulo INT NOT NULL,
    UNIQUE KEY uniq_resumo_municipio (nr_turno, ds_cargo, cd_municipio),
    KEY idx_total (nr_turno, ds_cargo, total_votos),
    KEY idx_nome (nm_municipio),
    KEY idx_lookup (nr_turno, ds_cargo, nm_municipio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA CANDIDATOS 2024
-- ============================================================

DROP TABLE IF EXISTS candidatos_situacao_2024;

CREATE TABLE candidatos_situacao_2024 (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nr_turno INT DEFAULT NULL,
    ds_cargo VARCHAR(50) DEFAULT NULL,
    cd_municipio INT DEFAULT NULL,
    nm_municipio VARCHAR(150) DEFAULT NULL,
    nr_zona INT DEFAULT NULL,
    nr_cand VARCHAR(20) DEFAULT NULL,
    nm_candidato VARCHAR(200) DEFAULT NULL,
    nm_urna_candidato VARCHAR(200) DEFAULT NULL,
    sg_partido VARCHAR(20) DEFAULT NULL,
    ds_sit_tot_turno VARCHAR(100) DEFAULT NULL,
    ds_situacao_candidatura VARCHAR(100) DEFAULT NULL,
    sq_candidato VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    KEY idx_cargo_municipio (ds_cargo, nm_municipio),
    KEY idx_cand (nr_cand, nm_candidato),
    KEY idx_sq_candidato (sq_candidato),
    KEY idx_turno_cargo_nome (nr_turno, ds_cargo, nm_municipio),
    KEY idx_turno_cargo_cand (nr_turno, ds_cargo, nr_cand),
    KEY idx_turno_cargo_sq (nr_turno, ds_cargo, sq_candidato)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELAS DE RESUMO (Performance)
-- ============================================================

DROP TABLE IF EXISTS resumo_candidatos;

CREATE TABLE resumo_candidatos (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nr_turno INT DEFAULT NULL,
    cargo VARCHAR(50) DEFAULT NULL,
    nm_candidato VARCHAR(200) DEFAULT NULL,
    sg_partido VARCHAR(20) DEFAULT NULL,
    total_votos INT DEFAULT 0,
    situacao_turno VARCHAR(50) DEFAULT NULL,
    UNIQUE KEY unique_key (nr_turno, cargo, nm_candidato, sg_partido),
    KEY idx_turno_cargo (nr_turno, cargo),
    KEY idx_votos (total_votos DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS resumo_municipios;

CREATE TABLE resumo_municipios (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nr_turno INT DEFAULT NULL,
    municipio VARCHAR(100) DEFAULT NULL,
    total_votos INT DEFAULT 0,
    UNIQUE KEY unique_key (nr_turno, municipio),
    KEY idx_votos (total_votos DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS resumo_partidos;

CREATE TABLE resumo_partidos (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nr_turno INT DEFAULT NULL,
    cargo VARCHAR(50) DEFAULT NULL,
    sg_partido VARCHAR(20) DEFAULT NULL,
    total_votos INT DEFAULT 0,
    UNIQUE KEY unique_key (nr_turno, cargo, sg_partido),
    KEY idx_votos (total_votos DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABELA PERFIL ELEITOR
-- ============================================================

DROP TABLE IF EXISTS perfil_eleitor_municipio;

CREATE TABLE perfil_eleitor_municipio (
    cd_municipio    INT          NOT NULL,
    nm_municipio    VARCHAR(100) NOT NULL,
    qt_total        INT          NOT NULL DEFAULT 0,
    qt_biometria    INT          NOT NULL DEFAULT 0,
    qt_deficiencia  INT          NOT NULL DEFAULT 0,
    qt_nome_social  INT          NOT NULL DEFAULT 0,
    genero          JSON,
    faixa_etaria    JSON,
    grau_instrucao  JSON,
    cor_raca        JSON,
    estado_civil    JSON,
    obrigatoriedade JSON,
    ano_eleicao     SMALLINT     NOT NULL DEFAULT 0,
    mes_ref         VARCHAR(7)   NOT NULL DEFAULT '',
    PRIMARY KEY (cd_municipio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELAS PREMIUM
-- ============================================================

DROP TABLE IF EXISTS premium_users;

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

DROP TABLE IF EXISTS premium_campaigns;

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

DROP TABLE IF EXISTS premium_campaign_settings;

CREATE TABLE premium_campaign_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    settings_json LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_campaign (campaign_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS premium_campaign_leaders;

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

DROP TABLE IF EXISTS premium_agenda;

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

DROP TABLE IF EXISTS premium_forecast_runs;

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

DROP TABLE IF EXISTS premium_region_municipios;

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

-- ============================================================
-- TABELA PREMIUM PESQUISAS
-- ============================================================

DROP TABLE IF EXISTS premium_pesquisas;

CREATE TABLE premium_pesquisas (
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

-- ============================================================
-- INSERTS INICIAIS
-- ============================================================

INSERT INTO premium_users (name, email, password_hash, status)
VALUES (
    'Administrador Premium',
    'premium@apoiacandidato.com.br',
    '$2y$10$fIXO4PFGGluoVFjFIHF4C.k7UTqXtD46BojG8g47zIpcYs7LebYsi',
    'active'
);

-- ============================================================
-- POPULAR RESUMO CANDIDATOS (Exemplo de INSERT com SELECT)
-- ============================================================

INSERT INTO resumo_candidatos (nr_turno, cargo, nm_candidato, sg_partido, total_votos, situacao_turno)
SELECT nr_turno, cargo, nm_candidato, sg_partido, 
       SUM(qt_votos_nominais) as total_votos, 
       MAX(situacao_turno) as situacao_turno
FROM votacao_2022
GROUP BY nr_turno, cargo, nm_candidato, sg_partido
ON DUPLICATE KEY UPDATE total_votos = VALUES(total_votos);

INSERT INTO resumo_municipios (nr_turno, municipio, total_votos)
SELECT nr_turno, municipio, SUM(qt_votos_nominais) as total_votos
FROM votacao_2022
GROUP BY nr_turno, municipio
ON DUPLICATE KEY UPDATE total_votos = VALUES(total_votos);

INSERT INTO resumo_partidos (nr_turno, cargo, sg_partido, total_votos)
SELECT nr_turno, cargo, sg_partido, SUM(qt_votos_nominais) as total_votos
FROM votacao_2022
WHERE sg_partido != ''
GROUP BY nr_turno, cargo, sg_partido
ON DUPLICATE KEY UPDATE total_votos = VALUES(total_votos);

-- ============================================================
-- ÍNDICES ADICIONAIS DE PERFORMANCE
-- ============================================================

ALTER TABLE resumo_votacao_2024_se
    ADD KEY idx_municipio_nome (nr_turno, ds_cargo, nm_municipio),
    ADD KEY idx_zona (nr_turno, ds_cargo, nr_zona);

ALTER TABLE resumo_municipio_2024_se
    ADD KEY idx_lookup (nr_turno, ds_cargo, nm_municipio);

ALTER TABLE votacao_secao_2024_se
    ADD KEY idx_turno_cargo_municipio_nome (nr_turno, ds_cargo, nm_municipio, nr_zona, nr_secao),
    ADD KEY idx_turno_cargo_zona_only (nr_turno, ds_cargo, nr_zona);

ALTER TABLE votacao_2022
    ADD KEY idx_turno_cargo (nr_turno, cargo),
    ADD KEY idx_turno_municipio (nr_turno, municipio),
    ADD KEY idx_cargo_partido (cargo, sg_partido),
    ADD KEY idx_turno_cargo_situacao (nr_turno, cargo, situacao_turno);

-- ============================================================
-- DADOS PERFIL ELEITOR MUNICÍPIO
-- ============================================================

INSERT INTO perfil_eleitor_municipio (cd_municipio, nm_municipio, qt_total, qt_biometria, qt_deficiencia, qt_nome_social, genero, faixa_etaria, grau_instrucao, cor_raca, estado_civil, obrigatoriedade, ano_eleicao) VALUES
(31003, 'SANTANA DO SÃO FRANCISCO', 6277, 5997, 40, 0, '{\"FEMININO\":3285,\"MASCULINO\":2992}', '{\"65 a 69 anos\":237,\"45 a 49 anos\":638,\"60 a 64 anos\":328,\"19 anos\":131,\"30 a 34 anos\":699,\"35 a 39 anos\":711,\"50 a 54 anos\":477,\"55 a 59 anos\":421,\"40 a 44 anos\":675,\"25 a 29 anos\":668,\"18 anos\":127,\"21 a 24 anos\":496,\"80 a 84 anos\":92,\"17 anos\":44,\"85 a 89 anos\":43,\"20 anos\":148,\"100 anos ou mais\":2,\"70 a 74 anos\":169,\"75 a 79 anos\":131,\"90 a 94 anos\":14,\"16 anos\":20,\"95 a 99 anos\":4,\"15 anos\":1,\"Inválida\":1}', '{\"ENSINO FUNDAMENTAL COMPLETO\":224,\"SUPERIOR COMPLETO\":221,\"ENSINO FUNDAMENTAL INCOMPLETO\":2067,\"ENSINO MÉDIO INCOMPLETO\":1391,\"LÊ E ESCREVE\":496,\"ANALFABETO\":300,\"ENSINO MÉDIO COMPLETO\":1417,\"SUPERIOR INCOMPLETO\":161}', '{\"NÃO INFORMADO\":4452,\"Parda\":1301,\"Preta\":252,\"Branca\":258,\"Indígena\":3,\"Amarela\":11}', '{\"SOLTEIRO\":4338,\"SEPARADO JUDICIALMENTE\":18,\"CASADO\":1648,\"VIÚVO\":94,\"DIVORCIADO\":179}', '{\"Obrigatório\":5561,\"Facultativo\":716}', 9999),
(31011, 'AMPARO DE SÃO FRANCISCO', 3055, 2953, 27, 0, '{\"FEMININO\":1546,\"MASCULINO\":1509}', '{\"60 a 64 anos\":168,\"45 a 49 anos\":286,\"65 a 69 anos\":132,\"40 a 44 anos\":335,\"20 anos\":73,\"90 a 94 anos\":11,\"30 a 34 anos\":335,\"35 a 39 anos\":346,\"50 a 54 anos\":242,\"21 a 24 anos\":206,\"19 anos\":43,\"18 anos\":54,\"70 a 74 anos\":90,\"55 a 59 anos\":180,\"25 a 29 anos\":357,\"80 a 84 anos\":37,\"75 a 79 anos\":72,\"85 a 89 anos\":27,\"17 anos\":41,\"16 anos\":15,\"95 a 99 anos\":3,\"100 anos ou mais\":2}', '{\"LÊ E ESCREVE\":321,\"SUPERIOR INCOMPLETO\":139,\"ANALFABETO\":115,\"ENSINO MÉDIO COMPLETO\":769,\"ENSINO MÉDIO INCOMPLETO\":652,\"SUPERIOR COMPLETO\":160,\"ENSINO FUNDAMENTAL INCOMPLETO\":758,\"ENSINO FUNDAMENTAL COMPLETO\":141}', '{\"NÃO INFORMADO\":2038,\"Branca\":120,\"Preta\":183,\"Parda\":699,\"Amarela\":13,\"Indígena\":2}', '{\"SOLTEIRO\":2143,\"VIÚVO\":40,\"CASADO\":771,\"DIVORCIADO\":91,\"SEPARADO JUDICIALMENTE\":10}', '{\"Obrigatório\":2680,\"Facultativo\":375}', 9999),
(31038, 'AQUIDABÃ', 17193, 16581, 93, 1, '{\"MASCULINO\":8242,\"FEMININO\":8951}', '{\"65 a 69 anos\":884,\"17 anos\":191,\"45 a 49 anos\":1557,\"25 a 29 anos\":1770,\"40 a 44 anos\":1579,\"30 a 34 anos\":1718,\"70 a 74 anos\":698,\"55 a 59 anos\":1250,\"50 a 54 anos\":1336,\"75 a 79 anos\":494,\"60 a 64 anos\":1085,\"35 a 39 anos\":1755,\"18 anos\":275,\"20 anos\":322,\"21 a 24 anos\":1271,\"80 a 84 anos\":320,\"16 anos\":73,\"85 a 89 anos\":185,\"19 anos\":314,\"90 a 94 anos\":79,\"100 anos ou mais\":4,\"95 a 99 anos\":16,\"15 anos\":17}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":4915,\"ENSINO MÉDIO INCOMPLETO\":3336,\"SUPERIOR INCOMPLETO\":574,\"ENSINO MÉDIO COMPLETO\":2760,\"LÊ E ESCREVE\":2756,\"SUPERIOR COMPLETO\":827,\"ENSINO FUNDAMENTAL COMPLETO\":880,\"ANALFABETO\":1145}', '{\"NÃO INFORMADO\":13499,\"Parda\":2590,\"Preta\":356,\"Branca\":721,\"Amarela\":24,\"Indígena\":3}', '{\"CASADO\":4118,\"SOLTEIRO\":11873,\"VIÚVO\":364,\"DIVORCIADO\":656,\"SEPARADO JUDICIALMENTE\":182}', '{\"Obrigatório\":14430,\"Facultativo\":2763}', 9999),
(31054, 'ARACAJU', 416703, 395659, 5277, 135, '{\"FEMININO\":231245,\"MASCULINO\":185458}', '{\"21 a 24 anos\":27346,\"40 a 44 anos\":45835,\"50 a 54 anos\":36537,\"45 a 49 anos\":42906,\"35 a 39 anos\":41541,\"75 a 79 anos\":12351,\"70 a 74 anos\":17436,\"60 a 64 anos\":28984,\"25 a 29 anos\":39101,\"55 a 59 anos\":32834,\"90 a 94 anos\":1140,\"30 a 34 anos\":38750,\"65 a 69 anos\":22223,\"18 anos\":4324,\"19 anos\":5687,\"80 a 84 anos\":7200,\"17 anos\":1808,\"20 anos\":6356,\"85 a 89 anos\":3219,\"16 anos\":583,\"95 a 99 anos\":349,\"100 anos ou mais\":86,\"15 anos\":106,\"Inválida\":1}', '{\"ENSINO MÉDIO INCOMPLETO\":64387,\"ENSINO MÉDIO COMPLETO\":127014,\"ENSINO FUNDAMENTAL INCOMPLETO\":69685,\"SUPERIOR INCOMPLETO\":45830,\"SUPERIOR COMPLETO\":75380,\"ENSINO FUNDAMENTAL COMPLETO\":17584,\"ANALFABETO\":4549,\"LÊ E ESCREVE\":12274}', '{\"Branca\":13906,\"Parda\":31632,\"NÃO INFORMADO\":362896,\"Preta\":7995,\"Amarela\":238,\"Indígena\":36}', '{\"SOLTEIRO\":269216,\"CASADO\":114893,\"DIVORCIADO\":19706,\"SEPARADO JUDICIALMENTE\":5106,\"VIÚVO\":7782}', '{\"Obrigatório\":369388,\"Facultativo\":47315}', 9999),
(31070, 'ARAUÁ', 9509, 9079, 31, 1, '{\"FEMININO\":4871,\"MASCULINO\":4638}', '{\"35 a 39 anos\":965,\"40 a 44 anos\":994,\"45 a 49 anos\":892,\"60 a 64 anos\":604,\"50 a 54 anos\":787,\"65 a 69 anos\":460,\"90 a 94 anos\":25,\"70 a 74 anos\":318,\"75 a 79 anos\":220,\"25 a 29 anos\":948,\"30 a 34 anos\":983,\"85 a 89 anos\":90,\"21 a 24 anos\":714,\"20 anos\":175,\"17 anos\":90,\"80 a 84 anos\":153,\"55 a 59 anos\":729,\"19 anos\":187,\"18 anos\":149,\"16 anos\":14,\"15 anos\":2,\"95 a 99 anos\":8,\"100 anos ou mais\":2}', '{\"ENSINO MÉDIO COMPLETO\":1660,\"ENSINO FUNDAMENTAL INCOMPLETO\":2654,\"ENSINO MÉDIO INCOMPLETO\":1975,\"LÊ E ESCREVE\":1580,\"ANALFABETO\":699,\"SUPERIOR INCOMPLETO\":222,\"SUPERIOR COMPLETO\":345,\"ENSINO FUNDAMENTAL COMPLETO\":374}', '{\"Parda\":1019,\"Preta\":248,\"NÃO INFORMADO\":8059,\"Branca\":171,\"Amarela\":11,\"Indígena\":1}', '{\"SOLTEIRO\":7818,\"DIVORCIADO\":142,\"SEPARADO JUDICIALMENTE\":31,\"CASADO\":1427,\"VIÚVO\":91}', '{\"Obrigatório\":8119,\"Facultativo\":1390}', 9999),
(31097, 'AREIA BRANCA', 15040, 14603, 146, 0, '{\"MASCULINO\":7096,\"FEMININO\":7944}', '{\"65 a 69 anos\":637,\"50 a 54 anos\":1168,\"25 a 29 anos\":1759,\"55 a 59 anos\":1024,\"35 a 39 anos\":1502,\"17 anos\":193,\"70 a 74 anos\":473,\"40 a 44 anos\":1552,\"45 a 49 anos\":1418,\"20 anos\":319,\"30 a 34 anos\":1633,\"21 a 24 anos\":1237,\"60 a 64 anos\":842,\"19 anos\":300,\"18 anos\":257,\"16 anos\":93,\"80 a 84 anos\":193,\"85 a 89 anos\":81,\"75 a 79 anos\":290,\"15 anos\":20,\"90 a 94 anos\":40,\"95 a 99 anos\":6,\"100 anos ou mais\":3}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":6003,\"ENSINO MÉDIO COMPLETO\":2555,\"ENSINO MÉDIO INCOMPLETO\":2807,\"LÊ E ESCREVE\":1255,\"ENSINO FUNDAMENTAL COMPLETO\":731,\"SUPERIOR INCOMPLETO\":397,\"ANALFABETO\":851,\"SUPERIOR COMPLETO\":441}', '{\"NÃO INFORMADO\":11972,\"Preta\":323,\"Parda\":2160,\"Branca\":566,\"Amarela\":18,\"Indígena\":1}', '{\"SOLTEIRO\":11851,\"CASADO\":2455,\"VIÚVO\":266,\"DIVORCIADO\":386,\"SEPARADO JUDICIALMENTE\":82}', '{\"Obrigatório\":13097,\"Facultativo\":1943}', 9999),
(31119, 'BARRA DOS COQUEIROS', 31184, 29969, 524, 6, '{\"MASCULINO\":14387,\"FEMININO\":16797}', '{\"45 a 49 anos\":3243,\"55 a 59 anos\":2328,\"40 a 44 anos\":3503,\"50 a 54 anos\":2798,\"80 a 84 anos\":299,\"35 a 39 anos\":3240,\"25 a 29 anos\":3308,\"21 a 24 anos\":2316,\"30 a 34 anos\":3244,\"75 a 79 anos\":529,\"70 a 74 anos\":899,\"20 anos\":596,\"19 anos\":606,\"65 a 69 anos\":1365,\"18 anos\":451,\"60 a 64 anos\":1899,\"85 a 89 anos\":136,\"17 anos\":261,\"90 a 94 anos\":47,\"16 anos\":93,\"15 anos\":12,\"95 a 99 anos\":10,\"100 anos ou mais\":1}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":6906,\"ENSINO MÉDIO INCOMPLETO\":5406,\"ENSINO MÉDIO COMPLETO\":9878,\"ENSINO FUNDAMENTAL COMPLETO\":1326,\"SUPERIOR INCOMPLETO\":2238,\"LÊ E ESCREVE\":1312,\"SUPERIOR COMPLETO\":3541,\"ANALFABETO\":577}', '{\"Parda\":6668,\"NÃO INFORMADO\":20783,\"Branca\":1920,\"Preta\":1729,\"Indígena\":7,\"Amarela\":77}', '{\"SOLTEIRO\":21696,\"CASADO\":7178,\"VIÚVO\":519,\"DIVORCIADO\":1505,\"SEPARADO JUDICIALMENTE\":286}', '{\"Obrigatório\":28478,\"Facultativo\":2706}', 9999),
(31135, 'BREJO GRANDE', 8028, 7677, 82, 4, '{\"MASCULINO\":3976,\"FEMININO\":4052}', '{\"21 a 24 anos\":711,\"85 a 89 anos\":63,\"19 anos\":174,\"35 a 39 anos\":868,\"25 a 29 anos\":1051,\"45 a 49 anos\":701,\"60 a 64 anos\":389,\"15 anos\":4,\"50 a 54 anos\":605,\"70 a 74 anos\":241,\"55 a 59 anos\":498,\"40 a 44 anos\":783,\"17 anos\":94,\"30 a 34 anos\":960,\"18 anos\":168,\"75 a 79 anos\":126,\"90 a 94 anos\":20,\"65 a 69 anos\":282,\"20 anos\":201,\"80 a 84 anos\":65,\"95 a 99 anos\":9,\"16 anos\":11,\"100 anos ou mais\":4}', '{\"ENSINO MÉDIO INCOMPLETO\":1652,\"LÊ E ESCREVE\":840,\"ENSINO FUNDAMENTAL INCOMPLETO\":2828,\"SUPERIOR INCOMPLETO\":223,\"ENSINO MÉDIO COMPLETO\":1324,\"ANALFABETO\":533,\"SUPERIOR COMPLETO\":308,\"ENSINO FUNDAMENTAL COMPLETO\":320}', '{\"Parda\":1469,\"NÃO INFORMADO\":5775,\"Preta\":444,\"Branca\":308,\"Amarela\":32}', '{\"SOLTEIRO\":6227,\"DIVORCIADO\":171,\"CASADO\":1505,\"VIÚVO\":107,\"SEPARADO JUDICIALMENTE\":18}', '{\"Obrigatório\":7025,\"Facultativo\":1003}', 9999),
(31151, 'BOQUIM', 21845, 20889, 186, 3, '{\"FEMININO\":11472,\"MASCULINO\":10373}', '{\"70 a 74 anos\":809,\"50 a 54 anos\":1879,\"60 a 64 anos\":1511,\"21 a 24 anos\":1494,\"40 a 44 anos\":2138,\"65 a 69 anos\":1018,\"45 a 49 anos\":2007,\"95 a 99 anos\":25,\"30 a 34 anos\":2144,\"55 a 59 anos\":1753,\"85 a 89 anos\":198,\"35 a 39 anos\":2113,\"19 anos\":386,\"25 a 29 anos\":2146,\"80 a 84 anos\":463,\"18 anos\":322,\"90 a 94 anos\":97,\"75 a 79 anos\":680,\"16 anos\":85,\"17 anos\":171,\"20 anos\":391,\"15 anos\":6,\"100 anos ou mais\":9}', '{\"LÊ E ESCREVE\":3176,\"ANALFABETO\":1296,\"ENSINO FUNDAMENTAL COMPLETO\":873,\"ENSINO FUNDAMENTAL INCOMPLETO\":6724,\"SUPERIOR INCOMPLETO\":821,\"ENSINO MÉDIO COMPLETO\":4037,\"SUPERIOR COMPLETO\":926,\"ENSINO MÉDIO INCOMPLETO\":3992}', '{\"NÃO INFORMADO\":17115,\"Parda\":3458,\"Preta\":574,\"Branca\":671,\"Amarela\":25,\"Indígena\":2}', '{\"DIVORCIADO\":554,\"SOLTEIRO\":16454,\"VIÚVO\":249,\"CASADO\":4467,\"SEPARADO JUDICIALMENTE\":121}', '{\"Facultativo\":3355,\"Obrigatório\":18490}', 9999),
(31194, 'CAMPO DO BRITO', 15501, 14801, 135, 4, '{\"FEMININO\":8247,\"MASCULINO\":7254}', '{\"20 anos\":286,\"55 a 59 anos\":1190,\"30 a 34 anos\":1522,\"45 a 49 anos\":1391,\"35 a 39 anos\":1426,\"21 a 24 anos\":1122,\"65 a 69 anos\":794,\"40 a 44 anos\":1568,\"70 a 74 anos\":591,\"19 anos\":277,\"17 anos\":152,\"50 a 54 anos\":1287,\"25 a 29 anos\":1633,\"60 a 64 anos\":946,\"80 a 84 anos\":299,\"85 a 89 anos\":172,\"75 a 79 anos\":412,\"95 a 99 anos\":37,\"16 anos\":54,\"90 a 94 anos\":71,\"18 anos\":253,\"100 anos ou mais\":14,\"15 anos\":4}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":4867,\"ENSINO FUNDAMENTAL COMPLETO\":480,\"SUPERIOR INCOMPLETO\":515,\"ENSINO MÉDIO COMPLETO\":2327,\"ENSINO MÉDIO INCOMPLETO\":2777,\"SUPERIOR COMPLETO\":742,\"ANALFABETO\":1118,\"LÊ E ESCREVE\":2675}', '{\"NÃO INFORMADO\":12295,\"Branca\":559,\"Parda\":2358,\"Preta\":284,\"Amarela\":4,\"Indígena\":1}', '{\"SOLTEIRO\":10919,\"CASADO\":3652,\"DIVORCIADO\":485,\"VIÚVO\":329,\"SEPARADO JUDICIALMENTE\":116}', '{\"Obrigatório\":13028,\"Facultativo\":2473}', 9999),
(31216, 'CANHOBA', 4603, 4487, 21, 0, '{\"MASCULINO\":2354,\"FEMININO\":2249}', '{\"40 a 44 anos\":416,\"25 a 29 anos\":520,\"21 a 24 anos\":350,\"85 a 89 anos\":39,\"65 a 69 anos\":194,\"60 a 64 anos\":287,\"35 a 39 anos\":530,\"75 a 79 anos\":108,\"45 a 49 anos\":404,\"55 a 59 anos\":328,\"80 a 84 anos\":55,\"17 anos\":63,\"70 a 74 anos\":134,\"19 anos\":83,\"30 a 34 anos\":485,\"50 a 54 anos\":391,\"16 anos\":13,\"18 anos\":83,\"15 anos\":5,\"20 anos\":93,\"90 a 94 anos\":18,\"95 a 99 anos\":4}', '{\"ENSINO MÉDIO COMPLETO\":861,\"ANALFABETO\":345,\"ENSINO FUNDAMENTAL INCOMPLETO\":1652,\"LÊ E ESCREVE\":368,\"ENSINO FUNDAMENTAL COMPLETO\":214,\"SUPERIOR COMPLETO\":148,\"ENSINO MÉDIO INCOMPLETO\":908,\"SUPERIOR INCOMPLETO\":107}', '{\"NÃO INFORMADO\":3422,\"Parda\":763,\"Preta\":170,\"Branca\":238,\"Amarela\":8,\"Indígena\":2}', '{\"CASADO\":1165,\"SOLTEIRO\":3242,\"VIÚVO\":66,\"DIVORCIADO\":109,\"SEPARADO JUDICIALMENTE\":21}', '{\"Obrigatório\":3932,\"Facultativo\":671}', 9999),
(31232, 'CANINDÉ DE SÃO FRANCISCO', 24412, 23072, 181, 2, '{\"MASCULINO\":11917,\"FEMININO\":12495}', '{\"70 a 74 anos\":584,\"45 a 49 anos\":2296,\"25 a 29 anos\":3034,\"65 a 69 anos\":796,\"21 a 24 anos\":2237,\"55 a 59 anos\":1654,\"20 anos\":538,\"50 a 54 anos\":1937,\"30 a 34 anos\":2775,\"40 a 44 anos\":2435,\"35 a 39 anos\":2577,\"80 a 84 anos\":275,\"60 a 64 anos\":1225,\"75 a 79 anos\":468,\"85 a 89 anos\":121,\"19 anos\":579,\"18 anos\":423,\"17 anos\":283,\"15 anos\":12,\"16 anos\":106,\"95 a 99 anos\":8,\"100 anos ou mais\":11,\"90 a 94 anos\":38}', '{\"LÊ E ESCREVE\":4210,\"ENSINO FUNDAMENTAL INCOMPLETO\":8450,\"ENSINO MÉDIO COMPLETO\":3233,\"SUPERIOR INCOMPLETO\":550,\"ENSINO MÉDIO INCOMPLETO\":4741,\"ANALFABETO\":1764,\"ENSINO FUNDAMENTAL COMPLETO\":714,\"SUPERIOR COMPLETO\":750}', '{\"NÃO INFORMADO\":19419,\"Parda\":3844,\"Branca\":700,\"Preta\":379,\"Amarela\":61,\"Indígena\":9}', '{\"CASADO\":5258,\"SOLTEIRO\":18029,\"VIÚVO\":336,\"DIVORCIADO\":611,\"SEPARADO JUDICIALMENTE\":178}', '{\"Facultativo\":3202,\"Obrigatório\":21210}', 9999),
(31259, 'CAPELA', 26910, 25669, 371, 6, '{\"FEMININO\":13991,\"MASCULINO\":12919}', '{\"60 a 64 anos\":1498,\"21 a 24 anos\":2251,\"55 a 59 anos\":1820,\"75 a 79 anos\":611,\"65 a 69 anos\":1188,\"16 anos\":111,\"35 a 39 anos\":2795,\"50 a 54 anos\":2114,\"85 a 89 anos\":189,\"19 anos\":527,\"70 a 74 anos\":919,\"18 anos\":452,\"40 a 44 anos\":2811,\"25 a 29 anos\":3017,\"80 a 84 anos\":389,\"20 anos\":538,\"45 a 49 anos\":2535,\"30 a 34 anos\":2780,\"95 a 99 anos\":16,\"17 anos\":250,\"90 a 94 anos\":80,\"15 anos\":11,\"100 anos ou mais\":8}', '{\"LÊ E ESCREVE\":2635,\"ENSINO FUNDAMENTAL INCOMPLETO\":8783,\"ENSINO MÉDIO COMPLETO\":5305,\"ANALFABETO\":1576,\"ENSINO MÉDIO INCOMPLETO\":5641,\"ENSINO FUNDAMENTAL COMPLETO\":1101,\"SUPERIOR COMPLETO\":1060,\"SUPERIOR INCOMPLETO\":809}', '{\"Parda\":5163,\"NÃO INFORMADO\":20058,\"Preta\":801,\"Branca\":831,\"Amarela\":55,\"Indígena\":2}', '{\"CASADO\":4754,\"SOLTEIRO\":20665,\"DIVORCIADO\":877,\"VIÚVO\":485,\"SEPARADO JUDICIALMENTE\":129}', '{\"Obrigatório\":23246,\"Facultativo\":3664}', 9999),
(31275, 'CARIRA', 16872, 16111, 123, 2, '{\"MASCULINO\":8082,\"FEMININO\":8790}', '{\"60 a 64 anos\":1105,\"80 a 84 anos\":352,\"35 a 39 anos\":1630,\"30 a 34 anos\":1604,\"40 a 44 anos\":1550,\"85 a 89 anos\":215,\"70 a 74 anos\":746,\"50 a 54 anos\":1408,\"25 a 29 anos\":1705,\"16 anos\":61,\"55 a 59 anos\":1300,\"65 a 69 anos\":856,\"21 a 24 anos\":1213,\"45 a 49 anos\":1476,\"75 a 79 anos\":530,\"20 anos\":295,\"19 anos\":308,\"18 anos\":245,\"17 anos\":140,\"90 a 94 anos\":81,\"100 anos ou mais\":6,\"15 anos\":14,\"95 a 99 anos\":32}', '{\"ANALFABETO\":3436,\"ENSINO MÉDIO INCOMPLETO\":2657,\"ENSINO MÉDIO COMPLETO\":1862,\"ENSINO FUNDAMENTAL COMPLETO\":749,\"SUPERIOR COMPLETO\":538,\"ENSINO FUNDAMENTAL INCOMPLETO\":4653,\"LÊ E ESCREVE\":2543,\"SUPERIOR INCOMPLETO\":434}', '{\"NÃO INFORMADO\":14120,\"Parda\":1936,\"Branca\":668,\"Preta\":126,\"Amarela\":22}', '{\"CASADO\":4076,\"DIVORCIADO\":485,\"SOLTEIRO\":11815,\"VIÚVO\":370,\"SEPARADO JUDICIALMENTE\":126}', '{\"Facultativo\":4491,\"Obrigatório\":12381}', 9999),
(31291, 'CARMÓPOLIS', 12541, 11975, 117, 5, '{\"FEMININO\":6558,\"MASCULINO\":5983}', '{\"50 a 54 anos\":967,\"25 a 29 anos\":1361,\"20 anos\":290,\"21 a 24 anos\":1014,\"40 a 44 anos\":1449,\"80 a 84 anos\":132,\"45 a 49 anos\":1335,\"70 a 74 anos\":340,\"60 a 64 anos\":723,\"19 anos\":241,\"65 a 69 anos\":526,\"75 a 79 anos\":227,\"30 a 34 anos\":1329,\"35 a 39 anos\":1396,\"55 a 59 anos\":836,\"17 anos\":82,\"90 a 94 anos\":32,\"18 anos\":173,\"85 a 89 anos\":54,\"16 anos\":24,\"95 a 99 anos\":6,\"15 anos\":4}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":4122,\"ENSINO MÉDIO INCOMPLETO\":2671,\"ENSINO FUNDAMENTAL COMPLETO\":495,\"ENSINO MÉDIO COMPLETO\":2997,\"ANALFABETO\":365,\"SUPERIOR COMPLETO\":483,\"LÊ E ESCREVE\":922,\"SUPERIOR INCOMPLETO\":486}', '{\"NÃO INFORMADO\":10667,\"Parda\":1229,\"Preta\":340,\"Branca\":277,\"Amarela\":24,\"Indígena\":4}', '{\"CASADO\":2611,\"SOLTEIRO\":9227,\"VIÚVO\":221,\"SEPARADO JUDICIALMENTE\":97,\"DIVORCIADO\":385}', '{\"Obrigatório\":11416,\"Facultativo\":1125}', 9999),
(31313, 'CEDRO DE SÃO JOÃO', 5265, 5154, 63, 0, '{\"MASCULINO\":2523,\"FEMININO\":2742}', '{\"25 a 29 anos\":530,\"30 a 34 anos\":480,\"21 a 24 anos\":385,\"19 anos\":99,\"18 anos\":100,\"70 a 74 anos\":210,\"80 a 84 anos\":113,\"40 a 44 anos\":477,\"60 a 64 anos\":378,\"35 a 39 anos\":541,\"55 a 59 anos\":443,\"75 a 79 anos\":157,\"50 a 54 anos\":450,\"45 a 49 anos\":435,\"65 a 69 anos\":236,\"20 anos\":96,\"95 a 99 anos\":6,\"17 anos\":46,\"85 a 89 anos\":59,\"90 a 94 anos\":15,\"16 anos\":8,\"100 anos ou mais\":1}', '{\"ENSINO FUNDAMENTAL COMPLETO\":267,\"ENSINO FUNDAMENTAL INCOMPLETO\":1474,\"ENSINO MÉDIO COMPLETO\":1232,\"LÊ E ESCREVE\":465,\"SUPERIOR COMPLETO\":429,\"ENSINO MÉDIO INCOMPLETO\":971,\"SUPERIOR INCOMPLETO\":253,\"ANALFABETO\":174}', '{\"NÃO INFORMADO\":4332,\"Parda\":581,\"Branca\":249,\"Preta\":92,\"Amarela\":11}', '{\"SOLTEIRO\":3478,\"DIVORCIADO\":180,\"CASADO\":1470,\"VIÚVO\":111,\"SEPARADO JUDICIALMENTE\":26}', '{\"Obrigatório\":4550,\"Facultativo\":715}', 9999),
(31330, 'CRISTINÁPOLIS', 14519, 13764, 77, 3, '{\"FEMININO\":7614,\"MASCULINO\":6905}', '{\"40 a 44 anos\":1540,\"50 a 54 anos\":1167,\"55 a 59 anos\":1016,\"70 a 74 anos\":415,\"45 a 49 anos\":1371,\"75 a 79 anos\":290,\"35 a 39 anos\":1488,\"30 a 34 anos\":1543,\"18 anos\":228,\"65 a 69 anos\":567,\"21 a 24 anos\":1230,\"60 a 64 anos\":755,\"80 a 84 anos\":201,\"25 a 29 anos\":1730,\"20 anos\":353,\"90 a 94 anos\":55,\"85 a 89 anos\":114,\"19 anos\":263,\"16 anos\":49,\"17 anos\":116,\"95 a 99 anos\":15,\"100 anos ou mais\":7,\"15 anos\":5,\"Inválida\":1}', '{\"ENSINO MÉDIO COMPLETO\":2134,\"ENSINO FUNDAMENTAL INCOMPLETO\":4741,\"LÊ E ESCREVE\":2177,\"ENSINO FUNDAMENTAL COMPLETO\":493,\"ANALFABETO\":1214,\"SUPERIOR INCOMPLETO\":347,\"ENSINO MÉDIO INCOMPLETO\":2952,\"SUPERIOR COMPLETO\":461}', '{\"NÃO INFORMADO\":11283,\"Parda\":2443,\"Branca\":402,\"Preta\":376,\"Amarela\":12,\"Indígena\":3}', '{\"SOLTEIRO\":12128,\"CASADO\":1945,\"DIVORCIADO\":261,\"VIÚVO\":142,\"SEPARADO JUDICIALMENTE\":43}', '{\"Obrigatório\":12433,\"Facultativo\":2086}', 9999),
(31356, 'NOSSA SENHORA APARECIDA', 7522, 7251, 50, 1, '{\"MASCULINO\":3717,\"FEMININO\":3805}', '{\"75 a 79 anos\":255,\"40 a 44 anos\":679,\"18 anos\":91,\"50 a 54 anos\":663,\"70 a 74 anos\":322,\"55 a 59 anos\":541,\"20 anos\":129,\"45 a 49 anos\":756,\"35 a 39 anos\":768,\"60 a 64 anos\":453,\"25 a 29 anos\":772,\"19 anos\":133,\"85 a 89 anos\":82,\"65 a 69 anos\":340,\"30 a 34 anos\":748,\"21 a 24 anos\":504,\"17 anos\":64,\"80 a 84 anos\":144,\"16 anos\":16,\"90 a 94 anos\":51,\"100 anos ou mais\":2,\"95 a 99 anos\":6,\"15 anos\":3}', '{\"LÊ E ESCREVE\":1066,\"ENSINO FUNDAMENTAL INCOMPLETO\":2368,\"ENSINO FUNDAMENTAL COMPLETO\":308,\"ENSINO MÉDIO COMPLETO\":1021,\"SUPERIOR INCOMPLETO\":205,\"ENSINO MÉDIO INCOMPLETO\":1265,\"ANALFABETO\":1027,\"SUPERIOR COMPLETO\":262}', '{\"Parda\":809,\"NÃO INFORMADO\":6393,\"Preta\":35,\"Branca\":285}', '{\"DIVORCIADO\":176,\"SOLTEIRO\":4644,\"CASADO\":2507,\"VIÚVO\":149,\"SEPARADO JUDICIALMENTE\":46}', '{\"Facultativo\":1587,\"Obrigatório\":5935}', 9999),
(31372, 'CUMBE', 4435, 4250, 28, 0, '{\"MASCULINO\":2194,\"FEMININO\":2241}', '{\"50 a 54 anos\":361,\"30 a 34 anos\":478,\"40 a 44 anos\":434,\"60 a 64 anos\":263,\"21 a 24 anos\":352,\"45 a 49 anos\":432,\"55 a 59 anos\":337,\"18 anos\":65,\"25 a 29 anos\":483,\"70 a 74 anos\":142,\"35 a 39 anos\":449,\"65 a 69 anos\":181,\"17 anos\":49,\"75 a 79 anos\":109,\"80 a 84 anos\":78,\"85 a 89 anos\":43,\"19 anos\":67,\"20 anos\":79,\"16 anos\":8,\"100 anos ou mais\":2,\"90 a 94 anos\":16,\"95 a 99 anos\":4,\"15 anos\":3}', '{\"LÊ E ESCREVE\":489,\"ENSINO MÉDIO INCOMPLETO\":815,\"SUPERIOR INCOMPLETO\":158,\"ENSINO FUNDAMENTAL COMPLETO\":177,\"ENSINO FUNDAMENTAL INCOMPLETO\":1500,\"SUPERIOR COMPLETO\":244,\"ANALFABETO\":213,\"ENSINO MÉDIO COMPLETO\":839}', '{\"NÃO INFORMADO\":3685,\"Parda\":537,\"Branca\":155,\"Preta\":52,\"Amarela\":6}', '{\"SOLTEIRO\":3123,\"CASADO\":1078,\"VIÚVO\":67,\"DIVORCIADO\":144,\"SEPARADO JUDICIALMENTE\":23}', '{\"Obrigatório\":3860,\"Facultativo\":575}', 9999),
(31399, 'DIVINA PASTORA', 4374, 4214, 21, 0, '{\"FEMININO\":2258,\"MASCULINO\":2116}', '{\"70 a 74 anos\":126,\"40 a 44 anos\":470,\"50 a 54 anos\":366,\"75 a 79 anos\":84,\"30 a 34 anos\":451,\"35 a 39 anos\":423,\"20 anos\":93,\"21 a 24 anos\":331,\"45 a 49 anos\":404,\"60 a 64 anos\":235,\"55 a 59 anos\":316,\"90 a 94 anos\":17,\"19 anos\":101,\"25 a 29 anos\":492,\"80 a 84 anos\":64,\"18 anos\":82,\"16 anos\":28,\"65 a 69 anos\":183,\"17 anos\":66,\"85 a 89 anos\":33,\"15 anos\":5,\"100 anos ou mais\":1,\"95 a 99 anos\":3}', '{\"LÊ E ESCREVE\":469,\"ENSINO MÉDIO COMPLETO\":1008,\"ENSINO FUNDAMENTAL INCOMPLETO\":1191,\"SUPERIOR INCOMPLETO\":167,\"SUPERIOR COMPLETO\":195,\"ENSINO MÉDIO INCOMPLETO\":991,\"ANALFABETO\":174,\"ENSINO FUNDAMENTAL COMPLETO\":179}', '{\"NÃO INFORMADO\":3448,\"Preta\":241,\"Parda\":560,\"Branca\":121,\"Indígena\":2,\"Amarela\":2}', '{\"VIÚVO\":81,\"CASADO\":785,\"SOLTEIRO\":3348,\"SEPARADO JUDICIALMENTE\":23,\"DIVORCIADO\":137}', '{\"Facultativo\":529,\"Obrigatório\":3845}', 9999),
(31410, 'ESTÂNCIA', 50267, 47758, 342, 15, '{\"FEMININO\":26619,\"MASCULINO\":23648}', '{\"45 a 49 anos\":4811,\"40 a 44 anos\":5411,\"80 a 84 anos\":730,\"55 a 59 anos\":3834,\"65 a 69 anos\":2492,\"35 a 39 anos\":5170,\"60 a 64 anos\":3163,\"19 anos\":917,\"21 a 24 anos\":3735,\"70 a 74 anos\":1743,\"25 a 29 anos\":5253,\"30 a 34 anos\":5216,\"50 a 54 anos\":4018,\"75 a 79 anos\":1226,\"18 anos\":696,\"85 a 89 anos\":350,\"20 anos\":902,\"17 anos\":336,\"15 anos\":13,\"95 a 99 anos\":25,\"90 a 94 anos\":116,\"16 anos\":105,\"100 anos ou mais\":5}', '{\"SUPERIOR INCOMPLETO\":2014,\"ENSINO MÉDIO COMPLETO\":12526,\"LÊ E ESCREVE\":3306,\"ENSINO FUNDAMENTAL INCOMPLETO\":15614,\"SUPERIOR COMPLETO\":2812,\"ANALFABETO\":1939,\"ENSINO MÉDIO INCOMPLETO\":9442,\"ENSINO FUNDAMENTAL COMPLETO\":2614}', '{\"NÃO INFORMADO\":40993,\"Branca\":1347,\"Preta\":1188,\"Parda\":6676,\"Amarela\":59,\"Indígena\":4}', '{\"SOLTEIRO\":36153,\"CASADO\":11226,\"VIÚVO\":916,\"DIVORCIADO\":1632,\"SEPARADO JUDICIALMENTE\":340}', '{\"Obrigatório\":44363,\"Facultativo\":5904}', 9999),
(31437, 'FEIRA NOVA', 6168, 5909, 38, 0, '{\"MASCULINO\":2954,\"FEMININO\":3214}', '{\"50 a 54 anos\":452,\"55 a 59 anos\":451,\"40 a 44 anos\":619,\"75 a 79 anos\":129,\"19 anos\":128,\"25 a 29 anos\":702,\"80 a 84 anos\":92,\"45 a 49 anos\":539,\"30 a 34 anos\":645,\"60 a 64 anos\":370,\"35 a 39 anos\":630,\"21 a 24 anos\":502,\"17 anos\":71,\"20 anos\":124,\"70 a 74 anos\":216,\"95 a 99 anos\":6,\"65 a 69 anos\":268,\"16 anos\":19,\"15 anos\":6,\"85 a 89 anos\":46,\"18 anos\":128,\"90 a 94 anos\":21,\"100 anos ou mais\":4}', '{\"LÊ E ESCREVE\":802,\"ENSINO FUNDAMENTAL INCOMPLETO\":2032,\"ENSINO MÉDIO COMPLETO\":1193,\"ANALFABETO\":438,\"ENSINO MÉDIO INCOMPLETO\":1172,\"ENSINO FUNDAMENTAL COMPLETO\":217,\"SUPERIOR COMPLETO\":170,\"SUPERIOR INCOMPLETO\":144}', '{\"NÃO INFORMADO\":4409,\"Parda\":1357,\"Branca\":265,\"Preta\":132,\"Amarela\":5}', '{\"SOLTEIRO\":4716,\"CASADO\":1186,\"VIÚVO\":74,\"DIVORCIADO\":170,\"SEPARADO JUDICIALMENTE\":22}', '{\"Obrigatório\":5274,\"Facultativo\":894}', 9999),
(31453, 'FREI PAULO', 13357, 12934, 57, 0, '{\"FEMININO\":7057,\"MASCULINO\":6300}', '{\"35 a 39 anos\":1370,\"55 a 59 anos\":996,\"40 a 44 anos\":1287,\"21 a 24 anos\":974,\"80 a 84 anos\":245,\"45 a 49 anos\":1113,\"75 a 79 anos\":411,\"50 a 54 anos\":1078,\"70 a 74 anos\":514,\"25 a 29 anos\":1371,\"17 anos\":138,\"65 a 69 anos\":625,\"30 a 34 anos\":1457,\"60 a 64 anos\":768,\"18 anos\":214,\"85 a 89 anos\":140,\"20 anos\":251,\"19 anos\":223,\"15 anos\":17,\"16 anos\":69,\"90 a 94 anos\":73,\"100 anos ou mais\":6,\"95 a 99 anos\":16,\"Inválida\":1}', '{\"ENSINO MÉDIO INCOMPLETO\":2238,\"LÊ E ESCREVE\":1381,\"ENSINO FUNDAMENTAL COMPLETO\":740,\"ANALFABETO\":1745,\"ENSINO FUNDAMENTAL INCOMPLETO\":4574,\"ENSINO MÉDIO COMPLETO\":1899,\"SUPERIOR INCOMPLETO\":357,\"SUPERIOR COMPLETO\":423}', '{\"NÃO INFORMADO\":11633,\"Branca\":265,\"Parda\":1308,\"Preta\":145,\"Indígena\":1,\"Amarela\":5}', '{\"SOLTEIRO\":9070,\"CASADO\":3533,\"DIVORCIADO\":348,\"SEPARADO JUDICIALMENTE\":109,\"VIÚVO\":297}', '{\"Obrigatório\":10567,\"Facultativo\":2790}', 9999),
(31470, 'GENERAL MAYNARD', 3314, 3208, 34, 0, '{\"FEMININO\":1710,\"MASCULINO\":1604}', '{\"21 a 24 anos\":255,\"55 a 59 anos\":257,\"45 a 49 anos\":303,\"20 anos\":63,\"35 a 39 anos\":321,\"85 a 89 anos\":20,\"40 a 44 anos\":336,\"19 anos\":50,\"30 a 34 anos\":362,\"18 anos\":67,\"65 a 69 anos\":150,\"50 a 54 anos\":277,\"60 a 64 anos\":210,\"70 a 74 anos\":111,\"80 a 84 anos\":52,\"25 a 29 anos\":362,\"17 anos\":33,\"75 a 79 anos\":63,\"15 anos\":1,\"100 anos ou mais\":5,\"16 anos\":7,\"90 a 94 anos\":8,\"95 a 99 anos\":1}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":940,\"SUPERIOR COMPLETO\":186,\"ENSINO MÉDIO COMPLETO\":949,\"ENSINO MÉDIO INCOMPLETO\":682,\"LÊ E ESCREVE\":162,\"ANALFABETO\":141,\"ENSINO FUNDAMENTAL COMPLETO\":129,\"SUPERIOR INCOMPLETO\":125}', '{\"NÃO INFORMADO\":2495,\"Parda\":494,\"Preta\":164,\"Amarela\":4,\"Branca\":154,\"Indígena\":3}', '{\"SOLTEIRO\":2395,\"CASADO\":737,\"DIVORCIADO\":115,\"VIÚVO\":55,\"SEPARADO JUDICIALMENTE\":12}', '{\"Obrigatório\":2915,\"Facultativo\":399}', 9999),
(31496, 'GARARU', 9521, 9164, 31, 0, '{\"MASCULINO\":4759,\"FEMININO\":4762}', '{\"50 a 54 anos\":800,\"25 a 29 anos\":1023,\"35 a 39 anos\":910,\"40 a 44 anos\":963,\"45 a 49 anos\":859,\"60 a 64 anos\":584,\"75 a 79 anos\":272,\"55 a 59 anos\":723,\"70 a 74 anos\":359,\"21 a 24 anos\":741,\"19 anos\":166,\"17 anos\":80,\"80 a 84 anos\":165,\"30 a 34 anos\":949,\"85 a 89 anos\":95,\"65 a 69 anos\":428,\"18 anos\":150,\"20 anos\":184,\"16 anos\":22,\"90 a 94 anos\":38,\"95 a 99 anos\":7,\"15 anos\":2,\"100 anos ou mais\":1}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":3391,\"SUPERIOR COMPLETO\":353,\"ENSINO MÉDIO INCOMPLETO\":1688,\"LÊ E ESCREVE\":1242,\"ENSINO FUNDAMENTAL COMPLETO\":433,\"ANALFABETO\":681,\"ENSINO MÉDIO COMPLETO\":1537,\"SUPERIOR INCOMPLETO\":196}', '{\"Branca\":489,\"Parda\":1435,\"NÃO INFORMADO\":7367,\"Preta\":215,\"Amarela\":9,\"Indígena\":6}', '{\"SOLTEIRO\":6573,\"CASADO\":2572,\"DIVORCIADO\":193,\"VIÚVO\":130,\"SEPARADO JUDICIALMENTE\":53}', '{\"Obrigatório\":8056,\"Facultativo\":1465}', 9999),
(31518, 'GRACCHO CARDOSO', 6375, 6196, 37, 2, '{\"FEMININO\":3194,\"MASCULINO\":3181}', '{\"60 a 64 anos\":403,\"25 a 29 anos\":655,\"70 a 74 anos\":225,\"21 a 24 anos\":473,\"19 anos\":127,\"30 a 34 anos\":642,\"50 a 54 anos\":500,\"65 a 69 anos\":326,\"40 a 44 anos\":608,\"75 a 79 anos\":183,\"35 a 39 anos\":648,\"45 a 49 anos\":593,\"55 a 59 anos\":479,\"85 a 89 anos\":60,\"17 anos\":57,\"20 anos\":119,\"90 a 94 anos\":29,\"18 anos\":118,\"80 a 84 anos\":93,\"15 anos\":7,\"95 a 99 anos\":6,\"100 anos ou mais\":5,\"16 anos\":19}', '{\"SUPERIOR INCOMPLETO\":196,\"ENSINO FUNDAMENTAL COMPLETO\":317,\"ANALFABETO\":399,\"LÊ E ESCREVE\":1090,\"ENSINO MÉDIO COMPLETO\":1036,\"ENSINO FUNDAMENTAL INCOMPLETO\":1911,\"ENSINO MÉDIO INCOMPLETO\":1134,\"SUPERIOR COMPLETO\":292}', '{\"NÃO INFORMADO\":5027,\"Parda\":931,\"Branca\":333,\"Preta\":77,\"Amarela\":6,\"Indígena\":1}', '{\"CASADO\":1590,\"SOLTEIRO\":4488,\"DIVORCIADO\":175,\"VIÚVO\":95,\"SEPARADO JUDICIALMENTE\":27}', '{\"Obrigatório\":5426,\"Facultativo\":949}', 9999),
(31534, 'ILHA DAS FLORES', 7613, 7332, 67, 1, '{\"MASCULINO\":3637,\"FEMININO\":3976}', '{\"30 a 34 anos\":874,\"55 a 59 anos\":518,\"65 a 69 anos\":327,\"21 a 24 anos\":592,\"35 a 39 anos\":798,\"45 a 49 anos\":656,\"50 a 54 anos\":590,\"20 anos\":171,\"75 a 79 anos\":153,\"17 anos\":60,\"80 a 84 anos\":118,\"25 a 29 anos\":892,\"90 a 94 anos\":41,\"19 anos\":143,\"100 anos ou mais\":3,\"60 a 64 anos\":435,\"40 a 44 anos\":766,\"70 a 74 anos\":229,\"95 a 99 anos\":14,\"18 anos\":145,\"85 a 89 anos\":74,\"16 anos\":14}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":2761,\"ENSINO MÉDIO COMPLETO\":1394,\"ENSINO MÉDIO INCOMPLETO\":1533,\"ANALFABETO\":436,\"LÊ E ESCREVE\":728,\"SUPERIOR COMPLETO\":255,\"ENSINO FUNDAMENTAL COMPLETO\":338,\"SUPERIOR INCOMPLETO\":168}', '{\"Branca\":303,\"NÃO INFORMADO\":5481,\"Parda\":1502,\"Preta\":302,\"Amarela\":20,\"Indígena\":5}', '{\"SOLTEIRO\":5554,\"DIVORCIADO\":163,\"CASADO\":1710,\"VIÚVO\":132,\"SEPARADO JUDICIALMENTE\":54}', '{\"Obrigatório\":6629,\"Facultativo\":984}', 9999),
(31550, 'INDIAROBA', 13549, 12972, 136, 0, '{\"FEMININO\":6936,\"MASCULINO\":6613}', '{\"18 anos\":305,\"20 anos\":318,\"19 anos\":325,\"21 a 24 anos\":1230,\"25 a 29 anos\":1559,\"50 a 54 anos\":1080,\"75 a 79 anos\":281,\"70 a 74 anos\":425,\"45 a 49 anos\":1239,\"40 a 44 anos\":1434,\"30 a 34 anos\":1380,\"85 a 89 anos\":81,\"55 a 59 anos\":916,\"35 a 39 anos\":1358,\"60 a 64 anos\":739,\"65 a 69 anos\":517,\"16 anos\":14,\"17 anos\":132,\"80 a 84 anos\":168,\"90 a 94 anos\":37,\"95 a 99 anos\":8,\"100 anos ou mais\":3}', '{\"ENSINO MÉDIO INCOMPLETO\":2919,\"ENSINO MÉDIO COMPLETO\":1988,\"ENSINO FUNDAMENTAL INCOMPLETO\":4550,\"ANALFABETO\":1426,\"LÊ E ESCREVE\":1422,\"SUPERIOR INCOMPLETO\":251,\"SUPERIOR COMPLETO\":540,\"ENSINO FUNDAMENTAL COMPLETO\":453}', '{\"Branca\":189,\"Parda\":2043,\"Preta\":580,\"NÃO INFORMADO\":10724,\"Indígena\":3,\"Amarela\":10}', '{\"SOLTEIRO\":10753,\"CASADO\":2286,\"VIÚVO\":182,\"DIVORCIADO\":278,\"SEPARADO JUDICIALMENTE\":50}', '{\"Obrigatório\":11364,\"Facultativo\":2185}', 9999),
(31577, 'ITABAIANA', 75329, 71767, 1039, 27, '{\"MASCULINO\":34761,\"FEMININO\":40568}', '{\"30 a 34 anos\":8026,\"60 a 64 anos\":4627,\"55 a 59 anos\":5573,\"40 a 44 anos\":7891,\"17 anos\":565,\"21 a 24 anos\":5631,\"50 a 54 anos\":6220,\"45 a 49 anos\":7220,\"35 a 39 anos\":7867,\"18 anos\":1095,\"20 anos\":1328,\"70 a 74 anos\":2622,\"25 a 29 anos\":8100,\"65 a 69 anos\":3423,\"80 a 84 anos\":1056,\"95 a 99 anos\":48,\"75 a 79 anos\":1869,\"90 a 94 anos\":185,\"85 a 89 anos\":504,\"19 anos\":1245,\"16 anos\":184,\"15 anos\":43,\"100 anos ou mais\":7}', '{\"ENSINO MÉDIO INCOMPLETO\":14671,\"ANALFABETO\":3776,\"ENSINO FUNDAMENTAL INCOMPLETO\":23919,\"SUPERIOR COMPLETO\":4116,\"ENSINO MÉDIO COMPLETO\":12651,\"LÊ E ESCREVE\":9611,\"ENSINO FUNDAMENTAL COMPLETO\":2685,\"SUPERIOR INCOMPLETO\":3900}', '{\"NÃO INFORMADO\":61615,\"Parda\":9056,\"Branca\":3547,\"Preta\":1080,\"Indígena\":9,\"Amarela\":22}', '{\"SOLTEIRO\":51851,\"CASADO\":18694,\"DIVORCIADO\":2422,\"SEPARADO JUDICIALMENTE\":646,\"VIÚVO\":1716}', '{\"Obrigatório\":65857,\"Facultativo\":9472}', 9999),
(31593, 'ITABAIANINHA', 31728, 30154, 183, 2, '{\"MASCULINO\":15181,\"FEMININO\":16547}', '{\"65 a 69 anos\":1416,\"19 anos\":613,\"50 a 54 anos\":2412,\"40 a 44 anos\":3385,\"30 a 34 anos\":3359,\"55 a 59 anos\":2173,\"60 a 64 anos\":1697,\"25 a 29 anos\":3644,\"35 a 39 anos\":3175,\"75 a 79 anos\":774,\"70 a 74 anos\":1023,\"18 anos\":513,\"21 a 24 anos\":2720,\"85 a 89 anos\":310,\"45 a 49 anos\":2921,\"17 anos\":235,\"90 a 94 anos\":134,\"80 a 84 anos\":471,\"20 anos\":607,\"16 anos\":72,\"95 a 99 anos\":47,\"15 anos\":9,\"100 anos ou mais\":18}', '{\"LÊ E ESCREVE\":5198,\"ENSINO MÉDIO COMPLETO\":4242,\"ANALFABETO\":2829,\"ENSINO FUNDAMENTAL INCOMPLETO\":10914,\"ENSINO MÉDIO INCOMPLETO\":6112,\"SUPERIOR COMPLETO\":939,\"SUPERIOR INCOMPLETO\":644,\"ENSINO FUNDAMENTAL COMPLETO\":850}', '{\"NÃO INFORMADO\":27311,\"Parda\":3172,\"Branca\":811,\"Preta\":415,\"Indígena\":3,\"Amarela\":16}', '{\"SOLTEIRO\":25928,\"CASADO\":5020,\"DIVORCIADO\":459,\"VIÚVO\":252,\"SEPARADO JUDICIALMENTE\":69}', '{\"Obrigatório\":26657,\"Facultativo\":5071}', 9999),
(31615, 'ITABI', 4689, 4499, 11, 0, '{\"FEMININO\":2417,\"MASCULINO\":2272}', '{\"55 a 59 anos\":373,\"21 a 24 anos\":330,\"35 a 39 anos\":448,\"65 a 69 anos\":266,\"25 a 29 anos\":455,\"50 a 54 anos\":376,\"60 a 64 anos\":308,\"17 anos\":38,\"80 a 84 anos\":95,\"70 a 74 anos\":199,\"30 a 34 anos\":482,\"75 a 79 anos\":160,\"40 a 44 anos\":440,\"45 a 49 anos\":407,\"90 a 94 anos\":20,\"85 a 89 anos\":51,\"18 anos\":70,\"20 anos\":84,\"19 anos\":68,\"16 anos\":10,\"95 a 99 anos\":6,\"15 anos\":2,\"100 anos ou mais\":1}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":1706,\"ENSINO MÉDIO COMPLETO\":930,\"SUPERIOR INCOMPLETO\":189,\"ENSINO MÉDIO INCOMPLETO\":926,\"SUPERIOR COMPLETO\":252,\"LÊ E ESCREVE\":308,\"ANALFABETO\":215,\"ENSINO FUNDAMENTAL COMPLETO\":163}', '{\"Parda\":438,\"NÃO INFORMADO\":3926,\"Branca\":259,\"Preta\":54,\"Amarela\":9,\"Indígena\":3}', '{\"SOLTEIRO\":2909,\"CASADO\":1473,\"SEPARADO JUDICIALMENTE\":56,\"DIVORCIADO\":148,\"VIÚVO\":103}', '{\"Obrigatório\":3979,\"Facultativo\":710}', 9999),
(31631, 'ITAPORANGA D''AJUDA', 29563, 28206, 308, 5, '{\"FEMININO\":15342,\"MASCULINO\":14221}', '{\"50 a 54 anos\":2441,\"25 a 29 anos\":3106,\"90 a 94 anos\":113,\"60 a 64 anos\":1620,\"35 a 39 anos\":3027,\"45 a 49 anos\":2876,\"55 a 59 anos\":2170,\"70 a 74 anos\":946,\"65 a 69 anos\":1348,\"40 a 44 anos\":3139,\"17 anos\":281,\"20 anos\":545,\"21 a 24 anos\":2343,\"75 a 79 anos\":705,\"80 a 84 anos\":402,\"30 a 34 anos\":3079,\"18 anos\":497,\"85 a 89 anos\":210,\"19 anos\":573,\"16 anos\":75,\"15 anos\":10,\"100 anos ou mais\":22,\"95 a 99 anos\":35}', '{\"ENSINO MÉDIO COMPLETO\":5326,\"ENSINO FUNDAMENTAL INCOMPLETO\":9548,\"LÊ E ESCREVE\":2628,\"SUPERIOR INCOMPLETO\":747,\"ANALFABETO\":2657,\"ENSINO FUNDAMENTAL COMPLETO\":1376,\"ENSINO MÉDIO INCOMPLETO\":6324,\"SUPERIOR COMPLETO\":957}', '{\"Parda\":5061,\"NÃO INFORMADO\":22138,\"Branca\":993,\"Preta\":1341,\"Indígena\":6,\"Amarela\":24}', '{\"SOLTEIRO\":22894,\"CASADO\":5347,\"SEPARADO JUDICIALMENTE\":166,\"VIÚVO\":368,\"DIVORCIADO\":788}', '{\"Obrigatório\":25025,\"Facultativo\":4538}', 9999),
(31658, 'JAPARATUBA', 14744, 14078, 123, 6, '{\"FEMININO\":7703,\"MASCULINO\":7041}', '{\"75 a 79 anos\":386,\"20 anos\":269,\"25 a 29 anos\":1701,\"65 a 69 anos\":671,\"70 a 74 anos\":490,\"17 anos\":121,\"21 a 24 anos\":1106,\"35 a 39 anos\":1591,\"40 a 44 anos\":1481,\"50 a 54 anos\":1139,\"60 a 64 anos\":925,\"19 anos\":261,\"30 a 34 anos\":1557,\"45 a 49 anos\":1303,\"18 anos\":194,\"80 a 84 anos\":236,\"55 a 59 anos\":1073,\"95 a 99 anos\":19,\"15 anos\":8,\"90 a 94 anos\":48,\"16 anos\":46,\"85 a 89 anos\":114,\"100 anos ou mais\":5}', '{\"SUPERIOR COMPLETO\":758,\"ENSINO MÉDIO INCOMPLETO\":3085,\"ANALFABETO\":637,\"ENSINO FUNDAMENTAL INCOMPLETO\":4980,\"ENSINO MÉDIO COMPLETO\":2996,\"LÊ E ESCREVE\":1335,\"ENSINO FUNDAMENTAL COMPLETO\":497,\"SUPERIOR INCOMPLETO\":456}', '{\"Parda\":1904,\"NÃO INFORMADO\":11970,\"Amarela\":30,\"Preta\":471,\"Branca\":367,\"Indígena\":2}', '{\"VIÚVO\":290,\"SOLTEIRO\":11007,\"CASADO\":2971,\"DIVORCIADO\":383,\"SEPARADO JUDICIALMENTE\":93}', '{\"Facultativo\":1793,\"Obrigatório\":12951}', 9999),
(31674, 'JAPOATÃ', 11303, 10668, 62, 3, '{\"FEMININO\":5781,\"MASCULINO\":5522}', '{\"60 a 64 anos\":650,\"35 a 39 anos\":1199,\"19 anos\":241,\"45 a 49 anos\":1056,\"70 a 74 anos\":350,\"21 a 24 anos\":948,\"90 a 94 anos\":32,\"40 a 44 anos\":1150,\"20 anos\":227,\"50 a 54 anos\":899,\"85 a 89 anos\":85,\"75 a 79 anos\":297,\"30 a 34 anos\":1239,\"55 a 59 anos\":768,\"65 a 69 anos\":463,\"18 anos\":177,\"25 a 29 anos\":1224,\"80 a 84 anos\":174,\"95 a 99 anos\":9,\"17 anos\":101,\"16 anos\":9,\"100 anos ou mais\":2,\"15 anos\":3}', '{\"ENSINO MÉDIO COMPLETO\":1902,\"ENSINO FUNDAMENTAL INCOMPLETO\":4068,\"ENSINO MÉDIO INCOMPLETO\":2327,\"ANALFABETO\":730,\"ENSINO FUNDAMENTAL COMPLETO\":526,\"LÊ E ESCREVE\":1043,\"SUPERIOR INCOMPLETO\":257,\"SUPERIOR COMPLETO\":450}', '{\"NÃO INFORMADO\":9481,\"Branca\":295,\"Parda\":1189,\"Preta\":293,\"Amarela\":39,\"Indígena\":6}', '{\"DIVORCIADO\":290,\"SOLTEIRO\":8171,\"VIÚVO\":225,\"CASADO\":2554,\"SEPARADO JUDICIALMENTE\":63}', '{\"Obrigatório\":9745,\"Facultativo\":1558}', 9999),
(31690, 'LAGARTO', 81099, 77266, 737, 13, '{\"FEMININO\":43115,\"MASCULINO\":37984}', '{\"35 a 39 anos\":8344,\"65 a 69 anos\":3846,\"60 a 64 anos\":5085,\"50 a 54 anos\":6625,\"21 a 24 anos\":6033,\"45 a 49 anos\":7279,\"19 anos\":1277,\"40 a 44 anos\":8115,\"70 a 74 anos\":3025,\"25 a 29 anos\":8799,\"80 a 84 anos\":1435,\"30 a 34 anos\":8471,\"55 a 59 anos\":6090,\"20 anos\":1435,\"85 a 89 anos\":749,\"75 a 79 anos\":2149,\"90 a 94 anos\":310,\"17 anos\":580,\"18 anos\":1089,\"95 a 99 anos\":98,\"16 anos\":210,\"15 anos\":34,\"100 anos ou mais\":20,\"Inválida\":1}', '{\"SUPERIOR COMPLETO\":3960,\"LÊ E ESCREVE\":9432,\"ANALFABETO\":7049,\"ENSINO MÉDIO COMPLETO\":15382,\"ENSINO FUNDAMENTAL INCOMPLETO\":22791,\"ENSINO MÉDIO INCOMPLETO\":15847,\"SUPERIOR INCOMPLETO\":3357,\"ENSINO FUNDAMENTAL COMPLETO\":3281}', '{\"Branca\":2996,\"NÃO INFORMADO\":66836,\"Parda\":9864,\"Preta\":1290,\"Amarela\":105,\"Indígena\":8}', '{\"SOLTEIRO\":56983,\"VIÚVO\":1458,\"CASADO\":19559,\"DIVORCIADO\":2470,\"SEPARADO JUDICIALMENTE\":629}', '{\"Obrigatório\":67689,\"Facultativo\":13410}', 9999),
(31712, 'LARANJEIRAS', 21934, 21093, 133, 3, '{\"FEMININO\":11511,\"MASCULINO\":10423}', '{\"21 a 24 anos\":1794,\"50 a 54 anos\":1835,\"40 a 44 anos\":2434,\"18 anos\":347,\"35 a 39 anos\":2234,\"25 a 29 anos\":2276,\"45 a 49 anos\":2253,\"70 a 74 anos\":631,\"55 a 59 anos\":1599,\"60 a 64 anos\":1298,\"20 anos\":429,\"65 a 69 anos\":917,\"19 anos\":394,\"85 a 89 anos\":102,\"17 anos\":212,\"16 anos\":90,\"80 a 84 anos\":248,\"30 a 34 anos\":2343,\"75 a 79 anos\":436,\"90 a 94 anos\":34,\"15 anos\":17,\"95 a 99 anos\":9,\"100 anos ou mais\":2}', '{\"ENSINO FUNDAMENTAL COMPLETO\":999,\"SUPERIOR INCOMPLETO\":708,\"ENSINO MÉDIO INCOMPLETO\":5122,\"ENSINO MÉDIO COMPLETO\":5643,\"ENSINO FUNDAMENTAL INCOMPLETO\":6648,\"SUPERIOR COMPLETO\":770,\"ANALFABETO\":785,\"LÊ E ESCREVE\":1259}', '{\"NÃO INFORMADO\":18210,\"Amarela\":31,\"Parda\":2369,\"Preta\":845,\"Branca\":476,\"Indígena\":3}', '{\"SOLTEIRO\":16665,\"CASADO\":4128,\"VIÚVO\":389,\"DIVORCIADO\":607,\"SEPARADO JUDICIALMENTE\":145}', '{\"Obrigatório\":19695,\"Facultativo\":2239}', 9999),
(31739, 'MACAMBIRA', 6765, 6513, 97, 0, '{\"MASCULINO\":3268,\"FEMININO\":3497}', '{\"55 a 59 anos\":501,\"50 a 54 anos\":517,\"65 a 69 anos\":304,\"45 a 49 anos\":577,\"18 anos\":124,\"21 a 24 anos\":503,\"70 a 74 anos\":257,\"75 a 79 anos\":199,\"35 a 39 anos\":670,\"19 anos\":137,\"25 a 29 anos\":703,\"40 a 44 anos\":655,\"80 a 84 anos\":143,\"30 a 34 anos\":708,\"60 a 64 anos\":413,\"20 anos\":125,\"17 anos\":80,\"85 a 89 anos\":57,\"90 a 94 anos\":38,\"16 anos\":24,\"95 a 99 anos\":16,\"15 anos\":8,\"100 anos ou mais\":6}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":2702,\"LÊ E ESCREVE\":667,\"ENSINO MÉDIO INCOMPLETO\":1356,\"ENSINO MÉDIO COMPLETO\":1008,\"SUPERIOR INCOMPLETO\":164,\"ANALFABETO\":412,\"ENSINO FUNDAMENTAL COMPLETO\":275,\"SUPERIOR COMPLETO\":181}', '{\"Branca\":190,\"NÃO INFORMADO\":5658,\"Parda\":831,\"Preta\":84,\"Amarela\":2}', '{\"SOLTEIRO\":4766,\"SEPARADO JUDICIALMENTE\":42,\"CASADO\":1625,\"DIVORCIADO\":181,\"VIÚVO\":151}', '{\"Obrigatório\":5708,\"Facultativo\":1057}', 9999),
(31755, 'MALHADA DOS BOIS', 4256, 4120, 33, 1, '{\"MASCULINO\":2122,\"FEMININO\":2134}', '{\"45 a 49 anos\":405,\"50 a 54 anos\":347,\"65 a 69 anos\":170,\"21 a 24 anos\":333,\"20 anos\":62,\"85 a 89 anos\":30,\"55 a 59 anos\":280,\"35 a 39 anos\":458,\"30 a 34 anos\":429,\"19 anos\":80,\"60 a 64 anos\":254,\"25 a 29 anos\":480,\"80 a 84 anos\":54,\"16 anos\":62,\"18 anos\":85,\"90 a 94 anos\":14,\"70 a 74 anos\":122,\"40 a 44 anos\":410,\"75 a 79 anos\":96,\"17 anos\":59,\"95 a 99 anos\":7,\"15 anos\":19}', '{\"ENSINO MÉDIO INCOMPLETO\":1031,\"LÊ E ESCREVE\":381,\"ENSINO FUNDAMENTAL INCOMPLETO\":1373,\"ANALFABETO\":183,\"ENSINO FUNDAMENTAL COMPLETO\":195,\"ENSINO MÉDIO COMPLETO\":757,\"SUPERIOR INCOMPLETO\":140,\"SUPERIOR COMPLETO\":196}', '{\"NÃO INFORMADO\":3236,\"Parda\":743,\"Branca\":163,\"Amarela\":17,\"Preta\":96,\"Indígena\":1}', '{\"SOLTEIRO\":3292,\"CASADO\":803,\"DIVORCIADO\":93,\"VIÚVO\":47,\"SEPARADO JUDICIALMENTE\":21}', '{\"Obrigatório\":3669,\"Facultativo\":587}', 9999),
(31771, 'MALHADOR', 10222, 9856, 72, 5, '{\"FEMININO\":5300,\"MASCULINO\":4922}', '{\"50 a 54 anos\":880,\"60 a 64 anos\":652,\"80 a 84 anos\":195,\"70 a 74 anos\":350,\"25 a 29 anos\":1036,\"35 a 39 anos\":993,\"40 a 44 anos\":1016,\"21 a 24 anos\":730,\"30 a 34 anos\":1055,\"45 a 49 anos\":957,\"55 a 59 anos\":797,\"75 a 79 anos\":263,\"18 anos\":172,\"17 anos\":109,\"85 a 89 anos\":77,\"16 anos\":28,\"65 a 69 anos\":518,\"19 anos\":172,\"20 anos\":179,\"90 a 94 anos\":31,\"15 anos\":3,\"95 a 99 anos\":7,\"100 anos ou mais\":2}', '{\"ENSINO MÉDIO INCOMPLETO\":1969,\"LÊ E ESCREVE\":1427,\"ENSINO FUNDAMENTAL INCOMPLETO\":3648,\"ANALFABETO\":560,\"ENSINO MÉDIO COMPLETO\":1420,\"ENSINO FUNDAMENTAL COMPLETO\":466,\"SUPERIOR INCOMPLETO\":289,\"SUPERIOR COMPLETO\":443}', '{\"NÃO INFORMADO\":7960,\"Branca\":394,\"Preta\":189,\"Parda\":1675,\"Amarela\":3,\"Indígena\":1}', '{\"CASADO\":2294,\"SOLTEIRO\":7292,\"DIVORCIADO\":350,\"VIÚVO\":239,\"SEPARADO JUDICIALMENTE\":47}', '{\"Obrigatório\":8795,\"Facultativo\":1427}', 9999),
(31798, 'MARUIM', 13128, 12362, 115, 2, '{\"FEMININO\":6889,\"MASCULINO\":6239}', '{\"18 anos\":220,\"21 a 24 anos\":1021,\"40 a 44 anos\":1363,\"70 a 74 anos\":416,\"30 a 34 anos\":1342,\"55 a 59 anos\":916,\"60 a 64 anos\":813,\"45 a 49 anos\":1342,\"65 a 69 anos\":548,\"19 anos\":263,\"25 a 29 anos\":1400,\"80 a 84 anos\":204,\"35 a 39 anos\":1300,\"20 anos\":297,\"17 anos\":133,\"75 a 79 anos\":280,\"16 anos\":47,\"50 a 54 anos\":1076,\"100 anos ou mais\":8,\"85 a 89 anos\":85,\"95 a 99 anos\":9,\"90 a 94 anos\":38,\"15 anos\":6,\"Inválida\":1}', '{\"ENSINO MÉDIO INCOMPLETO\":2767,\"ENSINO MÉDIO COMPLETO\":3050,\"LÊ E ESCREVE\":937,\"ENSINO FUNDAMENTAL COMPLETO\":532,\"ENSINO FUNDAMENTAL INCOMPLETO\":4374,\"ANALFABETO\":587,\"SUPERIOR INCOMPLETO\":420,\"SUPERIOR COMPLETO\":461}', '{\"Parda\":1655,\"NÃO INFORMADO\":10613,\"Preta\":509,\"Branca\":346,\"Amarela\":4,\"Indígena\":1}', '{\"SOLTEIRO\":9464,\"CASADO\":2877,\"VIÚVO\":252,\"SEPARADO JUDICIALMENTE\":69,\"DIVORCIADO\":466}', '{\"Obrigatório\":11518,\"Facultativo\":1610}', 9999),
(31810, 'MOITA BONITA', 9691, 9343, 90, 1, '{\"MASCULINO\":4585,\"FEMININO\":5106}', '{\"45 a 49 anos\":955,\"90 a 94 anos\":48,\"55 a 59 anos\":704,\"30 a 34 anos\":967,\"65 a 69 anos\":472,\"21 a 24 anos\":622,\"40 a 44 anos\":970,\"35 a 39 anos\":969,\"85 a 89 anos\":99,\"50 a 54 anos\":845,\"75 a 79 anos\":323,\"70 a 74 anos\":410,\"80 a 84 anos\":244,\"60 a 64 anos\":560,\"95 a 99 anos\":19,\"20 anos\":147,\"25 a 29 anos\":984,\"19 anos\":147,\"17 anos\":62,\"18 anos\":118,\"16 anos\":17,\"100 anos ou mais\":6,\"15 anos\":3}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":3621,\"LÊ E ESCREVE\":1446,\"ENSINO MÉDIO INCOMPLETO\":1559,\"ENSINO FUNDAMENTAL COMPLETO\":455,\"ENSINO MÉDIO COMPLETO\":1330,\"ANALFABETO\":630,\"SUPERIOR INCOMPLETO\":307,\"SUPERIOR COMPLETO\":343}', '{\"NÃO INFORMADO\":8249,\"Parda\":1041,\"Branca\":328,\"Preta\":68,\"Amarela\":5}', '{\"SOLTEIRO\":6421,\"DIVORCIADO\":257,\"CASADO\":2728,\"VIÚVO\":202,\"SEPARADO JUDICIALMENTE\":83}', '{\"Obrigatório\":8144,\"Facultativo\":1547}', 9999),
(31836, 'MONTE ALEGRE DE SERGIPE', 12656, 12073, 80, 1, '{\"FEMININO\":6386,\"MASCULINO\":6270}', '{\"40 a 44 anos\":1188,\"70 a 74 anos\":364,\"20 anos\":304,\"55 a 59 anos\":871,\"45 a 49 anos\":1085,\"30 a 34 anos\":1422,\"25 a 29 anos\":1555,\"18 anos\":237,\"60 a 64 anos\":653,\"21 a 24 anos\":1124,\"65 a 69 anos\":443,\"19 anos\":279,\"75 a 79 anos\":275,\"50 a 54 anos\":959,\"35 a 39 anos\":1339,\"17 anos\":175,\"80 a 84 anos\":165,\"15 anos\":21,\"85 a 89 anos\":79,\"90 a 94 anos\":35,\"16 anos\":64,\"100 anos ou mais\":10,\"95 a 99 anos\":9}', '{\"ENSINO MÉDIO INCOMPLETO\":2499,\"LÊ E ESCREVE\":1981,\"SUPERIOR COMPLETO\":338,\"ENSINO MÉDIO COMPLETO\":1416,\"ENSINO FUNDAMENTAL INCOMPLETO\":4459,\"ENSINO FUNDAMENTAL COMPLETO\":580,\"ANALFABETO\":1100,\"SUPERIOR INCOMPLETO\":283}', '{\"NÃO INFORMADO\":10182,\"Parda\":1576,\"Preta\":145,\"Branca\":732,\"Amarela\":21}', '{\"CASADO\":2611,\"SOLTEIRO\":9658,\"VIÚVO\":142,\"DIVORCIADO\":217,\"SEPARADO JUDICIALMENTE\":28}', '{\"Obrigatório\":10716,\"Facultativo\":1940}', 9999),
(31852, 'MURIBECA', 7438, 7161, 33, 2, '{\"FEMININO\":3799,\"MASCULINO\":3639}', '{\"60 a 64 anos\":412,\"20 anos\":151,\"50 a 54 anos\":591,\"40 a 44 anos\":747,\"55 a 59 anos\":523,\"25 a 29 anos\":790,\"21 a 24 anos\":555,\"35 a 39 anos\":815,\"30 a 34 anos\":835,\"65 a 69 anos\":341,\"17 anos\":73,\"75 a 79 anos\":192,\"90 a 94 anos\":41,\"45 a 49 anos\":670,\"70 a 74 anos\":230,\"16 anos\":33,\"80 a 84 anos\":113,\"19 anos\":143,\"85 a 89 anos\":55,\"18 anos\":115,\"95 a 99 anos\":7,\"15 anos\":4,\"100 anos ou mais\":1,\"Inválida\":1}', '{\"SUPERIOR COMPLETO\":342,\"ENSINO MÉDIO INCOMPLETO\":1529,\"ENSINO FUNDAMENTAL INCOMPLETO\":2294,\"SUPERIOR INCOMPLETO\":244,\"ENSINO FUNDAMENTAL COMPLETO\":359,\"ENSINO MÉDIO COMPLETO\":1494,\"LÊ E ESCREVE\":794,\"ANALFABETO\":382}', '{\"Parda\":947,\"NÃO INFORMADO\":6172,\"Preta\":130,\"Branca\":163,\"Amarela\":24,\"Indígena\":2}', '{\"DIVORCIADO\":180,\"SOLTEIRO\":5604,\"VIÚVO\":124,\"CASADO\":1486,\"SEPARADO JUDICIALMENTE\":44}', '{\"Obrigatório\":6457,\"Facultativo\":981}', 9999),
(31879, 'NEÓPOLIS', 14165, 13512, 105, 2, '{\"MASCULINO\":6799,\"FEMININO\":7366}', '{\"40 a 44 anos\":1447,\"55 a 59 anos\":1056,\"45 a 49 anos\":1327,\"30 a 34 anos\":1441,\"60 a 64 anos\":906,\"35 a 39 anos\":1408,\"75 a 79 anos\":390,\"65 a 69 anos\":694,\"80 a 84 anos\":230,\"50 a 54 anos\":1121,\"70 a 74 anos\":536,\"25 a 29 anos\":1476,\"21 a 24 anos\":1040,\"19 anos\":263,\"16 anos\":52,\"20 anos\":287,\"17 anos\":120,\"85 a 89 anos\":121,\"18 anos\":194,\"95 a 99 anos\":8,\"90 a 94 anos\":40,\"15 anos\":7,\"100 anos ou mais\":1}', '{\"ENSINO MÉDIO COMPLETO\":3094,\"ENSINO FUNDAMENTAL INCOMPLETO\":4392,\"ENSINO FUNDAMENTAL COMPLETO\":593,\"LÊ E ESCREVE\":1363,\"ANALFABETO\":708,\"ENSINO MÉDIO INCOMPLETO\":2853,\"SUPERIOR INCOMPLETO\":410,\"SUPERIOR COMPLETO\":752}', '{\"NÃO INFORMADO\":10694,\"Parda\":2495,\"Branca\":522,\"Preta\":427,\"Amarela\":26,\"Indígena\":1}', '{\"CASADO\":4187,\"DIVORCIADO\":541,\"SOLTEIRO\":9045,\"SEPARADO JUDICIALMENTE\":86,\"VIÚVO\":306}', '{\"Obrigatório\":12203,\"Facultativo\":1962}', 9999),
(31895, 'NOSSA SENHORA DA GLÓRIA', 29808, 28289, 135, 6, '{\"FEMININO\":15772,\"MASCULINO\":14036}', '{\"40 a 44 anos\":3015,\"50 a 54 anos\":2356,\"45 a 49 anos\":2716,\"55 a 59 anos\":2105,\"15 anos\":13,\"35 a 39 anos\":3221,\"80 a 84 anos\":458,\"25 a 29 anos\":3288,\"65 a 69 anos\":1271,\"19 anos\":555,\"60 a 64 anos\":1719,\"30 a 34 anos\":3197,\"18 anos\":471,\"70 a 74 anos\":957,\"75 a 79 anos\":745,\"21 a 24 anos\":2457,\"85 a 89 anos\":221,\"95 a 99 anos\":23,\"20 anos\":603,\"17 anos\":238,\"16 anos\":99,\"90 a 94 anos\":71,\"100 anos ou mais\":9}', '{\"LÊ E ESCREVE\":3721,\"ENSINO FUNDAMENTAL INCOMPLETO\":9309,\"SUPERIOR INCOMPLETO\":1060,\"ENSINO MÉDIO INCOMPLETO\":5829,\"SUPERIOR COMPLETO\":1359,\"ANALFABETO\":2219,\"ENSINO FUNDAMENTAL COMPLETO\":1285,\"ENSINO MÉDIO COMPLETO\":5026}', '{\"NÃO INFORMADO\":23318,\"Parda\":4555,\"Branca\":1512,\"Preta\":383,\"Amarela\":30,\"Indígena\":10}', '{\"SOLTEIRO\":21188,\"CASADO\":7143,\"DIVORCIADO\":981,\"VIÚVO\":373,\"SEPARADO JUDICIALMENTE\":123}', '{\"Obrigatório\":25559,\"Facultativo\":4249}', 9999),
(31917, 'NOSSA SENHORA DAS DORES', 21083, 20226, 152, 5, '{\"FEMININO\":11192,\"MASCULINO\":9891}', '{\"35 a 39 anos\":2109,\"60 a 64 anos\":1380,\"75 a 79 anos\":601,\"25 a 29 anos\":2203,\"21 a 24 anos\":1557,\"40 a 44 anos\":2148,\"50 a 54 anos\":1673,\"20 anos\":389,\"45 a 49 anos\":1928,\"65 a 69 anos\":1038,\"80 a 84 anos\":374,\"30 a 34 anos\":2188,\"19 anos\":359,\"55 a 59 anos\":1456,\"16 anos\":64,\"70 a 74 anos\":839,\"85 a 89 anos\":183,\"18 anos\":345,\"90 a 94 anos\":58,\"17 anos\":170,\"15 anos\":5,\"95 a 99 anos\":16}', '{\"SUPERIOR COMPLETO\":895,\"ENSINO FUNDAMENTAL INCOMPLETO\":7787,\"ANALFABETO\":1425,\"ENSINO MÉDIO COMPLETO\":3541,\"SUPERIOR INCOMPLETO\":681,\"ENSINO FUNDAMENTAL COMPLETO\":622,\"LÊ E ESCREVE\":2015,\"ENSINO MÉDIO INCOMPLETO\":4117}', '{\"Parda\":2619,\"NÃO INFORMADO\":17509,\"Preta\":239,\"Branca\":692,\"Amarela\":22,\"Indígena\":2}', '{\"CASADO\":4744,\"VIÚVO\":454,\"SOLTEIRO\":14913,\"SEPARADO JUDICIALMENTE\":241,\"DIVORCIADO\":731}', '{\"Obrigatório\":17842,\"Facultativo\":3241}', 9999),
(31933, 'NOSSA SENHORA DE LOURDES', 6121, 5877, 24, 0, '{\"MASCULINO\":2990,\"FEMININO\":3131}', '{\"35 a 39 anos\":634,\"60 a 64 anos\":379,\"55 a 59 anos\":445,\"40 a 44 anos\":611,\"25 a 29 anos\":686,\"30 a 34 anos\":647,\"50 a 54 anos\":480,\"65 a 69 anos\":278,\"75 a 79 anos\":170,\"21 a 24 anos\":441,\"19 anos\":131,\"45 a 49 anos\":543,\"20 anos\":114,\"80 a 84 anos\":108,\"70 a 74 anos\":208,\"17 anos\":62,\"18 anos\":95,\"16 anos\":17,\"85 a 89 anos\":50,\"90 a 94 anos\":18,\"95 a 99 anos\":4}', '{\"ENSINO MÉDIO COMPLETO\":1293,\"ENSINO FUNDAMENTAL INCOMPLETO\":1962,\"ANALFABETO\":359,\"SUPERIOR INCOMPLETO\":197,\"ENSINO MÉDIO INCOMPLETO\":1230,\"SUPERIOR COMPLETO\":294,\"LÊ E ESCREVE\":510,\"ENSINO FUNDAMENTAL COMPLETO\":276}', '{\"Preta\":123,\"NÃO INFORMADO\":4666,\"Parda\":890,\"Branca\":433,\"Amarela\":7,\"Indígena\":2}', '{\"SOLTEIRO\":4144,\"DIVORCIADO\":162,\"CASADO\":1667,\"VIÚVO\":99,\"SEPARADO JUDICIALMENTE\":49}', '{\"Obrigatório\":5257,\"Facultativo\":864}', 9999);

INSERT INTO perfil_eleitor_municipio (cd_municipio, nm_municipio, qt_total, qt_biometria, qt_deficiencia, qt_nome_social, genero, faixa_etaria, grau_instrucao, cor_raca, estado_civil, obrigatoriedade, ano_eleicao) VALUES
(31950, 'NOSSA SENHORA DO SOCORRO', 121631, 115490, 1284, 32, '{\"MASCULINO\":55604,\"FEMININO\":66027}', '{\"40 a 44 anos\":12950,\"45 a 49 anos\":11623,\"60 a 64 anos\":7429,\"21 a 24 anos\":10422,\"18 anos\":1820,\"50 a 54 anos\":9957,\"20 anos\":2598,\"25 a 29 anos\":14144,\"65 a 69 anos\":5049,\"19 anos\":2392,\"55 a 59 anos\":9359,\"80 a 84 anos\":980,\"70 a 74 anos\":3119,\"35 a 39 anos\":12812,\"30 a 34 anos\":13506,\"17 anos\":789,\"75 a 79 anos\":1873,\"16 anos\":260,\"85 a 89 anos\":337,\"95 a 99 anos\":27,\"90 a 94 anos\":104,\"15 anos\":72,\"100 anos ou mais\":8,\"Inválida\":1}', '{\"ENSINO MÉDIO COMPLETO\":35125,\"ENSINO MÉDIO INCOMPLETO\":28250,\"ENSINO FUNDAMENTAL INCOMPLETO\":35306,\"SUPERIOR INCOMPLETO\":5017,\"ENSINO FUNDAMENTAL COMPLETO\":5784,\"SUPERIOR COMPLETO\":3580,\"LÊ E ESCREVE\":6455,\"ANALFABETO\":2114}', '{\"NÃO INFORMADO\":96811,\"Parda\":15336,\"Branca\":4135,\"Preta\":4922,\"Amarela\":389,\"Indígena\":38}', '{\"CASADO\":25705,\"SOLTEIRO\":89242,\"VIÚVO\":1707,\"DIVORCIADO\":4118,\"SEPARADO JUDICIALMENTE\":859}', '{\"Obrigatório\":112673,\"Facultativo\":8958}', 9999),
(31976, 'PACATUBA', 11703, 11311, 115, 0, '{\"MASCULINO\":5750,\"FEMININO\":5953}', '{\"18 anos\":206,\"30 a 34 anos\":1197,\"60 a 64 anos\":661,\"40 a 44 anos\":1305,\"50 a 54 anos\":854,\"21 a 24 anos\":936,\"35 a 39 anos\":1288,\"75 a 79 anos\":266,\"45 a 49 anos\":1106,\"55 a 59 anos\":760,\"25 a 29 anos\":1329,\"20 anos\":251,\"65 a 69 anos\":487,\"70 a 74 anos\":380,\"17 anos\":95,\"80 a 84 anos\":177,\"19 anos\":231,\"16 anos\":22,\"90 a 94 anos\":40,\"100 anos ou mais\":4,\"85 a 89 anos\":99,\"95 a 99 anos\":9}', '{\"ENSINO MÉDIO COMPLETO\":2305,\"ANALFABETO\":677,\"ENSINO FUNDAMENTAL COMPLETO\":445,\"ENSINO FUNDAMENTAL INCOMPLETO\":3887,\"ENSINO MÉDIO INCOMPLETO\":2034,\"LÊ E ESCREVE\":1492,\"SUPERIOR COMPLETO\":573,\"SUPERIOR INCOMPLETO\":290}', '{\"Parda\":1648,\"NÃO INFORMADO\":9364,\"Branca\":299,\"Preta\":359,\"Indígena\":5,\"Amarela\":28}', '{\"SOLTEIRO\":8101,\"CASADO\":3101,\"DIVORCIADO\":285,\"VIÚVO\":187,\"SEPARADO JUDICIALMENTE\":29}', '{\"Obrigatório\":10168,\"Facultativo\":1535}', 9999),
(31992, 'PEDRA MOLE', 3363, 3250, 12, 0, '{\"FEMININO\":1668,\"MASCULINO\":1695}', '{\"80 a 84 anos\":50,\"40 a 44 anos\":325,\"55 a 59 anos\":233,\"45 a 49 anos\":300,\"30 a 34 anos\":373,\"25 a 29 anos\":375,\"70 a 74 anos\":130,\"35 a 39 anos\":385,\"21 a 24 anos\":216,\"90 a 94 anos\":15,\"50 a 54 anos\":261,\"75 a 79 anos\":99,\"60 a 64 anos\":212,\"95 a 99 anos\":3,\"17 anos\":33,\"19 anos\":51,\"18 anos\":42,\"65 a 69 anos\":144,\"85 a 89 anos\":32,\"16 anos\":12,\"20 anos\":67,\"100 anos ou mais\":5}', '{\"ANALFABETO\":291,\"ENSINO MÉDIO COMPLETO\":617,\"LÊ E ESCREVE\":525,\"SUPERIOR COMPLETO\":164,\"ENSINO FUNDAMENTAL INCOMPLETO\":960,\"ENSINO MÉDIO INCOMPLETO\":548,\"ENSINO FUNDAMENTAL COMPLETO\":170,\"SUPERIOR INCOMPLETO\":88}', '{\"NÃO INFORMADO\":2748,\"Parda\":457,\"Branca\":89,\"Preta\":65,\"Amarela\":4}', '{\"VIÚVO\":55,\"SOLTEIRO\":2397,\"CASADO\":820,\"DIVORCIADO\":77,\"SEPARADO JUDICIALMENTE\":14}', '{\"Facultativo\":567,\"Obrigatório\":2796}', 9999),
(32018, 'PEDRINHAS', 7826, 7453, 50, 0, '{\"MASCULINO\":3698,\"FEMININO\":4128}', '{\"50 a 54 anos\":624,\"17 anos\":74,\"40 a 44 anos\":776,\"25 a 29 anos\":904,\"21 a 24 anos\":592,\"20 anos\":196,\"70 a 74 anos\":259,\"75 a 79 anos\":186,\"35 a 39 anos\":772,\"65 a 69 anos\":353,\"60 a 64 anos\":481,\"30 a 34 anos\":822,\"19 anos\":163,\"45 a 49 anos\":684,\"80 a 84 anos\":106,\"55 a 59 anos\":564,\"85 a 89 anos\":80,\"16 anos\":25,\"18 anos\":116,\"90 a 94 anos\":28,\"100 anos ou mais\":4,\"95 a 99 anos\":9,\"15 anos\":8}', '{\"LÊ E ESCREVE\":1219,\"ENSINO MÉDIO COMPLETO\":1292,\"ANALFABETO\":510,\"ENSINO MÉDIO INCOMPLETO\":1640,\"ENSINO FUNDAMENTAL COMPLETO\":297,\"SUPERIOR INCOMPLETO\":256,\"SUPERIOR COMPLETO\":319,\"ENSINO FUNDAMENTAL INCOMPLETO\":2293}', '{\"NÃO INFORMADO\":6352,\"Parda\":1027,\"Preta\":205,\"Branca\":225,\"Amarela\":16,\"Indígena\":1}', '{\"SOLTEIRO\":6442,\"CASADO\":1184,\"DIVORCIADO\":119,\"VIÚVO\":60,\"SEPARADO JUDICIALMENTE\":21}', '{\"Obrigatório\":6699,\"Facultativo\":1127}', 9999),
(32034, 'PINHÃO', 5561, 5377, 39, 0, '{\"FEMININO\":2848,\"MASCULINO\":2713}', '{\"55 a 59 anos\":419,\"19 anos\":90,\"35 a 39 anos\":566,\"21 a 24 anos\":401,\"85 a 89 anos\":54,\"17 anos\":61,\"50 a 54 anos\":431,\"65 a 69 anos\":277,\"25 a 29 anos\":575,\"60 a 64 anos\":339,\"18 anos\":94,\"40 a 44 anos\":525,\"16 anos\":38,\"70 a 74 anos\":243,\"45 a 49 anos\":506,\"75 a 79 anos\":182,\"30 a 34 anos\":500,\"20 anos\":98,\"80 a 84 anos\":112,\"90 a 94 anos\":27,\"15 anos\":13,\"95 a 99 anos\":7,\"100 anos ou mais\":3}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":1723,\"ENSINO MÉDIO INCOMPLETO\":1019,\"ENSINO FUNDAMENTAL COMPLETO\":303,\"LÊ E ESCREVE\":830,\"SUPERIOR COMPLETO\":177,\"ENSINO MÉDIO COMPLETO\":903,\"SUPERIOR INCOMPLETO\":97,\"ANALFABETO\":509}', '{\"NÃO INFORMADO\":4591,\"Parda\":747,\"Branca\":130,\"Preta\":90,\"Amarela\":3}', '{\"CASADO\":1411,\"DIVORCIADO\":164,\"SOLTEIRO\":3821,\"VIÚVO\":128,\"SEPARADO JUDICIALMENTE\":37}', '{\"Obrigatório\":4502,\"Facultativo\":1059}', 9999),
(32050, 'PIRAMBU', 8512, 8160, 69, 0, '{\"MASCULINO\":4260,\"FEMININO\":4252}', '{\"55 a 59 anos\":608,\"20 anos\":195,\"65 a 69 anos\":407,\"75 a 79 anos\":172,\"45 a 49 anos\":770,\"19 anos\":156,\"35 a 39 anos\":891,\"60 a 64 anos\":531,\"70 a 74 anos\":284,\"21 a 24 anos\":706,\"50 a 54 anos\":666,\"30 a 34 anos\":847,\"40 a 44 anos\":892,\"25 a 29 anos\":928,\"80 a 84 anos\":122,\"85 a 89 anos\":79,\"18 anos\":140,\"90 a 94 anos\":39,\"17 anos\":47,\"95 a 99 anos\":15,\"16 anos\":8,\"15 anos\":2,\"100 anos ou mais\":6,\"Inválida\":1}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":2967,\"ENSINO MÉDIO INCOMPLETO\":1704,\"ANALFABETO\":376,\"ENSINO MÉDIO COMPLETO\":1653,\"SUPERIOR COMPLETO\":489,\"ENSINO FUNDAMENTAL COMPLETO\":305,\"LÊ E ESCREVE\":731,\"SUPERIOR INCOMPLETO\":287}', '{\"NÃO INFORMADO\":6741,\"Preta\":325,\"Branca\":272,\"Parda\":1134,\"Amarela\":37,\"Indígena\":3}', '{\"CASADO\":1750,\"SOLTEIRO\":6296,\"SEPARADO JUDICIALMENTE\":68,\"DIVORCIADO\":248,\"VIÚVO\":150}', '{\"Obrigatório\":7512,\"Facultativo\":1000}', 9999),
(32077, 'POÇO REDONDO', 22114, 20873, 128, 4, '{\"FEMININO\":11297,\"MASCULINO\":10817}', '{\"25 a 29 anos\":2769,\"17 anos\":219,\"40 a 44 anos\":2178,\"35 a 39 anos\":2276,\"50 a 54 anos\":1610,\"30 a 34 anos\":2590,\"80 a 84 anos\":296,\"60 a 64 anos\":1165,\"21 a 24 anos\":2102,\"55 a 59 anos\":1407,\"70 a 74 anos\":639,\"65 a 69 anos\":812,\"45 a 49 anos\":1862,\"19 anos\":467,\"20 anos\":545,\"75 a 79 anos\":512,\"18 anos\":364,\"100 anos ou mais\":16,\"16 anos\":66,\"90 a 94 anos\":58,\"85 a 89 anos\":137,\"95 a 99 anos\":11,\"15 anos\":13}', '{\"ENSINO MÉDIO COMPLETO\":2635,\"ENSINO MÉDIO INCOMPLETO\":4334,\"ENSINO FUNDAMENTAL INCOMPLETO\":7612,\"ANALFABETO\":1839,\"LÊ E ESCREVE\":4228,\"ENSINO FUNDAMENTAL COMPLETO\":673,\"SUPERIOR COMPLETO\":519,\"SUPERIOR INCOMPLETO\":274}', '{\"Parda\":2744,\"NÃO INFORMADO\":18525,\"Preta\":297,\"Branca\":471,\"Amarela\":73,\"Indígena\":4}', '{\"SOLTEIRO\":16350,\"CASADO\":4843,\"DIVORCIADO\":435,\"VIÚVO\":372,\"SEPARADO JUDICIALMENTE\":114}', '{\"Obrigatório\":18845,\"Facultativo\":3269}', 9999),
(32093, 'POÇO VERDE', 19266, 18596, 127, 4, '{\"MASCULINO\":9276,\"FEMININO\":9990}', '{\"21 a 24 anos\":1378,\"19 anos\":317,\"60 a 64 anos\":1215,\"30 a 34 anos\":1903,\"70 a 74 anos\":773,\"65 a 69 anos\":945,\"35 a 39 anos\":1861,\"45 a 49 anos\":1689,\"75 a 79 anos\":671,\"40 a 44 anos\":1677,\"50 a 54 anos\":1593,\"20 anos\":366,\"25 a 29 anos\":2031,\"55 a 59 anos\":1494,\"80 a 84 anos\":418,\"17 anos\":173,\"18 anos\":243,\"16 anos\":66,\"95 a 99 anos\":52,\"85 a 89 anos\":232,\"90 a 94 anos\":126,\"15 anos\":24,\"100 anos ou mais\":19}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":5816,\"ENSINO MÉDIO INCOMPLETO\":3634,\"ENSINO MÉDIO COMPLETO\":2767,\"ENSINO FUNDAMENTAL COMPLETO\":806,\"LÊ E ESCREVE\":3621,\"ANALFABETO\":1488,\"SUPERIOR COMPLETO\":554,\"SUPERIOR INCOMPLETO\":580}', '{\"NÃO INFORMADO\":16609,\"Parda\":1986,\"Branca\":524,\"Preta\":136,\"Amarela\":11}', '{\"SOLTEIRO\":14630,\"DIVORCIADO\":502,\"CASADO\":3849,\"VIÚVO\":191,\"SEPARADO JUDICIALMENTE\":94}', '{\"Obrigatório\":15814,\"Facultativo\":3452}', 9999),
(32115, 'PORTO DA FOLHA', 24651, 23709, 131, 1, '{\"MASCULINO\":12172,\"FEMININO\":12479}', '{\"25 a 29 anos\":2782,\"65 a 69 anos\":1031,\"40 a 44 anos\":2464,\"35 a 39 anos\":2587,\"55 a 59 anos\":1762,\"15 anos\":30,\"70 a 74 anos\":829,\"60 a 64 anos\":1487,\"45 a 49 anos\":2155,\"30 a 34 anos\":2645,\"50 a 54 anos\":1893,\"21 a 24 anos\":1847,\"19 anos\":494,\"75 a 79 anos\":585,\"16 anos\":139,\"18 anos\":418,\"20 anos\":518,\"17 anos\":258,\"85 a 89 anos\":192,\"80 a 84 anos\":415,\"95 a 99 anos\":28,\"90 a 94 anos\":87,\"100 anos ou mais\":5}', '{\"ENSINO MÉDIO INCOMPLETO\":4507,\"ENSINO FUNDAMENTAL INCOMPLETO\":7096,\"SUPERIOR COMPLETO\":809,\"ENSINO MÉDIO COMPLETO\":3852,\"LÊ E ESCREVE\":4880,\"ANALFABETO\":1423,\"ENSINO FUNDAMENTAL COMPLETO\":1475,\"SUPERIOR INCOMPLETO\":609}', '{\"Branca\":2108,\"NÃO INFORMADO\":18513,\"Parda\":3426,\"Preta\":470,\"Amarela\":72,\"Indígena\":62}', '{\"SOLTEIRO\":15632,\"CASADO\":7929,\"SEPARADO JUDICIALMENTE\":93,\"DIVORCIADO\":659,\"VIÚVO\":338}', '{\"Obrigatório\":21143,\"Facultativo\":3508}', 9999),
(32131, 'PROPRIÁ', 20028, 19118, 172, 5, '{\"FEMININO\":10741,\"MASCULINO\":9287}', '{\"30 a 34 anos\":1976,\"45 a 49 anos\":1755,\"85 a 89 anos\":202,\"35 a 39 anos\":2102,\"55 a 59 anos\":1611,\"60 a 64 anos\":1461,\"20 anos\":372,\"21 a 24 anos\":1436,\"70 a 74 anos\":731,\"65 a 69 anos\":1113,\"90 a 94 anos\":67,\"25 a 29 anos\":1952,\"50 a 54 anos\":1574,\"17 anos\":151,\"40 a 44 anos\":1997,\"19 anos\":337,\"95 a 99 anos\":17,\"80 a 84 anos\":299,\"18 anos\":308,\"75 a 79 anos\":497,\"100 anos ou mais\":6,\"16 anos\":59,\"15 anos\":5}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":4701,\"ENSINO FUNDAMENTAL COMPLETO\":1016,\"LÊ E ESCREVE\":1025,\"ENSINO MÉDIO COMPLETO\":5162,\"ENSINO MÉDIO INCOMPLETO\":4204,\"SUPERIOR COMPLETO\":1496,\"ANALFABETO\":1429,\"SUPERIOR INCOMPLETO\":995}', '{\"Parda\":2418,\"NÃO INFORMADO\":16258,\"Branca\":780,\"Amarela\":64,\"Preta\":495,\"Indígena\":13}', '{\"SOLTEIRO\":12789,\"CASADO\":5819,\"VIÚVO\":494,\"DIVORCIADO\":821,\"SEPARADO JUDICIALMENTE\":105}', '{\"Obrigatório\":17068,\"Facultativo\":2960}', 9999),
(32158, 'RIACHÃO DO DANTAS', 17392, 16591, 80, 2, '{\"MASCULINO\":8526,\"FEMININO\":8866}', '{\"40 a 44 anos\":1768,\"30 a 34 anos\":1846,\"90 a 94 anos\":80,\"55 a 59 anos\":1301,\"45 a 49 anos\":1545,\"65 a 69 anos\":757,\"60 a 64 anos\":958,\"25 a 29 anos\":1943,\"35 a 39 anos\":1682,\"70 a 74 anos\":571,\"19 anos\":338,\"50 a 54 anos\":1397,\"21 a 24 anos\":1391,\"75 a 79 anos\":468,\"20 anos\":336,\"80 a 84 anos\":339,\"15 anos\":4,\"18 anos\":289,\"16 anos\":31,\"85 a 89 anos\":181,\"17 anos\":136,\"95 a 99 anos\":22,\"100 anos ou mais\":9}', '{\"SUPERIOR COMPLETO\":428,\"ENSINO MÉDIO INCOMPLETO\":3191,\"LÊ E ESCREVE\":3534,\"SUPERIOR INCOMPLETO\":270,\"ENSINO FUNDAMENTAL INCOMPLETO\":5902,\"ENSINO MÉDIO COMPLETO\":1892,\"ANALFABETO\":1631,\"ENSINO FUNDAMENTAL COMPLETO\":544}', '{\"NÃO INFORMADO\":15266,\"Parda\":1544,\"Branca\":305,\"Preta\":262,\"Amarela\":12,\"Indígena\":3}', '{\"SOLTEIRO\":14069,\"VIÚVO\":104,\"CASADO\":3026,\"DIVORCIADO\":164,\"SEPARADO JUDICIALMENTE\":29}', '{\"Obrigatório\":14462,\"Facultativo\":2930}', 9999),
(32174, 'RIACHUELO', 8214, 7944, 47, 1, '{\"FEMININO\":4362,\"MASCULINO\":3852}', '{\"35 a 39 anos\":827,\"75 a 79 anos\":159,\"40 a 44 anos\":915,\"70 a 74 anos\":260,\"18 anos\":144,\"60 a 64 anos\":467,\"45 a 49 anos\":868,\"65 a 69 anos\":342,\"50 a 54 anos\":633,\"21 a 24 anos\":649,\"25 a 29 anos\":914,\"17 anos\":72,\"55 a 59 anos\":565,\"30 a 34 anos\":853,\"90 a 94 anos\":22,\"19 anos\":179,\"20 anos\":149,\"85 a 89 anos\":55,\"80 a 84 anos\":104,\"15 anos\":6,\"16 anos\":24,\"95 a 99 anos\":6,\"100 anos ou mais\":1}', '{\"ENSINO MÉDIO INCOMPLETO\":1775,\"ENSINO FUNDAMENTAL INCOMPLETO\":2471,\"ENSINO FUNDAMENTAL COMPLETO\":375,\"LÊ E ESCREVE\":712,\"SUPERIOR INCOMPLETO\":252,\"SUPERIOR COMPLETO\":394,\"ENSINO MÉDIO COMPLETO\":1936,\"ANALFABETO\":299}', '{\"Parda\":1056,\"NÃO INFORMADO\":6617,\"Preta\":293,\"Branca\":239,\"Amarela\":9}', '{\"CASADO\":1444,\"SOLTEIRO\":6298,\"VIÚVO\":175,\"DIVORCIADO\":240,\"SEPARADO JUDICIALMENTE\":57}', '{\"Obrigatório\":7341,\"Facultativo\":873}', 9999),
(32190, 'RIBEIRÓPOLIS', 14587, 14023, 149, 0, '{\"FEMININO\":7647,\"MASCULINO\":6940}', '{\"50 a 54 anos\":1206,\"21 a 24 anos\":1000,\"65 a 69 anos\":741,\"40 a 44 anos\":1466,\"45 a 49 anos\":1371,\"35 a 39 anos\":1441,\"55 a 59 anos\":1103,\"70 a 74 anos\":600,\"30 a 34 anos\":1425,\"25 a 29 anos\":1403,\"60 a 64 anos\":964,\"18 anos\":167,\"75 a 79 anos\":459,\"20 anos\":227,\"80 a 84 anos\":319,\"17 anos\":126,\"85 a 89 anos\":172,\"90 a 94 anos\":102,\"19 anos\":218,\"15 anos\":5,\"95 a 99 anos\":27,\"16 anos\":33,\"100 anos ou mais\":12}', '{\"SUPERIOR COMPLETO\":727,\"ENSINO MÉDIO COMPLETO\":2414,\"ENSINO FUNDAMENTAL INCOMPLETO\":4204,\"ANALFABETO\":1558,\"ENSINO MÉDIO INCOMPLETO\":2497,\"LÊ E ESCREVE\":1966,\"ENSINO FUNDAMENTAL COMPLETO\":673,\"SUPERIOR INCOMPLETO\":548}', '{\"NÃO INFORMADO\":12000,\"Parda\":1715,\"Branca\":754,\"Preta\":113,\"Amarela\":5}', '{\"CASADO\":4047,\"SOLTEIRO\":9571,\"VIÚVO\":363,\"DIVORCIADO\":502,\"SEPARADO JUDICIALMENTE\":104}', '{\"Obrigatório\":11841,\"Facultativo\":2746}', 9999),
(32212, 'ROSÁRIO DO CATETE', 8897, 8494, 81, 2, '{\"FEMININO\":4835,\"MASCULINO\":4062}', '{\"21 a 24 anos\":701,\"30 a 34 anos\":924,\"75 a 79 anos\":168,\"35 a 39 anos\":930,\"45 a 49 anos\":830,\"40 a 44 anos\":998,\"70 a 74 anos\":275,\"18 anos\":153,\"50 a 54 anos\":752,\"55 a 59 anos\":648,\"20 anos\":179,\"25 a 29 anos\":962,\"60 a 64 anos\":516,\"65 a 69 anos\":373,\"19 anos\":175,\"16 anos\":19,\"85 a 89 anos\":63,\"80 a 84 anos\":106,\"90 a 94 anos\":26,\"17 anos\":80,\"95 a 99 anos\":9,\"100 anos ou mais\":5,\"15 anos\":5}', '{\"SUPERIOR INCOMPLETO\":324,\"ENSINO FUNDAMENTAL INCOMPLETO\":2806,\"LÊ E ESCREVE\":727,\"SUPERIOR COMPLETO\":430,\"ENSINO MÉDIO INCOMPLETO\":1895,\"ENSINO FUNDAMENTAL COMPLETO\":397,\"ENSINO MÉDIO COMPLETO\":2020,\"ANALFABETO\":298}', '{\"Branca\":224,\"NÃO INFORMADO\":7332,\"Parda\":997,\"Preta\":328,\"Amarela\":13,\"Indígena\":3}', '{\"CASADO\":1723,\"SOLTEIRO\":6818,\"SEPARADO JUDICIALMENTE\":32,\"VIÚVO\":100,\"DIVORCIADO\":224}', '{\"Obrigatório\":7963,\"Facultativo\":934}', 9999),
(32239, 'SALGADO', 17686, 16915, 133, 2, '{\"MASCULINO\":8634,\"FEMININO\":9052}', '{\"45 a 49 anos\":1727,\"35 a 39 anos\":1731,\"40 a 44 anos\":1783,\"55 a 59 anos\":1376,\"65 a 69 anos\":821,\"25 a 29 anos\":1825,\"80 a 84 anos\":302,\"30 a 34 anos\":1799,\"18 anos\":275,\"60 a 64 anos\":1091,\"70 a 74 anos\":622,\"50 a 54 anos\":1498,\"75 a 79 anos\":414,\"21 a 24 anos\":1295,\"19 anos\":348,\"20 anos\":342,\"85 a 89 anos\":123,\"17 anos\":174,\"90 a 94 anos\":47,\"95 a 99 anos\":13,\"16 anos\":61,\"15 anos\":15,\"100 anos ou mais\":4}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":5761,\"ENSINO MÉDIO COMPLETO\":2910,\"SUPERIOR INCOMPLETO\":467,\"ENSINO FUNDAMENTAL COMPLETO\":839,\"LÊ E ESCREVE\":2426,\"ANALFABETO\":1226,\"ENSINO MÉDIO INCOMPLETO\":3515,\"SUPERIOR COMPLETO\":542}', '{\"NÃO INFORMADO\":14431,\"Preta\":414,\"Parda\":2283,\"Branca\":540,\"Amarela\":15,\"Indígena\":3}', '{\"CASADO\":4087,\"SOLTEIRO\":12807,\"VIÚVO\":262,\"DIVORCIADO\":466,\"SEPARADO JUDICIALMENTE\":64}', '{\"Obrigatório\":15104,\"Facultativo\":2582}', 9999),
(32255, 'SANTA LUZIA DO ITANHY', 12126, 11792, 53, 0, '{\"FEMININO\":6300,\"MASCULINO\":5826}', '{\"35 a 39 anos\":1229,\"70 a 74 anos\":375,\"25 a 29 anos\":1506,\"21 a 24 anos\":1037,\"40 a 44 anos\":1259,\"60 a 64 anos\":658,\"65 a 69 anos\":538,\"50 a 54 anos\":939,\"16 anos\":37,\"45 a 49 anos\":1066,\"30 a 34 anos\":1362,\"20 anos\":262,\"55 a 59 anos\":801,\"17 anos\":102,\"75 a 79 anos\":235,\"18 anos\":223,\"19 anos\":282,\"85 a 89 anos\":72,\"95 a 99 anos\":8,\"100 anos ou mais\":5,\"90 a 94 anos\":14,\"15 anos\":5,\"80 a 84 anos\":111}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":4428,\"ANALFABETO\":1426,\"ENSINO MÉDIO COMPLETO\":1608,\"ENSINO FUNDAMENTAL COMPLETO\":396,\"ENSINO MÉDIO INCOMPLETO\":2513,\"SUPERIOR COMPLETO\":404,\"LÊ E ESCREVE\":1114,\"SUPERIOR INCOMPLETO\":237}', '{\"NÃO INFORMADO\":9324,\"Parda\":2083,\"Branca\":204,\"Preta\":500,\"Amarela\":12,\"Indígena\":3}', '{\"CASADO\":1827,\"SOLTEIRO\":9845,\"VIÚVO\":163,\"DIVORCIADO\":252,\"SEPARADO JUDICIALMENTE\":39}', '{\"Obrigatório\":10103,\"Facultativo\":2023}', 9999),
(32298, 'SANTA ROSA DE LIMA', 4590, 4451, 25, 1, '{\"MASCULINO\":2210,\"FEMININO\":2380}', '{\"20 anos\":111,\"21 a 24 anos\":413,\"50 a 54 anos\":378,\"35 a 39 anos\":430,\"60 a 64 anos\":256,\"30 a 34 anos\":480,\"75 a 79 anos\":114,\"40 a 44 anos\":482,\"55 a 59 anos\":306,\"65 a 69 anos\":186,\"70 a 74 anos\":138,\"45 a 49 anos\":448,\"19 anos\":98,\"17 anos\":39,\"25 a 29 anos\":463,\"90 a 94 anos\":24,\"85 a 89 anos\":45,\"80 a 84 anos\":75,\"18 anos\":79,\"16 anos\":13,\"95 a 99 anos\":10,\"15 anos\":1,\"100 anos ou mais\":1}', '{\"ENSINO MÉDIO INCOMPLETO\":986,\"ENSINO FUNDAMENTAL INCOMPLETO\":1337,\"ENSINO MÉDIO COMPLETO\":940,\"LÊ E ESCREVE\":483,\"SUPERIOR INCOMPLETO\":211,\"ANALFABETO\":228,\"ENSINO FUNDAMENTAL COMPLETO\":205,\"SUPERIOR COMPLETO\":200}', '{\"Preta\":192,\"Parda\":839,\"NÃO INFORMADO\":3393,\"Branca\":163,\"Amarela\":3}', '{\"SOLTEIRO\":3508,\"CASADO\":825,\"VIÚVO\":79,\"DIVORCIADO\":143,\"SEPARADO JUDICIALMENTE\":35}', '{\"Obrigatório\":4010,\"Facultativo\":580}', 9999),
(32310, 'SANTO AMARO DAS BROTAS', 10333, 9880, 80, 1, '{\"FEMININO\":5308,\"MASCULINO\":5025}', '{\"45 a 49 anos\":952,\"30 a 34 anos\":990,\"21 a 24 anos\":743,\"18 anos\":166,\"35 a 39 anos\":1025,\"20 anos\":205,\"25 a 29 anos\":1029,\"65 a 69 anos\":499,\"60 a 64 anos\":691,\"70 a 74 anos\":370,\"40 a 44 anos\":1077,\"19 anos\":229,\"50 a 54 anos\":856,\"55 a 59 anos\":771,\"90 a 94 anos\":44,\"75 a 79 anos\":255,\"80 a 84 anos\":170,\"100 anos ou mais\":9,\"17 anos\":107,\"85 a 89 anos\":112,\"95 a 99 anos\":23,\"15 anos\":4,\"16 anos\":6}', '{\"ENSINO FUNDAMENTAL COMPLETO\":529,\"ENSINO MÉDIO INCOMPLETO\":2143,\"SUPERIOR COMPLETO\":423,\"SUPERIOR INCOMPLETO\":277,\"ANALFABETO\":426,\"LÊ E ESCREVE\":828,\"ENSINO FUNDAMENTAL INCOMPLETO\":3223,\"ENSINO MÉDIO COMPLETO\":2484}', '{\"Preta\":481,\"NÃO INFORMADO\":7959,\"Parda\":1503,\"Branca\":351,\"Amarela\":35,\"Indígena\":4}', '{\"SOLTEIRO\":7211,\"CASADO\":2518,\"VIÚVO\":230,\"DIVORCIADO\":327,\"SEPARADO JUDICIALMENTE\":47}', '{\"Obrigatório\":9009,\"Facultativo\":1324}', 9999),
(32336, 'SÃO CRISTÓVÃO', 60947, 57858, 569, 21, '{\"MASCULINO\":28505,\"FEMININO\":32442}', '{\"35 a 39 anos\":6525,\"21 a 24 anos\":4743,\"45 a 49 anos\":5937,\"55 a 59 anos\":4562,\"20 anos\":1168,\"50 a 54 anos\":5140,\"18 anos\":814,\"75 a 79 anos\":1201,\"60 a 64 anos\":3659,\"25 a 29 anos\":6560,\"30 a 34 anos\":6344,\"40 a 44 anos\":6512,\"70 a 74 anos\":2018,\"65 a 69 anos\":2854,\"17 anos\":407,\"80 a 84 anos\":715,\"19 anos\":1108,\"85 a 89 anos\":353,\"90 a 94 anos\":146,\"16 anos\":106,\"100 anos ou mais\":16,\"95 a 99 anos\":34,\"15 anos\":25}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":17956,\"ENSINO MÉDIO COMPLETO\":16873,\"ENSINO MÉDIO INCOMPLETO\":12401,\"ENSINO FUNDAMENTAL COMPLETO\":2602,\"LÊ E ESCREVE\":3429,\"SUPERIOR COMPLETO\":2889,\"SUPERIOR INCOMPLETO\":3336,\"ANALFABETO\":1461}', '{\"NÃO INFORMADO\":50758,\"Parda\":6213,\"Preta\":2093,\"Branca\":1838,\"Amarela\":38,\"Indígena\":7}', '{\"SOLTEIRO\":45557,\"CASADO\":12368,\"SEPARADO JUDICIALMENTE\":451,\"DIVORCIADO\":1857,\"VIÚVO\":714}', '{\"Obrigatório\":54984,\"Facultativo\":5963}', 9999),
(32352, 'SÃO DOMINGOS', 8423, 8102, 20, 0, '{\"FEMININO\":4399,\"MASCULINO\":4024}', '{\"21 a 24 anos\":617,\"80 a 84 anos\":190,\"60 a 64 anos\":436,\"40 a 44 anos\":846,\"50 a 54 anos\":719,\"85 a 89 anos\":80,\"75 a 79 anos\":242,\"55 a 59 anos\":573,\"45 a 49 anos\":860,\"25 a 29 anos\":891,\"70 a 74 anos\":364,\"65 a 69 anos\":367,\"30 a 34 anos\":825,\"19 anos\":145,\"35 a 39 anos\":812,\"18 anos\":156,\"16 anos\":14,\"20 anos\":142,\"90 a 94 anos\":46,\"17 anos\":62,\"95 a 99 anos\":19,\"100 anos ou mais\":13,\"15 anos\":4}', '{\"ENSINO MÉDIO INCOMPLETO\":1458,\"ANALFABETO\":621,\"LÊ E ESCREVE\":987,\"ENSINO MÉDIO COMPLETO\":1293,\"SUPERIOR COMPLETO\":290,\"ENSINO FUNDAMENTAL INCOMPLETO\":3262,\"SUPERIOR INCOMPLETO\":242,\"ENSINO FUNDAMENTAL COMPLETO\":270}', '{\"Parda\":933,\"NÃO INFORMADO\":7172,\"Branca\":192,\"Preta\":120,\"Amarela\":6}', '{\"SOLTEIRO\":5902,\"CASADO\":1975,\"SEPARADO JUDICIALMENTE\":106,\"VIÚVO\":230,\"DIVORCIADO\":210}', '{\"Obrigatório\":7016,\"Facultativo\":1407}', 9999),
(32379, 'SÃO FRANCISCO', 3751, 3624, 65, 1, '{\"FEMININO\":1904,\"MASCULINO\":1847}', '{\"50 a 54 anos\":290,\"30 a 34 anos\":373,\"65 a 69 anos\":157,\"21 a 24 anos\":281,\"70 a 74 anos\":133,\"55 a 59 anos\":288,\"45 a 49 anos\":350,\"75 a 79 anos\":72,\"35 a 39 anos\":410,\"18 anos\":63,\"40 a 44 anos\":379,\"19 anos\":81,\"85 a 89 anos\":26,\"25 a 29 anos\":422,\"20 anos\":64,\"60 a 64 anos\":233,\"17 anos\":42,\"80 a 84 anos\":52,\"16 anos\":15,\"90 a 94 anos\":7,\"95 a 99 anos\":9,\"15 anos\":4}', '{\"SUPERIOR COMPLETO\":214,\"ENSINO FUNDAMENTAL INCOMPLETO\":1166,\"ENSINO MÉDIO INCOMPLETO\":770,\"ENSINO MÉDIO COMPLETO\":649,\"LÊ E ESCREVE\":402,\"SUPERIOR INCOMPLETO\":110,\"ANALFABETO\":256,\"ENSINO FUNDAMENTAL COMPLETO\":184}', '{\"Parda\":506,\"NÃO INFORMADO\":2982,\"Preta\":101,\"Branca\":152,\"Amarela\":10}', '{\"DIVORCIADO\":61,\"SOLTEIRO\":2853,\"CASADO\":798,\"VIÚVO\":25,\"SEPARADO JUDICIALMENTE\":14}', '{\"Obrigatório\":3220,\"Facultativo\":531}', 9999),
(32395, 'SÃO MIGUEL DO ALEIXO', 3989, 3834, 20, 0, '{\"FEMININO\":2052,\"MASCULINO\":1937}', '{\"50 a 54 anos\":341,\"55 a 59 anos\":272,\"35 a 39 anos\":420,\"45 a 49 anos\":368,\"25 a 29 anos\":430,\"75 a 79 anos\":111,\"65 a 69 anos\":181,\"60 a 64 anos\":234,\"21 a 24 anos\":316,\"70 a 74 anos\":149,\"20 anos\":64,\"18 anos\":46,\"40 a 44 anos\":373,\"30 a 34 anos\":437,\"80 a 84 anos\":79,\"17 anos\":36,\"90 a 94 anos\":18,\"85 a 89 anos\":37,\"19 anos\":49,\"15 anos\":3,\"16 anos\":14,\"95 a 99 anos\":7,\"100 anos ou mais\":4}', '{\"ENSINO MÉDIO COMPLETO\":529,\"LÊ E ESCREVE\":466,\"ANALFABETO\":466,\"ENSINO FUNDAMENTAL COMPLETO\":178,\"ENSINO FUNDAMENTAL INCOMPLETO\":1369,\"ENSINO MÉDIO INCOMPLETO\":714,\"SUPERIOR COMPLETO\":147,\"SUPERIOR INCOMPLETO\":120}', '{\"Preta\":63,\"NÃO INFORMADO\":3216,\"Parda\":548,\"Branca\":161,\"Amarela\":1}', '{\"SOLTEIRO\":2961,\"CASADO\":860,\"DIVORCIADO\":94,\"VIÚVO\":66,\"SEPARADO JUDICIALMENTE\":8}', '{\"Obrigatório\":3233,\"Facultativo\":756}', 9999),
(32417, 'SIMÃO DIAS', 36807, 35375, 570, 3, '{\"FEMININO\":19366,\"MASCULINO\":17441}', '{\"55 a 59 anos\":2747,\"65 a 69 anos\":1707,\"30 a 34 anos\":3851,\"20 anos\":651,\"45 a 49 anos\":3176,\"60 a 64 anos\":2360,\"40 a 44 anos\":3493,\"50 a 54 anos\":2956,\"85 a 89 anos\":478,\"80 a 84 anos\":770,\"35 a 39 anos\":3694,\"75 a 79 anos\":1069,\"95 a 99 anos\":68,\"18 anos\":531,\"25 a 29 anos\":3948,\"16 anos\":113,\"90 a 94 anos\":217,\"19 anos\":601,\"21 a 24 anos\":2775,\"70 a 74 anos\":1266,\"17 anos\":273,\"100 anos ou mais\":32,\"15 anos\":31}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":11713,\"LÊ E ESCREVE\":6304,\"ENSINO MÉDIO COMPLETO\":6158,\"ENSINO MÉDIO INCOMPLETO\":6546,\"ENSINO FUNDAMENTAL COMPLETO\":1232,\"ANALFABETO\":2489,\"SUPERIOR INCOMPLETO\":1004,\"SUPERIOR COMPLETO\":1361}', '{\"Branca\":2038,\"NÃO INFORMADO\":25781,\"Parda\":8079,\"Amarela\":41,\"Preta\":864,\"Indígena\":4}', '{\"SOLTEIRO\":26136,\"CASADO\":8705,\"DIVORCIADO\":1080,\"VIÚVO\":590,\"SEPARADO JUDICIALMENTE\":296}', '{\"Obrigatório\":31002,\"Facultativo\":5805}', 9999),
(32433, 'SIRIRI', 6761, 6463, 52, 1, '{\"FEMININO\":3463,\"MASCULINO\":3298}', '{\"60 a 64 anos\":394,\"75 a 79 anos\":174,\"35 a 39 anos\":646,\"50 a 54 anos\":566,\"40 a 44 anos\":702,\"65 a 69 anos\":298,\"25 a 29 anos\":747,\"55 a 59 anos\":506,\"70 a 74 anos\":253,\"21 a 24 anos\":519,\"18 anos\":132,\"85 a 89 anos\":53,\"17 anos\":73,\"80 a 84 anos\":108,\"19 anos\":119,\"45 a 49 anos\":618,\"30 a 34 anos\":686,\"20 anos\":125,\"90 a 94 anos\":20,\"95 a 99 anos\":3,\"15 anos\":3,\"100 anos ou mais\":1,\"16 anos\":15}', '{\"SUPERIOR COMPLETO\":292,\"LÊ E ESCREVE\":555,\"ENSINO FUNDAMENTAL COMPLETO\":326,\"ENSINO FUNDAMENTAL INCOMPLETO\":2118,\"ANALFABETO\":281,\"ENSINO MÉDIO INCOMPLETO\":1423,\"ENSINO MÉDIO COMPLETO\":1535,\"SUPERIOR INCOMPLETO\":231}', '{\"NÃO INFORMADO\":5616,\"Amarela\":15,\"Parda\":796,\"Branca\":159,\"Preta\":171,\"Indígena\":4}', '{\"CASADO\":1380,\"SOLTEIRO\":5000,\"VIÚVO\":131,\"SEPARADO JUDICIALMENTE\":49,\"DIVORCIADO\":201}', '{\"Obrigatório\":5871,\"Facultativo\":890}', 9999),
(32450, 'TELHA', 3596, 3479, 17, 1, '{\"FEMININO\":1847,\"MASCULINO\":1749}', '{\"20 anos\":79,\"40 a 44 anos\":359,\"35 a 39 anos\":404,\"30 a 34 anos\":416,\"19 anos\":67,\"85 a 89 anos\":22,\"21 a 24 anos\":264,\"45 a 49 anos\":375,\"25 a 29 anos\":402,\"60 a 64 anos\":214,\"55 a 59 anos\":250,\"50 a 54 anos\":274,\"17 anos\":38,\"65 a 69 anos\":127,\"70 a 74 anos\":105,\"16 anos\":11,\"80 a 84 anos\":42,\"75 a 79 anos\":70,\"18 anos\":60,\"90 a 94 anos\":11,\"95 a 99 anos\":4,\"100 anos ou mais\":1,\"15 anos\":1}', '{\"ENSINO FUNDAMENTAL INCOMPLETO\":1110,\"ENSINO MÉDIO COMPLETO\":708,\"SUPERIOR INCOMPLETO\":122,\"LÊ E ESCREVE\":301,\"ANALFABETO\":178,\"ENSINO MÉDIO INCOMPLETO\":854,\"ENSINO FUNDAMENTAL COMPLETO\":163,\"SUPERIOR COMPLETO\":160}', '{\"Parda\":470,\"NÃO INFORMADO\":2868,\"Preta\":123,\"Branca\":121,\"Amarela\":14}', '{\"SOLTEIRO\":2626,\"CASADO\":851,\"DIVORCIADO\":75,\"VIÚVO\":33,\"SEPARADO JUDICIALMENTE\":11}', '{\"Obrigatório\":3164,\"Facultativo\":432}', 9999),
(32476, 'TOBIAS BARRETO', 41826, 39614, 337, 6, '{\"FEMININO\":22085,\"MASCULINO\":19741}', '{\"55 a 59 anos\":3021,\"90 a 94 anos\":136,\"25 a 29 anos\":4471,\"21 a 24 anos\":3209,\"35 a 39 anos\":4294,\"85 a 89 anos\":393,\"50 a 54 anos\":3245,\"45 a 49 anos\":3843,\"40 a 44 anos\":4103,\"30 a 34 anos\":4437,\"60 a 64 anos\":2563,\"70 a 74 anos\":1555,\"65 a 69 anos\":2006,\"19 anos\":720,\"75 a 79 anos\":1170,\"18 anos\":592,\"17 anos\":364,\"20 anos\":739,\"80 a 84 anos\":740,\"95 a 99 anos\":38,\"16 anos\":151,\"15 anos\":25,\"100 anos ou mais\":11}', '{\"SUPERIOR COMPLETO\":1284,\"ANALFABETO\":4754,\"ENSINO MÉDIO INCOMPLETO\":7325,\"ENSINO MÉDIO COMPLETO\":6081,\"ENSINO FUNDAMENTAL INCOMPLETO\":15837,\"ENSINO FUNDAMENTAL COMPLETO\":1382,\"SUPERIOR INCOMPLETO\":1027,\"LÊ E ESCREVE\":4136}', '{\"NÃO INFORMADO\":34955,\"Preta\":557,\"Parda\":4737,\"Branca\":1528,\"Amarela\":48,\"Indígena\":1}', '{\"SOLTEIRO\":32412,\"VIÚVO\":492,\"CASADO\":7613,\"DIVORCIADO\":1061,\"SEPARADO JUDICIALMENTE\":248}', '{\"Obrigatório\":33852,\"Facultativo\":7974}', 9999),
(32492, 'TOMAR DO GERU', 10327, 9899, 55, 1, '{\"MASCULINO\":5035,\"FEMININO\":5292}', '{\"65 a 69 anos\":441,\"50 a 54 anos\":845,\"80 a 84 anos\":198,\"60 a 64 anos\":662,\"45 a 49 anos\":1003,\"35 a 39 anos\":968,\"30 a 34 anos\":1078,\"85 a 89 anos\":115,\"20 anos\":202,\"19 anos\":182,\"25 a 29 anos\":1023,\"21 a 24 anos\":806,\"55 a 59 anos\":743,\"40 a 44 anos\":1132,\"70 a 74 anos\":337,\"75 a 79 anos\":254,\"18 anos\":163,\"90 a 94 anos\":63,\"17 anos\":69,\"15 anos\":6,\"95 a 99 anos\":17,\"16 anos\":13,\"100 anos ou mais\":7}', '{\"LÊ E ESCREVE\":1391,\"ENSINO MÉDIO COMPLETO\":1504,\"ANALFABETO\":1038,\"ENSINO FUNDAMENTAL COMPLETO\":316,\"SUPERIOR INCOMPLETO\":164,\"ENSINO MÉDIO INCOMPLETO\":1738,\"ENSINO FUNDAMENTAL INCOMPLETO\":3906,\"SUPERIOR COMPLETO\":270}', '{\"NÃO INFORMADO\":8516,\"Parda\":1264,\"Branca\":399,\"Preta\":136,\"Indígena\":5,\"Amarela\":7}', '{\"CASADO\":1473,\"DIVORCIADO\":142,\"SOLTEIRO\":8612,\"VIÚVO\":76,\"SEPARADO JUDICIALMENTE\":24}', '{\"Obrigatório\":8584,\"Facultativo\":1743}', 9999),
(32514, 'UMBAÚBA', 19386, 18533, 111, 0, '{\"MASCULINO\":9060,\"FEMININO\":10326}', '{\"65 a 69 anos\":877,\"21 a 24 anos\":1549,\"70 a 74 anos\":665,\"55 a 59 anos\":1312,\"18 anos\":318,\"45 a 49 anos\":1886,\"25 a 29 anos\":2140,\"40 a 44 anos\":2056,\"19 anos\":382,\"30 a 34 anos\":2102,\"80 a 84 anos\":261,\"50 a 54 anos\":1483,\"16 anos\":64,\"35 a 39 anos\":2075,\"20 anos\":363,\"60 a 64 anos\":1081,\"75 a 79 anos\":416,\"85 a 89 anos\":121,\"17 anos\":177,\"95 a 99 anos\":6,\"15 anos\":11,\"90 a 94 anos\":38,\"Inválida\":1,\"100 anos ou mais\":2}', '{\"ANALFABETO\":1993,\"ENSINO FUNDAMENTAL INCOMPLETO\":6473,\"ENSINO MÉDIO COMPLETO\":3135,\"LÊ E ESCREVE\":2061,\"ENSINO FUNDAMENTAL COMPLETO\":571,\"ENSINO MÉDIO INCOMPLETO\":4034,\"SUPERIOR COMPLETO\":613,\"SUPERIOR INCOMPLETO\":506}', '{\"Parda\":2882,\"NÃO INFORMADO\":15715,\"Preta\":399,\"Branca\":374,\"Amarela\":15,\"Indígena\":1}', '{\"SOLTEIRO\":14973,\"CASADO\":3554,\"DIVORCIADO\":423,\"VIÚVO\":366,\"SEPARADO JUDICIALMENTE\":70}', '{\"Facultativo\":3111,\"Obrigatório\":16275}', 9999);

COMMIT;
