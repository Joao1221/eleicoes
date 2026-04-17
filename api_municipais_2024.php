<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/db.php';

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function sqlString(mysqli $conn, string $value): string
{
    return "'" . $conn->real_escape_string($value) . "'";
}

function baseConditions(mysqli $conn, string $cargo, int $turno, string $municipio, int $zona, string $tipo, string $busca): array
{
    $conditions = [
        "ds_cargo = " . sqlString($conn, $cargo),
        "nr_turno = {$turno}",
    ];

    if ($municipio !== '') {
        $conditions[] = "nm_municipio = " . sqlString($conn, $municipio);
    }

    if ($zona > 0) {
        $conditions[] = "nr_zona = {$zona}";
    }

    if ($tipo !== 'todos') {
        if ($cargo === 'Vereador' || $cargo === 'Prefeito') {
            $conditions[] = "tipo_voto = 'Candidato'";
        } else {
            $conditions[] = "tipo_voto = " . sqlString($conn, ucfirst($tipo));
        }
    }

    if ($busca !== '') {
        $needle = '%' . $conn->real_escape_string($busca) . '%';
        $conditions[] = "(nm_votavel LIKE '{$needle}' OR CAST(nr_votavel AS CHAR) LIKE '{$needle}')";
    }

    return $conditions;
}

function whereClause(array $conditions): string
{
    return $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
}

function toInt(mixed $value): int
{
    return (int) $value;
}

function formatShare(int $value, int $total): float
{
    if ($total <= 0) {
        return 0.0;
    }

    return round(($value / $total) * 100, 2);
}

