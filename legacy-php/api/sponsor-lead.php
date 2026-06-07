<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start_if_not_started();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'invalid_csrf']);
    exit;
}

$name    = trim($_POST['name'] ?? '');
$email   = normalizeEmail($_POST['email'] ?? '');
$company = trim($_POST['company'] ?? '');
$budget  = trim($_POST['budget'] ?? 'manual');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Please enter a valid name, email, and message.']);
    exit;
}
if (isBotUserAgent() || !rateLimitCheck('sponsor-lead-ip', 4, 60 * 60) || !rateLimitCheckKey('sponsor-lead-email', $email, 2, 24 * 60 * 60)) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Too many attempts. Please try again later.']);
    exit;
}
if (!isAllowedEmail($email) || preg_match('/https?:\/\/|casino|crypto|forex|viagra|porn|escort|telegram/i', $name . ' ' . $company . ' ' . $message)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Please enter a valid inquiry.']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO sponsor_leads (name, email, company, budget, message, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        mb_substr($name, 0, 120),
        mb_substr($email, 0, 190),
        mb_substr($company, 0, 160),
        mb_substr($budget, 0, 40),
        mb_substr($message, 0, 1200),
    ]);
} catch (Throwable $e) {
    error_log('Sponsor lead failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save your inquiry right now.']);
    exit;
}

echo json_encode(['ok' => true, 'message' => 'Thanks. Your partnership inquiry has been saved.']);
