<?php

declare(strict_types=1);

/*
 * Temporary exporter for large InfinityFree tables.
 *
 * Upload this file to the old InfinityFree site under /database and run it
 * from the browser. It uses the db.php file from that hosting account, so it
 * must be executed where db.php still points to the InfinityFree database.
 *
 * IMPORTANT:
 * 1. Change $exportToken before uploading.
 * 2. Delete this file from the server after exporting.
 */

$exportToken = 'codex-export-401k-votacao-se-2026';
$table = 'votacao_secao_2024_se';
$idColumn = 'id';
$defaultChunkSize = 50000;
$maxChunkSize = 50000;

require_once __DIR__ . '/../db.php';

@set_time_limit(0);
@ini_set('memory_limit', '512M');

function exporter_fail(int $status, string $message): void
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
}

function exporter_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function exporter_sql_ident(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function exporter_sql_value(mysqli $conn, $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    return "'" . $conn->real_escape_string((string) $value) . "'";
}

if ($exportToken === 'change-this-token-before-upload') {
    exporter_fail(500, 'Edite este arquivo e troque $exportToken antes de subir para a hospedagem.');
}

$requestToken = (string) ($_GET['token'] ?? '');
if ($requestToken === '' || !hash_equals($exportToken, $requestToken)) {
    exporter_fail(403, 'Token invalido.');
}

$chunkSize = (int) ($_GET['size'] ?? $defaultChunkSize);
$chunkSize = max(1000, min($maxChunkSize, $chunkSize));

$tableSql = exporter_sql_ident($table);
$idSql = exporter_sql_ident($idColumn);

$summary = querySingle($conn, "
    SELECT
        MIN({$idSql}) AS min_id,
        MAX({$idSql}) AS max_id,
        COUNT(*) AS total_rows
    FROM {$tableSql}
");

if (!$summary) {
    exporter_fail(500, 'Nao foi possivel consultar a tabela.');
}

$minId = (int) ($summary['min_id'] ?? 0);
$maxId = (int) ($summary['max_id'] ?? 0);
$totalRows = (int) ($summary['total_rows'] ?? 0);

if (isset($_GET['download'])) {
    $from = max($minId, (int) ($_GET['from'] ?? $minId));
    $to = min($maxId, (int) ($_GET['to'] ?? ($from + $chunkSize - 1)));

    if ($from <= 0 || $to < $from) {
        exporter_fail(400, 'Intervalo invalido.');
    }

    $result = $conn->query("
        SELECT *
        FROM {$tableSql}
        WHERE {$idSql} BETWEEN {$from} AND {$to}
        ORDER BY {$idSql} ASC
    ");

    if (!$result) {
        exporter_fail(500, 'Erro ao consultar registros: ' . $conn->error);
    }

    $fields = [];
    foreach ($result->fetch_fields() as $field) {
        $fields[] = exporter_sql_ident($field->name);
    }

    $filename = sprintf('%s_%010d_%010d.sql', $table, $from, $to);
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');

    echo "-- Export parcial de {$table}\n";
    echo "-- Intervalo {$idColumn}: {$from} a {$to}\n";
    echo "-- Gerado em " . date('Y-m-d H:i:s') . "\n\n";
    echo "SET NAMES utf8mb4;\n";
    echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $batch = [];
    $batchSize = 500;
    $exported = 0;

    while ($row = $result->fetch_assoc()) {
        $values = [];
        foreach ($row as $value) {
            $values[] = exporter_sql_value($conn, $value);
        }

        $batch[] = '(' . implode(', ', $values) . ')';
        $exported++;

        if (count($batch) >= $batchSize) {
            echo "INSERT INTO {$tableSql} (" . implode(', ', $fields) . ") VALUES\n";
            echo implode(",\n", $batch) . ";\n\n";
            $batch = [];
            flush();
        }
    }

    if ($batch) {
        echo "INSERT INTO {$tableSql} (" . implode(', ', $fields) . ") VALUES\n";
        echo implode(",\n", $batch) . ";\n\n";
    }

    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    echo "-- Registros exportados neste arquivo: {$exported}\n";
    exit;
}

header('Content-Type: text/html; charset=utf-8');

$self = strtok((string) ($_SERVER['REQUEST_URI'] ?? ''), '?') ?: '';
$baseQuery = 'token=' . rawurlencode($exportToken) . '&size=' . $chunkSize;

?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Exportar tabela em partes</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 32px; line-height: 1.45; }
        code { background: #f2f2f2; padding: 2px 5px; border-radius: 4px; }
        a { display: inline-block; margin: 4px 8px 4px 0; }
    </style>
</head>
<body>
    <h1>Exportar <?= exporter_html($table) ?></h1>
    <p>Total encontrado: <strong><?= number_format($totalRows, 0, ',', '.') ?></strong> registros.</p>
    <p>ID minimo: <code><?= $minId ?></code> | ID maximo: <code><?= $maxId ?></code></p>
    <p>Baixe todos os arquivos abaixo e importe em ordem no novo banco.</p>

    <h2>Arquivos</h2>
    <?php for ($from = $minId; $from <= $maxId; $from += $chunkSize): ?>
        <?php
            $to = min($maxId, $from + $chunkSize - 1);
            $href = $self . '?' . $baseQuery . '&download=1&from=' . $from . '&to=' . $to;
        ?>
        <a href="<?= exporter_html($href) ?>">IDs <?= $from ?> a <?= $to ?></a><br>
    <?php endfor; ?>

    <h2>Depois de terminar</h2>
    <p>Apague este arquivo da hospedagem antiga: <code>database/export_votacao_secao_chunks.php</code>.</p>
</body>
</html>
