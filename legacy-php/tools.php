<?php
$pageTitle  = 'Design Tools — Visual Design Journey v2';
$metaDesc   = 'VDJ v2 design toolkit: live CSS generators, typography references, color palettes, prompts, UI patterns, and website section inspiration.';
$activeNav  = 'tools';
require_once __DIR__ . '/includes/v2_catalog.php';
require_once __DIR__ . '/includes/header.php';

$catalog = vdj_v2_catalog();
$totalTools = array_sum(array_map(fn($section) => count($section['items']), $catalog));
?>

<main class="vdj-v2-shell">
  <section class="vdj-v2-hero vdj-v2-hero--tools">
    <div class="vdj-v2-container">
      <div class="vdj-v2-eyebrow">
        <span></span>
        Visual Design Journey v2
      </div>
      <h1>Design tools, prompts, and visual systems in one place.</h1>
      <p>
        The existing VDJ community, blog, SEO URLs, admin, and uploads stay intact.
        The VibeKit tool library now ships as a safe PHP v2 layer for one-domain deployment.
      </p>
      <div class="vdj-v2-actions">
        <a href="#interactive" class="vdj-v2-btn vdj-v2-btn--primary"><i class="fa-solid fa-bolt"></i> Try live tools</a>
        <a href="<?= e(vdj_localized_url('/blog')) ?>" class="vdj-v2-btn"><i class="fa-solid fa-newspaper"></i> Read the blog</a>
      </div>
      <div class="vdj-v2-stats" aria-label="VDJ v2 rollout status">
        <div><strong><?= number_format($totalTools) ?>+</strong><span>tool references</span></div>
        <div><strong>4</strong><span>live generators</span></div>
        <div><strong>0</strong><span>broken legacy URLs</span></div>
      </div>
    </div>
  </section>

  <?php foreach ($catalog as $section): ?>
  <section class="vdj-v2-section" id="<?= e($section['slug']) ?>">
    <div class="vdj-v2-container">
      <div class="vdj-v2-section-head">
        <div>
          <p><?= e($section['kicker']) ?></p>
          <h2><i class="fa-solid <?= e($section['icon']) ?>"></i> <?= e($section['label']) ?></h2>
          <span><?= e($section['description']) ?></span>
        </div>
        <?php if ($section['slug'] !== 'interactive'): ?>
          <a href="/<?= e($section['slug']) ?>" class="vdj-v2-link">Open collection <i class="fa-solid fa-arrow-right"></i></a>
        <?php endif; ?>
      </div>

      <div class="vdj-v2-grid">
        <?php foreach ($section['items'] as $tool): ?>
          <?php [$name, $desc, $href, $icon, $badge] = $tool; ?>
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
    </div>
  </section>
  <?php endforeach; ?>

  <section class="vdj-v2-deploy-note">
    <div class="vdj-v2-container">
      <i class="fa-solid fa-shield-halved"></i>
      <div>
        <strong>Safe v2 rollout</strong>
        <p>Legacy PHP admin, API endpoints, blog slugs, uploads, sitemap, feed, AdSense, and Search Console-sensitive SEO paths are preserved for this deploy.</p>
      </div>
      <a href="/admin/health.php">Admin health</a>
    </div>
  </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
