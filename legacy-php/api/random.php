<?php
/**
 * api/random.php — Redirect to a random published board (GET)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start_if_not_started();

$db   = getDB();
$stmt = $db->prepare('SELECT id FROM boards WHERE is_draft = 0 ORDER BY RAND() LIMIT 1');
$stmt->execute();
$id = $stmt->fetchColumn();

if ($id) {
    header('Location: /board/' . (int)$id);
} else {
    header('Location: /');
}
exit;
