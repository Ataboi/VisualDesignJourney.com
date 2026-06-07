<?php
/**
 * api/palette.php — Extract dominant colors from a board's cover image (GET)
 * GET: board_id (int)
 * Returns JSON: { colors: ["#RRGGBB", ...] }
 *
 * Strategy: resize image to 10×10 with GD, sample all 100 pixels,
 * group by approximate hue bucket, return top 5 hex values by frequency.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=86400');

session_start_if_not_started();

$boardId = (int)($_GET['board_id'] ?? 0);

if ($boardId <= 0) {
    echo json_encode(['colors' => []]);
    exit;
}

$db = getDB();

// Fetch board cover image
$stmt = $db->prepare('SELECT cover_image FROM boards WHERE id = ? AND is_draft = 0 LIMIT 1');
$stmt->execute([$boardId]);
$board = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$board || empty($board['cover_image'])) {
    echo json_encode(['colors' => []]);
    exit;
}

$coverPath = $board['cover_image'];

/* ── Resolve image path / resource ───────────────────────────── */
$tmpFile = null;

if (str_starts_with($coverPath, 'http://') || str_starts_with($coverPath, 'https://')) {
    // Remote URL — download to a temp file
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 5,
            'user_agent' => 'VisualDesignJourney/1.0 PaletteBot',
        ],
        'https' => [
            'timeout' => 5,
            'user_agent' => 'VisualDesignJourney/1.0 PaletteBot',
        ],
    ]);
    $data = @file_get_contents($coverPath, false, $ctx);
    if ($data === false) {
        echo json_encode(['colors' => []]);
        exit;
    }
    $tmpFile  = tempnam(sys_get_temp_dir(), 'vdj_palette_');
    file_put_contents($tmpFile, $data);
    $filePath = $tmpFile;

    // Derive extension from URL
    $urlPath = parse_url($coverPath, PHP_URL_PATH);
    $ext     = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));
} else {
    // Local file — map from uploads path
    // cover_image may be stored as bare filename or relative path
    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');

    if (str_starts_with($coverPath, '/')) {
        $filePath = $docRoot . $coverPath;
    } else {
        $filePath = $docRoot . '/uploads/' . $coverPath;
    }

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
}

/* ── Load image with GD ───────────────────────────────────────── */
function loadImageGd(string $filePath, string $ext): mixed
{
    if (!extension_loaded('gd')) return false;

    return match ($ext) {
        'jpg', 'jpeg' => @imagecreatefromjpeg($filePath),
        'png'         => @imagecreatefrompng($filePath),
        'webp'        => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($filePath) : false,
        'gif'         => @imagecreatefromgif($filePath),
        default       => false,
    };
}

$src = loadImageGd($filePath, $ext);

// Clean up temp file immediately after loading
if ($tmpFile !== null && file_exists($tmpFile)) {
    @unlink($tmpFile);
}

if ($src === false || $src === null) {
    echo json_encode(['colors' => []]);
    exit;
}

/* ── Resize to 10×10 and sample pixels ───────────────────────── */
$thumb = imagecreatetruecolor(10, 10);
if ($thumb === false) {
    imagedestroy($src);
    echo json_encode(['colors' => []]);
    exit;
}

// Preserve alpha for PNG
imagealphablending($thumb, false);
imagesavealpha($thumb, true);

$w = imagesx($src);
$h = imagesy($src);

imagecopyresampled($thumb, $src, 0, 0, 0, 0, 10, 10, $w, $h);
imagedestroy($src);

/* ── Collect pixel colours ────────────────────────────────────── */
$pixelColors = [];
for ($y = 0; $y < 10; $y++) {
    for ($x = 0; $x < 10; $x++) {
        $rgb   = imagecolorat($thumb, $x, $y);
        $alpha = ($rgb >> 24) & 0x7F;
        if ($alpha > 100) continue; // mostly transparent, skip

        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8)  & 0xFF;
        $b =  $rgb        & 0xFF;

        // Skip near-white and near-black (boring neutrals)
        $brightness = ($r + $g + $b) / 3;
        if ($brightness > 240 || $brightness < 15) continue;

        // Quantise to reduce noise: round each channel to nearest 32
        $rq = (int)(round($r / 32) * 32);
        $gq = (int)(round($g / 32) * 32);
        $bq = (int)(round($b / 32) * 32);

        $key = sprintf('%02x%02x%02x', min($rq, 255), min($gq, 255), min($bq, 255));
        $pixelColors[$key] = ($pixelColors[$key] ?? 0) + 1;
    }
}

imagedestroy($thumb);

if (empty($pixelColors)) {
    echo json_encode(['colors' => []]);
    exit;
}

// Sort by frequency desc, take top 5
arsort($pixelColors);
$top5 = array_slice(array_keys($pixelColors), 0, 5);

$colors = array_map(fn($hex) => '#' . strtoupper($hex), $top5);

echo json_encode(['colors' => $colors]);
