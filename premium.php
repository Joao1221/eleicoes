<?php

declare(strict_types=1);

require_once __DIR__ . '/premium_helpers.php';
require_once __DIR__ . '/premium_advisor_helpers.php';

premium_ensure_campaign_photo_column($conn);
premium_ensure_campaign_access_table($conn);
premium_ensure_campaign_allied_parties_table($conn);

function premium_fmt_int(int $value): string
{
    return number_format($value, 0, ',', '.');
}

function premium_fmt_percent(float $value, int $decimals = 2): string
{
    return number_format($value, $decimals, ',', '.') . '%';
}

function premium_render_region_select_options(string $selectedRegion = ''): string
{
    $html = [];
    foreach (premium_region_choices() as $regionName) {
        $selected = $regionName === $selectedRegion ? ' selected' : '';
        $html[] = '<option value="' . premium_escape_html($regionName) . '"' . $selected . '>' . premium_escape_html($regionName) . '</option>';
    }

    return implode('', $html);
}

function premium_render_municipality_options(string $selectedMunicipality = ''): string
{
    $items = [];
    foreach (premium_region_definitions() as $regionName => $municipios) {
        foreach ($municipios as $municipio) {
            $items[] = [
                'municipio' => $municipio,
                'region' => $regionName,
            ];
        }
    }

    usort($items, static function (array $a, array $b): int {
        $cityCompare = strcmp(premium_normalize_text((string) ($a['municipio'] ?? '')), premium_normalize_text((string) ($b['municipio'] ?? '')));
        if ($cityCompare !== 0) {
            return $cityCompare;
        }

        return strcmp((string) ($a['region'] ?? ''), (string) ($b['region'] ?? ''));
    });

    $html = [];
    foreach ($items as $item) {
        $municipio = (string) ($item['municipio'] ?? '');
        $regionName = (string) ($item['region'] ?? '');
        $selected = $municipio === $selectedMunicipality ? ' selected' : '';
        $html[] = '<option value="' . premium_escape_html($municipio) . '" data-region="' . premium_escape_html($regionName) . '"' . $selected . '>' . premium_escape_html($municipio) . '</option>';
    }

    return implode('', $html);
}

function premium_render_region_options(string $selectedMunicipality = ''): string
{
    return premium_render_municipality_options($selectedMunicipality);
}

function premium_render_region_select(string $name, string $id, string $selected = '', bool $required = false): string
{
    $requiredAttr = $required ? ' required' : '';

    return '<select name="' . premium_escape_html($name) . '" id="' . premium_escape_html($id) . '"' . $requiredAttr . '>'
        . '<option value="">Selecione</option>'
        . premium_render_region_select_options($selected)
        . '</select>';
}

function premium_render_allied_party_selector(array $availableParties, array $selectedParties = [], string $fieldName = 'allied_parties'): string
{
    $selectedKeys = array_flip(premium_party_filter_keys($selectedParties));
    $selectedCount = count(premium_normalize_party_list($selectedParties));
    $fieldName = trim($fieldName) !== '' ? trim($fieldName) : 'allied_parties';
    $html = [];

    $html[] = '<fieldset class="party-selector" data-party-selector>';
    $html[] = '  <input type="hidden" name="allied_parties_present" value="1">';
    $html[] = '  <legend>Partidos aliados</legend>';
    $html[] = '  <div class="party-selector__head">';
    $html[] = '    <div>';
    $html[] = '      <p>Opcional. A lista selecionada ajuda a filtrar lideranças e fontes por alinhamento partidário.</p>';
    $html[] = '    </div>';
    $html[] = '    <div class="party-selector__tools">';
    $html[] = '      <span class="party-selector__count" data-party-selector-count>' . $selectedCount . ' selecionado' . ($selectedCount === 1 ? '' : 's') . '</span>';
    $html[] = '      <button class="btn ghost btn-small" type="button" data-party-selector-action="all">Selecionar todos</button>';
    $html[] = '      <button class="btn ghost btn-small" type="button" data-party-selector-action="clear">Limpar</button>';
    $html[] = '    </div>';
    $html[] = '  </div>';
    $html[] = '  <div class="party-selector__grid">';

    foreach ($availableParties as $party) {
        $acronym = (string) ($party['acronym'] ?? '');
        if ($acronym === '') {
            continue;
        }

        $partyKey = premium_normalize_text($acronym);
        $checked = isset($selectedKeys[$partyKey]) ? ' checked' : '';
        $number = (string) ($party['number'] ?? '');
        $name = (string) ($party['name'] ?? '');
        $spectrum = (string) ($party['spectrum'] ?? '');

        $html[] = '    <label class="party-option">';
        $html[] = '      <input type="checkbox" name="' . premium_escape_html($fieldName) . '[]" value="' . premium_escape_html($acronym) . '"' . $checked . '>';
        $html[] = '      <span class="party-option__body">';
        $html[] = '        <span class="party-option__top"><strong>' . premium_escape_html($acronym) . '</strong><em>' . premium_escape_html($number) . '</em></span>';
        $html[] = '        <span class="party-option__name">' . premium_escape_html($name) . '</span>';
        $html[] = '        <span class="party-option__meta">' . premium_escape_html($spectrum) . '</span>';
        $html[] = '      </span>';
        $html[] = '    </label>';
    }

    $html[] = '  </div>';
    $html[] = '</fieldset>';

    return implode("\n", $html);
}

function premium_read_markdown_excerpt(string $relativePath, string $startHeading, string $endHeading, int $maxLines = 120): string
{
    $path = __DIR__ . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
    if (!is_file($path)) {
        return '';
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        return '';
    }

    $normalizedContents = str_replace(["\r\n", "\r"], "\n", (string) $contents);
    $lines = explode("\n", $normalizedContents);
    if (!$lines) {
        return '';
    }

    $capturing = false;
    $buffer = [];

    foreach ($lines as $line) {
        $line = (string) $line;
        $normalizedLine = trim($line);

        if (!$capturing) {
            if ($normalizedLine === $startHeading) {
                $capturing = true;
            }
            continue;
        }

        if (preg_match($endHeading, $normalizedLine) === 1) {
            break;
        }

        $buffer[] = $line;
        if (count($buffer) >= $maxLines) {
            break;
        }
    }

    if (!$buffer) {
        $buffer = array_slice($lines, 0, $maxLines);
    }

    return trim(implode("\n", $buffer));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'login') {
    $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

    if (!premium_validate_csrf($_POST['csrf'] ?? null)) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Sessão expirada. Recarregue a página.']);
            exit;
        }
        premium_flash('error', 'Sua sessão expirou. Recarregue a página e tente novamente.');
        header('Location: premium');
        exit;
    }

    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (premium_login($conn, $email, $password)) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        premium_flash('success', 'Acesso premium liberado.');
    } else {
        if ($isAjax) {
            $errorMessage = 'E-mail ou senha incorretos.';
            premium_pull_flash();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $errorMessage]);
            exit;
        }
    }

    header('Location: premium');
    exit;
}

$user = premium_current_user($conn);
$isAdmin = premium_is_admin_user($user);
$accessBadgeLabel = $user ? 'Acesso premium' : null;

if ($user && isset($_GET['campaign_id'])) {
    $requestedCampaignId = (int) $_GET['campaign_id'];
    if ($requestedCampaignId > 0 && premium_get_campaign($conn, $requestedCampaignId, (int) $user['id'], $isAdmin)) {
        premium_set_active_campaign($requestedCampaignId);
    }
}

$flash = premium_pull_flash();
$csrf = premium_csrf_token();
$premiumWhatsappPhone = '5579999248114';
$premiumWhatsappMessage = 'Olá! Vim pelo Apoia Candidato Premium e quero agendar uma apresentação para entender como o sistema pode ajudar minha campanha com projeções, lideranças, agenda e relatórios.';
$premiumWhatsappUrl = 'https://wa.me/' . $premiumWhatsappPhone . '?text=' . rawurlencode($premiumWhatsappMessage);

$campaigns = [];
$campaign = null;
$settings = premium_default_settings();
$baseline = [
    'candidate_name' => '',
    'cargo' => '',
    'candidate_number' => null,
    'total_votes' => 0,
    'municipalities' => [],
    'regions' => [],
    'found' => false,
    'municipality_count' => 0,
];
$leaders = [];
$availableParties = premium_available_parties($conn);
$campaignAlliedParties = [];
$campaignAlliedPartyAcronyms = [];
$agenda = [];
$agendaSummary = [
    'total' => 0,
    'open' => 0,
    'doing' => 0,
    'done' => 0,
    'archived' => 0,
];
$agendaPendingPreview = [];
$forecast = [
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
];
$isSenateCampaign = false;
$senateSources = [];
$senateSuggestions = [];
$senateSearchResults = [];
$senateForecast = premium_empty_senate_forecast(premium_default_settings());
$reportForecast = $forecast;
$advisor = null;
$baselinePanelHidden = false;
$settingsPanelHidden = false;
$premiumCampaigns = [];
$campaignBaselineYear = 2022;
$campaignBaselineLabel = premium_baseline_label($campaignBaselineYear);
$onboardingStudyExcerpt = premium_read_markdown_excerpt(
    'docs/explicacao_previsao_transferencia_premium.md',
    '## 1. Ideia central',
    '/^##\\s+4\\./u',
    140
);

if ($onboardingStudyExcerpt === '') {
    $onboardingStudyExcerpt = "O sistema parte de uma taxa inicial de transferência e depois ajusta essa taxa com fatores políticos.\n\nOs valores padrão mostram a lógica do modelo e servem como ponto de partida para a campanha.";
}

if ($user) {
    $campaigns = premium_get_campaigns($conn, (int) $user['id']);
    $campaign = premium_active_campaign($conn, (int) $user['id'], $isAdmin);

    if ($campaign) {
        $campaignBaselineYear = premium_resolve_baseline_year((int) ($campaign['baseline_year'] ?? 2022));
        $campaignBaselineLabel = premium_baseline_label($campaignBaselineYear);
        premium_set_active_campaign((int) $campaign['id']);
        $settings = premium_load_campaign_settings($conn, (int) $campaign['id']);
        $campaignMembers = premium_get_campaign_members($conn, (int) $campaign['id']);
        $campaignAlliedParties = premium_get_campaign_allied_parties($conn, (int) $campaign['id']);
        $campaignAlliedPartyAcronyms = array_values(array_filter(array_map(
            static fn(array $party): string => (string) ($party['party_acronym'] ?? $party['acronym'] ?? ''),
            $campaignAlliedParties
        )));
        $baseline = premium_candidate_baseline($conn, (string) ($campaign['candidate_name'] ?? ''), (string) ($campaign['candidate_cargo'] ?? ''), $campaignBaselineYear);
        $leaders = premium_get_campaign_leaders($conn, (int) $campaign['id']);
        $agenda = premium_load_agenda($conn, (int) $campaign['id']);
        foreach ($agenda as $agendaItem) {
            $status = (string) ($agendaItem['status'] ?? 'open');
            if (isset($agendaSummary[$status])) {
                $agendaSummary[$status]++;
            }

            $agendaSummary['total']++;

            if (in_array($status, ['open', 'doing'], true)) {
                $agendaPendingPreview[] = $agendaItem;
            }
        }
        $agendaPendingPreview = array_slice($agendaPendingPreview, 0, 5);
        $forecast = premium_build_forecast($baseline, $leaders, $settings);
        $isSenateCampaign = premium_is_senate_cargo((string) ($campaign['candidate_cargo'] ?? ''));
        if ($isSenateCampaign) {
            $senateSources = premium_get_senate_vote_sources($conn, (int) $campaign['id']);
            $senateForecast = premium_build_senate_forecast($conn, $campaign, $senateSources, $settings);

            $senateQuery = trim((string) ($_GET['senate_query'] ?? ''));
            $senateSourceCargo = trim((string) ($_GET['senate_source_cargo'] ?? 'all'));
            $senateMunicipality = trim((string) ($_GET['senate_municipality'] ?? ''));
            $senateSourceYear = trim((string) ($_GET['senate_source_year'] ?? ''));
            $senateAlliedOnly = (string) ($_GET['senate_allied_only'] ?? '') === '1';
            $hasSenateSearchFilter = $senateSourceCargo !== '' && $senateSourceCargo !== 'all'
                || $senateMunicipality !== ''
                || ($senateAlliedOnly && $campaignAlliedPartyAcronyms !== []);
            if ($senateQuery !== '' || $hasSenateSearchFilter) {
                $senateYearFilter = $senateSourceYear !== '' ? [(int) $senateSourceYear] : [2018, 2020, 2022, 2024];
                $senateCandidateSources = $senateAlliedOnly && $campaignAlliedPartyAcronyms === []
                    ? []
                    : premium_search_historical_candidates($conn, $senateQuery, $senateYearFilter, [
                        'cargo_filter' => $senateSourceCargo,
                        'municipality' => $senateMunicipality,
                        'ally_parties' => $senateAlliedOnly ? $campaignAlliedPartyAcronyms : [],
                    ]);
                foreach ($senateCandidateSources as $source) {
                    $relationshipType = premium_senate_guess_relationship($source, $campaign);
                    $source['relationship_type'] = $relationshipType;
                    $source['relationship_label'] = premium_senate_relationship_label($relationshipType);
                    $source['transfer_rate'] = premium_senate_default_transfer_rate($relationshipType, (string) ($source['source_cargo'] ?? ''), (int) ($source['source_year'] ?? 0));
                    $source['confidence_score'] = premium_senate_confidence_for_source($source, $relationshipType, $campaign);
                    $senateSearchResults[] = $source;
                }
            }
        }
        $reportForecast = $isSenateCampaign ? $senateForecast : $forecast;
        $advisor = premium_build_campaign_advisor($campaign, $baseline, $leaders, $forecast, $settings);
        $baselinePanelHidden = !empty($campaign['baseline_panel_hidden']);
        $settingsPanelHidden = !empty($campaign['settings_panel_hidden']);
    }

    if ($isAdmin) {
        $premiumCampaigns = premium_get_all_campaigns($conn);

        $campaignPerPage = 5;
        $campaignPage = max(1, (int) ($_GET['campaign_page'] ?? 1));
        $totalCampaignPages = $premiumCampaigns ? (int) ceil(count($premiumCampaigns) / $campaignPerPage) : 1;
        $campaignPage = min($campaignPage, $totalCampaignPages);
        $pagedCampaigns = array_slice($premiumCampaigns, ($campaignPage - 1) * $campaignPerPage, $campaignPerPage);
    }
}
$premiumUsers = [];
$premiumUserSummary = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
];

if ($isAdmin) {
    $premiumUsers = premium_get_users($conn);
    $premiumUserSummary['total'] = count($premiumUsers);

    foreach ($premiumUsers as $premiumUser) {
        $status = (string) ($premiumUser['status'] ?? 'inactive');
        if ($status === 'active') {
            $premiumUserSummary['active']++;
        } else {
            $premiumUserSummary['inactive']++;
        }
    }

    $userPerPage = 5;
    $userPage = max(1, (int) ($_GET['user_page'] ?? 1));
    $totalUserPages = $premiumUsers ? (int) ceil(count($premiumUsers) / $userPerPage) : 1;
    $userPage = min($userPage, $totalUserPages);
    $pagedUsers = array_slice($premiumUsers, ($userPage - 1) * $userPerPage, $userPerPage);
}

$allowedTabs = ['home', 'agenda', 'relatorios', 'opcoes'];
if ($isSenateCampaign) {
    $allowedTabs[] = 'senado';
} else {
    $allowedTabs[] = 'liderancas';
}
$activeTab = trim((string) ($_GET['tab'] ?? ($campaign ? 'home' : 'opcoes')));
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = $isSenateCampaign && $activeTab === 'liderancas' ? 'senado' : ($campaign ? 'home' : 'opcoes');
}

function premium_selected_campaign_label(?array $campaign): string
{
    if (!$campaign) {
        return 'Nenhuma campanha ativa';
    }

    $parts = [
        (string) ($campaign['campaign_name'] ?? 'Campanha'),
        (string) ($campaign['candidate_name'] ?? ''),
        (string) ($campaign['candidate_cargo'] ?? ''),
    ];

    return trim(implode(' • ', array_filter($parts, static fn(string $item): bool => $item !== '')));
}

function premium_selected_campaign_subtitle(?array $campaign, ?int $candidateNumber = null): string
{
    if (!$campaign) {
        return '';
    }

    $parts = [];
    $cargo = trim((string) ($campaign['candidate_cargo'] ?? ''));
    if ($cargo !== '') {
        $parts[] = $cargo;
    }

    if ($candidateNumber === null) {
        $candidateNumber = premium_parse_candidate_number($campaign['candidate_number'] ?? null);
    }

    $formattedNumber = premium_fmt_candidate_number_plain($candidateNumber);
    if ($formattedNumber !== '') {
        $parts[] = $formattedNumber;
    }

    return trim(implode(' • ', $parts));
}

function premium_render_campaign_options(array $campaigns, ?array $selectedCampaign): string
{
    $selectedId = (int) ($selectedCampaign['id'] ?? 0);
    $html = ['<option value="">Selecione uma campanha</option>'];

    foreach ($campaigns as $campaign) {
        $campaignId = (int) ($campaign['id'] ?? 0);
        $selected = $campaignId === $selectedId ? ' selected' : '';
        $label = trim((string) ($campaign['campaign_name'] ?? 'Campanha') . ' • ' . (string) ($campaign['candidate_name'] ?? '') . ' • ' . (string) ($campaign['candidate_cargo'] ?? ''));
        $html[] = '<option value="' . $campaignId . '"' . $selected . '>' . premium_escape_html($label) . '</option>';
    }

    return implode('', $html);
}

function premium_render_stat(string $label, string $value, string $sub = ''): string
{
    return '<div class="stat-card"><div class="stat-label">' . premium_escape_html($label) . '</div><div class="stat-value">' . premium_escape_html($value) . '</div>'
        . ($sub !== '' ? '<div class="stat-sub">' . premium_escape_html($sub) . '</div>' : '')
        . '</div>';
}

function premium_size_label(string $sizeClass): string
{
    return match ($sizeClass) {
        'small' => 'Pequeno',
        'large' => 'Grande',
        default => 'Médio',
    };
}

