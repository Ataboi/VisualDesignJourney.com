<?php

use App\Http\Controllers\BriefController;
use App\Http\Controllers\ColorPaletteController;
use App\Http\Controllers\ColorReferenceController;
use App\Http\Controllers\CssSnippetController;
use App\Http\Controllers\DesignPromptController;
use App\Http\Controllers\FontPairingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InteractiveToolController;
use App\Http\Controllers\TypeToolController;
use App\Http\Controllers\UiPatternController;
use App\Http\Controllers\Vdj\AccountController;
use App\Http\Controllers\Vdj\BlogController;
use App\Http\Controllers\Vdj\BoardController;
use App\Http\Controllers\Vdj\AuthController;
use App\Http\Controllers\Vdj\ExploreController;
use App\Http\Controllers\Vdj\FeedController;
use App\Http\Controllers\Vdj\MediaController;
use App\Http\Controllers\Vdj\NewsletterController;
use App\Http\Controllers\Vdj\ProfileController;
use App\Http\Controllers\Vdj\SearchController;
use App\Http\Controllers\Vdj\SitemapController;
use App\Http\Controllers\Vdj\SocialApiController;
use App\Http\Controllers\Vdj\SourceBookmarkController;
use App\Http\Controllers\Vdj\SponsorLeadController;
use App\Http\Controllers\Vdj\StaticPageController;
use App\Http\Controllers\WebsiteSectionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [ExploreController::class, 'index'])->name('home');
Route::get('/search', SearchController::class)->name('search');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/logout', [AuthController::class, 'logout'])->middleware('auth');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/{lang}/blog', [BlogController::class, 'localizedIndex'])->whereIn('lang', ['tr', 'de'])->name('blog.localized.index');
Route::get('/{lang}/blog/{slug}', [BlogController::class, 'localizedShow'])->whereIn('lang', ['tr', 'de'])->name('blog.localized.show');

Route::get('/board/{id}', [BoardController::class, 'show'])->whereNumber('id')->name('board.show.id');
Route::get('/board/{id}-{slug?}', [BoardController::class, 'show'])->whereNumber('id')->name('board.show');
Route::get('/profile/{username}', [ProfileController::class, 'show'])->name('profile.show');
Route::get('/followers/{username}', [AccountController::class, 'followers'])->name('followers');

