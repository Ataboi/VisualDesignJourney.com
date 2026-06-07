<?php
/**
 * Server-side deploy webhook helpers for the legacy admin deploy page.
 *
 * The saved webhook URL and optional secret are never written into client-side
 * JavaScript. The admin UI should display only masked connection details.
 */

function deploySyncBootstrap(PDO $db): void {
    $driver = (string)$db->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $db->exec("
            CREATE TABLE IF NOT EXISTS deploy_settings (
              id INTEGER PRIMARY KEY,
              webhook_url TEXT NULL,
              status_url TEXT NULL,
              webhook_secret TEXT NOT NULL DEFAULT '',
              environment TEXT NOT NULL DEFAULT 'production',
              sync_state TEXT NOT NULL DEFAULT 'idle',
              last_status TEXT NULL,
              last_http_code INTEGER NULL,
              last_response TEXT NULL,
              last_deploy_at TEXT NULL,
              last_checked_at TEXT NULL,
              in_progress_until TEXT NULL,
              created_at TEXT NOT NULL,
              updated_at TEXT NOT NULL
            )
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS deploy_history (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              action TEXT NOT NULL,
              environment TEXT NOT NULL DEFAULT 'production',
              status TEXT NOT NULL DEFAULT 'pending',
              http_code INTEGER NULL,
              payload_json TEXT NULL,
              response_body TEXT NULL,
              error_message TEXT NULL,
              triggered_by TEXT NULL,
              idempotency_key TEXT NULL,
              started_at TEXT NOT NULL,
              finished_at TEXT NULL,
              duration_ms INTEGER NULL
            )
        ");
    } else {
        $db->exec("
            CREATE TABLE IF NOT EXISTS deploy_settings (
              id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
              webhook_url TEXT NULL,
              status_url TEXT NULL,
              webhook_secret VARCHAR(255) NOT NULL DEFAULT '',
              environment VARCHAR(32) NOT NULL DEFAULT 'production',
              sync_state VARCHAR(32) NOT NULL DEFAULT 'idle',
              last_status VARCHAR(120) DEFAULT NULL,
              last_http_code INT DEFAULT NULL,
              last_response MEDIUMTEXT NULL,
              last_deploy_at DATETIME DEFAULT NULL,
              last_checked_at DATETIME DEFAULT NULL,
              in_progress_until DATETIME DEFAULT NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              KEY idx_sync_state (sync_state),
              KEY idx_last_deploy (last_deploy_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS deploy_history (
              id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              action VARCHAR(40) NOT NULL,
              environment VARCHAR(32) NOT NULL DEFAULT 'production',
              status VARCHAR(32) NOT NULL DEFAULT 'pending',
              http_code INT DEFAULT NULL,
              payload_json MEDIUMTEXT NULL,
              response_body MEDIUMTEXT NULL,
              error_message TEXT NULL,
              triggered_by VARCHAR(120) DEFAULT NULL,
              idempotency_key VARCHAR(80) DEFAULT NULL,
              started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              finished_at DATETIME DEFAULT NULL,
              duration_ms INT DEFAULT NULL,
              KEY idx_action (action),
              KEY idx_status (status),
              KEY idx_started (started_at),
              KEY idx_idempotency (idempotency_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    if ($driver === 'sqlite') {
        $stmt = $db->prepare("
            INSERT OR IGNORE INTO deploy_settings (id, environment, sync_state, created_at, updated_at)
            VALUES (1, 'production', 'idle', ?, ?)
        ");
    } else {
        $stmt = $db->prepare("
            INSERT IGNORE INTO deploy_settings (id, environment, sync_state, created_at, updated_at)
            VALUES (1, 'production', 'idle', ?, ?)
        ");
    }

    $now = deploySyncDateTime();
    $stmt->execute([$now, $now]);
}

function deploySyncDateTime(?int $timestamp = null): string {
    return date('Y-m-d H:i:s', $timestamp ?? time());
}

function deploySyncSettings(PDO $db): array {
    deploySyncBootstrap($db);

    $row = $db->query('SELECT * FROM deploy_settings WHERE id = 1 LIMIT 1')->fetch(PDO::FETCH_ASSOC) ?: [];

    return array_merge([
        'id' => 1,
        'webhook_url' => '',
        'status_url' => '',
        'webhook_secret' => '',
        'environment' => 'production',
        'sync_state' => 'idle',
        'last_status' => null,
        'last_http_code' => null,
        'last_response' => null,
        'last_deploy_at' => null,
        'last_checked_at' => null,
        'in_progress_until' => null,
        'created_at' => null,
        'updated_at' => null,
    ], $row);
}

function deploySyncHistory(PDO $db, int $limit = 10): array {
    deploySyncBootstrap($db);
    $limit = max(1, min(50, $limit));

    $stmt = $db->query("SELECT * FROM deploy_history ORDER BY started_at DESC, id DESC LIMIT {$limit}");

    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function deploySyncLatestFailed(PDO $db): ?array {
    deploySyncBootstrap($db);

    $stmt = $db->query("SELECT * FROM deploy_history WHERE status = 'failed' ORDER BY started_at DESC, id DESC LIMIT 1");
    $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;

    return $row ?: null;
}

function deploySyncUpdateSettings(PDO $db, array $updates): void {
    $allowed = [
        'webhook_url', 'status_url', 'webhook_secret', 'environment', 'sync_state',
        'last_status', 'last_http_code', 'last_response', 'last_deploy_at',
        'last_checked_at', 'in_progress_until',
    ];

    $sets = [];
    $values = [];
    foreach ($updates as $key => $value) {
        if (!in_array($key, $allowed, true)) {
            continue;
        }
        $sets[] = "{$key} = ?";
        $values[] = $value;
    }

    if (!$sets) {
        return;
    }

    $sets[] = 'updated_at = ?';
    $values[] = deploySyncDateTime();
    $values[] = 1;

    $stmt = $db->prepare('UPDATE deploy_settings SET ' . implode(', ', $sets) . ' WHERE id = ?');
    $stmt->execute($values);
}

function deploySyncValidateUrl(?string $url, bool $allowEmpty = false): array {
    $url = trim((string)$url);

    if ($url === '') {
        return [
            'ok' => $allowEmpty,
            'url' => '',
            'message' => $allowEmpty ? '' : 'Webhook URL is required.',
        ];
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['ok' => false, 'url' => '', 'message' => 'Enter a valid http or https URL.'];
    }

    $parts = parse_url($url);
    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    $host = strtolower((string)($parts['host'] ?? ''));

    if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
        return ['ok' => false, 'url' => '', 'message' => 'Webhook URL must use http or https.'];
    }

    if (deploySyncIsProductionLike() && deploySyncHostIsPrivate($host)) {
        return ['ok' => false, 'url' => '', 'message' => 'Production webhook URLs cannot target localhost or private network addresses.'];
    }

    return ['ok' => true, 'url' => $url, 'message' => $scheme === 'https' ? '' : 'HTTP is allowed, but HTTPS is recommended.'];
}

function deploySyncIsProductionLike(): bool {
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $siteHost = defined('SITE_URL') ? strtolower((string)(parse_url(SITE_URL, PHP_URL_HOST) ?: '')) : '';
    $candidate = $host ?: $siteHost;

    if ($candidate === '') {
        return true;
    }

    return !str_contains($candidate, 'localhost')
        && !str_starts_with($candidate, '127.')
        && !str_starts_with($candidate, '10.')
        && $candidate !== '::1'
        && $candidate !== '[::1]';
}

function deploySyncHostIsPrivate(string $host): bool {
    $host = trim(strtolower($host), '[]');

    if ($host === 'localhost' || $host === '0.0.0.0' || $host === '::1' || str_ends_with($host, '.local')) {
        return true;
    }

    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    $resolved = @gethostbynamel($host);
    if (is_array($resolved)) {
        foreach ($resolved as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return true;
            }
        }
    }

    return false;
}

function deploySyncMaskUrl(?string $url): string {
    $url = trim((string)$url);
    if ($url === '') {
        return 'Not connected';
    }

    $parts = parse_url($url);
    $scheme = $parts['scheme'] ?? 'https';
    $host = $parts['host'] ?? 'webhook';
    $path = $parts['path'] ?? '';
    if (strlen($path) > 28) {
        $path = substr($path, 0, 20) . '...' . substr($path, -5);
    }

    return $scheme . '://' . $host . ($path ?: '');
}

function deploySyncStateClass(?string $state): string {
    return match ($state) {
        'success' => 'success',
        'failed' => 'failed',
        'pending', 'running' => 'running',
        default => 'idle',
    };
}

function deploySyncSaveWebhook(PDO $db, array $settings, array $input): array {
    $webhookUrl = trim((string)($input['webhook_url'] ?? ''));
    $statusUrl = trim((string)($input['status_url'] ?? ''));
    $secret = trim((string)($input['webhook_secret'] ?? ''));
    $environment = trim((string)($input['environment'] ?? ($settings['environment'] ?? 'production')));
    $environment = in_array($environment, ['production', 'staging'], true) ? $environment : 'production';

    $updates = ['environment' => $environment];

    if ($webhookUrl !== '') {
        $check = deploySyncValidateUrl($webhookUrl);
        if (!$check['ok']) {
            return ['ok' => false, 'message' => $check['message']];
        }
        $updates['webhook_url'] = $check['url'];
    } elseif (trim((string)($settings['webhook_url'] ?? '')) === '') {
        return ['ok' => false, 'message' => 'Webhook URL is required before deploy actions can run.'];
    }

    if ($statusUrl !== '') {
        $check = deploySyncValidateUrl($statusUrl);
        if (!$check['ok']) {
            return ['ok' => false, 'message' => $check['message']];
        }
        $updates['status_url'] = $check['url'];
    }

    if (!empty($input['clear_secret'])) {
        $updates['webhook_secret'] = '';
    } elseif ($secret !== '') {
        if (strlen($secret) < 12) {
            return ['ok' => false, 'message' => 'Shared secret should be at least 12 characters.'];
        }
        $updates['webhook_secret'] = $secret;
    }

    deploySyncUpdateSettings($db, $updates);

    return ['ok' => true, 'message' => 'Webhook settings saved. Existing private values were preserved unless you entered replacements.'];
}

function deploySyncAllowedAction(string $action): string {
    $action = strtolower(trim($action));

    return in_array($action, ['deploy', 'sync', 'rebuild', 'publish', 'test'], true) ? $action : 'deploy';
}

function deploySyncAcquireLock(PDO $db, int $ttlSeconds = 600): bool {
    $now = deploySyncDateTime();
    $expires = deploySyncDateTime(time() + max(60, $ttlSeconds));

    $stmt = $db->prepare("
        UPDATE deploy_settings
        SET sync_state = 'pending', in_progress_until = ?, updated_at = ?
        WHERE id = 1
          AND (
            sync_state NOT IN ('pending', 'running')
            OR in_progress_until IS NULL
            OR in_progress_until < ?
          )
    ");
    $stmt->execute([$expires, $now, $now]);

    return $stmt->rowCount() > 0;
}

function deploySyncIdempotencyKey(): string {
    return 'vdj_' . bin2hex(random_bytes(16));
}

function deploySyncPayload(string $action, string $environment, string $adminUser, string $idempotencyKey, array $changedPaths = []): array {
    return [
        'action' => $action,
        'site_name' => 'visualdesignjourney.com',
        'site_url' => defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://visualdesignjourney.com',
        'environment' => $environment,
        'timestamp' => gmdate('c'),
        'idempotency_key' => $idempotencyKey,
        'source' => [
            'type' => 'admin_panel',
            'page' => '/admin/deploy.php',
            'user' => $adminUser,
        ],
        'build' => [
            'id' => defined('VDJ_BUILD_ID') ? VDJ_BUILD_ID : null,
            'asset_version' => defined('VDJ_ASSET_VERSION') ? VDJ_ASSET_VERSION : null,
        ],
        'changed_paths' => array_values(array_filter(array_map('strval', $changedPaths))),
    ];
}

function deploySyncJson(array $payload): string {
    return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) ?: '{}';
}

function deploySyncTruncate(?string $value, int $limit = 12000): ?string {
    if ($value === null) {
        return null;
    }

    return strlen($value) > $limit ? substr($value, 0, $limit) . "\n...[truncated]" : $value;
}

function deploySyncInsertHistory(PDO $db, array $row): int {
    $stmt = $db->prepare("
        INSERT INTO deploy_history
          (action, environment, status, http_code, payload_json, response_body, error_message, triggered_by, idempotency_key, started_at)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $row['action'] ?? 'deploy',
        $row['environment'] ?? 'production',
        $row['status'] ?? 'pending',
        $row['http_code'] ?? null,
        $row['payload_json'] ?? null,
        $row['response_body'] ?? null,
        $row['error_message'] ?? null,
        $row['triggered_by'] ?? null,
        $row['idempotency_key'] ?? null,
        $row['started_at'] ?? deploySyncDateTime(),
    ]);

    return (int)$db->lastInsertId();
}

function deploySyncFinishHistory(PDO $db, int $id, array $updates): void {
    $stmt = $db->prepare("
        UPDATE deploy_history
        SET status = ?, http_code = ?, response_body = ?, error_message = ?, finished_at = ?, duration_ms = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $updates['status'] ?? 'failed',
        $updates['http_code'] ?? null,
        $updates['response_body'] ?? null,
        $updates['error_message'] ?? null,
        $updates['finished_at'] ?? deploySyncDateTime(),
        $updates['duration_ms'] ?? null,
        $id,
    ]);
}

function deploySyncHttpJson(string $url, string $method, ?array $payload, ?string $secret, string $event): array {
    $started = microtime(true);
    $body = $payload === null ? '' : deploySyncJson($payload);
    $timestamp = (string)time();
    $headers = [
        'Accept: application/json',
        'User-Agent: VisualDesignJourney-DeploySync/1.0',
        'X-VDJ-Timestamp: ' . $timestamp,
        'X-VDJ-Event: ' . $event,
    ];

    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Content-Length: ' . strlen($body);
    }

    $secret = trim((string)$secret);
    if ($secret !== '') {
        $headers[] = 'X-VDJ-Signature: sha256=' . hash_hmac('sha256', $timestamp . '.' . $event . '.' . $body, $secret);
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
        ]);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return [
            'ok' => $raw !== false && $code >= 200 && $code < 300,
            'http_code' => $code ?: null,
            'body' => is_string($raw) ? deploySyncTruncate($raw) : '',
            'error' => $raw === false ? ($error ?: 'Webhook request failed.') : '',
            'duration_ms' => (int)round((microtime(true) - $started) * 1000),
        ];
    }

    $context = stream_context_create([
        'http' => [
            'method' => strtoupper($method),
            'timeout' => 15,
            'ignore_errors' => true,
            'header' => implode("\r\n", $headers) . "\r\n",
            'content' => $payload !== null ? $body : '',
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $raw = @file_get_contents($url, false, $context);
    $code = null;
    foreach (($http_response_header ?? []) as $line) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
            $code = (int)$m[1];
            break;
        }
    }

    return [
        'ok' => is_string($raw) && $code !== null && $code >= 200 && $code < 300,
        'http_code' => $code,
        'body' => is_string($raw) ? deploySyncTruncate($raw) : '',
        'error' => is_string($raw) ? '' : 'Webhook request failed or timed out.',
        'duration_ms' => (int)round((microtime(true) - $started) * 1000),
    ];
}

function deploySyncStateFromBody(?string $body): ?string {
    $decoded = json_decode((string)$body, true);
    if (!is_array($decoded)) {
        return null;
    }

    $value = $decoded['sync_state'] ?? $decoded['state'] ?? $decoded['status'] ?? $decoded['deploy_status'] ?? null;
    if (!is_string($value)) {
        return null;
    }

    $value = strtolower($value);

    return match ($value) {
        'queued', 'accepted', 'pending' => 'pending',
        'building', 'deploying', 'running', 'in_progress' => 'running',
        'ok', 'complete', 'completed', 'success', 'succeeded', 'deployed' => 'success',
        'error', 'failed', 'failure', 'cancelled', 'canceled' => 'failed',
        'idle' => 'idle',
        default => null,
    };
}

function deploySyncStatusUrlFromSettings(array $settings): string {
    $statusUrl = trim((string)($settings['status_url'] ?? ''));
    if ($statusUrl !== '') {
        return $statusUrl;
    }

    $decoded = json_decode((string)($settings['last_response'] ?? ''), true);
    if (!is_array($decoded)) {
        return '';
    }

    foreach (['status_url', 'deploy_status_url', 'url'] as $key) {
        if (!empty($decoded[$key]) && is_string($decoded[$key])) {
            return $decoded[$key];
        }
    }

    if (!empty($decoded['_links']['status']) && is_string($decoded['_links']['status'])) {
        return $decoded['_links']['status'];
    }

    return '';
}

function deploySyncSendAction(PDO $db, array $settings, string $action, string $environment, string $adminUser, array $changedPaths = []): array {
    $action = deploySyncAllowedAction($action);
    $environment = in_array($environment, ['production', 'staging'], true) ? $environment : 'production';
    $webhookUrl = trim((string)($settings['webhook_url'] ?? ''));
    $urlCheck = deploySyncValidateUrl($webhookUrl);

    if (!$urlCheck['ok']) {
        return ['ok' => false, 'message' => $urlCheck['message']];
    }

    $isDeployAction = $action !== 'test';
    if ($isDeployAction && !deploySyncAcquireLock($db)) {
        return ['ok' => false, 'message' => 'A deploy or sync is already pending. Pull latest status or wait for the lock to expire before triggering again.'];
    }

    $idempotencyKey = deploySyncIdempotencyKey();
    $payload = deploySyncPayload($action, $environment, $adminUser, $idempotencyKey, $changedPaths);
    $historyId = deploySyncInsertHistory($db, [
        'action' => $action,
        'environment' => $environment,
        'status' => 'pending',
        'payload_json' => deploySyncJson($payload),
        'triggered_by' => $adminUser,
        'idempotency_key' => $idempotencyKey,
    ]);

    $response = deploySyncHttpJson($urlCheck['url'], 'POST', $payload, $settings['webhook_secret'] ?? '', 'deploy.' . $action);
    $remoteState = deploySyncStateFromBody($response['body']);
    $finalStatus = $response['ok'] ? 'success' : 'failed';
    $syncState = $response['ok']
        ? ($remoteState ?: ($action === 'test' ? 'success' : 'pending'))
        : 'failed';

    deploySyncFinishHistory($db, $historyId, [
        'status' => $finalStatus,
        'http_code' => $response['http_code'],
        'response_body' => $response['body'],
        'error_message' => $response['error'],
        'duration_ms' => $response['duration_ms'],
    ]);

    $updates = [
        'environment' => $environment,
        'sync_state' => $syncState,
        'last_status' => $remoteState ?: ($response['ok'] ? 'webhook_accepted' : 'webhook_failed'),
        'last_http_code' => $response['http_code'],
        'last_response' => $response['body'],
        'last_checked_at' => deploySyncDateTime(),
        'in_progress_until' => in_array($syncState, ['pending', 'running'], true) ? deploySyncDateTime(time() + 600) : null,
    ];

    if ($isDeployAction) {
        $updates['last_deploy_at'] = deploySyncDateTime();
    }

    deploySyncUpdateSettings($db, $updates);

    return [
        'ok' => $response['ok'],
        'message' => $response['ok'] ? ucfirst($action) . ' webhook sent.' : ucfirst($action) . ' webhook failed.',
        'response' => $response,
        'history_id' => $historyId,
    ];
}

function deploySyncPullStatus(PDO $db, array $settings, string $adminUser): array {
    $statusUrl = deploySyncStatusUrlFromSettings($settings);
    $urlCheck = deploySyncValidateUrl($statusUrl);

    if (!$urlCheck['ok']) {
        return ['ok' => false, 'message' => 'No valid status URL is saved or available from the last webhook response.'];
    }

    $historyId = deploySyncInsertHistory($db, [
        'action' => 'pull_status',
        'environment' => $settings['environment'] ?? 'production',
        'status' => 'pending',
        'payload_json' => deploySyncJson(['action' => 'pull_status', 'timestamp' => gmdate('c')]),
        'triggered_by' => $adminUser,
        'idempotency_key' => deploySyncIdempotencyKey(),
    ]);

    $response = deploySyncHttpJson($urlCheck['url'], 'GET', null, $settings['webhook_secret'] ?? '', 'deploy.status_pull');
    $remoteState = deploySyncStateFromBody($response['body']);
    $syncState = $response['ok'] ? ($remoteState ?: 'success') : 'failed';

    deploySyncFinishHistory($db, $historyId, [
        'status' => $response['ok'] ? 'success' : 'failed',
        'http_code' => $response['http_code'],
        'response_body' => $response['body'],
        'error_message' => $response['error'],
        'duration_ms' => $response['duration_ms'],
    ]);

    deploySyncUpdateSettings($db, [
        'sync_state' => $syncState,
        'last_status' => $remoteState ?: ($response['ok'] ? 'status_pulled' : 'status_pull_failed'),
        'last_http_code' => $response['http_code'],
        'last_response' => $response['body'],
        'last_checked_at' => deploySyncDateTime(),
        'in_progress_until' => in_array($syncState, ['pending', 'running'], true) ? deploySyncDateTime(time() + 600) : null,
    ]);

    return [
        'ok' => $response['ok'],
        'message' => $response['ok'] ? 'Deployment status updated.' : 'Could not pull deployment status.',
        'response' => $response,
    ];
}

function deploySyncVerifySignature(string $rawBody, string $secret, string $timestamp, string $event, string $signature): bool {
    $secret = trim($secret);
    if ($secret === '' || $timestamp === '' || $event === '' || $signature === '') {
        return false;
    }

    if (abs(time() - (int)$timestamp) > 600) {
        return false;
    }

    $expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $event . '.' . $rawBody, $secret);

    return hash_equals($expected, $signature);
}

function deploySyncApplyIncomingStatus(PDO $db, array $settings, array $payload, string $rawBody): array {
    $state = deploySyncStateFromBody(deploySyncJson($payload)) ?: 'success';
    $action = is_string($payload['action'] ?? null) ? deploySyncAllowedAction((string)$payload['action']) : 'sync';
    $idempotencyKey = is_string($payload['idempotency_key'] ?? null) ? $payload['idempotency_key'] : null;
    $now = deploySyncDateTime();
    $responseBody = deploySyncTruncate($rawBody);

    if ($idempotencyKey) {
        $stmt = $db->prepare("
            UPDATE deploy_history
            SET status = ?, response_body = ?, finished_at = ?, duration_ms = COALESCE(duration_ms, 0)
            WHERE idempotency_key = ?
        ");
        $stmt->execute([$state === 'failed' ? 'failed' : 'success', $responseBody, $now, $idempotencyKey]);
    }

    if (!$idempotencyKey || ($stmt ?? null)?->rowCount() === 0) {
        deploySyncInsertHistory($db, [
            'action' => 'status_callback',
            'environment' => $settings['environment'] ?? 'production',
            'status' => $state === 'failed' ? 'failed' : 'success',
            'payload_json' => $responseBody,
            'response_body' => $responseBody,
            'triggered_by' => 'webhook_callback',
            'idempotency_key' => $idempotencyKey,
            'started_at' => $now,
        ]);
    }

    deploySyncUpdateSettings($db, [
        'sync_state' => $state,
        'last_status' => is_string($payload['status'] ?? null) ? (string)$payload['status'] : $state,
        'last_response' => $responseBody,
        'last_checked_at' => $now,
        'last_deploy_at' => $state === 'success' ? $now : ($settings['last_deploy_at'] ?? null),
        'in_progress_until' => in_array($state, ['pending', 'running'], true) ? deploySyncDateTime(time() + 600) : null,
    ]);

    return ['ok' => true, 'state' => $state, 'action' => $action];
}
