@extends('layouts.app')
@section('title', 'All Tools — VibeKit')
@section('meta_description', 'All VibeKit design tools: font pairings, color palettes, CSS snippets, UI patterns, interactive generators and more. Free for modern designers.')

@section('content')
<div class="vk-container" style="padding-top:64px;padding-bottom:80px;">

    <div style="margin-bottom:56px;">
        <p style="font-size:11px;font-weight:600;color:#787583;text-transform:uppercase;letter-spacing:0.08em;margin:0 0 10px;">Browse</p>
        <h1 class="page-title" style="margin:0 0 12px;">All Tools</h1>
        <p style="font-size:16px;color:#454F5E;margin:0;">100 free design tools — no account needed. CSS, UI, typography, color, dev, and more.</p>
    </div>

    @php
    $sections = [
        [
            'label' => 'CSS & Styles',
            'icon' => '{}',
            'tools' => [
                ['name'=>'Gradient Library','desc'=>'Curated CSS gradients — linear, radial, conic. One-click copy.','href'=>route('snippets.gradient.index'),'icon'=>'〜'],
                ['name'=>'Box Shadow Styles','desc'=>'Soft, hard, neon, neumorphic shadow presets with CSS output.','href'=>route('snippets.shadow.index'),'icon'=>'□'],
                ['name'=>'Motion Presets','desc'=>'CSS animation snippets for common UI interactions.','href'=>route('snippets.motion.index'),'icon'=>'↗'],
                ['name'=>'Easing Reference','desc'=>'Visual easing curve comparison with CSS cubic-bezier output.','href'=>route('snippets.easing.index'),'icon'=>'~'],
                ['name'=>'Glassmorphism Presets','desc'=>'backdrop-filter + rgba card styles with copy code.','href'=>route('snippets.glassmorphism.index'),'icon'=>'◻'],
                ['name'=>'Clip-Path Gallery','desc'=>'Visual clip-path shapes — polygon, circle, ellipse, custom.','href'=>route('snippets.clip_path.index'),'icon'=>'◇'],
                ['name'=>'SVG Pattern Library','desc'=>'Repeating SVG background patterns with copy code.','href'=>route('snippets.svg_pattern.index'),'icon'=>'▦'],
                ['name'=>'Noise Texture Gallery','desc'=>'CSS/SVG grain and noise overlay styles.','href'=>route('snippets.noise.index'),'icon'=>'⋯'],
                ['name'=>'Micro-interaction Library','desc'=>'Hover, click, focus micro-interaction CSS snippets.','href'=>route('snippets.micro_interaction.index'),'icon'=>'↺'],
                ['name'=>'CSS Variable Snippets','desc'=>'Ready-to-paste design token sets as CSS custom properties.','href'=>route('snippets.css_variable.index'),'icon'=>'--'],
                ['name'=>'Tailwind Config Snippets','desc'=>'Ready-to-use tailwind.config.js extensions.','href'=>route('snippets.tailwind_config.index'),'icon'=>'⊞'],
                ['name'=>'Container Query Snippets','desc'=>'CSS container query patterns for responsive components.','href'=>route('snippets.container_query.index'),'icon'=>'⊡'],
                ['name'=>'CSS Reset Comparison','desc'=>'Modern CSS resets side-by-side — Meyer, Normalize, modern-css-reset.','href'=>route('snippets.css_reset.index'),'icon'=>'↺'],
                ['name'=>'Print Style Reference','desc'=>'CSS @media print snippets and A4/Letter layout patterns.','href'=>route('snippets.print_style.index'),'icon'=>'🖨'],
            ],
        ],
        [
            'label' => 'UI Patterns',
            'icon' => '▦',
            'tools' => [
                ['name'=>'Button Gallery','desc'=>'Curated button variants — ghost, solid, pill, icon, group.','href'=>route('patterns.button.index'),'icon'=>'⊡'],
                ['name'=>'Form Field Styles','desc'=>'Input, select, checkbox, radio, and textarea style presets.','href'=>route('patterns.form_field.index'),'icon'=>'☰'],
                ['name'=>'Card Style Gallery','desc'=>'Card layout variations with hover states and code.','href'=>route('patterns.card.index'),'icon'=>'▭'],
                ['name'=>'Navigation Patterns','desc'=>'Topbar, sidebar, tab bar, and breadcrumb patterns.','href'=>route('patterns.navigation.index'),'icon'=>'≡'],
                ['name'=>'Hero Sections','desc'=>'Text-only, image-right, and full-bleed hero variants.','href'=>route('patterns.hero.index'),'icon'=>'↑'],
                ['name'=>'CTA Sections','desc'=>'Call-to-action block designs with copy-ready HTML.','href'=>route('patterns.cta.index'),'icon'=>'→'],
                ['name'=>'Footer Templates','desc'=>'Simple, mega, and minimal footer HTML patterns.','href'=>route('patterns.footer.index'),'icon'=>'↓'],
                ['name'=>'Pricing Tables','desc'=>'Tier, toggle, and feature list pricing layouts.','href'=>route('patterns.pricing.index'),'icon'=>'$'],
                ['name'=>'Testimonial Blocks','desc'=>'Quote, avatar, and logo wall testimonial patterns.','href'=>route('patterns.testimonial.index'),'icon'=>'"'],
                ['name'=>'Feature Sections','desc'=>'Grid, alternating, and icon-left feature layouts.','href'=>route('patterns.feature.index'),'icon'=>'✦'],
                ['name'=>'Empty State Library','desc'=>'Designed empty states with illustration and copy guidance.','href'=>route('patterns.empty_state.index'),'icon'=>'○'],
                ['name'=>'Loading States','desc'=>'Skeleton, spinner, and shimmer loading pattern HTML.','href'=>route('patterns.loading.index'),'icon'=>'⟳'],
                ['name'=>'Error Pages','desc'=>'404, 500, and offline page design patterns.','href'=>route('patterns.error_page.index'),'icon'=>'!'],
                ['name'=>'Toast Notifications','desc'=>'Success, error, warning, and info toast styles.','href'=>route('patterns.toast.index'),'icon'=>'△'],
                ['name'=>'Modal & Dialog','desc'=>'Confirmation, form, and media modal patterns.','href'=>route('patterns.modal.index'),'icon'=>'▣'],
                ['name'=>'Sidebar Layouts','desc'=>'Left, right, collapsible, icon sidebar patterns.','href'=>route('patterns.sidebar.index'),'icon'=>'◧'],
                ['name'=>'Dashboard Layouts','desc'=>'Admin and analytics dashboard starter layouts.','href'=>route('patterns.dashboard.index'),'icon'=>'⊞'],
                ['name'=>'Mobile UI Patterns','desc'=>'iOS/Android UI pattern reference in HTML/CSS.','href'=>route('patterns.mobile.index'),'icon'=>'📱'],
                ['name'=>'Dark UI Inspiration','desc'=>'Curated dark UI designs with copy-ready code.','href'=>route('patterns.dark_ui.index'),'icon'=>'◑'],
                ['name'=>'Email Templates','desc'=>'Transactional and marketing email HTML patterns.','href'=>route('patterns.email.index'),'icon'=>'✉'],
                ['name'=>'OG Image Templates','desc'=>'Open Graph image layout patterns with dimension guide.','href'=>route('patterns.og_template.index'),'icon'=>'🖼'],
                ['name'=>'Icon Style Guide','desc'=>'Comparison of icon styles: Lucide, Heroicons, Phosphor, Tabler.','href'=>route('patterns.icon_style.index'),'icon'=>'◎'],
                ['name'=>'Illustration Styles','desc'=>'Flat, 3D, outlined, and pixel illustration style references.','href'=>route('patterns.illustration.index'),'icon'=>'✏'],
                ['name'=>'Photography Guide','desc'=>'Photography style references for product and brand shoots.','href'=>route('patterns.photography.index'),'icon'=>'📷'],
            ],
        ],
        [
            'label' => 'Typography & Layout',
            'icon' => 'Aa',
            'tools' => [
                ['name'=>'Type Scale Reference','desc'=>'Visual type scales — Major Third, Perfect Fourth, Golden Ratio.','href'=>route('type-tools.type_scale.index'),'icon'=>'Aa'],
                ['name'=>'Line Height Guide','desc'=>'Visual line-height comparisons for body and display text.','href'=>route('type-tools.line_height.index'),'icon'=>'☰'],
                ['name'=>'Letter Spacing Reference','desc'=>'Tracking presets for headings, body, and labels.','href'=>route('type-tools.letter_spacing.index'),'icon'=>'A↔'],
                ['name'=>'Font Size Clamp','desc'=>'CSS clamp() responsive font size generator reference.','href'=>route('type-tools.font_clamp.index'),'icon'=>'↔'],
                ['name'=>'Border Radius Scale','desc'=>'Visual border-radius token reference.','href'=>route('type-tools.border_radius.index'),'icon'=>'◻'],
                ['name'=>'Spacing Scale','desc'=>'Tailwind / custom spacing scale visual reference.','href'=>route('type-tools.spacing_scale.index'),'icon'=>'↕'],
                ['name'=>'Z-index Manager','desc'=>'Named z-index layers reference with copy snippets.','href'=>route('type-tools.z_index.index'),'icon'=>'⊕'],
                ['name'=>'Breakpoint Reference','desc'=>'Tailwind, Bootstrap, and custom breakpoint visualizer.','href'=>route('type-tools.breakpoint.index'),'icon'=>'◁▷'],
                ['name'=>'Aspect Ratio Reference','desc'=>'Common image, video, card, and avatar aspect ratios.','href'=>route('type-tools.aspect_ratio.index'),'icon'=>'▭'],
                ['name'=>'Viewport Unit Cheatsheet','desc'=>'vw, vh, dvh, svh, cqi — explained with examples.','href'=>route('type-tools.viewport_unit.index'),'icon'=>'⊞'],
                ['name'=>'CSS Grid Reference','desc'=>'Grid template areas, auto-fit, fr units visual reference.','href'=>route('type-tools.grid_ref.index'),'icon'=>'▦'],
                ['name'=>'Flexbox Reference','desc'=>'Visual flexbox direction and alignment reference.','href'=>route('type-tools.flexbox_ref.index'),'icon'=>'≡'],
            ],
        ],
        [
            'label' => 'Color',
            'icon' => '◑',
            'tools' => [
                ['name'=>'UI Color Roles','desc'=>'Background, surface, border, and text color role mapping.','href'=>route('color-refs.color_role.index'),'icon'=>'◑'],
                ['name'=>'Mesh Gradient Gallery','desc'=>'Multi-point mesh gradients with CSS variable output.','href'=>route('color-refs.mesh_gradient.index'),'icon'=>'〜'],
                ['name'=>'Background Patterns','desc'=>'Dot, grid, and line CSS-only decorative backgrounds.','href'=>route('color-refs.bg_pattern.index'),'icon'=>'▦'],
                ['name'=>'Accessible Color Pairs','desc'=>'WCAG-compliant foreground + background color combinations.','href'=>route('color-refs.accessible_pair.index'),'icon'=>'Aa'],
                ['name'=>'Brand Archetypes','desc'=>'12 archetypes with visual and tonal color examples.','href'=>route('color-refs.brand_archetype.index'),'icon'=>'◎'],
                ['name'=>'Color Stories','desc'=>'Narrative behind curated palettes for design presentations.','href'=>route('color-refs.color_story.index'),'icon'=>'✦'],
            ],
        ],
        [
            'label' => 'Interactive Generators',
            'icon' => '⚡',
            'tools' => [
                ['name'=>'Contrast Checker','desc'=>'Live WCAG AA/AAA contrast ratio checker with pass/fail.','href'=>route('tools.contrast-checker'),'icon'=>'Aa'],
                ['name'=>'Gradient Builder','desc'=>'Interactive linear/radial/conic gradient editor with CSS output.','href'=>route('tools.gradient-builder'),'icon'=>'〜'],
                ['name'=>'Shadow Builder','desc'=>'Visual box-shadow editor — layer, blur, spread, color.','href'=>route('tools.shadow-builder'),'icon'=>'□'],
                ['name'=>'Tint & Shade Generator','desc'=>'Generate a full 50–950 color scale from any base hex.','href'=>route('tools.tint-shade'),'icon'=>'◑'],
                ['name'=>'Palette Harmony','desc'=>'Generate complementary, analogous, and triadic palettes.','href'=>route('tools.palette-harmony'),'icon'=>'◎'],
                ['name'=>'Font Pairing Preview','desc'=>'Type live text and switch Google Fonts in real time.','href'=>route('tools.font-preview'),'icon'=>'Aa'],
                ['name'=>'Border Radius Builder','desc'=>'Visual border-radius editor — uniform or per-corner.','href'=>route('tools.border-radius-builder'),'icon'=>'◻'],
                ['name'=>'Spacing Visualizer','desc'=>'Visualise your spacing scale and export CSS custom properties.','href'=>route('tools.spacing-visualizer'),'icon'=>'↕'],
                ['name'=>'README Badge Generator','desc'=>'Build shields.io badges for your project README.','href'=>route('tools.readme-badges'),'icon'=>'🔖'],
                ['name'=>'Favicon Generator','desc'=>'Create a favicon from text or emoji — download as PNG.','href'=>route('tools.favicon-generator'),'icon'=>'⚡'],
                ['name'=>'OG Image Generator','desc'=>'Design a 1200×630 Open Graph image and download as PNG.','href'=>route('tools.og-image-generator'),'icon'=>'🖼'],
                ['name'=>'CSS Keyframe Builder','desc'=>'Build @keyframes animations visually and copy CSS output.','href'=>route('tools.keyframe-builder'),'icon'=>'↗'],
                ['name'=>'Responsive Preview','desc'=>'Preview any URL at multiple viewport sizes side-by-side.','href'=>route('tools.responsive-preview'),'icon'=>'◁▷'],
                ['name'=>'Tailwind Theme Generator','desc'=>'Configure tokens and export a tailwind.config.js theme.','href'=>route('tools.tailwind-theme'),'icon'=>'⊞'],
                ['name'=>'Color Extractor','desc'=>'Upload an image and extract its dominant color palette.','href'=>route('tools.color-extractor'),'icon'=>'◑'],
                ['name'=>'Dark Mode Converter','desc'=>'Convert a light palette to dark-mode equivalents.','href'=>route('tools.dark-mode-converter'),'icon'=>'◐'],
                ['name'=>'Design Audit Checklist','desc'=>'Structured audit covering visual, A11y, copy, and flows.','href'=>route('tools.design-audit'),'icon'=>'✓'],
                ['name'=>'A/B Test Hypothesis Builder','desc'=>'Generate a structured hypothesis statement for any A/B test.','href'=>route('tools.ab-hypothesis'),'icon'=>'↔'],
                ['name'=>'Component Prop Table','desc'=>'Define your component API and generate a Markdown prop table.','href'=>route('tools.component-props'),'icon'=>'{}'],
                ['name'=>'Tailwind Cheatsheet','desc'=>'Quick-reference for every major Tailwind utility class. Click to copy.','href'=>route('tools.tailwind-cheatsheet'),'icon'=>'📋'],
            ],
        ],
        [
            'label' => 'Existing Tools',
            'icon' => '✦',
            'tools' => [
                ['name'=>'Font Pairings','desc'=>'Curated heading + body font combinations. Filter by style.','href'=>route('font-pairings.index'),'icon'=>'Aa'],
                ['name'=>'Color Palettes','desc'=>'Hand-picked palettes with mood tags and CSS variable export.','href'=>route('color-palettes.index'),'icon'=>'◑'],
                ['name'=>'Design Prompt Library','desc'=>'Skill-building challenges for UI, branding, and illustration.','href'=>route('design-prompts.index'),'icon'=>'✦'],
                ['name'=>'Section Gallery','desc'=>'Website section inspiration: heroes, navbars, pricing, footers.','href'=>route('website-sections.index'),'icon'=>'▦'],
                ['name'=>'Brief Generator','desc'=>'Generate a structured landing page design brief in seconds.','href'=>route('briefs.index'),'icon'=>'⊡'],
            ],
        ],
    ];
    @endphp

    @foreach($sections as $section)
    <div style="margin-bottom:56px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
            <span style="font-size:18px;color:#7F77DD;">{{ $section['icon'] }}</span>
            <h2 style="font-size:18px;font-weight:700;color:#131B2E;margin:0;">{{ $section['label'] }}</h2>
            <span style="font-size:12px;color:#787583;margin-left:4px;">{{ count($section['tools']) }} tools</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($section['tools'] as $tool)
            <a href="{{ $tool['href'] }}" class="card" style="padding:20px 24px;text-decoration:none;display:flex;align-items:flex-start;gap:14px;">
                <div style="width:38px;height:38px;border-radius:10px;background:#F2F3FF;border:.5px solid #E9ECEF;display:flex;align-items:center;justify-content:center;font-size:16px;color:#7F77DD;flex-shrink:0;">{{ $tool['icon'] }}</div>
                <div>
                    <h3 style="font-size:14px;font-weight:700;color:#131B2E;margin:0 0 5px;line-height:1.2;">{{ $tool['name'] }}</h3>
                    <p style="font-size:12px;color:#454F5E;line-height:1.55;margin:0;">{{ $tool['desc'] }}</p>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@endsection
