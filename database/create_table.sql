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