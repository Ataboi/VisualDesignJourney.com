<?php
// FILE: admin/discovery.php
// Discovery hub — 4-tab admin UI: Queue | Board Scanner | Sources & Feeds | History
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../includes/source_discovery.php';

$currentPage = 'discovery';
$flash = '';
$error  = '';

/* ══════════════════════════════════════════════════════════════════════════
   POST handlers (redirect-after-POST pattern)
═══════════════════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    /* ── approve ── */
    if ($action === 'approve' && $id > 0) {
        $boardId = (int)($_POST['board_id'] ?? 0);
        if ($boardId <= 0) {
            $error = 'Choose a board before approving.';
        } else {
            try {
                $stmt = $db->prepare('SELECT * FROM source_bookmarks WHERE id = ? LIMIT 1');
                $stmt->execute([$id]);
                $src = $stmt->fetch();
                if (!$src || empty($src['image_url'])) {
                    $error = 'This source has no preview image.';
                } else {
                    $maxOrder = $db->prepare('SELECT COALESCE(MAX(sort_order), -1) FROM board_images WHERE board_id = ?');
                    $maxOrder->execute([$boardId]);
                    $order = (int)$maxOrder->fetchColumn() + 1;
                    try {
                        $imgStmt = $db->prepare(
                            'INSERT INTO board_images (board_id, image_url, sort_order, source_url, source_platform, credit, caption)
                             VALUES (?, ?, ?, ?, ?, ?, ?)'
                        );
                        $imgStmt->execute([
                            $boardId,
                            $src['image_url'],
                            $order,
                            $src['source_url'],
                            $src['source_platform'],
                            $src['source_platform'],
                            $src['title'],
                        ]);
                    } catch (Throwable $e2) {
                        $imgStmt = $db->prepare('INSERT INTO board_images (board_id, image_url, sort_order) VALUES (?, ?, ?)');
                        $imgStmt->execute([$boardId, $src['image_url'], $order]);
                    }
                    $db->prepare('UPDATE boards SET cover_image = COALESCE(cover_image, ?) WHERE id = ?')->execute([$src['image_url'], $boardId]);
                    $db->prepare('UPDATE source_bookmarks SET status="approved", board_id=?, reviewed_at=NOW() WHERE id=?')->execute([$boardId, $id]);
                    $flash = 'Source approved and added to board.';
                }
            } catch (Throwable $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }

    /* ── reject ── */
    if ($action === 'reject' && $id > 0) {
        try {
            $db->prepare('UPDATE source_bookmarks SET status="rejected", reviewed_at=NOW() WHERE id=?')->execute([$id]);
            $flash = 'Source rejected.';
        } catch (Throwable $e) { $error = $e->getMessage(); }
    }

    /* ── batch reject low-score (score <= 4) ── */
    if ($action === 'batch_reject_low') {
        try {
            // We'll compute and reject in PHP since score is computed, not stored
            $pending = $db->query('SELECT id, source_platform, title, description, image_url, suggested_tags, source_url FROM source_bookmarks WHERE status="pending"')->fetchAll();
            $knownStyleTags   = ['Minimal','Bauhaus','Y2K','Swiss','Brutalist','Art Deco','Grunge','Retro','Futurist','Organic'];
            $lowQualityDomains = ['tumblr.com','pinterest.com','reddit.com'];
            $lowIds = [];
            foreach ($pending as $row) {
                $score = 0;
                if (!empty($row['source_platform'])) $score += 3;
                if (!empty($row['title']) && mb_strlen($row['title']) > 10) $score += 2;
                if (!empty($row['description']) && mb_strlen($row['description']) > 20) $score += 2;
                if (!empty($row['image_url'])) $score += 2;
                if (!empty($row['suggested_tags'])) {
                    $score += 1;
                    $rowTags = array_map('trim', explode(',', $row['suggested_tags']));
                    $styleMatches = 0;
                    foreach ($rowTags as $rt) {
                        foreach ($knownStyleTags as $st) {
                            if (stripos($rt, $st) !== false) { $styleMatches++; break; }
                        }
                        if ($styleMatches >= 3) break;
                    }
                    $score += $styleMatches;
                }
                if (!empty($row['source_url'])) {
                    foreach ($lowQualityDomains as $domain) {
                        if (stripos($row['source_url'], $domain) !== false) { $score -= 2; break; }
                    }
                }
                $score = max(0, min(10, $score));
                if ($score <= 4) $lowIds[] = (int)$row['id'];
            }
            if ($lowIds) {
                $placeholders = implode(',', array_fill(0, count($lowIds), '?'));
                $db->prepare("UPDATE source_bookmarks SET status='rejected', reviewed_at=NOW() WHERE id IN ($placeholders)")->execute($lowIds);
                $flash = count($lowIds) . ' low-score source(s) rejected.';
            } else {
                $flash = 'No low-score sources to reject.';
            }
        } catch (Throwable $e) { $error = $e->getMessage(); }
    }

    /* ── add_feed ── */
    if ($action === 'add_feed') {
        $name    = trim($_POST['name'] ?? '');
        $feedUrl = trim($_POST['feed_url'] ?? '');
        $tags    = trim($_POST['tags'] ?? '');
        if ($name !== '' && filter_var($feedUrl, FILTER_VALIDATE_URL)) {
            try {
                $stmt = $db->prepare(
                    'INSERT INTO source_feeds (name, feed_url, tags) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE name=VALUES(name), tags=VALUES(tags), is_active=1'
                );
                $stmt->execute([$name, $feedUrl, $tags]);
                $flash = 'Feed saved.';
            } catch (Throwable $e) { $error = $e->getMessage(); }
        } else {
            $error = 'Enter a feed name and valid URL.';
        }
    }

    /* ── toggle_feed ── */
    if ($action === 'toggle_feed' && $id > 0) {
        try {
            $db->prepare('UPDATE source_feeds SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
            $flash = 'Feed updated.';
        } catch (Throwable $e) { $error = $e->getMessage(); }
    }

    /* ── delete_feed ── */
    if ($action === 'delete_feed' && $id > 0) {
        try {
            $db->prepare('DELETE FROM source_feeds WHERE id = ?')->execute([$id]);
            $flash = 'Feed deleted.';
        } catch (Throwable $e) { $error = $e->getMessage(); }
    }

    /* ── requeue ── */
    if ($action === 'requeue' && $id > 0) {
        try {
            $db->prepare("UPDATE source_bookmarks SET status='pending', reviewed_at=NULL WHERE id=?")->execute([$id]);
            $flash = 'Item re-queued for review.';
        } catch (Throwable $e) { $error = $e->getMessage(); }
    }

    if ($flash || $error) {
        $qs = $error ? '?error=' . urlencode($error) : '?flash=' . urlencode($flash);
        $tab = $_POST['_tab'] ?? 'queue';
        header('Location: /admin/discovery.php' . $qs . '#' . $tab);
        exit;
    }
}

/* ══════════════════════════════════════════════════════════════════════════
   Flash / error from redirect
═══════════════════════════════════════════════════════════════════════════ */
if (empty($flash) && !empty($_GET['flash'])) $flash = $_GET['flash'];
if (empty($error) && !empty($_GET['error'])) $error = $_GET['error'];

/* ══════════════════════════════════════════════════════════════════════════
   Data loading
═══════════════════════════════════════════════════════════════════════════ */
$ready   = false;
$sources = []; // pending
$feeds   = [];
$boards  = [];
$approved = [];
$rejected = [];
$statsBar = ['pending' => 0, 'approved_today' => 0, 'rejected_total' => 0];

$knownStyleTags    = ['Minimal','Bauhaus','Y2K','Swiss','Brutalist','Art Deco','Grunge','Retro','Futurist','Organic'];
$lowQualityDomains = ['tumblr.com','pinterest.com','reddit.com'];

function computeScore(array $row, array $knownStyleTags, array $lowQualityDomains): int {
    $score = 0;
    if (!empty($row['source_platform'])) $score += 3;
    if (!empty($row['title']) && mb_strlen($row['title']) > 10) $score += 2;
    if (!empty($row['description']) && mb_strlen($row['description']) > 20) $score += 2;
    if (!empty($row['image_url'])) $score += 2;
    if (!empty($row['suggested_tags'])) {
        $score += 1;
        $rowTags = array_map('trim', explode(',', $row['suggested_tags']));
        $styleMatches = 0;
        foreach ($rowTags as $rt) {
            foreach ($knownStyleTags as $st) {
                if (stripos($rt, $st) !== false) { $styleMatches++; break; }
            }
            if ($styleMatches >= 3) break;
        }
        $score += $styleMatches;
    }
    if (!empty($row['source_url'])) {
        foreach ($lowQualityDomains as $domain) {
            if (stripos($row['source_url'], $domain) !== false) { $score -= 2; break; }
        }
    }
    return max(0, min(10, $score));
}

try {
    vdj_ensure_source_discovery_schema($db);
    $db->query('SELECT 1 FROM source_bookmarks LIMIT 1');
    $ready = true;

    // Pending sources
    $rawPending = $db->query(
        'SELECT * FROM source_bookmarks WHERE status="pending" ORDER BY created_at DESC LIMIT 120'
    )->fetchAll();
    foreach ($rawPending as &$row) {
        $row['score'] = computeScore($row, $knownStyleTags, $lowQualityDomains);
    }
    unset($row);
    usort($rawPending, fn($a, $b) => $b['score'] <=> $a['score']);
    $sources = $rawPending;

    // Stats
    $statsBar['pending'] = count($sources);
    $statsBar['approved_today'] = (int)$db->query(
        'SELECT COUNT(*) FROM source_bookmarks WHERE status="approved" AND DATE(reviewed_at)=CURDATE()'
    )->fetchColumn();
    $statsBar['rejected_total'] = (int)$db->query(
        'SELECT COUNT(*) FROM source_bookmarks WHERE status="rejected"'
    )->fetchColumn();

    // Feeds
    $feeds = $db->query('SELECT * FROM source_feeds ORDER BY created_at DESC LIMIT 50')->fetchAll();

    // Boards (non-draft)
    $boards = $db->query(
        'SELECT b.id, b.title, b.style_tags, u.username
         FROM boards b JOIN users u ON u.id=b.user_id
         WHERE b.is_draft = 0
         ORDER BY b.created_at DESC LIMIT 300'
    )->fetchAll();

    // History
    $approved = $db->query(
        'SELECT sb.*, b.title AS board_title
         FROM source_bookmarks sb
         LEFT JOIN boards b ON b.id = sb.board_id
         WHERE sb.status="approved"
         ORDER BY sb.reviewed_at DESC LIMIT 100'
    )->fetchAll();
    $rejected = $db->query(
        'SELECT * FROM source_bookmarks WHERE status="rejected" ORDER BY reviewed_at DESC LIMIT 100'
    )->fetchAll();

} catch (Throwable $e) { /* tables may not exist yet */ }

/* ══════════════════════════════════════════════════════════════════════════
   Helpers
═══════════════════════════════════════════════════════════════════════════ */
function scoreBadge(int $score): array {
    if ($score >= 8) return ['High',   '#22c55e', 'high'];
    if ($score >= 5) return ['Good',   '#f59e0b', 'good'];
    return              ['Low',    '#94a3b8', 'low'];
}
function platformIcon(string $platform): string {
    $map = [
        'wikimedia'  => 'fa-brands fa-wikipedia-w',
        'met'        => 'fa-solid fa-landmark',
        'chicago'    => 'fa-solid fa-building-columns',
        'rss'        => 'fa-solid fa-rss',
        'manual'     => 'fa-solid fa-hand-point-up',
    ];
    foreach ($map as $key => $icon) {
        if (stripos($platform, $key) !== false) return $icon;
    }
    return 'fa-solid fa-globe';
}
function licenseBadge(string $desc): string {
    if (stripos($desc, 'public domain') !== false) return '<span class="disc-license-badge pd">Public Domain</span>';
    if (preg_match('/cc[\s\-]?by/i', $desc)) return '<span class="disc-license-badge cc">CC-BY</span>';
    if (stripos($desc, 'cc0') !== false) return '<span class="disc-license-badge cc">CC0</span>';
    if (stripos($desc, 'creative commons') !== false) return '<span class="disc-license-badge cc">CC</span>';
    return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discovery — Admin — Visual Design Journey</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/admin/admin.css">
    <style>
/* ── Discovery page custom styles ─────────────────────────────────────── */

/* Tab nav */
.disc-tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid var(--border);
    margin-bottom: 24px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.disc-tab-btn {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-muted);
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    cursor: pointer;
    white-space: nowrap;
    transition: color .15s, border-color .15s;
}
.disc-tab-btn:hover { color: var(--text); }
.disc-tab-btn[aria-selected="true"] {
    color: var(--accent);
    border-bottom-color: var(--accent);
}
.disc-tab-btn .badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 18px;
    padding: 0 5px;
    border-radius: 99px;
    background: var(--accent-light);
    color: var(--accent);
    font-size: 11px;
    font-weight: 700;
}
.disc-tab-panel { display: none; }
.disc-tab-panel.active { display: block; }

/* Stats bar */
.disc-stats-bar {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.disc-stat {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 10px 16px;
    font-size: 13px;
    box-shadow: var(--shadow);
}
.disc-stat strong {
    font-family: 'Manrope', sans-serif;
    font-size: 20px;
    font-weight: 700;
    color: var(--text);
}
.disc-stat span { color: var(--text-muted); }
.disc-stat i { color: var(--accent); font-size: 15px; }

/* Filter row */
.disc-filter-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
    margin-bottom: 20px;
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 12px 16px;
    box-shadow: var(--shadow);
}
.disc-filter-row select,
.disc-filter-row input[type="search"] {
    height: 34px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--bg);
    color: var(--text);
    padding: 0 10px;
    font-size: 13px;
    transition: border-color .15s;
}
.disc-filter-row select:focus,
.disc-filter-row input:focus {
    outline: none;
    border-color: var(--accent);
}
.disc-filter-row input[type="search"] { flex: 1; min-width: 160px; }
.disc-filter-row label { font-size: 13px; color: var(--text-muted); font-weight: 500; }

