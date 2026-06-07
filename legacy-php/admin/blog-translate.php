<?php
require_once __DIR__ . '/_guard.php';

$flash = ''; $flashType = 'success';
$postId = (int)($_GET['id'] ?? 0);

function adminBlogSlugExists(PDO $db, string $slug, string $lang, int $excludeId): bool {
    $column = $lang === 'de' ? 'slug_de' : ($lang === 'tr' ? 'slug_tr' : 'slug');
    $stmt = $db->prepare("SELECT COUNT(*) FROM blog_posts WHERE `$column` = ? AND id <> ?");
    $stmt->execute([$slug, $excludeId]);
    return (int)$stmt->fetchColumn() > 0;
}

function adminBlogSeoState(array $post, string $lang): array {
    $status = blogTranslationStatus($post, $lang);
    $missing = $status['missing'];
    foreach (['excerpt', 'seo_title', 'seo_description'] as $field) {
        if (trim((string)($post[$field . '_' . $lang] ?? '')) === '') {
            $missing[] = $field . '_' . $lang;
        }
    }
    return [
        'ready' => empty($status['missing']),
        'complete' => empty($missing),
        'missing' => $missing,
    ];
}

if ($postId < 1) {
    header('Location: /admin/blog.php');
    exit;
}

// Verify blog_posts has i18n columns; if not, redirect with message
try {
    $db->query('SELECT title_tr FROM blog_posts LIMIT 1');
} catch (PDOException $e) {
    $flash = 'Run blog-i18n-migration.sql on your database first to add translation columns.';
    $flashType = 'error';
}

