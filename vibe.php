<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();
$vibeI18nReady = dbColumnExists($pdo, 'vibe_styles', 'label_tr');

function vibeFallbackCopy(string $slug): array {
    $map = [
        'brutalist' => ['desc' => 'Raw structure, loud type, exposed grids, and deliberate friction for work that wants to feel direct and unpolished.', 'movement' => 'brutalist', 'tags' => ['Raw', 'Grid', 'High contrast']],
        'soft-minimal' => ['desc' => 'Quiet spacing, gentle neutrals, refined typography, and calm interfaces for editorial and lifestyle references.', 'movement' => 'minimalism', 'tags' => ['Neutral', 'Whitespace', 'Editorial']],
        'neon-cyber' => ['desc' => 'Synthetic color, dark UI, future nostalgia, chrome surfaces, and high-energy digital culture references.', 'movement' => 'web-design', 'tags' => ['Neon', 'Digital', 'Dark UI']],
        'cottagecore' => ['desc' => 'Botanical warmth, handmade texture, nostalgic illustration, and soft palettes rooted in domestic craft.', 'movement' => 'art-nouveau', 'tags' => ['Botanical', 'Warm', 'Handmade']],
        'high-fashion' => ['desc' => 'Editorial restraint, luxury contrast, dramatic scale, and image-led compositions for premium visual identity.', 'movement' => 'editorial', 'tags' => ['Luxury', 'Editorial', 'Contrast']],
        'y2k-revival' => ['desc' => 'Gloss, playful type, candy color, metallic UI details, and early internet optimism brought into contemporary design.', 'movement' => 'web-design', 'tags' => ['Gloss', 'Chrome', 'Playful']],
        'japandi' => ['desc' => 'Natural materials, disciplined calm, muted palettes, and balanced compositions inspired by Japanese and Scandinavian restraint.', 'movement' => 'minimalism', 'tags' => ['Calm', 'Natural', 'Balanced']],
        'maximalist' => ['desc' => 'Layered color, expressive type, dense composition, and confident visual abundance for bold identity systems.', 'movement' => 'poster', 'tags' => ['Layered', 'Expressive', 'Bold']],
        'bauhaus' => ['desc' => 'Primary colors, geometric form, functional layouts, and type-led composition rooted in modernist education.', 'movement' => 'bauhaus', 'tags' => ['Geometry', 'Primary color', 'Modernist']],
        'art-nouveau' => ['desc' => 'Organic lines, decorative rhythm, botanical forms, and image-type integration inspired by turn-of-the-century design.', 'movement' => 'art-nouveau', 'tags' => ['Organic', 'Decorative', 'Floral']],
        'dark-academia' => ['desc' => 'Scholarly tone, serif typography, archival palettes, and bookish visual references with a quiet dramatic edge.', 'movement' => 'editorial', 'tags' => ['Serif', 'Archive', 'Scholarly']],
        'swiss-grid' => ['desc' => 'Objective layouts, modular grids, sans-serif typography, and disciplined hierarchy for systematic design work.', 'movement' => 'swiss-grid', 'tags' => ['Grid', 'Helvetica', 'Systematic']],
    ];
    return $map[$slug] ?? ['desc' => 'A curated aesthetic path through boards, typography, color, and design history.', 'movement' => 'archive', 'tags' => ['Curated', 'Visual', 'Style']];
}

function vibeDescription(array $vibe): string {
    $desc = localizedField($vibe, 'description');
    if ($desc !== '') return $desc;
    return vibeFallbackCopy((string)($vibe['slug'] ?? ''))['desc'];
}

function vibeRelatedTags(array $vibe): array {
    $tags = parseTags((string)($vibe['related_tags'] ?? ''));
    if (!empty($tags)) return array_slice($tags, 0, 6);
    return vibeFallbackCopy((string)($vibe['slug'] ?? ''))['tags'];
}

$vibeSlug   = trim($_GET['vibe'] ?? '');
$vibeStyle  = null;
$vibeBoards = [];

