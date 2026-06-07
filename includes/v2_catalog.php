<?php
/**
 * VDJ v2 public tool/catalog data.
 * Keeps the VibeKit content layer deployable on the existing PHP stack.
 */

function vdj_v2_catalog(): array {
    static $catalog = null;
    if ($catalog !== null) return $catalog;

    $catalog = [
        [
            'slug' => 'interactive',
            'label' => 'Interactive Generators',
            'kicker' => 'Live tools',
            'icon' => 'fa-bolt',
            'description' => 'Browser-native generators preserved from VDJ and polished with the v2 theme.',
            'items' => [
                ['Gradient Builder', 'Build linear, radial, and conic CSS gradients with copy-ready output.', '/gradient-builder', 'fa-wand-magic-sparkles', 'Live'],
                ['Shadow Builder', 'Tune offset, blur, spread, opacity, and inset shadows visually.', '/shadow-builder', 'fa-layer-group', 'Live'],
                ['Contrast Checker', 'Check WCAG AA/AAA contrast with instant foreground/background previews.', '/contrast-checker', 'fa-circle-half-stroke', 'Live'],
                ['Palette Harmony', 'Generate complementary, analogous, triadic, split, tetradic, and mono palettes.', '/palette-harmony', 'fa-palette', 'Live'],
                ['Tint & Shade Generator', 'Generate 50-950 token scales from a base color.', '/tint-shade', 'fa-droplet', 'v2'],
                ['Border Radius Builder', 'Preview uniform and per-corner radius systems.', '/border-radius-builder', 'fa-vector-square', 'v2'],
                ['Spacing Visualizer', 'Compare spacing scales and export CSS custom properties.', '/spacing-visualizer', 'fa-ruler-combined', 'v2'],
                ['OG Image Generator', 'Compose 1200x630 social preview images.', '/og-image-generator', 'fa-image', 'v2'],
            ],
        ],
        [
            'slug' => 'font-pairings',
            'label' => 'Font Pairings',
            'kicker' => 'Typography',
            'icon' => 'fa-font',
            'description' => 'Curated heading/body combinations inspired by the VibeKit typography library.',
            'items' => [
                ['Editorial Sans + Humanist Text', 'High-trust pairing for essays, blog posts, and culture pages.', '/font-pairings/editorial-sans-humanist-text', 'fa-font', 'Reference'],
                ['Grotesk Display + Mono UI', 'A sharp pairing for dashboards, design systems, and technical tools.', '/font-pairings/grotesk-display-mono-ui', 'fa-code', 'Reference'],
                ['Soft Serif + Neutral Sans', 'A warm pairing for premium editorial landing pages.', '/font-pairings/soft-serif-neutral-sans', 'fa-pen-nib', 'Reference'],
                ['Condensed Display + Utility Sans', 'Compact, strong hierarchy for gallery and index pages.', '/font-pairings/condensed-display-utility-sans', 'fa-heading', 'Reference'],
            ],
        ],
        [
            'slug' => 'color-palettes',
            'label' => 'Color Palettes',
            'kicker' => 'Color',
            'icon' => 'fa-swatchbook',
            'description' => 'V2 palettes with roles, moods, and fast handoff values.',
            'items' => [
                ['VDJ Acid Dark', 'Black surfaces, zinc borders, acid action color, and cool support tones.', '/color-palettes/vdj-acid-dark', 'fa-circle-half-stroke', 'Palette'],
                ['Gallery Neutral', 'Quiet neutral palette for board-heavy browse experiences.', '/color-palettes/gallery-neutral', 'fa-border-all', 'Palette'],
                ['Editorial Contrast', 'Serious editorial palette tuned for long-form readability.', '/color-palettes/editorial-contrast', 'fa-newspaper', 'Palette'],
                ['Signal System', 'Accessible success, warning, error, and information colors.', '/color-palettes/signal-system', 'fa-circle-nodes', 'Palette'],
            ],
        ],
        [
            'slug' => 'design-prompts',
            'label' => 'Design Prompts',
            'kicker' => 'Practice',
            'icon' => 'fa-sparkles',
            'description' => 'Skill-building prompts for UI, branding, typography, and visual systems.',
            'items' => [
                ['Redesign a Design Archive', 'Create a browsing system for 100 visual references with filters and hierarchy.', '/design-prompts/redesign-design-archive', 'fa-compass-drafting', 'Prompt'],
                ['Build a Color Story', 'Turn a palette into a landing page mood, role map, and art direction note.', '/design-prompts/build-color-story', 'fa-palette', 'Prompt'],
                ['Typography Mood Shift', 'Use one layout and three type pairings to change the emotional tone.', '/design-prompts/typography-mood-shift', 'fa-font', 'Prompt'],
                ['Component Critique Sprint', 'Audit buttons, forms, empty states, and loading states for a product screen.', '/design-prompts/component-critique-sprint', 'fa-list-check', 'Prompt'],
            ],
        ],
        [
            'slug' => 'website-sections',
            'label' => 'Website Sections',
            'kicker' => 'Layout',
            'icon' => 'fa-table-cells-large',
            'description' => 'Section inspiration for real production pages, not marketing filler.',
            'items' => [
                ['Tool Hub Hero', 'Dense, utility-first hero for a product/tool directory.', '/website-sections/tool-hub-hero', 'fa-window-maximize', 'Section'],
                ['Editorial Blog Index', 'Article list layout with category, search, and strong scan rhythm.', '/website-sections/editorial-blog-index', 'fa-newspaper', 'Section'],
                ['Gallery Browse Grid', 'Moodboard grid pattern with metadata and save affordances.', '/website-sections/gallery-browse-grid', 'fa-border-all', 'Section'],
                ['Admin Health Panel', 'Operational status cards for deploy, SEO, media, and AdSense checks.', '/website-sections/admin-health-panel', 'fa-heart-pulse', 'Section'],
            ],
        ],
        [
            'slug' => 'css-resources',
            'label' => 'CSS & Style Resources',
            'kicker' => 'Code',
            'icon' => 'fa-code',
            'description' => 'Copy-friendly CSS snippets and UI implementation references.',
            'items' => [
                ['Gradient Library', 'Reusable linear, radial, mesh-like, and brand gradient snippets.', '/gradients', 'fa-wand-magic-sparkles', 'CSS'],
                ['Shadow Styles', 'Soft, hard, elevated, inset, and glow shadow presets.', '/shadows', 'fa-layer-group', 'CSS'],
                ['Motion Presets', 'Hover, focus, reveal, and loading motion recipes.', '/motion-presets', 'fa-person-running', 'CSS'],
                ['Glassmorphism Presets', 'Backdrop-filter cards and panels with fallback-friendly CSS.', '/glassmorphism', 'fa-square', 'CSS'],
                ['Container Queries', 'Responsive component patterns using container query syntax.', '/container-queries', 'fa-boxes-stacked', 'CSS'],
                ['CSS Variables', 'Token sets for themes, roles, spacing, and typography.', '/css-variables', 'fa-sliders', 'CSS'],
            ],
        ],
        [
            'slug' => 'ui-patterns',
            'label' => 'UI Pattern Library',
            'kicker' => 'Interface',
            'icon' => 'fa-cubes-stacked',
            'description' => 'Production UI pattern references for components, states, and page sections.',
            'items' => [
                ['Button Gallery', 'Solid, ghost, icon, segmented, and tool-button patterns.', '/buttons', 'fa-square-check', 'Pattern'],
                ['Form Field Styles', 'Inputs, selects, checkboxes, radios, and textareas.', '/form-fields', 'fa-keyboard', 'Pattern'],
                ['Card Styles', 'Compact cards, gallery cards, stat cards, and code cards.', '/card-styles', 'fa-id-card', 'Pattern'],
                ['Navigation Patterns', 'Topbar, sidebar, tabs, breadcrumbs, and mobile drawers.', '/navigation-patterns', 'fa-bars', 'Pattern'],
                ['Empty States', 'No-result, first-run, error, and permission states.', '/empty-states', 'fa-circle-info', 'Pattern'],
                ['Dashboard Layouts', 'Admin and analytics layouts for operational tools.', '/dashboard-layouts', 'fa-chart-line', 'Pattern'],
            ],
        ],
        [
            'slug' => 'type-layout',
            'label' => 'Typography & Layout',
            'kicker' => 'Reference',
            'icon' => 'fa-ruler',
            'description' => 'Type scale, spacing, radius, grid, flexbox, and breakpoint references.',
            'items' => [
                ['Type Scales', 'Major third, perfect fourth, modular, and editorial scales.', '/type-scales', 'fa-heading', 'Reference'],
                ['Line Heights', 'Readable body, compact UI, and display line-height references.', '/line-heights', 'fa-align-left', 'Reference'],
                ['Font Clamps', 'Responsive CSS clamp() recipes for headings and body text.', '/font-clamps', 'fa-text-width', 'Reference'],
                ['Spacing Scale', 'Visual spacing tokens for UI rhythm and layout gutters.', '/spacing-scale', 'fa-ruler-combined', 'Reference'],
                ['CSS Grid', 'Auto-fit, named areas, editorial grids, and gallery grids.', '/css-grid', 'fa-table-cells', 'Reference'],
                ['Flexbox', 'Alignment, wrapping, toolbar, and list layout patterns.', '/flexbox', 'fa-grip-lines', 'Reference'],
            ],
        ],
    ];

    return $catalog;
}

