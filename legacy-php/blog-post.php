<?php
/**
 * blog-post.php — Individual blog post page
 * Accepts: ?slug=, /blog/{slug} legacy redirect, or WordPress-compatible /{slug}/
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

session_start_if_not_started();

$db   = getDB();
$slug = trim($_GET['slug'] ?? '');

if ($slug === '') {
    http_response_code(301);
    header('Location: ' . blogIndexPath());
    exit;
}

/* ── Graceful fallback if blog tables don't exist yet ───────────────────── */
try {
    $db->query('SELECT 1 FROM blog_posts LIMIT 1');
} catch (PDOException $e) {
    http_response_code(302);
    header('Location: ' . blogIndexPath());
    exit;
}

/* ── Language detection (before slug lookup so we pick right slug) ───────── */
$lang = vdj_current_language();
$blogI18nReady = dbColumnExists($db, 'blog_posts', 'title_tr')
    && dbColumnExists($db, 'blog_categories', 'name_tr')
    && dbColumnExists($db, 'blog_tags', 'name_tr');

/* ── Fetch post: try language-specific slug first, fall back to EN slug ──── */
$post = null;
if ($lang !== 'en') {
    // Try translated slug column first
    try {
        $stmtL = $db->prepare("SELECT * FROM blog_posts WHERE `slug_{$lang}` = ? LIMIT 1");
        $stmtL->execute([$slug]);
        $post = $stmtL->fetch() ?: null;
    } catch (PDOException $e) { /* column may not exist yet */ }
}
if (!$post) {
    // Fall back to English slug (covers old URLs still being indexed)
    $stmtE = $db->prepare('SELECT * FROM blog_posts WHERE slug = ? LIMIT 1');
    $stmtE->execute([$slug]);
    $post = $stmtE->fetch() ?: null;
}