if ($vibeSlug !== '') {
    $vs = $pdo->prepare('SELECT * FROM vibe_styles WHERE slug = ?');
    $vs->execute([$vibeSlug]);
    $vibeStyle = $vs->fetch(PDO::FETCH_ASSOC);

    if ($vibeStyle) {
        $bs = $pdo->prepare(
            'SELECT b.*, u.username, u.avatar, COUNT(bl.id) AS like_count
             FROM boards b
             JOIN users u ON u.id = b.user_id
             LEFT JOIN board_likes bl ON bl.board_id = b.id
             WHERE b.is_draft = 0 AND FIND_IN_SET(:label, REPLACE(b.style_tags, \', \', \',\')) > 0
             GROUP BY b.id
             ORDER BY b.created_at DESC
             LIMIT 40'
        );
        $bs->execute([':label' => $vibeStyle['label']]);
        $vibeBoards = $bs->fetchAll(PDO::FETCH_ASSOC);
    }
}

if ($vibeSlug === '' || !$vibeStyle) {
    $allStmt  = $pdo->query('SELECT * FROM vibe_styles ORDER BY label ASC');
    $allVibes = $allStmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── Design History Palettes ────────────────────────────────────────────────
$palettes       = [];
$paletteMovements = [];
$activeMov      = trim($_GET['mov'] ?? '');
$palettePage    = max(1, (int)($_GET['pp'] ?? 1));
$palettePerPage = 48;
$palettesTotal  = 0;

try {
    // Distinct movements for filter bar
    $movRows = $pdo->query(
        "SELECT movement, COUNT(*) AS cnt FROM color_palettes GROUP BY movement ORDER BY cnt DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
    $paletteMovements = $movRows;

    // Count
    if ($activeMov !== '') {
        $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM color_palettes WHERE movement = ?");
        $cntStmt->execute([$activeMov]);
    } else {
        $cntStmt = $pdo->query("SELECT COUNT(*) FROM color_palettes");
    }
    $palettesTotal = (int)$cntStmt->fetchColumn();

    // Fetch page
    $offset = ($palettePage - 1) * $palettePerPage;
    if ($activeMov !== '') {
        $pStmt = $pdo->prepare(
            "SELECT * FROM color_palettes WHERE movement = ?
             ORDER BY movement ASC, id ASC LIMIT ? OFFSET ?"
        );
        $pStmt->execute([$activeMov, $palettePerPage, $offset]);
    } else {
        $pStmt = $pdo->prepare(
            "SELECT * FROM color_palettes ORDER BY movement ASC, id ASC LIMIT ? OFFSET ?"
        );
        $pStmt->execute([$palettePerPage, $offset]);
    }
    $palettes = $pStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $palettes = [];
}
$palettesTotalPages = $palettesTotal > 0 ? (int)ceil($palettesTotal / $palettePerPage) : 1;

$pageTitle    = $vibeStyle
    ? e(localizedVibeLabel($vibeStyle)) . ' Vibe — Visual Design Journey'
    : __('vibe.page_title', 'Find Your Vibe — Visual Design Journey');
$metaDesc     = $vibeStyle
    ? sprintf(__('vibe.meta_detail', 'Explore %s mood boards, colour palettes and curated collections on Visual Design Journey.'), localizedVibeLabel($vibeStyle))
    : __('vibe.meta_desc', 'Pick an aesthetic — Minimal, Bauhaus, Y2K, Japandi and more — and discover mood boards that match your visual style.');
$canonicalUrl = publicUrl('/vibe' . ($vibeSlug !== '' ? '?vibe=' . urlencode($vibeSlug) : ''));
if ($vibeStyle && !empty($vibeBoards[0]['cover_image'])) {
    $metaImage = imageUrl($vibeBoards[0]['cover_image']);
}
$activeNav    = 'vibe';
$headExtra = '<script type="application/ld+json">' . json_encode([
    '@context' => 'https://schema.org',
    '@type' => $vibeStyle ? 'CollectionPage' : 'WebPage',
    'name' => $vibeStyle ? localizedVibeLabel($vibeStyle) . ' Vibe' : __('vibe.hero', 'Find Your Vibe'),
    'description' => $vibeStyle ? vibeDescription($vibeStyle) : $metaDesc,
    'url' => $canonicalUrl,
    'isPartOf' => [
        '@type' => 'WebSite',
        'name' => 'Visual Design Journey',
        'url' => publicUrl('/'),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-wrap" id="main-content">
<div class="container">

<?php if ($vibeStyle): ?>

  <a href="<?= e(vdj_localized_url('/vibe')) ?>" style="display:inline-flex;align-items:center;gap:.35rem;text-decoration:none;color:var(--muted);font-size:.875rem;margin-bottom:1.5rem;">
    <?= e(__('vibe.prev', '← Back')) ?> Vibes
  </a>

  <?php
  $palette = [];
  if (!empty($vibeStyle['palette_colors'])) {
      $palette = json_decode($vibeStyle['palette_colors'], true) ?? [];
  }
  $bannerBg = !empty($palette[0]) ? $palette[0] : 'var(--accent)';
  $bannerText = '#fff';
  $fallback = vibeFallbackCopy($vibeStyle['slug']);
  $relatedMovement = $fallback['movement'];
  $relatedTags = vibeRelatedTags($vibeStyle);
  ?>
  <div class="vibe-detail-hero" style="--vibe-bg:<?= e($bannerBg) ?>;color:<?= e($bannerText) ?>;">
    <div class="vibe-detail-hero__main">
      <p class="vibe-kicker">Aesthetic discovery</p>
      <h1><?= e(localizedVibeLabel($vibeStyle)) ?></h1>
      <p class="vibe-detail-hero__desc"><?= e(vibeDescription($vibeStyle)) ?></p>
      <div class="vibe-actions">
        <a href="<?= e(vdj_localized_url('/archive/' . $relatedMovement)) ?>" class="vibe-action">
          <i class="fa-solid fa-layer-group"></i> Related movement
        </a>
        <button type="button" class="vibe-action" data-copy-current>
          <i class="fa-solid fa-link"></i> Copy result
        </button>
      </div>
    </div>
    <aside class="vibe-detail-hero__panel" aria-label="Vibe details">
      <?php if (!empty($vibeStyle['font_primary'])): ?>
      <div class="vibe-panel-row">
        <span>Font pairing</span>
        <strong><?= e($vibeStyle['font_primary']) ?><?= !empty($vibeStyle['font_secondary']) ? ' + ' . e($vibeStyle['font_secondary']) : '' ?></strong>
      </div>
      <?php endif; ?>
      <?php if (!empty($palette)): ?>
      <div class="vibe-panel-row">
        <span>Palette</span>
        <div class="vibe-swatches" role="list" aria-label="Palette colours">
          <?php foreach ($palette as $hex): ?>
          <button type="button" class="vibe-swatch" data-hex="<?= e($hex) ?>" title="Copy <?= e($hex) ?>" aria-label="Copy colour <?= e($hex) ?>" style="background:<?= e($hex) ?>;"></button>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
      <div class="vibe-panel-row">
        <span>Style tags</span>
        <div class="vibe-tag-list">
          <?php foreach ($relatedTags as $tag): ?><span><?= e($tag) ?></span><?php endforeach; ?>
        </div>
      </div>
    </aside>
  </div>

  <h2 style="font-size:1.1rem;font-weight:700;margin:0 0 1.25rem;"><?= e(__('vibe.boards_in', 'Boards in this vibe')) ?></h2>

  <?php if (empty($vibeBoards)): ?>
  <div style="text-align:center;padding:3rem 1rem;color:var(--muted);">
    <div style="font-size:2.5rem;margin-bottom:.75rem;">🎨</div>
    <p class="t-body-md"><?= e(__('vibe.no_boards', 'No boards tagged with this vibe yet.')) ?></p>
  </div>
  <?php else: ?>
  <div class="masonry" role="list" aria-label="Mood boards">
    <?php foreach ($vibeBoards as $board):
      $boardTitle = localizedBoardTitle($board);
      $liked = currentUserId() ? hasLiked($pdo, (int)$board['id'], currentUserId()) : false;
    ?>
    <article class="board-card" role="listitem" aria-label="<?= e($boardTitle) ?>">
      <?php if (!empty($board['cover_image'])): ?>
      <a href="<?= e(boardUrl($board)) ?>"
         aria-label="View board: <?= e($boardTitle) ?>">
        <div class="board-card__img-wrap">
          <div class="board-card__skeleton skeleton"></div>
          <img src="<?= e(imageUrl($board['cover_image'])) ?>"
               alt="Cover image for <?= e($boardTitle) ?> by <?= e($board['username']) ?>"
               class="board-card__img"
               loading="lazy"
               onload="this.classList.add('loaded');this.previousElementSibling.style.display='none'"/>
        </div>
      </a>
      <?php else: ?>
      <div class="board-card__img--placeholder" role="img" aria-label="No cover image for <?= e($boardTitle) ?>">
        <i class="fa-regular fa-image" style="font-size:32px;color:var(--border);"></i>
      </div>
      <?php endif; ?>
      <div class="board-card__body">
        <h3 class="board-card__title">
          <a href="<?= e(boardUrl($board)) ?>"><?= e($boardTitle) ?></a>
        </h3>
        <div class="board-card__meta">
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
        </div>
        <?php if (!empty($board['style_tags'])): ?>
        <div class="board-card__tags">
          <?php foreach (array_slice(parseTags($board['style_tags']), 0, 3) as $tag): ?>
          <a href="/vibe.php?vibe=<?= urlencode($vibeSlug) ?>&tag=<?= urlencode($tag) ?>" class="tag"><?= e($tag) ?></a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

<?php else: ?>

  <div style="text-align:center;margin-bottom:2.5rem;">
    <h1 class="t-headline-md" style="margin:0 0 .5rem;"><?= e(__('vibe.hero', 'Find Your Vibe')) ?></h1>
    <p class="t-body-md text-muted"><?= e(__('vibe.sub', 'Pick an aesthetic and discover boards that match your style.')) ?></p>
  </div>

  <section class="vibe-selector" aria-label="Vibe selector">
    <div>
      <p class="vibe-selector__eyebrow">Start with intent</p>
      <h2>What kind of visual direction are you looking for?</h2>
    </div>
    <div class="vibe-selector__options">
      <button type="button" data-vibe-filter="calm">Calm systems</button>
      <button type="button" data-vibe-filter="bold">Bold identity</button>
      <button type="button" data-vibe-filter="editorial">Editorial mood</button>
      <button type="button" data-vibe-filter="digital">Digital energy</button>
      <button type="button" data-vibe-filter="all" class="active">All vibes</button>
    </div>
  </section>

  <?php if (empty($allVibes)): ?>
  <div style="text-align:center;padding:4rem 1rem;color:var(--muted);">
    <p class="t-body-md"><?= e(__('vibe.no_vibes', 'No vibes configured yet.')) ?></p>
  </div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1.25rem;margin-bottom:3.5rem;">
    <?php foreach ($allVibes as $vibe):
      $vPalette = [];
      if (!empty($vibe['palette_colors'])) {
          $vPalette = json_decode($vibe['palette_colors'], true) ?? [];
      }
      $tileBg   = !empty($vPalette[0]) ? $vPalette[0] : '#7F77DD';
      $tileText = '#fff';
    ?>
    <?php
      $fallback = vibeFallbackCopy($vibe['slug']);
      $intent = str_contains($vibe['slug'], 'minimal') || $vibe['slug'] === 'japandi' || $vibe['slug'] === 'swiss-grid' ? 'calm'
        : (str_contains($vibe['slug'], 'cyber') || str_contains($vibe['slug'], 'y2k') ? 'digital'
        : (str_contains($vibe['slug'], 'fashion') || str_contains($vibe['slug'], 'academia') ? 'editorial' : 'bold'));
    ?>
    <a href="<?= e(vdj_localized_url('/vibe')) ?>?vibe=<?= urlencode($vibe['slug']) ?>"
       data-vibe-card data-intent="<?= e($intent) ?>"
       style="display:block;text-decoration:none;border-radius:var(--r-lg);overflow:hidden;background:<?= e($tileBg) ?>;color:<?= e($tileText) ?>;transition:transform .15s,box-shadow .15s;">
      <div style="padding:2rem 1.5rem;">
        <div style="font-size:1.4rem;font-weight:800;margin-bottom:.35rem;"><?= e(localizedVibeLabel($vibe)) ?></div>
        <p class="vibe-card-desc"><?= e(vibeDescription($vibe)) ?></p>
        <?php if (!empty($vibe['font_primary'])): ?>
        <div style="font-size:.8rem;opacity:.8;margin-bottom:.75rem;"><?= e($vibe['font_primary']) ?></div>
        <?php endif; ?>
        <?php if (!empty($vPalette)): ?>
        <div style="display:flex;gap:.35rem;flex-wrap:wrap;" role="list" aria-label="Palette">
          <?php foreach (array_slice($vPalette, 0, 5) as $hex): ?>
          <div role="listitem" aria-label="<?= e($hex) ?>" title="<?= e($hex) ?>"
               style="width:20px;height:20px;border-radius:.35rem;background:<?= e($hex) ?>;border:1.5px solid rgba(255,255,255,.4);"></div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ═══════════════════════════════════════════════════════════════════════
       DESIGN HISTORY PALETTES
  ════════════════════════════════════════════════════════════════════════ -->
  <div class="palette-archive-header">
    <div>
      <h2 class="palette-archive-title"><?= e(__('vibe.palettes_title', 'Design History Palettes')) ?></h2>
      <p class="palette-archive-sub"><?= e(__('vibe.palettes_sub', "500 historically sourced colour palettes from the world's most influential graphic design movements.")) ?></p>
    </div>
    <?php if ($palettesTotal > 0): ?>
    <span class="palette-archive-count"><?= number_format($palettesTotal) ?> palettes</span>
    <?php endif; ?>
  </div>

  <?php if (!empty($paletteMovements)): ?>
  <!-- Movement filter bar -->
  <div class="palette-filter-bar" role="navigation" aria-label="Filter by movement">
    <a href="<?= e(vdj_localized_url('/vibe')) ?><?= $activeMov === '' ? '' : '#palettes' ?>"
       class="palette-filter-btn <?= $activeMov === '' ? 'active' : '' ?>"><?= e(__('vibe.palettes_all', 'All')) ?></a>
    <?php foreach ($paletteMovements as $mov): ?>
    <a href="/vibe.php?mov=<?= urlencode($mov['movement']) ?>#palettes"
       class="palette-filter-btn <?= $activeMov === $mov['movement'] ? 'active' : '' ?>">
      <?= e($mov['movement']) ?>
      <span class="palette-filter-count"><?= (int)$mov['cnt'] ?></span>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (empty($palettes)): ?>
  <div class="palette-empty">
    <i class="fa-solid fa-palette"></i>
    <p><?= e(__('vibe.palette_empty', 'Palette library not loaded yet.')) ?></p>
  </div>
  <?php else: ?>

  <!-- Palette grid -->
  <div class="palette-grid" id="palettes">
    <?php foreach ($palettes as $pal):
      $colors = json_decode($pal['colors'], true) ?? [];
      $colorsJson = htmlspecialchars($pal['colors'], ENT_QUOTES);
    ?>
    <div class="palette-card" data-movement="<?= e($pal['movement']) ?>">
      <!-- Color strip -->
      <div class="palette-card__strip">
        <?php foreach ($colors as $hex): ?>
        <button class="palette-swatch"
                data-hex="<?= e($hex) ?>"
                title="Copy <?= e($hex) ?>"
                style="background:<?= e($hex) ?>;"
                aria-label="Copy colour <?= e($hex) ?>"></button>
        <?php endforeach; ?>
      </div>
      <!-- Card body -->
      <div class="palette-card__body">
        <div class="palette-card__name"><?= e($pal['name']) ?></div>
        <div class="palette-card__meta">
          <span class="palette-movement-chip"><?= e($pal['movement']) ?></span>
          <?php if (!empty($pal['era'])): ?>
          <span class="palette-era"><?= e($pal['era']) ?></span>
          <?php endif; ?>
        </div>
        <?php if (!empty($pal['source'])): ?>
        <div class="palette-card__source"><?= e($pal['source']) ?></div>
        <?php endif; ?>
        <!-- Copy actions -->
        <div class="palette-card__actions">
          <button class="palette-copy-hex" data-colors="<?= $colorsJson ?>" title="Copy HEX codes">
            <i class="fa-solid fa-copy"></i> HEX
          </button>
          <button class="palette-copy-css" data-colors="<?= $colorsJson ?>" data-slug="<?= e($pal['slug']) ?>" title="Copy as CSS variables">
            <i class="fa-solid fa-code"></i> CSS
          </button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($palettesTotalPages > 1): ?>
  <nav class="palette-pagination" aria-label="Palette pages">
    <?php if ($palettePage > 1): ?>
    <a href="<?= e(vdj_localized_url('/vibe')) ?>?<?= $activeMov ? 'mov='.urlencode($activeMov).'&' : '' ?>pp=<?= $palettePage - 1 ?>#palettes" class="palette-page-btn"><?= e(__('vibe.prev', '← Önceki')) ?></a>
    <?php endif; ?>
    <span class="palette-page-info"><?= sprintf(__('vibe.page_info', 'Page %d of %d'), $palettePage, $palettesTotalPages) ?></span>
    <?php if ($palettePage < $palettesTotalPages): ?>
    <a href="<?= e(vdj_localized_url('/vibe')) ?>?<?= $activeMov ? 'mov='.urlencode($activeMov).'&' : '' ?>pp=<?= $palettePage + 1 ?>#palettes" class="palette-page-btn"><?= e(__('vibe.next', 'Next →')) ?></a>
    <?php endif; ?>
  </nav>
  <?php endif; ?>

  <!-- Toast notification -->
  <div class="palette-toast" id="paletteToast" aria-live="polite"></div>

  <?php endif; // palettes ?>

<?php endif; ?>

</div>
</main>

<style>
a[href^="/vibe.php?vibe"]:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0,0,0,.15);
}
.vibe-detail-hero {
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(280px, .65fr);
  gap: 24px;
  background: var(--vibe-bg);
  border-radius: var(--r-lg);
  padding: clamp(1.5rem, 4vw, 2.75rem);
  margin-bottom: 2rem;
  overflow: hidden;
}
.vibe-kicker {
  margin: 0 0 .75rem;
  font-size: .75rem;
  font-weight: 800;
  letter-spacing: .12em;
  text-transform: uppercase;
  opacity: .8;
}
.vibe-detail-hero h1 {
  margin: 0 0 .75rem;
  font-size: clamp(2rem, 5vw, 3.5rem);
  font-weight: 900;
  line-height: .95;
}
.vibe-detail-hero__desc {
  max-width: 680px;
  margin: 0;
  font-size: 1rem;
  line-height: 1.65;
  opacity: .9;
}
.vibe-detail-hero__panel {
  background: rgba(255,255,255,.14);
  border: 1px solid rgba(255,255,255,.22);
  border-radius: 14px;
  padding: 16px;
  backdrop-filter: blur(14px);
}
.vibe-panel-row + .vibe-panel-row { margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,.18); }
.vibe-panel-row span:first-child {
  display: block;
  font-size: .72rem;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
  opacity: .72;
  margin-bottom: 6px;
}
.vibe-panel-row strong { font-size: .95rem; }
.vibe-swatches { display:flex;gap:.45rem;flex-wrap:wrap; }
.vibe-swatch { width:30px;height:30px;border-radius:8px;border:2px solid rgba(255,255,255,.42);cursor:pointer; }
.vibe-tag-list { display:flex;gap:6px;flex-wrap:wrap; }
.vibe-tag-list span {
  border: 1px solid rgba(255,255,255,.28);
  border-radius: 999px;
  padding: 4px 8px;
  font-size: .75rem;
  font-weight: 700;
}
.vibe-actions { display:flex;gap:10px;flex-wrap:wrap;margin-top:22px; }
.vibe-action {
  display:inline-flex;align-items:center;gap:8px;
  border:1px solid rgba(255,255,255,.28);
  border-radius:999px;
  background:rgba(255,255,255,.14);
  color:inherit;
  padding:9px 13px;
  font-size:.82rem;
  font-weight:800;
  text-decoration:none;
  cursor:pointer;
}
.vibe-selector {
  display:flex;
  justify-content:space-between;
  gap:20px;
  align-items:flex-end;
  border:1px solid var(--border);
  background:var(--surface);
  border-radius:var(--r-lg);
  padding:20px;
  margin-bottom:1.5rem;
}
.vibe-selector__eyebrow { margin:0 0 5px;color:var(--accent);font-size:.72rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase; }
.vibe-selector h2 { margin:0;font-size:1.15rem;line-height:1.25; }
.vibe-selector__options { display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end; }
.vibe-selector__options button {
  border:1px solid var(--border);
  background:#fff;
  color:var(--text);
  border-radius:999px;
  padding:8px 11px;
  font-size:.78rem;
  font-weight:800;
}
.vibe-selector__options button.active,
.vibe-selector__options button:hover { background:var(--accent);border-color:var(--accent);color:#fff; }
.vibe-card-desc {
  margin: 0 0 .8rem;
  max-width: 34ch;
  font-size: .82rem;
  line-height: 1.45;
  opacity: .82;
}

/* ── Design History Palettes ─────────────────────────────────────────── */
.palette-archive-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  margin: 0 0 1.25rem;
  flex-wrap: wrap;
}
.palette-archive-title {
  font-size: 1.6rem;
  font-weight: 800;
  margin: 0 0 .25rem;
  color: var(--text);
}
.palette-archive-sub {
  font-size: .875rem;
  color: var(--muted);
  margin: 0;
}
.palette-archive-count {
  font-size: .8rem;
  font-weight: 700;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 999px;
  padding: .25rem .75rem;
  color: var(--muted);
  white-space: nowrap;
}

/* Filter bar */
.palette-filter-bar {
  display: flex;
  gap: .5rem;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border);
}
.palette-filter-btn {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  padding: .35rem .75rem;
  border-radius: 999px;
  font-size: .8rem;
  font-weight: 600;
  background: var(--surface);
  border: 1px solid var(--border);
  color: var(--text);
  text-decoration: none;
  transition: background .15s, border-color .15s, color .15s;
  cursor: pointer;
}
.palette-filter-btn:hover { background: var(--accent); color: #fff; border-color: var(--accent); }
.palette-filter-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); }
.palette-filter-count {
  font-size: .7rem;
  opacity: .75;
}

/* Grid */
.palette-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

/* Palette card */
.palette-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 12px;
  overflow: hidden;
  transition: transform .15s, box-shadow .15s;
}
.palette-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,.09);
}
.palette-card__strip {
  display: flex;
  height: 72px;
}
.palette-swatch {
  flex: 1;
  border: none;
  cursor: pointer;
  padding: 0;
  transition: flex .2s;
  position: relative;
}
.palette-swatch:hover {
  flex: 2;
}
.palette-swatch:hover::after {
  content: attr(data-hex);
  position: absolute;
  bottom: 4px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 9px;
  font-weight: 700;
  background: rgba(0,0,0,.55);
  color: #fff;
  padding: 2px 5px;
  border-radius: 4px;
  white-space: nowrap;
  pointer-events: none;
}
.palette-card__body {
  padding: .75rem 1rem;
}
.palette-card__name {
  font-size: .875rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: .4rem;
  line-height: 1.3;
}
.palette-card__meta {
  display: flex;
  align-items: center;
  gap: .4rem;
  flex-wrap: wrap;
  margin-bottom: .4rem;
}
.palette-movement-chip {
  font-size: .7rem;
  font-weight: 700;
  background: var(--accent);
  color: #fff;
  border-radius: 999px;
  padding: .15rem .55rem;
  white-space: nowrap;
  opacity: .85;
}
.palette-era {
  font-size: .7rem;
  color: var(--muted);
  font-weight: 500;
}
.palette-card__source {
  font-size: .7rem;
  color: var(--muted);
  margin-bottom: .5rem;
  line-height: 1.35;
  font-style: italic;
}
.palette-card__actions {
  display: flex;
  gap: .4rem;
}
.palette-copy-hex,
.palette-copy-css {
  display: inline-flex;
  align-items: center;
  gap: .3rem;
  font-size: .7rem;
  font-weight: 700;
  padding: .25rem .6rem;
  border-radius: 6px;
  border: 1px solid var(--border);
  background: var(--bg);
  color: var(--muted);
  cursor: pointer;
  transition: background .15s, color .15s, border-color .15s;
}
.palette-copy-hex:hover { background: var(--accent); color: #fff; border-color: var(--accent); }
.palette-copy-css:hover { background: #3b82f6; color: #fff; border-color: #3b82f6; }

/* Pagination */
.palette-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin: 1.5rem 0 2rem;
}
.palette-page-btn {
  padding: .45rem 1rem;
  border-radius: 8px;
  background: var(--surface);
  border: 1px solid var(--border);
  color: var(--text);
  font-size: .875rem;
  font-weight: 600;
  text-decoration: none;
  transition: background .15s;
}
.palette-page-btn:hover { background: var(--accent); color: #fff; border-color: var(--accent); }
.palette-page-info { font-size: .875rem; color: var(--muted); }

/* Empty */
.palette-empty {
  text-align: center;
  padding: 3rem 1rem;
  color: var(--muted);
}
.palette-empty i { font-size: 2.5rem; margin-bottom: .75rem; display: block; opacity: .35; }

/* Toast */
.palette-toast {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  background: #1a1a1a;
  color: #fff;
  font-size: .85rem;
  font-weight: 600;
  padding: .6rem 1.1rem;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0,0,0,.25);
  pointer-events: none;
  opacity: 0;
  transform: translateY(8px);
  transition: opacity .2s, transform .2s;
  z-index: 9999;
}
.palette-toast.show {
  opacity: 1;
  transform: translateY(0);
}

@media (max-width: 640px) {
  .palette-grid { grid-template-columns: 1fr 1fr; }
  .palette-archive-title { font-size: 1.3rem; }
  .vibe-selector { align-items:flex-start; }
}
@media (max-width: 400px) {
  .palette-grid { grid-template-columns: 1fr; }
}
@media (max-width: 820px) {
  .vibe-detail-hero { grid-template-columns: 1fr; }
  .vibe-selector { flex-direction:column; }
  .vibe-selector__options { justify-content:flex-start; }
}
</style>

<script>
(function () {
  var toast = document.getElementById('paletteToast');
  var toastTimer;

  function showToast(msg) {
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 2000);
  }

  function copyText(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).catch(function () { fallbackCopy(text); });
    } else {
      fallbackCopy(text);
    }
  }

  function fallbackCopy(text) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;left:-9999px;top:-9999px;';
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
  }

  // Individual swatch click → copy hex
  document.addEventListener('click', function (e) {
    var copyCurrent = e.target.closest('[data-copy-current]');
    if (copyCurrent) {
      copyText(window.location.href);
      showToast('Copied vibe link');
      return;
    }

    var vibeSwatch = e.target.closest('.vibe-swatch');
    if (vibeSwatch) {
      copyText(vibeSwatch.dataset.hex);
      showToast('Copied ' + vibeSwatch.dataset.hex);
      return;
    }

    var vibeFilter = e.target.closest('[data-vibe-filter]');
    if (vibeFilter) {
      var filter = vibeFilter.dataset.vibeFilter;
      document.querySelectorAll('[data-vibe-filter]').forEach(function (btn) { btn.classList.toggle('active', btn === vibeFilter); });
      document.querySelectorAll('[data-vibe-card]').forEach(function (card) {
        card.style.display = (filter === 'all' || card.dataset.intent === filter) ? 'block' : 'none';
      });
      return;
    }

    var sw = e.target.closest('.palette-swatch');
    if (sw) {
      var hex = sw.dataset.hex;
      copyText(hex);
      showToast('Copied ' + hex);
      return;
    }

    // Copy all HEX
    var hex = e.target.closest('.palette-copy-hex');
    if (hex) {
      try {
        var colors = JSON.parse(hex.dataset.colors || '[]');
        copyText(colors.join('  '));
        showToast('Copied ' + colors.length + ' HEX codes');
      } catch (err) {}
      return;
    }

    // Copy as CSS variables
    var css = e.target.closest('.palette-copy-css');
    if (css) {
      try {
        var colors = JSON.parse(css.dataset.colors || '[]');
        var slug = css.dataset.slug || 'palette';
        var cssVars = colors.map(function (c, i) {
          return '  --' + slug + '-' + (i + 1) + ': ' + c + ';';
        }).join('\n');
        copyText(':root {\n' + cssVars + '\n}');
        showToast('Copied CSS variables');
      } catch (err) {}
      return;
    }
  });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
