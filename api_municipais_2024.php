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

function sqlList(mysqli $conn, array $values): string
{
    return implode(', ', array_map(
        static fn(string $value): string => sqlString($conn, $value),
        $values
    ));
}

function searchCondition(mysqli $conn, string $busca): ?string
{
    $busca = trim($busca);
    if ($busca === '') {
        return null;
    }

    $needle = '%' . $conn->real_escape_string($busca) . '%';

    if (preg_match('/^\d+$/', $busca) === 1) {
        $numeric = (int) $busca;

        return "(nr_votavel = {$numeric} OR nm_votavel LIKE '{$needle}')";
    }

    return "(nm_votavel LIKE '{$needle}' OR CAST(nr_votavel AS CHAR) LIKE '{$needle}')";
}

function resolveCandidateSearchClause(mysqli $conn, string $cargo, int $turno, string $municipio, string $busca): ?string
{
    $busca = trim($busca);
    if ($busca === '' || preg_match('/^\d+$/', $busca) === 1) {
        return null;
    }

    $matchConditions = [
        "ds_cargo = " . sqlString($conn, $cargo),
        "nr_turno = {$turno}",
        "sq_candidato IS NOT NULL",
    ];

    if ($municipio !== '') {
        $matchConditions[] = "nm_municipio = " . sqlString($conn, $municipio);
    }

    $exactSql = whereClause(array_merge($matchConditions, [
        "(nm_urna_candidato = " . sqlString($conn, $busca) . " OR nm_candidato = " . sqlString($conn, $busca) . ")",
    ]));

    $exactRows = query(
        $conn,
        "SELECT DISTINCT sq_candidato
         FROM candidatos_situacao_2024
         {$exactSql}
         ORDER BY sq_candidato ASC"
    );

    $sqCandidatos = [];
    foreach ($exactRows as $row) {
        $sq = !empty($row['sq_candidato']) ? (int) $row['sq_candidato'] : null;
        if ($sq !== null && $sq > 0) {
            $sqCandidatos[$sq] = true;
        }
    }

    if (!$sqCandidatos) {
        $needle = '%' . $conn->real_escape_string($busca) . '%';
        $likeSql = whereClause(array_merge($matchConditions, [
            "(nm_urna_candidato LIKE '{$needle}' OR nm_candidato LIKE '{$needle}')",
        ]));

        $likeRows = query(
            $conn,
            "SELECT DISTINCT sq_candidato
             FROM candidatos_situacao_2024
             {$likeSql}
             ORDER BY sq_candidato ASC"
        );

        foreach ($likeRows as $row) {
            $sq = !empty($row['sq_candidato']) ? (int) $row['sq_candidato'] : null;
            if ($sq !== null && $sq > 0) {
                $sqCandidatos[$sq] = true;
            }
        }
    }

    if (!$sqCandidatos) {
        return null;
    }

    return 'sq_candidato IN (' . implode(',', array_keys($sqCandidatos)) . ')';
}

