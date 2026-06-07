<?php
/**
 * api/like.php — Toggle board like (AJAX endpoint)
 * POST: board_id, action (like|unlike), csrf_token
 * Returns JSON: { ok, liked, count }
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

session_start_if_not_started();

$userId = currentUserId();
if (!$userId) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Login required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// CSRF check
$token = $_POST['csrf_token'] ?? '';
if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$boardId = (int)($_POST['board_id'] ?? 0);
$action  = $_POST['action'] ?? '';

if ($boardId <= 0 || !in_array($action, ['like', 'unlike'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid parameters']);
    exit;
}

$db = getDB();

// Verify board exists
$s = $db->prepare('SELECT id, user_id FROM boards WHERE id = ? AND is_draft = 0');
$s->execute([$boardId]);
$board = $s->fetch(PDO::FETCH_ASSOC);
if (!$board) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Board not found']);
    exit;
}

if ($action === 'like') {
    $ins = $db->prepare('INSERT IGNORE INTO board_likes (board_id, user_id) VALUES (?, ?)');
    $ins->execute([$boardId, $userId]);

    // Notify board owner (skip self-like)
    if ((int)$board['user_id'] !== $userId && $ins->rowCount() > 0) {
        createNotification($db, (int)$board['user_id'], $userId, 'like', $boardId);
    }
    $liked = true;
} else {
    $del = $db->prepare('DELETE FROM board_likes WHERE board_id = ? AND user_id = ?');
    $del->execute([$boardId, $userId]);
    $liked = false;
}

$count = getLikeCount($db, $boardId);

echo json_encode(['ok' => true, 'liked' => $liked, 'count' => $count]);