/* Batch bar */
.disc-batch-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: var(--warning-light);
    border: 1px solid var(--warning);
    border-radius: var(--radius-sm);
    padding: 10px 16px;
    margin-bottom: 16px;
    font-size: 13px;
}
.disc-batch-bar.hidden { display: none; }

/* Source card grid */
.disc-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}
@media (max-width: 1200px) { .disc-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 900px)  { .disc-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 580px)  { .disc-grid { grid-template-columns: 1fr; } }

.source-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: var(--shadow);
    transition: box-shadow .15s, transform .15s;
}
.source-card:hover { box-shadow: var(--shadow-md); transform: translateY(-1px); }
.source-card.filtered-out { display: none; }

.source-card__img-wrap {
    position: relative;
    height: 160px;
    background: #f1f5f9;
    cursor: pointer;
    overflow: hidden;
    flex-shrink: 0;
}
.source-card__img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .25s ease;
}
.source-card__img-wrap:hover img { transform: scale(1.04); }
.source-card__img-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    font-size: 28px;
}
.source-card__img-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,.35);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 22px;
    opacity: 0;
    transition: opacity .2s;
}
.source-card__img-wrap:hover .source-card__img-overlay { opacity: 1; }

.source-card__body {
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
}

.source-card__badges {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.disc-score-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
    flex-shrink: 0;
}
.disc-platform-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 7px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 600;
    background: var(--accent-light);
    color: var(--accent);
    white-space: nowrap;
}
.disc-license-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 7px;
    border-radius: 99px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .3px;
    text-transform: uppercase;
}
.disc-license-badge.pd {
    background: rgba(34,197,94,.12);
    color: #16a34a;
}
.disc-license-badge.cc {
    background: rgba(245,158,11,.12);
    color: #b45309;
}

