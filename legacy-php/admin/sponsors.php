<?php
require_once __DIR__ . '/_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? 'status';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'note' && $id > 0) {
        $note = substr(strip_tags($_POST['note'] ?? ''), 0, 500);
        try {
            $db->prepare('UPDATE sponsor_leads SET notes = ? WHERE id = ?')->execute([$note, $id]);
        } catch (Throwable $e) { /* column may not exist yet */ }
        header('Location: /admin/sponsors.php');
        exit;
    }

    $status = $_POST['status'] ?? 'new';
    if ($id > 0 && in_array($status, ['new','contacted','won','lost'], true)) {
        try {
            $stmt = $db->prepare('UPDATE sponsor_leads SET status = ? WHERE id = ?');
            $stmt->execute([$status, $id]);
        } catch (Throwable $e) {}
    }
    header('Location: /admin/sponsors.php');
    exit;
}

$currentPage = 'sponsors';
$leads = [];
$ready = false;
try {
    $db->query('SELECT 1 FROM sponsor_leads LIMIT 1');
    $ready = true;
    $leads = $db->query('SELECT * FROM sponsor_leads ORDER BY created_at DESC LIMIT 100')->fetchAll();
} catch (Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sponsors — Admin — Visual Design Journey</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/admin/admin.css">
</head>
<body>
<?php require_once __DIR__ . '/_sidebar.php'; ?>
<main class="admin-main">
    <div class="admin-topbar">
        <div class="admin-header">
            <h1>Sponsors</h1>
            <p>Partnership inquiries and monetization leads.</p>
        </div>
        <button class="hamburger" id="hamburger" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <?php if (!$ready): ?>
        <div class="admin-card">
            <div class="empty-state">
                <i class="fa-solid fa-database"></i>
                <p>Run <code>schema-update.sql</code> to enable sponsor lead storage.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="admin-card">
            <div class="admin-card__header">
                <h2><i class="fa-solid fa-handshake"></i> Recent inquiries</h2>
                <a href="/sponsor.php" target="_blank">View page</a>
            </div>
            <?php if (!$leads): ?>
                <div class="empty-state"><i class="fa-regular fa-envelope"></i><p>No sponsor inquiries yet.</p></div>
            <?php else: ?>
            <table class="admin-table">
                <style>
                    .notes-form textarea {
                        width: 100%;
                        min-width: 180px;
                        max-width: 260px;
                        height: 60px;
                        font-size: 12px;
                        border: 1px solid #ddd;
                        border-radius: 6px;
                        padding: 6px 8px;
                        resize: vertical;
                        font-family: inherit;
                        color: #333;
                    }
                    .notes-form button {
                        margin-top: 4px;
                        font-size: 11px;
                        padding: 3px 10px;
                        background: #111;
                        color: #fff;
                        border: none;
                        border-radius: 5px;
                        cursor: pointer;
                    }
                    .notes-form button:hover { background: #333; }
                </style>
                <thead>
                    <tr>
                        <th>Contact</th>
                        <th>Company</th>
                        <th>Budget</th>
                        <th>Message</th>
                        <th>Notes / Invoice</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): ?>
                    <tr>
                        <td>
                            <strong><?= e($lead['name']) ?></strong><br>
                            <a href="mailto:<?= e($lead['email']) ?>"><?= e($lead['email']) ?></a>
                        </td>
                        <td><?= e($lead['company'] ?: '-') ?></td>
                        <td><?= e($lead['budget'] ?: 'manual') ?></td>
                        <td style="max-width:280px;white-space:normal;"><?= e($lead['message']) ?></td>
                        <td>
                            <form method="post" class="notes-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="action" value="note">
                                <input type="hidden" name="id" value="<?= (int)$lead['id'] ?>">
                                <textarea name="note" placeholder="Invoice notes…"><?= e($lead['notes'] ?? '') ?></textarea><br>
                                <button type="submit"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                            </form>
                        </td>
                        <td>
                            <form method="post" style="display:flex;gap:6px;">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="action" value="status">
                                <input type="hidden" name="id" value="<?= (int)$lead['id'] ?>">
                                <select name="status" class="admin-select" onchange="this.form.submit()">
                                    <?php foreach (['new','contacted','won','lost'] as $st): ?>
                                    <option value="<?= $st ?>" <?= $lead['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td><?= e(date('M j, Y', strtotime($lead['created_at']))) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
