<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function premium_session_lifetime_seconds(): int
{
    $configured = (int) (getenv('PREMIUM_SESSION_LIFETIME') ?: 2592000);
    if ($configured < 3600) {
        return 2592000;
    }

    return min($configured, 31536000);
}

function premium_is_https_request(): bool
{
    $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
    if ($https === 'on' || $https === '1') {
        return true;
    }

    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if (str_contains($forwardedProto, 'https')) {
        return true;
    }

    return (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

function premium_refresh_session_cookie(int $lifetime): void
{
    if (!ini_get('session.use_cookies') || session_id() === '' || headers_sent()) {
        return;
    }

    $params = session_get_cookie_params();
    setcookie(session_name(), session_id(), [
        'expires' => time() + $lifetime,
        'path' => $params['path'] !== '' ? $params['path'] : '/',
        'domain' => (string) ($params['domain'] ?? ''),
        'secure' => (bool) ($params['secure'] ?? false),
        'httponly' => true,
        'samesite' => (string) ($params['samesite'] ?? 'Lax'),
    ]);
}

function premium_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        premium_refresh_session_cookie(premium_session_lifetime_seconds());
        return;
    }

    $lifetime = premium_session_lifetime_seconds();
    ini_set('session.gc_maxlifetime', (string) $lifetime);
    ini_set('session.cookie_lifetime', (string) $lifetime);
    ini_set('session.use_strict_mode', '1');

    $params = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => $params['path'] !== '' ? $params['path'] : '/',
        'domain' => (string) ($params['domain'] ?? ''),
        'secure' => premium_is_https_request(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
    premium_refresh_session_cookie($lifetime);
}

premium_start_session();

function premium_escape_html(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function premium_render_pwa_tags(string $themeColor = '#07111d'): string
{
    $themeColor = premium_escape_html($themeColor);

    return implode("\n", [
        '<link rel="manifest" href="manifest.webmanifest">',
        '<meta name="theme-color" content="' . $themeColor . '">',
        '<meta name="application-name" content="ApoiaCandidato">',
        '<meta name="mobile-web-app-capable" content="yes">',
        '<meta name="apple-mobile-web-app-capable" content="yes">',
        '<meta name="apple-mobile-web-app-title" content="ApoiaCandidato">',
        '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">',
        '<link rel="apple-touch-icon" href="apple-touch-icon.png">',
        '<link rel="icon" type="image/png" sizes="192x192" href="assets/icons/icon-192.png">',
        '<link rel="icon" type="image/png" sizes="512x512" href="assets/icons/icon-512.png">',
        '<script src="assets/js/pwa.js" defer></script>',
    ]);
}

function premium_sql_quote(mysqli $conn, ?string $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    return "'" . $conn->real_escape_string($value) . "'";
}

function premium_sql_int(mixed $value): int
{
    return (int) $value;
}

function premium_parse_candidate_number(mixed $value): ?int
{
    if ($value === null) {
        return null;
    }

    $digits = preg_replace('/\D+/', '', trim((string) $value)) ?? '';
    if ($digits === '') {
        return null;
    }

    $number = (int) $digits;
    return $number > 0 ? $number : null;
}

function premium_fmt_candidate_number(?int $value): string
{
    $number = (int) $value;
    if ($number <= 0) {
        return '';
    }

    return number_format($number, 0, ',', '.');
}

function premium_fmt_candidate_number_plain(?int $value): string
{
    $number = (int) $value;
    return $number > 0 ? (string) $number : '';
}

function premium_vip_support_whatsapp_url(?array $user, ?array $campaign, string $phone = '5579999248114'): string
{
    if (!$user) {
        return '';
    }

    $userName = trim((string) ($user['name'] ?? ''));
    $userEmail = trim((string) ($user['email'] ?? ''));
    $campaignParts = [];

    if ($campaign) {
        $campaignParts = [
            (string) ($campaign['campaign_name'] ?? 'Campanha'),
            (string) ($campaign['candidate_name'] ?? ''),
        ];
    }

    $campaignLabel = trim(implode(' • ', array_filter($campaignParts, static fn(string $item): bool => trim($item) !== '')));
    if ($campaignLabel === '') {
        $campaignLabel = 'Nenhuma campanha ativa';
    }

    $message = implode("\n", [
        'Olá! Preciso de ajuda no Apoia Candidato Premium.',
        'Usuário: ' . ($userName !== '' ? $userName : 'Não informado'),
        'E-mail: ' . ($userEmail !== '' ? $userEmail : 'Não informado'),
        'Campanha: ' . $campaignLabel,
        'Pode me atender?',
    ]);

    return 'https://wa.me/' . preg_replace('/\D+/', '', $phone) . '?text=' . rawurlencode($message);
}

function premium_ensure_campaign_photo_column(mysqli $conn): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $checked = true;

    $column = querySingle($conn, "
        SHOW COLUMNS FROM premium_campaigns LIKE 'candidate_photo_path'
    ");

    if ($column) {
        return;
    }

    $conn->query("
        ALTER TABLE premium_campaigns
        ADD COLUMN candidate_photo_path VARCHAR(255) DEFAULT NULL AFTER candidate_number
    ");
}

function premium_ensure_user_trial_columns(mysqli $conn): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $checked = true;

    $trialStartedColumn = querySingle($conn, "
        SHOW COLUMNS FROM premium_users LIKE 'trial_started_at'
    ");

    if (!$trialStartedColumn) {
        $conn->query("
            ALTER TABLE premium_users
            ADD COLUMN trial_started_at DATETIME DEFAULT NULL AFTER last_login_at
        ");
    }

    $trialEndsColumn = querySingle($conn, "
        SHOW COLUMNS FROM premium_users LIKE 'trial_ends_at'
    ");

    if (!$trialEndsColumn) {
        $conn->query("
            ALTER TABLE premium_users
            ADD COLUMN trial_ends_at DATETIME DEFAULT NULL AFTER trial_started_at
        ");
    }
}

function premium_ensure_campaign_access_table(mysqli $conn): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $checked = true;

    $conn->query("
        CREATE TABLE IF NOT EXISTS premium_campaign_access (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT UNSIGNED NOT NULL,
            user_id     INT UNSIGNED NOT NULL,
            created_by  INT UNSIGNED NOT NULL,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_access (campaign_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function premium_ensure_campaign_allied_parties_table(mysqli $conn): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $checked = true;

    $conn->query("
        CREATE TABLE IF NOT EXISTS premium_campaign_allied_parties (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT UNSIGNED NOT NULL,
            party_acronym VARCHAR(20) NOT NULL,
            party_name VARCHAR(120) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_campaign_party (campaign_id, party_acronym),
            KEY idx_party_acronym (party_acronym)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function premium_ensure_senate_tables(mysqli $conn): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $checked = true;

    $conn->query("
        CREATE TABLE IF NOT EXISTS premium_senate_vote_sources (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT UNSIGNED NOT NULL,
            source_year SMALLINT NOT NULL,
            source_cargo VARCHAR(60) NOT NULL,
            source_candidate_name VARCHAR(190) NOT NULL,
            source_ballot_name VARCHAR(190) DEFAULT NULL,
            source_party VARCHAR(20) DEFAULT NULL,
            source_number INT DEFAULT NULL,
            source_sq_candidato VARCHAR(50) DEFAULT NULL,
            source_scope_label VARCHAR(120) DEFAULT NULL,
            source_total_votes INT NOT NULL DEFAULT 0,
            source_vote_percent DECIMAL(6,2) DEFAULT NULL,
            relationship_type VARCHAR(40) NOT NULL DEFAULT 'manual',
            transfer_rate DECIMAL(6,2) NOT NULL DEFAULT 40.00,
            confidence_score DECIMAL(6,2) NOT NULL DEFAULT 50.00,
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_campaign (campaign_id),
            KEY idx_source_year (source_year),
            KEY idx_source_candidate (source_candidate_name),
            KEY idx_source_sq (source_sq_candidato)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    if (!premium_table_has_column($conn, 'premium_senate_vote_sources', 'source_scope_label')) {
        $conn->query("
            ALTER TABLE premium_senate_vote_sources
            ADD COLUMN source_scope_label VARCHAR(120) DEFAULT NULL AFTER source_sq_candidato
        ");
    }

    if (!premium_table_has_column($conn, 'premium_senate_vote_sources', 'source_vote_percent')) {
        $conn->query("
            ALTER TABLE premium_senate_vote_sources
            ADD COLUMN source_vote_percent DECIMAL(6,2) DEFAULT NULL AFTER source_total_votes
        ");
    }

    $conn->query("
        CREATE TABLE IF NOT EXISTS premium_senate_vote_source_municipios (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            source_id INT UNSIGNED NOT NULL,
            municipality VARCHAR(120) NOT NULL,
            region_name VARCHAR(120) DEFAULT NULL,
            source_votes INT NOT NULL DEFAULT 0,
            projected_votes INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_source (source_id),
            KEY idx_municipality (municipality)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS premium_party_alliances (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            election_year SMALLINT NOT NULL,
            scope_type VARCHAR(20) NOT NULL DEFAULT 'state',
            municipality VARCHAR(120) DEFAULT NULL,
            anchor_party VARCHAR(20) NOT NULL,
            ally_party VARCHAR(20) NOT NULL,
            alliance_name VARCHAR(190) DEFAULT NULL,
            source_notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_year_anchor (election_year, anchor_party),
            KEY idx_scope_city (scope_type, municipality),
            KEY idx_ally_party (ally_party)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function premium_trial_end_timestamp(?array $user): ?int
{
    $value = trim((string) ($user['trial_ends_at'] ?? ''));
    if ($value === '') {
        return null;
    }

    $timestamp = strtotime($value);
    return $timestamp === false ? null : $timestamp;
}

function premium_trial_days_remaining(?array $user): ?int
{
    $trialEnd = premium_trial_end_timestamp($user);
    if ($trialEnd === null) {
        return null;
    }

    $remainingSeconds = $trialEnd - time();
    if ($remainingSeconds <= 0) {
        return 0;
    }

    return (int) ceil($remainingSeconds / 86400);
}

function premium_trial_is_expired(?array $user): bool
{
    $trialEnd = premium_trial_end_timestamp($user);
    return $trialEnd !== null && $trialEnd <= time();
}

function premium_mark_trial_expired(mysqli $conn, int $userId): void
{
    $conn->query("
        UPDATE premium_users
        SET status = 'inactive'
        WHERE id = " . (int) $userId . "
        LIMIT 1
    ");
}

function premium_create_trial_user(mysqli $conn, string $name, string $email, string $password, int $trialDays = 7): bool
{
    $name = trim($name);
    $email = strtolower(trim($email));
    $password = (string) $password;

    if ($name === '' || $email === '' || $password === '') {
        premium_flash('error', 'Preencha nome, e-mail e senha para iniciar o teste.');
        return false;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        premium_flash('error', 'Informe um e-mail válido para começar o teste.');
        return false;
    }

    if (strlen($password) < 8) {
        premium_flash('error', 'A senha precisa ter pelo menos 8 caracteres.');
        return false;
    }

    $existing = querySingle($conn, "
        SELECT id, status, trial_ends_at
        FROM premium_users
        WHERE email = " . premium_sql_quote($conn, $email) . "
        LIMIT 1
    ");

    if (!empty($existing)) {
        $trialEndsAt = trim((string) ($existing['trial_ends_at'] ?? ''));
        if (($existing['status'] ?? '') === 'active' && $trialEndsAt !== '') {
            premium_flash('error', 'Este e-mail já possui um acesso ativo. Entre no sistema ou use outro e-mail para testar.');
        } elseif (($existing['status'] ?? '') === 'active') {
            premium_flash('error', 'Este e-mail já possui acesso ativo. Entre no sistema ou use outro e-mail.');
        } else {
            premium_flash('error', 'Este e-mail já está cadastrado. Use outro e-mail para iniciar o teste.');
        }
        return false;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        premium_flash('error', 'Não foi possível criar a senha do teste.');
        return false;
    }

    $trialDays = max(1, $trialDays);
    $trialStartedAt = date('Y-m-d H:i:s');
    $trialEndsAt = date('Y-m-d H:i:s', time() + ($trialDays * 86400));

    $conn->query("
        INSERT INTO premium_users (
            name,
            email,
            password_hash,
            status,
            trial_started_at,
            trial_ends_at
        ) VALUES (
            " . premium_sql_quote($conn, $name) . ",
            " . premium_sql_quote($conn, $email) . ",
            " . premium_sql_quote($conn, $passwordHash) . ",
            'active',
            " . premium_sql_quote($conn, $trialStartedAt) . ",
            " . premium_sql_quote($conn, $trialEndsAt) . "
        )
    ");

    if ($conn->errno) {
        premium_flash('error', 'Não foi possível liberar o teste gratuito.');
        return false;
    }

    return premium_login($conn, $email, $password);
}

function premium_normalize_text(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($converted !== false && $converted !== '') {
        $value = $converted;
    }

    $value = strtoupper($value);
    $value = str_replace(["'", '`', '^', '~', '"'], '', $value);
    $value = preg_replace('/[^A-Z0-9]+/', ' ', $value) ?? $value;
    $value = preg_replace('/\s+/', ' ', $value) ?? $value;

    return trim($value);
}

function premium_tse_2026_parties(): array
{
    return [
        ['number' => 36, 'acronym' => 'AGIR', 'name' => 'Agir', 'spectrum' => 'Centro', 'foundation_year' => 1994, 'party_fund' => false, 'status' => 'Ativo'],
        ['number' => 70, 'acronym' => 'AVANTE', 'name' => 'Avante', 'spectrum' => 'Centro', 'foundation_year' => 1994, 'party_fund' => true, 'status' => 'Ativo'],
        ['number' => 23, 'acronym' => 'CIDADANIA', 'name' => 'Cidadania', 'spectrum' => 'Centro-esquerda', 'foundation_year' => 1992, 'party_fund' => true, 'status' => 'Ativo'],
        ['number' => 27, 'acronym' => 'DC', 'name' => 'Democracia Cristã', 'spectrum' => 'Centro-direita', 'foundation_year' => 1997, 'party_fund' => false, 'status' => 'Ativo'],
        ['number' => 15, 'acronym' => 'MDB', 'name' => 'Movimento Democrático Brasileiro', 'spectrum' => 'Centro', 'foundation_year' => 1980, 'party_fund' => true, 'status' => 'Ativo'],
        ['number' => 14, 'acronym' => 'MISSÃO', 'name' => 'Partido Missão', 'spectrum' => 'Direita', 'foundation_year' => 2025, 'party_fund' => false, 'status' => 'Ativo'],
        ['number' => 48, 'acronym' => 'MOBILIZA', 'name' => 'Mobilização Nacional', 'spectrum' => 'Outros/Misto', 'foundation_year' => 2019, 'party_fund' => false, 'status' => 'Ativo'],
        ['number' => 30, 'acronym' => 'NOVO', 'name' => 'Partido Novo', 'spectrum' => 'Direita', 'foundation_year' => 2011, 'party_fund' => false, 'status' => 'Ativo'],
        ['number' => 61, 'acronym' => 'O DEMOCRATA', 'name' => 'O Democrata', 'spectrum' => 'Outros/Misto', 'foundation_year' => 2019, 'party_fund' => false, 'status' => 'Ativo'],
        ['number' => 21, 'acronym' => 'PCB', 'name' => 'Partido Comunista Brasileiro', 'spectrum' => 'Extrema-esquerda', 'foundation_year' => 1922, 'party_fund' => false, 'status' => 'Ativo'],
        ['number' => 65, 'acronym' => 'PCdoB', 'name' => 'Partido Comunista do Brasil', 'spectrum' => 'Extrema-esquerda', 'foundation_year' => 1962, 'party_fund' => true, 'status' => 'Ativo'],
        ['number' => 29, 'acronym' => 'PCO', 'name' => 'Partido da Causa Operária', 'spectrum' => 'Extrema-esquerda', 'foundation_year' => 1997, 'party_fund' => false, 'status' => 'Ativo'],
        ['number' => 12, 'acronym' => 'PDT', 'name' => 'Partido Democrático Trabalhista', 'spectrum' => 'Centro-esquerda', 'foundation_year' => 1980, 'party_fund' => true, 'status' => 'Ativo'],
        ['number' => 22, 'acronym' => 'PL', 'name' => 'Partido Liberal', 'spectrum' => 'Direita', 'foundation_year' => 1985, 'party_fund' => true, 'status' => 'Ativo'],
        ['number' => 35, 'acronym' => 'PMB', 'name' => 'Partido da Mulher Brasileira', 'spectrum' => 'Outros/Misto', 'foundation_year' => 2009, 'party_fund' => false, 'status' => 'Ativo'],
        ['number' => 33, 'acronym' => 'PMN', 'name' => 'Partido da Mobilização Nacional', 'spectrum' => 'Outros/Misto', 'foundation_year' => 1984, 'party_fund' => false, 'status' => 'Ativo'],
        ['number' => 20, 'acronym' => 'PODE', 'name' => 'Podemos', 'spectrum' => 'Centro', 'foundation_year' => 1995, 'party_fund' => true, 'status' => 'Ativo'],
        ['number' => 11, 'acronym' => 'PP', 'name' => 'Progressistas', 'spectrum' => 'Centro-direita', 'foundation_year' => 1995, 'party_fund' => true, 'status' => 'Ativo'],
        ['number' => 25, 'acronym' => 'PRD', 'name' => 'Partido Renovação Democrática', 'spectrum' => 'Direita', 'foundation_year' => 2023, 'party_fund' => true, 'status' => 'Ativo'],
        ['number' => 28, 'acronym' => 'PRTB', 'name' => 'Partido Renovador Trabalhista Brasileiro', 'spectrum' => 'Extrema-direita', 'foundation_year' => 1994, 'party_fund' => false, 'status' => 'Ativo'],
        ['number' => 40, 'acronym' => 'PSB', 'name' => 'Partido Socialista Brasileiro', 'spectrum' => 'Centro-esquerda', 'foundation_year' => 1947, 'party_fund' => true, 'status' => 'Ativo'],
        ['number' => 55, 'acronym' => 'PSD', 'name' => 'Partido Social Democrático', 'spectrum' => 'Centro', 'foundation_year' => 2011, 'party_fund' => true, 'status' => 'Ativo'],
        ['number' => 45, 'acronym' => 'PSDB', 'name' => 'Partido da Social Democracia Brasileira', 'spectrum' => 'Centro', 'foundation_year' => 1988, 'party_fund' => true, 'status' => 'Ativo'],
        ['number' => 50, 'acronym' => 'PSOL', 'name' => 'Partido Socialismo e Liberdade', 'spectrum' => 'Esquerda', 'foundation_year' => 2004, 'party_fund' => true, 'status' => 'Ativo'],
        ['number' => 16, 'acronym' => 'PSTU', 'name' => 'Partido Socialista dos Trabalhadores Unificado', 'spectrum' => 'Extrema-esquerda', 'foundation_year' => 1994, 'party_fund' => false, 'status' => 'Ativo'],
        ['number' => 13, 'acronym' => 'PT', 'name' => 'Partido dos Trabalhadores', 'spectrum' => 'Esquerda', 'foundation_year' => 1980, 'party_fund' => true, 'status' => 'Ativo'],
        ['number' => 43, 'acronym' => 'PV', 'name' => 'Partido Verde', 'spectrum' => 'Centro-esquerda', 'foundation_year' => 1986, 'party_fund' => true, 'status' => 'Ativo'],
        ['number' => 18, 'acronym' => 'REDE', 'name' => 'Rede Sustentabilidade', 'spectrum' => 'Centro-esquerda', 'foundation_year' => 2013, 'party_fund' => true, 'status' => 'Ativo'],
        ['number' => 10, 'acronym' => 'REPUBLICANOS', 'name' => 'Republicanos', 'spectrum' => 'Centro-direita', 'foundation_year' => 2005, 'party_fund' => true, 'status' => 'Ativo'],
        ['number' => 77, 'acronym' => 'SOLIDARIEDADE', 'name' => 'Solidariedade', 'spectrum' => 'Centro', 'foundation_year' => 2012, 'party_fund' => true, 'status' => 'Ativo'],
        ['number' => 44, 'acronym' => 'UNIÃO', 'name' => 'União Brasil', 'spectrum' => 'Centro-direita', 'foundation_year' => 2022, 'party_fund' => true, 'status' => 'Ativo'],
        ['number' => 80, 'acronym' => 'UP', 'name' => 'Unidade Popular', 'spectrum' => 'Extrema-esquerda', 'foundation_year' => 2019, 'party_fund' => false, 'status' => 'Ativo'],
    ];
}

function premium_available_parties(mysqli $conn): array
{
    return premium_tse_2026_parties();
}

function premium_party_catalog_by_key(): array
{
    $catalog = [];
    foreach (premium_tse_2026_parties() as $party) {
        $catalog[premium_normalize_text((string) ($party['acronym'] ?? ''))] = $party;
    }

    $catalog['PC DO B'] = $catalog['PCDOB'] ?? null;
    $catalog['UNIAO'] = $catalog['UNIAO'] ?? ($catalog[premium_normalize_text('UNIÃO')] ?? null);
    $catalog['MISSAO'] = $catalog['MISSAO'] ?? ($catalog[premium_normalize_text('MISSÃO')] ?? null);
    return array_filter($catalog);
}

function premium_normalize_party_acronym(string $party): string
{
    $party = trim($party);
    if ($party === '') {
        return '';
    }

    $catalog = premium_party_catalog_by_key();
    $key = premium_normalize_text($party);
    if (isset($catalog[$key])) {
        return (string) ($catalog[$key]['acronym'] ?? $party);
    }

    return function_exists('mb_strtoupper') ? mb_strtoupper($party, 'UTF-8') : strtoupper($party);
}

function premium_party_details(string $party): ?array
{
    $catalog = premium_party_catalog_by_key();
    return $catalog[premium_normalize_text($party)] ?? null;
}

function premium_normalize_party_list(array $parties): array
{
    $normalized = [];
    foreach ($parties as $party) {
        $acronym = premium_normalize_party_acronym((string) $party);
        if ($acronym === '') {
            continue;
        }

        $normalized[premium_normalize_text($acronym)] = $acronym;
    }

    return array_values($normalized);
}

function premium_expand_party_acronyms(array $parties): array
{
    $expanded = [];
    foreach (premium_normalize_party_list($parties) as $party) {
        $expanded[] = $party;
        $expanded[] = premium_normalize_text($party);
        if (premium_normalize_text($party) === 'PCDOB') {
            $expanded[] = 'PC do B';
            $expanded[] = 'PC DO B';
        }
        if (premium_normalize_text($party) === 'UNIAO') {
            $expanded[] = 'UNIÃO';
            $expanded[] = 'UNIAO';
        }
        if (premium_normalize_text($party) === 'MISSAO') {
            $expanded[] = 'MISSÃO';
            $expanded[] = 'MISSAO';
        }
    }

    return array_values(array_unique(array_filter(array_map(
        static fn(string $party): string => trim($party),
        $expanded
    ), static fn(string $party): bool => $party !== '')));
}

function premium_party_filter_keys(array $parties): array
{
    return array_values(array_unique(array_map(
        static fn(string $party): string => premium_normalize_text($party),
        premium_expand_party_acronyms($parties)
    )));
}

function premium_get_campaign_allied_parties(mysqli $conn, int $campaignId): array
{
    premium_ensure_campaign_allied_parties_table($conn);
    if ($campaignId <= 0) {
        return [];
    }

    $rows = queryAll($conn, "
        SELECT party_acronym, party_name
        FROM premium_campaign_allied_parties
        WHERE campaign_id = " . (int) $campaignId . "
        ORDER BY party_acronym ASC
    ");

    foreach ($rows as &$row) {
        $row['party_acronym'] = premium_normalize_party_acronym((string) ($row['party_acronym'] ?? ''));
        $details = premium_party_details((string) $row['party_acronym']);
        if ($details) {
            $row = array_merge($details, $row);
        }
    }
    unset($row);

    return $rows;
}

function premium_get_campaign_allied_party_acronyms(mysqli $conn, int $campaignId): array
{
    return array_values(array_filter(array_map(
        static fn(array $row): string => (string) ($row['party_acronym'] ?? ''),
        premium_get_campaign_allied_parties($conn, $campaignId)
    )));
}

function premium_save_campaign_allied_parties(mysqli $conn, int $campaignId, array $parties): bool
{
    premium_ensure_campaign_allied_parties_table($conn);
    if ($campaignId <= 0) {
        return false;
    }

    $normalized = premium_normalize_party_list($parties);
    $conn->begin_transaction();

    try {
        $conn->query("
            DELETE FROM premium_campaign_allied_parties
            WHERE campaign_id = " . (int) $campaignId . "
        ");
        if ($conn->errno) {
            throw new RuntimeException($conn->error);
        }

        foreach ($normalized as $party) {
            $details = premium_party_details($party);
            $partyName = (string) ($details['name'] ?? '');
            $conn->query("
                INSERT INTO premium_campaign_allied_parties (
                    campaign_id,
                    party_acronym,
                    party_name
                ) VALUES (
                    " . (int) $campaignId . ",
                    " . premium_sql_quote($conn, $party) . ",
                    " . premium_sql_quote($conn, $partyName !== '' ? $partyName : null) . "
                )
                ON DUPLICATE KEY UPDATE
                    party_name = VALUES(party_name),
                    updated_at = CURRENT_TIMESTAMP
            ");
            if ($conn->errno) {
                throw new RuntimeException($conn->error);
            }
        }

        $conn->commit();
        return true;
    } catch (Throwable) {
        $conn->rollback();
        return false;
    }
}

function premium_resolve_2024_municipality_name(mysqli $conn, string $municipio): string
{
    $municipio = trim($municipio);
    if ($municipio === '') {
        return '';
    }

    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $rows = queryAll($conn, "
            SELECT DISTINCT nm_municipio
            FROM resumo_votacao_2024_se
            WHERE nm_municipio IS NOT NULL
        ");

        foreach ($rows as $row) {
            $name = trim((string) ($row['nm_municipio'] ?? ''));
            if ($name === '') {
                continue;
            }

            $cache[premium_normalize_text($name)] = $name;
        }

        $rows = queryAll($conn, "
            SELECT DISTINCT nm_municipio
            FROM candidatos_situacao_2024
            WHERE nm_municipio IS NOT NULL
        ");

        foreach ($rows as $row) {
            $name = trim((string) ($row['nm_municipio'] ?? ''));
            if ($name === '') {
                continue;
            }

            $key = premium_normalize_text($name);
            if (!isset($cache[$key])) {
                $cache[$key] = $name;
            }
        }
    }

    return $cache[premium_normalize_text($municipio)] ?? $municipio;
}

function premium_leader_votes_election_year(string $cargo, int $campaignBaselineYear = 2022): int
{
    $normalized = premium_normalize_cargo($cargo);
    if (in_array($normalized, ['PREFEITO', 'VICE-PREFEITO', 'VEREADOR'], true)) {
        return 2024;
    }

    return $campaignBaselineYear;
}

function premium_normalize_cargo(string $cargo): string
{
    $normalized = premium_normalize_text($cargo);
    if ($normalized === '') {
        return '';
    }

    return match ($normalized) {
        'DEPUTADA FEDERAL', 'DEPUTADO FEDERAL' => 'DEPUTADO FEDERAL',
        'DEPUTADA ESTADUAL', 'DEPUTADO ESTADUAL' => 'DEPUTADO ESTADUAL',
        'SENADORA', 'SENADOR' => 'SENADOR',
        'GOVERNADORA', 'GOVERNADOR' => 'GOVERNADOR',
        'VICE PREFEITA', 'VICE PREFEITO', 'VICEPREFEITA', 'VICEPREFEITO' => 'VICE-PREFEITO',
        'PREFEITA', 'PREFEITO' => 'PREFEITO',
        'VEREADORA', 'VEREADOR' => 'VEREADOR',
        default => $normalized,
    };
}

function premium_cargo_variants(string $cargo): array
{
    $normalized = premium_normalize_cargo($cargo);
    if ($normalized === '') {
        return [];
    }

    $variants = [
        'DEPUTADO FEDERAL' => ['Deputado Federal', 'Deputada Federal'],
        'DEPUTADO ESTADUAL' => ['Deputado Estadual', 'Deputada Estadual'],
        'SENADOR' => ['Senador', 'Senadora'],
        'GOVERNADOR' => ['Governador', 'Governadora'],
        'VICE-PREFEITO' => ['Vice-prefeito', 'Vice-prefeita', 'Vice Prefeito', 'Vice Prefeita'],
        'PREFEITO' => ['Prefeito', 'Prefeita'],
        'VEREADOR' => ['Vereador', 'Vereadora'],
    ];

    return $variants[$normalized] ?? [$cargo];
}

function premium_leader_type_bucket(string $cargo): string
{
    $normalized = premium_normalize_cargo($cargo);

    return match ($normalized) {
        'PREFEITO' => 'prefeito',
        'VEREADOR' => 'vereador',
        default => 'sem_mandato',
    };
}

function premium_leader_type_label(string $bucket): string
{
    return match ($bucket) {
        'prefeito' => 'Prefeito',
        'vereador' => 'Vereador',
        default => 'Liderança sem mandato',
    };
}

function premium_supported_baseline_years(): array
{
    return [
        2018 => 'votacao_2018',
        2022 => 'votacao_2022',
    ];
}

function premium_resolve_baseline_year(int $year): int
{
    return array_key_exists($year, premium_supported_baseline_years()) ? $year : 2022;
}

function premium_baseline_table_for_year(int $year): ?string
{
    $resolvedYear = premium_resolve_baseline_year($year);
    $tables = premium_supported_baseline_years();

    return $tables[$resolvedYear] ?? null;
}

function premium_baseline_label(int $year): string
{
    return (string) premium_resolve_baseline_year($year);
}

function premium_baseline_table_exists(mysqli $conn, int $year): bool
{
    static $cache = [];

    $table = premium_baseline_table_for_year($year);
    if ($table === null) {
        return false;
    }

    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    $row = querySingle($conn, "
        SHOW TABLES LIKE " . premium_sql_quote($conn, $table) . "
    ");

    $cache[$table] = !empty($row);

    return $cache[$table];
}

function premium_table_has_column(mysqli $conn, string $table, string $column): bool
{
    static $cache = [];

    $cacheKey = $table . '.' . $column;
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $row = querySingle($conn, "
        SHOW COLUMNS FROM {$table} LIKE " . premium_sql_quote($conn, $column) . "
    ");

    $cache[$cacheKey] = !empty($row);

    return $cache[$cacheKey];
}

function premium_table_exists(mysqli $conn, string $table): bool
{
    static $cache = [];

    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    $row = querySingle($conn, "
        SHOW TABLES LIKE " . premium_sql_quote($conn, $table) . "
    ");

    $cache[$table] = !empty($row);

    return $cache[$table];
}

function premium_region_definitions(): array
{
    return [
        'Alto Sertão Sergipano' => [
            'Canindé de São Francisco',
            'Gararu',
            'Monte Alegre de Sergipe',
            'Nossa Senhora da Glória',
            'Nossa Senhora de Lourdes',
            'Poço Redondo',
            'Porto da Folha',
        ],
        'Agreste Central' => [
            'Areia Branca',
            'Campo do Brito',
            'Carira',
            'Frei Paulo',
            'Itabaiana',
            'Macambira',
            'Malhador',
            'Moita Bonita',
            'Nossa Senhora Aparecida',
            'Pedra Mole',
            'Pinhão',
            'Ribeirópolis',
            'São Domingos',
            'São Miguel do Aleixo',
        ],
        'Baixo São Francisco' => [
            'Amparo de São Francisco',
            'Brejo Grande',
            'Canhoba',
            'Cedro de São João',
            'Ilha das Flores',
            'Japoatã',
            'Malhada dos Bois',
            'Muribeca',
            'Neópolis',
            'Pacatuba',
            'Propriá',
            'Santana do São Francisco',
            'São Francisco',
            'Telha',
        ],
        'Centro Sul Sergipano' => [
            'Lagarto',
            'Poço Verde',
            'Riachão do Dantas',
            'Simão Dias',
            'Tobias Barreto',
        ],
        'Grande Aracaju' => [
            'Aracaju',
            'Barra dos Coqueiros',
            'Itaporanga d\'Ajuda',
            'Laranjeiras',
            'Maruim',
            'Nossa Senhora do Socorro',
            'Riachuelo',
            'Santa Rosa de Lima',
            'Santo Amaro das Brotas',
            'São Cristóvão',
        ],
        'Leste Sergipano' => [
            'Capela',
            'Carmópolis',
            'Divina Pastora',
            'General Maynard',
            'Japaratuba',
            'Pirambu',
            'Rosário do Catete',
            'Siriri',
        ],
        'Médio Sertão Sergipano' => [
            'Aquidabã',
            'Cumbe',
            'Feira Nova',
            'Graccho Cardoso',
            'Itabi',
            'Nossa Senhora das Dores',
        ],
        'Sul Sergipano' => [
            'Arauá',
            'Boquim',
            'Cristinápolis',
            'Estância',
            'Indiaroba',
            'Itabaianinha',
            'Pedrinhas',
            'Salgado',
            'Santa Luzia do Itanhy',
            'Tomar do Geru',
            'Umbaúba',
        ],
    ];
}

function premium_region_lookup(): array
{
    static $lookup = null;
    if ($lookup !== null) {
        return $lookup;
    }

    $lookup = []; 
    foreach (premium_region_definitions() as $region => $municipios) {
        foreach ($municipios as $municipio) {
            $lookup[premium_normalize_text($municipio)] = $region;
        }
    }

    $lookup[premium_normalize_text('Araua')] = 'Sul Sergipano';
    $lookup[premium_normalize_text('Gracho Cardoso')] = 'Médio Sertão Sergipano';

    return $lookup;
}

function premium_region_for_city(string $city): ?string
{
    $lookup = premium_region_lookup();
    $key = premium_normalize_text($city);

    return $lookup[$key] ?? null;
}

function premium_region_choices(): array
{
    return array_keys(premium_region_definitions());
}

function premium_default_settings(): array
{
    return [
        'baseline_retention' => 0.30,
        'transfer_rate_default' => 30.00,
        'alignment_bonus' => 0.20,
        'visibility_weight' => 0.12,
        'investment_weight' => 0.10,
        'margin_weight' => 0.15,
        'small_city_bonus' => 0.15,
        'medium_city_bonus' => 0.08,
        'large_city_bonus' => 0.00,
        'scenario_conservative' => 0.90,
        'scenario_base' => 1.00,
        'scenario_optimistic' => 1.12,
        'small_city_threshold' => 10000,
        'medium_city_threshold' => 30000,
        'senate_state_government_support' => 0,
        'senate_government_multiplier' => 1.08,
        'senate_overlap_mode' => 'alert_only',
    ];
}

function premium_normalize_settings(array $settings): array
{
    $defaults = premium_default_settings();
    $merged = array_replace($defaults, $settings);

    foreach ($merged as $key => $value) {
        if (is_string($value) && is_numeric($value)) {
            $merged[$key] = str_contains($value, '.') ? (float) $value : (int) $value;
        }
    }
    $merged['senate_overlap_mode'] = premium_senate_overlap_mode($merged['senate_overlap_mode'] ?? 'alert_only');

    return $merged;
}

function premium_senate_overlap_modes(): array
{
    return [
        'alert_only' => [
            'label' => 'Manter valores e apenas alertar',
            'description' => 'O sistema soma as fontes sem redutor automatico e destaca cidades com risco de sobreposicao ou percentual muito alto.',
        ],
        'manual' => [
            'label' => 'Revisao manual pelo usuario',
            'description' => 'O sistema nao reduz automaticamente; use os ajustes das fontes para reduzir migracao ou excluir bases duplicadas.',
        ],
        'automatic' => [
            'label' => 'Aplicar redutor automatico',
            'description' => 'O sistema reduz fontes subsequentes na mesma cidade para conter possivel dupla contagem de bases.',
        ],
    ];
}

function premium_senate_overlap_mode(mixed $value): string
{
    $mode = trim((string) $value);
    return array_key_exists($mode, premium_senate_overlap_modes()) ? $mode : 'alert_only';
}

function premium_senate_overlap_mode_label(mixed $value): string
{
    $mode = premium_senate_overlap_mode($value);
    return (string) (premium_senate_overlap_modes()[$mode]['label'] ?? $mode);
}

function premium_senate_overlap_mode_description(mixed $value): string
{
    $mode = premium_senate_overlap_mode($value);
    return (string) (premium_senate_overlap_modes()[$mode]['description'] ?? '');
}

function premium_load_campaign_settings(mysqli $conn, int $campaignId): array
{
    $row = querySingle($conn, "
        SELECT settings_json
        FROM premium_campaign_settings
        WHERE campaign_id = " . (int) $campaignId . "
        LIMIT 1
    ");

    if (!$row || empty($row['settings_json'])) {
        return premium_default_settings();
    }

    $decoded = json_decode((string) $row['settings_json'], true);
    if (!is_array($decoded)) {
        return premium_default_settings();
    }

    return premium_normalize_settings($decoded);
}

function premium_flash(string $type, string $message): void
{
    $_SESSION['premium_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function premium_push_flash(string $message, string $type = 'info'): void
{
    premium_flash($type, $message);
}

function premium_pull_flash(): ?array
{
    if (!isset($_SESSION['premium_flash'])) {
        return null;
    }

    $flash = $_SESSION['premium_flash'];
    unset($_SESSION['premium_flash']);

    return is_array($flash) ? $flash : null;
}

function premium_csrf_token(): string
{
    if (empty($_SESSION['premium_csrf'])) {
        $_SESSION['premium_csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['premium_csrf'];
}

function premium_validate_csrf(?string $token): bool
{
    if (empty($_SESSION['premium_csrf']) || $token === null || $token === '') {
        return false;
    }

    return hash_equals((string) $_SESSION['premium_csrf'], $token);
}

function premium_admin_user_emails(): array
{
    $raw = getenv('PREMIUM_ADMIN_EMAILS');
    if ($raw === false || trim($raw) === '') {
        $raw = 'rapware@gmail.com,premium@apoiacandidato.com.br,premium@eleicoes.local';
    }

    $emails = preg_split('/[,\s;]+/', strtolower($raw)) ?: [];
    $emails = array_map('trim', $emails);
    $emails = array_filter($emails, static fn(string $email): bool => $email !== '');

    return array_values(array_unique($emails));
}

function premium_is_admin_user(?array $user): bool
{
    if (!$user) {
        return false;
    }

    $userId = (int) ($user['id'] ?? 0);
    if ($userId === 1) {
        return true;
    }

    $email = strtolower(trim((string) ($user['email'] ?? '')));
    if ($email === '') {
        return false;
    }

    return in_array($email, premium_admin_user_emails(), true);
}

function premium_current_user(mysqli $conn): ?array
{
    $userId = (int) ($_SESSION['premium_user_id'] ?? 0);
    if ($userId <= 0) {
        return null;
    }

    $user = querySingle($conn, "
        SELECT id, name, email, status, created_at, last_login_at, trial_started_at, trial_ends_at
        FROM premium_users
        WHERE id = {$userId}
        LIMIT 1
    ");

    if (!$user) {
        unset($_SESSION['premium_user_id'], $_SESSION['premium_user_name'], $_SESSION['premium_login_at'], $_SESSION['premium_campaign_id']);
        return null;
    }

    if (($user['status'] ?? '') !== 'active') {
        unset($_SESSION['premium_user_id'], $_SESSION['premium_user_name'], $_SESSION['premium_login_at'], $_SESSION['premium_campaign_id']);
        return null;
    }

    if (premium_trial_is_expired($user)) {
        premium_mark_trial_expired($conn, (int) $user['id']);
        unset($_SESSION['premium_user_id'], $_SESSION['premium_user_name'], $_SESSION['premium_login_at'], $_SESSION['premium_campaign_id']);
        premium_flash('error', 'Seu teste de 7 dias expirou. Solicite acesso para continuar.');
        return null;
    }

    return $user;
}

function premium_get_users(mysqli $conn): array
{
    $users = queryAll($conn, "
        SELECT id, name, email, status, created_at, last_login_at, trial_started_at, trial_ends_at
        FROM premium_users
        ORDER BY
            CASE WHEN status = 'active' THEN 0 ELSE 1 END,
            created_at DESC,
            id DESC
    ");

    foreach ($users as &$user) {
        $user['id'] = (int) ($user['id'] ?? 0);
        $user['name'] = (string) ($user['name'] ?? '');
        $user['email'] = (string) ($user['email'] ?? '');
        $user['status'] = (string) ($user['status'] ?? 'inactive');
        $user['created_at'] = (string) ($user['created_at'] ?? '');
        $user['last_login_at'] = (string) ($user['last_login_at'] ?? '');
        $user['trial_started_at'] = (string) ($user['trial_started_at'] ?? '');
        $user['trial_ends_at'] = (string) ($user['trial_ends_at'] ?? '');
    }
    unset($user);

    return $users;
}

function premium_get_all_campaigns(mysqli $conn): array
{
    $campaigns = queryAll($conn, "
        SELECT
            c.*,
            u.name AS owner_name,
            u.email AS owner_email,
            u.status AS owner_status
        FROM premium_campaigns c
        LEFT JOIN premium_users u ON u.id = c.user_id
        ORDER BY
            CASE WHEN c.status = 'active' THEN 0 ELSE 1 END,
            c.updated_at DESC,
            c.id DESC
    ");

    foreach ($campaigns as &$campaign) {
        $campaign['id'] = (int) ($campaign['id'] ?? 0);
        $campaign['user_id'] = (int) ($campaign['user_id'] ?? 0);
        $campaign['campaign_name'] = (string) ($campaign['campaign_name'] ?? '');
        $campaign['candidate_name'] = (string) ($campaign['candidate_name'] ?? '');
        $campaign['candidate_cargo'] = (string) ($campaign['candidate_cargo'] ?? '');
        $campaign['candidate_number'] = premium_parse_candidate_number($campaign['candidate_number'] ?? null);
        $campaign['status'] = (string) ($campaign['status'] ?? 'inactive');
        $campaign['created_at'] = (string) ($campaign['created_at'] ?? '');
        $campaign['updated_at'] = (string) ($campaign['updated_at'] ?? '');
        $campaign['owner_name'] = (string) ($campaign['owner_name'] ?? '');
        $campaign['owner_email'] = (string) ($campaign['owner_email'] ?? '');
        $campaign['owner_status'] = (string) ($campaign['owner_status'] ?? 'inactive');
    }
    unset($campaign);

    return $campaigns;
}

function premium_fmt_datetime(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return 'Nunca';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('d/m/Y H:i', $timestamp);
}

function premium_login(mysqli $conn, string $email, string $password): bool
{
    $email = strtolower(trim($email));
    if ($email === '' || $password === '') {
        premium_flash('error', 'Credenciais inválidas ou conta inativa.');
        return false;
    }

    $user = querySingle($conn, "
        SELECT id, name, email, password_hash, status, trial_started_at, trial_ends_at
        FROM premium_users
        WHERE email = " . premium_sql_quote($conn, $email) . "
        LIMIT 1
    ");

    if (!$user) {
        premium_flash('error', 'Credenciais inválidas ou conta inativa.');
        return false;
    }

    if (($user['status'] ?? '') !== 'active') {
        if (premium_trial_is_expired($user)) {
            premium_flash('error', 'Seu teste de 7 dias expirou. Solicite acesso para continuar.');
        } else {
            premium_flash('error', 'Credenciais inválidas ou conta inativa.');
        }
        return false;
    }

    if (premium_trial_is_expired($user)) {
        premium_mark_trial_expired($conn, (int) $user['id']);
        premium_flash('error', 'Seu teste de 7 dias expirou. Solicite acesso para continuar.');
        return false;
    }

    if (!password_verify($password, (string) $user['password_hash'])) {
        premium_flash('error', 'Credenciais inválidas ou conta inativa.');
        return false;
    }

    session_regenerate_id(true);
    premium_refresh_session_cookie(premium_session_lifetime_seconds());
    $_SESSION['premium_user_id'] = (int) $user['id'];
    $_SESSION['premium_user_name'] = (string) ($user['name'] ?? '');
    $_SESSION['premium_login_at'] = date('Y-m-d H:i:s');

    $conn->query("
        UPDATE premium_users
        SET last_login_at = NOW()
        WHERE id = " . (int) $user['id'] . "
    ");

    return true;
}

function premium_logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] !== '' ? $params['path'] : '/',
            'domain' => (string) ($params['domain'] ?? ''),
            'secure' => (bool) ($params['secure'] ?? false),
            'httponly' => true,
            'samesite' => (string) ($params['samesite'] ?? 'Lax'),
        ]);
    }

    session_destroy();
}

function premium_clear_active_campaign(): void
{
    unset($_SESSION['premium_campaign_id']);
}

function premium_require_user(mysqli $conn, bool $json = false): ?array
{
    $user = premium_current_user($conn);
    if ($user) {
        return $user;
    }

    if ($json) {
        http_response_code(401);
        echo json_encode([
            'error' => true,
            'message' => 'Acesso premium requerido.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    premium_flash('error', 'Você precisa entrar com credenciais premium para continuar.');
    header('Location: premium');
    exit;
}

function premium_get_campaigns(mysqli $conn, int $userId): array
{
    $campaigns = queryAll($conn, "
        SELECT *
        FROM premium_campaigns
        WHERE user_id = " . (int) $userId . "
        UNION
        SELECT c.*
        FROM premium_campaigns c
        JOIN premium_campaign_access a ON a.campaign_id = c.id
        WHERE a.user_id = " . (int) $userId . "
        ORDER BY
            CASE WHEN status = 'active' THEN 0 ELSE 1 END,
            updated_at DESC,
            id DESC
    ");

    foreach ($campaigns as &$campaign) {
        $campaign['candidate_number'] = premium_parse_candidate_number($campaign['candidate_number'] ?? null);
    }
    unset($campaign);

    return $campaigns;
}

function premium_delete_campaign(mysqli $conn, int $campaignId): bool
{
    if ($campaignId <= 0) {
        return false;
    }

    $conn->begin_transaction();

    try {
        $conn->query("
            DELETE FROM premium_senate_vote_source_municipios
            WHERE source_id IN (
                SELECT id
                FROM premium_senate_vote_sources
                WHERE campaign_id = " . (int) $campaignId . "
            )
        ");

        if ($conn->errno) {
            throw new RuntimeException($conn->error);
        }

        foreach ([
            'premium_senate_vote_sources',
            'premium_forecast_runs',
            'premium_agenda',
            'premium_campaign_leaders',
            'premium_campaign_allied_parties',
            'premium_campaign_settings',
        ] as $table) {
            $conn->query("
                DELETE FROM {$table}
                WHERE campaign_id = " . (int) $campaignId . "
            ");

            if ($conn->errno) {
                throw new RuntimeException($conn->error);
            }
        }

        $conn->query("
            DELETE FROM premium_campaigns
            WHERE id = " . (int) $campaignId . "
            LIMIT 1
        ");

        if ($conn->errno) {
            throw new RuntimeException($conn->error);
        }

        $conn->commit();
        return true;
    } catch (Throwable) {
        $conn->rollback();
        return false;
    }
}

function premium_get_campaign(mysqli $conn, int $campaignId, int $userId, bool $isAdmin = false): ?array
{
    $accessCondition = $isAdmin
        ? '1 = 1'
        : "user_id = " . (int) $userId . "
               OR EXISTS (
                   SELECT 1 FROM premium_campaign_access a
                   WHERE a.campaign_id = " . (int) $campaignId . "
                     AND a.user_id = " . (int) $userId . "
               )";

    $campaign = querySingle($conn, "
        SELECT *
        FROM premium_campaigns
        WHERE id = " . (int) $campaignId . "
          AND (" . $accessCondition . ")
        LIMIT 1
    ");

    if ($campaign) {
        $campaign['candidate_number'] = premium_parse_candidate_number($campaign['candidate_number'] ?? null);
    }

    return $campaign ?: null;
}

function premium_get_campaign_members(mysqli $conn, int $campaignId): array
{
    $members = queryAll($conn, "
        SELECT
            u.id,
            u.name,
            u.email,
            u.status,
            a.created_at,
            a.created_by,
            cb.name AS created_by_name
        FROM premium_campaign_access a
        JOIN premium_users u ON u.id = a.user_id
        LEFT JOIN premium_users cb ON cb.id = a.created_by
        WHERE a.campaign_id = " . (int) $campaignId . "
        ORDER BY a.created_at ASC
    ");

    return $members ?? [];
}

function premium_render_campaign_members_panel(array $members, array $campaign, string $csrf): string
{
    $html = [];
    $campaignId = (int) ($campaign['id'] ?? 0);
    $campaignName = premium_escape_html($campaign['campaign_name'] ?? '');

    $html[] = '<section class="panel" id="membersPanelBody">';
    $html[] = '  <div class="eyebrow">Gestão de acesso</div>';
    $html[] = '  <h3>Membros do gabinete</h3>';

    if (!empty($members)) {
        $html[] = '  <div style="margin-bottom: 2rem;">';
        $html[] = '    <table class="admin-user-table" style="width:100%;">';
        $html[] = '      <thead>';
        $html[] = '        <tr>';
        $html[] = '          <th>Nome</th>';
        $html[] = '          <th>E-mail</th>';
        $html[] = '          <th>Adicionado em</th>';
        $html[] = '          <th style="text-align:right;">Ação</th>';
        $html[] = '        </tr>';
        $html[] = '      </thead>';
        $html[] = '      <tbody>';

        foreach ($members as $member) {
            $memberId = (int) ($member['id'] ?? 0);
            $memberName = premium_escape_html($member['name'] ?? '');
            $memberEmail = premium_escape_html($member['email'] ?? '');
            $createdAt = premium_fmt_datetime($member['created_at'] ?? '');
            $createdByName = premium_escape_html($member['created_by_name'] ?? 'Desconhecido');

            $html[] = '        <tr>';
            $html[] = '          <td>' . $memberName . '</td>';
            $html[] = '          <td style="opacity: 0.8; font-size: 0.9rem;">' . $memberEmail . '</td>';
            $html[] = '          <td style="font-size: 0.9rem; opacity: 0.7;">' . $createdAt . '</td>';
            $html[] = '          <td style="text-align:right;">';
            $html[] = '            <form method="post" action="premium_actions.php" style="display:inline;" onsubmit="return confirm(\'Revogar acesso a ' . $memberName . '?\');">';
            $html[] = '              <input type="hidden" name="csrf" value="' . premium_escape_html($csrf) . '">';
            $html[] = '              <input type="hidden" name="action" value="revoke_gabinete_access">';
            $html[] = '              <input type="hidden" name="campaign_id" value="' . $campaignId . '">';
            $html[] = '              <input type="hidden" name="user_id" value="' . $memberId . '">';
            $html[] = '              <button class="btn danger btn-small" type="submit">Revogar</button>';
            $html[] = '            </form>';
            $html[] = '          </td>';
            $html[] = '        </tr>';
        }

        $html[] = '      </tbody>';
        $html[] = '    </table>';
        $html[] = '  </div>';
    } else {
        $html[] = '  <p style="opacity: 0.7; margin-bottom: 1.5rem;">Nenhum membro adicionado ainda.</p>';
    }

    $html[] = '  <div style="border-top: 1px solid var(--line); padding-top: 1.5rem;">';
    $html[] = '    <h4 style="margin-top: 0;">Adicionar novo membro</h4>';
    $html[] = '    <p style="opacity: 0.8; font-size: 0.9rem; margin-bottom: 1rem;">O novo membro terá acesso completo à campanha <strong>' . $campaignName . '</strong>.</p>';
    $html[] = '    <form method="post" action="premium_actions.php">';
    $html[] = '      <input type="hidden" name="csrf" value="' . premium_escape_html($csrf) . '">';
    $html[] = '      <input type="hidden" name="action" value="create_gabinete_user">';
    $html[] = '      <input type="hidden" name="campaign_id" value="' . $campaignId . '">';
    $html[] = '      <div class="form-grid">';
    $html[] = '        <label>Nome completo';
    $html[] = '          <input type="text" name="name" placeholder="João da Silva" required autocomplete="off">';
    $html[] = '        </label>';
    $html[] = '        <label>E-mail';
    $html[] = '          <input type="email" name="email" placeholder="joao@example.com" required autocomplete="off">';
    $html[] = '        </label>';
    $html[] = '        <label>Senha';
    $html[] = '          <input type="password" name="password" placeholder="••••••••" required autocomplete="new-password">';
    $html[] = '        </label>';
    $html[] = '        <label>Confirme a senha';
    $html[] = '          <input type="password" name="password_confirm" placeholder="••••••••" required autocomplete="new-password">';
    $html[] = '        </label>';
    $html[] = '      </div>';
    $html[] = '      <div class="action-row">';
    $html[] = '        <button class="btn primary" type="submit">Adicionar membro</button>';
    $html[] = '      </div>';
    $html[] = '    </form>';
    $html[] = '  </div>';
    $html[] = '</section>';

    return implode("\n", $html);
}

function premium_active_campaign(mysqli $conn, int $userId, bool $isAdmin = false): ?array
{
    $selectedId = (int) ($_SESSION['premium_campaign_id'] ?? 0);
    if ($selectedId > 0) {
        $campaign = premium_get_campaign($conn, $selectedId, $userId, $isAdmin);
        if ($campaign) {
            return $campaign;
        }
    }

    $campaigns = premium_get_campaigns($conn, $userId);
    if (!$campaigns) {
        return null;
    }

    return $campaigns[0];
}

function premium_leader_display_name(mysqli $conn, array $leader): string
{
    static $cache = [];

    $sourceSq = trim((string) ($leader['source_sq_candidato'] ?? ''));
    $sourceNr = (int) ($leader['source_nr_votavel'] ?? 0);
    $turno = max(1, (int) ($leader['source_turno'] ?? 1));
    $municipality = trim((string) ($leader['municipality'] ?? ''));
    $cargo = trim((string) ($leader['leader_cargo'] ?? ''));
    $leaderName = trim((string) ($leader['leader_name'] ?? ''));

    $cacheKey = implode('|', [
        'sq:' . $sourceSq,
        'nr:' . $sourceNr,
        't:' . $turno,
        'm:' . premium_normalize_text($municipality),
        'c:' . premium_normalize_text($cargo),
        'n:' . premium_normalize_text($leaderName),
    ]);

    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $row = null;
    if ($sourceSq !== '') {
        $row = querySingle($conn, "
            SELECT nm_urna_candidato, nm_candidato
            FROM candidatos_situacao_2024
            WHERE sq_candidato = " . premium_sql_quote($conn, $sourceSq) . "
              AND nr_turno = {$turno}
            ORDER BY nr_zona = 0 DESC, id DESC
            LIMIT 1
        ");
    }

    if (!$row && $sourceNr > 0) {
        $conditions = [
            'nr_turno = ' . $turno,
            'nr_cand = ' . $sourceNr,
        ];

        if ($municipality !== '') {
            $dbMunicipality = premium_resolve_2024_municipality_name($conn, $municipality);
            $codeRows = queryAll($conn, "
                SELECT DISTINCT cd_municipio
                FROM resumo_votacao_2024_se
                WHERE nm_municipio = " . premium_sql_quote($conn, $dbMunicipality) . "
                  AND cd_municipio IS NOT NULL
            ");
            $codes = [];
            foreach ($codeRows as $codeRow) {
                $code = (int) ($codeRow['cd_municipio'] ?? 0);
                if ($code > 0) {
                    $codes[] = $code;
                }
            }

            if ($codes) {
                $conditions[] = 'cd_municipio IN (' . implode(',', array_map('intval', $codes)) . ')';
            } else {
                $conditions[] = 'nm_municipio = ' . premium_sql_quote($conn, $dbMunicipality);
            }
        }

        if ($cargo !== '') {
            $conditions[] = 'ds_cargo = ' . premium_sql_quote($conn, $cargo);
        }

        $row = querySingle($conn, "
            SELECT nm_urna_candidato, nm_candidato
            FROM candidatos_situacao_2024
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY nr_zona = 0 DESC, id DESC
            LIMIT 1
        ");
    }

    $displayName = trim((string) ($row['nm_urna_candidato'] ?? ''));
    if ($displayName === '') {
        $displayName = trim((string) ($row['nm_candidato'] ?? ''));
    }
    if ($displayName === '') {
        $displayName = $leaderName;
    }

    $cache[$cacheKey] = $displayName;

    return $displayName;
}

function premium_set_active_campaign(int $campaignId): void
{
    $_SESSION['premium_campaign_id'] = $campaignId;
}

function premium_get_campaign_leaders(mysqli $conn, int $campaignId): array
{
    $leaders = queryAll($conn, "
        SELECT *
        FROM premium_campaign_leaders
        WHERE campaign_id = " . (int) $campaignId . "
        ORDER BY municipality ASC, leader_votes_2024 DESC, updated_at DESC, id DESC
    ");

    foreach ($leaders as &$leader) {
        $leader['id'] = (int) $leader['id'];
        $leader['campaign_id'] = (int) $leader['campaign_id'];
        $leader['source_nr_votavel'] = isset($leader['source_nr_votavel']) ? (int) $leader['source_nr_votavel'] : null;
        $leader['source_turno'] = isset($leader['source_turno']) ? (int) $leader['source_turno'] : 1;
        $leader['leader_votes_2024'] = (int) ($leader['leader_votes_2024'] ?? 0);
        $leader['margin_percent'] = (float) ($leader['margin_percent'] ?? 0);
        $leader['transfer_rate'] = (float) ($leader['transfer_rate'] ?? premium_default_settings()['transfer_rate_default']);
        $leader['aligned_with_executive'] = (int) ($leader['aligned_with_executive'] ?? 0);
        $leader['visibility_score'] = (float) ($leader['visibility_score'] ?? 50);
        $leader['investment_score'] = (float) ($leader['investment_score'] ?? 50);
        $leader['size_class'] = (string) ($leader['size_class'] ?? 'medium');
        $leader['region_name'] = (string) ($leader['region_name'] ?? '');
        $leader['municipality'] = (string) ($leader['municipality'] ?? '');
        $leader['leader_type'] = premium_leader_type_bucket((string) ($leader['leader_cargo'] ?? ''));
        $leader['leader_type_label'] = premium_leader_type_label((string) $leader['leader_type']);
        $leader['leader_display_name'] = premium_leader_display_name($conn, $leader);
        $leader['is_manual_projection'] = (int) ($leader['is_manual_projection'] ?? 0);
    }
    unset($leader);

    return $leaders;
}

function premium_load_agenda(mysqli $conn, int $campaignId): array
{
    premium_archive_overdue_agenda($conn, $campaignId);

    return queryAll($conn, "
        SELECT *
        FROM premium_agenda
        WHERE campaign_id = " . (int) $campaignId . "
        ORDER BY
            CASE status
                WHEN 'open' THEN 0
                WHEN 'doing' THEN 1
                WHEN 'done' THEN 2
                ELSE 3
            END,
            due_date IS NULL,
            due_date ASC,
            id DESC
    ");
}

function premium_load_pending_agenda(mysqli $conn, int $campaignId, int $limit = 5): array
{
    premium_archive_overdue_agenda($conn, $campaignId);

    $limit = max(1, $limit);

    return queryAll($conn, "
        SELECT *
        FROM premium_agenda
        WHERE campaign_id = " . (int) $campaignId . "
          AND status IN ('open', 'doing')
        ORDER BY
            due_date IS NULL,
            due_date ASC,
            id DESC
        LIMIT {$limit}
    ");
}

function premium_archive_overdue_agenda(mysqli $conn, int $campaignId): int
{
    if ($campaignId <= 0) {
        return 0;
    }

    $today = premium_sql_quote($conn, date('Y-m-d'));

    $conn->query("
        UPDATE premium_agenda
        SET status = 'archived'
        WHERE campaign_id = " . (int) $campaignId . "
          AND status IN ('open', 'doing')
          AND due_date IS NOT NULL
          AND due_date < {$today}
    ");

    return (int) $conn->affected_rows;
}

function premium_candidate_baseline(mysqli $conn, string $candidateName, string $cargo, int $baselineYear = 2022): array
{
    $candidateName = trim($candidateName);
    $cargo = trim($cargo);
    $baselineYear = premium_resolve_baseline_year($baselineYear);
    $baselineTable = premium_baseline_table_for_year($baselineYear);

    $emptyBaseline = [
        'candidate_name' => $candidateName,
        'cargo' => $cargo,
        'candidate_number' => null,
        'total_votes' => 0,
        'municipalities' => [],
        'regions' => [],
        'found' => false,
        'municipality_count' => 0,
        'baseline_year' => $baselineYear,
        'source_table' => $baselineTable,
        'source_available' => $baselineTable !== null && premium_baseline_table_exists($conn, $baselineYear),
    ];

    if ($candidateName === '' || $cargo === '') {
        return $emptyBaseline;
    }

    $nameSql = premium_sql_quote($conn, $candidateName);
    $cargoVariants = premium_cargo_variants($cargo);
    if (!$cargoVariants) {
        return $emptyBaseline;
    }

    if ($baselineTable === null || !premium_baseline_table_exists($conn, $baselineYear)) {
        return $emptyBaseline;
    }

    $cargoConditions = array_map(
        static fn(string $variant): string => 'cargo = ' . premium_sql_quote($conn, $variant),
        $cargoVariants
    );

    $nameConditions = [
        "nm_candidato = {$nameSql}",
        "nm_urna_candidato = {$nameSql}",
        "nm_candidato LIKE " . premium_sql_quote($conn, '%' . $candidateName . '%'),
        "nm_urna_candidato LIKE " . premium_sql_quote($conn, '%' . $candidateName . '%'),
    ];

    if (preg_match('/^\d+$/', $candidateName) === 1) {
        $nameConditions[] = 'nr_candidato = ' . (int) $candidateName;
    }

    $candidateNumberRow = querySingle($conn, "
        SELECT nr_candidato AS candidate_number, SUM(qt_votos_nominais) AS total_votos
        FROM {$baselineTable}
        WHERE (" . implode(' OR ', $cargoConditions) . ")
          AND (" . implode(' OR ', $nameConditions) . ")
          AND nr_candidato IS NOT NULL
        GROUP BY nr_candidato
        ORDER BY total_votos DESC, nr_candidato ASC
        LIMIT 1
    ");
    $candidateNumber = premium_parse_candidate_number($candidateNumberRow['candidate_number'] ?? null);

    $rows = queryAll($conn, "
        SELECT municipio, SUM(qt_votos_nominais) AS total_votos
        FROM {$baselineTable}
        WHERE (" . implode(' OR ', $cargoConditions) . ")
          AND (" . implode(' OR ', $nameConditions) . ")
        GROUP BY municipio
        ORDER BY total_votos DESC, municipio ASC
    ");

    $lookup = premium_region_lookup();
    $municipalities = [];
    $regions = [];
    $totalVotes = 0;

    foreach ($rows as $row) {
        $municipio = (string) ($row['municipio'] ?? '');
        $votes = (int) ($row['total_votos'] ?? 0);
        $region = $lookup[premium_normalize_text($municipio)] ?? 'Sem região';
        $totalVotes += $votes;

        $municipalities[] = [
            'municipio' => $municipio,
            'total_votos' => $votes,
            'regiao' => $region,
        ];

        if (!isset($regions[$region])) {
            $regions[$region] = 0;
        }
        $regions[$region] += $votes;
    }

    $municipalityCount = count($municipalities);
    foreach ($municipalities as &$municipality) {
        $municipality['share'] = round(($municipality['total_votos'] / max(1, $totalVotes)) * 100, 2);
    }
    unset($municipality);

    $regionRows = [];
    foreach ($regions as $regionName => $votes) {
        $regionRows[] = [
            'regiao' => $regionName,
            'total_votos' => (int) $votes,
            'share' => $totalVotes > 0 ? round(($votes / $totalVotes) * 100, 2) : 0,
        ];
    }

    usort($regionRows, static fn(array $a, array $b): int => $b['total_votos'] <=> $a['total_votos']);

    return [
        'candidate_name' => $candidateName,
        'cargo' => $cargo,
        'candidate_number' => $candidateNumber,
        'total_votes' => $totalVotes,
        'municipalities' => $municipalities,
        'regions' => $regionRows,
        'found' => $totalVotes > 0,
        'municipality_count' => $municipalityCount,
        'baseline_year' => $baselineYear,
        'source_table' => $baselineTable,
        'source_available' => true,
    ];
}

function premium_default_size_class_from_votes(int $votes, array $settings): string
{
    $small = (int) ($settings['small_city_threshold'] ?? premium_default_settings()['small_city_threshold']);
    $medium = (int) ($settings['medium_city_threshold'] ?? premium_default_settings()['medium_city_threshold']);

    if ($votes <= $small) {
        return 'small';
    }

    if ($votes <= $medium) {
        return 'medium';
    }

    return 'large';
}

function premium_size_bonus(string $sizeClass, array $settings): float
{
    return match ($sizeClass) {
        'small' => (float) ($settings['small_city_bonus'] ?? 0),
        'medium' => (float) ($settings['medium_city_bonus'] ?? 0),
        default => (float) ($settings['large_city_bonus'] ?? 0),
    };
}

function premium_search_2024_candidates(mysqli $conn, string $cargo, string $municipio = '', string $query = '', int $turno = 1, array $filters = []): array
{
    $cargo = trim($cargo);
    $municipio = trim($municipio);
    $query = trim($query);
    $allyPartyKeys = premium_party_filter_keys((array) ($filters['ally_parties'] ?? []));

    if ($cargo === '') {
        $cargo = 'Prefeito';
    }

    $cargoVariants = premium_cargo_variants($cargo);
    if (!$cargoVariants) {
        $cargoVariants = [$cargo];
    }

    $dbMunicipio = $municipio !== '' ? premium_resolve_2024_municipality_name($conn, $municipio) : '';
    $dbMunicipioCodes = [];
    if ($dbMunicipio !== '') {
        $codeRows = queryAll($conn, "
            SELECT DISTINCT cd_municipio
            FROM resumo_votacao_2024_se
            WHERE nm_municipio = " . premium_sql_quote($conn, $dbMunicipio) . "
              AND cd_municipio IS NOT NULL
        ");
        foreach ($codeRows as $codeRow) {
            $code = (int) ($codeRow['cd_municipio'] ?? 0);
            if ($code > 0) {
                $dbMunicipioCodes[] = $code;
            }
        }
    }

    $conditions = [
        '(' . implode(' OR ', array_map(
            static fn(string $variant): string => 'r.ds_cargo = ' . premium_sql_quote($conn, $variant),
            $cargoVariants
        )) . ')',
        "r.nr_turno = " . (int) $turno,
        "r.tipo_voto = 'Candidato'",
    ];

    if ($dbMunicipio !== '') {
        $conditions[] = "r.nm_municipio = " . premium_sql_quote($conn, $dbMunicipio);
    }

    $summaryLimit = $allyPartyKeys ? 1000 : 200;
    $summaryRows = queryAll($conn, "
        SELECT
            MAX(r.cd_municipio) AS cd_municipio,
            r.nm_municipio,
            r.nr_votavel,
            MAX(NULLIF(r.sq_candidato, '')) AS sq_candidato,
            MAX(r.nm_votavel) AS nm_votavel,
            SUM(r.total_votos) AS total_votos,
            COUNT(DISTINCT r.nr_zona) AS zonas
        FROM resumo_votacao_2024_se r
        WHERE " . implode(' AND ', $conditions) . "
        GROUP BY r.nm_municipio, r.nr_votavel
        ORDER BY total_votos DESC, r.nm_municipio ASC, r.nr_votavel ASC
        LIMIT " . (int) $summaryLimit . "
    ");

    $metaConditions = [
        '(' . implode(' OR ', array_map(
            static fn(string $variant): string => 'ds_cargo = ' . premium_sql_quote($conn, $variant),
            $cargoVariants
        )) . ')',
        "nr_turno = " . (int) $turno,
    ];

    if ($dbMunicipioCodes) {
        $metaConditions[] = 'cd_municipio IN (' . implode(',', array_map('intval', $dbMunicipioCodes)) . ')';
    } elseif ($dbMunicipio !== '') {
        $metaConditions[] = "nm_municipio = " . premium_sql_quote($conn, $dbMunicipio);
    }

    $metaRows = queryAll($conn, "
        SELECT
            cd_municipio,
            COALESCE(NULLIF(MAX(CASE WHEN nr_zona = 0 THEN nm_municipio ELSE '' END), ''), MAX(nm_municipio)) AS nm_municipio,
            nr_cand,
            COALESCE(NULLIF(MAX(CASE WHEN nr_zona = 0 THEN nm_urna_candidato ELSE '' END), ''), MAX(nm_urna_candidato)) AS nm_urna_candidato,
            COALESCE(NULLIF(MAX(CASE WHEN nr_zona = 0 THEN nm_candidato ELSE '' END), ''), MAX(nm_candidato)) AS nm_candidato,
            COALESCE(NULLIF(MAX(CASE WHEN nr_zona = 0 THEN sg_partido ELSE '' END), ''), MAX(sg_partido)) AS sg_partido,
            COALESCE(NULLIF(MAX(CASE WHEN ds_sit_tot_turno NOT IN ('#NULO', '#NE', '') THEN ds_sit_tot_turno ELSE '' END), ''), MAX(ds_sit_tot_turno)) AS situacao,
            MAX(sq_candidato) AS sq_candidato
        FROM candidatos_situacao_2024
        WHERE " . implode(' AND ', $metaConditions) . "
        GROUP BY cd_municipio, nr_cand, sq_candidato
    ");

    $metaMap = [];
    foreach ($metaRows as $metaRow) {
        $cityKey = premium_normalize_text((string) ($metaRow['nm_municipio'] ?? ''));
        $cityCode = trim((string) ($metaRow['cd_municipio'] ?? ''));
        $numberKey = (string) (int) ($metaRow['nr_cand'] ?? 0);
        $sqKey = trim((string) ($metaRow['sq_candidato'] ?? ''));

        if ($sqKey !== '' && $sqKey !== '-3') {
            $metaMap['sq|' . $sqKey] = $metaRow;
        }

        if ($cityCode !== '' && $numberKey !== '0') {
            $metaMap['code_nr|' . $cityCode . '|' . $numberKey] = $metaRow;
        }

        if ($cityKey !== '' && $numberKey !== '0') {
            $metaMap['city_nr|' . $cityKey . '|' . $numberKey] = $metaRow;
        }

        $metaNames = array_filter([
            premium_normalize_text((string) ($metaRow['nm_urna_candidato'] ?? '')),
            premium_normalize_text((string) ($metaRow['nm_candidato'] ?? '')),
        ]);

        foreach ($metaNames as $nameKey) {
            $metaMap[$cityKey . '|' . $nameKey] = $metaRow;
        }
    }

    $rowsByCity = [];
    foreach ($summaryRows as $row) {
        $city = (string) ($row['nm_municipio'] ?? '');
        $rowsByCity[$city][] = $row;
    }

    $leaderRows = [];
    foreach ($rowsByCity as $city => $cityRows) {
        usort($cityRows, static fn(array $a, array $b): int => (int) $b['total_votos'] <=> (int) $a['total_votos']);
        $cityTotal = array_sum(array_map(static fn(array $item): int => (int) $item['total_votos'], $cityRows));
        $topVotes = (int) ($cityRows[0]['total_votos'] ?? 0);
        $runnerUpVotes = (int) ($cityRows[1]['total_votos'] ?? 0);
        $marginVotes = max(0, $topVotes - $runnerUpVotes);
        $marginPercent = $cityTotal > 0 ? round(($marginVotes / $cityTotal) * 100, 2) : 0.0;
        $regionName = premium_region_for_city($city) ?? 'Sem região';

        foreach ($cityRows as $row) {
            $cityCode = trim((string) ($row['cd_municipio'] ?? ''));
            $candidateSq = trim((string) ($row['sq_candidato'] ?? ''));
            $candidateNumber = (int) ($row['nr_votavel'] ?? 0);
            $candidateName = (string) ($row['nm_votavel'] ?? '');
            $meta = [];
            if ($candidateSq !== '' && $candidateSq !== '-3') {
                $meta = $metaMap['sq|' . $candidateSq] ?? [];
            }
            if (!$meta && $cityCode !== '' && $candidateNumber > 0) {
                $meta = $metaMap['code_nr|' . $cityCode . '|' . $candidateNumber] ?? [];
            }
            if (!$meta && $candidateNumber > 0) {
                $meta = $metaMap['city_nr|' . premium_normalize_text($city) . '|' . $candidateNumber] ?? [];
            }
            if (!$meta) {
                $meta = $metaMap[premium_normalize_text($city) . '|' . premium_normalize_text($candidateName)] ?? [];
            }
            $ballotName = trim((string) ($meta['nm_urna_candidato'] ?? ''));
            if ($ballotName === '') {
                $ballotName = $candidateName;
            }

            $legalName = trim((string) ($meta['nm_candidato'] ?? ''));
            if ($legalName === '') {
                $legalName = $candidateName;
            }

            $candidateKey = premium_normalize_text($candidateName . ' ' . $city);

            if ($query !== '') {
                $needle = premium_normalize_text($query);
                $candidateHaystack = premium_normalize_text($candidateName . ' ' . $ballotName . ' ' . $legalName . ' ' . $city . ' ' . (string) ($meta['sg_partido'] ?? '') . ' ' . (string) ($row['nr_votavel'] ?? '') . ' ' . (string) ($meta['nr_cand'] ?? ''));
                if ($needle !== '' && !str_contains($candidateHaystack, $needle)) {
                    continue;
                }
            }

            if ($allyPartyKeys) {
                $candidatePartyKey = premium_normalize_text((string) ($meta['sg_partido'] ?? ''));
                if ($candidatePartyKey === '' || !in_array($candidatePartyKey, $allyPartyKeys, true)) {
                    continue;
                }
            }

            $leaderRows[] = [
                'nm_municipio' => $city,
                'nr_votavel' => $candidateNumber,
                'nm_votavel' => $candidateName,
                'nm_urna_candidato' => $ballotName,
                'nm_candidato' => $legalName,
                'leader_display_name' => $ballotName,
                'total_votos' => (int) $row['total_votos'],
                'zonas' => (int) $row['zonas'],
                'sg_partido' => $meta['sg_partido'] ?? null,
                'situacao' => $meta['situacao'] ?? null,
                'sq_candidato' => !empty($meta['sq_candidato']) ? (string) $meta['sq_candidato'] : ($candidateSq !== '' && $candidateSq !== '-3' ? $candidateSq : null),
                'nr_cand' => !empty($meta['nr_cand']) ? (int) $meta['nr_cand'] : null,
                'turno' => $turno,
                'margin_votes' => $marginVotes,
                'margin_percent' => $marginPercent,
                'city_total_votes' => $cityTotal,
                'size_class' => premium_default_size_class_from_votes($cityTotal, premium_default_settings()),
                'region_name' => $regionName,
            ];
        }
    }

    usort($leaderRows, static function (array $a, array $b): int {
        $cityCompare = strcmp($a['nm_municipio'], $b['nm_municipio']);
        if ($cityCompare !== 0) {
            return $cityCompare;
        }

        return $b['total_votos'] <=> $a['total_votos'];
    });

    return $leaderRows;
}

function premium_is_senate_cargo(string $cargo): bool
{
    return premium_normalize_cargo($cargo) === 'SENADOR';
}

function premium_senate_relationship_choices(): array
{
    $choices = [
        'proprio' => 'Próprio candidato',
        'aliado_dep_federal' => 'Deputado federal aliado',
        'aliado_dep_estadual' => 'Deputado estadual aliado',
        'prefeito' => 'Prefeito',
        'vereador' => 'Vereador',
        'aliado' => 'Aliado',
        'manual' => 'Fonte manual',
    ];
    unset($choices['familiar']);

    return $choices;
}

function premium_senate_relationship_label(string $relationshipType): string
{
    $choices = premium_senate_relationship_choices();
    if ($relationshipType === 'familiar') {
        return $choices['aliado'];
    }

    return $choices[$relationshipType] ?? $choices['manual'];
}

function premium_senate_default_transfer_rates(): array
{
    return [
        'proprio' => 60.00,
        'aliado_dep_federal' => 45.00,
        'aliado_dep_estadual' => 35.00,
        'prefeito' => 35.00,
        'vice_prefeito' => 35.00,
        'vereador' => 25.00,
        'aliado' => 40.00,
        'manual' => 30.00,
    ];
}

function premium_senate_default_transfer_rate(string $relationshipType, string $cargo = '', int $year = 0): float
{
    $relationshipType = premium_senate_normalize_relationship_type($relationshipType, $cargo, $year);
    $rates = premium_senate_default_transfer_rates();

    return (float) ($rates[$relationshipType] ?? $rates['manual']);
}

function premium_senate_transfer_rate_bounds(string $relationshipType): array
{
    return match ($relationshipType) {
        'proprio'            => ['warn_low' => 40.0, 'warn_high' => 65.0],
        'aliado_dep_federal' => ['warn_low' => 25.0, 'warn_high' => 55.0],
        'aliado_dep_estadual'=> ['warn_low' => 15.0, 'warn_high' => 40.0],
        'prefeito'           => ['warn_low' => 15.0, 'warn_high' => 45.0],
        'vice_prefeito'      => ['warn_low' => 12.0, 'warn_high' => 40.0],
        'vereador'           => ['warn_low' =>  8.0, 'warn_high' => 30.0],
        'aliado'             => ['warn_low' =>  8.0, 'warn_high' => 30.0],
        default              => ['warn_low' =>  5.0, 'warn_high' => 35.0],
    };
}

function premium_senate_transfer_rate_alert(float $rate, string $relationshipType): string
{
    $bounds = premium_senate_transfer_rate_bounds($relationshipType);
    return ($rate < $bounds['warn_low'] || $rate > $bounds['warn_high']) ? 'warning' : 'ok';
}

function premium_senate_city_alert_level(float $pctEleitorado): string
{
    return match (true) {
        $pctEleitorado > 45.0 => 'danger',
        $pctEleitorado > 40.0 => 'caution',
        $pctEleitorado > 35.0 => 'warning',
        default               => 'ok',
    };
}

function premium_senate_is_own_baseline_source(array $row): bool
{
    $sourceYear = (int) ($row['source_year'] ?? 0);
    $sourceCargo = (string) ($row['source_cargo'] ?? '');

    return $sourceYear === 2018
        && premium_normalize_cargo($sourceCargo) === 'SENADOR'
        && premium_senate_normalize_relationship_type((string) ($row['relationship_type'] ?? ''), $sourceCargo, $sourceYear) === 'proprio';
}

function premium_senate_normalize_relationship_type(string $relationshipType, string $cargo = '', int $year = 0): string
{
    $relationshipType = trim($relationshipType);
    if ($relationshipType === 'familiar') {
        return 'aliado';
    }

    $choices = premium_senate_relationship_choices();
    if (isset($choices[$relationshipType])) {
        return $relationshipType;
    }

    $normalizedCargo = premium_normalize_cargo($cargo);
    if ($normalizedCargo === 'SENADOR' && $year === 2018) {
        return 'proprio';
    }

    return match ($normalizedCargo) {
        'DEPUTADO FEDERAL' => 'aliado_dep_federal',
        'DEPUTADO ESTADUAL' => 'aliado_dep_estadual',
        'PREFEITO', 'VICE-PREFEITO' => 'prefeito',
        'VEREADOR' => 'vereador',
        default => 'manual',
    };
}

function premium_city_suggestion(int $baselineVotes, int $projectedVotes, int $leaderCount): array
{
    $delta = $projectedVotes - $baselineVotes;
    $deltaPercent = $baselineVotes > 0 ? ($delta / $baselineVotes) * 100 : 0;
    $hasLeaders = $leaderCount > 0;
    $pct = static fn(float $v): string => number_format(abs($v), 1, ',', '.');

    if ($baselineVotes === 0 && $projectedVotes > 0) {
        return ['label' => 'Oportunidade nova', 'class' => 'opportunity', 'priority' => 3,
            'tip' => 'Sem votos históricos, mas com projeção positiva via lideranças. Invista em presença local para consolidar a cidade.'];
    }

    if ($baselineVotes === 0) {
        return ['label' => 'Sem dados históricos', 'class' => 'neutral', 'priority' => 4,
            'tip' => 'Nenhum voto registrado nesta cidade na última eleição e sem projeção. Avalie o potencial local.'];
    }

    if ($deltaPercent >= 25) {
        return ['label' => 'Crescimento forte', 'class' => 'positive-strong', 'priority' => 5,
            'tip' => 'Projeção supera o histórico em ' . $pct($deltaPercent) . '%. Consolide lideranças e priorize agenda na cidade.'];
    }

    if ($deltaPercent >= 5) {
        return ['label' => 'Crescimento', 'class' => 'positive', 'priority' => 5,
            'tip' => 'Projeção acima do histórico em ' . $pct($deltaPercent) . '%. Mantenha as lideranças ativas e monitore o avanço.'];
    }

    if ($deltaPercent >= -5) {
        $extra = $hasLeaders ? ' Mantenha o trabalho e avalie pontos de melhoria.' : ' Sem lideranças locais — cadastre referências para fortalecer o resultado.';
        return ['label' => 'Estável', 'class' => 'neutral', 'priority' => 4,
            'tip' => 'Projeção próxima ao histórico (variação de ' . $pct($deltaPercent) . '%).' . $extra];
    }

    if ($deltaPercent >= -20) {
        if (!$hasLeaders) {
            return ['label' => 'Base em risco', 'class' => 'warning', 'priority' => 2,
                'tip' => 'Queda de ' . $pct($deltaPercent) . '% sem lideranças ativas. Urgente: cadastre referências locais para conter a perda.'];
        }
        return ['label' => 'Atenção', 'class' => 'warning', 'priority' => 2,
            'tip' => 'Queda moderada de ' . $pct($deltaPercent) . '%. Revise as taxas de transferência e o engajamento das lideranças.'];
    }

    if (!$hasLeaders) {
        return ['label' => 'Alerta crítico', 'class' => 'danger', 'priority' => 1,
            'tip' => 'Queda severa de ' . $pct($deltaPercent) . '% sem nenhuma liderança. Defina referências locais imediatamente e reforce a presença na cidade.'];
    }

    return ['label' => 'Alerta', 'class' => 'danger', 'priority' => 1,
        'tip' => 'Queda severa de ' . $pct($deltaPercent) . '%. Revise a estratégia local, reforce lideranças e aumente a presença na cidade.'];
}

function premium_senate_is_municipal_source_cargo(string $cargo): bool
{
    return in_array(premium_normalize_cargo($cargo), ['PREFEITO', 'VICE-PREFEITO', 'VEREADOR'], true);
}

function premium_senate_scope_label(string $cargo, string $municipality = ''): string
{
    $municipality = trim($municipality);
    if (premium_senate_is_municipal_source_cargo($cargo)) {
        return $municipality !== '' ? $municipality : 'Votação municipal';
    }

    return 'Votação Estadual';
}

function premium_senate_family_name_tokens(string $candidateName): array
{
    $candidateTokens = preg_split('/\s+/', premium_normalize_text($candidateName)) ?: [];
    $ignored = ['DE', 'DA', 'DO', 'DAS', 'DOS', 'E', 'JOSE', 'MARIA', 'ANA'];

    $tokens = [];
    foreach ($candidateTokens as $token) {
        if (strlen($token) >= 4 && !in_array($token, $ignored, true)) {
            $tokens[] = $token;
        }
    }

    if (!$tokens) {
        return [];
    }

    return [end($tokens)];
}

function premium_senate_shared_family_name(string $candidateName, string $sourceName): bool
{
    $sourceText = ' ' . premium_normalize_text($sourceName) . ' ';
    foreach (premium_senate_family_name_tokens($candidateName) as $token) {
        if (str_contains($sourceText, ' ' . $token . ' ')) {
            return true;
        }
    }

    return false;
}

function premium_senate_guess_relationship(array $source, array $campaign): string
{
    $sourceYear = (int) ($source['source_year'] ?? 0);
    $sourceCargo = (string) ($source['source_cargo'] ?? '');
    $normalizedCargo = premium_normalize_cargo($sourceCargo);
    $candidateName = (string) ($campaign['candidate_name'] ?? '');
    $sourceName = trim((string) ($source['source_candidate_name'] ?? '') . ' ' . (string) ($source['source_ballot_name'] ?? ''));
    $candidateKey = premium_normalize_text($candidateName);
    $sourceKey = premium_normalize_text($sourceName);

    if ($candidateKey !== '' && str_contains(' ' . $sourceKey . ' ', ' ' . $candidateKey . ' ')) {
        return 'proprio';
    }

    return premium_senate_normalize_relationship_type('', $sourceCargo, $sourceYear);
}

function premium_senate_confidence_for_source(array $source, string $relationshipType, array $campaign): float
{
    $sourceYear = (int) ($source['source_year'] ?? 0);
    $normalizedCargo = premium_normalize_cargo((string) ($source['source_cargo'] ?? ''));

    if ($relationshipType === 'proprio' && $sourceYear === 2018 && $normalizedCargo === 'SENADOR') {
        return 90.00;
    }

    if ($relationshipType === 'proprio') {
        return 70.00;
    }

    return match ($relationshipType) {
        'aliado_dep_federal', 'aliado_dep_estadual' => 50.00,
        'prefeito', 'vereador' => 45.00,
        default => 40.00,
    };
}

function premium_senate_source_key(array $source): string
{
    $sq = trim((string) ($source['source_sq_candidato'] ?? ''));
    if ($sq !== '') {
        return implode('|', [
            (int) ($source['source_year'] ?? 0),
            premium_normalize_cargo((string) ($source['source_cargo'] ?? '')),
            'sq:' . premium_normalize_text($sq),
        ]);
    }

    $number = (int) ($source['source_number'] ?? 0);

    return implode('|', [
        (int) ($source['source_year'] ?? 0),
        premium_normalize_cargo((string) ($source['source_cargo'] ?? '')),
        premium_senate_is_municipal_source_cargo((string) ($source['source_cargo'] ?? ''))
            ? 'scope:' . premium_normalize_text((string) ($source['source_scope_label'] ?? ''))
            : 'scope:estadual',
        $number > 0 ? 'nr:' . $number : 'name:' . premium_normalize_text((string) ($source['source_candidate_name'] ?? '')),
    ]);
}

function premium_senate_search_cargo_variants(string $cargoFilter): array
{
    return match ($cargoFilter) {
        'deputado_federal' => ['Deputado Federal', 'Deputada Federal'],
        'deputado_estadual' => ['Deputado Estadual', 'Deputada Estadual'],
        'prefeito' => ['Prefeito', 'Prefeita', 'Vice-prefeito', 'Vice-prefeita', 'Vice Prefeito', 'Vice Prefeita'],
        'vereador' => ['Vereador', 'Vereadora'],
        default => [],
    };
}

function premium_senate_search_cargo_order(string $cargo): int
{
    return match (premium_normalize_cargo($cargo)) {
        'DEPUTADO FEDERAL' => 1,
        'DEPUTADO ESTADUAL' => 2,
        'PREFEITO', 'VICE-PREFEITO' => 3,
        'VEREADOR' => 4,
        'SENADOR' => 0,
        default => 9,
    };
}

function premium_get_party_allies(mysqli $conn, int $year, string $party, string $scopeType = 'state', string $municipality = ''): array
{
    premium_ensure_senate_tables($conn);

    $party = strtoupper(trim($party));
    if ($party === '') {
        return [];
    }

    $partySql = premium_sql_quote($conn, $party);
    $conditions = [
        'election_year = ' . (int) $year,
        '(anchor_party = ' . $partySql . ' OR ally_party = ' . $partySql . ')',
    ];

    $scopeType = $scopeType === 'municipal' ? 'municipal' : 'state';
    $conditions[] = 'scope_type = ' . premium_sql_quote($conn, $scopeType);
    if ($scopeType === 'municipal' && trim($municipality) !== '') {
        $conditions[] = 'municipality = ' . premium_sql_quote($conn, trim($municipality));
    }

    $rows = queryAll($conn, "
        SELECT DISTINCT
            CASE
                WHEN anchor_party = {$partySql} THEN ally_party
                ELSE anchor_party
            END AS related_party
        FROM premium_party_alliances
        WHERE " . implode(' AND ', $conditions) . "
        ORDER BY related_party ASC
    ");

    $allies = [$party];
    foreach ($rows as $row) {
        $ally = strtoupper(trim((string) ($row['related_party'] ?? '')));
        if ($ally !== '') {
            $allies[] = $ally;
        }
    }

    return array_values(array_unique($allies));
}

function premium_search_historical_candidates(mysqli $conn, string $query, array $years = [2018, 2020, 2022, 2024], array $filters = []): array
{
    $query = trim($query);
    $cargoFilter = trim((string) ($filters['cargo_filter'] ?? 'all'));
    $municipalityFilter = trim((string) ($filters['municipality'] ?? ''));
    $partyFilter = strtoupper(trim((string) ($filters['party'] ?? '')));
    $allyParties = premium_expand_party_acronyms((array) ($filters['ally_parties'] ?? []));
    $hasActiveFilter = $cargoFilter !== '' && $cargoFilter !== 'all' || $municipalityFilter !== '' || $partyFilter !== '' || $allyParties !== [];

    if ($query === '' && !$hasActiveFilter) {
        return [];
    }

    $needleSql = $query !== '' ? premium_sql_quote($conn, '%' . $query . '%') : null;
    $numericNeedle = preg_match('/^\d+$/', $query) === 1 ? (int) $query : null;
    $years = array_values(array_unique(array_map('intval', $years)));
    $results = [];
    $filterCargoVariants = premium_senate_search_cargo_variants($cargoFilter);
    $filterCargoCondition = static function (mysqli $conn, string $column, array $variants): string {
        if (!$variants) {
            return '';
        }

        return '(' . implode(' OR ', array_map(
            static fn(string $variant): string => $column . ' = ' . premium_sql_quote($conn, $variant),
            $variants
        )) . ')';
    };

    foreach ([2018, 2022] as $year) {
        if (!in_array($year, $years, true) || !premium_baseline_table_exists($conn, $year)) {
            continue;
        }

        if (in_array($cargoFilter, ['prefeito', 'vereador'], true)) {
            continue;
        }

        $table = premium_baseline_table_for_year($year);
        if ($table === null) {
            continue;
        }

        $identityConditions = [];
        $filterConditions = [];
        if ($needleSql !== null) {
            $identityConditions[] = "nm_candidato LIKE {$needleSql}";
            $identityConditions[] = "nm_urna_candidato LIKE {$needleSql}";
        }
        if ($numericNeedle !== null) {
            $identityConditions[] = 'nr_candidato = ' . $numericNeedle;
            $identityConditions[] = 'sq_candidato = ' . $numericNeedle;
        }
        if ($partyFilter !== '') {
            $filterConditions[] = 'sg_partido = ' . premium_sql_quote($conn, $partyFilter);
        }
        if ($allyParties) {
            $filterConditions[] = 'sg_partido IN (' . implode(',', array_map(static fn(string $party): string => premium_sql_quote($conn, $party), $allyParties)) . ')';
        }

        $cargoCondition = $filterCargoCondition($conn, 'cargo', $filterCargoVariants);
        if ($cargoCondition !== '') {
            $filterConditions[] = $cargoCondition;
        }

        $whereParts = ['nr_turno = 1'];
        if ($identityConditions) {
            $whereParts[] = '(' . implode(' OR ', $identityConditions) . ')';
        }
        foreach ($filterConditions as $filterCondition) {
            $whereParts[] = $filterCondition;
        }

        if (count($whereParts) <= 1) {
            continue;
        }

        $rows = queryAll($conn, "
            SELECT
                {$year} AS source_year,
                cargo AS source_cargo,
                COALESCE(NULLIF(MAX(nm_candidato), ''), MAX(nm_urna_candidato)) AS source_candidate_name,
                COALESCE(NULLIF(MAX(nm_urna_candidato), ''), MAX(nm_candidato)) AS source_ballot_name,
                MAX(sg_partido) AS source_party,
                nr_candidato AS source_number,
                MAX(sq_candidato) AS source_sq_candidato,
                SUM(qt_votos_nominais) AS source_total_votes,
                COUNT(DISTINCT municipio) AS municipality_count,
                MAX(situacao_turno) AS source_situacao
            FROM {$table}
            WHERE " . implode(' AND ', $whereParts) . "
            GROUP BY cargo, nr_candidato, sq_candidato, nm_candidato, nm_urna_candidato, sg_partido
            HAVING source_total_votes > 0
            ORDER BY source_total_votes DESC
            LIMIT 100
        ");

        foreach ($rows as $row) {
            $results[] = [
                'source_year' => (int) ($row['source_year'] ?? $year),
                'source_cargo' => (string) ($row['source_cargo'] ?? ''),
                'source_candidate_name' => (string) ($row['source_candidate_name'] ?? ''),
                'source_ballot_name' => (string) ($row['source_ballot_name'] ?? ''),
                'source_party' => (string) ($row['source_party'] ?? ''),
                'source_number' => premium_parse_candidate_number($row['source_number'] ?? null),
                'source_sq_candidato' => !empty($row['source_sq_candidato']) ? (string) $row['source_sq_candidato'] : null,
                'source_scope_label' => 'Votação Estadual',
                'source_total_votes' => (int) ($row['source_total_votes'] ?? 0),
                'source_vote_percent' => null,
                'municipality_count' => (int) ($row['municipality_count'] ?? 0),
                'source_situacao' => (string) ($row['source_situacao'] ?? ''),
            ];
        }
    }

    if (in_array(2020, $years, true) && premium_table_exists($conn, 'resumo_votacao_2020_se')) {
        $metaMatches = [];

        if (($needleSql !== null || $numericNeedle !== null) && premium_table_exists($conn, 'candidatos_situacao_2020')) {
            $metaIdentityConditions = [];
            $metaFilterConditions = ['nr_turno = 1'];
            if ($needleSql !== null) {
                $metaIdentityConditions[] = "nm_candidato LIKE {$needleSql}";
                $metaIdentityConditions[] = "nm_urna_candidato LIKE {$needleSql}";
            }
            if ($numericNeedle !== null) {
                $metaIdentityConditions[] = 'CAST(nr_cand AS UNSIGNED) = ' . $numericNeedle;
                $metaIdentityConditions[] = 'sq_candidato = ' . premium_sql_quote($conn, (string) $numericNeedle);
            }
            if ($metaIdentityConditions) {
                $metaFilterConditions[] = '(' . implode(' OR ', $metaIdentityConditions) . ')';
            }
            $cargoCondition = $filterCargoCondition($conn, 'ds_cargo', $filterCargoVariants);
            if ($cargoCondition !== '') {
                $metaFilterConditions[] = $cargoCondition;
            }
            if ($municipalityFilter !== '') {
                $metaFilterConditions[] = 'nm_municipio = ' . premium_sql_quote($conn, $municipalityFilter);
            }
            if ($partyFilter !== '') {
                $metaFilterConditions[] = 'sg_partido = ' . premium_sql_quote($conn, $partyFilter);
            }
            if ($allyParties) {
                $metaFilterConditions[] = 'sg_partido IN (' . implode(',', array_map(static fn(string $party): string => premium_sql_quote($conn, $party), $allyParties)) . ')';
            }

            $metaMatches = queryAll($conn, "
                SELECT
                    ds_cargo,
                    nm_municipio,
                    MAX(cd_municipio) AS cd_municipio,
                    CAST(nr_cand AS UNSIGNED) AS nr_cand,
                    MAX(nm_candidato) AS nm_candidato,
                    MAX(nm_urna_candidato) AS nm_urna_candidato,
                    MAX(sg_partido) AS sg_partido,
                    MAX(sq_candidato) AS sq_candidato,
                    MAX(ds_sit_tot_turno) AS ds_sit_tot_turno
                FROM candidatos_situacao_2020
                WHERE " . implode(' AND ', $metaFilterConditions) . "
                GROUP BY ds_cargo, nm_municipio, nr_cand, sq_candidato
                LIMIT 100
            ");
        }

        $summaryIdentityConditions = [];
        $summaryFilterConditions = [
            'r.nr_turno = 1',
            "r.tipo_voto = 'Candidato'",
        ];
        if ($needleSql !== null) {
            $summaryIdentityConditions[] = "r.nm_votavel LIKE {$needleSql}";
        }
        if ($numericNeedle !== null) {
            $summaryIdentityConditions[] = 'r.nr_votavel = ' . $numericNeedle;
            $summaryIdentityConditions[] = 'r.sq_candidato = ' . premium_sql_quote($conn, (string) $numericNeedle);
        }

        $metaSummaryConditions = [];
        foreach (array_slice($metaMatches, 0, 40) as $metaRow) {
            $cargo = trim((string) ($metaRow['ds_cargo'] ?? ''));
            $municipality = trim((string) ($metaRow['nm_municipio'] ?? ''));
            $number = (int) ($metaRow['nr_cand'] ?? 0);
            if ($cargo !== '' && $municipality !== '' && $number > 0) {
                $metaSummaryConditions[] = '(r.ds_cargo = ' . premium_sql_quote($conn, $cargo) . ' AND r.nm_municipio = ' . premium_sql_quote($conn, $municipality) . ' AND r.nr_votavel = ' . $number . ')';
            }
        }

        if ($summaryIdentityConditions || $metaSummaryConditions) {
            $summaryFilterConditions[] = '(' . implode(' OR ', array_merge($summaryIdentityConditions, $metaSummaryConditions)) . ')';
        }

        $cargoCondition = $filterCargoCondition($conn, 'r.ds_cargo', $filterCargoVariants);
        if ($cargoCondition !== '') {
            $summaryFilterConditions[] = $cargoCondition;
        }
        if ($municipalityFilter !== '') {
            $summaryFilterConditions[] = 'r.nm_municipio = ' . premium_sql_quote($conn, $municipalityFilter);
        }
        if ($allyParties) {
            $summaryFilterConditions[] = 'm.sg_partido IN (' . implode(',', array_map(static fn(string $party): string => premium_sql_quote($conn, $party), $allyParties)) . ')';
        } elseif ($partyFilter !== '') {
            $summaryFilterConditions[] = 'm.sg_partido = ' . premium_sql_quote($conn, $partyFilter);
        }

        $rows = queryAll($conn, "
            SELECT
                2020 AS source_year,
                r.ds_cargo AS source_cargo,
                MAX(r.nm_votavel) AS source_ballot_name,
                COALESCE(NULLIF(MAX(m.nm_candidato), ''), MAX(r.nm_votavel)) AS source_candidate_name,
                COALESCE(NULLIF(MAX(m.nm_urna_candidato), ''), MAX(r.nm_votavel)) AS source_ballot_name_meta,
                MAX(m.sg_partido) AS row_source_party,
                MAX(m.sq_candidato) AS row_source_sq_candidato,
                r.nm_municipio AS source_scope_label,
                r.nr_votavel AS source_number,
                MAX(NULLIF(r.sq_candidato, '')) AS source_sq_candidato,
                SUM(r.total_votos) AS source_total_votes,
                MAX(rm.votos_candidato) AS scope_total_votes,
                CASE
                    WHEN MAX(rm.votos_candidato) > 0
                    THEN ROUND((SUM(r.total_votos) / MAX(rm.votos_candidato)) * 100, 2)
                    ELSE NULL
                END AS source_vote_percent,
                COUNT(DISTINCT r.nm_municipio) AS municipality_count
            FROM resumo_votacao_2020_se r
            LEFT JOIN (
                SELECT nr_turno, ds_cargo, cd_municipio,
                       CAST(nr_cand AS UNSIGNED) AS nr_cand_uint,
                       MAX(nm_candidato) AS nm_candidato,
                       MAX(nm_urna_candidato) AS nm_urna_candidato,
                       MAX(sg_partido) AS sg_partido,
                       MAX(sq_candidato) AS sq_candidato,
                       MAX(ds_sit_tot_turno) AS ds_sit_tot_turno
                FROM candidatos_situacao_2020
                GROUP BY nr_turno, ds_cargo, cd_municipio, CAST(nr_cand AS UNSIGNED)
            ) m
              ON m.nr_turno = r.nr_turno
             AND m.ds_cargo = r.ds_cargo
             AND m.cd_municipio = r.cd_municipio
             AND m.nr_cand_uint = r.nr_votavel
            LEFT JOIN resumo_municipio_2020_se rm
              ON rm.nr_turno = r.nr_turno
             AND rm.ds_cargo = r.ds_cargo
             AND rm.cd_municipio = r.cd_municipio
            WHERE " . implode(' AND ', $summaryFilterConditions) . "
            GROUP BY r.ds_cargo, r.nm_municipio, r.nr_votavel
            HAVING source_total_votes > 0
            ORDER BY source_total_votes DESC, r.nm_municipio ASC
            LIMIT 100
        ");

        $metaByCargoCityNumber = [];
        foreach ($metaMatches as $metaRow) {
            $key = premium_normalize_cargo((string) ($metaRow['ds_cargo'] ?? '')) . '|' . premium_normalize_text((string) ($metaRow['nm_municipio'] ?? '')) . '|' . (int) ($metaRow['nr_cand'] ?? 0);
            $metaByCargoCityNumber[$key] = $metaRow;
        }

        foreach ($rows as $row) {
            $key = premium_normalize_cargo((string) ($row['source_cargo'] ?? '')) . '|' . premium_normalize_text((string) ($row['source_scope_label'] ?? '')) . '|' . (int) ($row['source_number'] ?? 0);
            $meta = $metaByCargoCityNumber[$key] ?? [];
            $candidateName = trim((string) ($meta['nm_candidato'] ?? ''));
            $ballotName = trim((string) ($meta['nm_urna_candidato'] ?? ''));
            if ($candidateName === '') {
                $candidateName = (string) ($row['source_candidate_name'] ?? '');
            }
            if ($ballotName === '') {
                $ballotName = (string) (($row['source_ballot_name_meta'] ?? '') ?: ($row['source_ballot_name'] ?? ''));
            }

            $results[] = [
                'source_year' => 2020,
                'source_cargo' => (string) ($row['source_cargo'] ?? ''),
                'source_candidate_name' => $candidateName,
                'source_ballot_name' => $ballotName,
                'source_party' => (string) (($meta['sg_partido'] ?? '') ?: ($row['row_source_party'] ?? '')),
                'source_number' => premium_parse_candidate_number($row['source_number'] ?? null),
                'source_sq_candidato' => null,
                'source_scope_label' => premium_senate_scope_label((string) ($row['source_cargo'] ?? ''), (string) ($row['source_scope_label'] ?? '')),
                'source_total_votes' => (int) ($row['source_total_votes'] ?? 0),
                'source_vote_percent' => ($row['source_vote_percent'] ?? null) !== null ? (float) $row['source_vote_percent'] : null,
                'municipality_count' => (int) ($row['municipality_count'] ?? 0),
                'source_situacao' => (string) ($meta['ds_sit_tot_turno'] ?? ''),
            ];
        }
    }

    if (in_array(2024, $years, true) && premium_table_exists($conn, 'resumo_votacao_2024_se')) {
        $hasResumoSq = premium_table_has_column($conn, 'resumo_votacao_2024_se', 'sq_candidato');
        $metaMatches = [];

        if (($needleSql !== null || $numericNeedle !== null) && premium_table_exists($conn, 'candidatos_situacao_2024')) {
            $metaIdentityConditions = [];
            $metaFilterConditions = ['nr_turno = 1'];
            if ($needleSql !== null) {
                $metaIdentityConditions[] = "nm_candidato LIKE {$needleSql}";
                $metaIdentityConditions[] = "nm_urna_candidato LIKE {$needleSql}";
            }
            if ($numericNeedle !== null) {
                $metaIdentityConditions[] = 'CAST(nr_cand AS UNSIGNED) = ' . $numericNeedle;
                $metaIdentityConditions[] = 'sq_candidato = ' . premium_sql_quote($conn, (string) $numericNeedle);
            }
            if ($metaIdentityConditions) {
                $metaFilterConditions[] = '(' . implode(' OR ', $metaIdentityConditions) . ')';
            }
            $cargoCondition = $filterCargoCondition($conn, 'ds_cargo', $filterCargoVariants);
            if ($cargoCondition !== '') {
                $metaFilterConditions[] = $cargoCondition;
            }
            if ($municipalityFilter !== '') {
                $metaFilterConditions[] = 'nm_municipio = ' . premium_sql_quote($conn, $municipalityFilter);
            }
            if ($partyFilter !== '') {
                $metaFilterConditions[] = 'sg_partido = ' . premium_sql_quote($conn, $partyFilter);
            }
            if ($allyParties) {
                $metaFilterConditions[] = 'sg_partido IN (' . implode(',', array_map(static fn(string $party): string => premium_sql_quote($conn, $party), $allyParties)) . ')';
            }

            $metaMatches = queryAll($conn, "
                SELECT
                    ds_cargo,
                    nm_municipio,
                    MAX(cd_municipio) AS cd_municipio,
                    CAST(nr_cand AS UNSIGNED) AS nr_cand,
                    MAX(nm_candidato) AS nm_candidato,
                    MAX(nm_urna_candidato) AS nm_urna_candidato,
                    MAX(sg_partido) AS sg_partido,
                    MAX(sq_candidato) AS sq_candidato,
                    MAX(ds_sit_tot_turno) AS ds_sit_tot_turno
                FROM candidatos_situacao_2024
                WHERE " . implode(' AND ', $metaFilterConditions) . "
                GROUP BY ds_cargo, nm_municipio, nr_cand, sq_candidato
                LIMIT 100
            ");
        }

        $summaryIdentityConditions = [];
        $summaryFilterConditions = [
            'r.nr_turno = 1',
            "r.tipo_voto = 'Candidato'",
        ];
        if ($needleSql !== null) {
            $summaryIdentityConditions[] = "r.nm_votavel LIKE {$needleSql}";
        }
        if ($numericNeedle !== null) {
            $summaryIdentityConditions[] = 'r.nr_votavel = ' . $numericNeedle;
            if ($hasResumoSq) {
                $summaryIdentityConditions[] = 'r.sq_candidato = ' . premium_sql_quote($conn, (string) $numericNeedle);
            }
        }

        $metaSummaryConditions = [];
        foreach (array_slice($metaMatches, 0, 40) as $metaRow) {
            $cargo = trim((string) ($metaRow['ds_cargo'] ?? ''));
            $municipality = trim((string) ($metaRow['nm_municipio'] ?? ''));
            $number = (int) ($metaRow['nr_cand'] ?? 0);
            if ($cargo !== '' && $municipality !== '' && $number > 0) {
                $metaSummaryConditions[] = '(r.ds_cargo = ' . premium_sql_quote($conn, $cargo) . ' AND r.nm_municipio = ' . premium_sql_quote($conn, $municipality) . ' AND r.nr_votavel = ' . $number . ')';
            }
        }

        if ($summaryIdentityConditions || $metaSummaryConditions) {
            $summaryFilterConditions[] = '(' . implode(' OR ', array_merge($summaryIdentityConditions, $metaSummaryConditions)) . ')';
        }

        $cargoCondition = $filterCargoCondition($conn, 'r.ds_cargo', $filterCargoVariants);
        if ($cargoCondition !== '') {
            $summaryFilterConditions[] = $cargoCondition;
        }
        if ($municipalityFilter !== '') {
            $summaryFilterConditions[] = 'r.nm_municipio = ' . premium_sql_quote($conn, $municipalityFilter);
        }
        if ($allyParties) {
            $summaryFilterConditions[] = 'm.sg_partido IN (' . implode(',', array_map(static fn(string $party): string => premium_sql_quote($conn, $party), $allyParties)) . ')';
        } elseif ($partyFilter !== '') {
            $summaryFilterConditions[] = 'm.sg_partido = ' . premium_sql_quote($conn, $partyFilter);
        }

        $sqSelect = $hasResumoSq ? "MAX(NULLIF(r.sq_candidato, '')) AS source_sq_candidato" : "NULL AS source_sq_candidato";
        $rows = queryAll($conn, "
            SELECT
                2024 AS source_year,
                r.ds_cargo AS source_cargo,
                MAX(r.nm_votavel) AS source_ballot_name,
                COALESCE(NULLIF(MAX(m.nm_candidato), ''), MAX(r.nm_votavel)) AS source_candidate_name,
                COALESCE(NULLIF(MAX(m.nm_urna_candidato), ''), MAX(r.nm_votavel)) AS source_ballot_name_meta,
                MAX(m.sg_partido) AS row_source_party,
                MAX(m.sq_candidato) AS row_source_sq_candidato,
                r.nm_municipio AS source_scope_label,
                r.nr_votavel AS source_number,
                {$sqSelect},
                SUM(r.total_votos) AS source_total_votes,
                MAX(rm.votos_candidato) AS scope_total_votes,
                CASE
                    WHEN MAX(rm.votos_candidato) > 0
                    THEN ROUND((SUM(r.total_votos) / MAX(rm.votos_candidato)) * 100, 2)
                    ELSE NULL
                END AS source_vote_percent,
                COUNT(DISTINCT r.nm_municipio) AS municipality_count
            FROM resumo_votacao_2024_se r
            LEFT JOIN (
                SELECT nr_turno, ds_cargo, nm_municipio,
                       CAST(nr_cand AS UNSIGNED) AS nr_cand_uint,
                       MAX(nm_candidato) AS nm_candidato,
                       MAX(nm_urna_candidato) AS nm_urna_candidato,
                       MAX(sg_partido) AS sg_partido,
                       MAX(sq_candidato) AS sq_candidato,
                       MAX(ds_sit_tot_turno) AS ds_sit_tot_turno
                FROM candidatos_situacao_2024
                GROUP BY nr_turno, ds_cargo, nm_municipio, CAST(nr_cand AS UNSIGNED)
            ) m
              ON m.nr_turno = r.nr_turno
             AND m.ds_cargo = r.ds_cargo
             AND m.nm_municipio = r.nm_municipio
             AND m.nr_cand_uint = r.nr_votavel
            LEFT JOIN resumo_municipio_2024_se rm
              ON rm.nr_turno = r.nr_turno
             AND rm.ds_cargo = r.ds_cargo
             AND rm.nm_municipio = r.nm_municipio
            WHERE " . implode(' AND ', $summaryFilterConditions) . "
            GROUP BY r.ds_cargo, r.nm_municipio, r.nr_votavel
            HAVING source_total_votes > 0
            ORDER BY source_total_votes DESC, r.nm_municipio ASC
            LIMIT 100
        ");

        $metaByCargoCityNumber = [];
        foreach ($metaMatches as $metaRow) {
            $key = premium_normalize_cargo((string) ($metaRow['ds_cargo'] ?? '')) . '|' . premium_normalize_text((string) ($metaRow['nm_municipio'] ?? '')) . '|' . (int) ($metaRow['nr_cand'] ?? 0);
            $metaByCargoCityNumber[$key] = $metaRow;
        }

        foreach ($rows as $row) {
            $key = premium_normalize_cargo((string) ($row['source_cargo'] ?? '')) . '|' . premium_normalize_text((string) ($row['source_scope_label'] ?? '')) . '|' . (int) ($row['source_number'] ?? 0);
            $meta = $metaByCargoCityNumber[$key] ?? [];
            $candidateName = trim((string) ($meta['nm_candidato'] ?? ''));
            $ballotName = trim((string) ($meta['nm_urna_candidato'] ?? ''));
            if ($candidateName === '') {
                $candidateName = (string) ($row['source_candidate_name'] ?? '');
            }
            if ($ballotName === '') {
                $ballotName = (string) (($row['source_ballot_name_meta'] ?? '') ?: ($row['source_ballot_name'] ?? ''));
            }

            $results[] = [
                'source_year' => 2024,
                'source_cargo' => (string) ($row['source_cargo'] ?? ''),
                'source_candidate_name' => $candidateName,
                'source_ballot_name' => $ballotName,
                'source_party' => (string) (($meta['sg_partido'] ?? '') ?: ($row['row_source_party'] ?? '')),
                'source_number' => premium_parse_candidate_number($row['source_number'] ?? null),
                'source_sq_candidato' => !empty($meta['sq_candidato']) ? (string) $meta['sq_candidato'] : (!empty($row['source_sq_candidato']) ? (string) $row['source_sq_candidato'] : (!empty($row['row_source_sq_candidato']) ? (string) $row['row_source_sq_candidato'] : null)),
                'source_scope_label' => premium_senate_scope_label((string) ($row['source_cargo'] ?? ''), (string) ($row['source_scope_label'] ?? '')),
                'source_total_votes' => (int) ($row['source_total_votes'] ?? 0),
                'source_vote_percent' => ($row['source_vote_percent'] ?? null) !== null ? (float) $row['source_vote_percent'] : null,
                'municipality_count' => (int) ($row['municipality_count'] ?? 0),
                'source_situacao' => (string) ($meta['ds_sit_tot_turno'] ?? ''),
            ];
        }
    }

    $deduped = [];
    foreach ($results as $row) {
        $key = premium_senate_source_key($row);
        if (!isset($deduped[$key]) || (int) ($row['source_total_votes'] ?? 0) > (int) ($deduped[$key]['source_total_votes'] ?? 0)) {
            $deduped[$key] = $row;
        }
    }

    $results = array_values($deduped);
    usort($results, static function (array $a, array $b): int {
        $yearCompare = (int) $a['source_year'] <=> (int) $b['source_year'];
        if ($yearCompare !== 0) {
            return $yearCompare;
        }

        return (int) ($b['source_total_votes'] ?? 0) <=> (int) ($a['source_total_votes'] ?? 0);
    });

    return $results;
}

function premium_get_candidate_votes_by_municipality(
    mysqli $conn,
    int $year,
    string $cargo,
    string $candidateName,
    ?string $sqCandidato = null,
    ?int $candidateNumber = null,
    ?string $sourceScopeLabel = null
): array {
    $year = (int) $year;
    $cargo = trim($cargo);
    $candidateName = trim($candidateName);
    $candidateNumber = $candidateNumber !== null && $candidateNumber > 0 ? $candidateNumber : null;
    $sqCandidato = trim((string) $sqCandidato);
    $sqCandidato = $sqCandidato !== '' ? $sqCandidato : null;
    $sourceScopeLabel = trim((string) $sourceScopeLabel);

    $cargoVariants = premium_cargo_variants($cargo);
    if (!$cargoVariants && $cargo !== '') {
        $cargoVariants = [$cargo];
    }

    $rows = [];
    if (in_array($year, [2018, 2022], true)) {
        $table = premium_baseline_table_for_year($year);
        if ($table === null || !premium_baseline_table_exists($conn, $year) || !$cargoVariants) {
            return [];
        }

        $cargoConditions = array_map(
            static fn(string $variant): string => 'cargo = ' . premium_sql_quote($conn, $variant),
            $cargoVariants
        );

        // Priority: valid sq_candidato > number+name fallback
        if ($sqCandidato !== null && (int) $sqCandidato > 0) {
            $identityConditions = ['sq_candidato = ' . premium_sql_quote($conn, $sqCandidato)];
        } else {
            $identityConditions = [];
            if ($candidateNumber !== null) {
                $identityConditions[] = 'nr_candidato = ' . $candidateNumber;
            }
            if ($candidateName !== '') {
                $identityConditions[] = 'nm_candidato = ' . premium_sql_quote($conn, $candidateName);
                $identityConditions[] = 'nm_urna_candidato = ' . premium_sql_quote($conn, $candidateName);
                $identityConditions[] = 'nm_candidato LIKE ' . premium_sql_quote($conn, '%' . $candidateName . '%');
                $identityConditions[] = 'nm_urna_candidato LIKE ' . premium_sql_quote($conn, '%' . $candidateName . '%');
            }
        }

        if (!$identityConditions) {
            return [];
        }

        $rows = queryAll($conn, "
            SELECT municipio AS municipality, SUM(qt_votos_nominais) AS source_votes
            FROM {$table}
            WHERE nr_turno = 1
              AND (" . implode(' OR ', $cargoConditions) . ")
              AND (" . implode(' OR ', $identityConditions) . ")
            GROUP BY municipio
            HAVING source_votes > 0
            ORDER BY source_votes DESC, municipio ASC
        ");
    } elseif ($year === 2020 && premium_table_exists($conn, 'resumo_votacao_2020_se')) {
        if (!$cargoVariants) {
            return [];
        }

        $cargoConditions = array_map(
            static fn(string $variant): string => 'r.ds_cargo = ' . premium_sql_quote($conn, $variant),
            $cargoVariants
        );

        // Priority: valid sq_candidato > number+name fallback
        if ($sqCandidato !== null && (int) $sqCandidato > 0) {
            $identityConditions = ['r.sq_candidato = ' . premium_sql_quote($conn, $sqCandidato)];
        } else {
            $identityConditions = [];
            if ($candidateNumber !== null) {
                $identityConditions[] = 'r.nr_votavel = ' . $candidateNumber;
            }
            if ($candidateName !== '') {
                $identityConditions[] = 'r.nm_votavel = ' . premium_sql_quote($conn, $candidateName);
                $identityConditions[] = 'r.nm_votavel LIKE ' . premium_sql_quote($conn, '%' . $candidateName . '%');
            }
        }
        if (!$identityConditions) {
            return [];
        }

        $municipalityFilter = '';
        if (premium_senate_is_municipal_source_cargo($cargo) && $sourceScopeLabel !== '' && premium_normalize_text($sourceScopeLabel) !== premium_normalize_text('VotaÃ§Ã£o Estadual')) {
            $municipalityFilter = ' AND r.nm_municipio = ' . premium_sql_quote($conn, $sourceScopeLabel);
        }

        $rows = queryAll($conn, "
            SELECT
                r.nm_municipio AS municipality,
                SUM(r.total_votos) AS source_votes,
                CASE
                    WHEN MAX(rm.votos_candidato) > 0
                    THEN ROUND((SUM(r.total_votos) / MAX(rm.votos_candidato)) * 100, 2)
                    ELSE NULL
                END AS source_vote_percent
            FROM resumo_votacao_2020_se r
            LEFT JOIN resumo_municipio_2020_se rm
              ON rm.nr_turno = r.nr_turno
             AND rm.ds_cargo = r.ds_cargo
             AND rm.nm_municipio = r.nm_municipio
            WHERE r.nr_turno = 1
              AND r.tipo_voto = 'Candidato'
              AND (" . implode(' OR ', $cargoConditions) . ")
              AND (" . implode(' OR ', $identityConditions) . ")
              {$municipalityFilter}
            GROUP BY r.nm_municipio
            HAVING source_votes > 0
            ORDER BY source_votes DESC, r.nm_municipio ASC
        ");
    } elseif ($year === 2024 && premium_table_exists($conn, 'resumo_votacao_2024_se')) {
        if (!$cargoVariants) {
            return [];
        }

        $hasResumoSq = premium_table_has_column($conn, 'resumo_votacao_2024_se', 'sq_candidato');
        $cargoConditions = array_map(
            static fn(string $variant): string => 'r.ds_cargo = ' . premium_sql_quote($conn, $variant),
            $cargoVariants
        );

        // Priority: valid sq_candidato > number+name fallback
        if ($sqCandidato !== null && $hasResumoSq && (int) $sqCandidato > 0) {
            $identityConditions = ['r.sq_candidato = ' . premium_sql_quote($conn, $sqCandidato)];
        } else {
            $identityConditions = [];
            if ($candidateNumber !== null) {
                $identityConditions[] = 'r.nr_votavel = ' . $candidateNumber;
            }
            if ($candidateName !== '') {
                $identityConditions[] = 'r.nm_votavel = ' . premium_sql_quote($conn, $candidateName);
                $identityConditions[] = 'r.nm_votavel LIKE ' . premium_sql_quote($conn, '%' . $candidateName . '%');
            }
        }
        if (!$identityConditions) {
            return [];
        }

        $municipalityFilter = '';
        if (premium_senate_is_municipal_source_cargo($cargo) && $sourceScopeLabel !== '' && premium_normalize_text($sourceScopeLabel) !== premium_normalize_text('Votação Estadual')) {
            $municipalityFilter = ' AND r.nm_municipio = ' . premium_sql_quote($conn, $sourceScopeLabel);
        }

        $rows = queryAll($conn, "
            SELECT
                r.nm_municipio AS municipality,
                SUM(r.total_votos) AS source_votes,
                CASE
                    WHEN MAX(rm.votos_candidato) > 0
                    THEN ROUND((SUM(r.total_votos) / MAX(rm.votos_candidato)) * 100, 2)
                    ELSE NULL
                END AS source_vote_percent
            FROM resumo_votacao_2024_se r
            LEFT JOIN resumo_municipio_2024_se rm
              ON rm.nr_turno = r.nr_turno
             AND rm.ds_cargo = r.ds_cargo
             AND rm.nm_municipio = r.nm_municipio
            WHERE r.nr_turno = 1
              AND r.tipo_voto = 'Candidato'
              AND (" . implode(' OR ', $cargoConditions) . ")
              AND (" . implode(' OR ', $identityConditions) . ")
              {$municipalityFilter}
            GROUP BY r.nm_municipio
            HAVING source_votes > 0
            ORDER BY source_votes DESC, r.nm_municipio ASC
        ");
    }

    $normalizedRows = [];
    foreach ($rows as $row) {
        $municipality = trim((string) ($row['municipality'] ?? ''));
        if ($municipality === '') {
            continue;
        }

        $normalizedRows[] = [
            'municipality' => $municipality,
            'region_name' => premium_region_for_city($municipality) ?? 'Sem região',
            'source_votes' => (int) ($row['source_votes'] ?? 0),
            'source_vote_percent' => ($row['source_vote_percent'] ?? null) !== null ? (float) $row['source_vote_percent'] : null,
        ];
    }

    return $normalizedRows;
}

function premium_repair_senate_sources_for_campaign(mysqli $conn, int $campaignId): void
{
    static $repairedCampaigns = [];
    if ($campaignId <= 0 || isset($repairedCampaigns[$campaignId])) {
        return;
    }

    $repairedCampaigns[$campaignId] = true;

    $rows = queryAll($conn, "
        SELECT id, source_cargo
        FROM premium_senate_vote_sources
        WHERE campaign_id = " . (int) $campaignId . "
          AND source_year IN (2020, 2024)
    ");

    foreach ($rows as $row) {
        if (premium_senate_is_municipal_source_cargo((string) ($row['source_cargo'] ?? ''))) {
            premium_refresh_senate_source_municipalities($conn, (int) ($row['id'] ?? 0));
        }
    }
}

function premium_get_senate_vote_sources(mysqli $conn, int $campaignId): array
{
    premium_ensure_senate_tables($conn);
    premium_repair_senate_sources_for_campaign($conn, $campaignId);

    $sources = queryAll($conn, "
        SELECT
            s.*,
            COALESCE(m.municipality_count, 0) AS municipality_count,
            COALESCE(m.projected_votes, 0) AS projected_votes
        FROM premium_senate_vote_sources s
        LEFT JOIN (
            SELECT
                source_id,
                COUNT(*) AS municipality_count,
                MIN(municipality) AS first_municipality,
                SUM(projected_votes) AS projected_votes
            FROM premium_senate_vote_source_municipios
            GROUP BY source_id
        ) m ON m.source_id = s.id
        WHERE s.campaign_id = " . (int) $campaignId . "
        ORDER BY s.source_year ASC, s.source_total_votes DESC, s.id DESC
    ");

    foreach ($sources as &$source) {
        $source['id'] = (int) ($source['id'] ?? 0);
        $source['campaign_id'] = (int) ($source['campaign_id'] ?? 0);
        $source['source_year'] = (int) ($source['source_year'] ?? 0);
        $source['source_number'] = premium_parse_candidate_number($source['source_number'] ?? null);
        $source['source_total_votes'] = (int) ($source['source_total_votes'] ?? 0);
        $source['relationship_type'] = premium_senate_normalize_relationship_type(
            (string) ($source['relationship_type'] ?? 'manual'),
            (string) ($source['source_cargo'] ?? ''),
            (int) ($source['source_year'] ?? 0)
        );
        $source['relationship_label'] = premium_senate_relationship_label((string) $source['relationship_type']);
        $source['transfer_rate'] = (float) ($source['transfer_rate'] ?? premium_senate_default_transfer_rate((string) $source['relationship_type']));
        $source['confidence_score'] = (float) ($source['confidence_score'] ?? 50);
        $source['source_scope_label'] = trim((string) ($source['source_scope_label'] ?? ''));
        if ($source['source_scope_label'] === '') {
            $source['source_scope_label'] = premium_senate_scope_label((string) ($source['source_cargo'] ?? ''), (string) ($source['first_municipality'] ?? ''));
        }
        $source['source_vote_percent'] = $source['source_vote_percent'] !== null ? (float) $source['source_vote_percent'] : null;
        $source['municipality_count'] = (int) ($source['municipality_count'] ?? 0);
        $source['projected_votes'] = (int) ($source['projected_votes'] ?? 0);
    }
    unset($source);

    return $sources;
}

function premium_refresh_senate_source_municipalities(mysqli $conn, int $sourceId): void
{
    premium_ensure_senate_tables($conn);

    $source = querySingle($conn, "
        SELECT *
        FROM premium_senate_vote_sources
        WHERE id = " . (int) $sourceId . "
        LIMIT 1
    ");

    if (!$source) {
        return;
    }

    $conn->query("
        DELETE FROM premium_senate_vote_source_municipios
        WHERE source_id = " . (int) $sourceId . "
    ");

    $lookupName = trim((string) ($source['source_ballot_name'] ?? ''));
    if ($lookupName === '') {
        $lookupName = trim((string) ($source['source_candidate_name'] ?? ''));
    }

    $rows = premium_get_candidate_votes_by_municipality(
        $conn,
        (int) ($source['source_year'] ?? 0),
        (string) ($source['source_cargo'] ?? ''),
        $lookupName,
        !empty($source['source_sq_candidato']) ? (string) $source['source_sq_candidato'] : null,
        premium_parse_candidate_number($source['source_number'] ?? null),
        !empty($source['source_scope_label']) ? (string) $source['source_scope_label'] : null
    );

    if (!$rows && (int) ($source['source_total_votes'] ?? 0) > 0) {
        $rows[] = [
            'municipality' => 'Sem município',
            'region_name' => 'Sem região',
            'source_votes' => (int) ($source['source_total_votes'] ?? 0),
            'source_vote_percent' => null,
        ];
    }

    $transferRate = (float) ($source['transfer_rate'] ?? 0);
    $sourceTotalVotes = 0;
    $sourceVotePercent = null;
    $sourceScopeLabel = trim((string) ($source['source_scope_label'] ?? ''));
    foreach ($rows as $row) {
        $sourceVotes = max(0, (int) ($row['source_votes'] ?? 0));
        $sourceTotalVotes += $sourceVotes;
        if (count($rows) === 1 && ($row['source_vote_percent'] ?? null) !== null) {
            $sourceVotePercent = (float) $row['source_vote_percent'];
        }
        if (
            count($rows) === 1
            && premium_senate_is_municipal_source_cargo((string) ($source['source_cargo'] ?? ''))
            && trim((string) ($row['municipality'] ?? '')) !== ''
        ) {
            $sourceScopeLabel = (string) ($row['municipality'] ?? '');
        }

        $projectedVotes = (int) round($sourceVotes * ($transferRate / 100));

        $conn->query("
            INSERT INTO premium_senate_vote_source_municipios (
                source_id,
                municipality,
                region_name,
                source_votes,
                projected_votes
            ) VALUES (
                " . (int) $sourceId . ",
                " . premium_sql_quote($conn, (string) ($row['municipality'] ?? '')) . ",
                " . premium_sql_quote($conn, (string) ($row['region_name'] ?? 'Sem região')) . ",
                " . $sourceVotes . ",
                " . $projectedVotes . "
            )
        ");
    }

    if ($sourceTotalVotes > 0) {
        $conn->query("
            UPDATE premium_senate_vote_sources
            SET source_total_votes = " . (int) $sourceTotalVotes . ",
                source_scope_label = " . premium_sql_quote($conn, $sourceScopeLabel !== '' ? $sourceScopeLabel : premium_senate_scope_label((string) ($source['source_cargo'] ?? ''))) . ",
                source_vote_percent = " . ($sourceVotePercent !== null ? number_format($sourceVotePercent, 2, '.', '') : 'NULL') . "
            WHERE id = " . (int) $sourceId . "
            LIMIT 1
        ");
    }
}

function premium_save_senate_vote_source(mysqli $conn, int $campaignId, array $data, ?int $sourceId = null): ?int
{
    premium_ensure_senate_tables($conn);

    $sourceYear = (int) ($data['source_year'] ?? 0);
    if (!in_array($sourceYear, [2018, 2020, 2022, 2024], true)) {
        $sourceYear = 2022;
    }

    $sourceCargo = trim((string) ($data['source_cargo'] ?? ''));
    if ($sourceCargo === '') {
        $sourceCargo = 'Manual';
    }

    $sourceCandidateName = trim((string) ($data['source_candidate_name'] ?? ''));
    $sourceBallotName = trim((string) ($data['source_ballot_name'] ?? ''));
    if ($sourceCandidateName === '' && $sourceBallotName !== '') {
        $sourceCandidateName = $sourceBallotName;
    }
    if ($sourceBallotName === '' && $sourceCandidateName !== '') {
        $sourceBallotName = $sourceCandidateName;
    }
    if ($sourceCandidateName === '') {
        return null;
    }

    $sourceParty = trim((string) ($data['source_party'] ?? ''));
    $sourceNumber = premium_parse_candidate_number($data['source_number'] ?? null);
    $sourceSq = trim((string) ($data['source_sq_candidato'] ?? ''));
    $sourceScopeLabel = trim((string) ($data['source_scope_label'] ?? ''));
    if ($sourceScopeLabel === '') {
        $sourceScopeLabel = premium_senate_scope_label($sourceCargo);
    }
    $sourceTotalVotes = max(0, (int) ($data['source_total_votes'] ?? 0));
    $sourceVotePercent = null;
    if (isset($data['source_vote_percent']) && $data['source_vote_percent'] !== '') {
        $sourceVotePercent = max(0, min(100, (float) $data['source_vote_percent']));
    }
    $relationshipType = premium_senate_normalize_relationship_type(
        (string) ($data['relationship_type'] ?? 'manual'),
        $sourceCargo,
        $sourceYear
    );
    $transferRate = (float) ($data['transfer_rate'] ?? premium_senate_default_transfer_rate($relationshipType, $sourceCargo, $sourceYear));
    $transferRate = max(0, min(100, $transferRate));
    $confidenceScore = (float) ($data['confidence_score'] ?? 50);
    $confidenceScore = max(0, min(100, $confidenceScore));
    $notes = trim((string) ($data['notes'] ?? ''));

    if ($sourceId !== null && $sourceId > 0) {
        $conn->query("
            UPDATE premium_senate_vote_sources
            SET source_year = " . $sourceYear . ",
                source_cargo = " . premium_sql_quote($conn, $sourceCargo) . ",
                source_candidate_name = " . premium_sql_quote($conn, $sourceCandidateName) . ",
                source_ballot_name = " . premium_sql_quote($conn, $sourceBallotName !== '' ? $sourceBallotName : null) . ",
                source_party = " . premium_sql_quote($conn, $sourceParty !== '' ? $sourceParty : null) . ",
                source_number = " . ($sourceNumber !== null ? (string) $sourceNumber : 'NULL') . ",
                source_sq_candidato = " . premium_sql_quote($conn, $sourceSq !== '' ? $sourceSq : null) . ",
                source_scope_label = " . premium_sql_quote($conn, $sourceScopeLabel !== '' ? $sourceScopeLabel : null) . ",
                source_total_votes = " . $sourceTotalVotes . ",
                source_vote_percent = " . ($sourceVotePercent !== null ? number_format($sourceVotePercent, 2, '.', '') : 'NULL') . ",
                relationship_type = " . premium_sql_quote($conn, $relationshipType) . ",
                transfer_rate = " . number_format($transferRate, 2, '.', '') . ",
                confidence_score = " . number_format($confidenceScore, 2, '.', '') . ",
                notes = " . premium_sql_quote($conn, $notes !== '' ? $notes : null) . "
            WHERE id = " . (int) $sourceId . "
              AND campaign_id = " . (int) $campaignId . "
            LIMIT 1
        ");

        if ($conn->errno) {
            return null;
        }

        premium_refresh_senate_source_municipalities($conn, (int) $sourceId);
        return (int) $sourceId;
    }

    $conn->query("
        INSERT INTO premium_senate_vote_sources (
            campaign_id,
            source_year,
            source_cargo,
            source_candidate_name,
            source_ballot_name,
            source_party,
            source_number,
            source_sq_candidato,
            source_scope_label,
            source_total_votes,
            source_vote_percent,
            relationship_type,
            transfer_rate,
            confidence_score,
            notes
        ) VALUES (
            " . (int) $campaignId . ",
            " . $sourceYear . ",
            " . premium_sql_quote($conn, $sourceCargo) . ",
            " . premium_sql_quote($conn, $sourceCandidateName) . ",
            " . premium_sql_quote($conn, $sourceBallotName !== '' ? $sourceBallotName : null) . ",
            " . premium_sql_quote($conn, $sourceParty !== '' ? $sourceParty : null) . ",
            " . ($sourceNumber !== null ? (string) $sourceNumber : 'NULL') . ",
            " . premium_sql_quote($conn, $sourceSq !== '' ? $sourceSq : null) . ",
            " . premium_sql_quote($conn, $sourceScopeLabel !== '' ? $sourceScopeLabel : null) . ",
            " . $sourceTotalVotes . ",
            " . ($sourceVotePercent !== null ? number_format($sourceVotePercent, 2, '.', '') : 'NULL') . ",
            " . premium_sql_quote($conn, $relationshipType) . ",
            " . number_format($transferRate, 2, '.', '') . ",
            " . number_format($confidenceScore, 2, '.', '') . ",
            " . premium_sql_quote($conn, $notes !== '' ? $notes : null) . "
        )
    ");

    if ($conn->errno) {
        return null;
    }

    $newSourceId = (int) $conn->insert_id;
    premium_refresh_senate_source_municipalities($conn, $newSourceId);

    return $newSourceId;
}

function premium_delete_senate_vote_source(mysqli $conn, int $campaignId, int $sourceId): bool
{
    premium_ensure_senate_tables($conn);

    $conn->query("
        DELETE FROM premium_senate_vote_source_municipios
        WHERE source_id IN (
            SELECT id
            FROM premium_senate_vote_sources
            WHERE id = " . (int) $sourceId . "
              AND campaign_id = " . (int) $campaignId . "
        )
    ");

    $conn->query("
        DELETE FROM premium_senate_vote_sources
        WHERE id = " . (int) $sourceId . "
          AND campaign_id = " . (int) $campaignId . "
        LIMIT 1
    ");

    return $conn->affected_rows > 0;
}

function premium_senate_should_skip_suggestion(array $source, array $campaign, string $relationshipType): bool
{
    $sourceName = premium_normalize_text(trim((string) ($source['source_candidate_name'] ?? '') . ' ' . (string) ($source['source_ballot_name'] ?? '')));
    $candidateName = premium_normalize_text((string) ($campaign['candidate_name'] ?? ''));
    $normalizedCargo = premium_normalize_cargo((string) ($source['source_cargo'] ?? ''));

    if ($normalizedCargo === 'SENADOR' && $relationshipType !== 'proprio') {
        return true;
    }

    foreach (['ALESSANDRO VIEIRA', 'ROGERIO CARVALHO', 'ANDRE DAVI'] as $blockedName) {
        if ($sourceName !== '' && str_contains($sourceName, premium_normalize_text($blockedName)) && ($candidateName === '' || !str_contains($sourceName, $candidateName))) {
            return true;
        }
    }

    return false;
}

function premium_suggest_senate_vote_sources(mysqli $conn, array $campaign): array
{
    if (!premium_is_senate_cargo((string) ($campaign['candidate_cargo'] ?? ''))) {
        return [];
    }

    $candidateName = trim((string) ($campaign['candidate_name'] ?? ''));
    if ($candidateName === '') {
        return [];
    }

    $existingKeys = [];
    foreach (premium_get_senate_vote_sources($conn, (int) ($campaign['id'] ?? 0)) as $source) {
        $existingKeys[premium_senate_source_key($source)] = true;
    }

    $queries = [$candidateName];
    foreach (premium_senate_family_name_tokens($candidateName) as $token) {
        $queries[] = $token;
    }

    $alliedPartiesByYear = [];
    $latestAnchorParty = '';
    $latestAnchorYear = 0;
    foreach (premium_search_historical_candidates($conn, $candidateName, [2018, 2022]) as $source) {
        $relationshipType = premium_senate_guess_relationship($source, $campaign);
        $party = strtoupper(trim((string) ($source['source_party'] ?? '')));
        $year = (int) ($source['source_year'] ?? 0);
        if ($relationshipType === 'proprio' && $party !== '' && in_array($year, [2018, 2022], true)) {
            $alliedPartiesByYear[$year] = premium_get_party_allies($conn, $year, $party, 'state');
            if ($year >= $latestAnchorYear) {
                $latestAnchorYear = $year;
                $latestAnchorParty = $party;
            }
        }
    }
    if ($latestAnchorParty !== '') {
        $alliedPartiesByYear[2020] = premium_get_party_allies($conn, 2020, $latestAnchorParty, 'municipal');
        $alliedPartiesByYear[2024] = premium_get_party_allies($conn, 2024, $latestAnchorParty, 'state');
    }

    $campaignAlliedParties = premium_get_campaign_allied_party_acronyms($conn, (int) ($campaign['id'] ?? 0));
    if ($campaignAlliedParties) {
        foreach ([2018, 2020, 2022, 2024] as $year) {
            $alliedPartiesByYear[$year] = array_values(array_unique(array_merge(
                $alliedPartiesByYear[$year] ?? [],
                $campaignAlliedParties
            )));
        }
    }

    $isPartyAligned = static function (array $source) use ($alliedPartiesByYear): bool {
        $year = (int) ($source['source_year'] ?? 0);
        $partyKey = premium_normalize_text((string) ($source['source_party'] ?? ''));
        $yearPartyKeys = premium_party_filter_keys($alliedPartiesByYear[$year] ?? []);

        return $partyKey !== '' && in_array($partyKey, $yearPartyKeys, true);
    };

    $suggestions = [];
    foreach (array_values(array_unique($queries)) as $query) {
        foreach (premium_search_historical_candidates($conn, $query, [2018, 2020, 2022, 2024]) as $source) {
            $key = premium_senate_source_key($source);
            if (isset($existingKeys[$key]) || isset($suggestions[$key])) {
                continue;
            }

            $relationshipType = premium_senate_guess_relationship($source, $campaign);
            if (premium_senate_should_skip_suggestion($source, $campaign, $relationshipType)) {
                continue;
            }

            $source['relationship_type'] = $relationshipType;
            $source['relationship_label'] = premium_senate_relationship_label($relationshipType);
            $source['party_alliance_match'] = $isPartyAligned($source);
            $source['transfer_rate'] = premium_senate_default_transfer_rate(
                $relationshipType,
                (string) ($source['source_cargo'] ?? ''),
                (int) ($source['source_year'] ?? 0)
            );
            $source['confidence_score'] = premium_senate_confidence_for_source($source, $relationshipType, $campaign);
            $source['suggestion_reason'] = match ($relationshipType) {
                'proprio' => 'Registro do próprio candidato encontrado na base histórica.',
                'aliado_dep_federal', 'aliado_dep_estadual' => 'Base legislativa potencialmente migrável para chapa majoritária.',
                'prefeito', 'vereador' => 'Liderança municipal histórica com potencial de transferência local.',
                default => 'Fonte encontrada na busca multiano; classifique antes de adicionar.',
            };

            $suggestions[$key] = $source;
        }
    }

    foreach ($alliedPartiesByYear as $year => $alliedParties) {
        foreach (['deputado_federal', 'deputado_estadual'] as $cargoFilter) {
            $addedForCargo = 0;
            foreach (premium_search_historical_candidates($conn, '', [(int) $year], [
                'cargo_filter' => $cargoFilter,
                'ally_parties' => $alliedParties,
            ]) as $source) {
                if ($addedForCargo >= 4) {
                    break;
                }

                $key = premium_senate_source_key($source);
                if (isset($existingKeys[$key]) || isset($suggestions[$key])) {
                    continue;
                }

                $relationshipType = premium_senate_guess_relationship($source, $campaign);
                if (premium_senate_should_skip_suggestion($source, $campaign, $relationshipType)) {
                    continue;
                }

                $source['relationship_type'] = $relationshipType;
                $source['relationship_label'] = premium_senate_relationship_label($relationshipType);
                $source['party_alliance_match'] = true;
                $source['transfer_rate'] = premium_senate_default_transfer_rate(
                    $relationshipType,
                    (string) ($source['source_cargo'] ?? ''),
                    (int) ($source['source_year'] ?? 0)
                );
                $source['confidence_score'] = premium_senate_confidence_for_source($source, $relationshipType, $campaign);
                $source['suggestion_reason'] = 'Partido do candidato ou alianca cadastrada no ano da eleicao; priorizado por cargo.';
                $suggestions[$key] = $source;
                $addedForCargo++;
            }
        }
    }

    $municipalAlliedPartiesByYear = [
        2020 => $alliedPartiesByYear[2020] ?? ($alliedPartiesByYear[2018] ?? []),
        2024 => $alliedPartiesByYear[2024] ?? ($alliedPartiesByYear[2022] ?? ($alliedPartiesByYear[2018] ?? [])),
    ];
    foreach ($municipalAlliedPartiesByYear as $municipalYear => $municipalAlliedParties) {
        if (!$municipalAlliedParties) {
            continue;
        }

        foreach (['prefeito', 'vereador'] as $cargoFilter) {
            $addedForCargo = 0;
            foreach (premium_search_historical_candidates($conn, '', [$municipalYear], [
                'cargo_filter' => $cargoFilter,
                'ally_parties' => $municipalAlliedParties,
            ]) as $source) {
                if ($addedForCargo >= 4) {
                    break;
                }

                $key = premium_senate_source_key($source);
                if (isset($existingKeys[$key]) || isset($suggestions[$key])) {
                    continue;
                }

                $relationshipType = premium_senate_guess_relationship($source, $campaign);
                if (premium_senate_should_skip_suggestion($source, $campaign, $relationshipType)) {
                    continue;
                }

                $source['relationship_type'] = $relationshipType;
                $source['relationship_label'] = premium_senate_relationship_label($relationshipType);
                $source['party_alliance_match'] = true;
                $source['transfer_rate'] = premium_senate_default_transfer_rate(
                    $relationshipType,
                    (string) ($source['source_cargo'] ?? ''),
                    (int) ($source['source_year'] ?? 0)
                );
                $source['confidence_score'] = premium_senate_confidence_for_source($source, $relationshipType, $campaign);
                $source['suggestion_reason'] = 'Partido do candidato ou alianca cadastrada; lideranca municipal priorizada.';
                $suggestions[$key] = $source;
                $addedForCargo++;
            }
        }
    }

    $suggestions = array_values($suggestions);
    usort($suggestions, static function (array $a, array $b): int {
        $isOwnA = (string) ($a['relationship_type'] ?? '') === 'proprio' ? 0 : 1;
        $isOwnB = (string) ($b['relationship_type'] ?? '') === 'proprio' ? 0 : 1;
        if ($isOwnA !== $isOwnB) {
            return $isOwnA <=> $isOwnB;
        }

        if ($isOwnA !== 0) {
            $alignedA = !empty($a['party_alliance_match']) ? 0 : 1;
            $alignedB = !empty($b['party_alliance_match']) ? 0 : 1;
            if ($alignedA !== $alignedB) {
                return $alignedA <=> $alignedB;
            }

            $cargoOrderA = premium_senate_search_cargo_order((string) ($a['source_cargo'] ?? ''));
            $cargoOrderB = premium_senate_search_cargo_order((string) ($b['source_cargo'] ?? ''));
            if ($cargoOrderA !== $cargoOrderB) {
                return $cargoOrderA <=> $cargoOrderB;
            }
        }

        $priority = [
            'proprio' => 0,
            'aliado_dep_federal' => 2,
            'aliado_dep_estadual' => 3,
            'prefeito' => 4,
            'vereador' => 5,
            'aliado' => 6,
            'manual' => 7,
        ];
        $priorityA = $priority[(string) ($a['relationship_type'] ?? 'manual')] ?? 9;
        $priorityB = $priority[(string) ($b['relationship_type'] ?? 'manual')] ?? 9;
        if ($priorityA !== $priorityB) {
            return $priorityA <=> $priorityB;
        }

        $scoreA = (float) ($a['confidence_score'] ?? 0);
        $scoreB = (float) ($b['confidence_score'] ?? 0);
        if ($scoreA !== $scoreB) {
            return $scoreB <=> $scoreA;
        }

        return (int) ($b['source_total_votes'] ?? 0) <=> (int) ($a['source_total_votes'] ?? 0);
    });

    return array_slice($suggestions, 0, 12);
}

function premium_empty_senate_forecast(array $settings = []): array
{
    $settings = premium_normalize_settings($settings ?: premium_default_settings());

    return [
        'settings' => $settings,
        'baseline' => [
            'found' => false,
            'total_votes' => 0,
            'municipalities' => [],
        ],
        'totals' => [
            'baseline_votes' => 0,
            'source_raw_votes' => 0,
            'source_projected_votes' => 0,
            'overlap_discount' => 0,
            'suggested_overlap_discount' => 0,
            'projected_conservative' => 0,
            'projected_base' => 0,
            'projected_optimistic' => 0,
            'system_projection' => 0,
            'source_count' => 0,
            'overlap_mode' => premium_senate_overlap_mode($settings['senate_overlap_mode'] ?? 'alert_only'),
            'overlap_mode_label' => premium_senate_overlap_mode_label($settings['senate_overlap_mode'] ?? 'alert_only'),
            'government_support' => !empty($settings['senate_state_government_support']) ? 1 : 0,
            'government_multiplier' => !empty($settings['senate_state_government_support'])
                ? max(1.00, min(1.30, (float) ($settings['senate_government_multiplier'] ?? 1.08)))
                : 1.00,
        ],
        'regions' => [],
        'cities' => [],
        'sources' => [],
        'leaders' => [],
    ];
}

function premium_build_senate_forecast(mysqli $conn, array $campaign, array $sources, array $settings): array
{
    $settings = premium_normalize_settings($settings);
    $campaignName = (string) ($campaign['candidate_name'] ?? '');
    $baseline = premium_candidate_baseline($conn, $campaignName, 'Senador', 2018);
    $forecast = premium_empty_senate_forecast($settings);
    $forecast['baseline'] = $baseline;

    $scenarioConservative = (float) ($settings['scenario_conservative'] ?? 0.90);
    $scenarioBase = (float) ($settings['scenario_base'] ?? 1.00);
    $scenarioOptimistic = (float) ($settings['scenario_optimistic'] ?? 1.12);
    $governmentMultiplier = !empty($settings['senate_state_government_support'])
        ? max(1.00, min(1.30, (float) ($settings['senate_government_multiplier'] ?? 1.08)))
        : 1.00;
    $overlapMode = premium_senate_overlap_mode($settings['senate_overlap_mode'] ?? 'alert_only');
    $applyOverlapReducer = $overlapMode === 'automatic';
    $lookup = premium_region_lookup();
    $cities = [];
    $sourceBreakdown = [];
    $citySources = [];

    $electorateMap = [];
    foreach (queryAll($conn, "SELECT nm_municipio, total_votos FROM resumo_municipio_2024_se WHERE nr_turno = 1 AND ds_cargo = 'Prefeito'") as $er) {
        $electorateMap[premium_normalize_text((string) ($er['nm_municipio'] ?? ''))] = max(0, (int) ($er['total_votos'] ?? 0));
    }

    foreach ($baseline['municipalities'] ?? [] as $row) {
        $municipality = (string) ($row['municipio'] ?? '');
        $key = premium_normalize_text($municipality);
        $cities[$key] = [
            'municipio'            => $municipality,
            'regiao'               => (string) ($row['regiao'] ?? ($lookup[$key] ?? 'Sem região')),
            'baseline_votes'       => (int) ($row['total_votos'] ?? 0),
            'eleitorado'           => $electorateMap[$key] ?? 0,
            'pct_eleitorado'       => 0.0,
            'alert_level'          => 'ok',
            'growth_warning'       => false,
            'growth_pct'           => 0.0,
            'capped'               => false,
            'cap_suggested'        => false,
            'electorate_cap'       => 0,
            'own_baseline_omitted' => false,
            'own_baseline_votes'   => 0,
            'own_baseline_projected_votes' => 0,
            'source_raw_votes'     => 0,
            'source_projected_votes' => 0,
            'overlap_discount'     => 0,
            'suggested_overlap_discount' => 0,
            'source_count'         => 0,
            'top_source'           => '',
            'projected_conservative' => 0,
            'projected_base'       => 0,
            'projected_optimistic' => 0,
            'system_projection'    => 0,
        ];
    }

    $sourceRows = queryAll($conn, "
        SELECT
            s.id AS source_id,
            s.source_year,
            s.source_cargo,
            s.source_candidate_name,
            s.source_ballot_name,
            s.source_party,
            s.relationship_type,
            s.transfer_rate,
            m.municipality,
            m.region_name,
            m.source_votes
        FROM premium_senate_vote_sources s
        JOIN premium_senate_vote_source_municipios m ON m.source_id = s.id
        WHERE s.campaign_id = " . (int) ($campaign['id'] ?? 0) . "
    ");

    $hasExplicitOwnSenateBase = false;
    foreach ($sources as $source) {
        if (
            (int) ($source['source_year'] ?? 0) === 2018
            && premium_normalize_cargo((string) ($source['source_cargo'] ?? '')) === 'SENADOR'
            && (string) ($source['relationship_type'] ?? '') === 'proprio'
        ) {
            $hasExplicitOwnSenateBase = true;
            break;
        }
    }

    if (!$hasExplicitOwnSenateBase && !empty($baseline['found'])) {
        foreach ($baseline['municipalities'] ?? [] as $row) {
            $sourceRows[] = [
                'source_id' => 0,
                'source_year' => 2018,
                'source_cargo' => 'Senador',
                'source_candidate_name' => $campaignName,
                'source_ballot_name' => $campaignName,
                'source_party' => '',
                'relationship_type' => 'proprio',
                'transfer_rate' => premium_senate_default_transfer_rate('proprio'),
                'municipality' => (string) ($row['municipio'] ?? ''),
                'region_name' => (string) ($row['regiao'] ?? 'Sem região'),
                'source_votes' => (int) ($row['total_votos'] ?? 0),
            ];
        }
    }

    $rowsByCity = [];
    foreach ($sourceRows as $row) {
        $municipality = trim((string) ($row['municipality'] ?? ''));
        if ($municipality === '') {
            continue;
        }

        $cityKey = premium_normalize_text($municipality);
        $transferRate = (float) ($row['transfer_rate'] ?? 0);
        $sourceVotes = max(0, (int) ($row['source_votes'] ?? 0));
        $rawProjected = (int) round($sourceVotes * ($transferRate / 100));
        $row['raw_projected_votes'] = $rawProjected;
        $rowsByCity[$cityKey][] = $row;

        if (!isset($cities[$cityKey])) {
            $cities[$cityKey] = [
                'municipio'            => $municipality,
                'regiao'               => (string) ($row['region_name'] ?? premium_region_for_city($municipality) ?? 'Sem região'),
                'baseline_votes'       => 0,
                'eleitorado'           => $electorateMap[$cityKey] ?? 0,
                'pct_eleitorado'       => 0.0,
                'alert_level'          => 'ok',
                'growth_warning'       => false,
                'growth_pct'           => 0.0,
                'capped'               => false,
                'cap_suggested'        => false,
                'electorate_cap'       => 0,
                'own_baseline_omitted' => false,
                'own_baseline_votes'   => 0,
                'own_baseline_projected_votes' => 0,
                'source_raw_votes'     => 0,
                'source_projected_votes' => 0,
                'overlap_discount'     => 0,
                'suggested_overlap_discount' => 0,
                'source_count'         => 0,
                'top_source'           => '',
                'projected_conservative' => 0,
                'projected_base'       => 0,
                'projected_optimistic' => 0,
                'system_projection'    => 0,
            ];
        }
    }

    foreach ($rowsByCity as $cityKey => $cityRows) {
        $hasCampaignSource = false;
        foreach ($cityRows as $sourceRow) {
            if (!premium_senate_is_own_baseline_source($sourceRow)) {
                $hasCampaignSource = true;
                break;
            }
        }

        // Cidade sem nenhuma fonte/liderança real cadastrada: ignora completamente.
        if (!$hasCampaignSource) {
            continue;
        }

        $filteredRows = [];
        $ownBaselineVotes = 0;
        $ownBaselineProjectedVotes = 0;

        foreach ($cityRows as $sourceRow) {
            if (premium_senate_is_own_baseline_source($sourceRow)) {
                $ownBaselineVotes += (int) ($sourceRow['source_votes'] ?? 0);
                $ownBaselineProjectedVotes += (int) ($sourceRow['raw_projected_votes'] ?? 0);
                continue;
            }

            $filteredRows[] = $sourceRow;
        }

        $cityRows = $filteredRows;
        $cities[$cityKey]['own_baseline_omitted'] = $ownBaselineVotes > 0 || $ownBaselineProjectedVotes > 0;
        $cities[$cityKey]['own_baseline_votes'] = $ownBaselineVotes;
        $cities[$cityKey]['own_baseline_projected_votes'] = $ownBaselineProjectedVotes;

        usort($cityRows, static fn(array $a, array $b): int => (int) ($b['raw_projected_votes'] ?? 0) <=> (int) ($a['raw_projected_votes'] ?? 0));

        $rawTotal = 0;
        $adjustedTotal = 0;
        $appliedOverlapDiscount = 0;
        $suggestedOverlapDiscount = 0;
        $citySourcesStart = count($citySources);
        foreach ($cityRows as $index => $sourceRow) {
            $raw = (int) ($sourceRow['raw_projected_votes'] ?? 0);
            $rawTotal += $raw;
            $overlapMultiplier = match (true) {
                $index === 0 => 1.00,
                $index === 1 => 0.75,
                $index === 2 => 0.60,
                $index <= 5  => 0.50,
                $index <= 9  => 0.40,
                $index <= 14 => 0.30,
                default      => 0.22,
            };
            $suggestedAdjusted = (int) round($raw * $overlapMultiplier);
            $adjusted = $applyOverlapReducer ? $suggestedAdjusted : $raw;
            $appliedOverlapDiscount += max(0, $raw - $adjusted);
            $suggestedOverlapDiscount += max(0, $raw - $suggestedAdjusted);
            $adjustedTotal += $adjusted;

            $sourceId = (int) ($sourceRow['source_id'] ?? 0);
            $sourceDisplayName = trim((string) ($sourceRow['source_ballot_name'] ?? ''));
            if ($sourceDisplayName === '') {
                $sourceDisplayName = trim((string) ($sourceRow['source_candidate_name'] ?? ''));
            }
            if (!isset($sourceBreakdown[$sourceId])) {
                $sourceBreakdown[$sourceId] = [
                    'id' => $sourceId,
                    'source_name' => $sourceDisplayName,
                    'source_year' => (int) ($sourceRow['source_year'] ?? 0),
                    'source_cargo' => (string) ($sourceRow['source_cargo'] ?? ''),
                    'relationship_type' => (string) ($sourceRow['relationship_type'] ?? 'manual'),
                    'relationship_label' => premium_senate_relationship_label((string) ($sourceRow['relationship_type'] ?? 'manual')),
                    'source_votes' => 0,
                    'raw_projected_votes' => 0,
                    'suggested_projected_votes' => 0,
                    'projected_votes' => 0,
                    'overlap_discount' => 0,
                ];
            }
            $sourceBreakdown[$sourceId]['source_votes'] += (int) ($sourceRow['source_votes'] ?? 0);
            $sourceBreakdown[$sourceId]['raw_projected_votes'] += $raw;
            $sourceBreakdown[$sourceId]['suggested_projected_votes'] += $suggestedAdjusted;
            $sourceBreakdown[$sourceId]['projected_votes'] += $adjusted;
            $sourceBreakdown[$sourceId]['overlap_discount'] += max(0, $raw - $adjusted);

            $citySources[] = [
                'source_id'          => $sourceId,
                'source_name'        => $sourceDisplayName,
                'source_party'       => (string) ($sourceRow['source_party'] ?? ''),
                'source_year'        => (int) ($sourceRow['source_year'] ?? 0),
                'source_cargo'       => (string) ($sourceRow['source_cargo'] ?? ''),
                'relationship_label' => premium_senate_relationship_label((string) ($sourceRow['relationship_type'] ?? 'manual')),
                'transfer_rate'      => (float) ($sourceRow['transfer_rate'] ?? 0),
                'municipality'       => trim((string) ($sourceRow['municipality'] ?? '')),
                'region_name'        => (string) ($sourceRow['region_name'] ?? 'Sem região'),
                'source_votes'       => (int) ($sourceRow['source_votes'] ?? 0),
                'raw_projected'      => $raw,
                'suggested_projected_votes' => $suggestedAdjusted,
                'projected_votes'    => $adjusted,
                'overlap_multiplier' => $overlapMultiplier,
                'overlap_applied'    => $applyOverlapReducer ? 1 : 0,
                'overlap_discount'   => max(0, $raw - $adjusted),
            ];
        }

        $baselineVotes = (int) ($cities[$cityKey]['baseline_votes'] ?? 0);
        $eleitorado    = (int) ($cities[$cityKey]['eleitorado'] ?? 0);
        $capBase       = (int) round(max($baselineVotes * 1.25, $rawTotal * 0.85));
        $baseProjectionBeforeCap = $adjustedTotal;
        if ($applyOverlapReducer && $capBase > 0) {
            $baseProjectionBeforeCap = min($baseProjectionBeforeCap, $capBase);
        }
        $baseProjection = $baseProjectionBeforeCap;
        $baseProjection = (int) round($baseProjection * $governmentMultiplier);

        $capped = false;
        $capSuggested = false;
        $electorateCap = 0;
        if ($eleitorado > 0) {
            $electorateCap = (int) round($eleitorado * 0.45);
            if ($baseProjection > $electorateCap) {
                if ($applyOverlapReducer) {
                    $baseProjection = $electorateCap;
                    $capped = true;
                } else {
                    $capSuggested = true;
                }
            }
        }

        $topSource = (string) (($cityRows[0]['source_ballot_name'] ?? '') ?: ($cityRows[0]['source_candidate_name'] ?? ''));

        $projectedBase = (int) round($baseProjection * $scenarioBase);
        $pctEleitorado = $eleitorado > 0 ? round(($projectedBase / $eleitorado) * 100.0, 1) : 0.0;

        // Redutor sugerido = apenas o excesso acima do limiar Revisão (25% do comparecimento).
        // Abaixo desse limiar o sistema aceita sem sugestão de corte.
        $revisaoThreshold = $eleitorado > 0 ? (int) round($eleitorado * 0.35) : 0;
        $suggestedOverlapDiscount = $revisaoThreshold > 0 ? max(0, $projectedBase - $revisaoThreshold) : 0;
        $cityOverThreshold = $suggestedOverlapDiscount > 0;

        // Atualiza suggested_projected_votes por fonte com base no status real da cidade.
        // Se dentro do limiar: sem sugestão de corte (suggested = projected).
        // Se acima do limiar: distribui o excesso proporcionalmente — cada fonte cede a mesma
        // fração, preservando as proporções relativas e limitando o corte total a 619 votos (excesso).
        if ($cityOverThreshold && $revisaoThreshold > 0 && $projectedBase > 0) {
            $scale = (float) $revisaoThreshold / (float) $projectedBase;
            for ($i = $citySourcesStart, $iMax = count($citySources); $i < $iMax; $i++) {
                $srcProj = (int) ($citySources[$i]['projected_votes'] ?? 0);
                $citySources[$i]['suggested_projected_votes'] = (int) round($srcProj * $scale);
            }
        } else {
            for ($i = $citySourcesStart, $iMax = count($citySources); $i < $iMax; $i++) {
                $citySources[$i]['suggested_projected_votes'] = $citySources[$i]['projected_votes'];
            }
        }

        $growthWarning = $baselineVotes > 0 && $projectedBase > (int) round($baselineVotes * 1.5);
        $growthPct     = $baselineVotes > 0 ? round(($projectedBase / $baselineVotes - 1.0) * 100.0, 1) : 0.0;

        $cities[$cityKey]['source_raw_votes']      = $rawTotal;
        $cities[$cityKey]['source_projected_votes'] = $baseProjection;
        $cities[$cityKey]['overlap_discount']       = $appliedOverlapDiscount;
        $cities[$cityKey]['suggested_overlap_discount'] = $suggestedOverlapDiscount;
        $cities[$cityKey]['source_count']           = count($cityRows);
        $cities[$cityKey]['top_source']             = $topSource;
        $cities[$cityKey]['capped']                 = $capped;
        $cities[$cityKey]['cap_suggested']          = $capSuggested;
        $cities[$cityKey]['electorate_cap']         = $electorateCap;
        $cities[$cityKey]['pct_eleitorado']         = $pctEleitorado;
        $cities[$cityKey]['alert_level']            = premium_senate_city_alert_level($pctEleitorado);
        $cities[$cityKey]['growth_warning']         = $growthWarning;
        $cities[$cityKey]['growth_pct']             = $growthPct;
        $cities[$cityKey]['projected_base']         = $projectedBase;
        $cities[$cityKey]['projected_conservative'] = (int) round($baseProjection * $scenarioConservative);
        $cities[$cityKey]['projected_optimistic']   = (int) round($baseProjection * $scenarioOptimistic);
        $cities[$cityKey]['system_projection']      = $projectedBase;
    }

    // Remove cidades sem fontes reais cadastradas (apenas fallback da base própria do candidato).
    // Essas cidades têm source_count = 0 após o filtro de own_baseline e não devem aparecer no relatório.
    $cities = array_filter($cities, static fn(array $c): bool => (int) ($c['source_count'] ?? 0) > 0);

    $regions = [];
    foreach ($cities as $city) {
        $region = (string) ($city['regiao'] ?? 'Sem região');
        if (!isset($regions[$region])) {
            $regions[$region] = [
                'regiao' => $region,
                'baseline_votes' => 0,
                'source_raw_votes' => 0,
                'source_projected_votes' => 0,
                'overlap_discount' => 0,
                'suggested_overlap_discount' => 0,
                'source_count' => 0,
                'projected_conservative' => 0,
                'projected_base' => 0,
                'projected_optimistic' => 0,
                'system_projection' => 0,
            ];
        }

        foreach (['baseline_votes', 'source_raw_votes', 'source_projected_votes', 'overlap_discount', 'suggested_overlap_discount', 'source_count', 'projected_conservative', 'projected_base', 'projected_optimistic', 'system_projection'] as $field) {
            $regions[$region][$field] += (int) ($city[$field] ?? 0);
        }
    }

    $totals = $forecast['totals'];
    $totals['baseline_votes'] = (int) ($baseline['total_votes'] ?? 0);
    $totals['source_count'] = count($sources) + (!$hasExplicitOwnSenateBase && !empty($baseline['found']) ? 1 : 0);
    $totals['government_support'] = !empty($settings['senate_state_government_support']) ? 1 : 0;
    $totals['government_multiplier'] = $governmentMultiplier;
    $totals['overlap_mode'] = $overlapMode;
    $totals['overlap_mode_label'] = premium_senate_overlap_mode_label($overlapMode);
    $totals['cities_with_alerts'] = 0;
    $totals['cities_capped'] = 0;
    $totals['cities_with_growth_warning'] = 0;
    foreach ($cities as $city) {
        foreach (['source_raw_votes', 'source_projected_votes', 'overlap_discount', 'suggested_overlap_discount', 'projected_conservative', 'projected_base', 'projected_optimistic', 'system_projection'] as $field) {
            $totals[$field] += (int) ($city[$field] ?? 0);
        }
        if (($city['alert_level'] ?? 'ok') !== 'ok') {
            $totals['cities_with_alerts']++;
        }
        if (!empty($city['capped'])) {
            $totals['cities_capped']++;
        }
        if (!empty($city['growth_warning'])) {
            $totals['cities_with_growth_warning']++;
        }
    }

    $cities = array_values($cities);
    usort($cities, static fn(array $a, array $b): int => (int) ($b['projected_base'] ?? 0) <=> (int) ($a['projected_base'] ?? 0));

    $regions = array_values($regions);
    usort($regions, static fn(array $a, array $b): int => (int) ($b['projected_base'] ?? 0) <=> (int) ($a['projected_base'] ?? 0));

    $sourceBreakdown = array_values($sourceBreakdown);
    usort($sourceBreakdown, static fn(array $a, array $b): int => (int) ($b['projected_votes'] ?? 0) <=> (int) ($a['projected_votes'] ?? 0));

    return [
        'settings' => $settings,
        'baseline' => $baseline,
        'totals' => $totals,
        'regions' => $regions,
        'cities' => $cities,
        'sources' => $sourceBreakdown,
        'city_sources' => $citySources,
        'leaders' => [],
    ];
}

function premium_apply_transfer_multiplier(array $leader, array $settings): array
{
    $isManualProjection = !empty($leader['is_manual_projection']);

    $transferRate = (float) ($leader['transfer_rate'] ?? ($settings['transfer_rate_default'] ?? premium_default_settings()['transfer_rate_default']));
    if ($transferRate > 1) {
        $transferRate /= 100;
    }

    $baseEffect = (int) round(((int) ($leader['leader_votes_2024'] ?? 0)) * $transferRate);

    if ($isManualProjection) {
        return [
            'transfer_rate' => round($transferRate * 100, 2),
            'alignment_multiplier' => 1.0,
            'visibility_multiplier' => 1.0,
            'investment_multiplier' => 1.0,
            'margin_multiplier' => 1.0,
            'size_multiplier' => 1.0,
            'base_effect' => $baseEffect,
            'projected_votes' => $baseEffect,
            'multiplier' => 1.0,
            'is_manual_projection' => true,
        ];
    }

    $alignmentMultiplier = !empty($leader['aligned_with_executive'])
        ? (1.0 + (float) ($settings['alignment_bonus'] ?? 0))
        : 1.0;

    $visibilityScore = (float) ($leader['visibility_score'] ?? 50);
    if ($visibilityScore > 1) {
        $visibilityScore /= 100;
    }
    $visibilityMultiplier = 1.0 + ($visibilityScore * (float) ($settings['visibility_weight'] ?? 0));

    $investmentScore = (float) ($leader['investment_score'] ?? 50);
    if ($investmentScore > 1) {
        $investmentScore /= 100;
    }
    $investmentMultiplier = 1.0 + ($investmentScore * (float) ($settings['investment_weight'] ?? 0));

    $marginPercent = (float) ($leader['margin_percent'] ?? 0);
    if ($marginPercent > 1) {
        $marginPercent /= 100;
    }
    $marginMultiplier = 1.0 + ($marginPercent * (float) ($settings['margin_weight'] ?? 0));

    $sizeClass = (string) ($leader['size_class'] ?? 'medium');
    $sizeMultiplier = 1.0 + premium_size_bonus($sizeClass, $settings);

    $multiplier = $alignmentMultiplier * $visibilityMultiplier * $investmentMultiplier * $marginMultiplier * $sizeMultiplier;
    $projectedVotes = (int) round($baseEffect * $multiplier);

    return [
        'transfer_rate' => round($transferRate * 100, 2),
        'alignment_multiplier' => round($alignmentMultiplier, 4),
        'visibility_multiplier' => round($visibilityMultiplier, 4),
        'investment_multiplier' => round($investmentMultiplier, 4),
        'margin_multiplier' => round($marginMultiplier, 4),
        'size_multiplier' => round($sizeMultiplier, 4),
        'base_effect' => $baseEffect,
        'projected_votes' => $projectedVotes,
        'multiplier' => round($multiplier, 4),
    ];
}

function premium_build_forecast(array $baseline, array $leaders, array $settings): array
{
    $settings = premium_normalize_settings($settings);
    $fallbackRetention = (float) ($settings['baseline_retention'] ?? premium_default_settings()['baseline_retention']);
    if ($fallbackRetention > 1) {
        $fallbackRetention /= 100;
    }

    $scenarioConservative = (float) ($settings['scenario_conservative'] ?? 0.90);
    $scenarioBase = (float) ($settings['scenario_base'] ?? 1.00);
    $scenarioOptimistic = (float) ($settings['scenario_optimistic'] ?? 1.12);

    $lookup = premium_region_lookup();
    $cities = [];
    $regions = [];
    $leaderBreakdown = [];

    foreach ($baseline['municipalities'] ?? [] as $row) {
        $municipio = (string) ($row['municipio'] ?? '');
        $votes = (int) ($row['total_votos'] ?? 0);
        $region = (string) ($row['regiao'] ?? ($lookup[premium_normalize_text($municipio)] ?? 'Sem região'));
        $key = premium_normalize_text($municipio);

        $cities[$key] = [
            'municipio' => $municipio,
            'regiao' => $region,
            'baseline_votes' => $votes,
            'leader_effect' => 0,
            'leader_count' => 0,
            'projection_mode' => 'baseline_fallback',
            'projected_conservative' => 0,
            'projected_base' => 0,
            'projected_optimistic' => 0,
        ];
    }

    foreach ($leaders as $leader) {
        $cityKey = premium_normalize_text((string) ($leader['municipality'] ?? ''));
        $region = (string) ($leader['region_name'] ?? premium_region_for_city((string) ($leader['municipality'] ?? '')) ?? 'Sem região');
        $cityVotes = (int) ($leader['city_total_votes'] ?? 0);

        if (!isset($cities[$cityKey])) {
            $cities[$cityKey] = [
                'municipio' => (string) ($leader['municipality'] ?? ''),
                'regiao' => $region,
                'baseline_votes' => 0,
                'leader_effect' => 0,
                'leader_count' => 0,
                'projection_mode' => 'leaders',
                'projected_conservative' => 0,
                'projected_base' => 0,
                'projected_optimistic' => 0,
            ];
        }

        $projection = premium_apply_transfer_multiplier($leader, $settings);
        $cities[$cityKey]['leader_effect'] += $projection['projected_votes'];
        $cities[$cityKey]['leader_count'] += 1;
        $cities[$cityKey]['projection_mode'] = 'leaders';

        $leaderBreakdown[] = array_merge($leader, $projection, [
            'region_name' => $region,
            'city_total_votes' => $cityVotes,
        ]);

        if (!isset($regions[$region])) {
            $regions[$region] = [
                'regiao' => $region,
                'baseline_votes' => 0,
                'leader_effect' => 0,
                'leader_projection' => 0,
                'independent_votes' => 0,
                'leader_count' => 0,
                'system_projection' => 0,
                'projected_conservative' => 0,
                'projected_base' => 0,
                'projected_optimistic' => 0,
            ];
        }
    }

    foreach ($cities as &$city) {
        if (!empty($city['leader_count'])) {
            $city['projected_base'] = (int) round($city['leader_effect'] * $scenarioBase);
            $city['projected_conservative'] = (int) round($city['leader_effect'] * $scenarioConservative);
            $city['projected_optimistic'] = (int) round($city['leader_effect'] * $scenarioOptimistic);
            $city['projection_mode'] = 'leaders';
        } else {
            $fallbackBase = $city['baseline_votes'] * $fallbackRetention;
            $city['projected_base'] = (int) round($fallbackBase);
            $city['projected_conservative'] = (int) round($fallbackBase * $scenarioConservative);
            $city['projected_optimistic'] = (int) round($fallbackBase * $scenarioOptimistic);
            $city['projection_mode'] = 'baseline_fallback';
        }

        $city['leader_projection'] = (int) round($city['leader_effect']);
        $city['independent_votes'] = max(0, (int) $city['projected_base'] - (int) $city['leader_projection']);
        $city['system_projection'] = (int) $city['projected_base'];

        $regionKey = $city['regiao'];
        if (!isset($regions[$regionKey])) {
            $regions[$regionKey] = [
                'regiao' => $regionKey,
                'baseline_votes' => 0,
                'leader_effect' => 0,
                'leader_projection' => 0,
                'independent_votes' => 0,
                'leader_count' => 0,
                'system_projection' => 0,
                'projected_conservative' => 0,
                'projected_base' => 0,
                'projected_optimistic' => 0,
            ];
        }

        $regions[$regionKey]['baseline_votes'] += (int) ($city['baseline_votes'] ?? 0);
        $regions[$regionKey]['leader_effect'] += (int) ($city['leader_effect'] ?? 0);
        $regions[$regionKey]['leader_projection'] += (int) ($city['leader_projection'] ?? 0);
        $regions[$regionKey]['independent_votes'] += (int) ($city['independent_votes'] ?? 0);
        $regions[$regionKey]['leader_count'] += (int) ($city['leader_count'] ?? 0);
        $regions[$regionKey]['system_projection'] += (int) ($city['system_projection'] ?? 0);
        $regions[$regionKey]['projected_base'] += (int) ($city['projected_base'] ?? 0);
        $regions[$regionKey]['projected_conservative'] += (int) ($city['projected_conservative'] ?? 0);
        $regions[$regionKey]['projected_optimistic'] += (int) ($city['projected_optimistic'] ?? 0);
    }
    unset($city);

    $totals = [
        'baseline_votes' => (int) ($baseline['total_votes'] ?? 0),
        'leader_effect' => 0,
        'leader_projection' => 0,
        'independent_votes' => 0,
        'projected_conservative' => 0,
        'projected_base' => 0,
        'projected_optimistic' => 0,
        'system_projection' => 0,
    ];

    foreach ($cities as $city) {
        $totals['leader_effect'] += (int) ($city['leader_effect'] ?? 0);
        $totals['leader_projection'] += (int) ($city['leader_projection'] ?? 0);
        $totals['independent_votes'] += (int) ($city['independent_votes'] ?? 0);
        $totals['projected_conservative'] += (int) ($city['projected_conservative'] ?? 0);
        $totals['projected_base'] += (int) ($city['projected_base'] ?? 0);
        $totals['projected_optimistic'] += (int) ($city['projected_optimistic'] ?? 0);
        $totals['system_projection'] += (int) ($city['system_projection'] ?? 0);
    }

    $totals['delta_base'] = $totals['projected_base'] - $totals['baseline_votes'];
    $totals['delta_conservative'] = $totals['projected_conservative'] - $totals['baseline_votes'];
    $totals['delta_optimistic'] = $totals['projected_optimistic'] - $totals['baseline_votes'];

    $regions = array_values($regions);
    usort($regions, static fn(array $a, array $b): int => $b['projected_base'] <=> $a['projected_base']);

    $cities = array_values($cities);
    usort($cities, static fn(array $a, array $b): int => $b['projected_base'] <=> $a['projected_base']);

    usort($leaderBreakdown, static fn(array $a, array $b): int => $b['projected_votes'] <=> $a['projected_votes']);

    return [
        'settings' => $settings,
        'totals' => $totals,
        'regions' => $regions,
        'cities' => $cities,
        'leaders' => $leaderBreakdown,
    ];
}

premium_ensure_campaign_photo_column($conn);
premium_ensure_user_trial_columns($conn);
premium_ensure_manual_projection_column($conn);
premium_ensure_senate_tables($conn);

function premium_ensure_manual_projection_column(mysqli $conn): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $checked = true;

    $column = querySingle($conn, "
        SHOW COLUMNS FROM premium_campaign_leaders LIKE 'is_manual_projection'
    ");

    if ($column) {
        return;
    }

    $conn->query("
        ALTER TABLE premium_campaign_leaders
        ADD COLUMN is_manual_projection TINYINT(1) NOT NULL DEFAULT 0 AFTER notes
    ");
}

// ---------------------------------------------------------------------------
// Pesquisas eleitorais
// ---------------------------------------------------------------------------

function premium_get_pesquisas(mysqli $conn, int $campaignId): array
{
    return queryAll($conn, "
        SELECT * FROM premium_pesquisas
        WHERE campaign_id = " . $campaignId . "
        ORDER BY data_pesquisa DESC, id DESC
    ") ?: [];
}

function premium_save_pesquisa(mysqli $conn, int $campaignId, array $data): bool
{
    $instituto     = $conn->real_escape_string(trim((string) ($data['instituto']     ?? '')));
    $tipo          = in_array($data['tipo'] ?? '', ['estadual', 'municipal'], true) ? $data['tipo'] : 'estadual';
    $cdMunicipio   = $tipo === 'municipal' && !empty($data['cd_municipio']) ? (int) $data['cd_municipio'] : 'NULL';
    $nmMunicipio   = $tipo === 'municipal' && !empty($data['nm_municipio'])
        ? "'" . $conn->real_escape_string(trim((string) $data['nm_municipio'])) . "'"
        : 'NULL';
    $dataPesquisa  = $conn->real_escape_string((string) ($data['data_pesquisa'] ?? date('Y-m-d')));
    $pctCandidato  = number_format((float) ($data['pct_candidato'] ?? 0), 2, '.', '');
    $observacoes   = $conn->real_escape_string(trim((string) ($data['observacoes'] ?? '')));

    $conn->query("
        INSERT INTO premium_pesquisas
            (campaign_id, instituto, tipo, cd_municipio, nm_municipio, data_pesquisa, pct_candidato, observacoes)
        VALUES
            ($campaignId, '$instituto', '$tipo', $cdMunicipio, $nmMunicipio, '$dataPesquisa', $pctCandidato, '$observacoes')
    ");

    return $conn->affected_rows > 0;
}

function premium_delete_pesquisa(mysqli $conn, int $id, int $campaignId): bool
{
    $conn->query("DELETE FROM premium_pesquisas WHERE id = $id AND campaign_id = $campaignId LIMIT 1");
    return $conn->affected_rows > 0;
}

/**
 * Compara % de uma pesquisa com a projeção 2026 da campanha.
 *
 * @param array $pesquisa  Linha da tabela premium_pesquisas
 * @param array $forecast  Retorno de premium_build_forecast()
 * @param array $municipios Array de municípios de perfil_eleitor_municipio (indexado por cd_municipio)
 */
function premium_comparar_pesquisa(array $pesquisa, array $forecast, array $municipios): array
{
    $tipo         = (string) ($pesquisa['tipo'] ?? 'estadual');
    $pctPesquisa  = (float)  ($pesquisa['pct_candidato'] ?? 0);

    if ($tipo === 'estadual') {
        $totalEleitores = (int) array_sum(array_column($municipios, 'qt_total'));
        $votosProjecao  = (int) ($forecast['totals']['system_projection'] ?? 0);
        $votosBaseline  = (int) ($forecast['totals']['baseline_votes'] ?? 0);
        $scope          = 'Sergipe (estadual)';
    } else {
        $cdMunicipio    = (int) ($pesquisa['cd_municipio'] ?? 0);
        $nmPesquisa     = premium_normalize_text((string) ($pesquisa['nm_municipio'] ?? ''));
        $totalEleitores = 0;
        $votosProjecao  = 0;
        $votosBaseline  = 0;
        $scope          = (string) ($pesquisa['nm_municipio'] ?? 'Município');

        // Busca total de eleitores pelo cd_municipio
        foreach ($municipios as $m) {
            if ((int) $m['cd_municipio'] === $cdMunicipio) {
                $totalEleitores = (int) $m['qt_total'];
                break;
            }
        }

        // Busca projeção e baseline pelo nome normalizado do município
        foreach ($forecast['cities'] as $city) {
            if (premium_normalize_text((string) ($city['municipio'] ?? '')) === $nmPesquisa) {
                $votosProjecao = (int) ($city['system_projection'] ?? 0);
                $votosBaseline = (int) ($city['baseline_votes'] ?? 0);
                break;
            }
        }
    }

    if ($totalEleitores <= 0) {
        return ['erro' => 'Total de eleitores não encontrado para o escopo informado.'];
    }

    $pctProjecao = round(($votosProjecao / $totalEleitores) * 100, 2);
    $votosPesquisa = (int) round($totalEleitores * $pctPesquisa / 100);
    $deltaPp       = round($pctPesquisa - $pctProjecao, 2);
    $deltaVotos    = $votosPesquisa - $votosProjecao;

    return [
        'scope'           => $scope,
        'total_eleitores' => $totalEleitores,
        'votos_baseline'  => $votosBaseline,
        'votos_pesquisa'  => $votosPesquisa,
        'pct_pesquisa'    => $pctPesquisa,
        'votos_projecao'  => $votosProjecao,
        'pct_projecao'    => $pctProjecao,
        'delta_pp'        => $deltaPp,
        'delta_votos'     => $deltaVotos,
    ];
}