function vdj_v2_section_by_slug(string $slug): ?array {
    $slug = trim($slug, '/');
    $aliases = [
        'brief-generator' => 'design-prompts',
        'color-roles' => 'color-palettes',
        'mesh-gradients' => 'css-resources',
        'background-patterns' => 'css-resources',
        'accessible-colors' => 'color-palettes',
        'brand-archetypes' => 'color-palettes',
        'color-stories' => 'color-palettes',
        'gradients' => 'css-resources',
        'shadows' => 'css-resources',
        'glassmorphism' => 'css-resources',
        'motion-presets' => 'css-resources',
        'easing-reference' => 'css-resources',
        'clip-paths' => 'css-resources',
        'svg-patterns' => 'css-resources',
        'noise-textures' => 'css-resources',
        'micro-interactions' => 'css-resources',
        'css-variables' => 'css-resources',
        'tailwind-snippets' => 'css-resources',
        'container-queries' => 'css-resources',
        'css-resets' => 'css-resources',
        'print-styles' => 'css-resources',
        'buttons' => 'ui-patterns',
        'form-fields' => 'ui-patterns',
        'card-styles' => 'ui-patterns',
        'navigation-patterns' => 'ui-patterns',
        'hero-templates' => 'ui-patterns',
        'cta-library' => 'ui-patterns',
        'footer-templates' => 'ui-patterns',
        'pricing-tables' => 'ui-patterns',
        'testimonial-blocks' => 'ui-patterns',
        'feature-sections' => 'ui-patterns',
        'empty-states' => 'ui-patterns',
        'loading-states' => 'ui-patterns',
        'error-pages' => 'ui-patterns',
        'toast-gallery' => 'ui-patterns',
        'modal-gallery' => 'ui-patterns',
        'sidebar-layouts' => 'ui-patterns',
        'dashboard-layouts' => 'ui-patterns',
        'mobile-patterns' => 'ui-patterns',
        'dark-ui' => 'ui-patterns',
        'email-templates' => 'ui-patterns',
        'og-templates' => 'ui-patterns',
        'icon-styles' => 'ui-patterns',
        'illustration-styles' => 'ui-patterns',
        'photography-guide' => 'ui-patterns',
        'type-scales' => 'type-layout',
        'line-heights' => 'type-layout',
        'letter-spacing' => 'type-layout',
        'font-clamps' => 'type-layout',
        'border-radius' => 'type-layout',
        'spacing-scale' => 'type-layout',
        'z-index' => 'type-layout',
        'breakpoints' => 'type-layout',
        'aspect-ratios' => 'type-layout',
        'viewport-units' => 'type-layout',
        'css-grid' => 'type-layout',
        'flexbox' => 'type-layout',
    ];
    $canonical = $aliases[$slug] ?? $slug;
    foreach (vdj_v2_catalog() as $section) {
        if ($section['slug'] === $canonical) return $section;
    }
    return null;
}
