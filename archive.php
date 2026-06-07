<?php
/**
 * archive.php — Design Movement Archive + Chronological Board Archive
 *
 * Routes:
 *   /archive           → index: lists all 10 design movements
 *   /archive/bauhaus   → movement detail page (via .htaccess → ?style=bauhaus)
 *   /archive.php       → falls back to chronological archive (legacy)
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

session_start_if_not_started();

// ── Design Movements definition ───────────────────────────────────────────────
$movements = [
    'bauhaus' => [
        'label'       => 'Bauhaus',
        'years'       => '1919–1933',
        'color'       => '#E63946',
        'tag_filter'  => 'Bauhaus',
        'icon'        => 'fa-solid fa-square',
        'description' => 'Bauhaus was a German art school that combined fine arts with crafts and functional design. Founded by Walter Gropius in Weimar, it revolutionized art education and laid the groundwork for modern graphic design. Its philosophy of "form follows function" continues to influence designers worldwide.',
        'characteristics' => [
            'Geometric abstraction and primary colours',
            'Integration of fine art and industrial craft',
            'Typography as visual form',
            'Minimalist ornamentation, functional purpose',
            'Grid-based, systematic composition',
        ],
    ],
    'swiss-grid' => [
        'label'       => 'Swiss Grid',
        'years'       => '1950s–1970s',
        'color'       => '#2B2D42',
        'tag_filter'  => 'Swiss',
        'icon'        => 'fa-solid fa-table-cells',
        'description' => 'The International Typographic Style, commonly known as Swiss Design, emerged from Switzerland and Germany in the mid-twentieth century. It championed objectivity, cleanliness, and mathematical grid systems as the foundation of visual communication. Its influence on modern UI and editorial design is immeasurable.',
        'characteristics' => [
            'Mathematical grid systems',
            'Helvetica and sans-serif type dominance',
            'Asymmetric but balanced layouts',
            'Photography over illustration',
            'Objective, neutral visual language',
        ],
    ],
    'brutalist' => [
        'label'       => 'Brutalist',
        'years'       => '1950s–present',
        'color'       => '#FF6B35',
        'tag_filter'  => 'Brutalist',
        'icon'        => 'fa-solid fa-cubes',
        'description' => 'Brutalism in design is raw, unapologetic, and deliberately confrontational. Borrowed from the architectural movement that celebrated exposed concrete and honest materiality, graphic and web brutalism rejects polish in favour of bold typography, harsh contrasts, and visible structure. It is simultaneously retro and radically contemporary.',
        'characteristics' => [
            'Raw, unpolished visual language',
            'Heavy borders and harsh colour contrast',
            'Exposed structural elements',
            'Anti-decorative, anti-"pretty" aesthetics',
            'Bold oversized typography',
        ],
    ],
    'minimalism' => [
        'label'       => 'Minimalism',
        'years'       => '1960s–present',
        'color'       => '#7F77DD',
        'tag_filter'  => 'Minimal',
        'icon'        => 'fa-regular fa-circle',
        'description' => 'Minimalism strips design down to its essential elements, eliminating anything that does not serve a clear purpose. Emerging from fine art in the 1960s, it became the dominant visual language of tech, luxury branding, and editorial design. White space is not empty — it is intentional breathing room.',
        'characteristics' => [
            'Abundant negative (white) space',
            'Monochromatic or very limited palette',
            'Clean, geometric typefaces',
            'Single focal point per composition',
            '"Less is more" philosophy',
        ],
    ],
    'art-nouveau' => [
        'label'       => 'Art Nouveau',
        'years'       => '1890–1910',
        'color'       => '#4A7C59',
        'tag_filter'  => 'Art Nouveau',
        'icon'        => 'fa-solid fa-seedling',
        'description' => 'Art Nouveau swept across Europe at the turn of the twentieth century, drawing inspiration from natural forms, flowing organic lines, and ornamental excess. It was the first truly international design style, appearing in architecture, typography, jewellery, and poster art simultaneously. Its sinuous curves and floral motifs remain instantly recognisable.',
        'characteristics' => [
            'Flowing, organic, vine-like curves',
            'Botanical and natural motifs',
            'Asymmetric, dynamic compositions',
            'Rich decorative ornamentation',
            'Integration of text and image as one',
        ],
    ],
    'editorial' => [
        'label'       => 'Editorial Design',
        'years'       => '1950s–present',
        'color'       => '#C77DFF',
        'tag_filter'  => 'Editorial',
        'icon'        => 'fa-solid fa-newspaper',
        'description' => 'Editorial design governs how content is structured, paced, and presented across magazines, books, and digital publications. It is the art of guiding the reader\'s eye through hierarchy, contrast, and rhythm. Great editorial design makes complex information feel effortless and reading feel like pleasure.',
        'characteristics' => [
            'Strong typographic hierarchy',
            'Deliberate use of visual rhythm and pacing',
            'Full-bleed photography and white space interplay',
            'Column-based grid systems',
            'Caption, headline, and body type as distinct voices',
        ],
    ],
    'web-design' => [
        'label'       => 'Web Design',
        'years'       => '1990s–present',
        'color'       => '#0077B6',
        'tag_filter'  => 'Web',
        'icon'        => 'fa-solid fa-globe',
        'description' => 'Web design has evolved from static HTML pages to responsive, interactive, immersive experiences in just three decades. Each era — from skeuomorphism to flat design to glassmorphism — reflects the technology and cultural values of its time. Today it sits at the intersection of visual design, motion, and interaction.',
        'characteristics' => [
            'Responsive, device-agnostic layouts',
            'Interactive and animated elements',
            'Accessibility and usability as first principles',
            'Component-driven visual systems',
            'Continuous evolution driven by technology shifts',
        ],
    ],
    'poster' => [
        'label'       => 'Poster Design',
        'years'       => '1880s–present',
        'color'       => '#D62828',
        'tag_filter'  => 'Poster',
        'icon'        => 'fa-solid fa-rectangle-ad',
        'description' => 'The poster is one of the oldest and most democratic forms of visual communication. From nineteenth-century lithographs to psychedelic concert posters to today\'s digital prints, the format demands maximum impact from a single frozen moment. A great poster works at a distance and rewards close inspection.',
        'characteristics' => [
            'Single, immediate visual hook',
            'Hierarchy readable at a glance',
            'Bold colour and high contrast',
            'Integration of image and typographic message',
            'Designed for specific context and audience',
        ],
    ],
    'typography' => [
        'label'       => 'Typography',
        'years'       => '1450s–present',
        'color'       => '#1B1B2F',
        'tag_filter'  => 'Typographic',
        'icon'        => 'fa-solid fa-font',
        'description' => 'Typography is the oldest and most fundamental tool in a designer\'s arsenal — it predates the printing press. From Gutenberg\'s moveable type to variable fonts rendered in browsers, type sets tone, conveys meaning beyond words, and gives identity to every piece of communication. Typography-led design treats letterforms as both language and art.',
        'characteristics' => [
            'Type as the primary visual and communicative element',
            'Mastery of spacing: kerning, leading, tracking',
            'Typeface personality matched to message',
            'Expressive, experimental letterform arrangements',
            'Hierarchy through scale, weight, and style contrast',
        ],
    ],
    'branding' => [
        'label'       => 'Branding',
        'years'       => '1950s–present',
        'color'       => '#F4A261',
        'tag_filter'  => 'Corporate',
        'icon'        => 'fa-solid fa-star',
        'description' => 'Branding design creates the visual and emotional identity of organisations, products, and ideas. It encompasses logos, colour systems, typography, tone of voice, and every touchpoint a person has with a brand. Great branding is consistent, memorable, and flexible enough to work everywhere — from a business card to a billboard.',
        'characteristics' => [
            'Distinctive, scalable logomark',
            'Cohesive colour and typeface system',
            'Consistent cross-channel visual language',
            'Emotional resonance and differentiation',
            'Guidelines that enable and constrain simultaneously',
        ],
    ],
];

$movementEnhancements = [
    'bauhaus' => [
        'timeline' => ['1919: Founded in Weimar', '1925: Moves to Dessau', '1933: Closed under political pressure'],
        'designers' => ['Walter Gropius', 'László Moholy-Nagy', 'Herbert Bayer', 'Josef Albers'],
        'vibes' => ['bauhaus', 'soft-minimal', 'swiss-grid'],
        'reading' => ['Bauhaus typography', 'Primary color systems', 'Modernist poster composition'],
    ],
    'swiss-grid' => [
        'timeline' => ['1950s: International Typographic Style emerges', '1957: Helvetica released', '1960s: Grid systems shape corporate design'],
        'designers' => ['Josef Müller-Brockmann', 'Armin Hofmann', 'Max Bill', 'Emil Ruder'],
        'vibes' => ['swiss-grid', 'soft-minimal', 'japandi'],
        'reading' => ['Grid systems', 'Helvetica and neutrality', 'Editorial hierarchy'],
    ],
    'brutalist' => [
        'timeline' => ['1950s: Architectural brutalism rises', '1990s: Early web rawness appears', '2010s: Digital brutalism returns'],
        'designers' => ['Alison and Peter Smithson', 'Le Corbusier', 'Experimental web studios'],
        'vibes' => ['brutalist', 'neon-cyber', 'y2k-revival'],
        'reading' => ['Raw interface design', 'High contrast typography', 'Anti-polish aesthetics'],
    ],
    'minimalism' => [
        'timeline' => ['1960s: Minimal art influences design', '1980s: Luxury restraint becomes brand language', '2000s: Minimal UI dominates tech'],
        'designers' => ['Dieter Rams', 'Massimo Vignelli', 'Naoto Fukasawa'],
        'vibes' => ['soft-minimal', 'japandi', 'swiss-grid'],
        'reading' => ['Whitespace', 'Design restraint', 'Functional hierarchy'],
    ],
    'art-nouveau' => [
        'timeline' => ['1890s: Style spreads across Europe', '1900: Paris Exposition amplifies it', '1910s: Modernism begins replacing ornament'],
        'designers' => ['Alphonse Mucha', 'Aubrey Beardsley', 'Hector Guimard'],
        'vibes' => ['art-nouveau', 'cottagecore', 'maximalist'],
        'reading' => ['Organic linework', 'Decorative typography', 'Poster lithography'],
    ],
    'editorial' => [
        'timeline' => ['1950s: Magazine art direction matures', '1990s: Digital publishing expands', '2010s: Responsive editorial systems emerge'],
        'designers' => ['Alexey Brodovitch', 'Willy Fleckhaus', 'Fabien Baron'],
        'vibes' => ['high-fashion', 'dark-academia', 'soft-minimal'],
        'reading' => ['Magazine grids', 'Image-led storytelling', 'Typographic pacing'],
    ],
    'web-design' => [
        'timeline' => ['1991: First website goes live', '2007: Mobile web changes layout', '2010s: Component systems and responsive design mature'],
        'designers' => ['Tim Berners-Lee', 'Khoi Vinh', 'Jen Simmons'],
        'vibes' => ['neon-cyber', 'y2k-revival', 'brutalist'],
        'reading' => ['Responsive grids', 'Interaction patterns', 'Digital visual culture'],
    ],
    'poster' => [
        'timeline' => ['1880s: Lithographic posters boom', '1960s: Psychedelic posters reshape culture', 'Today: Digital poster systems evolve'],
        'designers' => ['Jules Chéret', 'Paula Scher', 'Milton Glaser'],
        'vibes' => ['maximalist', 'bauhaus', 'art-nouveau'],
        'reading' => ['Visual hierarchy', 'Single-image impact', 'Type and image integration'],
    ],
    'typography' => [
        'timeline' => ['1450s: Movable type transforms publishing', '1950s: Modern sans-serifs rise', '2016: Variable fonts enter browsers'],
        'designers' => ['Gutenberg', 'Adrian Frutiger', 'Matthew Carter', 'Zuzana Licko'],
        'vibes' => ['swiss-grid', 'dark-academia', 'brutalist'],
        'reading' => ['Typeface anatomy', 'Kerning and spacing', 'Expressive letterforms'],
    ],
    'branding' => [
        'timeline' => ['1950s: Corporate identity systems formalize', '1980s: Brand guidelines scale globally', '2010s: Responsive identity systems become standard'],
        'designers' => ['Paul Rand', 'Saul Bass', 'Chermayeff & Geismar'],
        'vibes' => ['high-fashion', 'soft-minimal', 'maximalist'],
        'reading' => ['Identity systems', 'Logo scalability', 'Brand consistency'],
    ],
];

foreach ($movements as $slug => &$movement) {
    $movement += $movementEnhancements[$slug] ?? [
        'timeline' => [],
        'designers' => [],
        'vibes' => [],
        'reading' => [],
    ];
}
unset($movement);

function renderArchiveProductStyles(): void {
    ?>
    <style>
    .movement-hero,.archive-index-hero{background:linear-gradient(135deg,var(--surface-lo, #f7f7fb),var(--accent-soft,rgba(127,119,221,.12)));border-bottom:1px solid var(--border);padding:clamp(56px,8vw,96px) 0}
    .movement-hero{background:linear-gradient(135deg,color-mix(in srgb,var(--mv-color) 14%,transparent),var(--surface-lo,#f7f7fb))}
    .movement-hero__back{display:inline-flex;align-items:center;gap:8px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:700;margin-bottom:22px}
    .movement-hero__eyebrow,.archive-index-hero__eyebrow{display:inline-flex;align-items:center;gap:8px;color:var(--accent);font-size:12px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;margin-bottom:16px}
    .movement-hero__title,.archive-index-hero__title{font-family:var(--font-display);font-size:clamp(42px,7vw,82px);line-height:.98;letter-spacing:-.04em;margin:0 0 18px;color:var(--text)}
    .movement-hero__desc,.archive-index-hero__sub{max-width:780px;color:var(--muted);font-size:clamp(16px,2vw,19px);line-height:1.7;margin:0 0 24px}
    .movement-characteristics{display:flex;gap:8px;flex-wrap:wrap;list-style:none;margin:28px 0 0;padding:0}
    .characteristic-pill,.movement-hero__stat{display:inline-flex;align-items:center;gap:7px;border:1px solid var(--border);background:var(--bg);border-radius:999px;padding:8px 12px;font-size:13px;font-weight:700;color:var(--text)}
    .characteristic-pill__icon{color:var(--mv-color)}
    .movement-hero__stats{margin-top:18px}
    .movement-section-title{font-size:1.35rem;font-weight:850;letter-spacing:-.02em;margin:0;color:var(--text)}
    .movement-knowledge{border-bottom:1px solid var(--border);background:var(--bg);padding:28px 0}
    .movement-knowledge-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:14px}
    .movement-info-panel{border:1px solid var(--border);border-radius:12px;background:var(--surface);padding:18px}
    .movement-info-panel h2{display:flex;align-items:center;gap:9px;font-size:.95rem;margin:0 0 14px;color:var(--text)}
    .movement-info-panel h2 i{color:var(--accent)}
    .movement-timeline{margin:0;padding-left:18px;color:var(--muted);font-size:13px;line-height:1.65}
    .movement-timeline li+li{margin-top:5px}
    .movement-chip-list{display:flex;gap:7px;flex-wrap:wrap}
    .movement-chip-list span,.movement-chip-list a{display:inline-flex;border:1px solid var(--border);border-radius:999px;background:var(--bg);padding:6px 9px;color:var(--text);font-size:12px;font-weight:750;text-decoration:none}
    .movement-chip-list a:hover{border-color:var(--accent);color:var(--accent)}
    .movement-grid,.movement-index-grid,.movement-related-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px}
    .movement-card,.movement-index-card,.movement-related-card{background:var(--bg);border:1px solid var(--border);border-radius:var(--r-lg);overflow:hidden;text-decoration:none;color:var(--text);transition:transform .18s,box-shadow .18s,border-color .18s}
    .movement-card:hover,.movement-index-card:hover,.movement-related-card:hover{transform:translateY(-3px);box-shadow:0 12px 34px rgba(0,0,0,.09);border-color:color-mix(in srgb,var(--mv-color,var(--accent)) 40%,var(--border))}
    .movement-card__thumb-link{display:block;background:var(--surface)}
    .movement-card__thumb{width:100%;aspect-ratio:4/3;object-fit:cover;display:block}
    .movement-card__thumb--placeholder{display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--mv-color) 12%,var(--surface))}
    .movement-card__body,.movement-index-card__body,.movement-related-card__body{padding:16px}
    .movement-card__title,.movement-index-card__title{font-size:1rem;font-weight:800;line-height:1.3;margin:0 0 9px}
    .movement-card__title a{color:inherit;text-decoration:none}
    .movement-card__meta{display:flex;justify-content:space-between;gap:10px;align-items:center;color:var(--muted);font-size:12px}
    .movement-card__author{display:flex;align-items:center;gap:8px;text-decoration:none;color:inherit;min-width:0}
    .movement-card__likes{display:flex;align-items:center;gap:5px;white-space:nowrap}
    .movement-card__tags{display:flex;gap:6px;flex-wrap:wrap;margin-top:12px}
    .movement-index-card__bar,.movement-related-card__bar{height:5px;background:var(--mv-color,var(--rel-color,var(--accent)))}
    .movement-index-card__header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px}
    .movement-index-card__icon,.movement-related-card__icon{color:var(--mv-color,var(--rel-color,var(--accent)));font-size:20px}
    .movement-index-card__years,.movement-related-card__years{font-size:12px;color:var(--muted);font-weight:800}
    .movement-index-card__desc{color:var(--muted);font-size:13px;line-height:1.55;margin:0 0 13px}
    .movement-index-card__traits{display:flex;gap:5px;flex-wrap:wrap;margin-bottom:16px}
    .movement-index-card__traits span{font-size:11px;font-weight:750;border:1px solid var(--border);border-radius:999px;padding:4px 7px;color:var(--muted)}
    .movement-index-card__footer{display:flex;align-items:center;justify-content:space-between;gap:12px;color:var(--muted);font-size:12px;font-weight:800}
    .movement-index-card__cta{color:var(--accent)}
    .movement-empty{text-align:center;border:1px solid var(--border);border-radius:var(--r-lg);background:var(--surface);padding:48px 20px}
    .movement-empty__icon{font-size:38px;color:var(--muted);opacity:.35;margin-bottom:12px}
    .movement-empty__title{font-size:1.1rem;font-weight:850;margin:0 0 6px}
    .movement-empty__sub{color:var(--muted);margin:0 0 18px}
    .movement-empty__cta,.archive-index-link-btn{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--accent);border-radius:999px;padding:10px 14px;color:var(--accent);font-weight:800;text-decoration:none}
    .movement-related{border-top:1px solid var(--border);background:var(--surface);padding:54px 0}
    .movement-related-card__label{font-size:1rem;font-weight:850;margin:0 0 4px}
    [data-animate]{opacity:0;transform:translateY(14px);transition:opacity .45s ease,transform .45s ease}
    [data-animate].is-visible{opacity:1;transform:translateY(0)}
    @media(max-width:640px){.movement-characteristics{display:grid}.movement-index-card__footer{align-items:flex-start;flex-direction:column}.movement-hero,.archive-index-hero{text-align:left}}
    </style>
    <?php
}

$db = getDB();

// ── Route: movement detail page ───────────────────────────────────────────────
$styleSlug = trim($_GET['style'] ?? '');

if ($styleSlug !== '') {
    // ── Unknown movement → 404 ────────────────────────────────────────────────
    if (!isset($movements[$styleSlug])) {
        http_response_code(404);
        $pageTitle   = '404 — Movement Not Found';
        $activeNav   = 'explore';
        $metaDesc    = 'This design movement page could not be found.';
        $canonicalUrl = publicUrl('/archive');
        require_once __DIR__ . '/includes/header.php';
        echo '<main class="page-wrap" style="padding-top:var(--nav-h);text-align:center;padding:120px 24px;">';
        echo '<h1 style="font-family:var(--font-display);font-size:40px;margin-bottom:16px;">Movement not found</h1>';
        echo '<p style="color:var(--muted);margin-bottom:32px;">That design movement does not exist in our archive.</p>';
        echo '<a href="/archive" style="color:var(--accent);">← Back to Archive</a>';
        echo '</main>';
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }

    $mv = $movements[$styleSlug];

    // ── Fetch boards for this movement ────────────────────────────────────────
    $boards = [];
    try {
        $stmt = $db->prepare(
            "SELECT b.*, u.username, u.avatar,
                    (SELECT COUNT(*) FROM board_likes bl WHERE bl.board_id = b.id) AS like_count
             FROM boards b
             JOIN users u ON u.id = b.user_id
             WHERE FIND_IN_SET(?, REPLACE(b.style_tags, ', ', ',')) > 0
               AND b.is_draft = 0
             ORDER BY like_count DESC, b.created_at DESC
             LIMIT 24"
        );
        $stmt->execute([$mv['tag_filter']]);
        $boards = $stmt->fetchAll();
    } catch (PDOException $e) {
        // DB error — show empty state
    }

    // Related movements: pick 3 others (excluding current)
    $relatedKeys = array_filter(array_keys($movements), fn($k) => $k !== $styleSlug);
    $relatedKeys = array_values($relatedKeys);
    // Pick 3 varied ones (spread across the list)
    $total = count($relatedKeys);
    $picks = [
        $relatedKeys[0],
        $relatedKeys[(int)floor($total / 2)],
        $relatedKeys[$total - 1],
    ];

    // ── SEO meta ──────────────────────────────────────────────────────────────
    $pageTitle    = $mv['label'] . ' Design Movement — Visual Design Journey Archive';
    $activeNav    = 'archive';
    $metaDesc     = mb_substr(
        $mv['label'] . ' (' . $mv['years'] . ') design movement — curated mood boards exploring '
        . strtolower($mv['description'])
        . ' Discover visual references on Visual Design Journey.',
        0, 160
    );
    $canonicalUrl = publicUrl('/archive/' . $styleSlug);

    // Schema.org: CollectionPage for this movement
    $headExtra = '<script type="application/ld+json">' . json_encode([
        '@context'  => 'https://schema.org',
        '@type'     => 'CollectionPage',
        'name'      => $mv['label'] . ' Design Movement — Visual Design Journey',
        'description' => $mv['description'],
        'url'       => SITE_URL . '/archive/' . $styleSlug,
        'about'     => [
            '@type'       => 'Thing',
            'name'        => $mv['label'],
            'description' => $mv['description'],
        ],
        'isPartOf'  => [
            '@type' => 'WebSite',
            'name'  => 'Visual Design Journey',
            'url'   => SITE_URL,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

    // Schema.org: BreadcrumbList
    $headExtra .= '<script type="application/ld+json">' . json_encode([
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',    'item' => SITE_URL],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Archive', 'item' => SITE_URL . '/archive'],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $mv['label'], 'item' => SITE_URL . '/archive/' . $styleSlug],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

    require_once __DIR__ . '/includes/header.php';
    renderArchiveProductStyles();
    ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkA==" crossorigin="anonymous" referrerpolicy="no-referrer"/>

    <main class="page-wrap" id="main-content" style="padding-top:var(--nav-h);">

      <!-- ── Breadcrumb ─────────────────────────────────────────────────────── -->
      <div class="container" style="padding-top:16px;">
        <nav aria-label="Breadcrumb" style="font-size:13px;color:var(--muted);margin-bottom:24px;">
          <a href="/" style="color:var(--muted);text-decoration:none;">Home</a>
          <span style="margin:0 6px;">›</span>
          <a href="/archive" style="color:var(--muted);text-decoration:none;">Archive</a>
          <span style="margin:0 6px;">›</span>
          <span style="color:var(--text);"><?= e($mv['label']) ?></span>
        </nav>
      </div>

      <!-- ── Movement Hero ───────────────────────────────────────────────────── -->
      <section class="movement-hero" style="--mv-color:<?= e($mv['color']) ?>;">
        <div class="container">
          <a href="/archive" class="movement-hero__back">
            <i class="fa-solid fa-arrow-left"></i>
            All Movements
          </a>
          <div class="movement-hero__eyebrow">
            <i class="<?= e($mv['icon']) ?>"></i>
            <?= e($mv['years']) ?>
          </div>
          <h1 class="movement-hero__title"><?= e($mv['label']) ?></h1>
          <p class="movement-hero__desc"><?= e($mv['description']) ?></p>

          <!-- Characteristics pills -->
          <ul class="movement-characteristics" aria-label="Key characteristics">
            <?php foreach ($mv['characteristics'] as $char): ?>
              <li class="characteristic-pill">
                <i class="fa-solid fa-check characteristic-pill__icon"></i>
                <?= e($char) ?>
              </li>
            <?php endforeach; ?>
          </ul>

          <!-- Stats -->
          <div class="movement-hero__stats">
            <span class="movement-hero__stat">
              <i class="fa-solid fa-layer-group"></i>
              <?= count($boards) ?> board<?= count($boards) !== 1 ? 's' : '' ?> curated
            </span>
          </div>
        </div>
      </section>

      <section class="movement-knowledge">
        <div class="container">
          <div class="movement-knowledge-grid">
            <?php if (!empty($mv['timeline'])): ?>
            <article class="movement-info-panel">
              <h2><i class="fa-solid fa-timeline"></i> Timeline</h2>
              <ol class="movement-timeline">
                <?php foreach ($mv['timeline'] as $item): ?>
                  <li><?= e($item) ?></li>
                <?php endforeach; ?>
              </ol>
            </article>
            <?php endif; ?>

            <?php if (!empty($mv['designers'])): ?>
            <article class="movement-info-panel">
              <h2><i class="fa-solid fa-user-pen"></i> Key designers</h2>
              <div class="movement-chip-list">
                <?php foreach ($mv['designers'] as $designer): ?>
                  <span><?= e($designer) ?></span>
                <?php endforeach; ?>
              </div>
            </article>
            <?php endif; ?>

            <?php if (!empty($mv['vibes'])): ?>
            <article class="movement-info-panel">
              <h2><i class="fa-solid fa-wand-magic-sparkles"></i> Related vibes</h2>
              <div class="movement-chip-list">
                <?php foreach ($mv['vibes'] as $vibe): ?>
                  <a href="<?= e(vdj_localized_url('/vibe')) ?>?vibe=<?= urlencode($vibe) ?>"><?= e(ucwords(str_replace('-', ' ', $vibe))) ?></a>
                <?php endforeach; ?>
              </div>
            </article>
            <?php endif; ?>

            <?php if (!empty($mv['reading'])): ?>
            <article class="movement-info-panel">
              <h2><i class="fa-solid fa-book-open"></i> Suggested reading</h2>
              <div class="movement-chip-list">
                <?php foreach ($mv['reading'] as $term): ?>
                  <a href="<?= e(vdj_localized_url('/blog')) ?>?q=<?= urlencode($term) ?>"><?= e($term) ?></a>
                <?php endforeach; ?>
              </div>
            </article>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <!-- ── Board Gallery ────────────────────────────────────────────────────── -->
      <div class="container" style="padding-top:56px;padding-bottom:16px;">
        <h2 class="movement-section-title">
          <i class="fa-solid fa-images" style="color:<?= e($mv['color']) ?>;margin-right:10px;"></i>
          <?= e($mv['label']) ?> Boards
        </h2>
      </div>

      <div class="container" style="padding-bottom:72px;">
        <?php if (empty($boards)): ?>
          <!-- Empty state -->
          <div class="movement-empty">
            <div class="movement-empty__icon">
              <i class="fa-regular fa-folder-open"></i>
            </div>
            <p class="movement-empty__title">No boards yet</p>
            <p class="movement-empty__sub">
              Be the first to create a <?= e($mv['label']) ?> mood board on Visual Design Journey.
            </p>
            <a href="/create.php" class="movement-empty__cta">
              <i class="fa-solid fa-plus"></i>
              Create one →
            </a>
          </div>

        <?php else: ?>
          <div class="movement-grid">
            <?php foreach ($boards as $board):
              $boardTags = parseTags($board['style_tags'] ?? '');
              $coverSrc  = !empty($board['cover_image']) ? imageUrl($board['cover_image']) : null;
            ?>
            <article class="movement-card" data-animate>
              <!-- Cover -->
              <a href="/board.php?id=<?= (int)$board['id'] ?>" class="movement-card__thumb-link" tabindex="-1" aria-hidden="true">
                <?php if ($coverSrc): ?>
                  <img
                    src="<?= e($coverSrc) ?>"
                    alt="<?= e($board['title']) ?>"
                    class="movement-card__thumb"
                    loading="lazy"
                    decoding="async"
                  />
                <?php else: ?>
                  <div class="movement-card__thumb movement-card__thumb--placeholder" style="--mv-color:<?= e($mv['color']) ?>;">
                    <i class="fa-regular fa-image" style="font-size:36px;opacity:.4;"></i>
                  </div>
                <?php endif; ?>
              </a>

              <!-- Body -->
              <div class="movement-card__body">
                <h3 class="movement-card__title">
                  <a href="/board.php?id=<?= (int)$board['id'] ?>"><?= e($board['title']) ?></a>
                </h3>
                <div class="movement-card__meta">
                  <a href="/profile.php?u=<?= urlencode($board['username']) ?>" class="movement-card__author">
                    <?php if (!empty($board['avatar'])): ?>
                      <img
                        src="<?= e(imageUrl($board['avatar'])) ?>"
                        alt="<?= e($board['username']) ?>"
                        class="avatar avatar--sm"
                        loading="lazy"
                      />
                    <?php else: ?>
                      <span class="avatar--initials" aria-hidden="true">
                        <?= strtoupper(substr($board['username'], 0, 1)) ?>
                      </span>
                    <?php endif; ?>
                    <span><?= e($board['username']) ?></span>
                  </a>
                  <span class="movement-card__likes" aria-label="<?= (int)$board['like_count'] ?> likes">
                    <i class="fa-<?= (int)$board['like_count'] > 0 ? 'solid' : 'regular' ?> fa-heart"
                       style="color:<?= (int)$board['like_count'] > 0 ? 'var(--accent)' : 'var(--muted)' ?>;"></i>
                    <?= formatCount((int)$board['like_count']) ?>
                  </span>
                </div>

                <?php if (!empty($boardTags)): ?>
                  <div class="movement-card__tags">
                    <?php foreach (array_slice($boardTags, 0, 3) as $t): ?>
                      <a href="/archive.php?tag=<?= urlencode($t) ?>" class="tag"><?= e($t) ?></a>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </article>
            <?php endforeach; ?>
          </div><!-- /movement-grid -->
        <?php endif; ?>
      </div>

      <!-- ── Related Movements ────────────────────────────────────────────────── -->
      <div class="movement-related">
        <div class="container">
          <h2 class="movement-section-title" style="margin-bottom:28px;">
            <i class="fa-solid fa-compass" style="color:var(--accent);margin-right:10px;"></i>
            Related Movements
          </h2>
          <div class="movement-related-grid">
            <?php foreach ($picks as $key):
              $rel = $movements[$key];
            ?>
            <a href="/archive/<?= e($key) ?>" class="movement-related-card" style="--rel-color:<?= e($rel['color']) ?>;">
              <div class="movement-related-card__bar"></div>
              <div class="movement-related-card__body">
                <i class="<?= e($rel['icon']) ?> movement-related-card__icon"></i>
                <p class="movement-related-card__label"><?= e($rel['label']) ?></p>
                <p class="movement-related-card__years"><?= e($rel['years']) ?></p>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </main>

    <script>
    (function () {
      'use strict';
      var cards = document.querySelectorAll('[data-animate]');
      if (!cards.length) return;
      if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add('is-visible');
              io.unobserve(entry.target);
            }
          });
        }, { threshold: 0.06, rootMargin: '0px 0px -32px 0px' });
        cards.forEach(function (card, i) {
          card.style.transitionDelay = Math.min(i % 12, 11) * 40 + 'ms';
          io.observe(card);
        });
      } else {
        cards.forEach(function (card) { card.classList.add('is-visible'); });
      }
    }());
    </script>

    <?php require_once __DIR__ . '/includes/footer.php';
    exit;
}

// ── Route: Archive index (all movements) ─────────────────────────────────────
if (!isset($_GET['page']) && !isset($_GET['q']) && !isset($_GET['tag']) && !isset($_GET['sort'])) {
    // Fetch board counts per tag_filter
    $tagCounts = [];
    try {
        foreach ($movements as $slug => $mv) {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM boards b
                 WHERE FIND_IN_SET(?, REPLACE(b.style_tags, ', ', ',')) > 0
                   AND b.is_draft = 0"
            );
            $stmt->execute([$mv['tag_filter']]);
            $tagCounts[$slug] = (int)$stmt->fetchColumn();
        }
    } catch (PDOException $e) {
        // silent
    }

    $pageTitle    = 'Design Movement Archive — Visual Design Journey';
    $activeNav    = 'archive';
    $metaDesc     = 'Explore ten defining design movements — Bauhaus, Minimalism, Art Nouveau, Swiss Style, Y2K, Brutalism, and more. Curated mood boards for each era on Visual Design Journey.';
    $canonicalUrl = publicUrl('/archive');

    require_once __DIR__ . '/includes/header.php';
    renderArchiveProductStyles();
    ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkA==" crossorigin="anonymous" referrerpolicy="no-referrer"/>

    <main class="page-wrap" id="main-content" style="padding-top:var(--nav-h);">

      <!-- ── Hero ───────────────────────────────────────────────────────────── -->
      <section class="archive-index-hero">
        <div class="container">
          <div class="archive-index-hero__eyebrow">
            <i class="fa-solid fa-layer-group"></i>
            Design Movement Archive
          </div>
          <h1 class="archive-index-hero__title">
            Ten Movements.<br>
            <span style="color:var(--accent);">One Archive.</span>
          </h1>
          <p class="archive-index-hero__sub">
            Explore the visual language of the world's most influential design movements —
            each brought to life through curated mood boards.
          </p>
        </div>
      </section>

      <!-- ── Movement grid ──────────────────────────────────────────────────── -->
      <div class="container" style="padding-top:64px;padding-bottom:96px;">
        <div class="movement-index-grid">
          <?php foreach ($movements as $slug => $mv):
            $count = $tagCounts[$slug] ?? 0;
          ?>
          <a href="/archive/<?= e($slug) ?>" class="movement-index-card" style="--mv-color:<?= e($mv['color']) ?>;" data-animate>
            <div class="movement-index-card__bar"></div>
            <div class="movement-index-card__body">
              <div class="movement-index-card__header">
                <i class="<?= e($mv['icon']) ?> movement-index-card__icon"></i>
                <span class="movement-index-card__years"><?= e($mv['years']) ?></span>
              </div>
              <h2 class="movement-index-card__title"><?= e($mv['label']) ?></h2>
              <p class="movement-index-card__desc">
                <?= e(mb_strimwidth($mv['description'], 0, 110, '…')) ?>
              </p>
              <div class="movement-index-card__traits">
                <?php foreach (array_slice($mv['characteristics'], 0, 3) as $trait): ?>
                  <span><?= e($trait) ?></span>
                <?php endforeach; ?>
              </div>
              <div class="movement-index-card__footer">
                <span class="movement-index-card__count">
                  <i class="fa-solid fa-layer-group"></i>
                  <?= $count ?> board<?= $count !== 1 ? 's' : '' ?>
                </span>
                <span class="movement-index-card__cta">
                  Explore <i class="fa-solid fa-arrow-right"></i>
                </span>
              </div>
            </div>
          </a>
          <?php endforeach; ?>
        </div>

        <!-- Chronological archive link -->
        <div style="text-align:center;margin-top:64px;padding-top:48px;border-top:1px solid var(--border);">
          <p style="color:var(--muted);font-size:15px;margin-bottom:20px;">
            Looking for the full chronological board archive?
          </p>
          <a href="/archive.php?q=" class="archive-index-link-btn">
            <i class="fa-solid fa-clock-rotate-left"></i>
            Browse all boards chronologically
          </a>
        </div>
      </div>

    </main>

    <script>
    (function () {
      'use strict';
      var cards = document.querySelectorAll('[data-animate]');
      if (!cards.length) return;
      if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add('is-visible');
              io.unobserve(entry.target);
            }
          });
        }, { threshold: 0.06 });
        cards.forEach(function (card, i) {
          card.style.transitionDelay = Math.min(i, 9) * 50 + 'ms';
          io.observe(card);
        });
      } else {
        cards.forEach(function (card) { card.classList.add('is-visible'); });
      }
    }());
    </script>

    <?php require_once __DIR__ . '/includes/footer.php';
    exit;
}

// ── Fallback: chronological archive (legacy / direct ?q= / ?tag= / etc.) ─────
$page   = max(1, (int)($_GET['page']   ?? 1));
$tag    = trim($_GET['tag']   ?? '');
$search = trim($_GET['q']     ?? '');
$sort   = in_array($_GET['sort'] ?? '', ['newest', 'oldest', 'popular'], true)
          ? $_GET['sort']
          : 'newest';

$perPage = 24;

$conditions = ['b.is_draft = 0'];
$cntParams  = [];
if ($tag !== '') {
    $conditions[] = 'FIND_IN_SET(?, b.style_tags)';
    $cntParams[]  = $tag;
}
if ($search !== '') {
    $conditions[] = '(b.title LIKE ? OR u.username LIKE ?)';
    $cntParams[]  = "%$search%";
    $cntParams[]  = "%$search%";
}
$where = implode(' AND ', $conditions);

try {
    $cntStmt = $db->prepare(
        "SELECT COUNT(*) FROM boards b JOIN users u ON u.id = b.user_id WHERE $where"
    );
    $cntStmt->execute($cntParams);
    $totalBoards = (int)$cntStmt->fetchColumn();
} catch (PDOException $e) {
    $totalBoards = 0;
}

$totalPages = max(1, (int)ceil($totalBoards / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$orderClausemap = [
    'oldest'  => 'b.created_at ASC',
    'popular' => 'like_count DESC, b.created_at DESC',
];
$orderClause = $orderClausemap[$sort] ?? 'b.created_at DESC';

$fetchParams   = $cntParams;
$fetchParams[] = $perPage;
$fetchParams[] = $offset;

$boards = [];
try {
    $stmt = $db->prepare(
        "SELECT b.*, u.username, u.avatar,
                (SELECT COUNT(*) FROM board_likes bl WHERE bl.board_id = b.id) AS like_count
         FROM boards b
         JOIN users u ON u.id = b.user_id
         WHERE $where
         ORDER BY $orderClause
         LIMIT ? OFFSET ?"
    );
    $stmt->execute($fetchParams);
    $boards = $stmt->fetchAll();
} catch (PDOException $e) {
    $boards = [];
}

$allTags = [];
try {
    $tagStmt = $db->query(
        "SELECT DISTINCT TRIM(t.tag) AS tag
         FROM boards b
         JOIN (
           SELECT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(b2.style_tags, ',', n.n), ',', -1)) AS tag
           FROM boards b2
           JOIN (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) n
             ON CHAR_LENGTH(b2.style_tags) - CHAR_LENGTH(REPLACE(b2.style_tags,',','')) >= n.n - 1
         ) t ON 1=1
         WHERE b.is_draft = 0 AND t.tag != ''
         ORDER BY tag ASC
         LIMIT 60"
    );
    $allTags = $tagStmt->fetchAll(\PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $allTags = [];
}

$byYear = [];
foreach ($boards as $board) {
    $year = date('Y', strtotime($board['created_at']));
    $byYear[$year][] = $board;
}

$pageTitle = 'Archive — Visual Design Journey';
$activeNav = 'explore';
$metaDesc  = 'A complete chronological archive of every mood board published on Visual Design Journey.';

require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkA==" crossorigin="anonymous" referrerpolicy="no-referrer"/>

<style>
/* ── Archive page (chronological) ────────────────────────────────────────── */
.archive-hero {
  background: linear-gradient(135deg, var(--surface-lo) 0%, var(--accent-soft) 100%);
  border-bottom: 1px solid var(--border);
  padding: 72px 0 56px;
  text-align: center;
}
.archive-hero__eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-family: var(--font-sans);
  font-size: 12px;
  font-weight: 600;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--accent);
  margin-bottom: 16px;
}
.archive-hero__title {
  font-family: var(--font-display);
  font-size: clamp(40px, 6vw, 72px);
  font-weight: 700;
  letter-spacing: -0.03em;
  line-height: 1.05;
  color: var(--text);
  margin-bottom: 14px;
}
.archive-hero__sub {
  font-size: 18px;
  color: var(--muted);
  margin-bottom: 32px;
}
.archive-stat {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--r-pill);
  padding: 10px 22px;
  font-size: 14px;
  font-weight: 600;
  color: var(--text);
  box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.archive-stat i { color: var(--accent); font-size: 15px; }

