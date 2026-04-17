DROP TABLE IF EXISTS resumo_municipio_2024_se;
DROP TABLE IF EXISTS resumo_votacao_2024_se;
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
    KEY idx_tipo_voto (tipo_voto),
    KEY idx_nm_votavel (nm_votavel(100)),
    KEY idx_secao (cd_municipio, nr_zona, nr_secao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    KEY idx_votavel (nr_turno, ds_cargo, nr_votavel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    KEY idx_nome (nm_municipio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
