<?php
require 'db.php';

$total = querySingle($conn, "SELECT COUNT(*) as c FROM votacao_2022");
echo "Total registros: " . $total['c'] . "\n";

$partidos = query($conn, "SELECT sg_partido, SUM(qt_votos_nominais) as votos FROM votacao_2022 WHERE sg_partido != '' GROUP BY sg_partido ORDER BY votos DESC LIMIT 5");
echo "\nTop 5 Partidos:\n";
foreach ($partidos as $p) {
    echo $p['sg_partido'] . ": " . number_format($p['votos'], 0, ',', '.') . "\n";
}