.archive-filters {
  background: var(--bg);
  border-bottom: 1px solid var(--border);
  padding: 18px 0;
  position: sticky;
  top: var(--nav-h);
  z-index: 50;
}
.archive-filters__inner {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}
.archive-filters__search {
  position: relative;
  flex: 1 1 220px;
  min-width: 160px;
  max-width: 320px;
}
.archive-filters__search i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--muted);
  font-size: 14px;
  pointer-events: none;
}
.archive-filters__search input {
  width: 100%;
  height: 40px;
  border: 1px solid var(--border);
  border-radius: var(--r-pill);
  padding: 0 14px 0 38px;
  font-size: 14px;
  color: var(--text);
  background: var(--surface);
  transition: border-color .2s, box-shadow .2s;
  outline: none;
}
.archive-filters__search input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(127,119,221,.15);
  background: var(--bg);
}
.archive-filters select {
  height: 40px;
  border: 1px solid var(--border);
  border-radius: var(--r-pill);
  padding: 0 36px 0 14px;
  font-size: 14px;
  color: var(--text);
  background: var(--surface) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23454F5E' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 14px center;
  -webkit-appearance: none;
  appearance: none;
  outline: none;
  cursor: pointer;
  transition: border-color .2s;
}
.archive-filters select:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(127,119,221,.15);
}
.archive-filters__submit {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 40px;
  padding: 0 20px;
  border: none;
  border-radius: var(--r-pill);
  background: var(--accent);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity .2s;
}
.archive-filters__submit:hover { opacity: .88; }
.archive-filters__clear {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 40px;
  padding: 0 18px;
  border: 1px solid var(--border);
  border-radius: var(--r-pill);
  background: var(--bg);
  color: var(--muted);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
  transition: border-color .2s, color .2s;
}
.archive-filters__clear:hover { border-color: var(--accent); color: var(--accent); }

