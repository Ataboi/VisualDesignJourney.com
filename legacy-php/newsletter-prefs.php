<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
session_start_if_not_started();

$pageTitle = 'Email Preferences — Visual Design Journey';
$activeNav = '';
$metaDesc  = 'Manage your newsletter preferences for Visual Design Journey.';
$token = trim($_GET['token'] ?? '');
$subscriber = null;
$message = '';
$error = '';

if ($token !== '') {
    try {
        $stmt = $db->prepare('SELECT * FROM newsletter_subscribers WHERE unsubscribe_token = ? LIMIT 1');
        $stmt->execute([$token]);
        $subscriber = $stmt->fetch() ?: null;
    } catch (Throwable $e) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $subscriber) {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'resubscribe') {
            $db->prepare("UPDATE newsletter_subscribers SET status='active' WHERE id=?")->execute([$subscriber['id']]);
            $message = 'You\'re subscribed again. Welcome back!';
            $subscriber['status'] = 'active';
        } elseif ($action === 'unsubscribe') {
            $db->prepare("UPDATE newsletter_subscribers SET status='unsubscribed' WHERE id=?")->execute([$subscriber['id']]);
            $message = 'You\'ve been unsubscribed. Sorry to see you go.';
            $subscriber['status'] = 'unsubscribed';
        }
    } catch (Throwable $e) {
        $error = 'Something went wrong. Please try again.';
    }
}

// Mask email: j***@gmail.com
function maskEmail(string $email): string {
    $parts = explode('@', $email, 2);
    if (count($parts) !== 2) return '***';
    $local  = $parts[0];
    $domain = $parts[1];
    $show   = substr($local, 0, 1);
    return $show . str_repeat('*', max(2, strlen($local) - 1)) . '@' . $domain;
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
.prefs-wrap {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48px 16px;
}
.prefs-card {
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 14px;
    box-shadow: 0 4px 24px rgba(0,0,0,.06);
    max-width: 480px;
    width: 100%;
    padding: 40px 36px 32px;
    text-align: center;
}
.prefs-card .prefs-icon {
    font-size: 36px;
    color: #7c6af7;
    margin-bottom: 16px;
}
.prefs-card h1 {
    font-family: 'Manrope', sans-serif;
    font-size: 22px;
    font-weight: 700;
    color: #111;
    margin: 0 0 8px;
}
.prefs-card .prefs-subtitle {
    font-size: 14px;
    color: #888;
    margin: 0 0 28px;
    line-height: 1.5;
}
.prefs-email-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: #f7f7f7;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 15px;
    font-weight: 600;
    color: #333;
}
.prefs-email-row i { color: #bbb; }
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 20px;
    padding: 4px 14px;
    margin-bottom: 28px;
}
.status-badge.active   { background: #e6f9ee; color: #1a7a3c; }
.status-badge.inactive { background: #fef2f2; color: #b91c1c; }
.prefs-message {
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 20px;
    font-weight: 500;
}
.prefs-message.success { background: #e6f9ee; color: #1a7a3c; }
.prefs-message.error   { background: #fef2f2; color: #b91c1c; }
.prefs-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    border: none;
    border-radius: 9px;
    padding: 13px 20px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity .15s, background .15s;
    font-family: inherit;
    margin-bottom: 10px;
}
.prefs-btn:hover { opacity: .88; }
.prefs-btn.unsub  { background: #fef2f2; color: #b91c1c; border: 1.5px solid #fca5a5; }
.prefs-btn.resub  { background: #111; color: #fff; }
.prefs-back {
    display: inline-block;
    margin-top: 20px;
    font-size: 13px;
    color: #aaa;
    text-decoration: none;
    transition: color .12s;
}
.prefs-back:hover { color: #555; }
.prefs-invalid {
    text-align: center;
    padding: 16px 0;
}
.prefs-invalid .invalid-icon {
    font-size: 40px;
    color: #ddd;
    margin-bottom: 16px;
}
.prefs-invalid h2 {
    font-size: 18px;
    font-weight: 700;
    color: #555;
    margin: 0 0 8px;
}
.prefs-invalid p {
    font-size: 14px;
    color: #aaa;
    margin: 0 0 24px;
}
</style>

<div class="prefs-wrap">
    <div class="prefs-card">
        <?php if ($token === '' || $subscriber === null): ?>
            <!-- Invalid / missing token -->
            <div class="prefs-invalid">
                <div class="invalid-icon"><i class="fa-solid fa-link-slash"></i></div>
                <h2>Invalid link</h2>
                <p>This preference link is invalid or has expired. Please use the link from your email.</p>
                <a href="/" class="prefs-back"><i class="fa-solid fa-arrow-left"></i> Back to home</a>
            </div>
        <?php else: ?>
            <div class="prefs-icon"><i class="fa-solid fa-envelope-circle-check"></i></div>
            <h1>Email Preferences</h1>
            <p class="prefs-subtitle">Manage how you receive newsletters from Visual Design Journey.</p>

            <div class="prefs-email-row">
                <i class="fa-regular fa-envelope"></i>
                <?= e(maskEmail($subscriber['email'])) ?>
            </div>

            <?php if ($subscriber['status'] === 'active'): ?>
                <span class="status-badge active"><i class="fa-solid fa-circle-check"></i> Active — you're subscribed</span>
            <?php else: ?>
                <span class="status-badge inactive"><i class="fa-solid fa-circle-xmark"></i> Unsubscribed</span>
            <?php endif; ?>

            <?php if ($message !== ''): ?>
                <div class="prefs-message success"><i class="fa-solid fa-check"></i> <?= e($message) ?></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="prefs-message error"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($subscriber['status'] === 'active'): ?>
                <form method="post">
                    <input type="hidden" name="action" value="unsubscribe">
                    <button type="submit" class="prefs-btn unsub">
                        <i class="fa-solid fa-bell-slash"></i> Unsubscribe from newsletter
                    </button>
                </form>
            <?php else: ?>
                <form method="post">
                    <input type="hidden" name="action" value="resubscribe">
                    <button type="submit" class="prefs-btn resub">
                        <i class="fa-solid fa-bell"></i> Re-subscribe to newsletter
                    </button>
                </form>
            <?php endif; ?>

            <a href="/" class="prefs-back"><i class="fa-solid fa-arrow-left"></i> Back to home</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