$post = null;
if (!$flash) {
    $stmt = $db->prepare('SELECT * FROM blog_posts WHERE id = ? LIMIT 1');
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    if (!$post) {
        header('Location: /admin/blog.php');
        exit;
    }
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $post) {
    verifyCsrf();
    $fields = [
        'title_tr', 'title_de',
        'slug_tr', 'slug_de',
        'excerpt_tr', 'excerpt_de',
        'content_tr', 'content_de',
        'seo_title_tr', 'seo_title_de',
        'seo_description_tr', 'seo_description_de',
    ];
    $sets = [];
    $vals = [];
    $newValues = [];
    foreach ($fields as $f) {
        $sets[] = "`$f` = ?";
        $value = trim($_POST[$f] ?? '');
        if ($f === 'slug_tr' && $value === '') {
            $value = slugify($_POST['title_tr'] ?? '');
        }
        if ($f === 'slug_de' && $value === '') {
            $value = slugify($_POST['title_de'] ?? '');
        }
        if ($f === 'slug_tr' || $f === 'slug_de') {
            $value = slugify($value);
        }
        $vals[] = $value;
        $newValues[$f] = $value;
    }
    $slugErrors = [];
    if (($newValues['slug_tr'] ?? '') !== '' && adminBlogSlugExists($db, $newValues['slug_tr'], 'tr', $postId)) {
        $slugErrors[] = 'Turkish slug is already used by another post.';
    }
    if (($newValues['slug_de'] ?? '') !== '' && adminBlogSlugExists($db, $newValues['slug_de'], 'de', $postId)) {
        $slugErrors[] = 'German slug is already used by another post.';
    }

    if ($slugErrors) {
        $flash = implode(' ', $slugErrors);
        $flashType = 'error';
        foreach ($newValues as $key => $value) {
            $post[$key] = $value;
        }
    } else {
        $vals[] = $postId;
        $db->prepare('UPDATE blog_posts SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);

        // Refresh
        $stmt->execute([$postId]);
        $post = $stmt->fetch();
        $flash = 'Translations saved.';
    }
}

$currentPage = 'blog';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Translate Post — Admin — Visual Design Journey</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/admin/admin.css">
  <style>
    .lang-section { margin-bottom: 2.5rem; }
    .lang-badge { display:inline-block;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin-bottom:1rem; }
    .lang-badge--tr { background:#e8f5e9;color:#1b5e20; }
    .lang-badge--de { background:#e3f2fd;color:#0d47a1; }
    .source-preview { background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:12px 14px;font-size:13px;color:var(--text-muted);margin-bottom:8px;line-height:1.55;max-height:120px;overflow:auto; }
    .source-preview h4 { font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--text-muted);margin:0 0 6px; }
    .two-col { display:grid;grid-template-columns:1fr 1fr;gap:24px; }
    @media(max-width:820px){ .two-col { grid-template-columns:1fr; } }
    textarea { width:100%;box-sizing:border-box;font-family:inherit;font-size:13px;line-height:1.6;padding:10px 12px;border:1px solid var(--border);border-radius:8px;resize:vertical;background:var(--white);color:var(--text); }
    textarea:focus { outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(100,80,220,.12); }
    label { display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:4px;letter-spacing:.02em; }
    .field { margin-bottom:18px; }
    .translation-status-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px; }
    .translation-status-card { border:1px solid var(--border);border-radius:10px;background:var(--white);padding:14px 16px; }
    .translation-status-card h3 { font-size:13px;font-weight:700;margin-bottom:8px;display:flex;align-items:center;gap:8px; }
    .translation-status-card p { color:var(--text-muted);font-size:12px;line-height:1.5; }
    .status-dot { width:9px;height:9px;border-radius:99px;background:var(--success);display:inline-block; }
    .status-dot.warn { background:var(--warning); }
    .status-dot.danger { background:var(--danger); }
    .missing-list { display:flex;gap:5px;flex-wrap:wrap;margin-top:8px; }
    .missing-list span { background:#fef3c7;color:#92400e;border-radius:999px;padding:3px 7px;font-size:11px;font-weight:700; }
    .helper-row { display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:7px; }
    .mini-btn { border:1px solid var(--border);background:#fff;border-radius:8px;padding:5px 8px;font-size:11px;font-weight:700;color:var(--text-muted); }
    .mini-btn:hover { color:var(--accent);border-color:var(--accent); }
    .field-hint { display:flex;justify-content:space-between;gap:8px;margin-top:5px;font-size:11px;color:var(--text-muted); }
    .field-hint strong { color:var(--text);font-weight:700; }
    .preview-links { display:flex;gap:8px;flex-wrap:wrap;margin-left:auto; }
    .preview-links a { border:1px solid var(--border);border-radius:999px;padding:6px 10px;font-size:12px;font-weight:700;text-decoration:none;background:#fff;color:var(--text-muted); }
    .preview-links a:hover { border-color:var(--accent);color:var(--accent); }
    .dirty-warning { background:#fff1f2;border:1px solid #fecdd3;color:#9f1239;border-radius:10px;padding:12px 14px;margin-bottom:20px;font-size:13px;line-height:1.5; }
    @media(max-width:980px){ .translation-status-grid { grid-template-columns:1fr; } .preview-links { margin-left:0; } }
  </style>
</head>
<body>
<?php include __DIR__ . '/_sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-topbar">
    <h1 class="admin-page-title">
      <a href="/admin/blog.php" style="color:inherit;text-decoration:none;opacity:.55;">Blog</a>
      <span style="opacity:.35;margin:0 6px;">/</span>
      Translate Post
    </h1>
  </div>

  <?php if ($flash): ?>
  <div class="alert alert-<?= $flashType === 'error' ? 'danger' : 'success' ?>" style="margin:0 0 20px;">
    <?= htmlspecialchars($flash) ?>
  </div>
  <?php endif; ?>

  <?php if (!$post): ?>
    <p style="color:var(--text-muted);">Post not found or migration not run yet.</p>
  <?php else: ?>
  <?php
    $trState = adminBlogSeoState($post, 'tr');
    $deState = adminBlogSeoState($post, 'de');
    $dirtyOriginal = blogHasDirtyContent($post);
    $previewUrls = [
        'EN' => blogCanonicalPath($post, 'en'),
        'TR' => blogCanonicalPath($post, 'tr'),
        'DE' => blogCanonicalPath($post, 'de'),
    ];
  ?>

  <div class="admin-card" style="padding:20px 24px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
      <?php if (!empty($post['featured_image'])): ?>
        <img src="<?= e(blogThumbnailUrl($post['featured_image'], 80, 54)) ?>" alt="" style="width:80px;height:54px;object-fit:cover;border-radius:6px;">
      <?php endif; ?>
      <div>
        <div style="font-weight:700;font-size:1rem;"><?= htmlspecialchars($post['title']) ?></div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:3px;"><?= htmlspecialchars($post['slug']) ?> · <?= date('M j, Y', strtotime($post['published_at'])) ?></div>
      </div>
      <div class="preview-links">
        <?php foreach ($previewUrls as $label => $url): ?>
          <a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-eye"></i> <?= e($label) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <?php if ($dirtyOriginal): ?>
    <div class="dirty-warning">
      <strong><i class="fa-solid fa-broom"></i> Dirty source warning:</strong>
      This original post may contain JSON, shortcode, ad markers, lorem ipsum, AI-response artifacts, or duplicate title fragments. Review the source before translating.
      <a href="/admin/blog.php?translation=dirty" style="font-weight:700;color:inherit;">Open dirty content list</a>.
    </div>
  <?php endif; ?>

  <div class="translation-status-grid">
    <div class="translation-status-card">
      <h3><span class="status-dot <?= $dirtyOriginal ? 'danger' : '' ?>"></span> Source quality</h3>
      <p><?= $dirtyOriginal ? 'Needs manual cleanup before it becomes a strong SEO source.' : 'No obvious dirty-content markers detected.' ?></p>
    </div>
    <div class="translation-status-card">
      <h3><span class="status-dot <?= $trState['ready'] ? '' : 'warn' ?>"></span> Turkish status</h3>
      <p><?= $trState['ready'] ? 'Public TR article body is ready.' : 'TR URL stays live with a translation-pending state until required fields are filled.' ?></p>
      <?php if ($trState['missing']): ?>
        <div class="missing-list"><?php foreach ($trState['missing'] as $m): ?><span><?= e($m) ?></span><?php endforeach; ?></div>
      <?php endif; ?>
    </div>
    <div class="translation-status-card">
      <h3><span class="status-dot <?= $deState['ready'] ? '' : 'warn' ?>"></span> German status</h3>
      <p><?= $deState['ready'] ? 'Public DE article body is ready.' : 'DE URL stays live with a translation-pending state until required fields are filled.' ?></p>
      <?php if ($deState['missing']): ?>
        <div class="missing-list"><?php foreach ($deState['missing'] as $m): ?><span><?= e($m) ?></span><?php endforeach; ?></div>
      <?php endif; ?>
    </div>
  </div>

  <form method="POST" action="/admin/blog-translate.php?id=<?= $postId ?>">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <!-- ═══ TURKISH ═══════════════════════════════════════════════════════ -->
    <div class="admin-card lang-section" style="padding:24px;">
      <span class="lang-badge lang-badge--tr">🇹🇷 Türkçe</span>

      <div class="two-col">
        <div>
          <div class="field">
            <label>Başlık (title_tr)</label>
            <div class="source-preview"><h4>Kaynak (EN)</h4><?= htmlspecialchars($post['title']) ?></div>
            <input type="text" name="title_tr" id="title_tr" data-count value="<?= htmlspecialchars($post['title_tr'] ?? '') ?>"
                   placeholder="Türkçe başlık..."
                   style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;">
            <div class="helper-row">
              <button type="button" class="mini-btn" data-slug-from="title_tr" data-slug-to="slug_tr">Generate slug</button>
              <button type="button" class="mini-btn" data-copy-from="title_tr" data-copy-to="seo_title_tr">Use as SEO title</button>
            </div>
          </div>
          <div class="field">
            <label>SEO URL Slug (slug_tr)</label>
            <div class="source-preview"><h4>Kaynak (EN)</h4><?= htmlspecialchars($post['slug']) ?></div>
            <input type="text" name="slug_tr" id="slug_tr" data-count value="<?= htmlspecialchars($post['slug_tr'] ?? '') ?>"
                   placeholder="turkce-seo-url-slug"
                   style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;">
            <div class="field-hint"><span>Clean URL: <?= e(publicUrl('/tr/blog/')) ?><strong data-preview-slug="slug_tr"><?= e($post['slug_tr'] ?: 'turkce-seo-url-slug') ?></strong>/</span><span data-counter-for="slug_tr"></span></div>
          </div>
          <div class="field">
            <label>SEO Başlık (seo_title_tr)</label>
            <div class="source-preview"><h4>Kaynak (EN)</h4><?= htmlspecialchars($post['seo_title'] ?? '') ?: '<em style="opacity:.5">—</em>' ?></div>
            <input type="text" name="seo_title_tr" id="seo_title_tr" data-count value="<?= htmlspecialchars($post['seo_title_tr'] ?? '') ?>"
                   placeholder="SEO başlık Türkçe..."
                   style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;">
            <div class="field-hint"><span>Ideal: 45-65 chars</span><span data-counter-for="seo_title_tr"></span></div>
          </div>
          <div class="field">
            <label>SEO Açıklama (seo_description_tr)</label>
            <div class="source-preview"><h4>Kaynak (EN)</h4><?= htmlspecialchars($post['seo_description'] ?? '') ?: '<em style="opacity:.5">—</em>' ?></div>
            <textarea name="seo_description_tr" id="seo_description_tr" data-count rows="3" placeholder="SEO açıklama Türkçe..."><?= htmlspecialchars($post['seo_description_tr'] ?? '') ?></textarea>
            <div class="field-hint"><span>Ideal: 120-160 chars</span><span data-counter-for="seo_description_tr"></span></div>
          </div>
          <div class="field">
            <label>Özet / Excerpt (excerpt_tr)</label>
            <div class="source-preview"><h4>Kaynak (EN)</h4><?= htmlspecialchars(strip_tags($post['excerpt'] ?? '')) ?: '<em style="opacity:.5">—</em>' ?></div>
            <textarea name="excerpt_tr" id="excerpt_tr" data-count rows="4" placeholder="Kısa özet Türkçe..."><?= htmlspecialchars($post['excerpt_tr'] ?? '') ?></textarea>
            <div class="field-hint"><span>Shows on cards when content is pending</span><span data-counter-for="excerpt_tr"></span></div>
          </div>
        </div>
        <div>
          <div class="field">
            <label>İçerik (content_tr) — HTML kabul edilir</label>
            <div class="source-preview" style="max-height:80px;"><h4>Kaynak (EN) — <?= number_format(mb_strlen(strip_tags($post['content'] ?? ''))) ?> karakter</h4><?= htmlspecialchars(mb_substr(strip_tags($post['content'] ?? ''), 0, 300)) ?>…</div>
            <textarea name="content_tr" id="content_tr" data-count rows="20" placeholder="Makale içeriği Türkçe (HTML)..."><?= htmlspecialchars($post['content_tr'] ?? '') ?></textarea>
            <div class="field-hint"><span>Required for full TR publication</span><span data-counter-for="content_tr"></span></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ GERMAN ════════════════════════════════════════════════════════ -->
    <div class="admin-card lang-section" style="padding:24px;">
      <span class="lang-badge lang-badge--de">🇩🇪 Deutsch</span>

      <div class="two-col">
        <div>
          <div class="field">
            <label>Titel (title_de)</label>
            <div class="source-preview"><h4>Source (EN)</h4><?= htmlspecialchars($post['title']) ?></div>
            <input type="text" name="title_de" id="title_de" data-count value="<?= htmlspecialchars($post['title_de'] ?? '') ?>"
                   placeholder="Deutschen Titel..."
                   style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;">
            <div class="helper-row">
              <button type="button" class="mini-btn" data-slug-from="title_de" data-slug-to="slug_de">Generate slug</button>
              <button type="button" class="mini-btn" data-copy-from="title_de" data-copy-to="seo_title_de">Use as SEO title</button>
            </div>
          </div>
          <div class="field">
            <label>SEO URL Slug (slug_de)</label>
            <div class="source-preview"><h4>Source (EN)</h4><?= htmlspecialchars($post['slug']) ?></div>
            <input type="text" name="slug_de" id="slug_de" data-count value="<?= htmlspecialchars($post['slug_de'] ?? '') ?>"
                   placeholder="deutscher-seo-url-slug"
                   style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;">
            <div class="field-hint"><span>Clean URL: <?= e(publicUrl('/de/blog/')) ?><strong data-preview-slug="slug_de"><?= e($post['slug_de'] ?: 'deutscher-seo-url-slug') ?></strong>/</span><span data-counter-for="slug_de"></span></div>
          </div>
          <div class="field">
            <label>SEO-Titel (seo_title_de)</label>
            <div class="source-preview"><h4>Source (EN)</h4><?= htmlspecialchars($post['seo_title'] ?? '') ?: '<em style="opacity:.5">—</em>' ?></div>
            <input type="text" name="seo_title_de" id="seo_title_de" data-count value="<?= htmlspecialchars($post['seo_title_de'] ?? '') ?>"
                   placeholder="SEO-Titel Deutsch..."
                   style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;">
            <div class="field-hint"><span>Ideal: 45-65 chars</span><span data-counter-for="seo_title_de"></span></div>
          </div>
          <div class="field">
            <label>SEO-Beschreibung (seo_description_de)</label>
            <div class="source-preview"><h4>Source (EN)</h4><?= htmlspecialchars($post['seo_description'] ?? '') ?: '<em style="opacity:.5">—</em>' ?></div>
            <textarea name="seo_description_de" id="seo_description_de" data-count rows="3" placeholder="SEO-Beschreibung Deutsch..."><?= htmlspecialchars($post['seo_description_de'] ?? '') ?></textarea>
            <div class="field-hint"><span>Ideal: 120-160 chars</span><span data-counter-for="seo_description_de"></span></div>
          </div>
          <div class="field">
            <label>Auszug / Excerpt (excerpt_de)</label>
            <div class="source-preview"><h4>Source (EN)</h4><?= htmlspecialchars(strip_tags($post['excerpt'] ?? '')) ?: '<em style="opacity:.5">—</em>' ?></div>
            <textarea name="excerpt_de" id="excerpt_de" data-count rows="4" placeholder="Kurze Zusammenfassung Deutsch..."><?= htmlspecialchars($post['excerpt_de'] ?? '') ?></textarea>
            <div class="field-hint"><span>Shows on cards when content is pending</span><span data-counter-for="excerpt_de"></span></div>
          </div>
        </div>
        <div>
          <div class="field">
            <label>Inhalt (content_de) — HTML erlaubt</label>
            <div class="source-preview" style="max-height:80px;"><h4>Source (EN) — <?= number_format(mb_strlen(strip_tags($post['content'] ?? ''))) ?> chars</h4><?= htmlspecialchars(mb_substr(strip_tags($post['content'] ?? ''), 0, 300)) ?>…</div>
            <textarea name="content_de" id="content_de" data-count rows="20" placeholder="Artikel-Inhalt Deutsch (HTML)..."><?= htmlspecialchars($post['content_de'] ?? '') ?></textarea>
            <div class="field-hint"><span>Required for full DE publication</span><span data-counter-for="content_de"></span></div>
          </div>
        </div>
      </div>
    </div>

    <div style="display:flex;gap:12px;align-items:center;margin-bottom:40px;">
      <button type="submit" class="btn btn--primary">
        <i class="fa-solid fa-floppy-disk"></i> Save Translations
      </button>
      <a href="/admin/blog.php" class="btn btn--secondary">Cancel</a>
      <span style="margin-left:auto;font-size:12px;color:var(--text-muted);">
        Empty content_tr/content_de fields show a localized "translation pending" state instead of publishing English body text under TR/DE URLs.
      </span>
    </div>
  </form>

  <?php endif; ?>
</main>
<script>
(function () {
  function slugify(value) {
    return String(value || '')
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .replace(/ı/g, 'i').replace(/İ/g, 'i').replace(/ğ/g, 'g').replace(/Ğ/g, 'g')
      .replace(/ü/g, 'u').replace(/Ü/g, 'u').replace(/ş/g, 's').replace(/Ş/g, 's')
      .replace(/ö/g, 'o').replace(/Ö/g, 'o').replace(/ç/g, 'c').replace(/Ç/g, 'c')
      .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  }
  function updateCounter(el) {
    var target = document.querySelector('[data-counter-for="' + el.id + '"]');
    if (target) target.textContent = String(el.value || '').length + ' chars';
    var preview = document.querySelector('[data-preview-slug="' + el.id + '"]');
    if (preview) preview.textContent = slugify(el.value) || el.placeholder || 'slug';
  }
  document.querySelectorAll('[data-count]').forEach(function (el) {
    updateCounter(el);
    el.addEventListener('input', function () { updateCounter(el); });
  });
  document.querySelectorAll('[data-slug-from]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var from = document.getElementById(btn.dataset.slugFrom);
      var to = document.getElementById(btn.dataset.slugTo);
      if (!from || !to) return;
      to.value = slugify(from.value);
      updateCounter(to);
      to.focus();
    });
  });
  document.querySelectorAll('[data-copy-from]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var from = document.getElementById(btn.dataset.copyFrom);
      var to = document.getElementById(btn.dataset.copyTo);
      if (!from || !to) return;
      to.value = from.value;
      updateCounter(to);
      to.focus();
    });
  });
})();
</script>
</body>
</html>
