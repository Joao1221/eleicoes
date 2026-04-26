<?php

declare(strict_types=1);

/*
 * Importa perfil_eleitor_secao_ATUAL_SE.csv (TSE) para a tabela
 * perfil_eleitor_municipio, agregando por município.
 *
 * Uso: php database/import_perfil_eleitor.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Somente via CLI.');
}

require_once __DIR__ . '/../db.php';

$csvPath = __DIR__ . '/../perfil_eleitor_secao_ATUAL_SE.csv';

if (!is_file($csvPath)) {
    fwrite(STDERR, "Arquivo não encontrado: $csvPath\n");
    exit(1);
}

// ---------------------------------------------------------------------------
// Cria (ou recria) a tabela
// ---------------------------------------------------------------------------
$conn->query('DROP TABLE IF EXISTS perfil_eleitor_municipio');
$conn->query("
CREATE TABLE perfil_eleitor_municipio (
    cd_municipio    INT          NOT NULL,
    nm_municipio    VARCHAR(100) NOT NULL,
    qt_total        INT          NOT NULL DEFAULT 0,
    qt_biometria    INT          NOT NULL DEFAULT 0,
    qt_deficiencia  INT          NOT NULL DEFAULT 0,
    qt_nome_social  INT          NOT NULL DEFAULT 0,
    genero          JSON,
    faixa_etaria    JSON,
    grau_instrucao  JSON,
    cor_raca        JSON,
    estado_civil    JSON,
    obrigatoriedade JSON,
    ano_eleicao     SMALLINT     NOT NULL DEFAULT 0,
    mes_ref         VARCHAR(7)   NOT NULL DEFAULT '',
    PRIMARY KEY (cd_municipio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

if ($conn->errno) {
    fwrite(STDERR, "Erro ao criar tabela: " . $conn->error . "\n");
    exit(1);
}

echo "Tabela criada.\n";

// ---------------------------------------------------------------------------
// Lê e agrega o CSV
// ---------------------------------------------------------------------------
$fh = fopen($csvPath, 'r');
if (!$fh) {
    fwrite(STDERR, "Não foi possível abrir o CSV.\n");
    exit(1);
}

$municipios = [];
$lineCount  = 0;
$start      = microtime(true);

// Descarta cabeçalho
fgetcsv($fh, 0, ';');

while (($row = fgetcsv($fh, 0, ';')) !== false) {
    $lineCount++;

    // Converte Latin-1 → UTF-8 (encoding padrão TSE)
    $row = array_map(static fn(string $v): string => mb_convert_encoding($v, 'UTF-8', 'ISO-8859-1'), $row);

    $cdMunicipio   = (int)   ($row[4]  ?? 0);
    $nmMunicipio   = trim((string) ($row[5]  ?? ''));
    $genero        = trim((string) ($row[11] ?? ''));
    $estadoCivil   = trim((string) ($row[13] ?? ''));
    $faixaEtaria   = trim((string) ($row[15] ?? ''));
    $grauInstrucao = trim((string) ($row[17] ?? ''));
    $corRaca       = trim((string) ($row[19] ?? ''));
    $obrigatoriede = trim((string) ($row[26] ?? ''));
    $qtEleitores   = (int) ($row[27] ?? 0);
    $qtBiometria   = (int) ($row[28] ?? 0);
    $qtDeficiencia = (int) ($row[29] ?? 0);
    $qtNomeSocial  = (int) ($row[30] ?? 0);
    $anoEleicao    = (int) ($row[2]  ?? 0);
    $dtGeracao     = trim((string) ($row[0] ?? ''));
    // dd/mm/yyyy → mm/yyyy
    $mesRef        = (strlen($dtGeracao) === 10) ? substr($dtGeracao, 3, 7) : '';

    if ($cdMunicipio <= 0 || $nmMunicipio === '') {
        continue;
    }

    if (!isset($municipios[$cdMunicipio])) {
        $municipios[$cdMunicipio] = [
            'nm_municipio'   => $nmMunicipio,
            'qt_total'       => 0,
            'qt_biometria'   => 0,
            'qt_deficiencia' => 0,
            'qt_nome_social' => 0,
            'genero'         => [],
            'faixa_etaria'   => [],
            'grau_instrucao' => [],
            'cor_raca'       => [],
            'estado_civil'   => [],
            'obrigatoriedade'=> [],
            'ano_eleicao'    => $anoEleicao,
            'mes_ref'        => $mesRef,
        ];
    }

    $municipios[$cdMunicipio]['qt_total']       += $qtEleitores;
    $municipios[$cdMunicipio]['qt_biometria']   += $qtBiometria;
    $municipios[$cdMunicipio]['qt_deficiencia'] += $qtDeficiencia;
    $municipios[$cdMunicipio]['qt_nome_social'] += $qtNomeSocial;

    if ($genero !== '')        $municipios[$cdMunicipio]['genero'][$genero]               = ($municipios[$cdMunicipio]['genero'][$genero]               ?? 0) + $qtEleitores;
    if ($estadoCivil !== '')   $municipios[$cdMunicipio]['estado_civil'][$estadoCivil]    = ($municipios[$cdMunicipio]['estado_civil'][$estadoCivil]    ?? 0) + $qtEleitores;
    if ($faixaEtaria !== '')   $municipios[$cdMunicipio]['faixa_etaria'][$faixaEtaria]    = ($municipios[$cdMunicipio]['faixa_etaria'][$faixaEtaria]    ?? 0) + $qtEleitores;
    if ($grauInstrucao !== '') $municipios[$cdMunicipio]['grau_instrucao'][$grauInstrucao] = ($municipios[$cdMunicipio]['grau_instrucao'][$grauInstrucao] ?? 0) + $qtEleitores;
    if ($corRaca !== '')       $municipios[$cdMunicipio]['cor_raca'][$corRaca]             = ($municipios[$cdMunicipio]['cor_raca'][$corRaca]             ?? 0) + $qtEleitores;
    if ($obrigatoriede !== '') $municipios[$cdMunicipio]['obrigatoriedade'][$obrigatoriede] = ($municipios[$cdMunicipio]['obrigatoriedade'][$obrigatoriede] ?? 0) + $qtEleitores;

    if ($lineCount % 100000 === 0) {
        $elapsed = round(microtime(true) - $start, 1);
        echo "  {$lineCount} linhas processadas ({$elapsed}s)…\n";
    }
}

fclose($fh);

echo "Leitura concluída: {$lineCount} linhas. Inserindo no banco…\n";

// ---------------------------------------------------------------------------
// Insere no banco
// ---------------------------------------------------------------------------
$inserted = 0;
foreach ($municipios as $cdMunicipio => $m) {
    $cdMunicipio   = (int) $cdMunicipio;
    $nmMunicipio   = $conn->real_escape_string($m['nm_municipio']);
    $qtTotal       = (int) $m['qt_total'];
    $qtBiometria   = (int) $m['qt_biometria'];
    $qtDeficiencia = (int) $m['qt_deficiencia'];
    $qtNomeSocial  = (int) $m['qt_nome_social'];
    $anoEleicao    = (int) $m['ano_eleicao'];
    $mesRef        = $conn->real_escape_string($m['mes_ref']);

    $genero         = $conn->real_escape_string((string) json_encode($m['genero'],         JSON_UNESCAPED_UNICODE));
    $faixaEtaria    = $conn->real_escape_string((string) json_encode($m['faixa_etaria'],   JSON_UNESCAPED_UNICODE));
    $grauInstrucao  = $conn->real_escape_string((string) json_encode($m['grau_instrucao'], JSON_UNESCAPED_UNICODE));
    $corRaca        = $conn->real_escape_string((string) json_encode($m['cor_raca'],        JSON_UNESCAPED_UNICODE));
    $estadoCivil    = $conn->real_escape_string((string) json_encode($m['estado_civil'],    JSON_UNESCAPED_UNICODE));
    $obrigatoriedade= $conn->real_escape_string((string) json_encode($m['obrigatoriedade'],JSON_UNESCAPED_UNICODE));

    $conn->query("
        INSERT INTO perfil_eleitor_municipio
            (cd_municipio, nm_municipio, qt_total, qt_biometria, qt_deficiencia, qt_nome_social,
             genero, faixa_etaria, grau_instrucao, cor_raca, estado_civil, obrigatoriedade, ano_eleicao, mes_ref)
        VALUES
            ($cdMunicipio, '$nmMunicipio', $qtTotal, $qtBiometria, $qtDeficiencia, $qtNomeSocial,
             '$genero', '$faixaEtaria', '$grauInstrucao', '$corRaca', '$estadoCivil', '$obrigatoriedade', $anoEleicao, '$mesRef')
        ON DUPLICATE KEY UPDATE
            nm_municipio    = VALUES(nm_municipio),
            qt_total        = VALUES(qt_total),
            qt_biometria    = VALUES(qt_biometria),
            qt_deficiencia  = VALUES(qt_deficiencia),
            qt_nome_social  = VALUES(qt_nome_social),
            genero          = VALUES(genero),
            faixa_etaria    = VALUES(faixa_etaria),
            grau_instrucao  = VALUES(grau_instrucao),
            cor_raca        = VALUES(cor_raca),
            estado_civil    = VALUES(estado_civil),
            obrigatoriedade = VALUES(obrigatoriedade),
            ano_eleicao     = VALUES(ano_eleicao),
            mes_ref         = VALUES(mes_ref)
    ");

    if ($conn->errno) {
        fwrite(STDERR, "Erro ao inserir município $cdMunicipio: " . $conn->error . "\n");
    } else {
        $inserted++;
    }
}

$total = round(microtime(true) - $start, 1);
echo "Concluído: {$inserted} municípios inseridos em {$total}s.\n";
