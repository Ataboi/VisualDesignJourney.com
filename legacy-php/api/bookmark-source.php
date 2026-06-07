<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/source_discovery.php';

session_start_if_not_started();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'invalid_csrf']);
    exit;
}

$url = trim($_POST['url'] ?? '');
$tags = trim($_POST['tags'] ?? '');

if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Enter a valid http/https URL.']);
    exit;
}

// Auto-style tag matching based on URL + title keywords
function suggestStyleTags(string $url, string $title, string $desc, string $userTags): string {
    $text = strtolower($url . ' ' . $title . ' ' . $desc);
    $map  = [
        'Minimal'      => ['minimal', 'minimalist', 'clean', 'whitespace', 'simple'],
        'Bauhaus'      => ['bauhaus', 'constructivist', 'geometric', 'modernist'],
        'Y2K'          => ['y2k', 'cyber', 'chrome', 'holographic', 'pixel', 'retro-digital'],
        'Art Nouveau'  => ['art nouveau', 'organic', 'ornamental', 'floral', 'vine'],
        'Brutalist'    => ['brutalist', 'brutalism', 'raw', 'concrete', 'exposed'],
        'Dark Academia' => ['dark academia', 'gothic', 'moody', 'vintage library', 'classical'],
        'Japandi'      => ['japandi', 'wabi-sabi', 'zen', 'nordic', 'scandinavian', 'japanese'],
        'Maximalist'   => ['maximalist', 'maximalism', 'bold pattern', 'eclectic', 'layered'],
        'Retro'        => ['retro', 'vintage', '70s', '80s', '90s', 'nostalgia'],
        'Swiss'        => ['swiss', 'international style', 'grid system', 'typography'],
    ];
    $found = [];
    foreach ($map as $tag => $keywords) {
        foreach ($keywords as $kw) {
            if (str_contains($text, $kw)) { $found[] = $tag; break; }
        }
    }
    // Merge with user-supplied tags, deduplicate
    $user = array_filter(array_map('trim', explode(',', $userTags)));
    return implode(', ', array_unique(array_merge($user, $found)));
}

try {
    $db = getDB();
    vdj_ensure_source_discovery_schema($db);
    $meta = vdj_extract_source_meta($url);
    $resolvedTags = suggestStyleTags($url, $meta['title'] ?? '', $meta['description'] ?? '', $tags);
    vdj_queue_source($db, $meta, currentUserId(), $resolvedTags);
    echo json_encode(['ok' => true, 'message' => 'Reference added to the moderation queue.']);
} catch (Throwable $e) {
    error_log('bookmark-source failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not queue this reference.']);
}
