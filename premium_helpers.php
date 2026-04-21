<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function premium_escape_html(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
    $value = preg_replace('/[^A-Z0-9]+/', ' ', $value) ?? $value;
    $value = preg_replace('/\s+/', ' ', $value) ?? $value;

    return trim($value);
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

function premium_region_definitions(): array
{
    return [
        'Alto Sertão Sergipano' => [
            'Canindé de São Francisco',
            'Monte Alegre de Sergipe',
            'Nossa Senhora da Glória',
            'Nossa Senhora de Lourdes',
            'Poço Redondo',
            'Porto da Folha',
        ],
        'Médio Sertão Sergipano' => [
            'Aquidabã',
            'Carira',
            'Cumbe',
            'Feira Nova',
            'Frei Paulo',
            'Gararu',
            'Gracho Cardoso',
            'Itabi',
            'Nossa Senhora Aparecida',
            'Nossa Senhora das Dores',
            'São Miguel do Aleixo',
            'Ribeirópolis',
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
        'Agreste Central Sergipano' => [
            'Areia Branca',
            'Campo do Brito',
            'Itabaiana',
            'Macambira',
            'Malhador',
            'Moita Bonita',
            'Pedra Mole',
            'Pinhão',
            'São Domingos',
        ],
        'Centro Sul Sergipano' => [
            'Boquim',
            'Lagarto',
            'Pedrinhas',
            'Poço Verde',
            'Riachão do Dantas',
            'Salgado',
            'Simão Dias',
            'Tobias Barreto',
        ],
        'Sul Sergipano' => [
            'Arauá',
            'Cristinápolis',
            'Estância',
            'Indiaroba',
            'Itabaianinha',
            'Santa Luzia do Itanhy',
            'Tomar do Geru',
            'Umbaúba',
        ],
        'Leste Sergipano' => [
            'Capela',
            'Carmópolis',
            'Divina Pastora',
            'General Maynard',
            'Japaratuba',
            'Pirambu',
            'Rosário do Catete',
            'Santa Rosa de Lima',
            'Siriri',
        ],
        'Grande Aracaju (Região Metropolitana)' => [
            'Aracaju',
            'Barra dos Coqueiros',
            'Itaporanga d\'Ajuda',
            'Laranjeiras',
            'Maruim',
            'Nossa Senhora do Socorro',
            'Riachuelo',
            'Santo Amaro das Brotas',
            'São Cristóvão',
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

    $lookup[premium_normalize_text('Amparo de São Francisco')] = 'Baixo São Francisco';
    $lookup[premium_normalize_text('Graccho Cardoso')] = 'Médio Sertão Sergipano';

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
        'baseline_retention' => 0.45,
        'transfer_rate_default' => 40.00,
        'alignment_bonus' => 0.20,
        'visibility_weight' => 0.12,
        'investment_weight' => 0.10,
        'margin_weight' => 0.25,
        'small_city_bonus' => 0.18,
        'medium_city_bonus' => 0.08,
        'large_city_bonus' => 0.00,
        'scenario_conservative' => 0.90,
        'scenario_base' => 1.00,
        'scenario_optimistic' => 1.12,
        'small_city_threshold' => 15000,
        'medium_city_threshold' => 40000,
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

    return $merged;
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
        $raw = 'premium@eleicoes.local';
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
        SELECT id, name, email, status, created_at, last_login_at
        FROM premium_users
        WHERE id = {$userId} AND status = 'active'
        LIMIT 1
    ");

    return $user ?: null;
}

function premium_get_users(mysqli $conn): array
{
    $users = queryAll($conn, "
        SELECT id, name, email, status, created_at, last_login_at
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
        return false;
    }

    $user = querySingle($conn, "
        SELECT id, name, email, password_hash, status
        FROM premium_users
        WHERE email = " . premium_sql_quote($conn, $email) . "
        LIMIT 1
    ");

    if (!$user || ($user['status'] ?? '') !== 'active') {
        return false;
    }

    if (!password_verify($password, (string) $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
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
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
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
    header('Location: premium.php');
    exit;
}

function premium_get_campaigns(mysqli $conn, int $userId): array
{
    $campaigns = queryAll($conn, "
        SELECT *
        FROM premium_campaigns
        WHERE user_id = " . (int) $userId . "
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
        foreach ([
            'premium_forecast_runs',
            'premium_agenda',
            'premium_campaign_leaders',
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

function premium_get_campaign(mysqli $conn, int $campaignId, int $userId): ?array
{
    $campaign = querySingle($conn, "
        SELECT *
        FROM premium_campaigns
        WHERE id = " . (int) $campaignId . "
          AND user_id = " . (int) $userId . "
        LIMIT 1
    ");

    if ($campaign) {
        $campaign['candidate_number'] = premium_parse_candidate_number($campaign['candidate_number'] ?? null);
    }

    return $campaign ?: null;
}

function premium_active_campaign(mysqli $conn, int $userId): ?array
{
    $selectedId = (int) ($_SESSION['premium_campaign_id'] ?? 0);
    if ($selectedId > 0) {
        $campaign = premium_get_campaign($conn, $selectedId, $userId);
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
            LIMIT 1
        ");
    }

    if (!$row && $sourceNr > 0) {
        $conditions = [
            'nr_turno = ' . $turno,
            'nr_cand = ' . $sourceNr,
        ];

        if ($municipality !== '') {
            $conditions[] = 'nm_municipio = ' . premium_sql_quote($conn, $municipality);
        }

        if ($cargo !== '') {
            $conditions[] = 'ds_cargo = ' . premium_sql_quote($conn, $cargo);
        }

        $row = querySingle($conn, "
            SELECT nm_urna_candidato, nm_candidato
            FROM candidatos_situacao_2024
            WHERE " . implode(' AND ', $conditions) . "
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

function premium_candidate_baseline(mysqli $conn, string $candidateName, string $cargo): array
{
    $candidateName = trim($candidateName);
    $cargo = trim($cargo);

    if ($candidateName === '' || $cargo === '') {
        return [
            'candidate_name' => $candidateName,
            'cargo' => $cargo,
            'candidate_number' => null,
            'total_votes' => 0,
            'municipalities' => [],
            'regions' => [],
            'found' => false,
        ];
    }

    $nameSql = premium_sql_quote($conn, $candidateName);
    $cargoVariants = premium_cargo_variants($cargo);
    if (!$cargoVariants) {
        return [
            'candidate_name' => $candidateName,
            'cargo' => $cargo,
            'candidate_number' => null,
            'total_votes' => 0,
            'municipalities' => [],
            'regions' => [],
            'found' => false,
        ];
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
        FROM votacao_2022
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
        FROM votacao_2022
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
    ];
}

function premium_default_size_class_from_votes(int $votes, array $settings): string
{
    $small = (int) ($settings['small_city_threshold'] ?? 15000);
    $medium = (int) ($settings['medium_city_threshold'] ?? 40000);

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

function premium_search_2024_candidates(mysqli $conn, string $cargo, string $municipio = '', string $query = '', int $turno = 1): array
{
    $cargo = trim($cargo);
    $municipio = trim($municipio);
    $query = trim($query);

    if ($cargo === '') {
        $cargo = 'Prefeito';
    }

    $cargoVariants = premium_cargo_variants($cargo);
    if (!$cargoVariants) {
        $cargoVariants = [$cargo];
    }

    $conditions = [
        '(' . implode(' OR ', array_map(
            static fn(string $variant): string => 'r.ds_cargo = ' . premium_sql_quote($conn, $variant),
            $cargoVariants
        )) . ')',
        "r.nr_turno = " . (int) $turno,
        "r.tipo_voto = 'Candidato'",
    ];

    if ($municipio !== '') {
        $conditions[] = "r.nm_municipio = " . premium_sql_quote($conn, $municipio);
    }

    $summaryRows = queryAll($conn, "
        SELECT
            r.nm_municipio,
            r.nr_votavel,
            MAX(r.nm_votavel) AS nm_votavel,
            SUM(r.total_votos) AS total_votos,
            COUNT(DISTINCT r.nr_zona) AS zonas
        FROM resumo_votacao_2024_se r
        WHERE " . implode(' AND ', $conditions) . "
        GROUP BY r.nm_municipio, r.nr_votavel
        ORDER BY total_votos DESC, r.nm_municipio ASC, r.nr_votavel ASC
        LIMIT 200
    ");

    $metaConditions = [
        '(' . implode(' OR ', array_map(
            static fn(string $variant): string => 'ds_cargo = ' . premium_sql_quote($conn, $variant),
            $cargoVariants
        )) . ')',
        "nr_turno = " . (int) $turno,
    ];

    if ($municipio !== '') {
        $metaConditions[] = "nm_municipio = " . premium_sql_quote($conn, $municipio);
    }

    $metaRows = queryAll($conn, "
        SELECT
            nm_municipio,
            nm_urna_candidato,
            MAX(nm_candidato) AS nm_candidato,
            MAX(sg_partido) AS sg_partido,
            MAX(ds_sit_tot_turno) AS situacao,
            MAX(sq_candidato) AS sq_candidato,
            MAX(nr_cand) AS nr_cand
        FROM candidatos_situacao_2024
        WHERE " . implode(' AND ', $metaConditions) . "
        GROUP BY nm_municipio, nm_urna_candidato
    ");

    $metaMap = [];
    foreach ($metaRows as $metaRow) {
        $cityKey = premium_normalize_text((string) ($metaRow['nm_municipio'] ?? ''));
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
            $candidateName = (string) ($row['nm_votavel'] ?? '');
            $meta = $metaMap[premium_normalize_text($city) . '|' . premium_normalize_text($candidateName)] ?? [];
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

            $leaderRows[] = [
                'nm_municipio' => $city,
                'nr_votavel' => (int) $row['nr_votavel'],
                'nm_votavel' => $candidateName,
                'nm_urna_candidato' => $ballotName,
                'nm_candidato' => $legalName,
                'leader_display_name' => $ballotName,
                'total_votos' => (int) $row['total_votos'],
                'zonas' => (int) $row['zonas'],
                'sg_partido' => $meta['sg_partido'] ?? null,
                'situacao' => $meta['situacao'] ?? null,
                'sq_candidato' => !empty($meta['sq_candidato']) ? (string) $meta['sq_candidato'] : null,
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

function premium_apply_transfer_multiplier(array $leader, array $settings): array
{
    $transferRate = (float) ($leader['transfer_rate'] ?? ($settings['transfer_rate_default'] ?? 40.00));
    if ($transferRate > 1) {
        $transferRate /= 100;
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
    $baseEffect = (int) round(((int) ($leader['leader_votes_2024'] ?? 0)) * $transferRate);
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
    $fallbackRetention = (float) ($settings['baseline_retention'] ?? 0.45);
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