function premium_render_leaders_table(array $leaders, int $baselineVotes = 0, int $forecast2026 = 0, array $settings = [], ?array $campaign = null, string $csrf = ''): string
{
    $settings = $settings ?: premium_default_settings();
    $baselineLabel = premium_baseline_label((int) ($campaign['baseline_year'] ?? 2022));
    $campaignBaselineYear = (int) ($campaign['baseline_year'] ?? 2022);
    $leaders = array_values($leaders);
    usort($leaders, static function (array $a, array $b): int {
        $cityCompare = strcmp(
            premium_normalize_text((string) ($a['municipality'] ?? '')),
            premium_normalize_text((string) ($b['municipality'] ?? ''))
        );
        if ($cityCompare !== 0) {
            return $cityCompare;
        }

        $votesCompare = (int) ($b['leader_votes_2024'] ?? 0) <=> (int) ($a['leader_votes_2024'] ?? 0);
        if ($votesCompare !== 0) {
            return $votesCompare;
        }

        return strcmp(
            premium_normalize_text((string) ($a['leader_display_name'] ?? $a['leader_name'] ?? '')),
            premium_normalize_text((string) ($b['leader_display_name'] ?? $b['leader_name'] ?? ''))
        );
    });

    $cities = [];
    $parties = [];
    foreach ($leaders as &$leader) {
        $leaderType = (string) ($leader['leader_type'] ?? premium_leader_type_bucket((string) ($leader['leader_cargo'] ?? '')));
        $leader['leader_type'] = $leaderType;
        $leader['leader_type_label'] = (string) ($leader['leader_type_label'] ?? premium_leader_type_label($leaderType));

        $municipality = trim((string) ($leader['municipality'] ?? ''));
        if ($municipality !== '') {
            $cities[$municipality] = true;
        }

        $party = trim((string) ($leader['leader_party'] ?? ''));
        if ($party !== '') {
            $parties[$party] = true;
        }
    }
    unset($leader);

    ksort($cities, SORT_NATURAL | SORT_FLAG_CASE);
    $parties = $parties ?? [];
    ksort($parties, SORT_NATURAL | SORT_FLAG_CASE);

    $campaignId = (int) ($campaign['id'] ?? 0);
    $canDeleteLeader = $campaignId > 0 && trim($csrf) !== '';
    $leaderTotal = count($leaders);

    $html = [];
    $html[] = '<div class="leaders-table-shell">';
    $html[] = '<div class="leaders-table-toolbar">';
    $html[] = '  <div class="leaders-table-toolbar__meta">';
    $html[] = '    <div class="leaders-table-toolbar__title">Filtrar lideranças</div>';
    $html[] = '    <div class="leaders-table-toolbar__sub">Refine a lista por cidade, por tipo de candidato e por partido. "Liderança sem mandato" agrupa registros fora de prefeito e vereador.</div>';
    $html[] = '  </div>';
    $html[] = '  <div class="leaders-table-toolbar__filters">';
    $html[] = '    <label class="leaders-table-filter" for="activeLeadersCityFilter"><span>Cidade</span><select id="activeLeadersCityFilter"><option value="">Todas as cidades</option>';
    foreach (array_keys($cities) as $city) {
        $html[] = '      <option value="' . premium_escape_html($city) . '">' . premium_escape_html($city) . '</option>';
    }
    $html[] = '    </select></label>';
    $html[] = '    <label class="leaders-table-filter" for="activeLeadersTypeFilter"><span>Tipo de candidato</span><select id="activeLeadersTypeFilter"><option value="">Todos os tipos</option><option value="prefeito">Prefeito</option><option value="vereador">Vereador</option><option value="sem_mandato">Liderança sem mandato</option></select></label>';
    $html[] = '    <label class="leaders-table-filter" for="activeLeadersPartyFilter"><span>Partido</span><select id="activeLeadersPartyFilter"><option value="">Todos os partidos</option>';
    foreach (array_keys($parties) as $party) {
        $html[] = '      <option value="' . premium_escape_html($party) . '">' . premium_escape_html($party) . '</option>';
    }
    $html[] = '    </select></label>';
    $html[] = '    <button type="button" class="btn ghost btn-small" id="activeLeadersResetBtn">Limpar filtros</button>';
    $html[] = '  </div>';
    $html[] = '  <div class="leaders-table-toolbar__count">Mostrando <strong id="activeLeadersVisibleCount">' . premium_fmt_int($leaderTotal) . '</strong> de <strong id="activeLeadersTotalCount">' . premium_fmt_int($leaderTotal) . '</strong> lideranças</div>';
    $html[] = '</div>';
    if ($canDeleteLeader) {
        $html[] = '  <div class="leaders-table-toolbar__bulk">';
        $html[] = '    <div class="leaders-table-toolbar__bulk-item leaders-table-toolbar__bulk-item--transfer">';
        $html[] = '    <div class="leaders-table-toolbar__bulk-head">';
        $html[] = '      <div class="leaders-table-toolbar__bulk-title">Alterar transferência em lote</div>';
        $html[] = '      <div class="leaders-table-toolbar__bulk-sub">Defina um novo percentual e aplique aos registros selecionados, aos visíveis ou a toda a campanha.</div>';
        $html[] = '    </div>';
        $html[] = '    <div class="leaders-table-toolbar__bulk-sub" hidden id="leaderBulkTransferSub">Defina um novo percentual e aplique aos registros selecionados, aos visíveis ou a toda a campanha.</div>';
        $html[] = '    <button class="btn ghost btn-small" type="button" data-toggle-target="leaderBulkTransferForm" aria-controls="leaderBulkTransferForm" aria-expanded="false">Abrir</button>';
        $html[] = '    <form method="post" action="premium_actions.php" id="leaderBulkTransferForm" class="leaders-table-bulk-form leader-bulk-transfer-form" hidden>';
        $html[] = '      <input type="hidden" name="csrf" value="' . premium_escape_html($csrf) . '">';
        $html[] = '      <input type="hidden" name="action" value="update_leaders_transfer_batch">';
        $html[] = '      <input type="hidden" name="redirect_tab" value="liderancas">';
        $html[] = '      <input type="hidden" name="campaign_id" value="' . $campaignId . '">';
        $html[] = '      <input type="hidden" name="transfer_scope" id="leaderBulkTransferScope" value="selected">';
        $html[] = '      <input type="hidden" name="leaders_json" id="leaderBulkTransferPayload">';
        $html[] = '      <label class="leader-bulk-transfer-form__field" for="leaderBulkTransferValue">';
        $html[] = '        <span>Transferência %</span>';
        $html[] = '        <input type="number" name="transfer_rate" id="leaderBulkTransferValue" value="' . premium_escape_html((string) ($settings['transfer_rate_default'] ?? 30)) . '" min="0" max="100" step="0.01">';
        $html[] = '      </label>';
        $html[] = '      <div class="leader-bulk-transfer-form__actions">';
        $html[] = '        <button class="btn primary btn-small" type="submit" id="leaderBulkTransferSelectedBtn" data-bulk-transfer-scope="selected" disabled>Aplicar selecionadas</button>';
        $html[] = '        <button class="btn ghost btn-small" type="submit" id="leaderBulkTransferVisibleBtn" data-bulk-transfer-scope="visible">Aplicar visíveis</button>';
        $html[] = '        <button class="btn ghost btn-small" type="submit" id="leaderBulkTransferAllBtn" data-bulk-transfer-scope="all">Aplicar todas</button>';
        $html[] = '      </div>';
        $html[] = '    </form>';
        $html[] = '  </div>';
        $html[] = '  <div class="leaders-table-toolbar__bulk-item leaders-table-toolbar__bulk-item--delete">';
        $html[] = '    <div class="leaders-table-toolbar__bulk-copy">';
        $html[] = '      <div class="leaders-table-toolbar__bulk-title"><span id="leaderBulkSelectedCount">0 selecionadas</span></div>';
        $html[] = '      <div class="leaders-table-toolbar__bulk-sub">Marque uma ou mais lideranças para excluir em lote. A projeção será recalculada automaticamente após salvar.</div>';
        $html[] = '    </div>';
        $html[] = '    <button class="btn ghost btn-small" type="button" data-toggle-target="leaderBulkDeleteActions" aria-controls="leaderBulkDeleteActions" aria-expanded="false">Abrir</button>';
        $html[] = '    <div class="leaders-table-toolbar__bulk-actions" id="leaderBulkDeleteActions" hidden>';
        $html[] = '      <button type="button" class="btn ghost btn-small" id="leaderBulkSelectVisibleBtn">Selecionar visíveis</button>';
        $html[] = '      <button type="button" class="btn ghost btn-small" id="leaderBulkClearBtn">Limpar seleção</button>';
        $html[] = '      <form method="post" action="premium_actions.php" id="leaderBulkDeleteForm" class="leaders-table-bulk-form">';
        $html[] = '        <input type="hidden" name="csrf" value="' . premium_escape_html($csrf) . '">';
        $html[] = '        <input type="hidden" name="action" value="delete_leaders_batch">';
        $html[] = '        <input type="hidden" name="redirect_tab" value="liderancas">';
        $html[] = '        <input type="hidden" name="campaign_id" value="' . $campaignId . '">';
        $html[] = '        <input type="hidden" name="leaders_json" id="leaderBulkDeletePayload">';
        $html[] = '        <button class="btn danger btn-small" type="submit" id="leaderBulkDeleteBtn" disabled>Excluir selecionadas</button>';
        $html[] = '      </form>';
        $html[] = '    </div>';
        $html[] = '  </div>';
        $html[] = '  </div>';
    }
    $html[] = '<div class="leaders-summary">';
    $html[] = '  <div class="summary-metric">';
    $html[] = '    <div class="summary-metric__label">Dados da campanha ' . premium_escape_html($baselineLabel) . '</div>';
    $html[] = '    <div class="summary-metric__value" id="activeLeadersBaselineValue" data-default-value="' . $baselineVotes . '">' . premium_fmt_int($baselineVotes) . '</div>';
    $html[] = '    <div class="summary-metric__sub" id="activeLeadersBaselineSub">Total histórico do candidato nesta campanha</div>';
    $html[] = '  </div>';
    $html[] = '  <div class="summary-metric">';
    $html[] = '    <div class="summary-metric__label">Previsão 2026</div>';
    $html[] = '    <div class="summary-metric__value" id="activeLeadersForecastValue" data-default-value="' . $forecast2026 . '">' . premium_fmt_int($forecast2026) . '</div>';
    $html[] = '    <div class="summary-metric__sub" id="activeLeadersForecastSub">Cenário base calculado com os pesos atuais</div>';
    $html[] = '  </div>';
    $html[] = '</div>';
    $html[] = '<div class="empty-state" id="activeLeadersFilterEmpty" hidden>Nenhuma liderança corresponde aos filtros selecionados.</div>';
    $html[] = '<div id="activeLeadersRowsViewport" class="leaders-rows-viewport">';
    $html[] = '<br>';
    $html[] = '<table class="leaders-table">';
    $html[] = '<caption>Lideranças e projeção de votação em 2026</caption>';
    $html[] = '<thead><tr>';
    if ($canDeleteLeader) {
        $html[] = '<th class="leaders-table-select-cell"><input type="checkbox" id="activeLeadersSelectAll" aria-label="Selecionar todas as lideranças visíveis"></th>';
    }
    $html[] = '<th>Região</th>';
    $html[] = '<th>Município</th>';
    $html[] = '<th>Liderança</th>';
    $html[] = '<th>Votos</th>';
    $html[] = '<th>Transferência %</th>';
    $html[] = '<th>Base transferível</th>';
    $html[] = '<th>Projeção 2026</th>';
    $html[] = '<th>Ação</th>';
    $html[] = '</tr></thead><tbody>';

    foreach ($leaders as $leader) {
        $leaderId = (int) ($leader['id'] ?? 0);
        $regionName = (string) ($leader['region_name'] ?? 'Sem região');
        $municipality = (string) ($leader['municipality'] ?? '');
        $leaderName = (string) ($leader['leader_display_name'] ?? $leader['leader_name'] ?? 'Liderança');
        $leaderCargo = (string) ($leader['leader_cargo'] ?? '');
        $leaderParty = (string) ($leader['leader_party'] ?? '');
        $leaderType = (string) ($leader['leader_type'] ?? premium_leader_type_bucket($leaderCargo));
        $votes2024 = (int) ($leader['leader_votes_2024'] ?? 0);
        $votesYear = premium_leader_votes_election_year($leaderCargo, $campaignBaselineYear);
        $transferRate = (float) ($leader['transfer_rate'] ?? 0);
        $projection = premium_apply_transfer_multiplier($leader, $settings);
        $baseEffect = (int) ($projection['base_effect'] ?? 0);
        $projectedVotes = (int) ($projection['projected_votes'] ?? 0);

        $html[] = '<tr class="leaders-table-row" data-active-leader-row data-leader-municipality="' . premium_escape_html($municipality) . '" data-leader-type="' . premium_escape_html($leaderType) . '" data-leader-party="' . premium_escape_html($leaderParty) . '">';
        if ($canDeleteLeader) {
            $html[] = '<td class="leaders-table-select-cell"><input type="checkbox" class="leader-bulk-checkbox" value="' . $leaderId . '" aria-label="Selecionar liderança ' . premium_escape_html($leaderName) . '"></td>';
        }
        $html[] = '<td><span class="table-pill">' . premium_escape_html($regionName) . '</span></td>';
        $html[] = '<td class="leaders-table-city-cell"><span class="leaders-table-city">' . premium_escape_html($municipality) . '</span></td>';
        $html[] = '<td class="leaders-table-leader-cell"><span class="leaders-table-leader-content">';
        $html[] = '<button type="button" class="leader-open-btn leader-open-btn--compact" data-leader-id="' . $leaderId . '" title="' . premium_escape_html($leaderName) . '">' . premium_escape_html($leaderName) . '</button>';
        if ($leaderCargo !== '') {
            $html[] = '<span class="leaders-table-leader-cargo">' . premium_escape_html($leaderCargo) . '</span>';
        }
        if ($leaderParty !== '') {
            $html[] = '<span class="leaders-table-leader-meta">' . premium_escape_html($leaderParty) . '</span>';
        }
        $html[] = '</span></td>';
        $html[] = '<td>' . (!empty($leader['is_manual_projection']) ? '-' : premium_fmt_int($votes2024) . '<span class="leaders-table-leader-cargo">Eleição ' . $votesYear . '</span>') . '</td>';
        $html[] = '<td>' . premium_fmt_percent($transferRate) . '</td>';
        $html[] = '<td>' . premium_fmt_int($baseEffect) . '</td>';
        $html[] = '<td>' . premium_fmt_int($projectedVotes) . '</td>';
        $html[] = '<td class="leaders-table-action-cell"><div class="leaders-table-actions"><button type="button" class="btn ghost btn-small leader-open-btn" data-leader-id="' . $leaderId . '">Abrir</button>';
        if ($canDeleteLeader) {
            $html[] = '<form method="post" action="premium_actions.php" class="leaders-table-action-form" onsubmit="return confirm(\'Remover esta liderança da campanha?\');">';
            $html[] = '  <input type="hidden" name="csrf" value="' . premium_escape_html($csrf) . '">';
            $html[] = '  <input type="hidden" name="action" value="delete_leader">';
            $html[] = '  <input type="hidden" name="redirect_tab" value="liderancas">';
            $html[] = '  <input type="hidden" name="campaign_id" value="' . $campaignId . '">';
            $html[] = '  <input type="hidden" name="leader_id" value="' . $leaderId . '">';
            $html[] = '  <button class="btn danger btn-small leader-delete-icon-btn" type="submit" aria-label="Excluir liderança" title="Excluir liderança">';
            $html[] = '    <svg viewBox="0 0 24 24" class="leader-delete-icon" aria-hidden="true" focusable="false">';
            $html[] = '      <path d="M4 7h16M9 7V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V7m-8 0 .8 11a2 2 0 0 0 2 1.8h4.4a2 2 0 0 0 2-1.8L17 7m-7 4v6m4-6v6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />';
            $html[] = '    </svg>';
            $html[] = '  </button>';
            $html[] = '</form>';
        }
        $html[] = '</div></td>';
        $html[] = '</tr>';
    }

    $html[] = '</tbody></table></div>';

    return implode('', $html);
}

function premium_render_leader_modal(?array $campaign, string $csrf): string
{
    if (!$campaign) {
        return '';
    }

    $campaignId = (int) ($campaign['id'] ?? 0);
    $html = [];
    $html[] = '<div class="leader-modal" id="leaderModal" hidden aria-hidden="true">';
    $html[] = '  <div class="leader-modal__backdrop" data-modal-close></div>';
    $html[] = '  <div class="leader-modal__panel" role="dialog" aria-modal="true" aria-labelledby="leaderModalTitle">';
    $html[] = '    <div class="leader-modal__header">';
    $html[] = '      <div>';
    $html[] = '        <div class="eyebrow">Detalhes da liderança</div>';
    $html[] = '        <h3 id="leaderModalTitle">Selecione uma liderança</h3>';
    $html[] = '        <p class="muted" id="leaderModalSubtitle">Clique em uma liderança na tabela para abrir todos os dados e editar sem sair da tela.</p>';
    $html[] = '      </div>';
    $html[] = '      <button type="button" class="btn ghost" data-modal-close>Fechar</button>';
    $html[] = '    </div>';
    $html[] = '    <div class="leader-modal__summary" id="leaderModalSummary"></div>';
    $html[] = '    <form method="post" action="premium_actions.php" class="leader-form" id="leaderModalForm">';
    $html[] = '      <input type="hidden" name="csrf" value="' . premium_escape_html($csrf) . '">';
    $html[] = '      <input type="hidden" name="action" value="update_leader">';
    $html[] = '      <input type="hidden" name="redirect_tab" value="liderancas">';
    $html[] = '      <input type="hidden" name="campaign_id" value="' . $campaignId . '">';
    $html[] = '      <input type="hidden" name="leader_id" id="modalLeaderId" value="">';
    $html[] = '      <div class="form-grid compact modal-grid">';
    $html[] = '        <label>Região';
    $html[] = '          ' . premium_render_region_select('region_name', 'modalLeaderRegion', '', true);
    $html[] = '        </label>';
    $html[] = '        <label>Município';
    $html[] = '          <select name="municipality" id="modalLeaderMunicipality" required onchange="syncLeaderRegionFromMunicipality(this, \'modalLeaderRegion\')">';
    $html[] = '            <option value="">Selecione</option>';
    $html[] = '            ' . premium_render_municipality_options();
    $html[] = '          </select>';
    $html[] = '        </label>';
    $html[] = '        <label>Nome da urna';
    $html[] = '          <input type="text" name="leader_name" id="modalLeaderName" required>';
    $html[] = '        </label>';
    $html[] = '        <label>Cargo';
    $html[] = '          <input type="text" name="leader_cargo" id="modalLeaderCargo" placeholder="Prefeito ou Vereador" required>';
    $html[] = '        </label>';
    $html[] = '        <label>Partido';
    $html[] = '          <input type="text" name="leader_party" id="modalLeaderParty">';
    $html[] = '        </label>';
    $html[] = '        <label>Votos 2024';
    $html[] = '          <input type="number" name="leader_votes_2024" id="modalLeaderVotes" value="0" min="0" step="1">';
    $html[] = '        </label>';
    $html[] = '        <label>Margem %';
    $html[] = '          <input type="number" name="margin_percent" id="modalLeaderMargin" value="0" min="0" step="0.01">';
    $html[] = '          <span class="field-help">Diferença entre o primeiro e o segundo colocado no município. Quanto maior a margem, maior a folga política da liderança.</span>';
    $html[] = '        </label>';
    $html[] = '        <label>Transferência %';
        $html[] = '          <input type="number" name="transfer_rate" id="modalLeaderTransfer" value="' . premium_escape_html((string) premium_default_settings()['transfer_rate_default']) . '" min="0" max="100" step="0.01">';
    $html[] = '          <span class="field-help">Percentual da votação desta liderança que pode migrar para o candidato. É o motor principal da projeção.</span>';
    $html[] = '        </label>';
    $html[] = '        <label>Visibilidade';
    $html[] = '          <input type="number" name="visibility_score" id="modalLeaderVisibility" value="50" min="0" max="100" step="0.01">';
    $html[] = '          <span class="field-help">Mede presença pública, reconhecimento e força de comunicação da liderança no território.</span>';
    $html[] = '        </label>';
    $html[] = '        <label>Investimento';
    $html[] = '          <input type="number" name="investment_score" id="modalLeaderInvestment" value="50" min="0" max="100" step="0.01">';
    $html[] = '          <span class="field-help">Avalia a associação da liderança com entregas, obras e ações visíveis que podem converter capital político em voto.</span>';
    $html[] = '        </label>';
    $html[] = '        <label>Tamanho';
    $html[] = '          <select name="size_class" id="modalLeaderSizeClass">';
    $html[] = '            <option value="small">Pequeno</option>';
    $html[] = '            <option value="medium">Médio</option>';
    $html[] = '            <option value="large">Grande</option>';
    $html[] = '          </select>';
    $html[] = '          <span class="field-help">Classificação do município usada para ajustar o peso da liderança. Municípios menores tendem a transferir melhor voto.</span>';
    $html[] = '        </label>';
    $html[] = '        <label class="checkbox modal-checkbox"><input type="checkbox" name="aligned_with_executive" id="modalLeaderAligned" value="1"> Alinhado ao executivo</label>';
    $html[] = '        <label>Notas';
    $html[] = '          <textarea name="notes" id="modalLeaderNotes" rows="2"></textarea>';
    $html[] = '        </label>';
    $html[] = '      </div>';
    $html[] = '      <div class="action-row">';
    $html[] = '        <button class="btn primary" type="submit">Salvar liderança</button>';
    $html[] = '      </div>';
    $html[] = '    </form>';
    $html[] = '    <form method="post" action="premium_actions.php" class="leader-modal__delete" onsubmit="return confirm(\'Remover esta liderança da campanha?\');">';
    $html[] = '      <input type="hidden" name="csrf" value="' . premium_escape_html($csrf) . '">';
    $html[] = '      <input type="hidden" name="action" value="delete_leader">';
    $html[] = '      <input type="hidden" name="redirect_tab" value="liderancas">';
    $html[] = '      <input type="hidden" name="campaign_id" value="' . $campaignId . '">';
    $html[] = '      <input type="hidden" name="leader_id" id="modalLeaderDeleteId" value="">';
    $html[] = '      <button class="btn danger" type="submit">Excluir liderança</button>';
    $html[] = '    </form>';
    $html[] = '  </div>';
    $html[] = '</div>';

    return implode('', $html);
}

function premium_render_scope_modal(int $baselineYear = 2022): string
{
    $baselineLabel = premium_baseline_label($baselineYear);
    $html = [];
    $html[] = '<div class="leader-modal" id="scopeModal" hidden aria-hidden="true">';
    $html[] = '  <div class="leader-modal__backdrop" data-modal-close></div>';
    $html[] = '  <div class="leader-modal__panel" role="dialog" aria-modal="true" aria-labelledby="scopeModalTitle">';
    $html[] = '    <div class="leader-modal__header">';
    $html[] = '      <div>';
    $html[] = '        <div class="eyebrow">Cidade ou região</div>';
    $html[] = '        <h3 id="scopeModalTitle">Selecione um recorte territorial</h3>';
    $html[] = '        <p class="muted" id="scopeModalSubtitle">Clique em uma cidade ou região para ver as lideranças, as projeções individuais e o comparativo com ' . premium_escape_html($baselineLabel) . '.</p>';
    $html[] = '      </div>';
    $html[] = '      <div class="modal-header-actions">';
    $html[] = '        <button type="button" class="btn comparison-report-btn" data-scope-print>Imprimir</button>';
    $html[] = '        <button type="button" class="btn ghost" data-modal-close>Fechar</button>';
    $html[] = '      </div>';
    $html[] = '    </div>';
    $html[] = '    <div class="leader-modal__summary" id="scopeModalSummary"></div>';
    $html[] = '    <p class="panel-note" id="scopeModalNote">O detalhe territorial mostrará o total de votos de ' . premium_escape_html($baselineLabel) . ' apenas como comparativo e destacará a projeção atual construída pelas lideranças cadastradas.</p>';
    $html[] = '    <div class="table-wrap">';
    $html[] = '      <table class="scope-modal-table">';
    $html[] = '        <thead id="scopeModalHead">';
    $html[] = '          <tr>';
    $html[] = '            <th>Liderança</th>';
    $html[] = '            <th>Município</th>';
    $html[] = '            <th>Votos 2024</th>';
    $html[] = '            <th>Base transferível</th>';
    $html[] = '            <th>Projeção</th>';
    $html[] = '            <th>Transferência</th>';
    $html[] = '            <th>Ação</th>';
    $html[] = '          </tr>';
    $html[] = '        </thead>';
    $html[] = '        <tbody id="scopeModalBody">';
    $html[] = '          <tr><td colspan="7" class="muted">Selecione uma cidade ou região para carregar os líderes.</td></tr>';
    $html[] = '        </tbody>';
    $html[] = '      </table>';
    $html[] = '    </div>';
    $html[] = '  </div>';
    $html[] = '</div>';

    return implode('', $html);
}

function premium_render_city_delta_report(array $forecast, int $baselineYear, ?array $campaign): string
{
    $cities = (array) ($forecast['cities'] ?? []);
    if (!$cities) {
        return '<div class="empty-state">Sem dados de projeção disponíveis. Cadastre lideranças para gerar o comparativo.</div>';
    }

    $rows = [];
    foreach ($cities as $city) {
        $baselineVotes  = (int) ($city['baseline_votes'] ?? 0);
        $projectedVotes = (int) ($city['projected_base'] ?? 0);
        $leaderCount    = (int) ($city['leader_count'] ?? $city['source_count'] ?? 0);
        $delta          = $projectedVotes - $baselineVotes;
        $deltaPercent   = $baselineVotes > 0 ? ($delta / $baselineVotes) * 100 : 0;
        $suggestion     = premium_city_suggestion($baselineVotes, $projectedVotes, $leaderCount);

        $rows[] = [
            'municipio'      => (string) ($city['municipio'] ?? ''),
            'regiao'         => (string) ($city['regiao'] ?? ''),
            'baseline_votes' => $baselineVotes,
            'projected'      => $projectedVotes,
            'delta'          => $delta,
            'delta_pct'      => $deltaPercent,
            'leader_count'   => $leaderCount,
            'suggestion'     => $suggestion,
            'alert_level'         => (string) ($city['alert_level'] ?? 'ok'),
            'pct_eleitorado'      => (float) ($city['pct_eleitorado'] ?? 0.0),
            'capped'              => (bool) ($city['capped'] ?? false),
            'cap_suggested'       => (bool) ($city['cap_suggested'] ?? false),
            'growth_warning'      => (bool) ($city['growth_warning'] ?? false),
            'growth_pct'          => (float) ($city['growth_pct'] ?? 0.0),
            'suggested_reduction' => (int) ($city['suggested_overlap_discount'] ?? 0),
        ];
    }

    usort($rows, static function (array $a, array $b): int {
        $p = ($a['suggestion']['priority'] ?? 5) <=> ($b['suggestion']['priority'] ?? 5);
        if ($p !== 0) {
            return $p;
        }
        return abs((int) $b['delta']) <=> abs((int) $a['delta']);
    });

    $countGrowth = $countStable = $countRisk = 0;
    foreach ($rows as $r) {
        $c = $r['suggestion']['class'];
        if (in_array($c, ['positive', 'positive-strong', 'opportunity'], true)) {
            $countGrowth++;
        } elseif ($c === 'neutral') {
            $countStable++;
        } else {
            $countRisk++;
        }
    }

    $html = [];
    $html[] = '<div class="grid-3 city-delta-summary">';
    $html[] = premium_render_stat('Em queda', (string) $countRisk, 'Projeção abaixo do histórico');
    $html[] = premium_render_stat('Estável', (string) $countStable, 'Variação próxima ao histórico');
    $html[] = premium_render_stat('Crescimento', (string) $countGrowth, 'Projeção acima do histórico');
    $html[] = '</div>';
    $html[] = '<div class="table-wrap city-delta-report-wrap">';
    $html[] = '<table class="leaders-table city-delta-table">';
    $html[] = '<thead><tr><th>Município</th><th>Região</th><th>Votos ' . (int) $baselineYear . '</th><th>Projeção 2026</th><th>Diferença</th><th>Situação</th><th>Sugestão</th></tr></thead>';
    $html[] = '<tbody>';

    foreach ($rows as $r) {
        $delta   = $r['delta'];
        $pct     = $r['delta_pct'];
        $sugg    = $r['suggestion'];
        $cls     = premium_escape_html($sugg['class']);
        $sign    = $delta >= 0 ? '+' : '';
        $pctFmt  = $r['baseline_votes'] > 0
            ? $sign . number_format(abs($pct), 1, ',', '.') . '%'
            : ($r['projected'] > 0 ? 'novo' : '—');
        $deltaFmt = $r['baseline_votes'] > 0 || $delta !== 0
            ? $sign . premium_fmt_int($delta)
            : '—';

        $html[] = '<tr>';
        $html[] = '<td><strong>' . premium_escape_html($r['municipio']) . '</strong>'
            . ($r['leader_count'] > 0
                ? '<span class="leaders-table-leader-cargo">' . $r['leader_count'] . ' liderança' . ($r['leader_count'] > 1 ? 's' : '') . '</span>'
                : '<span class="leaders-table-leader-cargo city-delta-no-leaders">Sem lideranças</span>')
            . '</td>';
        $html[] = '<td>' . premium_escape_html($r['regiao']) . '</td>';
        $html[] = '<td>' . premium_fmt_int($r['baseline_votes']) . '</td>';
        $alertLevel = $r['alert_level'] ?? 'ok';
        $alertBadge = '';
        if ($alertLevel !== 'ok') {
            $alertLabels      = ['warning' => 'Revisão', 'caution' => 'Atenção', 'danger' => 'Alerta'];
            $baselineVotesFmt = premium_fmt_int($r['baseline_votes']);
            $proj2026Fmt      = premium_fmt_int($r['projected']);
            $pctEleit         = number_format((float) ($r['pct_eleitorado'] ?? 0), 1, ',', '');
            $reductionFmt     = premium_fmt_int($r['suggested_reduction'] ?? 0);
            $cappedNote       = !empty($r['capped'])
                ? ' Projeção já limitada automaticamente ao teto de comparecimento.'
                : (!empty($r['cap_suggested'])
                    ? ' O teto foi sinalizado; a projeção exibida não foi reduzida (modo manual ativo).'
                    : '');
            $alertMessages = [
                'warning' => "Projeção de {$proj2026Fmt} votos = {$pctEleit}% do comparecimento 2024 — acima do limiar de 25%. Redutor sugerido: −{$reductionFmt} votos (excesso acima do limiar).{$cappedNote}",
                'caution' => "Projeção de {$proj2026Fmt} votos = {$pctEleit}% do comparecimento 2024 — nível elevado. Redutor sugerido: −{$reductionFmt} votos (excesso acima do limiar de 25%).{$cappedNote}",
                'danger'  => "Projeção de {$proj2026Fmt} votos = {$pctEleit}% do comparecimento 2024 — nível crítico. Redutor sugerido: −{$reductionFmt} votos (excesso acima do limiar de 25%). Corrija as taxas de migração.{$cappedNote}",
            ];
            $alertTip   = $alertMessages[$alertLevel] ?? "{$pctEleit}% do comparecimento.{$cappedNote}";
            $alertBadge = ' <span class="senate-rate-warning" tabindex="0" aria-label="' . premium_escape_html($alertTip) . '" data-tooltip="' . premium_escape_html($alertTip) . '">' . premium_escape_html($alertLabels[$alertLevel] ?? $alertLevel) . '</span>';
        } elseif (!empty($r['growth_warning'])) {
            $growthPctFmt = number_format((float) ($r['growth_pct'] ?? 0), 1, ',', '');
            $baselineFmt  = premium_fmt_int($r['baseline_votes']);
            $projFmt      = premium_fmt_int($r['projected']);
            $growthTip    = "Crescimento de {$growthPctFmt}% sobre a última eleição ({$baselineFmt} → {$projFmt} votos). Projeção ainda dentro do limiar aceitável, mas vale monitorar.";
            $alertBadge   = ' <span class="senate-rate-warning senate-rate-warning--growth" tabindex="0" aria-label="' . premium_escape_html($growthTip) . '" data-tooltip="' . premium_escape_html($growthTip) . '">Crescimento</span>';
        }
        $html[] = '<td>' . premium_fmt_int($r['projected']) . $alertBadge . '</td>';
        $html[] = '<td class="city-delta-cell city-delta-cell--' . $cls . '">'
            . '<strong>' . premium_escape_html($deltaFmt) . '</strong>'
            . '<span class="leaders-table-leader-cargo">' . premium_escape_html($pctFmt) . '</span>'
            . '</td>';
        $html[] = '<td><span class="city-delta-badge city-delta-badge--' . $cls . '">' . premium_escape_html($sugg['label']) . '</span></td>';
        $html[] = '<td class="city-delta-tip">' . premium_escape_html($sugg['tip']) . '</td>';
        $html[] = '</tr>';
    }

    $html[] = '</tbody></table></div>';
    return implode('', $html);
}

