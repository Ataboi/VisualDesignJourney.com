<?php
/**
 * index.php — Main feed / Explore page
 * Matches: visual_design_journey_main_feed_1/2/3
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/data/designers.php';

session_start_if_not_started();

$activeNav = 'explore';
// pageTitle and metaDesc use translations — set after i18n bootstrap (via functions.php)
$pageTitle = __('nav.explore', 'Explore') . ' — Visual Design Journey';
$metaDesc  = __('explore.meta_desc', 'Discover mood boards curated by the world\'s best visual designers.');

$db     = getDB();
$boardI18nReady = dbColumnExists($db, 'boards', 'title_tr');
$hasSaveCount = dbColumnExists($db, 'boards', 'save_count');
$hasViewCount = dbColumnExists($db, 'boards', 'view_count');
$page   = max(1, (int)($_GET['page'] ?? 1));
$tag    = trim($_GET['tag']    ?? '');
$search = trim($_GET['q']      ?? '');
$sort   = in_array($_GET['sort'] ?? '', ['newest','popular','trending']) ? $_GET['sort'] : 'newest';

// Build boards query with sort support
$sortClause = match($sort) {
    'popular'  => 'ORDER BY like_count DESC',
    'trending' => 'ORDER BY (like_count / (DATEDIFF(NOW(), b.created_at) + 1)) DESC',
    default    => 'ORDER BY b.created_at DESC',
};
$where2  = ['b.is_draft = 0'];
$params2 = [];
if ($tag !== '') {
    $where2[]  = 'FIND_IN_SET(?, REPLACE(b.style_tags, \', \', \',\')) > 0';
    $params2[] = $tag;
}
if ($search !== '') {
    $where2[]  = $boardI18nReady
        ? '(b.title LIKE ? OR b.title_tr LIKE ? OR b.title_de LIKE ? OR b.description LIKE ? OR b.description_tr LIKE ? OR b.description_de LIKE ? OR u.username LIKE ?)'
        : '(b.title LIKE ? OR b.description LIKE ? OR u.username LIKE ?)';
    $searchLike = "%$search%";
    $params2 = array_merge($params2, $boardI18nReady
        ? [$searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike]
        : [$searchLike, $searchLike, $searchLike]);
}
$whereSQL2 = implode(' AND ', $where2);
$perPage = 12;
$params2[] = $perPage;
$params2[] = ($page - 1) * $perPage;
$boardsStmt = $db->prepare(
    "SELECT b.*, u.username, u.avatar,
            (SELECT COUNT(*) FROM board_likes bl WHERE bl.board_id = b.id) AS like_count,
            " . ($hasSaveCount ? 'b.save_count' : '0') . " AS save_count,
            " . ($hasViewCount ? 'b.view_count' : '0') . " AS view_count
     FROM boards b
     JOIN users u ON u.id = b.user_id
     WHERE $whereSQL2
     $sortClause
     LIMIT ? OFFSET ?"
);
$boardsStmt->execute($params2);
$boards = $boardsStmt->fetchAll();

// Style filter tags
$styleTags = [
    '' => 'All', 'Minimal' => 'Minimal', 'Bauhaus' => 'Bauhaus',
    'Y2K' => 'Y2K', 'Art Nouveau' => 'Art Nouveau', 'Brutalist' => 'Brutalist',
    'Dark Academia' => 'Dark Academia',
    'Japandi' => 'Japandi', 'Maximalist' => 'Maximalist',
];

function vdjStyleLabel(string $label): string {
    if ($label === '' || strtolower($label) === 'all') {
        return __('style.all', 'All');
    }
    return vdj_movement_label($label);
}

function vdjDesignerCardDescription(array $designer, ?array $post): string {
    $lang = vdj_current_language();
    if ($post) {
        $localized = trim(strip_tags(localizedField($post, 'seo_description')));
        if ($localized === '') {
            $localized = trim(strip_tags(localizedField($post, 'excerpt')));
        }
        if ($lang === 'en' && $localized !== '') {
            return $localized;
        }
        if ($lang !== 'en') {
            $strict = trim(strip_tags((string)($post['seo_description_' . $lang] ?? '')));
            if ($strict === '') $strict = trim(strip_tags((string)($post['excerpt_' . $lang] ?? '')));
            if ($strict !== '') return $strict;
        }
    }

    $movement = vdj_movement_label($designer['movement'] ?? '');
    if ($lang === 'tr') {
        return sprintf('%s, %s yaklaşımıyla görsel kültürü şekillendiren önemli bir tasarım referansıdır.', $designer['name'], $movement);
    }
    if ($lang === 'de') {
        return sprintf('%s ist eine wichtige Designreferenz, die visuelle Kultur durch %s geprägt hat.', $designer['name'], $movement);
    }
    return sprintf('%s shaped visual culture through %s.', $designer['name'], $designer['movement']);
}

function vdjDesignerKey(string $value): string {
    if (function_exists('iconv')) {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    }
    $value = strtolower($value);
    $value = preg_replace('/\([^)]*\)/', '', $value);
    $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
    return trim(preg_replace('/\s+/', ' ', $value));
}

function vdjDesignerSlug(string $value): string {
    return str_replace(' ', '-', vdjDesignerKey($value));
}

function vdjDesignerTerms(string $name): array {
    $key = vdjDesignerKey($name);
    $aliases = [
        'josef muller brockmann' => ['josef mueller brockmann', 'muller brockmann', 'mueller brockmann', 'brockmann'],
        'laszlo moholy nagy' => ['lazslo moholy nagy', 'moholy nagy', 'moholy'],
        'alexey brodovitch' => ['alexei brodovitch', 'alexey brodovitch', 'brodovitch'],
        'alexei brodovitch' => ['alexey brodovitch', 'alexei brodovitch', 'brodovitch'],
        'wolfgang weingart' => ['weingart'],
        'jan tschichold' => ['tschichold'],
        'el lissitzky' => ['lissitzky', 'el lisitsky'],
        'wim crouwel' => ['crouwel'],
        'armin hofmann' => ['hofmann'],
        'massimo vignelli' => ['vignelli'],
        'paul rand' => ['rand'],
        'saul bass' => ['bass'],
        'paula scher' => ['scher'],
        'stefan sagmeister' => ['sagmeister'],
        'david carson' => ['carson'],
        'neville brody' => ['brody', 'fontshop'],
        'the designers republic' => ['designers republic', 'thedesignersrepublic'],
        'kesselskramer' => ['kessels kramer'],
        'experimental jetset' => ['experimental jetset'],
        'bibliotheque' => ['bibliotheque', 'bibliothèque'],
        'eike konig' => ['eike koenig', 'konig', 'koenig'],
    ];

    $terms = [$key];
    if (isset($aliases[$key])) {
        $terms = array_merge($terms, $aliases[$key]);
    }

    $tokens = array_values(array_filter(explode(' ', $key), fn($token) => strlen($token) >= 4));
    if (count($tokens) >= 2) {
        $terms[] = $tokens[0] . ' ' . end($tokens);
        $terms[] = end($tokens);
    } elseif (count($tokens) === 1) {
        $terms[] = $tokens[0];
    }

    return array_values(array_unique(array_filter($terms)));
}

$designerBlogPosts = [];
try {
    $db->query('SELECT 1 FROM blog_posts LIMIT 1');
    $blogRows = $db->query(
        'SELECT title, title_tr, title_de, slug, slug_tr, slug_de, featured_image,
                excerpt, excerpt_tr, excerpt_de,
                seo_description, seo_description_tr, seo_description_de, published_at
         FROM blog_posts
         ORDER BY published_at DESC'
    )->fetchAll();

    foreach ($top100Designers as $designer) {
        $nameKey = vdjDesignerKey($designer['name']);
        if ($nameKey === '') continue;

        $terms = vdjDesignerTerms($designer['name']);
        $bestPost = null;
        $bestScore = 0;

        foreach ($blogRows as $post) {
            $titleKey = vdjDesignerKey($post['title']);
            $slugKey = vdjDesignerKey($post['slug']);
            $haystack = trim($titleKey . ' ' . $slugKey);
            if ($haystack === '') continue;

            $score = 0;
            foreach ($terms as $term) {
                $termKey = vdjDesignerKey($term);
                if ($termKey === '') continue;
                if (str_contains($haystack, $termKey)) {
                    $score += 120 + (strlen($termKey) * 2);
                }
            }

            $nameTokens = array_values(array_filter(explode(' ', $nameKey), fn($token) => strlen($token) >= 4));
            foreach ($nameTokens as $token) {
                if (str_contains($haystack, $token)) $score += 22;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestPost = $post;
            }
        }

        if ($bestPost && $bestScore >= 44) {
            $designerBlogPosts[$designer['name']] = $bestPost;
        }
    }
} catch (Throwable $e) {
    $designerBlogPosts = [];
}

$headExtra = $headExtra ?? '';
if ($page === 1 && $tag === '' && $search === '' && !empty($boards[0])) {
    $lcpCover = $boards[0]['cover_image'] ?? null;
    if (!$lcpCover) {
        try {
            $lcpImgStmt = $db->prepare('SELECT image_url FROM board_images WHERE board_id = ? ORDER BY sort_order ASC LIMIT 1');
            $lcpImgStmt->execute([(int)$boards[0]['id']]);
            $lcpCover = $lcpImgStmt->fetchColumn() ?: null;
        } catch (Throwable $e) {
            $lcpCover = null;
        }
    }
    if ($lcpCover) {
        $lcpThumb = thumbnailUrl($lcpCover, 640, 480);
        $headExtra .= '<link rel="preload" as="image" fetchpriority="high" href="' . e($lcpThumb) . '">' . "\n";
        $metaImage = $metaImage ?? $lcpThumb;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="page-wrap" id="main-content">
<div class="container">

  <!-- Design Masters slider -->
  <?php if (!empty($top100Designers)): ?>
  <section class="designers-section designers-section--top" aria-label="Design masters">
    <div class="designers-section__header">
      <div>
        <h2 style="font-family:var(--font-display);font-size:1.25rem;font-weight:700;margin:0 0 4px;">
          <?= e(__('explore.design_masters', 'Design Masters')) ?>
        </h2>
        <p style="font-size:13px;color:var(--muted);margin:0;"><?= e(__('explore.designers_sub', '100 designers who shaped visual culture')) ?></p>
      </div>
      <a href="<?= e(vdj_localized_url('/blog')) ?>" class="btn btn--secondary btn--sm" style="border-radius:var(--r-pill);">
        <?= e(__('explore.explore_articles', 'Explore articles')) ?> &rarr;
      </a>
    </div>
    <div class="designers-slider-wrap" role="list" aria-label="Design masters"
         style="display:flex;gap:16px;overflow-x:auto;padding-bottom:16px;scrollbar-width:thin;">
      <?php foreach ($top100Designers as $d):
        $matchedPost = $designerBlogPosts[$d['name']] ?? null;
        $cardUrl = $matchedPost ? blogPostUrl($matchedPost) : vdj_localized_url('/blog') . '?q=' . urlencode($d['name']);
        $cardImage = $matchedPost['featured_image'] ?? '';
        $cardDesc = vdjDesignerCardDescription($d, $matchedPost);
        if (mb_strlen($cardDesc) > 86) $cardDesc = mb_substr($cardDesc, 0, 86) . '…';
      ?>
      <a href="<?= e($cardUrl) ?>"
         class="designer-card"
         role="listitem"
         aria-label="<?= e($d['name']) ?><?= $matchedPost ? ' blog article' : ' blog search' ?>, <?= e($d['movement']) ?>"
         style="flex-shrink:0;width:190px;border-radius:12px;overflow:hidden;border:1px solid var(--border);background:var(--surface);text-decoration:none;color:var(--text);display:block;transition:transform .2s;">
        <?php if ($cardImage): ?>
          <div class="designer-card__image" style="width:100%;height:128px;overflow:hidden;background:<?= e($d['bg']) ?>;">
            <img src="<?= e(blogThumbnailUrl($cardImage, 380, 256)) ?>"
                 alt="Visual article cover for <?= e($d['name']) ?>"
                 loading="lazy"
                 decoding="async"
                 width="380"
                 height="256"
                 style="width:100%;height:100%;object-fit:cover;display:block;"/>
          </div>
        <?php else: ?>
          <div class="designer-card__photo-placeholder"
               style="background:<?= e($d['bg']) ?>;width:100%;height:128px;display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:800;color:rgba(255,255,255,.9);letter-spacing:-2px;font-family:var(--font-display);">
            <?= e($d['initials']) ?>
          </div>
        <?php endif; ?>
        <div class="designer-card__body" style="padding:10px 12px;">
          <div class="designer-card__name" style="font-weight:700;font-size:12px;line-height:1.3;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($d['name']) ?></div>
          <div class="designer-card__years" style="font-size:11px;color:var(--muted);margin-bottom:3px;"><?= e($d['years']) ?></div>
          <div class="designer-card__movement" style="font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--accent);"><?= e(vdj_movement_label($d['movement'])) ?></div>
          <?php if ($cardDesc): ?>
            <p style="font-size:11px;color:var(--muted);line-height:1.45;margin:7px 0 0;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;"><?= e($cardDesc) ?></p>
          <?php endif; ?>
          <span style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:700;color:var(--accent);margin-top:8px;text-transform:uppercase;letter-spacing:.04em;">
            <?= $matchedPost ? e(__('explore.read_article', 'Read article')) : e(__('explore.find_article', 'Find article')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="premium-strip" aria-label="Premium features coming soon">
    <div>
      <span class="premium-strip__eyebrow"><i class="fa-solid fa-crown"></i> <?= e(__('explore.premium_eyebrow', 'Premium Features')) ?></span>
      <strong><?= e(__('explore.premium_title', 'Coming soon for serious curators.')) ?></strong>
      <p><?= e(__('explore.premium_body', 'Private collections, advanced source discovery, moodboard PDF/ZIP exports, weekly trend reports, portfolio-ready presentation mode, and curator analytics.')) ?></p>
    </div>
    <a href="<?= e(vdj_localized_url('/premium')) ?>" class="btn btn--secondary btn--sm"><?= e(__('explore.premium_cta', 'Explore premium roadmap')) ?></a>
  </section>

  <div style="padding:20px 0 4px;text-align:center;"><?= vdj_ad('leaderboard') ?><?= vdj_ad('mobile_320x50') ?></div>

  <!-- Filter bar (matches main_feed screen) -->
  <div class="filter-bar" role="navigation" aria-label="Style filters">
    <?php foreach ($styleTags as $value => $label): ?>
      <a href="<?= e($value === '' ? vdj_localized_url('/') : vdj_localized_url('/') . '?tag=' . urlencode($value)) ?>"
         class="btn btn--sm <?= $tag === $value ? 'btn--primary' : 'btn--secondary' ?>"
         style="border-radius:var(--r-pill);"
         <?= $tag === $value ? 'aria-current="true"' : '' ?>>
        <?= e(vdjStyleLabel($label)) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Explore toolbar: sort + view toggle + random -->
  <div class="explore-toolbar" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:24px;">
    <form method="GET" action="<?= e(vdj_localized_url('/')) ?>">
      <?php if ($tag): ?><input type="hidden" name="tag" value="<?= e($tag) ?>"><?php endif; ?>
      <?php if ($search): ?><input type="hidden" name="q" value="<?= e($search) ?>"><?php endif; ?>
      <select name="sort" class="sort-select" onchange="this.form.submit()" aria-label="<?= e(__('explore.sort_label', 'Sort boards')) ?>">
        <option value="newest"   <?= $sort === 'newest'   ? 'selected' : '' ?>><?= e(__('explore.sort_newest', 'Newest')) ?></option>
        <option value="popular"  <?= $sort === 'popular'  ? 'selected' : '' ?>><?= e(__('explore.sort_popular', 'Most Liked')) ?></option>
        <option value="trending" <?= $sort === 'trending' ? 'selected' : '' ?>><?= e(__('explore.sort_trending', 'Trending')) ?></option>
      </select>
    </form>
    <!-- View toggle -->
    <div class="view-toggle" role="group" aria-label="View mode">
      <button class="view-toggle-btn active" data-view="comfortable" aria-label="<?= e(__('explore.view_comfortable', 'Comfortable view')) ?>"><i class="fa-solid fa-border-all"></i></button>
      <button class="view-toggle-btn" data-view="compact" aria-label="<?= e(__('explore.view_compact', 'Compact view')) ?>"><i class="fa-solid fa-table-cells-large"></i></button>
    </div>
    <!-- Random inspiration -->
    <a href="/api/random.php" class="random-btn" title="Random board">
      <i class="fa-solid fa-shuffle"></i> <?= e(__('explore.surprise', 'Surprise me')) ?>
    </a>
    <span style="margin-left:auto;font-size:13px;color:var(--muted);" aria-live="polite">
      <?= sprintf(__('explore.boards_count', '%d boards'), count($boards)) ?>
    </span>
  </div>

  <aside class="support-strip" aria-label="Partnership">
    <div>
      <strong><?= e(__('explore.support_title', 'Curated space, no intrusive ads.')) ?></strong>
      <span><?= e(__('explore.support_body', 'Partner with Visual Design Journey through sponsored boards, newsletter placements, and design-tool features.')) ?></span>
    </div>
    <a href="<?= e(vdj_localized_url('/sponsor')) ?>" class="btn btn--secondary btn--sm">
      <i class="fa-solid fa-handshake"></i> <?= e(__('explore.support_cta', 'Partner options')) ?>
    </a>
  </aside>

  <?php if (empty($boards)): ?>
    <!-- Empty state -->
    <div class="empty-state">
      <div class="empty-state__icon"><span class="material-symbols-outlined" style="font-size:48px;opacity:.3;">grid_view</span></div>
      <p class="empty-state__title"><?= e(__('explore.no_boards_title', 'No boards found')) ?></p>
      <p class="empty-state__sub"><?= e(__('explore.no_boards_sub', 'Be the first to share a mood board.')) ?></p>
      <a href="<?= e(vdj_localized_url('/create')) ?>" class="btn btn--primary"><?= e(__('explore.create_board', 'Create a Board')) ?></a>
    </div>
  <?php else: ?>

    <!-- Masonry grid (matches screen card structure exactly) -->
    <div class="masonry" role="list" aria-label="Mood boards">
      <?php foreach ($boards as $idx => $board):
        $boardTitle = localizedBoardTitle($board);
        $boardDescription = localizedBoardDescription($board);
        $tags   = parseTags($board['style_tags'] ?? '');
        $liked  = currentUserId() ? hasLiked($db, (int)$board['id'], currentUserId()) : false;
        $images = getBoardImages($db, (int)$board['id']);
        $cover  = $board['cover_image'] ?? ($images[0]['image_url'] ?? null);
        $coverThumb = $cover ? thumbnailUrl($cover, 640, 480) : '';
        $isSaved = currentUserId() ? hasSaved($db, (int)$board['id'], currentUserId()) : false;
        $saveCount = (int)($board['save_count'] ?? 0);
        $viewCount = (int)($board['view_count'] ?? 0);
      ?>
      <article class="board-card" role="listitem" aria-label="<?= e($boardTitle) ?>">

        <!-- NEW badge + Cover image -->
        <a href="<?= e(boardUrl($board)) ?>"
           aria-label="View board: <?= e($boardTitle) ?>"
           style="position:relative;display:block;">
          <?php $isNew = strtotime($board['created_at'] ?? '') > strtotime('-7 days'); ?>
          <?php if ($isNew): ?>
            <div style="position:absolute;top:12px;left:12px;z-index:10;background:#fff;border:0.5px solid var(--border);padding:3px 10px;border-radius:var(--r-pill);">
              <span style="font-size:10px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--accent);">NEW</span>
            </div>
          <?php endif; ?>
          <?php if ($cover): ?>
            <div class="board-card__img-wrap">
              <div class="board-card__skeleton skeleton"></div>
              <img src="<?= e($coverThumb) ?>"
                   alt="Cover image for <?= e($boardTitle) ?> by <?= e($board['username']) ?>"
                   class="board-card__img"
                   loading="<?= $idx === 0 ? 'eager' : 'lazy' ?>"
                   decoding="async"
                   fetchpriority="<?= $idx === 0 ? 'high' : 'auto' ?>"
                   width="640"
                   height="480"
                   onload="this.classList.add('loaded');this.previousElementSibling.style.display='none'"/>
            </div>
          <?php else: ?>
            <div class="board-card__img--placeholder" role="img" aria-label="No cover image for <?= e($boardTitle) ?>">
              <i class="fa-regular fa-image" style="font-size:32px;color:var(--border);"></i>
            </div>
          <?php endif; ?>
        </a>

        <!-- Body -->
        <div class="board-card__body">
          <!-- Palette swatches -->
          <?php
            $palHexes = [];
            if (!empty($board['palette_colors'])) {
                $decoded = json_decode($board['palette_colors'], true);
                if (is_array($decoded)) $palHexes = array_slice($decoded, 0, 5);
            }
          ?>
          <div class="board-card__topline">
          <?php if ($palHexes): ?>
          <div class="board-card__palette" aria-label="Board palette">
            <?php foreach ($palHexes as $hex): ?>
              <span style="background:<?= e($hex) ?>;" title="<?= e($hex) ?>"></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
            <time datetime="<?= e($board['created_at'] ?? '') ?>" class="board-card__time"><?= e(vdjLocalizedDate($board['created_at'] ?? '', 'short')) ?></time>
          </div>
          <h3 class="board-card__title">
            <a href="<?= e(boardUrl($board)) ?>"><?= e($boardTitle) ?></a>
          </h3>
          <?php if ($boardDescription !== ''): ?>
            <p class="board-card__description"><?= e($boardDescription) ?></p>
          <?php endif; ?>
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
            <!-- Like -->
            <button class="board-card__likes like-btn"
                    data-board="<?= (int)$board['id'] ?>"
                    data-liked="<?= $liked ? '1' : '0' ?>"
                    aria-label="<?= $liked ? 'Unlike' : 'Like' ?> «<?= e($boardTitle) ?>» (<?= formatCount((int)$board['like_count']) ?> likes)"
                    style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:4px;">
              <span class="material-symbols-outlined <?= $liked ? 'icon-filled text-accent' : '' ?>"
                    aria-hidden="true"
                    style="font-size:16px;">favorite</span>
              <span class="like-count" aria-live="polite"><?= formatCount((int)$board['like_count']) ?></span>
            </button>
            <!-- Save -->
            <button class="save-btn"
                    data-board="<?= (int)$board['id'] ?>"
                    data-saved="<?= $isSaved ? '1' : '0' ?>"
                    aria-label="<?= $isSaved ? 'Unsave' : 'Save' ?> «<?= e($boardTitle) ?>»"
                    style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:4px;">
              <span class="material-symbols-outlined <?= $isSaved ? 'icon-filled text-accent' : '' ?>"
                    aria-hidden="true"
                    style="font-size:16px;"><?= $isSaved ? 'bookmark_added' : 'bookmark' ?></span>
              <span class="save-count" aria-live="polite"><?= formatCount($saveCount) ?></span>
            </button>
          </div>
          <div class="board-card__stats" aria-label="Board activity">
            <span><i class="fa-regular fa-heart"></i><span data-stat-like><?= formatCount((int)$board['like_count']) ?></span></span>
            <span><i class="fa-regular fa-bookmark"></i><span data-stat-save><?= formatCount($saveCount) ?></span></span>
            <?php if ($viewCount > 0): ?><span><i class="fa-regular fa-eye"></i><?= formatCount($viewCount) ?></span><?php endif; ?>
          </div>
        </div>

        <!-- Style tags -->
        <?php if ($tags): ?>
        <div class="board-card__tags">
          <?php foreach (array_slice($tags, 0, 3) as $t): ?>
            <a href="<?= e(vdj_localized_url('/')) ?>?tag=<?= urlencode($t) ?>" class="tag"><?= e($t) ?></a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

      </article>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if (count($boards) === $perPage): ?>
    <div style="text-align:center;margin-top:40px;padding-bottom:40px;">
      <a href="?page=<?= $page + 1 ?><?= $tag ? '&tag='.urlencode($tag) : '' ?><?= $sort !== 'newest' ? '&sort='.urlencode($sort) : '' ?>"
         class="btn btn--secondary" id="load-more-btn">
        <?= e(__('explore.load_more', 'Load more')) ?>
      </a>
    </div>
    <?php endif; ?>

  <?php endif; ?>
</div><!-- /container -->
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
