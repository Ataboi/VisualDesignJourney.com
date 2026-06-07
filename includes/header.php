<?php
/**
 * includes/header.php
 * Global navigation + HTML head — matches screen exports exactly.
 * Requires: $pageTitle (string), $activeNav (string, optional)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/ads.php';

session_start_if_not_started();
$currentLang = vdj_bootstrap_language();
$supportedLangs = vdj_supported_languages();

$pageTitle    = $pageTitle    ?? 'Visual Design Journey';
$activeNav    = $activeNav    ?? '';
$metaDesc     = $metaDesc     ?? 'A community for visual designers and curators to share mood boards and discover creative inspiration.';
$metaImage    = $metaImage    ?? publicUrl('/assets/img/og-default.jpg');
$canonicalUrl = $canonicalUrl ?? publicUrl(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'));
$ogType       = $ogType       ?? 'website';

$isLoggedIn = isset($_SESSION['user_id']);
$currentUser = null;
if ($isLoggedIn) {
    $currentUser = getUserById(getDB(), $_SESSION['user_id']);
}

$unreadCount = $isLoggedIn ? getUnreadNotifCount(getDB(), $_SESSION['user_id']) : 0;

$navBlogCategories = [];
$navBlogTags = [];
try {
    $navDb = getDB();
    $hasBlogCategoryI18n = dbColumnExists($navDb, 'blog_categories', 'name_tr') && dbColumnExists($navDb, 'blog_categories', 'slug_tr');
    $catSelect = $hasBlogCategoryI18n
        ? 'bc.name, bc.name_tr, bc.name_de, bc.slug, bc.slug_tr, bc.slug_de, COUNT(bpc.post_id) AS cnt'
        : 'bc.name, "" AS name_tr, "" AS name_de, bc.slug, "" AS slug_tr, "" AS slug_de, COUNT(bpc.post_id) AS cnt';
    $catGroup = $hasBlogCategoryI18n
        ? 'bc.id, bc.name, bc.name_tr, bc.name_de, bc.slug, bc.slug_tr, bc.slug_de'
        : 'bc.id, bc.name, bc.slug';
    $catStmt = $navDb->query("SELECT {$catSelect}
        FROM blog_categories bc
        JOIN blog_post_categories bpc ON bpc.category_id = bc.id
        GROUP BY {$catGroup}
        HAVING cnt > 0
        ORDER BY cnt DESC, bc.name ASC
        LIMIT 6");
    $navBlogCategories = $catStmt->fetchAll();

    $hasBlogTagI18n = dbColumnExists($navDb, 'blog_tags', 'name_tr') && dbColumnExists($navDb, 'blog_tags', 'slug_tr');
    $tagSelect = $hasBlogTagI18n
        ? 'bt.name, bt.name_tr, bt.name_de, bt.slug, bt.slug_tr, bt.slug_de, COUNT(bpt.post_id) AS cnt'
        : 'bt.name, "" AS name_tr, "" AS name_de, bt.slug, "" AS slug_tr, "" AS slug_de, COUNT(bpt.post_id) AS cnt';
    $tagGroup = $hasBlogTagI18n
        ? 'bt.id, bt.name, bt.name_tr, bt.name_de, bt.slug, bt.slug_tr, bt.slug_de'
        : 'bt.id, bt.name, bt.slug';
    $tagStmt = $navDb->query("SELECT {$tagSelect}
        FROM blog_tags bt
        JOIN blog_post_tags bpt ON bpt.tag_id = bt.id
        GROUP BY {$tagGroup}
        HAVING cnt > 0
        ORDER BY cnt DESC, bt.name ASC
        LIMIT 6");
    $navBlogTags = $tagStmt->fetchAll();
} catch (Throwable $e) {
    $navBlogCategories = [];
    $navBlogTags = [];
}
?>
<!DOCTYPE html>
<!-- VDJ_BUILD: <?= e(VDJ_BUILD_ID) ?> -->
<html lang="<?= e($currentLang) ?>">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="<?= e($metaDesc) ?>"/>
  <meta name="csrf-token" content="<?= e(csrfToken()) ?>"/>
  <meta name="robots" content="index, follow"/>
  <link rel="canonical" href="<?= e($canonicalUrl) ?>"/>
  <?php
  // $hreflangPost is set in blog-post.php with per-language slug URLs
  // for all other pages fall back to the generic language switcher URL
  if (!empty($hreflangPost)):
      foreach ($hreflangPost as $hfLang => $hfUrl): ?>
  <link rel="alternate" hreflang="<?= e($hfLang) ?>" href="<?= e($hfUrl) ?>"/>
  <?php endforeach; ?>
  <link rel="alternate" hreflang="x-default" href="<?= e($hreflangPost['en']) ?>"/>
  <?php else: foreach ($supportedLangs as $langCode => $langMeta): ?>
  <link rel="alternate" hreflang="<?= e($langCode) ?>" href="<?= e(publicUrl(vdj_language_url($langCode))) ?>"/>
  <?php endforeach; ?>
  <link rel="alternate" hreflang="x-default" href="<?= e(publicUrl(vdj_language_url('en'))) ?>"/>
  <?php endif; ?>

  <!-- Open Graph -->
  <meta property="og:type"        content="<?= e($ogType) ?>"/>
  <meta property="og:site_name"   content="Visual Design Journey"/>
  <meta property="og:title"       content="<?= e($pageTitle) ?>"/>
  <meta property="og:description" content="<?= e($metaDesc) ?>"/>
  <meta property="og:url"         content="<?= e($canonicalUrl) ?>"/>
  <meta property="og:image"       content="<?= e($metaImage) ?>"/>
  <meta property="og:image:width"  content="1200"/>
  <meta property="og:image:height" content="630"/>
  <meta property="og:locale"      content="<?= e($currentLang === 'tr' ? 'tr_TR' : ($currentLang === 'de' ? 'de_DE' : 'en_US')) ?>"/>

  <!-- Twitter Card -->
  <meta name="twitter:card"        content="summary_large_image"/>
  <meta name="twitter:title"       content="<?= e($pageTitle) ?>"/>
  <meta name="twitter:description" content="<?= e($metaDesc) ?>"/>
  <meta name="twitter:image"       content="<?= e($metaImage) ?>"/>

  <!-- Schema.org WebSite (homepage only) -->
  <?php if ($activeNav === 'explore'): ?>
  <?php
    $websiteLd = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'Visual Design Journey',
        'url' => publicUrl(),
        'description' => $metaDesc,
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => publicUrl('/search.php?q={search_term_string}'),
            'query-input' => 'required name=search_term_string',
        ],
    ];
  ?>
  <?= vdj_json_ld_script($websiteLd, 'WebSite JSON-LD') ?>
  <?php endif; ?>

  <!-- Google consent mode + Analytics -->
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    window.vdjAdsenseClient = <?= json_encode(adsenseEnabled() ? ADSENSE_CLIENT : '') ?>;
    gtag('consent', 'default', {
      ad_storage: 'denied',
      ad_user_data: 'denied',
      ad_personalization: 'denied',
      analytics_storage: 'denied'
    });
    if (localStorage.getItem('vdj_cookie_consent') === 'accepted') {
      gtag('consent', 'update', {
        ad_storage: 'granted',
        ad_user_data: 'granted',
        ad_personalization: 'granted',
        analytics_storage: 'granted'
      });
    }
    (window.requestIdleCallback || function (cb) { return setTimeout(cb, 2200); })(function () {
      var ga = document.createElement('script');
      ga.async = true;
      ga.src = 'https://www.googletagmanager.com/gtag/js?id=G-F97VPH51W6';
      document.head.appendChild(ga);
      gtag('js', new Date());
      gtag('config', 'G-F97VPH51W6', {
        page_title: document.title,
        page_location: window.location.href,
        anonymize_ip: true
      });

      if (window.vdjAdsenseClient) {
        var ads = document.createElement('script');
        ads.async = true;
        ads.crossOrigin = 'anonymous';
        ads.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' + encodeURIComponent(window.vdjAdsenseClient);
        document.head.appendChild(ads);
      }
    });
  </script>

  <style>
    :root{--bg:#fff;--surface:#f8f9fa;--border:#e9ecef;--text:#0f172a;--muted:#454f5e;--accent:#7f77dd;--acid:#D7FF3F;--nav-h:60px;--container:1440px;--margin-desk:48px;--margin-tab:32px;--margin-mob:16px;--font-sans:'Inter',system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;--font-display:'Inter',system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;--font-mono:'JetBrains Mono','Courier New',monospace}
    *,*::before,*::after{box-sizing:border-box}html{background:var(--bg)}body{margin:0;font-family:var(--font-sans);font-size:16px;line-height:1.6;color:var(--text);background:var(--bg);-webkit-font-smoothing:antialiased}img{display:block;max-width:100%;height:auto}.container{width:100%;max-width:var(--container);margin-inline:auto;padding-inline:var(--margin-desk)}.page-wrap{padding-top:calc(var(--nav-h) + 40px)}.nav{position:fixed;inset:0 0 auto;height:var(--nav-h);background:rgba(255,255,255,.92);border-bottom:1px solid var(--border);z-index:100}.nav__inner{display:grid;grid-template-columns:max-content minmax(0,1fr) max-content;align-items:center;column-gap:clamp(18px,2vw,32px);height:100%}.nav__logo{grid-column:1;display:inline-flex;align-items:baseline;gap:4px;min-width:max-content;font-family:var(--font-display);font-weight:700;color:var(--text);text-decoration:none;white-space:nowrap}.nav__logo-accent{color:var(--accent)}.nav__center{grid-column:2;display:flex;align-items:center;justify-content:flex-start;min-width:0;overflow:visible}.nav__links{display:flex;align-items:center;gap:clamp(10px,.9vw,16px);list-style:none;margin:0;padding:0;flex-wrap:nowrap;min-width:0}.nav__link{font-size:12px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);text-decoration:none}.nav__optional{display:none}.nav__more{display:list-item}.nav__actions{grid-column:3;display:flex;align-items:center;gap:8px;min-width:max-content;justify-self:end}.bg-surface{background:var(--surface)}@media(max-width:2200px) and (min-width:769px){.container.nav__inner{padding-inline:clamp(18px,2vw,28px)}.nav__inner{column-gap:clamp(12px,1.35vw,22px)}.nav__logo{font-size:14px}.nav__link{font-size:11px;letter-spacing:.08em}.nav__search{width:44px}}@media(max-width:1120px) and (min-width:769px){.nav__center{display:none!important}.nav__hamburger{display:flex}.nav__search,.nav__hide-mobile{display:none!important}}@media(max-width:768px){.container{padding-inline:var(--margin-tab)}.nav__center{display:none!important}}@media(max-width:480px){.container{padding-inline:var(--margin-mob)}}
  </style>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous"/>
  <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'"/>
  <link rel="preload" href="/assets/css/style.min.css?v=<?= e(VDJ_ASSET_VERSION) ?>" as="style" onload="this.onload=null;this.rel='stylesheet'"/>
  <link rel="preload" href="/assets/css/vdj-v2.css?v=<?= e(VDJ_ASSET_VERSION) ?>" as="style" onload="this.onload=null;this.rel='stylesheet'"/>
  <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" as="style" crossorigin="anonymous" referrerpolicy="no-referrer" onload="this.onload=null;this.rel='stylesheet'"/>
  <link rel="preload" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'"/>
  <noscript>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap"/>
    <link rel="stylesheet" href="/assets/css/style.min.css?v=<?= e(VDJ_ASSET_VERSION) ?>"/>
    <link rel="stylesheet" href="/assets/css/vdj-v2.css?v=<?= e(VDJ_ASSET_VERSION) ?>"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap"/>
  </noscript>
  <?php if (!empty($headExtra)) echo $headExtra; ?>
  <script>
    (function(){
      var t = localStorage.getItem('vdj_theme') ||
              (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    })();
  </script>
</head>
<body class="bg-surface">

<div class="cookie-banner" id="cookie-banner" hidden>
  <div class="cookie-banner__body">
    <strong><?= e(__('cookie.title', 'Cookies & ads')) ?></strong>
    <p><?= e(__('cookie.body', 'We use analytics cookies and may use Google AdSense cookies to keep Visual Design Journey useful and sustainable.')) ?></p>
  </div>
  <div class="cookie-banner__actions">
    <a href="<?= e(vdj_localized_url('/privacy')) ?>#cookies" class="btn btn--secondary btn--sm"><?= e(__('cookie.learn_more', 'Learn more')) ?></a>
    <button type="button" class="btn btn--secondary btn--sm" id="cookie-reject"><?= e(__('cookie.reject', 'Reject')) ?></button>
    <button type="button" class="btn btn--primary btn--sm" id="cookie-accept"><?= e(__('cookie.accept', 'Accept')) ?></button>
  </div>
</div>

<nav class="nav" id="top-nav" role="navigation" aria-label="Main navigation">
  <div class="container nav__inner">

    <!-- Logo -->
    <a href="<?= e(vdj_localized_url('/')) ?>" class="nav__logo" aria-label="Visual Design Journey home">
      <span class="nav__logo-main">Visual Design</span>
      <span class="nav__logo-accent">Journey</span>
    </a>

    <!-- Center: links -->
    <div class="nav__center">
      <ul class="nav__links" id="nav-links" role="list">
        <li><a href="<?= e(vdj_localized_url('/')) ?>"          class="nav__link <?= $activeNav === 'explore'   ? 'active' : '' ?>"><?= e(__('nav.explore', 'Explore')) ?></a></li>
        <li><a href="<?= e(vdj_localized_url('/curators')) ?>"  class="nav__link <?= $activeNav === 'curators'  ? 'active' : '' ?>"><?= e(__('nav.curators', 'Curators')) ?></a></li>
        <li><a href="<?= e(vdj_localized_url('/trends')) ?>"    class="nav__link <?= $activeNav === 'trends'    ? 'active' : '' ?>"><?= e(__('nav.trends', 'Trends')) ?></a></li>
        <li class="nav__optional"><a href="<?= e(vdj_localized_url('/vibe')) ?>"      class="nav__link <?= $activeNav === 'vibe'      ? 'active' : '' ?>"><?= e(__('nav.vibe', 'Vibe')) ?></a></li>
        <li class="nav__item nav__item--dropdown nav__item--blog">
          <a href="<?= e(vdj_localized_url('/blog')) ?>" class="nav__link nav__link--dropdown <?= $activeNav === 'blog' ? 'active' : '' ?>" aria-haspopup="true" aria-expanded="false">
            <?= e(__('nav.blog', 'Blog')) ?> <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
          </a>
          <div class="nav__mega" role="menu" aria-label="<?= e(__('nav.blog', 'Blog')) ?>">
            <a href="<?= e(vdj_localized_url('/blog')) ?>" class="nav__mega-lead" role="menuitem">
              <strong><?= e(__('nav.blog_latest', 'Latest Articles')) ?></strong>
              <span><?= e(__('nav.blog_latest_desc', 'Editorial guides, design culture, and visual references.')) ?></span>
            </a>
            <?php if (!empty($navBlogCategories)): ?>
            <div class="nav__mega-group">
              <span><?= e(__('nav.blog_categories', 'Categories')) ?></span>
              <?php foreach ($navBlogCategories as $navCat): ?>
                <a href="<?= e(blogCategoryUrl(localizedCategorySlug($navCat))) ?>" role="menuitem"><?= e(localizedCategoryName($navCat)) ?></a>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($navBlogTags)): ?>
            <div class="nav__mega-group">
              <span><?= e(__('nav.blog_tags', 'Tags')) ?></span>
              <?php foreach ($navBlogTags as $navTag): ?>
                <a href="<?= e(blogTagUrl(localizedTagSlug($navTag))) ?>" role="menuitem">#<?= e(localizedTagName($navTag)) ?></a>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </li>
        <li class="nav__item nav__item--dropdown">
          <a href="/tools" class="nav__link nav__link--dropdown <?= $activeNav === 'tools' ? 'active' : '' ?>" aria-haspopup="true" aria-expanded="false">
            Tools <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
          </a>
          <div class="nav__mega nav__mega--compact" role="menu" aria-label="Design Tools">
            <div class="nav__mega-group">
              <a href="/tools" role="menuitem"><i class="fa-solid fa-grid-2"></i> All Tools</a>
              <a href="/gradient-builder" role="menuitem"><i class="fa-solid fa-wand-magic-sparkles"></i> Gradient Builder</a>
              <a href="/shadow-builder" role="menuitem"><i class="fa-solid fa-layer-group"></i> Shadow Builder</a>
              <a href="/contrast-checker" role="menuitem"><i class="fa-solid fa-circle-half-stroke"></i> Contrast Checker</a>
              <a href="/palette-harmony" role="menuitem"><i class="fa-solid fa-palette"></i> Palette Harmony</a>
            </div>
          </div>
        </li>
        <li class="nav__optional"><a href="<?= e(vdj_localized_url('/glossary')) ?>"  class="nav__link <?= $activeNav === 'glossary'  ? 'active' : '' ?>"><?= e(__('nav.glossary', 'Glossary')) ?></a></li>
        <li><a href="<?= e(vdj_localized_url('/archive')) ?>"   class="nav__link <?= $activeNav === 'archive'   ? 'active' : '' ?>"><?= e(__('nav.archive', 'Archive')) ?></a></li>
        <li class="nav__optional"><a href="<?= e(vdj_localized_url('/premium')) ?>"   class="nav__link <?= $activeNav === 'premium'   ? 'active' : '' ?>"><?= e(__('nav.premium', 'Premium')) ?></a></li>
        <li class="nav__optional"><a href="<?= e(vdj_localized_url('/sponsor')) ?>"   class="nav__link <?= $activeNav === 'sponsor'   ? 'active' : '' ?>"><?= e(__('nav.partner', 'Partner')) ?></a></li>
        <li class="nav__item nav__item--dropdown nav__more">
          <button type="button" class="nav__link nav__link--dropdown nav__link-button <?= in_array($activeNav, ['vibe','glossary','premium','sponsor'], true) ? 'active' : '' ?>" aria-haspopup="true" aria-expanded="false">
            <?= e(__('nav.more', 'More')) ?> <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
          </button>
          <div class="nav__mega nav__mega--compact" role="menu" aria-label="<?= e(__('nav.more', 'More')) ?>">
            <div class="nav__mega-group">
              <a href="<?= e(vdj_localized_url('/vibe')) ?>" role="menuitem"><i class="fa-solid fa-palette"></i> <?= e(__('nav.vibe', 'Vibe')) ?></a>
              <a href="<?= e(vdj_localized_url('/glossary')) ?>" role="menuitem"><i class="fa-solid fa-book-open"></i> <?= e(__('nav.glossary', 'Glossary')) ?></a>
              <a href="<?= e(vdj_localized_url('/premium')) ?>" role="menuitem"><i class="fa-solid fa-crown"></i> <?= e(__('nav.premium', 'Premium')) ?></a>
              <a href="<?= e(vdj_localized_url('/sponsor')) ?>" role="menuitem"><i class="fa-solid fa-handshake"></i> <?= e(__('nav.partner', 'Partner')) ?></a>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- Actions -->
    <div class="nav__actions">
      <!-- Search -->
      <div class="nav__search">
        <span class="material-symbols-outlined" aria-hidden="true">search</span>
        <form action="<?= e(vdj_localized_url('/search')) ?>" method="GET" role="search">
          <input type="text" name="q" id="nav-search"
                 class="form-input"
                 placeholder="<?= e(__('nav.search', 'Search boards...')) ?>"
                 value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                 aria-label="Search boards"/>
        </form>
      </div>
      <!-- Theme toggle -->
      <button class="theme-toggle" id="theme-toggle" aria-label="Toggle dark mode" title="Toggle dark mode">
        <i class="fa-solid fa-sun icon-sun"></i>
        <i class="fa-solid fa-moon icon-moon"></i>
      </button>
      <div class="lang-switcher" aria-label="<?= e(__('nav.language', 'Choose language')) ?>">
        <?php foreach ($supportedLangs as $langCode => $langMeta): ?>
          <a href="<?= e(vdj_language_url($langCode)) ?>"
             class="lang-switcher__item <?= $currentLang === $langCode ? 'active' : '' ?>"
             hreflang="<?= e($langCode) ?>"
             aria-label="<?= e($langMeta['label']) ?>"
             <?= $currentLang === $langCode ? 'aria-current="true"' : '' ?>>
            <span aria-hidden="true"><?= e($langMeta['flag']) ?></span>
            <span><?= e($langMeta['short']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
      <?php if ($isLoggedIn): ?>
        <!-- Notifications -->
        <a href="<?= e(vdj_localized_url('/notifications')) ?>" class="nav__icon-btn" aria-label="Notifications" id="nav-notif-btn" data-tip="Notifications">
          <i class="fa-solid fa-bell" style="font-size:16px;"></i>
          <?php if ($unreadCount > 0): ?>
            <span class="nav__badge" aria-label="<?= $unreadCount ?> unread"></span>
          <?php endif; ?>
        </a>
        <!-- Create board -->
        <a href="<?= e(vdj_localized_url('/create')) ?>" class="btn btn--primary btn--sm nav__hide-mobile" id="nav-create-btn">
          <i class="fa-solid fa-plus" style="font-size:13px;"></i>
          <?= e(__('nav.create_board', 'Create Board')) ?>
        </a>
        <!-- Avatar dropdown -->
        <div class="nav__user-menu" id="nav-user-menu">
          <button class="nav__avatar-btn" id="nav-avatar-btn" aria-label="Account menu" aria-expanded="false" aria-haspopup="true">
            <?php if (!empty($currentUser['avatar'])): ?>
              <img src="<?= e(imageUrl($currentUser['avatar'] ?? null)) ?>"
                   alt="<?= e($currentUser['username']) ?>"
                   class="avatar avatar--sm loaded"/>
            <?php else: ?>
              <span class="avatar avatar--sm avatar--initial"><?= strtoupper(substr($currentUser['username'] ?? 'U', 0, 1)) ?></span>
            <?php endif; ?>
          </button>
          <div class="nav__dropdown" id="nav-dropdown" role="menu">
            <div class="nav__dropdown-header">
              <span class="nav__dropdown-name"><?= e($currentUser['username'] ?? '') ?></span>
              <span class="nav__dropdown-email"><?= e($currentUser['email'] ?? '') ?></span>
            </div>
            <a href="/profile.php?u=<?= urlencode($currentUser['username'] ?? '') ?>" class="nav__dropdown-item" role="menuitem">
              <i class="fa-solid fa-user"></i> <?= e(__('nav.profile', 'My Profile')) ?>
            </a>
            <a href="<?= e(vdj_localized_url('/create')) ?>" class="nav__dropdown-item" role="menuitem">
              <i class="fa-solid fa-plus"></i> <?= e(__('nav.create_board', 'Create Board')) ?>
            </a>
            <a href="<?= e(vdj_localized_url('/saved')) ?>" class="nav__dropdown-item" role="menuitem">
              <i class="fa-solid fa-bookmark"></i> <?= e(__('nav.saved', 'Saved Boards')) ?>
            </a>
            <a href="<?= e(vdj_localized_url('/settings')) ?>" class="nav__dropdown-item" role="menuitem">
              <i class="fa-solid fa-gear"></i> <?= e(__('nav.settings', 'Settings')) ?>
            </a>
            <?php if (!empty($currentUser['is_admin'])): ?>
            <a href="/admin/" class="nav__dropdown-item" role="menuitem">
              <i class="fa-solid fa-shield-halved"></i> <?= e(__('nav.admin', 'Admin Panel')) ?>
            </a>
            <?php endif; ?>
            <div class="nav__dropdown-divider"></div>
            <a href="<?= e(vdj_localized_url('/logout')) ?>" class="nav__dropdown-item nav__dropdown-item--danger" role="menuitem">
              <i class="fa-solid fa-right-from-bracket"></i> <?= e(__('nav.sign_out', 'Sign out')) ?>
            </a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= e(vdj_localized_url('/login')) ?>"    class="btn btn--secondary btn--sm nav__hide-mobile" id="nav-signin-btn"><?= e(__('nav.sign_in', 'Sign in')) ?></a>
        <a href="<?= e(vdj_localized_url('/register')) ?>" class="btn btn--primary btn--sm nav__hide-mobile"   id="nav-register-btn"><?= e(__('nav.join', 'Join')) ?></a>
      <?php endif; ?>

      <!-- Mobile hamburger -->
      <button class="nav__hamburger" id="nav-hamburger-btn" aria-label="Open menu" aria-expanded="false" aria-controls="nav-links">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
  </div>
</nav>

<!-- Mobile nav drawer -->
<div class="nav__mobile-overlay" id="nav-mobile-overlay" aria-hidden="true"></div>
<div class="nav__mobile-drawer" id="nav-mobile-drawer" role="dialog" aria-label="Navigation" aria-hidden="true">
  <div class="nav__mobile-header">
    <span class="nav__logo"><span class="nav__logo-main">Visual Design</span><span class="nav__logo-accent">Journey</span></span>
    <button class="nav__mobile-close" id="nav-mobile-close" aria-label="Close menu">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>
  <nav class="nav__mobile-links">
    <a href="<?= e(vdj_localized_url('/')) ?>" class="nav__mobile-link <?= $activeNav==='explore'  ? 'active':'' ?>"><i class="fa-solid fa-compass"></i> <?= e(__('nav.explore', 'Explore')) ?></a>
    <a href="<?= e(vdj_localized_url('/curators')) ?>" class="nav__mobile-link <?= $activeNav==='curators' ? 'active':'' ?>"><i class="fa-solid fa-users"></i> <?= e(__('nav.curators', 'Curators')) ?></a>
    <a href="<?= e(vdj_localized_url('/trends')) ?>" class="nav__mobile-link <?= $activeNav==='trends'   ? 'active':'' ?>"><i class="fa-solid fa-fire"></i> <?= e(__('nav.trends', 'Trends')) ?></a>
    <a href="<?= e(vdj_localized_url('/vibe')) ?>" class="nav__mobile-link <?= $activeNav==='vibe'     ? 'active':'' ?>"><i class="fa-solid fa-palette"></i> <?= e(__('nav.vibe', 'Vibe')) ?></a>
    <a href="<?= e(vdj_localized_url('/blog')) ?>" class="nav__mobile-link <?= $activeNav==='blog'     ? 'active':'' ?>"><i class="fa-solid fa-pen-nib"></i> <?= e(__('nav.blog', 'Blog')) ?></a>
    <a href="<?= e(vdj_localized_url('/glossary')) ?>" class="nav__mobile-link <?= $activeNav==='glossary' ? 'active':'' ?>"><i class="fa-solid fa-book-open"></i> <?= e(__('nav.glossary', 'Glossary')) ?></a>
    <a href="<?= e(vdj_localized_url('/archive')) ?>" class="nav__mobile-link <?= $activeNav==='archive'  ? 'active':'' ?>"><i class="fa-solid fa-layer-group"></i> <?= e(__('nav.archive', 'Archive')) ?></a>
    <a href="<?= e(vdj_localized_url('/premium')) ?>" class="nav__mobile-link <?= $activeNav==='premium'  ? 'active':'' ?>"><i class="fa-solid fa-crown"></i> <?= e(__('nav.premium', 'Premium')) ?></a>
    <a href="<?= e(vdj_localized_url('/sponsor')) ?>" class="nav__mobile-link <?= $activeNav==='sponsor'  ? 'active':'' ?>"><i class="fa-solid fa-handshake"></i> <?= e(__('nav.partner', 'Partner')) ?></a>
    <a href="<?= e(vdj_localized_url('/submit-source')) ?>" class="nav__mobile-link"><i class="fa-solid fa-bookmark"></i> <?= e(__('nav.submit', 'Submit Reference')) ?></a>
    <?php if ($isLoggedIn): ?>
    <div class="nav__mobile-divider"></div>
    <a href="/profile.php?u=<?= urlencode($currentUser['username'] ?? '') ?>" class="nav__mobile-link"><i class="fa-solid fa-user"></i> <?= e(__('nav.profile', 'My Profile')) ?></a>
    <a href="<?= e(vdj_localized_url('/create')) ?>"    class="nav__mobile-link"><i class="fa-solid fa-plus"></i> <?= e(__('nav.create_board', 'Create Board')) ?></a>
    <a href="<?= e(vdj_localized_url('/saved')) ?>" class="nav__mobile-link"><i class="fa-solid fa-bookmark"></i> <?= e(__('nav.saved', 'Saved Boards')) ?></a>
    <a href="<?= e(vdj_localized_url('/notifications')) ?>" class="nav__mobile-link"><i class="fa-solid fa-bell"></i> <?= e(__('nav.notifications', 'Notifications')) ?><?= $unreadCount > 0 ? " <span class='nav__mobile-badge'>{$unreadCount}</span>" : '' ?></a>
    <a href="<?= e(vdj_localized_url('/settings')) ?>"  class="nav__mobile-link"><i class="fa-solid fa-gear"></i> <?= e(__('nav.settings', 'Settings')) ?></a>
    <?php if (!empty($currentUser['is_admin'])): ?>
    <a href="/admin/"        class="nav__mobile-link"><i class="fa-solid fa-shield-halved"></i> <?= e(__('nav.admin', 'Admin Panel')) ?></a>
    <?php endif; ?>
    <div class="nav__mobile-divider"></div>
    <a href="/logout.php"    class="nav__mobile-link nav__mobile-link--danger"><i class="fa-solid fa-right-from-bracket"></i> <?= e(__('nav.sign_out', 'Sign out')) ?></a>
    <?php else: ?>
    <div class="nav__mobile-divider"></div>
    <a href="<?= e(vdj_localized_url('/login')) ?>"    class="nav__mobile-link"><i class="fa-solid fa-right-to-bracket"></i> <?= e(__('nav.sign_in', 'Sign in')) ?></a>
    <a href="<?= e(vdj_localized_url('/register')) ?>" class="nav__mobile-link"><i class="fa-solid fa-user-plus"></i> <?= e(__('nav.create_account', 'Create Account')) ?></a>
    <?php endif; ?>
  </nav>
</div>
