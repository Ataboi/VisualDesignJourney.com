<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/i18n.php';

if (!defined('VDJ_BUILD_ID')) {
    define('VDJ_BUILD_ID', 'vdj-v2-hybrid-safe-deploy-20260607');
}
if (!defined('VDJ_ASSET_VERSION')) {
    define('VDJ_ASSET_VERSION', '4.0');
}

/* ── EMAIL VALIDATION ────────────────────────────────────────────────────── */
function isAllowedEmail(string $email): bool {
    $domain = emailDomain($email);
    if ($domain === '') return false;

    $blocked = [
        'mailinator.com','guerrillamail.com','guerrillamail.org','guerrillamail.net',
        'tempmail.com','temp-mail.org','throwaway.email','sharklasers.com',
        'trashmail.com','trashmail.net','dispostable.com','yopmail.com',
        'maildrop.cc','getairmail.com','fakeinbox.com','spamgourmet.com',
        'spam4.me','bccto.me','mailnull.com','spammotel.com','10minutemail.com',
        '10minutemail.net','20minutemail.com','33mail.com','anonaddy.com',
        'burnermail.io','byom.de','disposablemail.com','emailondeck.com',
        'getnada.com','inboxkitten.com','mail.tm','moakt.com','mytemp.email',
        'tempmailo.com','temp-mail.io','tmailor.com','tmpmail.org','mailnesia.com',
        'mintemail.com','mohmal.com','dropmail.me','fakemail.net','fakemailgenerator.com',
        'generator.email','mailcatch.com','emailfake.com','yopmail.fr','yopmail.net',
        'duck.com',
    ];
    if (in_array($domain, $blocked, true)) return false;

    foreach (['.ru', '.cn', '.top', '.xyz', '.click', '.monster', '.quest', '.rest', '.cam', '.icu', '.cyou'] as $tld) {
        if (str_ends_with($domain, $tld)) return false;
    }

    // Allow well-known permanent consumer providers immediately.
    $allowed = [
        'gmail.com','googlemail.com','yahoo.com','yahoo.co.uk','yahoo.co.jp',
        'outlook.com','hotmail.com','hotmail.co.uk','live.com','msn.com',
        'icloud.com','me.com','mac.com',
        'proton.me','protonmail.com','pm.me',
        'yandex.com',
    ];
    if (in_array($domain, $allowed, true)) return true;

    // Corporate/custom domains must at least exist in DNS.
    if (function_exists('checkdnsrr')) {
        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A') || checkdnsrr($domain, 'AAAA');
    }
    return true;
}

function normalizeEmail(string $email): string {
    return strtolower(trim($email));
}

function emailDomain(string $email): string {
    $email = normalizeEmail($email);
    $pos = strrpos($email, '@');
    if ($pos === false) return '';
    return strtolower(substr($email, $pos + 1));
}

function isBotUserAgent(): bool {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '') return true;
    return (bool)preg_match('/curl|wget|python|scrapy|httpclient|go-http-client|libwww|bot|spider|crawler|headless/i', $ua);
}

function signupSpamReason(string $username, string $email): ?string {
    $username = strtolower(trim($username));
    $email = normalizeEmail($email);
    $local = strtolower(strtok($email, '@') ?: '');

    $badFragments = [
        'casino','crypto','forex','loan','porn','sex','escort','viagra','cialis',
        'telegram','whatsapp','bonus','betting','slots','seo-backlink','backlink',
        'traffic','followers','nft','airdrop',
    ];
    foreach ($badFragments as $fragment) {
        if (str_contains($username, $fragment) || str_contains($local, $fragment)) {
            return 'Please use a real personal or professional account.';
        }
    }

    if (preg_match('/\d{5,}/', $username) || preg_match('/^[a-z]{1,4}\d{4,}$/', $username)) {
        return 'Please choose a more readable username.';
    }
    if (strlen($local) > 24 && preg_match('/[a-z]{8,}\d{4,}/', $local)) {
        return 'Please use a real email address.';
    }
    return null;
}

/* ── ABUSE PROTECTION ───────────────────────────────────────────────────── */
function clientIp(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        $value = $_SERVER[$key] ?? '';
        if ($key === 'HTTP_X_FORWARDED_FOR' && $value !== '') {
            $value = trim(explode(',', $value)[0]);
        }
        if ($value !== '' && filter_var($value, FILTER_VALIDATE_IP)) {
            return $value;
        }
    }
    return '0.0.0.0';
}

function rateLimitDir(): string {
    $dir = rtrim(UPLOAD_DIR, '/') . '/.rate-limit';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        $dir = sys_get_temp_dir() . '/vdj-rate-limit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
    return $dir;
}

function rateLimitCheck(string $bucket, int $maxAttempts, int $windowSeconds): bool {
    return rateLimitCheckKey($bucket, clientIp(), $maxAttempts, $windowSeconds);
}

function rateLimitCheckKey(string $bucket, string $keyMaterial, int $maxAttempts, int $windowSeconds): bool {
    $dir = rateLimitDir();
    $key = hash('sha256', $bucket . '|' . $keyMaterial);
    $file = $dir . '/' . $key . '.json';
    $now = time();
    $hits = [];

    if (is_file($file)) {
        $raw = file_get_contents($file);
        $decoded = json_decode($raw ?: '[]', true);
        if (is_array($decoded)) {
            $hits = array_values(array_filter($decoded, fn($ts) => is_int($ts) && $ts > ($now - $windowSeconds)));
        }
    }

    if (count($hits) >= $maxAttempts) {
        return false;
    }

    $hits[] = $now;
    file_put_contents($file, json_encode($hits), LOCK_EX);
    return true;
}

function signupTimestamp(): string {
    session_start_if_not_started();
    $_SESSION['signup_started_at'] = time();
    return (string)$_SESSION['signup_started_at'];
}

