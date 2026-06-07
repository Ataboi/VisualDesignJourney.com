<?php
/**
 * profile.php — Curator portfolio profile.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

session_start_if_not_started();

$db        = getDB();
$currentId = currentUserId();
$username  = trim($_GET['u'] ?? '');

if ($username === '') {
    header('Location: /index.php');
    exit;
}

$profileUser = getUserByUsername($db, $username);
if (!$profileUser) {
    header('Location: /404.php');
    exit;
}

$profileId    = (int)$profileUser['id'];
$isOwnProfile = ($currentId === $profileId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentId && !$isOwnProfile) {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'follow') {
        if (rateLimitCheck('follow-user-' . (int)$currentId, 30, 60 * 60)) {
            $stmt = $db->prepare('INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)');
            $stmt->execute([$currentId, $profileId]);
        }
    } elseif ($action === 'unfollow') {
        $stmt = $db->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?');
        $stmt->execute([$currentId, $profileId]);
    }

    header('Location: /profile.php?u=' . urlencode($username));
    exit;
}

$tab = $_GET['tab'] ?? 'boards';
if ($tab !== 'boards') $tab = 'boards';

$followerCount = getFollowerCount($db, $profileId);
$boardCount    = getBoardCount($db, $profileId);
$boards        = getUserBoards($db, $profileId, $isOwnProfile);
$totalBoards   = count($boards);

$isFollowing = false;
if ($currentId && !$isOwnProfile) {
    $isFollowing = isFollowing($db, $currentId, $profileId);
}

// --- Top movements from board style_tags ---
$topMovements = [];
try {
    $tagsStmt = $db->prepare('SELECT style_tags FROM boards WHERE user_id = ? AND is_draft = 0 AND style_tags != ""');
    $tagsStmt->execute([$profileId]);
    $allTags = $tagsStmt->fetchAll(PDO::FETCH_COLUMN);
    $tagCounts = [];
    foreach ($allTags as $raw) {
        foreach (array_map('trim', explode(',', $raw)) as $t) {
            if ($t) $tagCounts[$t] = ($tagCounts[$t] ?? 0) + 1;
        }
    }
    arsort($tagCounts);
    $topMovements = array_slice(array_keys($tagCounts), 0, 3);
} catch (Throwable $e) {}

// --- Featured board: most-liked published board ---
$featuredBoard = null;
try {
    $fbStmt = $db->prepare(
        'SELECT b.*, (SELECT COUNT(*) FROM board_likes bl WHERE bl.board_id = b.id) AS like_count
         FROM boards b WHERE b.user_id = ? AND b.is_draft = 0
         ORDER BY like_count DESC LIMIT 1'
    );
    $fbStmt->execute([$profileId]);
    $featuredBoard = $fbStmt->fetch() ?: null;
} catch (Throwable $e) {}

$featuredCover = null;
if ($featuredBoard) {
    $featuredCover = $featuredBoard['cover_image'] ?? null;
    if (!$featuredCover) {
        $imgStmt = $db->prepare('SELECT image_url FROM board_images WHERE board_id = ? ORDER BY sort_order ASC LIMIT 1');
        $imgStmt->execute([(int)$featuredBoard['id']]);
        $featuredCover = $imgStmt->fetchColumn() ?: null;
    }
}

$profileBio = localizedField($profileUser, 'bio');
$profileBioFallback = __('profile.bio_fallback', 'A visual curator building mood boards, references, and design inspiration collections.');

$relatedCurators = [];
if (!empty($topMovements)) {
    try {
        $tagClauses = [];
        $tagParams = [];
        foreach (array_slice($topMovements, 0, 3) as $movement) {
            $tagClauses[] = "FIND_IN_SET(?, REPLACE(b.style_tags, ', ', ',')) > 0";
            $tagParams[] = $movement;
        }
        $sql = 'SELECT u.*,
                       COUNT(DISTINCT b.id) AS board_count,
                       COUNT(DISTINCT f.id) AS follower_count
                FROM users u
                JOIN boards b ON b.user_id = u.id AND b.is_draft = 0
                LEFT JOIN follows f ON f.following_id = u.id
                WHERE u.id <> ? AND (' . implode(' OR ', $tagClauses) . ')
                GROUP BY u.id
                ORDER BY board_count DESC, follower_count DESC
                LIMIT 6';
        $relStmt = $db->prepare($sql);
        $relStmt->execute(array_merge([$profileId], $tagParams));
        $relatedCurators = $relStmt->fetchAll();
    } catch (Throwable $e) {
        $relatedCurators = [];
    }
}

// --- Website label ---
$websiteLabel = '';
if (!empty($profileUser['website'])) {
    $websiteLabel = preg_replace('#^https?://#', '', $profileUser['website']);
    $websiteLabel = rtrim($websiteLabel, '/');
}

// --- Editorial articles authored by this curator ---
$profileBlogPosts = [];
$profileBlogCount = 0;
try {
    $hasBlogI18n = dbColumnExists($db, 'blog_posts', 'title_tr') && dbColumnExists($db, 'blog_posts', 'content_tr');
    $blogLocalizedSelect = $hasBlogI18n
        ? 'title_tr, title_de, slug_tr, slug_de, excerpt_tr, excerpt_de, content_tr, content_de, seo_description_tr, seo_description_de,'
        : '"" AS title_tr, "" AS title_de, "" AS slug_tr, "" AS slug_de, "" AS excerpt_tr, "" AS excerpt_de, "" AS content_tr, "" AS content_de, "" AS seo_description_tr, "" AS seo_description_de,';
    $authorAliases = [$profileUser['username']];
    if (strcasecmp((string)$profileUser['username'], 'Ata') === 0 || strcasecmp((string)$profileUser['username'], 'AtaOzelbicer') === 0) {
        $authorAliases = ['AtaOzelbicer', 'Ata Ozelbicer', 'Ata Özelbiçer', 'Ata'];
    }
    $authorAliases = array_values(array_unique(array_filter($authorAliases)));
    $placeholders = implode(',', array_fill(0, count($authorAliases), '?'));

    $countStmt = $db->prepare("SELECT COUNT(*) FROM blog_posts WHERE author IN ($placeholders)");
    $countStmt->execute($authorAliases);
    $profileBlogCount = (int)$countStmt->fetchColumn();

    $blogStmt = $db->prepare(
        "SELECT id, title, slug, excerpt, content, {$blogLocalizedSelect} featured_image, author, published_at, seo_description
         FROM blog_posts
         WHERE author IN ($placeholders)
         ORDER BY published_at DESC
         LIMIT 6"
    );
    $blogStmt->execute($authorAliases);
    $profileBlogPosts = $blogStmt->fetchAll();
} catch (Throwable $e) {
    $profileBlogPosts = [];
    $profileBlogCount = 0;
}

// --- Movement chip colours ---
$movementColors = [
    'Bauhaus'       => '#d4002a',
    'Minimalism'    => '#6b7280',
    'Minimal'       => '#6b7280',
    'Art Nouveau'   => '#16a34a',
    'Swiss'         => '#2563eb',
    'Y2K'           => '#9333ea',
    'Brutalist'     => '#374151',
    'Dark Academia' => '#78350f',
    'Japandi'       => '#0e7490',
    'Maximalist'    => '#dc2626',
    'Art Deco'      => '#d97706',
];

// --- SEO meta ---
$metaDesc = $profileUser['username'] . "'s design portfolio on Visual Design Journey"
    . (!empty($topMovements) ? " — curating " . implode(', ', $topMovements) : '')
    . ". {$totalBoards} curated mood boards"
    . ($profileBlogCount > 0 ? " and {$profileBlogCount} editorial articles." : ".");

$pageTitle    = $profileUser['username'] . ' — Curator Portfolio — Visual Design Journey';
$activeNav    = 'curators';
$canonicalUrl = publicUrl(vdj_localized_url('/profile/' . rawurlencode($profileUser['username'])));
$metaImage    = null;
if (!empty($profileUser['avatar'])) {
    $metaImage = imageUrl($profileUser['avatar']);
} elseif ($featuredCover) {
    $metaImage = imageUrl($featuredCover);
}
$headExtra = '<script type="application/ld+json">' . json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ProfilePage',
    'name' => $profileUser['username'] . ' — Visual Design Journey',
    'description' => $metaDesc,
    'url' => $canonicalUrl,
    'mainEntity' => [
        '@type' => 'Person',
        'name' => $profileUser['username'],
        'description' => $profileBio !== '' ? strip_tags($profileBio) : $profileBioFallback,
        'image' => !empty($profileUser['avatar']) ? imageUrl($profileUser['avatar']) : null,
        'sameAs' => !empty($profileUser['website']) ? [$profileUser['website']] : [],
    ],
    'hasPart' => array_map(static function (array $article): array {
        return [
            '@type' => 'Article',
            'headline' => localizedField($article, 'title'),
            'url' => publicUrl(blogPostUrl($article)),
            'datePublished' => date('c', strtotime((string)$article['published_at'])),
            'author' => [
                '@type' => 'Person',
                'name' => $article['author'] ?? '',
                'url' => publicUrl(blogAuthorProfileUrl($article['author'] ?? '')),
            ],
        ];
    }, $profileBlogPosts),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-wrap profile-page-wrap" id="main-content">

  <!-- ===== EDITORIAL HERO ===== -->
  <section class="profile-portfolio-hero">
    <div class="profile-portfolio-hero__cover">
      <?php if ($featuredCover): ?>
        <img src="<?= e(thumbnailUrl($featuredCover, 1280, 720)) ?>" alt="" aria-hidden="true" decoding="async">
      <?php elseif (!empty($profileUser['avatar'])): ?>
        <img src="<?= e(imageUrl($profileUser['avatar'])) ?>" alt="" aria-hidden="true" decoding="async">
      <?php endif; ?>
    </div>

    <div class="container profile-portfolio-hero__inner">
      <div class="profile-identity-card">
        <!-- Avatar -->
        <div class="profile-avatar-xl">
          <?php if (!empty($profileUser['avatar'])): ?>
            <img src="<?= e(imageUrl($profileUser['avatar'])) ?>" alt="<?= e($profileUser['username']) ?> avatar" decoding="async">
          <?php else: ?>
            <span><?= e(strtoupper(substr($profileUser['username'], 0, 1))) ?></span>
          <?php endif; ?>
        </div>

        <!-- Name + bio (editorial focus) -->
        <div>
          <p class="profile-eyebrow">
            <?= !empty($profileUser['is_verified'])
                ? '<i class="fa-solid fa-circle-check"></i> Verified curator'
                : '<i class="fa-solid fa-layer-group"></i> Curator portfolio' ?>
          </p>

          <h1><?= e($profileUser['username']) ?></h1>

          <?php if ($profileBio !== ''): ?>
            <p class="profile-bio"><?= nl2br(e($profileBio)) ?></p>
          <?php else: ?>
            <p class="profile-bio"><?= e($profileBioFallback) ?></p>
          <?php endif; ?>

          <!-- Curates X boards · Focuses on Y, Z -->
          <p class="profile-curates-line">
            Curates <strong><?= formatCount($boardCount) ?> <?= $boardCount === 1 ? 'board' : 'boards' ?></strong>
            <?php if (!empty($topMovements)): ?>
              &nbsp;&middot;&nbsp; Focuses on <strong><?= e(implode(', ', $topMovements)) ?></strong>
            <?php endif; ?>
          </p>

          <!-- Secondary: follower count -->
          <p class="profile-followers-secondary">
            <a href="/followers.php?u=<?= urlencode($username) ?>&tab=followers">
              <?= formatCount($followerCount) ?> <?= $followerCount === 1 ? 'follower' : 'followers' ?>
            </a>
          </p>

          <!-- Links row -->
          <div class="profile-links">
            <?php if ($websiteLabel): ?>
              <a href="<?= e($profileUser['website']) ?>" target="_blank" rel="nofollow noopener noreferrer">
                <i class="fa-solid fa-globe"></i><?= e($websiteLabel) ?>
              </a>
            <?php endif; ?>
            <?php if (!empty($profileUser['location'])): ?>
              <span><i class="fa-solid fa-location-dot"></i><?= e($profileUser['location']) ?></span>
            <?php endif; ?>
            <span><i class="fa-regular fa-calendar"></i>Member since <?= e(date('M Y', strtotime($profileUser['created_at']))) ?></span>
          </div>
        </div>
      </div><!-- /.profile-identity-card -->

      <!-- Follow / Edit actions — visually secondary -->
      <div class="profile-actions-card profile-actions-card--secondary">
        <?php if ($isOwnProfile): ?>
          <a href="/settings.php" class="btn btn--secondary btn--sm"><i class="fa-solid fa-pen"></i>Edit portfolio</a>
          <a href="/create.php" class="btn btn--primary btn--sm"><i class="fa-solid fa-plus"></i>New board</a>
        <?php elseif ($currentId): ?>
          <form method="POST" action="/profile.php?u=<?= urlencode($username) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="<?= $isFollowing ? 'unfollow' : 'follow' ?>">
            <button type="submit" class="btn <?= $isFollowing ? 'btn--secondary' : 'btn--ghost' ?> btn--sm">
              <i class="fa-solid <?= $isFollowing ? 'fa-user-check' : 'fa-user-plus' ?>"></i>
              <?= $isFollowing ? 'Following' : 'Follow' ?>
            </button>
          </form>
        <?php else: ?>
          <a href="/login.php?next=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn--ghost btn--sm">
            <i class="fa-solid fa-user-plus"></i>Follow
          </a>
        <?php endif; ?>
      </div>
    </div><!-- /.profile-portfolio-hero__inner -->
  </section><!-- /.profile-portfolio-hero -->

  <div class="container profile-portfolio-wrap">

    <!-- ===== DESIGN MOVEMENT IDENTITY BAR ===== -->
    <?php if (!empty($topMovements)): ?>
      <section class="profile-movement-bar" aria-label="Design focus">
        <span class="profile-movement-bar__label">Design Focus</span>
        <div class="profile-movement-bar__chips">
          <?php foreach ($topMovements as $movement):
            $dotColor = $movementColors[$movement] ?? 'var(--accent)';
          ?>
            <a href="/index.php?tag=<?= urlencode($movement) ?>" class="movement-chip">
              <span class="movement-chip__dot" style="background:<?= e($dotColor) ?>"></span>
              <?= e($movement) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <!-- ===== STATS ROW (small, secondary) ===== -->
    <section class="profile-stats-row" aria-label="Curator statistics">
      <span><strong><?= formatCount($boardCount) ?></strong> <?= $boardCount === 1 ? 'Board' : 'Boards' ?></span>
      <span class="profile-stats-row__sep">&middot;</span>
      <a href="/followers.php?u=<?= urlencode($username) ?>&tab=followers">
        <strong><?= formatCount($followerCount) ?></strong> <?= $followerCount === 1 ? 'Follower' : 'Followers' ?>
      </a>
      <span class="profile-stats-row__sep">&middot;</span>
      <span>Member since <strong><?= e(date('M Y', strtotime($profileUser['created_at']))) ?></strong></span>
    </section>

    <!-- ===== FEATURED BOARD ===== -->
    <?php if ($featuredBoard): ?>
      <?php
        $featuredTitle = localizedBoardTitle($featuredBoard);
        $featuredDescription = localizedBoardDescription($featuredBoard);
        $featuredSaveCount = (int)($featuredBoard['save_count'] ?? getSaveCount($db, (int)$featuredBoard['id']));
        $featuredViewCount = (int)($featuredBoard['view_count'] ?? 0);
      ?>
      <section class="profile-featured" aria-label="Featured board">
        <a href="<?= e(boardUrl($featuredBoard)) ?>" class="profile-featured__media" aria-label="Open featured board <?= e($featuredTitle) ?>">
          <?php if ($featuredCover): ?>
            <img src="<?= e(thumbnailUrl($featuredCover, 760, 428)) ?>" alt="<?= e($featuredTitle) ?> cover" decoding="async" width="760" height="428">
          <?php else: ?>
            <span class="material-symbols-outlined">collections</span>
          <?php endif; ?>
        </a>
        <div class="profile-featured__body">
          <p class="profile-section-kicker"><?= e(__('profile.featured_board', 'Featured Board')) ?></p>
          <h2><a href="<?= e(boardUrl($featuredBoard)) ?>"><?= e($featuredTitle) ?></a></h2>
          <?php if ($featuredDescription !== ''): ?>
            <p><?= e(mb_substr(strip_tags($featuredDescription), 0, 220)) ?></p>
          <?php endif; ?>
          <div class="profile-featured__meta">
            <span><i class="fa-solid fa-heart"></i><?= formatCount((int)($featuredBoard['like_count'] ?? 0)) ?> likes</span>
            <span><i class="fa-regular fa-bookmark"></i><?= formatCount($featuredSaveCount) ?> saves</span>
            <?php if ($featuredViewCount > 0): ?><span><i class="fa-regular fa-eye"></i><?= formatCount($featuredViewCount) ?> views</span><?php endif; ?>
            <span><i class="fa-regular fa-clock"></i><?= e(timeAgo($featuredBoard['created_at'])) ?></span>
          </div>
          <a href="<?= e(boardUrl($featuredBoard)) ?>" class="btn btn--primary btn--sm"><?= e(__('profile.view_board', 'View board')) ?></a>
        </div>
      </section>
    <?php endif; ?>

    <?php if (!empty($profileBlogPosts)): ?>
      <section class="profile-editorial" aria-label="<?= e(__('profile.editorial_articles', 'Editorial Articles')) ?>">
        <div class="profile-boards-header">
          <div>
            <p class="profile-section-kicker"><?= e(__('profile.editorial_articles', 'Editorial Articles')) ?></p>
            <h2 class="profile-boards-title"><?= e(__('profile.editorial_articles', 'Editorial Articles')) ?> (<?= formatCount($profileBlogCount) ?>)</h2>
            <p class="profile-editorial__sub"><?= e(__('profile.editorial_articles_sub', 'Blog articles published by this curator for Visual Design Journey.')) ?></p>
          </div>
        </div>
        <div class="profile-editorial-grid">
          <?php foreach ($profileBlogPosts as $article):
            $articleTitle = localizedField($article, 'title');
            $articleTranslation = blogTranslationStatus($article, vdj_current_language());
            $articleExcerpt = (vdj_current_language() !== 'en' && !$articleTranslation['is_translated'])
                ? cleanBlogExcerpt($articleTitle, (string)($article['excerpt_' . vdj_current_language()] ?? ''), (string)($article['content_' . vdj_current_language()] ?? ''), vdj_current_language(), 130)
                : cleanBlogExcerpt($articleTitle, localizedField($article, 'excerpt'), localizedField($article, 'content'), vdj_current_language(), 130);
          ?>
          <article class="profile-editorial-card">
            <a href="<?= e(blogPostUrl($article)) ?>" class="profile-editorial-card__media" aria-label="<?= e(__('blog.read_article', 'Read article')) ?>: <?= e($articleTitle) ?>">
              <?php if (!empty($article['featured_image'])): ?>
                <img src="<?= e(blogThumbnailUrl($article['featured_image'], 420, 236)) ?>" alt="<?= e($articleTitle) ?>" loading="lazy" decoding="async" width="420" height="236">
              <?php else: ?>
                <i class="fa-solid fa-pen-nib" aria-hidden="true"></i>
              <?php endif; ?>
            </a>
            <div class="profile-editorial-card__body">
              <h3><a href="<?= e(blogPostUrl($article)) ?>"><?= e($articleTitle) ?></a></h3>
              <?php if ($articleExcerpt !== ''): ?><p><?= e($articleExcerpt) ?></p><?php endif; ?>
              <div class="profile-editorial-card__meta">
                <span><i class="fa-regular fa-calendar"></i><?= e(vdjLocalizedDate($article['published_at'])) ?></span>
                <a href="<?= e(blogPostUrl($article)) ?>"><?= e(__('blog.read', 'Read')) ?> <i class="fa-solid fa-arrow-right"></i></a>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php elseif ($profileBlogCount === 0 && strcasecmp((string)$profileUser['username'], 'Ata') === 0): ?>
      <section class="profile-editorial profile-editorial--empty" aria-label="<?= e(__('profile.editorial_articles', 'Editorial Articles')) ?>">
        <p class="profile-section-kicker"><?= e(__('profile.editorial_articles', 'Editorial Articles')) ?></p>
        <p class="profile-editorial__sub"><?= e(__('profile.no_articles', 'No published blog articles yet.')) ?></p>
      </section>
    <?php endif; ?>

    <!-- ===== TAB NAVIGATION ===== -->
    <nav style="display:flex;gap:32px;border-bottom:0.5px solid var(--border);margin-bottom:2.5rem;" aria-label="Profile tabs">
      <?php foreach (['boards' => 'Boards', 'liked' => 'Liked', 'following' => 'Following'] as $tKey => $tLabel):
        $tActive = ($tab === $tKey) || ($tKey === 'boards' && !in_array($tab, ['liked','following']));
      ?>
      <?php if ($tKey === 'boards'): ?>
      <a href="/profile.php?u=<?= urlencode($username) ?>"
         style="padding-bottom:16px;text-decoration:none;font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;position:relative;color:<?= $tActive ? 'var(--accent)' : 'var(--muted)' ?>;">
        <?= $tLabel ?>
        <?php if ($tActive): ?>
          <span style="position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:5px;height:5px;background:var(--accent);border-radius:50%;"></span>
        <?php endif; ?>
      </a>
      <?php else: ?>
      <a href="/profile.php?u=<?= urlencode($username) ?>&tab=<?= $tKey ?>"
         style="padding-bottom:16px;text-decoration:none;font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);transition:color .2s;"
         onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--muted)'">
        <?= $tLabel ?>
      </a>
      <?php endif; ?>
      <?php endforeach; ?>
    </nav>

    <!-- ===== BOARDS GRID ===== -->
    <div class="profile-boards-header">
      <h2 class="profile-boards-title"><?= e(__('profile.all_boards', 'All Boards')) ?><?= $totalBoards > 0 ? ' (' . formatCount($totalBoards) . ')' : '' ?></h2>
    </div>

    <?php
    // Exclude featured board from grid to avoid duplication
    $gridBoards = $boards;
    if ($featuredBoard) {
        $featuredId = (int)$featuredBoard['id'];
        $gridBoards = array_filter($boards, fn($b) => (int)$b['id'] !== $featuredId);
    }
    ?>

    <?php if (empty($boards)): ?>
      <div class="empty-state profile-empty">
        <i class="fa-regular fa-images"></i>
        <p class="empty-state__title"><?= e(__('profile.empty_boards', "This curator hasn't published any boards yet.")) ?></p>
        <?php if ($isOwnProfile): ?>
          <a href="/create.php" class="btn btn--primary"><?= e(__('profile.create_first', 'Create your first board')) ?></a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="masonry" style="columns:3;" role="list" aria-label="Portfolio boards">
        <?php foreach ($gridBoards as $board):
          $tags  = parseTags($board['style_tags'] ?? '');
          $gridTitle = localizedBoardTitle($board);
          $gridSaveCount = (int)($board['save_count'] ?? getSaveCount($db, (int)$board['id']));
          $gridViewCount = (int)($board['view_count'] ?? 0);
          $cover = $board['cover_image'] ?? null;
          if (!$cover) {
              $firstImg = $db->prepare('SELECT image_url FROM board_images WHERE board_id = ? ORDER BY sort_order ASC LIMIT 1');
              $firstImg->execute([(int)$board['id']]);
              $cover = $firstImg->fetchColumn() ?: null;
          }
        ?>
        <article class="board-card" role="listitem" style="break-inside:avoid;margin-bottom:var(--gutter,24px);">
          <a href="<?= e(boardUrl($board)) ?>" style="display:block;overflow:hidden;border-radius:var(--r-lg) var(--r-lg) 0 0;">
            <?php if ($cover): ?>
              <div class="board-card__img-wrap">
                <div class="board-card__skeleton skeleton"></div>
                <img src="<?= e(thumbnailUrl($cover, 640, 480)) ?>"
                     alt="<?= e($gridTitle) ?> cover"
                     class="board-card__img"
                     loading="lazy"
                     decoding="async"
                     width="640"
                     height="480"
                     onload="this.classList.add('loaded');this.previousElementSibling.style.display='none'">
              </div>
            <?php else: ?>
              <div class="board-card__img--placeholder">
                <span class="material-symbols-outlined" style="font-size:32px;color:var(--border);">image</span>
              </div>
            <?php endif; ?>
          </a>
          <div class="board-card__body">
            <h3 class="board-card__title">
              <a href="<?= e(boardUrl($board)) ?>"><?= e($gridTitle) ?></a>
            </h3>
            <div class="board-card__meta">
              <span style="display:flex;align-items:center;gap:4px;font-size:12px;color:var(--muted);">
                <span class="material-symbols-outlined" style="font-size:16px;">favorite</span>
                <?= formatCount((int)($board['like_count'] ?? 0)) ?>
              </span>
              <?php if ($isOwnProfile): ?>
                <?php if (!empty($board['is_draft'])): ?>
                  <span class="tag" style="font-size:10px;">Draft</span>
                <?php endif; ?>
                <a href="/edit-board.php?id=<?= (int)$board['id'] ?>" class="btn btn--sm btn--secondary" style="border-radius:var(--r-pill);padding:3px 10px;font-size:11px;margin-left:auto;" aria-label="Edit <?= e($gridTitle) ?>">Edit</a>
              <?php endif; ?>
            </div>
            <div class="board-card__stats" aria-label="Board activity">
              <span><i class="fa-regular fa-heart"></i><?= formatCount((int)($board['like_count'] ?? 0)) ?></span>
              <span><i class="fa-regular fa-bookmark"></i><?= formatCount($gridSaveCount) ?></span>
              <?php if ($gridViewCount > 0): ?><span><i class="fa-regular fa-eye"></i><?= formatCount($gridViewCount) ?></span><?php endif; ?>
            </div>
            <?php if ($tags): ?>
            <div class="board-card__tags">
              <?php foreach (array_slice($tags, 0, 3) as $tagName): ?>
                <a href="/index.php?tag=<?= urlencode($tagName) ?>" class="tag"><?= e($tagName) ?></a>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($relatedCurators)): ?>
      <section class="profile-related-curators" aria-label="Related curators">
        <div class="profile-boards-header">
          <h2 class="profile-boards-title"><?= e(__('profile.related_curators', 'Related Curators')) ?></h2>
        </div>
        <div class="profile-related-grid">
          <?php foreach ($relatedCurators as $rc):
            $rcBio = localizedField($rc, 'bio');
            $rcFocus = userTopStyleTags($db, (int)$rc['id'], 3);
          ?>
          <a href="<?= e(vdj_localized_url('/profile/' . rawurlencode($rc['username']))) ?>" class="profile-related-card">
            <?php if (!empty($rc['avatar'])): ?>
              <img src="<?= e(imageUrl($rc['avatar'])) ?>" alt="<?= e($rc['username']) ?> avatar" loading="lazy" decoding="async">
            <?php else: ?>
              <span class="profile-related-card__initial"><?= e(strtoupper(substr($rc['username'], 0, 1))) ?></span>
            <?php endif; ?>
            <strong>@<?= e($rc['username']) ?></strong>
            <?php if ($rcBio !== ''): ?><p><?= e(mb_substr(strip_tags($rcBio), 0, 95)) ?></p><?php endif; ?>
            <span><?= formatCount((int)$rc['board_count']) ?> boards · <?= formatCount((int)$rc['follower_count']) ?> followers</span>
            <?php if ($rcFocus): ?>
              <em><?= e(implode(' · ', $rcFocus)) ?></em>
            <?php endif; ?>
          </a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

  </div><!-- /.container.profile-portfolio-wrap -->
</main>

<style>
.profile-editorial { margin: 42px 0 38px; padding-top: 28px; border-top: 1px solid var(--border); }
.profile-editorial--empty { border: 1px solid var(--border); border-radius: var(--r-lg); padding: 22px; background: var(--bg); }
.profile-editorial__sub { margin: 6px 0 0; color: var(--muted); font-size: 13px; line-height: 1.55; }
.profile-editorial-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:16px; }
.profile-editorial-card { border:1px solid var(--border); border-radius:var(--r-lg); background:var(--bg); overflow:hidden; display:flex; flex-direction:column; min-width:0; transition:border-color .18s, transform .18s, box-shadow .18s; }
.profile-editorial-card:hover { border-color:var(--accent); transform:translateY(-2px); box-shadow:0 10px 26px rgba(0,0,0,.08); }
.profile-editorial-card__media { aspect-ratio:16/9; background:var(--surface); display:flex; align-items:center; justify-content:center; color:var(--muted); text-decoration:none; overflow:hidden; }
.profile-editorial-card__media img { width:100%; height:100%; object-fit:cover; }
.profile-editorial-card__media i { font-size:28px; opacity:.35; }
.profile-editorial-card__body { padding:15px; display:flex; flex-direction:column; gap:8px; flex:1; }
.profile-editorial-card h3 { margin:0; font-size:15px; line-height:1.35; }
.profile-editorial-card h3 a { color:var(--text); text-decoration:none; }
.profile-editorial-card h3 a:hover { color:var(--accent); }
.profile-editorial-card p { margin:0; color:var(--muted); font-size:12px; line-height:1.5; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
.profile-editorial-card__meta { margin-top:auto; display:flex; align-items:center; gap:12px; flex-wrap:wrap; color:var(--muted); font-size:11px; font-weight:700; }
.profile-editorial-card__meta i { margin-right:4px; }
.profile-editorial-card__meta a { margin-left:auto; color:var(--accent); text-decoration:none; }
.profile-related-curators { margin-top: 48px; padding-top: 28px; border-top: 1px solid var(--border); }
.profile-related-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:14px; }
.profile-related-card { border:1px solid var(--border); border-radius:var(--r-lg); background:var(--bg); padding:16px; color:inherit; text-decoration:none; display:flex; flex-direction:column; gap:8px; transition:border-color .18s, transform .18s, box-shadow .18s; }
.profile-related-card:hover { border-color:var(--accent); transform:translateY(-2px); box-shadow:0 10px 26px rgba(0,0,0,.08); }
.profile-related-card img,.profile-related-card__initial { width:48px; height:48px; border-radius:50%; object-fit:cover; background:var(--surface); display:flex; align-items:center; justify-content:center; font-weight:800; color:var(--muted); }
.profile-related-card p { margin:0; color:var(--muted); font-size:12px; line-height:1.45; }
.profile-related-card span,.profile-related-card em { color:var(--muted); font-size:11px; font-style:normal; font-weight:700; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
