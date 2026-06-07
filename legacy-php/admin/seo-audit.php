<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/_metrics.php';

$currentPage = 'seo-audit';
$uploadsRoot = realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads');

function seoAuditScalar(PDO $db, string $sql): int {
    try {
        return (int)$db->query($sql)->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function seoAuditMediaRefs(?string $value): array {
    $value = (string)$value;
    if ($value === '') return [];
    $refs = [];
    if (preg_match_all('#(?:https?://[^"\')\s]+/)?(?:wp-content/)?uploads/([^"\')\s\\\\]+)#i', $value, $m)) {
        foreach ($m[1] as $rel) $refs[] = ltrim($rel, '/');
    }
    return array_values(array_unique($refs));
}

function seoAuditMediaExists(string $root, string $rel): bool {
    $rel = ltrim(str_replace('\\', '/', $rel), '/');
    if ($rel === '' || str_contains($rel, '..')) return false;
    return is_file($root . '/' . $rel);
}

function seoAuditStructuredDataSourceIssues(): array {
    $targets = [
        __DIR__ . '/../blog-post.php',
        __DIR__ . '/../includes/header.php',
    ];
    $issues = [];
    foreach ($targets as $file) {
        $raw = is_file($file) ? file($file, FILE_IGNORE_NEW_LINES) : false;
        if ($raw === false) {
            $issues[] = basename($file) . ': file could not be read';
            continue;
        }
        foreach ($raw as $idx => $line) {
            if (stripos($line, '<script') !== false
                && stripos($line, 'application/ld+json') !== false
                && stripos($line, 'vdj_json_ld_script') === false) {
                $issues[] = str_replace(dirname(__DIR__) . '/', '', $file) . ':' . ($idx + 1);
            }
        }
    }
    return $issues;
}

$totalPosts = adminBlogPostCount($db);
$missingMeta = seoAuditScalar($db, "SELECT COUNT(*) FROM blog_posts WHERE COALESCE(seo_description, '') = '' AND COALESCE(excerpt, '') = ''");
$missingFeatured = seoAuditScalar($db, "SELECT COUNT(*) FROM blog_posts WHERE COALESCE(featured_image, '') = ''");
$shortContent = seoAuditScalar($db, "SELECT COUNT(*) FROM blog_posts WHERE CHAR_LENGTH(TRIM(REPLACE(REPLACE(content, '\r', ' '), '\n', ' '))) < 500");
$duplicateSlugs = seoAuditScalar($db, "SELECT COUNT(*) FROM (SELECT slug FROM blog_posts GROUP BY slug HAVING COUNT(*) > 1) d");
$emptyPosts = seoAuditScalar($db, "SELECT COUNT(*) FROM blog_posts WHERE TRIM(COALESCE(content, '')) = ''");
$structuredDataSourceIssues = seoAuditStructuredDataSourceIssues();

$brokenMedia = 0;
$placeholderMedia = 0;
$sampleIssues = [];
try {
    $stmt = $db->query("SELECT id, title, slug, featured_image, content FROM blog_posts ORDER BY published_at DESC");
    while ($post = $stmt->fetch()) {
        $refs = seoAuditMediaRefs($post['featured_image'] ?? '');
        $refs = array_merge($refs, seoAuditMediaRefs($post['content'] ?? ''));
        foreach (array_unique($refs) as $rel) {
            if (!seoAuditMediaExists($uploadsRoot, $rel)) {
                $brokenMedia++;
                if (count($sampleIssues) < 10) {
                    $sampleIssues[] = [
                        'title' => $post['title'] ?: '(Untitled)',
                        'url' => blogPostUrl((string)$post['slug']),
                        'media' => $rel,
                    ];
                }
            }
        }
        if (stripos((string)$post['content'], 'Media unavailable') !== false || stripos((string)$post['content'], 'placeholder') !== false) {
            $placeholderMedia++;
        }
    }
} catch (Throwable $e) {}

$checks = [
    ['label' => 'Missing meta description', 'value' => $missingMeta, 'hint' => 'Posts with neither SEO description nor excerpt.'],
    ['label' => 'Missing featured image', 'value' => $missingFeatured, 'hint' => 'Posts without a featured image.'],
    ['label' => 'Broken or missing media references', 'value' => $brokenMedia, 'hint' => 'Images referenced by posts but not found under /uploads.'],
    ['label' => 'Placeholder media mentions', 'value' => $placeholderMedia, 'hint' => 'Posts that may contain placeholder text.'],
    ['label' => 'Very short content', 'value' => $shortContent, 'hint' => 'Posts with less than roughly 500 characters.'],
    ['label' => 'Duplicate slugs', 'value' => $duplicateSlugs, 'hint' => 'Slug collisions that can confuse canonical URLs.'],
    ['label' => 'Empty posts', 'value' => $emptyPosts, 'hint' => 'Posts with no body content.'],
    ['label' => 'Raw JSON-LD script tags', 'value' => count($structuredDataSourceIssues), 'hint' => 'blog-post.php and includes/header.php should use vdj_json_ld_script().'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SEO Audit — Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/admin/admin.css">
  <style>
    .audit-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
    .audit-card{background:#fff;border:1px solid var(--border);border-radius:10px;padding:18px;box-shadow:var(--shadow)}
    .audit-card strong{display:block;font-size:26px;font-family:Manrope,sans-serif;color:var(--text)}
    .audit-card span{display:block;color:var(--text);font-weight:700;margin-top:4px}
    .audit-card p{margin:6px 0 0;color:var(--text-muted);font-size:12px;line-height:1.45}
    .audit-ok strong{color:#16a34a}
    .audit-warn strong{color:#d97706}
    @media(max-width:980px){.audit-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:640px){.audit-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
<?php require_once __DIR__ . '/_sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-topbar">
    <div class="admin-header">
      <h1>SEO Audit</h1>
      <p>Lightweight checks for imported blog content, media, and canonical recovery.</p>
    </div>
    <button class="hamburger" id="hamburger"><i class="fa-solid fa-bars"></i></button>
  </div>

  <div class="stat-grid" style="grid-template-columns:repeat(3,1fr);">
    <div class="stat-card">
      <div class="stat-card__icon"><i class="fa-solid fa-newspaper"></i></div>
      <div class="stat-card__body">
        <div class="stat-card__num"><?= number_format($totalPosts) ?></div>
        <div class="stat-card__label">Imported Blog Posts</div>
      </div>
    </div>
    <a class="stat-card" href="/sitemap.xml" target="_blank" style="text-decoration:none;">
      <div class="stat-card__icon" style="background:rgba(34,197,94,.1);color:#16a34a;"><i class="fa-solid fa-sitemap"></i></div>
      <div class="stat-card__body">
        <div class="stat-card__num" style="font-size:22px;">Sitemap</div>
        <div class="stat-card__label">Open public XML</div>
      </div>
    </a>
    <a class="stat-card" href="/robots.txt" target="_blank" style="text-decoration:none;">
      <div class="stat-card__icon"><i class="fa-solid fa-robot"></i></div>
      <div class="stat-card__body">
        <div class="stat-card__num" style="font-size:22px;">Robots</div>
        <div class="stat-card__label">Crawl rules</div>
      </div>
    </a>
  </div>

  <div class="audit-grid">
    <?php foreach ($checks as $check): ?>
      <?php $ok = (int)$check['value'] === 0; ?>
      <div class="audit-card <?= $ok ? 'audit-ok' : 'audit-warn' ?>">
        <strong><?= number_format((int)$check['value']) ?></strong>
        <span><?= e($check['label']) ?></span>
        <p><?= e($check['hint']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($structuredDataSourceIssues): ?>
  <div class="admin-card" style="margin-bottom:24px;">
    <div class="admin-card__header">
      <h2><i class="fa-solid fa-code"></i> Structured Data Source Warnings</h2>
    </div>
    <div class="admin-card__body">
      <p style="margin-top:0;color:var(--text-muted);font-size:13px;">Raw JSON-LD script tags bypass the safe encoder and can break Search Console parsing.</p>
      <ul style="margin:0;padding-left:18px;font-family:monospace;font-size:12px;">
        <?php foreach ($structuredDataSourceIssues as $issue): ?>
          <li><?= e($issue) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>

  <div class="admin-card">
    <div class="admin-card__header">
      <h2><i class="fa-solid fa-image"></i> Sample Broken Media</h2>
    </div>
    <?php if ($sampleIssues): ?>
      <table class="admin-table">
        <thead>
          <tr><th>Post</th><th>Missing media path</th><th>Open</th></tr>
        </thead>
        <tbody>
          <?php foreach ($sampleIssues as $issue): ?>
            <tr>
              <td><?= e($issue['title']) ?></td>
              <td style="font-family:monospace;font-size:12px;"><?= e($issue['media']) ?></td>
              <td><a class="btn btn-sm btn-accent" href="<?= e($issue['url']) ?>" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i></a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="empty-state">
        <i class="fa-solid fa-circle-check"></i>
        <p>No broken media samples found in the scanned blog posts.</p>
      </div>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
