<?php
require_once __DIR__ . '/_guard.php';

// ── Actions ────────────────────────────────────────────────────────────────
$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_admin') {
        $targetId = (int)($_POST['user_id'] ?? 0);
        if ($targetId && $targetId !== (int)$_SESSION['user_id']) {
            $target = getUserById($db, $targetId);
            if ($target) {
                $newVal = $target['is_admin'] ? 0 : 1;
                $db->prepare('UPDATE users SET is_admin = ? WHERE id = ?')->execute([$newVal, $targetId]);
                $flash = $newVal ? "Admin granted to {$target['username']}." : "Admin revoked from {$target['username']}.";
            }
        } else { $flash = 'Cannot modify your own admin status.'; $flashType = 'error'; }
    }

    if ($action === 'delete_user') {
        $targetId = (int)($_POST['user_id'] ?? 0);
        if ($targetId && $targetId !== (int)$_SESSION['user_id']) {
            $target = getUserById($db, $targetId);
            if ($target) {
                $db->prepare('DELETE FROM users WHERE id = ?')->execute([$targetId]);
                $flash = "User \"{$target['username']}\" deleted.";
            }
        } else { $flash = 'Cannot delete your own account here.'; $flashType = 'error'; }
    }

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// ── Query params ───────────────────────────────────────────────────────────
$q      = trim($_GET['q'] ?? '');
$role   = $_GET['role'] ?? 'all';
$sort   = $_GET['sort'] ?? 'newest';
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

// ── WHERE ──────────────────────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];