.active-filters {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  padding: 10px 0 0;
}
.active-filter-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border-radius: var(--r-pill);
  background: var(--accent-soft);
  color: var(--accent-dim);
  font-size: 13px;
  font-weight: 500;
}
.active-filter-chip a {
  display: flex;
  align-items: center;
  color: var(--accent-dim);
  opacity: .7;
  transition: opacity .15s;
}
.active-filter-chip a:hover { opacity: 1; }

.archive-year { margin-top: 60px; }
.archive-year__header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 28px;
  padding-bottom: 16px;
  border-bottom: 2px solid var(--border);
}
.archive-year__num {
  font-family: var(--font-display);
  font-size: clamp(28px, 4vw, 40px);
  font-weight: 700;
  letter-spacing: -0.03em;
  color: var(--text);
  line-height: 1;
}
.archive-year__count {
  font-size: 13px;
  font-weight: 500;
  color: var(--muted);
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-pill);
  padding: 4px 12px;
}
.archive-year__line { flex: 1; height: 1px; background: var(--border); }

.archive-masonry { columns: 4; column-gap: 20px; }
@media (max-width: 1100px) { .archive-masonry { columns: 3; } }
@media (max-width: 720px)  { .archive-masonry { columns: 2; } }
@media (max-width: 420px)  { .archive-masonry { columns: 1; } }

