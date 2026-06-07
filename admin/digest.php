<?php
require_once __DIR__ . '/_guard.php';
$currentPage = 'digest';

// Top boards this week
$weekBoards = [];
try {
    $weekBoards = $db->query(
        "SELECT b.*, u.username, u.avatar,
                (SELECT COUNT(*) FROM board_likes bl WHERE bl.board_id = b.id) AS like_count
         FROM boards b JOIN users u ON u.id = b.user_id
         WHERE b.is_draft = 0 AND b.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         ORDER BY like_count DESC LIMIT 6"
    )->fetchAll();
    if (count($weekBoards) < 3) {
        // fallback to top boards overall
        $weekBoards = $db->query(
            "SELECT b.*, u.username, u.avatar,
                    (SELECT COUNT(*) FROM board_likes bl WHERE bl.board_id = b.id) AS like_count
             FROM boards b JOIN users u ON u.id = b.user_id
             WHERE b.is_draft = 0
             ORDER BY like_count DESC LIMIT 6"
        )->fetchAll();
    }
} catch (Throwable $e) {}

// Featured curator
$featuredCurator = null;
try {
    $featuredCurator = $db->query(
        "SELECT u.*, COUNT(f.follower_id) AS fc
         FROM users u LEFT JOIN follows f ON f.following_id = u.id
         WHERE u.is_verified = 1
         GROUP BY u.id ORDER BY fc DESC LIMIT 1"
    )->fetch();
} catch (Throwable $e) {}

// Top style tag this week
$topTag = '';
try {
    $tagRows = $db->query(
        "SELECT style_tags FROM boards WHERE is_draft=0 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND style_tags != ''"
    )->fetchAll(PDO::FETCH_COLUMN);
    $tagCounts = [];
    foreach ($tagRows as $raw) {
        foreach (array_map('trim', explode(',', $raw)) as $t) {
            if ($t) $tagCounts[$t] = ($tagCounts[$t] ?? 0) + 1;
        }
    }
    arsort($tagCounts);
    $topTag = (string)array_key_first($tagCounts);
} catch (Throwable $e) {}

// Newsletter stats
$nlCount = 0;
try {
    $nlCount = (int)$db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status='active'")->fetchColumn();
} catch (Throwable $e) {}

