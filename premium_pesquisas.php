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
if (!$campaign) {
    premium_push_flash('Nenhuma campanha ativa. Crie ou selecione uma campanha primeiro.', 'error');
    header('Location: premium');
    exit;
}

$campaignId = (int) $campaign['id'];
$premiumSupportWhatsappUrl = premium_vip_support_whatsapp_url($user, $campaign);

// ---------------------------------------------------------------------------
// Municípios (para select e comparativo)
// ---------------------------------------------------------------------------
$municipiosRows = queryAll($conn, 'SELECT cd_municipio, nm_municipio, qt_total FROM perfil_eleitor_municipio ORDER BY nm_municipio ASC') ?: [];
$municipiosPorCd = [];
foreach ($municipiosRows as $m) {
    $municipiosPorCd[(int) $m['cd_municipio']] = $m;
}
$temPerfil = !empty($municipiosRows);

// ---------------------------------------------------------------------------
// Forecast da campanha
// ---------------------------------------------------------------------------
$settings  = premium_load_campaign_settings($conn, $campaignId);
$baselineYear = premium_resolve_baseline_year((int) ($campaign['baseline_year'] ?? 2022));
$baseline  = premium_candidate_baseline($conn, (string) ($campaign['candidate_name'] ?? ''), (string) ($campaign['candidate_cargo'] ?? ''), $baselineYear);
$leaders   = premium_get_campaign_leaders($conn, $campaignId);
$forecast  = premium_build_forecast($baseline, $leaders, $settings);
$temForecast = !empty($forecast['cities']);

// ---------------------------------------------------------------------------
// POST — excluir pesquisa
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!premium_validate_csrf((string) ($_POST['csrf'] ?? ''))) {
        premium_push_flash('Token inválido. Tente novamente.', 'error');
        header('Location: premium_pesquisas.php' . ($campaign ? '?campaign_id=' . $campaignId : ''));
        exit;
    }
    $delId = (int) ($_POST['pesquisa_id'] ?? 0);
    if ($delId > 0 && premium_delete_pesquisa($conn, $delId, $campaignId)) {
        premium_push_flash('Pesquisa excluída.', 'success');
    } else {
        premium_push_flash('Não foi possível excluir a pesquisa.', 'error');
    }
    header('Location: premium_pesquisas.php?campaign_id=' . $campaignId);
    exit;
}

// ---------------------------------------------------------------------------
// POST — salvar pesquisa
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] === 'save')) {
    if (!premium_validate_csrf((string) ($_POST['csrf'] ?? ''))) {
        premium_push_flash('Token inválido. Tente novamente.', 'error');
        header('Location: premium_pesquisas.php?campaign_id=' . $campaignId);
        exit;
    }

    $erros = [];
    $instituto    = trim((string) ($_POST['instituto']    ?? ''));
    $tipo         = (string) ($_POST['tipo']         ?? 'estadual');
    $cdMunicipio  = (int)    ($_POST['cd_municipio'] ?? 0);
    $nmMunicipio  = trim((string) ($_POST['nm_municipio'] ?? ''));
    $dataPesquisa = trim((string) ($_POST['data_pesquisa'] ?? ''));
    $pctCandidato = (float)  str_replace(',', '.', (string) ($_POST['pct_candidato'] ?? ''));
    $observacoes  = trim((string) ($_POST['observacoes'] ?? ''));

    if ($instituto === '') $erros[] = 'Informe o instituto.';
    if (!in_array($tipo, ['estadual', 'municipal'], true)) $erros[] = 'Tipo inválido.';
    if ($tipo === 'municipal' && $cdMunicipio <= 0) $erros[] = 'Selecione o município.';
    if ($dataPesquisa === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataPesquisa)) $erros[] = 'Data inválida.';
    if ($pctCandidato <= 0 || $pctCandidato > 100) $erros[] = 'Percentual inválido (0,01 a 100).';

    if ($tipo === 'municipal' && $cdMunicipio > 0 && isset($municipiosPorCd[$cdMunicipio])) {
        $nmMunicipio = (string) $municipiosPorCd[$cdMunicipio]['nm_municipio'];
    }

    if ($erros) {
        premium_push_flash(implode(' ', $erros), 'error');
    } else {
        $ok = premium_save_pesquisa($conn, $campaignId, [
            'instituto'     => $instituto,
            'tipo'          => $tipo,
            'cd_municipio'  => $cdMunicipio > 0 ? $cdMunicipio : null,
            'nm_municipio'  => $nmMunicipio !== '' ? $nmMunicipio : null,
            'data_pesquisa' => $dataPesquisa,
            'pct_candidato' => $pctCandidato,
            'observacoes'   => $observacoes,
        ]);
        if ($ok) {
            premium_push_flash('Pesquisa registrada com sucesso.', 'success');
        } else {
            premium_push_flash('Erro ao salvar. Tente novamente.', 'error');
        }
    }
    header('Location: premium_pesquisas.php?campaign_id=' . $campaignId);
    exit;
}

