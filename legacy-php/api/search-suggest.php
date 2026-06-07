<?php
/**
 * api/search-suggest.php — Instant search suggestions (GET)
 * GET: q (string, min 2 chars)
 * Returns JSON: { boards: [...], users: [...] }
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

session_start_if_not_started();

$q = trim($_GET['q'] ?? '');

if (mb_strlen($q) < 2) {
    echo json_encode(['boards' => [], 'users' => []]);
    exit;
}

$db      = getDB();
$pattern = '%' . $q . '%';

/* ── Boards ───────────────────────────────────────────────────── */
$bStmt = $db->prepare(
    'SELECT b.id, b.title, b.cover_image, u.username,
            (SELECT COUNT(*) FROM board_likes bl WHERE bl.board_id = b.id) AS like_count
     FROM boards b
     JOIN users u ON u.id = b.user_id
     WHERE b.is_draft = 0 AND b.title LIKE ?
     ORDER BY like_count DESC
     LIMIT 6'
);
$bStmt->execute([$pattern]);
$boardRows = $bStmt->fetchAll(PDO::FETCH_ASSOC);

$boards = [];
foreach ($boardRows as $row) {
    $boards[] = [
        'id'       => (int)$row['id'],
        'title'    => $row['title'],
        'username' => $row['username'],
        'cover'    => $row['cover_image'] ? imageUrl($row['cover_image']) : null,
        'url'      => '/board/' . (int)$row['id'],
    ];
}

/* ── Users ────────────────────────────────────────────────────── */
$uStmt = $db->prepare(
    'SELECT id, username, avatar
     FROM users
     WHERE username LIKE ?
     LIMIT 4'
);
$uStmt->execute([$pattern]);
$userRows = $uStmt->fetchAll(PDO::FETCH_ASSOC);

$users = [];
foreach ($userRows as $row) {
    $users[] = [
        'id'       => (int)$row['id'],
        'username' => $row['username'],
        'avatar'   => $row['avatar'] ? imageUrl($row['avatar']) : null,
        'url'      => '/profile.php?u=' . urlencode($row['username']),
    ];
}

echo json_encode(['boards' => $boards, 'users' => $users]);