function verifySignupTiming(int $minSeconds = 3, int $maxSeconds = 7200): bool {
    session_start_if_not_started();
    $posted = (int)($_POST['form_started_at'] ?? 0);
    $sessionValue = (int)($_SESSION['signup_started_at'] ?? 0);
    if ($posted <= 0 || $sessionValue <= 0 || $posted !== $sessionValue) {
        return false;
    }
    $age = time() - $posted;
    return $age >= $minSeconds && $age <= $maxSeconds;
}

/**
 * includes/functions.php
 * Shared helper functions — auth, users, boards, uploads, notifications.
 */

/* ── SESSION ──────────────────────────────────────────────────────────────── */
function session_start_if_not_started(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function requireLogin(): void {
    session_start_if_not_started();
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function currentUserId(): ?int {
    session_start_if_not_started();
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function publicUrl(string $path = ''): string {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $isLocalHost = $host === '' || str_contains($host, 'localhost') || str_starts_with($host, '127.0.0.1');
    $base = rtrim(SITE_URL, '/');

    if (!$isLocalHost) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $base = $scheme . '://' . $host;
    }

    if ($path === '') return $base;
    return $base . '/' . ltrim($path, '/');
}

/* ── USERS ────────────────────────────────────────────────────────────────── */
function getUserById(PDO $db, int $id): ?array {
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getUserByUsername(PDO $db, string $username): ?array {
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    return $stmt->fetch() ?: null;
}

function getUserByEmail(PDO $db, string $email): ?array {
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}

function avatarUrl(array $user): string {
    return imageUrl($user['avatar'] ?? null);
}

function avatarInitial(array $user): string {
    return strtoupper(substr($user['username'], 0, 1));
}

function isFollowing(PDO $db, int $followerId, int $followingId): bool {
    $stmt = $db->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ? LIMIT 1');
    $stmt->execute([$followerId, $followingId]);
    return (bool)$stmt->fetchColumn();
}

function getFollowerCount(PDO $db, int $userId): int {
    $stmt = $db->prepare('SELECT COUNT(*) FROM follows WHERE following_id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function getFollowingCount(PDO $db, int $userId): int {
    $stmt = $db->prepare('SELECT COUNT(*) FROM follows WHERE follower_id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/* ── BOARDS ───────────────────────────────────────────────────────────────── */
function getBoardById(PDO $db, int $id): ?array {
    $stmt = $db->prepare(
        'SELECT b.*, u.username, u.avatar
         FROM boards b
         JOIN users u ON u.id = b.user_id
         WHERE b.id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getBoardImages(PDO $db, int $boardId): array {
    $stmt = $db->prepare(
        'SELECT * FROM board_images WHERE board_id = ? ORDER BY sort_order ASC'
    );
    $stmt->execute([$boardId]);
    return $stmt->fetchAll();
}

function getLikeCount(PDO $db, int $boardId): int {
    $stmt = $db->prepare('SELECT COUNT(*) FROM board_likes WHERE board_id = ?');
    $stmt->execute([$boardId]);
    return (int)$stmt->fetchColumn();
}

function hasLiked(PDO $db, int $boardId, int $userId): bool {
    $stmt = $db->prepare('SELECT 1 FROM board_likes WHERE board_id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$boardId, $userId]);
    return (bool)$stmt->fetchColumn();
}

function getSaveCount(PDO $db, int $boardId): int {
    try {
        if (dbColumnExists($db, 'boards', 'save_count')) {
            $stmt = $db->prepare('SELECT save_count FROM boards WHERE id = ? LIMIT 1');
            $stmt->execute([$boardId]);
            return (int)$stmt->fetchColumn();
        }
        $stmt = $db->prepare('SELECT COUNT(*) FROM board_saves WHERE board_id = ?');
        $stmt->execute([$boardId]);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function hasSaved(PDO $db, int $boardId, int $userId): bool {
    try {
        $stmt = $db->prepare('SELECT 1 FROM board_saves WHERE board_id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$boardId, $userId]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function getBoardCount(PDO $db, int $userId): int {
    $stmt = $db->prepare('SELECT COUNT(*) FROM boards WHERE user_id = ? AND is_draft = 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Fetch paginated public boards with optional tag/search filter.
 */
function getBoards(PDO $db, int $page = 1, int $perPage = 20, string $tag = '', string $search = ''): array {
    $offset = ($page - 1) * $perPage;
    $conditions = ['b.is_draft = 0'];
    $params = [];

    if ($tag !== '') {
        $conditions[] = 'FIND_IN_SET(?, b.style_tags)';
        $params[] = $tag;
    }
    if ($search !== '') {
        $conditions[] = '(b.title LIKE ? OR u.username LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $where = implode(' AND ', $conditions);
    $params[] = $perPage;
    $params[] = $offset;

    $stmt = $db->prepare(
        "SELECT b.*, u.username, u.avatar,
                (SELECT COUNT(*) FROM board_likes bl WHERE bl.board_id = b.id) AS like_count
         FROM boards b
         JOIN users u ON u.id = b.user_id
         WHERE $where
         ORDER BY b.created_at DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getUserBoards(PDO $db, int $userId, bool $includeDrafts = false): array {
    $draftClause = $includeDrafts ? '' : 'AND is_draft = 0';
    $stmt = $db->prepare(
        "SELECT b.*,
                (SELECT COUNT(*) FROM board_likes bl WHERE bl.board_id = b.id) AS like_count
         FROM boards b
         WHERE b.user_id = ? $draftClause
         ORDER BY b.created_at DESC"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function userTopStyleTags(PDO $db, int $userId, int $limit = 3): array {
    try {
        $stmt = $db->prepare('SELECT style_tags FROM boards WHERE user_id = ? AND is_draft = 0 AND COALESCE(style_tags, "") <> ""');
        $stmt->execute([$userId]);
        $counts = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $raw) {
            foreach (parseTags((string)$raw) as $tag) {
                if ($tag !== '') $counts[$tag] = ($counts[$tag] ?? 0) + 1;
            }
        }
        arsort($counts);
        return array_slice(array_keys($counts), 0, $limit);
    } catch (Throwable $e) {
        return [];
    }
}

function parseTags(string $raw): array {
    return array_filter(array_map('trim', explode(',', $raw)));
}

/* ── NOTIFICATIONS ────────────────────────────────────────────────────────── */
function getUnreadNotifCount(PDO $db, int $userId): int {
    $stmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function getNotifications(PDO $db, int $userId, int $limit = 30): array {
    $stmt = $db->prepare(
        'SELECT n.*, u.username AS from_username, u.avatar AS from_avatar, b.title AS board_title
         FROM notifications n
         LEFT JOIN users u ON u.id = n.from_user_id
         LEFT JOIN boards b ON b.id = n.board_id
         WHERE n.user_id = ?
         ORDER BY n.created_at DESC
         LIMIT ?'
    );
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

function createNotification(PDO $db, int $userId, ?int $fromUserId, string $type, ?int $boardId = null): void {
    if ($userId === $fromUserId) return; // no self-notifications
    $stmt = $db->prepare(
        'INSERT INTO notifications (user_id, from_user_id, type, board_id) VALUES (?,?,?,?)'
    );
    $stmt->execute([$userId, $fromUserId, $type, $boardId]);
}

function markAllNotifsRead(PDO $db, int $userId): void {
    $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$userId]);
}

/* ── IMAGE UPLOAD ─────────────────────────────────────────────────────────── */
/**
 * Compress an image using GD. Resizes to max 1200px wide, saves at 85% JPEG.
 * Returns path to the compressed file (replaces original).
 */
function compressImage(string $path, string $mime): void {
    if (!extension_loaded('gd')) return;

    [$w, $h] = getimagesize($path);
    $maxW = 1200;

    $src = match($mime) {
        'image/jpeg' => imagecreatefromjpeg($path),
        'image/png'  => imagecreatefrompng($path),
        'image/webp' => imagecreatefromwebp($path),
        default      => null,
    };
    if (!$src) return;

    if ($w > $maxW) {
        $newH = (int)round($h * ($maxW / $w));
        $dst  = imagecreatetruecolor($maxW, $newH);
        // Preserve transparency for PNG
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxW, $newH, $w, $h);
        imagedestroy($src);
        $src = $dst;
    }

    // Always save as JPEG for storage efficiency (85% quality)
    $jpgPath = preg_replace('/\.[^.]+$/', '.jpg', $path);
    imagejpeg($src, $jpgPath, 85);
    imagedestroy($src);

    // Remove original if it was a different format
    if ($jpgPath !== $path && file_exists($path)) {
        unlink($path);
    }
}

function handleImageUpload(array $file, string $subDir = ''): string {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload error code: ' . $file['error']);
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new RuntimeException('File exceeds 5MB limit.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_TYPES, true)) {
        throw new RuntimeException('Only JPG, PNG and WebP files are allowed.');
    }
    if (@getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('The uploaded file is not a valid image.');
    }

    $ext      = match($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'jpg',
    };
    $dir      = UPLOAD_DIR . ($subDir ? rtrim($subDir, '/') . '/' : '');
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        throw new RuntimeException('Failed to save uploaded file.');
    }
    // Compress & resize via GD
    compressImage($dir . $filename, $mime);
    // Filename changes to .jpg only when GD actually created the compressed file.
    $jpgFile = preg_replace('/\.[^.]+$/', '.jpg', $filename);
    $finalFile = file_exists($dir . $jpgFile) ? $jpgFile : $filename;
    return ($subDir ? rtrim($subDir, '/') . '/' : '') . $finalFile;
}

/* ── FORMATTING ───────────────────────────────────────────────────────────── */
function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}

function formatCount(int $n): string {
    if ($n >= 1000) return round($n / 1000, 1) . 'k';
    return (string)$n;
}

/* ── MULTILINGUAL CONTENT ────────────────────────────────────────────────── */
function localizedField(array $row, string $field, ?string $lang = null): string {
    $lang = $lang ?: vdj_current_language();
    $fallback = trim((string)($row[$field] ?? ''));

    if ($lang === 'en') {
        return $fallback;
    }

    $localizedKey = $field . '_' . $lang;
    $localized = trim((string)($row[$localizedKey] ?? ''));
    return $localized !== '' ? $localized : $fallback;
}

function localizedSlug(array $row, string $field = 'slug', ?string $lang = null): string {
    return localizedField($row, $field, $lang);
}

function localizedBoardTitle(array $board): string {
    return localizedField($board, 'title');
}

function localizedBoardDescription(array $board): string {
    return localizedField($board, 'description');
}

function localizedVibeLabel(array $vibe): string {
    return localizedField($vibe, 'label');
}

function localizedCategoryName(array $category): string {
    return localizedField($category, 'name');
}

function localizedCategorySlug(array $category): string {
    return localizedSlug($category);
}

function localizedTagName(array $tag): string {
    return localizedField($tag, 'name');
}

function localizedTagSlug(array $tag): string {
    return localizedSlug($tag);
}

function blogDirtyPatterns(): array {
    return [
        '/I understand the previous response/i',
        '/JSON output format/i',
        '/\bad_?1\b/i',
        '/lorem ipsum/i',
        '/shortcode fragments?/i',
        '/\bparagraphs\b/i',
        '/```[\s\S]*?```/',
        '/\[[a-zA-Z][a-zA-Z0-9_-]*(?:\s+[^\]]*)?\]/',
        '/\[\/[a-zA-Z][a-zA-Z0-9_-]*\]/',
    ];
}

function blogTextLooksDirty(string $text): bool {
    $text = trim($text);
    if ($text === '') return false;
    foreach (blogDirtyPatterns() as $pattern) {
        if (preg_match($pattern, $text)) return true;
    }
    $prefix = ltrim(mb_substr($text, 0, 160));
    if (preg_match('/^\s*[\{\[]\s*["\']?[a-z0-9_-]+["\']?\s*[:\}\]]/i', $prefix)) return true;
    if (substr_count($prefix, '{') >= 2 || substr_count($prefix, '":') >= 2) return true;
    return false;
}

function blogStripDirtyFragments(string $text): string {
    $text = preg_replace('/```[\s\S]*?```/', ' ', $text);
    $text = preg_replace('/\[[a-zA-Z][a-zA-Z0-9_-]*(?:\s+[^\]]*)?\]/', ' ', $text);
    $text = preg_replace('/\[\/[a-zA-Z][a-zA-Z0-9_-]*\]/', ' ', $text);
    $text = preg_replace('/\{\s*"[^"]+"\s*:\s*[^}]+\}/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', (string)$text);
    return trim((string)$text);
}

function blogRemoveDuplicateTitlePrefix(string $title, string $text): string {
    $titlePlain = trim(strip_tags($title));
    if ($titlePlain === '' || trim($text) === '') return trim($text);
    $normalize = static function (string $value): string {
        $value = mb_strtolower(trim(strip_tags($value)));
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', (string)$value);
        return trim(preg_replace('/\s+/', ' ', (string)$value));
    };
    $normalizedTitle = $normalize($titlePlain);
    $normalizedStart = $normalize(mb_substr($text, 0, max(80, mb_strlen($titlePlain) + 40)));
    if ($normalizedTitle !== '' && str_starts_with($normalizedStart, $normalizedTitle)) {
        $len = mb_strlen($titlePlain);
        $text = trim(mb_substr($text, $len));
        $text = ltrim($text, " \t\n\r\0\x0B:-–—|.");
    }
    return trim($text);
}

function cleanBlogExcerpt(string $title, string $excerpt = '', string $content = '', ?string $lang = null, int $maxChars = 180): string {
    $candidates = [$excerpt, $content];
    foreach ($candidates as $candidate) {
        $text = trim(strip_tags((string)$candidate));
        if ($text === '') continue;
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = blogStripDirtyFragments($text);
        $text = blogRemoveDuplicateTitlePrefix($title, $text);
        if ($text === '' || blogTextLooksDirty($text)) continue;
        if (mb_strlen($text) > $maxChars) {
            $text = mb_substr($text, 0, $maxChars);
            $last = mb_strrpos($text, ' ');
            if ($last !== false) $text = mb_substr($text, 0, $last);
            $text = rtrim($text, " \t\n\r\0\x0B.,;:") . '…';
        }
        return $text;
    }
    return '';
}

function blogHasDirtyContent(array $post): bool {
    $fields = ['title', 'excerpt', 'content', 'seo_description', 'title_tr', 'excerpt_tr', 'content_tr', 'seo_description_tr', 'title_de', 'excerpt_de', 'content_de', 'seo_description_de'];
    foreach ($fields as $field) {
        if (!empty($post[$field]) && blogTextLooksDirty((string)$post[$field])) return true;
    }
    $title = (string)($post['title'] ?? '');
    $excerpt = (string)($post['excerpt'] ?? '');
    return $excerpt !== '' && cleanBlogExcerpt($title, $excerpt, (string)($post['content'] ?? ''), 'en', 160) === '';
}

function blogTranslationStatus(array $post, ?string $lang = null): array {
    $lang = $lang ?: vdj_current_language();
    if ($lang === 'en') {
        return ['lang' => 'en', 'is_translated' => true, 'is_partial' => false, 'missing' => [], 'label' => 'Original'];
    }
    $missing = [];
    $filled = [];
    foreach (['title', 'slug', 'content'] as $field) {
        if (trim((string)($post[$field . '_' . $lang] ?? '')) === '') {
            $missing[] = $field . '_' . $lang;
        } else {
            $filled[] = $field . '_' . $lang;
        }
    }
    $isTranslated = empty($missing);
    $isPartial = !$isTranslated && !empty($filled);
    $state = $isTranslated ? 'ready' : ($isPartial ? 'partial' : 'missing');
    return [
        'lang' => $lang,
        'is_translated' => $isTranslated,
        'is_partial' => $isPartial,
        'state' => $state,
        'missing' => $missing,
        'label' => $isTranslated
            ? strtoupper($lang) . ' ready'
            : strtoupper($lang) . ' ' . $state . ': ' . implode(', ', $missing),
    ];
}

function dbColumnExists(PDO $db, string $table, string $column): bool {
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);
        return $cache[$key] = ((int)$stmt->fetchColumn() > 0);
    } catch (Throwable $e) {
        return $cache[$key] = false;
    }
}

function slugify(string $value): string {
    $value = trim($value);
    if ($value === '') return '';
    if (function_exists('iconv')) {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    }
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    return trim((string)$value, '-');
}

/** Returns a fully-qualified image URL whether the stored value is absolute or a local path */
function imageUrl(?string $path): string {
    if (empty($path)) return '';
    if (strncmp($path, 'http://', 7) === 0 || strncmp($path, 'https://', 8) === 0) {
        return $path;
    }
    return publicUrl('/uploads/' . ltrim($path, '/'));
}

/** Returns avatar img src — imageUrl if set, empty string if not */
function avatarSrc(?string $avatar): string {
    return imageUrl($avatar);
}

function siteUrl(string $path = ''): string {
    return publicUrl($path);
}

function blogIndexPath(array $params = [], ?string $lang = null): string {
    $params = array_filter($params, fn($value) => $value !== null && $value !== '');
    $path = vdj_localized_url('/blog', $lang);
    return $path . ($params ? '?' . http_build_query($params) : '');
}

function blogCanonicalPath(string|array $post, ?string $lang = null): string {
    $lang = $lang ?: vdj_current_language();
    if (is_array($post)) {
        $slug = ($lang !== 'en' && !empty($post["slug_{$lang}"]))
            ? $post["slug_{$lang}"]
            : ($post['slug'] ?? '');
    } else {
        $slug = $post;
    }
    $slug = trim((string)$slug, '/');
    return vdj_localized_url('/blog/' . rawurlencode($slug) . '/', $lang);
}

function blogPostUrl(string|array $post): string {
    return blogCanonicalPath($post);
}

function blogAuthorProfileUsername(?string $author): string {
    $author = trim((string)$author);
    if ($author === '') return '';
    $map = [
        'ataozelbicer' => 'Ata',
        'ata ozelbicer' => 'Ata',
        'ata özelbiçer' => 'Ata',
        'ata' => 'Ata',
    ];
    $key = mb_strtolower($author);
    return $map[$key] ?? preg_replace('/[^A-Za-z0-9_]/', '', $author);
}

function blogAuthorProfileUrl(?string $author): string {
    $username = blogAuthorProfileUsername($author);
    return $username !== '' ? '/profile.php?u=' . rawurlencode($username) : '';
}

function blogCategoryUrl(string $slug): string {
    return vdj_localized_url('/category/' . rawurlencode(trim($slug, '/')) . '/');
}

function blogTagUrl(string $slug): string {
    return vdj_localized_url('/tag/' . rawurlencode(trim($slug, '/')) . '/');
}

function boardUrl(array $board): string {
    $id = (int)($board['id'] ?? 0);
    $slug = localizedSlug($board);
    $path = $id > 0 && $slug !== ''
        ? '/board/' . $id . '-' . rawurlencode(trim($slug, '/')) . '/'
        : '/board/' . $id;
    return vdj_localized_url($path);
}

function vdjLocalizedDate(?string $date, string $format = 'medium'): string {
    $timestamp = strtotime((string)$date);
    if (!$timestamp) return '';
    $lang = vdj_current_language();
    if ($lang === 'en') {
        return date($format === 'short' ? 'M j, Y' : 'M j, Y', $timestamp);
    }
    $months = [
        'tr' => [1=>'Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'],
        'de' => [1=>'Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'],
    ];
    $day = (int)date('j', $timestamp);
    $month = $months[$lang][(int)date('n', $timestamp)] ?? date('M', $timestamp);
    $year = date('Y', $timestamp);
    return $lang === 'de' ? "{$day}. {$month} {$year}" : "{$day} {$month} {$year}";
}

function vdjRenderLocalizedStaticPage(string $page): bool {
    $lang = vdj_current_language();
    if ($lang === 'en') return false;

    $copy = [
        'tr' => [
            'privacy' => [
                'title' => 'Gizlilik Politikası',
                'meta' => 'Visual Design Journey kişisel verileri, çerezleri ve kullanıcı haklarını nasıl ele alır.',
                'eyebrow' => 'Yasal',
                'intro' => 'Gizlilik bir özellik değil, temel bir haktır. Bu sayfa hangi verileri topladığımızı, neden kullandığımızı ve seçimlerinizi nasıl yöneteceğinizi açıklar.',
                'sections' => [
                    ['Topladığımız Bilgiler', 'Hesap, bülten, iletişim ve board oluşturma gibi işlemlerde verdiğiniz bilgiler; ayrıca güvenlik, analiz ve performans için sınırlı teknik veriler işlenebilir.'],
                    ['Kullanım Amacı', 'Verileri hesabınızı çalıştırmak, içerik göndermenizi sağlamak, spamı önlemek, site performansını ölçmek ve yasal yükümlülükleri yerine getirmek için kullanırız.'],
                    ['Çerezler', 'Tema tercihi, oturum güvenliği, dil seçimi ve anonim analiz için çerezler kullanılabilir. Reklam veya analiz çerezleri izin durumuna göre yönetilir.'],
                    ['Haklarınız', 'Erişim, düzeltme, silme, itiraz ve veri taşınabilirliği talepleri için bizimle iletişime geçebilirsiniz.'],
                ],
            ],
            'terms' => [
                'title' => 'Kullanım Şartları',
                'meta' => 'Visual Design Journey kullanım şartları, kullanıcı içerikleri ve platform kuralları.',
                'eyebrow' => 'Yasal',
                'intro' => 'Visual Design Journey’i kullanarak bu şartları kabul etmiş olursunuz. Platform; tasarım ilhamı, moodboardlar, blog yazıları ve küratörlü referanslar için hazırlanmıştır.',
                'sections' => [
                    ['Platform Kullanımı', 'Siteyi yasal, saygılı ve yaratıcı topluluğa uygun şekilde kullanmalısınız. Spam, kötüye kullanım ve yanıltıcı içerik kaldırılabilir.'],
                    ['Kullanıcı İçeriği', 'Gönderdiğiniz içeriklerin haklarına sahip olduğunuzu veya paylaşmaya yetkili olduğunuzu kabul edersiniz. İnceleme ve moderasyon hakkımız saklıdır.'],
                    ['Fikri Mülkiyet', 'Visual Design Journey arayüzü, marka öğeleri ve editoryal içerikleri izinsiz kopyalanamaz. Kaynak eserlerin hakları sahiplerine aittir.'],
                    ['Değişiklikler', 'Şartları zaman zaman güncelleyebiliriz. Önemli değişiklikler site üzerinde duyurulur.'],
                ],
            ],
            'editorial-policy' => [
                'title' => 'Editoryal Politika',
                'meta' => 'Visual Design Journey içerikleri nasıl seçer, kaynak gösterir ve sponsorlu işleri nasıl ayırır.',
                'eyebrow' => 'Editoryal',
                'intro' => 'Visual Design Journey görsel kültürden öğrenmeyi kolaylaştırmak için çalışır. Bu politika, referansları nasıl seçtiğimizi ve ticari ilişkileri editoryal içerikten nasıl ayırdığımızı açıklar.',
                'sections' => [
                    ['Kürasyon Standartları', 'Referanslar eğitim değeri, görsel açıklık, tarihsel önem veya tasarım pratiğine faydası nedeniyle seçilir.'],
                    ['Kaynak ve Telif', 'Mümkün olduğunda kaynak ve kredi bilgisi ekleriz. Düzeltme veya kaldırma taleplerini hızlıca inceleriz.'],
                    ['Sponsorlu İçerik', 'Sponsorlu yerleşimler editoryal görüşü belirlemez ve kullanıldığında açık şekilde etiketlenir.'],
                    ['Düzeltmeler', 'Kırık bağlantılar, hatalı atıflar ve güncel olmayan bilgiler tespit edildiğinde düzeltilir.'],
                ],
            ],
            'contact' => [
                'title' => 'İletişim',
                'meta' => 'Visual Design Journey ile editoryal, ortaklık, telif ve destek konuları için iletişime geçin.',
                'eyebrow' => 'İletişim',
                'intro' => 'Editoryal sorular, telif talepleri, partnerlikler, hata bildirimleri veya özellik önerileri için bize ulaşabilirsiniz.',
                'sections' => [
                    ['E-posta', 'En hızlı yol: a.ozelbicer@gmail.com adresine yazın. Konu satırına talebinizi kısa ve net ekleyin.'],
                    ['Partnerlik', 'Sponsorlu board, bülten yerleşimi veya tasarım aracı iş birlikleri için kampanya hedefinizi paylaşın.'],
                    ['Telif ve Kredi', 'Bir görselin kaldırılması veya kredi bilgisinin düzeltilmesi gerekiyorsa ilgili URL ile birlikte yazın.'],
                ],
            ],
            'premium' => [
                'title' => 'Premium Yol Haritası',
                'meta' => 'Visual Design Journey premium kürasyon araçları, özel koleksiyonlar ve trend raporları.',
                'eyebrow' => 'Premium',
                'intro' => 'Premium, Visual Design Journey’i ciddi küratörler ve tasarım ekipleri için daha güçlü bir çalışma alanına dönüştürecek.',
                'sections' => [
                    ['Yakında', 'Özel koleksiyonlar, PDF/ZIP moodboard dışa aktarma, portföy sunum modu ve haftalık trend raporları planlanıyor.'],
                    ['Küratör Araçları', 'Kaynak keşfi, kayıtlı aramalar, etiket yönetimi ve küratör analitiği premium akışın merkezinde olacak.'],
                    ['Bekleme Listesi', 'Erken erişim için iletişim sayfasından talep bırakabilirsiniz.'],
                ],
            ],
            'sponsor' => [
                'title' => 'Partnerlikler',
                'meta' => 'Visual Design Journey sponsorlu boardlar, bülten yerleşimleri ve tasarım aracı ortaklıkları.',
                'eyebrow' => 'Partner',
                'intro' => 'Visual Design Journey, rahatsız edici reklamlar yerine tasarım kültürüne uygun, seçilmiş partnerlik formatlarına odaklanır.',
                'sections' => [
                    ['Sponsorlu Board', 'Bir kampanya, araç veya tasarım kaynağı etrafında açıkça etiketlenmiş küratörlü board hazırlanabilir.'],
                    ['Bülten Yerleşimi', 'Haftalık kürasyon e-postasında kısa ve doğal partner tanıtımları yapılabilir.'],
                    ['Editoryal İş Birliği', 'Uzun form rehber, vaka çalışması veya trend raporu formatları değerlendirilebilir.'],
                ],
            ],
        ],
        'de' => [
            'privacy' => [
                'title' => 'Datenschutzerklärung',
                'meta' => 'Wie Visual Design Journey personenbezogene Daten, Cookies und Nutzerrechte behandelt.',
                'eyebrow' => 'Rechtliches',
                'intro' => 'Datenschutz ist kein Feature, sondern ein Grundrecht. Diese Seite erklärt, welche Daten wir erfassen, warum wir sie nutzen und wie du deine Optionen verwaltest.',
                'sections' => [
                    ['Erfasste Informationen', 'Wir können Informationen verarbeiten, die du bei Konto, Newsletter, Kontakt oder Board-Erstellung angibst, sowie begrenzte technische Daten für Sicherheit und Analyse.'],
                    ['Nutzung', 'Daten werden genutzt, um Konten zu betreiben, Einreichungen zu ermöglichen, Spam zu verhindern, Performance zu messen und rechtliche Pflichten zu erfüllen.'],
                    ['Cookies', 'Cookies können für Theme, Sitzungssicherheit, Sprache und anonyme Analyse eingesetzt werden. Analyse- und Werbecookies richten sich nach deiner Einwilligung.'],
                    ['Deine Rechte', 'Du kannst Zugriff, Berichtigung, Löschung, Widerspruch und Datenübertragbarkeit anfragen.'],
                ],
            ],
            'terms' => [
                'title' => 'Nutzungsbedingungen',
                'meta' => 'Nutzungsbedingungen von Visual Design Journey, Nutzerinhalte und Plattformregeln.',
                'eyebrow' => 'Rechtliches',
                'intro' => 'Durch die Nutzung von Visual Design Journey akzeptierst du diese Bedingungen. Die Plattform dient Designinspiration, Moodboards, Blogartikeln und kuratierten Referenzen.',
                'sections' => [
                    ['Plattformnutzung', 'Nutze die Website legal, respektvoll und passend zur kreativen Community. Spam, Missbrauch und irreführende Inhalte können entfernt werden.'],
                    ['Nutzerinhalte', 'Du bestätigst, dass du Rechte an eingereichten Inhalten besitzt oder zur Veröffentlichung berechtigt bist. Moderation bleibt vorbehalten.'],
                    ['Geistiges Eigentum', 'Interface, Marke und redaktionelle Inhalte von Visual Design Journey dürfen nicht ohne Erlaubnis kopiert werden. Rechte an Quellen verbleiben bei ihren Inhabern.'],
                    ['Änderungen', 'Wir können diese Bedingungen aktualisieren. Wesentliche Änderungen werden auf der Website bekanntgegeben.'],
                ],
            ],
            'editorial-policy' => [
                'title' => 'Redaktionelle Richtlinie',
                'meta' => 'Wie Visual Design Journey Inhalte kuratiert, Quellen nennt und Sponsorings trennt.',
                'eyebrow' => 'Redaktion',
                'intro' => 'Visual Design Journey hilft Menschen, von visueller Kultur zu lernen. Diese Richtlinie erklärt unsere Auswahl, Attribution und Trennung von kommerziellen Beziehungen.',
                'sections' => [
                    ['Kurationsstandards', 'Referenzen werden nach Bildungswert, visueller Klarheit, historischer Relevanz oder praktischer Nützlichkeit ausgewählt.'],
                    ['Quellen und Urheberrecht', 'Wo möglich nennen wir Quellen und Credits. Korrektur- oder Entfernungsanfragen prüfen wir zeitnah.'],
                    ['Sponsoring', 'Bezahlte Platzierungen bestimmen keine redaktionellen Aussagen und werden klar gekennzeichnet.'],
                    ['Korrekturen', 'Defekte Links, falsche Attributionen und veraltete Informationen werden korrigiert, sobald sie auffallen.'],
                ],
            ],
            'contact' => [
                'title' => 'Kontakt',
                'meta' => 'Kontakt zu Visual Design Journey für Redaktion, Partnerschaften, Urheberrecht und Support.',
                'eyebrow' => 'Kontakt',
                'intro' => 'Für redaktionelle Fragen, Urheberrechtsanliegen, Partnerschaften, Fehlerberichte oder Feature-Ideen kannst du uns schreiben.',
                'sections' => [
                    ['E-Mail', 'Der schnellste Weg: schreibe an a.ozelbicer@gmail.com und fasse dein Anliegen im Betreff kurz zusammen.'],
                    ['Partnerschaften', 'Für gesponserte Boards, Newsletter-Platzierungen oder Design-Tool-Kooperationen teile bitte dein Kampagnenziel mit.'],
                    ['Copyright und Credits', 'Wenn ein Bild entfernt oder ein Credit korrigiert werden soll, sende bitte die betroffene URL mit.'],
                ],
            ],
            'premium' => [
                'title' => 'Premium-Roadmap',
                'meta' => 'Premium-Kurationstools, private Sammlungen und Trendberichte für Visual Design Journey.',
                'eyebrow' => 'Premium',
                'intro' => 'Premium wird Visual Design Journey zu einem stärkeren Arbeitsbereich für ernsthafte Kuratoren und Designteams machen.',
                'sections' => [
                    ['Demnächst', 'Private Sammlungen, PDF/ZIP-Moodboard-Export, Präsentationsmodus und wöchentliche Trendberichte sind geplant.'],
                    ['Kurator-Tools', 'Quellensuche, gespeicherte Suchen, Tag-Management und Kurator-Analytics stehen im Mittelpunkt.'],
                    ['Warteliste', 'Für frühen Zugang kannst du über die Kontaktseite eine Anfrage senden.'],
                ],
            ],
            'sponsor' => [
                'title' => 'Partnerschaften',
                'meta' => 'Gesponserte Boards, Newsletter-Platzierungen und Design-Tool-Partnerschaften mit Visual Design Journey.',
                'eyebrow' => 'Partner',
                'intro' => 'Visual Design Journey setzt auf ausgewählte Partnerschaften, die zur Designkultur passen, statt auf störende Werbung.',
                'sections' => [
                    ['Gesponsertes Board', 'Ein klar gekennzeichnetes kuratiertes Board kann rund um eine Kampagne, ein Tool oder eine Designressource entstehen.'],
                    ['Newsletter-Platzierung', 'Kurze, native Partnerhinweise können in der wöchentlichen Kurationsmail erscheinen.'],
                    ['Redaktionelle Kooperation', 'Langform-Guides, Case Studies oder Trendberichte können gemeinsam entwickelt werden.'],
                ],
            ],
        ],
    ];

    if (empty($copy[$lang][$page])) return false;
    $data = $copy[$lang][$page];
    $pageTitle = $data['title'] . ' — Visual Design Journey';
    $metaDesc = $data['meta'];
    $activeNav = $page === 'sponsor' ? 'sponsor' : ($page === 'premium' ? 'premium' : '');
    $canonicalUrl = publicUrl(vdj_localized_url('/' . $page));
    require __DIR__ . '/header.php';
    ?>
    <main class="page-wrap" id="main-content">
      <section class="container" style="max-width:920px;padding:64px 0 84px;">
        <p class="t-label text-muted" style="text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px;"><?= e($data['eyebrow']) ?></p>
        <h1 class="t-display" style="margin-bottom:18px;"><?= e($data['title']) ?></h1>
        <p class="t-body-lg text-muted" style="line-height:1.75;margin-bottom:18px;max-width:760px;"><?= e($data['intro']) ?></p>
        <p class="text-muted" style="font-size:13px;margin-bottom:32px;"><i class="fa-regular fa-calendar"></i> <?= e(__('static.updated', 'Last updated')) ?>: <?= e(vdjLocalizedDate('2026-05-01')) ?></p>
        <div style="display:grid;gap:18px;">
          <?php foreach ($data['sections'] as $section): ?>
            <section class="admin-card" style="padding:24px;">
              <h2 style="font-size:20px;margin-bottom:10px;"><?= e($section[0]) ?></h2>
              <p style="color:var(--muted);line-height:1.7;margin:0;"><?= e($section[1]) ?></p>
            </section>
          <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:28px;">
          <a href="<?= e(vdj_localized_url('/contact')) ?>" class="btn btn--primary"><?= e(__('static.contact_cta', 'Contact us')) ?></a>
          <a href="<?= e(vdj_localized_url('/')) ?>" class="btn btn--secondary"><?= e(__('nav.explore', 'Explore')) ?></a>
        </div>
      </section>
    </main>
    <?php
    require __DIR__ . '/footer.php';
    return true;
}

function blogImageUrl(?string $url): string {
    $url = trim((string)$url);
    if ($url === '') return '';

    if (preg_match('#^https?://#i', $url)) {
        $parts = parse_url($url);
        $path = $parts['path'] ?? '';
        if ($path !== '' && preg_match('#/(wp-content/uploads/.+)$#i', $path, $m)) {
            return publicUrl('/uploads/' . preg_replace('#^wp-content/uploads/#i', '', $m[1]));
        }
        if ($path !== '' && preg_match('#/(uploads/.+)$#i', $path, $m)) {
            return publicUrl('/' . $m[1]);
        }
        return $url;
    }

    if (str_starts_with($url, 'wp-content/') || str_starts_with($url, '/wp-content/')) {
        $path = preg_replace('#^/?wp-content/uploads/#i', '', $url);
        return publicUrl('/uploads/' . ltrim($path, '/'));
    }

    if (str_starts_with($url, 'uploads/') || str_starts_with($url, '/uploads/')) {
        return publicUrl('/' . ltrim($url, '/'));
    }

    return imageUrl($url);
}

function localMediaPath(?string $url): ?string {
    $url = trim((string)$url);
    if ($url === '') return null;

    $root = dirname(__DIR__);
    if (preg_match('#^https?://#i', $url)) {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $siteHost = strtolower(parse_url(publicUrl(), PHP_URL_HOST) ?: parse_url(SITE_URL, PHP_URL_HOST) ?: '');
        $path = $parts['path'] ?? '';
        if ($host !== '' && $siteHost !== '' && $host !== $siteHost && !str_ends_with($host, 'visualdesignjourney.com')) {
            return null;
        }
        $url = $path;
    }

    $url = preg_replace('#^/?wp-content/uploads/#i', 'uploads/', $url);
    if (!str_starts_with($url, 'uploads/') && !str_starts_with($url, '/uploads/')) {
        $url = 'uploads/' . ltrim($url, '/');
    }

    $rel = ltrim($url, '/');
    if ($rel === '' || str_contains($rel, '..')) return null;
    $candidate = $root . '/' . $rel;
    return is_file($candidate) ? $candidate : null;
}

function thumbnailUrl(?string $url, int $width = 480, int $height = 270, string $fit = 'cover'): string {
    $url = trim((string)$url);
    if ($url === '') return '';
    $width = max(80, min(1600, $width));
    $height = max(80, min(1600, $height));
    $fit = $fit === 'contain' ? 'contain' : 'cover';

    if (!localMediaPath($url) || !extension_loaded('gd')) {
        return blogImageUrl($url);
    }

    return publicUrl('/api/thumb.php?src=' . rawurlencode($url) . '&w=' . $width . '&h=' . $height . '&fit=' . $fit);
}

function blogThumbnailUrl(?string $url, int $width = 480, int $height = 270): string {
    return thumbnailUrl($url, $width, $height, 'cover');
}

function blogContentHtml(string $html): string {
    if ($html === '') return '';

    $html = preg_replace_callback(
        '#https?://[^"\'\s)\\\\]+/(wp-content/uploads/[^"\'\s)\\\\]+)#i',
        fn($m) => publicUrl('/uploads/' . preg_replace('#^wp-content/uploads/#i', '', $m[1])),
        $html
    );

    $html = preg_replace_callback(
        '#(?<![/:])\bwp-content/uploads/([^"\'\s)\\\\]+)#i',
        fn($m) => publicUrl('/uploads/' . $m[1]),
        $html
    );

    return $html;
}

function adsenseEnabled(): bool {
    return defined('ADSENSE_CLIENT') && preg_match('/^ca-pub-\d+$/', ADSENSE_CLIENT);
}

function renderAdSlot(string $name, string $format = 'auto'): void {
    if (!adsenseEnabled()) return;
    $slotId = 'vdj-ad-' . preg_replace('/[^a-z0-9_-]/', '-', strtolower($name));
    ?>
    <aside class="ad-slot ad-slot--<?= e($format) ?>" aria-label="Advertisement">
      <span class="ad-slot__label">Advertisement</span>
      <ins class="adsbygoogle"
           style="display:block"
           data-ad-client="<?= e(ADSENSE_CLIENT) ?>"
           data-ad-format="<?= e($format) ?>"
           data-full-width-responsive="true"></ins>
      <script>
        (adsbygoogle = window.adsbygoogle || []).push({});
      </script>
    </aside>
    <?php
}

function vdj_is_local_or_admin_request(): bool {
    $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return $host === ''
        || str_contains($host, 'localhost')
        || str_starts_with($host, '127.0.0.1')
        || str_starts_with($host, '[::1]')
        || str_starts_with($path, '/admin');
}

function vdj_json_ld_encode(array $payload, string $label = 'JSON-LD'): string {
    try {
        $json = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || empty($decoded['@context']) || (empty($decoded['@type']) && empty($decoded['@graph']))) {
            throw new RuntimeException('Missing required @context and @type/@graph');
        }
        return $json;
    } catch (Throwable $e) {
        error_log($label . ' encode/validation failed: ' . $e->getMessage());
        return '';
    }
}

function vdj_json_ld_script(array $payload, string $label = 'JSON-LD'): string {
    $json = vdj_json_ld_encode($payload, $label);
    if ($json === '') {
        return vdj_is_local_or_admin_request()
            ? '<!-- ' . e($label) . ' validation failed; see PHP error log. -->' . "\n"
            : '';
    }
    return '<script type="application/ld+json">' . $json . '</script>' . "\n";
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ── CSRF ─────────────────────────────────────────────────────────────────── */
function csrfToken(): string {
    session_start_if_not_started();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    session_start_if_not_started();
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}
