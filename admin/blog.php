<?php
require_once __DIR__ . '/_guard.php';

$flash = ''; $flashType = 'success';

// Check blog tables exist
$blogReady = false;
try { $db->query('SELECT 1 FROM blog_posts LIMIT 1'); $blogReady = true; } catch(PDOException $e) {}

$q       = trim($_GET['q'] ?? '');
$catSlug = trim($_GET['cat'] ?? '');
$translationFilter = trim($_GET['translation'] ?? '');
$allowedTranslationFilters = ['', 'missing_tr', 'missing_de', 'missing_any', 'dirty', 'ready_review'];
if (!in_array($translationFilter, $allowedTranslationFilters, true)) $translationFilter = '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$posts = []; $total = 0; $totalPages = 1; $filterCats = [];
$totalPosts = 0; $totalCats = 0; $totalTags = 0;
$missingTrCount = 0; $missingDeCount = 0; $dirtyCount = 0;
$readyReviewCount = 0;
$blogI18nReady = false;

if ($blogReady) {
    $blogI18nReady = dbColumnExists($db, 'blog_posts', 'title_tr')
        && dbColumnExists($db, 'blog_posts', 'content_tr');
    // Build WHERE
    $where = ['1=1']; $params = [];
    if ($q !== '') {
        $where[] = '(bp.title LIKE ? OR bp.author LIKE ? OR bp.slug LIKE ?)';
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if ($catSlug !== '') {
        $where[] = 'EXISTS (SELECT 1 FROM blog_post_categories bpc JOIN blog_categories bc ON bc.id=bpc.category_id WHERE bpc.post_id=bp.id AND bc.slug=?)';
        $params[] = $catSlug;
    }
    if ($translationFilter === 'dirty') {
        $dirtyLikes = [];
        foreach (['I understand the previous response', 'JSON output format', 'ad_1', 'lorem ipsum', 'shortcode', 'paragraphs'] as $dirtyNeedle) {
            $dirtyLikes[] = '(bp.title LIKE ? OR bp.excerpt LIKE ? OR bp.content LIKE ?)';
            $params[] = "%$dirtyNeedle%";
            $params[] = "%$dirtyNeedle%";
            $params[] = "%$dirtyNeedle%";
        }
        $where[] = '(' . implode(' OR ', $dirtyLikes) . ')';
    } elseif ($blogI18nReady) {
        if ($translationFilter === 'missing_tr') {
            $where[] = "(COALESCE(bp.title_tr,'')='' OR COALESCE(bp.slug_tr,'')='' OR COALESCE(bp.content_tr,'')='')";
        } elseif ($translationFilter === 'missing_de') {
            $where[] = "(COALESCE(bp.title_de,'')='' OR COALESCE(bp.slug_de,'')='' OR COALESCE(bp.content_de,'')='')";
        } elseif ($translationFilter === 'missing_any') {
            $where[] = "((COALESCE(bp.title_tr,'')='' OR COALESCE(bp.slug_tr,'')='' OR COALESCE(bp.content_tr,'')='') OR (COALESCE(bp.title_de,'')='' OR COALESCE(bp.slug_de,'')='' OR COALESCE(bp.content_de,'')=''))";
        } elseif ($translationFilter === 'ready_review') {
            $where[] = "(COALESCE(bp.title_tr,'')<>'' AND COALESCE(bp.slug_tr,'')<>'' AND COALESCE(bp.content_tr,'')<>'' AND COALESCE(bp.title_de,'')<>'' AND COALESCE(bp.slug_de,'')<>'' AND COALESCE(bp.content_de,'')<>'')";
        }
    } elseif ($translationFilter !== '') {
        $where[] = '1=0';
    }
    $whereSQL = implode(' AND ', $where);

    // Count
    $cs = $db->prepare("SELECT COUNT(*) FROM blog_posts bp WHERE $whereSQL");
    $cs->execute($params); $total = (int)$cs->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));

    // Posts
    $localizedSelect = $blogI18nReady
        ? 'bp.title_tr, bp.title_de, bp.slug_tr, bp.slug_de, bp.excerpt_tr, bp.excerpt_de, bp.content_tr, bp.content_de, bp.seo_description_tr, bp.seo_description_de,'
        : "'' AS title_tr, '' AS title_de, '' AS slug_tr, '' AS slug_de, '' AS excerpt_tr, '' AS excerpt_de, '' AS content_tr, '' AS content_de, '' AS seo_description_tr, '' AS seo_description_de,";
    $s = $db->prepare("SELECT bp.id, bp.title, bp.slug, bp.excerpt, bp.content, bp.author, bp.published_at, bp.featured_image, $localizedSelect
        (SELECT bc.name FROM blog_post_categories bpc JOIN blog_categories bc ON bc.id=bpc.category_id WHERE bpc.post_id=bp.id LIMIT 1) AS cat_name
        FROM blog_posts bp WHERE $whereSQL ORDER BY bp.published_at DESC LIMIT $perPage OFFSET $offset");
    $s->execute($params); $posts = $s->fetchAll();

    // Categories for filter
    $cs2 = $db->query('SELECT bc.name, bc.slug, COUNT(bpc.post_id) AS cnt FROM blog_categories bc JOIN blog_post_categories bpc ON bpc.category_id=bc.id GROUP BY bc.id ORDER BY cnt DESC LIMIT 15');
    $filterCats = $cs2->fetchAll();

    // Stats
    $totalPosts = (int)$db->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
    $totalCats  = (int)$db->query('SELECT COUNT(*) FROM blog_categories')->fetchColumn();
    $totalTags  = (int)$db->query('SELECT COUNT(*) FROM blog_tags')->fetchColumn();
    if ($blogI18nReady) {
        $missingTrCount = (int)$db->query("SELECT COUNT(*) FROM blog_posts WHERE COALESCE(title_tr,'')='' OR COALESCE(slug_tr,'')='' OR COALESCE(content_tr,'')=''")->fetchColumn();
        $missingDeCount = (int)$db->query("SELECT COUNT(*) FROM blog_posts WHERE COALESCE(title_de,'')='' OR COALESCE(slug_de,'')='' OR COALESCE(content_de,'')=''")->fetchColumn();
        $readyReviewCount = (int)$db->query("SELECT COUNT(*) FROM blog_posts WHERE COALESCE(title_tr,'')<>'' AND COALESCE(slug_tr,'')<>'' AND COALESCE(content_tr,'')<>'' AND COALESCE(title_de,'')<>'' AND COALESCE(slug_de,'')<>'' AND COALESCE(content_de,'')<>''")->fetchColumn();
    }
    $dirtyCount = (int)$db->query("SELECT COUNT(*) FROM blog_posts WHERE title LIKE '%I understand the previous response%' OR excerpt LIKE '%I understand the previous response%' OR content LIKE '%I understand the previous response%' OR title LIKE '%JSON output format%' OR excerpt LIKE '%JSON output format%' OR content LIKE '%JSON output format%' OR title LIKE '%ad_1%' OR excerpt LIKE '%ad_1%' OR content LIKE '%ad_1%' OR title LIKE '%lorem ipsum%' OR excerpt LIKE '%lorem ipsum%' OR content LIKE '%lorem ipsum%' OR title LIKE '%shortcode%' OR excerpt LIKE '%shortcode%' OR content LIKE '%shortcode%' OR title LIKE '%paragraphs%' OR excerpt LIKE '%paragraphs%' OR content LIKE '%paragraphs%'")->fetchColumn();
}

