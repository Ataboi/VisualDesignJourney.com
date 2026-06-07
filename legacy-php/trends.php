<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();

$activeTag = trim($_GET['tag'] ?? '');

$tagWhere  = $activeTag !== '' ? 'AND FIND_IN_SET(:tag, REPLACE(b.style_tags, \', \', \',\')) > 0' : '';
$tagParams = $activeTag !== '' ? [':tag' => $activeTag] : [];

$sql = "SELECT b.*, u.username, u.avatar, COUNT(bl.id) AS like_count
        FROM boards b
        JOIN users u ON u.id = b.user_id
        LEFT JOIN board_likes bl ON bl.board_id = b.id
        WHERE b.is_draft = 0 $tagWhere
        GROUP BY b.id
        ORDER BY like_count DESC
        LIMIT 30";

$stmt = $pdo->prepare($sql);
$stmt->execute($tagParams);
$boards = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hero      = !empty($boards) ? array_shift($boards) : null;
$remaining = $boards;

$tagStmt = $pdo->query(
    'SELECT DISTINCT TRIM(t.tag) AS tag
     FROM boards b
     JOIN (SELECT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(b2.style_tags, \',\', n.n), \',\', -1)) AS tag
           FROM boards b2
           JOIN (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) n
             ON CHAR_LENGTH(b2.style_tags) - CHAR_LENGTH(REPLACE(b2.style_tags, \',\', \'\')) >= n.n - 1
          ) t ON 1=1
     WHERE b.is_draft = 0 AND t.tag != \'\'
     ORDER BY tag ASC
     LIMIT 30'
);
$allTags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle    = $activeTag !== ''
    ? sprintf(__('trends.page_title_tag', '%s Trend — Visual Design Journey'), htmlspecialchars($activeTag))
    : __('trends.page_title', 'Trending Now — Visual Design Journey');
$metaDesc     = $activeTag !== ''
    ? sprintf(__('trends.meta_tag', 'Browse the most-loved %s mood boards on Visual Design Journey.'), htmlspecialchars($activeTag))
    : __('trends.meta_desc', 'The most-liked mood boards from the Visual Design Journey design community — curated by the best visual designers.');
$canonicalUrl = publicUrl('/trends' . ($activeTag !== '' ? '?tag=' . urlencode($activeTag) : ''));
$activeNav    = 'trends';

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-wrap" id="main-content">
<div class="container">

  <div style="text-align:center;margin-bottom:2rem;">
    <h1 class="t-headline-md" style="margin:0 0 .5rem;"><?= e(__('trends.hero', 'Trending Now')) ?></h1>
    <p class="t-body-md text-muted"><?= e(__('trends.sub', 'The most-loved boards from the community.')) ?></p>
  </div>

  <?php if (!empty($allTags)): ?>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:2rem;justify-content:center;">
    <a href="<?= e(vdj_localized_url('/trends')) ?>" class="tag <?= $activeTag === '' ? 'tag--active tag--accent' : '' ?>" style="text-decoration:none;">
      <?= e(__('trends.all', 'All')) ?>
    </a>
    <?php foreach ($allTags as $tag): ?>
    <a href="/trends.php?tag=<?= urlencode($tag) ?>"
       class="tag <?= $activeTag === $tag ? 'tag--active tag--accent' : '' ?>"
       style="text-decoration:none;">
      <?= e($tag) ?>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!$hero): ?>
  <div style="text-align:center;padding:4rem 1rem;color:var(--muted);">
    <div style="font-size:2.5rem;margin-bottom:.75rem;">📭</div>
    <p class="t-body-md"><?= e($activeTag !== '' ? __('trends.empty_tag', 'No trending boards for this tag yet.') : __('trends.empty', 'No trending boards yet.')) ?></p>
  </div>
  <?php else: ?>

  <?php $heroLiked = currentUserId() ? hasLiked($pdo, (int)$hero['id'], currentUserId()) : false; ?>
  <section style="margin-bottom:3rem;" aria-label="This week's top pick">
    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;">
      <span style="font-size:1rem;" aria-hidden="true">🔥</span>
      <h2 style="margin:0;font-size:1.05rem;font-weight:700;"><?= e(__('trends.top_pick', "This Week's Top Pick")) ?></h2>
    </div>
    <a href="/board.php?id=<?= (int)$hero['id'] ?>"
       aria-label="View top board: <?= e($hero['title']) ?>"
       style="display:block;text-decoration:none;border-radius:var(--r-lg);overflow:hidden;position:relative;background:#111;">
      <?php if (!empty($hero['cover_image'])): ?>
      <img src="<?= e(imageUrl($hero['cover_image'])) ?>"
           alt="Cover image for <?= e($hero['title']) ?> by <?= e($hero['username']) ?>"
           style="width:100%;max-height:480px;object-fit:cover;display:block;opacity:.75;"
           loading="eager"
           fetchpriority="high">
      <?php else: ?>
      <div style="width:100%;height:320px;background:linear-gradient(135deg,var(--accent),#b8b3f0);" role="img" aria-label="No cover image"></div>
      <?php endif; ?>
      <div style="position:absolute;bottom:0;left:0;right:0;padding:2rem 1.5rem;background:linear-gradient(transparent,rgba(0,0,0,.75));">
        <h3 style="margin:0 0 .5rem;color:#fff;font-size:1.75rem;font-weight:800;line-height:1.2;">
          <?= e($hero['title']) ?>
        </h3>
        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
          <a href="/profile.php?u=<?= urlencode($hero['username']) ?>"
             onclick="event.stopPropagation();"
             aria-label="View profile of <?= e($hero['username']) ?>"
             style="display:flex;align-items:center;gap:.4rem;color:rgba(255,255,255,.85);text-decoration:none;font-size:.9rem;font-weight:600;">
            <?php if (!empty($hero['avatar'])): ?>
              <img src="<?= e(imageUrl($hero['avatar'])) ?>"
                   alt="<?= e($hero['username']) ?>'s avatar"
                   style="width:24px;height:24px;border-radius:50%;object-fit:cover;">
            <?php else: ?>
              <span style="width:24px;height:24px;border-radius:50%;background:rgba(255,255,255,.2);display:inline-flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;"
                    role="img" aria-label="<?= e($hero['username']) ?>'s avatar">
                <?= strtoupper(substr($hero['username'], 0, 1)) ?>
              </span>
            <?php endif; ?>
            @<?= e($hero['username']) ?>
          </a>
          <button class="like-btn"
                  data-board="<?= (int)$hero['id'] ?>"
                  data-liked="<?= $heroLiked ? '1' : '0' ?>"
                  aria-label="<?= $heroLiked ? 'Unlike' : 'Like' ?> «<?= e($hero['title']) ?>» (<?= formatCount((int)$hero['like_count']) ?> likes)"
                  onclick="event.stopPropagation();"
                  style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);border-radius:var(--r-pill);color:#fff;cursor:pointer;display:flex;align-items:center;gap:5px;padding:4px 10px;font-size:.85rem;">
            <span class="material-symbols-outlined <?= $heroLiked ? 'icon-filled' : '' ?>"
                  aria-hidden="true"
                  style="font-size:15px;">favorite</span>
            <span class="like-count" aria-live="polite"><?= formatCount((int)$hero['like_count']) ?></span>
          </button>
        </div>
      </div>
    </a>
  </section>

  <?php if (!empty($remaining)): ?>
  <section>
    <h2 style="margin:0 0 1.25rem;font-size:1.05rem;font-weight:700;"><?= e(__('trends.more', 'More Trending')) ?></h2>
    <div class="masonry" role="list" aria-label="Trending boards">
      <?php foreach ($remaining as $board):
        $liked = currentUserId() ? hasLiked($pdo, (int)$board['id'], currentUserId()) : false;
      ?>
      <article class="board-card" role="listitem" aria-label="<?= e($board['title']) ?>">
        <?php if (!empty($board['cover_image'])): ?>
        <a href="/board.php?id=<?= (int)$board['id'] ?>"
           aria-label="View board: <?= e($board['title']) ?>">
          <div class="board-card__img-wrap">
            <div class="board-card__skeleton skeleton"></div>
            <img src="<?= e(imageUrl($board['cover_image'])) ?>"
                 alt="Cover image for <?= e($board['title']) ?> by <?= e($board['username']) ?>"
                 class="board-card__img"
                 loading="lazy"
                 onload="this.classList.add('loaded');this.previousElementSibling.style.display='none'"/>
          </div>
        </a>
        <?php else: ?>
        <div class="board-card__img--placeholder" role="img" aria-label="No cover image for <?= e($board['title']) ?>">
          <i class="fa-regular fa-image" style="font-size:32px;color:var(--border);"></i>
        </div>
        <?php endif; ?>
        <div class="board-card__body">
          <h3 class="board-card__title">
            <a href="/board.php?id=<?= (int)$board['id'] ?>"><?= e($board['title']) ?></a>
          </h3>
          <div class="board-card__meta">
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
          </div>
          <?php if (!empty($board['style_tags'])): ?>
          <div class="board-card__tags">
            <?php foreach (array_slice(parseTags($board['style_tags']), 0, 3) as $tag): ?>
            <a href="/trends.php?tag=<?= urlencode($tag) ?>" class="tag"><?= e($tag) ?></a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
  <?php endif; ?>

</div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
