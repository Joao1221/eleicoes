<?php
header('Content-Type: text/html; charset=utf-8');
echo "Teste";
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'eleicoes';

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');

$result = $conn->query("SELECT sg_partido, SUM(qt_votos_nominais) as votos FROM votacao_2022 WHERE sg_partido != '' GROUP BY sg_partido ORDER BY votos DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo $row['sg_partido'] . ": " . $row['votos'] . "<br>";
}