<?php
require_once __DIR__ . '/_guard.php';

// ── Actions ────────────────────────────────────────────────────────────────
$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_board') {
        $boardId = (int)($_POST['board_id'] ?? 0);
        if ($boardId) {
            $s = $db->prepare('SELECT * FROM boards WHERE id = ?');
            $s->execute([$boardId]);
            $board = $s->fetch();
            if ($board) {
                $db->prepare('DELETE FROM boards WHERE id = ?')->execute([$boardId]);
                $flash = "Board \"{$board['title']}\" deleted.";
            }
        }
    }

    if ($action === 'toggle_draft') {
        $boardId = (int)($_POST['board_id'] ?? 0);
        if ($boardId) {
            $s = $db->prepare('SELECT * FROM boards WHERE id = ?');
            $s->execute([$boardId]);
            $board = $s->fetch();
            if ($board) {
                $newDraft = $board['is_draft'] ? 0 : 1;
                $db->prepare('UPDATE boards SET is_draft = ? WHERE id = ?')->execute([$newDraft, $boardId]);
                $flash = "Board \"" . $board['title'] . "\" " . ($newDraft ? "moved to drafts." : "published.");
            }
        }
    }

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// ── Query params ───────────────────────────────────────────────────────────
$q      = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'all';
$sort   = $_GET['sort'] ?? 'newest';
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

// ── WHERE ──────────────────────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];

if ($q !== '') {
    $where[]  = 'b.title LIKE ?';
    $params[] = "%$q%";
}

if ($status === 'published') {
    $where[] = 'b.is_draft = 0';
} elseif ($status === 'draft') {
    $where[] = 'b.is_draft = 1';
}

$whereSQL = implode(' AND ', $where);

// ── ORDER BY ───────────────────────────────────────────────────────────────
$orderMap = [
    'newest'  => 'b.created_at DESC',
    'oldest'  => 'b.created_at ASC',
    'popular' => 'like_count DESC',
];
$orderSQL = $orderMap[$sort] ?? 'b.created_at DESC';

// ── Count ──────────────────────────────────────────────────────────────────
$stmtCount = $db->prepare("SELECT COUNT(*) FROM boards b WHERE $whereSQL");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// ── Fetch boards ───────────────────────────────────────────────────────────
$stmtBoards = $db->prepare(
    "SELECT b.*, u.username,
            COUNT(bl.id) AS like_count
     FROM boards b
     JOIN users u ON u.id = b.user_id
     LEFT JOIN board_likes bl ON bl.board_id = b.id
     WHERE $whereSQL
     GROUP BY b.id
     ORDER BY $orderSQL
     LIMIT ? OFFSET ?"
);
$stmtBoards->execute(array_merge($params, [$perPage, $offset]));
$boards = $stmtBoards->fetchAll();

