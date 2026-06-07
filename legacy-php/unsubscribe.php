<?php
/**
 * unsubscribe.php — Newsletter unsubscribe (one-click via token)
 * Visual Design Journey · vibe.visualdesignjourney.com
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

session_start_if_not_started();

$pageTitle = 'Unsubscribe — Visual Design Journey';
$activeNav = '';
$metaDesc  = 'Unsubscribe from the Visual Design Journey newsletter.';

$token = trim($_GET['token'] ?? '');

// Determine state: no_token | invalid | already | success
$state = 'no_token';

if ($token !== '') {
    $db = getDB();
    $stmt = $db->prepare('SELECT id, email, status FROM newsletter_subscribers WHERE unsubscribe_token = ? LIMIT 1');
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $state = 'invalid';
    } elseif ($row['status'] === 'unsubscribed') {
        $state = 'already';
    } else {
        // Mark as unsubscribed
        $upd = $db->prepare('UPDATE newsletter_subscribers SET status = ? WHERE id = ?');
        $upd->execute(['unsubscribed', $row['id']]);
        $state = 'success';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
.unsub-wrap {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px 16px;
}
.unsub-card {
    background: var(--surface, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: var(--r-lg, 16px);
    padding: 48px 40px;
    max-width: 480px;
    width: 100%;
    text-align: center;
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
}
.unsub-icon {
    font-size: 40px;
    margin-bottom: 20px;
    line-height: 1;
}
.unsub-card h1 {
    font-family: var(--font-display, inherit);
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 12px;
    color: var(--text, #111);
}
.unsub-card p {
    color: var(--muted, #6b7280);
    margin-bottom: 28px;
    line-height: 1.6;
}
.unsub-card .btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
@media (max-width: 520px) {
    .unsub-card { padding: 32px 20px; }
}
</style>

<div class="unsub-wrap">
    <div class="unsub-card">
        <?php if ($state === 'success'): ?>
            <div class="unsub-icon">✓</div>
            <h1>You've been unsubscribed</h1>
            <p>You won't receive any more newsletter emails from Visual Design Journey. We're sorry to see you go.</p>
            <a href="<?= publicUrl('/') ?>" class="btn btn--secondary">Back to home</a>

        <?php elseif ($state === 'already'): ?>
            <div class="unsub-icon">👍</div>
            <h1>Already unsubscribed</h1>
            <p>This email address is already removed from our newsletter. No further action is needed.</p>
            <a href="<?= publicUrl('/') ?>" class="btn btn--secondary">Back to home</a>

        <?php elseif ($state === 'invalid'): ?>
            <div class="unsub-icon">⚠</div>
            <h1>Invalid link</h1>
            <p>This unsubscribe link is invalid or has already been used. If you're still receiving emails you didn't sign up for, please <a href="<?= publicUrl('/contact') ?>">contact us</a>.</p>
            <a href="<?= publicUrl('/') ?>" class="btn btn--secondary">Back to home</a>

        <?php else: ?>
            <div class="unsub-icon">✉</div>
            <h1>Missing unsubscribe token</h1>
            <p>No unsubscribe token was provided. Please use the link from your newsletter email.</p>
            <a href="<?= publicUrl('/') ?>" class="btn btn--secondary">Back to home</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