if ($q !== '') {
    $where[]  = '(u.username LIKE ? OR u.email LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}

if ($role === 'admin') {
    $where[] = 'u.is_admin = 1';
} elseif ($role === 'user') {
    $where[] = 'u.is_admin = 0';
}

$whereSQL = implode(' AND ', $where);

// ── ORDER BY ───────────────────────────────────────────────────────────────
$orderMap = [
    'newest'    => 'u.created_at DESC',
    'oldest'    => 'u.created_at ASC',
    'boards'    => 'board_count DESC',
    'followers' => 'follower_count DESC',
];
$orderSQL = $orderMap[$sort] ?? 'u.created_at DESC';

// ── Count ──────────────────────────────────────────────────────────────────
$stmtCount = $db->prepare("SELECT COUNT(*) FROM users u WHERE $whereSQL");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// ── Fetch users ────────────────────────────────────────────────────────────
$stmtUsers = $db->prepare(
    "SELECT u.*,
            (SELECT COUNT(*) FROM boards b WHERE b.user_id = u.id) AS board_count,
            (SELECT COUNT(*) FROM follows f WHERE f.following_id = u.id) AS follower_count
     FROM users u
     WHERE $whereSQL
     ORDER BY $orderSQL
     LIMIT ? OFFSET ?"
);
$stmtUsers->execute(array_merge($params, [$perPage, $offset]));
$users = $stmtUsers->fetchAll();

$csrf = csrfToken();
$currentPage = 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Users — Admin — Visual Design Journey</title>
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
            <h1>Users</h1>
            <p>Manage all user accounts on the platform.</p>
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
        <form method="get" action="/admin/users.php" class="search-wrap" id="search-form">
            <?php if ($role !== 'all'): ?>
                <input type="hidden" name="role" value="<?= e($role) ?>">
            <?php endif; ?>
            <?php if ($sort !== 'newest'): ?>
                <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <?php endif; ?>
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" name="q" class="search-input" placeholder="Search username or email…" value="<?= e($q) ?>">
        </form>

        <!-- Role filter tabs -->
        <div class="filter-tabs">
            <a href="?<?= http_build_query(array_merge($_GET, ['role' => 'all', 'page' => 1])) ?>"
               class="filter-tab <?= $role === 'all' ? 'active' : '' ?>">All</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['role' => 'admin', 'page' => 1])) ?>"
               class="filter-tab <?= $role === 'admin' ? 'active' : '' ?>">Admins</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['role' => 'user', 'page' => 1])) ?>"
               class="filter-tab <?= $role === 'user' ? 'active' : '' ?>">Users</a>
        </div>

        <!-- Sort -->
        <form method="get" action="/admin/users.php" id="sort-form" style="display:flex;align-items:center;gap:6px;">
            <?php if ($q !== ''): ?>
                <input type="hidden" name="q" value="<?= e($q) ?>">
            <?php endif; ?>
            <?php if ($role !== 'all'): ?>
                <input type="hidden" name="role" value="<?= e($role) ?>">
            <?php endif; ?>
            <input type="hidden" name="page" value="1">
            <select name="sort" onchange="this.form.submit()" style="padding:6px 10px;border:1px solid var(--border);border-radius:6px;font-size:13px;color:var(--text);background:var(--white);outline:none;cursor:pointer;">
                <option value="newest"    <?= $sort === 'newest'    ? 'selected' : '' ?>>Newest first</option>
                <option value="oldest"    <?= $sort === 'oldest'    ? 'selected' : '' ?>>Oldest first</option>
                <option value="boards"    <?= $sort === 'boards'    ? 'selected' : '' ?>>Most boards</option>
                <option value="followers" <?= $sort === 'followers' ? 'selected' : '' ?>>Most followers</option>
            </select>
        </form>

        <span class="result-count"><?= number_format($total) ?> user<?= $total !== 1 ? 's' : '' ?></span>
    </div>

    <!-- Table -->
    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:48px;">ID</th>
                    <th>User</th>
                    <th style="width:80px;">Boards</th>
                    <th style="width:90px;">Followers</th>
                    <th style="width:90px;">Role</th>
                    <th style="width:110px;">Joined</th>
                    <th style="width:220px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($users): ?>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td class="text-muted">#<?= $user['id'] ?></td>
                    <td>
                        <div class="user-cell">
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="<?= e(imageUrl($user['avatar'])) ?>" alt="" class="user-avatar">
                            <?php else: ?>
                                <span class="user-avatar-placeholder"><?= strtoupper(substr($user['username'], 0, 1)) ?></span>
                            <?php endif; ?>
                            <div class="user-info">
                                <div class="user-name"><?= e($user['username']) ?></div>
                                <div class="user-email"><?= e($user['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="text-muted"><?= formatCount((int)$user['board_count']) ?></td>
                    <td class="text-muted"><?= formatCount((int)$user['follower_count']) ?></td>
                    <td>
                        <?php if ($user['is_admin']): ?>
                            <span class="badge badge-admin"><i class="fa-solid fa-shield-halved"></i> Admin</span>
                        <?php else: ?>
                            <span class="text-muted" style="font-size:12px;">User</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted" style="white-space:nowrap;"><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <div class="actions" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                            <!-- View Profile -->
                            <a href="/profile.php?u=<?= urlencode($user['username']) ?>" target="_blank" class="btn btn-sm" style="border:1px solid var(--border);background:var(--white);color:var(--text);">
                                <i class="fa-solid fa-user"></i> Profile
                            </a>

                            <?php if ((int)$user['id'] !== (int)$_SESSION['user_id']): ?>
                                <!-- Toggle Admin -->
                                <form method="post" action="/admin/users.php" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="action" value="toggle_admin">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-accent" title="<?= $user['is_admin'] ? 'Revoke Admin' : 'Grant Admin' ?>">
                                        <i class="fa-solid fa-<?= $user['is_admin'] ? 'user-minus' : 'user-shield' ?>"></i>
                                        <?= $user['is_admin'] ? 'Revoke' : 'Make Admin' ?>
                                    </button>
                                </form>
                                <!-- Delete -->
                                <form method="post" action="/admin/users.php" style="display:inline;"
                                      onsubmit="return confirm('Delete user <?= e(addslashes($user['username'])) ?>? This cannot be undone.');">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted" style="font-size:12px;">You</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="fa-solid fa-users-slash"></i>
                            No users found.
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