.archive-masonry .board-card {
  break-inside: avoid;
  margin-bottom: 20px;
  opacity: 0;
  transform: translateY(16px);
  transition: opacity .45s ease, transform .45s ease;
}
.archive-masonry .board-card.is-visible { opacity: 1; transform: translateY(0); }

.board-card {
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  overflow: hidden;
  position: relative;
}
.board-card__img { width: 100%; display: block; object-fit: cover; }
.board-card__img--placeholder {
  width: 100%;
  aspect-ratio: 4/3;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--surface);
  color: var(--border);
}
.board-card__body { padding: 12px 14px 10px; }
.board-card__title {
  font-family: var(--font-display);
  font-size: 15px;
  font-weight: 600;
  line-height: 1.3;
  margin-bottom: 10px;
  color: var(--text);
}
.board-card__title a { color: inherit; }
.board-card__title a:hover { color: var(--accent); }
.board-card__meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}
.board-card__author {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  font-weight: 500;
  color: var(--muted);
  text-decoration: none;
  min-width: 0;
}
.board-card__author:hover { color: var(--accent); }
.board-card__author span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.board-card__likes {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 13px;
  color: var(--muted);
  flex-shrink: 0;
}
.board-card__likes i { font-size: 14px; }
.board-card__date {
  font-size: 11px;
  color: var(--outline);
  padding: 0 14px 10px;
  display: flex;
  align-items: center;
  gap: 5px;
}
.board-card__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  padding: 0 14px 12px;
}
.tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 10px;
  border-radius: var(--r-pill);
  background: var(--surface);
  border: 1px solid var(--border);
  font-size: 11px;
  font-weight: 600;
  color: var(--muted);
  letter-spacing: .04em;
  text-transform: uppercase;
  text-decoration: none;
  transition: background .15s, color .15s, border-color .15s;
}
.tag:hover { background: var(--accent-soft); color: var(--accent); border-color: var(--accent-soft); }

