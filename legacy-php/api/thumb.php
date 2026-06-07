<?php
/**
 * api/thumb.php — Cached local thumbnail renderer for blog and board media.
 * Only local /uploads and imported wp-content/uploads files are resized.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$src = trim($_GET['src'] ?? '');
$w = max(80, min(1600, (int)($_GET['w'] ?? 480)));
$h = max(80, min(1600, (int)($_GET['h'] ?? 270)));
$fit = ($_GET['fit'] ?? 'cover') === 'contain' ? 'contain' : 'cover';

$sourcePath = localMediaPath($src);
if (!$sourcePath || !extension_loaded('gd')) {
    $fallback = blogImageUrl($src);
    if ($fallback !== '') {
        header('Location: ' . $fallback, true, 302);
        exit;
    }
    http_response_code(404);
    exit;
}

$info = @getimagesize($sourcePath);
if (!$info) {
    http_response_code(404);
    exit;
}

[$sourceW, $sourceH] = $info;
$mime = $info['mime'] ?? '';
$cacheDir = dirname(__DIR__) . '/cache/thumbs';
if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);

$format = function_exists('imagewebp') ? 'webp' : 'jpg';
$cacheKey = hash('sha256', $sourcePath . '|' . filemtime($sourcePath) . "|$w|$h|$fit|$format");
$cacheFile = $cacheDir . '/' . $cacheKey . '.' . $format;

if (is_file($cacheFile)) {
    header('Content-Type: image/' . ($format === 'jpg' ? 'jpeg' : 'webp'));
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Content-Length: ' . filesize($cacheFile));
    readfile($cacheFile);
    exit;
}

$source = match ($mime) {
    'image/jpeg' => @imagecreatefromjpeg($sourcePath),
    'image/png' => @imagecreatefrompng($sourcePath),
    'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : null,
    default => null,
};
if (!$source) {
    header('Location: ' . blogImageUrl($src), true, 302);
    exit;
}

$dest = imagecreatetruecolor($w, $h);
imagealphablending($dest, true);
imagesavealpha($dest, true);
$bg = imagecolorallocate($dest, 248, 249, 250);
imagefill($dest, 0, 0, $bg);

if ($fit === 'contain') {
    $scale = min($w / $sourceW, $h / $sourceH);
    $newW = (int)round($sourceW * $scale);
    $newH = (int)round($sourceH * $scale);
    $dstX = (int)floor(($w - $newW) / 2);
    $dstY = (int)floor(($h - $newH) / 2);
    imagecopyresampled($dest, $source, $dstX, $dstY, 0, 0, $newW, $newH, $sourceW, $sourceH);
} else {
    $scale = max($w / $sourceW, $h / $sourceH);
    $cropW = (int)round($w / $scale);
    $cropH = (int)round($h / $scale);
    $srcX = (int)max(0, floor(($sourceW - $cropW) / 2));
    $srcY = (int)max(0, floor(($sourceH - $cropH) / 2));
    imagecopyresampled($dest, $source, 0, 0, $srcX, $srcY, $w, $h, $cropW, $cropH);
}

if ($format === 'webp') {
    imagewebp($dest, $cacheFile, 82);
} else {
    imagejpeg($dest, $cacheFile, 84);
}
imagedestroy($source);
imagedestroy($dest);

header('Content-Type: image/' . ($format === 'jpg' ? 'jpeg' : 'webp'));
header('Cache-Control: public, max-age=31536000, immutable');
header('Content-Length: ' . filesize($cacheFile));
readfile($cacheFile);