function baseConditions(mysqli $conn, string $cargo, int $turno, string $municipio, int $zona, string $tipo, ?string $searchClause = null): array
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

    if ($searchClause !== null) {
        $conditions[] = $searchClause;
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

function buildPrefeitoElectedRanking(mysqli $conn, string $municipio, ?string $searchClause, array $turno2Totals = []): array
{
    $turno2TotalVotos = array_sum($turno2Totals);
    $candidateConditions = [
        "ds_cargo = 'Prefeito'",
        "tipo_voto = 'Candidato'",
    ];

    if ($municipio !== '') {
        $candidateConditions[] = "nm_municipio = " . sqlString($conn, $municipio);
    }

    if ($searchClause !== null) {
        $candidateConditions[] = $searchClause;
    }

    $candidateRows = query(
        $conn,
        "SELECT
            nr_turno,
            nr_votavel,
            nm_votavel,
            cd_municipio,
            nm_municipio,
            SUM(total_votos) AS total_votos,
            COUNT(DISTINCT nr_zona) AS zonas
         FROM resumo_votacao_2024_se
         " . whereClause($candidateConditions) . "
         GROUP BY nr_turno, nr_votavel, nm_votavel, cd_municipio, nm_municipio
         ORDER BY nm_municipio ASC, nr_turno DESC, total_votos DESC, nr_votavel ASC"
    );

    $rowsByKey = [];
    $rowsByMunicipio = [];
    foreach ($candidateRows as $row) {
        $municipioKey = (string) $row['nm_municipio'];
        $candidateKey = $municipioKey . '|' . (int) $row['nr_turno'] . '|' . (int) $row['nr_votavel'];
        $candidateRow = [
            'sq_candidato' => null,
            'nr_votavel' => (int) $row['nr_votavel'],
            'nm_votavel' => $row['nm_votavel'],
            'nm_urna_candidato' => $row['nm_votavel'],
            'sg_partido' => null,
            'situacao' => null,
            'tipo_voto' => 'Candidato',
            'total_votos' => (int) $row['total_votos'],
            'municipios' => 1,
            'zonas' => (int) $row['zonas'],
            'cd_municipio' => (int) $row['cd_municipio'],
            'nm_municipio' => $municipioKey,
            'nr_turno' => (int) $row['nr_turno'],
            'turno_2_total_votos' => strtoupper($municipioKey) === 'ARACAJU'
                ? ($turno2Totals[(int) $row['nr_votavel']] ?? null)
                : null,
            'turno_2_percentual' => strtoupper($municipioKey) === 'ARACAJU' && $turno2TotalVotos > 0
                ? formatShare((int) ($turno2Totals[(int) $row['nr_votavel']] ?? 0), $turno2TotalVotos)
                : null,
        ];

        $rowsByKey[$candidateKey] = $candidateRow;
        $rowsByMunicipio[$municipioKey][] = $candidateRow;
    }

    $statusConditions = [
        "ds_cargo = 'Prefeito'",
        "ds_sit_tot_turno LIKE '%ELEITO%'",
        "ds_sit_tot_turno NOT LIKE '%NÃO ELEITO%'",
        "ds_sit_tot_turno NOT LIKE '%NAO ELEITO%'",
        "ds_sit_tot_turno NOT LIKE '%SUPLENTE%'",
    ];

    if ($municipio !== '') {
        $statusConditions[] = "nm_municipio = " . sqlString($conn, $municipio);
    }

    $statusRows = query(
        $conn,
        "SELECT DISTINCT
            nm_municipio,
            nr_turno,
            nr_cand,
            nm_urna_candidato,
            sg_partido,
            ds_sit_tot_turno,
            sq_candidato
         FROM candidatos_situacao_2024
         " . whereClause($statusConditions) . "
         ORDER BY nm_municipio ASC, nr_turno DESC, nr_cand ASC"
    );

    $winners = [];
    foreach ($statusRows as $statusRow) {
        $municipioKey = (string) $statusRow['nm_municipio'];
        $candidateKey = $municipioKey . '|' . (int) $statusRow['nr_turno'] . '|' . (int) $statusRow['nr_cand'];
        $candidateRow = $rowsByKey[$candidateKey] ?? null;
        if (!$candidateRow) {
            continue;
        }

        $candidateRow['sq_candidato'] = !empty($statusRow['sq_candidato']) ? (int) $statusRow['sq_candidato'] : null;
        $candidateRow['nm_urna_candidato'] = $statusRow['nm_urna_candidato'] ?? $candidateRow['nm_urna_candidato'];
        $candidateRow['sg_partido'] = $statusRow['sg_partido'] ?? null;
        $candidateRow['situacao'] = $statusRow['ds_sit_tot_turno'] ?? 'ELEITO';

        if (
            !isset($winners[$municipioKey]) ||
            $candidateRow['nr_turno'] > $winners[$municipioKey]['nr_turno'] ||
            (
                $candidateRow['nr_turno'] === $winners[$municipioKey]['nr_turno'] &&
                $candidateRow['total_votos'] > $winners[$municipioKey]['total_votos']
            )
        ) {
            $winners[$municipioKey] = $candidateRow;
        }
    }

    foreach ($rowsByMunicipio as $municipioKey => $municipioRows) {
        if (isset($winners[$municipioKey])) {
            continue;
        }

        usort($municipioRows, static function (array $a, array $b): int {
            $voteCompare = $b['total_votos'] <=> $a['total_votos'];
            if ($voteCompare !== 0) {
                return $voteCompare;
            }

            $turnCompare = $b['nr_turno'] <=> $a['nr_turno'];
            if ($turnCompare !== 0) {
                return $turnCompare;
            }

            return $a['nr_votavel'] <=> $b['nr_votavel'];
        });

        $best = $municipioRows[0];
        $best['situacao'] = 'ELEITO';
        $winners[$municipioKey] = $best;
    }

    $ranking = array_values($winners);
    usort($ranking, static function (array $a, array $b): int {
        $voteCompare = $b['total_votos'] <=> $a['total_votos'];
        if ($voteCompare !== 0) {
            return $voteCompare;
        }

        return strcmp($a['nm_municipio'], $b['nm_municipio']);
    });

    return $ranking;
}

function fetchAracajuPrefeitoTurno2Totals(mysqli $conn): array
{
    $rows = query(
        $conn,
        "SELECT
            nr_votavel,
            SUM(total_votos) AS total_votos
         FROM resumo_votacao_2024_se
         WHERE ds_cargo = 'Prefeito'
           AND nr_turno = 2
           AND nm_municipio = 'ARACAJU'
           AND tipo_voto = 'Candidato'
         GROUP BY nr_votavel
         ORDER BY total_votos DESC, nr_votavel ASC"
    );

    $map = [];
    foreach ($rows as $row) {
        $map[(int) $row['nr_votavel']] = (int) $row['total_votos'];
    }

    return $map;
}

function fetchAracajuPrefeitoTurno2Winner(mysqli $conn): array
{
    $rows = query(
        $conn,
        "SELECT
            nr_cand,
            nm_urna_candidato,
            nm_candidato,
            sg_partido,
            ds_sit_tot_turno,
            sq_candidato
         FROM candidatos_situacao_2024
         WHERE ds_cargo = 'Prefeito'
           AND nr_turno = 2
           AND nm_municipio = 'ARACAJU'
           AND ds_sit_tot_turno LIKE '%ELEITO%'
           AND ds_sit_tot_turno NOT LIKE '%NÃƒO ELEITO%'
           AND ds_sit_tot_turno NOT LIKE '%NAO ELEITO%'
           AND ds_sit_tot_turno NOT LIKE '%SUPLENTE%'
         ORDER BY nr_cand ASC
         LIMIT 1"
    );

    return $rows[0] ?? [];
}

try {
    $cargo = trim($_GET['cargo'] ?? 'Prefeito');
    $requestedTurno = (int) ($_GET['turno'] ?? 1);
    $municipio = trim($_GET['municipio'] ?? '');
    $zona = max(0, (int) ($_GET['zona'] ?? 0));
    $tipo = strtolower(trim($_GET['tipo'] ?? 'candidato'));
    $busca = trim($_GET['busca'] ?? '');
    $rankingMode = strtolower(trim($_GET['ranking'] ?? 'todos'));

    if (!in_array($rankingMode, ['todos', 'eleitos'], true)) {
        $rankingMode = 'todos';
    }

    $turno = max(1, $requestedTurno);
    $aracajuTurno2Totals = ($cargo === 'Prefeito' && $requestedTurno === 1)
        ? fetchAracajuPrefeitoTurno2Totals($conn)
        : [];
    $aracajuTurno2TotalVotos = $aracajuTurno2Totals ? array_sum($aracajuTurno2Totals) : 0;
    $aracajuTurno2Winner = ($cargo === 'Prefeito' && $requestedTurno === 1 && $aracajuTurno2Totals)
        ? fetchAracajuPrefeitoTurno2Winner($conn)
        : [];
    $aracajuTurno2WinnerNr = $aracajuTurno2Totals ? (int) array_key_first($aracajuTurno2Totals) : 0;
    $aracajuTurno2WinnerVotes = $aracajuTurno2WinnerNr > 0 ? (int) ($aracajuTurno2Totals[$aracajuTurno2WinnerNr] ?? 0) : 0;

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

    $searchClause = searchCondition($conn, $busca);
    $candidateSearchClause = resolveCandidateSearchClause($conn, $cargo, $turno, $municipio, $busca);
    $focusCandidate = null;

    if (
        $busca !== '' &&
        $tipo === 'candidato' &&
        ($cargo === 'Vereador' || $cargo === 'Prefeito')
    ) {
        $candidateConditions = baseConditions(
            $conn,
            $cargo,
            $turno,
            $municipio,
            $zona,
            $tipo,
            $candidateSearchClause ?? $searchClause
        );
        $candidateRow = querySingle(
            $conn,
            "SELECT
                nr_votavel,
                nm_votavel,
                MAX(sq_candidato) AS sq_candidato,
                SUM(qt_votos) AS total_votos,
                COUNT(DISTINCT cd_municipio) AS total_municipios,
                COUNT(DISTINCT nr_zona) AS total_zonas,
                COUNT(DISTINCT nr_secao) AS total_secoes,
                MAX(nm_municipio) AS nm_municipio,
                MAX(cd_municipio) AS cd_municipio
             FROM votacao_secao_2024_se
             " . whereClause($candidateConditions) . "
             GROUP BY nr_votavel, nm_votavel
             ORDER BY total_votos DESC, nr_votavel ASC
             LIMIT 1"
        );

        if ($candidateRow) {
            $candidateMeta = [];
            if (!empty($candidateRow['sq_candidato'])) {
                $candidateMeta = querySingle(
                    $conn,
                    "SELECT nm_urna_candidato, sg_partido, ds_sit_tot_turno, nm_candidato
                     FROM candidatos_situacao_2024
                     WHERE ds_cargo = " . sqlString($conn, $cargo) . "
                       AND nr_turno = {$turno}
                       AND sq_candidato = " . (int) $candidateRow['sq_candidato'] . "
                     LIMIT 1"
                );
            }

            $focusCandidate = [
                'nr_votavel' => (int) $candidateRow['nr_votavel'],
                'nm_votavel' => $candidateRow['nm_votavel'],
                'sq_candidato' => !empty($candidateRow['sq_candidato']) ? (int) $candidateRow['sq_candidato'] : null,
                'total_votos' => (int) $candidateRow['total_votos'],
                'total_municipios' => (int) $candidateRow['total_municipios'],
                'total_zonas' => (int) $candidateRow['total_zonas'],
                'total_secoes' => (int) $candidateRow['total_secoes'],
                'nm_municipio' => $candidateRow['nm_municipio'],
                'cd_municipio' => (int) $candidateRow['cd_municipio'],
                'nm_urna_candidato' => $candidateMeta['nm_urna_candidato'] ?? $candidateRow['nm_votavel'],
                'sg_partido' => $candidateMeta['sg_partido'] ?? null,
                'situacao' => $candidateMeta['ds_sit_tot_turno'] ?? null,
                'turno_2_total_votos' => (
                    $cargo === 'Prefeito'
                    && $requestedTurno === 1
                    && strtoupper((string) $candidateRow['nm_municipio']) === 'ARACAJU'
                ) ? ($aracajuTurno2Totals[(int) $candidateRow['nr_votavel']] ?? null) : null,
            ];
        }
    }

    $effectiveSearchClause = $focusCandidate
        ? "(nr_votavel = " . (int) $focusCandidate['nr_votavel'] . " AND nm_votavel = " . sqlString($conn, $focusCandidate['nm_votavel']) . ")"
        : $searchClause;

    $conditions = baseConditions($conn, $cargo, $turno, $municipio, $zona, $tipo, $effectiveSearchClause);
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

    if ($cargo === 'Prefeito' && $rankingMode === 'eleitos') {
        $ranking = buildPrefeitoElectedRanking($conn, $municipio, $effectiveSearchClause, $aracajuTurno2Totals);
    } elseif (($cargo === 'Vereador' || $cargo === 'Prefeito') && $tipo !== 'todos') {
        $rankingConditions = [
            "ds_cargo = " . sqlString($conn, $cargo),
            "nr_turno = {$turno}",
            "tipo_voto = 'Candidato'",
        ];

        if ($municipio !== '') {
            $rankingConditions[] = "nm_municipio = " . sqlString($conn, $municipio);
        }

        if ($zona > 0) {
            $rankingConditions[] = "nr_zona = {$zona}";
        }

        if ($searchClause !== null) {
            $rankingConditions[] = $searchClause;
        }

        $statusConditions = [
            "ds_cargo = " . sqlString($conn, $cargo),
            "nr_turno = {$turno}",
        ];

        if ($municipio !== '') {
            $statusConditions[] = "nm_municipio = " . sqlString($conn, $municipio);
        }

        $statusRows = query(
            $conn,
            "SELECT DISTINCT
                cd_municipio,
                nm_municipio,
                nr_cand,
                nm_urna_candidato,
                sg_partido,
                ds_sit_tot_turno,
                sq_candidato
             FROM candidatos_situacao_2024
             " . whereClause($statusConditions) . "
             ORDER BY cd_municipio ASC, nr_cand ASC"
        );

        $statusMap = [];
        foreach ($statusRows as $statusRow) {
            $statusKey = (int) $statusRow['cd_municipio'] . '|' . (int) $statusRow['nr_cand'];
            $statusMap[$statusKey] = [
                'sq_candidato' => !empty($statusRow['sq_candidato']) ? (int) $statusRow['sq_candidato'] : null,
                'nm_urna_candidato' => $statusRow['nm_urna_candidato'] ?? null,
                'sg_partido' => $statusRow['sg_partido'] ?? null,
                'situacao' => $statusRow['ds_sit_tot_turno'] ?? null,
            ];
        }

        $candidateRows = query(
            $conn,
            "SELECT
                nr_votavel,
                nm_votavel,
                cd_municipio,
                nm_municipio,
                SUM(total_votos) AS total_votos,
                COUNT(DISTINCT nr_zona) AS zonas,
                COUNT(DISTINCT cd_municipio) AS municipios
             FROM resumo_votacao_2024_se
             " . whereClause($rankingConditions) . "
             GROUP BY nr_votavel, nm_votavel, cd_municipio, nm_municipio
             ORDER BY total_votos DESC, nr_votavel ASC"
        );

        $ranking = array_map(
            function(array $row) use ($statusMap, $turno, $cargo, $requestedTurno, $aracajuTurno2Totals, $aracajuTurno2TotalVotos): array {
                $statusKey = (int) $row['cd_municipio'] . '|' . (int) $row['nr_votavel'];
                $status = $statusMap[$statusKey] ?? null;

                $turno2TotalVotos = (
                    $cargo === 'Prefeito'
                    && $requestedTurno === 1
                    && isset($row['nm_municipio'])
                    && strtoupper((string) $row['nm_municipio']) === 'ARACAJU'
                ) ? (int) ($aracajuTurno2Totals[(int) $row['nr_votavel']] ?? 0) : 0;

                return [
                    'sq_candidato' => $status['sq_candidato'] ?? null,
                    'nr_votavel' => (int) $row['nr_votavel'],
                    'nr_turno' => isset($row['nr_turno']) ? (int) $row['nr_turno'] : $turno,
                    'nm_votavel' => $row['nm_votavel'],
                    'nm_urna_candidato' => $status['nm_urna_candidato'] ?? $row['nm_votavel'],
                    'sg_partido' => $status['sg_partido'] ?? null,
                    'situacao' => $status['situacao'] ?? null,
                    'tipo_voto' => 'Candidato',
                    'total_votos' => (int) $row['total_votos'],
                    'municipios' => (int) $row['municipios'],
                    'zonas' => (int) $row['zonas'],
                    'cd_municipio' => (int) $row['cd_municipio'],
                    'nm_municipio' => $row['nm_municipio'],
                    'turno_2_total_votos' => $turno2TotalVotos > 0 ? $turno2TotalVotos : null,
                    'turno_2_percentual' => $turno2TotalVotos > 0 && $aracajuTurno2TotalVotos > 0
                        ? formatShare($turno2TotalVotos, $aracajuTurno2TotalVotos)
                        : null,
                ];
            },
            $candidateRows
        );
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

    $leader = $ranking[0] ?? null;
    $rankingTotalVotos = 0;
    foreach ($ranking as $row) {
        $rankingTotalVotos += (int) ($row['total_votos'] ?? 0);
    }
    if ($rankingTotalVotos <= 0) {
        $rankingTotalVotos = $totalVotos;
    }

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

    $topMunicipalityNames = array_map(
        static fn(array $row): string => (string) $row['nm_municipio'],
        $municipalityTotals
    );

    $municipalityLeadersWhere = $where;
    if ($topMunicipalityNames) {
        $municipalityLeadersWhere .= ($municipalityLeadersWhere === '' ? 'WHERE ' : ' AND ')
            . 'nm_municipio IN (' . sqlList($conn, $topMunicipalityNames) . ')';
    }

    $municipalityLeaders = query(
        $conn,
        "SELECT
            nm_municipio,
            nr_votavel,
            nm_votavel,
            tipo_voto,
            SUM(total_votos) AS total_votos
         FROM resumo_votacao_2024_se
         {$municipalityLeadersWhere}
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
    $applyAracajuTurno2MunicipalityOverride = $cargo === 'Prefeito'
        && $requestedTurno === 1
        && $municipio === ''
        && $aracajuTurno2TotalVotos > 0
        && $aracajuTurno2WinnerNr > 0;

    foreach ($municipalityTotals as $row) {
        $city = $row['nm_municipio'];
        $cityTotal = (int) $row['total_votos'];
        $leaderCity = $leadersByCity[$city] ?? null;

        if ($applyAracajuTurno2MunicipalityOverride && strtoupper($city) === 'ARACAJU') {
            $cityTotal = $aracajuTurno2TotalVotos;
            $winnerDisplayName = $aracajuTurno2Winner['nm_candidato']
                ?? $aracajuTurno2Winner['nm_urna_candidato']
                ?? ($leaderCity['nm_votavel'] ?? $city);
            $winnerUrnaName = $aracajuTurno2Winner['nm_urna_candidato']
                ?? $winnerDisplayName;

            $leaderCity = [
                'nr_votavel' => $aracajuTurno2WinnerNr,
                'nm_votavel' => $winnerDisplayName,
                'nm_urna_candidato' => $winnerUrnaName,
                'tipo_voto' => 'Candidato',
                'total_votos' => $aracajuTurno2WinnerVotes,
                'sg_partido' => $aracajuTurno2Winner['sg_partido'] ?? null,
                'situacao' => $aracajuTurno2Winner['ds_sit_tot_turno'] ?? null,
                'turno_2_total_votos' => $aracajuTurno2WinnerVotes,
                'turno_2_percentual' => formatShare($aracajuTurno2WinnerVotes, $cityTotal),
            ];
        }

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

    $deferHeavyInsights = $focusCandidate === null && $zona === 0 && $busca === '';

    if ($deferHeavyInsights) {
        $sections = [];
        $timeline = [];
    } else {
        $sectionLimit = $focusCandidate ? '' : 'LIMIT 18';

        $sections = query(
            $conn,
            "SELECT
                nm_municipio,
                nr_zona,
                nr_secao,
                nr_votavel,
                nm_votavel,
                tipo_voto,
                MAX(nm_local_votacao) AS nm_local_votacao,
                MAX(ds_local_votacao_endereco) AS ds_local_votacao_endereco,
                SUM(qt_votos) AS total_votos
             FROM votacao_secao_2024_se
             " . whereClause($conditions) . "
             GROUP BY nm_municipio, nr_zona, nr_secao, nr_votavel, nm_votavel, tipo_voto
             ORDER BY total_votos DESC, nm_municipio ASC, nr_zona ASC, nr_secao ASC
             {$sectionLimit}"
        );

        $timeline = query(
            $conn,
            "SELECT
                nr_zona,
                SUM(qt_votos) AS total_votos
             FROM votacao_secao_2024_se
             " . whereClause($conditions) . "
             GROUP BY nr_zona
             ORDER BY total_votos DESC
             LIMIT 10"
        );
    }

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
        'detail_mode' => $focusCandidate !== null,
        'insights_deferred' => $deferHeavyInsights,
        'focused_candidate' => $focusCandidate ? [
            'nr_votavel' => $focusCandidate['nr_votavel'],
                'nm_votavel' => $focusCandidate['nm_votavel'],
                'nm_urna_candidato' => $focusCandidate['nm_urna_candidato'],
                'sg_partido' => $focusCandidate['sg_partido'],
                'situacao' => $focusCandidate['situacao'],
                'sq_candidato' => $focusCandidate['sq_candidato'],
                'nr_turno' => 1,
                'total_votos' => $focusCandidate['total_votos'],
                'total_municipios' => $focusCandidate['total_municipios'],
                'total_zonas' => $focusCandidate['total_zonas'],
                'total_secoes' => $focusCandidate['total_secoes'],
                'nm_municipio' => $focusCandidate['nm_municipio'],
                'cd_municipio' => $focusCandidate['cd_municipio'],
                'turno_2_total_votos' => (
                    $cargo === 'Prefeito'
                    && $requestedTurno === 1
                    && isset($focusCandidate['nm_municipio'])
                    && strtoupper((string) $focusCandidate['nm_municipio']) === 'ARACAJU'
                ) ? ($aracajuTurno2Totals[(int) $focusCandidate['nr_votavel']] ?? null) : null,
            ] : null,
        'stats' => [
            'total_votos' => toInt($statRow['total_votos'] ?? 0),
            'total_municipios' => toInt($statRow['total_municipios'] ?? 0),
            'total_zonas' => toInt($statRow['total_zonas'] ?? 0),
            'total_votaveis' => toInt($statRow['total_votaveis'] ?? 0),
            'total_secoes' => toInt($statRow['total_secoes'] ?? 0),
            'lider' => $leader ? [
                'nr_votavel' => (int) $leader['nr_votavel'],
                'nm_votavel' => $leader['nm_votavel'],
                'tipo_voto' => $leader['tipo_voto'] ?? 'Candidato',
                'total_votos' => (int) $leader['total_votos'],
                'percentual' => formatShare((int) $leader['total_votos'], $totalVotos),
                'nm_municipio' => $leader['nm_municipio'] ?? null,
                'nm_urna_candidato' => $leader['nm_urna_candidato'] ?? null,
                'sg_partido' => $leader['sg_partido'] ?? null,
                'situacao' => $leader['situacao'] ?? null,
                'sq_candidato' => $leader['sq_candidato'] ?? null,
                'nr_turno' => isset($leader['nr_turno']) ? (int) $leader['nr_turno'] : $turno,
                'turno_2_total_votos' => isset($leader['turno_2_total_votos']) ? (int) $leader['turno_2_total_votos'] : null,
                'turno_2_percentual' => isset($leader['turno_2_percentual']) ? (float) $leader['turno_2_percentual'] : null,
            ] : null,
        ],
        'ranking' => array_map(
            function(array $row) use ($rankingTotalVotos): array {
                $tipoVoto = $row['tipo_voto'] ?? 'Candidato';
                $cidadeTotal = $row['cidade_total'] ?? $rankingTotalVotos;
                return [
                    'sq_candidato' => isset($row['sq_candidato']) ? (int) $row['sq_candidato'] : null,
                    'nr_votavel' => (int) $row['nr_votavel'],
                    'nr_turno' => isset($row['nr_turno']) ? (int) $row['nr_turno'] : null,
                    'nm_votavel' => $row['nm_votavel'],
                    'nm_urna_candidato' => $row['nm_urna_candidato'] ?? null,
                    'sg_partido' => $row['sg_partido'] ?? null,
                    'situacao' => $row['situacao'] ?? null,
                    'tipo_voto' => $tipoVoto,
                    'total_votos' => (int) $row['total_votos'],
                    'municipios' => (int) $row['municipios'],
                    'zonas' => (int) $row['zonas'],
                    'share' => formatShare((int) $row['total_votos'], $cidadeTotal),
                    'cidade_forte' => null,
                    'nm_municipio' => $row['nm_municipio'] ?? null,
                    'turno_2_total_votos' => isset($row['turno_2_total_votos']) ? (int) $row['turno_2_total_votos'] : null,
                    'turno_2_percentual' => isset($row['turno_2_percentual']) ? (float) $row['turno_2_percentual'] : null,
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
                'nr_secao' => (int) $row['nr_secao'],
                'nr_votavel' => (int) $row['nr_votavel'],
                'nm_votavel' => $row['nm_votavel'],
                'tipo_voto' => $row['tipo_voto'],
                'total_votos' => (int) $row['total_votos'],
                'nm_local_votacao' => $row['nm_local_votacao'] ?? null,
                'ds_local_votacao_endereco' => $row['ds_local_votacao_endereco'] ?? null,
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
