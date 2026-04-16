<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'eleicoes';

// Conexão com opções de performance
$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');

// Otimizações de performance
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
$conn->options(MYSQLI_OPT_READ_TIMEOUT, 30);

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

function query($conn, $sql) {
    $result = $conn->query($sql);
    if (!$result) return [];
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

function querySingle($conn, $sql) {
    $result = $conn->query($sql);
    if (!$result) return [];
    return $result->fetch_assoc();
}