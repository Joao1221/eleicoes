<?php

declare(strict_types=1);

require_once __DIR__ . '/premium_helpers.php';

$user       = premium_require_user($conn);
$isAdmin    = premium_is_admin_user($user);

if (isset($_GET['campaign_id'])) {
    $requestedCampaignId = (int) $_GET['campaign_id'];
    if ($requestedCampaignId > 0 && premium_get_campaign($conn, $requestedCampaignId, (int) $user['id'], $isAdmin)) {
        premium_set_active_campaign($requestedCampaignId);
    }
}

$campaign = premium_active_campaign($conn, (int) $user['id'], $isAdmin);

if (!$campaign) {
    http_response_code(404);
    exit('Nenhuma campanha ativa.');
}

$settings         = premium_load_campaign_settings($conn, (int) $campaign['id']);
$baselineYear     = premium_resolve_baseline_year((int) ($campaign['baseline_year'] ?? 2022));
$baselineLabel    = premium_baseline_label($baselineYear);
$baseline         = premium_candidate_baseline($conn, (string) ($campaign['candidate_name'] ?? ''), (string) ($campaign['candidate_cargo'] ?? ''), $baselineYear);
$leaders          = premium_get_campaign_leaders($conn, (int) $campaign['id']);
$isSenateCampaign = premium_is_senate_cargo((string) ($campaign['candidate_cargo'] ?? ''));
if ($isSenateCampaign) {
    $senateSources = premium_get_senate_vote_sources($conn, (int) $campaign['id']);
    $forecast      = premium_build_senate_forecast($conn, $campaign, $senateSources, $settings);
} else {
    $forecast = premium_build_forecast($baseline, $leaders, $settings);
}

$candidateName    = (string) ($campaign['candidate_name']  ?? '');
$candidateCargo   = (string) ($campaign['candidate_cargo'] ?? '');
$campaignName     = (string) ($campaign['campaign_name']   ?? '');
$generatedAt      = date('d/m/Y \à\s H:i');

// ── Build rows ───────────────────────────────────────────────────────────────

$cities = (array) ($forecast['cities'] ?? []);
$rows   = [];

foreach ($cities as $city) {
    $baselineVotes  = (int) ($city['baseline_votes'] ?? 0);
    $projectedVotes = (int) ($city['projected_base'] ?? 0);
    $leaderCount    = (int) ($city['leader_count'] ?? $city['source_count'] ?? 0);
    $delta          = $projectedVotes - $baselineVotes;
    $deltaPercent   = $baselineVotes > 0 ? ($delta / $baselineVotes) * 100 : 0;
    $suggestion     = premium_city_suggestion($baselineVotes, $projectedVotes, $leaderCount);

    $rows[] = [
        'municipio'      => (string) ($city['municipio'] ?? ''),
        'regiao'         => (string) ($city['regiao']    ?? ''),
        'baseline_votes' => $baselineVotes,
        'projected'      => $projectedVotes,
        'delta'          => $delta,
        'delta_pct'      => $deltaPercent,
        'leader_count'   => $leaderCount,
        'suggestion'     => $suggestion,
    ];
}

usort($rows, static function (array $a, array $b): int {
    $p = ($a['suggestion']['priority'] ?? 5) <=> ($b['suggestion']['priority'] ?? 5);
    return $p !== 0 ? $p : abs((int) $b['delta']) <=> abs((int) $a['delta']);
});

// ── Summary numbers ──────────────────────────────────────────────────────────

$totalCities  = count($rows);
$countRisk    = $countStable = $countGrowth = 0;
$totalBase    = $totalProj   = 0;

foreach ($rows as $r) {
    $c = $r['suggestion']['class'];
    if (in_array($c, ['positive', 'positive-strong', 'opportunity'], true)) {
        $countGrowth++;
    } elseif ($c === 'neutral') {
        $countStable++;
    } else {
        $countRisk++;
    }
    $totalBase += $r['baseline_votes'];
    $totalProj += $r['projected'];
}

$deltaTotal    = $totalProj - $totalBase;
$deltaTotalPct = $totalBase > 0 ? ($deltaTotal / $totalBase) * 100 : 0;

