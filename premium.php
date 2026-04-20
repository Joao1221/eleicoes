<?php

declare(strict_types=1);

require_once __DIR__ . '/premium_helpers.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'login') {
    if (!premium_validate_csrf($_POST['csrf'] ?? null)) {
        premium_flash('error', 'Sua sessão expirou. Recarregue a página e tente novamente.');
        header('Location: premium.php');
        exit;
    }

    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (premium_login($conn, $email, $password)) {
        premium_flash('success', 'Acesso premium liberado.');
    } else {
        premium_flash('error', 'Credenciais inválidas ou conta inativa.');
    }

    header('Location: premium.php');
    exit;
}

$user = premium_current_user($conn);

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
$baselinePanelHidden = false;
$settingsPanelHidden = false;
$isAdmin = premium_is_admin_user($user);
$premiumCampaigns = [];

if ($user) {
    $campaigns = premium_get_campaigns($conn, (int) $user['id']);
    $campaign = premium_active_campaign($conn, (int) $user['id']);

    if ($campaign) {
        premium_set_active_campaign((int) $campaign['id']);
        $settings = premium_load_campaign_settings($conn, (int) $campaign['id']);
        $baseline = premium_candidate_baseline($conn, (string) ($campaign['candidate_name'] ?? ''), (string) ($campaign['candidate_cargo'] ?? ''));
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

function premium_render_leaders_table(array $leaders, int $baselineVotes = 0, int $forecast2026 = 0, array $settings = []): string
{
    $settings = $settings ?: premium_default_settings();
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
    $html = [];
    $html[] = '<div class="leaders-table-shell">';
    $html[] = '<div class="leaders-summary">';
    $html[] = '  <div class="summary-metric">';
    $html[] = '    <div class="summary-metric__label">Baseline 2022</div>';
    $html[] = '    <div class="summary-metric__value">' . premium_fmt_int($baselineVotes) . '</div>';
    $html[] = '    <div class="summary-metric__sub">Total histórico do candidato nesta campanha</div>';
    $html[] = '  </div>';
    $html[] = '  <div class="summary-metric">';
    $html[] = '    <div class="summary-metric__label">Previsão 2026</div>';
    $html[] = '    <div class="summary-metric__value">' . premium_fmt_int($forecast2026) . '</div>';
    $html[] = '    <div class="summary-metric__sub">Cenário base calculado com os pesos atuais</div>';
    $html[] = '  </div>';
    $html[] = '</div>';
    $html[] = '<table class="leaders-table">';
    $html[] = '<thead><tr>';
    $html[] = '<th>Região</th>';
    $html[] = '<th>Município</th>';
    $html[] = '<th>Liderança</th>';
    $html[] = '<th>Votos</th>';
    $html[] = '<th>Transferência %</th>';
    $html[] = '<th>Base transferível</th>';
    $html[] = '<th>Projeção</th>';
    $html[] = '<th>Ação</th>';
    $html[] = '</tr></thead><tbody>';

    foreach ($leaders as $leader) {
    $leaderId = (int) ($leader['id'] ?? 0);
    $regionName = (string) ($leader['region_name'] ?? 'Sem região');
    $municipality = (string) ($leader['municipality'] ?? '');
    $leaderName = (string) ($leader['leader_display_name'] ?? $leader['leader_name'] ?? 'Liderança');
        $leaderCargo = (string) ($leader['leader_cargo'] ?? '');
        $leaderParty = (string) ($leader['leader_party'] ?? '');
        $votes2024 = (int) ($leader['leader_votes_2024'] ?? 0);
        $transferRate = (float) ($leader['transfer_rate'] ?? 0);
        $projection = premium_apply_transfer_multiplier($leader, $settings);
        $baseEffect = (int) ($projection['base_effect'] ?? 0);
        $projectedVotes = (int) ($projection['projected_votes'] ?? 0);

        $html[] = '<tr>';
        $html[] = '<td><span class="table-pill">' . premium_escape_html($regionName) . '</span></td>';
        $html[] = '<td>' . premium_escape_html($municipality) . '</td>';
        $html[] = '<td>';
        $html[] = '<button type="button" class="leader-open-btn" data-leader-id="' . $leaderId . '">' . premium_escape_html($leaderName) . '</button>';
        if ($leaderCargo !== '' || $leaderParty !== '') {
            $meta = trim($leaderCargo . ($leaderParty !== '' ? ' · ' . $leaderParty : ''));
            $html[] = '<div class="table-sub">' . premium_escape_html($meta) . '</div>';
        }
        $html[] = '</td>';
        $html[] = '<td>' . premium_fmt_int($votes2024) . '</td>';
        $html[] = '<td>' . premium_fmt_percent($transferRate) . '</td>';
        $html[] = '<td>' . premium_fmt_int($baseEffect) . '</td>';
        $html[] = '<td>' . premium_fmt_int($projectedVotes) . '</td>';
        $html[] = '<td><button type="button" class="btn ghost btn-small leader-open-btn" data-leader-id="' . $leaderId . '">Abrir</button></td>';
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
    $html[] = '          <input type="number" name="transfer_rate" id="modalLeaderTransfer" value="' . premium_escape_html((string) (premium_default_settings()['transfer_rate_default'] ?? 40)) . '" min="0" max="100" step="0.01">';
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
    $html[] = '      <input type="hidden" name="campaign_id" value="' . $campaignId . '">';
    $html[] = '      <input type="hidden" name="leader_id" id="modalLeaderDeleteId" value="">';
    $html[] = '      <button class="btn danger" type="submit">Excluir liderança</button>';
    $html[] = '    </form>';
    $html[] = '  </div>';
    $html[] = '</div>';

    return implode('', $html);
}

function premium_render_scope_modal(): string
{
    $html = [];
    $html[] = '<div class="leader-modal" id="scopeModal" hidden aria-hidden="true">';
    $html[] = '  <div class="leader-modal__backdrop" data-modal-close></div>';
    $html[] = '  <div class="leader-modal__panel" role="dialog" aria-modal="true" aria-labelledby="scopeModalTitle">';
    $html[] = '    <div class="leader-modal__header">';
    $html[] = '      <div>';
    $html[] = '        <div class="eyebrow">Cidade ou região</div>';
    $html[] = '        <h3 id="scopeModalTitle">Selecione um recorte territorial</h3>';
    $html[] = '        <p class="muted" id="scopeModalSubtitle">Clique em uma cidade ou região para ver as lideranças, as projeções individuais e o comparativo com 2022.</p>';
    $html[] = '      </div>';
    $html[] = '      <button type="button" class="btn ghost" data-modal-close>Fechar</button>';
    $html[] = '    </div>';
    $html[] = '    <div class="leader-modal__summary" id="scopeModalSummary"></div>';
    $html[] = '    <p class="panel-note" id="scopeModalNote">O detalhe territorial mostrará o total de votos de 2022 apenas como comparativo e destacará a projeção atual construída pelas lideranças cadastradas.</p>';
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

function premium_render_city_comparison_modal(array $forecast): string
{
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
    $html[] = '        <h3 id="cityComparisonTitle">2022 x projeção 2026 em todas as cidades</h3>';
    $html[] = '        <p class="muted" id="cityComparisonSubtitle">Compare o histórico de 2022 com a projeção do sistema, separando claramente os votos das lideranças e os votos independentes.</p>';
    $html[] = '      </div>';
    $html[] = '      <div class="modal-header-actions">';
    $html[] = '        <button type="button" class="btn comparison-report-btn" data-city-comparison-print>Imprimir relatório</button>';
    $html[] = '        <button type="button" class="btn ghost" data-modal-close>Fechar</button>';
    $html[] = '      </div>';
    $html[] = '    </div>';
    $html[] = '    <div class="leader-modal__summary comparison-summary-grid">';
    $html[] = '      <div class="summary-metric summary-metric--primary">';
    $html[] = '        <div class="summary-metric__label">Comparativo 2022</div>';
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
    $html[] = '    <p class="panel-note" id="cityComparisonNote">Os votos de liderança mostram a força das lideranças cadastradas; os votos independentes mostram a parcela não atribuída a lideranças. Nas cidades sem liderança, a projeção do sistema usa o fallback de 2022.</p>';
    $html[] = '    <div class="table-wrap">';
    $html[] = '      <table class="comparison-modal-table">';
    $html[] = '        <thead>';
    $html[] = '          <tr>';
    $html[] = '            <th>Município</th>';
    $html[] = '            <th>Região</th>';
    $html[] = '            <th>2022</th>';
    $html[] = '            <th>Lideranças</th>';
    $html[] = '            <th>Votos liderança</th>';
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

function premium_render_leaf_card(array $leader): string
{
    $leaderId = (int) ($leader['id'] ?? 0);
    $yesChecked = !empty($leader['aligned_with_executive']) ? ' checked' : '';
    $leaderName = (string) ($leader['leader_display_name'] ?? $leader['leader_name'] ?? 'Liderança');
    $transferRate = number_format((float) ($leader['transfer_rate'] ?? 40), 2, '.', '');
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
    $html[] = '        <input type="hidden" name="campaign_id" value="' . $campaignId . '">';
    $html[] = '        <input type="hidden" name="agenda_id" id="modalAgendaArchiveId" value="">';
    $html[] = '        <button class="btn ghost" type="submit">Arquivar tarefa</button>';
    $html[] = '      </form>';
    $html[] = '      <form method="post" action="premium_actions.php" class="agenda-modal__inline" onsubmit="return confirm(\'Remover esta tarefa da agenda?\');">';
    $html[] = '        <input type="hidden" name="csrf" value="' . premium_escape_html($csrf) . '">';
    $html[] = '        <input type="hidden" name="action" value="delete_agenda">';
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

?>
<!DOCTYPx html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark light">
    <script>
        (function () {
            try {
                const storedTheme = localStorage.getItem('premium-theme');
                const theme = storedTheme === 'light' ? 'light' : 'dark';
                document.documentElement.dataset.theme = theme;
                document.documentElement.style.colorScheme = theme;
            } catch (error) {
                document.documentElement.dataset.theme = 'dark';
                document.documentElement.style.colorScheme = 'dark';
            }
        }());
    </script>
    <title>Escritório Premium | xleições Sergipe</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #04131a;
            --bg-soft: #0a1d26;
            --panel: rgba(10, 28, 37, 0.9);
            --panel-strong: rgba(12, 34, 46, 0.98);
            --line: rgba(116, 207, 169, 0.16);
            --text: #ecfff8;
            --muted: #9bc1b7;
            --accent: #6ef3c5;
            --accent-2: #ffd166;
            --accent-3: #64d2ff;
            --danger: #ff6b7a;
            --shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
        }

        * { box-sizing: border-box; }
        html {
            color-scheme: dark;
        }

        html[data-theme="light"] {
            color-scheme: light;
            --bg: #f4f7fb;
            --bg-soft: #e9eff6;
            --panel: rgba(255, 255, 255, 0.95);
            --panel-strong: rgba(255, 255, 255, 0.99);
            --line: rgba(15, 23, 42, 0.12);
            --text: #0f172a;
            --muted: #475569;
            --shadow: 0 24px 70px rgba(15, 23, 42, 0.10);
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(100, 210, 255, 0.14), transparent 28%),
                radial-gradient(circle at top right, rgba(110, 243, 197, 0.12), transparent 24%),
                linear-gradient(180deg, #04131a 0%, #071a22 42%, #031017 100%);
            color: var(--text);
            min-height: 100vh;
        }

        html[data-theme="light"] body {
            background:
                radial-gradient(circle at top left, rgba(14, 165, 233, 0.10), transparent 26%),
                radial-gradient(circle at top right, rgba(16, 185, 129, 0.08), transparent 24%),
                linear-gradient(180deg, #f8fbff 0%, #eef3f8 44%, #e7edf4 100%);
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
            background-size: 32px 32px;
            mask-image: radial-gradient(circle at center, black 42%, transparent 95%);
        }

        html[data-theme="light"] body::before {
            background-image:
                linear-gradient(rgba(15, 23, 42, 0.022) 1px, transparent 1px),
                linear-gradient(90deg, rgba(15, 23, 42, 0.022) 1px, transparent 1px);
        }

        a { color: inherit; text-decoration: none; }

        .shell {
            width: min(1440px, calc(100vw - 32px));
            margin: 0 auto;
            padding: 24px 0 64px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 20px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--line);
            color: var(--accent);
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        h1, h2, h3, h4 {
            font-family: 'Space Grotesk', sans-serif;
            margin: 0;
        }

        .topbar h1 {
            font-size: clamp(1.8rem, 3vw, 3rem);
            line-height: 1.02;
            margin-top: 10px;
        }

        .topbar p, .muted {
            color: var(--muted);
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .topbar-right {
            display: grid;
            justify-items: end;
            gap: 10px;
        }

        .theme-switch {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: rgba(255,255,255,0.05);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.03);
        }

        html[data-theme="light"] .theme-switch {
            background: rgba(15, 23, 42, 0.04);
        }

        .theme-switch__btn {
            appearance: none;
            border: 1px solid transparent;
            width: 32px;
            height: 32px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            font-size: 0.94rem;
            line-height: 1;
            transition: transform .18s ease, background .18s ease, color .18s ease, border-color .18s ease, box-shadow .18s ease;
        }

        .theme-switch__btn:hover {
            transform: translateY(-1px);
            color: var(--text);
        }

        .theme-switch__btn.is-active {
            background: var(--accent);
            color: #04221c;
            border-color: var(--accent);
            box-shadow: 0 10px 24px rgba(110, 243, 197, 0.18);
        }

        .theme-switch__btn:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }

        html[data-theme="light"] .hero {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(241, 247, 252, 0.96));
        }

        html[data-theme="light"] .btn.ghost {
            background: rgba(15, 23, 42, 0.04);
            color: var(--text);
            border-color: rgba(15, 23, 42, 0.12);
        }

        html[data-theme="light"] .flash {
            background: rgba(255, 255, 255, 0.85);
        }

        html[data-theme="light"] .leader-card,
        html[data-theme="light"] .agenda-card {
            background: rgba(15, 23, 42, 0.03);
        }

        html[data-theme="light"] .agenda-filter-btn {
            background: rgba(15, 23, 42, 0.04);
            color: var(--text);
        }

        html[data-theme="light"] .agenda-filter-btn.is-active {
            background: rgba(16, 185, 129, 0.12);
            color: #0f766e;
            border-color: rgba(16, 185, 129, 0.26);
        }

        html[data-theme="light"] .leader-search-row.is-selected {
            background: rgba(16, 185, 129, 0.06);
        }

        html[data-theme="light"] th,
        html[data-theme="light"] td {
            border-bottom-color: rgba(15, 23, 42, 0.08);
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 22px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
        }

        .panel + .panel { margin-top: 18px; }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(280px, 380px);
            gap: 20px;
            align-items: stretch;
            margin-bottom: 18px;
            background: linear-gradient(135deg, rgba(11, 29, 38, 0.95), rgba(7, 36, 49, 0.92));
        }

        .hero .copy p {
            max-width: 72ch;
            line-height: 1.6;
        }

        .pill-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,0.04);
            color: var(--text);
            font-size: .8rem;
            font-weight: 700;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid transparent;
            border-radius: 14px;
            padding: 12px 16px;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            transition: transform .18s ease, background .18s ease, border-color .18s ease;
        }

        .btn:hover { transform: translateY(-1px); }
        .btn.primary { background: var(--accent); color: #04221c; }
        .btn.ghost { background: rgba(255,255,255,0.05); color: var(--text); border-color: var(--line); }
        .btn.danger { background: rgba(255,107,122,0.14); color: #ffd5db; border-color: rgba(255,107,122,0.32); }

        .btn.comparison-cta {
            background: linear-gradient(135deg, #6ef3c5 0%, #64d2ff 100%);
            color: #031a15;
            border-color: rgba(110, 243, 197, 0.48);
            padding: 14px 22px;
            min-height: 48px;
            font-size: 0.98rem;
            font-weight: 900;
            letter-spacing: .04em;
            text-transform: uppercase;
            border-radius: 16px;
            box-shadow: 0 16px 34px rgba(110, 243, 197, 0.24), 0 0 0 1px rgba(100, 210, 255, 0.14);
        }

        .btn.comparison-cta:hover {
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 20px 38px rgba(110, 243, 197, 0.30), 0 0 0 1px rgba(100, 210, 255, 0.22);
        }

        .btn.comparison-report-btn {
            background: rgba(255,255,255,0.06);
            color: var(--text);
            border-color: rgba(100, 210, 255, 0.26);
            padding: 12px 18px;
            min-height: 44px;
            font-size: 0.88rem;
            font-weight: 900;
            letter-spacing: .04em;
            text-transform: uppercase;
            border-radius: 14px;
        }

        .btn.comparison-report-btn:hover {
            transform: translateY(-1px);
            border-color: rgba(110, 243, 197, 0.34);
            background: rgba(110, 243, 197, 0.10);
        }

        html[data-theme="light"] .btn.comparison-report-btn {
            background: rgba(15, 23, 42, 0.04);
            color: var(--text);
            border-color: rgba(15, 23, 42, 0.12);
        }

        html[data-theme="light"] .btn.comparison-report-btn:hover {
            background: rgba(16, 185, 129, 0.08);
            border-color: rgba(16, 185, 129, 0.22);
        }

        .modal-header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        html[data-theme="light"] .btn.comparison-cta {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.96), rgba(14, 165, 233, 0.96));
            color: #f8fffb;
            border-color: rgba(14, 165, 233, 0.24);
            box-shadow: 0 16px 34px rgba(14, 165, 233, 0.16), 0 0 0 1px rgba(16, 185, 129, 0.16);
        }

        html[data-theme="light"] .btn.comparison-cta:hover {
            box-shadow: 0 20px 38px rgba(14, 165, 233, 0.22), 0 0 0 1px rgba(16, 185, 129, 0.20);
        }

        .flash {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid transparent;
            position: relative;
            overflow: hidden;
            font-weight: 700;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(30, 41, 59, 0.92));
            color: #eef2ff;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.18);
        }

        .flash::before {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(120deg, rgba(255, 255, 255, 0.18), transparent 45%);
        }

        .flash.success {
            background: linear-gradient(135deg, rgba(4, 120, 87, 0.98), rgba(16, 185, 129, 0.92));
            border-color: rgba(110, 243, 197, 0.84);
            color: #ecfdf5;
            box-shadow: 0 18px 36px rgba(16, 185, 129, 0.22);
        }

        .flash.error {
            background: linear-gradient(135deg, rgba(153, 27, 27, 0.98), rgba(220, 38, 38, 0.92));
            border-color: rgba(255, 107, 122, 0.88);
            color: #fff1f2;
            box-shadow: 0 18px 36px rgba(220, 38, 38, 0.22);
        }

        .flash.warning {
            background: linear-gradient(135deg, rgba(146, 64, 14, 0.98), rgba(245, 158, 11, 0.92));
            border-color: rgba(255, 209, 102, 0.88);
            color: #fffbeb;
            box-shadow: 0 18px 36px rgba(245, 158, 11, 0.22);
        }

        html[data-theme="light"] .flash {
            background: linear-gradient(135deg, rgba(239, 246, 255, 0.98), rgba(255, 255, 255, 0.96));
            color: #0f172a;
            border-color: rgba(59, 130, 246, 0.22);
            box-shadow: 0 18px 32px rgba(15, 23, 42, 0.08);
        }

        html[data-theme="light"] .flash.success {
            background: linear-gradient(135deg, rgba(220, 252, 231, 0.98), rgba(167, 243, 208, 0.92));
            border-color: rgba(16, 185, 129, 0.38);
            color: #065f46;
            box-shadow: 0 18px 32px rgba(16, 185, 129, 0.14);
        }

        html[data-theme="light"] .flash.error {
            background: linear-gradient(135deg, rgba(255, 228, 230, 0.98), rgba(254, 202, 202, 0.92));
            border-color: rgba(239, 68, 68, 0.38);
            color: #9f1239;
            box-shadow: 0 18px 32px rgba(239, 68, 68, 0.14);
        }

        html[data-theme="light"] .flash.warning {
            background: linear-gradient(135deg, rgba(255, 247, 205, 0.98), rgba(254, 240, 138, 0.92));
            border-color: rgba(245, 158, 11, 0.38);
            color: #92400e;
            box-shadow: 0 18px 32px rgba(245, 158, 11, 0.14);
        }

        .auth-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 18px;
        }

        .auth-card form, .campaign-form, .leader-form, .agenda-form, .settings-form {
            display: block;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .form-grid.compact {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        label {
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: .85rem;
            color: var(--muted);
            font-weight: 700;
        }

        input, select, textarea {
            width: 100%;
            border-radius: 14px;
            border: 1px solid var(--line);
            background: rgba(6, 18, 24, 0.96);
            color: var(--text);
            padding: 12px 14px;
            min-height: 44px;
            font: inherit;
        }

        html[data-theme="light"] input,
        html[data-theme="light"] select,
        html[data-theme="light"] textarea {
            background: rgba(255, 255, 255, 0.98);
            color: var(--text);
            border-color: rgba(15, 23, 42, 0.14);
        }

        select {
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        select option {
            background: #08151d;
            color: #ecfff8;
        }

        html[data-theme="light"] select option {
            background: #ffffff;
            color: #0f172a;
        }

        textarea { resize: vertical; min-height: 88px; }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(110,243,197,0.12);
        }

        .checkbox {
            flex-direction: row;
            align-items: center;
            gap: 10px;
        }

        .checkbox input {
            width: auto;
            margin: 0;
        }

        .field-help {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 0.72rem;
            font-weight: 600;
            line-height: 1.45;
        }

        .panel-note {
            margin-top: 4px;
            color: var(--muted);
            font-size: 0.88rem;
            line-height: 1.5;
        }

        #settingsBody .panel-note {
            font-size: 0.78rem;
            line-height: 1.38;
            font-weight: 400;
        }

        #settingsBody .field-help {
            font-size: 0.68rem;
            line-height: 1.32;
            font-weight: 400;
            opacity: 0.92;
        }

        .agenda-filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        .agenda-filter-btn {
            appearance: none;
            border: 1px solid var(--line);
            background: rgba(255,255,255,0.04);
            color: var(--text);
            border-radius: 999px;
            padding: 7px 10px;
            font: inherit;
            font-size: 0.72rem;
            font-weight: 800;
            cursor: pointer;
            transition: border-color .18s ease, background .18s ease, transform .18s ease;
        }

        .agenda-filter-btn:hover {
            transform: translateY(-1px);
            border-color: var(--accent);
        }

        .agenda-filter-btn.is-active {
            background: rgba(110, 243, 197, 0.14);
            color: var(--accent);
            border-color: rgba(110, 243, 197, 0.3);
        }

        .campaign-shortcuts {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 18px;
            padding: 16px 18px;
            border: 1px solid rgba(110, 243, 197, 0.22);
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(12, 34, 46, 0.98), rgba(8, 21, 29, 0.96));
        }

        html[data-theme="light"] .campaign-shortcuts {
            background: linear-gradient(135deg, rgba(255,255,255,0.98), rgba(242,248,252,0.96));
        }

        .campaign-shortcuts__copy {
            display: grid;
            gap: 4px;
        }

        .campaign-shortcuts__title {
            font-size: 1rem;
            font-weight: 800;
        }

        .campaign-shortcuts__sub {
            color: var(--muted);
            font-size: 0.86rem;
            line-height: 1.45;
        }

        .campaign-shortcuts__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .dashboard-single {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
            margin-bottom: 18px;
        }

        .dashboard-panels-split {
            grid-template-columns: minmax(0, 13fr) minmax(0, 7fr);
        }

        .panel-shortcut {
            display: grid;
            gap: 8px;
            min-height: 100%;
        }

        .panel-shortcut__title {
            font-size: 1.2rem;
            font-weight: 800;
        }

        .panel-shortcut__sub {
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.55;
        }

        .panel-shortcut__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 6px;
        }

        .accordion-panel {
            padding: 0;
            overflow: hidden;
        }

        .accordion-summary {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
            cursor: pointer;
            list-style: none;
            user-select: none;
        }

        .accordion-summary::-webkit-details-marker {
            display: none;
        }

        .accordion-summary__copy {
            display: grid;
            gap: 4px;
        }

        .accordion-summary__title {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .accordion-summary__sub {
            color: var(--muted);
            font-size: 0.88rem;
            line-height: 1.45;
        }

        .accordion-summary__badge {
            display: inline-flex;
            align-items: center;
            align-self: center;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,0.05);
            color: var(--text);
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        html[data-theme="light"] .accordion-summary__badge {
            background: rgba(15, 23, 42, 0.04);
        }

        .accordion-body {
            padding: 0 20px 20px;
        }

        .accordion-panel[open] .accordion-summary {
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        html[data-theme="light"] .accordion-panel[open] .accordion-summary {
            border-bottom-color: rgba(15, 23, 42, 0.08);
        }

        .accordion-summary:hover .accordion-summary__badge {
            border-color: var(--accent);
        }

        .accordion-panel[open] .accordion-summary__badge {
            background: rgba(110, 243, 197, 0.14);
            color: var(--accent);
            border-color: rgba(110, 243, 197, 0.3);
        }

        .leaders-table-shell {
            max-height: 620px;
            overflow: auto;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: rgba(255,255,255,0.03);
        }

        html[data-theme="light"] .leaders-table-shell {
            background: rgba(255,255,255,0.82);
        }

        .leaders-table {
            width: 100%;
            min-width: 980px;
            border-collapse: collapse;
        }

        .leaders-summary {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin: 14px 14px 0;
        }

        .scope-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin: 14px 14px 10px;
        }

        .scope-summary-grid .summary-metric {
            min-height: 126px;
        }

        .scope-summary-grid .summary-metric--primary {
            background: linear-gradient(135deg, rgba(110, 243, 197, 0.16), rgba(100, 210, 255, 0.08));
            border-color: rgba(110, 243, 197, 0.30);
        }

        html[data-theme="light"] .scope-summary-grid .summary-metric--primary {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.10), rgba(14, 165, 233, 0.06));
            border-color: rgba(15, 23, 42, 0.10);
        }

        .scope-summary-grid .summary-metric--delta .summary-metric__value {
            color: var(--accent-2);
        }

        .scope-summary-grid .summary-metric__value {
            font-size: clamp(1.5rem, 3.2vw, 2.2rem);
        }

        .scope-summary-grid .summary-metric__sub {
            font-size: 0.78rem;
        }

        .scope-summary-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 0 14px 14px;
        }

        .comparison-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin: 14px 14px 10px;
        }

        .comparison-summary-grid .summary-metric {
            min-height: 126px;
        }

        .comparison-summary-grid .summary-metric--primary {
            background: linear-gradient(135deg, rgba(110, 243, 197, 0.16), rgba(100, 210, 255, 0.08));
            border-color: rgba(110, 243, 197, 0.30);
        }

        html[data-theme="light"] .comparison-summary-grid .summary-metric--primary {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.10), rgba(14, 165, 233, 0.06));
            border-color: rgba(15, 23, 42, 0.10);
        }

        .comparison-modal-table {
            width: 100%;
            min-width: 1520px;
            border-collapse: collapse;
        }

        .comparison-mode-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 800;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .comparison-mode-pill--leaders {
            background: rgba(110, 243, 197, 0.16);
            color: var(--accent);
            border-color: rgba(110, 243, 197, 0.22);
        }

        .comparison-mode-pill--fallback {
            background: rgba(100, 210, 255, 0.12);
            color: var(--accent-3);
            border-color: rgba(100, 210, 255, 0.20);
        }

        html[data-theme="light"] .comparison-mode-pill--leaders {
            background: rgba(16, 185, 129, 0.10);
            color: #0f766e;
            border-color: rgba(16, 185, 129, 0.18);
        }

        html[data-theme="light"] .comparison-mode-pill--fallback {
            background: rgba(14, 165, 233, 0.10);
            color: #0369a1;
            border-color: rgba(14, 165, 233, 0.18);
        }

        .comparison-row--leaders {
            background: rgba(110, 243, 197, 0.04);
        }

        .comparison-row--fallback {
            background: rgba(100, 210, 255, 0.03);
        }

        html[data-theme="light"] .comparison-row--leaders {
            background: rgba(16, 185, 129, 0.04);
        }

        html[data-theme="light"] .comparison-row--fallback {
            background: rgba(14, 165, 233, 0.03);
        }

        .comparison-row--leaders td,
        .comparison-row--fallback td {
            vertical-align: middle;
        }

        .scope-rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,0.05);
            color: var(--text);
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: .04em;
        }

        .scope-rank-badge--top {
            background: linear-gradient(135deg, rgba(255, 209, 102, 0.94), rgba(110, 243, 197, 0.88));
            color: #05141b;
            border-color: transparent;
            box-shadow: 0 12px 24px rgba(110, 243, 197, 0.14);
        }

        .scope-rank-badge--silver {
            background: linear-gradient(135deg, rgba(226, 232, 240, 0.94), rgba(148, 163, 184, 0.72));
            color: #0f172a;
            border-color: transparent;
        }

        .scope-rank-badge--bronze {
            background: linear-gradient(135deg, rgba(255, 186, 110, 0.94), rgba(244, 114, 182, 0.70));
            color: #1f1306;
            border-color: transparent;
        }

        .scope-row--top {
            background: rgba(110, 243, 197, 0.06);
        }

        html[data-theme="light"] .scope-row--top {
            background: rgba(14, 165, 233, 0.06);
        }

        .scope-row--top td {
            border-bottom-color: rgba(110, 243, 197, 0.18);
        }

        .summary-metric {
            border: 1px solid var(--line);
            border-radius: 16px;
            background: rgba(255,255,255,0.04);
            padding: 14px 16px;
        }

        html[data-theme="light"] .summary-metric {
            background: rgba(15, 23, 42, 0.03);
        }

        .summary-metric__label {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .7rem;
            font-weight: 800;
        }

        .summary-metric__value {
            margin-top: 8px;
            font-size: clamp(1.35rem, 2.8vw, 1.9rem);
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
        }

        .summary-metric__sub {
            margin-top: 6px;
            color: var(--muted);
            font-size: .8rem;
            line-height: 1.45;
        }

        .leaders-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: rgba(8, 21, 29, 0.96);
            backdrop-filter: blur(8px);
        }

        html[data-theme="light"] .leaders-table thead th {
            background: rgba(245, 248, 252, 0.98);
        }

        .leaders-table th,
        .leaders-table td {
            padding: 14px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            vertical-align: top;
        }

        .leaders-table tbody tr:hover {
            background: rgba(110, 243, 197, 0.05);
        }

        .table-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,0.04);
            color: var(--text);
            font-size: 0.75rem;
            font-weight: 700;
        }

        html[data-theme="light"] .table-pill {
            background: rgba(15, 23, 42, 0.04);
        }

        .table-sub {
            margin-top: 4px;
            color: var(--muted);
            font-size: 0.8rem;
            line-height: 1.4;
        }

        .agenda-table-shell {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            max-height: 460px;
            overflow: auto;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: rgba(255,255,255,0.03);
        }

        html[data-theme="light"] .agenda-table-shell {
            background: rgba(255,255,255,0.82);
        }

        .agenda-mini-list {
            display: grid;
            gap: 10px;
            max-height: 340px;
            overflow: auto;
        }

        .agenda-mini-card {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            border-radius: 16px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,0.04);
        }

        html[data-theme="light"] .agenda-mini-card {
            background: rgba(15, 23, 42, 0.03);
        }

        .agenda-mini-card--done {
            border-color: rgba(110, 243, 197, 0.24);
        }

        .agenda-mini-card--open {
            border-color: rgba(100, 210, 255, 0.2);
        }

        .agenda-mini-card--doing {
            border-color: rgba(255, 209, 102, 0.2);
        }

        .agenda-mini-card--archived {
            opacity: 0.78;
        }

        .agenda-mini-card__main {
            min-width: 0;
            display: grid;
            gap: 6px;
        }

        .agenda-mini-card__side {
            flex-shrink: 0;
            display: grid;
            justify-items: end;
            gap: 8px;
        }

        .agenda-mini-title {
            font-size: .9rem;
            line-height: 1.3;
            max-width: 100%;
        }

        .agenda-mini-meta {
            color: var(--muted);
            font-size: 0.75rem;
            line-height: 1.45;
        }

        .agenda-mini-date {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,0.04);
            color: var(--text);
            font-size: 0.74rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .agenda-table {
            width: 100%;
            border-collapse: collapse;
        }

        .agenda-table--full {
            min-width: 900px;
        }

        .agenda-table--compact {
            table-layout: fixed;
        }

        .agenda-table--compact th:nth-child(1),
        .agenda-table--compact td:nth-child(1) {
            width: 110px;
            white-space: nowrap;
        }

        .agenda-table--compact th:nth-child(2),
        .agenda-table--compact td:nth-child(2) {
            width: auto;
        }

        .agenda-table--compact th:nth-child(3),
        .agenda-table--compact td:nth-child(3) {
            width: 130px;
        }

        .agenda-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: rgba(8, 21, 29, 0.96);
            backdrop-filter: blur(8px);
        }

        html[data-theme="light"] .agenda-table thead th {
            background: rgba(245, 248, 252, 0.98);
        }

        .agenda-table th,
        .agenda-table td {
            padding: 14px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            vertical-align: top;
        }

        .agenda-table tbody tr:hover {
            background: rgba(110, 243, 197, 0.05);
        }

        .agenda-row--archived {
            opacity: 0.72;
        }

        .agenda-status,
        .agenda-priority {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid var(--line);
            font-size: 0.75rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .agenda-status--open {
            background: rgba(100, 210, 255, 0.14);
            color: var(--accent-3);
        }

        .agenda-status--doing {
            background: rgba(255, 209, 102, 0.14);
            color: var(--accent-2);
        }

        .agenda-status--done {
            background: rgba(110, 243, 197, 0.14);
            color: var(--accent);
        }

        .agenda-status--archived {
            background: rgba(255,255,255,0.06);
            color: var(--muted);
        }

        .agenda-priority--low {
            background: rgba(255,255,255,0.04);
            color: var(--text);
        }

        .agenda-priority--medium {
            background: rgba(100, 210, 255, 0.14);
            color: var(--accent-3);
        }

        .agenda-priority--high {
            background: rgba(255, 209, 102, 0.16);
            color: var(--accent-2);
        }

        .agenda-priority--urgent {
            background: rgba(255, 107, 122, 0.16);
            color: #ffd5db;
        }

        .leader-open-btn, .agenda-open-btn {
            border: 0;
            background: transparent;
            color: var(--accent);
            padding: 0;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            text-align: left;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .leader-open-btn:hover,
        .agenda-open-btn:hover {
            text-decoration: underline;
        }

        .agenda-open-btn {
            display: inline-block;
            text-align: left;
        }

        .leader-search-table thead th:first-child,
        .leader-search-table tbody td:first-child {
            width: 54px;
            text-align: center;
        }

        .leader-search-row.is-selected {
            background: rgba(110, 243, 197, 0.07);
        }

        .leader-batch-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--accent);
        }

        .leader-batch-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 14px;
            padding: 14px 16px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: rgba(255,255,255,0.03);
        }

        html[data-theme="light"] .leader-batch-toolbar {
            background: rgba(15, 23, 42, 0.03);
        }

        .leader-batch-toolbar__copy {
            display: grid;
            gap: 4px;
        }

        .leader-batch-toolbar__title {
            font-size: 0.92rem;
            font-weight: 800;
        }

        .leader-batch-toolbar__sub {
            color: var(--muted);
            font-size: 0.76rem;
            line-height: 1.35;
        }

        .leader-batch-toolbar__actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .leader-batch-form {
            display: inline-flex;
            margin: 0;
        }

        .btn-small {
            padding: 9px 12px;
            border-radius: 12px;
            font-size: 0.84rem;
        }

        .leader-modal[hidden],
        .agenda-modal[hidden] {
            display: none !important;
        }

        .leader-modal,
        .agenda-modal {
            position: fixed;
            inset: 0;
            z-index: 1200;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .leader-modal__backdrop,
        .agenda-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(2, 8, 12, 0.72);
            backdrop-filter: blur(6px);
        }

        .leader-modal__panel,
        .agenda-modal__panel {
            position: relative;
            width: min(1180px, 100%);
            max-height: calc(100vh - 48px);
            overflow: auto;
            background: linear-gradient(180deg, rgba(11, 29, 38, 0.98), rgba(7, 22, 29, 0.98));
            border: 1px solid var(--line);
            border-radius: 28px;
            box-shadow: var(--shadow);
            padding: 22px;
        }

        .agenda-modal__panel--wide {
            width: min(1360px, 100%);
        }

        html[data-theme="light"] .leader-modal__panel,
        html[data-theme="light"] .agenda-modal__panel {
            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(242,248,252,0.98));
        }

        .leader-modal__header,
        .agenda-modal__header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 14px;
        }

        .leader-modal__header h3,
        .agenda-modal__header h3 {
            margin-top: 8px;
            font-size: clamp(1.5rem, 2.8vw, 2rem);
        }

        .leader-modal__summary,
        .agenda-modal__summary {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        #scopeModalSummary {
            display: block;
        }

        .leader-modal__delete {
            margin-top: 14px;
        }

        .agenda-modal__actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        .agenda-modal__inline {
            margin: 0;
        }

        .modal-checkbox {
            min-height: 56px;
            justify-content: center;
        }

        body.modal-open {
            overflow: hidden;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .stat-card {
            background: var(--panel-strong);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 18px;
            min-height: 120px;
        }

        .stat-label {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .72rem;
            font-weight: 800;
        }

        .stat-value {
            margin-top: 10px;
            font-size: clamp(1.5rem, 3vw, 2.2rem);
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
        }

        .stat-sub {
            margin-top: 8px;
            color: var(--muted);
            font-size: .85rem;
        }

        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 14px;
        }

        .section-title h2 {
            font-size: 1.35rem;
        }

        .section-title .comparison-cta {
            white-space: nowrap;
        }

        .section-title .comparison-cta,
        .section-title .comparison-report-btn {
            white-space: nowrap;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 18px;
        }

        .grid-2.dashboard-panels-split {
            grid-template-columns: minmax(0, 65fr) minmax(0, 35fr);
        }

        .grid-2 > .panel {
            min-width: 0;
        }

        .forecast-agenda-split {
            grid-template-columns: minmax(0, 3fr) minmax(0, 1fr);
            align-items: start;
            margin-top: 18px;
        }

        .forecast-agenda-split > .panel:last-child .agenda-form .form-grid {
            grid-template-columns: 1fr;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 18px;
        }

        .table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
        }

        .scope-modal-table {
            width: 100%;
            min-width: 1000px;
            border-collapse: collapse;
        }

        .city-comparison-modal__panel {
            width: min(1360px, 100%);
        }

        th, td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            vertical-align: top;
        }

        th {
            color: var(--muted);
            font-size: .72rem;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .leader-card, .agenda-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 16px;
        }

        .leader-card + .leader-card,
        .agenda-card + .agenda-card {
            margin-top: 12px;
        }

        .leader-head, .agenda-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }

        .leader-head h4, .agenda-head h4 {
            font-size: 1.05rem;
            margin-top: 6px;
        }

        .leader-head p, .agenda-head p {
            margin: 6px 0 0;
            color: var(--muted);
        }

        .leader-metrics strong {
            display: block;
            font-size: 1.6rem;
            font-family: 'Space Grotesk', sans-serif;
        }

        .leader-metrics span {
            color: var(--muted);
            font-size: .8rem;
        }

        .action-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .agenda-form .action-row .btn.primary {
            padding: 8px 12px;
            font-size: 0.72rem;
            border-radius: 11px;
        }

        .search-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 12px;
        }

        .empty-state {
            color: var(--muted);
            padding: 16px;
            border: 1px dashed var(--line);
            border-radius: 16px;
            background: rgba(255,255,255,0.03);
        }

        .admin-user-table {
            min-width: 980px;
        }

        .admin-campaign-table {
            min-width: 1180px;
        }

        .user-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .user-status--active {
            background: rgba(110, 243, 197, 0.14);
            color: #6ef3c5;
        }

        .user-status--inactive {
            background: rgba(255, 209, 102, 0.14);
            color: #ffd166;
        }

        html[data-theme="light"] .user-status--active {
            background: rgba(16, 185, 129, 0.12);
            color: #0f766e;
        }

        html[data-theme="light"] .user-status--inactive {
            background: rgba(245, 158, 11, 0.12);
            color: #b45309;
        }

        .user-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .user-actions form {
            margin: 0;
        }

        @media (max-width: 1120px) {
            .hero, .auth-grid, .grid-2, .grid-3, .stats-grid, .search-grid, .form-grid, .form-grid.compact {
                grid-template-columns: 1fr;
            }

            .grid-2.dashboard-panels-split {
                grid-template-columns: 1fr;
            }

            .leaders-summary,
            .scope-summary-grid {
                grid-template-columns: 1fr;
            }

            .scope-summary-grid,
            .scope-summary-meta {
                margin-left: 0;
                margin-right: 0;
            }

            .campaign-shortcuts {
                align-items: flex-start;
            }

            .section-title .comparison-cta {
                width: 100%;
            }

            .modal-header-actions {
                width: 100%;
                justify-content: flex-start;
            }
        }

        @media (max-width: 768px) {
            .shell {
                width: calc(100vw - 24px);
                padding: 14px 12px 28px;
            }

            .topbar {
                flex-direction: column;
                align-items: stretch;
                margin-bottom: 16px;
            }

            .topbar-right {
                width: 100%;
                justify-items: end;
            }

            .topbar-actions {
                width: auto;
                justify-content: flex-end;
            }

            .topbar-actions > * {
                width: auto;
                flex: 0 0 auto;
            }

            .theme-switch {
                width: auto;
                justify-content: center;
            }

            .panel {
                padding: 16px;
                border-radius: 18px;
            }

            .hero {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .auth-grid,
            .grid-2,
            .grid-3,
            .stats-grid,
            .search-grid,
            .form-grid,
            .form-grid.compact,
            .leaders-summary,
            .scope-summary-grid,
            .comparison-summary-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .dashboard-panels-split,
            .forecast-agenda-split {
                grid-template-columns: 1fr;
            }

            .section-title,
            .leader-modal__header,
            .agenda-modal__header,
            .leader-head,
            .agenda-head,
            .leader-batch-toolbar,
            .campaign-shortcuts {
                flex-direction: column;
                align-items: stretch;
            }

            .section-title {
                gap: 10px;
            }

            .section-title h2 {
                font-size: 1.2rem;
            }

            .section-title > div {
                width: 100%;
            }

            .section-title .comparison-cta,
            .section-title .comparison-report-btn,
            .leader-modal__header .btn,
            .agenda-modal__header .btn,
            .campaign-shortcuts__actions .btn,
            .leader-batch-toolbar__actions .btn,
            .modal-header-actions .btn,
            .action-row .btn {
                width: 100%;
                white-space: normal;
            }

            .leader-modal,
            .agenda-modal {
                padding: 8px;
                align-items: stretch;
                justify-content: center;
            }

            .leader-modal__panel,
            .agenda-modal__panel {
                width: 100%;
                max-height: calc(100vh - 16px);
                padding: 16px;
                border-radius: 18px;
            }

            .agenda-modal__panel--wide,
            .city-comparison-modal__panel {
                width: 100%;
            }

            .leader-modal__summary,
            .agenda-modal__summary,
            .campaign-shortcuts__actions,
            .leader-batch-toolbar__actions,
            .modal-header-actions,
            .action-row,
            .pill-row,
            .agenda-filter-bar,
            .scope-summary-meta {
                width: 100%;
            }

            .leader-modal__summary,
            .agenda-modal__summary,
            .campaign-shortcuts__actions,
            .leader-batch-toolbar__actions,
            .modal-header-actions,
            .action-row {
                align-items: stretch;
            }

            .leader-batch-toolbar {
                padding: 12px;
            }

            .leader-batch-form {
                width: 100%;
                display: block;
            }

            .leader-batch-toolbar__actions {
                justify-content: flex-start;
            }

            .leader-batch-toolbar__actions > *,
            .campaign-shortcuts__actions > * {
                width: 100%;
            }

            .btn-small {
                width: auto;
            }

            .agenda-filter-bar {
                gap: 8px;
            }

            .agenda-filter-btn {
                width: calc(50% - 4px);
            }

            .pill-row {
                gap: 8px;
            }

            .pill,
            .table-pill,
            .comparison-mode-pill,
            .user-status {
                max-width: 100%;
                white-space: normal;
            }

            .leader-card,
            .agenda-card {
                padding: 14px;
                border-radius: 16px;
            }

            .leader-head,
            .agenda-head {
                gap: 10px;
            }

            .leader-head p,
            .agenda-head p,
            .panel-note,
            .field-help {
                line-height: 1.45;
            }

            .table-wrap {
                margin-left: -2px;
                margin-right: -2px;
            }

            table,
            .leaders-table,
            .scope-modal-table,
            .comparison-modal-table,
            .admin-user-table,
            .admin-campaign-table {
                min-width: 760px;
            }

            .leaders-table-shell,
            .agenda-table-shell {
                max-height: none;
            }

            .agenda-table--full {
                min-width: 820px;
            }

            .leader-search-table thead th:first-child,
            .leader-search-table tbody td:first-child {
                width: 44px;
            }
        }

        @media (max-width: 540px) {
            .shell {
                width: calc(100vw - 20px);
                padding: 12px 10px 24px;
            }

            .panel {
                padding: 14px;
                border-radius: 16px;
            }

            .topbar h1 {
                font-size: clamp(1.55rem, 9vw, 2.25rem);
            }

            .eyebrow {
                font-size: 0.66rem;
                padding: 6px 10px;
            }

            .stat-card {
                padding: 14px;
                min-height: 104px;
            }

            .stat-value {
                font-size: clamp(1.35rem, 8vw, 2rem);
            }

            .summary-metric {
                padding: 12px 14px;
            }

            .summary-metric__label,
            .stat-label {
                font-size: 0.66rem;
            }

            .summary-metric__sub,
            .stat-sub {
                font-size: 0.78rem;
            }

            .leader-modal__header h3,
            .agenda-modal__header h3 {
                font-size: clamp(1.3rem, 7vw, 1.7rem);
            }

            .agenda-filter-btn {
                width: 100%;
            }

            .leader-batch-toolbar__sub {
                font-size: 0.72rem;
            }

            .field-help {
                font-size: 0.7rem;
            }

            .panel-note {
                font-size: 0.84rem;
            }

            .table-wrap {
                margin-left: -4px;
                margin-right: -4px;
            }
        }
    </style>
</head>
<body>
<div class="shell">
    <header class="topbar">
        <div>
            <div class="eyebrow">Apoia Candidato Premium</div>
            <h1>Escritório de campanha</h1>
            <p class="muted">Baseline de 2022, lideranças de 2024, agenda, pesos configuráveis e previsões em um só lugar.</p>
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
                    <span class="pill">Comparativo 2022 x previsão</span>
                    <span class="pill">Agenda estratégica</span>
                    <span class="pill">Lideranças 2024</span>
                    <span class="pill">Relatórios premium</span>
                </div>
            </div>
            <div class="panel auth-card">
                <h3>Acesso premium</h3>
                <p class="muted">Use as credenciais premium para entrar no escritório da campanha.</p>
                <form method="post" action="premium.php" style="margin-top:16px;">
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
        <section class="panel hero">
            <div class="copy">
                <div class="eyebrow">Escritório ativo</div>
                <h2 style="font-size:2rem; margin-top: 12px;"><?= premium_escape_html(premium_selected_campaign_label($campaign)) ?></h2>
                <p class="muted" style="margin-top: 12px;">
                    Baseline de 2022, leitura regional e comparação com 2024, com fatores ajustáveis para alinhamento,
                    visibilidade, investimento e porte do município.
                </p>
                <div class="pill-row">
                    <span class="pill">Usuário: <?= premium_escape_html((string) ($user['email'] ?? '')) ?></span>
                    <span class="pill">Campanhas: <?= premium_fmt_int(count($campaigns)) ?></span>
                    <span class="pill">Regiões: 8</span>
                </div>
            </div>
            <div class="panel" style="margin:0;">
                <h3 style="margin-bottom:12px;">Selecionar campanha</h3>
                <form method="post" action="premium_actions.php" class="campaign-form">
                    <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                    <input type="hidden" name="action" value="select_campaign">
                    <label>Campanha
                        <select name="campaign_id">
                            <?= premium_render_campaign_options($campaigns, $campaign) ?>
                        </select>
                    </label>
                    <div class="action-row">
                        <button class="btn primary" type="submit">Carregar campanha</button>
                    </div>
                </form>
                <?php if ($campaign): ?>
                    <form method="post" action="premium_actions.php" class="campaign-form" style="margin-top: 12px;" onsubmit="return confirm('Excluir esta campanha permanentemente? Isso apagará baseline, lideranças, agenda e pesos.');">
                        <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                        <input type="hidden" name="action" value="delete_campaign">
                        <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                        <div class="action-row">
                            <button class="btn ghost" type="submit">Excluir campanha atual</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($isAdmin): ?>
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
                    Exclua campanhas próprias ou de qualquer conta premium. A exclusão remove baseline, lideranças, agenda, pesos e histórico de projeção.
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
                                                <form method="post" action="premium_actions.php" onsubmit="return confirm('Excluir esta campanha permanentemente? Isso apagará baseline, lideranças, agenda e pesos.');">
                                                    <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                                                    <input type="hidden" name="action" value="delete_campaign">
                                                    <input type="hidden" name="campaign_id" value="<?= $adminCampaignId ?>">
                                                    <button class="btn ghost btn-small" type="submit">Excluir</button>
                                                </form>
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
        <?php if (!$campaign): ?>
            <section class="panel">
                <div class="section-title">
                    <div>
                        <div class="eyebrow">Primeiro passo</div>
                        <h2>Criar a primeira campanha</h2>
                    </div>
                </div>
                <form method="post" action="premium_actions.php" class="campaign-form">
                    <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                    <input type="hidden" name="action" value="create_campaign">
                    <div class="form-grid">
                        <label>Nome da campanha
                            <input type="text" name="campaign_name" placeholder="Gabinete João 2026" required>
                        </label>
                        <label>Candidato
                            <input type="text" name="candidate_name" placeholder="Nome do candidato" required>
                        </label>
                        <label>Cargo
                            <input type="text" name="candidate_cargo" placeholder="Deputado Federal, Estadual..." required>
                        </label>
                        <label>Ano-base
                            <input type="number" name="baseline_year" value="2022" min="2022" step="1">
                        </label>
                    </div>
                    <label style="margin-top:12px;">Notas
                        <textarea name="notes" rows="3" placeholder="Contexto da campanha, público, restrições e metas."></textarea>
                    </label>
                    <div class="action-row">
                        <button class="btn primary" type="submit">Criar campanha</button>
                    </div>
                </form>
            </section>
        <?php else: ?>
            <section class="stats-grid">
                <?= premium_render_stat('Baseline 2022', premium_fmt_int((int) ($baseline['total_votes'] ?? 0)), 'Votação histórica do candidato'); ?>
                <?= premium_render_stat('Projeção base', premium_fmt_int((int) ($forecast['totals']['projected_base'] ?? 0)), 'Cenário com os pesos atuais'); ?>
                <?= premium_render_stat('Delta vs 2022', premium_fmt_int((int) ($forecast['totals']['delta_base'] ?? 0)), 'Diferença absoluta sobre a base'); ?>
                <?= premium_render_stat('Lideranças ativas', premium_fmt_int(count($leaders)), 'Lideranças adicionadas ao escritório'); ?>
            </section>

            <?php if ($baselinePanelHidden || $settingsPanelHidden): ?>
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
                                <button class="btn ghost" type="submit">Reabrir baseline</button>
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

            <?php if (!$baselinePanelHidden || !$settingsPanelHidden): ?>
                <?php $dashboardPanelsClass = (!$baselinePanelHidden && !$settingsPanelHidden) ? 'grid-2 dashboard-panels-split' : 'dashboard-single'; ?>
                <section class="<?= premium_escape_html($dashboardPanelsClass) ?>">
                <div class="panel"<?= $baselinePanelHidden ? ' hidden' : '' ?>>
                    <div class="section-title">
                        <div>
                            <div class="eyebrow">Baseline</div>
                            <h2>Comparativo 2022</h2>
                        </div>
                        <button class="btn ghost btn-small" type="button" data-toggle-target="baselineBody" aria-controls="baselineBody" aria-expanded="false">Abrir</button>
                    </div>
                    <div id="baselineBody" hidden>
                    <form method="post" action="premium_actions.php" class="campaign-form">
                        <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                        <input type="hidden" name="action" value="save_baseline">
                        <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                        <div class="form-grid">
                            <label>Candidato
                                <input type="text" name="candidate_name" value="<?= premium_escape_html((string) ($campaign['candidate_name'] ?? '')) ?>" required>
                            </label>
                            <label>Cargo
                                <input type="text" name="candidate_cargo" value="<?= premium_escape_html((string) ($campaign['candidate_cargo'] ?? '')) ?>" required>
                            </label>
                            <label>Ano-base
                                <input type="number" name="baseline_year" value="<?= (int) ($campaign['baseline_year'] ?? 2022) ?>" min="2022" step="1">
                            </label>
                            <label>Município-base
                                <input type="text" name="current_municipio" value="<?= premium_escape_html((string) ($campaign['current_municipio'] ?? '')) ?>" placeholder="Cidade principal, se houver">
                            </label>
                        </div>
                        <label style="margin-top:12px;">Região-base
                            <input type="text" name="current_region" value="<?= premium_escape_html((string) ($campaign['current_region'] ?? '')) ?>" placeholder="Região principal, se houver">
                        </label>
                        <label style="margin-top:12px;">Notas
                            <textarea name="notes" rows="3"><?= premium_escape_html((string) ($campaign['notes'] ?? '')) ?></textarea>
                        </label>
                        <div class="action-row">
                            <button class="btn primary" type="submit">Salvar baseline</button>
                        </div>
                    </form>
                    <?php if (!empty($baseline['found'])): ?>
                        <div style="margin-top:16px;">
                            <div class="pill-row">
                                <span class="pill">Municípios: <?= premium_fmt_int((int) ($baseline['municipality_count'] ?? 0)) ?></span>
                                <span class="pill">Total: <?= premium_fmt_int((int) ($baseline['total_votes'] ?? 0)) ?></span>
                            </div>
                            <div class="table-wrap" style="margin-top:12px;">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Município</th>
                                            <th>Região</th>
                                            <th>Votos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach (array_slice((array) $baseline['municipalities'], 0, 8) as $row): ?>
                                        <tr>
                                            <td><?= premium_escape_html((string) ($row['municipio'] ?? '')) ?></td>
                                            <td><?= premium_escape_html((string) ($row['regiao'] ?? '')) ?></td>
                                            <td><?= premium_fmt_int((int) ($row['total_votos'] ?? 0)) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="margin-top:16px;">
                            Nenhum baseline encontrado ainda. Salve o nome do candidato e o cargo para carregar os votos de 2022.
                        </div>
                    <?php endif; ?>
                    </div>
                </div>

                <div class="panel"<?= $settingsPanelHidden ? ' hidden' : '' ?>>
                    <div class="section-title">
                        <div>
                            <div class="eyebrow">Modelo</div>
                            <h2>Peso dos cenários</h2>
                        </div>
                        <button class="btn ghost btn-small" type="button" data-toggle-target="settingsBody" aria-controls="settingsBody" aria-expanded="false">Abrir</button>
                    </div>
                    <div id="settingsBody" hidden>
                    <p class="panel-note">Cada peso ajusta uma parte da projeção. A base de 2022 fica como comparativo e só entra no fallback onde não houver liderança cadastrada.</p>
                    <form method="post" action="premium_actions.php" class="settings-form">
                        <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                        <input type="hidden" name="action" value="save_settings">
                        <div class="form-grid">
                            <label>Fallback 2022
                                <input type="number" name="baseline_retention" value="<?= premium_escape_html((string) ($settings['baseline_retention'] ?? 0.45)) ?>" step="0.01" min="0" max="1">
                                <span class="field-help">Usado apenas quando o município ou a região não tem lideranças cadastradas; nesse caso, a base histórica vira referência de projeção.</span>
                            </label>
                            <label>Transferência padrão %
                                <input type="number" name="transfer_rate_default" value="<?= premium_escape_html((string) ($settings['transfer_rate_default'] ?? 40)) ?>" step="0.01" min="0" max="100">
                                <span class="field-help">Percentual médio da votação de uma liderança que pode migrar para o candidato apoiado.</span>
                            </label>
                            <label>Bônus alinhamento
                                <input type="number" name="alignment_bonus" value="<?= premium_escape_html((string) ($settings['alignment_bonus'] ?? 0.2)) ?>" step="0.01" min="0" max="1">
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
                                <input type="number" name="margin_weight" value="<?= premium_escape_html((string) ($settings['margin_weight'] ?? 0.25)) ?>" step="0.01" min="0" max="1">
                                <span class="field-help">Maior margem de vitória significa mais folga política e maior potencial de transferência.</span>
                            </label>
                            <label>Bônus cidade pequena
                                <input type="number" name="small_city_bonus" value="<?= premium_escape_html((string) ($settings['small_city_bonus'] ?? 0.18)) ?>" step="0.01" min="0" max="1">
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
                                <input type="number" name="small_city_threshold" value="<?= premium_escape_html((string) ($settings['small_city_threshold'] ?? 15000)) ?>" step="1" min="0">
                                <span class="field-help">Limite de votos totais para classificar um município como pequeno no modelo.</span>
                            </label>
                            <label>Médio até votos
                                <input type="number" name="medium_city_threshold" value="<?= premium_escape_html((string) ($settings['medium_city_threshold'] ?? 40000)) ?>" step="1" min="0">
                                <span class="field-help">Limite superior para classificar um município como médio antes de virar grande.</span>
                            </label>
                        </div>
                        <div class="action-row">
                            <button class="btn primary" type="submit">Salvar pesos</button>
                        </div>
                    </form>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <section class="panel">
                <div class="section-title">
                    <div>
                        <div class="eyebrow">Lideranças 2024</div>
                        <h2>Buscar e adicionar lideranças</h2>
                    </div>
                    <button class="btn ghost btn-small" type="button" data-toggle-target="leaderSearchBody" aria-controls="leaderSearchBody" aria-expanded="false">Abrir</button>
                </div>
                <div id="leaderSearchBody" hidden>
                    <div class="search-grid">
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
                        <label>Turno
                            <select id="searchTurno">
                                <option value="1">1º turno</option>
                                <option value="2">2º turno</option>
                            </select>
                        </label>
                    </div>
                    <div class="action-row">
                        <button class="btn primary" type="button" id="searchLeadersBtn">Buscar lideranças</button>
                    </div>
                    <div class="table-wrap" style="margin-top: 14px;">
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
                                <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                                <input type="hidden" name="leaders_json" id="leaderBatchPayload">
                                <input type="hidden" name="batch_cargo" id="leaderBatchCargo" value="">
                                <button class="btn primary btn-small" type="submit" id="leaderBatchSubmitBtn" disabled>Adicionar lideranças</button>
                            </form>
                        </div>
                    </div>

                    <div style="margin-top:18px;" class="panel">
                        <div class="section-title" style="margin-bottom:12px;">
                            <div>
                                <div class="eyebrow">Adicionar liderança</div>
                                <h3 style="font-size:1.08rem;">Ao escritório</h3>
                            </div>
                            <button class="btn ghost btn-small" type="button" data-toggle-target="leaderAddBody" aria-controls="leaderAddBody" aria-expanded="false">Abrir</button>
                        </div>
                        <div id="leaderAddBody" hidden>
                            <form method="post" action="premium_actions.php" id="leaderForm">
                                <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                                <input type="hidden" name="action" value="add_leader">
                                <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                                <input type="hidden" name="source_sq_candidato" id="sourceSq">
                                <input type="hidden" name="source_nr_votavel" id="sourceNrVotavel">
                                <input type="hidden" name="source_turno" id="sourceTurno" value="1">
                                <div class="form-grid compact">
                                    <label>Região
                                        <?= premium_render_region_select('region_name', 'leaderRegion', '', true) ?>
                                    </label>
                                    <label>Município
                                        <select name="municipality" id="leaderMunicipality" required onchange="syncLeaderRegionFromMunicipality(this)">
                                            <option value="">Selecione</option>
                                            <?= premium_render_municipality_options() ?>
                                        </select>
                                    </label>
                                    <label>Nome da urna
                                        <input type="text" name="leader_name" id="leaderName" required>
                                    </label>
                                    <label>Cargo
                                        <input type="text" name="leader_cargo" id="leaderCargo" placeholder="Prefeito ou Vereador" required>
                                    </label>
                                    <label>Partido
                                        <input type="text" name="leader_party" id="leaderParty">
                                    </label>
                                    <label>Votos em 2024
                                        <input type="number" name="leader_votes_2024" id="leaderVotes" value="0" min="0" step="1">
                                    </label>
                                    <label>Margem %
                                        <input type="number" name="margin_percent" id="leaderMargin" value="0" min="0" step="0.01">
                                        <span class="field-help">Diferença entre o primeiro e o segundo colocado no município. Quanto maior a margem, maior a folga política da liderança.</span>
                                    </label>
                                    <label>Transferência %
                                        <input type="number" name="transfer_rate" id="leaderTransfer" value="<?= premium_escape_html((string) (($settings['transfer_rate_default'] ?? 40))) ?>" min="0" max="100" step="0.01">
                                        <span class="field-help">Percentual da votação desta liderança que pode migrar para o candidato. É o principal motor da projeção.</span>
                                    </label>
                                    <label>Visibilidade
                                        <input type="number" name="visibility_score" value="50" min="0" max="100" step="0.01">
                                        <span class="field-help">Mede presença pública, reconhecimento e força de comunicação da liderança no território.</span>
                                    </label>
                                    <label>Investimento
                                        <input type="number" name="investment_score" value="50" min="0" max="100" step="0.01">
                                        <span class="field-help">Avalia a associação da liderança com entregas, obras e ações visíveis que podem converter capital político em voto.</span>
                                    </label>
                                    <label>Tamanho
                                        <select name="size_class" id="leaderSizeClass">
                                            <option value="small">Pequeno</option>
                                            <option value="medium" selected>Médio</option>
                                            <option value="large">Grande</option>
                                        </select>
                                        <span class="field-help">Classificação do município usada para ajustar o peso da liderança. Municípios menores tendem a transferir melhor voto.</span>
                                    </label>
                                    <label class="checkbox">
                                        <input type="checkbox" name="aligned_with_executive" value="1">
                                        Alinhado ao executivo
                                    </label>
                                    <label>Notas
                                        <textarea name="notes" rows="2"></textarea>
                                    </label>
                                </div>
                                <div class="action-row">
                                    <button class="btn primary" type="submit">Adicionar liderança</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="section-title">
                    <div>
                        <div class="eyebrow">Lideranças ativas</div>
                        <h2>Lideranças selecionadas na campanha</h2>
                    </div>
                    <button class="btn ghost btn-small" type="button" data-toggle-target="leadersBody" aria-controls="leadersBody" aria-expanded="false">Abrir</button>
                </div>
                <div id="leadersBody" hidden>
                    <p class="muted" style="margin-bottom: 14px;">
                        Lista resumida para leitura rápida. A tabela está ordenada por cidade e por votos dentro de cada cidade. Clique no nome ou em <strong>Abrir</strong> para ver todos os dados, editar pesos e ajustar a estratégia sem ocupar espaço demais na tela.
                    </p>
                    <?php if ($leaders): ?>
                        <?= premium_render_leaders_table($leaders, (int) ($baseline['total_votes'] ?? 0), (int) ($forecast['totals']['projected_base'] ?? 0), (array) ($forecast['settings'] ?? $settings ?? [])) ?>
                    <?php else: ?>
                        <div class="empty-state">Ainda não há lideranças cadastradas. Use a busca acima para adicionar prefeitos e vereadores à campanha.</div>
                    <?php endif; ?>
                </div>
            </section>

            <?= premium_render_leader_modal($campaign, $csrf) ?>
            <?= premium_render_scope_modal() ?>
            <?= premium_render_city_comparison_modal($forecast) ?>
            <?= premium_render_agenda_list_modal($agenda) ?>
            <?= premium_render_agenda_detail_modal($campaign, $csrf) ?>

            <section class="grid-2 forecast-agenda-split">
                <div class="panel">
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
                    <p class="panel-note" style="margin-top:-4px; margin-bottom:12px;">
                        <strong>Votos totais</strong> é o voto bruto da liderança em 2024. <strong>Base transferível</strong> é a parcela considerada pelo modelo antes dos demais ajustes. Os votos de 2022 entram só como comparativo e como fallback onde não existir liderança cadastrada.
                    </p>
                    <?php if ($forecast['leaders']): ?>
                        <div class="table-wrap" style="margin-top: 12px;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Liderança</th>
                                        <th>Município</th>
                                        <th>Votos totais</th>
                                        <th>Base transferível</th>
                                        <th>Transferência</th>
                                        <th>Projeção</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice((array) $forecast['leaders'], 0, 8) as $leaderRow): ?>
                                        <tr>
                                            <td><?= premium_escape_html((string) ($leaderRow['leader_display_name'] ?? $leaderRow['leader_name'] ?? '')) ?></td>
                                            <td><?= premium_escape_html((string) ($leaderRow['municipality'] ?? '')) ?></td>
                                            <td><?= premium_fmt_int((int) ($leaderRow['leader_votes_2024'] ?? 0)) ?></td>
                                            <td><?= premium_fmt_int((int) ($leaderRow['base_effect'] ?? 0)) ?></td>
                                            <td><?= premium_fmt_percent((float) ($leaderRow['transfer_rate'] ?? 0)) ?></td>
                                            <td><?= premium_fmt_int((int) ($leaderRow['projected_votes'] ?? 0)) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    <div class="grid-2" style="margin-top: 16px;">
                        <div class="panel" style="margin:0;">
                            <h4 style="margin-bottom:12px;">Regiões com maior projeção</h4>
                            <p class="panel-note" style="margin-top:-2px; margin-bottom:10px;">Clique em uma região para ver todas as lideranças, as projeções individuais e o comparativo com 2022.</p>
                            <div class="table-wrap">
                                <table style="min-width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Região</th>
                                            <th>Votos 2022</th>
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
                                                    >Líderes</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="panel" style="margin:0;">
                            <h4 style="margin-bottom:12px;">Cidades com maior projeção</h4>
                            <p class="panel-note" style="margin-top:-2px; margin-bottom:10px;">Clique em uma cidade para abrir o resumo completo com os líderes cadastrados e o efeito no modelo.</p>
                            <div class="table-wrap">
                                <table style="min-width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Município</th>
                                            <th>Projeção<br>2026</th>
                                            <th>Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice((array) ($forecast['cities'] ?? []), 0, 6) as $cityRow): ?>
                                            <tr>
                                                <td><?= premium_escape_html((string) ($cityRow['municipio'] ?? '')) ?></td>
                                                <td><?= premium_fmt_int((int) ($cityRow['projected_base'] ?? 0)) ?></td>
                                                <td>
                                                    <button
                                                        type="button"
                                                        class="btn ghost btn-small scope-open-btn"
                                                        data-scope-type="city"
                                                        data-scope-name="<?= premium_escape_html((string) ($cityRow['municipio'] ?? '')) ?>"
                                                    >Líderes</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="section-title">
                        <div>
                            <div class="eyebrow">Agenda</div>
                            <h2>Escritório de campanha</h2>
                        </div>
                        <?php if ($agenda): ?>
                            <button class="btn ghost btn-small" type="button" data-agenda-list-open>Ver todas as tarefas</button>
                        <?php endif; ?>
                    </div>
                    <form method="post" action="premium_actions.php" class="agenda-form">
                        <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                        <input type="hidden" name="action" value="add_agenda">
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

                    <div style="margin-top:16px;">
                        <?php if ($agenda): ?>
                            <div class="agenda-filter-bar" role="group" aria-label="Filtrar tarefas da agenda">
                                <button class="agenda-filter-btn is-active" type="button" data-agenda-filter="pending">Pendentes: <?= premium_fmt_int($agendaSummary['open'] + $agendaSummary['doing']) ?></button>
                                <button class="agenda-filter-btn" type="button" data-agenda-filter="done">Concluídas: <?= premium_fmt_int($agendaSummary['done']) ?></button>
                                <button class="agenda-filter-btn" type="button" data-agenda-filter="archived">Arquivadas: <?= premium_fmt_int($agendaSummary['archived']) ?></button>
                            </div>
                            <div id="agendaPreviewArea">
                                <?php if ($agendaPendingPreview): ?>
                                    <?= premium_render_agenda_table($agendaPendingPreview, true) ?>
                                <?php else: ?>
                                    <div class="empty-state">Não há tarefas pendentes no momento. Use um dos botões acima para ver outra visão da agenda.</div>
                                <?php endif; ?>
                            </div>
                            <p class="panel-note" id="agendaPreviewNote" style="margin-top:12px;">
                                Mostrando apenas as 5 tarefas pendentes mais recentes.
                            </p>
                        <?php else: ?>
                            <div class="empty-state">A agenda ainda está vazia. Adicione as primeiras tarefas do escritório de campanha.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    const searchBtn = document.getElementById('searchLeadersBtn');
    const resultsBody = document.getElementById('leaderSearchResults');
    const leaderSelectAll = document.getElementById('leaderSelectAll');
    const leaderBatchForm = document.getElementById('leaderBatchForm');
    const leaderBatchPayload = document.getElementById('leaderBatchPayload');
    const leaderBatchCargo = document.getElementById('leaderBatchCargo');
    const leaderBatchSelectedCount = document.getElementById('leaderBatchSelectedCount');
    const leaderBatchSubmitBtn = document.getElementById('leaderBatchSubmitBtn');
    const leaderBatchSelectAllBtn = document.getElementById('leaderBatchSelectAllBtn');
    const leaderBatchClearBtn = document.getElementById('leaderBatchClearBtn');
    const leaderBatchDefaultTransfer = <?= json_encode((float) ($settings['transfer_rate_default'] ?? 40), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const premiumCampaign = <?= json_encode([
        'campaign_name' => (string) ($campaign['campaign_name'] ?? ''),
        'candidate_name' => (string) ($campaign['candidate_name'] ?? ''),
        'candidate_cargo' => (string) ($campaign['candidate_cargo'] ?? ''),
        'current_municipio' => (string) ($campaign['current_municipio'] ?? ''),
        'current_region' => (string) ($campaign['current_region'] ?? ''),
        'baseline_year' => (int) ($campaign['baseline_year'] ?? 2022),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const premiumLeaders = <?= json_encode($leaders, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const premiumAgenda = <?= json_encode($agenda, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const premiumForecast = <?= json_encode($forecast, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const leaderModal = document.getElementById('leaderModal');
    const leaderModalTitle = document.getElementById('leaderModalTitle');
    const leaderModalSubtitle = document.getElementById('leaderModalSubtitle');
    const leaderModalSummary = document.getElementById('leaderModalSummary');
    const scopeModal = document.getElementById('scopeModal');
    const scopeModalTitle = document.getElementById('scopeModalTitle');
    const scopeModalSubtitle = document.getElementById('scopeModalSubtitle');
    const scopeModalSummary = document.getElementById('scopeModalSummary');
    const scopeModalNote = document.getElementById('scopeModalNote');
    const scopeModalHead = document.getElementById('scopeModalHead');
    const scopeModalBody = document.getElementById('scopeModalBody');
    const cityComparisonModal = document.getElementById('cityComparisonModal');
    const cityComparisonFilterButtons = Array.from(document.querySelectorAll('[data-city-comparison-filter]'));
    const cityComparisonBody = document.getElementById('cityComparisonBody');
    const cityComparisonEmptyRow = document.getElementById('cityComparisonEmptyRow');
    const agendaModal = document.getElementById('agendaModal');
    const agendaListModal = document.getElementById('agendaListModal');
    const agendaModalTitle = document.getElementById('agendaModalTitle');
    const agendaModalSubtitle = document.getElementById('agendaModalSubtitle');
    const agendaModalSummary = document.getElementById('agendaModalSummary');
    const agendaPreviewArea = document.getElementById('agendaPreviewArea');
    const agendaPreviewNote = document.getElementById('agendaPreviewNote');
    const agendaFilterButtons = Array.from(document.querySelectorAll('[data-agenda-filter]'));
    let agendaFilter = 'pending';
    let scopeModalColspan = 8;
    let cityComparisonFilter = 'all';
    const themeToggleButtons = Array.from(document.querySelectorAll('[data-theme-toggle]'));

    function normalizePremiumTheme(value) {
        return String(value) === 'light' ? 'light' : 'dark';
    }

    function applyPremiumTheme(theme, persist = true) {
        const normalizedTheme = normalizePremiumTheme(theme);
        document.documentElement.dataset.theme = normalizedTheme;
        document.documentElement.style.colorScheme = normalizedTheme;

        if (persist) {
            try {
                localStorage.setItem('premium-theme', normalizedTheme);
            } catch (error) {
                // Ignore storage failures and keep the current theme in memory.
            }
        }

        themeToggleButtons.forEach((button) => {
            const isActive = normalizePremiumTheme(button.dataset.themeToggle || '') === normalizedTheme;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    applyPremiumTheme(document.documentElement.dataset.theme || 'dark', false);

    themeToggleButtons.forEach((button) => {
        button.addEventListener('click', () => {
            applyPremiumTheme(button.dataset.themeToggle || 'dark');
        });
    });

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    function normalizeText(value) {
        return String(value ?? '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim()
            .replace(/\s+/g, ' ');
    }

    function findForecastCity(cityName) {
        const needle = normalizeText(cityName);
        return (premiumForecast.cities || []).find((item) => normalizeText(item.municipio || '') === needle) || null;
    }

    function findForecastRegion(regionName) {
        const needle = normalizeText(regionName);
        return (premiumForecast.regions || []).find((item) => normalizeText(item.regiao || '') === needle) || null;
    }

    function getScopeLeaders(scopeType, scopeName) {
        const needle = normalizeText(scopeName);
        const leaders = (premiumForecast.leaders || []).filter((leader) => {
            if (scopeType === 'region') {
                return normalizeText(leader.region_name || '') === needle;
            }
            return normalizeText(leader.municipality || '') === needle;
        });

        return leaders.sort((a, b) => {
            const projectionCompare = Number(b.projected_votes || 0) - Number(a.projected_votes || 0);
            if (projectionCompare !== 0) {
                return projectionCompare;
            }

            const votesCompare = Number(b.leader_votes_2024 || 0) - Number(a.leader_votes_2024 || 0);
            if (votesCompare !== 0) {
                return votesCompare;
            }

            return String(a.leader_display_name || a.leader_name || '').localeCompare(String(b.leader_display_name || b.leader_name || ''), 'pt-BR');
        });
    }

    function clearScopeModal() {
        if (scopeModalTitle) {
            scopeModalTitle.textContent = 'Selecione um recorte territorial';
        }
        if (scopeModalSubtitle) {
            scopeModalSubtitle.textContent = 'Clique em uma cidade ou região para ver as lideranças, as projeções individuais e o comparativo com 2022.';
        }
        if (scopeModalSummary) {
            scopeModalSummary.innerHTML = '';
        }
        if (scopeModalNote) {
            scopeModalNote.textContent = 'O detalhe territorial mostrará o total de votos de 2022 apenas como comparativo e destacará a projeção atual construída pelas lideranças cadastradas.';
        }
        if (scopeModalHead) {
            scopeModalHead.innerHTML = `
                <tr>
                    <th>Liderança</th>
                    <th>Município</th>
                    <th>Votos 2024</th>
                    <th>Base transferível</th>
                    <th>Projeção</th>
                    <th>Transferência</th>
                    <th>Ação</th>
                </tr>
            `;
        }
        if (scopeModalBody) {
            scopeModalBody.innerHTML = `<tr><td colspan="${scopeModalColspan}" class="muted">Selecione uma cidade ou região para carregar os líderes.</td></tr>`;
        }
    }

    function openScopeModal(scopeType, scopeName) {
        if (!scopeModal) {
            return;
        }

        const normalizedType = scopeType === 'region' ? 'region' : 'city';
        scopeModalColspan = normalizedType === 'city' ? 7 : 8;
        closeLeaderModal(false);
        closeCityComparisonModal(false);
        closeAgendaModal(false);
        closeAgendaListModal(false);
        const leaders = getScopeLeaders(normalizedType, scopeName);
        const cityScope = normalizedType === 'city' ? findForecastCity(scopeName) : null;
        const regionScope = normalizedType === 'region' ? findForecastRegion(scopeName) : null;
        const scopeData = cityScope || regionScope;
        const comparativeBase = Number(scopeData?.baseline_votes || 0);
        const projected = Number(scopeData?.projected_base || 0);
        const delta = projected - comparativeBase;
        const leaderEffect = leaders.reduce((sum, leader) => sum + Number(leader.projected_votes || 0), 0);
        const totalVotes2024 = leaders.reduce((sum, leader) => sum + Number(leader.leader_votes_2024 || 0), 0);
        const baseTransferable = leaders.reduce((sum, leader) => sum + Number(leader.base_effect || 0), 0);

        if (scopeModalTitle) {
            scopeModalTitle.textContent = `${scopeName || 'Recorte territorial'} - ${normalizedType === 'region' ? 'Região' : 'Cidade'}`;
        }

        if (scopeModalSubtitle) {
            const comparativeBase = Number(scopeData?.baseline_votes || 0);
            const projected = Number(scopeData?.projected_base || 0);
            const delta = projected - comparativeBase;
            scopeModalSubtitle.textContent = `${scopeName || 'Recorte territorial'} • Comparativo 2022: ${formatNumber(comparativeBase)} • Projeção atual: ${formatNumber(projected)} • Delta: ${delta >= 0 ? '+' : ''}${formatNumber(delta)}`;
        }

        if (scopeModalSummary) {
            const comparativeBase = Number(scopeData?.baseline_votes || 0);
            const projected = Number(scopeData?.projected_base || 0);
            const delta = projected - comparativeBase;
            const leaderEffect = leaders.reduce((sum, leader) => sum + Number(leader.projected_votes || 0), 0);
            const totalVotes2024 = leaders.reduce((sum, leader) => sum + Number(leader.leader_votes_2024 || 0), 0);
            const baseTransferable = leaders.reduce((sum, leader) => sum + Number(leader.base_effect || 0), 0);
            scopeModalSummary.innerHTML = [
                `<span class="table-pill">2022: ${formatNumber(comparativeBase)}</span>`,
                `<span class="table-pill">Projeção: ${formatNumber(projected)}</span>`,
                `<span class="table-pill">Delta: ${delta >= 0 ? '+' : ''}${formatNumber(delta)}</span>`,
                `<span class="table-pill">Lideranças: ${formatNumber(leaders.length)}</span>`,
                `<span class="table-pill">Votos 2024: ${formatNumber(totalVotes2024)}</span>`,
                `<span class="table-pill">Base transferível: ${formatNumber(baseTransferable)}</span>`,
                `<span class="table-pill">efeito das lideranças: ${formatNumber(leaderEffect)}</span>`,
            ].join('');
        }

        if (scopeModalNote) {
            const hasLeaders = leaders.length > 0;
            scopeModalNote.textContent = hasLeaders
                ? 'As lideranças abaixo são as cadastradas para este recorte. A projeção total da cidade ou região é calculada a partir dos votos das lideranças; o total de 2022 aparece apenas como comparativo.'
                : 'Nenhuma liderança cadastrada neste recorte. Nesse caso, a projeção do território pode cair no fallback de 2022 para manter a leitura estratégica.';
        }

        if (scopeModalHead) {
            if (normalizedType === 'city') {
                scopeModalHead.innerHTML = `
                    <tr>
                        <th>Liderança</th>
                        <th>Votos 2024</th>
                        <th>Base transferível</th>
                        <th>Projeção</th>
                        <th>Transferência</th>
                        <th>Ação</th>
                    </tr>
                `;
            } else {
                scopeModalHead.innerHTML = `
                    <tr>
                        <th>Município</th>
                        <th>Liderança</th>
                        <th>Votos 2024</th>
                        <th>Base transferível</th>
                        <th>Projeção</th>
                        <th>Transferência</th>
                        <th>Ação</th>
                    </tr>
                `;
            }
        }

        if (scopeModalBody) {
            if (!leaders.length) {
                scopeModalBody.innerHTML = `<tr><td colspan="${scopeModalColspan}" class="muted">Nenhuma liderança cadastrada neste recorte.</td></tr>`;
            } else {
                scopeModalBody.innerHTML = leaders.map((leader) => {
                    const leaderDisplayName = leader.leader_display_name || leader.leader_name || 'Liderança';
                    const municipality = leader.municipality || scopeName || '-';
                    const votes = formatNumber(leader.leader_votes_2024 || 0);
                    const baseEffect = formatNumber(leader.base_effect || 0);
                    const projectedVotes = formatNumber(leader.projected_votes || 0);
                    const transferRate = formatNumber(leader.transfer_rate || 0) + '%';
                    const actionButton = leader.id
                        ? `<button type="button" class="btn ghost btn-small" data-leader-id="${escapeHtml(leader.id)}">Abrir</button>`
                        : '<span class="muted">-</span>';

                    if (normalizedType === 'city') {
                        return `
                            <tr>
                                <td>${escapeHtml(leaderDisplayName)}</td>
                                <td>${votes}</td>
                                <td>${baseEffect}</td>
                                <td>${projectedVotes}</td>
                                <td>${transferRate}</td>
                                <td>${actionButton}</td>
                            </tr>
                        `;
                    }

                    return `
                        <tr>
                            <td>${escapeHtml(municipality)}</td>
                            <td>${escapeHtml(leaderDisplayName)}</td>
                            <td>${votes}</td>
                            <td>${baseEffect}</td>
                            <td>${projectedVotes}</td>
                            <td>${transferRate}</td>
                            <td>${actionButton}</td>
                        </tr>
                    `;
                }).join('');
            }
        }

        if (scopeModalSubtitle) {
            scopeModalSubtitle.textContent = `${scopeName || 'Recorte territorial'} • Ranking por projeção individual • Comparativo 2022: ${formatNumber(comparativeBase)} • Projeção atual: ${formatNumber(projected)} • Delta: ${delta >= 0 ? '+' : ''}${formatNumber(delta)}`;
        }

        if (scopeModalSummary) {
            const topLeader = leaders[0] || null;
            const topLeaderName = topLeader ? (topLeader.leader_display_name || topLeader.leader_name || 'Liderança') : 'Sem liderança';
            scopeModalSummary.innerHTML = `
                <div class="scope-summary-grid">
                    <div class="summary-metric summary-metric--primary">
                        <div class="summary-metric__label">Projeção total</div>
                        <div class="summary-metric__value">${formatNumber(projected)}</div>
                        <div class="summary-metric__sub">Total projetado do recorte territorial</div>
                    </div>
                    <div class="summary-metric summary-metric--delta">
                        <div class="summary-metric__label">Diferença para 2022</div>
                        <div class="summary-metric__value">${delta >= 0 ? '+' : ''}${formatNumber(delta)}</div>
                        <div class="summary-metric__sub">Comparativo sobre a base histórica</div>
                    </div>
                    <div class="summary-metric">
                        <div class="summary-metric__label">Lideranças</div>
                        <div class="summary-metric__value">${formatNumber(leaders.length)}</div>
                        <div class="summary-metric__sub">Ordenadas pelo ranking interno do recorte</div>
                    </div>
                </div>
                <div class="scope-summary-meta">
                    <span class="table-pill">2022: ${formatNumber(comparativeBase)}</span>
                    <span class="table-pill">Votos 2024: ${formatNumber(totalVotes2024)}</span>
                    <span class="table-pill">Base transferível: ${formatNumber(baseTransferable)}</span>
                    <span class="table-pill">efeito das lideranças: ${formatNumber(leaderEffect)}</span>
                    <span class="table-pill">Top 1: ${escapeHtml(topLeaderName)}</span>
                </div>
            `;
        }

        if (scopeModalNote) {
            scopeModalNote.textContent = leaders.length
                ? 'Ranking ordenado por projeção individual. A projeção total soma as lideranças cadastradas e usa 2022 como fallback apenas onde não houver liderança.'
                : 'Nenhuma liderança cadastrada neste recorte. A projeção pode usar o fallback de 2022 para manter a leitura estratégica.';
        }

        if (scopeModalHead) {
            if (normalizedType === 'city') {
                scopeModalHead.innerHTML = `
                    <tr>
                        <th>Posição</th>
                        <th>Liderança</th>
                        <th>Votos 2024</th>
                        <th>Base transferível</th>
                        <th>Projeção</th>
                        <th>Transferência</th>
                        <th>Ação</th>
                    </tr>
                `;
            } else {
                scopeModalHead.innerHTML = `
                    <tr>
                        <th>Posição</th>
                        <th>Município</th>
                        <th>Liderança</th>
                        <th>Votos 2024</th>
                        <th>Base transferível</th>
                        <th>Projeção</th>
                        <th>Transferência</th>
                        <th>Ação</th>
                    </tr>
                `;
            }
        }

        if (scopeModalBody) {
            if (!leaders.length) {
                scopeModalBody.innerHTML = `<tr><td colspan="${scopeModalColspan}" class="muted">Nenhuma liderança cadastrada neste recorte.</td></tr>`;
            } else {
                scopeModalBody.innerHTML = leaders.map((leader, index) => {
                    const rank = String(index + 1).padStart(2, '0');
                    const rankClass = index === 0 ? 'scope-rank-badge scope-rank-badge--top' : index === 1 ? 'scope-rank-badge scope-rank-badge--silver' : index === 2 ? 'scope-rank-badge scope-rank-badge--bronze' : 'scope-rank-badge';
                    const rowClass = index === 0 ? 'scope-row--top' : '';
                    const leaderDisplayName = leader.leader_display_name || leader.leader_name || 'Liderança';
                    const municipality = leader.municipality || scopeName || '-';
                    const votes = formatNumber(leader.leader_votes_2024 || 0);
                    const baseEffect = formatNumber(leader.base_effect || 0);
                    const projectedVotes = formatNumber(leader.projected_votes || 0);
                    const transferRate = formatNumber(leader.transfer_rate || 0) + '%';
                    const actionButton = leader.id
                        ? `<button type="button" class="btn ghost btn-small" data-leader-id="${escapeHtml(leader.id)}">Abrir</button>`
                        : '<span class="muted">-</span>';

                    if (normalizedType === 'city') {
                        return `
                            <tr class="${rowClass}">
                                <td><span class="${rankClass}">${rank}</span></td>
                                <td>${escapeHtml(leaderDisplayName)}</td>
                                <td>${votes}</td>
                                <td>${baseEffect}</td>
                                <td>${projectedVotes}</td>
                                <td>${transferRate}</td>
                                <td>${actionButton}</td>
                            </tr>
                        `;
                    }

                    return `
                        <tr class="${rowClass}">
                            <td><span class="${rankClass}">${rank}</span></td>
                            <td>${escapeHtml(municipality)}</td>
                            <td>${escapeHtml(leaderDisplayName)}</td>
                            <td>${votes}</td>
                            <td>${baseEffect}</td>
                            <td>${projectedVotes}</td>
                            <td>${transferRate}</td>
                            <td>${actionButton}</td>
                        </tr>
                    `;
                }).join('');
            }
        }

        scopeModal.hidden = false;
        scopeModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeScopeModal(updateBody = true) {
        if (!scopeModal) {
            return;
        }

        scopeModal.hidden = true;
        scopeModal.setAttribute('aria-hidden', 'true');
        clearScopeModal();
        if (updateBody) {
            document.body.classList.remove('modal-open');
        }
    }

    function applyCityComparisonFilter(filter = 'all') {
        cityComparisonFilter = ['leaders', 'fallback'].includes(filter) ? filter : 'all';

        cityComparisonFilterButtons.forEach((button) => {
            const isActive = (button.dataset.cityComparisonFilter || 'all') === cityComparisonFilter;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        let visibleCount = 0;
        cityComparisonBody?.querySelectorAll('[data-city-comparison-row]')?.forEach((row) => {
            const rowMode = row.dataset.cityMode || 'all';
            const shouldShow = cityComparisonFilter === 'all' || rowMode === cityComparisonFilter;
            row.hidden = !shouldShow;
            if (shouldShow) {
                visibleCount += 1;
            }
        });

        if (cityComparisonEmptyRow) {
            cityComparisonEmptyRow.hidden = visibleCount > 0;
        }
    }

    function openCityComparisonModal() {
        if (!cityComparisonModal) {
            return;
        }

        closeLeaderModal(false);
        closeScopeModal(false);
        closeAgendaModal(false);
        closeAgendaListModal(false);
        applyCityComparisonFilter(cityComparisonFilter);

        cityComparisonModal.hidden = false;
        cityComparisonModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeCityComparisonModal(updateBody = true) {
        if (!cityComparisonModal) {
            return;
        }

        cityComparisonModal.hidden = true;
        cityComparisonModal.setAttribute('aria-hidden', 'true');
        if (updateBody) {
            document.body.classList.remove('modal-open');
        }
    }

    function getCityComparisonFilterLabel(filter) {
        if (filter === 'leaders') {
            return 'Com lideranças';
        }
        if (filter === 'fallback') {
            return 'Sem lideranças';
        }

        return 'Todas as cidades';
    }

    function getCityComparisonRows(filter = 'all') {
        const normalizedFilter = ['all', 'leaders', 'fallback'].includes(filter) ? filter : 'all';

        return (premiumForecast.cities || []).filter((city) => {
            const hasLeaders = Number(city.leader_count || 0) > 0;
            const rowMode = hasLeaders ? 'leaders' : 'fallback';
            return normalizedFilter === 'all' || normalizedFilter === rowMode;
        });
    }

    function buildCityComparisonReportHtml(filter = 'all') {
        const rows = getCityComparisonRows(filter);
        const generatedAt = new Intl.DateTimeFormat('pt-BR', {
            dateStyle: 'long',
            timeStyle: 'short',
        }).format(new Date());
        const campaignLabel = [
            premiumCampaign.campaign_name || 'Campanha Premium',
            premiumCampaign.candidate_name || '',
            premiumCampaign.candidate_cargo || '',
        ].filter(Boolean).join(' • ');
        const coverageLabel = [
            premiumCampaign.current_region || '',
            premiumCampaign.current_municipio || '',
        ].filter(Boolean).join(' • ') || 'Sergipe';
        const filterLabel = getCityComparisonFilterLabel(filter);

        const baselineTotal = rows.reduce((sum, city) => sum + Number(city.baseline_votes || 0), 0);
        const systemTotal = rows.reduce((sum, city) => sum + Number(city.system_projection || city.projected_base || 0), 0);
        const leaderVotesTotal = rows.reduce((sum, city) => sum + Number(city.leader_projection || city.leader_effect || 0), 0);
        const independentTotal = rows.reduce((sum, city) => sum + Number(city.independent_votes || 0), 0);
        const withLeaders = rows.filter((city) => Number(city.leader_count || 0) > 0).length;
        const withoutLeaders = rows.length - withLeaders;
        const deltaTotal = systemTotal - baselineTotal;

        const rowsHtml = rows.length ? rows.map((city, index) => {
            const municipality = city.municipio || '';
            const region = city.regiao || 'Sem região';
            const baselineVotes = Number(city.baseline_votes || 0);
            const leaderCount = Number(city.leader_count || 0);
            const leaderVotes = Number(city.leader_projection || city.leader_effect || 0);
            const independentVotes = Number(city.independent_votes || 0);
            const systemProjection = Number(city.system_projection || city.projected_base || 0);
            const delta = systemProjection - baselineVotes;
            const hasLeaders = leaderCount > 0;
            const statusLabel = hasLeaders ? 'Com lideranças' : 'Fallback 2022';
            const statusClass = hasLeaders ? 'report-status report-status--leaders' : 'report-status report-status--fallback';
            const rank = String(index + 1).padStart(2, '0');

            return `
                <tr class="${hasLeaders ? 'report-row--leaders' : 'report-row--fallback'}">
                    <td><span class="report-rank">${rank}</span> ${escapeHtml(municipality)}</td>
                    <td>${escapeHtml(region)}</td>
                    <td>${formatNumber(baselineVotes)}</td>
                    <td>${formatNumber(leaderVotes)}</td>
                    <td>${formatNumber(independentVotes)}</td>
                    <td>${formatNumber(systemProjection)}</td>
                    <td>${delta >= 0 ? '+' : ''}${formatNumber(delta)}</td>
                    <td><span class="${statusClass}">${escapeHtml(statusLabel)}</span></td>
                </tr>
            `;
        }).join('') : `
            <tr>
                <td colspan="8" class="report-empty">Nenhuma cidade corresponde ao filtro selecionado.</td>
            </tr>
        `;

        return `
<!DOCTYPx html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Comparativo | ${escapeHtml(campaignLabel)}</title>
    <style>
        :root {
            --bg: #f6f8fb;
            --paper: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --line: rgba(15, 23, 42, 0.10);
            --accent: #0f766e;
            --accent-2: #0284c7;
            --accent-3: #f59e0b;
            --success-bg: rgba(16, 185, 129, 0.10);
            --fallback-bg: rgba(2, 132, 199, 0.08);
            --shadow: 0 24px 80px rgba(15, 23, 42, 0.10);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background:
                radial-gradient(circle at top left, rgba(16, 185, 129, 0.10), transparent 24%),
                radial-gradient(circle at top right, rgba(2, 132, 199, 0.08), transparent 20%),
                var(--bg);
            color: var(--text);
            font-family: Inter, Arial, sans-serif;
        }

        .report-shell {
            max-width: 1280px;
            margin: 0 auto;
            padding: 28px 24px 36px;
        }

        .report-hero {
            background: linear-gradient(135deg, #ffffff, #eef7f5);
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 24px 26px;
            box-shadow: var(--shadow);
            margin-bottom: 18px;
        }

        .report-hero__top {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .report-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(15, 118, 110, 0.10);
            color: var(--accent);
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            font-size: .74rem;
        }

        .report-hero h1 {
            margin: 12px 0 8px;
            font-size: clamp(1.8rem, 3vw, 2.8rem);
            line-height: 1.02;
        }

        .report-hero p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .report-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .report-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,0.72);
            font-size: .8rem;
            font-weight: 700;
        }

        .report-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .report-action {
            appearance: none;
            border: 1px solid transparent;
            border-radius: 14px;
            padding: 12px 18px;
            font: inherit;
            font-weight: 900;
            letter-spacing: .04em;
            text-transform: uppercase;
            cursor: pointer;
        }

        .report-action--primary {
            background: linear-gradient(135deg, #0f766e, #0284c7);
            color: #f8fffb;
            box-shadow: 0 16px 28px rgba(2, 132, 199, 0.18);
        }

        .report-action--ghost {
            background: rgba(15, 23, 42, 0.04);
            color: var(--text);
            border-color: rgba(15, 23, 42, 0.12);
        }

        .report-summary {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 12px;
            margin: 18px 0;
        }

        .report-card {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 16px;
            box-shadow: 0 12px 34px rgba(15, 23, 42, 0.05);
            min-height: 116px;
        }

        .report-card__label {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .68rem;
            font-weight: 800;
        }

        .report-card__value {
            margin-top: 8px;
            font-size: 1.55rem;
            font-weight: 900;
            line-height: 1.06;
        }

        .report-card__sub {
            margin-top: 6px;
            color: var(--muted);
            font-size: .78rem;
            line-height: 1.4;
        }

        .report-notes {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin: 0 0 18px;
        }

        .report-note {
            background: rgba(255,255,255,0.75);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 14px 16px;
            color: var(--muted);
            font-size: .84rem;
            line-height: 1.6;
        }

        .report-legend {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .report-legend__item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.75);
            border: 1px solid var(--line);
            font-size: .78rem;
            font-weight: 700;
        }

        .report-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            flex: 0 0 10px;
        }

        .report-dot--leaders { background: #0f766e; }
        .report-dot--fallback { background: #0284c7; }

        .report-table-wrap {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: .78rem;
        }

        .report-table th,
        .report-table td {
            padding: 10px 10px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            vertical-align: top;
            word-wrap: break-word;
        }

        .report-table thead th {
            background: linear-gradient(180deg, #f8fafc, #eef2f7);
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .67rem;
            color: var(--muted);
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .report-table tbody tr:nth-child(even) {
            background: rgba(15, 23, 42, 0.015);
        }

        .report-row--leaders {
            background: rgba(16, 185, 129, 0.05);
        }

        .report-row--fallback {
            background: rgba(2, 132, 199, 0.04);
        }

        .report-rank {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 30px;
            height: 30px;
            padding: 0 9px;
            margin-right: 8px;
            border-radius: 999px;
            background: #0f172a;
            color: #fff;
            font-size: .72rem;
            font-weight: 900;
        }

        .report-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: .7rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .report-status--leaders {
            background: rgba(16, 185, 129, 0.12);
            color: #0f766e;
        }

        .report-status--fallback {
            background: rgba(2, 132, 199, 0.12);
            color: #0369a1;
        }

        .report-empty {
            padding: 22px;
            color: var(--muted);
            text-align: center;
        }

        .report-footer {
            margin-top: 14px;
            color: var(--muted);
            font-size: .78rem;
            line-height: 1.5;
        }

        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        @media print {
            body {
                background: #fff;
                font-size: 11px;
                line-height: 1.25;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .report-shell {
                max-width: none;
                padding: 0;
            }

            .report-hero {
                padding: 14px 16px;
                border-radius: 18px;
                margin-bottom: 12px;
            }

            .report-brand {
                padding: 5px 9px;
                font-size: .62rem;
            }

            .report-hero h1 {
                margin: 8px 0 6px;
                font-size: 1.45rem;
                line-height: 1.05;
            }

            .report-hero p {
                line-height: 1.3;
                font-size: .8rem;
            }

            .report-meta {
                margin-top: 10px;
                gap: 6px;
            }

            .report-pill {
                padding: 5px 9px;
                font-size: .68rem;
            }

            .report-actions {
                display: none !important;
            }

            .report-summary {
                gap: 8px;
                margin: 12px 0;
            }

            .report-card {
                padding: 9px 10px;
                min-height: 82px;
                border-radius: 14px;
            }

            .report-card__label {
                font-size: .6rem;
            }

            .report-card__value {
                margin-top: 4px;
                font-size: 1.1rem;
                line-height: 1.02;
            }

            .report-card__sub {
                margin-top: 3px;
                font-size: .65rem;
                line-height: 1.25;
            }

            .report-notes {
                gap: 8px;
                margin: 0 0 12px;
            }

            .report-note {
                padding: 9px 10px;
                font-size: .7rem;
                line-height: 1.3;
                border-radius: 12px;
            }

            .report-legend {
                gap: 6px;
                margin-bottom: 10px;
            }

            .report-legend__item {
                padding: 5px 9px;
                font-size: .68rem;
            }

            .report-table-wrap {
                border-radius: 14px;
            }

            .report-table {
                font-size: .64rem;
            }

            .report-table th,
            .report-table td {
                padding: 6px 8px;
                line-height: 1.2;
            }

            .report-table thead th {
                font-size: .58rem;
            }

            .report-rank {
                min-width: 20px;
                height: 20px;
                padding: 0 5px;
                margin-right: 5px;
                font-size: .56rem;
                line-height: 1;
            }

            .report-status {
                padding: 4px 8px;
                font-size: .6rem;
            }

            .report-empty {
                padding: 16px;
            }

            .report-footer {
                margin-top: 10px;
                font-size: .68rem;
                line-height: 1.3;
            }

            .report-hero,
            .report-card,
            .report-note,
            .report-legend__item,
            .report-table-wrap {
                box-shadow: none !important;
            }

            .report-table thead {
                display: table-header-group;
            }

            .report-table tr {
                page-break-inside: avoid;
            }
        }

        @media (max-width: 1120px) {
            .report-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .report-notes {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                try {
                    window.print();
                } catch (error) {}
            }, 350);
        });
    <\/script>
</head>
<body>
    <div class="report-shell">
        <section class="report-hero">
            <div class="report-hero__top">
                <div>
                    <div class="report-brand">Apoia Candidato Premium</div>
                    <h1>Comparativo municipal 2022 x projeção 2026</h1>
                    <p>${escapeHtml(campaignLabel)}</p>
                    <div class="report-meta">
                        <span class="report-pill">Cobertura: ${escapeHtml(coverageLabel)}</span>
                        <span class="report-pill">Filtro: ${escapeHtml(filterLabel)}</span>
                        <span class="report-pill">Gerado em: ${escapeHtml(generatedAt)}</span>
                    </div>
                </div>
                <div class="report-actions">
                    <button class="report-action report-action--primary" type="button" onclick="window.print()">Imprimir</button>
                    <button class="report-action report-action--ghost" type="button" onclick="window.close()">Fechar</button>
                </div>
            </div>
            <div class="report-notes" style="margin-top: 16px;">
                <div class="report-note">
                    <strong>Votos de liderança</strong> representam a parcela da projeção atribuída às lideranças cadastradas em cada município.
                </div>
                <div class="report-note">
                    <strong>Votos independentes</strong> representam a parcela da projeção que não depende de liderança cadastrada; nas cidades sem liderança, o sistema usa o fallback de 2022.
                </div>
            </div>
        </section>

        <section class="report-summary">
            <div class="report-card">
                <div class="report-card__label">Comparativo 2022</div>
                <div class="report-card__value">${formatNumber(baselineTotal)}</div>
                <div class="report-card__sub">Base histórica do recorte exibido</div>
            </div>
            <div class="report-card">
                <div class="report-card__label">Projeção 2026</div>
                <div class="report-card__value">${formatNumber(systemTotal)}</div>
                <div class="report-card__sub">Total projetado pelo modelo</div>
            </div>
            <div class="report-card">
                <div class="report-card__label">Delta total</div>
                <div class="report-card__value">${deltaTotal >= 0 ? '+' : ''}${formatNumber(deltaTotal)}</div>
                <div class="report-card__sub">Diferença entre projeção e 2022</div>
            </div>
            <div class="report-card">
                <div class="report-card__label">Com lideranças</div>
                <div class="report-card__value">${formatNumber(withLeaders)}</div>
                <div class="report-card__sub">Municípios com apoio cadastrado</div>
            </div>
            <div class="report-card">
                <div class="report-card__label">Sem lideranças</div>
                <div class="report-card__value">${formatNumber(withoutLeaders)}</div>
                <div class="report-card__sub">Municípios que usam fallback</div>
            </div>
            <div class="report-card">
                <div class="report-card__label">Votos de liderança</div>
                <div class="report-card__value">${formatNumber(leaderVotesTotal)}</div>
                <div class="report-card__sub">Parcela atribuída às lideranças</div>
            </div>
        </section>

        <div class="report-legend">
            <span class="report-legend__item"><span class="report-dot report-dot--leaders"></span>Municípios com lideranças</span>
            <span class="report-legend__item"><span class="report-dot report-dot--fallback"></span>Municípios sem lideranças</span>
            <span class="report-legend__item">Votos independentes = projeção fora das lideranças</span>
        </div>

        <section class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Município</th>
                        <th>Região</th>
                        <th>2022</th>
                        <th>Votos liderança</th>
                        <th>Votos independentes</th>
                        <th>Projeção 2026</th>
                        <th>Delta</th>
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHtml}
                </tbody>
            </table>
        </section>

        <div class="report-footer">
            Relatório elaborado a partir do módulo premium. Os votos de 2022 entram como comparativo e como fallback apenas nos municípios sem lideranças cadastradas.
        </div>
    </div>
</body>
</html>`;
    }

    function openCityComparisonReport() {
        if (!cityComparisonModal) {
            return;
        }

        const reportWindow = window.open('', '_blank', 'width=1280,height=900');
        if (!reportWindow) {
            alert('Não foi possível abrir o relatório de impressão. Verifique se o navegador bloqueou a janela.');
            return;
        }

        reportWindow.document.open();
        reportWindow.document.write(buildCityComparisonReportHtml(cityComparisonFilter));
        reportWindow.document.close();
        reportWindow.focus();
    }

    function getLeaderSearchCargo() {
        return document.getElementById('searchCargo')?.value || 'Prefeito';
    }

    function getLeaderSearchCheckboxes() {
        if (!resultsBody) {
            return [];
        }

        return Array.from(resultsBody.querySelectorAll('.leader-batch-checkbox'));
    }

    function resetLeaderBatchSelectionState() {
        if (leaderBatchSelectedCount) {
            leaderBatchSelectedCount.textContent = '0 selecionadas';
        }
        if (leaderBatchSubmitBtn) {
            leaderBatchSubmitBtn.disabled = true;
        }
        if (leaderSelectAll) {
            leaderSelectAll.checked = false;
            leaderSelectAll.indeterminate = false;
        }
        if (leaderBatchPayload) {
            leaderBatchPayload.value = '';
        }
        getLeaderSearchCheckboxes().forEach((checkbox) => {
            const row = checkbox.closest('tr');
            if (row) {
                row.classList.remove('is-selected');
            }
        });
    }

    function updateLeaderBatchSelectionState() {
        const checkboxes = getLeaderSearchCheckboxes();
        const selected = checkboxes.filter((checkbox) => checkbox.checked);

        if (leaderBatchSelectedCount) {
            leaderBatchSelectedCount.textContent = `${selected.length} selecionadas`;
        }

        if (leaderBatchSubmitBtn) {
            leaderBatchSubmitBtn.disabled = selected.length === 0;
        }

        if (leaderSelectAll) {
            leaderSelectAll.checked = checkboxes.length > 0 && selected.length === checkboxes.length;
            leaderSelectAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
        }

        checkboxes.forEach((checkbox) => {
            const row = checkbox.closest('tr');
            if (row) {
                row.classList.toggle('is-selected', checkbox.checked);
            }
        });
    }

    function setLeaderBatchSelection(checked) {
        getLeaderSearchCheckboxes().forEach((checkbox) => {
            checkbox.checked = checked;
        });
        updateLeaderBatchSelectionState();
    }

    function buildLeaderBatchPayload() {
        const cargo = getLeaderSearchCargo();

        return getLeaderSearchCheckboxes()
            .filter((checkbox) => checkbox.checked)
            .map((checkbox) => ({
                region_name: checkbox.dataset.regionName || '',
                municipality: checkbox.dataset.municipality || '',
                leader_name: checkbox.dataset.leaderDisplayName || checkbox.dataset.leaderName || '',
                leader_cargo: checkbox.dataset.cargo || cargo,
                leader_party: checkbox.dataset.party || '',
                source_sq_candidato: checkbox.dataset.sq || '',
                source_nr_votavel: checkbox.dataset.nrVotavel || '',
                source_turno: checkbox.dataset.turno || '1',
                leader_votes_2024: checkbox.dataset.votes || '0',
                margin_percent: checkbox.dataset.margin || '0',
                transfer_rate: leaderBatchDefaultTransfer,
                aligned_with_executive: 0,
                visibility_score: 50,
                investment_score: 50,
                size_class: checkbox.dataset.sizeClass || 'medium',
                notes: '',
            }));
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('pt-BR').format(Number(value || 0));
    }

    function formatSizeLabel(value) {
        if (value === 'small') {
            return 'Pequeno';
        }
        if (value === 'large') {
            return 'Grande';
        }

        return 'Médio';
    }

    function formatAgendaDate(value) {
        if (!value) {
            return 'Sem prazo';
        }

        const [year, month, day] = String(value).split('-').map((part) => Number(part || 0));
        if (!year || !month || !day) {
            return String(value);
        }

        const date = new Date(Date.UTC(year, month - 1, day));
        return new Intl.DateTimeFormat('pt-BR', { timeZone: 'UTC' }).format(date);
    }

    function agendaStatusLabel(status) {
        if (status === 'doing') {
            return 'xm andamento';
        }
        if (status === 'done') {
            return 'Concluída';
        }
        if (status === 'archived') {
            return 'Arquivada';
        }

        return 'Aberta';
    }

    function agendaPriorityLabel(priority) {
        if (priority === 'low') {
            return 'Baixa';
        }
        if (priority === 'high') {
            return 'Alta';
        }
        if (priority === 'urgent') {
            return 'Urgente';
        }

        return 'Média';
    }

    function agendaFilterLabel(filter) {
        if (filter === 'done') {
            return 'Concluídas';
        }
        if (filter === 'archived') {
            return 'Arquivadas';
        }

        return 'Pendentes';
    }

    function agendaMatchesFilter(item, filter) {
        const status = String(item?.status || 'open');
        if (filter === 'done') {
            return status === 'done';
        }
        if (filter === 'archived') {
            return status === 'archived';
        }

        return status === 'open' || status === 'doing';
    }

    function setAgendaFilterActive(filter) {
        agendaFilterButtons.forEach((button) => {
            const isActive = String(button.dataset.agendaFilter || '') === String(filter);
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function renderAgendaPreview(filter = 'pending') {
        if (!agendaPreviewArea) {
            return;
        }

        agendaFilter = filter;
        setAgendaFilterActive(filter);

        const filtered = premiumAgenda.filter((item) => agendaMatchesFilter(item, filter));
        const rows = filtered.slice(0, 5);

        if (!rows.length) {
            const emptyMessage = filter === 'done'
                ? 'Ainda não há tarefas concluídas.'
                : filter === 'archived'
                    ? 'Ainda não há tarefas arquivadas.'
                    : 'Não há tarefas pendentes no momento.';

            agendaPreviewArea.innerHTML = `<div class="empty-state">${escapeHtml(emptyMessage)} Use outro botão para trocar a visão da agenda.</div>`;
            if (agendaPreviewNote) {
                agendaPreviewNote.textContent = 'Nenhuma tarefa para esta visão.';
            }
            return;
        }

        const html = [];
        html.push('<div class="agenda-mini-list">');

        rows.forEach((item) => {
            const statusClass = String(item.status || 'open');
            const city = escapeHtml(item.municipality || '-');
            const title = escapeHtml(item.title || 'Tarefa');
            const leader = item.leader_name ? ` • ${escapeHtml(item.leader_name)}` : '';
            html.push('<article class="agenda-mini-card agenda-mini-card--' + escapeHtml(statusClass) + '">');
            html.push('  <div class="agenda-mini-card__main">');
            html.push('    <button type="button" class="agenda-mini-title agenda-open-btn" data-agenda-id="' + escapeHtml(item.id || '') + '">' + title + '</button>');
            html.push('    <div class="agenda-mini-meta">' + city + leader + '</div>');
            html.push('  </div>');
            html.push('  <div class="agenda-mini-card__side">');
            html.push('    <span class="agenda-mini-date">' + escapeHtml(formatAgendaDate(item.due_date || '')) + '</span>');
            html.push('    <button type="button" class="btn ghost btn-small agenda-open-btn" data-agenda-id="' + escapeHtml(item.id || '') + '">Abrir</button>');
            html.push('  </div>');
            html.push('</article>');
        });

        html.push('</div>');
        agendaPreviewArea.innerHTML = html.join('');

        if (agendaPreviewNote) {
            const total = filtered.length;
            const showing = Math.min(total, 5);
            const suffix = total > 5 ? ' Use "Ver todas as tarefas" para abrir a agenda completa.' : '';
            agendaPreviewNote.textContent = `Mostrando ${showing} de ${total} tarefa${total === 1 ? '' : 's'} ${agendaFilterLabel(filter).toLowerCase()}.${suffix}`;
        }
    }

    function openAgendaModal(agendaId, closeListModal = true) {
        if (!agendaModal) {
            return;
        }

        closeScopeModal(false);
        closeCityComparisonModal(false);
        const item = premiumAgenda.find((row) => String(row.id) === String(agendaId));
        if (!item) {
            return;
        }

        if (closeListModal) {
            closeAgendaListModal(false);
        }
        closeLeaderModal(false);

        const set = (id, value) => {
            const el = document.getElementById(id);
            if (el) {
                el.value = value ?? '';
            }
        };

        set('modalAgendaId', item.id ?? '');
        set('modalAgendaArchiveId', item.id ?? '');
        set('modalAgendaDeleteId', item.id ?? '');
        set('modalAgendaTitleInput', item.title || '');
        set('modalAgendaDueDate', item.due_date || '');
        set('modalAgendaPriority', item.priority || 'medium');
        set('modalAgendaStatus', item.status || 'open');
        set('modalAgendaMunicipality', item.municipality || '');
        set('modalAgendaLeader', item.leader_name || '');
        set('modalAgendaDescription', item.description || '');

        if (agendaModalTitle) {
            agendaModalTitle.textContent = item.title || 'Tarefa';
        }

        if (agendaModalSubtitle) {
            const bits = [
                item.municipality || 'Município',
                item.leader_name || 'Sem liderança',
                agendaStatusLabel(item.status || 'open'),
            ].filter(Boolean);
            agendaModalSubtitle.textContent = bits.join(' • ');
        }

        if (agendaModalSummary) {
            agendaModalSummary.innerHTML = [
                `<span class="table-pill">${escapeHtml(formatAgendaDate(item.due_date || ''))}</span>`,
                `<span class="table-pill">${escapeHtml(item.municipality || 'Sem município')}</span>`,
                `<span class="table-pill">${escapeHtml(item.leader_name || 'Sem liderança')}</span>`,
                `<span class="table-pill">${escapeHtml(agendaStatusLabel(item.status || 'open'))}</span>`,
                `<span class="table-pill">${escapeHtml(agendaPriorityLabel(item.priority || 'medium'))}</span>`,
            ].join('');
        }

        agendaModal.hidden = false;
        agendaModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeAgendaModal(updateBody = true) {
        if (!agendaModal) {
            return;
        }

        agendaModal.hidden = true;
        agendaModal.setAttribute('aria-hidden', 'true');
        if (updateBody) {
            document.body.classList.remove('modal-open');
        }
    }

    function openAgendaListModal() {
        if (!agendaListModal) {
            return;
        }

        closeScopeModal(false);
        closeCityComparisonModal(false);
        closeAgendaModal(false);
        closeLeaderModal(false);

        agendaListModal.hidden = false;
        agendaListModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeAgendaListModal(updateBody = true) {
        if (!agendaListModal) {
            return;
        }

        agendaListModal.hidden = true;
        agendaListModal.setAttribute('aria-hidden', 'true');
        if (updateBody) {
            document.body.classList.remove('modal-open');
        }
    }

    function closeAllModals() {
        closeLeaderModal(false);
        closeScopeModal(false);
        closeCityComparisonModal(false);
        closeAgendaModal(false);
        closeAgendaListModal(false);
        document.body.classList.remove('modal-open');
    }

    function syncLeaderRegionFromMunicipality(selectxl, targetId = 'leaderRegion') {
        const regionInput = document.getElementById(targetId);
        if (!regionInput || !selectxl) {
            return;
        }

        const option = selectxl.options?.[selectxl.selectedIndex];
        const regionName = option?.dataset?.region || '';
        regionInput.value = regionName || regionInput.value || '';
    }

    function fillLeaderFormFromResult(dataset) {
        const set = (id, value) => {
            const el = document.getElementById(id);
            if (el) {
                el.value = value ?? '';
            }
        };

        if (dataset.regionName) {
            set('leaderRegion', dataset.regionName || '');
        }
        set('leaderMunicipality', dataset.municipality || '');
        syncLeaderRegionFromMunicipality(document.getElementById('leaderMunicipality'), 'leaderRegion');
        set('leaderName', dataset.leaderDisplayName || dataset.leaderName || '');
        set('leaderCargo', dataset.cargo || '');
        set('leaderParty', dataset.party || '');
        set('leaderVotes', dataset.votes || '0');
        set('leaderMargin', dataset.margin || '0');
        set('leaderTransfer', document.getElementById('leaderTransfer')?.value || '40');
        set('leaderSizeClass', dataset.sizeClass || 'medium');
        set('sourceSq', dataset.sq || '');
        set('sourceNrVotavel', dataset.nrVotavel || '');
        set('sourceTurno', dataset.turno || '1');

        const leaderAddBody = document.getElementById('leaderAddBody');
        const leaderAddToggle = document.querySelector('[data-toggle-target="leaderAddBody"]');
        if (leaderAddBody && leaderAddBody.hidden) {
            toggleCollapsiblePanel('leaderAddBody', leaderAddToggle);
        }

        const leaderForm = document.getElementById('leaderForm');
        if (leaderForm && (!leaderAddBody || !leaderAddBody.hidden)) {
            window.scrollTo({ top: leaderForm.offsetTop - 24, behavior: 'smooth' });
        }
    }

    function updateLeaderModalSummary(leader) {
        if (!leaderModalSummary) {
            return;
        }

        leaderModalSummary.innerHTML = [
            `<span class="table-pill">${escapeHtml(leader.region_name || 'Sem região')}</span>`,
            `<span class="table-pill">${escapeHtml(leader.municipality || 'Sem município')}</span>`,
            `<span class="table-pill">${formatNumber(leader.leader_votes_2024 || 0)} votos</span>`,
            `<span class="table-pill">${formatNumber(leader.margin_percent || 0)}% margem</span>`,
            `<span class="table-pill">${formatNumber(leader.transfer_rate || 0)}% transferência</span>`,
            `<span class="table-pill">${escapeHtml(formatSizeLabel(leader.size_class || 'medium'))}</span>`,
        ].join('');
    }

    function openLeaderModal(leaderId) {
        if (!leaderModal) {
            return;
        }

        closeCityComparisonModal(false);
        closeScopeModal(false);
        closeAgendaModal(false);
        closeAgendaListModal(false);

        const leader = premiumLeaders.find((item) => String(item.id) === String(leaderId));
        if (!leader) {
            return;
        }

        const set = (id, value) => {
            const el = document.getElementById(id);
            if (el) {
                el.value = value ?? '';
            }
        };

        if (leaderModalTitle) {
            leaderModalTitle.textContent = leader.leader_display_name || leader.leader_name || 'Liderança';
        }

        if (leaderModalSubtitle) {
            leaderModalSubtitle.textContent = `${leader.municipality || 'Município'} • ${leader.leader_cargo || 'Cargo'}${leader.leader_party ? ` • ${leader.leader_party}` : ''}`;
        }

        set('modalLeaderId', leader.id ?? '');
        set('modalLeaderRegion', leader.region_name ?? '');
        set('modalLeaderMunicipality', leader.municipality ?? '');
        set('modalLeaderName', leader.leader_display_name ?? leader.leader_name ?? '');
        set('modalLeaderCargo', leader.leader_cargo ?? '');
        set('modalLeaderParty', leader.leader_party ?? '');
        set('modalLeaderVotes', leader.leader_votes_2024 ?? '0');
        set('modalLeaderMargin', leader.margin_percent ?? '0');
        set('modalLeaderTransfer', leader.transfer_rate ?? '40');
        set('modalLeaderVisibility', leader.visibility_score ?? '50');
        set('modalLeaderInvestment', leader.investment_score ?? '50');
        set('modalLeaderSizeClass', leader.size_class ?? 'medium');
        set('modalLeaderNotes', leader.notes ?? '');
        set('modalLeaderDeleteId', leader.id ?? '');

        const aligned = document.getElementById('modalLeaderAligned');
        if (aligned) {
            aligned.checked = Boolean(Number(leader.aligned_with_executive || 0));
        }

        syncLeaderRegionFromMunicipality(document.getElementById('modalLeaderMunicipality'), 'modalLeaderRegion');
        updateLeaderModalSummary(leader);

        leaderModal.hidden = false;
        leaderModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeLeaderModal(updateBody = true) {
        if (!leaderModal) {
            return;
        }

        leaderModal.hidden = true;
        leaderModal.setAttribute('aria-hidden', 'true');
        if (updateBody) {
            document.body.classList.remove('modal-open');
        }
    }

    function toggleCollapsiblePanel(targetId, triggerButton) {
        const target = document.getElementById(targetId);
        if (!target) {
            return;
        }

        const shouldOpen = target.hidden;
        target.hidden = !shouldOpen;

        const buttons = document.querySelectorAll(`[data-toggle-target="${targetId}"]`);
        buttons.forEach((button) => {
            button.textContent = shouldOpen ? 'Recolher' : 'Abrir';
            button.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        });

        if (shouldOpen) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    async function searchLeaders() {
        if (!resultsBody) {
            return;
        }

        const cargoValue = getLeaderSearchCargo();
        if (leaderBatchCargo) {
            leaderBatchCargo.value = cargoValue;
        }

        resultsBody.innerHTML = '<tr><td colspan="7" class="muted">Buscando...</td></tr>';
        resetLeaderBatchSelectionState();

        const params = new URLSearchParams({
            action: 'search_leaders',
            cargo: cargoValue,
            municipio: document.getElementById('searchMunicipality')?.value || '',
            query: document.getElementById('searchQuery')?.value || '',
            turno: document.getElementById('searchTurno')?.value || '1',
        });

        try {
            const response = await fetch('api_premium.php?' + params.toString(), { cache: 'no-store' });
            const data = await response.json();
            const rows = data.results || [];

            if (!rows.length) {
                resultsBody.innerHTML = '<tr><td colspan="7" class="muted">Nenhuma liderança encontrada.</td></tr>';
                resetLeaderBatchSelectionState();
                return;
            }

            resultsBody.innerHTML = rows.map((row) => `
                <tr class="leader-search-row">
                    <td>
                        <input
                            type="checkbox"
                            class="leader-batch-checkbox"
                            aria-label="Selecionar liderança"
                            data-region-name="${escapeHtml(row.region_name || '')}"
                            data-municipality="${escapeHtml(row.nm_municipio || '')}"
                            data-leader-display-name="${escapeHtml(row.leader_display_name || row.nm_urna_candidato || row.nm_votavel || '')}"
                            data-leader-name="${escapeHtml(row.nm_candidato || row.nm_votavel || '')}"
                            data-cargo="${escapeHtml(cargoValue)}"
                            data-party="${escapeHtml(row.sg_partido || '')}"
                            data-votes="${escapeHtml(row.total_votos || 0)}"
                            data-margin="${escapeHtml(row.margin_percent || 0)}"
                            data-size-class="${escapeHtml(row.size_class || 'medium')}"
                            data-sq="${escapeHtml(row.sq_candidato || '')}"
                            data-nr-votavel="${escapeHtml(row.nr_votavel || '')}"
                            data-turno="${escapeHtml(row.turno || 1)}"
                        >
                    </td>
                    <td>${escapeHtml(row.nm_municipio)}</td>
                    <td>${escapeHtml(row.leader_display_name || row.nm_urna_candidato || row.nm_votavel)}</td>
                    <td>${escapeHtml(row.sg_partido || '-')}</td>
                    <td>${formatNumber(row.total_votos)}</td>
                    <td>${formatNumber(row.margin_percent)}%</td>
                    <td>
                        <button
                            type="button"
                            class="btn ghost"
                            data-region-name="${escapeHtml(row.region_name || '')}"
                            data-municipality="${escapeHtml(row.nm_municipio || '')}"
                            data-leader-display-name="${escapeHtml(row.leader_display_name || row.nm_urna_candidato || row.nm_votavel || '')}"
                            data-leader-name="${escapeHtml(row.nm_candidato || row.nm_votavel || '')}"
                            data-cargo="${escapeHtml(cargoValue)}"
                            data-party="${escapeHtml(row.sg_partido || '')}"
                            data-votes="${escapeHtml(row.total_votos || 0)}"
                            data-margin="${escapeHtml(row.margin_percent || 0)}"
                            data-size-class="${escapeHtml(row.size_class || 'medium')}"
                            data-sq="${escapeHtml(row.sq_candidato || '')}"
                            data-nr-votavel="${escapeHtml(row.nr_votavel || '')}"
                            data-turno="${escapeHtml(row.turno || 1)}"
                        >Usar</button>
                    </td>
                </tr>
            `).join('');
            updateLeaderBatchSelectionState();
        } catch (error) {
            console.error(error);
            resultsBody.innerHTML = '<tr><td colspan="7" class="muted">Falha ao buscar lideranças.</td></tr>';
            resetLeaderBatchSelectionState();
        }
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', searchLeaders);
    }

    if (resultsBody) {
        resultsBody.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-leader-name]');
            if (!button) {
                return;
            }

            fillLeaderFormFromResult(button.dataset);
        });

        resultsBody.addEventListener('change', (event) => {
            if (!event.target.classList.contains('leader-batch-checkbox')) {
                return;
            }

            updateLeaderBatchSelectionState();
        });
    }

    const leaderMunicipality = document.getElementById('leaderMunicipality');
    if (leaderMunicipality) {
        leaderMunicipality.addEventListener('change', () => syncLeaderRegionFromMunicipality(leaderMunicipality));
        syncLeaderRegionFromMunicipality(leaderMunicipality);
    }

    if (leaderSelectAll) {
        leaderSelectAll.addEventListener('change', () => {
            setLeaderBatchSelection(Boolean(leaderSelectAll.checked));
        });
    }

    if (leaderBatchSelectAllBtn) {
        leaderBatchSelectAllBtn.addEventListener('click', () => {
            setLeaderBatchSelection(true);
        });
    }

    if (leaderBatchClearBtn) {
        leaderBatchClearBtn.addEventListener('click', () => {
            setLeaderBatchSelection(false);
        });
    }

    if (leaderBatchForm) {
        leaderBatchForm.addEventListener('submit', (event) => {
            const payload = buildLeaderBatchPayload();
            if (!payload.length) {
                event.preventDefault();
                alert('Selecione pelo menos uma liderança antes de adicionar ao escritório.');
                return;
            }

            if (leaderBatchPayload) {
                leaderBatchPayload.value = JSON.stringify(payload);
            }

            const countLabel = payload.length === 1 ? '1 liderança' : `${payload.length} lideranças`;
            const confirmed = window.confirm(`Adicionar ${countLabel} usando os pesos padrão da campanha?`);
            if (!confirmed) {
                event.preventDefault();
                return;
            }
        });
    }

    if (agendaFilterButtons.length) {
        agendaFilterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                renderAgendaPreview(button.dataset.agendaFilter || 'pending');
            });
        });
    }

    if (agendaPreviewArea) {
        renderAgendaPreview(agendaFilter);
    }

    document.addEventListener('click', (event) => {
        const toggleButton = event.target.closest('[data-toggle-target]');
        if (toggleButton) {
            toggleCollapsiblePanel(toggleButton.dataset.toggleTarget || '', toggleButton);
            return;
        }

        const scopeButton = event.target.closest('.scope-open-btn');
        if (scopeButton) {
            openScopeModal(scopeButton.dataset.scopeType || 'city', scopeButton.dataset.scopeName || '');
            return;
        }

        const cityComparisonOpenButton = event.target.closest('[data-city-comparison-open]');
        if (cityComparisonOpenButton) {
            openCityComparisonModal();
            return;
        }

        const cityComparisonPrintButton = event.target.closest('[data-city-comparison-print]');
        if (cityComparisonPrintButton) {
            openCityComparisonReport();
            return;
        }

        const cityComparisonFilterButton = event.target.closest('[data-city-comparison-filter]');
        if (cityComparisonFilterButton) {
            applyCityComparisonFilter(cityComparisonFilterButton.dataset.cityComparisonFilter || 'all');
            return;
        }

        const agendaListButton = event.target.closest('[data-agenda-list-open]');
        if (agendaListButton) {
            openAgendaListModal();
            return;
        }

        const agendaButton = event.target.closest('[data-agenda-id]');
        if (agendaButton) {
            openAgendaModal(agendaButton.dataset.agendaId);
            return;
        }

        const leaderButton = event.target.closest('button[data-leader-id]');
        if (leaderButton) {
            openLeaderModal(leaderButton.dataset.leaderId);
            return;
        }

        if (event.target.closest('[data-modal-close]')) {
            closeAllModals();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (
            event.key === 'Escape' &&
            (
                (leaderModal && !leaderModal.hidden) ||
                (scopeModal && !scopeModal.hidden) ||
                (cityComparisonModal && !cityComparisonModal.hidden) ||
                (agendaModal && !agendaModal.hidden) ||
                (agendaListModal && !agendaListModal.hidden)
            )
        ) {
            closeAllModals();
        }
    });
</script>
</body>
</html>
