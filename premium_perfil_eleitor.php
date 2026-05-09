<?php

declare(strict_types=1);

require_once __DIR__ . '/premium_helpers.php';

$user = premium_require_user($conn);
$isAdmin = premium_is_admin_user($user);
$csrf = premium_csrf_token();
$flash = premium_pull_flash();

$trialDaysRemaining = premium_trial_days_remaining($user);
if ($trialDaysRemaining !== null) {
    premium_push_flash('Esta página é exclusiva para usuários com acesso completo ao sistema.', 'error');
    header('Location: premium');
    exit;
}

if (isset($_GET['campaign_id'])) {
    $requestedCampaignId = (int) $_GET['campaign_id'];
    if ($requestedCampaignId > 0 && premium_get_campaign($conn, $requestedCampaignId, (int) $user['id'], $isAdmin)) {
        premium_set_active_campaign($requestedCampaignId);
    }
}

$campaign = premium_active_campaign($conn, (int) $user['id'], $isAdmin);
$premiumSupportWhatsappUrl = premium_vip_support_whatsapp_url($user, $campaign);

// ---------------------------------------------------------------------------
// Carrega municípios do banco
// ---------------------------------------------------------------------------
$rows = query($conn, 'SELECT * FROM perfil_eleitor_municipio ORDER BY nm_municipio ASC');

if (!$rows) {
    premium_push_flash('Os dados de perfil eleitoral ainda não foram importados.', 'error');
    header('Location: premium');
    exit;
}

// ---------------------------------------------------------------------------
// Ordem canônica das faixas etárias
// ---------------------------------------------------------------------------
$ordemFaixas = [
    '15 anos', '16 anos', '17 anos',
    '18 anos', '19 anos', '20 anos',
    '21 a 24 anos', '25 a 29 anos', '30 a 34 anos', '35 a 39 anos',
    '40 a 44 anos', '45 a 49 anos', '50 a 54 anos', '55 a 59 anos',
    '60 a 64 anos', '65 a 69 anos', '70 a 74 anos', '75 a 79 anos',
    '80 a 84 anos', '85 a 89 anos', '90 a 94 anos', '95 a 99 anos',
    '100 anos ou mais',
];

$ordemInstrucao = [
    'ANALFABETO',
    'LÊ E ESCREVE',
    'ENSINO FUNDAMENTAL INCOMPLETO',
    'ENSINO FUNDAMENTAL COMPLETO',
    'ENSINO MÉDIO INCOMPLETO',
    'ENSINO MÉDIO COMPLETO',
    'SUPERIOR INCOMPLETO',
    'SUPERIOR COMPLETO',
];

// Rótulos curtos para escolaridade
$labelInstrucao = [
    'ANALFABETO'                    => 'Analfabeto',
    'LÊ E ESCREVE'                  => 'Lê e escreve',
    'ENSINO FUNDAMENTAL INCOMPLETO' => 'Fund. incompleto',
    'ENSINO FUNDAMENTAL COMPLETO'   => 'Fund. completo',
    'ENSINO MÉDIO INCOMPLETO'       => 'Médio incompleto',
    'ENSINO MÉDIO COMPLETO'         => 'Médio completo',
    'SUPERIOR INCOMPLETO'           => 'Superior incompleto',
    'SUPERIOR COMPLETO'             => 'Superior completo',
];

// ---------------------------------------------------------------------------
// Decodifica JSON e monta estrutura de dados por município
// ---------------------------------------------------------------------------
$municipios = [];
foreach ($rows as $row) {
    $cd = (int) $row['cd_municipio'];
    $municipios[$cd] = [
        'cd_municipio'   => $cd,
        'nm_municipio'   => (string) $row['nm_municipio'],
        'qt_total'       => (int) $row['qt_total'],
        'qt_biometria'   => (int) $row['qt_biometria'],
        'qt_deficiencia' => (int) $row['qt_deficiencia'],
        'qt_nome_social' => (int) $row['qt_nome_social'],
        'ano_eleicao'    => (int) $row['ano_eleicao'],
        'genero'         => (array) (json_decode((string) $row['genero'],         true) ?? []),
        'faixa_etaria'   => (array) (json_decode((string) $row['faixa_etaria'],   true) ?? []),
        'grau_instrucao' => (array) (json_decode((string) $row['grau_instrucao'], true) ?? []),
        'cor_raca'       => (array) (json_decode((string) $row['cor_raca'],        true) ?? []),
        'estado_civil'   => (array) (json_decode((string) $row['estado_civil'],    true) ?? []),
        'obrigatoriedade'=> (array) (json_decode((string) $row['obrigatoriedade'],true) ?? []),
    ];
}