$csrf = csrfToken();
$currentPage = 'boards';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Boards — Admin — Visual Design Journey</title>
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
            <h1>Boards</h1>
            <p>Manage all boards on the platform.</p>
        </div>
        <button class="hamburger" id="hamburger"><i class="fa-solid fa-bars"></i></button>
    </div>

    <?php if ($flash): ?>
        <div class="flash flash-<?= $flashType === 'error' ? 'error' : 'success' ?>">
            <i class="fa-solid fa-<?= $flashType === 'error' ? 'circle-xmark' : 'circle-check' ?>"></i>
            <?= e($flash) ?>
        </div>
    <?php endif; ?>

    <!-- Toolbar -->
    <div class="toolbar">
        <form method="get" action="/admin/boards.php" class="search-wrap">
            <?php if ($status !== 'all'): ?>
                <input type="hidden" name="status" value="<?= e($status) ?>">
            <?php endif; ?>
            <?php if ($sort !== 'newest'): ?>
                <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <?php endif; ?>
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" name="q" class="search-input" placeholder="Search by title…" value="<?= e($q) ?>">
        </form>

        <!-- Status filter tabs -->
        <div class="filter-tabs">
            <a href="?<?= http_build_query(array_merge($_GET, ['status' => 'all', 'page' => 1])) ?>"
               class="filter-tab <?= $status === 'all' ? 'active' : '' ?>">All</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['status' => 'published', 'page' => 1])) ?>"
               class="filter-tab <?= $status === 'published' ? 'active' : '' ?>">Published</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['status' => 'draft', 'page' => 1])) ?>"
               class="filter-tab <?= $status === 'draft' ? 'active' : '' ?>">Drafts</a>
        </div>

        <!-- Sort -->
        <form method="get" action="/admin/boards.php" style="display:flex;align-items:center;gap:6px;">
            <?php if ($q !== ''): ?>
                <input type="hidden" name="q" value="<?= e($q) ?>">
            <?php endif; ?>
            <?php if ($status !== 'all'): ?>
                <input type="hidden" name="status" value="<?= e($status) ?>">
            <?php endif; ?>
            <input type="hidden" name="page" value="1">
            <select name="sort" onchange="this.form.submit()" style="padding:6px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px;color:var(--text);background:var(--white);outline:none;cursor:pointer;">
                <option value="newest"  <?= $sort === 'newest'  ? 'selected' : '' ?>>Newest first</option>
                <option value="oldest"  <?= $sort === 'oldest'  ? 'selected' : '' ?>>Oldest first</option>
                <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most popular</option>
            </select>
        </form>

        <span class="result-count"><?= number_format($total) ?> board<?= $total !== 1 ? 's' : '' ?></span>
    </div>

    <!-- Table -->
    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:60px;">Cover</th>
                    <th>Title</th>
                    <th style="width:110px;">Author</th>
                    <th style="width:140px;">Tags</th>
                    <th style="width:70px;">Likes</th>
                    <th style="width:100px;">Status</th>
                    <th style="width:100px;">Created</th>
                    <th style="width:210px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($boards): ?>
            <?php foreach ($boards as $board): ?>
                <?php
                $tags = parseTags($board['style_tags'] ?? '');
                $coverSrc = !empty($board['cover_image']) ? imageUrl($board['cover_image']) : '';
                ?>
                <tr>
                    <td>
                        <?php if ($coverSrc): ?>
                            <img src="<?= e($coverSrc) ?>" alt="" class="board-cover">
                        <?php else: ?>
                            <div class="board-cover-placeholder"><i class="fa-solid fa-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="/board.php?id=<?= $board['id'] ?>" target="_blank" style="font-weight:500;color:var(--text);text-decoration:none;">
                            <?= e($board['title']) ?>
                        </a>
                    </td>
                    <td>
                        <a href="/profile.php?u=<?= urlencode($board['username']) ?>" target="_blank" style="color:var(--accent);text-decoration:none;">
                            <?= e($board['username']) ?>
                        </a>
                    </td>
                    <td>
                        <?php if ($tags): ?>
                            <div style="display:flex;flex-wrap:wrap;gap:3px;">
                                <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
                                    <span style="display:inline-block;padding:1px 7px;border-radius:20px;background:#f1f5f9;color:var(--text-muted);font-size:11px;"><?= e($tag) ?></span>
                                <?php endforeach; ?>
                                <?php if (count($tags) > 3): ?>
                                    <span style="display:inline-block;padding:1px 7px;border-radius:20px;background:#f1f5f9;color:var(--text-muted);font-size:11px;">+<?= count($tags) - 3 ?></span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted">
                        <span style="display:inline-flex;align-items:center;gap:4px;">
                            <i class="fa-solid fa-heart" style="color:#f43f5e;font-size:11px;"></i>
                            <?= formatCount((int)$board['like_count']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($board['is_draft']): ?>
                            <span class="badge badge-draft"><i class="fa-solid fa-pencil"></i> Draft</span>
                        <?php else: ?>
                            <span class="badge badge-published"><i class="fa-solid fa-circle-check"></i> Published</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted" style="white-space:nowrap;"><?= date('M j, Y', strtotime($board['created_at'])) ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
                            <!-- View -->
                            <a href="/board.php?id=<?= $board['id'] ?>" target="_blank" class="btn btn-sm" style="border:1px solid var(--border);background:var(--white);color:var(--text);text-decoration:none;">
                                <i class="fa-solid fa-eye"></i> View
                            </a>
                            <!-- Edit -->
                            <a href="/edit-board.php?id=<?= $board['id'] ?>" class="btn btn-sm btn-accent" style="text-decoration:none;">
                                <i class="fa-solid fa-pen"></i> Edit
                            </a>
                            <!-- Toggle Draft -->
                            <form method="post" action="/admin/boards.php" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                <input type="hidden" name="action" value="toggle_draft">
                                <input type="hidden" name="board_id" value="<?= $board['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-warning" title="<?= $board['is_draft'] ? 'Publish' : 'Move to Draft' ?>">
                                    <i class="fa-solid fa-<?= $board['is_draft'] ? 'rocket' : 'eye-slash' ?>"></i>
                                    <?= $board['is_draft'] ? 'Publish' : 'Draft' ?>
                                </button>
                            </form>
                            <!-- Delete -->
                            <form method="post" action="/admin/boards.php" style="display:inline;"
                                  onsubmit="return confirm('Delete board <?= e(addslashes($board['title'])) ?>? This cannot be undone.');">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                <input type="hidden" name="action" value="delete_board">
                                <input type="hidden" name="board_id" value="<?= $board['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fa-solid fa-border-none"></i>
                            No boards found.
                            <p>Try adjusting your search or filters.</p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)])) ?>"
               class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                   class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => min($totalPages, $page + 1)])) ?>"
               class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
