<?php
/**
 * api/save.php — Toggle board save/unsave (AJAX endpoint)
 * POST: board_id (int), action ("save"|"unsave"), csrf_token
 * Returns JSON: { ok, saved, count }
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

$boardId = (int)($_POST['board_id'] ?? 0);
$action  = $_POST['action'] ?? '';

if ($boardId <= 0 || !in_array($action, ['save', 'unsave'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_params']);
    exit;
}

$db = getDB();

// Verify board exists and is published
$chk = $db->prepare('SELECT id FROM boards WHERE id = ? AND is_draft = 0 LIMIT 1');
$chk->execute([$boardId]);
if (!$chk->fetch()) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'board_not_found']);
    exit;
}

if ($action === 'save') {
    $ins = $db->prepare('INSERT IGNORE INTO board_saves (user_id, board_id) VALUES (?, ?)');
    $ins->execute([$userId, $boardId]);
    $saved = true;
} else {
    $del = $db->prepare('DELETE FROM board_saves WHERE user_id = ? AND board_id = ?');
    $del->execute([$userId, $boardId]);
    $saved = false;
}

// Update denormalised save_count on the board when the column exists.
if (dbColumnExists($db, 'boards', 'save_count')) {
    $upd = $db->prepare(
        'UPDATE boards
         SET save_count = (SELECT COUNT(*) FROM board_saves WHERE board_id = ?)
         WHERE id = ?'
    );
    $upd->execute([$boardId, $boardId]);

    $cnt = $db->prepare('SELECT save_count FROM boards WHERE id = ? LIMIT 1');
    $cnt->execute([$boardId]);
    $count = (int)($cnt->fetchColumn() ?: 0);
} else {
    $cnt = $db->prepare('SELECT COUNT(*) FROM board_saves WHERE board_id = ?');
    $cnt->execute([$boardId]);
    $count = (int)$cnt->fetchColumn();
}

echo json_encode(['ok' => true, 'saved' => $saved, 'count' => $count]);
