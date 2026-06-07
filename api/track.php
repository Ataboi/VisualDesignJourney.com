<?php
/**
 * api/track.php — Lightweight privacy-safe page-view & click tracker.
 * Called via navigator.sendBeacon() — returns 204 No Content.
 *
 * POST params:
 *   type        string  'view' | 'click'
 *   path        string  URL path (e.g. /board.php?id=42)
 *   title       string  document.title (optional)
 *   ref         string  document.referrer stripped to domain (optional)
 *   board_id    int     required when type=click
 *   click_src   string  'grid'|'featured'|'lightbox'|'profile' (click only)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start_if_not_started();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$db  = getDB();
$uid = $_SESSION['user_id'] ?? null;

// ── Detect device type from User-Agent ───────────────────────────────────────
function vdj_device_type(): string {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone/i', $ua)) {
        return 'mobile';
    }
    if (preg_match('/tablet|ipad/i', $ua)) {
        return 'tablet';
    }
    return 'desktop';
}

// ── Privacy-safe visitor hash: sha256(salt + IP + UA) ────────────────────────
// Never stored in plain form — used only to count distinct daily visitors.
function vdj_visitor_hash(): string {
    $ip  = $_SERVER['HTTP_CF_CONNECTING_IP']
        ?? $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '';
    // Take only the first IP if comma-list (X-Forwarded-For)
    $ip = trim(explode(',', $ip)[0]);
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    // Daily salt: hash rotates every day — cannot reconstruct raw IP later
    $daySalt = 'vdj-' . date('Y-m-d');
    return hash('sha256', $daySalt . $ip . $ua);
}

// Respect DNT — if Do Not Track is set, skip personal hashing
$doNotTrack = ($_SERVER['HTTP_DNT'] ?? '0') === '1';
$visitorHash = $doNotTrack ? 'dnt-' . date('Y-m-d') : vdj_visitor_hash();

$type    = $_POST['type']     ?? 'view';
$path    = mb_substr(trim($_POST['path']  ?? ''), 0, 500);
$title   = mb_substr(trim($_POST['title'] ?? ''), 0, 255);
$ref     = mb_substr(trim($_POST['ref']   ?? ''), 0, 500);
$device  = vdj_device_type();
$isGuest = $uid ? 0 : 1;

// Skip admin paths and bot-likely crawls
if (str_starts_with($path, '/admin') || str_starts_with($path, '/api')) {
    http_response_code(204);
    exit;
}

// ── Ensure page_views table exists ───────────────────────────────────────────
try {
    $db->exec("CREATE TABLE IF NOT EXISTS page_views (
        id           BIGINT AUTO_INCREMENT PRIMARY KEY,
        page_path    VARCHAR(500) NOT NULL,
        page_title   VARCHAR(255),
        user_id      INT NULL,
        is_guest     TINYINT(1) NOT NULL DEFAULT 1,
        referrer     VARCHAR(500),
        device_type  ENUM('desktop','mobile','tablet') NOT NULL DEFAULT 'desktop',
        visitor_hash VARCHAR(64),
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_created (created_at),
        KEY idx_path    (page_path(191)),
        KEY idx_guest   (is_guest),
        KEY idx_visitor (visitor_hash)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {}

// ── Ensure board_clicks table exists ─────────────────────────────────────────
try {
    $db->exec("CREATE TABLE IF NOT EXISTS board_clicks (
        id           BIGINT AUTO_INCREMENT PRIMARY KEY,
        board_id     INT NOT NULL,
        click_source VARCHAR(80) DEFAULT 'unknown',
        user_id      INT NULL,
        is_guest     TINYINT(1) NOT NULL DEFAULT 1,
        visitor_hash VARCHAR(64),
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_board   (board_id),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {}

// ── Handle tracking type ──────────────────────────────────────────────────────
if ($type === 'view' && $path !== '') {
    try {
        $db->prepare(
            'INSERT INTO page_views (page_path, page_title, user_id, is_guest, referrer, device_type, visitor_hash)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$path, $title ?: null, $uid, $isGuest, $ref ?: null, $device, $visitorHash]);
    } catch (Throwable $e) {}

} elseif ($type === 'click') {
    $boardId   = (int)($_POST['board_id']   ?? 0);
    $clickSrc  = mb_substr(trim($_POST['click_src'] ?? 'unknown'), 0, 80);
    if ($boardId > 0) {
        try {
            $db->prepare(
                'INSERT INTO board_clicks (board_id, click_source, user_id, is_guest, visitor_hash)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([$boardId, $clickSrc, $uid, $isGuest, $visitorHash]);
        } catch (Throwable $e) {}
    }
}

http_response_code(204);
exit;
