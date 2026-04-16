<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Falha na conexao com o banco de dados.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


function queryAll(mysqli $conn, string $sql): array {
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

function queryOne(mysqli $conn, string $sql): array {
    $result = $conn->query($sql);
    if (!$result) {
        throw new RuntimeException($conn->error . ' | SQL: ' . $sql);
    }

    return $result->fetch_assoc() ?: [];
}

function buildWhere(array $conditions): string {
    return count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
}

function normalizeKey(string $value): string {
    $value = trim($value);
    $map = [
        'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ä' => 'A',
        'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ó' => 'O', 'Ò' => 'O', 'Õ' => 'O', 'Ô' => 'O', 'Ö' => 'O',
        'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'Ç' => 'C',
        'á' => 'A', 'à' => 'A', 'ã' => 'A', 'â' => 'A', 'ä' => 'A',
        'é' => 'E', 'è' => 'E', 'ê' => 'E', 'ë' => 'E',
        'í' => 'I', 'ì' => 'I', 'î' => 'I', 'ï' => 'I',
        'ó' => 'O', 'ò' => 'O', 'õ' => 'O', 'ô' => 'O', 'ö' => 'O',
        'ú' => 'U', 'ù' => 'U', 'û' => 'U', 'ü' => 'U',
        'ç' => 'C'
    ];
    $value = strtr($value, $map);
    $value = strtoupper($value);
    $value = str_replace(["'", '-', '.'], ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value);
    return trim($value);
}

function mapCityToRegion(string $city, array $regionLookup): ?string {
    $key = normalizeKey($city);
    return $regionLookup[$key] ?? null;
}

function buildInsights(array $candidateRows, array $cityVoteRows, array $regionLookup): array {
    $byCandidate = [];
    foreach ($candidateRows as $candidate) {
        $key = $candidate['nm_candidato'] . '|' . $candidate['cargo'] . '|' . $candidate['nr_turno'];
        $byCandidate[$key] = [
            'nm_candidato' => $candidate['nm_candidato'],
            'nm_urna_candidato' => $candidate['nm_urna_candidato'] ?? $candidate['nm_candidato'],
            'sg_partido' => $candidate['sg_partido'],
            'cargo' => $candidate['cargo'],
            'nr_turno' => $candidate['nr_turno'],
            'situacao' => $candidate['situacao'],
            'total_votos' => (int) $candidate['total_votos'],
            'topCities' => [],
            'topRegions' => []
        ];
    }

    foreach ($cityVoteRows as $row) {
        $key = $row['nm_candidato'] . '|' . $row['cargo'] . '|' . $row['nr_turno'];
        if (!isset($byCandidate[$key])) {
            continue;
        }

        $votes = (int) $row['total_votos'];
        $cityEntry = [
            'municipio' => $row['municipio'],
            'total_votos' => $votes
        ];
        $byCandidate[$key]['topCities'][] = $cityEntry;

        $region = mapCityToRegion($row['municipio'], $regionLookup);
        if ($region) {
            if (!isset($byCandidate[$key]['topRegions'][$region])) {
                $byCandidate[$key]['topRegions'][$region] = [
                    'nome' => $region,
                    'total_votos' => 0
                ];
            }
            $byCandidate[$key]['topRegions'][$region]['total_votos'] += $votes;
        }
    }

    $insights = array_values($byCandidate);
    foreach ($insights as &$candidate) {
        usort($candidate['topCities'], function ($a, $b) {
            return $b['total_votos'] <=> $a['total_votos'];
        });
        $candidate['topCities'] = array_slice($candidate['topCities'], 0, 5);

        $candidate['topRegions'] = array_values($candidate['topRegions']);
        usort($candidate['topRegions'], function ($a, $b) {
            return $b['total_votos'] <=> $a['total_votos'];
        });
        $candidate['topRegions'] = array_slice($candidate['topRegions'], 0, 3);

        $candidate['strongest_city'] = $candidate['topCities'][0] ?? null;
        $candidate['strongest_region'] = $candidate['topRegions'][0] ?? null;
    }
    unset($candidate);

    usort($insights, function ($a, $b) {
        return $b['total_votos'] <=> $a['total_votos'];
    });

    return $insights;
}

function buildRegionSummary(array $cityVoteRows, array $candidateRows, array $regionLookup): array {
    $candidateIndex = [];
    foreach ($candidateRows as $candidate) {
        $candidateIndex[$candidate['nm_candidato'] . '|' . $candidate['cargo'] . '|' . $candidate['nr_turno']] = $candidate;
    }

    $regions = [];
    foreach ($cityVoteRows as $row) {
        $region = mapCityToRegion($row['municipio'], $regionLookup);
        if (!$region) {
            continue;
        }

        if (!isset($regions[$region])) {
            $regions[$region] = [
                'nome' => $region,
                'total_votos' => 0,
                'candidatos' => [],
                'cidades' => []
            ];
        }

        $votes = (int) $row['total_votos'];
        $regions[$region]['total_votos'] += $votes;

        if (!isset($regions[$region]['cidades'][$row['municipio']])) {
            $regions[$region]['cidades'][$row['municipio']] = [
                'municipio' => $row['municipio'],
                'total_votos' => 0
            ];
        }
        $regions[$region]['cidades'][$row['municipio']]['total_votos'] += $votes;

        $candidateKey = $row['nm_candidato'] . '|' . $row['cargo'] . '|' . $row['nr_turno'];
        if (!isset($regions[$region]['candidatos'][$candidateKey])) {
            $candidateMeta = $candidateIndex[$candidateKey] ?? [
                'nm_candidato' => $row['nm_candidato'],
                'nm_urna_candidato' => $row['nm_urna_candidato'] ?? $row['nm_candidato'],
                'sg_partido' => $row['sg_partido'],
                'cargo' => $row['cargo'],
                'nr_turno' => $row['nr_turno'],
                'situacao' => ''
            ];
            $regions[$region]['candidatos'][$candidateKey] = [
                'nm_candidato' => $candidateMeta['nm_candidato'],
                'nm_urna_candidato' => $candidateMeta['nm_urna_candidato'] ?? $candidateMeta['nm_candidato'],
                'sg_partido' => $candidateMeta['sg_partido'],
                'cargo' => $candidateMeta['cargo'],
                'nr_turno' => $candidateMeta['nr_turno'],
                'situacao' => $candidateMeta['situacao'],
                'total_votos' => 0
            ];
        }
        $regions[$region]['candidatos'][$candidateKey]['total_votos'] += $votes;
    }

    $summary = array_values($regions);
    foreach ($summary as &$region) {
        $region['candidatos'] = array_values($region['candidatos']);
        usort($region['candidatos'], function ($a, $b) {
            return $b['total_votos'] <=> $a['total_votos'];
        });

        $region['cidades'] = array_values($region['cidades']);
        usort($region['cidades'], function ($a, $b) {
            return $b['total_votos'] <=> $a['total_votos'];
        });
    }
    unset($region);

    usort($summary, function ($a, $b) {
        return $b['total_votos'] <=> $a['total_votos'];
    });

    return $summary;
}

function buildCandidateRegionSummary(array $cityVoteRows, array $regionLookup): array {
    $regions = [];
    foreach ($cityVoteRows as $row) {
        $region = mapCityToRegion($row['municipio'], $regionLookup);
        if (!$region) {
            continue;
        }

        if (!isset($regions[$region])) {
            $regions[$region] = [
                'nome' => $region,
                'total_votos' => 0,
                'cidades' => []
            ];
        }

        $votes = (int) $row['total_votos'];
        $regions[$region]['total_votos'] += $votes;
        $regions[$region]['cidades'][] = [
            'municipio' => $row['municipio'],
            'total_votos' => $votes
        ];
    }

    $summary = array_values($regions);
    foreach ($summary as &$region) {
        usort($region['cidades'], function ($a, $b) {
            return $b['total_votos'] <=> $a['total_votos'];
        });
        $region['strongest_city'] = $region['cidades'][0] ?? null;
    }
    unset($region);

    usort($summary, function ($a, $b) {
        return $b['total_votos'] <=> $a['total_votos'];
    });

    return $summary;
}

$turno = $_GET['turno'] ?? 'todos';
$any = isset($_GET['any']) && $_GET['any'] === '1';
$cargo = trim($_GET['cargo'] ?? '');
$candidato = trim($_GET['candidato'] ?? '');
$partido = trim($_GET['partido'] ?? '');
$situacao = trim($_GET['situacao'] ?? '');
$municipio = trim($_GET['municipio'] ?? '');

$regioes = [
    'Grande Aracaju' => ['Aracaju', 'Nossa Senhora do Socorro', 'São Cristóvão', 'Barra dos Coqueiros', "Itaporanga d'Ajuda", 'Laranjeiras', 'Riachuelo'],
    'Agreste Central Sergipano' => ['Itabaiana', 'Campo do Brito', 'Carira', 'Macambira', 'Malhador', 'Moita Bonita', 'Nossa Senhora Aparecida', 'Pinhão', 'Ribeirópolis', 'São Domingos', 'Frei Paulo'],
    'Leste Sergipano' => ['Estância', 'Boquim', 'Capela', 'Carmópolis', 'Japaratuba', 'Pirambu', 'Santa Luzia do Itanhy', 'Umbaúba', 'Indiaroba', 'Cristinápolis', 'Arauá', 'Itabaianinha', 'Tomar do Geru', 'General Maynard', 'Rosário do Catete', 'Santo Amaro das Brotas', 'Siriri'],
    'Médio Sertão Sergipano' => ['Aquidabã', 'Cumbe', 'Feira Nova', 'Graccho Cardoso', 'Itabi', 'Nossa Senhora das Dores', 'Nossa Senhora da Glória'],
    'Alto Sertão Sergipano' => ['Canindé de São Francisco', 'Monte Alegre de Sergipe', 'Nossa Senhora de Lourdes', 'Poço Redondo', 'Porto da Folha', 'Gararu', 'Canhoba'],
    'Sul Sergipano' => ['Lagarto', 'Simão Dias', 'Riachão do Dantas', 'Tobias Barreto', 'Salgado'],
    'Baixo São Francisco' => ['Propriá', 'Neópolis', 'Santana do São Francisco', 'Brejo Grande', 'Ilha das Flores', 'Japoatã', 'Muribeca', 'Pacatuba', 'Cedro de São João', 'Malhada dos Bois', 'São Francisco', 'Telha']
];

$regionLookup = [];
foreach ($regioes as $regionName => $cities) {
    foreach ($cities as $city) {
        $regionLookup[normalizeKey($city)] = $regionName;
    }
}

$extraConditions = [];
if ($cargo !== '') {
    $extraConditions[] = "cargo = '" . $conn->real_escape_string($cargo) . "'";
}
if ($candidato !== '') {
    $extraConditions[] = "nm_candidato = '" . $conn->real_escape_string($candidato) . "'";
}
if ($partido !== '') {
    $extraConditions[] = "sg_partido = '" . $conn->real_escape_string($partido) . "'";
}
if ($situacao !== '') {
    $extraConditions[] = "situacao_turno LIKE '%" . $conn->real_escape_string($situacao) . "%'";
}
if ($municipio !== '') {
    $extraConditions[] = "municipio = '" . $conn->real_escape_string($municipio) . "'";
}

$modeConditions = [];
$ui = [
    'modeTitle' => '',
    'modeDescription' => '',
    'tableTitle' => '',
    'municipiosTitle' => '',
    'showGovernorRace' => false,
    'showRegionSection' => false
];

if ($any) {
    // No mode filtering: return raw data according to extra conditions
    $modeConditions = [];
    $ui['modeTitle'] = 'Todos (com candidaturas)';
    $ui['modeDescription'] = 'Exibe todas as candidaturas sem filtro de situação/turno.';
    $ui['tableTitle'] = 'Candidatos (todos)';
    $ui['municipiosTitle'] = 'Total de votos por cidade';
    $ui['showGovernorRace'] = false;
    $ui['showRegionSection'] = false;
} elseif ($turno === '1') {
    $modeConditions = [
        "nr_turno = 1",
        "((cargo = 'Governador') OR situacao_turno LIKE 'ELEITO%')"
    ];
    $ui['modeTitle'] = '1º Turno';
    $ui['modeDescription'] = 'Exibe os eleitos ao Senado, Câmara Federal e Assembleia Legislativa, além de todos os candidatos ao governo no 1º turno.';
    $ui['tableTitle'] = 'Eleitos do 1º turno para disputar 2º turno';
    $ui['municipiosTitle'] = 'Total de votos por cidade no 1º turno';
    $ui['showGovernorRace'] = true;
    $ui['showRegionSection'] = true;
} elseif ($turno === '2') {
    $modeConditions = [
        "nr_turno = 2",
        "cargo = 'Governador'"
    ];
    $ui['modeTitle'] = '2º Turno';
    $ui['modeDescription'] = 'Exibe exclusivamente a disputa ao governo no 2º turno, com foco em cidades e regiões mais fortes de cada candidato.';
    $ui['tableTitle'] = 'Disputa ao governo no 2º turno';
    $ui['municipiosTitle'] = 'Total de votos por cidade no 2º turno';
    $ui['showGovernorRace'] = true;
    $ui['showRegionSection'] = true;
} else {
    $modeConditions = [
        "(situacao_turno LIKE 'ELEITO%')"
    ];
    $ui['modeTitle'] = 'Geral';
    $ui['modeDescription'] = 'Resumo geral com todos os candidatos eleitos e seus totais de votos.';
    $ui['tableTitle'] = 'Todos os eleitos e seus totais de votos';
    $ui['municipiosTitle'] = 'Total de votos dos eleitos por cidade';
}

$where = buildWhere(array_merge($modeConditions, $extraConditions));
$whereParty = buildWhere(array_merge($modeConditions, $extraConditions, ["sg_partido != ''"]));

try {
    $candidatos = queryAll($conn, "
        SELECT
            nm_candidato,
            nm_urna_candidato,
            sg_partido,
            cargo,
            nr_turno,
            SUM(qt_votos_nominais) AS total_votos,
            MAX(situacao_turno) AS situacao
        FROM votacao_2022
        $where
        GROUP BY nm_candidato, nm_urna_candidato, sg_partido, cargo, nr_turno
        ORDER BY FIELD(cargo, 'Governador', 'Senador', 'Deputado Federal', 'Deputado Estadual'),
                 nr_turno,
                 total_votos DESC
    ");

    $stats = [
        'total_votos' => 0,
        'total_candidatos' => count($candidatos),
        'total_municipios' => 0,
        'total_partidos' => 0
    ];
    foreach ($candidatos as $candidate) {
        $stats['total_votos'] += (int) $candidate['total_votos'];
    }

    $only_candidatos = isset($_GET['only_candidatos']) && $_GET['only_candidatos'] === '1';

    $municipios = [];
    $partidos = [];

    if (!$only_candidatos) {
        $municipios = queryAll($conn, "
            SELECT municipio, SUM(qt_votos_nominais) AS total_votos
            FROM votacao_2022
            $where
            GROUP BY municipio
            ORDER BY total_votos DESC
            LIMIT " . ($turno === '2' ? 75 : 20)
        );
        $stats['total_municipios'] = count($municipios ? queryAll($conn, "
            SELECT municipio
            FROM votacao_2022
            $where
            GROUP BY municipio
        ") : []);
        
        $partidos = queryAll($conn, "
            SELECT sg_partido, SUM(qt_votos_nominais) AS votos
            FROM votacao_2022
            $whereParty
            GROUP BY sg_partido
            ORDER BY votos DESC
            LIMIT 10
        ");
        $stats['total_partidos'] = count($partidos ? queryAll($conn, "
            SELECT sg_partido
            FROM votacao_2022
            $whereParty
            GROUP BY sg_partido
        ") : []);
    } else {
        $stats['total_municipios'] = 0;
        $partidos = [];
        $stats['total_partidos'] = 0;
    }

    $cargos = queryAll($conn, "
        SELECT cargo, SUM(qt_votos_nominais) AS votos
        FROM votacao_2022
        $where
        GROUP BY cargo
        ORDER BY FIELD(cargo, 'Governador', 'Senador', 'Deputado Federal', 'Deputado Estadual')
    ");

    if (!$only_candidatos) {
        $cityVoteRows = queryAll($conn, "
            SELECT
                nm_candidato,
                nm_urna_candidato,
                sg_partido,
                cargo,
                nr_turno,
                municipio,
                SUM(qt_votos_nominais) AS total_votos
            FROM votacao_2022
            $where
            GROUP BY nm_candidato, nm_urna_candidato, sg_partido, cargo, nr_turno, municipio
            ORDER BY total_votos DESC
        ");
    } else {
        $cityVoteRows = [];
    }

    $candidateInsights = buildInsights($candidatos, $cityVoteRows, $regionLookup);

    $governorCandidates = array_values(array_filter($candidatos, function ($candidate) {
        return $candidate['cargo'] === 'Governador';
    }));
    usort($governorCandidates, function ($a, $b) {
        return (int) $b['total_votos'] <=> (int) $a['total_votos'];
    });

    $governorCityRows = array_values(array_filter($cityVoteRows, function ($row) {
        return $row['cargo'] === 'Governador';
    }));
    $votosPorRegiao = [];
    if ($ui['showRegionSection'] && $governorCityRows) {
        $votosPorRegiao = buildRegionSummary($governorCityRows, $governorCandidates, $regionLookup);
    }

    $modeHighlights = [];
    if ($turno === '1' && count($governorCandidates) >= 2) {
        $modeHighlights[] = 'Os dois candidatos classificados ao 2º turno são ' . $governorCandidates[0]['nm_candidato'] . ' e ' . $governorCandidates[1]['nm_candidato'] . '.';
    }
    if ($turno === '2' && count($governorCandidates) >= 2) {
        $modeHighlights[] = 'A disputa do 2º turno está concentrada entre ' . $governorCandidates[0]['nm_candidato'] . ' e ' . $governorCandidates[1]['nm_candidato'] . '.';
    }
    if ($turno === 'todos') {
        $modeHighlights[] = 'Este modo considera somente os candidatos eleitos.';
    }

    $detalheCandidato = null;
    $votosPorMunicipio = [];
    $votosPorRegiaoCandidato = [];
    if ($candidato !== '') {
        foreach ($candidateInsights as $insight) {
            if ($insight['nm_candidato'] === $candidato) {
                $detalheCandidato = $insight;
                break;
            }
        }

        if ($detalheCandidato) {
            $candidateCityVoteRows = array_values(array_filter($cityVoteRows, function ($row) use ($detalheCandidato) {
                return $row['nm_candidato'] === $detalheCandidato['nm_candidato']
                    && $row['cargo'] === $detalheCandidato['cargo']
                    && (string) $row['nr_turno'] === (string) $detalheCandidato['nr_turno'];
            }));

            usort($candidateCityVoteRows, function ($a, $b) {
                return (int) $b['total_votos'] <=> (int) $a['total_votos'];
            });

            $votosPorMunicipio = array_map(function ($row) {
                return [
                    'municipio' => $row['municipio'],
                    'total_votos' => (int) $row['total_votos']
                ];
            }, $candidateCityVoteRows);

            $votosPorRegiaoCandidato = buildCandidateRegionSummary($candidateCityVoteRows, $regionLookup);

            $detalheCandidato['votosPorMunicipio'] = $votosPorMunicipio;
            $detalheCandidato['votosPorRegiao'] = $votosPorRegiaoCandidato;
        }
    }

    echo json_encode([
        'stats' => $stats,
        'candidatos' => $candidatos,
        'partidos' => $partidos,
        'cargos' => $cargos,
        'municipios' => $municipios,
        'votosPorRegiao' => $votosPorRegiao,
        'cityCandidateVotes' => $cityVoteRows,
        'candidateInsights' => $candidateInsights,
        'candidatosGovernador' => $governorCandidates,
        'detalheCandidato' => $detalheCandidato,
        'votosPorMunicipio' => $votosPorMunicipio,
        'votosPorRegiaoCandidato' => $votosPorRegiaoCandidato,
        'modeHighlights' => $modeHighlights,
        'ui' => $ui,
        'filters' => [
            'turno' => $turno,
            'cargo' => $cargo,
            'candidato' => $candidato,
            'partido' => $partido,
            'situacao' => $situacao,
            'municipio' => $municipio
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
