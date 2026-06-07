<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();
requireLogin();

$me      = currentUserId();
$type    = in_array($_GET['type'] ?? '', ['like', 'follow', 'comment', 'mention']) ? $_GET['type'] : 'all';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset  = ($page - 1) * $perPage;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_read') {
    verifyCsrf();
    markAllNotifsRead($pdo, $me);
    header('Location: /notifications.php?type=' . urlencode($type));
    exit;
}

$whereClauses = ['n.user_id = :uid'];
$params       = [':uid' => $me];

if ($type !== 'all') {
    $whereClauses[] = 'n.type = :type';
    $params[':type'] = $type;
}

$where = 'WHERE ' . implode(' AND ', $whereClauses);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications n $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$listSql = "SELECT n.*, u.username as from_username, u.avatar as from_avatar,
                   b.title as board_title
            FROM notifications n
            LEFT JOIN users u ON u.id = n.from_user_id
            LEFT JOIN boards b ON b.id = n.board_id
            $where
            ORDER BY n.created_at DESC
            LIMIT :limit OFFSET :offset";

$listStmt = $pdo->prepare($listSql);
foreach ($params as $k => $v) $listStmt->bindValue($k, $v);
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$notifications = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$unreadCount = (function () use ($pdo, $me) {
    $s = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $s->execute([$me]);
    return (int)$s->fetchColumn();
})();

$hasMore = ($offset + count($notifications)) < $total;

$pageTitle = __('notifications.title', 'Notifications');
$activeNav = 'notifications';

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-wrap" id="main-content">
<div class="container" style="max-width:720px">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
    <h1 class="t-headline-md" style="margin:0;"><?= e(__('notifications.title', 'Notifications')) ?>
      <?php if ($unreadCount > 0): ?>
      <span style="display:inline-flex;align-items:center;justify-content:center;background:var(--accent);color:#fff;border-radius:var(--r-pill);font-size:.75rem;font-weight:700;min-width:1.4rem;height:1.4rem;padding:0 .4rem;vertical-align:middle;margin-left:.4rem;">
        <?= $unreadCount ?>
      </span>
      <?php endif; ?>
    </h1>
    <?php if ($unreadCount > 0): ?>
    <form method="post" action="/notifications.php">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="action" value="mark_read">
      <button type="submit" class="btn btn--secondary btn--sm"><?= e(__('notifications.mark_read', 'Mark all as read')) ?></button>
    </form>
    <?php endif; ?>
  </div>

  <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.75rem;">
    <?php
    $filters = ['all' => __('notifications.all','All'), 'like' => __('notifications.likes','Likes'), 'follow' => __('notifications.follows','Follows'), 'comment' => __('notifications.comments','Comments')];
    foreach ($filters as $key => $label):
      $isActive = $type === $key;
    ?>
    <a href="/notifications.php?type=<?= e($key) ?>"
       class="tag <?= $isActive ? 'tag--active tag--accent' : '' ?>"
       style="text-decoration:none;<?= $isActive ? '' : 'color:var(--muted);' ?>">
      <?= $label ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($notifications)): ?>
  <div style="text-align:center;padding:4rem 1rem;color:var(--muted);background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);">
    <div style="font-size:2.5rem;margin-bottom:.75rem;">🔔</div>
    <p class="t-body-md"><?= e(__('notifications.empty', 'No notifications yet.')) ?></p>
  </div>
  <?php else: ?>
  <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);overflow:hidden;">
    <?php foreach ($notifications as $i => $notif):
      $isUnread = !(bool)$notif['is_read'];
      $borderTop = $i > 0 ? 'border-top:1px solid var(--border);' : '';
      $unreadBg  = $isUnread ? 'background:#FAFAFA;border-left:2px solid var(--accent);' : 'border-left:2px solid transparent;';

      switch ($notif['type']) {
        case 'like':
          $icon    = '❤️';
          $message = '<a href="/profile.php?u=' . e($notif['from_username']) . '" style="font-weight:700;color:inherit;text-decoration:none;">@' . e($notif['from_username']) . '</a> ' . e(__('notifications.liked_board','liked your board')) . ' <a href="/board.php?id=' . (int)$notif['board_id'] . '" style="font-weight:600;color:var(--accent);text-decoration:none;">' . e($notif['board_title']) . '</a>';
          break;
        case 'follow':
          $icon    = '👤';
          $message = '<a href="/profile.php?u=' . e($notif['from_username']) . '" style="font-weight:700;color:inherit;text-decoration:none;">@' . e($notif['from_username']) . '</a> ' . e(__('notifications.followed_you','started following you'));
          break;
        case 'comment':
          $icon    = '💬';
          $message = '<a href="/profile.php?u=' . e($notif['from_username']) . '" style="font-weight:700;color:inherit;text-decoration:none;">@' . e($notif['from_username']) . '</a> ' . e(__('notifications.commented_on','commented on')) . ' <a href="/board.php?id=' . (int)$notif['board_id'] . '" style="font-weight:600;color:var(--accent);text-decoration:none;">' . e($notif['board_title']) . '</a>';
          break;
        case 'mention':
          $icon    = '@';
          $message = '<a href="/profile.php?u=' . e($notif['from_username']) . '" style="font-weight:700;color:inherit;text-decoration:none;">@' . e($notif['from_username']) . '</a> ' . e(__('notifications.mentioned_in','mentioned you in')) . ' <a href="/board.php?id=' . (int)$notif['board_id'] . '" style="font-weight:600;color:var(--accent);text-decoration:none;">' . e($notif['board_title']) . '</a>';
          break;
        default:
          $icon    = '🔔';
          $message = e(__('notifications.new_notif','You have a new notification.'));
      }
    ?>
    <div style="display:flex;align-items:flex-start;gap:.875rem;padding:.875rem 1rem;<?= $borderTop ?><?= $unreadBg ?>">
      <?php if ($notif['from_username'] && $notif['from_avatar']): ?>
        <a href="/profile.php?u=<?= e($notif['from_username']) ?>" style="flex-shrink:0;">
          <img src="<?= e(imageUrl($notif['from_avatar'] ?? "")) ?>" alt="<?= e($notif['from_username']) ?>"
               class="avatar avatar--sm" style="width:40px;height:40px;object-fit:cover;">
        </a>
      <?php elseif ($notif['from_username']): ?>
        <a href="/profile.php?u=<?= e($notif['from_username']) ?>" style="flex-shrink:0;text-decoration:none;">
          <div class="avatar avatar--sm" style="width:40px;height:40px;background:var(--surface);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--muted);">
            <?= strtoupper(mb_substr($notif['from_username'], 0, 1)) ?>
          </div>
        </a>
      <?php else: ?>
        <div style="flex-shrink:0;width:40px;height:40px;background:var(--surface);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;">
          <?= $icon ?>
        </div>
      <?php endif; ?>
      <div style="flex:1;min-width:0;">
        <p class="t-body-md" style="margin:0 0 .2rem;"><?= $message ?></p>
        <span class="t-caption text-muted"><?= timeAgo($notif['created_at']) ?></span>
      </div>
      <?php if ($isUnread): ?>
      <div style="flex-shrink:0;width:.5rem;height:.5rem;border-radius:50%;background:var(--accent);margin-top:.4rem;"></div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($hasMore): ?>
  <div style="text-align:center;margin-top:1.5rem;">
    <a href="/notifications.php?<?= e(http_build_query(['type' => $type, 'page' => $page + 1])) ?>"
       class="btn btn--secondary"><?= e(__('notifications.load_more','Load More')) ?></a>
  </div>
  <?php endif; ?>
  <?php endif; ?>

</div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
