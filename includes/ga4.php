<?php
/**
 * includes/ga4.php — Google Analytics 4 Data API client
 *
 * Supports two auth methods (OAuth2 preferred, service account as fallback):
 *   A) OAuth2 — admin clicks "Connect with Google", refresh token stored locally.
 *   B) Service Account — requires adding SA email to GA4 property (GA4 UI blocks this).
 */

// ── Config paths ─────────────────────────────────────────────────────────────
define('GA4_SA_FILE',    __DIR__ . '/../config/ga4-service-account.json');
define('GA4_CFG_FILE',   __DIR__ . '/../config/ga4.local.php');
define('GA4_OAUTH_FILE', __DIR__ . '/../config/ga4-oauth.local.php');
define('GA4_CACHE_DIR',  __DIR__ . '/../cache');

if (is_file(GA4_CFG_FILE) && !defined('GA4_PROPERTY_ID')) {
    require_once GA4_CFG_FILE;
}
if (is_file(GA4_OAUTH_FILE) && !defined('GA4_OAUTH_REFRESH_TOKEN')) {
    require_once GA4_OAUTH_FILE;
}
if (!defined('GA4_PROPERTY_ID'))        define('GA4_PROPERTY_ID', '');
if (!defined('GA4_OAUTH_CLIENT_ID'))    define('GA4_OAUTH_CLIENT_ID', '');
if (!defined('GA4_OAUTH_CLIENT_SECRET'))define('GA4_OAUTH_CLIENT_SECRET', '');
if (!defined('GA4_OAUTH_REFRESH_TOKEN'))define('GA4_OAUTH_REFRESH_TOKEN', '');

// ── Helpers ───────────────────────────────────────────────────────────────────

/** OAuth2 is the preferred auth — returns true when connected */
function vdj_ga4_oauth_ready(): bool {
    return GA4_PROPERTY_ID !== '' && GA4_OAUTH_REFRESH_TOKEN !== '';
}

/** Service account fallback */
function vdj_ga4_sa_ready(): bool {
    return GA4_PROPERTY_ID !== '' && is_file(GA4_SA_FILE);
}

/** Either auth method available */
function vdj_ga4_ready(): bool {
    return vdj_ga4_oauth_ready() || vdj_ga4_sa_ready();
}

/** Returns OAuth2 config array */
function vdj_ga4_oauth_config(): array {
    return [
        'client_id'     => GA4_OAUTH_CLIENT_ID,
        'client_secret' => GA4_OAUTH_CLIENT_SECRET,
        'redirect_uri'  => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'visualdesignjourney.com') . '/admin/ga4-callback.php',
    ];
}

/** Generate Google OAuth2 authorization URL */
function vdj_ga4_oauth_url(): string {
    $cfg = vdj_ga4_oauth_config();
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id'     => $cfg['client_id'],
        'redirect_uri'  => $cfg['redirect_uri'],
        'response_type' => 'code',
        'scope'         => 'https://www.googleapis.com/auth/analytics.readonly',
        'access_type'   => 'offline',
        'prompt'        => 'consent',   // force refresh_token on every connect
    ]);
}

/** Exchange refresh token → access token (OAuth2) */
function vdj_ga4_oauth_access_token(): string {
    $cfg = vdj_ga4_oauth_config();
    [$raw] = vdj_ga4_http(
        'https://oauth2.googleapis.com/token',
        http_build_query([
            'client_id'     => $cfg['client_id'],
            'client_secret' => $cfg['client_secret'],
            'refresh_token' => GA4_OAUTH_REFRESH_TOKEN,
            'grant_type'    => 'refresh_token',
        ]),
        'application/x-www-form-urlencoded'
    );
    $resp = json_decode($raw, true);
    if (empty($resp['access_token'])) {
        $msg = $resp['error_description'] ?? $resp['error'] ?? substr($raw, 0, 200);
        throw new RuntimeException("GA4 OAuth token refresh failed: {$msg}");
    }
    return $resp['access_token'];
}

/** Get a valid access token — OAuth2 preferred, service account fallback */
function vdj_ga4_access_token_any(): string {
    if (vdj_ga4_oauth_ready()) return vdj_ga4_oauth_access_token();
    if (vdj_ga4_sa_ready())    return vdj_ga4_token();
    throw new RuntimeException('GA4 not configured. Set up OAuth2 or service account.');
}