.source-card__title {
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.source-card__desc {
    font-size: 12px;
    color: var(--text-muted);
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.source-card__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}
.source-card__tag {
    font-size: 10px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 99px;
    background: var(--bg);
    color: var(--text-muted);
    border: 1px solid var(--border);
}

.source-card__actions {
    margin-top: auto;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.source-card__approve-row {
    display: flex;
    gap: 6px;
    align-items: center;
}
.source-card__approve-row select {
    flex: 1;
    height: 30px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 12px;
    padding: 0 6px;
    background: var(--bg);
    color: var(--text);
}
.source-card__approve-row select:focus { outline: none; border-color: var(--accent); }

/* Buttons */
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 0 14px; height: 30px; border: none; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s, opacity .15s; white-space: nowrap; text-decoration: none; }
.btn-sm { height: 26px; padding: 0 10px; font-size: 12px; }
.btn-accent { background: var(--accent); color: #fff; }
.btn-accent:hover { background: var(--accent-hover); }
.btn-danger { background: var(--danger); color: #fff; }
.btn-danger:hover { background: #dc2626; }
.btn-ghost { background: transparent; color: var(--text-muted); border: 1px solid var(--border); }
.btn-ghost:hover { background: var(--bg); color: var(--text); }
.btn-warning { background: var(--warning); color: #fff; }
.btn-warning:hover { background: #d97706; }
.btn-success { background: var(--success); color: #fff; }
.btn-success:hover { background: #16a34a; }

/* ── Lightbox ───────────────────────────────────────────────────────────── */
#disc-lightbox {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9000;
    background: rgba(0,0,0,.82);
    align-items: center;
    justify-content: center;
    padding: 20px;
}
#disc-lightbox.open { display: flex; }
.disc-lightbox__inner {
    background: var(--white);
    border-radius: var(--radius);
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0,0,0,.4);
}
.disc-lightbox__top {
    position: relative;
    background: #111;
    border-radius: var(--radius) var(--radius) 0 0;
    overflow: hidden;
    max-height: 440px;
}
.disc-lightbox__top img {
    width: 100%;
    max-height: 440px;
    object-fit: contain;
    display: block;
}
.disc-lightbox__close {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(0,0,0,.5);
    color: #fff;
    border: none;
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .15s;
}
.disc-lightbox__close:hover { background: rgba(0,0,0,.8); }
.disc-lightbox__meta {
    padding: 20px 24px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.disc-lightbox__title {
    font-family: 'Manrope', sans-serif;
    font-size: 18px;
    font-weight: 700;
    color: var(--text);
}
.disc-lightbox__desc {
    font-size: 13px;
    color: var(--text-muted);
    line-height: 1.6;
}
.disc-lightbox__source-link {
    font-size: 12px;
    color: var(--accent);
    word-break: break-all;
    text-decoration: none;
}
.disc-lightbox__source-link:hover { text-decoration: underline; }
.disc-lightbox__actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}
.disc-lightbox__actions select {
    height: 34px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 13px;
    padding: 0 8px;
    background: var(--bg);
    color: var(--text);
    flex: 1;
    min-width: 160px;
}

/* ── Board Scanner tab ──────────────────────────────────────────────────── */
.disc-scanner-wrap {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 28px;
    max-width: 680px;
}
.disc-scanner-wrap h2 {
    font-family: 'Manrope', sans-serif;
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 6px;
}
.disc-scanner-wrap p.hint {
    font-size: 13px;
    color: var(--text-muted);
    margin-bottom: 20px;
    line-height: 1.6;
}
.disc-field { display: flex; flex-direction: column; gap: 5px; margin-bottom: 14px; }
.disc-field label { font-size: 13px; font-weight: 600; color: var(--text); }
.disc-field select, .disc-field input[type="text"] {
    height: 38px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 0 12px;
    font-size: 14px;
    font-family: inherit;
    background: var(--bg);
    color: var(--text);
    transition: border-color .15s;
}
.disc-field select:focus, .disc-field input:focus { outline: none; border-color: var(--accent); }
.disc-checkboxes { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
.disc-checkbox-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: border-color .15s, background .15s;
    font-size: 13px;
}
.disc-checkbox-row:hover { border-color: var(--accent); background: var(--accent-light); }
.disc-checkbox-row input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--accent); cursor: pointer; }
.disc-checkbox-row .cb-label { flex: 1; font-weight: 500; }
.disc-checkbox-row .cb-badge {
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 99px;
    background: var(--success-light);
    color: #15803d;
    font-weight: 600;
}

/* Scanner results */
#scanner-results { margin-top: 24px; }
.scanner-spinner { display: none; text-align: center; padding: 32px; color: var(--text-muted); }
.scanner-spinner i { font-size: 28px; color: var(--accent); animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.scanner-queries { font-size: 12px; color: var(--text-muted); margin-bottom: 14px; }
.scanner-queries span {
    display: inline-block;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 2px 8px;
    margin: 2px 3px 2px 0;
    font-size: 12px;
}
.scanner-success-msg {
    background: var(--success-light);
    border: 1px solid var(--success);
    border-radius: var(--radius-sm);
    padding: 12px 16px;
    font-size: 13px;
    color: #15803d;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.scanner-error-msg {
    background: var(--danger-light);
    border: 1px solid var(--danger);
    border-radius: var(--radius-sm);
    padding: 12px 16px;
    font-size: 13px;
    color: var(--danger);
    margin-bottom: 16px;
}
.scanner-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 10px;
}
.scanner-item {
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    overflow: hidden;
    background: var(--white);
}
.scanner-item img {
    width: 100%;
    height: 110px;
    object-fit: cover;
    display: block;
}
.scanner-item__body { padding: 8px; }
.scanner-item__title { font-size: 12px; font-weight: 600; line-height: 1.3; margin-bottom: 4px; color: var(--text); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.scanner-item__meta { font-size: 10px; color: var(--text-muted); }
.scanner-item__query { font-size: 10px; color: var(--accent); margin-top: 2px; font-style: italic; }

/* ── Feeds tab ──────────────────────────────────────────────────────────── */
.disc-section-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    margin-bottom: 20px;
    overflow: hidden;
}
.disc-section-card__header {
    padding: 14px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: var(--bg);
}
.disc-section-card__header h3 {
    font-family: 'Manrope', sans-serif;
    font-size: 15px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}
.disc-section-card__body { padding: 20px; }

/* Feed suggestions */
.feed-suggestions { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 20px; }
.feed-suggestion-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border: 1px solid var(--border);
    border-radius: 99px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    background: var(--bg);
    color: var(--text-muted);
    transition: border-color .15s, color .15s, background .15s;
}
.feed-suggestion-btn:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-light); }

/* Feeds table */
.disc-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.disc-table th {
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: var(--text-muted);
    padding: 8px 12px;
    border-bottom: 2px solid var(--border);
}
.disc-table td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}
.disc-table tr:last-child td { border-bottom: none; }
.disc-table tr:hover td { background: var(--bg); }
.disc-table .url-cell {
    max-width: 240px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.disc-table a { color: var(--accent); text-decoration: none; font-size: 12px; }
.disc-table a:hover { text-decoration: underline; }

/* Toggle switch */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 34px;
    height: 18px;
}
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-switch .slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background: var(--border);
    border-radius: 18px;
    transition: background .2s;
}
.toggle-switch .slider::before {
    content: '';
    position: absolute;
    height: 12px;
    width: 12px;
    left: 3px;
    bottom: 3px;
    background: #fff;
    border-radius: 50%;
    transition: transform .2s;
}
.toggle-switch input:checked + .slider { background: var(--success); }
.toggle-switch input:checked + .slider::before { transform: translateX(16px); }

