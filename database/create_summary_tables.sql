-- Tabelas de resumo para performance
CREATE TABLE IF NOT EXISTS resumo_candidatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nr_turno INT,
    cargo VARCHAR(50),
    nm_candidato VARCHAR(200),
    sg_partido VARCHAR(20),
    total_votos INT DEFAULT 0,
    situacao_turno VARCHAR(50),
    UNIQUE KEY unique_key (nr_turno, cargo, nm_candidato, sg_partido),
    INDEX idx_turno_cargo (nr_turno, cargo),
    INDEX idx_votos (total_votos DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS resumo_municipios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nr_turno INT,
    municipio VARCHAR(100),
    total_votos INT DEFAULT 0,
    UNIQUE KEY unique_key (nr_turno, municipio),
    INDEX idx_votos (total_votos DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS resumo_partidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nr_turno INT,
    cargo VARCHAR(50),
    sg_partido VARCHAR(20),
    total_votos INT DEFAULT 0,
    UNIQUE KEY unique_key (nr_turno, cargo, sg_partido),
    INDEX idx_votos (total_votos DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Preencher resumo_candidatos
INSERT INTO resumo_candidatos (nr_turno, cargo, nm_candidato, sg_partido, total_votos, situacao_turno)
SELECT nr_turno, cargo, nm_candidato, sg_partido, 
       SUM(qt_votos_nominais) as total_votos, 
       MAX(situacao_turno) as situacao_turno
FROM votacao_2022
GROUP BY nr_turno, cargo, nm_candidato, sg_partido
ON DUPLICATE KEY UPDATE total_votos = VALUES(total_votos);

-- Preencher resumo_municipios
INSERT INTO resumo_municipios (nr_turno, municipio, total_votos)
SELECT nr_turno, municipio, SUM(qt_votos_nominais) as total_votos
FROM votacao_2022
GROUP BY nr_turno, municipio
ON DUPLICATE KEY UPDATE total_votos = VALUES(total_votos);

-- Preencher resumo_partidos
INSERT INTO resumo_partidos (nr_turno, cargo, sg_partido, total_votos)
SELECT nr_turno, cargo, sg_partido, SUM(qt_votos_nominais) as total_votos
FROM votacao_2022
WHERE sg_partido != ''
GROUP BY nr_turno, cargo, sg_partido
ON DUPLICATE KEY UPDATE total_votos = VALUES(total_votos);