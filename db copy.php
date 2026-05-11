<?php

mysqli_report(MYSQLI_REPORT_OFF);

$host = getenv('DB_HOST') ?: '127.0.0.1';
$user = getenv('DB_USER') ?: 'apoiacandidato';
$pass = getenv('DB_PASS') ?: '';
$db = getenv('DB_NAME') ?: 'apoiacandidato';
$port = (int) (getenv('DB_PORT') ?: 3306);

$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

if (!$conn->real_connect($host, $user, $pass, $db, $port)) {
    die('Conexão falhou: ' . mysqli_connect_error());
}

$conn->set_charset('utf8mb4');
// Best-effort: some hosts impose a very small statement timeout.
// If the server allows it, relax the limit for this session.
@$conn->query('SET SESSION max_statement_time = 0');
@$conn->query('SET SESSION max_execution_time = 0');

function query(mysqli $conn, string $sql): array
{
    $result = $conn->query($sql);
    if (!$result) {
        return [];
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    return $data;
}

function queryAll(mysqli $conn, string $sql): array
{
    $result = $conn->query($sql);
    if (!$result) {
        throw new RuntimeException($conn->error . ' | SQL: ' . $sql);
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

function querySingle(mysqli $conn, string $sql): array
{
    $result = $conn->query($sql);
    if (!$result) {
        return [];
    }

    return $result->fetch_assoc() ?: [];
}

function queryOne(mysqli $conn, string $sql): array
{
    $result = $conn->query($sql);
    if (!$result) {
        throw new RuntimeException($conn->error . ' | SQL: ' . $sql);
    }

    return $result->fetch_assoc() ?: [];
}
