<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/premium_helpers.php';

$user = premium_require_user($conn, true);
$action = trim((string) ($_GET['action'] ?? 'summary'));

$campaignId = (int) ($_GET['campaign_id'] ?? ($_SESSION['premium_campaign_id'] ?? 0));
$campaign = $campaignId > 0 ? premium_get_campaign($conn, $campaignId, (int) $user['id']) : null;

if (!$campaign) {
    $campaign = premium_active_campaign($conn, (int) $user['id']);
    if ($campaign) {
        $campaignId = (int) $campaign['id'];
    }
}

if ($action === 'search_leaders') {
    $cargo = trim((string) ($_GET['cargo'] ?? 'Prefeito'));
    $municipio = trim((string) ($_GET['municipio'] ?? ''));
    $query = trim((string) ($_GET['query'] ?? ''));
    $turno = max(1, (int) ($_GET['turno'] ?? 1));

    echo json_encode([
        'error' => false,
        'results' => premium_search_2024_candidates($conn, $cargo, $municipio, $query, $turno),
        'filters' => [
            'cargo' => $cargo,
            'municipio' => $municipio,
            'query' => $query,
            'turno' => $turno,
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
    $baseline = premium_candidate_baseline($conn, (string) ($campaign['candidate_name'] ?? ''), (string) ($campaign['candidate_cargo'] ?? ''));
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

