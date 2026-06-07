<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
session_start_if_not_started();
vdj_bootstrap_language();
$db = getDB();
if (!isset($_SESSION['user_id'])) { header('Location: /login.php?next=/admin/'); exit; }
$adminUser = getUserById($db, $_SESSION['user_id']);
if (empty($adminUser['is_admin'])) { http_response_code(403); include __DIR__ . '/../404.php'; exit; }