function premium_render_city_comparison_modal(array $forecast, int $baselineYear = 2022): string
{
    $baselineLabel = premium_baseline_label($baselineYear);
    $cities = array_values((array) ($forecast['cities'] ?? []));
    usort($cities, static function (array $a, array $b): int {
        $projectedCompare = (int) ($b['system_projection'] ?? $b['projected_base'] ?? 0) <=> (int) ($a['system_projection'] ?? $a['projected_base'] ?? 0);
        if ($projectedCompare !== 0) {
            return $projectedCompare;
        }

        $regionCompare = strcmp(
            premium_normalize_text((string) ($a['regiao'] ?? '')),
            premium_normalize_text((string) ($b['regiao'] ?? ''))
        );
        if ($regionCompare !== 0) {
            return $regionCompare;
        }

        return strcmp(
            premium_normalize_text((string) ($a['municipio'] ?? '')),
            premium_normalize_text((string) ($b['municipio'] ?? ''))
        );
    });

    $baselineTotal = 0;
    $systemTotal = 0;
    $leaderVotesTotal = 0;
    $independentTotal = 0;
    $leaderCountTotal = 0;
    $withLeaders = 0;
    $withoutLeaders = 0;

    foreach ($cities as $city) {
        $baselineTotal += (int) ($city['baseline_votes'] ?? 0);
        $systemTotal += (int) ($city['system_projection'] ?? $city['projected_base'] ?? 0);
        $leaderVotesTotal += (int) ($city['leader_projection'] ?? $city['leader_effect'] ?? 0);
        $independentTotal += (int) ($city['independent_votes'] ?? 0);
        $leaderCount = (int) ($city['leader_count'] ?? $city['source_count'] ?? 0);
        $leaderCountTotal += $leaderCount;
        if ($leaderCount > 0) {
            $withLeaders++;
        } else {
            $withoutLeaders++;
        }
    }

    $deltaTotal = $systemTotal - $baselineTotal;

    $html = [];
    $html[] = '<div class="leader-modal city-comparison-modal" id="cityComparisonModal" hidden aria-hidden="true">';
    $html[] = '  <div class="leader-modal__backdrop" data-modal-close></div>';
    $html[] = '  <div class="leader-modal__panel city-comparison-modal__panel" role="dialog" aria-modal="true" aria-labelledby="cityComparisonTitle">';
    $html[] = '    <div class="leader-modal__header">';
    $html[] = '      <div>';
    $html[] = '        <div class="eyebrow">Comparativo municipal</div>';
    $html[] = '        <h3 id="cityComparisonTitle">' . premium_escape_html($baselineLabel) . ' x projeção 2026 em todas as cidades</h3>';
    $html[] = '        <p class="muted" id="cityComparisonSubtitle">Compare o histórico de ' . premium_escape_html($baselineLabel) . ' com a projeção do sistema, separando claramente os votos das lideranças e os votos independentes.</p>';
    $html[] = '      </div>';
    $html[] = '      <div class="modal-header-actions">';
    $html[] = '        <button type="button" class="btn comparison-report-btn" data-city-comparison-print>Imprimir relatório</button>';
    $html[] = '        <button type="button" class="btn ghost" data-modal-close>Fechar</button>';
    $html[] = '      </div>';
    $html[] = '    </div>';
    $html[] = '    <div class="leader-modal__summary comparison-summary-grid">';
    $html[] = '      <div class="summary-metric summary-metric--primary">';
    $html[] = '        <div class="summary-metric__label">Comparativo ' . premium_escape_html($baselineLabel) . '</div>';
    $html[] = '        <div class="summary-metric__value">' . premium_fmt_int($baselineTotal) . '</div>';
    $html[] = '        <div class="summary-metric__sub">Base histórica de todas as cidades</div>';
    $html[] = '      </div>';
    $html[] = '      <div class="summary-metric summary-metric--primary">';
    $html[] = '        <div class="summary-metric__label">Projeção 2026</div>';
    $html[] = '        <div class="summary-metric__value">' . premium_fmt_int($systemTotal) . '</div>';
    $html[] = '        <div class="summary-metric__sub">Total projetado pelo modelo</div>';
    $html[] = '      </div>';
    $html[] = '      <div class="summary-metric">';
    $html[] = '        <div class="summary-metric__label">Com lideranças</div>';
    $html[] = '        <div class="summary-metric__value">' . premium_fmt_int($withLeaders) . '</div>';
    $html[] = '        <div class="summary-metric__sub">Cidades com apoio cadastrado</div>';
    $html[] = '      </div>';
    $html[] = '      <div class="summary-metric">';
    $html[] = '        <div class="summary-metric__label">Sem lideranças</div>';
    $html[] = '        <div class="summary-metric__value">' . premium_fmt_int($withoutLeaders) . '</div>';
    $html[] = '        <div class="summary-metric__sub">Cidades que usam fallback do sistema</div>';
    $html[] = '      </div>';
    $html[] = '    </div>';
    $html[] = '    <div class="scope-summary-meta">';
    $html[] = '      <span class="table-pill">Votos de liderança: ' . premium_fmt_int($leaderVotesTotal) . '</span>';
    $html[] = '      <span class="table-pill">Votos independentes: ' . premium_fmt_int($independentTotal) . '</span>';
    $html[] = '      <span class="table-pill">Lideranças cadastradas: ' . premium_fmt_int($leaderCountTotal) . '</span>';
    $html[] = '      <span class="table-pill">Delta total: ' . ($deltaTotal >= 0 ? '+' : '') . premium_fmt_int($deltaTotal) . '</span>';
    $html[] = '    </div>';
    $html[] = '    <div class="scope-summary-meta">';
    $html[] = '      <button type="button" class="agenda-filter-btn is-active" data-city-comparison-filter="all">Todas (' . premium_fmt_int(count($cities)) . ')</button>';
    $html[] = '      <button type="button" class="agenda-filter-btn" data-city-comparison-filter="leaders">Com lideranças (' . premium_fmt_int($withLeaders) . ')</button>';
    $html[] = '      <button type="button" class="agenda-filter-btn" data-city-comparison-filter="fallback">Sem lideranças (' . premium_fmt_int($withoutLeaders) . ')</button>';
    $html[] = '    </div>';
    $html[] = '    <p class="panel-note" id="cityComparisonNote">Os votos de liderança mostram a força das lideranças cadastradas; os votos independentes mostram a parcela não atribuída a lideranças. Nas cidades sem liderança, a projeção do sistema usa a votação de ' . premium_escape_html($baselineLabel) . '.</p>';
    $html[] = '    <div class="table-wrap">';
    $html[] = '      <table class="comparison-modal-table">';
    $html[] = '        <thead>';
    $html[] = '          <tr>';
    $html[] = '            <th>Município</th>';
    $html[] = '            <th>Região</th>';
    $html[] = '            <th>' . premium_escape_html($baselineLabel) . '</th>';
    $html[] = '            <th>Lideranças</th>';
    $html[] = '            <th>Votos Liderança</th>';
    $html[] = '            <th>Votos independentes</th>';
    $html[] = '            <th>Projeção 2026</th>';
    $html[] = '            <th>Delta</th>';
    $html[] = '            <th>Situação</th>';
    $html[] = '            <th>Ação</th>';
    $html[] = '          </tr>';
    $html[] = '        </thead>';
    $html[] = '        <tbody id="cityComparisonBody">';

    if (!$cities) {
        $html[] = '          <tr><td colspan="10" class="muted">Nenhuma cidade disponível para comparação.</td></tr>';
    } else {
        foreach ($cities as $city) {
            $municipality = (string) ($city['municipio'] ?? '');
            $region = (string) ($city['regiao'] ?? 'Sem região');
            $baselineVotes = (int) ($city['baseline_votes'] ?? 0);
            $leaderCount = (int) ($city['leader_count'] ?? $city['source_count'] ?? 0);
            $leaderVotes = (int) ($city['leader_projection'] ?? $city['leader_effect'] ?? 0);
            $independentVotes = (int) ($city['independent_votes'] ?? max(0, (int) ($city['system_projection'] ?? $city['projected_base'] ?? 0) - $leaderVotes));
            $systemProjection = (int) ($city['system_projection'] ?? $city['projected_base'] ?? 0);
            $delta = $systemProjection - $baselineVotes;
            $hasLeaders = $leaderCount > 0;
            $rowMode = $hasLeaders ? 'leaders' : 'fallback';
            $statusLabel = $hasLeaders ? 'Com lideranças' : 'Sem lideranças';
            $statusClass = $hasLeaders ? 'comparison-mode-pill comparison-mode-pill--leaders' : 'comparison-mode-pill comparison-mode-pill--fallback';
            $rowClass = $hasLeaders ? 'comparison-row--leaders' : 'comparison-row--fallback';
            $cityAlertLevel    = (string) ($city['alert_level'] ?? 'ok');
            $cityPctEleitorado = (float) ($city['pct_eleitorado'] ?? 0.0);
            $cityCapped        = (bool) ($city['capped'] ?? false);
            $cityCapSuggested  = (bool) ($city['cap_suggested'] ?? false);
            $cityGrowthWarning = (bool) ($city['growth_warning'] ?? false);
            $cityGrowthPct     = (float) ($city['growth_pct'] ?? 0.0);
            $citySugReduction  = (int) ($city['suggested_overlap_discount'] ?? 0);
            $cityAlertBadge    = '';
            if ($cityAlertLevel !== 'ok') {
                $aLabels   = ['warning' => 'Revisão', 'caution' => 'Atenção', 'danger' => 'Alerta'];
                $aProj     = premium_fmt_int($systemProjection);
                $aPct      = number_format($cityPctEleitorado, 1, ',', '');
                $aRedFmt   = premium_fmt_int($citySugReduction);
                $aCapped   = $cityCapped
                    ? ' Projeção já limitada automaticamente ao teto de comparecimento.'
                    : ($cityCapSuggested
                        ? ' O teto foi sinalizado; a projeção exibida não foi reduzida (modo manual ativo).'
                        : '');
                $aMsgs = [
                    'warning' => "Projeção de {$aProj} votos = {$aPct}% do comparecimento 2024 — acima do limiar de 25%. Redutor sugerido: −{$aRedFmt} votos (excesso acima do limiar).{$aCapped}",
                    'caution' => "Projeção de {$aProj} votos = {$aPct}% do comparecimento 2024 — nível elevado. Redutor sugerido: −{$aRedFmt} votos (excesso acima do limiar de 25%).{$aCapped}",
                    'danger'  => "Projeção de {$aProj} votos = {$aPct}% do comparecimento 2024 — nível crítico. Redutor sugerido: −{$aRedFmt} votos (excesso acima do limiar de 25%). Corrija as taxas de migração.{$aCapped}",
                ];
                $aTip           = $aMsgs[$cityAlertLevel] ?? "{$aPct}% do comparecimento.{$aCapped}";
                $cityAlertBadge = ' <span class="senate-rate-warning" tabindex="0" aria-label="' . premium_escape_html($aTip) . '" data-tooltip="' . premium_escape_html($aTip) . '">' . premium_escape_html($aLabels[$cityAlertLevel] ?? $cityAlertLevel) . '</span>';
            } elseif ($cityGrowthWarning) {
                $aGrowthFmt     = number_format($cityGrowthPct, 1, ',', '');
                $aBase          = premium_fmt_int($baselineVotes);
                $aProj          = premium_fmt_int($systemProjection);
                $growthTip      = "Crescimento de {$aGrowthFmt}% sobre a última eleição ({$aBase} → {$aProj} votos). Projeção dentro do limiar aceitável, mas vale monitorar.";
                $cityAlertBadge = ' <span class="senate-rate-warning senate-rate-warning--growth" tabindex="0" aria-label="' . premium_escape_html($growthTip) . '" data-tooltip="' . premium_escape_html($growthTip) . '">Crescimento</span>';
            }
            $actionButton = '<button type="button" class="btn ghost btn-small scope-open-btn" data-scope-type="city" data-scope-name="' . premium_escape_html($municipality) . '">Abrir</button>';

            $html[] = '          <tr class="' . $rowClass . '" data-city-comparison-row data-city-mode="' . $rowMode . '">';
            $html[] = '            <td>' . premium_escape_html($municipality) . '</td>';
            $html[] = '            <td><span class="table-pill">' . premium_escape_html($region) . '</span></td>';
            $html[] = '            <td>' . premium_fmt_int($baselineVotes) . '</td>';
            $html[] = '            <td>' . premium_fmt_int($leaderCount) . '</td>';
            $html[] = '            <td>' . premium_fmt_int($leaderVotes) . '</td>';
            $html[] = '            <td>' . premium_fmt_int($independentVotes) . '</td>';
            $html[] = '            <td>' . premium_fmt_int($systemProjection) . $cityAlertBadge . '</td>';
            $html[] = '            <td>' . ($delta >= 0 ? '+' : '') . premium_fmt_int($delta) . '</td>';
            $html[] = '            <td><span class="' . $statusClass . '">' . premium_escape_html($statusLabel) . '</span></td>';
            $html[] = '            <td>' . $actionButton . '</td>';
            $html[] = '          </tr>';
        }
    }

    $html[] = '          <tr id="cityComparisonEmptyRow" hidden><td colspan="10" class="muted">Nenhuma cidade corresponde a esse filtro.</td></tr>';
    $html[] = '        </tbody>';
    $html[] = '      </table>';
    $html[] = '    </div>';
    $html[] = '  </div>';
    $html[] = '</div>';

    return implode('', $html);
}

function premium_build_onboarding_steps(?array $campaign): array
{
    $hasCampaign = $campaign !== null;
    $isSenateCampaign = $hasCampaign && premium_is_senate_cargo((string) ($campaign['candidate_cargo'] ?? ''));
    $opcoesHref = premium_tab_href('opcoes', $campaign);
    $liderancasHref = premium_tab_href('liderancas', $campaign);
    $senadoHref = premium_tab_href('senado', $campaign);
    $agendaHref = premium_tab_href('agenda', $campaign);
    $relatoriosHref = premium_tab_href('relatorios', $campaign);

    $steps = [
        [
            'number' => '1',
            'title' => $hasCampaign ? 'Dados da campanha' : 'Criar a campanha',
            'descriptionHtml' => $hasCampaign
                ? 'Atualize <strong>nome, candidato, cargo, número, município-base, região-base, foto e notas</strong>.'
                : 'Crie a campanha primeiro para liberar o restante do guia e organizar os dados no próximo passo.',
            'buttonLabel' => $hasCampaign ? 'Abrir dados' : 'Criar campanha',
            'href' => $opcoesHref . ($hasCampaign ? '#baselineBody' : '#campaignCreatePanel'),
            'statusLabel' => $hasCampaign ? 'Comece pelos dados da campanha' : 'Cadastre a primeira campanha',
            'locked' => false,
        ],
        [
            'number' => '2',
            'title' => 'Peso dos cenários',
            'descriptionHtml' => 'O sistema <strong>já está calibrado com os valores padrão</strong>. Se mexer, comece apenas por <strong>Transferência padrão %</strong> e altere o restante só com certeza.',
            'buttonLabel' => 'Abrir pesos',
            'href' => $opcoesHref . '#optionsSettingsBody',
            'statusLabel' => 'Revise o modelo antes de mudar',
            'locked' => !$hasCampaign,
        ],
        [
            'number' => '3',
            'title' => 'Adicionar lideranças à campanha',
            'descriptionHtml' => 'Escolha <strong>prefeito</strong> ou <strong>vereador</strong>, selecione o município, clique em <strong>Buscar lideranças</strong>, marque quem apoia o candidato e confirme em <strong>Adicionar lideranças</strong>. Se a liderança <strong>não foi candidata em 2024</strong>, use o card <strong>Adicionar liderança fora de 2024</strong> logo abaixo e preencha <strong>município, nome e votos esperados</strong>.',
            'buttonLabel' => 'Buscar lideranças',
            'href' => $liderancasHref . '#leaderSearchBody',
            'statusLabel' => 'Lideranças de 2024',
            'locked' => !$hasCampaign,
        ],
        [
            'number' => '4',
            'title' => 'Agenda de campanha',
            'descriptionHtml' => 'Registre <strong>visitas, reuniões, eventos e tarefas</strong> para manter a campanha e os assessores organizados em um só lugar.',
            'buttonLabel' => 'Abrir agenda',
            'href' => $agendaHref . '#agendaPanel',
            'statusLabel' => 'Tarefas e deslocamentos',
            'locked' => !$hasCampaign,
        ],
        [
            'number' => '5',
            'title' => 'Relatórios',
            'descriptionHtml' => 'Veja as <strong>oito lideranças com maior votação</strong>, as regiões e cidades com maior projeção e abra o relatório territorial completo em <strong>Líderes</strong>.',
            'buttonLabel' => 'Ver relatórios',
            'href' => $relatoriosHref . '#reportsPanel',
            'statusLabel' => 'Projeções e comparativos',
            'locked' => !$hasCampaign,
        ],
    ];

    if ($isSenateCampaign) {
        $steps[2]['title'] = 'Adicionar fontes ao Senado';
        $steps[2]['descriptionHtml'] = 'Use <strong>Projeção Senado</strong> para cadastrar base própria, deputados aliados, prefeitos, vereadores e fontes manuais. Esse é o único fluxo que alimenta a projeção de senador.';
        $steps[2]['buttonLabel'] = 'Abrir Senado';
        $steps[2]['href'] = $senadoHref;
        $steps[2]['statusLabel'] = 'Fontes do Senado';
        $steps[4]['descriptionHtml'] = 'Veja as <strong>fontes do Senado</strong>, as regiões e cidades com maior projeção e abra o relatório territorial completo.';
    }

    return $steps;
}

