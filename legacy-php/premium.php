<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (vdjRenderLocalizedStaticPage('premium')) exit;

$activeNav = 'premium';
$pageTitle = 'Premium Curator Tools — Visual Design Journey';
$metaDesc = 'Coming soon: private collections, source discovery workflows, moodboard exports, trend reports, and portfolio-ready curator tools for Visual Design Journey.';
$canonicalUrl = SITE_URL . '/premium';

require_once __DIR__ . '/includes/header.php';

$features = [
    ['Private collections', 'Build client, research, and draft moodboards before publishing them publicly.', 'fa-lock'],
    ['Advanced source discovery', 'Organize approved references, feeds, credits, tags, and source notes in one workspace.', 'fa-magnifying-glass-chart'],
    ['PDF and ZIP exports', 'Export boards with image credits, notes, palette references, and share-ready layouts.', 'fa-file-export'],
    ['Presentation mode', 'Turn a board into a fullscreen visual walkthrough for portfolio or client review.', 'fa-display'],
    ['Curator analytics', 'Track profile views, board saves, top styles, and collection performance.', 'fa-chart-line'],
    ['Weekly trend reports', 'Receive editorial trend summaries based on boards, blog archive, sources, and design movements.', 'fa-newspaper'],
];

$plans = [
    ['Curator', 'For individual designers building a polished public archive.', ['Private collections', 'Portfolio-ready profile', 'PDF board export', 'Weekly curator digest']],
    ['Studio', 'For small teams managing references and client moodboards.', ['Team workspace', 'Source approval queue', 'ZIP export', 'Sponsor-safe presentation links']],
    ['Archive', 'For editorial and culture-led design libraries.', ['Trend reports', 'Movement archive tools', 'Newsletter placements', 'Advanced analytics']],
];

$futureStack = [
    'portfolio-custom-domain' => 'Portfolio custom domain',
    'brandable-curator-page' => 'Brandable curator page',
    'weekly-trend-reports' => 'Weekly trend reports',
    'team-curation-workspace' => 'Team curation workspace',
];
?>

<section class="premium-hero">
  <div class="premium-hero__copy">
    <span class="section-eyebrow"><i class="fa-solid fa-crown"></i> Coming Soon</span>
    <h1>Premium tools for serious curators.</h1>
    <p>Visual Design Journey is staying focused on curated visual culture: boards, references, design movements, blog archives, and portfolio-grade curator pages. Premium will add workflow power without turning the platform into noisy social media.</p>
    <div class="premium-hero__actions">
      <a href="/sponsor.php" class="btn btn--primary"><i class="fa-solid fa-handshake"></i> Partner with us</a>
      <a href="/contact.php" class="btn btn--secondary"><i class="fa-solid fa-envelope"></i> Request early access</a>
    </div>
  </div>
  <div class="premium-hero__panel" aria-label="Premium roadmap summary">
    <div><strong>06</strong><span>Core modules</span></div>
    <div><strong>03</strong><span>Plan types</span></div>
    <div><strong>0</strong><span>Intrusive ads</span></div>
    <div><strong>100%</strong><span>Curated workflow</span></div>
  </div>
</section>

<section class="premium-section">
  <div class="section-head">
    <div>
      <span class="section-eyebrow">Curator OS</span>
      <h2>What is being built next</h2>
    </div>
  </div>
  <div class="premium-feature-grid">
    <?php foreach ($features as $feature):
      $featureSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $feature[0]));
      $featureSlug = trim($featureSlug, '-');
      $aliasIds = [
        'pdf-and-zip-exports' => 'zip-board-export',
        'presentation-mode' => 'moodboard-presentation-mode',
        'curator-analytics' => 'curator-analytics-pages',
      ];
      $cardId = $aliasIds[$featureSlug] ?? $featureSlug;
    ?>
      <article class="premium-feature-card" id="<?= e($cardId) ?>">
        <div class="premium-feature-card__icon"><i class="fa-solid <?= e($feature[2]) ?>"></i></div>
        <h3><?= e($feature[0]) ?></h3>
        <p><?= e($feature[1]) ?></p>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="premium-section">
  <div class="section-head">
    <div>
      <span class="section-eyebrow">No-AI Product Direction</span>
      <h2>Premium plans preview</h2>
    </div>
  </div>
  <div class="premium-plan-grid">
    <?php foreach ($plans as $plan): ?>
      <article class="premium-plan-card">
        <span><?= e($plan[0]) ?></span>
        <p><?= e($plan[1]) ?></p>
        <ul>
          <?php foreach ($plan[2] as $item): ?>
            <li><i class="fa-solid fa-check"></i><?= e($item) ?></li>
          <?php endforeach; ?>
        </ul>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="premium-section">
  <div class="section-head">
    <div>
      <span class="section-eyebrow">Future stack</span>
      <h2>Later-stage premium modules</h2>
    </div>
  </div>
  <div class="premium-stack">
    <?php foreach ($futureStack as $id => $label): ?>
      <a id="<?= e($id) ?>" href="/contact.php" class="premium-stack__item">
        <span><?= e($label) ?></span>
        <i class="fa-solid fa-arrow-right"></i>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="premium-cta">
  <span class="section-eyebrow"><i class="fa-solid fa-compass-drafting"></i> Design culture first</span>
  <h2>Not random image dumping. A curated archive with credits, context, and useful workflows.</h2>
  <p>The platform direction is moodboard creation, movement archives, source bookmarking, editorial blog content, curator portfolios, and sponsor-safe monetization.</p>
  <a href="/submit-source.php" class="btn btn--primary">Submit a reference</a>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
