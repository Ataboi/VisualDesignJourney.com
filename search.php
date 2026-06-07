<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();
$q    = trim($_GET['q'] ?? '');
$type = in_array($_GET['type'] ?? '', ['boards', 'users']) ? $_GET['type'] : 'boards';
$page = max(1, (int)($_GET['page'] ?? 1));
$me   = currentUserId();

$boards     = [];
$boardTotal = 0;
$users      = [];
$userTotal  = 0;

if ($q !== '') {
    if ($type === 'boards') {
        $result     = getBoards($pdo, $page, 20, '', $q);
        $boards     = $result['boards'] ?? $result;
        $boardTotal = $result['total'] ?? count($boards);
    } else {
        $likeParam = '%' . $q . '%';
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username LIKE ? OR bio LIKE ?');
        $countStmt->execute([$likeParam, $likeParam]);
        $userTotal = (int)$countStmt->fetchColumn();

        $userStmt = $pdo->prepare(
            'SELECT u.*, COUNT(b.id) as board_count
             FROM users u
             LEFT JOIN boards b ON b.user_id = u.id AND b.is_draft = 0
             WHERE u.username LIKE :q OR u.bio LIKE :q2
             GROUP BY u.id
             ORDER BY u.username ASC
             LIMIT 20'
        );
        $userStmt->bindValue(':q', $likeParam);
        $userStmt->bindValue(':q2', $likeParam);
        $userStmt->execute();
        $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$followingMap = [];
if ($me && $type === 'users' && !empty($users)) {
    foreach ($users as $u) {
        $followingMap[$u['id']] = isFollowing($pdo, $me, $u['id']);
    }
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
    header('Location: /search.php?' . http_build_query(['q' => $q, 'type' => $type]));
    exit;
}

$hasMoreBoards = $type === 'boards' && ($page * 20) < $boardTotal;

$pageTitle = $q !== '' ? 'Search: ' . e($q) : __('search.default_title', 'Search');
$activeNav = 'search';

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-wrap" id="main-content">
<div class="container">

  <!-- Search Bar (mockup: h-14, centered, icon + clear) -->
  <section style="margin-bottom:2.5rem;">
    <form method="get" action="/search.php" style="max-width:672px;margin:0 auto 2rem;">
      <input type="hidden" name="type" value="<?= e($type) ?>">
      <div style="position:relative;">
        <span class="material-symbols-outlined"
              style="position:absolute;left:16px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:20px;pointer-events:none;">search</span>
        <input class="form-input" type="search" name="q" id="search-q" value="<?= e($q) ?>"
               placeholder="<?= e(__('search.placeholder', 'Search boards, curators, tags…')) ?>"
               autofocus
               style="width:100%;height:56px;padding:0 48px 0 48px;font-size:1rem;border-radius:var(--r-lg);border:0.5px solid var(--border);background:#fff;box-sizing:border-box;transition:border-color .2s;"
               onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'">
        <?php if ($q !== ''): ?>
        <a href="/search.php?type=<?= e($type) ?>"
           style="position:absolute;right:12px;top:50%;transform:translateY(-50%);display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;color:var(--muted);text-decoration:none;"
           aria-label="Clear search">
          <span class="material-symbols-outlined" style="font-size:18px;">close</span>
        </a>
        <?php endif; ?>
      </div>
    </form>

    <?php if ($q !== ''): ?>
    <!-- Results count + Sort -->
    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px;margin-bottom:1.25rem;">
      <h1 style="font-family:var(--font-display);font-size:1.375rem;font-weight:600;margin:0;">
        <?= formatCount($type === 'boards' ? $boardTotal : $userTotal) ?>
        <?= $type === 'boards' ? 'board' : 'creator' ?><?= ($type === 'boards' ? $boardTotal : $userTotal) !== 1 ? 's' : '' ?>
        found for &ldquo;<?= e($q) ?>&rdquo;
      </h1>
      <div style="display:flex;align-items:center;gap:12px;">
        <!-- Type toggle -->
        <?php foreach (['boards' => __('search.boards','Boards'), 'users' => __('search.people','People')] as $key => $label):
          $isActive = $type === $key;
          $href = '/search.php?' . http_build_query(['q' => $q, 'type' => $key]);
        ?>
        <a href="<?= e($href) ?>"
           style="padding:6px 16px;border-radius:var(--r-pill);border:0.5px solid <?= $isActive ? 'var(--accent)' : 'var(--border)' ?>;background:<?= $isActive ? 'var(--accent)' : '#fff' ?>;color:<?= $isActive ? '#fff' : 'var(--text)' ?>;font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;text-decoration:none;transition:all .2s;">
          <?= $label ?>
        </a>
        <?php endforeach; ?>
        <?php if ($type === 'boards'): ?>
        <form method="get" action="/search.php" style="margin:0;">
          <input type="hidden" name="q" value="<?= e($q) ?>">
          <input type="hidden" name="type" value="boards">
          <select name="sort" onchange="this.form.submit()" class="sort-select"
                  style="border-radius:var(--r-pill);border:0.5px solid var(--border);padding:6px 16px;font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;background:#fff;cursor:pointer;" aria-label="Sort results">
            <option value="newest"   <?= ($_GET['sort'] ?? '') === 'newest'   ? 'selected' : '' ?>><?= e(__('search.newest','Newest')) ?></option>
            <option value="popular"  <?= ($_GET['sort'] ?? '') === 'popular'  ? 'selected' : '' ?>><?= e(__('search.popular','Most Liked')) ?></option>
            <option value="trending" <?= ($_GET['sort'] ?? '') === 'trending' ? 'selected' : '' ?>><?= e(__('search.trending','Trending')) ?></option>
          </select>
        </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- Aesthetic filter pills -->
    <?php if ($type === 'boards'): ?>
    <?php $aesthetics = ['All' => '', 'Minimal' => 'Minimal', 'Bauhaus' => 'Bauhaus', 'Y2K' => 'Y2K',
                          'Art Nouveau' => 'Art+Nouveau', 'Brutalist' => 'Brutalist',
                          'Japandi' => 'Japandi', 'Dark Academia' => 'Dark+Academia'];
    $activeTag = trim($_GET['tag'] ?? ''); ?>
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:2.5rem;">
      <?php foreach ($aesthetics as $label => $val): ?>
      <a href="/search.php?q=<?= urlencode($q) ?>&type=boards<?= $val ? '&tag='.rawurlencode($val) : '' ?>"
         style="padding:7px 16px;border-radius:var(--r-pill);border:0.5px solid <?= ($activeTag === urldecode($val)) ? 'var(--accent)' : 'var(--border)' ?>;background:<?= ($activeTag === urldecode($val)) ? 'var(--accent)' : '#fff' ?>;color:<?= ($activeTag === urldecode($val)) ? '#fff' : 'var(--text)' ?>;font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;text-decoration:none;white-space:nowrap;transition:all .2s;">
        <?= e($label) ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </section>

  <?php if ($q !== ''): ?>
  <?php if ($type === 'boards'): ?>
    <?php if (empty($boards)): ?>
    <div class="empty-state">
      <div class="empty-state__icon"><span class="material-symbols-outlined" style="font-size:48px;opacity:.3;">search_off</span></div>
      <p class="empty-state__title"><?= e(__('search.no_boards','No boards found')) ?></p>
      <p class="empty-state__sub"><?= e(__('search.no_boards_sub','Try a different keyword or style tag.')) ?></p>
    </div>
    <?php else: ?>
    <!-- Masonry grid with hover bookmark + image scale -->
    <div class="masonry" role="list" aria-label="Search results">
      <?php foreach ($boards as $board): ?>
      <?php
        $palHexes = [];
        if (!empty($board['palette_colors'])) {
            $dec = json_decode($board['palette_colors'], true);
            if (is_array($dec)) $palHexes = array_slice($dec, 0, 5);
        }
      ?>
      <article class="board-card" role="listitem" style="position:relative;">
        <?php if (!empty($board['cover_image'])): ?>
        <a href="/board.php?id=<?= (int)$board['id'] ?>" style="position:relative;display:block;overflow:hidden;">
          <img src="<?= e(imageUrl($board['cover_image'])) ?>"
               alt="<?= e($board['title']) ?>"
               class="board-card__img"
               loading="lazy"
               style="transition:transform .5s;"
               onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
          <!-- Bookmark on hover -->
          <button onclick="event.preventDefault();event.stopPropagation();"
                  class="save-btn"
                  data-board="<?= (int)$board['id'] ?>"
                  data-saved="0"
                  aria-label="Save «<?= e($board['title']) ?>»"
                  style="position:absolute;top:12px;right:12px;width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.9);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .2s;"
                  onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
            <span class="material-symbols-outlined" style="font-size:16px;color:var(--muted);">bookmark</span>
          </button>
        </a>
        <?php endif; ?>
        <div class="board-card__body">
          <?php if ($palHexes): ?>
          <div style="display:flex;gap:4px;margin-bottom:8px;">
            <?php foreach ($palHexes as $hex): ?>
              <div style="width:16px;height:16px;border-radius:3px;background:<?= e($hex) ?>;border:0.5px solid rgba(0,0,0,.08);" title="<?= e($hex) ?>"></div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <h3 class="board-card__title">
            <a href="/board.php?id=<?= (int)$board['id'] ?>" style="text-decoration:none;color:inherit;"><?= e($board['title']) ?></a>
          </h3>
          <div class="board-card__meta">
            <a href="/profile.php?u=<?= e($board['username']) ?>" class="board-card__author">
              <?php if (!empty($board['avatar'])): ?>
                <img src="<?= e(imageUrl($board['avatar'])) ?>" class="avatar avatar--sm" alt="">
              <?php else: ?>
                <span class="avatar avatar--sm" style="background:var(--surface);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:600;color:var(--muted);">
                  <?= strtoupper(mb_substr($board['username'], 0, 1)) ?>
                </span>
              <?php endif; ?>
              <span><?= e($board['username']) ?></span>
            </a>
            <span class="board-card__likes" style="display:flex;align-items:center;gap:4px;">
              <span class="material-symbols-outlined" style="font-size:16px;">favorite</span>
              <span><?= formatCount((int)$board['like_count']) ?></span>
            </span>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <?php if ($hasMoreBoards): ?>
    <div style="text-align:center;margin-top:2.5rem;padding-bottom:2rem;">
      <a href="/search.php?<?= e(http_build_query(['q' => $q, 'type' => 'boards', 'page' => $page + 1])) ?>"
         class="btn btn--secondary" style="border-radius:var(--r-pill);">Load More</a>
    </div>
    <?php endif; ?>
    <?php endif; ?>

  <?php else: ?>
    <?php if (empty($users)): ?>
    <div class="empty-state">
      <div class="empty-state__icon"><span class="material-symbols-outlined" style="font-size:48px;opacity:.3;">person_off</span></div>
      <p class="empty-state__title"><?= e(__('search.no_people','No creators found')) ?></p>
      <p class="empty-state__sub"><?= e(__('search.no_people_sub','Try a different name or username.')) ?></p>
    </div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem;">
      <?php foreach ($users as $u): ?>
      <div style="background:#fff;border:0.5px solid var(--border);border-radius:var(--r-lg);padding:1.25rem;display:flex;flex-direction:column;align-items:center;text-align:center;gap:.5rem;transition:border-color .2s;" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
        <a href="/profile.php?u=<?= e($u['username']) ?>" style="text-decoration:none;">
          <?php if (!empty($u['avatar'])): ?>
            <img src="<?= e(imageUrl($u['avatar'])) ?>" alt="<?= e($u['username']) ?>"
                 class="avatar avatar--md" style="width:64px;height:64px;object-fit:cover;">
          <?php else: ?>
            <div class="avatar avatar--md" style="width:64px;height:64px;background:var(--surface);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.4rem;color:var(--muted);">
              <?= strtoupper(mb_substr($u['username'], 0, 1)) ?>
            </div>
          <?php endif; ?>
        </a>
        <a href="/profile.php?u=<?= e($u['username']) ?>" style="font-weight:700;text-decoration:none;color:inherit;">
          @<?= e($u['username']) ?>
        </a>
        <?php if (!empty($u['bio'])): ?>
        <p style="font-size:13px;color:var(--muted);margin:0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
          <?= e($u['bio']) ?>
        </p>
        <?php endif; ?>
        <span style="font-size:12px;color:var(--muted);"><?= formatCount((int)$u['board_count']) ?> board<?= (int)$u['board_count'] !== 1 ? 's' : '' ?></span>
        <?php if ($me && $me !== (int)$u['id']): ?>
        <form method="post" action="/search.php?<?= e(http_build_query(['q' => $q, 'type' => 'users'])) ?>">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="target_id" value="<?= (int)$u['id'] ?>">
          <?php $alreadyFollowing = $followingMap[$u['id']] ?? false; ?>
          <input type="hidden" name="action" value="<?= $alreadyFollowing ? 'unfollow' : 'follow' ?>">
          <button type="submit" class="btn btn--sm <?= $alreadyFollowing ? 'btn--secondary' : 'btn--primary' ?>"
                  style="border-radius:var(--r-pill);">
            <?= $alreadyFollowing ? e(__('search.unfollow','Unfollow')) : e(__('search.follow','Follow')) ?>
          </button>
        </form>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  <?php endif; ?>

  <?php else: ?>
  <div class="empty-state" style="padding:5rem 1rem;">
    <div class="empty-state__icon"><span class="material-symbols-outlined" style="font-size:56px;opacity:.25;">manage_search</span></div>
    <p class="empty-state__title"><?= e(__('search.default_title','Search boards & creators')) ?></p>
    <p class="empty-state__sub"><?= e(__('search.default_sub','Type a style, keyword, or creator name above.')) ?></p>
  </div>
  <?php endif; ?>

</div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
