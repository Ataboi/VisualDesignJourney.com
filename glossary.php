<?php
/**
 * glossary.php — Design Glossary
 * Static A-Z reference of essential visual design terms.
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle    = __('glossary.title', 'Design Glossary') . ' — Visual Design Journey';
$activeNav    = '';
$metaDesc     = __('glossary.description', 'A comprehensive glossary of visual design terms, movements, and concepts for designers.');
$canonicalUrl = publicUrl('/glossary');
require_once __DIR__ . '/includes/header.php';

$terms = [
    // A
    ['term' => 'Art Direction',     'letter' => 'A', 'definition' => 'The visual management of creative projects — deciding how imagery, typography, and layout combine to communicate a unified message across media.'],
    ['term' => 'Asymmetry',         'letter' => 'A', 'definition' => 'A compositional approach where visual elements are deliberately unequal in weight or position, creating dynamic tension and visual interest.'],
    ['term' => 'Art Deco',          'letter' => 'A', 'definition' => 'A design style from the 1920s–30s characterized by geometric forms, bold colors, and lavish ornamentation, reflecting modernism and luxury.'],
    ['term' => 'Avant-Garde',       'letter' => 'A', 'definition' => 'Experimental, innovative work that pushes the boundaries of conventional design and challenges established norms.'],
    // B
    ['term' => 'Bauhaus',           'letter' => 'B', 'definition' => 'German art school (1919–1933) that combined fine arts with functional design, emphasizing the unity of art and craft. Its influence defines modern design education.'],
    ['term' => 'Bleed',             'letter' => 'B', 'definition' => 'The area of a printed design that extends beyond the trim line, ensuring color or images reach the edge of the page without white gaps after cutting.'],
    ['term' => 'Brand Identity',    'letter' => 'B', 'definition' => 'The collection of visual elements — logo, colors, typography, imagery — that together represent and distinguish a brand in the marketplace.'],
    ['term' => 'Brutalism',         'letter' => 'B', 'definition' => 'A design aesthetic inspired by raw concrete architecture, characterized by exposed structure, stark typography, and minimal decoration. In web design, it favors unconventional layouts.'],
    // C
    ['term' => 'CMYK',              'letter' => 'C', 'definition' => 'Cyan, Magenta, Yellow, Key (Black) — the subtractive color model used in color printing. Colors are produced by combining these four ink values.'],
    ['term' => 'Color Theory',      'letter' => 'C', 'definition' => 'The body of knowledge governing how colors mix, contrast, and communicate. Includes concepts like complementary, analogous, and triadic color relationships.'],
    ['term' => 'Composition',       'letter' => 'C', 'definition' => 'The deliberate arrangement of visual elements within a design or image to guide the viewer\'s eye and convey a specific message or mood.'],
    ['term' => 'Contrast',          'letter' => 'C', 'definition' => 'The degree of visual difference between elements — light vs. dark, large vs. small, bold vs. thin — used to create emphasis and legibility.'],
    ['term' => 'Constructivism',    'letter' => 'C', 'definition' => 'A Soviet art movement (1915–1934) that used geometric abstraction, bold typography, and stark contrasts to serve political and social goals.'],
    // D
    ['term' => 'De Stijl',          'letter' => 'D', 'definition' => 'Dutch art movement (1917–1931) characterized by strict geometric abstraction using only horizontal/vertical lines and primary colors plus black and white.'],
    ['term' => 'Design System',     'letter' => 'D', 'definition' => 'A collection of reusable components, guidelines, and standards that enable teams to build consistent products at scale.'],
    ['term' => 'Drop Shadow',       'letter' => 'D', 'definition' => 'A visual effect that adds a simulated shadow behind an element to create depth and lift it off the background.'],
    // E
    ['term' => 'Editorial Design',  'letter' => 'E', 'definition' => 'The design of publications — magazines, newspapers, books — focusing on layout, hierarchy, and the relationship between text and image.'],
    ['term' => 'Emboss',            'letter' => 'E', 'definition' => 'A print finishing technique that raises a design element above the surface of the substrate, creating a three-dimensional tactile effect.'],
    // F
    ['term' => 'Folio',             'letter' => 'F', 'definition' => 'A page number in a publication, often accompanied by a header or footer indicating the chapter or section name.'],
    ['term' => 'Form & Function',   'letter' => 'F', 'definition' => 'The principle, famously expressed by Louis Sullivan, that the shape of an object or design should be determined primarily by its intended function.'],
    // G
    ['term' => 'Gestalt',           'letter' => 'G', 'definition' => 'A set of psychological principles describing how humans perceive visual elements as organized wholes. Key laws include proximity, similarity, closure, and continuity.'],
    ['term' => 'Golden Ratio',      'letter' => 'G', 'definition' => 'A mathematical ratio (approximately 1.618:1) found in nature and used in design and architecture to create aesthetically pleasing proportions.'],
    ['term' => 'Grid System',       'letter' => 'G', 'definition' => 'A framework of intersecting horizontal and vertical lines that provides a structural basis for organizing visual content consistently across a layout.'],
    ['term' => 'Glyph',             'letter' => 'G', 'definition' => 'Any individual typographic character, symbol, or element within a typeface, including letters, numbers, punctuation, and special characters.'],
    // H
    ['term' => 'Helvetica',         'letter' => 'H', 'definition' => 'A Swiss sans-serif typeface designed in 1957 by Max Miedinger. One of the most widely used typefaces in the world, synonymous with modernist design.'],
    ['term' => 'Hierarchy',         'letter' => 'H', 'definition' => 'The visual ordering of design elements by importance, guiding the viewer\'s eye from the most critical information to the least through size, weight, color, and position.'],
    ['term' => 'Hue',               'letter' => 'H', 'definition' => 'The pure attribute of a color, distinguishing it from others — the quality described by color names such as red, blue, or yellow.'],
    // I
    ['term' => 'Identity Design',   'letter' => 'I', 'definition' => 'The creation of visual systems — logos, color palettes, type hierarchies — that define how a brand presents itself across all touchpoints.'],
    ['term' => 'Iteration',         'letter' => 'I', 'definition' => 'The cyclical process of refining a design through repeated testing, feedback, and improvement until it meets its objectives.'],
    // K
    ['term' => 'Kerning',           'letter' => 'K', 'definition' => 'The adjustment of space between specific pairs of characters in typesetting to achieve visually even spacing and improve readability.'],
    // L
    ['term' => 'Leading',           'letter' => 'L', 'definition' => 'The vertical space between lines of type, measured from baseline to baseline. Tight leading creates a dense texture; loose leading improves readability in body text.'],
    ['term' => 'Ligature',          'letter' => 'L', 'definition' => 'A typographic character formed by joining two or more letters into a single glyph (e.g., fi, fl) to improve aesthetics and avoid awkward letter collisions.'],
    ['term' => 'Logotype',          'letter' => 'L', 'definition' => 'A logo that consists entirely of styled text — the company\'s name rendered in a distinctive typeface or custom lettering — without an accompanying symbol.'],
    // M
    ['term' => 'Modernism',         'letter' => 'M', 'definition' => 'A 20th-century design philosophy rejecting ornament in favor of functionalism, clean geometry, and the honest expression of materials and structure.'],
    ['term' => 'Moodboard',         'letter' => 'M', 'definition' => 'A collection of images, colors, textures, and typography assembled to communicate the visual direction, tone, and feeling of a design project.'],
    ['term' => 'Monochromatic',     'letter' => 'M', 'definition' => 'A color scheme built from a single hue at varying lightness and saturation values, creating a cohesive, harmonious visual effect.'],
    // N
    ['term' => 'Negative Space',    'letter' => 'N', 'definition' => 'The empty or unmarked areas in a composition. Effective use of negative space gives designs clarity, balance, and can itself convey meaning.'],
    // O
    ['term' => 'Orphan',            'letter' => 'O', 'definition' => 'A single word or short line that appears isolated at the top of a column or page, separated from the paragraph it belongs to — considered typographically undesirable.'],
    // P
    ['term' => 'Pantone',           'letter' => 'P', 'definition' => 'A proprietary color-matching system (PMS) that assigns unique numbers to standardized colors, enabling consistent color reproduction across printers and materials worldwide.'],
    ['term' => 'Postmodernism',     'letter' => 'P', 'definition' => 'A design movement (1970s–90s) that reacted against modernist purity with eclecticism, irony, historical reference, and playful complexity.'],
    ['term' => 'Proportion',        'letter' => 'P', 'definition' => 'The relationship between the sizes of different elements in a composition, which determines overall balance, harmony, and visual weight.'],
    // R
    ['term' => 'RGB',               'letter' => 'R', 'definition' => 'Red, Green, Blue — the additive color model used for screens and digital displays. Colors are created by combining these three channels of light.'],
    ['term' => 'Rule of Thirds',    'letter' => 'R', 'definition' => 'A compositional guideline that divides an image into a 3×3 grid; placing key elements along the grid lines or at their intersections creates natural visual balance.'],
    ['term' => 'Raster Image',      'letter' => 'R', 'definition' => 'A digital image made of pixels, such as JPEG or PNG. Resolution-dependent — raster images lose quality when scaled up significantly.'],
    // S
    ['term' => 'Sans-Serif',        'letter' => 'S', 'definition' => 'A typeface classification without decorative strokes (serifs) at the ends of letterforms. Associated with modernity, clarity, and neutral tone.'],
    ['term' => 'Saturation',        'letter' => 'S', 'definition' => 'The intensity or purity of a color. Highly saturated colors appear vivid; desaturated colors appear muted or grayish.'],
    ['term' => 'Serif',             'letter' => 'S', 'definition' => 'A typeface classification featuring small decorative strokes (serifs) at the ends of letterforms. Traditionally associated with formality, authority, and print.'],
    ['term' => 'Swiss Style',       'letter' => 'S', 'definition' => 'Also known as the International Typographic Style; a design movement originating in Switzerland in the 1950s emphasizing grid systems, sans-serif type, and objective photography.'],
    ['term' => 'Style Tile',        'letter' => 'S', 'definition' => 'A design deliverable that communicates the visual language of a brand or interface — including colors, fonts, and textures — without defining full layouts.'],
    // T
    ['term' => 'Tracking',          'letter' => 'T', 'definition' => 'The uniform adjustment of spacing between all characters in a block of text, as opposed to kerning which adjusts pairs. Also called letter-spacing.'],
    ['term' => 'Typography',        'letter' => 'T', 'definition' => 'The art and technique of arranging type to make written language legible, readable, and visually appealing. Encompasses font choice, size, spacing, and hierarchy.'],
    ['term' => 'Tint',              'letter' => 'T', 'definition' => 'A color mixed with white to reduce its darkness. In print, a tint is often expressed as a percentage of a solid color.'],
    // U
    ['term' => 'Unity',             'letter' => 'U', 'definition' => 'A design principle that ensures all visual elements work together coherently to support a single idea, achieved through repetition, alignment, and consistent use of style.'],
    // V
    ['term' => 'Vector Image',      'letter' => 'V', 'definition' => 'A digital image made of mathematical paths rather than pixels. Vectors scale infinitely without quality loss — essential for logos and icons.'],
    ['term' => 'Vernacular Design', 'letter' => 'V', 'definition' => 'Graphic forms that emerge organically from everyday culture — signage, packaging, handmade lettering — rather than trained design practice.'],
    ['term' => 'Visual Weight',     'letter' => 'V', 'definition' => 'The perceived heaviness or dominance of an element in a composition, influenced by size, color, texture, and isolation from other elements.'],
    // W
    ['term' => 'Whitespace',        'letter' => 'W', 'definition' => 'The empty, unmarked areas of a design — not necessarily white — that give elements room to breathe, improve legibility, and communicate sophistication.'],
    ['term' => 'Wordmark',          'letter' => 'W', 'definition' => 'A logo consisting solely of the company or brand name in a distinctive typographic treatment, without an icon or symbol.'],
    // X
    ['term' => 'X-height',          'letter' => 'X', 'definition' => 'The height of lowercase letters (specifically \'x\') in a typeface, relative to capital letters. A tall x-height generally improves legibility at small sizes.'],
    // Z
    ['term' => 'Zeitgeist',         'letter' => 'Z', 'definition' => 'The defining spirit or mood of a particular period in history, which design often reflects and sometimes shapes. Literally "spirit of the time" in German.'],
    ['term' => 'Z-pattern',         'letter' => 'Z', 'definition' => 'A scanning pattern in which the eye follows a Z-shape across a page — top-left to top-right, diagonal down, then bottom-left to bottom-right — common for layouts with sparse content.'],
];

// Group by letter
$byLetter = [];
foreach ($terms as $t) {
    $byLetter[$t['letter']][] = $t;
}
ksort($byLetter);

$letters = array_keys($byLetter);
?>
<main class="page-wrap" id="main-content">
<div class="container">

  <!-- Hero -->
  <div style="text-align:center;padding:2.5rem 0 2rem;">
    <h1 class="t-headline-md" style="margin:0 0 .5rem;"><?= e(__('glossary.hero', 'Design Glossary')) ?></h1>
    <p class="t-body-md text-muted"><?= e(sprintf(__('glossary.sub', '%s+ essential design terms, movements, and concepts.'), count($terms))) ?></p>
  </div>

  <!-- A-Z filter nav -->
  <nav aria-label="Alphabet filter" style="display:flex;flex-wrap:wrap;gap:6px;justify-content:center;margin-bottom:2.5rem;">
    <?php foreach ($letters as $letter): ?>
      <a href="#letter-<?= e($letter) ?>"
         style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:var(--r-pill);border:1px solid var(--border);font-weight:700;font-size:13px;text-decoration:none;color:var(--text);transition:background .15s,color .15s,border-color .15s;"
         class="glossary-letter-btn"
         data-letter="<?= e($letter) ?>">
        <?= e($letter) ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <!-- Terms by letter -->
  <div style="max-width:760px;margin:0 auto;">
    <?php foreach ($byLetter as $letter => $group): ?>
    <section id="letter-<?= e($letter) ?>" style="margin-bottom:2.5rem;" aria-labelledby="heading-<?= e($letter) ?>">
      <h2 id="heading-<?= e($letter) ?>"
          style="font-size:1.5rem;font-weight:800;color:var(--accent);border-bottom:2px solid var(--border);padding-bottom:.4rem;margin-bottom:1.25rem;">
        <?= e($letter) ?>
      </h2>
      <dl style="display:flex;flex-direction:column;gap:1.1rem;">
        <?php foreach ($group as $entry): ?>
        <div>
          <dt style="font-weight:700;font-size:1rem;margin-bottom:.2rem;"><?= e($entry['term']) ?></dt>
          <dd style="color:var(--muted);font-size:.93rem;line-height:1.65;margin:0;"><?= e($entry['definition']) ?></dd>
        </div>
        <?php endforeach; ?>
      </dl>
    </section>
    <?php endforeach; ?>
  </div>

</div>
</main>

<!-- DefinedTermSet schema -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "DefinedTermSet",
  "name": <?= json_encode(__('glossary.title', 'Design Glossary')) ?>,
  "description": <?= json_encode($metaDesc) ?>,
  "url": <?= json_encode($canonicalUrl) ?>,
  "hasDefinedTerm": [
    <?php
    $schemaTerms = array_map(fn($t) => json_encode([
        '@type'      => 'DefinedTerm',
        'name'       => $t['term'],
        'description'=> $t['definition'],
        'inDefinedTermSet' => $canonicalUrl,
    ]), $terms);
    echo implode(",\n    ", $schemaTerms);
    ?>
  ]
}
</script>

<style>
.glossary-letter-btn:hover,
.glossary-letter-btn.active {
  background: var(--accent);
  color: #fff;
  border-color: var(--accent);
}
</style>

<script>
(function () {
  const btns = document.querySelectorAll('.glossary-letter-btn');
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const letter = entry.target.id.replace('letter-', '');
        btns.forEach(b => b.classList.toggle('active', b.dataset.letter === letter));
      }
    });
  }, { rootMargin: '-30% 0px -65% 0px' });

  document.querySelectorAll('[id^="letter-"]').forEach(sec => observer.observe(sec));

  btns.forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const target = document.getElementById('letter-' + btn.dataset.letter);
      if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
