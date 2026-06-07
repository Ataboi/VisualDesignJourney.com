<?php
/**
 * colors.php — Curated Design Color Palettes
 * Static page with 30+ curated palettes across multiple categories.
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle    = __('colors.title', 'Design Color Palettes') . ' — Visual Design Journey';
$activeNav    = '';
$metaDesc     = __('colors.description', 'Curated color palettes for designers. Explore color combinations used in iconic design movements and modern aesthetics.');
$canonicalUrl = publicUrl('/colors');
require_once __DIR__ . '/includes/header.php';

$palettes = [
    // Bauhaus
    ['name' => 'Bauhaus Primary',     'category' => 'Bauhaus',    'colors' => ['#CC0000', '#0000CC', '#FFCC00', '#FFFFFF', '#1A1A1A']],
    ['name' => 'Bauhaus Earth',       'category' => 'Bauhaus',    'colors' => ['#8B3A3A', '#D4A017', '#2E4A2E', '#F5F0E8', '#222222']],
    ['name' => 'Bauhaus Workshop',    'category' => 'Bauhaus',    'colors' => ['#E63946', '#457B9D', '#F4A261', '#264653', '#2A2A2A']],
    ['name' => 'Bauhaus Textile',     'category' => 'Bauhaus',    'colors' => ['#D62828', '#003049', '#F77F00', '#FCBF49', '#EAE2B7']],

    // Classic / Swiss
    ['name' => 'Swiss Neutral',       'category' => 'Classic',    'colors' => ['#1A1A1A', '#FFFFFF', '#E8E8E8', '#C0C0C0', '#666666']],
    ['name' => 'Swiss Red',           'category' => 'Classic',    'colors' => ['#FF0000', '#FFFFFF', '#1A1A1A', '#F2F2F2', '#888888']],
    ['name' => 'Helvetica Era',       'category' => 'Classic',    'colors' => ['#2B2B2B', '#F5F5F5', '#E30613', '#BFBFBF', '#7A7A7A']],
    ['name' => 'Modernist Grid',      'category' => 'Classic',    'colors' => ['#000000', '#FFFFFF', '#0057A8', '#D4D4D4', '#555555']],

    // Modern
    ['name' => 'Studio Blue',         'category' => 'Modern',     'colors' => ['#2D3A4A', '#4A90D9', '#F5F7FA', '#E1EBF5', '#8BA4BE']],
    ['name' => 'Matcha',              'category' => 'Modern',     'colors' => ['#3D5A3E', '#8DB48E', '#F0F4EE', '#C9DEC9', '#2B3F2C']],
    ['name' => 'Terracotta Studio',   'category' => 'Modern',     'colors' => ['#C36A2D', '#F5E0D3', '#7A3B1E', '#FAF0EA', '#D4916A']],
    ['name' => 'Neo Minimal',         'category' => 'Modern',     'colors' => ['#1C1C1C', '#F8F8F8', '#4ECDC4', '#E8E8E8', '#9ABCBA']],
    ['name' => 'Slate & Coral',       'category' => 'Modern',     'colors' => ['#4A5568', '#FF6B6B', '#F7FAFC', '#CBD5E0', '#2D3748']],

    // Muted
    ['name' => 'Dusty Rose',          'category' => 'Muted',      'colors' => ['#C9A9A6', '#E8D5D3', '#8C6F6C', '#F5EDEC', '#D4B8B5']],
    ['name' => 'Sage & Stone',        'category' => 'Muted',      'colors' => ['#87966F', '#B5C4A8', '#D8DDD0', '#F2F1EE', '#6B7A57']],
    ['name' => 'Nordic Fog',          'category' => 'Muted',      'colors' => ['#B0B8C1', '#D6DDE5', '#8C96A0', '#EEF1F4', '#697580']],
    ['name' => 'Warm Greige',         'category' => 'Muted',      'colors' => ['#C4BAB0', '#D9D2C9', '#A89D93', '#EDE9E4', '#7D726A']],

    // Bold
    ['name' => 'Neon Memphis',        'category' => 'Bold',       'colors' => ['#FF0080', '#00E5FF', '#FFD600', '#7B00FF', '#1A1A1A']],
    ['name' => 'Risograph',           'category' => 'Bold',       'colors' => ['#F76C5E', '#4DCCBD', '#FFE74C', '#2B2D42', '#FFFFFF']],
    ['name' => 'Pop Art',             'category' => 'Bold',       'colors' => ['#E63946', '#F4D03F', '#2980B9', '#1A1A1A', '#FFFFFF']],
    ['name' => 'Electric 80s',        'category' => 'Bold',       'colors' => ['#FF6EC7', '#00FF7F', '#7B2FBE', '#FFD700', '#111111']],

    // Pastel
    ['name' => 'Cotton Candy',        'category' => 'Pastel',     'colors' => ['#FFD6E0', '#FFEFCF', '#C1F0DC', '#BFDEFF', '#E8D5F5']],
    ['name' => 'Spring Bloom',        'category' => 'Pastel',     'colors' => ['#FDE4CF', '#FFCFD2', '#D4E6F1', '#CBDDD1', '#F8EDEB']],
    ['name' => 'Sorbet',              'category' => 'Pastel',     'colors' => ['#FAD7D7', '#FAF0CA', '#D4EDDD', '#D4E4F7', '#F0D4F0']],
    ['name' => 'French Macaron',      'category' => 'Pastel',     'colors' => ['#F4B8C1', '#F9DFBE', '#B8D8C8', '#BDD5F4', '#D4C0E8']],

    // Dark
    ['name' => 'Midnight Studio',     'category' => 'Dark',       'colors' => ['#0D0D0D', '#1A1A2E', '#16213E', '#0F3460', '#E94560']],
    ['name' => 'Obsidian',            'category' => 'Dark',       'colors' => ['#0A0A0A', '#1F1F1F', '#2E2E2E', '#444444', '#888888']],
    ['name' => 'Dark Academia',       'category' => 'Dark',       'colors' => ['#2C1810', '#3D2314', '#8B6347', '#C4A882', '#F5EDD6']],
    ['name' => 'Noir',                'category' => 'Dark',       'colors' => ['#000000', '#1A1A1A', '#333333', '#C0392B', '#FFFFFF']],

    // Earth Tones
    ['name' => 'Desert Sand',         'category' => 'Earth Tones','colors' => ['#C2956C', '#E5C9A6', '#8B5E3C', '#F5E6D3', '#4A2C1A']],
    ['name' => 'Forest Floor',        'category' => 'Earth Tones','colors' => ['#4A5240', '#7A8C6E', '#B5A88A', '#D4C9B0', '#2C3228']],
    ['name' => 'Autumn Harvest',      'category' => 'Earth Tones','colors' => ['#8B2500', '#D4570C', '#E8A04A', '#F5D78A', '#2C1800']],
    ['name' => 'Clay & Moss',         'category' => 'Earth Tones','colors' => ['#8B5E52', '#C4957A', '#5C6E4A', '#A8B897', '#F0E6D8']],
];

// Group by category
$byCategory = [];
foreach ($palettes as $p) {
    $byCategory[$p['category']][] = $p;
}

$categoryOrder = ['Classic', 'Bauhaus', 'Modern', 'Bold', 'Muted', 'Pastel', 'Dark', 'Earth Tones'];

function vdjColorCategoryLabel(string $category): string {
    $lang = vdj_current_language();
    $labels = [
        'tr' => [
            'Classic' => 'Klasik',
            'Bauhaus' => 'Bauhaus',
            'Modern' => 'Modern',
            'Bold' => 'Cesur',
            'Muted' => 'Sönük',
            'Pastel' => 'Pastel',
            'Dark' => 'Koyu',
            'Earth Tones' => 'Toprak Tonları',
        ],
        'de' => [
            'Classic' => 'Klassisch',
            'Bauhaus' => 'Bauhaus',
            'Modern' => 'Modern',
            'Bold' => 'Kräftig',
            'Muted' => 'Gedämpft',
            'Pastel' => 'Pastell',
            'Dark' => 'Dunkel',
            'Earth Tones' => 'Erdtöne',
        ],
    ];
    return $labels[$lang][$category] ?? $category;
}
?>
<main class="page-wrap" id="main-content">
<div class="container">

  <!-- Hero -->
  <div style="text-align:center;padding:2.5rem 0 2rem;">
    <h1 class="t-headline-md" style="margin:0 0 .5rem;"><?= e(__('colors.hero', 'Color Palettes')) ?></h1>
    <p class="t-body-md text-muted"><?= e(sprintf(__('colors.sub', '%s+ curated palettes spanning classic movements to contemporary design. Click any swatch to copy its hex code.'), count($palettes))) ?></p>
  </div>

  <!-- Category filter -->
  <nav aria-label="Category filter" style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin-bottom:2.5rem;">
    <button class="palette-filter-btn active" data-cat="all"
            style="border-radius:var(--r-pill);padding:6px 18px;border:1px solid var(--border);background:var(--accent);color:#fff;cursor:pointer;font-size:13px;font-weight:600;transition:all .15s;">
      <?= e(__('colors.all', 'All')) ?>
    </button>
    <?php foreach ($categoryOrder as $cat): ?>
      <?php if (isset($byCategory[$cat])): ?>
      <button class="palette-filter-btn" data-cat="<?= e($cat) ?>"
              style="border-radius:var(--r-pill);padding:6px 18px;border:1px solid var(--border);background:transparent;cursor:pointer;font-size:13px;font-weight:600;color:var(--text);transition:all .15s;">
        <?= e(vdjColorCategoryLabel($cat)) ?>
      </button>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>

  <!-- Palettes grid -->
  <div id="palettes-grid"
       style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.25rem;margin-bottom:3rem;">
    <?php foreach ($categoryOrder as $cat): ?>
      <?php if (!isset($byCategory[$cat])) continue; ?>
      <?php foreach ($byCategory[$cat] as $palette): ?>
      <article class="palette-card"
               data-cat="<?= e($palette['category']) ?>"
               style="border:1px solid var(--border);border-radius:var(--r-lg);overflow:hidden;background:var(--surface);transition:border-color .18s,box-shadow .18s;">
        <!-- Color swatches -->
        <div style="display:flex;height:80px;">
          <?php foreach ($palette['colors'] as $hex): ?>
          <button class="swatch-btn"
                  data-hex="<?= e($hex) ?>"
                  title="Copy <?= e($hex) ?>"
                  aria-label="Copy color <?= e($hex) ?>"
                  style="flex:1;background:<?= e($hex) ?>;border:none;cursor:pointer;transition:transform .15s;position:relative;"
                  onclick="copyHex(this, '<?= e($hex) ?>')">
          </button>
          <?php endforeach; ?>
        </div>
        <!-- Info -->
        <div style="padding:12px 14px;">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
            <h3 style="font-size:.92rem;font-weight:700;margin:0;"><?= e($palette['name']) ?></h3>
            <span style="font-size:11px;font-weight:600;color:var(--muted);background:var(--bg);border:1px solid var(--border);border-radius:var(--r-pill);padding:2px 9px;white-space:nowrap;"><?= e(vdjColorCategoryLabel($palette['category'])) ?></span>
          </div>
          <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap;">
            <?php foreach ($palette['colors'] as $hex): ?>
            <code style="font-size:10px;color:var(--muted);cursor:pointer;" onclick="copyHex(null, '<?= e($hex) ?>')"><?= e($hex) ?></code>
            <?php endforeach; ?>
          </div>
          <a href="/index.php?q=<?= urlencode($palette['name']) ?>"
             style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--accent);margin-top:10px;text-decoration:none;opacity:.8;">
            <i class="fa-solid fa-magnifying-glass" style="font-size:10px;"></i>
            <?= e(__('colors.find_boards', 'Find boards with this palette')) ?>
          </a>
        </div>
      </article>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </div>

  <!-- Copy toast -->
  <div id="copy-toast"
       style="display:none;position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1a1a1a;color:#fff;padding:10px 20px;border-radius:var(--r-pill);font-size:13px;font-weight:600;z-index:9999;pointer-events:none;box-shadow:0 4px 16px rgba(0,0,0,.25);">
    <?= e(__('colors.copied', 'Copied!')) ?>
  </div>

</div>
</main>

<style>
.palette-card:hover {
  border-color: var(--accent);
  box-shadow: 0 4px 16px rgba(0,0,0,.08);
}
.swatch-btn:hover {
  transform: scaleY(1.08);
  z-index: 1;
}
.palette-filter-btn:hover,
.palette-filter-btn.active {
  background: var(--accent) !important;
  color: #fff !important;
  border-color: var(--accent) !important;
}
</style>

<script>
function copyHex(btn, hex) {
  navigator.clipboard.writeText(hex).then(() => {
    const toast = document.getElementById('copy-toast');
    toast.textContent = hex + ' copied!';
    toast.style.display = 'block';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => { toast.style.display = 'none'; }, 1800);
  });
}

(function () {
  const filterBtns = document.querySelectorAll('.palette-filter-btn');
  const cards = document.querySelectorAll('.palette-card');

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const cat = btn.dataset.cat;
      cards.forEach(card => {
        const show = cat === 'all' || card.dataset.cat === cat;
        card.style.display = show ? '' : 'none';
      });
    });
  });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
