<?php
/**
 * blog.php — Blog index served from local DB (imported from WordPress)
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

session_start_if_not_started();
$lang = vdj_current_language();

/* ── Input ───────────────────────────────────────────────────────────────── */
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$search      = trim($_GET['q']   ?? '');
$catSlug     = trim($_GET['cat'] ?? '');
$tagSlug     = trim($_GET['tag'] ?? '');
$perPage     = 12;
$offset      = ($currentPage - 1) * $perPage;

/* ── Meta ────────────────────────────────────────────────────────────────── */
$pageTitle = __('blog.title', 'Blog') . ' — Visual Design Journey';
$activeNav = 'blog';
$metaDesc  = __('blog.description', 'Design thinking, creative process, and visual inspiration from the Visual Design Journey team.');

$db = getDB();

/* ── Check tables exist (graceful fallback if not imported yet) ──────────── */
$blogReady = false;
$blogI18nReady = false;
try {
    $db->query('SELECT 1 FROM blog_posts LIMIT 1');
    $blogReady = true;
    $blogI18nReady = dbColumnExists($db, 'blog_posts', 'title_tr')
        && dbColumnExists($db, 'blog_posts', 'content_tr')
        && dbColumnExists($db, 'blog_categories', 'name_tr')
        && dbColumnExists($db, 'blog_tags', 'name_tr');
} catch (PDOException $e) {
    // Tables not imported yet — show setup message below
}

$activeCat  = null;
$activeTag  = null;
$posts      = [];
$featuredPost = null;
$filterCats = [];
$totalPosts = 0;
$totalPages = 1;

if ($blogReady) {

/* ── Category / tag lookup for SEO ─────────────────────────────────────── */
if ($catSlug !== '') {
    $stmt = $db->prepare($lang !== 'en' && $blogI18nReady
        ? "SELECT * FROM blog_categories WHERE slug = ? OR slug_{$lang} = ? LIMIT 1"
        : 'SELECT * FROM blog_categories WHERE slug = ? LIMIT 1');
    $stmt->execute($lang !== 'en' && $blogI18nReady ? [$catSlug, $catSlug] : [$catSlug]);
    $activeCat = $stmt->fetch() ?: null;
    if ($activeCat) {
        $pageTitle = htmlspecialchars(localizedCategoryName($activeCat)) . ' — Blog — Visual Design Journey';
        $catDesc = localizedField($activeCat, 'description');
        $metaDesc = $catDesc !== '' ? $catDesc : $metaDesc;
    }
}
if ($tagSlug !== '') {
    $stmt = $db->prepare($lang !== 'en' && $blogI18nReady
        ? "SELECT * FROM blog_tags WHERE slug = ? OR slug_{$lang} = ? LIMIT 1"
        : 'SELECT * FROM blog_tags WHERE slug = ? LIMIT 1');
    $stmt->execute($lang !== 'en' && $blogI18nReady ? [$tagSlug, $tagSlug] : [$tagSlug]);
    $activeTag = $stmt->fetch() ?: null;
    if ($activeTag) {
        $pageTitle = '#' . htmlspecialchars(localizedTagName($activeTag)) . ' — Blog — Visual Design Journey';
    }
}

/* ── Build query ─────────────────────────────────────────────────────────── */
$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $searchFields = ['bp.title', 'bp.excerpt'];
    if ($lang !== 'en' && $blogI18nReady) {
        $searchFields[] = "bp.title_{$lang}";
        $searchFields[] = "bp.excerpt_{$lang}";
        $searchFields[] = "bp.content_{$lang}";
    }
    $where[] = '(' . implode(' LIKE ? OR ', $searchFields) . ' LIKE ?)';
    $like     = '%' . $search . '%';
    foreach ($searchFields as $_) {
        $params[] = $like;
    }
}
if ($catSlug !== '') {
    $where[]  = $lang !== 'en' && $blogI18nReady
        ? "EXISTS (SELECT 1 FROM blog_post_categories bpc JOIN blog_categories bc ON bc.id = bpc.category_id WHERE bpc.post_id = bp.id AND (bc.slug = ? OR bc.slug_{$lang} = ?))"
        : 'EXISTS (SELECT 1 FROM blog_post_categories bpc JOIN blog_categories bc ON bc.id = bpc.category_id WHERE bpc.post_id = bp.id AND bc.slug = ?)';
    $params[] = $catSlug;
    if ($lang !== 'en' && $blogI18nReady) $params[] = $catSlug;
}
if ($tagSlug !== '') {
    $where[]  = $lang !== 'en' && $blogI18nReady
        ? "EXISTS (SELECT 1 FROM blog_post_tags bpt JOIN blog_tags bt ON bt.id = bpt.tag_id WHERE bpt.post_id = bp.id AND (bt.slug = ? OR bt.slug_{$lang} = ?))"
        : 'EXISTS (SELECT 1 FROM blog_post_tags bpt JOIN blog_tags bt ON bt.id = bpt.tag_id WHERE bpt.post_id = bp.id AND bt.slug = ?)';
    $params[] = $tagSlug;
    if ($lang !== 'en' && $blogI18nReady) $params[] = $tagSlug;
}
$whereSQL = implode(' AND ', $where);

