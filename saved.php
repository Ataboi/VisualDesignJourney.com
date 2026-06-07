<?php
/**
 * saved.php — User's saved/bookmarked boards
 * Requires login.
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

session_start_if_not_started();
requireLogin();

$db     = getDB();
$userId = currentUserId();

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 21;
$offset  = ($page - 1) * $perPage;

// Saved boards with like count and saved_at timestamp
$stmt = $db->prepare(
    'SELECT b.*, u.username, u.avatar,
            (SELECT COUNT(*) FROM board_likes bl WHERE bl.board_id = b.id) AS like_count,
            bs.created_at AS saved_at
     FROM board_saves bs
     JOIN boards b ON b.id = bs.board_id
     JOIN users u ON u.id = b.user_id
     WHERE bs.user_id = ? AND b.is_draft = 0
     ORDER BY bs.created_at DESC
     LIMIT ? OFFSET ?'
);
$stmt->execute([$userId, $perPage, $offset]);
$boards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total saved count for pagination
$totalStmt = $db->prepare('SELECT COUNT(*) FROM board_saves bs JOIN boards b ON b.id = bs.board_id WHERE bs.user_id = ? AND b.is_draft = 0');
$totalStmt->execute([$userId]);
$total      = (int)$totalStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$pageTitle = __('saved.title', 'Saved Boards') . ' — Visual Design Journey';
$activeNav = 'saved';
$metaDesc  = 'Your saved mood boards on Visual Design Journey.';

require_once __DIR__ . '/includes/header.php';
?>

<main class="page-wrap" id="main-content">
<div class="container">

  <!-- Page heading -->
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:32px;padding-top:24px;">
    <div>
      <h1 style="font-family:var(--font-display);font-size:clamp(22px,4vw,32px);font-weight:700;letter-spacing:-0.03em;margin:0;">
        <?= e(__('saved.title', 'Your Saved Boards')) ?>
      </h1>
      <?php if ($total > 0): ?>
        <p style="color:var(--muted);font-size:14px;margin-top:6px;">
          <?= $total ?> <?= $total === 1 ? 'board' : 'boards' ?> saved
        </p>
      <?php endif; ?>
    </div>
    <a href="/index.php" class="btn btn--secondary" style="border-radius:var(--r-pill);">
      <span class="material-symbols-outlined" style="font-size:16px;vertical-align:-3px;margin-right:4px;">explore</span>
      <?= e(__('saved.explore', 'Explore boards')) ?>
    </a>
  </div>

  <?php if (empty($boards)): ?>
    <!-- Empty state -->
    <div class="empty-state" style="text-align:center;padding:80px 24px;">
      <div class="empty-state__icon" style="margin-bottom:20px;">
        <span class="material-symbols-outlined" style="font-size:56px;opacity:.25;display:block;">bookmark</span>
      </div>
      <p class="empty-state__title" style="font-size:18px;font-weight:600;margin-bottom:8px;">
        <?= e(__('saved.empty_title', 'No saved boards yet')) ?>
      </p>
      <p class="empty-state__sub" style="color:var(--muted);font-size:14px;margin-bottom:24px;">
        <?= e(__('saved.empty_sub', 'Tap the bookmark icon on any board to save it here for later.')) ?>
      </p>
      <a href="/index.php" class="btn btn--primary" style="border-radius:var(--r-pill);">
        <?= e(__('saved.explore', 'Explore boards')) ?>
      </a>
    </div>

  <?php else: ?>

    <!-- Masonry grid -->
    <div class="masonry" role="list" aria-label="Saved mood boards">
      <?php foreach ($boards as $board):
        $liked  = hasLiked($db, (int)$board['id'], $userId);
        $cover  = $board['cover_image'] ?? null;
      ?>
      <article class="board-card" role="listitem" aria-label="<?= e($board['title']) ?>">

        <!-- Cover image -->
        <a href="/board/<?= (int)$board['id'] ?>"
           aria-label="View board: <?= e($board['title']) ?>">
          <?php if ($cover): ?>
            <div class="board-card__img-wrap">
              <div class="board-card__skeleton skeleton"></div>
              <img src="<?= e(imageUrl($cover)) ?>"
                   alt="Cover image for <?= e($board['title']) ?> by <?= e($board['username']) ?>"
                   class="board-card__img"
                   loading="lazy"
                   onload="this.classList.add('loaded');this.previousElementSibling.style.display='none'"/>
            </div>
          <?php else: ?>
            <div class="board-card__img--placeholder" role="img" aria-label="No cover image for <?= e($board['title']) ?>">
              <span class="material-symbols-outlined" style="font-size:32px;color:var(--border);">image</span>
            </div>
          <?php endif; ?>
        </a>

        <!-- Body -->
        <div class="board-card__body">
          <h3 class="board-card__title">
            <a href="/board/<?= (int)$board['id'] ?>"><?= e($board['title']) ?></a>
          </h3>
          <div class="board-card__meta">
            <!-- Author -->
            <a href="/profile.php?u=<?= urlencode($board['username']) ?>"
               class="board-card__author"
               aria-label="View profile of <?= e($board['username']) ?>">
              <?php if (!empty($board['avatar'])): ?>
                <img src="<?= e(imageUrl($board['avatar'])) ?>"
                     alt="<?= e($board['username']) ?>'s avatar"
                     class="avatar avatar--sm"/>
              <?php else: ?>
                <span class="avatar avatar--sm"
                      role="img"
                      aria-label="<?= e($board['username']) ?>'s avatar"
                      style="background:var(--surface);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:600;color:var(--muted);">
                  <?= strtoupper(substr($board['username'], 0, 1)) ?>
                </span>
              <?php endif; ?>
              <span><?= e($board['username']) ?></span>
            </a>

            <!-- Like button -->
            <button class="board-card__likes like-btn"
                    data-board="<?= (int)$board['id'] ?>"
                    data-liked="<?= $liked ? '1' : '0' ?>"
                    aria-label="<?= $liked ? 'Unlike' : 'Like' ?> «<?= e($board['title']) ?>» (<?= formatCount((int)$board['like_count']) ?> likes)"
                    style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:4px;">
              <span class="material-symbols-outlined <?= $liked ? 'icon-filled text-accent' : '' ?>"
                    aria-hidden="true"
                    style="font-size:16px;">favorite</span>
              <span class="like-count" aria-live="polite"><?= formatCount((int)$board['like_count']) ?></span>
            </button>

            <!-- Save/Unsave button (already saved) -->
            <button class="save-btn"
                    data-board="<?= (int)$board['id'] ?>"
                    data-saved="1"
                    aria-label="Unsave <?= e($board['title']) ?>"
                    style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:4px;">
              <span class="material-symbols-outlined icon-filled text-accent"
                    aria-hidden="true"
                    style="font-size:16px;">bookmark</span>
            </button>
          </div>
        </div>

      </article>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav aria-label="Page navigation" style="text-align:center;margin-top:48px;padding-bottom:40px;display:flex;justify-content:center;gap:8px;flex-wrap:wrap;">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>"
           class="btn btn--secondary"
           style="border-radius:var(--r-pill);"
           aria-label="Previous page">
          ← Prev
        </a>
      <?php endif; ?>

      <?php
        $startPage = max(1, $page - 2);
        $endPage   = min($totalPages, $page + 2);
        for ($p = $startPage; $p <= $endPage; $p++):
      ?>
        <a href="?page=<?= $p ?>"
           class="btn <?= $p === $page ? 'btn--primary' : 'btn--secondary' ?>"
           style="border-radius:var(--r-pill);min-width:40px;"
           <?= $p === $page ? 'aria-current="page"' : '' ?>>
          <?= $p ?>
        </a>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>"
           class="btn btn--secondary"
           style="border-radius:var(--r-pill);"
           aria-label="Next page">
          Next →
        </a>
      <?php endif; ?>
    </nav>
    <?php endif; ?>

  <?php endif; ?>

</div><!-- /container -->
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
