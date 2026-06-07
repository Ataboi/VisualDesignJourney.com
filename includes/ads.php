<?php
/**
 * includes/ads.php — Adsterra ad unit renderer
 *
 * Usage: call vdj_ad(string $slot) anywhere in a page after this file is included.
 * Ads are shown only when ADSTERRA_ENABLED is true (set in config/ads.local.php).
 * On pages where $noAds = true the helper returns empty string.
 *
 * Slots:
 *   banner_300x250   — Rectangle, best below fold / sidebar
 *   leaderboard      — 728×90, desktop header/footer
 *   banner_468x60    — Standard banner, below nav or between sections
 *   skyscraper       — 160×600, sidebar (desktop only)
 *   mobile_320x50    — Mobile banner, sticky bottom bar area
 *   rectangle_small  — 160×300, sidebar / in-content
 *   social_bar       — Adsterra Social Bar (full-page-width, non-intrusive)
 */

// ── Load local config (optional — silently skip if missing) ──────────────────
$_adsLocalCfg = __DIR__ . '/../config/ads.local.php';
if (is_file($_adsLocalCfg) && !defined('ADSTERRA_ENABLED')) {
    require_once $_adsLocalCfg;
}

if (!defined('ADSTERRA_ENABLED')) define('ADSTERRA_ENABLED', true);

/**
 * Returns the HTML for an Adsterra ad slot.
 * Returns empty string when ads are disabled or $noAds is true.
 */
function vdj_ad(string $slot, string $class = ''): string {
    // Check page-level opt-out (set $noAds = true before including header.php)
    global $noAds;
    if (!empty($noAds))          return '';
    if (!ADSTERRA_ENABLED)       return '';

    // Adsterra invoke.js calls — each key is a unique ad zone
    $units = [

        // 300×250 — medium rectangle (best for sidebar / below fold)
        'banner_300x250' => [
            'key'    => 'e6b14c0b1e23d50b7cc854ab945d75d2',
            'w'      => 300,
            'h'      => 250,
            'format' => 'iframe',
        ],

        // 728×90 — leaderboard (desktop header / footer)
        'leaderboard' => [
            'key'    => '3b7ca9fe5e27e40425e7d54b722e0c9b',
            'w'      => 728,
            'h'      => 90,
            'format' => 'iframe',
        ],

        // 468×60 — standard banner (between sections)
        'banner_468x60' => [
            'key'    => 'c9ab950e78bcfd111d5ef395b7c3db32',
            'w'      => 468,
            'h'      => 60,
            'format' => 'iframe',
        ],

        // 160×600 — wide skyscraper (desktop sidebar)
        'skyscraper' => [
            'key'    => 'b8a76cdfb00943ce4df530532324a570',
            'w'      => 160,
            'h'      => 600,
            'format' => 'iframe',
        ],

        // 320×50 — mobile banner (below header / sticky bottom)
        'mobile_320x50' => [
            'key'    => '70a277bc726718d70c4c9c357bb406e4',
            'w'      => 320,
            'h'      => 50,
            'format' => 'iframe',
        ],

        // 160×300 — half-page rectangle (sidebar / in-content)
        'rectangle_small' => [
            'key'    => 'd4e1d4d09336216b9e711a6281ef7039',
            'w'      => 160,
            'h'      => 300,
            'format' => 'iframe',
        ],

        // Social Bar — full-width, loads as div container
        'social_bar' => [
            'key'    => '432d607d772e51993d224923217541cc',
            'format' => 'social_bar',
        ],
    ];

    if (!isset($units[$slot])) return '<!-- vdj_ad: unknown slot "' . htmlspecialchars($slot) . '" -->';

    $u = $units[$slot];
    $wrapClass = 'vdj-ad vdj-ad--' . $slot . ($class ? ' ' . htmlspecialchars($class) : '');

    // Social Bar: uses div container pattern
    if ($u['format'] === 'social_bar') {
        return <<<HTML
<div class="{$wrapClass}" aria-hidden="true">
  <script async="async" data-cfasync="false"
          src="https://acceptancesuicidegel.com/{$u['key']}/invoke.js"></script>
  <div id="container-{$u['key']}"></div>
</div>
HTML;
    }

    // Standard iframe units
    $key    = $u['key'];
    $w      = (int)$u['w'];
    $h      = (int)$u['h'];
    $format = htmlspecialchars($u['format']);

    // Responsive wrapper — centers the unit, hides on screens narrower than the ad
    $minScreen = $w >= 468 ? $w . 'px' : '0'; // leaderboard/banner hidden on mobile via CSS
    $hideClass = $w >= 468 ? ' vdj-ad--desktop-only' : '';
    if ($w <= 320) $hideClass = ' vdj-ad--mobile-only';

    return <<<HTML
<div class="{$wrapClass}{$hideClass}" style="text-align:center;overflow:hidden;max-width:100%;" aria-hidden="true">
  <script>
    atOptions = {
      'key':    '{$key}',
      'format': '{$format}',
      'height': {$h},
      'width':  {$w},
      'params': {}
    };
  </script>
  <script src="https://acceptancesuicidegel.com/{$key}/invoke.js"></script>
</div>
HTML;
}