/* ── Count ───────────────────────────────────────────────────────────────── */
$countStmt = $db->prepare("SELECT COUNT(*) FROM blog_posts bp WHERE $whereSQL");
$countStmt->execute($params);
$totalPosts = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalPosts / $perPage));

/* ── Posts ───────────────────────────────────────────────────────────────── */
$localizedSelect = $blogI18nReady
    ? 'bp.title_tr, bp.title_de, bp.slug_tr, bp.slug_de, bp.excerpt_tr, bp.excerpt_de, bp.content_tr, bp.content_de, bp.seo_description_tr, bp.seo_description_de,'
    : "'' AS title_tr, '' AS title_de, '' AS slug_tr, '' AS slug_de, '' AS excerpt_tr, '' AS excerpt_de, '' AS content_tr, '' AS content_de, '' AS seo_description_tr, '' AS seo_description_de,";
$localizedCatSelect = $blogI18nReady
    ? "(SELECT bc.name_tr FROM blog_post_categories bpc JOIN blog_categories bc ON bc.id = bpc.category_id WHERE bpc.post_id = bp.id LIMIT 1) AS cat_name_tr,
       (SELECT bc.name_de FROM blog_post_categories bpc JOIN blog_categories bc ON bc.id = bpc.category_id WHERE bpc.post_id = bp.id LIMIT 1) AS cat_name_de,
       (SELECT bc.slug_tr FROM blog_post_categories bpc JOIN blog_categories bc ON bc.id = bpc.category_id WHERE bpc.post_id = bp.id LIMIT 1) AS cat_slug_tr,
       (SELECT bc.slug_de FROM blog_post_categories bpc JOIN blog_categories bc ON bc.id = bpc.category_id WHERE bpc.post_id = bp.id LIMIT 1) AS cat_slug_de,"
    : "'' AS cat_name_tr, '' AS cat_name_de, '' AS cat_slug_tr, '' AS cat_slug_de,";

$stmt = $db->prepare(
    "SELECT bp.id, bp.title, bp.slug, bp.excerpt, bp.content, $localizedSelect bp.featured_image,
            bp.author, bp.published_at, bp.seo_description,
            (SELECT bc.name FROM blog_post_categories bpc
             JOIN blog_categories bc ON bc.id = bpc.category_id
             WHERE bpc.post_id = bp.id LIMIT 1) AS cat_name,
            (SELECT bc.slug FROM blog_post_categories bpc
             JOIN blog_categories bc ON bc.id = bpc.category_id
             WHERE bpc.post_id = bp.id LIMIT 1) AS cat_slug,
            $localizedCatSelect
            bp.id AS post_id_marker
     FROM blog_posts bp
     WHERE $whereSQL
     ORDER BY bp.published_at DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$posts = $stmt->fetchAll();

/* ── Featured post (page 1, no filters) ──────────────────────────────────── */
if ($currentPage === 1 && $search === '' && $catSlug === '' && $tagSlug === '' && !empty($posts)) {
    $featuredPost = array_shift($posts);
}

/* ── Category filter bar ─────────────────────────────────────────────────── */
$catGroupBy = $blogI18nReady
    ? 'bc.id, bc.name, bc.name_tr, bc.name_de, bc.slug, bc.slug_tr, bc.slug_de'
    : 'bc.id, bc.name, bc.slug';
$catBarStmt = $db->query(
    ($blogI18nReady
    ? 'SELECT bc.id, bc.name, bc.name_tr, bc.name_de, bc.slug, bc.slug_tr, bc.slug_de, COUNT(bpc.post_id) AS cnt'
    : 'SELECT bc.id, bc.name, "" AS name_tr, "" AS name_de, bc.slug, "" AS slug_tr, "" AS slug_de, COUNT(bpc.post_id) AS cnt') . '
     FROM blog_categories bc
     JOIN blog_post_categories bpc ON bpc.category_id = bc.id
     GROUP BY ' . $catGroupBy . '
     HAVING cnt > 0
     ORDER BY cnt DESC, bc.name ASC
     LIMIT 20'
);
$filterCats = $catBarStmt->fetchAll();

} // end if ($blogReady)