function rc_fmt(int $v): string { return number_format($v, 0, ',', '.'); }
function rc_pct(float $v, int $d = 1): string { return number_format(abs($v), $d, ',', '.') . '%'; }

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= premium_render_pwa_tags() ?>
    <title>Comparativo por cidade <?= (int) $baselineYear ?> × 2026 | <?= premium_escape_html($campaignName) ?></title>
    <style>
        :root {
            --bg:     #f0f4f8;
            --paper:  #ffffff;
            --text:   #0f172a;
            --muted:  #475569;
            --line:   rgba(15,23,42,.10);
            --accent: #0f766e;
            --blue:   #0284c7;
            --warn:   #b45309;
            --danger: #dc2626;
            --shadow: 0 20px 60px rgba(15,23,42,.09);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--bg);
            color: var(--text);
            font-family: Inter, 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            line-height: 1.5;
        }

        /* ── Shell ─────────────────────────────────── */
        .rp-shell { max-width: 1280px; margin: 0 auto; padding: 22px 20px 32px; }

        /* ── Hero ──────────────────────────────────── */
        .rp-hero {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 20px 24px 18px;
            box-shadow: var(--shadow);
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            flex-wrap: wrap;
        }
        .rp-brand {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 12px;
            border-radius: 999px;
            background: rgba(15,118,110,.10);
            color: var(--accent);
            font-size: .70rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .09em;
            margin-bottom: 10px;
        }
        .rp-brand::before {
            content: '';
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--accent);
        }
        .rp-hero h1 {
            font-size: clamp(1.45rem, 2.6vw, 2.2rem);
            font-weight: 900;
            line-height: 1.08;
            margin-bottom: 4px;
            letter-spacing: -.02em;
        }
        .rp-hero__sub {
            color: var(--muted);
            font-size: .82rem;
            margin-bottom: 12px;
        }
        .rp-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .rp-pill {
            display: inline-flex;
            align-items: center;
            padding: 5px 11px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,.72);
            font-size: .74rem;
            font-weight: 700;
            color: var(--muted);
        }
        .rp-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }
        .rp-btn {
            appearance: none;
            border: 1px solid transparent;
            border-radius: 12px;
            padding: 10px 18px;
            font: inherit;
            font-size: .80rem;
            font-weight: 800;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .rp-btn--primary {
            background: linear-gradient(135deg, var(--accent), var(--blue));
            color: #f0fdf4;
        }
        .rp-btn--ghost {
            background: rgba(15,23,42,.04);
            color: var(--text);
            border-color: rgba(15,23,42,.12);
        }

        /* ── Summary cards ─────────────────────────── */
        .rp-summary {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }
        .rp-card {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 13px 14px;
            box-shadow: 0 8px 24px rgba(15,23,42,.05);
        }
        .rp-card__label {
            font-size: .62rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .09em;
            color: var(--muted);
        }
        .rp-card__value {
            font-size: 1.30rem;
            font-weight: 900;
            margin-top: 8px;
            letter-spacing: -.01em;
        }
        .rp-card__value--risk    { color: var(--danger); }
        .rp-card__value--warn    { color: var(--warn); }
        .rp-card__value--growth  { color: var(--accent); }
        .rp-card__value--delta-pos { color: var(--accent); }
        .rp-card__value--delta-neg { color: var(--danger); }
        .rp-card__sub {
            font-size: .68rem;
            color: var(--muted);
            margin-top: 3px;
            line-height: 1.3;
        }

        /* ── Table ─────────────────────────────────── */
        .rp-table-wrap {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        .rp-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .74rem;
        }
        .rp-table thead th {
            background: linear-gradient(180deg, #f8fafc, #eef2f7);
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .62rem;
            font-weight: 800;
            color: var(--muted);
            padding: 10px 9px;
            border-bottom: 1px solid rgba(15,23,42,.08);
            text-align: left;
            white-space: nowrap;
        }
        .rp-table tbody td {
            padding: 7px 9px;
            border-bottom: 1px solid rgba(15,23,42,.06);
            vertical-align: middle;
            line-height: 1.3;
        }
        .rp-table tbody tr:nth-child(even) { background: rgba(15,23,42,.018); }
        .rp-table tbody tr:last-child td   { border-bottom: none; }
        .rp-num { font-variant-numeric: tabular-nums; text-align: right; }

        /* Row-level coloring by priority */
        .rp-row--danger  { background: rgba(239,68,68,.035)  !important; }
        .rp-row--warning { background: rgba(245,158,11,.035) !important; }

        /* Delta cell */
        .rp-delta { font-variant-numeric: tabular-nums; white-space: nowrap; }
        .rp-delta__main { font-weight: 800; display: block; }
        .rp-delta__pct  { font-size: .65rem; color: var(--muted); }
        .rp-delta--pos .rp-delta__main { color: var(--accent); }
        .rp-delta--neg .rp-delta__main { color: var(--danger); }
        .rp-delta--warn .rp-delta__main { color: var(--warn); }

        /* Badge */
        .rp-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: .62rem;
            font-weight: 800;
            white-space: nowrap;
            letter-spacing: .01em;
        }
        .rp-badge--positive-strong, .rp-badge--positive { background: rgba(16,185,129,.12); color: #0f766e; }
        .rp-badge--neutral    { background: rgba(100,116,139,.10); color: #475569; }
        .rp-badge--warning    { background: rgba(245,158,11,.13);  color: #b45309; }
        .rp-badge--danger     { background: rgba(239,68,68,.12);   color: #dc2626; }
        .rp-badge--opportunity{ background: rgba(14,165,233,.12);  color: #0369a1; }

        /* Suggestion text */
        .rp-tip { font-size: .68rem; color: var(--muted); line-height: 1.4; }

        /* Footer */
        .rp-footer {
            margin-top: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--muted);
            font-size: .72rem;
            border-top: 1px solid var(--line);
            padding-top: 10px;
        }
        .rp-footer__brand {
            font-weight: 900;
            font-size: .76rem;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        /* ── Print ─────────────────────────────────── */
        @page { size: A4 landscape; margin: 8mm 9mm; }

        @media print {
            body {
                background: #fff;
                font-size: 10px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .rp-shell    { max-width: none; padding: 0; }
            .rp-actions  { display: none !important; }
            .rp-hero {
                padding: 12px 14px;
                border-radius: 12px;
                margin-bottom: 8px;
                box-shadow: none;
            }
            .rp-hero h1     { font-size: 1.30rem; margin-bottom: 2px; }
            .rp-hero__sub   { font-size: .72rem; margin-bottom: 8px; }
            .rp-pill        { padding: 3px 8px; font-size: .60rem; }
            .rp-summary     { gap: 6px; margin-bottom: 8px; }
            .rp-card        { padding: 8px 10px; border-radius: 10px; box-shadow: none; }
            .rp-card__label { font-size: .52rem; }
            .rp-card__value { font-size: 1.05rem; margin-top: 5px; }
            .rp-card__sub   { font-size: .54rem; }
            .rp-table-wrap  { box-shadow: none; border-radius: 10px; }
            .rp-table       { font-size: .62rem; }
            .rp-table thead th { font-size: .54rem; padding: 6px 7px; }
            .rp-table tbody td { padding: 4px 7px; }
            .rp-table thead { display: table-header-group; }
            .rp-table tr    { page-break-inside: avoid; }
            .rp-tip         { font-size: .58rem; }
            .rp-badge       { font-size: .54rem; padding: 1px 6px; }
            .rp-footer      { margin-top: 8px; padding-top: 7px; font-size: .60rem; }
        }
    </style>
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { try { window.print(); } catch (e) {} }, 380);
        });
    </script>
</head>
<body>
<div class="rp-shell">

    <!-- ── Hero ──────────────────────────────────────────────────────── -->
    <section class="rp-hero">
        <div>
            <div class="rp-brand">ApoiaCandidato Premium</div>
            <h1>Comparativo por cidade — <?= (int) $baselineYear ?> × 2026</h1>
            <p class="rp-hero__sub">Desempenho histórico vs. projeção 2026 com sugestões estratégicas por município</p>
            <div class="rp-meta">
                <span class="rp-pill"><?= premium_escape_html($candidateName) ?></span>
                <span class="rp-pill"><?= premium_escape_html($candidateCargo) ?></span>
                <span class="rp-pill"><?= premium_escape_html($campaignName) ?></span>
                <span class="rp-pill">Base histórica: <?= premium_escape_html($baselineLabel) ?></span>
                <span class="rp-pill">Gerado em: <?= premium_escape_html($generatedAt) ?></span>
            </div>
        </div>
        <div class="rp-actions">
            <button class="rp-btn rp-btn--primary" type="button" onclick="window.print()">Imprimir</button>
            <button class="rp-btn rp-btn--ghost"   type="button" onclick="window.close()">Fechar</button>
        </div>
    </section>

    <!-- ── Summary cards ──────────────────────────────────────────────── -->
    <section class="rp-summary">
        <div class="rp-card">
            <div class="rp-card__label">Cidades analisadas</div>
            <div class="rp-card__value"><?= rc_fmt($totalCities) ?></div>
            <div class="rp-card__sub">Total de municípios no comparativo</div>
        </div>
        <div class="rp-card">
            <div class="rp-card__label">Em queda</div>
            <div class="rp-card__value rp-card__value--risk"><?= rc_fmt($countRisk) ?></div>
            <div class="rp-card__sub">Projeção abaixo do histórico</div>
        </div>
        <div class="rp-card">
            <div class="rp-card__label">Estável</div>
            <div class="rp-card__value"><?= rc_fmt($countStable) ?></div>
            <div class="rp-card__sub">Variação dentro de ±5%</div>
        </div>
        <div class="rp-card">
            <div class="rp-card__label">Crescimento</div>
            <div class="rp-card__value rp-card__value--growth"><?= rc_fmt($countGrowth) ?></div>
            <div class="rp-card__sub">Projeção acima do histórico</div>
        </div>
        <div class="rp-card">
            <div class="rp-card__label">Delta total</div>
            <?php $dSign = $deltaTotal >= 0 ? '+' : ''; ?>
            <div class="rp-card__value <?= $deltaTotal >= 0 ? 'rp-card__value--delta-pos' : 'rp-card__value--delta-neg' ?>">
                <?= $dSign . rc_fmt($deltaTotal) ?>
            </div>
            <div class="rp-card__sub"><?= $dSign . rc_pct($deltaTotalPct) ?> sobre o histórico estadual</div>
        </div>
    </section>

    <!-- ── Table ──────────────────────────────────────────────────────── -->
    <section class="rp-table-wrap">
        <table class="rp-table">
            <colgroup>
                <col style="width:3%;">
                <col style="width:13%;">
                <col style="width:10%;">
                <col style="width:8%;">
                <col style="width:8%;">
                <col style="width:9%;">
                <col style="width:12%;">
                <col style="width:37%;">
            </colgroup>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Município</th>
                    <th>Região</th>
                    <th class="rp-num">Votos <?= (int) $baselineYear ?></th>
                    <th class="rp-num">Projeção 2026</th>
                    <th class="rp-num">Diferença</th>
                    <th>Situação</th>
                    <th>Sugestão estratégica</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="8" style="padding:18px;color:var(--muted);text-align:center;">Sem dados de projeção disponíveis.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $i => $r):
                $sugg  = $r['suggestion'];
                $cls   = $sugg['class'];
                $delta = $r['delta'];
                $pct   = $r['delta_pct'];
                $sign  = $delta >= 0 ? '+' : '';
                $pctFmt = $r['baseline_votes'] > 0
                    ? $sign . rc_pct(abs($pct))
                    : ($r['projected'] > 0 ? 'novo' : '—');
                $deltaFmt = $r['baseline_votes'] > 0 || $delta !== 0
                    ? $sign . rc_fmt($delta)
                    : '—';
                $rowClass = in_array($cls, ['danger'], true) ? 'rp-row--danger'
                    : (in_array($cls, ['warning'], true) ? 'rp-row--warning' : '');
                $deltaModifier = $delta > 0 ? 'pos' : ($delta < 0 ? ($cls === 'warning' ? 'warn' : 'neg') : '');
            ?>
                <tr class="<?= premium_escape_html($rowClass) ?>">
                    <td style="color:var(--muted);font-size:.64rem;"><?= $i + 1 ?></td>
                    <td>
                        <strong><?= premium_escape_html($r['municipio']) ?></strong>
                        <?php if ($r['leader_count'] > 0): ?>
                            <span style="display:block;font-size:.62rem;color:var(--muted);"><?= $r['leader_count'] ?> liderança<?= $r['leader_count'] > 1 ? 's' : '' ?></span>
                        <?php else: ?>
                            <span style="display:block;font-size:.62rem;color:var(--danger);opacity:.75;">Sem lideranças</span>
                        <?php endif; ?>
                    </td>
                    <td><?= premium_escape_html($r['regiao']) ?></td>
                    <td class="rp-num"><?= rc_fmt($r['baseline_votes']) ?></td>
                    <td class="rp-num"><?= rc_fmt($r['projected']) ?></td>
                    <td class="rp-delta rp-delta--<?= premium_escape_html($deltaModifier) ?>">
                        <span class="rp-delta__main"><?= premium_escape_html($deltaFmt) ?></span>
                        <span class="rp-delta__pct"><?= premium_escape_html($pctFmt) ?></span>
                    </td>
                    <td><span class="rp-badge rp-badge--<?= premium_escape_html($cls) ?>"><?= premium_escape_html($sugg['label']) ?></span></td>
                    <td class="rp-tip"><?= premium_escape_html($sugg['tip']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- ── Footer ─────────────────────────────────────────────────────── -->
    <footer class="rp-footer">
        <span class="rp-footer__brand">ApoiaCandidato</span>
        <span>Relatório gerado em <?= premium_escape_html($generatedAt) ?> &mdash; <?= premium_escape_html($campaignName) ?></span>
        <span>apoiacandidato.com.br</span>
    </footer>

</div>
</body>
</html>
