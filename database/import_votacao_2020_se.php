<?php

declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_OFF);

function usage(): void
{
    echo "Uso:\n";
    echo "  php database/import_votacao_2020_se.php --file=docs/votacao_candidato_munzona_2020_SE.csv --database=eleicoes --truncate\n";
    echo "\nOpcoes:\n";
    echo "  --file       Caminho do CSV do TSE.\n";
    echo "  --host       Host MySQL. Padrao: DB_HOST ou 127.0.0.1.\n";
    echo "  --user       Usuario MySQL. Padrao: DB_USER ou root.\n";
    echo "  --pass       Senha MySQL. Padrao: DB_PASS ou vazio.\n";
    echo "  --database   Banco MySQL. Padrao: DB_NAME ou eleicoes.\n";
    echo "  --port       Porta MySQL. Padrao: DB_PORT ou 3306.\n";
    echo "  --truncate   Limpa as tabelas 2020 antes de importar.\n";
    echo "  --dry-run    Le o arquivo e cria tabelas, mas nao insere dados.\n";
}

function option_value(array $options, string $key, string $env, string $default): string
{
    if (isset($options[$key]) && $options[$key] !== false) {
        return (string) $options[$key];
    }

    $value = getenv($env);
    return $value !== false && $value !== '' ? $value : $default;
}

function db_quote(mysqli $conn, ?string $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    return "'" . $conn->real_escape_string($value) . "'";
}

function csv_text(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    if (function_exists('mb_check_encoding') && !mb_check_encoding($value, 'UTF-8')) {
        $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
    } elseif (!preg_match('//u', $value)) {
        $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $value);
        if ($converted !== false) {
            $value = $converted;
        }
    }

    $value = trim($value);
    if ($value === '' || in_array($value, ['#NULO', '#NULO#', '#NE'], true)) {
        return null;
    }

    return $value;
}

function csv_int(?string $value): ?int
{
    $value = csv_text($value);
    if ($value === null) {
        return null;
    }

    $value = str_replace(['.', ' '], '', $value);
    $value = str_replace(',', '.', $value);
    if (!is_numeric($value)) {
        return null;
    }

    return (int) round((float) $value);
}