// ---------------------------------------------------------------------------
// Agrega totais estaduais
// ---------------------------------------------------------------------------
$totaisUF = [
    'qt_total'       => 0,
    'qt_biometria'   => 0,
    'qt_deficiencia' => 0,
    'qt_nome_social' => 0,
    'genero'         => [],
    'faixa_etaria'   => [],
    'grau_instrucao' => [],
    'cor_raca'       => [],
    'estado_civil'   => [],
    'obrigatoriedade'=> [],
];

foreach ($municipios as $m) {
    $totaisUF['qt_total']       += $m['qt_total'];
    $totaisUF['qt_biometria']   += $m['qt_biometria'];
    $totaisUF['qt_deficiencia'] += $m['qt_deficiencia'];
    $totaisUF['qt_nome_social'] += $m['qt_nome_social'];

    foreach (['genero', 'faixa_etaria', 'grau_instrucao', 'cor_raca', 'estado_civil', 'obrigatoriedade'] as $dim) {
        foreach ($m[$dim] as $cat => $qt) {
            $totaisUF[$dim][$cat] = ($totaisUF[$dim][$cat] ?? 0) + $qt;
        }
    }
}

// ---------------------------------------------------------------------------
// Município selecionado para detalhamento
// ---------------------------------------------------------------------------
$cdSelecionado = isset($_GET['municipio']) ? (int) $_GET['municipio'] : 0;
$mSel = $cdSelecionado > 0 && isset($municipios[$cdSelecionado])
    ? $municipios[$cdSelecionado]
    : null;
$dadosFoco = $mSel ?? [
    'cd_municipio'   => 0,
    'nm_municipio'   => 'Sergipe',
    'qt_total'       => $totaisUF['qt_total'],
    'qt_biometria'   => $totaisUF['qt_biometria'],
    'qt_deficiencia' => $totaisUF['qt_deficiencia'],
    'qt_nome_social' => $totaisUF['qt_nome_social'],
    'genero'         => $totaisUF['genero'],
    'faixa_etaria'   => $totaisUF['faixa_etaria'],
    'grau_instrucao' => $totaisUF['grau_instrucao'],
    'cor_raca'       => $totaisUF['cor_raca'],
    'estado_civil'   => $totaisUF['estado_civil'],
    'obrigatoriedade'=> $totaisUF['obrigatoriedade'],
];
$labelFoco = $mSel ? $mSel['nm_municipio'] : 'Sergipe';
$totalFoco = (int) $dadosFoco['qt_total'];

// ---------------------------------------------------------------------------
// Helpers de formatação
// ---------------------------------------------------------------------------
function perfil_ucfirst(string $v): string
{
    return mb_convert_case($v, MB_CASE_TITLE, 'UTF-8');
}

function perfil_fmt_int(int $v): string
{
    return number_format($v, 0, ',', '.');
}

function perfil_fmt_pct(float $v, int $dec = 1): string
{
    return number_format($v, $dec, ',', '.') . '%';
}

function perfil_pct(int $part, int $total): float
{
    return $total > 0 ? ($part / $total) * 100 : 0.0;
}

