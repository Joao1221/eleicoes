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

$user = premium_require_user($conn);
$csrf = premium_csrf_token();
$flash = premium_pull_flash();

if (isset($_GET['campaign_id'])) {
    $requestedCampaignId = (int) $_GET['campaign_id'];
    if ($requestedCampaignId > 0 && premium_get_campaign($conn, $requestedCampaignId, (int) $user['id'])) {
        premium_set_active_campaign($requestedCampaignId);
    }
}

$campaigns = premium_get_campaigns($conn, (int) $user['id']);
$campaign = premium_active_campaign($conn, (int) $user['id']);
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
        <section class="panel hero hero--single advisor-hero">
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

        <section class="panel">
            <div class="section-title">
                <div>
                    <div class="eyebrow">Cidades</div>
                    <h2>Ranking de prioridade municipal</h2>
                </div>
            </div>
            <p class="panel-note">
                O score combina projeção, força das lideranças, oportunidade sobre 2022, porte municipal, força regional e penalidade para cidades sem liderança.
            </p>
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
                        <?php foreach (array_slice((array) ($advisor['cities'] ?? []), 0, 20) as $city): ?>
                            <?php $recommendation = (array) ($city['recommendation'] ?? []); ?>
                            <tr>
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
                            <?php foreach (array_slice((array) ($advisor['leaders'] ?? []), 0, 12) as $leader): ?>
                                <tr>
                                    <td><strong><?= premium_escape_html((string) ($leader['leader_display_name'] ?? $leader['leader_name'] ?? '')) ?></strong></td>
                                    <td><?= premium_escape_html((string) ($leader['municipality'] ?? '')) ?></td>
                                    <td><?= advisor_fmt_int((int) ($leader['leader_votes_2024'] ?? 0)) ?></td>
                                    <td><?= advisor_fmt_int((int) ($leader['projected_votes'] ?? 0)) ?></td>
                                    <td><?= advisor_fmt_percent((float) ($leader['conversion_percent'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
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