Route::middleware('auth')->group(function (): void {
    Route::get('/create', [AccountController::class, 'create'])->name('boards.create');
    Route::post('/create', [AccountController::class, 'storeBoard'])->name('boards.store');
    Route::get('/edit-board/{id}', [AccountController::class, 'editBoard'])->whereNumber('id')->name('boards.edit');
    Route::match(['post', 'put'], '/edit-board/{id}', [AccountController::class, 'updateBoard'])->whereNumber('id')->name('boards.update');
    Route::get('/saved', [AccountController::class, 'saved'])->name('saved');
    Route::get('/settings', [AccountController::class, 'settings'])->name('settings');
    Route::post('/settings', [AccountController::class, 'updateSettings'])->name('settings.update');
    Route::get('/notifications', [AccountController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/read', [AccountController::class, 'markNotificationsRead'])->name('notifications.read');
    Route::get('/onboarding', [AccountController::class, 'onboarding'])->name('onboarding');
    Route::post('/onboarding', [AccountController::class, 'finishOnboarding'])->name('onboarding.finish');
});

Route::get('/newsletter-prefs', [AccountController::class, 'newsletterPrefs'])->name('newsletter-prefs');
Route::post('/newsletter-prefs', [AccountController::class, 'updateNewsletterPrefs'])->name('newsletter-prefs.update');
Route::get('/unsubscribe', [AccountController::class, 'unsubscribe'])->name('unsubscribe');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/feed.xml', [FeedController::class, 'blog'])->name('feed');
Route::redirect('/feed.php', '/feed.xml', 301);
Route::redirect('/sitemap.xml.php', '/sitemap.xml', 301);
Route::redirect('/blog.php', '/blog', 301);
Route::redirect('/tools.php', '/tools', 301);
Route::redirect('/login.php', '/login', 301);
Route::redirect('/register.php', '/register', 301);
Route::redirect('/logout.php', '/logout', 301);
Route::redirect('/create.php', '/create', 301);
Route::redirect('/saved.php', '/saved', 301);
Route::redirect('/onboarding.php', '/onboarding', 301);
Route::get('/settings.php', fn (Request $request) => redirect('/settings?'.http_build_query($request->query()), 301));
Route::get('/notifications.php', fn (Request $request) => redirect('/notifications?'.http_build_query($request->query()), 301));
Route::get('/newsletter-prefs.php', fn (Request $request) => redirect('/newsletter-prefs?'.http_build_query($request->query()), 301));
Route::get('/unsubscribe.php', fn (Request $request) => redirect('/unsubscribe?'.http_build_query($request->query()), 301));
Route::get('/blog-post.php', function (Request $request) {
    $slug = trim((string) ($request->query('slug') ?: $request->query('s')));
    abort_if($slug === '', 404);

    return redirect('/blog/'.$slug.'/', 301);
});
Route::get('/board.php', function (Request $request) {
    $id = (int) $request->query('id');
    abort_if($id < 1, 404);

    return redirect('/board/'.$id, 301);
});
Route::get('/edit-board.php', function (Request $request) {
    $id = (int) $request->query('id');
    abort_if($id < 1, 404);

    return redirect('/edit-board/'.$id, 301);
});
Route::get('/profile.php', function (Request $request) {
    $username = trim((string) $request->query('username'));
    abort_if($username === '', 404);

    return redirect('/profile/'.$username, 301);
});
Route::get('/followers.php', function (Request $request) {
    $username = trim((string) ($request->query('username') ?: $request->query('u')));
    abort_if($username === '', 404);

    return redirect('/followers/'.$username, 301);
});
Route::get('/lightbox.php', function (Request $request) {
    $id = (int) ($request->query('board') ?: $request->query('id'));
    abort_if($id < 1, 404);

    return redirect('/board/'.$id, 301);
});
Route::get('/search.php', fn (Request $request) => redirect('/search?'.http_build_query($request->query()), 301));

foreach (['privacy', 'terms', 'contact', 'sponsor', 'premium', 'editorial-policy', 'colors', 'glossary', 'archive', 'vibe', 'curators', 'trends', 'submit-source'] as $staticSlug) {
    Route::get("/{$staticSlug}", [StaticPageController::class, 'show'])
        ->defaults('slug', $staticSlug)
        ->name("static.{$staticSlug}");
    Route::redirect("/{$staticSlug}.php", "/{$staticSlug}", 301);
}

Route::post('/api/newsletter.php', [NewsletterController::class, 'store'])->name('api.newsletter');
Route::post('/api/sponsor-lead.php', [SponsorLeadController::class, 'store'])->name('api.sponsor-lead');
Route::post('/api/submit-source.php', [SourceBookmarkController::class, 'store'])->name('api.submit-source');
Route::post('/api/bookmark-source.php', [SourceBookmarkController::class, 'store'])->name('api.bookmark-source');
Route::post('/api/discover-for-board.php', [SourceBookmarkController::class, 'discoverForBoard'])->name('api.discover-for-board');
Route::post('/api/like.php', [SocialApiController::class, 'like'])->name('api.like');
Route::post('/api/save.php', [SocialApiController::class, 'save'])->name('api.save');
Route::post('/api/comment.php', [SocialApiController::class, 'comment'])->name('api.comment');
Route::post('/api/follow.php', [SocialApiController::class, 'follow'])->name('api.follow');
Route::get('/api/search-suggest.php', [SocialApiController::class, 'searchSuggest'])->name('api.search-suggest');
Route::get('/api/random.php', [SocialApiController::class, 'randomBoard'])->name('api.random');
Route::get('/api/palette.php', [SocialApiController::class, 'palette'])->name('api.palette');
Route::match(['get', 'post'], '/api/track.php', [SocialApiController::class, 'track'])->name('api.track');
Route::get('/api/media.php', [MediaController::class, 'legacy'])->name('api.media');
Route::get('/api/thumb.php', [MediaController::class, 'legacy'])->name('api.thumb');
Route::get('/wp-content/uploads/{path}', [MediaController::class, 'legacy'])->where('path', '.*')->name('media.legacy');

Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/tools', [HomeController::class, 'tools'])->name('tools');

Route::get('/font-pairings', [FontPairingController::class, 'index'])->name('font-pairings.index');
Route::get('/font-pairings/{fontPairing:slug}', [FontPairingController::class, 'show'])->name('font-pairings.show');

Route::get('/color-palettes', [ColorPaletteController::class, 'index'])->name('color-palettes.index');
Route::get('/color-palettes/{colorPalette:slug}', [ColorPaletteController::class, 'show'])->name('color-palettes.show');

Route::get('/design-prompts', [DesignPromptController::class, 'index'])->name('design-prompts.index');
Route::get('/design-prompts/{designPrompt:slug}', [DesignPromptController::class, 'show'])->name('design-prompts.show');

Route::get('/website-sections', [WebsiteSectionController::class, 'index'])->name('website-sections.index');
Route::get('/website-sections/{websiteSection:slug}', [WebsiteSectionController::class, 'show'])->name('website-sections.show');

Route::get('/brief-generator', [BriefController::class, 'index'])->name('briefs.index');
Route::get('/brief-generator/{briefTemplate:slug}', [BriefController::class, 'show'])->name('briefs.show');
Route::post('/brief-generator/{briefTemplate:slug}/generate', [BriefController::class, 'generate'])->name('briefs.generate');

// ── CSS Snippet tools (14 tools, 1 controller) ──────────────────────────────
$cssTypes = [
    'gradient'        => 'gradients',
    'shadow'          => 'shadows',
    'glassmorphism'   => 'glassmorphism',
    'motion'          => 'motion-presets',
    'easing'          => 'easing-reference',
    'clip_path'       => 'clip-paths',
    'svg_pattern'     => 'svg-patterns',
    'noise'           => 'noise-textures',
    'micro_interaction'=> 'micro-interactions',
    'css_variable'    => 'css-variables',
    'tailwind_config' => 'tailwind-snippets',
    'container_query' => 'container-queries',
    'css_reset'       => 'css-resets',
    'print_style'     => 'print-styles',
];
foreach ($cssTypes as $type => $uri) {
    Route::get("/{$uri}", [CssSnippetController::class, 'index'])
        ->defaults('type', $type)
        ->name("snippets.{$type}.index");
    Route::get("/{$uri}/{slug}", [CssSnippetController::class, 'show'])
        ->defaults('type', $type)
        ->name("snippets.{$type}.show");
}

// ── UI Pattern tools (24 tools, 1 controller) ────────────────────────────────
$uiTypes = [
    'button'       => 'buttons',
    'form_field'   => 'form-fields',
    'card'         => 'card-styles',
    'navigation'   => 'navigation-patterns',
    'hero'         => 'hero-templates',
    'cta'          => 'cta-library',
    'footer'       => 'footer-templates',
    'pricing'      => 'pricing-tables',
    'testimonial'  => 'testimonial-blocks',
    'feature'      => 'feature-sections',
    'empty_state'  => 'empty-states',
    'loading'      => 'loading-states',
    'error_page'   => 'error-pages',
    'toast'        => 'toast-gallery',
    'modal'        => 'modal-gallery',
    'sidebar'      => 'sidebar-layouts',
    'dashboard'    => 'dashboard-layouts',
    'mobile'       => 'mobile-patterns',
    'dark_ui'      => 'dark-ui',
    'email'        => 'email-templates',
    'og_template'  => 'og-templates',
    'icon_style'   => 'icon-styles',
    'illustration' => 'illustration-styles',
    'photography'  => 'photography-guide',
];
foreach ($uiTypes as $type => $uri) {
    Route::get("/{$uri}", [UiPatternController::class, 'index'])
        ->defaults('type', $type)
        ->name("patterns.{$type}.index");
    Route::get("/{$uri}/{slug}", [UiPatternController::class, 'show'])
        ->defaults('type', $type)
        ->name("patterns.{$type}.show");
}

// ── Type/Layout reference tools (12 tools, 1 controller) ────────────────────
$typeToolTypes = [
    'type_scale'    => 'type-scales',
    'line_height'   => 'line-heights',
    'letter_spacing'=> 'letter-spacing',
    'font_clamp'    => 'font-clamps',
    'border_radius' => 'border-radius',
    'spacing_scale' => 'spacing-scale',
    'z_index'       => 'z-index',
    'breakpoint'    => 'breakpoints',
    'aspect_ratio'  => 'aspect-ratios',
    'viewport_unit' => 'viewport-units',
    'grid_ref'      => 'css-grid',
    'flexbox_ref'   => 'flexbox',
];
foreach ($typeToolTypes as $type => $uri) {
    Route::get("/{$uri}", [TypeToolController::class, 'index'])
        ->defaults('type', $type)
        ->name("type-tools.{$type}.index");
    Route::get("/{$uri}/{slug}", [TypeToolController::class, 'show'])
        ->defaults('type', $type)
        ->name("type-tools.{$type}.show");
}

// ── Color reference tools (6 tools, 1 controller) ───────────────────────────
$colorRefTypes = [
    'color_role'      => 'color-roles',
    'mesh_gradient'   => 'mesh-gradients',
    'bg_pattern'      => 'background-patterns',
    'accessible_pair' => 'accessible-colors',
    'brand_archetype' => 'brand-archetypes',
    'color_story'     => 'color-stories',
];
foreach ($colorRefTypes as $type => $uri) {
    Route::get("/{$uri}", [ColorReferenceController::class, 'index'])
        ->defaults('type', $type)
        ->name("color-refs.{$type}.index");
    Route::get("/{$uri}/{slug}", [ColorReferenceController::class, 'show'])
        ->defaults('type', $type)
        ->name("color-refs.{$type}.show");
}

// ── Interactive tools (Tier 4 — pure JS, no DB) ──────────────────────────────
Route::get('/contrast-checker',      [InteractiveToolController::class, 'contrastChecker'])->name('tools.contrast-checker');
Route::get('/gradient-builder',      [InteractiveToolController::class, 'gradientBuilder'])->name('tools.gradient-builder');
Route::get('/shadow-builder',        [InteractiveToolController::class, 'shadowBuilder'])->name('tools.shadow-builder');
Route::get('/border-radius-builder', [InteractiveToolController::class, 'borderRadiusBuilder'])->name('tools.border-radius-builder');
Route::get('/spacing-visualizer',    [InteractiveToolController::class, 'spacingVisualizer'])->name('tools.spacing-visualizer');
Route::get('/palette-harmony',       [InteractiveToolController::class, 'paletteHarmony'])->name('tools.palette-harmony');
Route::get('/favicon-generator',     [InteractiveToolController::class, 'faviconGenerator'])->name('tools.favicon-generator');
Route::get('/og-image-generator',    [InteractiveToolController::class, 'ogImageGenerator'])->name('tools.og-image-generator');
Route::get('/readme-badges',         [InteractiveToolController::class, 'readmeBadges'])->name('tools.readme-badges');
Route::get('/keyframe-builder',      [InteractiveToolController::class, 'keyframeBuilder'])->name('tools.keyframe-builder');
Route::get('/responsive-preview',    [InteractiveToolController::class, 'responsivePreview'])->name('tools.responsive-preview');
Route::get('/tailwind-theme',        [InteractiveToolController::class, 'tailwindTheme'])->name('tools.tailwind-theme');
Route::get('/color-extractor',       [InteractiveToolController::class, 'colorExtractor'])->name('tools.color-extractor');
Route::get('/tint-shade',            [InteractiveToolController::class, 'tintShadeGenerator'])->name('tools.tint-shade');
Route::get('/dark-mode-converter',   [InteractiveToolController::class, 'darkModeConverter'])->name('tools.dark-mode-converter');
Route::get('/font-preview',          [InteractiveToolController::class, 'fontPairingPreview'])->name('tools.font-preview');
Route::redirect('/font-pairing-preview', '/font-preview', 301);
Route::get('/design-audit',          [InteractiveToolController::class, 'designAudit'])->name('tools.design-audit');
Route::get('/ab-hypothesis',         [InteractiveToolController::class, 'abHypothesis'])->name('tools.ab-hypothesis');
Route::get('/component-props',       [InteractiveToolController::class, 'componentProps'])->name('tools.component-props');
Route::get('/tailwind-cheatsheet',   [InteractiveToolController::class, 'tailwindCheatsheet'])->name('tools.tailwind-cheatsheet');

$localizedToolAliases = array_values(array_unique(array_merge(
    [
        'tools',
        'font-pairings',
        'color-palettes',
        'design-prompts',
        'website-sections',
        'brief-generator',
        'contrast-checker',
        'gradient-builder',
        'shadow-builder',
        'border-radius-builder',
        'spacing-visualizer',
        'palette-harmony',
        'favicon-generator',
        'og-image-generator',
        'readme-badges',
        'keyframe-builder',
        'responsive-preview',
        'tailwind-theme',
        'color-extractor',
        'tint-shade',
        'dark-mode-converter',
        'font-preview',
        'font-pairing-preview',
        'design-audit',
        'ab-hypothesis',
        'component-props',
        'tailwind-cheatsheet',
    ],
    array_values($cssTypes),
    array_values($uiTypes),
    array_values($typeToolTypes),
    array_values($colorRefTypes),
)));

foreach ($localizedToolAliases as $toolSlug) {
    Route::get('/{lang}/'.$toolSlug, fn (string $lang) => redirect('/'.$toolSlug, 301))
        ->whereIn('lang', ['tr', 'de']);
    Route::get('/{lang}/'.$toolSlug.'/{item}', fn (string $lang, string $item) => redirect('/'.$toolSlug.'/'.$item, 301))
        ->whereIn('lang', ['tr', 'de']);
}

Route::get('/{slug}', [BlogController::class, 'legacyShow'])
    ->where('slug', '^(?!admin$|api$|assets$|build$|legacy-php$|public$|storage$|uploads$|vendor$|node_modules$).+')
    ->name('blog.legacy');
