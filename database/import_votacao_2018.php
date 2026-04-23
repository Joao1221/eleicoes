<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/db.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Execute este script via CLI.\n");
    exit(1);
}

$csvPath = dirname(__DIR__) . '/votacao_candidato_munzona_2018_SE.csv';
$truncate = in_array('--truncate', $argv, true);

if (!is_file($csvPath)) {
    fwrite(STDERR, "CSV não encontrado em: {$csvPath}\n");
    exit(1);
}

function import2018_normalize_value(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $value = trim($value);
    if ($value === '' || $value === '#NULO#' || $value === '#NE') {
        return null;
    }

    $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $value);
    if ($converted !== false && $converted !== '') {
        $value = $converted;
    }

    return trim($value);
}

function import2018_int(?string $value): ?int
{
    $value = import2018_normalize_value($value);
    if ($value === null) {
        return null;
    }

    return (int) preg_replace('/\D+/', '', $value);
}

$conn->query("
    CREATE TABLE IF NOT EXISTS votacao_2018 (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

if ($conn->errno) {
    fwrite(STDERR, "Erro ao criar tabela votacao_2018: {$conn->error}\n");
    exit(1);
}

if ($truncate) {
    $conn->query('TRUNCATE TABLE votacao_2018');
    if ($conn->errno) {
        fwrite(STDERR, "Erro ao limpar tabela votacao_2018: {$conn->error}\n");
        exit(1);
    }
}

$handle = fopen($csvPath, 'rb');
if ($handle === false) {
    fwrite(STDERR, "Não foi possível abrir o CSV.\n");
    exit(1);
}

$headers = fgetcsv($handle, 0, ';', '"');
if (!is_array($headers)) {
    fclose($handle);
    fwrite(STDERR, "Não foi possível ler o cabeçalho do CSV.\n");
    exit(1);
}

$headers = array_map(
    static fn(string $header): string => strtoupper(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', $header))),
    $headers
);

$columns = [
    'municipio',
    'cod_municipio',
    'zona',
    'cargo',
    'cod_cargo',
    'nr_turno',
    'sq_candidato',
    'nr_candidato',
    'nm_candidato',
    'nm_urna_candidato',
    'sg_partido',
    'nr_partido',
    'nm_partido',
    'qt_votos_nominais',
    'qt_votos_validos_zona',
    'situacao_turno',
    'situacao_candidatura',
    'ano_eleicao',
];

$placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
$sql = "
    INSERT INTO votacao_2018 (
        " . implode(', ', $columns) . "
    ) VALUES {$placeholders}
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    fclose($handle);
    fwrite(STDERR, "Erro ao preparar insert: {$conn->error}\n");
    exit(1);
}

$bindTypes = 'siisiiiisssisiissi';
$inserted = 0;
$lineNumber = 1;

while (($row = fgetcsv($handle, 0, ';', '"')) !== false) {
    $lineNumber++;
    if (count($row) !== count($headers)) {
        continue;
    }

    $data = array_combine($headers, $row);
    if (!is_array($data)) {
        continue;
    }

    $municipio = import2018_normalize_value($data['NM_MUNICIPIO'] ?? null);
    $codMunicipio = import2018_int($data['CD_MUNICIPIO'] ?? null);
    $zona = import2018_int($data['NR_ZONA'] ?? null);
    $cargo = import2018_normalize_value($data['DS_CARGO'] ?? null);
    $codCargo = import2018_int($data['CD_CARGO'] ?? null);
    $nrTurno = import2018_int($data['NR_TURNO'] ?? null) ?? 1;
    $sqCandidato = import2018_int($data['SQ_CANDIDATO'] ?? null);
    $nrCandidato = import2018_int($data['NR_CANDIDATO'] ?? null);
    $nmCandidato = import2018_normalize_value($data['NM_CANDIDATO'] ?? null);
    $nmUrnaCandidato = import2018_normalize_value($data['NM_URNA_CANDIDATO'] ?? null);
    $sgPartido = import2018_normalize_value($data['SG_PARTIDO'] ?? null);
    $nrPartido = import2018_int($data['NR_PARTIDO'] ?? null);
    $nmPartido = import2018_normalize_value($data['NM_PARTIDO'] ?? null);
    $qtVotosNominais = import2018_int($data['QT_VOTOS_NOMINAIS'] ?? null) ?? 0;
    $qtVotosValidosZona = import2018_int($data['QT_VOTOS_NOMINAIS_VALIDOS'] ?? null) ?? 0;
    $situacaoTurno = import2018_normalize_value($data['DS_SIT_TOT_TURNO'] ?? null);
    $situacaoCandidatura = import2018_normalize_value($data['DS_SITUACAO_CANDIDATURA'] ?? null);
    $anoEleicao = import2018_int($data['ANO_ELEICAO'] ?? null) ?? 2018;

    $stmt->bind_param(
        $bindTypes,
        $municipio,
        $codMunicipio,
        $zona,
        $cargo,
        $codCargo,
        $nrTurno,
        $sqCandidato,
        $nrCandidato,
        $nmCandidato,
        $nmUrnaCandidato,
        $sgPartido,
        $nrPartido,
        $nmPartido,
        $qtVotosNominais,
        $qtVotosValidosZona,
        $situacaoTurno,
        $situacaoCandidatura,
        $anoEleicao
    );

    if (!$stmt->execute()) {
        fclose($handle);
        $stmt->close();
        fwrite(STDERR, "Erro na linha {$lineNumber}: {$stmt->error}\n");
        exit(1);
    }

    $inserted++;

    if ($inserted % 5000 === 0) {
        fwrite(STDOUT, "Importadas {$inserted} linhas...\n");
    }
}

fclose($handle);
$stmt->close();

fwrite(STDOUT, "Importação concluída. Linhas inseridas: {$inserted}\n");