.avatar { display: block; border-radius: 50%; }
.avatar--sm { width: 28px; height: 28px; flex-shrink: 0; }
.avatar--initials {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: var(--surface-hi);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  color: var(--accent);
  flex-shrink: 0;
}

.archive-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 56px 0 64px;
  flex-wrap: wrap;
}
.archive-pagination__btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 44px;
  padding: 0 24px;
  border-radius: var(--r-pill);
  border: 1px solid var(--border);
  background: var(--bg);
  font-size: 14px;
  font-weight: 600;
  color: var(--text);
  cursor: pointer;
  text-decoration: none;
  transition: border-color .2s, color .2s, background .2s;
}
.archive-pagination__btn:hover:not(.is-disabled) {
  border-color: var(--accent);
  color: var(--accent);
  background: var(--accent-soft);
}
.archive-pagination__btn.is-disabled { opacity: .35; cursor: not-allowed; pointer-events: none; }
.archive-pagination__info { font-size: 14px; color: var(--muted); padding: 0 8px; }
.archive-pagination__current {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--accent);
  color: #fff;
  font-size: 14px;
  font-weight: 700;
}

.archive-empty {
  text-align: center;
  padding: 80px 24px;
}
.archive-empty__icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: var(--surface);
  border: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
  font-size: 32px;
  color: var(--outline);
}
.archive-empty__title {
  font-family: var(--font-display);
  font-size: 22px;
  font-weight: 700;
  margin-bottom: 8px;
  color: var(--text);
}
.archive-empty__sub { font-size: 15px; color: var(--muted); margin-bottom: 28px; }

