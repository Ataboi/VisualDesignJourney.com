<?php
/**
 * sitemap.xml.php — Dynamic XML sitemap
 * Served at /sitemap.xml via .htaccess rewrite
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');

$db      = getDB();
$siteUrl = rtrim(publicUrl(), '/');
$type    = $_GET['type'] ?? 'all';

$now = date('Y-m-d');

function sitemapEmitUrl(string $loc, string $lastmod, string $changefreq, string $priority): void {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($loc, ENT_XML1, 'UTF-8') . "</loc>\n";
    echo '    <lastmod>' . htmlspecialchars($lastmod, ENT_XML1, 'UTF-8') . "</lastmod>\n";
    echo '    <changefreq>' . htmlspecialchars($changefreq, ENT_XML1, 'UTF-8') . "</changefreq>\n";
    echo '    <priority>' . htmlspecialchars($priority, ENT_XML1, 'UTF-8') . "</priority>\n";
    echo "  </url>\n";
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

/* ── Static pages ──────────────────────────────────────────────────────────── */
$staticPages = [
    ['loc' => '/',             'priority' => '1.0', 'changefreq' => 'weekly',  'lastmod' => $now],
    ['loc' => '/blog',         'priority' => '0.9', 'changefreq' => 'daily',   'lastmod' => $now],
    ['loc' => '/curators',     'priority' => '0.7', 'changefreq' => 'weekly',  'lastmod' => $now],
    ['loc' => '/trends',       'priority' => '0.7', 'changefreq' => 'daily',   'lastmod' => $now],
    ['loc' => '/vibe',         'priority' => '0.6', 'changefreq' => 'weekly',  'lastmod' => $now],
    ['loc' => '/archive',            'priority' => '0.7', 'changefreq' => 'weekly',  'lastmod' => $now],
    ['loc' => '/archive/bauhaus',    'priority' => '0.8', 'changefreq' => 'weekly',  'lastmod' => $now],
    ['loc' => '/archive/swiss-grid', 'priority' => '0.8', 'changefreq' => 'weekly',  'lastmod' => $now],
    ['loc' => '/archive/brutalist',  'priority' => '0.8', 'changefreq' => 'weekly',  'lastmod' => $now],
    ['loc' => '/archive/minimalism', 'priority' => '0.8', 'changefreq' => 'weekly',  'lastmod' => $now],
    ['loc' => '/archive/art-nouveau','priority' => '0.8', 'changefreq' => 'weekly',  'lastmod' => $now],
    ['loc' => '/archive/editorial',  'priority' => '0.8', 'changefreq' => 'weekly',  'lastmod' => $now],
    ['loc' => '/archive/web-design', 'priority' => '0.8', 'changefreq' => 'weekly',  'lastmod' => $now],
    ['loc' => '/archive/poster',     'priority' => '0.8', 'changefreq' => 'weekly',  'lastmod' => $now],
    ['loc' => '/archive/typography', 'priority' => '0.8', 'changefreq' => 'weekly',  'lastmod' => $now],
    ['loc' => '/archive/branding',   'priority' => '0.8', 'changefreq' => 'weekly',  'lastmod' => $now],
    ['loc' => '/glossary',     'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $now],
    ['loc' => '/colors',       'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $now],
    ['loc' => '/premium',      'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $now],
    ['loc' => '/submit-source', 'priority' => '0.5', 'changefreq' => 'monthly', 'lastmod' => $now],
    ['loc' => '/sponsor',      'priority' => '0.4', 'changefreq' => 'monthly', 'lastmod' => $now],
    ['loc' => '/contact',      'priority' => '0.4', 'changefreq' => 'monthly', 'lastmod' => $now],
    ['loc' => '/about',        'priority' => '0.4', 'changefreq' => 'yearly',  'lastmod' => $now],
    ['loc' => '/editorial-policy', 'priority' => '0.4', 'changefreq' => 'yearly', 'lastmod' => $now],
    ['loc' => '/privacy',      'priority' => '0.3', 'changefreq' => 'yearly',  'lastmod' => $now],
    ['loc' => '/terms',        'priority' => '0.3', 'changefreq' => 'yearly',  'lastmod' => $now],
];

if ($type === 'all' || $type === 'pages') {
    foreach ($staticPages as $page) {
        sitemapEmitUrl($siteUrl . $page['loc'], $page['lastmod'], $page['changefreq'], $page['priority']);
        if (!in_array($page['loc'], ['/'], true)) {
            sitemapEmitUrl($siteUrl . '/tr' . $page['loc'], $page['lastmod'], $page['changefreq'], $page['priority']);
            sitemapEmitUrl($siteUrl . '/de' . $page['loc'], $page['lastmod'], $page['changefreq'], $page['priority']);
        }
    }
    sitemapEmitUrl($siteUrl . '/tr/', $now, 'weekly', '1.0');
    sitemapEmitUrl($siteUrl . '/de/', $now, 'weekly', '1.0');
}

/* ── Blog categories ───────────────────────────────────────────────────────── */
if ($type === 'all' || $type === 'pages') {
    try {
        $catI18n = dbColumnExists($db, 'blog_categories', 'slug_tr');
        $stmt = $db->query(
            ($catI18n
            ? 'SELECT bc.slug, bc.slug_tr, bc.slug_de FROM blog_categories bc'
            : 'SELECT bc.slug, "" AS slug_tr, "" AS slug_de FROM blog_categories bc') . '
             JOIN blog_post_categories bpc ON bpc.category_id = bc.id
             GROUP BY ' . ($catI18n ? 'bc.id, bc.slug, bc.slug_tr, bc.slug_de' : 'bc.id, bc.slug') . ' HAVING COUNT(bpc.post_id) > 0'
        );
        foreach ($stmt->fetchAll() as $cat) {
            sitemapEmitUrl($siteUrl . '/category/' . rawurlencode($cat['slug']) . '/', $now, 'weekly', '0.7');
            sitemapEmitUrl($siteUrl . '/tr/category/' . rawurlencode($cat['slug_tr'] ?: $cat['slug']) . '/', $now, 'weekly', '0.7');
            sitemapEmitUrl($siteUrl . '/de/category/' . rawurlencode($cat['slug_de'] ?: $cat['slug']) . '/', $now, 'weekly', '0.7');
        }
    } catch (PDOException $e) { /* blog tables missing — skip */ }

    try {
        $tagI18n = dbColumnExists($db, 'blog_tags', 'slug_tr');
        $stmt = $db->query(
            ($tagI18n
            ? 'SELECT bt.slug, bt.slug_tr, bt.slug_de FROM blog_tags bt'
            : 'SELECT bt.slug, "" AS slug_tr, "" AS slug_de FROM blog_tags bt') . '
             JOIN blog_post_tags bpt ON bpt.tag_id = bt.id
             GROUP BY ' . ($tagI18n ? 'bt.id, bt.slug, bt.slug_tr, bt.slug_de' : 'bt.id, bt.slug') . ' HAVING COUNT(bpt.post_id) > 0'
        );
        foreach ($stmt->fetchAll() as $tag) {
            sitemapEmitUrl($siteUrl . '/tag/' . rawurlencode($tag['slug']) . '/', $now, 'weekly', '0.5');
            sitemapEmitUrl($siteUrl . '/tr/tag/' . rawurlencode($tag['slug_tr'] ?: $tag['slug']) . '/', $now, 'weekly', '0.5');
            sitemapEmitUrl($siteUrl . '/de/tag/' . rawurlencode($tag['slug_de'] ?: $tag['slug']) . '/', $now, 'weekly', '0.5');
        }
    } catch (PDOException $e) { /* blog tag tables missing — skip */ }
}

/* ── Blog posts ────────────────────────────────────────────────────────────── */
if ($type === 'all' || $type === 'posts') {
    try {
        $postI18n = dbColumnExists($db, 'blog_posts', 'slug_tr') && dbColumnExists($db, 'blog_posts', 'content_tr');
        $stmt = $db->query(
            ($postI18n
            ? "SELECT slug, COALESCE(slug_tr,'') AS slug_tr, COALESCE(slug_de,'') AS slug_de, COALESCE(content_tr,'') AS content_tr, COALESCE(content_de,'') AS content_de, created_at, published_at"
            : "SELECT slug, '' AS slug_tr, '' AS slug_de, '' AS content_tr, '' AS content_de, created_at, published_at") . "
             FROM blog_posts WHERE slug <> '' ORDER BY published_at DESC"
        );
        while ($bp = $stmt->fetch()) {
            $raw     = $bp['published_at'] ?? $bp['created_at'] ?? null;
            $lastmod = $raw ? date('Y-m-d', strtotime($raw)) : $now;
            // EN
            sitemapEmitUrl($siteUrl . '/blog/' . rawurlencode($bp['slug']) . '/', $lastmod, 'monthly', '0.65');
            // TR
            $slTr = $bp['slug_tr'] ?: $bp['slug'];
            $trPriority = trim((string)$bp['content_tr']) !== '' ? '0.65' : '0.35';
            sitemapEmitUrl($siteUrl . '/tr/blog/' . rawurlencode($slTr) . '/', $lastmod, 'monthly', $trPriority);
            // DE
            $slDe = $bp['slug_de'] ?: $bp['slug'];
            $dePriority = trim((string)$bp['content_de']) !== '' ? '0.65' : '0.35';
            sitemapEmitUrl($siteUrl . '/de/blog/' . rawurlencode($slDe) . '/', $lastmod, 'monthly', $dePriority);
        }
    } catch (Throwable $e) {
        // blog_posts table missing or column mismatch — skip
    }
}

/* ── Board pages ───────────────────────────────────────────────────────────── */
if ($type === 'all' || $type === 'boards') {
    try {
        $stmt = $db->query(
            (dbColumnExists($db, 'boards', 'slug_tr')
            ? "SELECT id, slug, slug_tr, slug_de, created_at FROM boards WHERE is_draft=0 ORDER BY created_at DESC LIMIT 5000"
            : "SELECT id, '' AS slug, '' AS slug_tr, '' AS slug_de, created_at FROM boards WHERE is_draft=0 ORDER BY created_at DESC LIMIT 5000")
        );
        while ($b = $stmt->fetch()) {
            $raw     = $b['created_at'] ?? null;
            $lastmod = $raw ? date('Y-m-d', strtotime($raw)) : $now;
            $baseSlug = $b['slug'] !== '' ? '-' . rawurlencode($b['slug']) : '';
            $trSlug = ($b['slug_tr'] ?: $b['slug']) !== '' ? '-' . rawurlencode($b['slug_tr'] ?: $b['slug']) : '';
            $deSlug = ($b['slug_de'] ?: $b['slug']) !== '' ? '-' . rawurlencode($b['slug_de'] ?: $b['slug']) : '';
            sitemapEmitUrl($siteUrl . '/board/' . (int)$b['id'] . $baseSlug . '/', $lastmod, 'monthly', '0.7');
            sitemapEmitUrl($siteUrl . '/tr/board/' . (int)$b['id'] . $trSlug . '/', $lastmod, 'monthly', '0.7');
            sitemapEmitUrl($siteUrl . '/de/board/' . (int)$b['id'] . $deSlug . '/', $lastmod, 'monthly', '0.7');
        }
    } catch (Throwable $e) {
        // boards table missing — skip
    }
}

/* ── Curator profiles ──────────────────────────────────────────────────────── */
if ($type === 'all' || $type === 'boards') {
    try {
        $curators = $db->query(
            "SELECT username, created_at FROM users
             WHERE is_verified=1
                OR (SELECT COUNT(*) FROM boards b WHERE b.user_id=users.id AND b.is_draft=0) >= 3
             LIMIT 1000"
        );
        while ($c = $curators->fetch()) {
            $raw     = $c['created_at'] ?? null;
            $lastmod = $raw ? date('Y-m-d', strtotime($raw)) : $now;
            sitemapEmitUrl($siteUrl . '/profile/' . rawurlencode($c['username']), $lastmod, 'weekly', '0.6');
        }
    } catch (Throwable $e) {
        // users table missing — skip
    }
}

echo '</urlset>' . "\n";