/* ── Reading time helper ─────────────────────────────────────────────────── */
function blogReadingTime(string $content): int {
    return (int)ceil(str_word_count(strip_tags($content)) / 200) ?: 1;
}

function blogExcerpt(string $excerpt, string $content, int $maxChars = 180): string {
    return cleanBlogExcerpt('', $excerpt, $content, vdj_current_language(), $maxChars);
}

function blogIndexUrl(array $params = []): string {
    return blogIndexPath($params);
}

/* ── Canonical URL ───────────────────────────────────────────────────────── */
// Build the canonical so filtered/paginated pages aren't treated as duplicates
$canonicalUrl = publicUrl(blogIndexUrl());
if ($catSlug !== '') {
    $canonicalUrl = publicUrl(blogCategoryUrl($activeCat ? localizedCategorySlug($activeCat) : $catSlug));
} elseif ($tagSlug !== '') {
    $canonicalUrl = publicUrl(blogTagUrl($activeTag ? localizedTagSlug($activeTag) : $tagSlug));
}
if ($currentPage > 1) {
    $qs = array_filter(['page' => $currentPage, 'q' => $search ?: null, 'cat' => $catSlug ?: null, 'tag' => $tagSlug ?: null]);
    $canonicalUrl = publicUrl(blogIndexUrl($qs));
}

/* ── OG image from featured post ────────────────────────────────────────── */
if (!empty($featuredPost['featured_image'])) {
    $metaImage = blogImageUrl($featuredPost['featured_image']);
} elseif (!empty($posts[0]['featured_image'])) {
    $metaImage = blogImageUrl($posts[0]['featured_image']);
}