// Build digest text for clipboard
$digestLines = [];
$digestLines[] = "Subject: Visual Design Journey — Weekly Picks";
$digestLines[] = "";
$digestLines[] = "Hi there,";
$digestLines[] = "";
$digestLines[] = "Here are this week's top picks from the Visual Design Journey community.";
$digestLines[] = "";
if ($weekBoards) {
    $digestLines[] = "TOP BOARDS THIS WEEK";
    $digestLines[] = str_repeat("-", 40);
    foreach ($weekBoards as $i => $b) {
        $digestLines[] = ($i + 1) . ". " . $b['title'] . " by @" . $b['username'];
        $digestLines[] = "   https://visualdesignjourney.com/board.php?id=" . $b['id'];
        $digestLines[] = "   " . $b['like_count'] . " likes";
        $digestLines[] = "";
    }
}
if ($featuredCurator) {
    $digestLines[] = "FEATURED CURATOR";
    $digestLines[] = str_repeat("-", 40);
    $digestLines[] = "@" . $featuredCurator['username'] . " — " . $featuredCurator['fc'] . " followers";
    if (!empty($featuredCurator['bio'])) {
        $digestLines[] = substr(strip_tags($featuredCurator['bio']), 0, 120) . "...";
    }
    $digestLines[] = "https://visualdesignjourney.com/profile.php?u=" . urlencode($featuredCurator['username']);
    $digestLines[] = "";
}
if ($topTag) {
    $digestLines[] = "TRENDING STYLE THIS WEEK: " . strtoupper($topTag);
    $digestLines[] = "";
}
$digestLines[] = "—";
$digestLines[] = "Visual Design Journey";
$digestLines[] = "https://visualdesignjourney.com";
$digestLines[] = "Unsubscribe: https://visualdesignjourney.com/unsubscribe";
$digestText = implode("\n", $digestLines);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editorial Digest — Admin — Visual Design Journey</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/admin/admin.css">
    <style>
        .digest-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .digest-stat-card {
            background: #fff;
            border: 1px solid #e8e8e8;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .digest-stat-card .stat-label {
            font-size: 12px;
            color: #888;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .digest-stat-card .stat-value {
            font-size: 22px;
            font-weight: 700;
            color: #111;
            line-height: 1.2;
        }
        .digest-stat-card .stat-icon {
            font-size: 20px;
            color: #bbb;
            margin-bottom: 4px;
        }
        .curator-card {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            padding: 20px;
        }
        .curator-card .curator-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            background: #f0f0f0;
            flex-shrink: 0;
        }
        .curator-card .curator-avatar-placeholder {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #e8e8e8;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #aaa;
            font-size: 26px;
            flex-shrink: 0;
        }
        .curator-card .curator-info { flex: 1; }
        .curator-card .curator-name {
            font-size: 18px;
            font-weight: 700;
            color: #111;
            margin: 0 0 4px;
        }
        .curator-card .curator-bio {
            font-size: 14px;
            color: #555;
            margin: 0 0 10px;
            line-height: 1.5;
        }
        .curator-card .curator-meta {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 13px;
            color: #888;
        }
        .curator-card .curator-meta i { margin-right: 4px; }
        .boards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 18px;
            padding: 20px;
        }
        .board-card {
            border: 1px solid #e8e8e8;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
            transition: box-shadow .15s;
        }
        .board-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }
        .board-card .board-cover {
            width: 100%;
            height: 160px;
            object-fit: cover;
            background: #f5f5f5;
            display: block;
        }
        .board-card .board-cover-placeholder {
            width: 100%;
            height: 160px;
            background: linear-gradient(135deg, #f0f0f0, #e0e0e0);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccc;
            font-size: 32px;
        }
        .board-card .board-info {
            padding: 14px;
        }
        .board-card .board-title {
            font-size: 15px;
            font-weight: 600;
            color: #111;
            margin: 0 0 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .board-card .board-meta {
            font-size: 13px;
            color: #888;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .board-card .board-likes {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #e05252;
            font-weight: 600;
        }
        .board-card .board-actions {
            padding: 0 14px 14px;
            display: flex;
            gap: 8px;
        }
        .board-card .board-actions a {
            font-size: 12px;
            color: #555;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 4px 10px;
            transition: background .12s;
        }
        .board-card .board-actions a:hover { background: #f5f5f5; }
        .digest-textarea-wrap { padding: 0 20px 20px; }
        .digest-textarea-wrap textarea {
            width: 100%;
            height: 220px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 14px;
            resize: vertical;
            color: #333;
            background: #fafafa;
            box-sizing: border-box;
        }
        .digest-copy-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #111;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin: 0 20px 20px;
            transition: background .15s;
        }
        .digest-copy-btn:hover { background: #333; }
        .digest-copy-btn.copied { background: #2d8c4e; }
        .badge-verified {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #eaf4ff;
            color: #1a7fd4;
            font-size: 11px;
            font-weight: 600;
            border-radius: 4px;
            padding: 2px 8px;
        }
        .no-data-note {
            padding: 32px 20px;
            text-align: center;
            color: #aaa;
            font-size: 14px;
        }
        .no-data-note i { font-size: 28px; display: block; margin-bottom: 10px; }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/_sidebar.php'; ?>
<main class="admin-main">
    <div class="admin-topbar">
        <div class="admin-header">
            <h1>Editorial Digest</h1>
            <p>Weekly content preview — top boards, curators, and trends ready to feature.</p>
        </div>
        <button class="hamburger" id="hamburger" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <!-- Hero stat cards -->
    <div class="digest-stats">
        <div class="digest-stat-card">
            <div class="stat-icon"><i class="fa-solid fa-layout-grid" style="color:#7c6af7;"></i></div>
            <div class="stat-label">Boards this week</div>
            <div class="stat-value"><?= count($weekBoards) ?></div>
        </div>
        <div class="digest-stat-card">
            <div class="stat-icon"><i class="fa-solid fa-tag" style="color:#f0a500;"></i></div>
            <div class="stat-label">Top style</div>
            <div class="stat-value" style="font-size:16px;"><?= $topTag !== '' ? e($topTag) : '<span style="color:#ccc;font-size:14px;">None yet</span>' ?></div>
        </div>
        <div class="digest-stat-card">
            <div class="stat-icon"><i class="fa-solid fa-envelope" style="color:#2d8c4e;"></i></div>
            <div class="stat-label">Active subscribers</div>
            <div class="stat-value"><?= number_format($nlCount) ?></div>
        </div>
        <div class="digest-stat-card">
            <div class="stat-icon"><i class="fa-solid fa-star" style="color:#e05252;"></i></div>
            <div class="stat-label">Featured curator</div>
            <div class="stat-value" style="font-size:16px;">
                <?php if ($featuredCurator): ?>
                    @<?= e($featuredCurator['username']) ?>
                <?php else: ?>
                    <span style="color:#ccc;font-size:14px;">None</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Featured Curator -->
    <div class="admin-card" style="margin-bottom:24px;">
        <div class="admin-card__header">
            <h2><i class="fa-solid fa-star"></i> Featured Curator</h2>
        </div>
        <?php if ($featuredCurator): ?>
        <div class="curator-card">
            <?php if (!empty($featuredCurator['avatar'])): ?>
                <img src="<?= e($featuredCurator['avatar']) ?>" alt="<?= e($featuredCurator['username']) ?>" class="curator-avatar">
            <?php else: ?>
                <div class="curator-avatar-placeholder"><i class="fa-solid fa-user"></i></div>
            <?php endif; ?>
            <div class="curator-info">
                <p class="curator-name">
                    @<?= e($featuredCurator['username']) ?>
                    <span class="badge-verified"><i class="fa-solid fa-check"></i> Verified</span>
                </p>
                <?php if (!empty($featuredCurator['bio'])): ?>
                    <p class="curator-bio"><?= e(substr(strip_tags($featuredCurator['bio']), 0, 180)) ?><?= strlen(strip_tags($featuredCurator['bio'])) > 180 ? '...' : '' ?></p>
                <?php endif; ?>
                <div class="curator-meta">
                    <span><i class="fa-solid fa-users"></i><?= number_format((int)$featuredCurator['fc']) ?> followers</span>
                    <a href="/profile.php?u=<?= urlencode($featuredCurator['username']) ?>" target="_blank" style="color:#7c6af7;text-decoration:none;">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> View profile
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="no-data-note">
            <i class="fa-solid fa-user-check"></i>
            No verified curators found. Mark users as verified to feature them.
        </div>
        <?php endif; ?>
    </div>

    <!-- Top Boards Grid -->
    <div class="admin-card" style="margin-bottom:24px;">
        <div class="admin-card__header">
            <h2><i class="fa-solid fa-fire"></i> Top Boards This Week</h2>
            <a href="/index.php" target="_blank">View all</a>
        </div>
        <?php if ($weekBoards): ?>
        <div class="boards-grid">
            <?php foreach ($weekBoards as $b): ?>
            <div class="board-card">
                <?php if (!empty($b['cover_image'])): ?>
                    <img src="<?= e($b['cover_image']) ?>" alt="<?= e($b['title']) ?>" class="board-cover">
                <?php else: ?>
                    <div class="board-cover-placeholder"><i class="fa-regular fa-image"></i></div>
                <?php endif; ?>
                <div class="board-info">
                    <p class="board-title" title="<?= e($b['title']) ?>"><?= e($b['title']) ?></p>
                    <div class="board-meta">
                        <span>@<?= e($b['username']) ?></span>
                        <span class="board-likes"><i class="fa-solid fa-heart"></i><?= (int)$b['like_count'] ?></span>
                    </div>
                </div>
                <div class="board-actions">
                    <a href="/board.php?id=<?= (int)$b['id'] ?>" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> View on site</a>
                    <a href="/admin/boards.php" title="Manage boards"><i class="fa-solid fa-pen"></i> Manage</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="no-data-note">
            <i class="fa-regular fa-rectangle-list"></i>
            No published boards found yet.
        </div>
        <?php endif; ?>
    </div>

    <!-- Copy Digest Text -->
    <div class="admin-card" style="margin-bottom:24px;">
        <div class="admin-card__header">
            <h2><i class="fa-solid fa-envelope-open-text"></i> Copy Digest Email</h2>
        </div>
        <button class="digest-copy-btn" id="copyDigestBtn" type="button">
            <i class="fa-regular fa-copy"></i> Copy digest text
        </button>
        <div class="digest-textarea-wrap">
            <textarea id="digestTextarea" readonly><?= htmlspecialchars($digestText, ENT_QUOTES) ?></textarea>
        </div>
    </div>
</main>

<script>
(function () {
    const btn = document.getElementById('copyDigestBtn');
    const ta  = document.getElementById('digestTextarea');
    if (!btn || !ta) return;
    btn.addEventListener('click', function () {
        ta.select();
        ta.setSelectionRange(0, 99999);
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(ta.value).then(function () {
                    flashCopied();
                });
            } else {
                document.execCommand('copy');
                flashCopied();
            }
        } catch (e) {}
    });
    function flashCopied() {
        btn.classList.add('copied');
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
        setTimeout(function () {
            btn.classList.remove('copied');
            btn.innerHTML = '<i class="fa-regular fa-copy"></i> Copy digest text';
        }, 2200);
    }
    // Hamburger sidebar toggle (matches other admin pages)
    var hbg = document.getElementById('hamburger');
    if (hbg) {
        hbg.addEventListener('click', function () {
            document.querySelector('.admin-sidebar')?.classList.toggle('open');
        });
    }
})();
</script>
</body>
</html>