try {
    $cargo = trim($_GET['cargo'] ?? 'Prefeito');
    $requestedTurno = (int) ($_GET['turno'] ?? 1);
    $municipio = trim($_GET['municipio'] ?? '');
    $zona = max(0, (int) ($_GET['zona'] ?? 0));
    $tipo = strtolower(trim($_GET['tipo'] ?? 'candidato'));
    $busca = trim($_GET['busca'] ?? '');

    $turno = max(1, $requestedTurno);

    if ($cargo === 'Prefeito' && $requestedTurno === 1 && strtoupper($municipio) === 'ARACAJU') {
        $turnoCheck = $conn->query("SELECT DISTINCT nr_turno FROM resumo_votacao_2024_se WHERE ds_cargo = " . sqlString($conn, $cargo) . " AND nm_municipio = 'ARACAJU' ORDER BY nr_turno DESC");
        $availableTurnos = [];
        while ($row = $turnoCheck->fetch_assoc()) {
            $availableTurnos[] = (int) $row['nr_turno'];
        }
        if (in_array(2, $availableTurnos)) {
            $turno = 2;
        }
    }

    $filters = [
        'cargos' => array_map(
            static fn(array $row): string => $row['ds_cargo'],
            query($conn, "SELECT DISTINCT ds_cargo FROM resumo_votacao_2024_se ORDER BY FIELD(ds_cargo, 'Prefeito', 'Vereador'), ds_cargo")
        ),
        'turnos' => array_map(
            static fn(array $row): int => (int) $row['nr_turno'],
            query($conn, "SELECT DISTINCT nr_turno FROM resumo_votacao_2024_se WHERE ds_cargo = " . sqlString($conn, $cargo) . " ORDER BY nr_turno")
        ),
        'municipios' => array_map(
            static fn(array $row): string => $row['nm_municipio'],
            query($conn, "SELECT DISTINCT nm_municipio FROM resumo_municipio_2024_se WHERE ds_cargo = " . sqlString($conn, $cargo) . " AND nr_turno = {$turno} ORDER BY nm_municipio")
        ),
        'zonas' => array_map(
            static fn(array $row): int => (int) $row['nr_zona'],
            query(
                $conn,
                "SELECT DISTINCT nr_zona
                 FROM resumo_votacao_2024_se
                 WHERE ds_cargo = " . sqlString($conn, $cargo) . "
                   AND nr_turno = {$turno}" .
                   ($municipio !== '' ? " AND nm_municipio = " . sqlString($conn, $municipio) : '') . "
                 ORDER BY nr_zona"
            )
        ),
    ];

    $conditions = baseConditions($conn, $cargo, $turno, $municipio, $zona, $tipo, $busca);
    $where = whereClause($conditions);

    $statRow = querySingle(
        $conn,
        "SELECT
            COALESCE(SUM(total_votos), 0) AS total_votos,
            COUNT(DISTINCT cd_municipio) AS total_municipios,
            COUNT(DISTINCT CONCAT(cd_municipio, '-', nr_zona)) AS total_zonas,
            COUNT(DISTINCT nr_votavel) AS total_votaveis,
            COALESCE(SUM(secoes_com_votos), 0) AS total_secoes
         FROM resumo_votacao_2024_se
         {$where}"
    );

    $totalVotos = toInt($statRow['total_votos'] ?? 0);

    if ($cargo === 'Vereador' || $cargo === 'Prefeito') {
        $cityTotals = [];
        $cityTotalsResult = query(
            $conn,
            "SELECT nm_municipio, SUM(total_votos) AS total_votos
             FROM resumo_votacao_2024_se
             {$where} AND tipo_voto = 'Candidato'
             GROUP BY nm_municipio"
        );
        foreach ($cityTotalsResult as $ct) {
            $cityTotals[$ct['nm_municipio']] = (int) $ct['total_votos'];
        }
        
        $tempRanking = query(
            $conn,
            "SELECT nr_votavel, nm_votavel, tipo_voto, nm_municipio, SUM(total_votos) AS total_votos
             FROM resumo_votacao_2024_se
             {$where}
             GROUP BY nr_votavel, nm_votavel, tipo_voto, nm_municipio
             ORDER BY total_votos DESC, nr_votavel ASC"
        );
        
        $situacaoMap = [];
        $situacaoResult = query(
            $conn,
            "SELECT sq_candidato, ds_sit_tot_turno
             FROM candidatos_situacao_2024
             WHERE ds_cargo = " . sqlString($conn, $cargo) . " AND nr_turno = " . $turno
        );
        foreach ($situacaoResult as $sit) {
            if ($sit['sq_candidato']) {
                $situacaoMap[$sit['sq_candidato']] = $sit['ds_sit_tot_turno'];
            }
        }
        
        $vereadorRanking = [];
        $seenCity = [];
        $vereadorTotal = 0;
        foreach ($tempRanking as $row) {
            $city = $row['nm_municipio'];
            if (!isset($seenCity[$city])) {
                $seenCity[$city] = true;
                $cidadeTotal = $cityTotals[$city] ?? (int) $row['total_votos'];
                $vereadorTotal += $cidadeTotal;
                
                $sq = $row['sq_candidato'] ?? '';
                $situacao = $sq && isset($situacaoMap[$sq]) ? $situacaoMap[$sq] : null;
                
                $vereadorRanking[] = [
                    'nr_votavel' => $row['nr_votavel'],
                    'nm_votavel' => $row['nm_votavel'],
                    'tipo_voto' => $row['tipo_voto'],
                    'total_votos' => (int) $row['total_votos'],
                    'municipios' => 1,
                    'zonas' => 1,
                    'nm_municipio' => $row['nm_municipio'],
                    'cidade_total' => $cidadeTotal,
                    'situacao' => $situacao
                ];
                if (count($vereadorRanking) >= 20) break;
            }
        }
        usort($vereadorRanking, function($a, $b) {
            return $b['total_votos'] - $a['total_votos'];
        });
        $ranking = $vereadorRanking;
        $totalVotos = $vereadorTotal;
    } else {
        $ranking = query(
            $conn,
            "SELECT
                nr_votavel,
                nm_votavel,
                tipo_voto,
                SUM(total_votos) AS total_votos,
                COUNT(DISTINCT cd_municipio) AS municipios,
                COUNT(DISTINCT CONCAT(cd_municipio, '-', nr_zona)) AS zonas
             FROM resumo_votacao_2024_se
             {$where}
             GROUP BY nr_votavel, nm_votavel, tipo_voto
             ORDER BY total_votos DESC, nm_votavel ASC
             LIMIT 20"
        );
    }

    $cidadeForte = query(
        $conn,
        "SELECT nr_votavel, nm_votavel, tipo_voto, nm_municipio, SUM(total_votos) AS total_votos
         FROM resumo_votacao_2024_se
         {$where}
         GROUP BY nr_votavel, nm_votavel, tipo_voto, nm_municipio
         ORDER BY nr_votavel ASC, total_votos DESC"
    );

    $cidadeMap = [];
    foreach ($cidadeForte as $row) {
        $key = $row['nr_votavel'] . '|' . $row['nm_votavel'] . '|' . $row['tipo_voto'];
        if (!isset($cidadeMap[$key])) {
            $cidadeMap[$key] = $row['nm_municipio'];
        }
    }

    $leader = $ranking[0] ?? null;

    $municipalityTotals = query(
        $conn,
        "SELECT
            nm_municipio,
            SUM(total_votos) AS total_votos,
            COUNT(DISTINCT CONCAT(cd_municipio, '-', nr_zona)) AS zonas
         FROM resumo_votacao_2024_se
         {$where}
         GROUP BY nm_municipio
         ORDER BY total_votos DESC, nm_municipio ASC
         LIMIT 16"
    );

    $municipalityLeaders = query(
        $conn,
        "SELECT
            nm_municipio,
            nr_votavel,
            nm_votavel,
            tipo_voto,
            SUM(total_votos) AS total_votos
         FROM resumo_votacao_2024_se
         {$where}
         GROUP BY nm_municipio, nr_votavel, nm_votavel, tipo_voto
         ORDER BY nm_municipio ASC, total_votos DESC, nm_votavel ASC"
    );

    $leadersByCity = [];
    foreach ($municipalityLeaders as $row) {
        $city = $row['nm_municipio'];
        if (!isset($leadersByCity[$city])) {
            $leadersByCity[$city] = [
                'nr_votavel' => (int) $row['nr_votavel'],
                'nm_votavel' => $row['nm_votavel'],
                'tipo_voto' => $row['tipo_voto'],
                'total_votos' => (int) $row['total_votos'],
            ];
        }
    }

    $municipios = [];
    foreach ($municipalityTotals as $row) {
        $city = $row['nm_municipio'];
        $cityTotal = (int) $row['total_votos'];
        $leaderCity = $leadersByCity[$city] ?? null;
        $municipios[] = [
            'nm_municipio' => $city,
            'total_votos' => $cityTotal,
            'zonas' => (int) $row['zonas'],
            'lider' => $leaderCity,
            'lider_percentual' => $leaderCity ? formatShare((int) $leaderCity['total_votos'], $cityTotal) : 0,
        ];
    }

    $tipoResumen = query(
        $conn,
        "SELECT tipo_voto, SUM(total_votos) AS total_votos
         FROM resumo_votacao_2024_se
         {$where}
         GROUP BY tipo_voto
         ORDER BY total_votos DESC"
    );

    $sectionConditions = [
        "ds_cargo = " . sqlString($conn, $cargo),
        "nr_turno = {$turno}",
    ];

    if ($municipio !== '') {
        $sectionConditions[] = "nm_municipio = " . sqlString($conn, $municipio);
    }

    if ($zona > 0) {
        $sectionConditions[] = "nr_zona = {$zona}";
    }

    if ($tipo !== 'todos') {
        if ($cargo === 'Vereador' || $cargo === 'Prefeito') {
            $sectionConditions[] = "tipo_voto = 'Candidato'";
        } else {
            $sectionConditions[] = "tipo_voto = " . sqlString($conn, ucfirst($tipo));
        }
    }

    $sections = query(
        $conn,
        "SELECT
            nm_municipio,
            nr_zona,
            nr_votavel,
            nm_votavel,
            tipo_voto,
            SUM(total_votos) as total_votos
         FROM resumo_votacao_2024_se
         " . whereClause($sectionConditions) . "
         GROUP BY nm_municipio, nr_zona, nr_votavel, nm_votavel, tipo_voto
         ORDER BY total_votos DESC
         LIMIT 18"
    );

    $timeline = query(
        $conn,
        "SELECT
            nr_zona,
            SUM(total_votos) AS total_votos
         FROM resumo_votacao_2024_se
         " . whereClause($sectionConditions) . "
         GROUP BY nr_zona
         ORDER BY total_votos DESC
         LIMIT 10"
    );

    respond([
        'filters' => $filters,
        'state' => [
            'cargo' => $cargo,
            'turno' => $turno,
            'municipio' => $municipio,
            'zona' => $zona,
            'tipo' => $tipo,
            'busca' => $busca,
        ],
        'stats' => [
            'total_votos' => toInt($statRow['total_votos'] ?? 0),
            'total_municipios' => toInt($statRow['total_municipios'] ?? 0),
            'total_zonas' => toInt($statRow['total_zonas'] ?? 0),
            'total_votaveis' => toInt($statRow['total_votaveis'] ?? 0),
            'total_secoes' => toInt($statRow['total_secoes'] ?? 0),
            'lider' => $leader ? [
                'nr_votavel' => (int) $leader['nr_votavel'],
                'nm_votavel' => $leader['nm_votavel'],
                'tipo_voto' => $leader['tipo_voto'],
                'total_votos' => (int) $leader['total_votos'],
                'percentual' => formatShare((int) $leader['total_votos'], $totalVotos),
                'nm_municipio' => $leader['nm_municipio'] ?? null,
            ] : null,
        ],
        'ranking' => array_map(
            function(array $row) use ($cidadeMap, $totalVotos): array {
                $key = $row['nr_votavel'] . '|' . $row['nm_votavel'] . '|' . $row['tipo_voto'];
                $cidadeTotal = $row['cidade_total'] ?? $totalVotos;
                return [
                    'nr_votavel' => (int) $row['nr_votavel'],
                    'nm_votavel' => $row['nm_votavel'],
                    'tipo_voto' => $row['tipo_voto'],
                    'total_votos' => (int) $row['total_votos'],
                    'municipios' => (int) $row['municipios'],
                    'zonas' => (int) $row['zonas'],
                    'share' => formatShare((int) $row['total_votos'], $cidadeTotal),
                    'cidade_forte' => $cidadeMap[$key] ?? null,
                    'nm_municipio' => $row['nm_municipio'] ?? null,
                ];
            },
            $ranking
        ),
        'municipios' => $municipios,
        'tipo_resumo' => array_map(
            static fn(array $row): array => [
                'tipo_voto' => $row['tipo_voto'],
                'total_votos' => (int) $row['total_votos'],
                'share' => formatShare((int) $row['total_votos'], $totalVotos),
            ],
            $tipoResumen
        ),
        'secoes' => array_map(
            static fn(array $row): array => [
                'nm_municipio' => $row['nm_municipio'],
                'nr_zona' => (int) $row['nr_zona'],
                'nr_votavel' => (int) $row['nr_votavel'],
                'nm_votavel' => $row['nm_votavel'],
                'tipo_voto' => $row['tipo_voto'],
                'total_votos' => (int) $row['total_votos'],
            ],
            $sections
        ),
        'zonas_destaque' => array_map(
            static fn(array $row): array => [
                'nr_zona' => (int) $row['nr_zona'],
                'total_votos' => (int) $row['total_votos'],
                'share' => formatShare((int) $row['total_votos'], $totalVotos),
            ],
            $timeline
        ),
    ]);
} catch (Throwable $exception) {
    respond([
        'error' => true,
        'message' => $exception->getMessage(),
    ], 500);
}