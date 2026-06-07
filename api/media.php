<?php
/**
 * api/media.php — Lightweight fallback for missing imported WordPress media.
 *
 * Keeps legacy /wp-content/uploads/... image URLs from breaking without storing
 * the full old media library. Existing files are served by Apache before this
 * endpoint; this only handles missing paths.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$path = trim($_GET['path'] ?? '');
$path = str_replace('\\', '/', $path);
$path = preg_replace('#/+#', '/', $path);
$path = ltrim($path, '/');

if ($path === '' || str_contains($path, '..')) {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$localCandidates = [
    $root . '/uploads/' . $path,
    $root . '/wp-content/uploads/' . $path,
];

foreach ($localCandidates as $localFile) {
    if (!is_file($localFile)) continue;
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($localFile) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Content-Length: ' . filesize($localFile));
    readfile($localFile);
    exit;
}

$fileBase = pathinfo($path, PATHINFO_FILENAME);
$label = preg_replace('/[-_]+/', ' ', $fileBase);
$label = trim(preg_replace('/\s+/', ' ', $label));
$label = $label !== '' ? mb_convert_case(mb_substr($label, 0, 58), MB_CASE_TITLE, 'UTF-8') : 'Visual Reference';

$palette = [
    ['#F4F1EC', '#7F77DD', '#111827'],
    ['#F8FAFC', '#0F766E', '#1F2937'],
    ['#FFF7ED', '#D97706', '#111827'],
    ['#F0F9FF', '#2563EB', '#172554'],
    ['#FDF2F8', '#BE185D', '#1F2937'],
];
$pick = $palette[abs(crc32($path)) % count($palette)];
[$bg, $accent, $text] = $pick;

$safeLabel = e($label);
$safePath = e($path);
$cacheDir = dirname(__DIR__) . '/cache/media';
$cacheFile = $cacheDir . '/' . hash('sha256', $path) . '.svg';

if (is_file($cacheFile)) {
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Cache-Control: public, max-age=86400');
    readfile($cacheFile);
    exit;
}

$svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800" role="img" aria-label="{$safeLabel}">
  <rect width="1200" height="800" fill="{$bg}"/>
  <rect x="72" y="72" width="1056" height="656" rx="28" fill="none" stroke="{$accent}" stroke-width="4" opacity=".35"/>
  <circle cx="190" cy="178" r="38" fill="{$accent}" opacity=".24"/>
  <path d="M120 610 L342 388 L486 532 L602 416 L894 708 H120 Z" fill="{$accent}" opacity=".22"/>
  <path d="M602 416 L724 538 L812 452 L1080 720 H462 Z" fill="{$accent}" opacity=".13"/>
  <text x="92" y="120" fill="{$accent}" font-family="Inter, Arial, sans-serif" font-size="28" font-weight="800" letter-spacing="2">VISUAL DESIGN JOURNEY</text>
  <text x="92" y="665" fill="{$text}" font-family="Inter, Arial, sans-serif" font-size="48" font-weight="800">{$safeLabel}</text>
  <text x="92" y="710" fill="{$text}" font-family="Inter, Arial, sans-serif" font-size="22" opacity=".52">Archived reference image placeholder</text>
  <desc>Missing legacy media path: {$safePath}</desc>
</svg>
SVG;

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=86400');
if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
if (is_dir($cacheDir) && is_writable($cacheDir)) @file_put_contents($cacheFile, $svg, LOCK_EX);
echo $svg;
