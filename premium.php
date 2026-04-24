<?php

declare(strict_types=1);

require_once __DIR__ . '/premium_helpers.php';
require_once __DIR__ . '/premium_advisor_helpers.php';

premium_ensure_campaign_photo_column($conn);

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
    if (!premium_validate_csrf($_POST['csrf'] ?? null)) {
        premium_flash('error', 'Sua sessão expirou. Recarregue a página e tente novamente.');
        header('Location: premium');
        exit;
    }

    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (premium_login($conn, $email, $password)) {
        premium_flash('success', 'Acesso premium liberado.');
    }

    header('Location: premium');
    exit;
}

$user = premium_current_user($conn);
$trialDaysRemaining = $user ? premium_trial_days_remaining($user) : null;
$accessBadgeLabel = null;

if ($user) {
    if ($trialDaysRemaining !== null) {
        $accessBadgeLabel = $trialDaysRemaining > 0
            ? 'Teste grátis • ' . $trialDaysRemaining . ' dias restantes'
            : 'Teste grátis • expira hoje';
    } else {
        $accessBadgeLabel = 'Acesso ativo';
    }
}

if ($user && isset($_GET['campaign_id'])) {
    $requestedCampaignId = (int) $_GET['campaign_id'];
    if ($requestedCampaignId > 0 && premium_get_campaign($conn, $requestedCampaignId, (int) $user['id'])) {
        premium_set_active_campaign($requestedCampaignId);
    }
}

$flash = premium_pull_flash();
$csrf = premium_csrf_token();

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
$advisor = null;
$baselinePanelHidden = false;
$settingsPanelHidden = false;
$isAdmin = premium_is_admin_user($user);
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
    $campaign = premium_active_campaign($conn, (int) $user['id']);

    if ($campaign) {
        $campaignBaselineYear = premium_resolve_baseline_year((int) ($campaign['baseline_year'] ?? 2022));
        $campaignBaselineLabel = premium_baseline_label($campaignBaselineYear);
        premium_set_active_campaign((int) $campaign['id']);
        $settings = premium_load_campaign_settings($conn, (int) $campaign['id']);
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
        $advisor = premium_build_campaign_advisor($campaign, $baseline, $leaders, $forecast, $settings);
        $baselinePanelHidden = !empty($campaign['baseline_panel_hidden']);
        $settingsPanelHidden = !empty($campaign['settings_panel_hidden']);
    }

    if ($isAdmin) {
        $premiumCampaigns = premium_get_all_campaigns($conn);
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
}

$allowedTabs = ['home', 'liderancas', 'agenda', 'relatorios', 'opcoes'];
$activeTab = trim((string) ($_GET['tab'] ?? ($campaign ? 'home' : 'opcoes')));
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = $campaign ? 'home' : 'opcoes';
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
    $html[] = '    <div class="summary-metric__value">' . premium_fmt_int($baselineVotes) . '</div>';
    $html[] = '    <div class="summary-metric__sub">Total histórico do candidato nesta campanha</div>';
    $html[] = '  </div>';
    $html[] = '  <div class="summary-metric">';
    $html[] = '    <div class="summary-metric__label">Previsão 2026</div>';
    $html[] = '    <div class="summary-metric__value">' . premium_fmt_int($forecast2026) . '</div>';
    $html[] = '    <div class="summary-metric__sub">Cenário base calculado com os pesos atuais</div>';
    $html[] = '  </div>';
    $html[] = '</div>';
    $html[] = '<div class="empty-state" id="activeLeadersFilterEmpty" hidden>Nenhuma liderança corresponde aos filtros selecionados.</div>';
    $html[] = '<div id="activeLeadersRowsViewport">';
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
    $html[] = '<th>Votos ' . premium_escape_html($baselineLabel) . '</th>';
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
        $html[] = '<td>' . premium_fmt_int($votes2024) . '</td>';
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
        $leaderCount = (int) ($city['leader_count'] ?? 0);
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
            $leaderCount = (int) ($city['leader_count'] ?? 0);
            $leaderVotes = (int) ($city['leader_projection'] ?? $city['leader_effect'] ?? 0);
            $independentVotes = (int) ($city['independent_votes'] ?? max(0, (int) ($city['system_projection'] ?? $city['projected_base'] ?? 0) - $leaderVotes));
            $systemProjection = (int) ($city['system_projection'] ?? $city['projected_base'] ?? 0);
            $delta = $systemProjection - $baselineVotes;
            $hasLeaders = $leaderCount > 0;
            $rowMode = $hasLeaders ? 'leaders' : 'fallback';
            $statusLabel = $hasLeaders ? 'Com lideranças' : 'Sem lideranças';
            $statusClass = $hasLeaders ? 'comparison-mode-pill comparison-mode-pill--leaders' : 'comparison-mode-pill comparison-mode-pill--fallback';
            $rowClass = $hasLeaders ? 'comparison-row--leaders' : 'comparison-row--fallback';
            $actionButton = '<button type="button" class="btn ghost btn-small scope-open-btn" data-scope-type="city" data-scope-name="' . premium_escape_html($municipality) . '">Abrir</button>';

            $html[] = '          <tr class="' . $rowClass . '" data-city-comparison-row data-city-mode="' . $rowMode . '">';
            $html[] = '            <td>' . premium_escape_html($municipality) . '</td>';
            $html[] = '            <td><span class="table-pill">' . premium_escape_html($region) . '</span></td>';
            $html[] = '            <td>' . premium_fmt_int($baselineVotes) . '</td>';
            $html[] = '            <td>' . premium_fmt_int($leaderCount) . '</td>';
            $html[] = '            <td>' . premium_fmt_int($leaderVotes) . '</td>';
            $html[] = '            <td>' . premium_fmt_int($independentVotes) . '</td>';
            $html[] = '            <td>' . premium_fmt_int($systemProjection) . '</td>';
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

