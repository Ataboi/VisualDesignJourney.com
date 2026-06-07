<?php
// api/newsletter.php — Newsletter signup (POST: email, name optional)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');
session_start_if_not_started();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['ok'=>false,'error'=>'method_not_allowed']); exit;
}

$email = normalizeEmail($_POST['email'] ?? '');
$name  = trim($_POST['name'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400); echo json_encode(['ok'=>false,'error'=>'invalid_email']); exit;
}
if (isBotUserAgent() || !rateLimitCheck('newsletter-ip', 5, 60 * 60) || !rateLimitCheckKey('newsletter-email', $email, 2, 24 * 60 * 60)) {
    http_response_code(429); echo json_encode(['ok'=>false,'error'=>'too_many_attempts']); exit;
}
if (!isAllowedEmail($email)) {
    http_response_code(400); echo json_encode(['ok'=>false,'error'=>'invalid_email_domain']); exit;
}
if ($name !== '' && preg_match('/https?:\/\/|casino|crypto|forex|viagra|porn|escort|telegram/i', $name)) {
    http_response_code(400); echo json_encode(['ok'=>false,'error'=>'invalid_name']); exit;
}

$token = bin2hex(random_bytes(32));

$db = getDB();
try {
    $stmt = $db->prepare('INSERT INTO newsletter_subscribers (email, name, unsubscribe_token) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE unsubscribe_token = VALUES(unsubscribe_token), status="active", name=IF(?="",name,?)');
    $stmt->execute([$email, $name, $token, $name, $name]);
    echo json_encode(['ok'=>true,'message'=>'Subscribed successfully!','token'=>$token]);
} catch (PDOException $e) {
    http_response_code(500); echo json_encode(['ok'=>false,'error'=>'db_error']);
}
