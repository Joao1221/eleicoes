<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

set_time_limit(0);
ini_set('memory_limit', '1024M');

$csvFile = __DIR__ . '/votacao_secao_2024_SE.csv';
$schemaFile = __DIR__ . '/create_table_2024.sql';

if (!is_file($csvFile)) {
    exit("Arquivo CSV não encontrado em {$csvFile}\n");
}

if (!is_file($schemaFile)) {
    exit("Arquivo de schema não encontrado em {$schemaFile}\n");
}

function out(string $message): void
{
    if (PHP_SAPI === 'cli') {
        echo $message . PHP_EOL;
        return;
    }

    echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "<br>\n";
    @ob_flush();
    flush();
}

function parseBrDate(?string $value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $date = DateTime::createFromFormat('d/m/Y', $value);
    return $date ? $date->format('Y-m-d') : null;
}

function decodeText(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    return mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
}

function parseTimeValue(?string $value): ?string
{
    $value = trim((string) $value);
    return $value === '' ? null : $value;
}

function classifyVote(string $cargo, int $numero, string $nome, ?int $sqCandidato): string
{
    $nomeNormalizado = mb_strtoupper(trim($nome), 'UTF-8');

    if ($numero === 95 || str_contains($nomeNormalizado, 'BRANCO')) {
        return 'Branco';
    }

    if ($numero === 96 || str_contains($nomeNormalizado, 'NULO')) {
        return 'Nulo';
    }

    if (!empty($sqCandidato)) {
        return 'Candidato';
    }

    if ($cargo === 'Vereador' && $numero > 0 && $numero % 1000 === 0) {
        return 'Legenda';
    }

    return 'Outros';
}

function runSchema(mysqli $conn, string $schemaFile): void
{
    $sql = file_get_contents($schemaFile);
    if ($sql === false) {
        throw new RuntimeException('Não foi possível ler o arquivo de schema.');
    }

    if (!$conn->multi_query($sql)) {
        throw new RuntimeException('Falha ao criar schema: ' . $conn->error);
    }

    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
}

out('Preparando schema 2024...');
runSchema($conn, $schemaFile);

$handle = fopen($csvFile, 'r');
if (!$handle) {
    exit("Não foi possível abrir o CSV.\n");
}

$headers = fgetcsv($handle, 0, ';');
if (!$headers) {
    exit("CSV sem cabeçalho.\n");
}

$map = [];
foreach ($headers as $index => $header) {
    $map[$header] = $index;
}

$sql = 'INSERT INTO votacao_secao_2024_se (
    dt_geracao, hh_geracao, ano_eleicao, cd_tipo_eleicao, nm_tipo_eleicao,
    nr_turno, cd_eleicao, ds_eleicao, dt_eleicao, tp_abrangencia,
    sg_uf, sg_ue, nm_ue, cd_municipio, nm_municipio, nr_zona, nr_secao,
    cd_cargo, ds_cargo, nr_votavel, nm_votavel, qt_votos, nr_local_votacao,
    sq_candidato, nm_local_votacao, ds_local_votacao_endereco, tipo_voto
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    exit("Falha ao preparar insert: {$conn->error}\n");
}

$conn->begin_transaction();

$inserted = 0;
$batchSize = 5000;

while (($row = fgetcsv($handle, 0, ';')) !== false) {
    $get = static function (string $key) use ($row, $map): string {
        $idx = $map[$key] ?? null;
        return $idx === null ? '' : trim((string) ($row[$idx] ?? ''));
    };

    $dtGeracao = parseBrDate($get('DT_GERACAO'));
    $hhGeracao = parseTimeValue($get('HH_GERACAO'));
    $anoEleicao = (int) $get('ANO_ELEICAO');
    $cdTipoEleicao = (int) $get('CD_TIPO_ELEICAO');
    $nmTipoEleicao = decodeText($get('NM_TIPO_ELEICAO'));
    $nrTurno = (int) $get('NR_TURNO');
    $cdEleicao = (int) $get('CD_ELEICAO');
    $dsEleicao = decodeText($get('DS_ELEICAO'));
    $dtEleicao = parseBrDate($get('DT_ELEICAO'));
    $tpAbrangencia = decodeText($get('TP_ABRANGENCIA'));
    $sgUf = decodeText($get('SG_UF'));
    $sgUe = decodeText($get('SG_UE'));
    $nmUe = decodeText($get('NM_UE'));
    $cdMunicipio = (int) $get('CD_MUNICIPIO');
    $nmMunicipio = decodeText($get('NM_MUNICIPIO'));
    $nrZona = (int) $get('NR_ZONA');
    $nrSecao = (int) $get('NR_SECAO');
    $cdCargo = (int) $get('CD_CARGO');
    $dsCargo = decodeText($get('DS_CARGO'));
    $nrVotavel = (int) $get('NR_VOTAVEL');
    $nmVotavel = decodeText($get('NM_VOTAVEL'));
    $qtVotos = (int) $get('QT_VOTOS');
    $nrLocalVotacao = (int) $get('NR_LOCAL_VOTACAO');
    $sqCandidatoRaw = $get('SQ_CANDIDATO');
    $sqCandidato = $sqCandidatoRaw === '' ? null : (int) $sqCandidatoRaw;
    $nmLocalVotacao = decodeText($get('NM_LOCAL_VOTACAO'));
    $dsLocalVotacaoEndereco = decodeText($get('DS_LOCAL_VOTACAO_ENDERECO'));
    $tipoVoto = classifyVote($dsCargo, $nrVotavel, $nmVotavel, $sqCandidato);

    $stmt->bind_param(
        str_repeat('s', 27),
        $dtGeracao,
        $hhGeracao,
        $anoEleicao,
        $cdTipoEleicao,
        $nmTipoEleicao,
        $nrTurno,
        $cdEleicao,
        $dsEleicao,
        $dtEleicao,
        $tpAbrangencia,
        $sgUf,
        $sgUe,
        $nmUe,
        $cdMunicipio,
        $nmMunicipio,
        $nrZona,
        $nrSecao,
        $cdCargo,
        $dsCargo,
        $nrVotavel,
        $nmVotavel,
        $qtVotos,
        $nrLocalVotacao,
        $sqCandidato,
        $nmLocalVotacao,
        $dsLocalVotacaoEndereco,
        $tipoVoto
    );

    if (!$stmt->execute()) {
        throw new RuntimeException('Erro ao inserir linha ' . ($inserted + 1) . ': ' . $stmt->error);
    }

    $inserted++;

    if ($inserted % $batchSize === 0) {
        $conn->commit();
        out("Importadas {$inserted} linhas...");
        $conn->begin_transaction();
    }
}

