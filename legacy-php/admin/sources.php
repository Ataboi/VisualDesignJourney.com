<?php
require_once __DIR__ . '/_guard.php';

$currentPage = 'sources';
$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $boardId = (int)($_POST['board_id'] ?? 0);
    $urlsRaw = trim($_POST['urls'] ?? '');
    $credit = trim($_POST['credit'] ?? '');
    $caption = trim($_POST['caption'] ?? '');

    if ($boardId <= 0 || $urlsRaw === '') {
        $error = 'Choose a board and paste at least one URL.';
    } else {
        $lines = array_filter(array_map('trim', preg_split('/\R+/', $urlsRaw)));
        $added = 0;
        $maxOrderStmt = $db->prepare('SELECT COALESCE(MAX(sort_order), -1) FROM board_images WHERE board_id = ?');
        $maxOrderStmt->execute([$boardId]);
        $order = (int)$maxOrderStmt->fetchColumn() + 1;

        foreach ($lines as $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
                continue;
            }
            $host = parse_url($url, PHP_URL_HOST) ?: 'external';
            try {
                $stmt = $db->prepare(
                    'INSERT INTO board_images (board_id, image_url, sort_order, source_url, source_platform, credit, caption)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$boardId, $url, $order, $url, $host, $credit ?: null, $caption ?: null]);
            } catch (Throwable $e) {
                $stmt = $db->prepare('INSERT INTO board_images (board_id, image_url, sort_order) VALUES (?, ?, ?)');
                $stmt->execute([$boardId, $url, $order]);
            }

            try {
                $coverStmt = $db->prepare('UPDATE boards SET cover_image = COALESCE(cover_image, ?) WHERE id = ?');
                $coverStmt->execute([$url, $boardId]);
            } catch (Throwable $e) {}
            $order++;
            $added++;
        }
        $flash = $added . ' source image' . ($added === 1 ? '' : 's') . ' added.';
    }
}

$boards = $db->query(
    'SELECT b.id, b.title, u.username
     FROM boards b JOIN users u ON u.id = b.user_id
     ORDER BY b.created_at DESC LIMIT 300'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sources — Admin — Visual Design Journey</title>
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
            <h1>Source Import</h1>
            <p>Add permitted external reference images to existing boards without sending visitors away.</p>
        </div>
        <button class="hamburger" id="hamburger" aria-label="Toggle navigation"><i class="fa-solid fa-bars"></i></button>
    </div>

    <?php if ($flash): ?><div class="flash flash-success"><?= e($flash) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

    <div class="admin-card">
        <div class="admin-card__header">
            <h2><i class="fa-solid fa-link"></i> Add source images</h2>
            <a href="/admin/boards.php">Boards</a>
        </div>
        <form method="post" style="display:grid;gap:16px;max-width:820px;">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <label>
                <strong>Board</strong>
                <select name="board_id" class="admin-select" style="width:100%;height:42px;margin-top:6px;" required>
                    <option value="">Choose board...</option>
                    <?php foreach ($boards as $board): ?>
                    <option value="<?= (int)$board['id'] ?>"><?= e($board['title']) ?> — <?= e($board['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <strong>Image / reference URLs</strong>
                <textarea name="urls" rows="8" style="width:100%;margin-top:6px;border:1px solid var(--border);border-radius:var(--radius);padding:12px;" placeholder="https://..." required></textarea>
            </label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <label>
                    <strong>Credit</strong>
                    <input name="credit" style="width:100%;height:42px;margin-top:6px;border:1px solid var(--border);border-radius:var(--radius);padding:0 12px;">
                </label>
                <label>
                    <strong>Caption</strong>
                    <input name="caption" style="width:100%;height:42px;margin-top:6px;border:1px solid var(--border);border-radius:var(--radius);padding:0 12px;">
                </label>
            </div>
            <p style="color:var(--text-muted);font-size:13px;line-height:1.6;">
                Use direct image/reference URLs only when you have permission or the source allows embedding/reuse.
                This importer keeps the browsing experience on Visual Design Journey and stores source metadata for attribution.
            </p>
            <button class="btn btn-accent" type="submit" style="width:max-content;">
                <i class="fa-solid fa-plus"></i> Add to board
            </button>
        </form>
    </div>
</main>
</body>
</html>