if (!$post) {
    http_response_code(404);
    $pageTitle = '404 — ' . __('blog.not_found_title', 'Post Not Found');
    $activeNav = 'blog';
    $metaDesc  = __('blog.not_found_body', 'The blog post you are looking for could not be found.');
    require_once __DIR__ . '/includes/header.php';
    ?>
    <main class="page-wrap" id="main-content">
    <div class="container" style="text-align:center;padding:80px 0;">
      <i class="fa-solid fa-file-circle-question" style="font-size:56px;color:var(--muted);opacity:.3;margin-bottom:20px;display:block;"></i>
      <h1 style="font-size:2rem;font-weight:800;margin-bottom:10px;"><?= e(__('blog.not_found_title', 'Post Not Found')) ?></h1>
      <p style="color:var(--muted);margin-bottom:24px;"><?= e(__('blog.not_found_body', 'This article doesn\'t exist or has been removed.')) ?></p>
      <a href="<?= e(vdj_localized_url('/blog')) ?>" class="btn btn--primary"><i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i><?= e(__('blog.title','Blog')) ?></a>
    </div>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

/* ── If accessed via English slug while on TR/DE, redirect to translated URL */
if ($lang !== 'en' && !empty($post["slug_{$lang}"])) {
    $canonSlug = $post["slug_{$lang}"];
    if ($slug !== $canonSlug) {
        http_response_code(301);
        header('Location: ' . publicUrl('/' . $lang . '/blog/' . rawurlencode($canonSlug) . '/'));
        exit;
    }
}

/* ── i18n: pick translated fields when available ────────────────────────── */
$postTitle   = ($lang !== 'en' && !empty($post["title_$lang"]))   ? $post["title_$lang"]   : $post['title'];
$postExcerpt = ($lang !== 'en' && !empty($post["excerpt_$lang"]))  ? $post["excerpt_$lang"]  : $post['excerpt'];
$translatedExcerpt = $lang !== 'en' ? trim((string)($post["excerpt_$lang"] ?? '')) : trim((string)$postExcerpt);
$translationMissing = $lang !== 'en' && trim((string)($post["content_$lang"] ?? '')) === '';
$postContent = $translationMissing ? '' : (($lang !== 'en' && !empty($post["content_$lang"])) ? $post["content_$lang"] : $post['content']);
$postSeoTitle = ($lang !== 'en' && !empty($post["seo_title_$lang"])) ? $post["seo_title_$lang"] : $post['seo_title'];
$postSeoDesc  = ($lang !== 'en' && !empty($post["seo_description_$lang"])) ? $post["seo_description_$lang"] : $post['seo_description'];

/* ── Canonical URL: language-specific slug ───────────────────────────────── */
$canonicalUrl = publicUrl(blogCanonicalPath($post, $lang));

/* ── hreflang alternate URLs for this post ───────────────────────────────── */
$hreflangPost = [
    'en' => publicUrl(blogCanonicalPath($post, 'en')),
    'tr' => publicUrl(blogCanonicalPath($post, 'tr')),
    'de' => publicUrl(blogCanonicalPath($post, 'de')),
];

/* ── Redirect legacy or non-canonical article URLs to the clean URL ──────── */
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$canonicalPath = parse_url($canonicalUrl, PHP_URL_PATH) ?: '/';
if ($requestPath !== $canonicalPath) {
    http_response_code(301);
    header('Location: ' . $canonicalUrl);
    exit;
}

/* ── SEO meta ────────────────────────────────────────────────────────────── */
$seoTitle    = ($postSeoTitle !== '') ? $postSeoTitle : $postTitle . ' — Visual Design Journey';
$seoDesc     = cleanBlogExcerpt($postTitle, $postSeoDesc, $translationMissing ? $translatedExcerpt : $postExcerpt, $lang, 180);
if ($seoDesc === '' && $translationMissing) {
    $seoDesc = __('blog.translation_pending_body', 'This article translation is not available yet.');
}
$ogImage      = ($post['featured_image'] !== '') ? blogImageUrl($post['featured_image']) : publicUrl('/assets/img/og-default.jpg');
$pubDate      = $post['published_at'];
$displayDate  = vdjLocalizedDate($pubDate);
$isoDate      = date('c', strtotime($pubDate));

/* ── Reading time ────────────────────────────────────────────────────────── */
$readingTimeSource = $translationMissing ? ($postExcerpt ?: $postTitle) : $postContent;
$readingTime = (int)ceil(str_word_count(strip_tags($readingTimeSource)) / 200);
if ($readingTime < 1) $readingTime = 1;

/* ── Categories for this post ───────────────────────────────────────────── */
$catStmt = $db->prepare(
    ($blogI18nReady
    ? 'SELECT bc.name, bc.name_tr, bc.name_de, bc.slug, bc.slug_tr, bc.slug_de FROM blog_categories bc'
    : 'SELECT bc.name, "" AS name_tr, "" AS name_de, bc.slug, "" AS slug_tr, "" AS slug_de FROM blog_categories bc') . '
     JOIN blog_post_categories bpc ON bpc.category_id = bc.id
     WHERE bpc.post_id = ?
     ORDER BY bc.name ASC'
);
$catStmt->execute([$post['id']]);
$postCats = $catStmt->fetchAll();

/* ── Tags for this post ──────────────────────────────────────────────────── */
$tagStmt = $db->prepare(
    ($blogI18nReady
    ? 'SELECT bt.name, bt.name_tr, bt.name_de, bt.slug, bt.slug_tr, bt.slug_de FROM blog_tags bt'
    : 'SELECT bt.name, "" AS name_tr, "" AS name_de, bt.slug, "" AS slug_tr, "" AS slug_de FROM blog_tags bt') . '
     JOIN blog_post_tags bpt ON bpt.tag_id = bt.id
     WHERE bpt.post_id = ?
     ORDER BY bt.name ASC'
);
$tagStmt->execute([$post['id']]);
$postTags = $tagStmt->fetchAll();

/* ── Related posts (same first category, limit 3) ───────────────────────── */
$relatedPosts = [];
$firstCatId   = null;
if (!empty($postCats)) {
    $fcStmt = $db->prepare($lang !== 'en' && $blogI18nReady
        ? "SELECT bc.id FROM blog_categories bc WHERE bc.slug = ? OR bc.slug_{$lang} = ? LIMIT 1"
        : 'SELECT bc.id FROM blog_categories bc WHERE bc.slug = ? LIMIT 1');
    $firstCatSlug = localizedCategorySlug($postCats[0]);
    $fcStmt->execute($lang !== 'en' && $blogI18nReady ? [$firstCatSlug, $firstCatSlug] : [$postCats[0]['slug']]);
    $fcRow = $fcStmt->fetch();
    if ($fcRow) {
        $firstCatId = (int)$fcRow['id'];
        $relStmt = $db->prepare(
            'SELECT bp.id, bp.title, ' . ($blogI18nReady ? 'bp.title_tr, bp.title_de, bp.slug_tr, bp.slug_de, bp.excerpt_tr, bp.excerpt_de,' : '"" AS title_tr, "" AS title_de, "" AS slug_tr, "" AS slug_de, "" AS excerpt_tr, "" AS excerpt_de,') . ' bp.slug, bp.excerpt, bp.featured_image, bp.published_at,
                    (SELECT bc2.name FROM blog_post_categories bpc2
                     JOIN blog_categories bc2 ON bc2.id = bpc2.category_id
                     WHERE bpc2.post_id = bp.id LIMIT 1) AS cat_name,
                    ' . ($blogI18nReady
                        ? '(SELECT bc2.name_tr FROM blog_post_categories bpc2 JOIN blog_categories bc2 ON bc2.id = bpc2.category_id WHERE bpc2.post_id = bp.id LIMIT 1) AS cat_name_tr,
                           (SELECT bc2.name_de FROM blog_post_categories bpc2 JOIN blog_categories bc2 ON bc2.id = bpc2.category_id WHERE bpc2.post_id = bp.id LIMIT 1) AS cat_name_de'
                        : '"" AS cat_name_tr, "" AS cat_name_de') . '
             FROM blog_posts bp
             JOIN blog_post_categories bpc ON bpc.post_id = bp.id
             WHERE bpc.category_id = ? AND bp.id != ?
             ORDER BY bp.published_at DESC
             LIMIT 3'
        );
        $relStmt->execute([$firstCatId, $post['id']]);
        $relatedPosts = $relStmt->fetchAll();
    }
}