function premium_render_onboarding_panel(?array $campaign, string $activeTab, string $studyExcerpt): string
{
    $steps = premium_build_onboarding_steps($campaign);
    $initialStep = $steps[0] ?? [
        'number' => '1',
        'title' => 'Tutorial de uso',
        'descriptionHtml' => 'Clique no botão para avançar.',
        'buttonLabel' => 'Abrir',
        'href' => '#',
        'statusLabel' => 'Comece por aqui',
    ];
    $stepCount = count($steps);

    ob_start();
    ?>
    <div class="premium-sidebar__guide">
        <div class="premium-sidebar__guide-actions">
            <button type="button" class="btn ghost btn-small" data-onboarding-toggle aria-pressed="false">Ocultar tutorial</button>
        </div>
        <section class="panel onboarding-panel" data-onboarding-root data-onboarding-step-count="<?= (int) $stepCount ?>" data-onboarding-has-campaign="<?= $campaign ? '1' : '0' ?>">
            <div class="section-title onboarding-panel__head">
                <div>
                    <div class="eyebrow">Comece por aqui</div>
                    <h2>Tutorial rápido</h2>
                </div>
            </div>
            <p class="panel-note onboarding-panel__note">
                Um passo por vez. Quando você clicar no botão do card, o próximo passo aparece automaticamente.
            </p>
            <div class="onboarding-panel__progress" aria-label="Progresso do guia">
                <div class="onboarding-panel__progress-track"><span data-onboarding-progress-fill></span></div>
                <div class="onboarding-panel__progress-meta">
                    <strong data-onboarding-step-counter><?= (int) min(1, $stepCount) ?>/<?= (int) $stepCount ?></strong>
                    <span data-onboarding-step-status><?= premium_escape_html((string) ($initialStep['statusLabel'] ?? 'Comece por aqui')) ?></span>
                </div>
            </div>
            <article class="onboarding-panel__step">
                <span class="onboarding-panel__step-badge" data-onboarding-step-number><?= premium_escape_html((string) ($initialStep['number'] ?? '1')) ?></span>
                <div class="onboarding-panel__step-body">
                    <h3 data-onboarding-step-title><?= premium_escape_html((string) ($initialStep['title'] ?? 'Tutorial rápido')) ?></h3>
                    <p class="onboarding-panel__step-copy" data-onboarding-step-copy><?= (string) ($initialStep['descriptionHtml'] ?? '') ?></p>
                </div>
                <div class="onboarding-panel__step-actions">
                    <a class="btn primary btn-small" data-onboarding-step-action href="<?= premium_escape_html((string) ($initialStep['href'] ?? '#')) ?>"><?= premium_escape_html((string) ($initialStep['buttonLabel'] ?? 'Abrir')) ?></a>
                    <label class="onboarding-panel__step-jump">
                        <span>Ir para</span>
                        <select data-onboarding-step-jump>
                            <?php foreach ($steps as $stepIndex => $stepItem): ?>
                                <option value="<?= (int) $stepIndex ?>"<?= $stepIndex === 0 ? ' selected' : '' ?>>
                                    <?= premium_escape_html((string) ($stepItem['number'] ?? (string) ($stepIndex + 1)) . ' - ' . (string) ($stepItem['title'] ?? 'Passo')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
            </article>
            <div class="onboarding-panel__footer">
                <span class="muted onboarding-panel__footer-note">O tutorial se oculta ao final ou quando você quiser.</span>
            </div>
        </section>
    </div>
    <?php

    return (string) ob_get_clean();

    $hasCampaign = $campaign !== null;
    $tabLabels = [
        'home' => 'Home',
        'liderancas' => 'Lideranças',
        'agenda' => 'Agenda',
        'relatorios' => 'Relatórios',
        'opcoes' => 'Opções avançadas',
    ];
    $currentTabLabel = $tabLabels[$activeTab] ?? 'Campanha';
    $opcoesHref = premium_tab_href('opcoes', $campaign);
    $liderancasHref = premium_tab_href('liderancas', $campaign);
    $agendaHref = premium_tab_href('agenda', $campaign);
    $relatoriosHref = premium_tab_href('relatorios', $campaign);

    $steps = [
        [
            'number' => '1',
            'title' => 'Cadastrar os Dados da campanha',
            'description_html' => $hasCampaign
                ? 'Atualize <strong>nome, candidato, cargo, número, município-base, região-base, foto e notas</strong>.'
                : 'Cadastre <strong>nome, candidato, cargo, número, município-base, região-base, foto e notas</strong>. Esse é o ponto de partida do sistema.',
            'button_label' => $hasCampaign ? 'Abrir dados' : 'Criar campanha',
            'href' => $hasCampaign ? $opcoesHref . '#baselineBody' : $opcoesHref . '#campaignCreatePanel',
            'locked' => false,
        ],
        [
            'number' => '2',
            'title' => 'Peso dos cenários',
            'description_html' => 'O sistema <strong>já está calibrado com os valores padrão</strong>. Se for alterar algo, comece por <strong>Transferência padrão %</strong> e só mexa no restante se tiver certeza do impacto.',
            'button_label' => $hasCampaign ? 'Abrir pesos' : 'Disponível após criar a campanha',
            'href' => $hasCampaign ? $opcoesHref . '#optionsSettingsBody' : '',
            'locked' => !$hasCampaign,
            'tone' => 'warning',
        ],
        [
            'number' => '3',
            'title' => 'Adicionar lideranças à campanha',
            'description_html' => 'Para quem foi candidato em 2024: escolha <strong>prefeito</strong> ou <strong>vereador</strong>, filtre o município, clique em <strong>Buscar lideranças</strong>, selecione quem apoia você e confirme em <strong>Adicionar lideranças</strong>.',
            'button_label' => $hasCampaign ? 'Buscar lideranças' : 'Disponível após criar a campanha',
            'href' => $hasCampaign ? $liderancasHref . '#leaderSearchBody' : '',
            'locked' => !$hasCampaign,
        ],
        [
            'number' => '3.1',
            'title' => 'Lideranças fora de 2024',
            'description_html' => 'Para lideranças que <strong>não foram candidatas em 2024</strong>, use o card próprio e preencha <strong>município, nome e transferência %</strong> antes de salvar.',
            'button_label' => $hasCampaign ? 'Abrir formulário' : 'Disponível após criar a campanha',
            'href' => $hasCampaign ? $liderancasHref . '#leaderAddBody' : '',
            'locked' => !$hasCampaign,
        ],
        [
            'number' => '4',
            'title' => 'Agenda de campanha',
            'description_html' => 'Adicione <strong>visitas, lideranças, eventos e tarefas</strong>. Tudo fica organizado em um só lugar, com prazo e município relacionados à campanha.',
            'button_label' => $hasCampaign ? 'Abrir agenda' : 'Disponível após criar a campanha',
            'href' => $hasCampaign ? $agendaHref . '#agendaPanel' : '',
            'locked' => !$hasCampaign,
        ],
        [
            'number' => '5',
            'title' => 'Relatórios',
            'description_html' => 'A tela mostra as <strong>oito lideranças com maior votação</strong>, as regiões e cidades com maior projeção de votos e, ao clicar em <strong>Líderes</strong>, o detalhamento do recorte.',
            'button_label' => $hasCampaign ? 'Ver relatórios' : 'Disponível após criar a campanha',
            'href' => $hasCampaign ? $relatoriosHref . '#reportsPanel' : '',
            'locked' => !$hasCampaign,
        ],
    ];

    ob_start();
    ?>
    <section class="panel onboarding-panel">
        <div class="section-title">
            <div>
                <div class="eyebrow">Comece por aqui</div>
                <h2>Tutorial de uso</h2>
            </div>
            <div class="pill-row onboarding-panel__meta" style="margin-top: 0;">
                <span class="pill">Etapa atual: <?= premium_escape_html($currentTabLabel) ?></span>
                <span class="pill">Ordem recomendada: 1 → 5</span>
            </div>
        </div>
        <p class="panel-note">
            Siga esta ordem para não se perder. O sistema funciona melhor quando os dados entram primeiro na campanha, depois nos cenários, lideranças, agenda e relatórios.
        </p>
        <div class="onboarding-layout">
            <div class="onboarding-steps">
                <?php foreach ($steps as $step): ?>
                    <article class="onboarding-step<?= !empty($step['locked']) ? ' onboarding-step--locked' : '' ?><?= ($step['tone'] ?? '') === 'warning' ? ' onboarding-step--warning' : '' ?>">
                        <div class="onboarding-step__head">
                            <span class="onboarding-step__badge"><?= premium_escape_html((string) ($step['number'] ?? '')) ?></span>
                            <div class="onboarding-step__copy-block">
                                <h3><?= premium_escape_html((string) ($step['title'] ?? '')) ?></h3>
                                <p class="onboarding-step__copy"><?= (string) ($step['description_html'] ?? '') ?></p>
                            </div>
                        </div>
                        <div class="onboarding-step__actions">
                            <?php if (!empty($step['href']) && empty($step['locked'])): ?>
                                <a class="btn ghost btn-small" href="<?= premium_escape_html((string) $step['href']) ?>"><?= premium_escape_html((string) ($step['button_label'] ?? 'Abrir')) ?></a>
                            <?php else: ?>
                                <span class="btn ghost btn-small is-disabled"><?= premium_escape_html((string) ($step['button_label'] ?? 'Disponível em breve')) ?></span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <details class="onboarding-study">
                <summary>Ler o trecho da base técnica dos pesos e cenários</summary>
                <p class="onboarding-study__hint">
                    Arquivo de apoio: <code>docs/explicacao_previsao_transferencia_premium.md</code>
                </p>
                <p class="onboarding-study__hint">
                    O card <strong>Peso dos cenários</strong> foi calibrado com base nesse material. O sistema já vem com valores padrão; altere apenas o que fizer sentido para a campanha.
                </p>
                <?php if (trim($studyExcerpt) !== ''): ?>
                    <pre class="onboarding-study__excerpt"><?= premium_escape_html($studyExcerpt) ?></pre>
                <?php else: ?>
                    <p class="onboarding-study__fallback">Não foi possível carregar o trecho agora, mas o sistema continua calibrado com os valores padrão definidos nos estudos.</p>
                <?php endif; ?>
            </details>
        </div>
    </section>
    <?php

    return (string) ob_get_clean();
}

function premium_render_study_modal(string $studyExcerpt): string
{
    $html = [];
    $html[] = '<div class="leader-modal study-modal" id="studyModal" hidden aria-hidden="true">';
    $html[] = '  <div class="leader-modal__backdrop" data-modal-close></div>';
    $html[] = '  <div class="leader-modal__panel study-modal__panel" role="dialog" aria-modal="true" aria-labelledby="studyModalTitle">';
    $html[] = '    <div class="leader-modal__header">';
    $html[] = '      <div>';
    $html[] = '        <div class="eyebrow">Base técnica</div>';
    $html[] = '        <h3 id="studyModalTitle">Pesos e cenários</h3>';
    $html[] = '        <p class="muted" id="studyModalSubtitle">Trecho da documentação que explica como o modelo foi calibrado.</p>';
    $html[] = '      </div>';
    $html[] = '      <button type="button" class="btn ghost" data-modal-close>Fechar</button>';
    $html[] = '    </div>';
    $html[] = '    <div class="study-modal__body">';
    $html[] = '      <p class="panel-note">O card <strong>Peso dos cenários</strong> já vem com valores padrão. Leia a base antes de alterar qualquer parâmetro.</p>';
    if (trim($studyExcerpt) !== '') {
        $html[] = '      <pre class="study-modal__excerpt">' . premium_escape_html($studyExcerpt) . '</pre>';
    } else {
        $html[] = '      <p class="muted">Não foi possível carregar o trecho agora, mas o sistema continua calibrado com os valores padrão definidos nos estudos.</p>';
    }
    $html[] = '    </div>';
    $html[] = '  </div>';
    $html[] = '</div>';

    return implode('', $html);
}

function premium_render_leaf_card(array $leader): string
{
    $leaderId = (int) ($leader['id'] ?? 0);
    $yesChecked = !empty($leader['aligned_with_executive']) ? ' checked' : '';
    $leaderName = (string) ($leader['leader_display_name'] ?? $leader['leader_name'] ?? 'Liderança');
    $transferRate = number_format((float) ($leader['transfer_rate'] ?? premium_default_settings()['transfer_rate_default']), 2, '.', '');
    $marginPercent = number_format((float) ($leader['margin_percent'] ?? 0), 2, '.', '');
    $visibilityScore = number_format((float) ($leader['visibility_score'] ?? 50), 2, '.', '');
    $investmentScore = number_format((float) ($leader['investment_score'] ?? 50), 2, '.', '');
    $votes2024 = (int) ($leader['leader_votes_2024'] ?? 0);
    $regionName = (string) ($leader['region_name'] ?? 'Sem região');

    $html = [];
    $html[] = '<article class="leader-card">';
    $html[] = '<div class="leader-head">';
    $html[] = '<div>';
    $html[] = '<div class="pill">' . premium_escape_html($regionName) . '</div>';
    $html[] = '<h4>' . premium_escape_html($leaderName) . '</h4>';
    $html[] = '<p>' . premium_escape_html((string) ($leader['municipality'] ?? '')) . ' • ' . premium_escape_html((string) ($leader['leader_cargo'] ?? '')) . '</p>';
    $html[] = '</div>';
    $html[] = '<div class="leader-metrics">';
    $html[] = '<strong>' . premium_fmt_int($votes2024) . '</strong>';
    $html[] = '<span>votos em 2024</span>';
    $html[] = '</div>';
    $html[] = '</div>';
    $html[] = '<form class="leader-form" method="post" action="premium_actions.php">';
    $html[] = '<input type="hidden" name="csrf" value="' . premium_escape_html(premium_csrf_token()) . '">';
    $html[] = '<input type="hidden" name="action" value="update_leader">';
    $html[] = '<input type="hidden" name="redirect_tab" value="liderancas">';
    $html[] = '<input type="hidden" name="campaign_id" value="' . (int) ($_SESSION['premium_campaign_id'] ?? 0) . '">';
    $html[] = '<input type="hidden" name="leader_id" value="' . $leaderId . '">';
    $html[] = '<div class="form-grid compact">';
    $html[] = '<label>Região<input type="text" name="region_name" value="' . premium_escape_html((string) ($leader['region_name'] ?? '')) . '"></label>';
    $html[] = '<label>Município<input type="text" name="municipality" value="' . premium_escape_html((string) ($leader['municipality'] ?? '')) . '"></label>';
    $html[] = '<label>Nome da urna<input type="text" name="leader_name" value="' . premium_escape_html($leaderName) . '"></label>';
    $html[] = '<label>Cargo<input type="text" name="leader_cargo" value="' . premium_escape_html((string) ($leader['leader_cargo'] ?? '')) . '"></label>';
    $html[] = '<label>Partido<input type="text" name="leader_party" value="' . premium_escape_html((string) ($leader['leader_party'] ?? '')) . '"></label>';
    $html[] = '<label>Votos 2024<input type="number" name="leader_votes_2024" value="' . $votes2024 . '" min="0" step="1"></label>';
    $html[] = '<label>Margem %<input type="number" name="margin_percent" value="' . $marginPercent . '" min="0" step="0.01"></label>';
    $html[] = '<label>Transferência %<input type="number" name="transfer_rate" value="' . $transferRate . '" min="0" max="100" step="0.01"></label>';
    $html[] = '<label>Visibilidade<input type="number" name="visibility_score" value="' . $visibilityScore . '" min="0" max="100" step="0.01"></label>';
    $html[] = '<label>Investimento<input type="number" name="investment_score" value="' . $investmentScore . '" min="0" max="100" step="0.01"></label>';
    $html[] = '<label>Tamanho<select name="size_class"><option value="small"' . ((string) ($leader['size_class'] ?? '') === 'small' ? ' selected' : '') . '>Pequeno</option><option value="medium"' . ((string) ($leader['size_class'] ?? '') === 'medium' ? ' selected' : '') . '>Médio</option><option value="large"' . ((string) ($leader['size_class'] ?? '') === 'large' ? ' selected' : '') . '>Grande</option></select></label>';
    $html[] = '<label class="checkbox"><input type="checkbox" name="aligned_with_executive" value="1"' . $yesChecked . '> Alinhado ao executivo</label>';
    $html[] = '<label>Notas<textarea name="notes" rows="2">' . premium_escape_html((string) ($leader['notes'] ?? '')) . '</textarea></label>';
    $html[] = '</div>';
    $html[] = '<div class="action-row"><button class="btn primary" type="submit">Salvar liderança</button></div>';
    $html[] = '</form>';
    $html[] = '<form method="post" action="premium_actions.php" onsubmit="return confirm(\'Remover esta liderança da campanha?\');">';
    $html[] = '<input type="hidden" name="csrf" value="' . premium_escape_html(premium_csrf_token()) . '">';
    $html[] = '<input type="hidden" name="action" value="delete_leader">';
    $html[] = '<input type="hidden" name="redirect_tab" value="liderancas">';
    $html[] = '<input type="hidden" name="campaign_id" value="' . (int) ($_SESSION['premium_campaign_id'] ?? 0) . '">';
    $html[] = '<input type="hidden" name="leader_id" value="' . $leaderId . '">';
    $html[] = '<button class="btn danger" type="submit">Excluir liderança</button>';
    $html[] = '</form>';
    $html[] = '</article>';

    return implode('', $html);
}

function premium_render_agenda_card(array $item): string
{
    $agendaId = (int) ($item['id'] ?? 0);
    $dueDate = (string) ($item['due_date'] ?? '');
    $status = (string) ($item['status'] ?? 'open');
    $priority = (string) ($item['priority'] ?? 'medium');
    $html = [];
    $html[] = '<article class="agenda-card">';
    $html[] = '<form method="post" action="premium_actions.php" class="agenda-form">';
    $html[] = '<input type="hidden" name="redirect_tab" value="agenda">';
    $html[] = '<input type="hidden" name="csrf" value="' . premium_escape_html(premium_csrf_token()) . '">';
    $html[] = '<input type="hidden" name="campaign_id" value="' . (int) ($_SESSION['premium_campaign_id'] ?? 0) . '">';
    $html[] = '<input type="hidden" name="agenda_id" value="' . $agendaId . '">';
    $html[] = '<div class="agenda-top">';
    $html[] = '<label>Título<input type="text" name="title" value="' . premium_escape_html((string) ($item['title'] ?? '')) . '"></label>';
    $html[] = '<label>Status<select name="status"><option value="open"' . ($status === 'open' ? ' selected' : '') . '>Aberta</option><option value="doing"' . ($status === 'doing' ? ' selected' : '') . '>xm andamento</option><option value="done"' . ($status === 'done' ? ' selected' : '') . '>Concluída</option><option value="archived"' . ($status === 'archived' ? ' selected' : '') . '>Arquivada</option></select></label>';
    $html[] = '<label>Prioridade<select name="priority"><option value="low"' . ($priority === 'low' ? ' selected' : '') . '>Baixa</option><option value="medium"' . ($priority === 'medium' ? ' selected' : '') . '>Média</option><option value="high"' . ($priority === 'high' ? ' selected' : '') . '>Alta</option><option value="urgent"' . ($priority === 'urgent' ? ' selected' : '') . '>Urgente</option></select></label>';
    $html[] = '<label>Prazo<input type="date" name="due_date" value="' . premium_escape_html($dueDate) . '"></label>';
    $html[] = '<label>Município<input type="text" name="municipality" value="' . premium_escape_html((string) ($item['municipality'] ?? '')) . '"></label>';
    $html[] = '<label>Liderança<input type="text" name="leader_name" value="' . premium_escape_html((string) ($item['leader_name'] ?? '')) . '"></label>';
    $html[] = '<label>Descrição<textarea name="description" rows="2">' . premium_escape_html((string) ($item['description'] ?? '')) . '</textarea></label>';
    $html[] = '</div>';
    $html[] = '<div class="action-row"><button class="btn primary" type="submit" name="action" value="update_agenda">Salvar tarefa</button></div>';
    $html[] = '</form>';
    $html[] = '<form method="post" action="premium_actions.php" onsubmit="return confirm(\'Remover esta tarefa da agenda?\');">';
    $html[] = '<input type="hidden" name="csrf" value="' . premium_escape_html(premium_csrf_token()) . '">';
    $html[] = '<input type="hidden" name="action" value="delete_agenda">';
    $html[] = '<input type="hidden" name="redirect_tab" value="agenda">';
    $html[] = '<input type="hidden" name="campaign_id" value="' . (int) ($_SESSION['premium_campaign_id'] ?? 0) . '">';
    $html[] = '<input type="hidden" name="agenda_id" value="' . $agendaId . '">';
    $html[] = '<button class="btn danger" type="submit">Excluir tarefa</button>';
    $html[] = '</form>';
    $html[] = '</article>';

    return implode('', $html);
}

function premium_format_date_br(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return 'Sem prazo';
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    if (!$date) {
        return $value;
    }

    return $date->format('d/m/Y');
}

function premium_agenda_status_label(string $status): string
{
    return match ($status) {
        'open' => 'Aberta',
        'doing' => 'xm andamento',
        'done' => 'Concluída',
        'archived' => 'Arquivada',
        default => ucfirst($status),
    };
}

function premium_agenda_priority_label(string $priority): string
{
    return match ($priority) {
        'low' => 'Baixa',
        'high' => 'Alta',
        'urgent' => 'Urgente',
        default => 'Média',
    };
}

function premium_agenda_status_class(string $status): string
{
    return match ($status) {
        'open' => 'open',
        'doing' => 'doing',
        'done' => 'done',
        'archived' => 'archived',
        default => 'open',
    };
}

function premium_agenda_priority_class(string $priority): string
{
    return match ($priority) {
        'low' => 'low',
        'high' => 'high',
        'urgent' => 'urgent',
        default => 'medium',
    };
}

function premium_render_agenda_table(array $items, bool $compact = false): string
{
    if (!$items) {
        return '<div class="empty-state">A agenda ainda está vazia. Adicione as primeiras tarefas do escritório de campanha.</div>';
    }

    if ($compact) {
        $html = [];
        $html[] = '<div class="agenda-mini-list">';

        foreach ($items as $item) {
            $agendaId = (int) ($item['id'] ?? 0);
            $title = (string) ($item['title'] ?? '');
            $dueDate = premium_format_date_br((string) ($item['due_date'] ?? ''));
            $municipality = (string) ($item['municipality'] ?? '');
            $leaderName = (string) ($item['leader_name'] ?? '');
            $status = (string) ($item['status'] ?? 'open');
            $statusClass = premium_agenda_status_class($status);

            $html[] = '<article class="agenda-mini-card agenda-mini-card--' . premium_escape_html($statusClass) . '">';
            $html[] = '  <div class="agenda-mini-card__main">';
            $html[] = '    <button type="button" class="agenda-mini-title agenda-open-btn" data-agenda-id="' . $agendaId . '">' . premium_escape_html($title !== '' ? $title : 'Tarefa') . '</button>';
            $meta = trim($municipality . ($leaderName !== '' ? ' • ' . $leaderName : ''));
            $html[] = '    <div class="agenda-mini-meta">' . premium_escape_html($meta !== '' ? $meta : 'Sem município') . '</div>';
            $html[] = '  </div>';
            $html[] = '  <div class="agenda-mini-card__side">';
            $html[] = '    <span class="agenda-mini-date">' . premium_escape_html($dueDate) . '</span>';
            $html[] = '    <button type="button" class="btn ghost btn-small agenda-open-btn" data-agenda-id="' . $agendaId . '">Abrir</button>';
            $html[] = '  </div>';
            $html[] = '</article>';
        }

        $html[] = '</div>';

        return implode('', $html);
    }

    $html = [];
    $html[] = '<div class="agenda-table-shell">';
    $html[] = '<div class="table-wrap">';
    $html[] = '<table class="agenda-table agenda-table--full">';
    $html[] = '<thead><tr>';
    $html[] = '<th>Prazo</th>';
    $html[] = '<th>Tarefa</th>';
    $html[] = '<th>Município</th>';
    $html[] = '<th>Liderança</th>';
    $html[] = '<th>Status</th>';
    $html[] = '<th>Prioridade</th>';
    $html[] = '<th>Ação</th>';
    $html[] = '</tr></thead><tbody>';

    foreach ($items as $item) {
        $agendaId = (int) ($item['id'] ?? 0);
        $title = (string) ($item['title'] ?? '');
        $dueDate = (string) ($item['due_date'] ?? '');
        $municipality = (string) ($item['municipality'] ?? '');
        $leaderName = (string) ($item['leader_name'] ?? '');
        $status = (string) ($item['status'] ?? 'open');
        $priority = (string) ($item['priority'] ?? 'medium');
        $html[] = '<tr' . ($status === 'archived' ? ' class="agenda-row--archived"' : '') . '>';
        $html[] = '<td>' . premium_escape_html(premium_format_date_br($dueDate)) . '</td>';
        $html[] = '<td>';
        $html[] = '<button type="button" class="agenda-open-btn" data-agenda-id="' . $agendaId . '">' . premium_escape_html($title !== '' ? $title : 'Tarefa') . '</button>';
        $html[] = '</td>';
        $html[] = '<td>' . premium_escape_html($municipality !== '' ? $municipality : '-') . '</td>';
        $html[] = '<td>' . premium_escape_html($leaderName !== '' ? $leaderName : '-') . '</td>';
        $html[] = '<td><span class="agenda-status agenda-status--' . premium_escape_html(premium_agenda_status_class($status)) . '">' . premium_escape_html(premium_agenda_status_label($status)) . '</span></td>';
        $html[] = '<td><span class="agenda-priority agenda-priority--' . premium_escape_html(premium_agenda_priority_class($priority)) . '">' . premium_escape_html(premium_agenda_priority_label($priority)) . '</span></td>';
        $html[] = '<td><button type="button" class="btn ghost btn-small agenda-open-btn" data-agenda-id="' . $agendaId . '">Abrir</button></td>';
        $html[] = '</tr>';
    }

    $html[] = '</tbody></table></div></div>';

    return implode('', $html);
}

function premium_render_agenda_detail_modal(?array $campaign, string $csrf): string
{
    if (!$campaign) {
        return '';
    }

    $campaignId = (int) ($campaign['id'] ?? 0);
    $html = [];
    $html[] = '<div class="agenda-modal" id="agendaModal" hidden aria-hidden="true">';
    $html[] = '  <div class="agenda-modal__backdrop" data-modal-close></div>';
    $html[] = '  <div class="agenda-modal__panel" role="dialog" aria-modal="true" aria-labelledby="agendaModalTitle">';
    $html[] = '    <div class="leader-modal__header agenda-modal__header">';
    $html[] = '      <div>';
    $html[] = '        <div class="eyebrow">Detalhes da tarefa</div>';
    $html[] = '        <h3 id="agendaModalTitle">Selecione uma tarefa</h3>';
    $html[] = '        <p class="muted" id="agendaModalSubtitle">Abra uma linha da agenda para editar, arquivar ou excluir sem ocupar a tela principal.</p>';
    $html[] = '      </div>';
    $html[] = '      <button type="button" class="btn ghost" data-modal-close>Fechar</button>';
    $html[] = '    </div>';
    $html[] = '    <div class="leader-modal__summary agenda-modal__summary" id="agendaModalSummary"></div>';
    $html[] = '    <form method="post" action="premium_actions.php" class="agenda-form" id="agendaModalForm">';
    $html[] = '      <input type="hidden" name="csrf" value="' . premium_escape_html($csrf) . '">';
    $html[] = '      <input type="hidden" name="action" value="update_agenda">';
    $html[] = '      <input type="hidden" name="redirect_tab" value="agenda">';
    $html[] = '      <input type="hidden" name="campaign_id" value="' . $campaignId . '">';
    $html[] = '      <input type="hidden" name="agenda_id" id="modalAgendaId" value="">';
    $html[] = '      <div class="form-grid compact modal-grid">';
    $html[] = '        <label>Título';
    $html[] = '          <input type="text" name="title" id="modalAgendaTitleInput" required>';
    $html[] = '        </label>';
    $html[] = '        <label>Prazo';
    $html[] = '          <input type="date" name="due_date" id="modalAgendaDueDate">';
    $html[] = '        </label>';
    $html[] = '        <label>Prioridade';
    $html[] = '          <select name="priority" id="modalAgendaPriority">';
    $html[] = '            <option value="low">Baixa</option>';
    $html[] = '            <option value="medium">Média</option>';
    $html[] = '            <option value="high">Alta</option>';
    $html[] = '            <option value="urgent">Urgente</option>';
    $html[] = '          </select>';
    $html[] = '        </label>';
    $html[] = '        <label>Status';
    $html[] = '          <select name="status" id="modalAgendaStatus">';
    $html[] = '            <option value="open">Aberta</option>';
    $html[] = '            <option value="doing">xm andamento</option>';
    $html[] = '            <option value="done">Concluída</option>';
    $html[] = '            <option value="archived">Arquivada</option>';
    $html[] = '          </select>';
    $html[] = '        </label>';
    $html[] = '        <label>Município';
    $html[] = '          <input type="text" name="municipality" id="modalAgendaMunicipality">';
    $html[] = '        </label>';
    $html[] = '        <label>Liderança';
    $html[] = '          <input type="text" name="leader_name" id="modalAgendaLeader">';
    $html[] = '        </label>';
    $html[] = '      </div>';
    $html[] = '      <label style="margin-top:12px;">Descrição';
    $html[] = '        <textarea name="description" id="modalAgendaDescription" rows="4" placeholder="Detalhe a tarefa, prazo, entregáveis e responsáveis."></textarea>';
    $html[] = '      </label>';
    $html[] = '      <div class="action-row">';
    $html[] = '        <button class="btn primary" type="submit">Salvar alterações</button>';
    $html[] = '      </div>';
    $html[] = '    </form>';
    $html[] = '    <div class="agenda-modal__actions">';
    $html[] = '      <form method="post" action="premium_actions.php" class="agenda-modal__inline">';
    $html[] = '        <input type="hidden" name="csrf" value="' . premium_escape_html($csrf) . '">';
    $html[] = '        <input type="hidden" name="action" value="archive_agenda">';
    $html[] = '        <input type="hidden" name="redirect_tab" value="agenda">';
    $html[] = '        <input type="hidden" name="campaign_id" value="' . $campaignId . '">';
    $html[] = '        <input type="hidden" name="agenda_id" id="modalAgendaArchiveId" value="">';
    $html[] = '        <button class="btn ghost" type="submit">Arquivar tarefa</button>';
    $html[] = '      </form>';
    $html[] = '      <form method="post" action="premium_actions.php" class="agenda-modal__inline" onsubmit="return confirm(\'Remover esta tarefa da agenda?\');">';
    $html[] = '        <input type="hidden" name="csrf" value="' . premium_escape_html($csrf) . '">';
    $html[] = '        <input type="hidden" name="action" value="delete_agenda">';
    $html[] = '        <input type="hidden" name="redirect_tab" value="agenda">';
    $html[] = '        <input type="hidden" name="campaign_id" value="' . $campaignId . '">';
    $html[] = '        <input type="hidden" name="agenda_id" id="modalAgendaDeleteId" value="">';
    $html[] = '        <button class="btn danger" type="submit">Excluir tarefa</button>';
    $html[] = '      </form>';
    $html[] = '    </div>';
    $html[] = '    <p class="panel-note" style="margin-top:14px;">Tarefas com prazo vencido são arquivadas automaticamente e saem da lista de pendentes.</p>';
    $html[] = '  </div>';
    $html[] = '</div>';

    return implode('', $html);
}

function premium_render_agenda_list_modal(array $items): string
{
    $counts = [
        'total' => count($items),
        'open' => 0,
        'doing' => 0,
        'done' => 0,
        'archived' => 0,
    ];

    foreach ($items as $item) {
        $status = (string) ($item['status'] ?? 'open');
        if (isset($counts[$status])) {
            $counts[$status]++;
        }
    }

    $html = [];
    $html[] = '<div class="agenda-modal" id="agendaListModal" hidden aria-hidden="true">';
    $html[] = '  <div class="agenda-modal__backdrop" data-modal-close></div>';
    $html[] = '  <div class="agenda-modal__panel agenda-modal__panel--wide" role="dialog" aria-modal="true" aria-labelledby="agendaListModalTitle">';
    $html[] = '    <div class="leader-modal__header agenda-modal__header">';
    $html[] = '      <div>';
    $html[] = '        <div class="eyebrow">Agenda completa</div>';
    $html[] = '        <h3 id="agendaListModalTitle">Ver todas as tarefas</h3>';
    $html[] = '        <p class="muted">Acesse a visão completa com tarefas pendentes, concluídas e arquivadas.</p>';
    $html[] = '      </div>';
    $html[] = '      <button type="button" class="btn ghost" data-modal-close>Fechar</button>';
    $html[] = '    </div>';
    $html[] = '    <div class="leader-modal__summary agenda-modal__summary">';
    $html[] = '      <span class="table-pill">Total: ' . premium_fmt_int($counts['total']) . '</span>';
    $html[] = '      <span class="table-pill">Pendentes: ' . premium_fmt_int($counts['open'] + $counts['doing']) . '</span>';
    $html[] = '      <span class="table-pill">Concluídas: ' . premium_fmt_int($counts['done']) . '</span>';
    $html[] = '      <span class="table-pill">Arquivadas: ' . premium_fmt_int($counts['archived']) . '</span>';
    $html[] = '    </div>';
    $html[] = premium_render_agenda_table($items);
    $html[] = '  </div>';
    $html[] = '</div>';

    return implode('', $html);
}

function premium_render_senate_relationship_options(string $selected = ''): string
{
    $selected = $selected === 'familiar' ? 'aliado' : $selected;
    $html = [];
    foreach (premium_senate_relationship_choices() as $value => $label) {
        if ($value === 'familiar') {
            continue;
        }

        $selectedAttr = $value === $selected ? ' selected' : '';
        $html[] = '<option value="' . premium_escape_html($value) . '"' . $selectedAttr . '>' . premium_escape_html($label) . '</option>';
    }

    return implode('', $html);
}

function premium_render_senate_source_hidden_fields(array $source): string
{
    $fields = [
        'source_year' => (int) ($source['source_year'] ?? 2022),
        'source_cargo' => (string) ($source['source_cargo'] ?? ''),
        'source_candidate_name' => (string) ($source['source_candidate_name'] ?? ''),
        'source_ballot_name' => (string) ($source['source_ballot_name'] ?? ''),
        'source_party' => (string) ($source['source_party'] ?? ''),
        'source_number' => premium_fmt_candidate_number_plain(premium_parse_candidate_number($source['source_number'] ?? null)),
        'source_sq_candidato' => (string) ($source['source_sq_candidato'] ?? ''),
        'source_scope_label' => (string) ($source['source_scope_label'] ?? premium_senate_scope_label((string) ($source['source_cargo'] ?? ''))),
        'source_total_votes' => (int) ($source['source_total_votes'] ?? 0),
        'source_vote_percent' => ($source['source_vote_percent'] ?? null) !== null ? (float) $source['source_vote_percent'] : '',
        'confidence_score' => (float) ($source['confidence_score'] ?? 50),
        'notes' => (string) ($source['suggestion_reason'] ?? $source['notes'] ?? ''),
    ];

    $html = [];
    foreach ($fields as $name => $value) {
        $html[] = '<input type="hidden" name="' . premium_escape_html($name) . '" value="' . premium_escape_html((string) $value) . '">';
    }

    return implode('', $html);
}

function premium_render_senate_votes_cell(array $source): string
{
    $votes   = premium_fmt_int((int) ($source['source_total_votes'] ?? 0));
    $percent = $source['source_vote_percent'] ?? null;
    $year    = (int) ($source['source_year'] ?? 0);

    $html = '<strong>' . premium_escape_html($votes) . '</strong>';
    if ($percent !== null && $percent !== '') {
        $html .= '<span class="senate-source-sub">(' . premium_escape_html(premium_fmt_percent((float) $percent)) . ')</span>';
    }
    $html .= '<span class="senate-source-sub">' . $year . '</span>';

    return $html;
}

function premium_render_senate_source_add_controls(array $source, ?array $campaign, string $csrf, string $buttonLabel = 'Adicionar'): string
{
    $relationshipType = premium_senate_normalize_relationship_type(
        (string) ($source['relationship_type'] ?? 'manual'),
        (string) ($source['source_cargo'] ?? ''),
        (int) ($source['source_year'] ?? 0)
    );
    $transferRate = (float) ($source['transfer_rate'] ?? premium_senate_default_transfer_rate($relationshipType, (string) ($source['source_cargo'] ?? ''), (int) ($source['source_year'] ?? 0)));

    $html = [];
    $html[] = '<form method="post" action="premium_actions.php" class="senate-source-inline-form">';
    $html[] = '<input type="hidden" name="csrf" value="' . premium_escape_html($csrf) . '">';
    $html[] = '<input type="hidden" name="action" value="add_senate_source">';
    $html[] = '<input type="hidden" name="redirect_tab" value="senado">';
    $html[] = '<input type="hidden" name="campaign_id" value="' . (int) ($campaign['id'] ?? 0) . '">';
    $html[] = premium_render_senate_source_hidden_fields($source);
    $html[] = '<label><span>Relação</span><select name="relationship_type">' . premium_render_senate_relationship_options($relationshipType) . '</select></label>';
    $html[] = '<label><span>Migração %</span><input type="number" name="transfer_rate" value="' . premium_escape_html(number_format($transferRate, 2, '.', '')) . '" min="0" max="100" step="0.01"></label>';
    $html[] = '<button class="btn primary btn-small" type="submit">' . premium_escape_html($buttonLabel) . '</button>';
    $html[] = '</form>';

    return implode('', $html);
}

function premium_render_senate_candidate_rows(array $sources, ?array $campaign, string $csrf, string $emptyText): string
{
    if (!$sources) {
        return '<div class="empty-state">' . premium_escape_html($emptyText) . '</div>';
    }

    // Unique suffix per call so two renders on the same page never share IDs
    static $callCounter = 0;
    $callCounter++;
    $uid = 'src' . $callCounter;

    $campaignId = (int) ($campaign['id'] ?? 0);

    // Build JS data array (one object per source, indexed by row position)
    $jsSourceData = [];
    foreach ($sources as $source) {
        $jsSourceData[] = [
            'source_year'          => (int) ($source['source_year'] ?? 2022),
            'source_cargo'         => (string) ($source['source_cargo'] ?? ''),
            'source_candidate_name'=> (string) ($source['source_candidate_name'] ?? ''),
            'source_ballot_name'   => (string) ($source['source_ballot_name'] ?? ''),
            'source_party'         => (string) ($source['source_party'] ?? ''),
            'source_number'        => premium_fmt_candidate_number_plain(premium_parse_candidate_number($source['source_number'] ?? null)),
            'source_sq_candidato'  => (string) ($source['source_sq_candidato'] ?? ''),
            'source_scope_label'   => (string) ($source['source_scope_label'] ?? premium_senate_scope_label((string) ($source['source_cargo'] ?? ''))),
            'source_total_votes'   => (int) ($source['source_total_votes'] ?? 0),
            'source_vote_percent'  => ($source['source_vote_percent'] ?? null) !== null ? (float) $source['source_vote_percent'] : null,
            'confidence_score'     => (float) ($source['confidence_score'] ?? 50),
            'notes'                => (string) ($source['suggestion_reason'] ?? $source['notes'] ?? ''),
        ];
    }

    // Scope all IDs and classes to this render instance
    $idBar      = 'senate-bulk-bar-'   . $uid;
    $idCount    = 'senate-bulk-count-' . $uid;
    $idRel      = 'senate-bulk-rel-'   . $uid;
    $idRate     = 'senate-bulk-rate-'  . $uid;
    $idSubmit   = 'senate-bulk-sub-'   . $uid;
    $idCheckAll = 'senate-check-all-'  . $uid;
    $clsCheck   = 'senate-row-check-'  . $uid;

    $html = [];

    // Bulk action bar (hidden until at least one row is checked)
    $html[] = '<div class="senate-bulk-bar" id="' . $idBar . '" hidden>';
    $html[] = '<span class="senate-bulk-count"><span id="' . $idCount . '">0</span> selecionada(s)</span>';
    $html[] = '<label class="senate-bulk-field"><span>Relação</span><select id="' . $idRel . '">' . premium_render_senate_relationship_options('aliado') . '</select></label>';
    $html[] = '<label class="senate-bulk-field"><span>Migração %</span><input type="number" id="' . $idRate . '" value="35.00" min="0" max="100" step="0.01"></label>';
    $html[] = '<button type="button" class="btn primary btn-small" id="' . $idSubmit . '">Adicionar selecionadas</button>';
    $html[] = '</div>';

    $html[] = '<div class="table-wrap senate-table-wrap">';
    $html[] = '<table class="leaders-table senate-source-table">';
    $html[] = '<thead><tr>';
    $html[] = '<th><input type="checkbox" id="' . $idCheckAll . '" title="Selecionar todos"></th>';
    $html[] = '<th class="senate-col-year">Ano</th><th>Fonte</th><th class="senate-col-cargo">Cargo</th><th class="senate-col-votes">Votos</th><th class="senate-col-reason">Motivo</th><th>Ação</th>';
    $html[] = '</tr></thead><tbody>';

    foreach ($sources as $idx => $source) {
        $displayName = trim((string) ($source['source_ballot_name'] ?? ''));
        if ($displayName === '') {
            $displayName = (string) ($source['source_candidate_name'] ?? 'Fonte');
        }
        $scopeLabel = trim((string) ($source['source_scope_label'] ?? ''));
        if ($scopeLabel === '') {
            $scopeLabel = premium_senate_scope_label((string) ($source['source_cargo'] ?? ''));
        }
        $reason = (string) ($source['suggestion_reason'] ?? '');
        if ($reason === '') {
            $reason = premium_senate_relationship_label((string) ($source['relationship_type'] ?? 'manual'));
        }

        $html[] = '<tr>';
        $html[] = '<td><input type="checkbox" class="' . $clsCheck . '" data-idx="' . $idx . '"></td>';
        $searchParty = trim((string) ($source['source_party'] ?? ''));
        $html[] = '<td class="senate-col-year"><strong>' . (int) ($source['source_year'] ?? 0) . '</strong></td>';
        $html[] = '<td><strong>' . premium_escape_html($displayName) . '</strong>'
            . '<span class="senate-source-sub senate-source-sub--scope">' . premium_escape_html($scopeLabel) . '</span>'
            . ($searchParty !== '' ? '<span class="senate-source-sub senate-source-sub--party">' . premium_escape_html($searchParty) . '</span>' : '')
            . '</td>';
        $situacao = trim((string) ($source['source_situacao'] ?? ''));
        $cargoCell = premium_escape_html((string) ($source['source_cargo'] ?? ''));
        if ($situacao !== '') {
            $cargoCell .= '<span class="senate-source-sub">' . premium_escape_html($situacao) . '</span>';
        }
        $html[] = '<td>' . $cargoCell . '</td>';
        $html[] = '<td class="senate-col-votes">' . premium_render_senate_votes_cell($source) . '</td>';
        $html[] = '<td class="senate-col-reason">' . premium_escape_html($reason) . '</td>';
        $html[] = '<td>' . premium_render_senate_source_add_controls($source, $campaign, $csrf) . '</td>';
        $html[] = '</tr>';
    }

    $html[] = '</tbody></table></div>';

    // Inline JS — all IDs and class names scoped to this $uid
    $jsData    = json_encode($jsSourceData, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $jsCsrf    = json_encode($csrf, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $jsCampId  = json_encode((string) $campaignId, JSON_THROW_ON_ERROR);
    $jsBar      = json_encode($idBar,      JSON_THROW_ON_ERROR);
    $jsCount    = json_encode($idCount,    JSON_THROW_ON_ERROR);
    $jsRel      = json_encode($idRel,      JSON_THROW_ON_ERROR);
    $jsRate     = json_encode($idRate,     JSON_THROW_ON_ERROR);
    $jsSubmit   = json_encode($idSubmit,   JSON_THROW_ON_ERROR);
    $jsCheckAll = json_encode($idCheckAll, JSON_THROW_ON_ERROR);
    $jsCls      = json_encode('.' . $clsCheck, JSON_THROW_ON_ERROR);
    $jsClsChk   = json_encode('.' . $clsCheck . ':checked', JSON_THROW_ON_ERROR);

    $html[] = <<<JS
<script>
(function(){
  var data={$jsData};
  var bar=document.getElementById({$jsBar});
  var countEl=document.getElementById({$jsCount});
  var checkAll=document.getElementById({$jsCheckAll});
  var sel=function(q){return document.querySelectorAll(q);};
  function updateBar(){
    var n=sel({$jsClsChk}).length;
    countEl.textContent=n;
    bar.hidden=n===0;
    var total=sel({$jsCls}).length;
    checkAll.indeterminate=n>0&&n<total;
    checkAll.checked=n>0&&n===total;
  }
  checkAll.addEventListener('change',function(){
    sel({$jsCls}).forEach(function(c){c.checked=checkAll.checked;});
    updateBar();
  });
  sel({$jsCls}).forEach(function(c){c.addEventListener('change',updateBar);});
  document.getElementById({$jsSubmit}).addEventListener('click',function(){
    var selected=sel({$jsClsChk});
    if(!selected.length){return;}
    var rel=document.getElementById({$jsRel}).value;
    var rate=document.getElementById({$jsRate}).value;
    var f=document.createElement('form');
    f.method='post';f.action='premium_actions.php';
    function hi(n,v){var i=document.createElement('input');i.type='hidden';i.name=n;i.value=v;f.appendChild(i);}
    hi('csrf',{$jsCsrf});
    hi('action','add_senate_sources_bulk');
    hi('redirect_tab','senado');
    hi('campaign_id',{$jsCampId});
    hi('bulk_relationship_type',rel);
    hi('bulk_transfer_rate',rate);
    selected.forEach(function(c,i){
      hi('sources_json['+i+']',JSON.stringify(data[parseInt(c.dataset.idx)]));
    });
    document.body.appendChild(f);f.submit();
  });
})();
</script>
JS;

    return implode('', $html);
}

function premium_render_senate_registered_sources_table(array $sources, ?array $campaign, string $csrf): string
{
    if (!$sources) {
        return '<div class="empty-state">Nenhuma fonte cadastrada ainda. Adicione a base própria, aliados ou fontes manuais para montar a projeção.</div>';
    }

    $campaignId = (int) ($campaign['id'] ?? 0);
    $jsCsrf     = json_encode($csrf, JSON_THROW_ON_ERROR);
    $jsCampId   = json_encode((string) $campaignId, JSON_THROW_ON_ERROR);

    $html = [];

    // Bulk delete bar
    $html[] = '<div class="senate-bulk-bar" id="senate-reg-bulk-bar" hidden>';
    $html[] = '<span class="senate-bulk-count"><span id="senate-reg-bulk-count">0</span> selecionada(s)</span>';
    $html[] = '<button type="button" class="btn danger btn-small" id="senate-reg-bulk-delete">Excluir selecionadas</button>';
    $html[] = '</div>';

    $html[] = '<div class="table-wrap senate-table-wrap">';
    $html[] = '<table class="leaders-table senate-source-table">';
    $html[] = '<thead><tr><th><input type="checkbox" id="senate-reg-check-all" title="Selecionar todos"></th><th>Fonte</th><th>Ano / Cargo</th><th>Votos</th><th>Projeção 2026</th><th>Ações</th></tr></thead><tbody>';
    foreach ($sources as $source) {
        $sourceId = (int) ($source['id'] ?? 0);
        $displayName = trim((string) ($source['source_ballot_name'] ?? ''));
        if ($displayName === '') {
            $displayName = (string) ($source['source_candidate_name'] ?? 'Fonte');
        }
        $scopeLabel = trim((string) ($source['source_scope_label'] ?? ''));
        if ($scopeLabel === '') {
            $scopeLabel = premium_senate_scope_label((string) ($source['source_cargo'] ?? ''));
        }
        $relationshipType = (string) ($source['relationship_type'] ?? 'manual');
        $transferRate = number_format((float) ($source['transfer_rate'] ?? 0), 2, '.', '');
        $confidenceScore = number_format((float) ($source['confidence_score'] ?? 50), 2, '.', '');
        $notes = (string) ($source['notes'] ?? '');
        $adjRowId = 'senate-adj-' . $sourceId;

        $html[] = '<tr>';
        $html[] = '<td><input type="checkbox" class="senate-reg-check" data-id="' . $sourceId . '"></td>';
        $html[] = '<td><strong>' . premium_escape_html($displayName) . '</strong><span class="senate-source-sub">' . premium_escape_html($scopeLabel) . '</span><span class="senate-source-sub">' . premium_escape_html(premium_senate_relationship_label($relationshipType)) . '</span></td>';
        $srcParty = trim((string) ($source['source_party'] ?? ''));
        $html[] = '<td>'
            . '<strong>' . (int) ($source['source_year'] ?? 0) . '</strong>'
            . '<span class="senate-source-sub">' . premium_escape_html((string) ($source['source_cargo'] ?? '')) . '</span>'
            . ($srcParty !== '' ? '<span class="senate-source-sub">' . premium_escape_html($srcParty) . '</span>' : '')
            . '</td>';
        $html[] = '<td>' . premium_render_senate_votes_cell($source) . '</td>';
        $transferRateVal     = (float) ($source['transfer_rate'] ?? 0);
        $transferRateDisplay = number_format($transferRateVal, 2, ',', '') . '%';
        $rateAlertLevel      = premium_senate_transfer_rate_alert($transferRateVal, $relationshipType);
        $rateBounds          = premium_senate_transfer_rate_bounds($relationshipType);
        $rateWarn            = '';
        $projectionCellClass = '';
        $projectionSubClass  = 'senate-source-sub';
        if ($rateAlertLevel === 'warning') {
            $rateTip = 'Taxa fora do intervalo esperado para ' . premium_escape_html(premium_senate_relationship_label($relationshipType))
                . ' (' . $rateBounds['warn_low'] . '–' . $rateBounds['warn_high'] . '%). Considere revisar.';
            $rateWarn = ' <span class="senate-rate-warning" title="' . $rateTip . '">⚠</span>';
        }
        if ($rateAlertLevel === 'warning') {
            $projectionCellClass = ' class="senate-projection-cell senate-projection-cell--warning"';
            $projectionSubClass .= ' senate-source-sub--warning';
            $rateTip = 'Atenção: taxa fora do intervalo esperado para ' . premium_senate_relationship_label($relationshipType)
                . '. Faixa recomendada: ' . $rateBounds['warn_low'] . '% a ' . $rateBounds['warn_high']
                . '%. Taxa atual: ' . $transferRateDisplay . '. Revise antes de confiar na projeção.';
            $rateWarn = ' <span class="senate-rate-warning" tabindex="0" aria-label="' . premium_escape_html($rateTip) . '" data-tooltip="' . premium_escape_html($rateTip) . '">Revisar taxa</span>';
        }
        $html[] = '<td' . $projectionCellClass . '><strong>' . premium_fmt_int((int) ($source['projected_votes'] ?? 0)) . '</strong><span class="' . $projectionSubClass . '">' . premium_escape_html($transferRateDisplay) . $rateWarn . '</span></td>';
        $html[] = '<td class="senate-source-actions">';
        $html[] = '<button type="button" class="btn ghost btn-small" onclick="var r=document.getElementById(\'' . $adjRowId . '\');r.hidden=!r.hidden;">Ajustes</button>';
        $html[] = '<form method="post" action="premium_actions.php" onsubmit="return confirm(\'Remover esta fonte da projeção do Senado?\');" style="display:inline">';
        $html[] = '<input type="hidden" name="csrf" value="' . premium_escape_html($csrf) . '">';
        $html[] = '<input type="hidden" name="action" value="delete_senate_source">';
        $html[] = '<input type="hidden" name="redirect_tab" value="senado">';
        $html[] = '<input type="hidden" name="campaign_id" value="' . $campaignId . '">';
        $html[] = '<input type="hidden" name="source_id" value="' . $sourceId . '">';
        $html[] = '<button class="btn danger btn-small" type="submit">Excluir</button>';
        $html[] = '</form>';
        $html[] = '</td>';
        $html[] = '</tr>';

        $html[] = '<tr id="' . $adjRowId . '" hidden class="senate-source-adj-row">';
        $html[] = '<td colspan="6">';
        $html[] = '<form method="post" action="premium_actions.php" class="senate-source-edit-form">';
        $html[] = '<input type="hidden" name="csrf" value="' . premium_escape_html($csrf) . '">';
        $html[] = '<input type="hidden" name="action" value="update_senate_source">';
        $html[] = '<input type="hidden" name="redirect_tab" value="senado">';
        $html[] = '<input type="hidden" name="campaign_id" value="' . $campaignId . '">';
        $html[] = '<input type="hidden" name="source_id" value="' . $sourceId . '">';
        $html[] = premium_render_senate_source_hidden_fields($source);
        $html[] = '<label><span>Relação</span><select name="relationship_type">' . premium_render_senate_relationship_options($relationshipType) . '</select></label>';
        $html[] = '<label><span>Migração %</span><input type="number" name="transfer_rate" value="' . premium_escape_html($transferRate) . '" min="0" max="100" step="0.01"></label>';
        $html[] = '<label><span>Confiança</span><input type="number" name="confidence_score" value="' . premium_escape_html($confidenceScore) . '" min="0" max="100" step="0.01"></label>';
        $html[] = '<label class="senate-source-edit-form__notes"><span>Notas</span><textarea name="notes" rows="1">' . premium_escape_html($notes) . '</textarea></label>';
        $html[] = '<button class="btn ghost btn-small" type="submit">Salvar</button>';
        $html[] = '</form>';
        $html[] = '</td>';
        $html[] = '</tr>';
    }
    $html[] = '</tbody></table></div>';

    $html[] = <<<JS
<script>
(function(){
  var bar=document.getElementById('senate-reg-bulk-bar');
  var countEl=document.getElementById('senate-reg-bulk-count');
  var checkAll=document.getElementById('senate-reg-check-all');
  var checks=function(){return document.querySelectorAll('.senate-reg-check');};
  function updateBar(){
    var n=document.querySelectorAll('.senate-reg-check:checked').length;
    countEl.textContent=n;
    bar.hidden=n===0;
    checkAll.indeterminate=n>0&&n<checks().length;
    checkAll.checked=n>0&&n===checks().length;
  }
  checkAll.addEventListener('change',function(){
    checks().forEach(function(c){c.checked=checkAll.checked;});
    updateBar();
  });
  checks().forEach(function(c){c.addEventListener('change',updateBar);});
  document.getElementById('senate-reg-bulk-delete').addEventListener('click',function(){
    var selected=document.querySelectorAll('.senate-reg-check:checked');
    if(!selected.length){return;}
    if(!confirm('Remover '+selected.length+' fonte(s) da projeção do Senado?')){return;}
    var f=document.createElement('form');
    f.method='post';f.action='premium_actions.php';
    function hi(n,v){var i=document.createElement('input');i.type='hidden';i.name=n;i.value=v;f.appendChild(i);}
    hi('csrf',{$jsCsrf});
    hi('action','delete_senate_sources_bulk');
    hi('redirect_tab','senado');
    hi('campaign_id',{$jsCampId});
    selected.forEach(function(c,i){hi('source_ids['+i+']',c.dataset.id);});
    document.body.appendChild(f);f.submit();
  });
})();
</script>
JS;

    return implode('', $html);
}

function premium_render_senate_sources_leaders_view(array $sources, array $senateForecast): string
{
    if (!$sources) {
        return '';
    }

    $forecastBySourceId = [];
    foreach ((array) ($senateForecast['sources'] ?? []) as $fs) {
        $sid = (int) ($fs['id'] ?? 0);
        if ($sid > 0) {
            $forecastBySourceId[$sid] = $fs;
        }
    }

    $html = [];
    $html[] = '<div class="senate-leaders-view">';
    $html[] = '<h3 class="senate-leaders-view__title">Fontes cadastradas (Senado)</h3>';
    $html[] = '<p class="senate-leaders-view__desc muted">Fontes registradas no módulo Senado. Gerencie-as em <a href="?campaign_id=' . (int) ($sources[0]['campaign_id'] ?? 0) . '&tab=senado">Senado</a>.</p>';
    $html[] = '<div class="table-wrap"><table class="leaders-table senate-leaders-table">';
    $html[] = '<thead><tr>';
    $html[] = '<th>Fonte</th>';
    $html[] = '<th>Ano / Cargo</th>';
    $html[] = '<th class="num">Votos</th>';
    $html[] = '<th class="num">Migração %</th>';
    $html[] = '<th class="num">Projeção 2026</th>';
    $html[] = '</tr></thead>';
    $html[] = '<tbody>';

    foreach ($sources as $source) {
        $sourceId      = (int) ($source['id'] ?? 0);
        $displayName   = trim((string) ($source['source_ballot_name'] ?? ''));
        if ($displayName === '') {
            $displayName = trim((string) ($source['source_candidate_name'] ?? ''));
        }
        $party         = trim((string) ($source['source_party'] ?? ''));
        $relLabel      = (string) ($source['relationship_label'] ?? '');
        $year          = (int) ($source['source_year'] ?? 0);
        $cargo         = trim((string) ($source['source_cargo'] ?? ''));
        $totalVotes    = (int) ($source['source_total_votes'] ?? 0);
        $transferRate  = (float) ($source['transfer_rate'] ?? 0);
        $relType       = (string) ($source['relationship_type'] ?? 'manual');
        $rateAlert     = premium_senate_transfer_rate_alert($transferRate, $relType);

        $forecastedVotes = (int) ($forecastBySourceId[$sourceId]['projected_votes'] ?? 0);

        $rateClass = '';
        $rateIcon  = '';
        $projectionClass = 'num';
        $projectionAlert = '';
        if ($rateAlert === 'warning') {
            $rateClass = ' senate-rate-warning';
            $projectionClass .= ' senate-projection-cell senate-projection-cell--warning';
            $rateBounds = premium_senate_transfer_rate_bounds($relType);
            $rateTip = 'Atenção: taxa fora do intervalo esperado para ' . premium_senate_relationship_label($relType)
                . '. Faixa recomendada: ' . $rateBounds['warn_low'] . '% a ' . $rateBounds['warn_high']
                . '%. Taxa atual: ' . number_format($transferRate, 1, ',', '') . '%. Revise antes de confiar na projeção.';
            $projectionAlert = '<br><span class="senate-rate-warning senate-rate-warning--under" tabindex="0" aria-label="' . premium_escape_html($rateTip) . '" data-tooltip="' . premium_escape_html($rateTip) . '">Revisar taxa</span>';
            $rateIcon  = ' <span class="senate-rate-warning-icon" title="Taxa fora do intervalo recomendado">⚠</span>';
        }

        $html[] = '<tr>';
        $html[] = '<td>';
        $html[] = '<strong>' . premium_escape_html($displayName) . '</strong>';
        if ($relLabel !== '') {
            $html[] = '<br><span class="muted small">' . premium_escape_html($relLabel) . '</span>';
        }
        if ($party !== '') {
            $html[] = '<br><span class="muted small">' . premium_escape_html($party) . '</span>';
        }
        $html[] = '</td>';
        $html[] = '<td>';
        if ($year > 0) {
            $html[] = '<span class="senate-col-year">' . $year . '</span>';
        }
        if ($cargo !== '') {
            $html[] = '<br><span class="muted small">' . premium_escape_html($cargo) . '</span>';
        }
        $html[] = '</td>';
        $html[] = '<td class="num">' . premium_fmt_int($totalVotes) . '</td>';
        $html[] = '<td class="num' . $rateClass . '">' . premium_escape_html(number_format($transferRate, 1)) . '%' . $rateIcon . '</td>';
        $html[] = '<td class="' . $projectionClass . '">' . ($forecastedVotes > 0 ? premium_fmt_int($forecastedVotes) : '<span class="muted">—</span>') . $projectionAlert . '</td>';
        $html[] = '</tr>';
    }

    $html[] = '</tbody></table></div>';
    $html[] = '</div>';

    return implode('', $html);
}

function premium_render_senate_forecast_tables(array $senateForecast): string
{
    $cities = array_slice((array) ($senateForecast['cities'] ?? []), 0, 20);
    $regions = (array) ($senateForecast['regions'] ?? []);
    $overlapMode = premium_senate_overlap_mode($senateForecast['totals']['overlap_mode'] ?? ($senateForecast['settings']['senate_overlap_mode'] ?? 'alert_only'));
    $overlapColumnLabel = $overlapMode === 'automatic' ? 'Redutor' : 'Sugestao redutor';
    $overlapField = $overlapMode === 'automatic' ? 'overlap_discount' : 'suggested_overlap_discount';
    $html = [];

    $html[] = '<div class="grid-2 senate-results-grid">';
    $html[] = '<div><h3 class="senate-block-title">Regiões</h3><div class="table-wrap senate-table-wrap"><table class="leaders-table"><thead><tr><th>Região</th><th>Base 2018</th><th>Migrado</th><th>Projeção</th></tr></thead><tbody>';
    foreach ($regions as $region) {
        $html[] = '<tr><td>' . premium_escape_html((string) ($region['regiao'] ?? '')) . '</td><td>' . premium_fmt_int((int) ($region['baseline_votes'] ?? 0)) . '</td><td>' . premium_fmt_int((int) ($region['source_projected_votes'] ?? 0)) . '</td><td>' . premium_fmt_int((int) ($region['projected_base'] ?? 0)) . '</td></tr>';
    }
    if (!$regions) {
        $html[] = '<tr><td colspan="4" class="muted">Nenhuma região calculada ainda.</td></tr>';
    }
    $html[] = '</tbody></table></div></div>';

    $html[] = '<div><h3 class="senate-block-title">Municípios</h3><div class="table-wrap senate-table-wrap"><table class="leaders-table"><thead><tr><th>Município</th><th>Base 2018</th><th>Top fonte</th><th>' . premium_escape_html($overlapColumnLabel) . '</th><th>Projeção</th></tr></thead><tbody>';
    foreach ($cities as $city) {
        $html[] = '<tr><td>' . premium_escape_html((string) ($city['municipio'] ?? '')) . '</td><td>' . premium_fmt_int((int) ($city['baseline_votes'] ?? 0)) . '</td><td>' . premium_escape_html((string) ($city['top_source'] ?? '')) . '</td><td>' . premium_fmt_int((int) ($city[$overlapField] ?? 0)) . '</td><td>' . premium_fmt_int((int) ($city['projected_base'] ?? 0)) . '</td></tr>';
    }
    if (!$cities) {
        $html[] = '<tr><td colspan="5" class="muted">Nenhum município calculado ainda.</td></tr>';
    }
    $html[] = '</tbody></table></div></div>';
    $html[] = '</div>';

    return implode('', $html);
}

function premium_tab_href(string $tab, ?array $campaign = null): string
{
    $params = ['tab=' . urlencode($tab)];
    $campaignId = (int) ($campaign['id'] ?? 0);
    if ($campaignId > 0) {
        array_unshift($params, 'campaign_id=' . $campaignId);
    }

    return 'premium?' . implode('&', $params);
}

$premiumSupportWhatsappUrl = '';
if ($user) {
    $premiumSupportUserName = trim((string) ($user['name'] ?? ''));
    $premiumSupportUserEmail = trim((string) ($user['email'] ?? ''));
    $premiumSupportCampaignLabel = premium_selected_campaign_label($campaign);
    $premiumSupportWhatsappMessage = implode("\n", [
        'Olá! Preciso de ajuda no Apoia Candidato Premium.',
        'Usuário: ' . ($premiumSupportUserName !== '' ? $premiumSupportUserName : 'Não informado'),
        'E-mail: ' . ($premiumSupportUserEmail !== '' ? $premiumSupportUserEmail : 'Não informado'),
        'Campanha: ' . $premiumSupportCampaignLabel,
        'Pode me atender?',
    ]);
    $premiumSupportWhatsappUrl = 'https://wa.me/' . $premiumWhatsappPhone . '?text=' . rawurlencode($premiumSupportWhatsappMessage);
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <script src="assets/js/premium-bootstrap.js"></script>
    <title>Escritório Premium | Eleições Sergipe</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <?= premium_render_pwa_tags() ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/premium.css">
</head>
<body>
<div class="shell">
    <header class="topbar">
        <div>
            <div class="eyebrow">Apoia Candidato Premium</div>
            <h1>Escritório de campanha</h1>
            <p class="muted">Dados da campanha configuráveis, lideranças de 2024, agenda e previsões em um só lugar.</p>
        </div>
        <div class="topbar-right">
            <div class="topbar-actions">
                <div class="theme-switch" role="group" aria-label="Escolher tema">
                    <button
                        type="button"
                        class="theme-switch__btn"
                        data-theme-toggle="light"
                        aria-label="Modo claro"
                        title="Modo claro"
                    >&#9728;</button>
                    <button
                        type="button"
                        class="theme-switch__btn"
                        data-theme-toggle="dark"
                        aria-label="Modo escuro"
                        title="Modo escuro"
                    >&#9790;</button>
                </div>
            </div>
        <?php if ($user): ?>
            <div class="topbar-actions">
                <div class="pill">Olá, <?= premium_escape_html((string) ($user['name'] ?? '')) ?></div>
                <?php if ($accessBadgeLabel): ?>
                    <div class="pill"><?= premium_escape_html($accessBadgeLabel) ?></div>
                <?php endif; ?>
                <a class="btn ghost" href="premium_logout.php">Sair</a>
            </div>
            <?php if ($premiumSupportWhatsappUrl !== ''): ?>
                <div class="vip-support">
                    <a class="btn vip-support__btn" href="<?= premium_escape_html($premiumSupportWhatsappUrl) ?>" target="_blank" rel="noopener">
                        Pedir ajuda no WhatsApp
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="flash <?= premium_escape_html((string) ($flash['type'] ?? '')) ?>">
            <?= premium_escape_html((string) ($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <?php if (!$user): ?>
        <section class="auth-grid">
            <div class="panel">
                <div class="eyebrow">Área Restrita</div>
                <h2 style="font-size:2rem; margin-top: 12px;">Seu gabinete digital premium.</h2>
                <p class="muted" style="line-height:1.7; margin-top: 14px;">
                    Um painel executivo para comparar o capital político de 2022 com as lideranças de 2024, organizar agenda,
                    calibrar pesos do modelo e enxergar projeções por cidade, região e estado.
                </p>
                <div class="pill-row">
                    <span class="pill">Comparativo histórico x previsão</span>
                    <span class="pill">Agenda estratégica</span>
                    <span class="pill">Lideranças 2024</span>
                    <span class="pill">Relatórios premium</span>
                </div>
                <div class="whatsapp-presentation">
                    <div>
                        <strong>Quer ver isso funcionando na sua campanha?</strong>
                        <p class="muted">Agende uma apresentação rápida pelo WhatsApp e veja como transformar votos anteriores, apoios locais e agenda em um mapa claro de prioridades para 2026.</p>
                    </div>
                    <a class="btn whatsapp-cta" href="<?= premium_escape_html($premiumWhatsappUrl) ?>" target="_blank" rel="noopener">
                        Solicitar apresentação
                    </a>
                </div>
            </div>
            <div class="panel auth-card">
                <h3>Acesso premium</h3>
                <p class="muted">Use as credenciais premium para entrar no escritório da campanha. Se ainda não tem acesso, solicite uma apresentação pelo WhatsApp.</p>
                <form method="post" action="premium" style="margin-top:16px;">
                    <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                    <input type="hidden" name="action" value="login">
                    <div class="form-grid">
                        <label>E-mail
                            <input type="email" name="email" placeholder="premium@apoiacandidato.com.br" required>
                        </label>
                        <label>Senha
                            <input type="password" name="password" placeholder="••••••••" required>
                        </label>
                    </div>
                    <div class="action-row">
                        <button class="btn primary" type="submit">Entrar no premium</button>
                    </div>
                </form>
            </div>
        </section>
    <?php else: ?>
        <?php
            $activeCampaignNumber = premium_parse_candidate_number($campaign['candidate_number'] ?? null);
            if ($activeCampaignNumber === null) {
                $activeCampaignNumber = premium_parse_candidate_number($baseline['candidate_number'] ?? null);
            }
            $homeHeroMessages = [
                'Este sistema transforma dados eleitorais em leitura estratégica para indicar prioridades, riscos e oportunidades da campanha. As projeções servem como apoio técnico à decisão, não como garantia de resultado: vitória exige organização, presença, disciplina de execução e trabalho político consistente no território.',
                'Inteligência analítica para converter dados em estratégia. Nosso sistema processa o histórico de 2022 e 2024 para oferecer projeções precisas, permitindo ajustes em tempo real sobre visibilidade e investimento. Lembre-se: os dados iluminam o caminho, mas a vitória é construída com trabalho árduo e organização em campo. Transforme informação em sucesso.',
                'Onde a ciência de dados encontra a força da militância. Esta plataforma oferece uma bússola baseada em evidências reais, comparando ciclos eleitorais e fatores de porte regional. Entendemos que a ferramenta maximiza suas chances, mas o resultado final depende da execução incansável do seu gabinete. Com dados organizados e trabalho constante, o sucesso está ao seu alcance.',
                'Decisões baseadas em dados, vitórias conquistadas com trabalho. Utilize nossa base analítica para ajustar o rumo da sua campanha com precisão cirúrgica. O sistema fornece o mapa e as previsões; a organização e o suor da sua equipe garantem o destino final. Estruture sua campanha, otimize seus recursos e aproxime-se do êxito.',
            ];

            $activeCampaignLabel = trim(implode(' • ', array_filter([
                (string) ($campaign['campaign_name'] ?? 'Campanha'),
                (string) ($campaign['candidate_name'] ?? ''),
            ], static fn(string $item): bool => $item !== '')));
            $activeCampaignSubtitle = premium_selected_campaign_subtitle($campaign, $activeCampaignNumber);
            $candidatePhotoPath = trim((string) ($campaign['candidate_photo_path'] ?? ''));
        ?>
        <section class="premium-workspace">
            <aside class="premium-sidebar panel">
                <div class="premium-sidebar__campaign">
                    <div class="eyebrow">Navegação</div>
                    <h3><?= premium_escape_html($campaign ? ((string) ($campaign['campaign_name'] ?? 'Campanha ativa')) : 'Sem campanha ativa') ?></h3>
                    <p class="muted"><?= premium_escape_html($campaign ? ((string) ($campaign['candidate_name'] ?? '')) : 'Use as opções avançadas para criar a campanha.') ?></p>
                </div>
                <nav class="premium-sidebar__nav" aria-label="Seções do premium">
                    <a class="premium-sidebar__link<?= $activeTab === 'home' ? ' is-active' : '' ?>" href="<?= premium_escape_html(premium_tab_href('home', $campaign)) ?>">Home</a>
                    <?php if ($isSenateCampaign): ?>
                    <a class="premium-sidebar__link<?= $activeTab === 'senado' ? ' is-active' : '' ?>" href="<?= premium_escape_html(premium_tab_href('senado', $campaign)) ?>">Projeção Senado</a>
                    <?php else: ?>
                    <a class="premium-sidebar__link<?= $activeTab === 'liderancas' ? ' is-active' : '' ?>" href="<?= premium_escape_html(premium_tab_href('liderancas', $campaign)) ?>">Lideranças</a>
                    <?php endif; ?>
                    <a class="premium-sidebar__link<?= $activeTab === 'relatorios' ? ' is-active' : '' ?>" href="<?= premium_escape_html(premium_tab_href('relatorios', $campaign)) ?>">Relatórios</a>
                    <a class="premium-sidebar__link" href="premium_pesquisas.php<?= $campaign ? '?campaign_id=' . (int)$campaign['id'] : '' ?>">Pesquisas eleitorais</a>
                    <a class="premium-sidebar__link<?= $activeTab === 'agenda' ? ' is-active' : '' ?>" href="<?= premium_escape_html(premium_tab_href('agenda', $campaign)) ?>">Agenda de campanha</a>
                    <a class="premium-sidebar__link" href="premium_dicas_campanha.php<?= $campaign ? '?campaign_id=' . (int)$campaign['id'] : '' ?>">Estratégias de campanha</a>
                    <a class="premium-sidebar__link" href="premium_perfil_eleitor.php<?= $campaign ? '?campaign_id=' . (int)$campaign['id'] : '' ?>">Perfil do eleitorado</a>
                    <a class="premium-sidebar__link<?= $activeTab === 'opcoes' ? ' is-active' : '' ?>" href="<?= premium_escape_html(premium_tab_href('opcoes', $campaign)) ?>">Opções avançadas</a>
                </nav>
                <?php if ($user && !$isAdmin): ?>
                    <?= premium_render_onboarding_panel($campaign, $activeTab, $onboardingStudyExcerpt) ?>
                <?php endif; ?>
            </aside>
            <div class="premium-main">
        <?php if ($campaign && $activeTab === 'home'): ?>
        <section class="panel hero hero--active">
            <div class="copy">
                <div class="eyebrow">Escritório ativo</div>
                <h2 style="font-size:2rem; margin-top: 12px;"><?= premium_escape_html($activeCampaignLabel) ?></h2>
                <?php if ($activeCampaignSubtitle !== ''): ?>
                    <p class="muted" style="margin-top: 12px;"><?= premium_escape_html($activeCampaignSubtitle) ?></p>
                <?php endif; ?>
                <div class="hero-message-rotator" data-hero-rotator data-hero-interval="300000" aria-label="Mensagens estratégicas da campanha">
                    <div class="hero-message-viewport">
                        <?php foreach ($homeHeroMessages as $messageIndex => $homeHeroMessage): ?>
                            <p class="muted hero-message<?= $messageIndex === 0 ? ' is-active' : '' ?>"<?= $messageIndex === 0 ? '' : ' hidden' ?> data-hero-message style="margin-top: 12px;">
                                <?= premium_escape_html($homeHeroMessage) ?>
                            </p>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($homeHeroMessages) > 1): ?>
                    <div class="hero-message-controls" role="group" aria-label="Selecionar mensagem">
                        <?php foreach ($homeHeroMessages as $messageIndex => $homeHeroMessage): ?>
                            <button
                                class="hero-message-control<?= $messageIndex === 0 ? ' is-active' : '' ?>"
                                type="button"
                                data-hero-message-trigger
                                data-hero-message-index="<?= $messageIndex ?>"
                                aria-label="Exibir mensagem <?= $messageIndex + 1 ?>"
                                aria-pressed="<?= $messageIndex === 0 ? 'true' : 'false' ?>"
                            ></button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="candidate-photo-card">
                <?php if ($candidatePhotoPath !== ''): ?>
                    <img src="<?= premium_escape_html($candidatePhotoPath) ?>" alt="Foto de <?= premium_escape_html((string) ($campaign['candidate_name'] ?? 'candidato')) ?>">
                <?php else: ?>
                    <div class="candidate-photo-card__empty">
                        <span><?= premium_escape_html(premium_fmt_candidate_number_plain($activeCampaignNumber)) ?></span>
                    </div>
                <?php endif; ?>
                <div class="candidate-photo-card__caption">
                    <?php if ($activeCampaignNumber !== null): ?>
                        <span class="candidate-photo-card__number"><?= premium_escape_html(premium_fmt_candidate_number_plain($activeCampaignNumber)) ?></span>
                    <?php endif; ?>
                    <strong><?= premium_escape_html((string) ($campaign['candidate_name'] ?? 'Candidato')) ?></strong>
                    <span><?= premium_escape_html((string) ($campaign['candidate_cargo'] ?? '')) ?></span>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($isAdmin && $activeTab === 'opcoes'): ?>
            <section class="panel" id="premiumUsersPanel">
                <div class="section-title">
                    <div>
                        <div class="eyebrow">Administração</div>
                        <h2>Usuários premium</h2>
                    </div>
                    <div class="pill-row" style="margin-top: 0;">
                        <span class="pill">Total: <?= premium_fmt_int((int) ($premiumUserSummary['total'] ?? 0)) ?></span>
                        <span class="pill">Ativos: <?= premium_fmt_int((int) ($premiumUserSummary['active'] ?? 0)) ?></span>
                        <span class="pill">Inativos: <?= premium_fmt_int((int) ($premiumUserSummary['inactive'] ?? 0)) ?></span>
                    </div>
                </div>
                <p class="panel-note">
                    Crie novos acessos premium, acompanhe o último login e altere o status das contas sem sair do painel.
                    Esta área fica visível apenas para o usuário administrador.
                </p>
                <form method="post" action="premium_actions.php" class="campaign-form">
                    <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                    <input type="hidden" name="action" value="create_premium_user">
                    <div class="form-grid compact">
                        <label>Nome completo
                            <input type="text" name="name" maxlength="150" required placeholder="Nome do usuário">
                        </label>
                        <label>E-mail
                            <input type="email" name="email" maxlength="190" required placeholder="novo@exemplo.com">
                        </label>
                        <label>Senha temporária
                            <input type="password" name="password" minlength="8" required autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                        </label>
                        <label>Confirmar senha
                            <input type="password" name="password_confirm" minlength="8" required autocomplete="new-password" placeholder="Repita a senha">
                        </label>
                        <label>Status inicial
                            <select name="status">
                                <option value="active" selected>Ativo</option>
                                <option value="inactive">Inativo</option>
                            </select>
                            <span class="field-help">Use "Inativo" para preparar o acesso sem liberar o login imediatamente.</span>
                        </label>
                    </div>
                    <div class="action-row">
                        <button class="btn primary" type="submit">Cadastrar usuário</button>
                    </div>
                </form>

                <div class="table-wrap" style="margin-top: 14px;">
                    <table class="admin-user-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Status</th>
                                <th>Criado em</th>
                                <th>Último login</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pagedUsers): ?>
                                <?php foreach ($pagedUsers as $premiumUser): ?>
                                    <?php
                                        $premiumUserId = (int) ($premiumUser['id'] ?? 0);
                                        $premiumUserName = (string) ($premiumUser['name'] ?? '');
                                        $premiumUserxmail = (string) ($premiumUser['email'] ?? '');
                                        $premiumUserStatus = (string) ($premiumUser['status'] ?? 'inactive');
                                        $premiumUserCreatedAt = premium_fmt_datetime((string) ($premiumUser['created_at'] ?? ''));
                                        $premiumUserLastLoginAt = premium_fmt_datetime((string) ($premiumUser['last_login_at'] ?? ''));
                                        $isCurrentUser = $premiumUserId === (int) ($user['id'] ?? 0);
                                    ?>
                                    <tr>
                                        <td>
                                            <?= premium_escape_html($premiumUserName) ?>
                                            <?php if ($isCurrentUser): ?>
                                                <span class="pill">Conta atual</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= premium_escape_html($premiumUserxmail) ?></td>
                                        <td>
                                            <span class="user-status <?= $premiumUserStatus === 'active' ? 'user-status--active' : 'user-status--inactive' ?>">
                                                <?= $premiumUserStatus === 'active' ? 'Ativo' : 'Inativo' ?>
                                            </span>
                                        </td>
                                        <td><?= premium_escape_html($premiumUserCreatedAt) ?></td>
                                        <td><?= premium_escape_html($premiumUserLastLoginAt) ?></td>
                                        <td>
                                            <div class="user-actions">
                                                <?php if ($isCurrentUser): ?>
                                                    <span class="muted">Conta atual</span>
                                                <?php else: ?>
                                                    <form method="post" action="premium_actions.php" onsubmit="return confirm('Alterar o status deste usuário?');">
                                                        <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                                                        <input type="hidden" name="action" value="toggle_premium_user_status">
                                                        <input type="hidden" name="user_id" value="<?= $premiumUserId ?>">
                                                        <input type="hidden" name="target_status" value="<?= $premiumUserStatus === 'active' ? 'inactive' : 'active' ?>">
                                                        <button class="btn ghost btn-small" type="submit"><?= $premiumUserStatus === 'active' ? 'Desativar' : 'Reativar' ?></button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">Nenhum usuário premium cadastrado ainda.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if ($totalUserPages > 1): ?>
                    <div class="pagination-bar">
                        <?php
                        $paginationBase = 'premium?tab=opcoes';
                        $paginationCampaignId = (int) ($campaign['id'] ?? 0);
                        if ($paginationCampaignId > 0) {
                            $paginationBase = 'premium?campaign_id=' . $paginationCampaignId . '&tab=opcoes';
                        }
                        ?>
                        <?php if ($userPage > 1): ?>
                            <a class="pagination-btn" href="<?= $paginationBase ?>&user_page=<?= $userPage - 1 ?>">‹ Anterior</a>
                        <?php else: ?>
                            <span class="pagination-btn pagination-btn--disabled">‹ Anterior</span>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalUserPages; $p++): ?>
                            <a class="pagination-btn <?= $p === $userPage ? 'pagination-btn--active' : '' ?>"
                               href="<?= $paginationBase ?>&user_page=<?= $p ?>"><?= $p ?></a>
                        <?php endfor; ?>

                        <?php if ($userPage < $totalUserPages): ?>
                            <a class="pagination-btn" href="<?= $paginationBase ?>&user_page=<?= $userPage + 1 ?>">Próximo ›</a>
                        <?php else: ?>
                            <span class="pagination-btn pagination-btn--disabled">Próximo ›</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="panel" id="premiumCampaignsPanel">
                <div class="section-title">
                    <div>
                        <div class="eyebrow">Campanhas</div>
                        <h2>Campanhas de todos os usuários</h2>
                    </div>
                    <div class="pill-row" style="margin-top: 0;">
                        <span class="pill">Total: <?= premium_fmt_int(count($premiumCampaigns)) ?></span>
                    </div>
                </div>
                <p class="panel-note">
                    Exclua campanhas próprias ou de qualquer conta premium. A exclusão remove dados da campanha, lideranças, agenda, pesos e histórico de projeção.
                </p>
                <div class="table-wrap" style="margin-top: 14px;">
                    <table class="admin-campaign-table">
                        <thead>
                            <tr>
                                <th>Campanha</th>
                                <th>Usuário</th>
                                <th>E-mail</th>
                                <th>Candidato</th>
                                <th>Cargo</th>
                                <th>Status</th>
                                <th>Criada em</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pagedCampaigns): ?>
                                <?php foreach ($pagedCampaigns as $adminCampaign): ?>
                                    <?php
                                        $adminCampaignId = (int) ($adminCampaign['id'] ?? 0);
                                        $adminCampaignName = (string) ($adminCampaign['campaign_name'] ?? 'Campanha');
                                        $adminCampaignUser = (string) ($adminCampaign['owner_name'] ?? 'Usuário');
                                        $adminCampaignxmail = (string) ($adminCampaign['owner_email'] ?? '');
                                        $adminCampaignCandidate = (string) ($adminCampaign['candidate_name'] ?? '');
                                        $adminCampaignCargo = (string) ($adminCampaign['candidate_cargo'] ?? '');
                                        $adminCampaignStatus = (string) ($adminCampaign['status'] ?? 'inactive');
                                        $adminCampaignCreatedAt = premium_fmt_datetime((string) ($adminCampaign['created_at'] ?? ''));
                                    ?>
                                    <tr>
                                        <td><?= premium_escape_html($adminCampaignName) ?></td>
                                        <td><?= premium_escape_html($adminCampaignUser) ?></td>
                                        <td><?= premium_escape_html($adminCampaignxmail) ?></td>
                                        <td><?= premium_escape_html($adminCampaignCandidate) ?></td>
                                        <td><?= premium_escape_html($adminCampaignCargo) ?></td>
                                        <td>
                                            <span class="user-status <?= $adminCampaignStatus === 'active' ? 'user-status--active' : 'user-status--inactive' ?>">
                                                <?= $adminCampaignStatus === 'active' ? 'Ativa' : 'Arquivada' ?>
                                            </span>
                                        </td>
                                        <td><?= premium_escape_html($adminCampaignCreatedAt) ?></td>
                                        <td>
                                            <div class="user-actions">
                                                <a class="btn ghost btn-small" href="premium?campaign_id=<?= $adminCampaignId ?>&amp;tab=opcoes">Abrir/editar</a>
                                                <details class="admin-danger-menu">
                                                    <summary>Avancado</summary>
                                                    <form method="post" action="premium_actions.php" onsubmit="return confirm('Excluir esta campanha permanentemente? Isso apagará os dados da campanha, lideranças, agenda e pesos.');">
                                                        <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                                                        <input type="hidden" name="action" value="delete_campaign">
                                                        <input type="hidden" name="campaign_id" value="<?= $adminCampaignId ?>">
                                                        <label>Confirmacao
                                                            <input type="text" name="delete_confirmation" autocomplete="off" required placeholder="EXCLUIR CAMPANHA">
                                                        </label>
                                                        <button class="btn danger btn-small" type="submit">Excluir</button>
                                                    </form>
                                                </details>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="empty-state">Nenhuma campanha cadastrada ainda.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if ($totalCampaignPages > 1): ?>
                    <div class="pagination-bar">
                        <?php
                        $campaignPaginationBase = 'premium?tab=opcoes';
                        ?>
                        <?php if ($campaignPage > 1): ?>
                            <a class="pagination-btn" href="<?= $campaignPaginationBase ?>&campaign_page=<?= $campaignPage - 1 ?>">‹ Anterior</a>
                        <?php else: ?>
                            <span class="pagination-btn pagination-btn--disabled">‹ Anterior</span>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalCampaignPages; $p++): ?>
                            <a class="pagination-btn <?= $p === $campaignPage ? 'pagination-btn--active' : '' ?>"
                               href="<?= $campaignPaginationBase ?>&campaign_page=<?= $p ?>"><?= $p ?></a>
                        <?php endfor; ?>

                        <?php if ($campaignPage < $totalCampaignPages): ?>
                            <a class="pagination-btn" href="<?= $campaignPaginationBase ?>&campaign_page=<?= $campaignPage + 1 ?>">Próximo ›</a>
                        <?php else: ?>
                            <span class="pagination-btn pagination-btn--disabled">Próximo ›</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
        <?php if (!$campaign && $activeTab === 'opcoes'): ?>
            <section class="panel" id="campaignCreatePanel">
                <div class="section-title">
                    <div>
                        <div class="eyebrow">Primeiro passo</div>
                        <h2>Criar a primeira campanha</h2>
                    </div>
                </div>
                <form method="post" action="premium_actions.php" class="campaign-form baseline-form" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                    <input type="hidden" name="action" value="create_campaign">
                    <input type="hidden" name="redirect_tab" value="opcoes">
                    <div class="form-grid">
                        <label>Nome da campanha
                            <input type="text" name="campaign_name" placeholder="Gabinete de campanha" required>
                        </label>
                        <label>Candidato
                            <input type="text" name="candidate_name" placeholder="Nome do candidato" required>
                        </label>
                        <label>Cargo
                            <input type="text" name="candidate_cargo" placeholder="Deputado Federal, Estadual..." required>
                        </label>
                        <label>Nº campanha 2026
                            <input type="text" name="candidate_number" inputmode="numeric" placeholder="Opcional">
                        </label>
                        <label>Ano-base
                            <input type="number" name="baseline_year" value="2022" min="2018" step="4">
                        </label>
                    </div>
                    <div class="form-grid three-cols">
                        <label>Município-base
                            <input type="text" name="current_municipio" placeholder="Cidade principal, se houver">
                        </label>
                        <label>Região-base
                            <input type="text" name="current_region" placeholder="Região principal, se houver">
                        </label>
                        <?php if ($isAdmin): ?>
                        <label>Responsável pela campanha
                            <select name="target_user_id" required>
                                <option value="">Selecione o usuário</option>
                                <?php foreach ($premiumUsers as $pu):
                                    if ($pu['status'] !== 'active') continue; ?>
                                    <option value="<?= (int) $pu['id'] ?>">
                                        <?= premium_escape_html($pu['name']) ?> — <?= premium_escape_html($pu['email']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <?php endif; ?>
                    </div>
                    <div class="baseline-notes-photo">
                        <label>Notas
                            <textarea name="notes" rows="3" placeholder="Contexto da campanha, público, restrições e metas."></textarea>
                        </label>
                        <label>Foto do candidato
                            <input type="file" name="candidate_photo" accept="image/jpeg,image/png,image/webp">
                            <span class="field-help">JPG, PNG ou WEBP até 3 MB.</span>
                        </label>
                    </div>
                    <?= premium_render_allied_party_selector($availableParties) ?>
                    <div class="action-row">
                        <button class="btn primary" type="submit">Criar campanha</button>
                    </div>
                </form>
            </section>
        <?php elseif ($campaign): ?>
            <?php if ($activeTab === 'home'): ?>
            <?php
                $homeForecast = $isSenateCampaign ? $senateForecast : $forecast;
                $homeProjectionSub = $isSenateCampaign ? 'Cálculo específico do Senado' : 'Cenário com os pesos atuais';
                $homeSourceLabel = $isSenateCampaign ? 'Fontes Senado' : 'Lideranças ativas';
                $homeSourceValue = $isSenateCampaign ? count($senateSources) : count($leaders);
                $homeSourceSub = $isSenateCampaign ? 'Fontes adicionadas ao módulo Senado' : 'Lideranças adicionadas ao escritório';
                $homeBaselineLabel = $isSenateCampaign ? 'Senado 2018' : $campaignBaselineLabel;
                $homeBaselineVotes = $isSenateCampaign ? (int) ($senateForecast['baseline']['total_votes'] ?? 0) : (int) ($baseline['total_votes'] ?? 0);
            ?>
            <section class="stats-grid campaign-stats-grid">
                <?= premium_render_stat('Dados da campanha ' . $homeBaselineLabel, premium_fmt_int($homeBaselineVotes), 'Votação histórica do candidato'); ?>
                <?= premium_render_stat('Projeção base', premium_fmt_int((int) ($homeForecast['totals']['projected_base'] ?? 0)), $homeProjectionSub); ?>
                <?= premium_render_stat('Delta vs ' . $homeBaselineLabel, premium_fmt_int((int) (($homeForecast['totals']['projected_base'] ?? 0) - ($homeForecast['totals']['baseline_votes'] ?? $homeBaselineVotes))), 'Diferença absoluta sobre a base'); ?>
                <?= premium_render_stat($homeSourceLabel, premium_fmt_int($homeSourceValue), $homeSourceSub); ?>
            </section>

            <?php if ($advisor): ?>
                <section class="panel advisor-summary-panel">
                    <div class="section-title">
                        <div>
                            <div class="eyebrow">Conselheiro</div>
                            <h2>Próximas decisões de campanha</h2>
                        </div>
                        <a class="btn comparison-cta" href="premium_conselheiro.php?campaign_id=<?= (int) $campaign['id'] ?>">Abrir Conselheiro</a>
                    </div>
                    <div class="advisor-summary-grid">
                        <div>
                            <strong><?= premium_fmt_int((int) ($advisor['summary']['priority_cities'] ?? 0)) ?></strong>
                            <span>cidades de prioridade alta</span>
                        </div>
                        <div>
                            <strong><?= premium_fmt_int((int) ($advisor['summary']['risk_cities'] ?? 0)) ?></strong>
                            <span>bases históricas sem liderança</span>
                        </div>
                        <div>
                            <strong><?= premium_fmt_int((int) ($advisor['summary']['rentable_cities'] ?? 0)) ?></strong>
                            <span>cidades com alta rentabilidade</span>
                        </div>
                    </div>
                    <?php if (!empty($advisor['alerts'])): ?>
                        <div class="advisor-mini-alerts">
                            <?php foreach (array_slice((array) $advisor['alerts'], 0, 3) as $alert): ?>
                                <article>
                                    <strong><?= premium_escape_html((string) ($alert['title'] ?? 'Alerta')) ?></strong>
                                    <span><?= premium_escape_html((string) ($alert['text'] ?? '')) ?></span>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <section class="panel">
                <div class="section-title">
                    <div>
                        <div class="eyebrow">Atalhos</div>
                        <h2>Ferramentas principais da campanha</h2>
                    </div>
                    <a class="btn ghost" href="<?= premium_escape_html(premium_tab_href('relatorios', $campaign)) ?>">Ver relatórios</a>
                </div>
                    <div class="campaign-shortcuts__actions premium-home-shortcuts">
                    <a class="btn ghost" href="<?= premium_escape_html(premium_tab_href('agenda', $campaign)) ?>">Agenda de campanha</a>
                    <?php if ($isSenateCampaign): ?>
                    <a class="btn ghost" href="<?= premium_escape_html(premium_tab_href('senado', $campaign)) ?>">Projeção Senado</a>
                    <?php endif; ?>
                    <button class="btn comparison-cta" type="button" data-city-comparison-open>Comparar cidades</button>
                    <a class="btn ghost" href="premium_conselheiro.php?campaign_id=<?= (int) $campaign['id'] ?>">Abrir Conselheiro</a>
                </div>
            </section>
            <?php endif; ?>

            <?php if (false && $activeTab === 'opcoes' && ($baselinePanelHidden || $settingsPanelHidden)): ?>
                <section class="campaign-shortcuts">
                    <div class="campaign-shortcuts__copy">
                        <div class="campaign-shortcuts__title">Seus blocos estratégicos estão recolhidos</div>
                        <div class="campaign-shortcuts__sub">A tela principal ficou mais limpa. Reabra apenas o que precisar revisar e mantenha o foco nas lideranças e nos cenários.</div>
                    </div>
                    <div class="campaign-shortcuts__actions">
                        <?php if ($baselinePanelHidden): ?>
                            <form method="post" action="premium_actions.php">
                                <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                                <input type="hidden" name="action" value="show_baseline_panel">
                                <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                                <button class="btn ghost" type="submit">Reabrir dados da campanha</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($settingsPanelHidden): ?>
                            <form method="post" action="premium_actions.php">
                                <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                                <input type="hidden" name="action" value="show_settings_panel">
                                <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                                <button class="btn ghost" type="submit">Reabrir pesos</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($activeTab === 'opcoes'): ?>
                <?php
                    $campaignCandidateNumber = premium_parse_candidate_number($campaign['candidate_number'] ?? null);
                    if ($campaignCandidateNumber === null) {
                        $campaignCandidateNumber = premium_parse_candidate_number($baseline['candidate_number'] ?? null);
                    }
                ?>
                <section class="panel">
                    <div class="section-title">
                        <div>
                            <div class="eyebrow">Opções avançadas</div>
                            <h2>Gerenciar conta e campanha</h2>
                        </div>
                    </div>

                    <div class="leaders-tabs" role="tablist" aria-label="Fluxo de opções avançadas">
                        <button class="leaders-tab-btn is-active" type="button" id="optionsModeCampaign"
                            data-options-mode-target="campaign" role="tab" aria-controls="optionsCampaignBody"
                            aria-selected="true">Dados da campanha</button>
                        <button class="leaders-tab-btn" type="button" id="optionsModeSettings"
                            data-options-mode-target="settings" role="tab" aria-controls="optionsSettingsBody"
                            aria-selected="false">Pesos do modelo</button>
                        <button class="leaders-tab-btn" type="button" id="optionsModeSecurity"
                            data-options-mode-target="security" role="tab" aria-controls="optionsSecurityBody"
                            aria-selected="false">Alterar senha</button>
                        <button class="leaders-tab-btn" type="button" id="optionsModeMembros"
                            data-options-mode-target="membros" role="tab" aria-controls="optionsMembrosBody"
                            aria-selected="false">Membros do gabinete</button>
                        <button class="leaders-tab-btn" type="button" id="optionsModeDelete"
                            data-options-mode-target="delete" role="tab" aria-controls="optionsDeleteBody"
                            aria-selected="false">Excluir campanha</button>
                    </div>

                    <div id="optionsCampaignBody" class="leaders-tab-panel" data-options-mode-panel="campaign"
                        role="tabpanel" aria-labelledby="optionsModeCampaign">
                        <form method="post" action="premium_actions.php" class="campaign-form baseline-form"
                            enctype="multipart/form-data" id="baselineBody">
                            <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                            <input type="hidden" name="action" value="update_campaign">
                            <input type="hidden" name="redirect_tab" value="opcoes">
                            <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                            <div class="form-grid">
                                <label>Nome da campanha
                                    <input type="text" name="campaign_name"
                                        value="<?= premium_escape_html((string) ($campaign['campaign_name'] ?? '')) ?>" required>
                                </label>
                                <label>Candidato
                                    <input type="text" name="candidate_name"
                                        value="<?= premium_escape_html((string) ($campaign['candidate_name'] ?? '')) ?>" required>
                                </label>
                                <label>Cargo
                                    <input type="text" name="candidate_cargo"
                                        value="<?= premium_escape_html((string) ($campaign['candidate_cargo'] ?? '')) ?>" required>
                                </label>
                                <label>Nº campanha 2026
                                    <input type="text" name="candidate_number" inputmode="numeric"
                                        value="<?= premium_escape_html(premium_fmt_candidate_number_plain($campaignCandidateNumber)) ?>"
                                        placeholder="Opcional">
                                </label>
                                <label>Ano-base
                                    <input type="number" name="baseline_year"
                                        value="<?= (int) ($campaign['baseline_year'] ?? 2022) ?>" min="2018" step="4">
                                </label>
                            </div>
                            <div class="form-grid three-cols">
                                <label>Município-base
                                    <input type="text" name="current_municipio"
                                        value="<?= premium_escape_html((string) ($campaign['current_municipio'] ?? '')) ?>"
                                        placeholder="Cidade principal, se houver">
                                </label>
                                <label>Região-base
                                    <input type="text" name="current_region"
                                        value="<?= premium_escape_html((string) ($campaign['current_region'] ?? '')) ?>"
                                        placeholder="Região principal, se houver">
                                </label>
                                <?php if ($isAdmin): ?>
                                <label>Responsável pela campanha
                                    <select name="target_user_id" required>
                                        <option value="">Selecione o usuário</option>
                                        <?php foreach ($premiumUsers as $pu):
                                            if ($pu['status'] !== 'active') continue; ?>
                                            <option value="<?= (int) $pu['id'] ?>" <?= (int) ($campaign['user_id'] ?? 0) === (int) $pu['id'] ? 'selected' : '' ?>>
                                                <?= premium_escape_html($pu['name']) ?> — <?= premium_escape_html($pu['email']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <?php endif; ?>
                            </div>
                            <div class="baseline-notes-photo">
                                <label>Notas
                                    <textarea name="notes" rows="3"><?= premium_escape_html((string) ($campaign['notes'] ?? '')) ?></textarea>
                                </label>
                                <label>Foto do candidato
                                    <input type="file" name="candidate_photo" accept="image/jpeg,image/png,image/webp">
                                    <span class="field-help">JPG, PNG ou WEBP até 3 MB.</span>
                                </label>
                            </div>
                            <?= premium_render_allied_party_selector($availableParties, $campaignAlliedPartyAcronyms) ?>
                            <div class="action-row">
                                <button class="btn primary" type="submit">Salvar dados da campanha</button>
                            </div>
                        </form>
                    </div>

                    <div id="optionsSettingsBody" class="leaders-tab-panel" data-options-mode-panel="settings"
                        role="tabpanel" aria-labelledby="optionsModeSettings" hidden>
                        <p class="panel-note">Cada peso ajusta uma parte da projeção. A base de <?= premium_escape_html($campaignBaselineLabel) ?> fica como comparativo e só entra no fallback onde não houver liderança cadastrada. <button type="button" class="panel-inline-link__btn" data-study-open>Ler base técnica</button></p>
                        <form method="post" action="premium_actions.php" class="settings-form">
                            <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                            <input type="hidden" name="action" value="save_settings">
                            <input type="hidden" name="redirect_tab" value="opcoes">
                            <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                            <div class="form-grid">
                                <label>Fallback <?= premium_escape_html($campaignBaselineLabel) ?>
                                    <input type="number" name="baseline_retention" value="<?= premium_escape_html((string) ($settings['baseline_retention'] ?? 0.30)) ?>" step="0.01" min="0" max="1">
                                    <span class="field-help">Usado apenas quando o município ou a região não tem lideranças cadastradas; nesse caso, a base histórica vira referência de projeção.</span>
                                </label>
                                <label>Transferência %
                                    <input type="number" name="transfer_rate_default" value="<?= premium_escape_html((string) ($settings['transfer_rate_default'] ?? 30)) ?>" step="0.01" min="0" max="100">
                                    <span class="field-help">Percentual médio da votação de uma liderança que pode migrar para o candidato apoiado.</span>
                                </label>
                                <label>Bônus alinhamento
                                    <input type="number" name="alignment_bonus" value="<?= premium_escape_html((string) ($settings['alignment_bonus'] ?? 0.20)) ?>" step="0.01" min="0" max="1">
                                    <span class="field-help">Reforço aplicado quando a liderança está alinhada ao executivo estadual ou ao grupo dominante.</span>
                                </label>
                                <label>Peso visibilidade
                                    <input type="number" name="visibility_weight" value="<?= premium_escape_html((string) ($settings['visibility_weight'] ?? 0.12)) ?>" step="0.01" min="0" max="1">
                                    <span class="field-help">Quanto a presença pública e o reconhecimento da liderança aumentam a chance de transferência.</span>
                                </label>
                                <label>Peso investimento
                                    <input type="number" name="investment_weight" value="<?= premium_escape_html((string) ($settings['investment_weight'] ?? 0.10)) ?>" step="0.01" min="0" max="1">
                                    <span class="field-help">Quanto obras, entregas e ações visíveis ampliam o valor político da liderança.</span>
                                </label>
                                <label>Peso margem
                                    <input type="number" name="margin_weight" value="<?= premium_escape_html((string) ($settings['margin_weight'] ?? 0.15)) ?>" step="0.01" min="0" max="1">
                                    <span class="field-help">Maior margem de vitória significa mais folga política e maior potencial de transferência.</span>
                                </label>
                                <label>Bônus cidade pequena
                                    <input type="number" name="small_city_bonus" value="<?= premium_escape_html((string) ($settings['small_city_bonus'] ?? 0.15)) ?>" step="0.01" min="0" max="1">
                                    <span class="field-help">Peso extra para municípios menores, onde a relação entre eleitor e liderança é mais direta.</span>
                                </label>
                                <label>Bônus cidade média
                                    <input type="number" name="medium_city_bonus" value="<?= premium_escape_html((string) ($settings['medium_city_bonus'] ?? 0.08)) ?>" step="0.01" min="0" max="1">
                                    <span class="field-help">Ajuste intermediário para cidades de porte médio, com rede territorial mais distribuída.</span>
                                </label>
                                <label>Bônus cidade grande
                                    <input type="number" name="large_city_bonus" value="<?= premium_escape_html((string) ($settings['large_city_bonus'] ?? 0.00)) ?>" step="0.01" min="0" max="1">
                                    <span class="field-help">Ajuste para grandes centros, onde a transferência costuma ser mais diluída.</span>
                                </label>
                                <label>Cenário conservador
                                    <input type="number" name="scenario_conservative" value="<?= premium_escape_html((string) ($settings['scenario_conservative'] ?? 0.90)) ?>" step="0.01" min="0" max="2">
                                    <span class="field-help">Multiplicador de cautela para uma leitura mais dura da conversão em votos.</span>
                                </label>
                                <label>Cenário base
                                    <input type="number" name="scenario_base" value="<?= premium_escape_html((string) ($settings['scenario_base'] ?? 1.00)) ?>" step="0.01" min="0" max="2">
                                    <span class="field-help">Leitura principal do modelo, usada como referência da campanha.</span>
                                </label>
                                <label>Cenário otimista
                                    <input type="number" name="scenario_optimistic" value="<?= premium_escape_html((string) ($settings['scenario_optimistic'] ?? 1.12)) ?>" step="0.01" min="0" max="3">
                                    <span class="field-help">Hipótese de maior eficiência da campanha e da rede de lideranças.</span>
                                </label>
                                <label>Pequeno até votos
                                    <input type="number" name="small_city_threshold" value="<?= premium_escape_html((string) ($settings['small_city_threshold'] ?? 10000)) ?>" step="1" min="0">
                                    <span class="field-help">Limite de votos totais para classificar um município como pequeno no modelo.</span>
                                </label>
                                <label>Médio até votos
                                    <input type="number" name="medium_city_threshold" value="<?= premium_escape_html((string) ($settings['medium_city_threshold'] ?? 30000)) ?>" step="1" min="0">
                                    <span class="field-help">Limite superior para classificar um município como médio antes de virar grande.</span>
                                </label>
                            </div>
                            <div class="action-row">
                                <button class="btn primary" type="submit">Salvar pesos</button>
                            </div>
                        </form>
                    </div>

                    <div id="optionsSecurityBody" class="leaders-tab-panel" data-options-mode-panel="security"
                        role="tabpanel" aria-labelledby="optionsModeSecurity" hidden>
                        <form method="post" action="premium_actions.php" class="campaign-form">
                            <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                            <input type="hidden" name="action" value="change_password">
                            <input type="hidden" name="redirect_tab" value="opcoes">
                            <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                            <div class="form-grid compact">
                                <label>Senha atual
                                    <input type="password" name="current_password" required>
                                </label>
                                <label>Nova senha
                                    <input type="password" name="new_password" minlength="8" required>
                                </label>
                                <label>Confirmar nova senha
                                    <input type="password" name="new_password_confirm" minlength="8" required>
                                </label>
                            </div>
                            <div class="action-row">
                                <button class="btn primary" type="submit">Salvar nova senha</button>
                            </div>
                        </form>
                    </div>

                    <div id="optionsMembrosBody" class="leaders-tab-panel" data-options-mode-panel="membros"
                        role="tabpanel" aria-labelledby="optionsModeMembros" hidden>
                        <?= premium_render_campaign_members_panel($campaignMembers ?? [], $campaign, $csrf) ?>
                    </div>

                    <div id="optionsDeleteBody" class="leaders-tab-panel" data-options-mode-panel="delete"
                        role="tabpanel" aria-labelledby="optionsModeDelete" hidden>
                        <p class="panel-note">A exclusão é permanente e remove dados da campanha, lideranças, agenda, pesos e histórico de projeção.</p>
                        <form method="post" action="premium_actions.php" class="campaign-delete-form"
                            onsubmit="return confirm('Excluir esta campanha permanentemente? Esta ação não pode ser desfeita.');">
                            <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                            <input type="hidden" name="action" value="delete_campaign">
                            <input type="hidden" name="redirect_tab" value="opcoes">
                            <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                            <label>Digite EXCLUIR CAMPANHA para confirmar
                                <input type="text" name="delete_confirmation" autocomplete="off" required>
                            </label>
                            <div class="action-row">
                                <button class="btn danger" type="submit">Excluir campanha</button>
                            </div>
                        </form>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($isSenateCampaign && $activeTab === 'senado'): ?>
            <?php
                $senateTotals = (array) ($senateForecast['totals'] ?? []);
                $senateBaseline = (array) ($senateForecast['baseline'] ?? []);
                $senateQueryValue = trim((string) ($_GET['senate_query'] ?? ''));
                $senateSourceCargoValue = trim((string) ($_GET['senate_source_cargo'] ?? 'all'));
                $senateMunicipalityValue = trim((string) ($_GET['senate_municipality'] ?? ''));
                $senateSourceYearValue = trim((string) ($_GET['senate_source_year'] ?? ''));
                $senateAlliedOnlyValue = (string) ($_GET['senate_allied_only'] ?? '') === '1';
                $hasSenateSearchOutput = $senateQueryValue !== ''
                    || ($senateSourceCargoValue !== '' && $senateSourceCargoValue !== 'all')
                    || $senateMunicipalityValue !== ''
                    || ($senateAlliedOnlyValue && $campaignAlliedPartyAcronyms !== []);
                $senateGovernmentSupport = !empty($settings['senate_state_government_support']);
                $senateGovernmentMultiplier = max(1.00, min(1.30, (float) ($settings['senate_government_multiplier'] ?? 1.08)));
                $senateOverlapMode = premium_senate_overlap_mode($settings['senate_overlap_mode'] ?? ($senateTotals['overlap_mode'] ?? 'alert_only'));
                $senateOverlapMetricLabel = $senateOverlapMode === 'automatic' ? 'Redutor aplicado' : 'Redutor sugerido';
                $senateOverlapMetricValue = $senateOverlapMode === 'automatic'
                    ? (int) ($senateTotals['overlap_discount'] ?? 0)
                    : (int) ($senateTotals['suggested_overlap_discount'] ?? 0);
                $senateOverlapMetricSub = $senateOverlapMode === 'automatic'
                    ? 'Controle automatico de bases duplicadas'
                    : 'Possivel reducao se o modo automatico for usado';
            ?>
            <section class="panel panel-tint panel-tint--leaders-search senate-panel">
                <div class="section-title">
                    <div>
                        <div class="eyebrow">Projeção Senado</div>
                        <h2>Fontes de voto e cenários majoritários</h2>
                    </div>
                </div>

                <div class="grid-3 senate-metrics">
                    <?= premium_render_stat('Base Senado 2018', premium_fmt_int((int) ($senateBaseline['total_votes'] ?? 0)), !empty($senateBaseline['found']) ? 'Base propria encontrada' : 'Base propria nao encontrada'); ?>
                    <?= premium_render_stat('Cenario base', premium_fmt_int((int) ($senateTotals['projected_base'] ?? 0)), $senateOverlapMode === 'automatic' ? 'Com redutor automatico e teto municipal' : 'Sem redutor automatico; alertas ativos'); ?>
                    <?= premium_render_stat($senateOverlapMetricLabel, premium_fmt_int($senateOverlapMetricValue), $senateOverlapMetricSub); ?>
                </div>

                <div class="grid-3 senate-metrics senate-metrics--scenarios">
                    <?= premium_render_stat('Conservador', premium_fmt_int((int) ($senateTotals['projected_conservative'] ?? 0)), 'Multiplicador ' . premium_escape_html((string) ($settings['scenario_conservative'] ?? 0.90))); ?>
                    <?= premium_render_stat('Base', premium_fmt_int((int) ($senateTotals['projected_base'] ?? 0)), 'Leitura principal'); ?>
                    <?= premium_render_stat('Otimista', premium_fmt_int((int) ($senateTotals['projected_optimistic'] ?? 0)), 'Multiplicador ' . premium_escape_html((string) ($settings['scenario_optimistic'] ?? 1.12))); ?>
                </div>

                <form method="post" action="premium_actions.php" class="senate-context-form">
                    <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                    <input type="hidden" name="action" value="update_senate_context">
                    <input type="hidden" name="redirect_tab" value="senado">
                    <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                    <label class="senate-context-form__check">
                        <input type="checkbox" name="senate_state_government_support" value="1" <?= $senateGovernmentSupport ? 'checked' : '' ?>>
                        <span>Apoio do governo estadual</span>
                    </label>
                    <label>Multiplicador
                        <input type="number" name="senate_government_multiplier" value="<?= premium_escape_html(number_format($senateGovernmentMultiplier, 2, '.', '')) ?>" min="1" max="1.3" step="0.01">
                    </label>
                    <label>Sobreposicao de bases
                        <select name="senate_overlap_mode">
                            <?php foreach (premium_senate_overlap_modes() as $modeValue => $modeMeta): ?>
                                <option value="<?= premium_escape_html($modeValue) ?>" <?= $senateOverlapMode === $modeValue ? 'selected' : '' ?>>
                                    <?= premium_escape_html((string) ($modeMeta['label'] ?? $modeValue)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="field-help"><?= premium_escape_html(premium_senate_overlap_mode_description($senateOverlapMode)) ?></span>
                    </label>
                    <button class="btn ghost btn-small" type="submit">Salvar contexto</button>
                </form>

                <?php if (empty($senateBaseline['found'])): ?>
                    <div class="empty-state senate-warning">Não encontrei votação própria de Senado em 2018 para este nome. Use a busca ou cadastre uma fonte manual para alimentar a projeção.</div>
                <?php endif; ?>
            </section>

            <section class="panel senate-panel">
                <div class="section-title">
                    <div>
                        <div class="eyebrow">Busca multiano</div>
                        <h2>Adicionar fonte de votos</h2>
                    </div>
                </div>
                <form method="get" action="premium" class="senate-search-form">
                    <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                    <input type="hidden" name="tab" value="senado">
                    <label>Nome, urna, número ou SQ
                        <input type="text" name="senate_query" value="<?= premium_escape_html($senateQueryValue) ?>" placeholder="Ex.: Yandra, André Moura, 555">
                    </label>
                    <label>Cargo
                        <select name="senate_source_cargo">
                            <option value="all" <?= $senateSourceCargoValue === 'all' || $senateSourceCargoValue === '' ? 'selected' : '' ?>>Todos os cargos</option>
                            <option value="deputado_federal" <?= $senateSourceCargoValue === 'deputado_federal' ? 'selected' : '' ?>>Deputados Federais</option>
                            <option value="deputado_estadual" <?= $senateSourceCargoValue === 'deputado_estadual' ? 'selected' : '' ?>>Deputados Estaduais</option>
                            <option value="prefeito" <?= $senateSourceCargoValue === 'prefeito' ? 'selected' : '' ?>>Prefeitos</option>
                            <option value="vereador" <?= $senateSourceCargoValue === 'vereador' ? 'selected' : '' ?>>Vereadores</option>
                        </select>
                    </label>
                    <label>Municipio
                        <select name="senate_municipality">
                            <option value="">Todos</option>
                            <?= premium_render_municipality_options($senateMunicipalityValue) ?>
                        </select>
                    </label>
                    <label>Ano Eleição
                        <select name="senate_source_year">
                            <option value="">Todos</option>
                            <option value="2018" <?= $senateSourceYearValue === '2018' ? 'selected' : '' ?>>2018</option>
                            <option value="2020" <?= $senateSourceYearValue === '2020' ? 'selected' : '' ?>>2020</option>
                            <option value="2022" <?= $senateSourceYearValue === '2022' ? 'selected' : '' ?>>2022</option>
                            <option value="2024" <?= $senateSourceYearValue === '2024' ? 'selected' : '' ?>>2024</option>
                        </select>
                    </label>
                    <?php if ($campaignAlliedPartyAcronyms): ?>
                    <label class="checkbox senate-search-form__check">
                        <input type="checkbox" name="senate_allied_only" value="1" <?= $senateAlliedOnlyValue ? 'checked' : '' ?>>
                        <span>Apenas partidos aliados</span>
                    </label>
                    <?php endif; ?>
                    <button class="btn primary" type="submit">Buscar fontes</button>
                </form>

                <?php if ($hasSenateSearchOutput): ?>
                    <div class="senate-block">
                        <h3 class="senate-block-title">Resultados da busca</h3>
                        <?= premium_render_senate_candidate_rows($senateSearchResults, $campaign, $csrf, 'Nenhuma fonte encontrada para a busca informada.') ?>
                    </div>
                <?php endif; ?>

                <details class="senate-manual-source">
                    <summary>Adicionar fonte manual</summary>
                    <form method="post" action="premium_actions.php" class="campaign-form senate-manual-source__form">
                        <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                        <input type="hidden" name="action" value="add_senate_source">
                        <input type="hidden" name="redirect_tab" value="senado">
                        <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                        <div class="form-grid compact">
                            <label>Ano
                                <select name="source_year">
                                    <option value="2018">2018</option>
                                    <option value="2020">2020</option>
                                    <option value="2022" selected>2022</option>
                                    <option value="2024">2024</option>
                                </select>
                            </label>
                            <label>Cargo
                                <input type="text" name="source_cargo" placeholder="Deputado Federal, Prefeito...">
                            </label>
                            <label>Nome da fonte
                                <input type="text" name="source_candidate_name" required placeholder="Nome do candidato ou liderança">
                            </label>
                            <label>Nome de urna
                                <input type="text" name="source_ballot_name" placeholder="Opcional">
                            </label>
                            <label>Partido
                                <input type="text" name="source_party" maxlength="20" placeholder="Opcional">
                            </label>
                            <label>Número
                                <input type="text" name="source_number" inputmode="numeric" placeholder="Opcional">
                            </label>
                            <label>SQ candidato
                                <input type="text" name="source_sq_candidato" placeholder="Opcional">
                            </label>
                            <label>Município/escopo
                                <input type="text" name="source_scope_label" placeholder="Cidade ou Votação Estadual">
                            </label>
                            <label>Total de votos
                                <input type="number" name="source_total_votes" value="0" min="0" step="1">
                            </label>
                            <label>% dos votos
                                <input type="number" name="source_vote_percent" min="0" max="100" step="0.01" placeholder="Opcional">
                            </label>
                            <label>Relação
                                <select name="relationship_type">
                                    <?= premium_render_senate_relationship_options('manual') ?>
                                </select>
                            </label>
                            <label>Migração %
                                <input type="number" name="transfer_rate" value="30.00" min="0" max="100" step="0.01">
                            </label>
                            <label>Confiança
                                <input type="number" name="confidence_score" value="50.00" min="0" max="100" step="0.01">
                            </label>
                        </div>
                        <label>Notas
                            <textarea name="notes" rows="2" placeholder="Ex.: filha, aliado estadual, base do agrupamento..."></textarea>
                        </label>
                        <div class="action-row">
                            <button class="btn primary" type="submit">Adicionar fonte manual</button>
                        </div>
                    </form>
                </details>
            </section>

            <section class="panel senate-panel">
                <div class="section-title">
                    <div>
                        <div class="eyebrow">Fontes cadastradas</div>
                        <h2>Base própria, aliados e lideranças</h2>
                    </div>
                </div>
                <?= premium_render_senate_registered_sources_table($senateSources, $campaign, $csrf) ?>
            </section>
            <?php endif; ?>

            <?php if ($activeTab === 'liderancas'): ?>
            <section class="panel panel-tint panel-tint--leaders-search">
                <div class="section-title">
                    <div>
                        <div class="eyebrow">Lideranças</div>
                        <h2>Gerenciar lideranças da campanha</h2>
                    </div>
                </div>

                <div class="leaders-tabs" role="tablist" aria-label="Fluxo de lideranças">
                    <button class="leaders-tab-btn is-active" type="button" id="leaderModeAdd"
                        data-leader-mode-target="add" role="tab" aria-controls="leaderSearchBody"
                        aria-selected="true">Adicionar lideranças à campanha</button>
                    <button class="leaders-tab-btn" type="button" id="leaderModeConsult"
                        data-leader-mode-target="consult" role="tab" aria-controls="leadersBody"
                        aria-selected="false">Consultar lideranças da campanha</button>
                </div>

                <div id="leaderSearchBody" class="leaders-tab-panel" data-leader-mode-panel="add" role="tabpanel"
                    aria-labelledby="leaderModeAdd">
                    <div class="search-grid leaders-search-grid">
                        <label>Cargo
                            <select id="searchCargo">
                                <option value="Prefeito">Prefeito</option>
                                <option value="Vereador">Vereador</option>
                            </select>
                        </label>
                        <label>Município
                            <select id="searchMunicipality">
                                <option value="">Todos</option>
                                <?= premium_render_municipality_options() ?>
                            </select>
                        </label>
                        <label>Busca
                            <input type="text" id="searchQuery" placeholder="Nome da urna, candidato ou número">
                        </label>
                    </div>
                    <div class="action-row">
                        <button class="btn primary" type="button" id="searchLeadersBtn">Buscar lideranças</button>
                        <?php if ($campaignAlliedPartyAcronyms): ?>
                        <label class="checkbox leader-search-allied-filter">
                            <input type="checkbox" id="searchAlliedOnly" value="1">
                            <span>Apenas partidos aliados</span>
                        </label>
                        <?php endif; ?>
                    </div>
                    <div class="table-wrap leader-search-scroll leader-search-scroll--compact" style="margin-top: 14px;">
                        <table class="leader-search-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="leaderSelectAll" aria-label="Selecionar todas as lideranças exibidas"></th>
                                    <th>Município</th>
                                    <th>Liderança</th>
                                    <th>Partido</th>
                                    <th>Votos</th>
                                    <th>Margem</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody id="leaderSearchResults">
                                <tr><td colspan="7" class="muted">Busque lideranças para preencher a campanha.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="leader-batch-toolbar">
                        <div class="leader-batch-toolbar__copy">
                            <div class="leader-batch-toolbar__title"><span id="leaderBatchSelectedCount">0 selecionadas</span></div>
                            <div class="leader-batch-toolbar__sub">Os pesos padrão da campanha serão aplicados automaticamente para todas as lideranças marcadas.</div>
                        </div>
                        <div class="leader-batch-toolbar__actions">
                            <button class="btn ghost btn-small" type="button" id="leaderBatchSelectAllBtn">Selecionar todos</button>
                            <button class="btn ghost btn-small" type="button" id="leaderBatchClearBtn">Desmarcar</button>
                            <form method="post" action="premium_actions.php" id="leaderBatchForm" class="leader-batch-form">
                                <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                                <input type="hidden" name="action" value="add_leaders_batch">
                                <input type="hidden" name="redirect_tab" value="liderancas">
                                <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                                <input type="hidden" name="leaders_json" id="leaderBatchPayload">
                                <input type="hidden" name="batch_cargo" id="leaderBatchCargo" value="">
                                <button class="btn primary btn-small" type="submit" id="leaderBatchSubmitBtn" disabled>Adicionar lideranças</button>
                            </form>
                        </div>
                    </div>

                    <div class="leaders-external-entry">
                        <button class="btn ghost btn-small" type="button" data-toggle-target="leaderAddBody"
                            aria-controls="leaderAddBody" aria-expanded="false">Adicionar liderança fora de 2024</button>
                        <p class="muted">Use para inserir lideranças que não participaram de 2024 sem poluir a tela principal.</p>
                    </div>

                    <div id="leaderAddBody" hidden style="margin-top: 10px;">
                        <form method="post" action="premium_actions.php" id="leaderForm">
                            <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                            <input type="hidden" name="action" value="add_leader">
                            <input type="hidden" name="redirect_tab" value="liderancas">
                            <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                            <input type="hidden" name="source_sq_candidato" id="sourceSq">
                            <input type="hidden" name="source_nr_votavel" id="sourceNrVotavel">
                            <input type="hidden" name="source_turno" id="sourceTurno" value="1">
                            <input type="hidden" name="transfer_rate" value="100">
                            <input type="hidden" name="is_manual_projection" value="1">
                            <div class="form-grid compact">
                                <label>Município
                                    <select name="municipality" id="leaderMunicipality" required onchange="syncLeaderRegionFromMunicipality(this)">
                                        <option value="">Selecione</option>
                                        <?= premium_render_municipality_options() ?>
                                    </select>
                                </label>
                                <label>Nome
                                    <input type="text" name="leader_name" id="leaderName" required placeholder="Nome da liderança">
                                </label>
                                <label>Votos esperados
                                    <input type="number" name="leader_votes_2024" id="leaderVotes" value="0" min="0" step="1" placeholder="Total de votos esperados">
                                    <span class="field-help">Passa a ser considerado como Projeção 2026. Como não há base de votação em 2024, a transferência será 100% e a base transferível será igual aos votos esperados.</span>
                                </label>
                            </div>
                            <div class="action-row">
                                <button class="btn primary" type="submit">Adicionar liderança</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="leadersBody" class="leaders-tab-panel" data-leader-mode-panel="consult" role="tabpanel"
                    aria-labelledby="leaderModeConsult" hidden>
                    <div class="leaders-consult-head">
                        <h2>Lideranças participantes da campanha</h2>
                        <p class="muted">
                            Lista resumida para leitura rápida. A tabela está ordenada por cidade e por votos dentro de cada cidade. Clique no nome ou em <strong>Abrir</strong> para ver todos os dados, editar pesos e ajustar a estratégia.
                        </p>
                    </div>
                    <?php if ($leaders): ?>
                        <?= premium_render_leaders_table($leaders, (int) ($baseline['total_votes'] ?? 0), (int) ($forecast['totals']['projected_base'] ?? 0), (array) ($forecast['settings'] ?? $settings ?? []), $campaign, $csrf) ?>
                    <?php elseif (!$isSenateCampaign): ?>
                        <div class="empty-state">Ainda não há lideranças cadastradas. Use a busca para adicionar prefeitos e vereadores à campanha.</div>
                    <?php endif; ?>
                    <?php if ($isSenateCampaign && !empty($senateSources)): ?>
                        <?= premium_render_senate_sources_leaders_view($senateSources, $senateForecast) ?>
                    <?php elseif ($isSenateCampaign && !$leaders): ?>
                        <div class="empty-state">Ainda não há fontes cadastradas. Acesse a aba <a href="?campaign_id=<?= (int) ($campaign['id'] ?? 0) ?>&tab=senado">Senado</a> para adicionar fontes à campanha.</div>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>

            <?= premium_render_leader_modal($campaign, $csrf) ?>
            <?= premium_render_scope_modal($campaignBaselineYear) ?>
            <?= premium_render_city_comparison_modal($reportForecast, $campaignBaselineYear) ?>
            <?= premium_render_agenda_list_modal($agenda) ?>
            <?= premium_render_agenda_detail_modal($campaign, $csrf) ?>
            <?= premium_render_study_modal($onboardingStudyExcerpt) ?>

            <?php if ($activeTab === 'relatorios' || $activeTab === 'home' || $activeTab === 'agenda'): ?>
            <section class="grid-2 forecast-agenda-split">
                <?php if ($activeTab === 'relatorios'): ?>
                <div class="panel" id="reportsPanel">
                    <div class="section-title">
                        <div>
                            <div class="eyebrow">Previsão</div>
                            <h2>Cenários e regiões</h2>
                        </div>
                        <button class="btn comparison-cta" type="button" data-city-comparison-open>Comparar cidades</button>
                    </div>
                    <div class="grid-3">
                        <?= premium_render_stat('Conservador', premium_fmt_int((int) ($reportForecast['totals']['projected_conservative'] ?? 0)), 'Peso mais cauteloso'); ?>
                        <?= premium_render_stat('Base', premium_fmt_int((int) ($reportForecast['totals']['projected_base'] ?? 0)), 'Cálculo principal'); ?>
                        <?= premium_render_stat('Otimista', premium_fmt_int((int) ($reportForecast['totals']['projected_optimistic'] ?? 0)), 'Hipótese de maior conversão'); ?>
                    </div>
                    <div class="leaders-tabs" role="tablist" aria-label="Escolher relatório territorial" style="margin-top: 14px;">
                        <button class="leaders-tab-btn is-active" type="button" id="reportModeRegions"
                            data-report-mode-target="regions" role="tab" aria-controls="reportsRegionsBody"
                            aria-selected="true">Regiões com maior projeção</button>
                        <button class="leaders-tab-btn" type="button" id="reportModeCities"
                            data-report-mode-target="cities" role="tab" aria-controls="reportsCitiesBody"
                            aria-selected="false">Cidades com maior projeção</button>
                    </div>

                    <div id="reportsRegionsBody" class="leaders-tab-panel" data-report-mode-panel="regions"
                        role="tabpanel" aria-labelledby="reportModeRegions">
                        <div class="table-wrap reports-ranking-scroll">
                            <table style="min-width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Região</th>
                                        <th>Votos <?= premium_escape_html($campaignBaselineLabel) ?></th>
                                        <th>Projeção<br>2026</th>
                                        <th>Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ((array) ($reportForecast['regions'] ?? []) as $regionRow): ?>
                                        <tr>
                                            <td><?= premium_escape_html((string) ($regionRow['regiao'] ?? '')) ?></td>
                                            <td><?= premium_fmt_int((int) ($regionRow['baseline_votes'] ?? 0)) ?></td>
                                            <td><?= premium_fmt_int((int) ($regionRow['projected_base'] ?? 0)) ?></td>
                                            <td>
                                                <button
                                                    type="button"
                                                    class="btn ghost btn-small scope-open-btn"
                                                    data-scope-type="region"
                                                    data-scope-name="<?= premium_escape_html((string) ($regionRow['regiao'] ?? '')) ?>"
                                                >Relatório das cidades</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="reportsCitiesBody" class="leaders-tab-panel" data-report-mode-panel="cities"
                        role="tabpanel" aria-labelledby="reportModeCities" hidden>
                        <div class="table-wrap reports-ranking-scroll">
                            <table style="min-width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Município</th>
                                        <th>Votos <?= premium_escape_html($campaignBaselineLabel) ?></th>
                                        <th>Projeção<br>2026</th>
                                        <th>Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ((array) ($reportForecast['cities'] ?? []) as $cityRow): ?>
                                        <tr>
                                            <td><?= premium_escape_html((string) ($cityRow['municipio'] ?? '')) ?></td>
                                            <td><?= premium_fmt_int((int) ($cityRow['baseline_votes'] ?? 0)) ?></td>
                                            <td><?= premium_fmt_int((int) ($cityRow['projected_base'] ?? 0)) ?></td>
                                            <td>
                                                <button
                                                    type="button"
                                                    class="btn ghost btn-small scope-open-btn"
                                                    data-scope-type="city"
                                                    data-scope-name="<?= premium_escape_html((string) ($cityRow['municipio'] ?? '')) ?>"
                                                ><?= $isSenateCampaign ? 'Detalhes' : 'Relatórios de líderes' ?></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?php if ($advisor): ?>
                <?php
                    $advisorReportBaseUrl = 'premium_conselheiro.php?campaign_id=' . (int) $campaign['id'];
                    $advisorReportPrintUrl = $advisorReportBaseUrl . '&print=advisor-ranking';
                    $advisorReportFilters = [
                        'all' => 'Todos',
                        'consolidar-base' => 'Consolidar base',
                        'defender-base' => 'Defender base',
                        'base-em-risco' => 'Base em risco',
                        'buraco-eleitoral' => 'Buraco eleitoral',
                    ];
                ?>
                <div class="panel advisor-report-panel">
                    <div class="section-title">
                        <div>
                            <div class="eyebrow">Conselheiro</div>
                            <h2>Relatórios do Conselheiro</h2>
                        </div>
                    </div>
                    <p class="panel-note">Imprima a leitura estratégica do ranking municipal já filtrada por recomendação.</p>
                    <div class="advisor-report-actions">
                        <?php foreach ($advisorReportFilters as $filterKey => $filterLabel): ?>
                            <?php $href = $filterKey === 'all' ? $advisorReportPrintUrl : $advisorReportPrintUrl . '&filter=' . rawurlencode($filterKey); ?>
                            <a class="btn <?= $filterKey === 'all' ? 'comparison-cta' : 'ghost' ?> btn-small" href="<?= premium_escape_html($href) ?>" target="_blank" rel="noopener">
                                <?= premium_escape_html($filterLabel) ?>
                            </a>
                        <?php endforeach; ?>
                        <a class="btn ghost btn-small" href="<?= premium_escape_html($advisorReportBaseUrl . '#advisorRankingPanel') ?>">
                            Múltiplas recomendações
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <?php if ($activeTab === 'home' || $activeTab === 'agenda'): ?>
                <div class="panel" id="agendaPanel">
                    <div class="section-title">
                        <div>
                            <div class="eyebrow">Agenda</div>
                            <h2><?= $activeTab === 'agenda' ? 'Agenda de campanha' : 'Agendamentos pendentes' ?></h2>
                        </div>
                        <?php if ($activeTab === 'home'): ?>
                            <a class="btn ghost btn-small" href="<?= premium_escape_html(premium_tab_href('agenda', $campaign)) ?>">Abrir agenda</a>
                        <?php elseif ($agenda): ?>
                            <button class="btn ghost btn-small" type="button" data-agenda-list-open>Ver todas as tarefas</button>
                        <?php endif; ?>
                    </div>
                    <?php if ($activeTab === 'agenda'): ?>
                    <form method="post" action="premium_actions.php" class="agenda-form">
                        <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                        <input type="hidden" name="action" value="add_agenda">
                        <input type="hidden" name="redirect_tab" value="agenda">
                        <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                        <div class="form-grid">
                            <label>Título
                                <input type="text" name="title" placeholder="Visita estratégica" required>
                            </label>
                            <label>Prazo
                                <input type="date" name="due_date">
                            </label>
                            <label>Prioridade
                                <select name="priority">
                                    <option value="medium">Média</option>
                                    <option value="high">Alta</option>
                                    <option value="urgent">Urgente</option>
                                    <option value="low">Baixa</option>
                                </select>
                            </label>
                            <label>Status
                                <select name="status">
                                    <option value="open">Aberta</option>
                                    <option value="doing">xm andamento</option>
                                    <option value="done">Concluída</option>
                                    <option value="archived">Arquivada</option>
                                </select>
                            </label>
                            <label>Município
                                <input type="text" name="municipality" placeholder="Cidade foco">
                            </label>
                            <label>Liderança
                                <input type="text" name="leader_name" placeholder="Nome relacionado">
                            </label>
                        </div>
                        <label style="margin-top:12px;">Descrição
                            <textarea name="description" rows="3" placeholder="Detalhe a tarefa, prazo, entregáveis e responsáveis."></textarea>
                        </label>
                        <div class="action-row">
                            <button class="btn primary" type="submit">Adicionar tarefa</button>
                        </div>
                    </form>
                    <?php endif; ?>

                    <div style="margin-top:16px;">
                        <?php if ($agenda): ?>
                            <?php if ($activeTab === 'agenda'): ?>
                                <div class="agenda-filter-bar" role="group" aria-label="Filtrar tarefas da agenda">
                                    <button class="agenda-filter-btn is-active" type="button" data-agenda-filter="pending">Pendentes: <?= premium_fmt_int($agendaSummary['open'] + $agendaSummary['doing']) ?></button>
                                    <button class="agenda-filter-btn" type="button" data-agenda-filter="done">Concluídas: <?= premium_fmt_int($agendaSummary['done']) ?></button>
                                    <button class="agenda-filter-btn" type="button" data-agenda-filter="archived">Arquivadas: <?= premium_fmt_int($agendaSummary['archived']) ?></button>
                                </div>
                            <?php endif; ?>
                            <div id="agendaPreviewArea">
                                <?php if ($activeTab === 'agenda' && $agenda): ?>
                                    <?= premium_render_agenda_table($agenda, true) ?>
                                <?php elseif ($agendaPendingPreview): ?>
                                    <?= premium_render_agenda_table($agendaPendingPreview, true) ?>
                                <?php else: ?>
                                    <div class="empty-state">Não há tarefas pendentes no momento.</div>
                                <?php endif; ?>
                            </div>
                            <p class="panel-note" id="agendaPreviewNote" style="margin-top:12px;">
                                <?= $activeTab === 'home' ? 'Mostrando apenas os 5 agendamentos pendentes mais recentes.' : 'Mostrando a lista de pendências da campanha. Use "Ver todas as tarefas" para abrir a visão completa.' ?>
                            </p>
                        <?php else: ?>
                            <div class="empty-state">A agenda ainda está vazia. <?= $activeTab === 'agenda' ? 'Adicione as primeiras tarefas da campanha.' : 'Abra a agenda para começar o planejamento.' ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </section>
            <?php if ($activeTab === 'relatorios'): ?>
            <section class="panel" id="cityDeltaReportPanel">
                <div class="section-title">
                    <div>
                        <div class="eyebrow">Relatório</div>
                        <h2>Comparativo por cidade &mdash; <?= premium_escape_html($campaignBaselineLabel) ?> × 2026</h2>
                    </div>
                    <a class="btn comparison-cta btn-small" href="premium_relatorio_cidades.php?campaign_id=<?= (int) $campaign['id'] ?>" target="_blank" rel="noopener">Imprimir relatório</a>
                </div>
                <p class="panel-note">Votos do candidato na última eleição comparados com a projeção para 2026, com diferença e sugestão estratégica por cidade.</p>
                <?= premium_render_city_delta_report($reportForecast, $campaignBaselineYear, $campaign) ?>
            </section>
            <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script id="premium-page-data" type="application/json"><?=
    json_encode([
        'leaderBatchDefaultTransfer' => (float) ($settings['transfer_rate_default'] ?? premium_default_settings()['transfer_rate_default']),
        'campaign_id' => (int) ($campaign['id'] ?? 0),
        'campaign' => [
            'campaign_name' => (string) ($campaign['campaign_name'] ?? ''),
            'candidate_name' => (string) ($campaign['candidate_name'] ?? ''),
            'candidate_cargo' => (string) ($campaign['candidate_cargo'] ?? ''),
            'candidate_number' => premium_parse_candidate_number($campaign['candidate_number'] ?? null),
            'current_municipio' => (string) ($campaign['current_municipio'] ?? ''),
            'current_region' => (string) ($campaign['current_region'] ?? ''),
            'baseline_year' => (int) ($campaign['baseline_year'] ?? 2022),
        ],
        'onboarding' => [
            'hasCampaign' => (bool) $campaign,
            'steps' => premium_build_onboarding_steps($campaign),
        ],
        'leaders' => $leaders,
        'alliedParties' => $campaignAlliedPartyAcronyms,
        'senate' => [
            'enabled' => $isSenateCampaign,
            'sources' => $senateSources,
            'forecast' => $senateForecast,
        ],
        'agenda' => $agenda,
        'forecast' => $forecast,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
?></script>
    <script src="assets/js/premium.js" defer></script>
</body>
</html>
<?php endif; ?>
