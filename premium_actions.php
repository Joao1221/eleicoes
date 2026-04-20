<?php

declare(strict_types=1);

require_once __DIR__ . '/premium_helpers.php';

$user = premium_require_user($conn);
$isAdmin = premium_is_admin_user($user);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    premium_flash('error', 'Método inválido.');
    header('Location: premium');
    exit;
}

if (!premium_validate_csrf($_POST['csrf'] ?? null)) {
    premium_flash('error', 'A sessão premium expirou. Recarregue a página e tente novamente.');
    header('Location: premium');
    exit;
}

$action = trim((string) ($_POST['action'] ?? ''));
$selectedCampaignId = (int) ($_POST['campaign_id'] ?? ($_SESSION['premium_campaign_id'] ?? 0));

$redirectToCampaign = static function (int $campaignId = 0): void {
    $url = 'premium';
    if ($campaignId > 0) {
        premium_set_active_campaign($campaignId);
        $url .= '?campaign_id=' . $campaignId;
    }
    header('Location: ' . $url);
    exit;
};

$campaign = $selectedCampaignId > 0 ? premium_get_campaign($conn, $selectedCampaignId, (int) $user['id']) : null;
if (!$campaign && in_array($action, ['select_campaign', 'create_campaign'], true) === false) {
    $campaign = premium_active_campaign($conn, (int) $user['id']);
    if ($campaign) {
        $selectedCampaignId = (int) $campaign['id'];
    }
}

if (in_array($action, ['create_premium_user', 'toggle_premium_user_status'], true) && !$isAdmin) {
    premium_flash('error', 'Você não tem permissão para gerenciar usuários premium.');
    $redirectToCampaign($selectedCampaignId);
}

function premium_batch_leader_key(array $row): string
{
    $sourceSq = trim((string) ($row['source_sq_candidato'] ?? ''));
    if ($sourceSq !== '') {
        return 'sq:' . premium_normalize_text($sourceSq) . '|turno:' . (int) ($row['source_turno'] ?? 1);
    }

    return implode('|', [
        premium_normalize_text((string) ($row['municipality'] ?? '')),
        premium_normalize_text((string) ($row['leader_name'] ?? '')),
        premium_normalize_text((string) ($row['leader_cargo'] ?? '')),
        'nr:' . (int) ($row['source_nr_votavel'] ?? 0),
        'turno:' . (int) ($row['source_turno'] ?? 1),
    ]);
}

function premium_normalize_batch_leader_row(array $row, string $defaultCargo, array $settings): ?array
{
    $municipality = trim((string) ($row['municipality'] ?? ''));
    $leaderName = trim((string) ($row['leader_name'] ?? ''));
    $leaderCargo = trim((string) ($row['leader_cargo'] ?? $defaultCargo));

    if ($municipality === '' || $leaderName === '' || $leaderCargo === '') {
        return null;
    }

    $regionName = trim((string) ($row['region_name'] ?? ''));
    if ($regionName === '') {
        $regionName = premium_region_for_city($municipality) ?? 'Sem região';
    }

    $leaderParty = trim((string) ($row['leader_party'] ?? ''));
    $sourceSq = trim((string) ($row['source_sq_candidato'] ?? ''));
    $sourceNrVotavel = trim((string) ($row['source_nr_votavel'] ?? ''));
    $sourceTurno = max(1, (int) ($row['source_turno'] ?? 1));
    $leaderVotes = max(0, (int) ($row['leader_votes_2024'] ?? 0));
    $marginPercent = max(0, (float) ($row['margin_percent'] ?? 0));
    $sizeClass = trim((string) ($row['size_class'] ?? 'medium'));
    if (!in_array($sizeClass, ['small', 'medium', 'large'], true)) {
        $sizeClass = 'medium';
    }

    return [
        'region_name' => $regionName,
        'municipality' => $municipality,
        'leader_name' => $leaderName,
        'leader_cargo' => $leaderCargo,
        'leader_party' => $leaderParty !== '' ? $leaderParty : null,
        'source_sq_candidato' => $sourceSq !== '' ? $sourceSq : null,
        'source_nr_votavel' => $sourceNrVotavel !== '' ? (int) $sourceNrVotavel : null,
        'source_turno' => $sourceTurno,
        'leader_votes_2024' => $leaderVotes,
        'margin_percent' => $marginPercent,
        'transfer_rate' => (float) ($settings['transfer_rate_default'] ?? premium_default_settings()['transfer_rate_default']),
        'aligned_with_executive' => 0,
        'visibility_score' => 50,
        'investment_score' => 50,
        'size_class' => $sizeClass,
        'notes' => null,
    ];
}