$conn->commit();
fclose($handle);
$stmt->close();

out("Carga bruta concluída: {$inserted} linhas.");
out('Gerando resumos agregados...');

$summarySql = "
INSERT INTO resumo_votacao_2024_se (
    nr_turno, ds_cargo, cd_municipio, nm_municipio, nr_zona,
    nr_votavel, nm_votavel, tipo_voto, total_votos, secoes_com_votos
)
SELECT
    nr_turno,
    ds_cargo,
    cd_municipio,
    nm_municipio,
    nr_zona,
    nr_votavel,
    nm_votavel,
    tipo_voto,
    SUM(qt_votos) AS total_votos,
    COUNT(*) AS secoes_com_votos
FROM votacao_secao_2024_se
GROUP BY
    nr_turno, ds_cargo, cd_municipio, nm_municipio, nr_zona,
    nr_votavel, nm_votavel, tipo_voto
";

if (!$conn->query($summarySql)) {
    throw new RuntimeException('Falha ao gerar resumo por zona: ' . $conn->error);
}

$municipioSql = "
INSERT INTO resumo_municipio_2024_se (
    nr_turno, ds_cargo, cd_municipio, nm_municipio, total_votos,
    total_zonas, total_secoes, total_votaveis,
    votos_candidato, votos_legenda, votos_branco, votos_nulo
)
SELECT
    nr_turno,
    ds_cargo,
    cd_municipio,
    nm_municipio,
    SUM(qt_votos) AS total_votos,
    COUNT(DISTINCT nr_zona) AS total_zonas,
    COUNT(DISTINCT CONCAT(cd_municipio, '-', nr_zona, '-', nr_secao)) AS total_secoes,
    COUNT(DISTINCT CONCAT(nr_votavel, '-', tipo_voto)) AS total_votaveis,
    SUM(CASE WHEN tipo_voto = 'Candidato' THEN qt_votos ELSE 0 END) AS votos_candidato,
    SUM(CASE WHEN tipo_voto = 'Legenda' THEN qt_votos ELSE 0 END) AS votos_legenda,
    SUM(CASE WHEN tipo_voto = 'Branco' THEN qt_votos ELSE 0 END) AS votos_branco,
    SUM(CASE WHEN tipo_voto = 'Nulo' THEN qt_votos ELSE 0 END) AS votos_nulo
FROM votacao_secao_2024_se
GROUP BY nr_turno, ds_cargo, cd_municipio, nm_municipio
";

if (!$conn->query($municipioSql)) {
    throw new RuntimeException('Falha ao gerar resumo municipal: ' . $conn->error);
}

out('Resumos concluídos com sucesso.');

$stats = querySingle(
    $conn,
    "SELECT
        COUNT(*) AS linhas_brutas,
        COUNT(DISTINCT cd_municipio) AS municipios,
        COUNT(DISTINCT CONCAT(cd_municipio, '-', nr_zona)) AS zonas
     FROM votacao_secao_2024_se"
);

out(
    sprintf(
        'Base pronta: %s linhas, %s municípios, %s combinações município/zona.',
        number_format((int) ($stats['linhas_brutas'] ?? 0), 0, ',', '.'),
        number_format((int) ($stats['municipios'] ?? 0), 0, ',', '.'),
        number_format((int) ($stats['zonas'] ?? 0), 0, ',', '.')
    )
);