function csv_date(?string $value): ?string
{
    $value = csv_text($value);
    if ($value === null) {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('d/m/Y', $value);
    return $date ? $date->format('Y-m-d') : null;
}

function csv_time(?string $value): ?string
{
    $value = csv_text($value);
    if ($value === null) {
        return null;
    }

    return preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1 ? $value : null;
}

function normalize_party(string $party): string
{
    $party = strtoupper(trim($party));
    $party = preg_replace('/\s+/', ' ', $party) ?? $party;

    return trim($party);
}

function run_sql(mysqli $conn, string $sql): void
{
    if (!$conn->query($sql)) {
        throw new RuntimeException($conn->error . ' | SQL: ' . $sql);
    }
}

function create_tables(mysqli $conn): void
{
    $statements = [
        "
        CREATE TABLE IF NOT EXISTS votacao_candidato_munzona_2020_se (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            source_line INT UNSIGNED NOT NULL,
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
            nm_ue VARCHAR(120) DEFAULT NULL,
            cd_municipio INT NOT NULL,
            nm_municipio VARCHAR(120) NOT NULL,
            nr_zona SMALLINT NOT NULL,
            cd_cargo SMALLINT NOT NULL,
            ds_cargo VARCHAR(30) NOT NULL,
            sq_candidato_raw VARCHAR(50) DEFAULT NULL,
            nr_candidato INT NOT NULL,
            nm_candidato VARCHAR(200) NOT NULL,
            nm_urna_candidato VARCHAR(200) DEFAULT NULL,
            nm_social_candidato VARCHAR(200) DEFAULT NULL,
            cd_situacao_candidatura SMALLINT DEFAULT NULL,
            ds_situacao_candidatura VARCHAR(80) DEFAULT NULL,
            cd_detalhe_situacao_cand SMALLINT DEFAULT NULL,
            ds_detalhe_situacao_cand VARCHAR(120) DEFAULT NULL,
            cd_situacao_julgamento SMALLINT DEFAULT NULL,
            ds_situacao_julgamento VARCHAR(80) DEFAULT NULL,
            cd_situacao_cassacao SMALLINT DEFAULT NULL,
            ds_situacao_cassacao VARCHAR(80) DEFAULT NULL,
            cd_situacao_dconst_diploma SMALLINT DEFAULT NULL,
            ds_situacao_dconst_diploma VARCHAR(80) DEFAULT NULL,
            tp_agremiacao VARCHAR(60) DEFAULT NULL,
            nr_partido SMALLINT DEFAULT NULL,
            sg_partido VARCHAR(20) DEFAULT NULL,
            nm_partido VARCHAR(120) DEFAULT NULL,
            nr_federacao VARCHAR(30) DEFAULT NULL,
            nm_federacao VARCHAR(200) DEFAULT NULL,
            sg_federacao VARCHAR(20) DEFAULT NULL,
            ds_composicao_federacao TEXT DEFAULT NULL,
            sq_coligacao_raw VARCHAR(50) DEFAULT NULL,
            nm_coligacao VARCHAR(190) DEFAULT NULL,
            ds_composicao_coligacao TEXT DEFAULT NULL,
            st_voto_em_transito CHAR(1) DEFAULT NULL,
            qt_votos_nominais INT NOT NULL DEFAULT 0,
            nm_tipo_destinacao_votos VARCHAR(40) DEFAULT NULL,
            qt_votos_nominais_validos INT NOT NULL DEFAULT 0,
            cd_sit_tot_turno SMALLINT DEFAULT NULL,
            ds_sit_tot_turno VARCHAR(100) DEFAULT NULL,
            KEY idx_turno_cargo_municipio (nr_turno, ds_cargo, cd_municipio),
            KEY idx_candidato (nr_candidato, nm_candidato),
            KEY idx_sq_candidato (sq_candidato_raw),
            KEY idx_partido (sg_partido),
            KEY idx_coligacao (sq_coligacao_raw),
            KEY idx_municipio_nome (nr_turno, ds_cargo, nm_municipio)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        "
        CREATE TABLE IF NOT EXISTS resumo_votacao_2020_se (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nr_turno TINYINT NOT NULL,
            ds_cargo VARCHAR(30) NOT NULL,
            cd_municipio INT NOT NULL,
            nm_municipio VARCHAR(120) NOT NULL,
            nr_zona SMALLINT NOT NULL,
            nr_votavel INT NOT NULL,
            nm_votavel VARCHAR(200) NOT NULL,
            tipo_voto VARCHAR(20) NOT NULL,
            sq_candidato VARCHAR(50) DEFAULT NULL,
            total_votos INT NOT NULL,
            secoes_com_votos INT NOT NULL DEFAULT 0,
            UNIQUE KEY uniq_resumo (nr_turno, ds_cargo, cd_municipio, nr_zona, nr_votavel, tipo_voto),
            KEY idx_rank (nr_turno, ds_cargo, tipo_voto, total_votos),
            KEY idx_municipio (nr_turno, ds_cargo, cd_municipio, tipo_voto),
            KEY idx_votavel (nr_turno, ds_cargo, nr_votavel),
            KEY idx_sq_candidato (sq_candidato),
            KEY idx_municipio_nome (nr_turno, ds_cargo, nm_municipio),
            KEY idx_zona (nr_turno, ds_cargo, nr_zona)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        "
        CREATE TABLE IF NOT EXISTS resumo_municipio_2020_se (
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
            votos_legenda INT NOT NULL DEFAULT 0,
            votos_branco INT NOT NULL DEFAULT 0,
            votos_nulo INT NOT NULL DEFAULT 0,
            UNIQUE KEY uniq_resumo_municipio (nr_turno, ds_cargo, cd_municipio),
            KEY idx_total (nr_turno, ds_cargo, total_votos),
            KEY idx_nome (nm_municipio),
            KEY idx_lookup (nr_turno, ds_cargo, nm_municipio)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        "
        CREATE TABLE IF NOT EXISTS candidatos_situacao_2020 (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
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
            sq_coligacao VARCHAR(50) DEFAULT NULL,
            nm_coligacao VARCHAR(190) DEFAULT NULL,
            ds_composicao_coligacao TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            KEY idx_cargo_municipio (ds_cargo, nm_municipio),
            KEY idx_cand (nr_cand, nm_candidato),
            KEY idx_partido (nr_turno, ds_cargo, sg_partido),
            KEY idx_sq_candidato (sq_candidato),
            KEY idx_turno_cargo_nome (nr_turno, ds_cargo, nm_municipio),
            KEY idx_turno_cargo_cand (nr_turno, ds_cargo, nr_cand),
            KEY idx_turno_cargo_sq (nr_turno, ds_cargo, sq_candidato),
            KEY idx_coligacao (sq_coligacao)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        "
        CREATE TABLE IF NOT EXISTS premium_party_alliances (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            election_year SMALLINT NOT NULL,
            scope_type VARCHAR(20) NOT NULL DEFAULT 'state',
            municipality VARCHAR(120) DEFAULT NULL,
            anchor_party VARCHAR(20) NOT NULL,
            ally_party VARCHAR(20) NOT NULL,
            alliance_name VARCHAR(190) DEFAULT NULL,
            source_notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_year_anchor (election_year, anchor_party),
            KEY idx_scope_city (scope_type, municipality),
            KEY idx_ally_party (ally_party)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
    ];

    foreach ($statements as $statement) {
        run_sql($conn, $statement);
    }
}

function truncate_tables(mysqli $conn): void
{
    foreach ([
        'resumo_votacao_2020_se',
        'resumo_municipio_2020_se',
        'candidatos_situacao_2020',
        'votacao_candidato_munzona_2020_se',
    ] as $table) {
        run_sql($conn, "TRUNCATE TABLE {$table}");
    }

    run_sql($conn, "
        DELETE FROM premium_party_alliances
        WHERE election_year = 2020
          AND scope_type = 'municipal'
    ");
}

function flush_rows(mysqli $conn, array $columns, array &$rows): int
{
    if (!$rows) {
        return 0;
    }

    $values = [];
    foreach ($rows as $row) {
        $fields = [];
        foreach ($columns as $column) {
            $value = $row[$column] ?? null;
            if (is_int($value)) {
                $fields[] = (string) $value;
            } else {
                $fields[] = db_quote($conn, $value !== null ? (string) $value : null);
            }
        }
        $values[] = '(' . implode(',', $fields) . ')';
    }

    run_sql($conn, "
        INSERT INTO votacao_candidato_munzona_2020_se (" . implode(',', $columns) . ")
        VALUES " . implode(',', $values) . "
    ");

    $count = count($rows);
    $rows = [];

    return $count;
}

function rebuild_summaries(mysqli $conn): void
{
    run_sql($conn, 'TRUNCATE TABLE resumo_votacao_2020_se');
    run_sql($conn, "
        INSERT INTO resumo_votacao_2020_se (
            nr_turno,
            ds_cargo,
            cd_municipio,
            nm_municipio,
            nr_zona,
            nr_votavel,
            nm_votavel,
            tipo_voto,
            sq_candidato,
            total_votos,
            secoes_com_votos
        )
        SELECT
            nr_turno,
            ds_cargo,
            cd_municipio,
            nm_municipio,
            nr_zona,
            nr_candidato,
            COALESCE(NULLIF(MAX(nm_urna_candidato), ''), MAX(nm_candidato)),
            'Candidato',
            NULLIF(MAX(sq_candidato_raw), ''),
            SUM(qt_votos_nominais_validos),
            COUNT(*)
        FROM votacao_candidato_munzona_2020_se
        WHERE ano_eleicao = 2020
        GROUP BY nr_turno, ds_cargo, cd_municipio, nm_municipio, nr_zona, nr_candidato
    ");

    run_sql($conn, 'TRUNCATE TABLE resumo_municipio_2020_se');
    run_sql($conn, "
        INSERT INTO resumo_municipio_2020_se (
            nr_turno,
            ds_cargo,
            cd_municipio,
            nm_municipio,
            total_votos,
            total_zonas,
            total_secoes,
            total_votaveis,
            votos_candidato,
            votos_legenda,
            votos_branco,
            votos_nulo
        )
        SELECT
            nr_turno,
            ds_cargo,
            cd_municipio,
            nm_municipio,
            SUM(total_votos),
            COUNT(DISTINCT nr_zona),
            0,
            COUNT(DISTINCT CONCAT(nr_votavel, '|', COALESCE(sq_candidato, ''))),
            SUM(total_votos),
            0,
            0,
            0
        FROM resumo_votacao_2020_se
        WHERE tipo_voto = 'Candidato'
        GROUP BY nr_turno, ds_cargo, cd_municipio, nm_municipio
    ");

    run_sql($conn, 'TRUNCATE TABLE candidatos_situacao_2020');
    run_sql($conn, "
        INSERT INTO candidatos_situacao_2020 (
            nr_turno,
            ds_cargo,
            cd_municipio,
            nm_municipio,
            nr_zona,
            nr_cand,
            nm_candidato,
            nm_urna_candidato,
            sg_partido,
            ds_sit_tot_turno,
            ds_situacao_candidatura,
            sq_candidato,
            sq_coligacao,
            nm_coligacao,
            ds_composicao_coligacao
        )
        SELECT
            nr_turno,
            ds_cargo,
            cd_municipio,
            nm_municipio,
            0,
            CAST(nr_candidato AS CHAR),
            MAX(nm_candidato),
            COALESCE(NULLIF(MAX(nm_urna_candidato), ''), MAX(nm_candidato)),
            MAX(sg_partido),
            MAX(ds_sit_tot_turno),
            MAX(ds_situacao_candidatura),
            NULLIF(MAX(sq_candidato_raw), ''),
            NULLIF(MAX(sq_coligacao_raw), ''),
            NULLIF(MAX(nm_coligacao), ''),
            NULLIF(MAX(ds_composicao_coligacao), '')
        FROM votacao_candidato_munzona_2020_se
        WHERE ano_eleicao = 2020
        GROUP BY nr_turno, ds_cargo, cd_municipio, nm_municipio, nr_candidato
    ");
}

function rebuild_alliances(mysqli $conn): int
{
    run_sql($conn, "
        DELETE FROM premium_party_alliances
        WHERE election_year = 2020
          AND scope_type = 'municipal'
    ");

    $result = $conn->query("
        SELECT DISTINCT
            nm_municipio,
            nm_coligacao,
            ds_composicao_coligacao
        FROM votacao_candidato_munzona_2020_se
        WHERE ano_eleicao = 2020
          AND ds_composicao_coligacao IS NOT NULL
          AND ds_composicao_coligacao <> ''
    ");
    if (!$result) {
        throw new RuntimeException($conn->error);
    }

    $seen = [];
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $composition = (string) ($row['ds_composicao_coligacao'] ?? '');
        $parties = array_values(array_unique(array_filter(array_map(
            static fn(string $party): string => normalize_party($party),
            preg_split('/\s*\/\s*/', $composition) ?: []
        ), static fn(string $party): bool => $party !== '' && !str_starts_with($party, '#'))));

        if (count($parties) < 2) {
            continue;
        }

        $municipality = (string) ($row['nm_municipio'] ?? '');
        $allianceName = csv_text((string) ($row['nm_coligacao'] ?? ''));
        foreach ($parties as $anchorParty) {
            foreach ($parties as $allyParty) {
                if ($anchorParty === $allyParty) {
                    continue;
                }

                $key = implode('|', ['2020', $municipality, $anchorParty, $allyParty]);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $rows[] = [
                    'election_year' => 2020,
                    'scope_type' => 'municipal',
                    'municipality' => $municipality,
                    'anchor_party' => $anchorParty,
                    'ally_party' => $allyParty,
                    'alliance_name' => $allianceName,
                    'source_notes' => 'Importado de votacao_candidato_munzona_2020_SE.csv',
                ];
            }
        }
    }

    if (!$rows) {
        return 0;
    }

    $columns = ['election_year', 'scope_type', 'municipality', 'anchor_party', 'ally_party', 'alliance_name', 'source_notes'];
    $chunks = array_chunk($rows, 500);
    foreach ($chunks as $chunk) {
        $values = [];
        foreach ($chunk as $row) {
            $values[] = '(' . implode(',', array_map(
                static fn(string $column): string => is_int($row[$column] ?? null)
                    ? (string) $row[$column]
                    : db_quote($conn, $row[$column] !== null ? (string) $row[$column] : null),
                $columns
            )) . ')';
        }

        run_sql($conn, "
            INSERT INTO premium_party_alliances (" . implode(',', $columns) . ")
            VALUES " . implode(',', $values) . "
        ");
    }

    return count($rows);
}

$options = getopt('', ['file:', 'host::', 'user::', 'pass::', 'database::', 'port::', 'truncate', 'dry-run', 'help']);
if (isset($options['help'])) {
    usage();
    exit(0);
}

$file = isset($options['file']) ? (string) $options['file'] : __DIR__ . '/../docs/votacao_candidato_munzona_2020_SE.csv';
if (!is_file($file)) {
    fwrite(STDERR, "Arquivo nao encontrado: {$file}\n");
    exit(1);
}

$host = option_value($options, 'host', 'DB_HOST', '127.0.0.1');
$user = option_value($options, 'user', 'DB_USER', 'root');
$pass = option_value($options, 'pass', 'DB_PASS', '');
$database = option_value($options, 'database', 'DB_NAME', 'eleicoes');
$port = (int) option_value($options, 'port', 'DB_PORT', '3306');
$dryRun = isset($options['dry-run']);
$truncate = isset($options['truncate']);

$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
if (!$conn->real_connect($host, $user, $pass, $database, $port)) {
    fwrite(STDERR, 'Conexao falhou: ' . mysqli_connect_error() . "\n");
    exit(1);
}
$conn->set_charset('utf8mb4');
@$conn->query('SET SESSION max_statement_time = 0');
@$conn->query('SET SESSION max_execution_time = 0');

create_tables($conn);
if ($dryRun) {
    echo "Dry-run: tabelas verificadas, sem importacao.\n";
    exit(0);
}
if ($truncate) {
    truncate_tables($conn);
}

$handle = fopen($file, 'rb');
if ($handle === false) {
    fwrite(STDERR, "Nao foi possivel abrir o arquivo: {$file}\n");
    exit(1);
}

$headers = fgetcsv($handle, 0, ';');
if (!$headers) {
    fwrite(STDERR, "CSV sem cabecalho.\n");
    exit(1);
}
$headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);

$columns = [
    'source_line',
    'dt_geracao',
    'hh_geracao',
    'ano_eleicao',
    'cd_tipo_eleicao',
    'nm_tipo_eleicao',
    'nr_turno',
    'cd_eleicao',
    'ds_eleicao',
    'dt_eleicao',
    'tp_abrangencia',
    'sg_uf',
    'sg_ue',
    'nm_ue',
    'cd_municipio',
    'nm_municipio',
    'nr_zona',
    'cd_cargo',
    'ds_cargo',
    'sq_candidato_raw',
    'nr_candidato',
    'nm_candidato',
    'nm_urna_candidato',
    'nm_social_candidato',
    'cd_situacao_candidatura',
    'ds_situacao_candidatura',
    'cd_detalhe_situacao_cand',
    'ds_detalhe_situacao_cand',
    'cd_situacao_julgamento',
    'ds_situacao_julgamento',
    'cd_situacao_cassacao',
    'ds_situacao_cassacao',
    'cd_situacao_dconst_diploma',
    'ds_situacao_dconst_diploma',
    'tp_agremiacao',
    'nr_partido',
    'sg_partido',
    'nm_partido',
    'nr_federacao',
    'nm_federacao',
    'sg_federacao',
    'ds_composicao_federacao',
    'sq_coligacao_raw',
    'nm_coligacao',
    'ds_composicao_coligacao',
    'st_voto_em_transito',
    'qt_votos_nominais',
    'nm_tipo_destinacao_votos',
    'qt_votos_nominais_validos',
    'cd_sit_tot_turno',
    'ds_sit_tot_turno',
];

$batch = [];
$inserted = 0;
$line = 1;
$conn->begin_transaction();

try {
    while (($data = fgetcsv($handle, 0, ';')) !== false) {
        $line++;
        if (count($data) !== count($headers)) {
            throw new RuntimeException("Linha {$line} com quantidade inesperada de colunas.");
        }

        $csv = array_combine($headers, $data);
        if (!is_array($csv)) {
            throw new RuntimeException("Nao foi possivel mapear a linha {$line}.");
        }

        $row = [
            'source_line' => $line,
            'dt_geracao' => csv_date($csv['DT_GERACAO'] ?? null),
            'hh_geracao' => csv_time($csv['HH_GERACAO'] ?? null),
            'ano_eleicao' => csv_int($csv['ANO_ELEICAO'] ?? null) ?? 2020,
            'cd_tipo_eleicao' => csv_int($csv['CD_TIPO_ELEICAO'] ?? null),
            'nm_tipo_eleicao' => csv_text($csv['NM_TIPO_ELEICAO'] ?? null),
            'nr_turno' => csv_int($csv['NR_TURNO'] ?? null) ?? 1,
            'cd_eleicao' => csv_int($csv['CD_ELEICAO'] ?? null),
            'ds_eleicao' => csv_text($csv['DS_ELEICAO'] ?? null),
            'dt_eleicao' => csv_date($csv['DT_ELEICAO'] ?? null),
            'tp_abrangencia' => csv_text($csv['TP_ABRANGENCIA'] ?? null),
            'sg_uf' => csv_text($csv['SG_UF'] ?? null),
            'sg_ue' => csv_text($csv['SG_UE'] ?? null),
            'nm_ue' => csv_text($csv['NM_UE'] ?? null),
            'cd_municipio' => csv_int($csv['CD_MUNICIPIO'] ?? null) ?? 0,
            'nm_municipio' => csv_text($csv['NM_MUNICIPIO'] ?? null) ?? '',
            'nr_zona' => csv_int($csv['NR_ZONA'] ?? null) ?? 0,
            'cd_cargo' => csv_int($csv['CD_CARGO'] ?? null) ?? 0,
            'ds_cargo' => csv_text($csv['DS_CARGO'] ?? null) ?? '',
            'sq_candidato_raw' => csv_text($csv['SQ_CANDIDATO'] ?? null),
            'nr_candidato' => csv_int($csv['NR_CANDIDATO'] ?? null) ?? 0,
            'nm_candidato' => csv_text($csv['NM_CANDIDATO'] ?? null) ?? '',
            'nm_urna_candidato' => csv_text($csv['NM_URNA_CANDIDATO'] ?? null),
            'nm_social_candidato' => csv_text($csv['NM_SOCIAL_CANDIDATO'] ?? null),
            'cd_situacao_candidatura' => csv_int($csv['CD_SITUACAO_CANDIDATURA'] ?? null),
            'ds_situacao_candidatura' => csv_text($csv['DS_SITUACAO_CANDIDATURA'] ?? null),
            'cd_detalhe_situacao_cand' => csv_int($csv['CD_DETALHE_SITUACAO_CAND'] ?? null),
            'ds_detalhe_situacao_cand' => csv_text($csv['DS_DETALHE_SITUACAO_CAND'] ?? null),
            'cd_situacao_julgamento' => csv_int($csv['CD_SITUACAO_JULGAMENTO'] ?? null),
            'ds_situacao_julgamento' => csv_text($csv['DS_SITUACAO_JULGAMENTO'] ?? null),
            'cd_situacao_cassacao' => csv_int($csv['CD_SITUACAO_CASSACAO'] ?? null),
            'ds_situacao_cassacao' => csv_text($csv['DS_SITUACAO_CASSACAO'] ?? null),
            'cd_situacao_dconst_diploma' => csv_int($csv['CD_SITUACAO_DCONST_DIPLOMA'] ?? null),
            'ds_situacao_dconst_diploma' => csv_text($csv['DS_SITUACAO_DCONST_DIPLOMA'] ?? null),
            'tp_agremiacao' => csv_text($csv['TP_AGREMIACAO'] ?? null),
            'nr_partido' => csv_int($csv['NR_PARTIDO'] ?? null),
            'sg_partido' => csv_text($csv['SG_PARTIDO'] ?? null),
            'nm_partido' => csv_text($csv['NM_PARTIDO'] ?? null),
            'nr_federacao' => csv_text($csv['NR_FEDERACAO'] ?? null),
            'nm_federacao' => csv_text($csv['NM_FEDERACAO'] ?? null),
            'sg_federacao' => csv_text($csv['SG_FEDERACAO'] ?? null),
            'ds_composicao_federacao' => csv_text($csv['DS_COMPOSICAO_FEDERACAO'] ?? null),
            'sq_coligacao_raw' => csv_text($csv['SQ_COLIGACAO'] ?? null),
            'nm_coligacao' => csv_text($csv['NM_COLIGACAO'] ?? null),
            'ds_composicao_coligacao' => csv_text($csv['DS_COMPOSICAO_COLIGACAO'] ?? null),
            'st_voto_em_transito' => csv_text($csv['ST_VOTO_EM_TRANSITO'] ?? null),
            'qt_votos_nominais' => csv_int($csv['QT_VOTOS_NOMINAIS'] ?? null) ?? 0,
            'nm_tipo_destinacao_votos' => csv_text($csv['NM_TIPO_DESTINACAO_VOTOS'] ?? null),
            'qt_votos_nominais_validos' => csv_int($csv['QT_VOTOS_NOMINAIS_VALIDOS'] ?? null) ?? 0,
            'cd_sit_tot_turno' => csv_int($csv['CD_SIT_TOT_TURNO'] ?? null),
            'ds_sit_tot_turno' => csv_text($csv['DS_SIT_TOT_TURNO'] ?? null),
        ];

        $batch[] = $row;
        if (count($batch) >= 250) {
            $inserted += flush_rows($conn, $columns, $batch);
            echo "Importadas {$inserted} linhas...\r";
        }
    }

    $inserted += flush_rows($conn, $columns, $batch);
    rebuild_summaries($conn);
    $alliances = rebuild_alliances($conn);
    $conn->commit();
} catch (Throwable $exception) {
    $conn->rollback();
    fwrite(STDERR, "\nErro: " . $exception->getMessage() . "\n");
    exit(1);
} finally {
    fclose($handle);
}

echo "\nImportacao concluida.\n";
echo "Linhas brutas: {$inserted}\n";
echo "Aliancas municipais inseridas: {$alliances}\n";
