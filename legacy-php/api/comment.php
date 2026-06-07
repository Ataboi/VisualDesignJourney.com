<?php
/**
 * api/comment.php — Add or delete board comments (AJAX endpoint)
 * POST: board_id, content (add) | comment_id (delete), action ("add"|"delete"), csrf_token
 * Returns JSON
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

session_start_if_not_started();

// Must be POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

// Must be logged in
$userId = currentUserId();
if (!$userId) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'login_required']);
    exit;
}

// CSRF
$_csrfToken = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_csrfToken)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'invalid_csrf']);
    exit;
}

if (!rateLimitCheck('comment-user-' . (int)$userId, 12, 10 * 60)) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'rate_limited']);
    exit;
}

$action = $_POST['action'] ?? 'add';
$db     = getDB();

/* ── Helper: human-readable time ago ──────────────────────────── */
function time_ago(string $datetime): string
{
    $now  = new DateTimeImmutable('now');
    $then = new DateTimeImmutable($datetime);
    $diff = $now->getTimestamp() - $then->getTimestamp();

    if ($diff < 60)        return 'just now';
    if ($diff < 3600)      return floor($diff / 60)   . ' minute'  . (floor($diff / 60)   === 1.0 ? '' : 's') . ' ago';
    if ($diff < 86400)     return floor($diff / 3600)  . ' hour'    . (floor($diff / 3600)  === 1.0 ? '' : 's') . ' ago';
    if ($diff < 604800)    return floor($diff / 86400) . ' day'     . (floor($diff / 86400) === 1.0 ? '' : 's') . ' ago';
    if ($diff < 2592000)   return floor($diff / 604800). ' week'    . (floor($diff / 604800)=== 1.0 ? '' : 's') . ' ago';
    if ($diff < 31536000)  return floor($diff / 2592000).  ' month' . (floor($diff / 2592000)=== 1.0 ? '' : 's') . ' ago';
    return floor($diff / 31536000) . ' year' . (floor($diff / 31536000) === 1.0 ? '' : 's') . ' ago';
}

/* ── ADD ──────────────────────────────────────────────────────── */
if ($action === 'add') {
    $boardId = (int)($_POST['board_id'] ?? 0);
    $content = trim(strip_tags($_POST['content'] ?? ''));

    if ($boardId <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid_board_id']);
        exit;
    }

    if ($content === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'content_empty']);
        exit;
    }

    // Truncate to 500 chars
    $content = mb_substr($content, 0, 500);

    // Verify board exists and is published
    $chk = $db->prepare('SELECT id FROM boards WHERE id = ? AND is_draft = 0 LIMIT 1');
    $chk->execute([$boardId]);
    if (!$chk->fetch()) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'board_not_found']);
        exit;
    }

    // Insert comment
    $ins = $db->prepare(
        'INSERT INTO board_comments (board_id, user_id, content) VALUES (?, ?, ?)'
    );
    $ins->execute([$boardId, $userId, $content]);
    $commentId = (int)$db->lastInsertId();

    // Fetch new comment with user info
    $sel = $db->prepare(
        'SELECT c.id, c.content, c.created_at, u.username, u.avatar
         FROM board_comments c
         JOIN users u ON u.id = c.user_id
         WHERE c.id = ? LIMIT 1'
    );
    $sel->execute([$commentId]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok'      => true,
        'comment' => [
            'id'         => (int)$row['id'],
            'username'   => $row['username'],
            'avatar'     => $row['avatar'] ? imageUrl($row['avatar']) : null,
            'content'    => $row['content'],
            'created_at' => $row['created_at'],
            'time_ago'   => time_ago($row['created_at']),
        ],
    ]);
    exit;
}

/* ── DELETE ───────────────────────────────────────────────────── */
if ($action === 'delete') {
    $commentId = (int)($_POST['comment_id'] ?? 0);

    if ($commentId <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid_comment_id']);
        exit;
    }

    // Check ownership
    $own = $db->prepare('SELECT user_id FROM board_comments WHERE id = ? LIMIT 1');
    $own->execute([$commentId]);
    $row = $own->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'comment_not_found']);
        exit;
    }

    if ((int)$row['user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'forbidden']);
        exit;
    }

    $del = $db->prepare('DELETE FROM board_comments WHERE id = ? AND user_id = ?');
    $del->execute([$commentId, $userId]);

    echo json_encode(['ok' => true]);
    exit;
}

// Unknown action
http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'unknown_action']);
