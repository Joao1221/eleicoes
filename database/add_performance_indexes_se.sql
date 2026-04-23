-- Performance indexes for the Sergipe election dashboard.
-- Run this once on the existing database to help the broad views
-- avoid table scans and large temporary sorts.

ALTER TABLE resumo_votacao_2024_se
    ADD KEY idx_municipio_nome (nr_turno, ds_cargo, nm_municipio),
    ADD KEY idx_zona (nr_turno, ds_cargo, nr_zona);

ALTER TABLE resumo_municipio_2024_se
    ADD KEY idx_lookup (nr_turno, ds_cargo, nm_municipio);

ALTER TABLE votacao_secao_2024_se
    ADD KEY idx_turno_cargo_municipio_nome (nr_turno, ds_cargo, nm_municipio, nr_zona, nr_secao),
    ADD KEY idx_turno_cargo_zona_only (nr_turno, ds_cargo, nr_zona);

ALTER TABLE candidatos_situacao_2024
    ADD KEY idx_turno_cargo_nome (nr_turno, ds_cargo, nm_municipio),
    ADD KEY idx_turno_cargo_cand (nr_turno, ds_cargo, nr_cand),
    ADD KEY idx_turno_cargo_sq (nr_turno, ds_cargo, sq_candidato);

ANALYZE TABLE resumo_votacao_2024_se,
              resumo_municipio_2024_se,
              votacao_secao_2024_se,
              candidatos_situacao_2024;