// ---------------------------------------------------------------------------
// Carrega pesquisas salvas
// ---------------------------------------------------------------------------
$pesquisas = premium_get_pesquisas($conn, $campaignId);

// ---------------------------------------------------------------------------
// Helpers de formatação
// ---------------------------------------------------------------------------
function pesq_fmt_int(int $v): string   { return number_format($v, 0, ',', '.'); }
function pesq_fmt_pct(float $v): string { return number_format($v, 2, ',', '.') . '%'; }
function pesq_delta_class(float $d): string {
    if ($d > 0) return 'delta--pos';
    if ($d < 0) return 'delta--neg';
    return 'delta--zero';
}
function pesq_delta_label(float $d): string {
    $s = number_format(abs($d), 2, ',', '.');
    if ($d > 0) return "▲ +{$s}pp";
    if ($d < 0) return "▼ -{$s}pp";
    return "= 0pp";
}
function pesq_votos_delta(int $d): string {
    $s = pesq_fmt_int(abs($d));
    if ($d > 0) return "+{$s}";
    if ($d < 0) return "-{$s}";
    return "0";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <script src="assets/js/premium-bootstrap.js"></script>
    <title>Pesquisas Eleitorais | Apoia Candidato</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/premium.css">
    <style>
        .pesq-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .pesq-form-grid--full { grid-column:1/-1; }
        @media(max-width:640px){ .pesq-form-grid { grid-template-columns:1fr; } }

        .pesq-card { background:var(--panel-bg); border:1px solid var(--panel-border); border-radius:14px; padding:24px 28px; margin-bottom:20px; }
        .pesq-card__header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:20px; }
        .pesq-card__meta { font-size:.78rem; color:var(--muted); margin-top:4px; display:flex; gap:12px; flex-wrap:wrap; }
        .pesq-card__obs { font-size:.82rem; color:var(--muted); font-style:italic; margin-top:8px; }

        .pesq-table { width:100%; border-collapse:collapse; font-size:.875rem; }
        .pesq-table th { text-align:left; padding:10px 14px; border-bottom:2px solid var(--line); font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); }
        .pesq-table td { padding:10px 14px; border-bottom:1px solid var(--line); color:var(--text); }
        .pesq-table tr:last-child td { border-bottom:none; }
        .pesq-table td.num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
        .pesq-table td.label { color:var(--muted); font-size:.8rem; }

        .delta--pos { color:#10b981; font-weight:700; }
        .delta--neg { color:#ef4444; font-weight:700; }
        .delta--zero { color:var(--muted); font-weight:600; }

        .badge-tipo { display:inline-block; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; }
        .badge-tipo--estadual { background:rgba(14,165,233,.15); color:#0ea5e9; }
        .badge-tipo--municipal { background:rgba(139,92,246,.15); color:#8b5cf6; }

        .pesq-empty { text-align:center; padding:48px 24px; color:var(--muted); }
        .pesq-empty h3 { font-size:1.1rem; margin-bottom:8px; color:var(--text); }

        .form-field label { display:block; font-size:.8rem; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px; }
        .form-field input, .form-field select, .form-field textarea {
            width:100%; padding:10px 14px; border:1px solid var(--line); border-radius:8px;
            font-size:.9rem; background:var(--panel); color:var(--text); font-family:inherit;
            box-sizing:border-box;
        }
        .form-field textarea { resize:vertical; min-height:72px; }
        .form-field input:focus, .form-field select:focus, .form-field textarea:focus {
            outline:none; border-color:var(--accent);
        }
    </style>
</head>
<body>
<div class="shell">
    <header class="topbar">
        <div>
            <div class="eyebrow">Apoia Candidato Premium</div>
            <h1>Pesquisas Eleitorais</h1>
            <p class="muted"><?= premium_escape_html((string) ($campaign['candidate_name'] ?? '')) ?> · <?= premium_escape_html((string) ($campaign['campaign_name'] ?? '')) ?></p>
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
                <a class="btn comparison-cta" href="premium?campaign_id=<?= $campaignId ?>">Voltar ao painel</a>
                <a class="btn ghost" href="premium_logout.php">Sair</a>
            </div>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="flash flash--<?= premium_escape_html($flash['type']) ?>">
            <?= premium_escape_html($flash['message']) ?>
        </div>
    <?php endif; ?>

    <main class="content-area" style="max-width:860px;margin:0 auto;">

        <!-- ================================================================
             Formulário de nova pesquisa
        ================================================================ -->
        <section class="panel" style="margin-bottom:28px;">
            <div class="section-title">
                <div>
                    <div class="eyebrow">Nova pesquisa</div>
                    <h2>Registrar pesquisa eleitoral</h2>
                </div>
            </div>
            <p class="panel-note">Informe os dados da pesquisa. O sistema calculará o comparativo com a projeção 2026 automaticamente.</p>

            <form method="POST" action="premium_pesquisas.php?campaign_id=<?= $campaignId ?>" style="margin-top:20px;" novalidate>
                <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                <input type="hidden" name="action" value="save">

                <div class="pesq-form-grid">
                    <div class="form-field">
                        <label for="instituto">Instituto / Fonte</label>
                        <input type="text" id="instituto" name="instituto" placeholder="Ex: Datafolha, pesquisa interna…" maxlength="150" required>
                    </div>
                    <div class="form-field">
                        <label for="data_pesquisa">Data da pesquisa</label>
                        <input type="date" id="data_pesquisa" name="data_pesquisa" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-field">
                        <label for="tipo">Abrangência</label>
                        <select id="tipo" name="tipo" onchange="pesqToggleMunicipio(this.value)">
                            <option value="estadual">Estadual (Sergipe)</option>
                            <option value="municipal">Municipal</option>
                        </select>
                    </div>
                    <div class="form-field" id="municipio-field" style="display:none;">
                        <label for="cd_municipio">Município</label>
                        <select id="cd_municipio" name="cd_municipio">
                            <option value="">Selecione…</option>
                            <?php foreach ($municipiosRows as $m): ?>
                                <option value="<?= (int) $m['cd_municipio'] ?>"
                                        data-nm="<?= premium_escape_html((string) $m['nm_municipio']) ?>">
                                    <?= premium_escape_html((string) $m['nm_municipio']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" id="nm_municipio" name="nm_municipio">
                    </div>

                    <div class="form-field">
                        <label for="pct_candidato">% do candidato na pesquisa</label>
                        <input type="number" id="pct_candidato" name="pct_candidato"
                               min="0.01" max="100" step="0.01" placeholder="Ex: 8,5" required>
                    </div>
                    <div class="form-field pesq-form-grid--full">
                        <label for="observacoes">Observações (opcional)</label>
                        <textarea id="observacoes" name="observacoes" placeholder="Metodologia, margem de erro, público-alvo…"></textarea>
                    </div>
                </div>

                <div style="margin-top:20px;display:flex;justify-content:flex-end;">
                    <button type="submit" class="btn comparison-cta">Registrar pesquisa</button>
                </div>
            </form>
        </section>

        <!-- ================================================================
             Lista de pesquisas + comparativos
        ================================================================ -->
        <section>
            <div style="margin-bottom:20px;">
                <div class="eyebrow">Histórico</div>
                <h2 style="margin-top:4px;">Pesquisas registradas</h2>
            </div>

            <?php if (!$pesquisas): ?>
                <div class="panel pesq-empty">
                    <h3>Nenhuma pesquisa registrada ainda</h3>
                    <p>Use o formulário acima para registrar a primeira pesquisa desta campanha.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pesquisas as $pesq): ?>
                    <?php
                    $comp = ($temForecast && $temPerfil)
                        ? premium_comparar_pesquisa($pesq, $forecast, array_values($municipiosPorCd))
                        : null;
                    $tipoLabel = $pesq['tipo'] === 'estadual' ? 'Estadual' : 'Municipal';
                    $tipoClass = 'badge-tipo--' . $pesq['tipo'];
                    $dataFmt = date('d/m/Y', strtotime((string) $pesq['data_pesquisa']));
                    ?>
                    <div class="pesq-card">
                        <div class="pesq-card__header">
                            <div>
                                <h3 style="margin:0;font-size:1.05rem;"><?= premium_escape_html((string) $pesq['instituto']) ?></h3>
                                <div class="pesq-card__meta">
                                    <span class="badge-tipo <?= $tipoClass ?>"><?= $tipoLabel ?></span>
                                    <?php if ($pesq['tipo'] === 'municipal' && $pesq['nm_municipio']): ?>
                                        <span><?= premium_escape_html((string) $pesq['nm_municipio']) ?></span>
                                    <?php endif; ?>
                                    <span><?= $dataFmt ?></span>
                                </div>
                                <?php if (!empty($pesq['observacoes'])): ?>
                                    <p class="pesq-card__obs"><?= premium_escape_html((string) $pesq['observacoes']) ?></p>
                                <?php endif; ?>
                            </div>
                            <form method="POST" action="premium_pesquisas.php?campaign_id=<?= $campaignId ?>"
                                  onsubmit="return confirm('Excluir esta pesquisa?')">
                                <input type="hidden" name="csrf" value="<?= premium_escape_html($csrf) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="pesquisa_id" value="<?= (int) $pesq['id'] ?>">
                                <button type="submit" class="btn ghost" style="padding:4px 12px;font-size:.8rem;">Excluir</button>
                            </form>
                        </div>

                        <?php if ($comp && !isset($comp['erro'])): ?>
                            <table class="pesq-table">
                                <thead>
                                    <tr>
                                        <th>Indicador</th>
                                        <th class="num">Pesquisa</th>
                                        <th class="num">Projeção 2026</th>
                                        <th class="num">Delta</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="label">Total de eleitores (universo)</td>
                                        <td class="num"><?= pesq_fmt_int($comp['total_eleitores']) ?></td>
                                        <td class="num"><?= pesq_fmt_int($comp['total_eleitores']) ?></td>
                                        <td class="num">—</td>
                                    </tr>
                                    <tr>
                                        <td class="label">Votos <?= (int) ($campaign['baseline_year'] ?? 2022) ?></td>
                                        <td class="num">—</td>
                                        <td class="num"><?= pesq_fmt_int($comp['votos_baseline']) ?></td>
                                        <td class="num">—</td>
                                    </tr>
                                    <tr>
                                        <td class="label">Votos estimados</td>
                                        <td class="num"><?= pesq_fmt_int($comp['votos_pesquisa']) ?></td>
                                        <td class="num"><?= pesq_fmt_int($comp['votos_projecao']) ?></td>
                                        <td class="num <?= pesq_delta_class($comp['delta_votos']) ?>">
                                            <?= pesq_votos_delta($comp['delta_votos']) ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label">Percentual sobre eleitorado</td>
                                        <td class="num"><?= pesq_fmt_pct($comp['pct_pesquisa']) ?></td>
                                        <td class="num"><?= pesq_fmt_pct($comp['pct_projecao']) ?></td>
                                        <td class="num <?= pesq_delta_class($comp['delta_pp']) ?>">
                                            <?= pesq_delta_label($comp['delta_pp']) ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <?php
                            $d = $comp['delta_pp'];
                            if ($d > 2): ?>
                                <p class="panel-note" style="margin-top:14px;color:#10b981;">
                                    A pesquisa supera a projeção em <?= pesq_fmt_pct(abs($d)) ?> — sinal positivo de crescimento acima do esperado.
                                </p>
                            <?php elseif ($d < -2): ?>
                                <p class="panel-note" style="margin-top:14px;color:#ef4444;">
                                    A pesquisa está <?= pesq_fmt_pct(abs($d)) ?> abaixo da projeção — revisar estratégia de lideranças e presença territorial.
                                </p>
                            <?php else: ?>
                                <p class="panel-note" style="margin-top:14px;">
                                    Pesquisa alinhada com a projeção (variação de <?= pesq_fmt_pct(abs($d)) ?>).
                                </p>
                            <?php endif; ?>

                        <?php elseif ($comp && isset($comp['erro'])): ?>
                            <p class="panel-note" style="color:#ef4444;"><?= premium_escape_html($comp['erro']) ?></p>
                        <?php elseif (!$temForecast): ?>
                            <p class="panel-note">Cadastre lideranças ou dados de base para gerar a projeção 2026.</p>
                        <?php elseif (!$temPerfil): ?>
                            <p class="panel-note">Importe o perfil eleitoral (TSE) para habilitar o comparativo.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

    </main>
</div>

<script>
function pesqToggleMunicipio(tipo) {
    const field = document.getElementById('municipio-field');
    field.style.display = tipo === 'municipal' ? 'block' : 'none';
    const sel = document.getElementById('cd_municipio');
    if (tipo !== 'municipal') sel.value = '';
}

document.getElementById('cd_municipio')?.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    document.getElementById('nm_municipio').value = opt.dataset.nm || '';
});
</script>
</body>
</html>