/* ── Pagination SEO: prev/next link tags ─────────────────────────────────── */
$headExtra = '';
if ($currentPage > 1) {
    $prevQs = array_filter(['page' => $currentPage - 1, 'q' => $search, 'cat' => $catSlug, 'tag' => $tagSlug]);
    $headExtra .= '<link rel="prev" href="' . e(publicUrl(blogIndexUrl($prevQs))) . '"/>' . "\n";
}
if ($currentPage < $totalPages) {
    $nextQs = array_filter(['page' => $currentPage + 1, 'q' => $search, 'cat' => $catSlug, 'tag' => $tagSlug]);
    $headExtra .= '<link rel="next" href="' . e(publicUrl(blogIndexUrl($nextQs))) . '"/>' . "\n";
}
if (!empty($filterCats)) {
    $headExtra .= '<meta name="keywords" content="' . e(implode(', ', array_map('localizedCategoryName', $filterCats))) . '"/>' . "\n";
}
$collectionLd = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => strip_tags($pageTitle),
    'description' => $metaDesc,
    'url' => $canonicalUrl,
    'isPartOf' => [
        '@type' => 'WebSite',
        'name' => 'Visual Design Journey',
        'url' => publicUrl('/'),
    ],
];
$headExtra .= '<script type="application/ld+json">' . json_encode($collectionLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";

require_once __DIR__ . '/includes/header.php';
?>

<main class="page-wrap" id="main-content">
<div class="container">

  <!-- Page header -->
  <div style="text-align:center;padding:48px 0 36px;" data-animate>
    <p style="font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--accent);margin-bottom:10px;">
      <i class="fa-solid fa-pen-nib" style="margin-right:6px;"></i><?= e(__('blog.eyebrow', 'Journal')) ?>
    </p>
    <h1 style="font-size:clamp(2rem,5vw,3rem);font-weight:800;letter-spacing:-0.03em;margin:0 0 12px;">
      <?php if ($activeCat): ?>
        <?= htmlspecialchars(localizedCategoryName($activeCat)) ?>
      <?php elseif ($activeTag): ?>
        #<?= htmlspecialchars(localizedTagName($activeTag)) ?>
      <?php else: ?>
        <?= e(__('blog.title', 'Design Blog')) ?>
      <?php endif; ?>
    </h1>
    <p style="color:var(--muted);max-width:520px;margin:0 auto;font-size:1rem;line-height:1.6;">
      <?php if ($activeCat && localizedField($activeCat, 'description') !== ''): ?>
        <?= htmlspecialchars(localizedField($activeCat, 'description')) ?>
      <?php else: ?>
        <?= e(__('blog.description', 'Creative thinking, process notes, and visual inspiration from the team.')) ?>
      <?php endif; ?>
    </p>
  </div>

  <?php if (!$blogReady): ?>
  <!-- Tables not imported yet -->
  <div class="empty-state" data-animate style="padding:64px 0;">
    <div class="empty-state__icon">
      <i class="fa-solid fa-database" style="font-size:42px;opacity:.25;"></i>
    </div>
    <p class="empty-state__title">Blog posts are being set up</p>
    <p class="empty-state__sub">Import <code>blog-schema.sql</code> then <code>blog-import.sql</code> in phpMyAdmin to load 2,212 posts.</p>
    <a href="/admin/" class="btn btn--primary" style="margin-top:16px;">
      <i class="fa-solid fa-shield-halved"></i> Admin Panel
    </a>
  </div>

  <?php elseif (empty($featuredPost) && empty($posts)): ?>
  <!-- Empty state -->
  <div class="empty-state" data-animate>
    <div class="empty-state__icon"><i class="fa-solid fa-newspaper" style="font-size:40px;opacity:.25;"></i></div>
    <p class="empty-state__title"><?= e(__('blog.no_posts', 'No posts found')) ?></p>
    <p class="empty-state__sub"><?= $search !== '' ? e(__('blog.try_search', 'Try a different search term.')) : e(__('blog.empty', 'No blog posts yet.')) ?></p>
    <?php if ($search !== ''): ?>
      <a href="<?= e(vdj_localized_url('/blog')) ?>" class="btn btn--secondary" style="margin-top:12px;"><?= e(__('blog.clear_search', 'Clear search')) ?></a>
    <?php endif; ?>
  </div>

  <?php else: ?>

  <!-- Search + category filter bar -->
  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:32px;" data-animate>
    <form action="<?= e(vdj_localized_url('/blog')) ?>" method="GET" style="position:relative;flex:1;max-width:320px;">
      <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:13px;pointer-events:none;"></i>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
             placeholder="<?= e(__('blog.search_placeholder', 'Search articles...')) ?>"
             class="form-input"
             style="padding-left:38px;border-radius:var(--r-pill);height:40px;font-size:14px;width:100%;"
             aria-label="Search blog posts"/>
    </form>

    <div class="filter-bar" role="navigation" aria-label="Blog categories"
         style="flex:1;justify-content:flex-start;flex-wrap:wrap;padding:0;gap:6px;">
      <a href="<?= e(vdj_localized_url('/blog')) ?>"
         class="btn btn--sm <?= ($catSlug === '' && $tagSlug === '') ? 'btn--primary' : 'btn--secondary' ?>"
         style="border-radius:var(--r-pill);"
         <?= ($catSlug === '' && $tagSlug === '') ? 'aria-current="true"' : '' ?>><?= e(__('blog.all', 'All')) ?></a>
      <?php foreach ($filterCats as $fc): ?>
        <?php $fcSlug = localizedCategorySlug($fc); ?>
        <a href="<?= e(blogCategoryUrl($fcSlug)) ?>"
           class="btn btn--sm <?= in_array($catSlug, [$fc['slug'], $fcSlug], true) ? 'btn--primary' : 'btn--secondary' ?>"
           style="border-radius:var(--r-pill);"
           <?= in_array($catSlug, [$fc['slug'], $fcSlug], true) ? 'aria-current="true"' : '' ?>>
          <?= htmlspecialchars(localizedCategoryName($fc)) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($featuredPost): ?>
  <!-- ── Featured post ─────────────────────────────────────────────────── -->
  <?php
    $fTitle = localizedField($featuredPost, 'title');
    $fTranslation = blogTranslationStatus($featuredPost, $lang);
    $fExc  = ($lang !== 'en' && !$fTranslation['is_translated'])
      ? cleanBlogExcerpt($fTitle, (string)($featuredPost["excerpt_$lang"] ?? ''), (string)($featuredPost["content_$lang"] ?? ''), $lang, 220)
      : cleanBlogExcerpt($fTitle, localizedField($featuredPost, 'excerpt'), localizedField($featuredPost, 'content'), $lang, 220);
    $fDate = vdjLocalizedDate($featuredPost['published_at']);
    $fCat  = localizedField($featuredPost, 'cat_name');
    $fCatSlug = localizedField($featuredPost, 'cat_slug');
    $fAuthorUrl = blogAuthorProfileUrl($featuredPost['author'] ?? '');
  ?>
  <article class="blog-featured" data-animate>
    <?php if ($featuredPost['featured_image']): ?>
    <a href="<?= e(blogPostUrl($featuredPost)) ?>" class="blog-featured__img" aria-label="<?= e(__('blog.read_article', 'Read article')) ?>: <?= htmlspecialchars($fTitle) ?>">
      <img src="<?= e(blogThumbnailUrl($featuredPost['featured_image'], 920, 575)) ?>"
           alt="Featured image for: <?= htmlspecialchars($fTitle) ?>"
           loading="eager"
           decoding="async"
           width="920"
           height="575"
           fetchpriority="high"
           class="loaded"/>
    </a>
    <?php else: ?>
    <a href="<?= e(blogPostUrl($featuredPost)) ?>" class="blog-featured__img blog-featured__img--placeholder" aria-label="<?= e(__('blog.read_article', 'Read article')) ?>: <?= htmlspecialchars($fTitle) ?>">
      <i class="fa-solid fa-pen-nib" style="font-size:48px;color:var(--muted);opacity:.25;"></i>
    </a>
    <?php endif; ?>
    <div class="blog-featured__body">
      <?php if ($fCat): ?>
        <a href="<?= e(blogCategoryUrl($fCatSlug)) ?>" class="blog-tag">
          <?= htmlspecialchars($fCat) ?>
        </a>
      <?php endif; ?>
      <?php if ($lang !== 'en' && !$fTranslation['is_translated']): ?>
        <span class="blog-status-badge"><?= e(__('blog.translation_pending_badge', 'Translation pending')) ?></span>
      <?php endif; ?>
      <h2 class="blog-featured__title"><a href="<?= e(blogPostUrl($featuredPost)) ?>"><?= htmlspecialchars($fTitle) ?></a></h2>
      <?php if ($fExc !== ''): ?>
        <p class="blog-featured__excerpt"><?= htmlspecialchars($fExc) ?></p>
      <?php endif; ?>
      <div class="blog-meta">
        <?php if ($featuredPost['author']): ?>
          <?php if ($fAuthorUrl): ?>
            <a href="<?= e($fAuthorUrl) ?>" class="blog-author-link"><i class="fa-regular fa-user"></i> <?= htmlspecialchars($featuredPost['author']) ?></a>
          <?php else: ?>
            <span><i class="fa-regular fa-user"></i> <?= htmlspecialchars($featuredPost['author']) ?></span>
          <?php endif; ?>
        <?php endif; ?>
        <span><i class="fa-regular fa-calendar"></i> <?= $fDate ?></span>
        <a href="<?= e(blogPostUrl($featuredPost)) ?>" class="btn btn--primary btn--sm blog-read-link">
          <?= e(__('blog.read_article', 'Read article')) ?> <i class="fa-solid fa-arrow-right" style="margin-left:6px;font-size:11px;"></i>
        </a>
      </div>
    </div>
  </article>
  <?php endif; ?>

  <!-- ── Post grid ──────────────────────────────────────────────────────── -->
  <div style="padding:8px 0 24px;text-align:center;"><?= vdj_ad('leaderboard') ?><?= vdj_ad('banner_468x60') ?></div>
  <?php renderAdSlot('blog-index-before-grid'); ?>
  <?php if (!empty($posts)): ?>
  <div class="blog-grid" style="margin-top:<?= $featuredPost ? '48px' : '0' ?>;">
    <?php foreach ($posts as $i => $post):
      $postTitleLocalized = localizedField($post, 'title');
      $translation = blogTranslationStatus($post, $lang);
      $exc  = ($lang !== 'en' && !$translation['is_translated'])
        ? cleanBlogExcerpt($postTitleLocalized, (string)($post["excerpt_$lang"] ?? ''), (string)($post["content_$lang"] ?? ''), $lang, 160)
        : cleanBlogExcerpt($postTitleLocalized, localizedField($post, 'excerpt'), localizedField($post, 'content'), $lang, 160);
      $date = vdjLocalizedDate($post['published_at']);
      $cat  = localizedField($post, 'cat_name');
      $catS = localizedField($post, 'cat_slug');
      $authorUrl = blogAuthorProfileUrl($post['author'] ?? '');
    ?>
    <article class="blog-card" data-animate data-delay="<?= min($i + 1, 5) ?>">
      <?php if ($post['featured_image']): ?>
      <a href="<?= e(blogPostUrl($post)) ?>" class="blog-card__img" aria-label="<?= e(__('blog.read_article', 'Read article')) ?>: <?= htmlspecialchars($postTitleLocalized) ?>">
        <img src="<?= e(blogThumbnailUrl($post['featured_image'], 520, 292)) ?>"
             alt="<?= htmlspecialchars($postTitleLocalized) ?>"
             loading="lazy"
             decoding="async"
             width="520"
             height="292"
             class="loaded"/>
      </a>
      <?php else: ?>
      <a href="<?= e(blogPostUrl($post)) ?>" class="blog-card__img blog-card__img--placeholder" aria-label="<?= e(__('blog.read_article', 'Read article')) ?>: <?= htmlspecialchars($postTitleLocalized) ?>">
        <i class="fa-solid fa-pen-nib"></i>
      </a>
      <?php endif; ?>
      <div class="blog-card__body">
        <?php if ($cat): ?>
          <a href="<?= e(blogCategoryUrl($catS)) ?>" class="blog-tag"><?= htmlspecialchars($cat) ?></a>
        <?php endif; ?>
        <?php if ($lang !== 'en' && !$translation['is_translated']): ?>
          <span class="blog-status-badge"><?= e(__('blog.translation_pending_badge', 'Translation pending')) ?></span>
        <?php endif; ?>
        <h3 class="blog-card__title"><a href="<?= e(blogPostUrl($post)) ?>"><?= htmlspecialchars($postTitleLocalized) ?></a></h3>
        <?php if ($exc !== ''): ?>
          <p class="blog-card__exc"><?= htmlspecialchars($exc) ?></p>
        <?php endif; ?>
        <div class="blog-meta">
          <?php if (!empty($post['author'])): ?>
            <?php if ($authorUrl): ?>
              <a href="<?= e($authorUrl) ?>" class="blog-author-link"><i class="fa-regular fa-user"></i> <?= htmlspecialchars($post['author']) ?></a>
            <?php else: ?>
              <span><i class="fa-regular fa-user"></i> <?= htmlspecialchars($post['author']) ?></span>
            <?php endif; ?>
          <?php endif; ?>
          <span><i class="fa-regular fa-calendar"></i> <?= $date ?></span>
          <a href="<?= e(blogPostUrl($post)) ?>" class="blog-read-link blog-read-link--text">
            <?= e(__('blog.read', 'Read')) ?> <i class="fa-solid fa-arrow-right" style="font-size:10px;"></i>
          </a>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <nav aria-label="Blog pagination" style="display:flex;justify-content:center;gap:8px;margin-top:48px;flex-wrap:wrap;" data-animate>
    <?php for ($p = max(1, $currentPage - 3); $p <= min($totalPages, $currentPage + 3); $p++):
      $pParams = array_filter(['page' => $p > 1 ? $p : null, 'q' => $search ?: null, 'cat' => $catSlug ?: null, 'tag' => $tagSlug ?: null]);
      $pHref   = vdj_localized_url('/blog') . ($pParams ? '?' . http_build_query($pParams) : '');
    ?>
      <a href="<?= htmlspecialchars($pHref) ?>"
         aria-label="Page <?= $p ?>"
         <?= $p === $currentPage ? 'aria-current="page"' : '' ?>
         style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:9px;border:1px solid var(--border);font-size:13px;font-weight:600;text-decoration:none;transition:all .15s;
                <?= $p === $currentPage ? 'background:var(--accent);color:#fff;border-color:var(--accent);' : 'color:var(--text);background:#fff;' ?>">
        <?= $p ?>
      </a>
    <?php endfor; ?>
    <?php if ($currentPage < $totalPages):
      $nextParams = array_filter(['page' => $currentPage + 1, 'q' => $search ?: null, 'cat' => $catSlug ?: null, 'tag' => $tagSlug ?: null]);
    ?>
      <a href="<?= e(vdj_localized_url('/blog') . ($nextParams ? '?' . http_build_query($nextParams) : '')) ?>"
         aria-label="Next page"
         style="display:inline-flex;align-items:center;justify-content:center;height:38px;padding:0 14px;border-radius:9px;border:1px solid var(--border);font-size:13px;font-weight:600;text-decoration:none;color:var(--text);background:#fff;gap:6px;">
        Next <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
      </a>
    <?php endif; ?>
  </nav>
  <?php endif; ?>

  <?php endif; // end not-empty ?>

</div>
</main>

<style>
/* ── Blog layout ───────────────────────────────────────────────────────── */
.blog-featured {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  background: var(--bg);
  border-radius: var(--r-lg);
  overflow: hidden;
  border: 1px solid var(--border);
  transition: box-shadow 0.2s, transform 0.2s;
}
.blog-featured:hover { box-shadow: 0 12px 40px rgba(0,0,0,.10); transform: translateY(-2px); }
.blog-featured__img { aspect-ratio: 16/10; overflow: hidden; background: var(--surface); display:flex;align-items:center;justify-content:center; }
.blog-featured__img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
.blog-featured:hover .blog-featured__img img { transform: scale(1.04); }
.blog-featured__img--placeholder { background: var(--surface); }
.blog-featured__body { padding: 36px 36px 36px 0; display: flex; flex-direction: column; justify-content: center; gap: 14px; }
.blog-featured__title { font-size: clamp(1.25rem, 2.5vw, 1.75rem); font-weight: 800; letter-spacing: 0; color: var(--text); line-height: 1.25; margin: 0; }
.blog-featured__title a,
.blog-card__title a { color: inherit; text-decoration: none; }
.blog-featured__title a:hover,
.blog-card__title a:hover { color: var(--accent); }
.blog-featured__excerpt { color: var(--muted); line-height: 1.65; font-size: .95rem; margin: 0; }

.blog-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  align-items: start;
}
.blog-card {
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: box-shadow 0.2s, transform 0.2s;
  color: var(--text);
  min-width: 0;
}
.blog-card:hover { box-shadow: 0 8px 32px rgba(0,0,0,.09); transform: translateY(-3px); }
.blog-card__img { aspect-ratio: 16/9; min-height: 170px; max-height: 240px; overflow: hidden; background: var(--surface); display:block; }
.blog-card__img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
.blog-card:hover .blog-card__img img { transform: scale(1.05); }
.blog-card__img--placeholder { display: flex; align-items: center; justify-content: center; }
.blog-card__img--placeholder i { font-size: 28px; color: var(--muted); opacity: .3; }
.blog-card__body { padding: 20px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
.blog-card__title { font-size: 1rem; font-weight: 700; line-height: 1.35; margin: 0; color: var(--text); }
.blog-card__exc { font-size: .875rem; color: var(--muted); line-height: 1.6; margin: 0; flex: 1; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }

.blog-tag {
  display: inline-block;
  padding: 2px 10px;
  border-radius: var(--r-pill);
  background: var(--accent-soft, rgba(127,119,221,.1));
  color: var(--accent);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .04em;
  text-transform: uppercase;
  width: fit-content;
}
.blog-status-badge {
  display: inline-flex;
  align-items: center;
  width: fit-content;
  padding: 2px 9px;
  border-radius: var(--r-pill);
  background: rgba(245, 158, 11, .13);
  color: #b45309;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .02em;
}
.blog-meta { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; color: var(--muted); font-size: 12px; margin-top: 4px; }
.blog-meta i { margin-right: 4px; }
.blog-author-link { color: var(--muted); text-decoration: none; }
.blog-author-link:hover { color: var(--accent); }
.blog-read-link { margin-left:auto;border-radius:var(--r-pill); text-decoration:none; }
.blog-read-link--text { color:var(--accent);font-weight:600;font-size:11px;display:inline-flex;align-items:center;gap:5px; }

@media (max-width: 900px) {
  .blog-featured { grid-template-columns: 1fr; }
  .blog-featured__body { padding: 24px; }
  .blog-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 560px) {
  .blog-grid { grid-template-columns: 1fr; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