/* ── JSON-LD ─────────────────────────────────────────────────────────────── */
$authorProfileUrl = blogAuthorProfileUrl($post['author'] ?? '');

/* ── Breadcrumb data ─────────────────────────────────────────────────────── */
$firstCat      = !empty($postCats) ? $postCats[0] : null;
$bcPostTitle   = $postTitle;
$bcPostTrunc   = (mb_strlen($bcPostTitle) > 50) ? mb_substr($bcPostTitle, 0, 50) . '…' : $bcPostTitle;

$articleLdAuthor = [
    '@type' => 'Person',
    'name' => $post['author'] ?: 'Visual Design Journey',
];
if ($authorProfileUrl) {
    $articleLdAuthor['url'] = publicUrl($authorProfileUrl);
}
$articleLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $seoTitle,
    'description' => $seoDesc,
    'image' => [$ogImage],
    'datePublished' => $isoDate,
    'dateModified' => $isoDate,
    'author' => $articleLdAuthor,
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Visual Design Journey',
        'url' => publicUrl(),
    ],
    'url' => $canonicalUrl,
];
$breadcrumbItems = [
    [
        '@type' => 'ListItem',
        'position' => 1,
        'name' => __('nav.home', 'Home'),
        'item' => publicUrl(vdj_localized_url('/')),
    ],
    [
        '@type' => 'ListItem',
        'position' => 2,
        'name' => __('blog.title', 'Blog'),
        'item' => publicUrl(vdj_localized_url('/blog')),
    ],
];
if ($firstCat) {
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => 3,
        'name' => htmlspecialchars_decode(localizedCategoryName($firstCat)),
        'item' => publicUrl(blogCategoryUrl(localizedCategorySlug($firstCat))),
    ];
}
$breadcrumbItems[] = [
    '@type' => 'ListItem',
    'position' => count($breadcrumbItems) + 1,
    'name' => $seoTitle,
    'item' => $canonicalUrl,
];
$breadcrumbLd = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => $breadcrumbItems,
];