$anoRef = '04/2026';
$geracaoRef = 'Gerado em ' . date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <script src="assets/js/premium-bootstrap.js"></script>
    <title>Perfil do Eleitorado | Apoia Candidato</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/premium.css">
    <style>
        .perfil-bar-wrap { display:flex; align-items:center; gap:10px; margin:6px 0; }
        .perfil-bar-label { min-width:160px; font-size:.82rem; color:var(--muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .perfil-bar-track { flex:1; background:rgba(128,128,128,0.22); border-radius:4px; height:10px; }
        .perfil-bar-fill  { height:10px; border-radius:4px; transition:width .3s; }
        .perfil-bar-value { min-width:52px; font-size:.82rem; font-weight:600; text-align:right; }
        .perfil-stat-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin:20px 0; }
        .perfil-stat { background:var(--summary-metric-bg); border:1px solid var(--summary-metric-border); border-radius:10px; padding:18px 20px; color:var(--text); }
        .perfil-stat__label { font-size:.75rem; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); }
        .perfil-stat__value { font-size:1.7rem; font-weight:700; margin-top:4px; color:var(--text); }
        .perfil-stat__sub   { font-size:.78rem; color:var(--muted); margin-top:2px; }
        .perfil-municipio-table { width:100%; border-collapse:collapse; font-size:.85rem; }
        .perfil-municipio-table th { text-align:left; padding:8px 10px; border-bottom:2px solid var(--line); font-size:.75rem; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); }
        .perfil-municipio-table td { padding:8px 10px; border-bottom:1px solid var(--line); color:var(--text); }
        .perfil-municipio-table tr:last-child td { border-bottom:none; }
        .perfil-municipio-table td.num { text-align:right; font-variant-numeric:tabular-nums; }
        .perfil-filter-form { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:8px; }
        .perfil-filter-form select { padding:6px 10px; border:1px solid var(--line); border-radius:6px; font-size:.875rem; background:var(--panel); color:var(--text); }
        .perfil-filter-form .btn { padding:6px 14px; font-size:.875rem; }
        .section-divider { border:none; border-top:1px solid var(--line); margin:32px 0; }
    </style>
