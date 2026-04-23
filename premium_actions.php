<?php

declare(strict_types=1);

require_once __DIR__ . '/premium_helpers.php';

premium_ensure_campaign_photo_column($conn);

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
$redirectTab = trim((string) ($_POST['redirect_tab'] ?? ($_GET['tab'] ?? '')));

$redirectToCampaign = static function (int $campaignId = 0): void {
    $url = 'premium';
    if ($campaignId > 0) {
        premium_set_active_campaign($campaignId);
        $url .= '?campaign_id=' . $campaignId;
    }
    global $redirectTab;
    if ($redirectTab !== '') {
        $url .= ($campaignId > 0 ? '&' : '?') . 'tab=' . urlencode($redirectTab);
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

function premium_store_candidate_photo_upload(int $campaignId, ?string $currentPath = null): ?string
{
    if (empty($_FILES['candidate_photo']) || !is_array($_FILES['candidate_photo'])) {
        return $currentPath;
    }

    $file = $_FILES['candidate_photo'];
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return $currentPath;
    }

    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Nao foi possivel carregar a foto do candidato.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('Arquivo de foto invalido.');
    }

    $maxBytes = 3 * 1024 * 1024;
    if ((int) ($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('A foto deve ter no maximo 3 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmpName);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Use uma foto em JPG, PNG ou WEBP.');
    }

    $uploadDir = __DIR__ . '/assets/uploads/premium_candidates';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Nao foi possivel preparar a pasta de fotos.');
    }

    $filename = 'candidate-' . $campaignId . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extensions[$mime];
    $targetPath = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('Nao foi possivel salvar a foto do candidato.');
    }

    $relativePath = 'assets/uploads/premium_candidates/' . $filename;
    if ($currentPath && str_starts_with($currentPath, 'assets/uploads/premium_candidates/')) {
        $oldPath = __DIR__ . '/' . $currentPath;
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    return $relativePath;
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
        $candidateNumber = premium_parse_candidate_number($_POST['candidate_number'] ?? null);
        $baselineYear = premium_resolve_baseline_year((int) ($_POST['baseline_year'] ?? 2022));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $currentMunicipio = trim((string) ($_POST['current_municipio'] ?? ''));
        $currentRegion = trim((string) ($_POST['current_region'] ?? ''));

        if ($campaignName === '' || $candidateName === '' || $candidateCargo === '') {
            premium_flash('error', 'Informe o nome da campanha, o candidato e o cargo.');
            $redirectToCampaign($selectedCampaignId);
        }

        if ($candidateNumber === null) {
            $candidateBaseline = premium_candidate_baseline($conn, $candidateName, $candidateCargo, $baselineYear);
            $candidateNumber = premium_parse_candidate_number($candidateBaseline['candidate_number'] ?? null);
        }

        $conn->query("
            INSERT INTO premium_campaigns (
                user_id,
                campaign_name,
                candidate_name,
                candidate_cargo,
                candidate_number,
                baseline_year,
                notes,
                current_municipio,
                current_region
            ) VALUES (
                " . (int) $user['id'] . ",
                " . premium_sql_quote($conn, $campaignName) . ",
                " . premium_sql_quote($conn, $candidateName) . ",
                " . premium_sql_quote($conn, $candidateCargo) . ",
                " . ($candidateNumber !== null ? (string) $candidateNumber : 'NULL') . ",
                " . $baselineYear . ",
                " . premium_sql_quote($conn, $notes !== '' ? $notes : null) . ",
                " . premium_sql_quote($conn, $currentMunicipio !== '' ? $currentMunicipio : null) . ",
                " . premium_sql_quote($conn, $currentRegion !== '' ? $currentRegion : null) . "
            )
        ");

        if ($conn->errno) {
            premium_flash('error', 'Não foi possível criar a campanha.');
            $redirectToCampaign($selectedCampaignId);
        }

        $campaignId = (int) $conn->insert_id;
        try {
            $candidatePhotoPath = premium_store_candidate_photo_upload($campaignId, null);
        } catch (RuntimeException $exception) {
            premium_delete_campaign($conn, $campaignId);
            premium_flash('error', $exception->getMessage());
            $redirectToCampaign($selectedCampaignId);
        }

        if ($candidatePhotoPath) {
            $conn->query("
                UPDATE premium_campaigns
                SET candidate_photo_path = " . premium_sql_quote($conn, $candidatePhotoPath) . "
                WHERE id = {$campaignId}
                LIMIT 1
            ");
        }

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

    case 'update_campaign':
        if ($selectedCampaignId <= 0 || !$campaign) {
            premium_flash('error', 'Selecione uma campanha antes de atualizar os dados.');
            $redirectToCampaign();
        }

        $campaignName = trim((string) ($_POST['campaign_name'] ?? ''));
        $candidateName = trim((string) ($_POST['candidate_name'] ?? ''));
        $candidateCargo = trim((string) ($_POST['candidate_cargo'] ?? ''));
        $candidateNumber = premium_parse_candidate_number($_POST['candidate_number'] ?? null);
        $baselineYear = premium_resolve_baseline_year((int) ($_POST['baseline_year'] ?? 2022));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $currentMunicipio = trim((string) ($_POST['current_municipio'] ?? ''));
        $currentRegion = trim((string) ($_POST['current_region'] ?? ''));

        if ($campaignName === '' || $candidateName === '' || $candidateCargo === '') {
            premium_flash('error', 'Informe o nome da campanha, o candidato e o cargo.');
            $redirectToCampaign($selectedCampaignId);
        }

        if ($candidateNumber === null) {
            $candidateNumber = premium_parse_candidate_number($campaign['candidate_number'] ?? null);
        }

        if ($candidateNumber === null) {
            $candidateBaseline = premium_candidate_baseline($conn, $candidateName, $candidateCargo, $baselineYear);
            $candidateNumber = premium_parse_candidate_number($candidateBaseline['candidate_number'] ?? null);
        }

        try {
            $candidatePhotoPath = premium_store_candidate_photo_upload($selectedCampaignId, (string) ($campaign['candidate_photo_path'] ?? ''));
        } catch (RuntimeException $exception) {
            premium_flash('error', $exception->getMessage());
            $redirectToCampaign($selectedCampaignId);
        }

        $conn->query("
            UPDATE premium_campaigns
            SET campaign_name = " . premium_sql_quote($conn, $campaignName) . ",
                candidate_name = " . premium_sql_quote($conn, $candidateName) . ",
                candidate_cargo = " . premium_sql_quote($conn, $candidateCargo) . ",
                candidate_number = " . ($candidateNumber !== null ? (string) $candidateNumber : 'NULL') . ",
                baseline_year = " . $baselineYear . ",
                candidate_photo_path = " . premium_sql_quote($conn, $candidatePhotoPath !== '' ? $candidatePhotoPath : null) . ",
                notes = " . premium_sql_quote($conn, $notes !== '' ? $notes : null) . ",
                current_municipio = " . premium_sql_quote($conn, $currentMunicipio !== '' ? $currentMunicipio : null) . ",
                current_region = " . premium_sql_quote($conn, $currentRegion !== '' ? $currentRegion : null) . "
            WHERE id = " . (int) $selectedCampaignId . "
              AND user_id = " . (int) $user['id'] . "
            LIMIT 1
        ");

        if ($conn->errno) {
            premium_flash('error', 'Não foi possível atualizar a campanha.');
        } else {
            premium_flash('success', 'Campanha atualizada.');
        }

        $redirectToCampaign($selectedCampaignId);
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

        if (trim((string) ($_POST['delete_confirmation'] ?? '')) !== 'EXCLUIR CAMPANHA') {
            premium_flash('error', 'Confirme a exclusao digitando EXCLUIR CAMPANHA.');
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
                        " . number_format((float) ($leader['transfer_rate'] ?? premium_default_settings()['transfer_rate_default']), 2, '.', '') . ",
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
        $candidateNumber = premium_parse_candidate_number($_POST['candidate_number'] ?? null);
        $baselineYear = premium_resolve_baseline_year((int) ($_POST['baseline_year'] ?? 2022));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        $currentMunicipio = trim((string) ($_POST['current_municipio'] ?? ''));
        $currentRegion = trim((string) ($_POST['current_region'] ?? ''));
        try {
            $candidatePhotoPath = premium_store_candidate_photo_upload($selectedCampaignId, (string) ($campaign['candidate_photo_path'] ?? ''));
        } catch (RuntimeException $exception) {
            premium_flash('error', $exception->getMessage());
            $redirectToCampaign($selectedCampaignId);
        }

        if ($candidateNumber === null) {
            $candidateNumber = premium_parse_candidate_number($campaign['candidate_number'] ?? null);
        }

        if ($candidateNumber === null) {
            $candidateBaseline = premium_candidate_baseline($conn, $candidateName, $candidateCargo, $baselineYear);
            $candidateNumber = premium_parse_candidate_number($candidateBaseline['candidate_number'] ?? null);
        }

        $conn->query("
            UPDATE premium_campaigns
            SET candidate_name = " . premium_sql_quote($conn, $candidateName) . ",
                candidate_cargo = " . premium_sql_quote($conn, $candidateCargo) . ",
                candidate_number = " . ($candidateNumber !== null ? (string) $candidateNumber : 'NULL') . ",
                baseline_year = " . $baselineYear . ",
                baseline_panel_hidden = 1,
                candidate_photo_path = " . premium_sql_quote($conn, $candidatePhotoPath !== '' ? $candidatePhotoPath : null) . ",
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

    case 'change_password':
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['new_password_confirm'] ?? '');
        $authUser = querySingle($conn, "
            SELECT id, password_hash
            FROM premium_users
            WHERE id = " . (int) ($user['id'] ?? 0) . "
            LIMIT 1
        ");

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            premium_flash('error', 'Preencha a senha atual, a nova senha e a confirmação.');
            $redirectToCampaign($selectedCampaignId);
        }

        if (!$authUser || !password_verify($currentPassword, (string) ($authUser['password_hash'] ?? ''))) {
            premium_flash('error', 'A senha atual não confere.');
            $redirectToCampaign($selectedCampaignId);
        }

        if (strlen($newPassword) < 8) {
            premium_flash('error', 'A nova senha precisa ter pelo menos 8 caracteres.');
            $redirectToCampaign($selectedCampaignId);
        }

        if ($newPassword !== $confirmPassword) {
            premium_flash('error', 'A confirmação da nova senha não confere.');
            $redirectToCampaign($selectedCampaignId);
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            premium_flash('error', 'Não foi possível atualizar a senha.');
            $redirectToCampaign($selectedCampaignId);
        }

        $conn->query("
            UPDATE premium_users
            SET password_hash = " . premium_sql_quote($conn, $passwordHash) . "
            WHERE id = " . (int) ($user['id'] ?? 0) . "
            LIMIT 1
        ");

        if ($conn->errno) {
            premium_flash('error', 'Não foi possível salvar a nova senha.');
        } else {
            premium_flash('success', 'Senha alterada com sucesso.');
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

    case 'update_leaders_transfer_batch':
        if ($selectedCampaignId <= 0 || !$campaign) {
            premium_flash('error', 'Selecione uma campanha antes de alterar lideranças.');
            $redirectToCampaign();
        }

        $transferRateRaw = trim((string) ($_POST['transfer_rate'] ?? ''));
        $transferScope = trim((string) ($_POST['transfer_scope'] ?? 'selected'));

        if ($transferRateRaw === '' || !is_numeric($transferRateRaw)) {
            premium_flash('error', 'Informe uma transferência válida entre 0 e 100.');
            $redirectToCampaign($selectedCampaignId);
        }

        $transferRate = (float) $transferRateRaw;
        if ($transferRate < 0 || $transferRate > 100) {
            premium_flash('error', 'Informe uma transferência válida entre 0 e 100.');
            $redirectToCampaign($selectedCampaignId);
        }

        if (!in_array($transferScope, ['selected', 'visible', 'all'], true)) {
            $transferScope = 'selected';
        }

        $requestedIds = [];
        $invalidSelections = 0;
        if ($transferScope !== 'all') {
            $leadersJson = trim((string) ($_POST['leaders_json'] ?? ''));
            if ($leadersJson === '') {
                premium_flash('error', 'Selecione pelo menos uma liderança antes de alterar a transferência.');
                $redirectToCampaign($selectedCampaignId);
            }

            $decoded = json_decode($leadersJson, true);
            if (!is_array($decoded) || !$decoded) {
                premium_flash('error', 'Não foi possível ler a seleção em lote.');
                $redirectToCampaign($selectedCampaignId);
            }

            foreach ($decoded as $item) {
                $leaderId = 0;
                if (is_array($item) && isset($item['id'])) {
                    $leaderId = (int) $item['id'];
                } elseif (is_numeric($item)) {
                    $leaderId = (int) $item;
                }

                if ($leaderId <= 0) {
                    $invalidSelections++;
                    continue;
                }

                $requestedIds[$leaderId] = true;
            }

            if (!$requestedIds) {
                premium_flash('error', 'Nenhuma liderança válida foi selecionada.');
                $redirectToCampaign($selectedCampaignId);
            }

            $requestedIds = array_keys($requestedIds);
        }

        $transferRateSql = number_format($transferRate, 2, '.', '');
        $updatedCount = 0;

        $conn->begin_transaction();

        try {
            if ($transferScope === 'all') {
                $countRows = queryAll($conn, "
                    SELECT COUNT(*) AS total
                    FROM premium_campaign_leaders
                    WHERE campaign_id = " . (int) $selectedCampaignId . "
                ");
                $updatedCount = (int) ($countRows[0]['total'] ?? 0);

                if ($updatedCount <= 0) {
                    $conn->rollback();
                    premium_flash('warning', 'Não há lideranças nesta campanha para alterar.');
                    $redirectToCampaign($selectedCampaignId);
                }

                $conn->query("
                    UPDATE premium_campaign_leaders
                    SET transfer_rate = {$transferRateSql}
                    WHERE campaign_id = " . (int) $selectedCampaignId . "
                ");
            } else {
                $requestedSql = implode(',', array_map('intval', $requestedIds));
                $existingRows = queryAll($conn, "
                    SELECT id
                    FROM premium_campaign_leaders
                    WHERE campaign_id = " . (int) $selectedCampaignId . "
                      AND id IN ({$requestedSql})
                ");

                $existingIds = [];
                foreach ($existingRows as $row) {
                    $existingIds[] = (int) ($row['id'] ?? 0);
                }
                $existingIds = array_values(array_filter(array_unique($existingIds), static fn(int $id): bool => $id > 0));

                if (!$existingIds) {
                    $conn->rollback();
                    premium_flash('warning', 'Nenhuma liderança selecionada foi encontrada nesta campanha.');
                    $redirectToCampaign($selectedCampaignId);
                }

                $existingSql = implode(',', array_map('intval', $existingIds));
                $updatedCount = count($existingIds);

                $conn->query("
                    UPDATE premium_campaign_leaders
                    SET transfer_rate = {$transferRateSql}
                    WHERE campaign_id = " . (int) $selectedCampaignId . "
                      AND id IN ({$existingSql})
                ");
            }

            if ($conn->errno) {
                throw new RuntimeException($conn->error);
            }

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            premium_flash('error', 'Não foi possível alterar a transferência das lideranças selecionadas.');
            $redirectToCampaign($selectedCampaignId);
        }

        $messageParts = [];
        $messageParts[] = $updatedCount === 1 ? 'Transferência atualizada em 1 liderança' : 'Transferência atualizada em ' . $updatedCount . ' lideranças';
        if ($transferScope !== 'all') {
            $ignoredCount = max(0, count($requestedIds) - $updatedCount + $invalidSelections);
            if ($ignoredCount > 0) {
                $messageParts[] = $ignoredCount === 1 ? '1 seleção ignorada' : $ignoredCount . ' seleções ignoradas';
            }
        }

        premium_flash('success', implode('; ', $messageParts) . '.');
        $redirectToCampaign($selectedCampaignId);
        break;

    case 'delete_leaders_batch':
        if ($selectedCampaignId <= 0 || !$campaign) {
            premium_flash('error', 'Selecione uma campanha antes de excluir lideranças.');
            $redirectToCampaign();
        }

        $leadersJson = trim((string) ($_POST['leaders_json'] ?? ''));
        if ($leadersJson === '') {
            premium_flash('error', 'Selecione pelo menos uma liderança antes de excluir.');
            $redirectToCampaign($selectedCampaignId);
        }

        $decoded = json_decode($leadersJson, true);
        if (!is_array($decoded) || !$decoded) {
            premium_flash('error', 'Não foi possível ler a seleção em lote.');
            $redirectToCampaign($selectedCampaignId);
        }

        $requestedIds = [];
        $invalidSelections = 0;
        foreach ($decoded as $item) {
            $leaderId = 0;
            if (is_array($item) && isset($item['id'])) {
                $leaderId = (int) $item['id'];
            } elseif (is_numeric($item)) {
                $leaderId = (int) $item;
            }

            if ($leaderId <= 0) {
                $invalidSelections++;
                continue;
            }

            $requestedIds[$leaderId] = true;
        }

        if (!$requestedIds) {
            premium_flash('error', 'Nenhuma liderança válida foi selecionada.');
            $redirectToCampaign($selectedCampaignId);
        }

        $requestedIds = array_keys($requestedIds);
        $requestedSql = implode(',', array_map('intval', $requestedIds));

        $conn->begin_transaction();

        try {
            $existingRows = queryAll($conn, "
                SELECT id
                FROM premium_campaign_leaders
                WHERE campaign_id = " . (int) $selectedCampaignId . "
                  AND id IN ({$requestedSql})
            ");

            $existingIds = [];
            foreach ($existingRows as $row) {
                $existingIds[] = (int) ($row['id'] ?? 0);
            }
            $existingIds = array_values(array_filter(array_unique($existingIds), static fn(int $id): bool => $id > 0));

            if (!$existingIds) {
                $conn->rollback();
                premium_flash('warning', 'Nenhuma liderança selecionada foi encontrada nesta campanha.');
                $redirectToCampaign($selectedCampaignId);
            }

            $existingSql = implode(',', array_map('intval', $existingIds));
            $conn->query("
                DELETE FROM premium_campaign_leaders
                WHERE campaign_id = " . (int) $selectedCampaignId . "
                  AND id IN ({$existingSql})
            ");

            if ($conn->errno) {
                throw new RuntimeException($conn->error);
            }

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            premium_flash('error', 'Não foi possível excluir as lideranças selecionadas.');
            $redirectToCampaign($selectedCampaignId);
        }

        $removedCount = count($existingIds);
        $ignoredCount = max(0, count($requestedIds) - $removedCount + $invalidSelections);
        $messageParts = [];
        $messageParts[] = $removedCount === 1 ? '1 liderança removida' : $removedCount . ' lideranças removidas';
        if ($ignoredCount > 0) {
            $messageParts[] = $ignoredCount === 1 ? '1 seleção ignorada' : $ignoredCount . ' seleções ignoradas';
        }

        premium_flash('success', implode('; ', $messageParts) . '.');
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
