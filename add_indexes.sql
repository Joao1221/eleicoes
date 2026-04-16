-- Adicionar índices compostos para otimizar filtros combinados
ALTER TABLE votacao_2022 ADD INDEX idx_turno_cargo (nr_turno, cargo);
ALTER TABLE votacao_2022 ADD INDEX idx_turno_municipio (nr_turno, municipio);
ALTER TABLE votacao_2022 ADD INDEX idx_cargo_partido (cargo, sg_partido);
ALTER TABLE votacao_2022 ADD INDEX idx_turno_cargo_situacao (nr_turno, cargo, situacao_turno);