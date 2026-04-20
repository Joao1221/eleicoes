-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 19/04/2026 às 19:38
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `eleicoes`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `candidatos_situacao_2024`
--

CREATE TABLE `candidatos_situacao_2024` (
  `id` int(11) NOT NULL,
  `nr_turno` int(11) DEFAULT NULL,
  `ds_cargo` varchar(50) DEFAULT NULL,
  `cd_municipio` int(11) DEFAULT NULL,
  `nm_municipio` varchar(150) DEFAULT NULL,
  `nr_zona` int(11) DEFAULT NULL,
  `nr_cand` varchar(20) DEFAULT NULL,
  `nm_candidato` varchar(200) DEFAULT NULL,
  `nm_urna_candidato` varchar(200) DEFAULT NULL,
  `sg_partido` varchar(20) DEFAULT NULL,
  `ds_sit_tot_turno` varchar(100) DEFAULT NULL,
  `ds_situacao_candidatura` varchar(100) DEFAULT NULL,
  `sq_candidato` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `resumo_candidatos`
--

CREATE TABLE `resumo_candidatos` (
  `id` int(11) NOT NULL,
  `nr_turno` int(11) DEFAULT NULL,
  `cargo` varchar(50) DEFAULT NULL,
  `nm_candidato` varchar(200) DEFAULT NULL,
  `sg_partido` varchar(20) DEFAULT NULL,
  `total_votos` int(11) DEFAULT 0,
  `situacao_turno` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `resumo_municipios`
--

CREATE TABLE `resumo_municipios` (
  `id` int(11) NOT NULL,
  `nr_turno` int(11) DEFAULT NULL,
  `municipio` varchar(100) DEFAULT NULL,
  `total_votos` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `resumo_municipio_2024_se`
--

CREATE TABLE `resumo_municipio_2024_se` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nr_turno` tinyint(4) NOT NULL,
  `ds_cargo` varchar(30) NOT NULL,
  `cd_municipio` int(11) NOT NULL,
  `nm_municipio` varchar(120) NOT NULL,
  `total_votos` int(11) NOT NULL,
  `total_zonas` int(11) NOT NULL,
  `total_secoes` int(11) NOT NULL,
  `total_votaveis` int(11) NOT NULL,
  `votos_candidato` int(11) NOT NULL,
  `votos_legenda` int(11) NOT NULL,
  `votos_branco` int(11) NOT NULL,
  `votos_nulo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `resumo_partidos`
--

CREATE TABLE `resumo_partidos` (
  `id` int(11) NOT NULL,
  `nr_turno` int(11) DEFAULT NULL,
  `cargo` varchar(50) DEFAULT NULL,
  `sg_partido` varchar(20) DEFAULT NULL,
  `total_votos` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `resumo_votacao_2024_se`
--

CREATE TABLE `resumo_votacao_2024_se` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nr_turno` tinyint(4) NOT NULL,
  `ds_cargo` varchar(30) NOT NULL,
  `cd_municipio` int(11) NOT NULL,
  `nm_municipio` varchar(120) NOT NULL,
  `nr_zona` smallint(6) NOT NULL,
  `nr_votavel` int(11) NOT NULL,
  `sq_candidato` varchar(50) DEFAULT NULL,
  `nm_votavel` varchar(200) NOT NULL,
  `tipo_voto` varchar(20) NOT NULL,
  `total_votos` int(11) NOT NULL,
  `secoes_com_votos` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `votacao_2022`
--

CREATE TABLE `votacao_2022` (
  `id` int(11) NOT NULL,
  `municipio` varchar(100) DEFAULT NULL,
  `cod_municipio` int(11) DEFAULT NULL,
  `zona` int(11) DEFAULT NULL,
  `cargo` varchar(50) DEFAULT NULL,
  `cod_cargo` int(11) DEFAULT NULL,
  `nr_turno` int(11) DEFAULT 1,
  `sq_candidato` bigint(20) DEFAULT NULL,
  `nr_candidato` int(11) DEFAULT NULL,
  `nm_candidato` varchar(200) DEFAULT NULL,
  `nm_urna_candidato` varchar(200) DEFAULT NULL,
  `sg_partido` varchar(20) DEFAULT NULL,
  `nr_partido` int(11) DEFAULT NULL,
  `nm_partido` varchar(100) DEFAULT NULL,
  `qt_votos_nominais` int(11) DEFAULT NULL,
  `qt_votos_validos_zona` int(11) DEFAULT NULL,
  `situacao_turno` varchar(50) DEFAULT NULL,
  `situacao_candidatura` varchar(50) DEFAULT NULL,
  `ano_eleicao` int(11) DEFAULT 2022
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `votacao_secao_2024_se`
--

CREATE TABLE `votacao_secao_2024_se` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `dt_geracao` date DEFAULT NULL,
  `hh_geracao` time DEFAULT NULL,
  `ano_eleicao` smallint(6) NOT NULL,
  `cd_tipo_eleicao` smallint(6) DEFAULT NULL,
  `nm_tipo_eleicao` varchar(80) DEFAULT NULL,
  `nr_turno` tinyint(4) NOT NULL,
  `cd_eleicao` int(11) DEFAULT NULL,
  `ds_eleicao` varchar(120) DEFAULT NULL,
  `dt_eleicao` date DEFAULT NULL,
  `tp_abrangencia` char(1) DEFAULT NULL,
  `sg_uf` char(2) DEFAULT NULL,
  `sg_ue` varchar(10) DEFAULT NULL,
  `nm_ue` varchar(100) DEFAULT NULL,
  `cd_municipio` int(11) NOT NULL,
  `nm_municipio` varchar(120) NOT NULL,
  `nr_zona` smallint(6) NOT NULL,
  `nr_secao` smallint(6) NOT NULL,
  `cd_cargo` smallint(6) NOT NULL,
  `ds_cargo` varchar(30) NOT NULL,
  `nr_votavel` int(11) NOT NULL,
  `nm_votavel` varchar(200) NOT NULL,
  `qt_votos` int(11) NOT NULL,
  `nr_local_votacao` int(11) DEFAULT NULL,
  `sq_candidato` bigint(20) DEFAULT NULL,
  `nm_local_votacao` varchar(200) DEFAULT NULL,
  `ds_local_votacao_endereco` varchar(255) DEFAULT NULL,
  `tipo_voto` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `candidatos_situacao_2024`
--
ALTER TABLE `candidatos_situacao_2024`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cargo_municipio` (`ds_cargo`,`nm_municipio`),
  ADD KEY `idx_cand` (`nr_cand`,`nm_candidato`),
  ADD KEY `idx_sq_candidato` (`sq_candidato`),
  ADD KEY `idx_turno_cargo_nome` (`nr_turno`,`ds_cargo`,`nm_municipio`),
  ADD KEY `idx_turno_cargo_cand` (`nr_turno`,`ds_cargo`,`nr_cand`),
  ADD KEY `idx_turno_cargo_sq` (`nr_turno`,`ds_cargo`,`sq_candidato`);

--
-- Índices de tabela `resumo_candidatos`
--
ALTER TABLE `resumo_candidatos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_key` (`nr_turno`,`cargo`,`nm_candidato`,`sg_partido`),
  ADD KEY `idx_turno_cargo` (`nr_turno`,`cargo`),
  ADD KEY `idx_votos` (`total_votos`);

--
-- Índices de tabela `resumo_municipios`
--
ALTER TABLE `resumo_municipios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_key` (`nr_turno`,`municipio`),
  ADD KEY `idx_votos` (`total_votos`);

--
-- Índices de tabela `resumo_municipio_2024_se`
--
ALTER TABLE `resumo_municipio_2024_se`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_resumo_municipio` (`nr_turno`,`ds_cargo`,`cd_municipio`),
  ADD KEY `idx_total` (`nr_turno`,`ds_cargo`,`total_votos`),
  ADD KEY `idx_nome` (`nm_municipio`),
  ADD KEY `idx_lookup` (`nr_turno`,`ds_cargo`,`nm_municipio`);

--
-- Índices de tabela `resumo_partidos`
--
ALTER TABLE `resumo_partidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_key` (`nr_turno`,`cargo`,`sg_partido`),
  ADD KEY `idx_votos` (`total_votos`);

--
-- Índices de tabela `resumo_votacao_2024_se`
--
ALTER TABLE `resumo_votacao_2024_se`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_resumo` (`nr_turno`,`ds_cargo`,`cd_municipio`,`nr_zona`,`nr_votavel`,`tipo_voto`),
  ADD KEY `idx_rank` (`nr_turno`,`ds_cargo`,`tipo_voto`,`total_votos`),
  ADD KEY `idx_municipio` (`nr_turno`,`ds_cargo`,`cd_municipio`,`tipo_voto`),
  ADD KEY `idx_votavel` (`nr_turno`,`ds_cargo`,`nr_votavel`),
  ADD KEY `idx_municipio_nome` (`nr_turno`,`ds_cargo`,`nm_municipio`),
  ADD KEY `idx_zona` (`nr_turno`,`ds_cargo`,`nr_zona`);

--
-- Índices de tabela `votacao_2022`
--
ALTER TABLE `votacao_2022`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_municipio` (`municipio`),
  ADD KEY `idx_cargo` (`cargo`),
  ADD KEY `idx_partido` (`sg_partido`),
  ADD KEY `idx_candidato` (`nm_candidato`),
  ADD KEY `idx_turno` (`nr_turno`),
  ADD KEY `idx_turno_cargo` (`nr_turno`,`cargo`),
  ADD KEY `idx_turno_municipio` (`nr_turno`,`municipio`),
  ADD KEY `idx_cargo_partido` (`cargo`,`sg_partido`),
  ADD KEY `idx_turno_cargo_situacao` (`nr_turno`,`cargo`,`situacao_turno`);

--
-- Índices de tabela `votacao_secao_2024_se`
--
ALTER TABLE `votacao_secao_2024_se`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_turno_cargo` (`nr_turno`,`ds_cargo`),
  ADD KEY `idx_turno_cargo_municipio` (`nr_turno`,`ds_cargo`,`cd_municipio`),
  ADD KEY `idx_turno_cargo_zona` (`nr_turno`,`ds_cargo`,`cd_municipio`,`nr_zona`),
  ADD KEY `idx_turno_cargo_votavel` (`nr_turno`,`ds_cargo`,`nr_votavel`),
  ADD KEY `idx_tipo_voto` (`tipo_voto`),
  ADD KEY `idx_nm_votavel` (`nm_votavel`(100)),
  ADD KEY `idx_secao` (`cd_municipio`,`nr_zona`,`nr_secao`),
  ADD KEY `idx_turno_cargo_municipio_nome` (`nr_turno`,`ds_cargo`,`nm_municipio`,`nr_zona`,`nr_secao`),
  ADD KEY `idx_turno_cargo_zona_only` (`nr_turno`,`ds_cargo`,`nr_zona`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `candidatos_situacao_2024`
--
ALTER TABLE `candidatos_situacao_2024`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `resumo_candidatos`
--
ALTER TABLE `resumo_candidatos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `resumo_municipios`
--
ALTER TABLE `resumo_municipios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `resumo_municipio_2024_se`
--
ALTER TABLE `resumo_municipio_2024_se`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `resumo_partidos`
--
ALTER TABLE `resumo_partidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `resumo_votacao_2024_se`
--
ALTER TABLE `resumo_votacao_2024_se`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `votacao_2022`
--
ALTER TABLE `votacao_2022`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `votacao_secao_2024_se`
--
ALTER TABLE `votacao_secao_2024_se`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Estrutura para tabelas do módulo premium
--

CREATE TABLE `premium_users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_login_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_email` (`email`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `premium_campaigns` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `campaign_name` varchar(190) NOT NULL,
  `candidate_name` varchar(190) NOT NULL,
  `candidate_cargo` varchar(60) NOT NULL,
  `baseline_year` smallint(6) NOT NULL DEFAULT 2022,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `baseline_panel_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `settings_panel_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `current_municipio` varchar(120) DEFAULT NULL,
  `current_region` varchar(120) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_status` (`user_id`,`status`),
  KEY `idx_candidate` (`candidate_name`),
  KEY `idx_baseline_year` (`baseline_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `premium_campaign_settings` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id` int(10) UNSIGNED NOT NULL,
  `settings_json` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_campaign` (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `premium_campaign_leaders` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id` int(10) UNSIGNED NOT NULL,
  `region_name` varchar(120) NOT NULL,
  `municipality` varchar(120) NOT NULL,
  `leader_name` varchar(190) NOT NULL,
  `leader_cargo` varchar(60) NOT NULL,
  `leader_party` varchar(20) DEFAULT NULL,
  `source_sq_candidato` varchar(50) DEFAULT NULL,
  `source_nr_votavel` int(11) DEFAULT NULL,
  `source_turno` tinyint(4) NOT NULL DEFAULT 1,
  `leader_votes_2024` int(11) NOT NULL DEFAULT 0,
  `margin_percent` decimal(6,2) NOT NULL DEFAULT 0.00,
  `transfer_rate` decimal(6,2) NOT NULL DEFAULT 40.00,
  `aligned_with_executive` tinyint(1) NOT NULL DEFAULT 0,
  `visibility_score` decimal(6,2) NOT NULL DEFAULT 50.00,
  `investment_score` decimal(6,2) NOT NULL DEFAULT 50.00,
  `size_class` enum('small','medium','large') NOT NULL DEFAULT 'medium',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_campaign` (`campaign_id`),
  KEY `idx_municipality` (`municipality`),
  KEY `idx_region` (`region_name`),
  KEY `idx_source_sq` (`source_sq_candidato`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `premium_agenda` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(190) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` enum('open','doing','done','archived') NOT NULL DEFAULT 'open',
  `municipality` varchar(120) DEFAULT NULL,
  `leader_name` varchar(190) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_campaign_status` (`campaign_id`,`status`),
  KEY `idx_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `premium_forecast_runs` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id` int(10) UNSIGNED NOT NULL,
  `baseline_total` int(11) NOT NULL DEFAULT 0,
  `projected_total` int(11) NOT NULL DEFAULT 0,
  `scenario_key` varchar(20) NOT NULL DEFAULT 'base',
  `payload_json` longtext NOT NULL,
  `result_json` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_campaign` (`campaign_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `premium_region_municipios` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `region_name` varchar(120) NOT NULL,
  `municipality` varchar(120) NOT NULL,
  `is_polo` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_municipality` (`municipality`),
  KEY `idx_region` (`region_name`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `premium_users` (`name`, `email`, `password_hash`, `status`)
VALUES (
  'Administrador Premium',
  'premium@eleicoes.local',
  '$2y$10$fIXO4PFGGluoVFjFIHF4C.k7UTqXtD46BojG8g47zIpcYs7LebYsi',
  'active'
);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
