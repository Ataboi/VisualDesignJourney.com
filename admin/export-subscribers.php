<?php
// FILE: admin/export-subscribers.php
require_once __DIR__ . '/_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/');
    exit;
}

verifyCsrf();

$status = $_POST['status'] ?? 'all';
if (!in_array($status, ['active', 'unsubscribed', 'all'], true)) {
    $status = 'all';
}

try {
    if ($status === 'all') {
        $stmt = $db->query(
            'SELECT email, name, status, created_at FROM newsletter_subscribers ORDER BY created_at DESC'
        );
    } else {
        $stmt = $db->prepare(
            'SELECT email, name, status, created_at FROM newsletter_subscribers WHERE status = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$status]);
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $ref = $_SERVER['HTTP_REFERER'] ?? '/admin/';
    $sep = str_contains($ref, '?') ? '&' : '?';
    header('Location: ' . $ref . $sep . 'error=export_failed');
    exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="subscribers-' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-cache, no-store, must-revalidate');

$out = fopen('php://output', 'w');

// UTF-8 BOM so Excel opens it correctly
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, ['Email', 'Name', 'Status', 'Subscribed At']);

foreach ($rows as $row) {
    fputcsv($out, [
        $row['email'],
        $row['name'] ?? '',
        $row['status'],
        $row['created_at'],
    ]);
}

fclose($out);
exit;