$currentPage = 'blog';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blog — Admin — Visual Design Journey</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/admin/admin.css">
  <style>
    /* Mini stat cards for blog — 3-col compact variant */
    .blog-stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 14px;
        margin-bottom: 24px;
    }
    .blog-stat-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 16px 18px;
        box-shadow: var(--shadow);
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .blog-stat-card .bsc-icon {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        background: var(--accent-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: var(--accent);
        flex-shrink: 0;
    }
    .blog-stat-card .bsc-num {
        font-family: 'Manrope', sans-serif;
        font-size: 22px;
        font-weight: 700;
        color: var(--text);
        line-height: 1;
    }
    .blog-stat-card .bsc-label {
        font-size: 12px;
        color: var(--text-muted);
        margin-top: 3px;
    }

    /* Category filter pills */
    .cat-filter-wrap {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        align-items: center;
    }
    .cat-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        border: 1px solid var(--border);
        background: var(--white);
        color: var(--text-muted);
        transition: background .15s, color .15s, border-color .15s;
        white-space: nowrap;
    }
    .cat-pill:hover { background: #f1f5f9; color: var(--text); }
    .cat-pill.active { background: var(--accent); color: #fff; border-color: var(--accent); }
    .cat-pill .cnt { font-size: 11px; opacity: .75; }

    /* Blog thumbnail */
    .blog-thumb {
        width: 70px;
        height: 44px;
        border-radius: 6px;
        object-fit: cover;
        background: var(--accent-light);
        display: block;
        flex-shrink: 0;
    }
    .blog-thumb-placeholder {
        width: 70px;
        height: 44px;
        border-radius: 6px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--border);
        font-size: 18px;
        flex-shrink: 0;
    }

    /* Warning card */
    .warning-card {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: var(--radius);
        padding: 20px 24px;
        display: flex;
        align-items: flex-start;
        gap: 14px;
    }
    .warning-card i {
        font-size: 22px;
        color: var(--warning);
        flex-shrink: 0;
        margin-top: 2px;
    }
    .warning-card h3 { font-size: 15px; font-weight: 600; color: #92400e; margin-bottom: 4px; }
    .warning-card p  { font-size: 13px; color: #a16207; line-height: 1.6; }
    .warning-card code {
        background: #fef3c7;
        padding: 1px 6px;
        border-radius: 4px;
        font-family: monospace;
        font-size: 12px;
        color: #92400e;
    }
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }
    .status-pill.ok { background: #dcfce7; color: #166534; }
    .status-pill.warn { background: #fef3c7; color: #92400e; }
    .status-pill.partial { background: #e0f2fe; color: #075985; }
    .status-pill.dirty { background: #fee2e2; color: #991b1b; }
    .admin-select {
        border: 1px solid var(--border);
        border-radius: 10px;
        min-height: 40px;
        padding: 0 12px;
        background: var(--white);
        color: var(--text);
        font-size: 13px;
    }

    @media (max-width: 768px) {
        .blog-stat-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
<?php require_once __DIR__ . '/_sidebar.php'; ?>

<main class="admin-main">
    <div class="admin-topbar">
        <div class="admin-header">
            <h1>Blog Posts</h1>
            <p>Browse and search blog content imported from WordPress.</p>
        </div>
        <button class="hamburger" id="hamburger"><i class="fa-solid fa-bars"></i></button>
    </div>

    <?php if (!$blogReady): ?>
        <!-- Not ready warning -->
        <div class="warning-card">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <h3>Blog tables not imported yet</h3>
                <p>
                    Import <code>blog-schema.sql</code> then <code>blog-import.sql</code> in phpMyAdmin to enable
                    blog management. Once both files have been run, refresh this page.
                </p>
            </div>
        </div>

    <?php else: ?>
        <!-- Mini stat cards -->
        <div class="blog-stat-grid">
            <div class="blog-stat-card">
                <div class="bsc-icon"><i class="fa-solid fa-newspaper"></i></div>
                <div>
                    <div class="bsc-num"><?= number_format($totalPosts) ?></div>
                    <div class="bsc-label">Total Posts</div>
                </div>
            </div>
            <div class="blog-stat-card">
                <div class="bsc-icon"><i class="fa-solid fa-folder-open"></i></div>
                <div>
                    <div class="bsc-num"><?= number_format($totalCats) ?></div>
                    <div class="bsc-label">Categories</div>
                </div>
            </div>
            <div class="blog-stat-card">
                <div class="bsc-icon"><i class="fa-solid fa-tags"></i></div>
                <div>
                    <div class="bsc-num"><?= number_format($totalTags) ?></div>
                    <div class="bsc-label">Tags</div>
                </div>
            </div>
            <a class="blog-stat-card" href="/admin/blog.php?translation=missing_tr" style="text-decoration:none;">
                <div class="bsc-icon"><i class="fa-solid fa-language"></i></div>
                <div>
                    <div class="bsc-num"><?= number_format($missingTrCount) ?></div>
                    <div class="bsc-label">Missing Turkish</div>
                </div>
            </a>
            <a class="blog-stat-card" href="/admin/blog.php?translation=missing_de" style="text-decoration:none;">
                <div class="bsc-icon"><i class="fa-solid fa-language"></i></div>
                <div>
                    <div class="bsc-num"><?= number_format($missingDeCount) ?></div>
                    <div class="bsc-label">Missing German</div>
                </div>
            </a>
            <a class="blog-stat-card" href="/admin/blog.php?translation=dirty" style="text-decoration:none;">
                <div class="bsc-icon"><i class="fa-solid fa-broom"></i></div>
                <div>
                    <div class="bsc-num"><?= number_format($dirtyCount) ?></div>
                    <div class="bsc-label">Dirty Candidates</div>
                </div>
            </a>
            <a class="blog-stat-card" href="/admin/blog.php?translation=ready_review" style="text-decoration:none;">
                <div class="bsc-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                <div>
                    <div class="bsc-num"><?= number_format($readyReviewCount) ?></div>
                    <div class="bsc-label">Ready for Review</div>
                </div>
            </a>
        </div>

        <!-- Toolbar: search + category pills -->
        <div class="toolbar" style="flex-wrap:wrap;gap:10px;align-items:flex-start;margin-bottom:14px;">
            <form method="get" action="/admin/blog.php" class="search-wrap" style="flex-shrink:0;">
                <?php if ($catSlug !== ''): ?>
                    <input type="hidden" name="cat" value="<?= e($catSlug) ?>">
                <?php endif; ?>
                <?php if ($translationFilter !== ''): ?>
                    <input type="hidden" name="translation" value="<?= e($translationFilter) ?>">
                <?php endif; ?>
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" name="q" class="search-input" placeholder="Search title or author…" value="<?= e($q) ?>">
            </form>
            <form method="get" action="/admin/blog.php" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
                <?php if ($catSlug !== ''): ?><input type="hidden" name="cat" value="<?= e($catSlug) ?>"><?php endif; ?>
                <select name="translation" class="admin-select" onchange="this.form.submit()" aria-label="Translation filter">
                    <option value="" <?= $translationFilter === '' ? 'selected' : '' ?>>All translation states</option>
                    <option value="missing_tr" <?= $translationFilter === 'missing_tr' ? 'selected' : '' ?>>Missing Turkish</option>
                    <option value="missing_de" <?= $translationFilter === 'missing_de' ? 'selected' : '' ?>>Missing German</option>
                    <option value="missing_any" <?= $translationFilter === 'missing_any' ? 'selected' : '' ?>>Missing TR or DE</option>
                    <option value="dirty" <?= $translationFilter === 'dirty' ? 'selected' : '' ?>>Dirty content</option>
                    <option value="ready_review" <?= $translationFilter === 'ready_review' ? 'selected' : '' ?>>Ready for review</option>
                </select>
            </form>
            <span class="result-count" style="margin-left:auto;align-self:center;">
                <?= number_format($total) ?> post<?= $total !== 1 ? 's' : '' ?>
            </span>
        </div>

        <!-- Category filter pills -->
        <?php if ($filterCats): ?>
        <div class="cat-filter-wrap" style="margin-bottom:20px;">
            <a href="?<?= http_build_query(array_merge($_GET, ['cat' => '', 'page' => 1])) ?>"
               class="cat-pill <?= $catSlug === '' ? 'active' : '' ?>">
                All
            </a>
            <?php foreach ($filterCats as $cat): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['cat' => $cat['slug'], 'page' => 1])) ?>"
                   class="cat-pill <?= $catSlug === $cat['slug'] ? 'active' : '' ?>">
                    <?= e($cat['name']) ?>
                    <span class="cnt">(<?= (int)$cat['cnt'] ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Table -->
        <div class="admin-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width:42px;">#</th>
                        <th style="width:80px;">Image</th>
                        <th>Title</th>
                        <th style="width:130px;">Category</th>
                        <th style="width:92px;">TR</th>
                        <th style="width:92px;">DE</th>
                        <th style="width:110px;">Content</th>
                        <th style="width:120px;">Author</th>
                        <th style="width:110px;">Published</th>
                        <th style="width:80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($posts): ?>
                <?php foreach ($posts as $index => $post): ?>
                    <?php
                      $trStatus = blogTranslationStatus($post, 'tr');
                      $deStatus = blogTranslationStatus($post, 'de');
                      $dirty = blogHasDirtyContent($post);
                    ?>
                    <tr>
                        <td class="text-muted"><?= $offset + $index + 1 ?></td>
                        <td>
                            <?php if (!empty($post['featured_image'])): ?>
                                <img src="<?= e(blogThumbnailUrl($post['featured_image'], 120, 80)) ?>" alt="" class="blog-thumb">
                            <?php else: ?>
                                <div class="blog-thumb-placeholder"><i class="fa-regular fa-image"></i></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= e(blogCanonicalPath($post, 'en')) ?>" target="_blank"
                               style="font-weight:500;color:var(--text);text-decoration:none;"
                               title="<?= e($post['title']) ?>">
                                <?= e($post['title']) ?>
                            </a>
                        </td>
                        <td>
                            <?php if (!empty($post['cat_name'])): ?>
                                <span class="badge badge-admin" style="background:#ede9fe;color:#5b21b6;">
                                    <?= e($post['cat_name']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-pill <?= $trStatus['is_translated'] ? 'ok' : ($trStatus['is_partial'] ? 'partial' : 'warn') ?>" title="<?= e($trStatus['label']) ?>">
                                <i class="fa-solid <?= $trStatus['is_translated'] ? 'fa-check' : ($trStatus['is_partial'] ? 'fa-circle-half-stroke' : 'fa-triangle-exclamation') ?>"></i> TR
                            </span>
                        </td>
                        <td>
                            <span class="status-pill <?= $deStatus['is_translated'] ? 'ok' : ($deStatus['is_partial'] ? 'partial' : 'warn') ?>" title="<?= e($deStatus['label']) ?>">
                                <i class="fa-solid <?= $deStatus['is_translated'] ? 'fa-check' : ($deStatus['is_partial'] ? 'fa-circle-half-stroke' : 'fa-triangle-exclamation') ?>"></i> DE
                            </span>
                        </td>
                        <td>
                            <?php if ($dirty): ?>
                                <span class="status-pill dirty" title="Potential AI, JSON, shortcode, lorem ipsum, ad marker, or duplicate-title artifact">
                                    <i class="fa-solid fa-broom"></i> Dirty
                                </span>
                            <?php else: ?>
                                <span class="status-pill ok"><i class="fa-solid fa-check"></i> Clean</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted"><?= e($post['author'] ?? '—') ?></td>
                        <td class="text-muted" style="white-space:nowrap;">
                            <?php if (!empty($post['published_at'])): ?>
                                <?= date('M j, Y', strtotime($post['published_at'])) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                              <a href="<?= e(blogCanonicalPath($post, 'en')) ?>" target="_blank"
                                 class="btn btn-sm" style="border:1px solid var(--border);background:var(--white);color:var(--text);text-decoration:none;">
                                  <i class="fa-solid fa-eye"></i> View
                              </a>
                              <a href="/admin/blog-translate.php?id=<?= (int)$post['id'] ?>"
                                 class="btn btn-sm" style="border:1px solid var(--border);background:var(--white);color:var(--text);text-decoration:none;">
                                  🌐 Translate
                              </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10">
                            <div class="empty-state">
                                <i class="fa-solid fa-newspaper"></i>
                                No posts found.
                                <p>Try a different search term or category filter.</p>
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

    <?php endif; // $blogReady ?>
</main>
</body>
</html>