/* ── Page vars for header ────────────────────────────────────────────────── */
$pageTitle  = htmlspecialchars_decode($seoTitle);
$activeNav  = 'blog';
$metaDesc   = $seoDesc;
$metaImage  = $ogImage;
$ogType     = 'article';
$tagNames   = array_map('localizedTagName', $postTags);
$headExtra  = '<meta property="article:published_time" content="' . e($isoDate) . '"/>' . "\n";
$headExtra .= '<meta property="article:modified_time" content="' . e($isoDate) . '"/>' . "\n";
$headExtra .= '<meta name="twitter:card" content="summary_large_image"/>' . "\n";
$headExtra .= '<meta name="twitter:title" content="' . e($pageTitle) . '"/>' . "\n";
$headExtra .= '<meta name="twitter:description" content="' . e($metaDesc) . '"/>' . "\n";
$headExtra .= '<meta name="twitter:image" content="' . e($ogImage) . '"/>' . "\n";
if ($firstCat) {
    $headExtra .= '<meta property="article:section" content="' . e(localizedCategoryName($firstCat)) . '"/>' . "\n";
}
if (!empty($tagNames)) {
    $headExtra .= '<meta name="keywords" content="' . e(implode(', ', $tagNames)) . '"/>' . "\n";
    foreach ($tagNames as $tagName) {
        $headExtra .= '<meta property="article:tag" content="' . e($tagName) . '"/>' . "\n";
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Schema.org Article JSON-LD -->
<?= vdj_json_ld_script($articleLd, 'Article JSON-LD') ?>

<!-- BreadcrumbList JSON-LD -->
<?= vdj_json_ld_script($breadcrumbLd, 'BreadcrumbList JSON-LD') ?>

<main class="page-wrap" id="main-content">

  <!-- Breadcrumb navigation -->
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <div class="container">
      <ol class="breadcrumb__list">
        <li class="breadcrumb__item"><a href="<?= e(vdj_localized_url('/')) ?>"><?= e(__('nav.home', 'Home')) ?></a></li>
        <li class="breadcrumb__item"><a href="<?= e(vdj_localized_url('/blog')) ?>"><?= e(__('blog.title', 'Blog')) ?></a></li>
        <?php if ($firstCat): ?>
        <li class="breadcrumb__item"><a href="<?= e(blogCategoryUrl(localizedCategorySlug($firstCat))) ?>"><?= htmlspecialchars(localizedCategoryName($firstCat)) ?></a></li>
        <?php endif; ?>
        <li class="breadcrumb__item breadcrumb__item--current" aria-current="page"><?= htmlspecialchars($bcPostTrunc) ?></li>
      </ol>
    </div>
  </nav>

  <!-- Hero image -->
  <?php if ($post['featured_image']): ?>
  <div style="width:100%;max-height:480px;overflow:hidden;background:var(--surface);">
    <img src="<?= e(blogImageUrl($post['featured_image'])) ?>"
         alt="<?= htmlspecialchars($postTitle) ?>"
         style="width:100%;max-height:480px;object-fit:cover;display:block;"
         loading="eager"/>
  </div>
  <?php endif; ?>

  <div class="container">

    <!-- Back link -->
    <div style="padding-top:28px;margin-bottom:8px;">
      <a href="<?= e(vdj_localized_url('/blog')) ?>" style="font-size:13px;color:var(--muted);text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
        <i class="fa-solid fa-arrow-left" style="font-size:11px;"></i> <?= e(__('blog.title', 'Blog')) ?>
      </a>
    </div>

    <!-- Article wrapper -->
    <article style="max-width:740px;margin:0 auto;padding:0 0 80px;">

      <!-- Categories -->
      <?php if (!empty($postCats)): ?>
      <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px;">
        <?php foreach ($postCats as $cat): ?>
          <a href="<?= e(blogCategoryUrl(localizedCategorySlug($cat))) ?>"
             class="blog-tag" style="text-decoration:none;">
            <?= htmlspecialchars(localizedCategoryName($cat)) ?>
          </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Title -->
      <h1 style="font-size:clamp(1.6rem,4vw,2.4rem);font-weight:800;letter-spacing:-0.03em;line-height:1.2;margin:0 0 20px;color:var(--text);">
        <?= htmlspecialchars($postTitle) ?>
      </h1>

      <!-- Meta bar -->
      <div class="blog-meta" style="padding-bottom:24px;border-bottom:1px solid var(--border);margin-bottom:40px;">
        <?php if ($post['author']): ?>
          <?php if ($authorProfileUrl): ?>
            <a href="<?= e($authorProfileUrl) ?>" class="blog-author-link"><i class="fa-regular fa-user"></i> <?= htmlspecialchars($post['author']) ?></a>
          <?php else: ?>
            <span><i class="fa-regular fa-user"></i> <?= htmlspecialchars($post['author']) ?></span>
          <?php endif; ?>
        <?php endif; ?>
        <span><i class="fa-regular fa-calendar"></i> <?= $displayDate ?></span>
        <span><i class="fa-regular fa-clock"></i> <?= sprintf(e(__('blog.min_read', '%d min read')), $readingTime) ?></span>
      </div>

      <div style="padding:24px 0 8px;text-align:center;"><?= vdj_ad('banner_300x250') ?><?= vdj_ad('banner_468x60') ?></div>

      <!-- Content -->
      <div class="blog-content">
        <?php if ($translationMissing): ?>
          <section class="blog-translation-empty" role="status">
            <h2><?= e(__('blog.translation_pending_title', 'Translation is being prepared')) ?></h2>
            <?php if ($translatedExcerpt !== ''): ?>
              <p><?= e(strip_tags($translatedExcerpt)) ?></p>
            <?php endif; ?>
            <p><?= e(__('blog.translation_pending_body', 'This article translation is not available yet.')) ?></p>
            <a href="<?= e(vdj_localized_url('/blog')) ?>" class="btn btn--primary"><?= e(__('blog.translation_pending_cta', 'Back to articles')) ?></a>
          </section>
        <?php else: ?>
          <?= blogContentHtml($postContent) ?>
        <?php endif; ?>
      </div>

      <?php renderAdSlot('blog-post-after-content'); ?>

      <!-- Tags -->
      <?php if (!empty($postTags)): ?>
      <div style="margin-top:40px;padding-top:24px;border-top:1px solid var(--border);">
        <p style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:10px;">
          <i class="fa-solid fa-tags" style="margin-right:6px;"></i><?= e(__('blog.tags', 'Tags')) ?>
        </p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <?php foreach ($postTags as $tag): ?>
            <a href="<?= e(blogTagUrl(localizedTagSlug($tag))) ?>"
               style="display:inline-flex;align-items:center;padding:4px 12px;border-radius:var(--r-pill);background:var(--surface);border:1px solid var(--border);font-size:12px;font-weight:500;color:var(--text);text-decoration:none;transition:all .15s;">
              #<?= htmlspecialchars(localizedTagName($tag)) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Social share -->
      <?php
        $shareUrl   = rawurlencode($canonicalUrl);
        $shareTitle = rawurlencode($postTitle);
      ?>
      <div class="blog-share">
        <span class="blog-share__label"><?= e(__('blog.share', 'Share')) ?></span>
        <a href="https://twitter.com/intent/tweet?url=<?= $shareUrl ?>&text=<?= $shareTitle ?>"
           target="_blank" rel="noopener noreferrer" class="blog-share__btn blog-share__btn--twitter" aria-label="Share on X">
          <i class="fa-brands fa-x-twitter"></i>
        </a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $shareUrl ?>"
           target="_blank" rel="noopener noreferrer" class="blog-share__btn blog-share__btn--linkedin" aria-label="Share on LinkedIn">
          <i class="fa-brands fa-linkedin-in"></i>
        </a>
        <a href="https://pinterest.com/pin/create/button/?url=<?= $shareUrl ?>&description=<?= $shareTitle ?>"
           target="_blank" rel="noopener noreferrer" class="blog-share__btn blog-share__btn--pinterest" aria-label="Share on Pinterest">
          <i class="fa-brands fa-pinterest-p"></i>
        </a>
        <button class="blog-share__btn blog-share__btn--copy"
                onclick="navigator.clipboard.writeText(window.location.href).then(function(){this.innerHTML='<i class=\'fa-solid fa-check\'></i>';var btn=this;setTimeout(function(){btn.innerHTML='<i class=\'fa-solid fa-link\'></i>'},1500)}.bind(this))"
                aria-label="Copy link">
          <i class="fa-solid fa-link"></i>
        </button>
      </div>

      <!-- Back to blog button -->
      <div style="margin-top:48px;text-align:center;">
        <a href="<?= e(vdj_localized_url('/blog')) ?>" class="btn btn--secondary">
          <i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i><?= e(__('blog.back_to_blog', 'Back to Blog')) ?>
        </a>
      </div>

    </article>

    <!-- Related posts -->
    <?php if (!empty($relatedPosts)): ?>
    <section style="border-top:1px solid var(--border);padding:56px 0;" data-animate>
      <h2 style="font-size:1.25rem;font-weight:800;letter-spacing:-0.02em;margin:0 0 28px;">
        <i class="fa-solid fa-layer-group" style="color:var(--accent);margin-right:8px;"></i><?= e(__('blog.related_articles', 'Related Articles')) ?>
      </h2>
      <div class="blog-grid" style="grid-template-columns:repeat(3,1fr);">
        <?php foreach ($relatedPosts as $rel):
          $relTitle = localizedField($rel, 'title');
          $relExc  = trim(strip_tags(localizedField($rel, 'excerpt')));
          if ($relExc === '') $relExc = '';
          if (mb_strlen($relExc) > 120) $relExc = mb_substr($relExc, 0, 120) . '…';
          $relDate = vdjLocalizedDate($rel['published_at']);
        ?>
        <a href="<?= e(blogPostUrl($rel)) ?>"
           class="blog-card" style="text-decoration:none;">
          <?php if ($rel['featured_image']): ?>
          <div class="blog-card__img">
            <img src="<?= e(blogThumbnailUrl($rel['featured_image'], 520, 292)) ?>"
                 alt="<?= htmlspecialchars($relTitle) ?>"
                 loading="lazy" class="loaded"/>
          </div>
          <?php else: ?>
          <div class="blog-card__img blog-card__img--placeholder">
            <i class="fa-solid fa-pen-nib"></i>
          </div>
          <?php endif; ?>
          <div class="blog-card__body">
            <?php if ($rel['cat_name']): ?>
              <span class="blog-tag"><?= htmlspecialchars(localizedField($rel, 'cat_name')) ?></span>
            <?php endif; ?>
            <h3 class="blog-card__title"><?= htmlspecialchars($relTitle) ?></h3>
            <div class="blog-meta">
              <span><i class="fa-regular fa-calendar"></i> <?= $relDate ?></span>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

  </div><!-- /container -->
</main>

<style>
/* ── Blog post content styles ─────────────────────────────────────────── */
.blog-content { font-size: 1.0625rem; line-height: 1.78; color: var(--text); }
.blog-content p  { margin: 0 0 1.4em; }
.blog-content h1 { font-size: 1.9rem; font-weight: 800; letter-spacing: -.025em; margin: 2em 0 .6em; line-height: 1.2; }
.blog-content h2 { font-size: 1.5rem; font-weight: 800; letter-spacing: -.02em;  margin: 1.8em 0 .6em; line-height: 1.25; }
.blog-content h3 { font-size: 1.2rem; font-weight: 700; margin: 1.5em 0 .5em; }
.blog-content h4 { font-size: 1rem;   font-weight: 700; margin: 1.3em 0 .4em; }
.blog-content h5,
.blog-content h6 { font-size: .9rem;  font-weight: 700; margin: 1.2em 0 .4em; }
.blog-content img {
  max-width: 100%;
  height: auto;
  border-radius: var(--r-lg, 12px);
  display: block;
  margin: 1.8em auto;
}
.blog-content a { color: var(--accent); text-underline-offset: 3px; }
.blog-content a:hover { opacity: .8; }
.blog-content blockquote {
  border-left: 4px solid var(--accent);
  margin: 2em 0;
  padding: 1em 1.5em;
  background: var(--surface);
  border-radius: 0 var(--r-lg, 12px) var(--r-lg, 12px) 0;
  font-style: italic;
  color: var(--muted);
}
.blog-content ul,
.blog-content ol { margin: 0 0 1.4em 1.4em; padding: 0; }
.blog-content li { margin-bottom: .4em; }
.blog-content pre {
  background: #1e1e2e;
  color: #cdd6f4;
  padding: 1.25em 1.5em;
  border-radius: var(--r-lg, 12px);
  overflow-x: auto;
  font-size: .875rem;
  line-height: 1.6;
  margin: 1.8em 0;
}
.blog-content code {
  font-family: 'JetBrains Mono', 'Fira Code', monospace;
  font-size: .875em;
  background: var(--surface);
  padding: .15em .4em;
  border-radius: 4px;
  border: 1px solid var(--border);
}
.blog-content pre code {
  background: none;
  border: none;
  padding: 0;
  font-size: inherit;
}
.blog-content table {
  width: 100%;
  border-collapse: collapse;
  margin: 1.8em 0;
  font-size: .9rem;
}
.blog-content th,
.blog-content td {
  border: 1px solid var(--border);
  padding: 8px 12px;
  text-align: left;
}
.blog-content th { background: var(--surface); font-weight: 700; }
.blog-content figure { margin: 2em 0; }
.blog-content figcaption { text-align: center; font-size: .85rem; color: var(--muted); margin-top: .5em; }
.blog-content hr { border: none; border-top: 1px solid var(--border); margin: 2.5em 0; }
.blog-translation-empty {
  border: 1px solid var(--border);
  border-radius: 12px;
  background: var(--surface);
  padding: 24px;
}
.blog-translation-empty h2 {
  margin: 0 0 10px;
  font-size: 1.25rem;
}
.blog-translation-empty p {
  color: var(--muted);
}

/* Reuse blog-card / blog-tag / blog-meta from blog.php (same page load) */
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
.blog-meta { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; color: var(--muted); font-size: 12px; }
.blog-meta i { margin-right: 4px; }
.blog-author-link { color: var(--muted); text-decoration: none; }
.blog-author-link:hover { color: var(--accent); }
.blog-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}
.blog-card {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: box-shadow 0.2s, transform 0.2s;
  color: var(--text);
}
.blog-card:hover { box-shadow: 0 8px 32px rgba(0,0,0,.09); transform: translateY(-3px); }
.blog-card__img { aspect-ratio: 16/9; overflow: hidden; background: var(--surface); }
.blog-card__img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
.blog-card:hover .blog-card__img img { transform: scale(1.05); }
.blog-card__img--placeholder { display: flex; align-items: center; justify-content: center; }
.blog-card__img--placeholder i { font-size: 28px; color: var(--muted); opacity: .3; }
.blog-card__body { padding: 20px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
.blog-card__title { font-size: 1rem; font-weight: 700; line-height: 1.35; margin: 0; color: var(--text); }

@media (max-width: 900px) { .blog-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 560px) { .blog-grid { grid-template-columns: 1fr; } }

/* Breadcrumb */
.breadcrumb { padding: 12px 0; border-bottom: 1px solid var(--border); margin-bottom: 0; }
.breadcrumb__list { display: flex; align-items: center; gap: 6px; list-style: none; margin: 0; padding: 0; flex-wrap: wrap; }
.breadcrumb__item { display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--muted); }
.breadcrumb__item::after { content: '/'; color: var(--border); }
.breadcrumb__item:last-child::after { display: none; }
.breadcrumb__item a { color: var(--muted); text-decoration: none; transition: color .15s; }
.breadcrumb__item a:hover { color: var(--accent); }
.breadcrumb__item--current { color: var(--text); font-weight: 500; }

/* Social share */
.blog-share { display: flex; align-items: center; gap: 10px; padding: 24px 0; border-top: 1px solid var(--border); margin-top: 8px; }
.blog-share__label { font-size: 13px; font-weight: 600; color: var(--muted); margin-right: 4px; }
.blog-share__btn { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 50%; border: 1px solid var(--border); background: #fff; color: var(--text); font-size: 15px; text-decoration: none; transition: all .15s; cursor: pointer; }
.blog-share__btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.1); }
.blog-share__btn--twitter:hover { background: #000; border-color: #000; color: #fff; }
.blog-share__btn--linkedin:hover { background: #0077b5; border-color: #0077b5; color: #fff; }
.blog-share__btn--pinterest:hover { background: #e60023; border-color: #e60023; color: #fff; }
.blog-share__btn--copy:hover { background: var(--accent); border-color: var(--accent); color: #fff; }
</style>

<script>
(function () {
  document.querySelectorAll('.blog-content img').forEach(function (img) {
    if (!img.getAttribute('alt')) img.setAttribute('alt', <?= json_encode($postTitle) ?> + ' reference image');
    if (!img.getAttribute('loading')) img.setAttribute('loading', 'lazy');
    if (!img.getAttribute('decoding')) img.setAttribute('decoding', 'async');
  });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
