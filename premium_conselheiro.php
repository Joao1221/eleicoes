<?php

declare(strict_types=1);

require_once __DIR__ . '/premium_advisor_helpers.php';

premium_ensure_campaign_photo_column($conn);

function advisor_fmt_int(int $value): string
{
    return number_format($value, 0, ',', '.');
}

function advisor_fmt_percent(float $value, int $decimals = 1): string
{
    return number_format($value, $decimals, ',', '.') . '%';
}

function advisor_filter_key(string $value): string
{
    $normalized = strtolower(premium_normalize_text($value));
    $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';
    return trim($normalized, '-') ?: 'monitorar';
}

function advisor_render_ranking_print_report(array $rows, string $campaignTitle, string $baselineLabel, string $filterLabel): void
{
    $generatedAt = date('d/m/Y H:i');
    $baselineTotal = 0;
    $projectionTotal = 0;
    $leaderTotal = 0;
    $scoreTotal = 0.0;
    $rentabilityTotal = 0.0;
    $recommendationCounts = [];

    foreach ($rows as $city) {
        $recommendation = (array) ($city['recommendation'] ?? []);
        $recommendationTitle = (string) ($recommendation['title'] ?? 'Monitorar');
        $baselineTotal += (int) ($city['baseline_votes'] ?? 0);
        $projectionTotal += (int) ($city['projected_base'] ?? 0);
        $leaderTotal += (int) ($city['leader_count'] ?? 0);
        $scoreTotal += (float) ($city['advisor_score'] ?? 0);
        $rentabilityTotal += (float) ($city['rentability_score'] ?? 0);
        $recommendationCounts[$recommendationTitle] = ($recommendationCounts[$recommendationTitle] ?? 0) + 1;
    }

    $rowCount = count($rows);
    $averageScore = $rowCount > 0 ? $scoreTotal / $rowCount : 0;
    $averageRentability = $rowCount > 0 ? $rentabilityTotal / $rowCount : 0;
    $recommendationSummary = [];
    foreach ($recommendationCounts as $label => $count) {
        $recommendationSummary[] = $label . ': ' . advisor_fmt_int((int) $count);
    }
    $recommendationSummaryText = $recommendationSummary ? implode(' | ', $recommendationSummary) : 'Sem cidades no filtro';
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório do Conselheiro | <?= premium_escape_html($campaignTitle) ?></title>
    <style>
        :root { --bg:#f6f8fb; --paper:#fff; --text:#0f172a; --muted:#475569; --line:rgba(15,23,42,.10); --accent:#0f766e; --accent-2:#0284c7; --shadow:0 24px 80px rgba(15,23,42,.10); }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bg); color:var(--text); font-family:Inter, Arial, sans-serif; }
        .report-shell { max-width:1280px; margin:0 auto; padding:28px 24px 36px; }
        .report-hero { background:linear-gradient(135deg,#fff,#eef7f5); border:1px solid var(--line); border-radius:24px; padding:24px 26px; box-shadow:var(--shadow); margin-bottom:18px; }
        .report-hero__top { display:flex; justify-content:space-between; gap:16px; align-items:flex-start; flex-wrap:wrap; }
        .report-brand { display:inline-flex; padding:9px 14px; border-radius:999px; background:rgba(15,118,110,.10); color:var(--accent); font-size:.78rem; font-weight:900; text-transform:uppercase; letter-spacing:.08em; }
        h1 { margin:12px 0 8px; font-size:clamp(1.8rem,3vw,2.8rem); line-height:1.02; }
        p { margin:0; color:var(--muted); line-height:1.6; }
        .report-meta, .report-actions, .report-legend { display:flex; gap:8px; flex-wrap:wrap; }
        .report-meta { margin-top:16px; }
        .report-pill, .report-legend__item { display:inline-flex; align-items:center; padding:7px 12px; border-radius:999px; border:1px solid var(--line); background:rgba(255,255,255,.72); font-size:.8rem; font-weight:800; }
        .report-action { appearance:none; border:1px solid transparent; border-radius:14px; padding:12px 18px; font:inherit; font-weight:900; text-transform:uppercase; cursor:pointer; }
        .report-action--primary { background:linear-gradient(135deg,#0f766e,#0284c7); color:#f8fffb; }
        .report-action--ghost { background:rgba(15,23,42,.04); color:var(--text); border-color:rgba(15,23,42,.12); }
        .report-summary { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:12px; margin:18px 0; }
        .report-card { background:var(--paper); border:1px solid var(--line); border-radius:18px; padding:16px; box-shadow:0 12px 34px rgba(15,23,42,.05); }
        .report-card__label { color:var(--muted); text-transform:uppercase; letter-spacing:.08em; font-size:.68rem; font-weight:800; }
        .report-card__value { margin-top:12px; font-size:1.42rem; font-weight:900; }
        .report-card__sub { margin-top:5px; color:var(--muted); font-size:.76rem; line-height:1.35; }
        .report-legend { margin-bottom:14px; }
        .report-table-wrap { background:var(--paper); border:1px solid var(--line); border-radius:20px; overflow:hidden; box-shadow:var(--shadow); }
        .report-table { width:100%; border-collapse:collapse; table-layout:fixed; font-size:.72rem; }
        .report-table th, .report-table td { padding:8px; border-bottom:1px solid rgba(15,23,42,.08); vertical-align:top; overflow-wrap:anywhere; line-height:1.2; }
        .report-table thead th { background:linear-gradient(180deg,#f8fafc,#eef2f7); text-transform:uppercase; letter-spacing:.08em; font-size:.64rem; color:var(--muted); }
        .report-table tbody tr:nth-child(even) { background:rgba(15,23,42,.025); }
        .report-table td:nth-child(8) span { display:block; margin-top:4px; color:var(--muted); }
        .report-empty { padding:22px; color:var(--muted); text-align:center; }
        .report-footer { margin-top:14px; color:var(--muted); font-size:.78rem; line-height:1.5; }
        @page { size:A4 landscape; margin:8mm; }
        @media print {
            body { background:#fff; font-size:11px; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .report-shell { max-width:none; padding:0; }
            .report-actions { display:none !important; }
            .report-hero { padding:14px 16px; border-radius:18px; margin-bottom:10px; box-shadow:none; }
            h1 { font-size:1.45rem; margin:8px 0 6px; }
            .report-summary { grid-template-columns:repeat(5,minmax(0,1fr)); gap:6px; margin:10px 0; }
            .report-card { padding:8px; border-radius:12px; box-shadow:none; }
            .report-card__label { font-size:.54rem; }
            .report-card__value { margin-top:4px; font-size:.92rem; }
            .report-card__sub { font-size:.56rem; }
            .report-pill, .report-legend__item { padding:5px 8px; font-size:.64rem; }
            .report-table-wrap { box-shadow:none; }
            .report-table { font-size:.62rem; }
            .report-table th, .report-table td { padding:5px 6px; }
            .report-table thead { display:table-header-group; }
            .report-table tr { page-break-inside:avoid; }
        }
    </style>
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                try { window.print(); } catch (error) {}
            }, 350);
        });
    </script>
</head>
<body>
    <div class="report-shell">
        <section class="report-hero">
            <div class="report-hero__top">
                <div>
                    <div class="report-brand">Apoia Candidato Premium</div>
                    <h1>Relatório do Conselheiro de Campanha</h1>
                    <p><?= premium_escape_html($campaignTitle) ?></p>
                    <div class="report-meta">
                        <span class="report-pill">Filtro: <?= premium_escape_html($filterLabel) ?></span>
                        <span class="report-pill">Base histórica: <?= premium_escape_html($baselineLabel) ?></span>
                        <span class="report-pill">Gerado em: <?= premium_escape_html($generatedAt) ?></span>
                    </div>
                </div>
                <div class="report-actions">
                    <button class="report-action report-action--primary" type="button" onclick="window.print()">Imprimir</button>
                    <button class="report-action report-action--ghost" type="button" onclick="window.close()">Fechar</button>
                </div>
            </div>
        </section>
        <section class="report-summary">
            <div class="report-card"><div class="report-card__label">Cidades</div><div class="report-card__value"><?= advisor_fmt_int($rowCount) ?></div><div class="report-card__sub">Municípios no recorte filtrado</div></div>
            <div class="report-card"><div class="report-card__label">Score médio</div><div class="report-card__value"><?= advisor_fmt_percent($averageScore) ?></div><div class="report-card__sub">Prioridade estratégica média</div></div>
            <div class="report-card"><div class="report-card__label">Rentabilidade média</div><div class="report-card__value"><?= advisor_fmt_percent($averageRentability) ?></div><div class="report-card__sub">Retorno estimado por esforço</div></div>
            <div class="report-card"><div class="report-card__label">Votos <?= premium_escape_html($baselineLabel) ?></div><div class="report-card__value"><?= advisor_fmt_int($baselineTotal) ?></div><div class="report-card__sub">Base histórica do recorte</div></div>
            <div class="report-card"><div class="report-card__label">Projeção</div><div class="report-card__value"><?= advisor_fmt_int($projectionTotal) ?></div><div class="report-card__sub">Projeção atual somada</div></div>
        </section>
        <div class="report-legend">
            <span class="report-legend__item"><?= premium_escape_html($recommendationSummaryText) ?></span>
            <span class="report-legend__item">Lideranças somadas no recorte: <?= advisor_fmt_int($leaderTotal) ?></span>
        </div>
        <section class="report-table-wrap">
            <table class="report-table">
                <colgroup>
                    <col style="width:14%;">
                    <col style="width:10%;">
                    <col style="width:7%;">
                    <col style="width:7%;">
                    <col style="width:7%;">
                    <col style="width:7%;">
                    <col style="width:5%;">
                    <col style="width:43%;">
                </colgroup>
                <thead>
                    <tr>
                        <th>Cidade</th>
                        <th>Região</th>
                        <th>Score</th>
                        <th>Rentabilidade</th>
                        <th><?= premium_escape_html($baselineLabel) ?></th>
                        <th>Projeção</th>
                        <th>Lideranças</th>
                        <th>Recomendação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="8" class="report-empty">Nenhuma cidade corresponde aos filtros selecionados.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $city): ?>
                        <?php $recommendation = (array) ($city['recommendation'] ?? []); ?>
                        <tr>
                            <td><?= premium_escape_html((string) ($city['municipio'] ?? '')) ?></td>
                            <td><?= premium_escape_html((string) ($city['regiao'] ?? '')) ?></td>
                            <td><?= advisor_fmt_percent((float) ($city['advisor_score'] ?? 0)) ?></td>
                            <td><?= advisor_fmt_percent((float) ($city['rentability_score'] ?? 0)) ?></td>
                            <td><?= advisor_fmt_int((int) ($city['baseline_votes'] ?? 0)) ?></td>
                            <td><?= advisor_fmt_int((int) ($city['projected_base'] ?? 0)) ?></td>
                            <td><?= advisor_fmt_int((int) ($city['leader_count'] ?? 0)) ?></td>
                            <td><strong><?= premium_escape_html((string) ($recommendation['title'] ?? 'Monitorar')) ?></strong><span><?= premium_escape_html((string) ($recommendation['text'] ?? '')) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <div class="report-footer">
            Relatório elaborado a partir do Conselheiro de Campanha. Use os filtros da tela para imprimir apenas as recomendações selecionadas.
        </div>
    </div>
</body>
</html>
    <?php
}

$user = premium_require_user($conn);
$isAdmin = premium_is_admin_user($user);
$csrf = premium_csrf_token();
$flash = premium_pull_flash();

if (isset($_GET['campaign_id'])) {
    $requestedCampaignId = (int) $_GET['campaign_id'];
    if ($requestedCampaignId > 0 && premium_get_campaign($conn, $requestedCampaignId, (int) $user['id'], $isAdmin)) {
        premium_set_active_campaign($requestedCampaignId);
    }
}

$campaigns = premium_get_campaigns($conn, (int) $user['id']);
$campaign = premium_active_campaign($conn, (int) $user['id'], $isAdmin);
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
$forecast = [
    'settings' => premium_default_settings(),
    'totals' => [],
    'regions' => [],
    'cities' => [],
    'leaders' => [],
];
$advisor = null;

if ($campaign) {
    premium_set_active_campaign((int) $campaign['id']);
    $settings = premium_load_campaign_settings($conn, (int) $campaign['id']);
    $baseline = premium_candidate_baseline($conn, (string) ($campaign['candidate_name'] ?? ''), (string) ($campaign['candidate_cargo'] ?? ''));
    $leaders = premium_get_campaign_leaders($conn, (int) $campaign['id']);
    $forecast = premium_build_forecast($baseline, $leaders, $settings);
    $advisor = premium_build_campaign_advisor($campaign, $baseline, $leaders, $forecast, $settings);
}

$campaignTitle = $campaign
    ? trim((string) ($campaign['campaign_name'] ?? 'Campanha') . ' - ' . (string) ($campaign['candidate_name'] ?? ''))
    : 'Nenhuma campanha ativa';
$premiumSupportWhatsappUrl = premium_vip_support_whatsapp_url($user, $campaign);
$advisorCityRows = $advisor ? array_values((array) ($advisor['cities'] ?? [])) : [];
$advisorRecommendationCounts = [];
foreach ($advisorCityRows as $city) {
    $recommendation = (array) ($city['recommendation'] ?? []);
    $recommendationTitle = (string) ($recommendation['title'] ?? 'Monitorar');
    $filterKey = advisor_filter_key($recommendationTitle);
    if (!isset($advisorRecommendationCounts[$filterKey])) {
        $advisorRecommendationCounts[$filterKey] = [
            'label' => $recommendationTitle,
            'count' => 0,
        ];
    }
    $advisorRecommendationCounts[$filterKey]['count']++;
}
$advisorRecommendationOrder = [
    'consolidar-base',
    'defender-base',
    'base-em-risco',
    'buraco-eleitoral',
    'prioridade-alta',
    'alta-rentabilidade',
    'oportunidade-nova',
    'expandir-territorio',
    'monitorar',
];
$advisorPrimaryRecommendationLabels = [
    'consolidar-base' => 'Consolidar base',
    'defender-base' => 'Defender base',
    'base-em-risco' => 'Base em risco',
    'buraco-eleitoral' => 'Buraco eleitoral',
];
foreach ($advisorPrimaryRecommendationLabels as $filterKey => $label) {
    if (!isset($advisorRecommendationCounts[$filterKey])) {
        $advisorRecommendationCounts[$filterKey] = [
            'label' => $label,
            'count' => 0,
        ];
    }
}
uksort($advisorRecommendationCounts, static function (string $left, string $right) use ($advisorRecommendationOrder): int {
    $leftIndex = array_search($left, $advisorRecommendationOrder, true);
    $rightIndex = array_search($right, $advisorRecommendationOrder, true);
    $leftIndex = $leftIndex === false ? 999 : $leftIndex;
    $rightIndex = $rightIndex === false ? 999 : $rightIndex;

    return $leftIndex <=> $rightIndex ?: strcmp($left, $right);
});

if ($advisor && (string) ($_GET['print'] ?? '') === 'advisor-ranking') {
    $filterParam = trim((string) ($_GET['filter'] ?? ''));
    $filterKeys = array_values(array_filter(array_map('trim', explode(',', $filterParam))));
    $filterKeySet = array_fill_keys($filterKeys, true);
    $reportRows = $filterKeys
        ? array_values(array_filter($advisorCityRows, static function (array $city) use ($filterKeySet): bool {
            $recommendation = (array) ($city['recommendation'] ?? []);
            $recommendationTitle = (string) ($recommendation['title'] ?? 'Monitorar');
            return isset($filterKeySet[advisor_filter_key($recommendationTitle)]);
        }))
        : $advisorCityRows;

    $filterLabel = 'Todas as recomendações';
    if ($filterKeys) {
        $filterLabels = [];
        foreach ($filterKeys as $filterKey) {
            if (isset($advisorRecommendationCounts[$filterKey])) {
                $filterLabels[] = (string) ($advisorRecommendationCounts[$filterKey]['label'] ?? $filterKey);
            }
        }
        $filterLabel = $filterLabels ? implode(' + ', $filterLabels) : 'Filtro personalizado';
    }

    advisor_render_ranking_print_report(
        $reportRows,
        $campaignTitle,
        premium_baseline_label((int) ($campaign['baseline_year'] ?? 2022)),
        $filterLabel
    );
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <script src="assets/js/premium-bootstrap.js"></script>
    <title>Conselheiro de Campanha | Apoia Candidato</title>
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
            <h1>Conselheiro de campanha</h1>
            <p class="muted">Ranking de cidades, lideranças, alertas territoriais e recomendações práticas para orientar a campanha.</p>
        </div>
        <div class="topbar-right">
            <div class="topbar-actions">
                <div class="theme-switch" role="group" aria-label="Escolher tema">
                    <button type="button" class="theme-switch__btn" data-theme-toggle="light" aria-label="Modo claro" title="Modo claro">&#9728;</button>
                    <button type="button" class="theme-switch__btn" data-theme-toggle="dark" aria-label="Modo escuro" title="Modo escuro">&#9790;</button>
                </div>
            </div>
            <div class="topbar-actions">
                <div class="pill">Olá, <?= premium_escape_html((string) ($user['name'] ?? '')) ?></div>
                <a class="btn comparison-cta" href="premium">Voltar ao painel</a>
                <a class="btn ghost" href="premium_logout.php">Sair</a>
            </div>
            <?php if ($premiumSupportWhatsappUrl !== ''): ?>
                <div class="vip-support">
                    <span>Atendimento VIP para clientes premium</span>
                    <a class="btn vip-support__btn" href="<?= premium_escape_html($premiumSupportWhatsappUrl) ?>" target="_blank" rel="noopener">
                        Pedir ajuda no WhatsApp
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="flash <?= premium_escape_html((string) ($flash['type'] ?? '')) ?>">
            <?= premium_escape_html((string) ($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <?php if (!$campaign || !$advisor): ?>
        <section class="panel">
            <div class="eyebrow">Sem campanha</div>
            <h2 style="margin-top:12px;">Crie ou selecione uma campanha para usar o Conselheiro.</h2>
            <p class="muted" style="margin-top:12px;">O Conselheiro precisa de baseline, lideranças e projeções para gerar recomendações.</p>
            <div class="action-row">
                <a class="btn primary" href="premium">Voltar ao Premium</a>
            </div>
        </section>
    <?php else: ?>
        <section class="panel hero hero--single advisor-hero strategy-hero">
            <div class="copy">
                <div class="eyebrow">Campanha ativa</div>
                <h2 style="font-size:2rem; margin-top:12px;"><?= premium_escape_html($campaignTitle) ?></h2>
                <p class="muted" style="margin-top:12px;">
                    O Conselheiro cruza votos de 2022, lideranças de 2024, projeção de transferência e esforço territorial estimado para indicar onde a campanha deve agir primeiro.
                </p>
                <div class="pill-row">
                    <span class="pill">Cidades analisadas: <?= advisor_fmt_int(count((array) ($advisor['cities'] ?? []))) ?></span>
                    <span class="pill">Lideranças: <?= advisor_fmt_int(count($leaders)) ?></span>
                    <span class="pill">Projeção base: <?= advisor_fmt_int((int) ($forecast['totals']['projected_base'] ?? 0)) ?></span>
                </div>
            </div>
            <figure class="strategy-hero__media">
                <img src="assets/agente-estrategica.png" alt="Agente de IA analisando dados e recomendações de campanha">
            </figure>
        </section>

        <section class="stats-grid campaign-stats-grid">
            <div class="stat-card">
                <div class="stat-label">Prioridade alta</div>
                <div class="stat-value"><?= advisor_fmt_int((int) ($advisor['summary']['priority_cities'] ?? 0)) ?></div>
                <div class="stat-sub">Cidades que pedem ação imediata</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Bases em risco</div>
                <div class="stat-value"><?= advisor_fmt_int((int) ($advisor['summary']['risk_cities'] ?? 0)) ?></div>
                <div class="stat-sub">Voto histórico sem liderança local</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Alta rentabilidade</div>
                <div class="stat-value"><?= advisor_fmt_int((int) ($advisor['summary']['rentable_cities'] ?? 0)) ?></div>
                <div class="stat-sub">Melhor relação entre voto e esforço</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Oportunidades</div>
                <div class="stat-value"><?= advisor_fmt_int((int) ($advisor['summary']['new_opportunities'] ?? 0)) ?></div>
                <div class="stat-sub">Entrada nova via lideranças de 2024</div>
            </div>
        </section>

        <section class="advisor-alert-grid">
            <?php foreach ((array) ($advisor['alerts'] ?? []) as $alert): ?>
                <article class="advisor-alert advisor-alert--<?= premium_escape_html((string) ($alert['type'] ?? 'info')) ?>">
                    <strong><?= premium_escape_html((string) ($alert['title'] ?? 'Alerta')) ?></strong>
                    <p><?= premium_escape_html((string) ($alert['text'] ?? '')) ?></p>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="advisor-territory-grid">
            <div class="panel advisor-territory-card">
                <div class="eyebrow">Buracos eleitorais</div>
                <h2>Cidades abaixo do potencial</h2>
                <p class="panel-note">Regiões com sinal de força, mas cidades com baixa presença histórica ou baixa sustentação atual.</p>
                <div class="advisor-territory-list">
                    <?php foreach ((array) ($advisor['electoral_holes'] ?? []) as $city): ?>
                        <article>
                            <strong><?= premium_escape_html((string) ($city['municipio'] ?? '')) ?></strong>
                            <span><?= premium_escape_html((string) ($city['regiao'] ?? '')) ?> • score <?= advisor_fmt_percent((float) ($city['hole_score'] ?? 0)) ?></span>
                            <p><?= premium_escape_html((string) (($city['recommendation']['text'] ?? 'Busque liderança local antes de ampliar o investimento.'))) ?></p>
                        </article>
                    <?php endforeach; ?>
                    <?php if (empty($advisor['electoral_holes'])): ?>
                        <div class="empty-state">Nenhum buraco eleitoral relevante foi detectado.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel advisor-territory-card">
                <div class="eyebrow">Bases em risco</div>
                <h2>Defender voto histórico</h2>
                <p class="panel-note">Municípios onde houve votação anterior, mas falta liderança ou a sustentação atual está fraca.</p>
                <div class="advisor-territory-list">
                    <?php foreach ((array) ($advisor['defense_bases'] ?? []) as $city): ?>
                        <article>
                            <strong><?= premium_escape_html((string) ($city['municipio'] ?? '')) ?></strong>
                            <span><?= advisor_fmt_int((int) ($city['baseline_votes'] ?? 0)) ?> votos em 2022 • <?= advisor_fmt_int((int) ($city['leader_count'] ?? 0)) ?> lideranças</span>
                            <p><?= premium_escape_html((string) (($city['recommendation']['text'] ?? 'Reforce articulação local para defender a base.'))) ?></p>
                        </article>
                    <?php endforeach; ?>
                    <?php if (empty($advisor['defense_bases'])): ?>
                        <div class="empty-state">Nenhuma base crítica foi detectada.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel advisor-territory-card">
                <div class="eyebrow">Cidades de expansão</div>
                <h2>Entrar ou crescer</h2>
                <p class="panel-note">Cidades em regiões fortes, com boa rentabilidade ou liderança capaz de abrir território.</p>
                <div class="advisor-territory-list">
                    <?php foreach ((array) ($advisor['expansion_cities'] ?? []) as $city): ?>
                        <article>
                            <strong><?= premium_escape_html((string) ($city['municipio'] ?? '')) ?></strong>
                            <span><?= premium_escape_html((string) ($city['regiao'] ?? '')) ?> • expansão <?= advisor_fmt_percent((float) ($city['expansion_score'] ?? 0)) ?></span>
                            <p><?= premium_escape_html((string) (($city['recommendation']['text'] ?? 'Trabalhe como extensão natural da base regional.'))) ?></p>
                        </article>
                    <?php endforeach; ?>
                    <?php if (empty($advisor['expansion_cities'])): ?>
                        <div class="empty-state">Nenhuma cidade de expansão atingiu o corte atual.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="panel advisor-ranking-panel" id="advisorRankingPanel" data-advisor-ranking data-campaign-title="<?= premium_escape_html($campaignTitle) ?>" data-baseline-year="<?= premium_escape_html(premium_baseline_label((int) ($campaign['baseline_year'] ?? 2022))) ?>">
            <div class="section-title">
                <div>
                    <div class="eyebrow">Cidades</div>
                    <h2>Ranking de prioridade municipal</h2>
                </div>
                <button class="btn comparison-report-btn" type="button" data-advisor-ranking-print>Imprimir relatório</button>
            </div>
            <p class="panel-note">
                O score combina projeção, força das lideranças, oportunidade sobre 2022, porte municipal, força regional e penalidade para cidades sem liderança.
            </p>
            <div class="advisor-ranking-controls">
                <div class="agenda-filter-bar advisor-filter-bar" role="group" aria-label="Filtrar recomendações do ranking">
                    <button class="agenda-filter-btn is-active" type="button" data-advisor-filter-all aria-pressed="true">Todos (<?= advisor_fmt_int(count($advisorCityRows)) ?>)</button>
                    <?php foreach ($advisorRecommendationCounts as $filterKey => $filter): ?>
                        <button class="agenda-filter-btn" type="button" data-advisor-filter="<?= premium_escape_html($filterKey) ?>" aria-pressed="false">
                            <?= premium_escape_html((string) ($filter['label'] ?? 'Monitorar')) ?> (<?= advisor_fmt_int((int) ($filter['count'] ?? 0)) ?>)
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="advisor-ranking-status">
                    <span id="advisorRankingVisibleCount"><?= advisor_fmt_int(count($advisorCityRows)) ?></span> de
                    <span id="advisorRankingTotalCount"><?= advisor_fmt_int(count($advisorCityRows)) ?></span> cidades exibidas
                </div>
            </div>
            <div class="table-wrap advisor-table-wrap">
                <table class="advisor-table">
                    <thead>
                        <tr>
                            <th>Cidade</th>
                            <th>Região</th>
                            <th>Score</th>
                            <th>Rentabilidade</th>
                            <th>Votos 2022</th>
                            <th>Projeção</th>
                            <th>Lideranças</th>
                            <th>Recomendação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($advisorCityRows as $cityIndex => $city): ?>
                            <?php $recommendation = (array) ($city['recommendation'] ?? []); ?>
                            <?php $recommendationTitle = (string) ($recommendation['title'] ?? 'Monitorar'); ?>
                            <tr
                                data-advisor-city-row
                                data-advisor-rank="<?= (int) ($cityIndex + 1) ?>"
                                data-advisor-filter-key="<?= premium_escape_html(advisor_filter_key($recommendationTitle)) ?>"
                                data-advisor-recommendation="<?= premium_escape_html($recommendationTitle) ?>"
                                data-advisor-city="<?= premium_escape_html((string) ($city['municipio'] ?? '')) ?>"
                                data-advisor-region="<?= premium_escape_html((string) ($city['regiao'] ?? '')) ?>"
                                data-advisor-score="<?= premium_escape_html((string) ($city['advisor_score'] ?? 0)) ?>"
                                data-advisor-rentability="<?= premium_escape_html((string) ($city['rentability_score'] ?? 0)) ?>"
                                data-advisor-baseline="<?= (int) ($city['baseline_votes'] ?? 0) ?>"
                                data-advisor-projection="<?= (int) ($city['projected_base'] ?? 0) ?>"
                                data-advisor-leaders="<?= (int) ($city['leader_count'] ?? 0) ?>"
                                data-advisor-text="<?= premium_escape_html((string) ($recommendation['text'] ?? '')) ?>"
                            >
                                <td><strong><?= premium_escape_html((string) ($city['municipio'] ?? '')) ?></strong></td>
                                <td><?= premium_escape_html((string) ($city['regiao'] ?? '')) ?></td>
                                <td><span class="advisor-score"><?= advisor_fmt_percent((float) ($city['advisor_score'] ?? 0)) ?></span></td>
                                <td><?= advisor_fmt_percent((float) ($city['rentability_score'] ?? 0)) ?></td>
                                <td><?= advisor_fmt_int((int) ($city['baseline_votes'] ?? 0)) ?></td>
                                <td><?= advisor_fmt_int((int) ($city['projected_base'] ?? 0)) ?></td>
                                <td><?= advisor_fmt_int((int) ($city['leader_count'] ?? 0)) ?></td>
                                <td>
                                    <strong><?= premium_escape_html((string) ($recommendation['title'] ?? 'Monitorar')) ?></strong>
                                    <div class="advisor-table-note"><?= premium_escape_html((string) ($recommendation['text'] ?? '')) ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="advisorRankingEmptyRow" hidden>
                            <td colspan="8" class="muted">Nenhuma cidade corresponde aos filtros selecionados.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="grid-2 advisor-split">
            <div class="panel">
                <div class="section-title">
                    <div>
                        <div class="eyebrow">Lideranças</div>
                        <h2>Quem mais entrega voto</h2>
                    </div>
                </div>
                <p class="panel-note">Ranking dinâmico ordenado por projeção de votos. Sempre que lideranças, pesos ou transferência mudarem, esta lista é recalculada automaticamente.</p>
                <div class="table-wrap advisor-leaders-table-wrap">
                    <table class="advisor-table advisor-leaders-table">
                        <thead>
                            <tr>
                                <th>Liderança</th>
                                <th>Cidade</th>
                                <th>Votos 2024</th>
                                <th>Projeção</th>
                                <th>Conversão</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ((array) ($advisor['leaders'] ?? []) as $leader): ?>
                                <tr>
                                    <td><strong><?= premium_escape_html((string) ($leader['leader_display_name'] ?? $leader['leader_name'] ?? '')) ?></strong></td>
                                    <td><?= premium_escape_html((string) ($leader['municipality'] ?? '')) ?></td>
                                    <td><?= advisor_fmt_int((int) ($leader['leader_votes_2024'] ?? 0)) ?></td>
                                    <td><?= advisor_fmt_int((int) ($leader['projected_votes'] ?? 0)) ?></td>
                                    <td><?= advisor_fmt_percent((float) ($leader['conversion_percent'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($advisor['leaders'])): ?>
                                <tr>
                                    <td colspan="5" class="muted">Nenhuma liderança cadastrada nesta campanha.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <div class="section-title">
                    <div>
                        <div class="eyebrow">Expansão</div>
                        <h2>Cidades vizinhas-alvo por região</h2>
                    </div>
                </div>
                <p class="panel-note">
                    Primeira versão usa regiões como proxy territorial. Cidades sem liderança em regiões fortes aparecem como alvos para articulação.
                </p>
                <?php if (!empty($advisor['expansion'])): ?>
                    <div class="advisor-list advisor-expansion-list">
                        <?php foreach ((array) ($advisor['expansion'] ?? []) as $item): ?>
                            <article class="advisor-list-item">
                                <strong><?= premium_escape_html((string) ($item['municipio'] ?? '')) ?></strong>
                                <span><?= premium_escape_html((string) ($item['regiao'] ?? '')) ?></span>
                                <p><?= premium_escape_html((string) ($item['reason'] ?? '')) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">Nenhuma oportunidade regional sem liderança foi detectada nesta leitura.</div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
<script src="assets/js/premium.js"></script>
</body>
</html>
