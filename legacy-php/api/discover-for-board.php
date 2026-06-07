<?php
/**
 * api/discover-for-board.php
 * Board-aware discovery: searches licensed API sources using board title/tags
 * and queues results into source_bookmarks for admin review.
 *
 * POST: board_id, csrf_token, sources[] (wikimedia|met|chicago)
 * Returns JSON: {ok, queued, skipped, items:[{title,image_url,source_url,platform}]}
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/source_discovery.php';

session_start_if_not_started();
header('Content-Type: application/json');

// Auth: admin only
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'login_required']);
    exit;
}
$db = getDB();
$user = getUserById($db, $_SESSION['user_id']);
if (empty($user['is_admin'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

// CSRF
$csrfToken = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'invalid_csrf']);
    exit;
}

$boardId = (int)($_POST['board_id'] ?? 0);
if ($boardId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'invalid_board_id']);
    exit;
}

$board = $db->prepare('SELECT * FROM boards WHERE id = ? LIMIT 1');
$board->execute([$boardId]);
$board = $board->fetch();
if (!$board) {
    echo json_encode(['ok' => false, 'error' => 'board_not_found']);
    exit;
}

$enabledSources = $_POST['sources'] ?? ['wikimedia', 'met', 'chicago'];
if (!is_array($enabledSources)) $enabledSources = ['wikimedia'];

// Build queries from board data
$queries = vdj_build_board_queries($board);
if (empty($queries)) {
    echo json_encode(['ok' => false, 'error' => 'no_queries_built', 'board' => $board['title']]);
    exit;
}

// Run searches
$allResults = [];
$limit      = 10; // per source per query

foreach ($queries as $query) {
    if (in_array('wikimedia', $enabledSources)) {
        foreach (vdj_search_wikimedia($query, $limit) as $r) {
            $r['query'] = $query;
            $allResults[] = $r;
        }
    }
    if (in_array('met', $enabledSources)) {
        foreach (vdj_search_met_museum($query, min($limit, 5)) as $r) {
            $r['query'] = $query;
            $allResults[] = $r;
        }
    }
    if (in_array('chicago', $enabledSources)) {
        foreach (vdj_search_chicago_art($query, min($limit, 5)) as $r) {
            $r['query'] = $query;
            $allResults[] = $r;
        }
    }
}

// Deduplicate by image_url
$seen    = [];
$unique  = [];
foreach ($allResults as $r) {
    $key = md5($r['image_url']);
    if (!isset($seen[$key]) && !empty($r['image_url'])) {
        $seen[$key] = true;
        $unique[]   = $r;
    }
}

// Queue into source_bookmarks
vdj_ensure_source_discovery_schema($db);
$queued  = 0;
$skipped = 0;
$items   = [];

foreach ($unique as $r) {
    $tags = implode(', ', array_filter([
        $board['style_tags'] ?? '',
        $r['license'] ?? '',
    ]));
    $meta = [
        'source_url'      => $r['source_url'],
        'source_platform' => $r['platform'],
        'title'           => mb_substr($r['title'], 0, 255),
        'description'     => mb_substr($r['description'], 0, 500),
        'image_url'       => mb_substr($r['image_url'], 0, 1000),
    ];
    $ok = vdj_queue_source($db, $meta, $user['id'], $tags);
    if ($ok) {
        // Tag with suggested board_id
        try {
            $db->prepare('UPDATE source_bookmarks SET board_id = ? WHERE source_url = ? AND board_id IS NULL')
               ->execute([$boardId, $r['source_url']]);
        } catch (Throwable $e) {}
        $queued++;
        $items[] = [
            'title'      => $r['title'],
            'image_url'  => $r['image_url'],
            'source_url' => $r['source_url'],
            'platform'   => $r['platform'],
            'query'      => $r['query'],
        ];
    } else {
        $skipped++;
    }
}

echo json_encode([
    'ok'      => true,
    'queued'  => $queued,
    'skipped' => $skipped,
    'queries' => $queries,
    'items'   => array_slice($items, 0, 30),
]);