/* Add feed form */
.disc-add-feed-form {
    display: grid;
    grid-template-columns: 1fr 2fr 1fr auto;
    gap: 10px;
    align-items: end;
}
@media (max-width: 800px) { .disc-add-feed-form { grid-template-columns: 1fr; } }
.disc-add-feed-form .disc-field { margin-bottom: 0; }
.disc-add-feed-form input {
    height: 36px;
    width: 100%;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 0 10px;
    font-size: 13px;
    font-family: inherit;
    background: var(--bg);
    color: var(--text);
    transition: border-color .15s;
}
.disc-add-feed-form input:focus { outline: none; border-color: var(--accent); }

/* ── History tab ─────────────────────────────────────────────────────────── */
.disc-sub-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 16px;
}
.disc-sub-tab-btn {
    padding: 6px 16px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    background: var(--bg);
    color: var(--text-muted);
    transition: all .15s;
}
.disc-sub-tab-btn.active {
    background: var(--accent);
    color: #fff;
    border-color: var(--accent);
}
.disc-sub-panel { display: none; }
.disc-sub-panel.active { display: block; }

.hist-thumb {
    width: 48px;
    height: 36px;
    object-fit: cover;
    border-radius: 4px;
    display: block;
    background: var(--border);
}

/* ── Flash messages ─────────────────────────────────────────────────────── */
.flash {
    padding: 12px 16px;
    border-radius: var(--radius-sm);
    font-size: 13px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
}
.flash-success { background: var(--success-light); color: #15803d; border: 1px solid var(--success); }
.flash-error   { background: var(--danger-light);  color: var(--danger);  border: 1px solid var(--danger); }

/* ── Empty state ─────────────────────────────────────────────────────────── */
.empty-state {
    text-align: center;
    padding: 48px 20px;
    color: var(--text-muted);
}
.empty-state i { font-size: 36px; margin-bottom: 12px; display: block; opacity: .5; }
.empty-state p { font-size: 14px; }

/* ── Dark mode ───────────────────────────────────────────────────────────── */
@media (prefers-color-scheme: dark) {
    :root {
        --bg:         #0f172a;
        --white:      #1e293b;
        --text:       #f1f5f9;
        --text-muted: #94a3b8;
        --border:     #334155;
    }
    .source-card { background: #1e293b; }
    .disc-scanner-wrap { background: #1e293b; }
    .disc-section-card { background: #1e293b; }
    .disc-section-card__header { background: #0f172a; }
    .disc-lightbox__inner { background: #1e293b; }
    .disc-filter-row { background: #1e293b; }
    .disc-stats-bar .disc-stat { background: #1e293b; }
    .disc-table tr:hover td { background: #0f172a; }
    .feed-suggestion-btn { background: #0f172a; }
    .disc-sub-tab-btn { background: #0f172a; }
    .disc-add-feed-form input { background: #0f172a; }
    .disc-field select, .disc-field input { background: #0f172a; }
    .disc-filter-row select, .disc-filter-row input { background: #0f172a; }
    .source-card__approve-row select { background: #0f172a; }
    .scanner-item { background: #1e293b; }
    .source-card__tag { background: #0f172a; }
}

/* ── Responsive ─────────────────────────────────────────────────────────── */
@media (max-width: 700px) {
    .disc-stats-bar { gap: 8px; }
    .disc-stat strong { font-size: 16px; }
    .disc-filter-row { flex-direction: column; align-items: stretch; }
    .disc-filter-row input[type="search"] { min-width: 0; }
}
    </style>
</head>
<body>
<?php require_once __DIR__ . '/_sidebar.php'; ?>
<main class="admin-main">

    <div class="admin-topbar">
        <div class="admin-header">
            <h1><i class="fa-solid fa-satellite-dish" style="color:var(--accent);margin-right:8px;"></i>Discovery Hub</h1>
            <p>Curate, scan, and manage your visual content pipeline.</p>
        </div>
        <button class="hamburger" id="hamburger" aria-label="Toggle navigation"><i class="fa-solid fa-bars"></i></button>
    </div>

    <?php if ($flash): ?>
    <div class="flash flash-success"><i class="fa-solid fa-circle-check"></i> <?= e($flash) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="flash flash-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($error) ?></div>
    <?php endif; ?>

    <?php if (!$ready): ?>
    <div class="disc-section-card">
        <div class="empty-state">
            <i class="fa-solid fa-database"></i>
            <p>Run <code>schema-update.sql</code> to enable discovery tables.</p>
        </div>
    </div>
    <?php else: ?>

    <!-- ══ Tab navigation ══════════════════════════════════════════════════ -->
    <nav class="disc-tabs" role="tablist" aria-label="Discovery sections">
        <button class="disc-tab-btn" role="tab" data-tab="queue" aria-selected="false" aria-controls="panel-queue">
            <i class="fa-solid fa-inbox"></i> Queue
            <?php if ($statsBar['pending'] > 0): ?>
            <span class="badge"><?= $statsBar['pending'] ?></span>
            <?php endif; ?>
        </button>
        <button class="disc-tab-btn" role="tab" data-tab="scanner" aria-selected="false" aria-controls="panel-scanner">
            <i class="fa-solid fa-radar"></i> Board Scanner
        </button>
        <button class="disc-tab-btn" role="tab" data-tab="feeds" aria-selected="false" aria-controls="panel-feeds">
            <i class="fa-solid fa-rss"></i> Sources &amp; Feeds
            <?php if (count($feeds) > 0): ?>
            <span class="badge"><?= count($feeds) ?></span>
            <?php endif; ?>
        </button>
        <button class="disc-tab-btn" role="tab" data-tab="history" aria-selected="false" aria-controls="panel-history">
            <i class="fa-solid fa-clock-rotate-left"></i> History
        </button>
    </nav>

    <!-- ══ TAB 1: Queue ════════════════════════════════════════════════════ -->
    <div class="disc-tab-panel" id="panel-queue" role="tabpanel">

        <!-- Stats bar -->
        <div class="disc-stats-bar">
            <div class="disc-stat">
                <i class="fa-solid fa-hourglass-half"></i>
                <strong><?= $statsBar['pending'] ?></strong>
                <span>Pending</span>
            </div>
            <div class="disc-stat">
                <i class="fa-solid fa-circle-check" style="color:var(--success)"></i>
                <strong><?= $statsBar['approved_today'] ?></strong>
                <span>Approved today</span>
            </div>
            <div class="disc-stat">
                <i class="fa-solid fa-ban" style="color:var(--danger)"></i>
                <strong><?= $statsBar['rejected_total'] ?></strong>
                <span>Rejected total</span>
            </div>
        </div>

        <!-- Filter row -->
        <div class="disc-filter-row">
            <label for="filter-platform">Platform</label>
            <select id="filter-platform">
                <option value="">All platforms</option>
                <option value="wikimedia">Wikimedia Commons</option>
                <option value="met">Met Museum</option>
                <option value="chicago">Art Institute of Chicago</option>
                <option value="rss">RSS</option>
                <option value="manual">Manual</option>
            </select>
            <label for="filter-score">Score</label>
            <select id="filter-score">
                <option value="">All scores</option>
                <option value="high">High (8-10)</option>
                <option value="good">Good (5-7)</option>
                <option value="low">Low (0-4)</option>
            </select>
            <input type="search" id="filter-search" placeholder="Search by title…">
            <span id="filter-count" style="font-size:12px;color:var(--text-muted);white-space:nowrap;"></span>
        </div>

        <!-- Batch bar -->
        <?php $lowCount = count(array_filter($sources, fn($s) => $s['score'] <= 4)); ?>
        <?php if ($lowCount > 0): ?>
        <div class="disc-batch-bar" id="batch-bar">
            <span><i class="fa-solid fa-layer-group"></i> <strong><?= $lowCount ?></strong> low-score items in queue</span>
            <form method="post" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action" value="batch_reject_low">
                <input type="hidden" name="_tab" value="queue">
                <button class="btn btn-warning btn-sm" type="submit" onclick="return confirm('Reject all <?= $lowCount ?> low-score sources?')">
                    <i class="fa-solid fa-trash-can"></i> Reject all Low-score
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Source grid -->
        <?php if (!$sources): ?>
        <div class="empty-state" style="background:var(--white);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);">
            <i class="fa-regular fa-images"></i>
            <p>No pending references — you're all caught up!</p>
        </div>
        <?php else: ?>

        <div class="disc-grid" id="source-grid">
        <?php foreach ($sources as $src):
            [$badgeLabel, $badgeColor, $scoreClass] = scoreBadge($src['score']);
            $platform  = $src['source_platform'] ?? '';
            $platIcon  = platformIcon($platform);
            $license   = licenseBadge($src['description'] ?? '');
            $tags      = array_filter(array_map('trim', explode(',', $src['suggested_tags'] ?? '')));
            $titleDisp = $src['title'] ?: ($src['source_platform'] ?: 'Untitled');
        ?>
        <article class="source-card"
            data-platform="<?= e(strtolower($platform)) ?>"
            data-score="<?= e($scoreClass) ?>"
            data-title="<?= e(strtolower($titleDisp)) ?>">

            <!-- Image -->
            <div class="source-card__img-wrap"
                data-lb-id="<?= (int)$src['id'] ?>"
                data-lb-img="<?= e($src['image_url'] ?? '') ?>"
                data-lb-title="<?= e($titleDisp) ?>"
                data-lb-desc="<?= e(mb_substr($src['description'] ?? '', 0, 400)) ?>"
                data-lb-url="<?= e($src['source_url'] ?? '') ?>"
                data-lb-platform="<?= e($platform) ?>"
                data-lb-score="<?= (int)$src['score'] ?>"
                data-lb-score-label="<?= e($badgeLabel) ?>"
                data-lb-score-color="<?= e($badgeColor) ?>">
                <?php if ($src['image_url']): ?>
                <img src="<?= e($src['image_url']) ?>" alt="<?= e($titleDisp) ?>" loading="lazy">
                <div class="source-card__img-overlay"><i class="fa-solid fa-magnifying-glass-plus"></i></div>
                <?php else: ?>
                <div class="source-card__img-placeholder"><i class="fa-regular fa-image"></i></div>
                <?php endif; ?>
            </div>

            <!-- Body -->
            <div class="source-card__body">
                <div class="source-card__badges">
                    <span class="disc-score-badge" style="background:<?= $badgeColor ?>;">
                        <?= (int)$src['score'] ?>/10 <?= e($badgeLabel) ?>
                    </span>
                    <span class="disc-platform-badge">
                        <i class="<?= e($platIcon) ?>"></i>
                        <?= e($platform ?: 'Unknown') ?>
                    </span>
                    <?php if ($license): ?>
                    <?= $license ?>
                    <?php endif; ?>
                </div>

                <div class="source-card__title" title="<?= e($titleDisp) ?>"><?= e($titleDisp) ?></div>

                <?php if ($src['description']): ?>
                <div class="source-card__desc"><?= e($src['description']) ?></div>
                <?php endif; ?>

                <?php if ($tags): ?>
                <div class="source-card__tags">
                    <?php foreach (array_slice($tags, 0, 5) as $tag): ?>
                    <span class="source-card__tag"><?= e($tag) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="source-card__actions">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$src['id'] ?>">
                        <input type="hidden" name="_tab" value="queue">
                        <div class="source-card__approve-row">
                            <select name="board_id">
                                <option value="">Approve into board…</option>
                                <?php foreach ($boards as $board): ?>
                                <option value="<?= (int)$board['id'] ?>"><?= e($board['title']) ?> — <?= e($board['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-success btn-sm" name="action" value="approve" type="submit" title="Approve">
                                <i class="fa-solid fa-check"></i>
                            </button>
                        </div>
                        <button class="btn btn-danger btn-sm" name="action" value="reject" type="submit" style="width:100%;">
                            <i class="fa-solid fa-xmark"></i> Reject
                        </button>
                    </form>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
        </div><!-- /disc-grid -->

        <p id="no-filter-results" style="display:none;text-align:center;padding:40px;color:var(--text-muted);">
            <i class="fa-solid fa-filter-circle-xmark"></i> No items match your filters.
        </p>
        <?php endif; ?>
    </div><!-- /panel-queue -->

    <!-- ══ TAB 2: Board Scanner ════════════════════════════════════════════ -->
    <div class="disc-tab-panel" id="panel-scanner" role="tabpanel">
        <div class="disc-scanner-wrap">
            <h2><i class="fa-solid fa-satellite-dish" style="color:var(--accent);margin-right:6px;"></i>Board Scanner</h2>
            <p class="hint">Discover images from licensed public-domain sources that match your board's theme. Results are added to the Queue tab for review.</p>

            <form id="scanner-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                <div class="disc-field">
                    <label for="scanner-board">Select Board</label>
                    <select name="board_id" id="scanner-board" required>
                        <option value="">— Choose a board —</option>
                        <?php foreach ($boards as $board): ?>
                        <option value="<?= (int)$board['id'] ?>"><?= e($board['title']) ?> (<?= e($board['username']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <p style="font-size:13px;font-weight:600;margin-bottom:10px;">Sources to scan:</p>
                <div class="disc-checkboxes">
                    <label class="disc-checkbox-row">
                        <input type="checkbox" name="sources[]" value="wikimedia" checked>
                        <span class="cb-label"><i class="fa-brands fa-wikipedia-w" style="color:var(--accent);margin-right:4px;"></i> Wikimedia Commons</span>
                        <span class="cb-badge">Free, CC-licensed</span>
                    </label>
                    <label class="disc-checkbox-row">
                        <input type="checkbox" name="sources[]" value="met" checked>
                        <span class="cb-label"><i class="fa-solid fa-landmark" style="color:var(--accent);margin-right:4px;"></i> Met Museum</span>
                        <span class="cb-badge">Public Domain</span>
                    </label>
                    <label class="disc-checkbox-row">
                        <input type="checkbox" name="sources[]" value="chicago" checked>
                        <span class="cb-label"><i class="fa-solid fa-building-columns" style="color:var(--accent);margin-right:4px;"></i> Art Institute of Chicago</span>
                        <span class="cb-badge">Public Domain</span>
                    </label>
                </div>

                <button class="btn btn-accent" type="submit" id="scanner-btn" style="width:100%;height:42px;font-size:15px;justify-content:center;">
                    <i class="fa-solid fa-magnifying-glass"></i> Scan Now
                </button>
            </form>

            <!-- Results -->
            <div id="scanner-results">
                <div class="scanner-spinner" id="scanner-spinner">
                    <i class="fa-solid fa-circle-notch"></i>
                    <p style="margin-top:12px;font-size:14px;">Scanning sources…</p>
                </div>
                <div id="scanner-output" style="display:none;">
                    <div id="scanner-msg"></div>
                    <div class="scanner-queries" id="scanner-queries"></div>
                    <div class="scanner-grid" id="scanner-grid"></div>
                </div>
            </div>
        </div>
    </div><!-- /panel-scanner -->

    <!-- ══ TAB 3: Sources & Feeds ══════════════════════════════════════════ -->
    <div class="disc-tab-panel" id="panel-feeds" role="tabpanel">

        <!-- Preloaded suggestions -->
        <div class="disc-section-card">
            <div class="disc-section-card__header">
                <h3><i class="fa-solid fa-wand-magic-sparkles"></i> Quick-add design feeds</h3>
            </div>
            <div class="disc-section-card__body">
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px;">Click a feed to auto-fill the form below.</p>
                <div class="feed-suggestions">
                    <?php
                    $preloaded = [
                        ['Behance',       'https://feeds.feedburner.com/behance/vorr',    'design,portfolio'],
                        ['Dezeen',        'https://www.dezeen.com/feed/',                 'architecture,design'],
                        ["It's Nice That",'https://www.itsnicethat.com/rss',              'illustration,art'],
                        ['Eye on Design', 'https://eyeondesign.aiga.org/feed/',           'typography,graphic-design'],
                        ['Core77',        'https://www.core77.com/rss',                   'industrial-design'],
                    ];
                    foreach ($preloaded as [$n, $u, $t]):
                    ?>
                    <button type="button" class="feed-suggestion-btn"
                        data-name="<?= e($n) ?>"
                        data-url="<?= e($u) ?>"
                        data-tags="<?= e($t) ?>">
                        <i class="fa-solid fa-rss"></i> <?= e($n) ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <!-- Add feed form -->
                <form method="post" class="disc-add-feed-form" id="add-feed-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="add_feed">
                    <input type="hidden" name="_tab" value="feeds">
                    <div class="disc-field">
                        <label for="feed-name">Name</label>
                        <input type="text" name="name" id="feed-name" placeholder="Behance" required>
                    </div>
                    <div class="disc-field">
                        <label for="feed-url">RSS / Atom URL</label>
                        <input type="text" name="feed_url" id="feed-url" placeholder="https://…/feed/" required>
                    </div>
                    <div class="disc-field">
                        <label for="feed-tags">Tags</label>
                        <input type="text" name="tags" id="feed-tags" placeholder="design,typography">
                    </div>
                    <div class="disc-field">
                        <label>&nbsp;</label>
                        <button class="btn btn-accent" type="submit">
                            <i class="fa-solid fa-plus"></i> Add Feed
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Feed list -->
        <div class="disc-section-card">
            <div class="disc-section-card__header">
                <h3><i class="fa-solid fa-list-check"></i> Active feeds (<?= count($feeds) ?>)</h3>
                <a href="/cron/discover.php" target="_blank" class="btn btn-ghost btn-sm">
                    <i class="fa-solid fa-play"></i> Run discovery now
                </a>
            </div>
            <?php if (!$feeds): ?>
            <div class="empty-state"><i class="fa-solid fa-rss"></i><p>No feeds configured yet.</p></div>
            <?php else: ?>
            <div style="overflow-x:auto;">
            <table class="disc-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>URL</th>
                        <th>Tags</th>
                        <th>Last run</th>
                        <th>Active</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($feeds as $feed): ?>
                <tr>
                    <td style="font-weight:600;"><?= e($feed['name']) ?></td>
                    <td class="url-cell">
                        <a href="<?= e($feed['feed_url']) ?>" target="_blank" rel="noopener">
                            <?= e($feed['feed_url']) ?>
                        </a>
                    </td>
                    <td style="color:var(--text-muted);font-size:12px;"><?= e($feed['tags'] ?? '—') ?></td>
                    <td style="color:var(--text-muted);font-size:12px;white-space:nowrap;">
                        <?= $feed['last_run_at'] ? e(date('M j, Y H:i', strtotime($feed['last_run_at']))) : '—' ?>
                    </td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="toggle_feed">
                            <input type="hidden" name="id" value="<?= (int)$feed['id'] ?>">
                            <input type="hidden" name="_tab" value="feeds">
                            <label class="toggle-switch" title="Toggle active">
                                <input type="checkbox" <?= $feed['is_active'] ? 'checked' : '' ?> onchange="this.closest('form').submit()">
                                <span class="slider"></span>
                            </label>
                        </form>
                    </td>
                    <td>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this feed?')">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="delete_feed">
                            <input type="hidden" name="id" value="<?= (int)$feed['id'] ?>">
                            <input type="hidden" name="_tab" value="feeds">
                            <button class="btn btn-danger btn-sm" type="submit">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div><!-- /panel-feeds -->

    <!-- ══ TAB 4: History ══════════════════════════════════════════════════ -->
    <div class="disc-tab-panel" id="panel-history" role="tabpanel">

        <div class="disc-sub-tabs">
            <button class="disc-sub-tab-btn active" data-subtab="approved">
                <i class="fa-solid fa-circle-check"></i> Approved (<?= count($approved) ?>)
            </button>
            <button class="disc-sub-tab-btn" data-subtab="rejected">
                <i class="fa-solid fa-circle-xmark"></i> Rejected (<?= count($rejected) ?>)
            </button>
        </div>

        <!-- Approved subtab -->
        <div class="disc-sub-panel active" id="subtab-approved">
            <?php if (!$approved): ?>
            <div class="empty-state" style="background:var(--white);border:1px solid var(--border);border-radius:var(--radius);"><i class="fa-solid fa-check-double"></i><p>No approved sources yet.</p></div>
            <?php else: ?>
            <div class="disc-section-card">
                <div style="overflow-x:auto;">
                <table class="disc-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Platform</th>
                            <th>Board</th>
                            <th>Approved</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($approved as $src): ?>
                    <tr>
                        <td>
                            <?php if ($src['image_url']): ?>
                            <img src="<?= e($src['image_url']) ?>" alt="" class="hist-thumb">
                            <?php else: ?>
                            <div class="hist-thumb" style="display:flex;align-items:center;justify-content:center;color:var(--text-muted);"><i class="fa-regular fa-image"></i></div>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:220px;">
                            <div style="font-weight:600;font-size:13px;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                <?= e($src['title'] ?: '—') ?>
                            </div>
                            <?php if ($src['source_url']): ?>
                            <a href="<?= e($src['source_url']) ?>" target="_blank" style="font-size:11px;color:var(--text-muted);">
                                <i class="fa-solid fa-arrow-up-right-from-square fa-xs"></i> Source
                            </a>
                            <?php endif; ?>
                        </td>
                        <td><span class="disc-platform-badge"><i class="<?= e(platformIcon($src['source_platform'] ?? '')) ?>"></i> <?= e($src['source_platform'] ?: '—') ?></span></td>
                        <td style="font-size:13px;color:var(--text-muted);"><?= e($src['board_title'] ?? '—') ?></td>
                        <td style="font-size:12px;color:var(--text-muted);white-space:nowrap;">
                            <?= $src['reviewed_at'] ? e(date('M j, Y', strtotime($src['reviewed_at']))) : '—' ?>
                        </td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="action" value="requeue">
                                <input type="hidden" name="id" value="<?= (int)$src['id'] ?>">
                                <input type="hidden" name="_tab" value="history">
                                <button class="btn btn-ghost btn-sm" type="submit" title="Re-queue for review">
                                    <i class="fa-solid fa-rotate-left"></i> Re-queue
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Rejected subtab -->
        <div class="disc-sub-panel" id="subtab-rejected">
            <?php if (!$rejected): ?>
            <div class="empty-state" style="background:var(--white);border:1px solid var(--border);border-radius:var(--radius);"><i class="fa-solid fa-ban"></i><p>No rejected sources.</p></div>
            <?php else: ?>
            <div class="disc-section-card">
                <div style="overflow-x:auto;">
                <table class="disc-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Platform</th>
                            <th>Score</th>
                            <th>Rejected</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rejected as $src):
                        $sc = computeScore($src, $knownStyleTags, $lowQualityDomains);
                        [$bl, $bc] = scoreBadge($sc);
                    ?>
                    <tr>
                        <td>
                            <?php if ($src['image_url']): ?>
                            <img src="<?= e($src['image_url']) ?>" alt="" class="hist-thumb">
                            <?php else: ?>
                            <div class="hist-thumb" style="display:flex;align-items:center;justify-content:center;color:var(--text-muted);"><i class="fa-regular fa-image"></i></div>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:220px;">
                            <div style="font-weight:600;font-size:13px;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                <?= e($src['title'] ?: '—') ?>
                            </div>
                        </td>
                        <td><span class="disc-platform-badge"><i class="<?= e(platformIcon($src['source_platform'] ?? '')) ?>"></i> <?= e($src['source_platform'] ?: '—') ?></span></td>
                        <td><span class="disc-score-badge" style="background:<?= $bc ?>;"><?= $sc ?>/10 <?= e($bl) ?></span></td>
                        <td style="font-size:12px;color:var(--text-muted);white-space:nowrap;">
                            <?= $src['reviewed_at'] ? e(date('M j, Y', strtotime($src['reviewed_at']))) : '—' ?>
                        </td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="action" value="requeue">
                                <input type="hidden" name="id" value="<?= (int)$src['id'] ?>">
                                <input type="hidden" name="_tab" value="history">
                                <button class="btn btn-ghost btn-sm" type="submit" title="Re-queue for review">
                                    <i class="fa-solid fa-rotate-left"></i> Re-queue
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div><!-- /panel-history -->

    <?php endif; /* $ready */ ?>

</main>

<!-- ══ Lightbox ══════════════════════════════════════════════════════════ -->
<div id="disc-lightbox" aria-modal="true" role="dialog" aria-label="Image preview">
    <div class="disc-lightbox__inner" id="disc-lightbox-inner">
        <div class="disc-lightbox__top">
            <img id="lb-img" src="" alt="">
            <button class="disc-lightbox__close" id="lb-close" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="disc-lightbox__meta">
            <div class="disc-lightbox__badges" id="lb-badges"></div>
            <div class="disc-lightbox__title" id="lb-title"></div>
            <div class="disc-lightbox__desc" id="lb-desc"></div>
            <a class="disc-lightbox__source-link" id="lb-source-link" href="#" target="_blank" rel="noopener">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> <span id="lb-source-url"></span>
            </a>
            <div class="disc-lightbox__actions">
                <form method="post" id="lb-approve-form" style="display:contents;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="id" id="lb-id" value="">
                    <input type="hidden" name="_tab" value="queue">
                    <select name="board_id" class="admin-select" id="lb-board-select" style="flex:1;min-width:160px;height:34px;">
                        <option value="">Approve into board…</option>
                        <?php foreach ($boards as $board): ?>
                        <option value="<?= (int)$board['id'] ?>"><?= e($board['title']) ?> — <?= e($board['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-success" name="action" value="approve" type="submit">
                        <i class="fa-solid fa-check"></i> Approve
                    </button>
                    <button class="btn btn-danger" name="action" value="reject" type="submit">
                        <i class="fa-solid fa-xmark"></i> Reject
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
/* ══════════════════════════════════════════════════════════════════════════
   Tab system
═══════════════════════════════════════════════════════════════════════════ */
(function () {
    var tabBtns   = document.querySelectorAll('.disc-tab-btn');
    var tabPanels = document.querySelectorAll('.disc-tab-panel');
    var hashMap   = { queue: 0, scanner: 1, feeds: 2, history: 3 };

    function activateTab(tabName) {
        tabBtns.forEach(function (btn) {
            var isThis = btn.dataset.tab === tabName;
            btn.setAttribute('aria-selected', isThis ? 'true' : 'false');
        });
        tabPanels.forEach(function (panel) {
            panel.classList.toggle('active', panel.id === 'panel-' + tabName);
        });
        if (history.replaceState) {
            history.replaceState(null, '', '#' + tabName);
        }
    }

    tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activateTab(btn.dataset.tab);
        });
    });

    // Activate from hash or default to queue
    var hash = window.location.hash.replace('#', '');
    activateTab(hashMap.hasOwnProperty(hash) ? hash : 'queue');
})();

/* ══════════════════════════════════════════════════════════════════════════
   Filter / search (Queue tab)
═══════════════════════════════════════════════════════════════════════════ */
(function () {
    var grid       = document.getElementById('source-grid');
    var noResults  = document.getElementById('no-filter-results');
    var countEl    = document.getElementById('filter-count');
    if (!grid) return;

    var filterPlatform = document.getElementById('filter-platform');
    var filterScore    = document.getElementById('filter-score');
    var filterSearch   = document.getElementById('filter-search');

    function applyFilters() {
        var platform = filterPlatform.value.toLowerCase();
        var score    = filterScore.value;
        var search   = filterSearch.value.toLowerCase().trim();

        var cards    = grid.querySelectorAll('.source-card');
        var visible  = 0;

        cards.forEach(function (card) {
            var pMatch = !platform || card.dataset.platform.indexOf(platform) !== -1;
            var sMatch = !score    || card.dataset.score === score;
            var tMatch = !search   || card.dataset.title.indexOf(search) !== -1;
            var show   = pMatch && sMatch && tMatch;
            card.classList.toggle('filtered-out', !show);
            if (show) visible++;
        });

        if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
        if (countEl)   countEl.textContent = visible + ' of ' + cards.length + ' items';
    }

    filterPlatform.addEventListener('change', applyFilters);
    filterScore.addEventListener('change', applyFilters);
    filterSearch.addEventListener('input', applyFilters);

    // Initial count
    var allCards = grid.querySelectorAll('.source-card');
    if (countEl) countEl.textContent = allCards.length + ' of ' + allCards.length + ' items';
})();

/* ══════════════════════════════════════════════════════════════════════════
   Lightbox
═══════════════════════════════════════════════════════════════════════════ */
(function () {
    var lightbox = document.getElementById('disc-lightbox');
    var lbInner  = document.getElementById('disc-lightbox-inner');
    if (!lightbox) return;

    var lbImg    = document.getElementById('lb-img');
    var lbClose  = document.getElementById('lb-close');
    var lbTitle  = document.getElementById('lb-title');
    var lbDesc   = document.getElementById('lb-desc');
    var lbLink   = document.getElementById('lb-source-link');
    var lbUrl    = document.getElementById('lb-source-url');
    var lbBadges = document.getElementById('lb-badges');
    var lbId     = document.getElementById('lb-id');
    var lbBoard  = document.getElementById('lb-board-select');

    function openLightbox(wrap) {
        var img   = wrap.dataset.lbImg;
        var title = wrap.dataset.lbTitle;
        var desc  = wrap.dataset.lbDesc;
        var url   = wrap.dataset.lbUrl;
        var plat  = wrap.dataset.lbPlatform;
        var score = wrap.dataset.lbScore;
        var label = wrap.dataset.lbScoreLabel;
        var color = wrap.dataset.lbScoreColor;
        var id    = wrap.dataset.lbId;

        lbImg.src          = img || '';
        lbImg.style.display = img ? 'block' : 'none';
        lbTitle.textContent = title || '';
        lbDesc.textContent  = desc  || '';
        lbLink.href         = url   || '#';
        lbUrl.textContent   = url   || '';
        lbLink.style.display = url ? 'block' : 'none';
        lbId.value          = id   || '';

        lbBadges.innerHTML  =
            '<span class="disc-score-badge" style="background:' + color + ';margin-right:6px;">' +
                score + '/10 ' + label +
            '</span>' +
            (plat ? '<span class="disc-platform-badge">' + plat + '</span>' : '');

        lightbox.classList.add('open');
        document.body.style.overflow = 'hidden';
        lbClose.focus();
    }

    function closeLightbox() {
        lightbox.classList.remove('open');
        document.body.style.overflow = '';
    }

    // Click on image wrappers
    document.querySelectorAll('.source-card__img-wrap[data-lb-id]').forEach(function (wrap) {
        wrap.addEventListener('click', function () { openLightbox(wrap); });
    });

    lbClose.addEventListener('click', closeLightbox);

    // Click outside inner
    lightbox.addEventListener('click', function (e) {
        if (!lbInner.contains(e.target)) closeLightbox();
    });

    // Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && lightbox.classList.contains('open')) closeLightbox();
    });
})();

/* ══════════════════════════════════════════════════════════════════════════
   Board Scanner AJAX
═══════════════════════════════════════════════════════════════════════════ */
(function () {
    var form    = document.getElementById('scanner-form');
    var btn     = document.getElementById('scanner-btn');
    var spinner = document.getElementById('scanner-spinner');
    var output  = document.getElementById('scanner-output');
    var msgEl   = document.getElementById('scanner-msg');
    var queriesEl = document.getElementById('scanner-queries');
    var gridEl  = document.getElementById('scanner-grid');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        // Show spinner
        spinner.style.display = 'block';
        output.style.display  = 'none';
        btn.disabled          = true;
        btn.innerHTML         = '<i class="fa-solid fa-circle-notch fa-spin"></i> Scanning…';

        try {
            var res  = await fetch('/api/discover-for-board.php', {
                method: 'POST',
                body:   new FormData(form)
            });
            var data = await res.json();

            spinner.style.display = 'none';
            output.style.display  = 'block';

            if (data.ok) {
                // Success message
                var queued  = data.queued  || 0;
                var skipped = data.skipped || 0;
                msgEl.innerHTML =
                    '<div class="scanner-success-msg">' +
                        '<i class="fa-solid fa-circle-check"></i>' +
                        '<span><strong>' + queued + ' item' + (queued !== 1 ? 's' : '') + '</strong> queued for review' +
                        (skipped > 0 ? ' · ' + skipped + ' skipped (already in queue)' : '') +
                        ' — <a href="#queue" onclick="document.querySelector(\'[data-tab=queue]\').click();return false;" style="color:#15803d;font-weight:600;">go to Queue tab →</a></span>' +
                    '</div>';

                // Queries used
                if (data.queries && data.queries.length) {
                    queriesEl.innerHTML = '<strong style="font-size:12px;">Queries used:</strong> ' +
                        data.queries.map(function (q) { return '<span>' + escHtml(q) + '</span>'; }).join(' ');
                } else {
                    queriesEl.innerHTML = '';
                }

                // Results grid
                gridEl.innerHTML = '';
                if (data.items && data.items.length) {
                    data.items.forEach(function (item) {
                        var el = document.createElement('div');
                        el.className = 'scanner-item';
                        el.innerHTML =
                            (item.image_url
                                ? '<img src="' + escHtml(item.image_url) + '" alt="" loading="lazy">'
                                : '<div style="height:110px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#94a3b8;"><i class=\'fa-regular fa-image\'></i></div>') +
                            '<div class="scanner-item__body">' +
                                '<div class="scanner-item__title">' + escHtml(item.title || '—') + '</div>' +
                                '<div class="scanner-item__meta"><i class="fa-solid fa-globe fa-xs"></i> ' + escHtml(item.platform || '') + '</div>' +
                                (item.query ? '<div class="scanner-item__query">"' + escHtml(item.query) + '"</div>' : '') +
                            '</div>';
                        gridEl.appendChild(el);
                    });
                }
            } else {
                msgEl.innerHTML =
                    '<div class="scanner-error-msg"><i class="fa-solid fa-triangle-exclamation"></i> ' +
                    escHtml(data.error || 'An error occurred. Please try again.') + '</div>';
                queriesEl.innerHTML = '';
                gridEl.innerHTML    = '';
            }
        } catch (err) {
            spinner.style.display = 'none';
            output.style.display  = 'block';
            msgEl.innerHTML =
                '<div class="scanner-error-msg"><i class="fa-solid fa-triangle-exclamation"></i> ' +
                'Network error: ' + escHtml(err.message) + '</div>';
            queriesEl.innerHTML = '';
            gridEl.innerHTML    = '';
        }

        btn.disabled  = false;
        btn.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> Scan Now';
    });

    function escHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }
})();

/* ══════════════════════════════════════════════════════════════════════════
   Feed suggestions (auto-fill form)
═══════════════════════════════════════════════════════════════════════════ */
(function () {
    document.querySelectorAll('.feed-suggestion-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var nameEl = document.getElementById('feed-name');
            var urlEl  = document.getElementById('feed-url');
            var tagsEl = document.getElementById('feed-tags');
            if (nameEl) nameEl.value = btn.dataset.name || '';
            if (urlEl)  urlEl.value  = btn.dataset.url  || '';
            if (tagsEl) tagsEl.value = btn.dataset.tags || '';
            // Scroll to form
            var form = document.getElementById('add-feed-form');
            if (form) form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (urlEl) urlEl.focus();
        });
    });
})();

/* ══════════════════════════════════════════════════════════════════════════
   History sub-tabs
═══════════════════════════════════════════════════════════════════════════ */
(function () {
    var subBtns   = document.querySelectorAll('.disc-sub-tab-btn');
    var subPanels = document.querySelectorAll('.disc-sub-panel');

    subBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = btn.dataset.subtab;
            subBtns.forEach(function (b) { b.classList.toggle('active', b.dataset.subtab === target); });
            subPanels.forEach(function (p) { p.classList.toggle('active', p.id === 'subtab-' + target); });
        });
    });
})();
</script>
</body>
</html>
