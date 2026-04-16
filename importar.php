<?php

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'eleicoes';

$csvFile = 'C:\\Users\\10199\\Downloads\\Eleicoes 2022\\votacao_candidato_munzona_2022_SE.csv';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

echo "Conexão estabelecida!<br>";
$conn->set_charset("utf8mb4");

$handle = fopen($csvFile, 'r');
if (!$handle) {
    die("Erro ao abrir arquivo CSV");
}

$headers = fgetcsv($handle, 0, ';');

$headersMap = [];
foreach ($headers as $i => $header) {
    $headersMap[$header] = $i;
}

$batchSize = 1000;
$count = 0;
$totalInserted = 0;
$errors = 0;

$sql = "INSERT INTO votacao_2022 (
    municipio, cod_municipio, zona, cargo, cod_cargo, nr_turno,
    sq_candidato, nr_candidato, nm_candidato, nm_urna_candidato,
    sg_partido, nr_partido, nm_partido, qt_votos_nominais,
    qt_votos_validos_zona, situacao_turno, situacao_candidatura
) VALUES ";

$values = [];

while (($row = fgetcsv($handle, 0, ';')) !== false) {
    if (count($row) < 50) {
        continue;
    }
    
    $get = function($key) use ($row, $headersMap) {
        $idx = $headersMap[$key] ?? -1;
        return ($idx >= 0 && isset($row[$idx])) ? $row[$idx] : '';
    };
    
    $municipio = $conn->real_escape_string(mb_convert_encoding($get('NM_MUNICIPIO'), 'UTF-8', 'Windows-1252'));
    $codMunicipio = intval($get('CD_MUNICIPIO'));
    $zona = intval($get('NR_ZONA'));
    $cargo = $conn->real_escape_string(mb_convert_encoding($get('DS_CARGO'), 'UTF-8', 'Windows-1252'));
    $codCargo = intval($get('CD_CARGO'));
    $nrTurno = intval($get('NR_TURNO'));
    $sqCandidato = intval($get('SQ_CANDIDATO'));
    $nrCandidato = intval($get('NR_CANDIDATO'));
    $nmCandidato = $conn->real_escape_string(mb_convert_encoding($get('NM_CANDIDATO'), 'UTF-8', 'Windows-1252'));
    $nmUrnaCandidato = $conn->real_escape_string(mb_convert_encoding($get('NM_URNA_CANDIDATO'), 'UTF-8', 'Windows-1252'));
    $sgPartido = $conn->real_escape_string($get('SG_PARTIDO'));
    $nrPartido = intval($get('NR_PARTIDO'));
    $nmPartido = $conn->real_escape_string(mb_convert_encoding($get('NM_PARTIDO'), 'UTF-8', 'Windows-1252'));
    $qtVotosNominais = intval($get('QT_VOTOS_NOMINAIS'));
    $qtVotosValidosZona = intval($get('QT_VOTOS_NOMINAIS_VALIDOS'));
    $situacaoTurno = $conn->real_escape_string(mb_convert_encoding($get('DS_SIT_TOT_TURNO'), 'UTF-8', 'Windows-1252'));
    $situacaoCandidatura = $conn->real_escape_string(mb_convert_encoding($get('DS_SITUACAO_CANDIDATURA'), 'UTF-8', 'Windows-1252'));
    
    $values[] = "('$municipio', $codMunicipio, $zona, '$cargo', $codCargo, $nrTurno, $sqCandidato, $nrCandidato, '$nmCandidato', '$nmUrnaCandidato', '$sgPartido', $nrPartido, '$nmPartido', $qtVotosNominais, $qtVotosValidosZona, '$situacaoTurno', '$situacaoCandidatura')";
    
    $count++;
    $totalInserted++;
    
    if ($count >= $batchSize) {
        $fullSql = $sql . implode(", ", $values);
        if ($conn->query($fullSql)) {
            echo "Inserted $totalInserted rows...<br>";
        } else {
            echo "Error: " . $conn->error . "<br>";
            $errors++;
        }
        $count = 0;
        $values = [];
    }
}

if (count($values) > 0) {
    $fullSql = $sql . implode(", ", $values);
    $conn->query($fullSql);
}

fclose($handle);
$conn->close();

echo "<strong>Total de registros inseridos: $totalInserted</strong>";
if ($errors > 0) {
    echo "<br><strong>Erros: $errors</strong>";
}