function vdj_ga4_b64url(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Exchange service-account JSON for a short-lived Bearer token.
 * Throws RuntimeException on failure.
 */
function vdj_ga4_token(): string {
    $sa = json_decode(file_get_contents(GA4_SA_FILE), true);
    if (empty($sa['private_key']) || empty($sa['client_email'])) {
        throw new RuntimeException('Service account JSON is missing private_key or client_email.');
    }

    $now    = time();
    $header = vdj_ga4_b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $claims = vdj_ga4_b64url(json_encode([
        'iss'   => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $now,
        'exp'   => $now + 3600,
    ]));

    $sigInput = $header . '.' . $claims;
    $key = openssl_pkey_get_private($sa['private_key']);
    if (!$key) {
        $opensslErr = '';
        while ($e = openssl_error_string()) $opensslErr .= $e . ' ';
        throw new RuntimeException('Could not load private key. OpenSSL: ' . trim($opensslErr ?: 'unknown error') . '. Make sure the JSON was uploaded correctly.');
    }

    if (!openssl_sign($sigInput, $sig, $key, 'sha256WithRSAEncryption')) {
        throw new RuntimeException('openssl_sign failed — openssl extension may be missing or misconfigured on this server.');
    }
    $jwt = $sigInput . '.' . vdj_ga4_b64url($sig);

    $postData = http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion'  => $jwt,
    ]);

    [$raw, $httpCode] = vdj_ga4_http('https://oauth2.googleapis.com/token', $postData, 'application/x-www-form-urlencoded');
    $resp = json_decode($raw, true);

    if (empty($resp['access_token'])) {
        $msg = $resp['error_description'] ?? $resp['error'] ?? '';
        if ($msg === '') $msg = substr($raw, 0, 200);
        if ($msg === '') $msg = "HTTP {$httpCode} — server returned empty body. Check that your server allows outbound HTTPS (curl/allow_url_fopen).";
        throw new RuntimeException("GA4 token exchange failed: {$msg}");
    }

    return $resp['access_token'];
}

/**
 * Internal HTTP POST helper — prefers cURL, falls back to file_get_contents.
 * Returns [$responseBody, $httpStatusCode].
 */
function vdj_ga4_http(string $url, string $body, string $contentType = 'application/json', array $extraHeaders = []): array {
    $headers = array_merge(["Content-Type: {$contentType}"], $extraHeaders);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $raw  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException("cURL request to {$url} failed: {$err}");
        }
        return [(string)$raw, $code];
    }

    // Fallback: file_get_contents (needs allow_url_fopen=1)
    if (!ini_get('allow_url_fopen')) {
        throw new RuntimeException('Neither cURL nor allow_url_fopen is available on this server. Enable one in php.ini to use the GA4 API.');
    }

    $headerStr = implode("\r\n", $headers) . "\r\n";
    $ctx = stream_context_create(['http' => [
        'method'        => 'POST',
        'header'        => $headerStr,
        'content'       => $body,
        'timeout'       => 12,
        'ignore_errors' => true,
    ]]);
    $raw = @file_get_contents($url, false, $ctx);
    $raw = ($raw === false) ? '' : $raw;

    // Parse status from response headers
    $code = 0;
    $respHeaders = function_exists('http_get_last_response_headers')
        ? http_get_last_response_headers()
        : ($http_response_header ?? []);
    foreach ($respHeaders as $h) {
        if (preg_match('/^HTTP\/[\d.]+ (\d+)/', $h, $m)) {
            $code = (int)$m[1];
        }
    }

    return [$raw, $code];
}

/**
 * Run a standard report against the GA4 Data API.
 */