</head>
<body>
<div class="shell">
    <header class="topbar">
        <div>
            <div class="eyebrow">Apoia Candidato Premium</div>
            <h1>Perfil do Eleitorado — Sergipe</h1>
            <p class="muted">Dados TSE · <?= htmlspecialchars($anoRef) ?> · <?= perfil_fmt_int($totaisUF['qt_total']) ?> eleitores em <?= count($municipios) ?> municípios</p>
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
                <a class="btn comparison-cta" href="premium<?= $campaign ? '?campaign_id=' . (int) $campaign['id'] : '' ?>">Voltar ao painel</a>
                <a class="btn ghost" href="premium_logout.php">Sair</a>
            </div>
            <?php if ($premiumSupportWhatsappUrl !== ''): ?>
                <div class="vip-support">
                    <span>Atendimento VIP</span>
                    <a class="btn vip-support__btn" href="<?= premium_escape_html($premiumSupportWhatsappUrl) ?>" target="_blank" rel="noopener">WhatsApp</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <main class="premium-main" style="max-width:1100px; margin:0 auto; padding:32px 20px;">

        <?php if ($flash): ?>
            <div class="flash flash--<?= premium_escape_html((string) ($flash['type'] ?? 'info')) ?>"><?= premium_escape_html((string) ($flash['message'] ?? '')) ?></div>
        <?php endif; ?>

        <!-- ================================================================
             FILTRO GLOBAL
        ================================================================ -->
        <section class="panel" style="margin-bottom:24px;">
            <form class="perfil-filter-form" method="get" action="">
                <?php if ($campaign): ?>
                    <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
                <?php endif; ?>
                <label for="municipioSelect" style="font-size:.875rem;font-weight:600;white-space:nowrap;">Visualizar dados de:</label>
                <select name="municipio" id="municipioSelect" style="flex:1;min-width:200px;max-width:360px;">
                    <option value="0"<?= $cdSelecionado === 0 ? ' selected' : '' ?>>Sergipe — estado completo</option>
                    <?php foreach ($municipios as $m): ?>
                        <option value="<?= (int) $m['cd_municipio'] ?>"<?= $cdSelecionado === $m['cd_municipio'] ? ' selected' : '' ?>>
                            <?= premium_escape_html($m['nm_municipio']) ?> (<?= perfil_fmt_int($m['qt_total']) ?> eleitores)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn comparison-cta">Aplicar</button>
                <?php if ($cdSelecionado > 0): ?>
                    <a class="btn ghost" href="?<?= $campaign ? 'campaign_id=' . (int) $campaign['id'] . '&' : '' ?>municipio=0">Ver Sergipe completo</a>
                <?php endif; ?>
            </form>
        </section>

        <!-- ================================================================
             BLOCO 1 · Resumo do foco selecionado
        ================================================================ -->
        <?php
        $focoBio  = (int) $dadosFoco['qt_biometria'];
        $focoDef  = (int) $dadosFoco['qt_deficiencia'];
        $focoSoc  = (int) $dadosFoco['qt_nome_social'];
        $focoFem  = (int) ($dadosFoco['genero']['FEMININO']  ?? 0);
        $focoFac  = (int) ($dadosFoco['obrigatoriedade']['Facultativo'] ?? 0);
        $focoSub  = $mSel ? '1 município' : count($municipios) . ' municípios';

        // Faixa etária — bloco decisivo 25–49
        $focoFaixas = $dadosFoco['faixa_etaria'];
        $faixasDecisivas = ['25 a 29 anos', '30 a 34 anos', '35 a 39 anos', '40 a 44 anos', '45 a 49 anos'];
        $faixaBlocoQt = 0;
        $faixaBlocoLinhas = [];
        foreach ($faixasDecisivas as $f) {
            $qt = (int) ($focoFaixas[$f] ?? 0);
            $faixaBlocoQt += $qt;
            if ($qt > 0) {
                $faixaBlocoLinhas[] = $f . ': ' . perfil_fmt_pct(perfil_pct($qt, $totalFoco));
            }
        }

        // Escolaridade dominante
        $focoInstr     = $dadosFoco['grau_instrucao'];
        arsort($focoInstr);
        $instrDom      = (string) (array_key_first($focoInstr) ?? '—');
        $instrDomQt    = (int) ($focoInstr[$instrDom] ?? 0);
        $instrDomLabel = $labelInstrucao[$instrDom] ?? perfil_ucfirst($instrDom);
        $instr2Key     = (string) (array_key_first(array_slice($focoInstr, 1, 1, true)) ?? '');
        $instr2Qt      = (int) ($focoInstr[$instr2Key] ?? 0);
        $instr2Label   = $instr2Key !== '' ? ($labelInstrucao[$instr2Key] ?? perfil_ucfirst($instr2Key)) : '';

        // Gênero dominante (para card de perfil decisivo)
        $focoGenSort = $dadosFoco['genero'];
        arsort($focoGenSort);
        $genDomLabel = perfil_ucfirst((string) (array_key_first($focoGenSort) ?? '—'));

        // Estado civil — ordena por volume
        $focoEstCivil = $dadosFoco['estado_civil'];
        arsort($focoEstCivil);
        ?>
        <section class="panel">
            <div class="section-title">
                <div>
                    <div class="eyebrow">Visão geral</div>
                    <h2>Eleitorado — <?= premium_escape_html($labelFoco) ?></h2>
                </div>
            </div>
            <p class="panel-note">Totais e indicadores-chave conforme cadastro TSE.</p>

            <div class="perfil-stat-grid">
                <div class="perfil-stat">
                    <div class="perfil-stat__label">Total de eleitores</div>
                    <div class="perfil-stat__value"><?= perfil_fmt_int($totalFoco) ?></div>
                    <div class="perfil-stat__sub"><?= $focoSub ?></div>
                </div>
                <div class="perfil-stat">
                    <div class="perfil-stat__label">Biometria cadastrada</div>
                    <div class="perfil-stat__value"><?= perfil_fmt_pct(perfil_pct($focoBio, $totalFoco)) ?></div>
                    <div class="perfil-stat__sub"><?= perfil_fmt_int($focoBio) ?> eleitores</div>
                </div>
                <div class="perfil-stat">
                    <div class="perfil-stat__label">Eleitores com deficiência</div>
                    <div class="perfil-stat__value"><?= perfil_fmt_int($focoDef) ?></div>
                    <div class="perfil-stat__sub"><?= perfil_fmt_pct(perfil_pct($focoDef, $totalFoco)) ?> do eleitorado</div>
                </div>
                <div class="perfil-stat">
                    <div class="perfil-stat__label">Nome social</div>
                    <div class="perfil-stat__value"><?= perfil_fmt_int($focoSoc) ?></div>
                    <div class="perfil-stat__sub"><?= perfil_fmt_pct(perfil_pct($focoSoc, $totalFoco)) ?> do eleitorado</div>
                </div>
                <div class="perfil-stat">
                    <div class="perfil-stat__label">Eleitoras (feminino)</div>
                    <div class="perfil-stat__value"><?= perfil_fmt_pct(perfil_pct($focoFem, $totalFoco)) ?></div>
                    <div class="perfil-stat__sub"><?= perfil_fmt_int($focoFem) ?> eleitoras</div>
                </div>
                <div class="perfil-stat">
                    <div class="perfil-stat__label">Voto facultativo</div>
                    <div class="perfil-stat__value"><?= perfil_fmt_pct(perfil_pct($focoFac, $totalFoco)) ?></div>
                    <div class="perfil-stat__sub"><?= perfil_fmt_int($focoFac) ?> eleitores</div>
                </div>
                <div class="perfil-stat">
                    <div class="perfil-stat__label">Faixa etária dominante</div>
                    <div class="perfil-stat__value" style="font-size:1.1rem;line-height:1.3;"><?= perfil_fmt_pct(perfil_pct($faixaBlocoQt, $totalFoco)) ?> · bloco 25–49</div>
                    <div class="perfil-stat__sub">
                        <?= implode('<br>', array_map('premium_escape_html', $faixaBlocoLinhas)) ?><br>
                        <?= perfil_fmt_int($faixaBlocoQt) ?> eleitores combinados
                    </div>
                </div>
                <div class="perfil-stat">
                    <div class="perfil-stat__label">Escolaridade dominante</div>
                    <div class="perfil-stat__value" style="font-size:1.1rem;line-height:1.3;"><?= perfil_fmt_pct(perfil_pct($instrDomQt, $totalFoco)) ?> <?= premium_escape_html($instrDomLabel) ?></div>
                    <div class="perfil-stat__sub">
                        <?php if ($instr2Label !== ''): ?>
                            <?= perfil_fmt_pct(perfil_pct($instr2Qt, $totalFoco)) ?> <?= premium_escape_html($instr2Label) ?><br>
                        <?php endif; ?>
                        <?= perfil_fmt_int($instrDomQt + $instr2Qt) ?> eleitores combinados
                    </div>
                </div>
                <div class="perfil-stat">
                    <div class="perfil-stat__label">Perfil decisivo — deputado estadual</div>
                    <div class="perfil-stat__value" style="font-size:1.1rem;line-height:1.3;"><?= premium_escape_html($genDomLabel) ?> · 25–49 anos</div>
                    <div class="perfil-stat__sub"><?= premium_escape_html($instrDomLabel) ?> + <?= premium_escape_html($instr2Label) ?><br>SE elege 24 deputados estaduais</div>
                </div>
                <div class="perfil-stat">
                    <div class="perfil-stat__label">Estado civil</div>
                    <?php
                    $ecDomLabel = perfil_ucfirst((string) array_key_first($focoEstCivil));
                    $ecDomQt    = (int) reset($focoEstCivil);
                    ?>
                    <div class="perfil-stat__value"><?= perfil_fmt_pct(perfil_pct($ecDomQt, $totalFoco)) ?></div>
                    <div class="perfil-stat__sub">
                        <?php foreach ($focoEstCivil as $ec => $qt): ?>
                            <?= premium_escape_html(perfil_ucfirst((string) $ec)) ?>: <?= perfil_fmt_pct(perfil_pct((int) $qt, $totalFoco)) ?><br>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             BLOCO 2 · Gênero
        ================================================================ -->
        <section class="panel" style="margin-top:24px;">
            <div class="section-title">
                <div>
                    <div class="eyebrow">Gênero</div>
                    <?php if ($mSel): ?>
                        <h2>Distribuição em <?= premium_escape_html($labelFoco) ?></h2>
                    <?php else: ?>
                        <h2>Distribuição por município</h2>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($mSel): ?>
                <?php
                // Modo município: mostra todas as categorias de gênero do foco
                $generoFoco = (array) $dadosFoco['genero'];
                arsort($generoFoco);
                $maxGenero = max(1, ...(array_values($generoFoco) ?: [1]));
                $genCores = ['FEMININO' => '#e879a0', 'MASCULINO' => '#4f8ef7'];
                ?>
                <p class="panel-note">Distribuição absoluta e percentual por gênero.</p>
                <div style="margin-top:16px;">
                    <?php foreach ($generoFoco as $genLabel => $genQt): ?>
                        <?php
                        $genQt  = (int) $genQt;
                        $pctG   = perfil_pct($genQt, $totalFoco);
                        $barW   = number_format(($genQt / $maxGenero) * 100, 2, '.', '');
                        $corG   = $genCores[$genLabel] ?? '#a78bfa';
                        ?>
                        <div class="perfil-bar-wrap">
                            <span class="perfil-bar-label"><?= premium_escape_html(perfil_ucfirst($genLabel)) ?></span>
                            <div class="perfil-bar-track" title="<?= perfil_fmt_int($genQt) ?> eleitores">
                                <div class="perfil-bar-fill" style="width:<?= $barW ?>%;background:<?= $corG ?>;"></div>
                            </div>
                            <span class="perfil-bar-value" style="color:<?= $corG ?>;"><?= perfil_fmt_pct($pctG) ?></span>
                        </div>
                        <p class="panel-note" style="margin:0 0 6px 170px;font-size:.78rem;"><?= perfil_fmt_int($genQt) ?> eleitores</p>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <?php
                // Modo SE: ranking de todos os municípios por % feminino
                $municipiosPorGenero = $municipios;
                usort($municipiosPorGenero, static function (array $a, array $b): int {
                    $pctA = $a['qt_total'] > 0 ? (($a['genero']['FEMININO'] ?? 0) / $a['qt_total']) : 0;
                    $pctB = $b['qt_total'] > 0 ? (($b['genero']['FEMININO'] ?? 0) / $b['qt_total']) : 0;
                    return $pctB <=> $pctA;
                });
                ?>
                <p class="panel-note">Proporção de eleitoras (feminino) em cada município, do maior para o menor percentual.</p>
                <div style="margin-top:16px;">
                    <?php foreach ($municipiosPorGenero as $m): ?>
                        <?php
                        $fem  = (int) ($m['genero']['FEMININO']  ?? 0);
                        $masc = (int) ($m['genero']['MASCULINO'] ?? 0);
                        $pctF = perfil_pct($fem, $m['qt_total']);
                        $pctM = perfil_pct($masc, $m['qt_total']);
                        ?>
                        <div class="perfil-bar-wrap">
                            <span class="perfil-bar-label" title="<?= premium_escape_html($m['nm_municipio']) ?>"><?= premium_escape_html($m['nm_municipio']) ?></span>
                            <div class="perfil-bar-track" title="Feminino: <?= perfil_fmt_pct($pctF) ?> | Masculino: <?= perfil_fmt_pct($pctM) ?>">
                                <div class="perfil-bar-fill" style="width:<?= number_format($pctF, 2, '.', '') ?>%;background:#e879a0;"></div>
                            </div>
                            <span class="perfil-bar-value" style="color:#e879a0;"><?= perfil_fmt_pct($pctF) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="panel-note" style="margin-top:12px;">Rosa = % feminino. Restante = masculino e outros gêneros.</p>
            <?php endif; ?>
        </section>

        <!-- ================================================================
             BLOCO 3 · Faixa etária / Escolaridade / Cor-raça / Obrigatoriedade
        ================================================================ -->
        <section class="panel" style="margin-top:24px;">
            <div class="section-title">
                <div>
                    <div class="eyebrow">Perfil detalhado — <?= premium_escape_html($labelFoco) ?></div>
                    <h2>Faixa etária · Escolaridade · Cor/Raça</h2>
                </div>
            </div>

            <hr class="section-divider" style="margin-top:8px;">

            <!-- Faixa etária -->
            <h3 style="margin:0 0 14px;">Faixa etária</h3>
            <?php
            $faixasUsadas = (array) ($dadosFoco['faixa_etaria'] ?? $totaisUF['faixa_etaria']);
            $maxFaixa = max(1, ...(array_values($faixasUsadas) ?: [1]));
            foreach ($ordemFaixas as $faixa):
                $qt = (int) ($faixasUsadas[$faixa] ?? 0);
                if ($qt <= 0) continue;
                $pct = perfil_pct($qt, $totalFoco);
                $barW = $maxFaixa > 0 ? number_format(($qt / $maxFaixa) * 100, 2, '.', '') : '0';
            ?>
                <div class="perfil-bar-wrap">
                    <span class="perfil-bar-label"><?= premium_escape_html($faixa) ?></span>
                    <div class="perfil-bar-track">
                        <div class="perfil-bar-fill" style="width:<?= $barW ?>%;background:#4f8ef7;"></div>
                    </div>
                    <span class="perfil-bar-value" style="color:#4f8ef7;"><?= perfil_fmt_pct($pct) ?></span>
                </div>
            <?php endforeach; ?>

            <hr class="section-divider">

            <!-- Escolaridade -->
            <h3 style="margin:0 0 14px;">Escolaridade</h3>
            <?php
            $instrucaoUsada = (array) ($dadosFoco['grau_instrucao'] ?? $totaisUF['grau_instrucao']);
            $maxInstrucao = max(1, ...(array_values($instrucaoUsada) ?: [1]));
            // Calcula % superior completo para destaque
            $qtSuperior = (int) ($instrucaoUsada['SUPERIOR COMPLETO'] ?? 0);
            foreach ($ordemInstrucao as $grau):
                $qt = (int) ($instrucaoUsada[$grau] ?? 0);
                if ($qt <= 0) continue;
                $pct = perfil_pct($qt, $totalFoco);
                $barW = number_format(($qt / $maxInstrucao) * 100, 2, '.', '');
                $cor = in_array($grau, ['SUPERIOR INCOMPLETO', 'SUPERIOR COMPLETO'], true) ? '#34c48b' : '#4f8ef7';
                $label = $labelInstrucao[$grau] ?? $grau;
            ?>
                <div class="perfil-bar-wrap">
                    <span class="perfil-bar-label" title="<?= premium_escape_html($grau) ?>"><?= premium_escape_html($label) ?></span>
                    <div class="perfil-bar-track">
                        <div class="perfil-bar-fill" style="width:<?= $barW ?>%;background:<?= $cor ?>;"></div>
                    </div>
                    <span class="perfil-bar-value" style="color:<?= $cor ?>;"><?= perfil_fmt_pct($pct) ?></span>
                </div>
            <?php endforeach; ?>

            <hr class="section-divider">

            <!-- Cor / Raça (exclui NÃO INFORMADO do gráfico, mas exibe nota) -->
            <h3 style="margin:0 0 6px;">Cor/Raça <span style="font-size:.8rem;font-weight:400;" class="muted">(eleitores que declararam)</span></h3>
            <?php
            $corRacaUsada = (array) ($dadosFoco['cor_raca'] ?? $totaisUF['cor_raca']);
            $qtNaoInformado = (int) ($corRacaUsada['NÃO INFORMADO'] ?? 0);
            $corRacaDeclarada = array_filter($corRacaUsada, static fn($k) => $k !== 'NÃO INFORMADO' && $k !== '-1', ARRAY_FILTER_USE_KEY);
            arsort($corRacaDeclarada);
            $totalDeclarado = array_sum($corRacaDeclarada);
            $maxCor = max(1, ...(array_values($corRacaDeclarada) ?: [1]));
            $coresPaleta = ['#4f8ef7', '#34c48b', '#e879a0', '#f59e0b', '#a78bfa', '#f97316'];
            $iCor = 0;
            foreach ($corRacaDeclarada as $cor => $qt):
                if ($qt <= 0) continue;
                $pct = perfil_pct((int) $qt, (int) max(1, $totalDeclarado));
                $barW = number_format(((int) $qt / $maxCor) * 100, 2, '.', '');
                $corHex = $coresPaleta[$iCor % count($coresPaleta)];
                $iCor++;
            ?>
                <div class="perfil-bar-wrap">
                    <span class="perfil-bar-label"><?= premium_escape_html((string) $cor) ?></span>
                    <div class="perfil-bar-track">
                        <div class="perfil-bar-fill" style="width:<?= $barW ?>%;background:<?= $corHex ?>;"></div>
                    </div>
                    <span class="perfil-bar-value" style="color:<?= $corHex ?>;"><?= perfil_fmt_pct($pct) ?></span>
                </div>
            <?php endforeach; ?>
            <?php if ($qtNaoInformado > 0): ?>
                <p class="panel-note" style="margin-top:10px;">
                    <?= perfil_fmt_int($qtNaoInformado) ?> eleitores (<?= perfil_fmt_pct(perfil_pct($qtNaoInformado, $totalFoco)) ?>) não informaram cor/raça — excluídos do gráfico acima.
                </p>
            <?php endif; ?>

            <hr class="section-divider">

            <!-- Obrigatoriedade -->
            <h3 style="margin:0 0 14px;">Obrigatoriedade do voto</h3>
            <?php
            $obrigUsada = (array) ($dadosFoco['obrigatoriedade'] ?? $totaisUF['obrigatoriedade']);
            $obrigOrder = ['Obrigatório', 'Facultativo'];
            $obrigCores = ['Obrigatório' => '#4f8ef7', 'Facultativo' => '#f59e0b'];
            $maxObrig = max(1, ...(array_values($obrigUsada) ?: [1]));
            foreach ($obrigOrder as $tipo):
                $qt = (int) ($obrigUsada[$tipo] ?? 0);
                if ($qt <= 0) continue;
                $pct = perfil_pct($qt, $totalFoco);
                $barW = number_format(($qt / $maxObrig) * 100, 2, '.', '');
                $corH = $obrigCores[$tipo] ?? '#4f8ef7';
            ?>
                <div class="perfil-bar-wrap">
                    <span class="perfil-bar-label"><?= premium_escape_html($tipo) ?></span>
                    <div class="perfil-bar-track">
                        <div class="perfil-bar-fill" style="width:<?= $barW ?>%;background:<?= $corH ?>;"></div>
                    </div>
                    <span class="perfil-bar-value" style="color:<?= $corH ?>;"><?= perfil_fmt_pct($pct) ?></span>
                </div>
            <?php endforeach; ?>
            <p class="panel-note" style="margin-top:8px;">Voto facultativo: menores de 18 anos, maiores de 70 anos e analfabetos.</p>
        </section>

        <!-- ================================================================
             BLOCO 4 · Tabela completa por município
        ================================================================ -->
        <section class="panel" style="margin-top:24px;">
            <div class="section-title">
                <div>
                    <div class="eyebrow">Tabela comparativa</div>
                    <h2>Todos os municípios</h2>
                </div>
            </div>
            <p class="panel-note">
                Ordenado por total de eleitores (maior → menor).
                Verde = superior completo acima de 15%.
                <?= $cdSelecionado > 0 ? '<strong>Município selecionado destacado em azul.</strong>' : '' ?>
            </p>

            <div style="overflow-x:auto; margin-top:16px;">
                <table class="perfil-municipio-table">
                    <thead>
                        <tr>
                            <th>Município</th>
                            <th class="num">Total</th>
                            <th class="num">% Fem.</th>
                            <th class="num">% Superior</th>
                            <th class="num">% Biometria</th>
                            <th class="num">% Facultativo</th>
                            <th class="num">% Defic.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $municipiosOrdenados = $municipios;
                        usort($municipiosOrdenados, static fn(array $a, array $b): int => $b['qt_total'] <=> $a['qt_total']);
                        foreach ($municipiosOrdenados as $m):
                            $pctFem      = perfil_pct((int) ($m['genero']['FEMININO'] ?? 0), $m['qt_total']);
                            $pctSup      = perfil_pct((int) ($m['grau_instrucao']['SUPERIOR COMPLETO'] ?? 0), $m['qt_total']);
                            $pctBio      = perfil_pct($m['qt_biometria'], $m['qt_total']);
                            $pctFac      = perfil_pct((int) ($m['obrigatoriedade']['Facultativo'] ?? 0), $m['qt_total']);
                            $pctDef      = perfil_pct($m['qt_deficiencia'], $m['qt_total']);
                            $isSuperior  = $pctSup >= 15.0;
                            $isSelecionado = $cdSelecionado > 0 && $m['cd_municipio'] === $cdSelecionado;
                            $rowStyle = $isSelecionado
                                ? 'background:rgba(79,142,247,.13);outline:2px solid rgba(79,142,247,.40);outline-offset:-1px;'
                                : ($isSuperior ? 'background:rgba(52,196,139,.08);' : '');
                        ?>
                            <tr style="<?= $rowStyle ?>">
                                <td>
                                    <?= premium_escape_html($m['nm_municipio']) ?>
                                    <?php if ($isSelecionado): ?>
                                        <span style="font-size:.72rem;font-weight:600;color:#4f8ef7;margin-left:6px;">&#9679; selecionado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="num"><?= perfil_fmt_int($m['qt_total']) ?></td>
                                <td class="num"><?= perfil_fmt_pct($pctFem) ?></td>
                                <td class="num"<?= $isSuperior ? ' style="color:#34c48b;font-weight:600;"' : '' ?>><?= perfil_fmt_pct($pctSup) ?></td>
                                <td class="num"><?= perfil_fmt_pct($pctBio) ?></td>
                                <td class="num"><?= perfil_fmt_pct($pctFac) ?></td>
                                <td class="num"><?= perfil_fmt_pct($pctDef, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <p class="muted" style="text-align:center;margin-top:24px;font-size:.78rem;">
            Fonte: TSE · perfil_eleitor_secao_ATUAL_SE.csv · <?= premium_escape_html($geracaoRef) ?>
        </p>

    </main>
</div>
<script src="assets/js/premium.js"></script>
</body>
</html>
