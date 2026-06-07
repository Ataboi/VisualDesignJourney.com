<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();
$username = trim($_GET['u'] ?? '');
$tab      = in_array($_GET['tab'] ?? '', ['followers', 'following']) ? $_GET['tab'] : 'followers';
$search   = trim($_GET['q'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 20;
$offset   = ($page - 1) * $perPage;

if ($username === '') {
    http_response_code(404);
    require_once __DIR__ . '/404.php';
    exit;
}

$profileUser = getUserByUsername($pdo, $username);
if (!$profileUser) {
    http_response_code(404);
    require_once __DIR__ . '/404.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    verifyCsrf();
    $action   = $_POST['action'] ?? '';
    $targetId = (int)($_POST['target_id'] ?? 0);
    $me       = currentUserId();

    if ($targetId > 0 && $me !== $targetId && in_array($action, ['follow', 'unfollow'])) {
        if ($action === 'follow') {
            $stmt = $pdo->prepare('INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)');
            $stmt->execute([$me, $targetId]);
        } else {
            $stmt = $pdo->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?');
            $stmt->execute([$me, $targetId]);
        }
    }

    $qs = http_build_query(['u' => $username, 'tab' => $tab, 'q' => $search, 'page' => $page]);
    header('Location: /followers.php?' . $qs);
    exit;
}

$searchParam = '%' . $search . '%';

if ($tab === 'followers') {
    $countSql = 'SELECT COUNT(*) FROM follows f JOIN users u ON u.id = f.follower_id
                 WHERE f.following_id = :uid' . ($search !== '' ? ' AND u.username LIKE :q' : '');
    $listSql  = 'SELECT u.*, COUNT(f2.id) as follower_count
                 FROM follows f JOIN users u ON u.id = f.follower_id
                 LEFT JOIN follows f2 ON f2.following_id = u.id
                 WHERE f.following_id = :uid' . ($search !== '' ? ' AND u.username LIKE :q' : '') .
                ' GROUP BY u.id ORDER BY u.username ASC LIMIT :limit OFFSET :offset';
} else {
    $countSql = 'SELECT COUNT(*) FROM follows f JOIN users u ON u.id = f.following_id
                 WHERE f.follower_id = :uid' . ($search !== '' ? ' AND u.username LIKE :q' : '');
    $listSql  = 'SELECT u.*, COUNT(f2.id) as follower_count
                 FROM follows f JOIN users u ON u.id = f.following_id
                 LEFT JOIN follows f2 ON f2.following_id = u.id
                 WHERE f.follower_id = :uid' . ($search !== '' ? ' AND u.username LIKE :q' : '') .
                ' GROUP BY u.id ORDER BY u.username ASC LIMIT :limit OFFSET :offset';
}

$countStmt = $pdo->prepare($countSql);
$countStmt->bindValue(':uid', $profileUser['id'], PDO::PARAM_INT);
if ($search !== '') $countStmt->bindValue(':q', $searchParam);
$countStmt->execute();
$total = (int)$countStmt->fetchColumn();

$listStmt = $pdo->prepare($listSql);
$listStmt->bindValue(':uid', $profileUser['id'], PDO::PARAM_INT);
if ($search !== '') $listStmt->bindValue(':q', $searchParam);
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$users = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$followingMap = [];
$me = currentUserId();
if ($me) {
    foreach ($users as $u) {
        $followingMap[$u['id']] = isFollowing($pdo, $me, $u['id']);
    }
}

$followerCount  = getFollowerCount($pdo, $profileUser['id']);
$followingCount = getFollowingCount($pdo, $profileUser['id']);
$hasMore        = ($offset + count($users)) < $total;

$pageTitle = $tab === 'followers'
    ? 'Followers of @' . e($username)
    : '@' . e($username) . ' is Following';
$activeNav = '';

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-wrap" id="main-content">
<div class="container" style="max-width:680px">

  <a href="/profile.php?u=<?= e($username) ?>" class="t-caption text-muted" style="display:inline-flex;align-items:center;gap:.35rem;margin-bottom:1.5rem;text-decoration:none;">
    ← @<?= e($username) ?>'s Profile
  </a>

  <div style="display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:1.5rem;">
    <?php foreach (['followers' => __('followers.tab_followers','Followers'), 'following' => __('followers.tab_following','Following')] as $key => $label):
      $count = $key === 'followers' ? $followerCount : $followingCount;
      $isActive = $tab === $key;
      $href = '/followers.php?' . http_build_query(['u' => $username, 'tab' => $key, 'q' => $search]);
    ?>
    <a href="<?= e($href) ?>" style="display:flex;align-items:center;gap:.4rem;padding:.75rem 1.25rem;text-decoration:none;font-weight:600;font-size:.9rem;border-bottom:2px solid <?= $isActive ? 'var(--accent)' : 'transparent' ?>;color:<?= $isActive ? 'var(--accent)' : 'var(--muted)' ?>;">
      <?= $label ?>
      <span style="background:<?= $isActive ? 'var(--accent)' : 'var(--border)' ?>;color:<?= $isActive ? '#fff' : 'var(--muted)' ?>;border-radius:var(--r-pill);padding:.1rem .5rem;font-size:.75rem;">
        <?= formatCount($count) ?>
      </span>
    </a>
    <?php endforeach; ?>
  </div>

  <form method="get" action="/followers.php" style="margin-bottom:1.5rem;display:flex;gap:.5rem;">
    <input type="hidden" name="u" value="<?= e($username) ?>">
    <input type="hidden" name="tab" value="<?= e($tab) ?>">
    <input class="form-input" type="search" name="q" value="<?= e($search) ?>"
           placeholder="<?= $tab === 'followers' ? e(__('followers.tab_followers','followers')) : e(__('followers.tab_following','following')) ?>…"
           style="flex:1;">
    <button type="submit" class="btn btn--secondary"><?= e(__('followers.search','Search')) ?></button>
  </form>

  <?php if (empty($users)): ?>
  <div style="text-align:center;padding:3rem 1rem;color:var(--muted);">
    <div style="font-size:2.5rem;margin-bottom:.75rem;">👤</div>
    <p class="t-body-md">
      <?= $search !== ''
        ? 'No results for "' . e($search) . '".'
        : ($tab === 'followers' ? '@' . e($username) . ' has no followers yet.' : '@' . e($username) . ' isn\'t following anyone yet.')
      ?>
    </p>
  </div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:.75rem;">
    <?php foreach ($users as $u): ?>
    <div style="display:flex;align-items:center;gap:1rem;background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);padding:1rem;">
      <a href="/profile.php?u=<?= e($u['username']) ?>" style="flex-shrink:0;">
        <?php if ($u['avatar']): ?>
          <img src="<?= e(imageUrl($u['avatar'] ?? "")) ?>" alt="<?= e($u['username']) ?>"
               class="avatar avatar--md" style="width:48px;height:48px;object-fit:cover;">
        <?php else: ?>
          <div class="avatar avatar--md" style="width:48px;height:48px;background:var(--surface);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--muted);font-size:1.1rem;">
            <?= strtoupper(mb_substr($u['username'], 0, 1)) ?>
          </div>
        <?php endif; ?>
      </a>
      <div style="flex:1;min-width:0;">
        <a href="/profile.php?u=<?= e($u['username']) ?>" style="font-weight:700;text-decoration:none;color:inherit;">
          @<?= e($u['username']) ?>
        </a>
        <?php if (!empty($u['bio'])): ?>
        <p class="t-caption text-muted" style="margin:.15rem 0 0;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
          <?= e($u['bio']) ?>
        </p>
        <?php endif; ?>
        <span class="t-caption text-muted"><?= formatCount((int)$u['follower_count']) ?> followers</span>
      </div>
      <?php if ($me && $me !== (int)$u['id']): ?>
      <form method="post" action="/followers.php?u=<?= e($username) ?>&tab=<?= e($tab) ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?>&page=<?= $page ?>" style="flex-shrink:0;">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="target_id" value="<?= (int)$u['id'] ?>">
        <?php $alreadyFollowing = $followingMap[$u['id']] ?? false; ?>
        <input type="hidden" name="action" value="<?= $alreadyFollowing ? 'unfollow' : 'follow' ?>">
        <button type="submit" class="btn btn--sm <?= $alreadyFollowing ? 'btn--secondary' : 'btn--primary' ?>"
                style="border-radius:var(--r-pill);">
          <?= $alreadyFollowing ? e(__('followers.unfollow','Unfollow')) : e(__('followers.follow','Follow')) ?>
        </button>
      </form>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($hasMore): ?>
  <div style="text-align:center;margin-top:2rem;">
    <a href="/followers.php?<?= e(http_build_query(['u' => $username, 'tab' => $tab, 'q' => $search, 'page' => $page + 1])) ?>"
       class="btn btn--secondary"><?= e(__('followers.load_more','Load More')) ?></a>
  </div>
  <?php endif; ?>
  <?php endif; ?>

</div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
