<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/premium_helpers.php';

premium_ensure_campaign_allied_parties_table($conn);

$user = premium_require_user($conn, true);
$isAdmin = premium_is_admin_user($user);
$action = trim((string) ($_GET['action'] ?? 'summary'));

$campaignId = (int) ($_GET['campaign_id'] ?? ($_SESSION['premium_campaign_id'] ?? 0));
$campaign = $campaignId > 0 ? premium_get_campaign($conn, $campaignId, (int) $user['id'], $isAdmin) : null;

if (!$campaign) {
    $campaign = premium_active_campaign($conn, (int) $user['id'], $isAdmin);
    if ($campaign) {
        $campaignId = (int) $campaign['id'];
    }
}

if ($action === 'search_senate_sources') {
    if (!$campaign || !premium_is_senate_cargo((string) ($campaign['candidate_cargo'] ?? ''))) {
        echo json_encode([
            'error' => true,
            'message' => 'Selecione uma campanha de senador para buscar fontes.',
            'results' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $query = trim((string) ($_GET['query'] ?? ''));
    $yearsRaw = trim((string) ($_GET['years'] ?? '2018,2020,2022,2024'));
    $years = array_values(array_filter(array_map('intval', preg_split('/[,\s]+/', $yearsRaw) ?: []), static fn(int $year): bool => in_array($year, [2018, 2020, 2022, 2024], true)));
    if (!$years) {
        $years = [2018, 2020, 2022, 2024];
    }

    $cargoFilter = trim((string) ($_GET['cargo_filter'] ?? 'all'));
    $municipality = trim((string) ($_GET['municipality'] ?? ''));
    $alliedOnly = (string) ($_GET['allied_only'] ?? '') === '1';
    $campaignAlliedParties = $alliedOnly ? premium_get_campaign_allied_party_acronyms($conn, (int) $campaign['id']) : [];
    $hasFilters = ($cargoFilter !== '' && $cargoFilter !== 'all') || $municipality !== '' || ($alliedOnly && $campaignAlliedParties !== []);

    $results = [];
    if ($alliedOnly && $campaignAlliedParties === []) {
        $results = [];
    } elseif ($query !== '' || $hasFilters) {
        foreach (premium_search_historical_candidates($conn, $query, $years, [
            'cargo_filter' => $cargoFilter,
            'municipality' => $municipality,
            'ally_parties' => $campaignAlliedParties,
        ]) as $source) {
            $relationshipType = premium_senate_guess_relationship($source, $campaign);
            $source['relationship_type'] = $relationshipType;
            $source['relationship_label'] = premium_senate_relationship_label($relationshipType);
            $source['transfer_rate'] = premium_senate_default_transfer_rate($relationshipType, (string) ($source['source_cargo'] ?? ''), (int) ($source['source_year'] ?? 0));
            $source['confidence_score'] = premium_senate_confidence_for_source($source, $relationshipType, $campaign);
            $results[] = $source;
        }
    }

    echo json_encode([
        'error' => false,
        'results' => $results,
        'filters' => [
            'query' => $query,
            'years' => $years,
            'cargo_filter' => $cargoFilter,
            'municipality' => $municipality,
            'allied_only' => $alliedOnly,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($action === 'suggest_senate_sources') {
    if (!$campaign || !premium_is_senate_cargo((string) ($campaign['candidate_cargo'] ?? ''))) {
        echo json_encode([
            'error' => true,
            'message' => 'Selecione uma campanha de senador para sugerir fontes.',
            'results' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode([
        'error' => false,
        'results' => premium_suggest_senate_vote_sources($conn, $campaign),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($action === 'search_leaders') {
    $cargo = trim((string) ($_GET['cargo'] ?? 'Prefeito'));
    $municipio = trim((string) ($_GET['municipio'] ?? ''));
    $query = trim((string) ($_GET['query'] ?? ''));
    $turno = max(1, (int) ($_GET['turno'] ?? 1));
    $alliedOnly = (string) ($_GET['allied_only'] ?? '') === '1';
    $campaignAlliedParties = ($alliedOnly && $campaign) ? premium_get_campaign_allied_party_acronyms($conn, (int) $campaign['id']) : [];
    $results = $alliedOnly && $campaignAlliedParties === []
        ? []
        : premium_search_2024_candidates($conn, $cargo, $municipio, $query, $turno, [
            'ally_parties' => $campaignAlliedParties,
        ]);

    echo json_encode([
        'error' => false,
        'results' => $results,
        'filters' => [
            'cargo' => $cargo,
            'municipio' => $municipio,
            'query' => $query,
            'turno' => $turno,
            'allied_only' => $alliedOnly,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$summary = [
    'error' => false,
    'message' => 'Nenhuma campanha ativa encontrada.',
    'user' => [
        'id' => (int) $user['id'],
        'name' => (string) ($user['name'] ?? ''),
        'email' => (string) ($user['email'] ?? ''),
    ],
    'campaigns' => premium_get_campaigns($conn, (int) $user['id']),
    'campaign' => null,
    'baseline' => [
        'candidate_name' => '',
        'cargo' => '',
        'candidate_number' => null,
        'total_votes' => 0,
        'municipalities' => [],
        'regions' => [],
        'found' => false,
        'municipality_count' => 0,
    ],
    'leaders' => [],
    'agenda' => [],
    'settings' => premium_default_settings(),
    'forecast' => [
        'settings' => premium_default_settings(),
        'totals' => [
            'baseline_votes' => 0,
            'leader_effect' => 0,
            'projected_conservative' => 0,
            'projected_base' => 0,
            'projected_optimistic' => 0,
            'delta_base' => 0,
            'delta_conservative' => 0,
            'delta_optimistic' => 0,
        ],
        'regions' => [],
        'cities' => [],
        'leaders' => [],
    ],
    'regions' => premium_region_definitions(),
];

if ($campaign) {
    $settings = premium_load_campaign_settings($conn, (int) $campaign['id']);
    $baselineYear = premium_resolve_baseline_year((int) ($campaign['baseline_year'] ?? 2022));
    $baseline = premium_candidate_baseline($conn, (string) ($campaign['candidate_name'] ?? ''), (string) ($campaign['candidate_cargo'] ?? ''), $baselineYear);
    $leaders = premium_get_campaign_leaders($conn, (int) $campaign['id']);
    $agenda = premium_load_agenda($conn, (int) $campaign['id']);
    $forecast = premium_build_forecast($baseline, $leaders, $settings);

    $summary = [
        'error' => false,
        'message' => 'Resumo premium carregado com sucesso.',
        'user' => [
            'id' => (int) $user['id'],
            'name' => (string) ($user['name'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
        ],
        'campaigns' => premium_get_campaigns($conn, (int) $user['id']),
        'campaign' => $campaign,
        'baseline' => $baseline,
        'leaders' => $leaders,
        'agenda' => $agenda,
        'settings' => $settings,
        'forecast' => $forecast,
        'regions' => premium_region_definitions(),
    ];
}

echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
