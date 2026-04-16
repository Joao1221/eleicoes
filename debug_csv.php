<?php

$csvFile = 'C:\\Users\\10199\\Downloads\\Eleicoes 2022\\votacao_candidato_munzona_2022_SE.csv';

$handle = fopen($csvFile, 'r');
if (!$handle) {
    die("Erro ao abrir");
}

$headers = fgetcsv($handle, 0, ';');
echo "Total headers: " . count($headers) . "\n\n";

echo "Headers:\n";
foreach ($headers as $i => $h) {
    echo "$i: $h\n";
}

$row1 = fgetcsv($handle, 0, ';');
echo "\nTotal columns in row 1: " . count($row1) . "\n";

fclose($handle);