function premium_build_onboarding_steps(?array $campaign, string $activeTab): array
{
    $hasCampaign = $campaign !== null;
    $opcoesHref = premium_tab_href('opcoes', $campaign);
    $liderancasHref = premium_tab_href('liderancas', $campaign);
    $agendaHref = premium_tab_href('agenda', $campaign);
    $relatoriosHref = premium_tab_href('relatorios', $campaign);

    return [
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
}

function premium_render_onboarding_panel(?array $campaign, string $activeTab, string $studyExcerpt): string
{
    $steps = premium_build_onboarding_steps($campaign, $activeTab);
    $initialStep = $steps[0] ?? [
        'number' => '1',
        'title' => 'Guia rápido',
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
            <button type="button" class="btn ghost btn-small" data-onboarding-toggle aria-pressed="false">Ocultar guia</button>
        </div>
        <section class="panel onboarding-panel" data-onboarding-root data-onboarding-step-count="<?= (int) $stepCount ?>" data-onboarding-has-campaign="<?= $campaign ? '1' : '0' ?>">
            <div class="section-title onboarding-panel__head">
                <div>
                    <div class="eyebrow">Comece por aqui</div>
                    <h2>Guia rápido</h2>
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
                    <h3 data-onboarding-step-title><?= premium_escape_html((string) ($initialStep['title'] ?? 'Guia rápido')) ?></h3>
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
                <span class="muted onboarding-panel__footer-note">O guia se oculta ao final ou quando você quiser.</span>
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
                <h2>Guia rápido do teste de 7 dias</h2>
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

function premium_tab_href(string $tab, ?array $campaign = null): string
{
    $params = ['tab=' . urlencode($tab)];
    $campaignId = (int) ($campaign['id'] ?? 0);
    if ($campaignId > 0) {
        array_unshift($params, 'campaign_id=' . $campaignId);
    }

    return 'premium?' . implode('&', $params);
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
            </div>
            <div class="panel auth-card">
                <h3>Acesso premium</h3>
                <p class="muted">Use as credenciais premium para entrar no escritório da campanha.</p>
                <form method="post" action="premium" style="margin-top:16px;">
                    <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                    <input type="hidden" name="action" value="login">
                    <div class="form-grid">
                        <label>E-mail
                            <input type="email" name="email" placeholder="premium@eleicoes.local" required>
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
                    <a class="premium-sidebar__link<?= $activeTab === 'liderancas' ? ' is-active' : '' ?>" href="<?= premium_escape_html(premium_tab_href('liderancas', $campaign)) ?>">Lideranças</a>
                    <a class="premium-sidebar__link<?= $activeTab === 'agenda' ? ' is-active' : '' ?>" href="<?= premium_escape_html(premium_tab_href('agenda', $campaign)) ?>">Agenda de campanha</a>
                    <a class="premium-sidebar__link<?= $activeTab === 'relatorios' ? ' is-active' : '' ?>" href="<?= premium_escape_html(premium_tab_href('relatorios', $campaign)) ?>">Relatórios</a>
                    <a class="premium-sidebar__link<?= $activeTab === 'opcoes' ? ' is-active' : '' ?>" href="<?= premium_escape_html(premium_tab_href('opcoes', $campaign)) ?>">Opções avançadas</a>
                    <?php if ($trialDaysRemaining === null): ?>
                    <a class="premium-sidebar__link" href="premium_dicas_campanha.php<?= $campaign ? '?campaign_id=' . (int)$campaign['id'] : '' ?>">Estratégias de campanha</a>
                    <?php endif; ?>
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
                            <?php if ($premiumUsers): ?>
                                <?php foreach ($premiumUsers as $premiumUser): ?>
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
                            <?php if ($premiumCampaigns): ?>
                                <?php foreach ($premiumCampaigns as $adminCampaign): ?>
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
                        <label>Município-base
                            <input type="text" name="current_municipio" placeholder="Cidade principal, se houver">
                        </label>
                    </div>
                    <label style="margin-top:6px;">Região-base
                        <input type="text" name="current_region" placeholder="Região principal, se houver">
                    </label>
                    <div class="baseline-notes-photo">
                        <label>Notas
                            <textarea name="notes" rows="3" placeholder="Contexto da campanha, público, restrições e metas."></textarea>
                        </label>
                        <label>Foto do candidato
                            <input type="file" name="candidate_photo" accept="image/jpeg,image/png,image/webp">
                            <span class="field-help">JPG, PNG ou WEBP até 3 MB.</span>
                        </label>
                    </div>
                    <div class="action-row">
                        <button class="btn primary" type="submit">Criar campanha</button>
                    </div>
                </form>
            </section>
        <?php elseif ($campaign): ?>
            <?php if ($activeTab === 'home'): ?>
            <section class="stats-grid campaign-stats-grid">
                <?= premium_render_stat('Dados da campanha ' . $campaignBaselineLabel, premium_fmt_int((int) ($baseline['total_votes'] ?? 0)), 'Votação histórica do candidato'); ?>
                <?= premium_render_stat('Projeção base', premium_fmt_int((int) ($forecast['totals']['projected_base'] ?? 0)), 'Cenário com os pesos atuais'); ?>
                <?= premium_render_stat('Delta vs ' . $campaignBaselineLabel, premium_fmt_int((int) ($forecast['totals']['delta_base'] ?? 0)), 'Diferença absoluta sobre a base'); ?>
                <?= premium_render_stat('Lideranças ativas', premium_fmt_int(count($leaders)), 'Lideranças adicionadas ao escritório'); ?>
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
                                <label>Município-base
                                    <input type="text" name="current_municipio"
                                        value="<?= premium_escape_html((string) ($campaign['current_municipio'] ?? '')) ?>"
                                        placeholder="Cidade principal, se houver">
                                </label>
                            </div>
                            <label style="margin-top:6px;">Região-base
                                <input type="text" name="current_region"
                                    value="<?= premium_escape_html((string) ($campaign['current_region'] ?? '')) ?>"
                                    placeholder="Região principal, se houver">
                            </label>
                            <div class="baseline-notes-photo">
                                <label>Notas
                                    <textarea name="notes" rows="3"><?= premium_escape_html((string) ($campaign['notes'] ?? '')) ?></textarea>
                                </label>
                                <label>Foto do candidato
                                    <input type="file" name="candidate_photo" accept="image/jpeg,image/png,image/webp">
                                    <span class="field-help">JPG, PNG ou WEBP até 3 MB.</span>
                                </label>
                            </div>
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
                            <div class="form-grid compact">
                                <label>Município
                                    <select name="municipality" id="leaderMunicipality" required onchange="syncLeaderRegionFromMunicipality(this)">
                                        <option value="">Selecione</option>
                                        <?= premium_render_municipality_options() ?>
                                    </select>
                                </label>
                                <label>Nome
                                    <input type="text" name="leader_name" id="leaderName" required>
                                </label>
                                <label>Votos esperados
                                    <input type="number" name="leader_votes_2024" id="leaderVotes" value="0" min="0" step="1">
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
                    <?php else: ?>
                        <div class="empty-state">Ainda não há lideranças cadastradas. Use a busca para adicionar prefeitos e vereadores à campanha.</div>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>

            <?= premium_render_leader_modal($campaign, $csrf) ?>
            <?= premium_render_scope_modal($campaignBaselineYear) ?>
            <?= premium_render_city_comparison_modal($forecast, $campaignBaselineYear) ?>
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
                        <?= premium_render_stat('Conservador', premium_fmt_int((int) ($forecast['totals']['projected_conservative'] ?? 0)), 'Peso mais cauteloso'); ?>
                        <?= premium_render_stat('Base', premium_fmt_int((int) ($forecast['totals']['projected_base'] ?? 0)), 'Cálculo principal'); ?>
                        <?= premium_render_stat('Otimista', premium_fmt_int((int) ($forecast['totals']['projected_optimistic'] ?? 0)), 'Hipótese de maior conversão'); ?>
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
                        <div class="table-wrap">
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
                                    <?php foreach (array_slice((array) ($forecast['regions'] ?? []), 0, 6) as $regionRow): ?>
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
                        <div class="table-wrap">
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
                                    <?php foreach (array_slice((array) ($forecast['cities'] ?? []), 0, 6) as $cityRow): ?>
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
                                                >Relatórios de líderes</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
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
            'steps' => premium_build_onboarding_steps($campaign, $activeTab),
        ],
        'leaders' => $leaders,
        'agenda' => $agenda,
        'forecast' => $forecast,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
?></script>
    <script src="assets/js/premium.js" defer></script>
</body>
</html>