function vdj_ga4_report(
    string $token,
    array  $metrics,
    array  $dimensions  = [],
    string $startDate   = '7daysAgo',
    string $endDate     = 'today',
    int    $limit       = 20,
    array  $orderBys    = []
): array {
    $body = ['dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]]];
    $body['metrics']    = array_map(fn($m) => ['name' => $m], $metrics);
    $body['dimensions'] = array_map(fn($d) => ['name' => $d], $dimensions);
    $body['limit']      = $limit;
    if ($orderBys) $body['orderBys'] = $orderBys;
    $body['keepEmptyRows'] = false;

    $url = 'https://analyticsdata.googleapis.com/v1beta/properties/' . GA4_PROPERTY_ID . ':runReport';
    [$raw, $code] = vdj_ga4_http($url, json_encode($body), 'application/json', ["Authorization: Bearer {$token}"]);
    $data = json_decode($raw, true);
    if (!is_array($data)) throw new RuntimeException("GA4 runReport: non-JSON response (HTTP {$code}): " . substr($raw, 0, 120));
    if (isset($data['error'])) throw new RuntimeException('GA4 runReport: ' . ($data['error']['message'] ?? json_encode($data['error'])));
    return $data;
}

/**
 * Run a real-time report (active users right now).
 */
function vdj_ga4_realtime(string $token): array {
    $body = json_encode([
        'metrics'    => [['name' => 'activeUsers']],
        'dimensions' => [['name' => 'country']],
    ]);

    $url = 'https://analyticsdata.googleapis.com/v1beta/properties/' . GA4_PROPERTY_ID . ':runRealtimeReport';
    [$raw] = vdj_ga4_http($url, $body, 'application/json', ["Authorization: Bearer {$token}"]);
    $data = json_decode($raw, true);
    return (is_array($data) && !isset($data['error'])) ? $data : [];
}

/**
 * Parse report rows into [ [dim1 => val, ..., met1 => val, ...], ... ]
 */
function vdj_ga4_rows(array $report): array {
    $dimH = array_column($report['dimensionHeaders'] ?? [], 'name');
    $metH = array_column($report['metricHeaders']   ?? [], 'name');
    $rows = [];
    foreach ($report['rows'] ?? [] as $row) {
        $r = [];
        foreach ($row['dimensionValues'] ?? [] as $i => $dv) $r[$dimH[$i] ?? "d{$i}"] = $dv['value'];
        foreach ($row['metricValues']   ?? [] as $i => $mv) $r[$metH[$i] ?? "m{$i}"] = $mv['value'];
        $rows[] = $r;
    }
    return $rows;
}

/** Get totals from a report (index = metric position) */
function vdj_ga4_total(array $report, int $idx = 0): string {
    return $report['totals'][0]['metricValues'][$idx]['value'] ?? '0';
}

// ── File cache (5 minutes) ────────────────────────────────────────────────────
function vdj_ga4_cache_get(string $key): ?array {
    $file = GA4_CACHE_DIR . '/ga4-' . md5($key) . '.json';
    if (!is_file($file) || (time() - filemtime($file)) > 300) return null;
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : null;
}

function vdj_ga4_cache_set(string $key, array $data): void {
    $file = GA4_CACHE_DIR . '/ga4-' . md5($key) . '.json';
    @file_put_contents($file, json_encode($data), LOCK_EX);
}

/**
 * Fetch all dashboard data in one go (uses cache).
 * Returns array with keys: realtime, summary7d, summary28d, top_pages, countries, devices
 * Throws RuntimeException on API failure.
 */
function vdj_ga4_dashboard_data(): array {
    $cacheKey = 'dashboard-' . GA4_PROPERTY_ID;
    $cached   = vdj_ga4_cache_get($cacheKey);
    if ($cached) return $cached;

    $token = vdj_ga4_access_token_any();

    // Real-time active users
    $rt = vdj_ga4_realtime($token);
    $activeNow = 0;
    foreach ($rt['rows'] ?? [] as $row) {
        $activeNow += (int)($row['metricValues'][0]['value'] ?? 0);
    }

    // 7-day summary
    $sum7 = vdj_ga4_report($token,
        ['sessions', 'activeUsers', 'screenPageViews', 'bounceRate', 'averageSessionDuration'],
        [], '7daysAgo', 'today'
    );

    // 28-day summary
    $sum28 = vdj_ga4_report($token,
        ['sessions', 'activeUsers', 'screenPageViews'],
        [], '28daysAgo', 'today'
    );

    // Top 10 pages by views (7d)
    $pagesRaw = vdj_ga4_report($token,
        ['screenPageViews', 'activeUsers'],
        ['pagePath', 'pageTitle'],
        '7daysAgo', 'today', 10,
        [['metric' => ['metricName' => 'screenPageViews'], 'desc' => true]]
    );

    // Top 8 countries (7d)
    $ctryRaw = vdj_ga4_report($token,
        ['sessions'],
        ['country'],
        '7daysAgo', 'today', 8,
        [['metric' => ['metricName' => 'sessions'], 'desc' => true]]
    );

    // Sessions by device (7d)
    $devRaw = vdj_ga4_report($token,
        ['sessions'],
        ['deviceCategory'],
        '7daysAgo', 'today', 5
    );

    // Daily sessions (last 14 days for sparkline)
    $dailyRaw = vdj_ga4_report($token,
        ['sessions', 'activeUsers'],
        ['date'],
        '13daysAgo', 'today', 14,
        [['dimension' => ['dimensionName' => 'date'], 'desc' => false]]
    );

    $result = [
        'active_now'  => $activeNow,
        'summary_7d'  => [
            'sessions'    => vdj_ga4_total($sum7, 0),
            'users'       => vdj_ga4_total($sum7, 1),
            'pageviews'   => vdj_ga4_total($sum7, 2),
            'bounce_rate' => round((float)vdj_ga4_total($sum7, 3) * 100, 1),
            'avg_duration'=> (int)(float)vdj_ga4_total($sum7, 4),
        ],
        'summary_28d' => [
            'sessions'  => vdj_ga4_total($sum28, 0),
            'users'     => vdj_ga4_total($sum28, 1),
            'pageviews' => vdj_ga4_total($sum28, 2),
        ],
        'top_pages'   => vdj_ga4_rows($pagesRaw),
        'countries'   => vdj_ga4_rows($ctryRaw),
        'devices'     => vdj_ga4_rows($devRaw),
        'daily'       => vdj_ga4_rows($dailyRaw),
        'fetched_at'  => time(),
    ];

    vdj_ga4_cache_set($cacheKey, $result);
    return $result;
}
