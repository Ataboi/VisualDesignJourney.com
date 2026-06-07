<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

session_start_if_not_started();

$pageTitle    = 'Submit a Reference — Visual Design Journey';
$activeNav    = 'explore';
$metaDesc     = 'Contribute to the archive. Submit visual references across ten defining design movements — curated mood boards from Bauhaus to Y2K.';
$canonicalUrl = publicUrl('/submit-source');

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ── Submit page: Ten Movements. One Archive. ── */

/* Hero */
.submit-hero {
  background: linear-gradient(135deg, #0f0f1a 0%, #1a0f2e 100%);
  color: #fff;
  padding: clamp(64px, 10vw, 120px) 24px clamp(56px, 8vw, 96px);
  text-align: center;
}
.submit-hero__eyebrow {
  display: inline-block;
  font-size: .75rem;
  font-weight: 600;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: rgba(255,255,255,.45);
  margin: 0 0 20px;
}
.submit-hero__title {
  font-size: clamp(2.4rem, 6vw, 4.2rem);
  font-weight: 800;
  line-height: 1.06;
  letter-spacing: -.04em;
  margin: 0 0 20px;
}
.submit-hero__sub {
  max-width: 560px;
  margin: 0 auto 36px;
  font-size: clamp(1rem, 1.5vw, 1.15rem);
  line-height: 1.7;
  color: rgba(255,255,255,.65);
}
.submit-hero__cta {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 30px;
  border: 1.5px solid rgba(255,255,255,.3);
  border-radius: 8px;
  color: #fff;
  font-weight: 600;
  font-size: .95rem;
  text-decoration: none;
  transition: background .2s, border-color .2s;
}
.submit-hero__cta:hover {
  background: rgba(255,255,255,.1);
  border-color: rgba(255,255,255,.6);
}

/* Movement grid */
.movements-section {
  padding: clamp(48px, 7vw, 80px) 24px;
}
.movements-section__heading {
  text-align: center;
  font-size: clamp(1.4rem, 3vw, 2rem);
  font-weight: 700;
  letter-spacing: -.03em;
  margin: 0 0 8px;
}
.movements-section__sub {
  text-align: center;
  color: var(--muted, #6b7280);
  font-size: .95rem;
  margin: 0 0 40px;
}
.movements-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 18px;
  max-width: 1060px;
  margin: 0 auto;
}
.movement-card {
  position: relative;
  padding: 22px 20px 20px;
  border-radius: 12px;
  border: 1.5px solid var(--border, #e5e7eb);
  background: var(--surface, #fff);
  cursor: pointer;
  transition: transform .18s, box-shadow .18s, border-color .18s;
  overflow: hidden;
  user-select: none;
}
.movement-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 4px;
  background: var(--accent-clr);
  border-radius: 12px 12px 0 0;
}
.movement-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(0,0,0,.1);
  border-color: var(--accent-clr);
}
.movement-card.is-selected {
  border-color: var(--accent-clr);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent-clr) 18%, transparent);
}
.movement-card__name {
  font-size: 1.05rem;
  font-weight: 700;
  letter-spacing: -.02em;
  margin: 0 0 2px;
  color: var(--fg, #111);
}
.movement-card__years {
  font-size: .75rem;
  font-weight: 500;
  color: var(--accent-clr);
  margin: 0 0 10px;
}
.movement-card__desc {
  font-size: .85rem;
  line-height: 1.55;
  color: var(--muted, #6b7280);
  margin: 0 0 14px;
}
.movement-card__pill {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: .72rem;
  font-weight: 600;
  letter-spacing: .06em;
  text-transform: uppercase;
  background: color-mix(in srgb, var(--accent-clr) 12%, transparent);
  color: var(--accent-clr);
}

/* Form section */
.submit-form-section {
  background: var(--surface-alt, #f9fafb);
  padding: clamp(48px, 7vw, 80px) 24px;
  border-top: 1px solid var(--border, #e5e7eb);
  border-bottom: 1px solid var(--border, #e5e7eb);
}
.submit-form-wrap {
  max-width: 620px;
  margin: 0 auto;
}
.submit-form-wrap__heading {
  font-size: clamp(1.4rem, 3vw, 1.9rem);
  font-weight: 700;
  letter-spacing: -.03em;
  margin: 0 0 6px;
}
.submit-form-wrap__sub {
  color: var(--muted, #6b7280);
  font-size: .92rem;
  margin: 0 0 32px;
}
.form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 18px;
}
.form-grid label {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: .87rem;
  font-weight: 600;
  color: var(--fg, #111);
}
.form-grid label span.form-hint {
  font-weight: 400;
  color: var(--muted, #6b7280);
  font-size: .8rem;
}
.form-input,
.form-select,
.form-textarea {
  width: 100%;
  padding: 11px 14px;
  border: 1.5px solid var(--border, #d1d5db);
  border-radius: 8px;
  font-size: .95rem;
  background: var(--bg, #fff);
  color: var(--fg, #111);
  transition: border-color .15s, box-shadow .15s;
  box-sizing: border-box;
}
.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--accent, #6366f1);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent, #6366f1) 15%, transparent);
}
.form-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  padding-right: 36px;
}
.form-textarea {
  resize: vertical;
  min-height: 100px;
}
.submit-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 28px;
  background: var(--accent, #6366f1);
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: .95rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity .15s, transform .15s;
  margin-top: 4px;
}
.submit-btn:hover { opacity: .88; transform: translateY(-1px); }
.submit-btn:active { transform: none; }

#source-submit-msg {
  display: none;
  margin-top: 12px;
  font-size: .9rem;
  padding: 12px 16px;
  border-radius: 8px;
  background: color-mix(in srgb, var(--accent, #6366f1) 10%, transparent);
  color: var(--accent, #6366f1);
}

/* Guidelines section */
.guidelines-section {
  padding: clamp(48px, 7vw, 72px) 24px;
}
.guidelines-wrap {
  max-width: 620px;
  margin: 0 auto;
}
.guidelines-wrap h2 {
  font-size: 1.25rem;
  font-weight: 700;
  letter-spacing: -.02em;
  margin: 0 0 16px;
}
.guidelines-wrap ul {
  list-style: none;
  padding: 0;
  margin: 0 0 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.guidelines-wrap li {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: .9rem;
  line-height: 1.6;
  color: var(--muted, #6b7280);
}
.guidelines-wrap li::before {
  content: '—';
  color: var(--accent, #6366f1);
  font-weight: 700;
  flex-shrink: 0;
  margin-top: 1px;
}
.guidelines-note {
  font-size: .82rem;
  font-style: italic;
  color: var(--muted, #9ca3af);
  border-top: 1px solid var(--border, #e5e7eb);
  padding-top: 16px;
  margin-top: 4px;
}
</style>

<main id="main-content">

  <!-- ── 1. HERO ── -->
  <section class="submit-hero">
    <p class="submit-hero__eyebrow">Visual Design Journey</p>
    <h1 class="submit-hero__title">Ten Movements.<br>One Archive.</h1>
    <p class="submit-hero__sub">
      Explore the visual language of the world's most influential design movements
      — each brought to life through curated mood boards.
    </p>
    <a href="#submit-form" class="submit-hero__cta">
      Submit a Reference <span aria-hidden="true">→</span>
    </a>
  </section>

  <!-- ── 2. MOVEMENT GRID ── -->
  <section class="movements-section">
    <h2 class="movements-section__heading">The ten movements</h2>
    <p class="movements-section__sub">Click a movement to pre-select it in the form below.</p>
    <div class="movements-grid" id="movements-grid">

      <div class="movement-card"
           style="--accent-clr:#d4002a"
           data-movement="Bauhaus"
           data-tags="bauhaus, geometric, functional, modernist, typography">
        <p class="movement-card__name">Bauhaus</p>
        <p class="movement-card__years">1919–1933</p>
        <p class="movement-card__desc">Geometric, functional design philosophy that united craft and fine art under a modernist ideal.</p>
        <span class="movement-card__pill">Modernist</span>
      </div>

      <div class="movement-card"
           style="--accent-clr:#6b7280"
           data-movement="Minimalism"
           data-tags="minimalism, whitespace, clean, essential, reduction">
        <p class="movement-card__name">Minimalism</p>
        <p class="movement-card__years">1960s – now</p>
        <p class="movement-card__desc">Less is more. Pure form through elimination of the non-essential — whitespace as a design element.</p>
        <span class="movement-card__pill">Essential</span>
      </div>

      <div class="movement-card"
           style="--accent-clr:#16a34a"
           data-movement="Art Nouveau"
           data-tags="art nouveau, organic, floral, ornamental, decorative">
        <p class="movement-card__name">Art Nouveau</p>
        <p class="movement-card__years">1890–1910</p>
        <p class="movement-card__desc">Nature-inspired curves and organic forms translated into elegant, ornamental visual language.</p>
        <span class="movement-card__pill">Organic</span>
      </div>

      <div class="movement-card"
           style="--accent-clr:#2563eb"
           data-movement="Swiss / International"
           data-tags="swiss, international style, grid, typography, rational, helvetica">
        <p class="movement-card__name">Swiss / International</p>
        <p class="movement-card__years">1950s – now</p>
        <p class="movement-card__desc">Rigorous grid systems and rational typography — objectivity elevated to an art form.</p>
        <span class="movement-card__pill">Grid</span>
      </div>

      <div class="movement-card"
           style="--accent-clr:#9333ea"
           data-movement="Y2K Digital"
           data-tags="y2k, cyber, chrome, holographic, digital, futurism">
        <p class="movement-card__name">Y2K Digital</p>
        <p class="movement-card__years">1995–2005</p>
        <p class="movement-card__desc">Chrome surfaces, holographic gradients, and cyber aesthetics from the millennium's digital dawn.</p>
        <span class="movement-card__pill">Cyber</span>
      </div>

      <div class="movement-card"
           style="--accent-clr:#374151"
           data-movement="Brutalism"
           data-tags="brutalism, raw, concrete, exposed structure, anti-design">
        <p class="movement-card__name">Brutalism</p>
        <p class="movement-card__years">1950s – now</p>
        <p class="movement-card__desc">Raw, unpolished, deliberately anti-pretty. Exposed structure and honest materials as aesthetic.</p>
        <span class="movement-card__pill">Raw</span>
      </div>

      <div class="movement-card"
           style="--accent-clr:#78350f"
           data-movement="Dark Academia"
           data-tags="dark academia, gothic, moody, classical, library, literary">
        <p class="movement-card__name">Dark Academia</p>
        <p class="movement-card__years">2020s</p>
        <p class="movement-card__desc">Gothic libraries, candlelit reading rooms, and a reverence for classical learning rendered moodily.</p>
        <span class="movement-card__pill">Gothic</span>
      </div>

      <div class="movement-card"
           style="--accent-clr:#0e7490"
           data-movement="Japandi"
           data-tags="japandi, wabi-sabi, zen, nordic, japanese, minimalism">
        <p class="movement-card__name">Japandi</p>
        <p class="movement-card__years">2010s – now</p>
        <p class="movement-card__desc">Wabi-sabi philosophy meets Nordic calm — a quiet harmony of two minimalist traditions.</p>
        <span class="movement-card__pill">Zen</span>
      </div>

      <div class="movement-card"
           style="--accent-clr:#dc2626"
           data-movement="Maximalism"
           data-tags="maximalism, bold, layered, eclectic, pattern, ornament">
        <p class="movement-card__name">Maximalism</p>
        <p class="movement-card__years">Timeless</p>
        <p class="movement-card__desc">More is more. Bold palettes, layered patterns, and joyful excess as deliberate design intent.</p>
        <span class="movement-card__pill">Bold</span>
      </div>

      <div class="movement-card"
           style="--accent-clr:#d97706"
           data-movement="Art Deco"
           data-tags="art deco, geometric, glamour, gold, luxury, 1920s">
        <p class="movement-card__name">Art Deco</p>
        <p class="movement-card__years">1920s–1940s</p>
        <p class="movement-card__desc">Geometric glamour fused with luxury materials — gold, symmetry, and Gatsby-era opulence.</p>
        <span class="movement-card__pill">Glamour</span>
      </div>

    </div>
  </section>

  <!-- ── 3. SUBMISSION FORM ── -->
  <section class="submit-form-section" id="submit-form">
    <div class="submit-form-wrap">
      <h2 class="submit-form-wrap__heading">Contributing to the archive</h2>
      <p class="submit-form-wrap__sub">
        Share a source that captures the spirit of one of the ten movements.
        We review every submission before it appears.
      </p>

      <form id="source-submit-form" class="form-grid" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <label for="field-url">
          Source URL <span class="form-hint">Required — direct link to the reference</span>
          <input
            id="field-url"
            class="form-input"
            type="url"
            name="url"
            required
            placeholder="https://"
          >
        </label>

        <label for="field-movement">
          Design movement
          <select id="field-movement" class="form-select" name="movement">
            <option value="">— Select a movement —</option>
            <option value="Bauhaus">Bauhaus (1919–1933)</option>
            <option value="Minimalism">Minimalism (1960s – now)</option>
            <option value="Art Nouveau">Art Nouveau (1890–1910)</option>
            <option value="Swiss / International">Swiss / International (1950s – now)</option>
            <option value="Y2K Digital">Y2K Digital (1995–2005)</option>
            <option value="Brutalism">Brutalism (1950s – now)</option>
            <option value="Dark Academia">Dark Academia (2020s)</option>
            <option value="Japandi">Japandi (2010s – now)</option>
            <option value="Maximalism">Maximalism</option>
            <option value="Art Deco">Art Deco (1920s–1940s)</option>
          </select>
        </label>

        <label for="field-tags">
          Suggested tags <span class="form-hint">Comma-separated — auto-filled from movement, editable</span>
          <input
            id="field-tags"
            class="form-input"
            type="text"
            name="tags"
            placeholder="e.g. bauhaus, geometric, poster"
          >
        </label>

        <label for="field-notes">
          Notes <span class="form-hint">Optional — what makes this reference significant?</span>
          <textarea
            id="field-notes"
            class="form-textarea"
            name="notes"
            placeholder="What makes this reference significant?"
          ></textarea>
        </label>

        <div>
          <button type="submit" class="submit-btn">
            Add to the archive <span aria-hidden="true">→</span>
          </button>
          <p id="source-submit-msg" role="status"></p>
        </div>
      </form>
    </div>
  </section>

  <!-- ── 4. GUIDELINES ── -->
  <section class="guidelines-section">
    <div class="guidelines-wrap">
      <h2>What makes a good reference?</h2>
      <ul>
        <li>Licensed, public domain, or openly embeddable — we respect copyright.</li>
        <li>Clearly tied to one of the ten design movements in spirit or origin.</li>
        <li>High visual quality — crisp scans, professional photography, or well-preserved originals.</li>
        <li>Original or archival source preferred over secondary reposts.</li>
      </ul>
      <p class="guidelines-note">We review every submission before it appears in the archive.</p>
    </div>
  </section>

</main>

<script>
(function () {
  // Tag suggestions per movement (keyed to <option> values)
  var movementTags = {
    'Bauhaus':              'bauhaus, geometric, functional, modernist, typography',
    'Minimalism':           'minimalism, whitespace, clean, essential, reduction',
    'Art Nouveau':          'art nouveau, organic, floral, ornamental, decorative',
    'Swiss / International':'swiss, international style, grid, typography, rational, helvetica',
    'Y2K Digital':          'y2k, cyber, chrome, holographic, digital, futurism',
    'Brutalism':            'brutalism, raw, concrete, exposed structure, anti-design',
    'Dark Academia':        'dark academia, gothic, moody, classical, library, literary',
    'Japandi':              'japandi, wabi-sabi, zen, nordic, japanese, minimalism',
    'Maximalism':           'maximalism, bold, layered, eclectic, pattern, ornament',
    'Art Deco':             'art deco, geometric, glamour, gold, luxury, 1920s'
  };

  var movementSelect = document.getElementById('field-movement');
  var tagsInput      = document.getElementById('field-tags');
  var grid           = document.getElementById('movements-grid');

  // Populate tags when dropdown changes
  movementSelect.addEventListener('change', function () {
    var val = this.value;
    if (val && movementTags[val]) {
      tagsInput.value = movementTags[val];
    }
  });

  // Card click handler
  grid.addEventListener('click', function (e) {
    var card = e.target.closest('.movement-card');
    if (!card) return;

    // Visual active state
    document.querySelectorAll('.movement-card').forEach(function (c) {
      c.classList.remove('is-selected');
    });
    card.classList.add('is-selected');

    // Pre-select dropdown
    var movement = card.dataset.movement;
    movementSelect.value = movement;

    // Auto-populate tags
    if (movementTags[movement]) {
      tagsInput.value = movementTags[movement];
    }

    // Smooth scroll to form
    document.getElementById('submit-form').scrollIntoView({
      behavior: 'smooth',
      block: 'start'
    });
  });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
