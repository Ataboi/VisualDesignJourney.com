<?php
require_once __DIR__ . '/includes/v2_catalog.php';

$requestedSection = trim((string)($_GET['section'] ?? ''), '/');
$requestedSlug = trim((string)($_GET['slug'] ?? ''), '/');
$section = vdj_v2_section_by_slug($requestedSection);

if (!$section) {
    http_response_code(404);
    $pageTitle = 'Tool Collection Not Found — Visual Design Journey';
    $activeNav = 'tools';
    $metaDesc = 'This VDJ v2 tool collection could not be found.';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <main class="vdj-v2-shell">
      <section class="vdj-v2-section">
        <div class="vdj-v2-container">
          <div class="vdj-v2-empty">
            <i class="fa-solid fa-compass-drafting"></i>
            <h1>Collection not found</h1>
            <p>This route is reserved for the VDJ v2 toolkit, but no matching collection exists yet.</p>
            <a href="/tools" class="vdj-v2-btn vdj-v2-btn--primary">Back to tools</a>
          </div>
        </div>
      </section>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $section['label'] . ' — Visual Design Journey v2';
$metaDesc = $section['description'];
$activeNav = 'tools';
$canonicalUrl = publicUrl('/' . ($requestedSection ?: $section['slug']));

require_once __DIR__ . '/includes/header.php';
?>

<main class="vdj-v2-shell">
  <section class="vdj-v2-collection-hero">
    <div class="vdj-v2-container">
      <a href="/tools" class="vdj-v2-back"><i class="fa-solid fa-arrow-left"></i> Tools</a>
      <div class="vdj-v2-eyebrow">
        <span></span>
        <?= e($section['kicker']) ?>
      </div>
      <h1><i class="fa-solid <?= e($section['icon']) ?>"></i> <?= e($section['label']) ?></h1>
      <p><?= e($section['description']) ?></p>
    </div>
  </section>

  <section class="vdj-v2-section">
    <div class="vdj-v2-container">
      <?php if ($requestedSlug !== ''): ?>
        <?php
          $matched = null;
          foreach ($section['items'] as $item) {
              $path = trim(parse_url($item[2], PHP_URL_PATH) ?: '', '/');
              if (str_ends_with($path, '/' . $requestedSlug) || $path === $requestedSlug) {
                  $matched = $item;
                  break;
              }
          }
        ?>
        <?php if ($matched): ?>
          <article class="vdj-v2-detail">
            <div class="vdj-v2-card__icon"><i class="fa-solid <?= e($matched[3]) ?>"></i></div>
            <h2><?= e($matched[0]) ?></h2>
            <p><?= e($matched[1]) ?></p>
            <div class="code-block">
              <div class="code-block-header">
                <span class="code-block-label">VDJ v2 status</span>
              </div>
              <pre>This reference is included in the v2 safe-deploy catalog.
Legacy URLs, admin, API, blog slugs, and uploads remain unchanged for this release.</pre>
            </div>
            <a href="/<?= e($requestedSection) ?>" class="vdj-v2-btn">Open full collection</a>
          </article>
        <?php else: ?>
          <div class="vdj-v2-empty">
            <i class="fa-solid fa-layer-group"></i>
            <h2>Reference queued for v2 content</h2>
            <p>The collection is live, and this detail slug is reserved so it will not be mistaken for a blog post.</p>
            <a href="/<?= e($requestedSection) ?>" class="vdj-v2-btn vdj-v2-btn--primary">Open collection</a>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="vdj-v2-grid">
          <?php foreach ($section['items'] as $item): ?>
            <?php [$name, $desc, $href, $icon, $badge] = $item; ?>
            <a href="<?= e($href) ?>" class="vdj-v2-card">
              <div class="vdj-v2-card__icon"><i class="fa-solid <?= e($icon) ?>"></i></div>
              <div class="vdj-v2-card__body">
                <strong><?= e($name) ?></strong>
                <span><?= e($desc) ?></span>
              </div>
              <em><?= e($badge) ?></em>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