.archive-results-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 0 0;
  gap: 12px;
  flex-wrap: wrap;
}
.archive-results-bar__count { font-size: 14px; color: var(--muted); }
.archive-results-bar__count strong { color: var(--text); font-weight: 600; }
</style>

<main class="page-wrap" id="main-content" style="padding-top:var(--nav-h);">

  <section class="archive-hero">
    <div class="container">
      <div class="archive-hero__eyebrow">
        <i class="fa-solid fa-archive"></i>
        Complete Collection
      </div>
      <h1 class="archive-hero__title">The Archive</h1>
      <p class="archive-hero__sub">Every board, every vision.</p>
      <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
        <div class="archive-stat">
          <i class="fa-solid fa-layer-group"></i>
          <?= number_format($totalBoards) ?> board<?= $totalBoards !== 1 ? 's' : '' ?> published
        </div>
        <a href="/archive" class="archive-stat" style="text-decoration:none;color:var(--accent);border-color:var(--accent);">
          <i class="fa-solid fa-palette"></i>
          Browse by Design Movement
        </a>
      </div>
    </div>
  </section>

  <div class="archive-filters">
    <div class="container">
      <form method="GET" action="/archive.php" role="search" aria-label="Filter boards">
        <div class="archive-filters__inner">
          <div class="archive-filters__search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input
              type="text"
              name="q"
              id="archive-search"
              value="<?= e($search) ?>"
              placeholder="Search boards or creators…"
              aria-label="Search boards"
              autocomplete="off"
            />
          </div>
          <select name="tag" aria-label="Filter by style tag">
            <option value="" <?= $tag === '' ? 'selected' : '' ?>>All tags</option>
            <?php foreach ($allTags as $t): ?>
              <option value="<?= e($t) ?>" <?= $tag === $t ? 'selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
          </select>
          <select name="sort" aria-label="Sort order">
            <option value="newest"  <?= $sort === 'newest'  ? 'selected' : '' ?>>Newest first</option>
            <option value="oldest"  <?= $sort === 'oldest'  ? 'selected' : '' ?>>Oldest first</option>
            <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most liked</option>
          </select>
          <button type="submit" class="archive-filters__submit">
            <i class="fa-solid fa-filter"></i>
            Filter
          </button>
          <?php if ($search !== '' || $tag !== '' || $sort !== 'newest'): ?>
            <a href="/archive.php" class="archive-filters__clear">
              <i class="fa-solid fa-xmark"></i>
              Clear
            </a>
          <?php endif; ?>
        </div>
        <?php if ($search !== '' || $tag !== ''): ?>
        <div class="active-filters" aria-label="Active filters">
          <?php if ($search !== ''): ?>
            <span class="active-filter-chip">
              <i class="fa-solid fa-magnifying-glass" style="font-size:11px;"></i>
              <?= e($search) ?>
              <a href="/archive.php?<?= http_build_query(array_filter(['tag' => $tag, 'sort' => $sort !== 'newest' ? $sort : null])) ?>" aria-label="Remove search filter">
                <i class="fa-solid fa-xmark" style="font-size:11px;"></i>
              </a>
            </span>
          <?php endif; ?>
          <?php if ($tag !== ''): ?>
            <span class="active-filter-chip">
              <i class="fa-solid fa-tag" style="font-size:11px;"></i>
              <?= e($tag) ?>
              <a href="/archive.php?<?= http_build_query(array_filter(['q' => $search, 'sort' => $sort !== 'newest' ? $sort : null])) ?>" aria-label="Remove tag filter">
                <i class="fa-solid fa-xmark" style="font-size:11px;"></i>
              </a>
            </span>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <div class="container">

    <?php if (empty($boards)): ?>
    <div class="archive-empty">
      <div class="archive-empty__icon">
        <i class="fa-regular fa-folder-open"></i>
      </div>
      <p class="archive-empty__title">No boards found</p>
      <p class="archive-empty__sub">
        <?php if ($search !== '' || $tag !== ''): ?>
          Try different search terms or clear the filters.
        <?php else: ?>
          Be the first to publish a board on Visual Design Journey.
        <?php endif; ?>
      </p>
      <?php if ($search !== '' || $tag !== ''): ?>
        <a href="/archive.php" class="archive-pagination__btn">
          <i class="fa-solid fa-rotate-left"></i> Clear filters
        </a>
      <?php else: ?>
        <a href="/create.php" class="archive-pagination__btn" style="border-color:var(--accent);color:var(--accent);">
          <i class="fa-solid fa-plus"></i> Create a board
        </a>
      <?php endif; ?>
    </div>

    <?php else: ?>

    <div class="archive-results-bar">
      <p class="archive-results-bar__count">
        Showing <strong><?= number_format(($page - 1) * $perPage + 1) ?>–<?= number_format(min($page * $perPage, $totalBoards)) ?></strong>
        of <strong><?= number_format($totalBoards) ?></strong> board<?= $totalBoards !== 1 ? 's' : '' ?>
        <?php if ($search !== ''): ?> matching "<strong><?= e($search) ?></strong>"<?php endif; ?>
        <?php if ($tag !== ''): ?> tagged <strong><?= e($tag) ?></strong><?php endif; ?>
      </p>
      <p class="archive-results-bar__count">Page <?= $page ?> of <?= $totalPages ?></p>
    </div>

    <?php foreach ($byYear as $year => $yearBoards): ?>
    <section class="archive-year" aria-labelledby="year-<?= (int)$year ?>">
      <div class="archive-year__header">
        <h2 class="archive-year__num" id="year-<?= (int)$year ?>">
          <i class="fa-regular fa-calendar" style="font-size:0.7em;color:var(--accent);margin-right:4px;"></i>
          <?= (int)$year ?>
        </h2>
        <span class="archive-year__count">
          <?= count($yearBoards) ?> board<?= count($yearBoards) !== 1 ? 's' : '' ?>
        </span>
        <span class="archive-year__line" aria-hidden="true"></span>
      </div>
      <div class="archive-masonry" role="list" aria-label="Boards from <?= (int)$year ?>">
        <?php foreach ($yearBoards as $board):
          $boardTags = parseTags($board['style_tags'] ?? '');
          $coverSrc  = !empty($board['cover_image']) ? imageUrl($board['cover_image']) : null;
        ?>
        <article class="board-card" role="listitem" aria-label="<?= e($board['title']) ?>" data-animate>
          <a href="/board.php?id=<?= (int)$board['id'] ?>" tabindex="-1" aria-hidden="true">
            <?php if ($coverSrc): ?>
              <img src="<?= e($coverSrc) ?>" alt="Cover image for <?= e($board['title']) ?>" class="board-card__img" loading="lazy" decoding="async"/>
            <?php else: ?>
              <div class="board-card__img--placeholder">
                <i class="fa-regular fa-image" style="font-size:36px;color:var(--border);"></i>
              </div>
            <?php endif; ?>
          </a>
          <div class="board-card__body">
            <h3 class="board-card__title">
              <a href="/board.php?id=<?= (int)$board['id'] ?>"><?= e($board['title']) ?></a>
            </h3>
            <div class="board-card__meta">
              <a href="/profile.php?u=<?= urlencode($board['username']) ?>" class="board-card__author">
                <?php if (!empty($board['avatar'])): ?>
                  <img src="<?= e(imageUrl($board['avatar'])) ?>" alt="<?= e($board['username']) ?>" class="avatar avatar--sm" loading="lazy"/>
                <?php else: ?>
                  <span class="avatar--initials" aria-hidden="true"><?= strtoupper(substr($board['username'], 0, 1)) ?></span>
                <?php endif; ?>
                <span><?= e($board['username']) ?></span>
              </a>
              <span class="board-card__likes" aria-label="<?= (int)$board['like_count'] ?> likes">
                <i class="fa-<?= (int)$board['like_count'] > 0 ? 'solid' : 'regular' ?> fa-heart" style="color:<?= (int)$board['like_count'] > 0 ? 'var(--accent)' : 'var(--muted)' ?>;"></i>
                <?= formatCount((int)$board['like_count']) ?>
              </span>
            </div>
          </div>
          <div class="board-card__date">
            <i class="fa-regular fa-clock" style="font-size:11px;"></i>
            <time datetime="<?= e($board['created_at']) ?>"><?= date('M j, Y', strtotime($board['created_at'])) ?></time>
          </div>
          <?php if (!empty($boardTags)): ?>
          <div class="board-card__tags">
            <?php foreach (array_slice($boardTags, 0, 3) as $t): ?>
              <a href="/archive.php?tag=<?= urlencode($t) ?><?= $sort !== 'newest' ? '&sort=' . urlencode($sort) : '' ?>" class="tag"><?= e($t) ?></a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </article>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endforeach; ?>

    <?php
    $qParts = array_filter(['q' => $search, 'tag' => $tag, 'sort' => $sort !== 'newest' ? $sort : '']);
    $baseQs = $qParts ? '&' . http_build_query($qParts) : '';
    ?>
    <nav class="archive-pagination" aria-label="Pagination">
      <a href="<?= $page > 1 ? '/archive.php?page=' . ($page - 1) . $baseQs : '#' ?>"
         class="archive-pagination__btn <?= $page <= 1 ? 'is-disabled' : '' ?>"
         <?= $page <= 1 ? 'aria-disabled="true"' : '' ?> rel="prev">
        <i class="fa-solid fa-arrow-left"></i> Previous
      </a>
      <span class="archive-pagination__info" aria-current="page">
        <span class="archive-pagination__current"><?= $page ?></span>
      </span>
      <span class="archive-pagination__info" aria-label="of <?= $totalPages ?> pages">of <?= $totalPages ?></span>
      <a href="<?= $page < $totalPages ? '/archive.php?page=' . ($page + 1) . $baseQs : '#' ?>"
         class="archive-pagination__btn <?= $page >= $totalPages ? 'is-disabled' : '' ?>"
         <?= $page >= $totalPages ? 'aria-disabled="true"' : '' ?> rel="next">
        Next <i class="fa-solid fa-arrow-right"></i>
      </a>
    </nav>

    <?php endif; ?>

  </div>

</main>

<script>
(function () {
  'use strict';
  var cards = document.querySelectorAll('[data-animate]');
  if (!cards.length) return;
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
    cards.forEach(function (card, i) {
      card.style.transitionDelay = Math.min(i % 12, 11) * 35 + 'ms';
      io.observe(card);
    });
  } else {
    cards.forEach(function (card) { card.classList.add('is-visible'); });
  }
}());
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
