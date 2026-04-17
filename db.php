<?php

mysqli_report(MYSQLI_REPORT_OFF);

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db = getenv('DB_NAME') ?: 'eleicoes';
$port = (int) (getenv('DB_PORT') ?: 3306);

$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

if (!$conn->real_connect($host, $user, $pass, $db, $port)) {
    die('Conexão falhou: ' . mysqli_connect_error());
}

$conn->set_charset('utf8mb4');

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

function querySingle(mysqli $conn, string $sql): array
{
    $result = $conn->query($sql);
    if (!$result) {
        return [];
    }

    return $result->fetch_assoc() ?: [];
}