function premium_batch_leader_exists(mysqli $conn, int $campaignId, array $leader): bool
{
    $conditions = [];
    $sourceSq = trim((string) ($leader['source_sq_candidato'] ?? ''));
    if ($sourceSq !== '') {
        $conditions[] = 'source_sq_candidato = ' . premium_sql_quote($conn, $sourceSq);
    }

    $fallbackConditions = [
        'municipality = ' . premium_sql_quote($conn, (string) ($leader['municipality'] ?? '')),
        'leader_name = ' . premium_sql_quote($conn, (string) ($leader['leader_name'] ?? '')),
        'leader_cargo = ' . premium_sql_quote($conn, (string) ($leader['leader_cargo'] ?? '')),
        'source_turno = ' . (int) ($leader['source_turno'] ?? 1),
    ];

    $sourceNrVotavel = $leader['source_nr_votavel'] ?? null;
    if ($sourceNrVotavel !== null && $sourceNrVotavel !== '') {
        $fallbackConditions[] = 'source_nr_votavel = ' . (int) $sourceNrVotavel;
    }

    $conditions[] = '(' . implode(' AND ', $fallbackConditions) . ')';

    $row = querySingle($conn, "
        SELECT id
        FROM premium_campaign_leaders
        WHERE campaign_id = " . (int) $campaignId . "
          AND (" . implode(' OR ', $conditions) . ")
        LIMIT 1
    ");

    return !empty($row);
}

switch ($action) {
    case 'create_premium_user':
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
        $status = trim((string) ($_POST['status'] ?? 'active'));

        if ($name === '' || $email === '' || $password === '' || $passwordConfirm === '') {
            premium_flash('error', 'Preencha nome, e-mail e senha para cadastrar o usuário.');
            $redirectToCampaign($selectedCampaignId);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            premium_flash('error', 'Informe um e-mail válido para o novo usuário.');
            $redirectToCampaign($selectedCampaignId);
        }

        if (strlen($password) < 8) {
            premium_flash('error', 'A senha precisa ter pelo menos 8 caracteres.');
            $redirectToCampaign($selectedCampaignId);
        }

        if ($password !== $passwordConfirm) {
            premium_flash('error', 'A confirmação de senha não confere.');
            $redirectToCampaign($selectedCampaignId);
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $existing = querySingle($conn, "
            SELECT id
            FROM premium_users
            WHERE email = " . premium_sql_quote($conn, $email) . "
            LIMIT 1
        ");

        if (!empty($existing)) {
            premium_flash('error', 'Já existe um usuário premium com este e-mail.');
            $redirectToCampaign($selectedCampaignId);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            premium_flash('error', 'Não foi possível gerar a senha do usuário.');
            $redirectToCampaign($selectedCampaignId);
        }

        $conn->query("
            INSERT INTO premium_users (
                name,
                email,
                password_hash,
                status
            ) VALUES (
                " . premium_sql_quote($conn, $name) . ",
                " . premium_sql_quote($conn, $email) . ",
                " . premium_sql_quote($conn, $passwordHash) . ",
                " . premium_sql_quote($conn, $status) . "
            )
        ");

        if ($conn->errno) {
            premium_flash('error', 'Não foi possível cadastrar o usuário premium.');
        } else {
            premium_flash('success', 'Usuário premium cadastrado com sucesso.');
        }

        $redirectToCampaign($selectedCampaignId);
        break;

    case 'toggle_premium_user_status':
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $targetStatus = trim((string) ($_POST['target_status'] ?? ''));

        if ($targetUserId <= 0 || !in_array($targetStatus, ['active', 'inactive'], true)) {
            premium_flash('error', 'Usuário ou status inválido.');
            $redirectToCampaign($selectedCampaignId);
        }

        if ($targetUserId === (int) $user['id']) {
            premium_flash('error', 'Você não pode alterar o status da sua própria conta por aqui.');
            $redirectToCampaign($selectedCampaignId);
        }

        $targetUser = querySingle($conn, "
            SELECT id, name, status
            FROM premium_users
            WHERE id = " . (int) $targetUserId . "
            LIMIT 1
        ");

        if (!$targetUser) {
            premium_flash('error', 'Usuário premium não encontrado.');
            $redirectToCampaign($selectedCampaignId);
        }

        if ((string) ($targetUser['status'] ?? '') === $targetStatus) {
            premium_flash('warning', 'O usuário já está com esse status.');
            $redirectToCampaign($selectedCampaignId);
        }

        $conn->query("
            UPDATE premium_users
            SET status = " . premium_sql_quote($conn, $targetStatus) . "
            WHERE id = " . (int) $targetUserId . "
            LIMIT 1
        ");

        if ($conn->errno) {
            premium_flash('error', 'Não foi possível atualizar o status do usuário.');
        } else {
            premium_flash('success', 'Status do usuário atualizado com sucesso.');
        }

        $redirectToCampaign($selectedCampaignId);
        break;
    case 'create_campaign':
        $campaignName = trim((string) ($_POST['campaign_name'] ?? ''));
        $candidateName = trim((string) ($_POST['candidate_name'] ?? ''));
        $candidateCargo = trim((string) ($_POST['candidate_cargo'] ?? ''));
        $baselineYear = (int) ($_POST['baseline_year'] ?? 2022);
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if ($campaignName === '' || $candidateName === '' || $candidateCargo === '') {
            premium_flash('error', 'Informe o nome da campanha, o candidato e o cargo.');
            $redirectToCampaign($selectedCampaignId);
        }

        $conn->query("
            INSERT INTO premium_campaigns (
                user_id,
                campaign_name,
                candidate_name,
                candidate_cargo,
                baseline_year,
                notes
            ) VALUES (
                " . (int) $user['id'] . ",
                " . premium_sql_quote($conn, $campaignName) . ",
                " . premium_sql_quote($conn, $candidateName) . ",
                " . premium_sql_quote($conn, $candidateCargo) . ",
                " . max(2022, $baselineYear) . ",
                " . premium_sql_quote($conn, $notes !== '' ? $notes : null) . "
            )
        ");

        if ($conn->errno) {
            premium_flash('error', 'Não foi possível criar a campanha.');
            $redirectToCampaign($selectedCampaignId);
        }

        $campaignId = (int) $conn->insert_id;
        $settingsJson = json_encode(premium_default_settings(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $conn->query("
            INSERT INTO premium_campaign_settings (campaign_id, settings_json)
            VALUES (
                {$campaignId},
                " . premium_sql_quote($conn, $settingsJson) . "
            )
        ");

        premium_flash('success', 'Campanha premium criada com sucesso.');
        $redirectToCampaign($campaignId);
        break;

    case 'select_campaign':
        if ($selectedCampaignId <= 0 || !premium_get_campaign($conn, $selectedCampaignId, (int) $user['id'])) {
            premium_flash('error', 'Campanha inválida.');
            $redirectToCampaign($selectedCampaignId);
        }

        premium_flash('success', 'Campanha carregada.');
        $redirectToCampaign($selectedCampaignId);
        break;

    case 'delete_campaign':
        $campaignIdToDelete = (int) ($_POST['campaign_id'] ?? 0);
        $activeCampaignId = (int) ($_SESSION['premium_campaign_id'] ?? 0);

        if ($campaignIdToDelete <= 0) {
            premium_flash('error', 'Campanha inválida.');
            $redirectToCampaign($activeCampaignId);
        }

        $targetCampaign = querySingle($conn, "
            SELECT id, user_id, campaign_name
            FROM premium_campaigns
            WHERE id = " . (int) $campaignIdToDelete . "
            LIMIT 1
        ");

        if (!$targetCampaign) {
            premium_flash('error', 'Campanha não encontrada.');
            $redirectToCampaign($activeCampaignId);
        }

        $targetCampaignUserId = (int) ($targetCampaign['user_id'] ?? 0);
        if (!$isAdmin && $targetCampaignUserId !== (int) $user['id']) {
            premium_flash('error', 'Você não tem permissão para excluir esta campanha.');
            $redirectToCampaign($activeCampaignId);
        }

        if (!premium_delete_campaign($conn, $campaignIdToDelete)) {
            premium_flash('error', 'Não foi possível excluir a campanha.');
            $redirectToCampaign($activeCampaignId);
        }

        $redirectCampaignId = $activeCampaignId;
        if ($activeCampaignId === $campaignIdToDelete) {
            $remainingCampaigns = premium_get_campaigns($conn, (int) $user['id']);
            $redirectCampaignId = (int) ($remainingCampaigns[0]['id'] ?? 0);
            if ($redirectCampaignId > 0) {
                premium_set_active_campaign($redirectCampaignId);
            } else {
                premium_clear_active_campaign();
            }
        }

        premium_flash('success', 'Campanha premium excluída com sucesso.');
        if ($redirectCampaignId > 0) {
            $redirectToCampaign($redirectCampaignId);
        }

        premium_clear_active_campaign();
        header('Location: premium');
        exit;

    case 'add_leaders_batch':
        if ($selectedCampaignId <= 0 || !$campaign) {
            premium_flash('error', 'Selecione uma campanha antes de adicionar lideranças.');
            $redirectToCampaign();
        }

        $leadersJson = trim((string) ($_POST['leaders_json'] ?? ''));
        $batchCargo = trim((string) ($_POST['batch_cargo'] ?? ''));
        if ($leadersJson === '') {
            premium_flash('error', 'Selecione pelo menos uma liderança antes de adicionar ao escritório.');
            $redirectToCampaign($selectedCampaignId);
        }

        $decoded = json_decode($leadersJson, true);
        if (!is_array($decoded) || !$decoded) {
            premium_flash('error', 'Não foi possível ler a seleção em lote.');
            $redirectToCampaign($selectedCampaignId);
        }

        $settings = premium_load_campaign_settings($conn, $selectedCampaignId);
        $preparedLeaders = [];
        $seenKeys = [];
        $skippedInvalid = 0;
        $skippedDuplicate = 0;

        foreach ($decoded as $item) {
            if (!is_array($item)) {
                $skippedInvalid++;
                continue;
            }

            $normalized = premium_normalize_batch_leader_row($item, $batchCargo, $settings);
            if (!$normalized) {
                $skippedInvalid++;
                continue;
            }

            $key = premium_batch_leader_key($normalized);
            if (isset($seenKeys[$key])) {
                $skippedDuplicate++;
                continue;
            }

            if (premium_batch_leader_exists($conn, $selectedCampaignId, $normalized)) {
                $skippedDuplicate++;
                continue;
            }

            $seenKeys[$key] = true;
            $preparedLeaders[] = $normalized;
        }

        if (!$preparedLeaders) {
            if ($skippedDuplicate > 0 || $skippedInvalid > 0) {
                premium_flash('warning', 'Nenhuma liderança nova para adicionar. A seleção já existia ou estava incompleta.');
            } else {
                premium_flash('warning', 'Nenhuma liderança válida foi selecionada.');
            }
            $redirectToCampaign($selectedCampaignId);
        }

        $inserted = 0;
        $conn->begin_transaction();

        try {
            foreach ($preparedLeaders as $leader) {
                $conn->query("
                    INSERT INTO premium_campaign_leaders (
                        campaign_id,
                        region_name,
                        municipality,
                        leader_name,
                        leader_cargo,
                        leader_party,
                        source_sq_candidato,
                        source_nr_votavel,
                        source_turno,
                        leader_votes_2024,
                        margin_percent,
                        transfer_rate,
                        aligned_with_executive,
                        visibility_score,
                        investment_score,
                        size_class,
                        notes
                    ) VALUES (
                        " . (int) $selectedCampaignId . ",
                        " . premium_sql_quote($conn, (string) ($leader['region_name'] ?? 'Sem região')) . ",
                        " . premium_sql_quote($conn, (string) ($leader['municipality'] ?? '')) . ",
                        " . premium_sql_quote($conn, (string) ($leader['leader_name'] ?? '')) . ",
                        " . premium_sql_quote($conn, (string) ($leader['leader_cargo'] ?? '')) . ",
                        " . premium_sql_quote($conn, $leader['leader_party'] !== null ? (string) $leader['leader_party'] : null) . ",
                        " . premium_sql_quote($conn, $leader['source_sq_candidato'] !== null ? (string) $leader['source_sq_candidato'] : null) . ",
                        " . ($leader['source_nr_votavel'] !== null ? (int) $leader['source_nr_votavel'] : 'NULL') . ",
                        " . (int) ($leader['source_turno'] ?? 1) . ",
                        " . (int) ($leader['leader_votes_2024'] ?? 0) . ",
                        " . number_format((float) ($leader['margin_percent'] ?? 0), 2, '.', '') . ",
                        " . number_format((float) ($leader['transfer_rate'] ?? 40), 2, '.', '') . ",
                        " . (int) ($leader['aligned_with_executive'] ?? 0) . ",
                        " . number_format((float) ($leader['visibility_score'] ?? 50), 2, '.', '') . ",
                        " . number_format((float) ($leader['investment_score'] ?? 50), 2, '.', '') . ",
                        " . premium_sql_quote($conn, (string) ($leader['size_class'] ?? 'medium')) . ",
                        " . premium_sql_quote($conn, $leader['notes'] !== null ? (string) $leader['notes'] : null) . "
                    )
                ");

                if ($conn->errno) {
                    throw new RuntimeException($conn->error);
                }

                $inserted++;
            }

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            premium_flash('error', 'Não foi possível salvar o lote de lideranças.');
            $redirectToCampaign($selectedCampaignId);
        }

        $messageParts = [];
        $messageParts[] = $inserted === 1 ? '1 liderança adicionada' : $inserted . ' lideranças adicionadas';
        if ($skippedDuplicate > 0) {
            $messageParts[] = $skippedDuplicate . ' duplicada' . ($skippedDuplicate === 1 ? '' : 's') . ' ignorada' . ($skippedDuplicate === 1 ? '' : 's');
        }
        if ($skippedInvalid > 0) {
            $messageParts[] = $skippedInvalid . ' inválida' . ($skippedInvalid === 1 ? '' : 's') . ' ignorada' . ($skippedInvalid === 1 ? '' : 's');
        }

        premium_flash('success', implode('; ', $messageParts) . '.');
        $redirectToCampaign($selectedCampaignId);
        break;

    case 'save_baseline':
        if ($selectedCampaignId <= 0 || !$campaign) {
            premium_flash('error', 'Selecione uma campanha antes de salvar o baseline.');
            $redirectToCampaign();
        }

        $candidateName = trim((string) ($_POST['candidate_name'] ?? ''));
        $candidateCargo = trim((string) ($_POST['candidate_cargo'] ?? ''));
        $baselineYear = (int) ($_POST['baseline_year'] ?? 2022);
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $currentMunicipio = trim((string) ($_POST['current_municipio'] ?? ''));
        $currentRegion = trim((string) ($_POST['current_region'] ?? ''));

        $conn->query("
            UPDATE premium_campaigns
            SET candidate_name = " . premium_sql_quote($conn, $candidateName) . ",
                candidate_cargo = " . premium_sql_quote($conn, $candidateCargo) . ",
                baseline_year = " . max(2022, $baselineYear) . ",
                baseline_panel_hidden = 1,
                notes = " . premium_sql_quote($conn, $notes !== '' ? $notes : null) . ",
                current_municipio = " . premium_sql_quote($conn, $currentMunicipio !== '' ? $currentMunicipio : null) . ",
                current_region = " . premium_sql_quote($conn, $currentRegion !== '' ? $currentRegion : null) . "
            WHERE id = " . (int) $selectedCampaignId . "
              AND user_id = " . (int) $user['id'] . "
            LIMIT 1
        ");

        if ($conn->errno) {
            premium_flash('error', 'Não foi possível atualizar o baseline.');
        } else {
            premium_flash('success', 'Baseline atualizado.');
        }

        $redirectToCampaign($selectedCampaignId);
        break;

    case 'save_settings':
        if ($selectedCampaignId <= 0 || !$campaign) {
            premium_flash('error', 'Selecione uma campanha antes de salvar os pesos.');
            $redirectToCampaign();
        }

        $settings = premium_default_settings();
        foreach ($settings as $key => $value) {
            if (isset($_POST[$key]) && $_POST[$key] !== '') {
                $settings[$key] = is_float($value) ? (float) $_POST[$key] : (int) $_POST[$key];
            }
        }

        $settingsJson = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $conn->query("
            INSERT INTO premium_campaign_settings (campaign_id, settings_json)
            VALUES (
                " . (int) $selectedCampaignId . ",
                " . premium_sql_quote($conn, $settingsJson) . "
            )
            ON DUPLICATE KEY UPDATE
                settings_json = VALUES(settings_json),
                updated_at = CURRENT_TIMESTAMP
        ");

        $conn->query("
            UPDATE premium_campaigns
            SET settings_panel_hidden = 1
            WHERE id = " . (int) $selectedCampaignId . "
              AND user_id = " . (int) $user['id'] . "
            LIMIT 1
        ");

        if ($conn->errno) {
            premium_flash('error', 'Não foi possível salvar os pesos do modelo.');
        } else {
            premium_flash('success', 'Pesos do modelo salvos.');
        }

        $redirectToCampaign($selectedCampaignId);
        break;

    case 'show_baseline_panel':
        if ($selectedCampaignId <= 0 || !$campaign) {
            premium_flash('error', 'Selecione uma campanha antes de reabrir o baseline.');
            $redirectToCampaign();
        }

        $conn->query("
            UPDATE premium_campaigns
            SET baseline_panel_hidden = 0
            WHERE id = " . (int) $selectedCampaignId . "
              AND user_id = " . (int) $user['id'] . "
            LIMIT 1
        ");

        if ($conn->errno) {
            premium_flash('error', 'Não foi possível reabrir o baseline.');
        } else {
            premium_flash('success', 'Baseline reaberto.');
        }

        $redirectToCampaign($selectedCampaignId);
        break;

    case 'show_settings_panel':
        if ($selectedCampaignId <= 0 || !$campaign) {
            premium_flash('error', 'Selecione uma campanha antes de reabrir os pesos.');
            $redirectToCampaign();
        }

        $conn->query("
            UPDATE premium_campaigns
            SET settings_panel_hidden = 0
            WHERE id = " . (int) $selectedCampaignId . "
              AND user_id = " . (int) $user['id'] . "
            LIMIT 1
        ");

        if ($conn->errno) {
            premium_flash('error', 'Não foi possível reabrir os pesos.');
        } else {
            premium_flash('success', 'Pesos reabertos.');
        }

        $redirectToCampaign($selectedCampaignId);
        break;

    case 'add_leader':
        if ($selectedCampaignId <= 0 || !$campaign) {
            premium_flash('error', 'Selecione uma campanha antes de adicionar lideranças.');
            $redirectToCampaign();
        }

        $municipality = trim((string) ($_POST['municipality'] ?? ''));
        $leaderName = trim((string) ($_POST['leader_name'] ?? ''));
        $leaderCargo = trim((string) ($_POST['leader_cargo'] ?? ''));
        $leaderParty = trim((string) ($_POST['leader_party'] ?? ''));
        $regionName = trim((string) ($_POST['region_name'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $sourceSq = trim((string) ($_POST['source_sq_candidato'] ?? ''));
        $sourceNrVotavel = trim((string) ($_POST['source_nr_votavel'] ?? ''));
        $sourceTurno = max(1, (int) ($_POST['source_turno'] ?? 1));
        $leaderVotes = (int) ($_POST['leader_votes_2024'] ?? 0);
        $marginPercent = (float) ($_POST['margin_percent'] ?? 0);
        $transferRate = (float) ($_POST['transfer_rate'] ?? premium_default_settings()['transfer_rate_default']);
        $aligned = isset($_POST['aligned_with_executive']) ? 1 : 0;
        $visibilityScore = (float) ($_POST['visibility_score'] ?? 50);
        $investmentScore = (float) ($_POST['investment_score'] ?? 50);
        $sizeClass = trim((string) ($_POST['size_class'] ?? 'medium'));

        if ($municipality === '' || $leaderName === '' || $leaderCargo === '') {
            premium_flash('error', 'Informe município, nome da liderança e cargo.');
            $redirectToCampaign($selectedCampaignId);
        }

        if ($regionName === '') {
            $regionName = premium_region_for_city($municipality) ?? 'Sem região';
        }

        if (!in_array($sizeClass, ['small', 'medium', 'large'], true)) {
            $sizeClass = 'medium';
        }

        $conn->query("
            INSERT INTO premium_campaign_leaders (
                campaign_id,
                region_name,
                municipality,
                leader_name,
                leader_cargo,
                leader_party,
                source_sq_candidato,
                source_nr_votavel,
                source_turno,
                leader_votes_2024,
                margin_percent,
                transfer_rate,
                aligned_with_executive,
                visibility_score,
                investment_score,
                size_class,
                notes
            ) VALUES (
                " . (int) $selectedCampaignId . ",
                " . premium_sql_quote($conn, $regionName) . ",
                " . premium_sql_quote($conn, $municipality) . ",
                " . premium_sql_quote($conn, $leaderName) . ",
                " . premium_sql_quote($conn, $leaderCargo) . ",
                " . premium_sql_quote($conn, $leaderParty !== '' ? $leaderParty : null) . ",
                " . premium_sql_quote($conn, $sourceSq !== '' ? $sourceSq : null) . ",
                " . ($sourceNrVotavel !== '' ? (int) $sourceNrVotavel : 'NULL') . ",
                {$sourceTurno},
                {$leaderVotes},
                " . number_format($marginPercent, 2, '.', '') . ",
                " . number_format($transferRate, 2, '.', '') . ",
                {$aligned},
                " . number_format($visibilityScore, 2, '.', '') . ",
                " . number_format($investmentScore, 2, '.', '') . ",
                " . premium_sql_quote($conn, $sizeClass) . ",
                " . premium_sql_quote($conn, $notes !== '' ? $notes : null) . "
            )
        ");

        if ($conn->errno) {
            premium_flash('error', 'Não foi possível salvar a liderança.');
        } else {
            premium_flash('success', 'Liderança adicionada ao escritório de campanha.');
        }

        $redirectToCampaign($selectedCampaignId);
        break;

    case 'update_leader':
        if ($selectedCampaignId <= 0 || !$campaign) {
            premium_flash('error', 'Selecione uma campanha antes de atualizar lideranças.');
            $redirectToCampaign();
        }

        $leaderId = (int) ($_POST['leader_id'] ?? 0);
        if ($leaderId <= 0) {
            premium_flash('error', 'Liderança inválida.');
            $redirectToCampaign($selectedCampaignId);
        }

        $existing = querySingle($conn, "
            SELECT id
            FROM premium_campaign_leaders
            WHERE id = {$leaderId}
              AND campaign_id = " . (int) $selectedCampaignId . "
            LIMIT 1
        ");

        if (!$existing) {
            premium_flash('error', 'Liderança não encontrada.');
            $redirectToCampaign($selectedCampaignId);
        }

        $regionName = trim((string) ($_POST['region_name'] ?? ''));
        $municipality = trim((string) ($_POST['municipality'] ?? ''));
        $leaderName = trim((string) ($_POST['leader_name'] ?? ''));
        $leaderCargo = trim((string) ($_POST['leader_cargo'] ?? ''));
        $leaderParty = trim((string) ($_POST['leader_party'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $leaderVotes = (int) ($_POST['leader_votes_2024'] ?? 0);
        $marginPercent = (float) ($_POST['margin_percent'] ?? 0);
        $transferRate = (float) ($_POST['transfer_rate'] ?? premium_default_settings()['transfer_rate_default']);
        $aligned = isset($_POST['aligned_with_executive']) ? 1 : 0;
        $visibilityScore = (float) ($_POST['visibility_score'] ?? 50);
        $investmentScore = (float) ($_POST['investment_score'] ?? 50);
        $sizeClass = trim((string) ($_POST['size_class'] ?? 'medium'));

        if ($regionName === '') {
            $regionName = premium_region_for_city($municipality) ?? 'Sem região';
        }

        if (!in_array($sizeClass, ['small', 'medium', 'large'], true)) {
            $sizeClass = 'medium';
        }

        $conn->query("
            UPDATE premium_campaign_leaders
            SET region_name = " . premium_sql_quote($conn, $regionName) . ",
                municipality = " . premium_sql_quote($conn, $municipality) . ",
                leader_name = " . premium_sql_quote($conn, $leaderName) . ",
                leader_cargo = " . premium_sql_quote($conn, $leaderCargo) . ",
                leader_party = " . premium_sql_quote($conn, $leaderParty !== '' ? $leaderParty : null) . ",
                leader_votes_2024 = {$leaderVotes},
                margin_percent = " . number_format($marginPercent, 2, '.', '') . ",
                transfer_rate = " . number_format($transferRate, 2, '.', '') . ",
                aligned_with_executive = {$aligned},
                visibility_score = " . number_format($visibilityScore, 2, '.', '') . ",
                investment_score = " . number_format($investmentScore, 2, '.', '') . ",
                size_class = " . premium_sql_quote($conn, $sizeClass) . ",
                notes = " . premium_sql_quote($conn, $notes !== '' ? $notes : null) . "
            WHERE id = {$leaderId}
              AND campaign_id = " . (int) $selectedCampaignId . "
            LIMIT 1
        ");

        if ($conn->errno) {
            premium_flash('error', 'Não foi possível atualizar a liderança.');
        } else {
            premium_flash('success', 'Liderança atualizada.');
        }

        $redirectToCampaign($selectedCampaignId);
        break;

    case 'delete_leader':
        if ($selectedCampaignId <= 0 || !$campaign) {
            premium_flash('error', 'Selecione uma campanha antes de excluir lideranças.');
            $redirectToCampaign();
        }

        $leaderId = (int) ($_POST['leader_id'] ?? 0);
        if ($leaderId <= 0) {
            premium_flash('error', 'Liderança inválida.');
            $redirectToCampaign($selectedCampaignId);
        }

        $conn->query("
            DELETE FROM premium_campaign_leaders
            WHERE id = {$leaderId}
              AND campaign_id = " . (int) $selectedCampaignId . "
            LIMIT 1
        ");

        if ($conn->errno) {
            premium_flash('error', 'Não foi possível excluir a liderança.');
        } else {
            premium_flash('success', 'Liderança removida.');
        }

        $redirectToCampaign($selectedCampaignId);
        break;

    case 'add_agenda':
        if ($selectedCampaignId <= 0 || !$campaign) {
            premium_flash('error', 'Selecione uma campanha antes de adicionar tarefas.');
            $redirectToCampaign();
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $dueDate = trim((string) ($_POST['due_date'] ?? ''));
        $priority = trim((string) ($_POST['priority'] ?? 'medium'));
        $status = trim((string) ($_POST['status'] ?? 'open'));
        $municipality = trim((string) ($_POST['municipality'] ?? ''));
        $leaderName = trim((string) ($_POST['leader_name'] ?? ''));

        if ($title === '') {
            premium_flash('error', 'Informe um título para a tarefa.');
            $redirectToCampaign($selectedCampaignId);
        }

        if (!in_array($priority, ['low', 'medium', 'high', 'urgent'], true)) {
            $priority = 'medium';
        }

        if (!in_array($status, ['open', 'doing', 'done', 'archived'], true)) {
            $status = 'open';
        }

        $dueDateSql = $dueDate !== '' ? premium_sql_quote($conn, $dueDate) : 'NULL';
        $conn->query("
            INSERT INTO premium_agenda (
                campaign_id,
                title,
                description,
                due_date,
                priority,
                status,
                municipality,
                leader_name
            ) VALUES (
                " . (int) $selectedCampaignId . ",
                " . premium_sql_quote($conn, $title) . ",
                " . premium_sql_quote($conn, $description !== '' ? $description : null) . ",
                {$dueDateSql},
                " . premium_sql_quote($conn, $priority) . ",
                " . premium_sql_quote($conn, $status) . ",
                " . premium_sql_quote($conn, $municipality !== '' ? $municipality : null) . ",
                " . premium_sql_quote($conn, $leaderName !== '' ? $leaderName : null) . "
            )
        ");

        if ($conn->errno) {
            premium_flash('error', 'Não foi possível salvar a tarefa.');
        } else {
            premium_flash('success', 'Tarefa adicionada à agenda.');
        }

        $redirectToCampaign($selectedCampaignId);
        break;

    case 'update_agenda':
        if ($selectedCampaignId <= 0 || !$campaign) {
            premium_flash('error', 'Selecione uma campanha antes de editar tarefas.');
            $redirectToCampaign();
        }

        $agendaId = (int) ($_POST['agenda_id'] ?? 0);
        if ($agendaId <= 0) {
            premium_flash('error', 'Tarefa inválida.');
            $redirectToCampaign($selectedCampaignId);
        }

        $existing = querySingle($conn, "
            SELECT id
            FROM premium_agenda
            WHERE id = {$agendaId}
              AND campaign_id = " . (int) $selectedCampaignId . "
            LIMIT 1
        ");

        if (!$existing) {
            premium_flash('error', 'Tarefa não encontrada.');
            $redirectToCampaign($selectedCampaignId);
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $dueDate = trim((string) ($_POST['due_date'] ?? ''));
        $priority = trim((string) ($_POST['priority'] ?? 'medium'));
        $status = trim((string) ($_POST['status'] ?? 'open'));
        $municipality = trim((string) ($_POST['municipality'] ?? ''));
        $leaderName = trim((string) ($_POST['leader_name'] ?? ''));

        if ($title === '') {
            premium_flash('error', 'Informe um título para a tarefa.');
            $redirectToCampaign($selectedCampaignId);
        }

        if (!in_array($priority, ['low', 'medium', 'high', 'urgent'], true)) {
            $priority = 'medium';
        }

        if (!in_array($status, ['open', 'doing', 'done', 'archived'], true)) {
            $status = 'open';
        }

        $dueDateSql = $dueDate !== '' ? premium_sql_quote($conn, $dueDate) : 'NULL';
        $conn->query("
            UPDATE premium_agenda
            SET title = " . premium_sql_quote($conn, $title) . ",
                description = " . premium_sql_quote($conn, $description !== '' ? $description : null) . ",
                due_date = {$dueDateSql},
                priority = " . premium_sql_quote($conn, $priority) . ",
                status = " . premium_sql_quote($conn, $status) . ",
                municipality = " . premium_sql_quote($conn, $municipality !== '' ? $municipality : null) . ",
                leader_name = " . premium_sql_quote($conn, $leaderName !== '' ? $leaderName : null) . "
            WHERE id = {$agendaId}
              AND campaign_id = " . (int) $selectedCampaignId . "
            LIMIT 1
        ");

        if ($conn->errno) {
            premium_flash('error', 'Não foi possível atualizar a tarefa.');
        } else {
            premium_flash('success', 'Tarefa atualizada.');
        }

        $redirectToCampaign($selectedCampaignId);
        break;

    case 'archive_agenda':
        if ($selectedCampaignId <= 0 || !$campaign) {
            premium_flash('error', 'Selecione uma campanha antes de arquivar tarefas.');
            $redirectToCampaign();
        }

        $agendaId = (int) ($_POST['agenda_id'] ?? 0);
        if ($agendaId <= 0) {
            premium_flash('error', 'Tarefa inválida.');
            $redirectToCampaign($selectedCampaignId);
        }

        $existing = querySingle($conn, "
            SELECT id
            FROM premium_agenda
            WHERE id = {$agendaId}
              AND campaign_id = " . (int) $selectedCampaignId . "
            LIMIT 1
        ");

        if (!$existing) {
            premium_flash('error', 'Tarefa não encontrada.');
            $redirectToCampaign($selectedCampaignId);
        }

        $conn->query("
            UPDATE premium_agenda
            SET status = 'archived'
            WHERE id = {$agendaId}
              AND campaign_id = " . (int) $selectedCampaignId . "
            LIMIT 1
        ");

        if ($conn->errno) {
            premium_flash('error', 'Não foi possível arquivar a tarefa.');
        } else {
            premium_flash('success', 'Tarefa arquivada.');
        }

        $redirectToCampaign($selectedCampaignId);
        break;

    case 'delete_agenda':
        if ($selectedCampaignId <= 0 || !$campaign) {
            premium_flash('error', 'Selecione uma campanha antes de excluir tarefas.');
            $redirectToCampaign();
        }

        $agendaId = (int) ($_POST['agenda_id'] ?? 0);
        if ($agendaId <= 0) {
            premium_flash('error', 'Tarefa inválida.');
            $redirectToCampaign($selectedCampaignId);
        }

        $conn->query("
            DELETE FROM premium_agenda
            WHERE id = {$agendaId}
              AND campaign_id = " . (int) $selectedCampaignId . "
            LIMIT 1
        ");

        if ($conn->errno) {
            premium_flash('error', 'Não foi possível excluir a tarefa.');
        } else {
            premium_flash('success', 'Tarefa removida da agenda.');
        }

        $redirectToCampaign($selectedCampaignId);
        break;

    default:
        premium_flash('error', 'Ação premium inválida.');
        $redirectToCampaign($selectedCampaignId);
        break;
}
