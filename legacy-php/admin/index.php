<?php
// FILE: admin/index.php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/_metrics.php';
require_once __DIR__ . '/../includes/ga4.php';

// ── GA4 dashboard data (non-fatal) ────────────────────────────────────────
$ga4Data  = null;
$ga4Error = '';
if (vdj_ga4_ready()) {
    try {
        $ga4Data = vdj_ga4_dashboard_data();
    } catch (Throwable $e) {
        $ga4Error = $e->getMessage();
    }
}

// ── Stats Row 1 ────────────────────────────────────────────────────────────
$totalUsers   = (int)adminScalar($db, 'SELECT COUNT(*) FROM users');
$totalBoards  = (int)adminScalar($db, 'SELECT COUNT(*) FROM boards');
$totalLikes   = (int)adminScalar($db, 'SELECT COUNT(*) FROM board_likes');
$totalFollows = (int)adminScalar($db, 'SELECT COUNT(*) FROM follows');

// ── Stats Row 2 ────────────────────────────────────────────────────────────
$publishedBoards = (int)adminScalar($db, 'SELECT COUNT(*) FROM boards WHERE is_draft = 0');
$draftBoards     = (int)adminScalar($db, 'SELECT COUNT(*) FROM boards WHERE is_draft = 1');

$newUsersWeek  = (int)adminScalar($db,
    "SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
);

$newBoardsWeek = (int)adminScalar($db,
    "SELECT COUNT(*) FROM boards WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
);

$adsenseReadiness = adminAdsenseReadiness();
$blogPostCount = adminBlogPostCount($db);
$mediaSummary = adminMediaSummary($db, false);
$sitemapHealth = adminSitemapHealth($db);

// ── Top 5 Most Liked Boards ────────────────────────────────────────────────
$topBoards = adminFetchAll($db,
    'SELECT b.id, b.title, u.username,
            COUNT(bl.id) AS likes
     FROM boards b
     JOIN users u ON u.id = b.user_id
     LEFT JOIN board_likes bl ON bl.board_id = b.id
     WHERE b.is_draft = 0
     GROUP BY b.id, b.title, u.username
     ORDER BY likes DESC
     LIMIT 5'
);

// ── Recent Signups (last 7) ────────────────────────────────────────────────
$recentUsers = adminFetchAll($db,
    'SELECT u.id, u.username, u.email, u.created_at, u.is_admin,
            (SELECT COUNT(*) FROM boards b WHERE b.user_id = u.id) AS board_count
     FROM users u
     ORDER BY u.created_at DESC
     LIMIT 7'
);

// ── Recent Boards (last 10) with cover ────────────────────────────────────
$recentBoards = adminFetchAll($db,
    'SELECT b.id, b.title, b.created_at, b.is_draft, b.cover_image,
            (SELECT COUNT(*) FROM board_likes bl WHERE bl.board_id = b.id) AS like_count,
            u.username, u.id AS user_id
     FROM boards b
     JOIN users u ON u.id = b.user_id
     ORDER BY b.created_at DESC
     LIMIT 10'
);

$currentPage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Admin — Visual Design Journey</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/admin/admin.css">
</head>
<body>

<?php require_once __DIR__ . '/_sidebar.php'; ?>

<main class="admin-main">

    <!-- Topbar -->
    <div class="admin-topbar">
        <div class="admin-header">
            <h1>Dashboard</h1>
            <p>Welcome back, <?= e($adminUser['username']) ?>. Here's what's happening.</p>
        </div>
        <button class="hamburger" id="hamburger" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <!-- ── Stat Grid Row 1 ──────────────────────────────────────────────── -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-card__icon"><i class="fa-solid fa-users"></i></div>
            <div class="stat-card__body">
                <div class="stat-card__num"><?= number_format($totalUsers) ?></div>
                <div class="stat-card__label">Total Users</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card__icon"><i class="fa-solid fa-border-all"></i></div>
            <div class="stat-card__body">
                <div class="stat-card__num"><?= number_format($totalBoards) ?></div>
                <div class="stat-card__label">Total Boards</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card__icon" style="background:rgba(244,63,94,.1);color:#f43f5e;"><i class="fa-solid fa-heart"></i></div>
            <div class="stat-card__body">
                <div class="stat-card__num"><?= number_format($totalLikes) ?></div>
                <div class="stat-card__label">Total Likes</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card__icon" style="background:rgba(34,197,94,.1);color:#22c55e;"><i class="fa-solid fa-user-check"></i></div>
            <div class="stat-card__body">
                <div class="stat-card__num"><?= number_format($totalFollows) ?></div>
                <div class="stat-card__label">Total Follows</div>
            </div>
        </div>
    </div>

    <!-- ── Stat Grid Row 2 ──────────────────────────────────────────────── -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-card__icon" style="background:rgba(34,197,94,.1);color:#22c55e;"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-card__body">
                <div class="stat-card__num"><?= number_format($publishedBoards) ?></div>
                <div class="stat-card__label">Published Boards</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card__icon" style="background:rgba(245,158,11,.1);color:#f59e0b;"><i class="fa-solid fa-file-pen"></i></div>
            <div class="stat-card__body">
                <div class="stat-card__num"><?= number_format($draftBoards) ?></div>
                <div class="stat-card__label">Draft Boards</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card__icon"><i class="fa-solid fa-user-plus"></i></div>
            <div class="stat-card__body">
                <div class="stat-card__num accent"><?= number_format($newUsersWeek) ?></div>
                <div class="stat-card__label">New Users This Week</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card__icon"><i class="fa-solid fa-layer-group"></i></div>
            <div class="stat-card__body">
                <div class="stat-card__num accent"><?= number_format($newBoardsWeek) ?></div>
                <div class="stat-card__label">New Boards This Week</div>
            </div>
        </div>
    </div>

    <!-- ── SEO + Monetization Health ───────────────────────────────────── -->
    <div class="stat-grid">
        <a class="stat-card" href="/admin/adsense.php" style="text-decoration:none;">
            <div class="stat-card__icon" style="background:rgba(34,197,94,.1);color:#16a34a;"><i class="fa-brands fa-google"></i></div>
            <div class="stat-card__body">
                <div class="stat-card__num" style="font-size:20px;"><?= e($adsenseReadiness['status']) ?></div>
                <div class="stat-card__label">AdSense Readiness</div>
            </div>
        </a>
        <a class="stat-card" href="/admin/blog.php" style="text-decoration:none;">
            <div class="stat-card__icon"><i class="fa-solid fa-newspaper"></i></div>
            <div class="stat-card__body">
                <div class="stat-card__num"><?= number_format($blogPostCount) ?></div>
                <div class="stat-card__label">Blog Posts</div>
            </div>
        </a>
        <a class="stat-card" href="/admin/media.php" style="text-decoration:none;">
            <div class="stat-card__icon" style="background:rgba(245,158,11,.1);color:#d97706;"><i class="fa-solid fa-images"></i></div>
            <div class="stat-card__body">
                <div class="stat-card__num" style="font-size:24px;"><?= e(adminBytes((int)$mediaSummary['total_size'])) ?></div>
                <div class="stat-card__label">Uploaded Media Size</div>
            </div>
        </a>
        <a class="stat-card" href="/admin/media.php?view=unused" style="text-decoration:none;">
            <div class="stat-card__icon" style="background:rgba(239,68,68,.1);color:#dc2626;"><i class="fa-solid fa-broom"></i></div>
            <div class="stat-card__body">
                <div class="stat-card__num"><?= number_format((int)$mediaSummary['unused_count']) ?></div>
                <div class="stat-card__label">Unused Media Candidates</div>
            </div>
        </a>
    </div>

    <div class="stat-grid" style="grid-template-columns:repeat(2,1fr);">
        <a class="stat-card" href="/sitemap.xml" target="_blank" style="text-decoration:none;">
            <div class="stat-card__icon" style="background:rgba(34,197,94,.1);color:#16a34a;"><i class="fa-solid fa-sitemap"></i></div>
            <div class="stat-card__body">
                <div class="stat-card__num" style="font-size:22px;"><?= e($sitemapHealth['label']) ?></div>
                <div class="stat-card__label">Sitemap Health</div>
            </div>
        </a>
        <a class="stat-card" href="/admin/seo-audit.php" style="text-decoration:none;">
            <div class="stat-card__icon"><i class="fa-solid fa-magnifying-glass-chart"></i></div>
            <div class="stat-card__body">
                <div class="stat-card__num" style="font-size:22px;">SEO Audit</div>
                <div class="stat-card__label">Content and media quality checks</div>
            </div>
        </a>
    </div>

    <!-- ── Google Analytics 4 ───────────────────────────────────────────── -->
    <div class="admin-card" style="margin-bottom:24px;">
        <div class="admin-card__header">
            <h2><i class="fa-brands fa-google" style="color:#ea4335;"></i> Google Analytics
                <?php if ($ga4Data): ?>
                    <span style="display:inline-flex;align-items:center;gap:5px;background:#dcfce7;color:#166534;border-radius:999px;padding:3px 10px;font-size:11px;font-weight:700;margin-left:8px;">
                        <span style="width:6px;height:6px;border-radius:50%;background:#16a34a;display:inline-block;animation:ga4pulse 1.5s infinite;"></span>
                        <?= number_format($ga4Data['active_now']) ?> active now
                    </span>
                <?php endif; ?>
            </h2>
            <a href="/admin/analytics.php" style="font-size:13px;">Full report →</a>
        </div>

        <?php if (!vdj_ga4_ready()): ?>
            <div style="padding:24px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                <div style="flex:1;min-width:220px;">
                    <p style="font-weight:600;margin:0 0 4px;font-size:14px;">GA4 not configured</p>
                    <p style="font-size:13px;color:var(--text-muted);margin:0;">Connect your Google account in Analytics to see live traffic here.</p>
                </div>
                <a href="/admin/analytics.php#ga4-setup" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-gear"></i> Set up GA4
                </a>
            </div>

        <?php elseif ($ga4Error): ?>
            <div style="padding:16px 20px;color:#991b1b;font-size:13px;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                GA4 API error: <?= e($ga4Error) ?>
                — <a href="/admin/analytics.php#ga4-setup" style="color:#7f77dd;">check config</a>
            </div>

        <?php else:
            $s7  = $ga4Data['summary_7d'];
            $s28 = $ga4Data['summary_28d'];
            $avgDur = sprintf('%d:%02d', intdiv($s7['avg_duration'], 60), $s7['avg_duration'] % 60);
            // Sparkline data
            $sparkSessions = array_column($ga4Data['daily'], 'sessions');
            $sparkMax = max(array_map('intval', $sparkSessions) ?: [1]);
        ?>

        <!-- 7-day metric strip -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:1px;background:var(--border);">
            <?php
            $ga4Metrics = [
                ['label'=>'Sessions (7d)',   'value'=>number_format((int)$s7['sessions']),   'icon'=>'fa-arrow-pointer', 'color'=>'#7f77dd'],
                ['label'=>'Users (7d)',       'value'=>number_format((int)$s7['users']),      'icon'=>'fa-users',         'color'=>'#3b82f6'],
                ['label'=>'Page Views (7d)',  'value'=>number_format((int)$s7['pageviews']),  'icon'=>'fa-eye',           'color'=>'#10b981'],
                ['label'=>'Avg Session',      'value'=>$avgDur,                               'icon'=>'fa-clock',         'color'=>'#f59e0b'],
                ['label'=>'Bounce Rate',      'value'=>$s7['bounce_rate'].'%',               'icon'=>'fa-right-from-bracket','color'=>'#ef4444'],
                ['label'=>'Sessions (28d)',   'value'=>number_format((int)$s28['sessions']),  'icon'=>'fa-calendar',      'color'=>'#8b5cf6'],
            ];
            foreach ($ga4Metrics as $m): ?>
            <div style="background:#fff;padding:16px 18px;">
                <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:6px;">
                    <i class="fa-solid <?= $m['icon'] ?>" style="color:<?= $m['color'] ?>;margin-right:4px;"></i><?= $m['label'] ?>
                </div>
                <div style="font-size:22px;font-weight:700;font-family:'Manrope',sans-serif;color:var(--text);"><?= $m['value'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Sparkline + Top Pages + Countries -->
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:0;background:var(--border);">

            <!-- Top pages -->
            <div style="background:#fff;padding:20px;">
                <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:12px;">
                    Top Pages — last 7 days
                </div>
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <?php foreach (array_slice($ga4Data['top_pages'], 0, 7) as $pg): ?>
                    <tr style="border-bottom:1px solid var(--border);">
                        <td style="padding:7px 0;color:var(--text);max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <span title="<?= e($pg['pageTitle'] ?? '') ?>"><?= e(strlen($pg['pagePath']) > 45 ? substr($pg['pagePath'],0,45).'…' : $pg['pagePath']) ?></span>
                        </td>
                        <td style="padding:7px 0 7px 12px;text-align:right;color:var(--text-muted);white-space:nowrap;font-variant-numeric:tabular-nums;">
                            <?= number_format((int)$pg['screenPageViews']) ?> views
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Countries + Devices -->
            <div style="background:#fff;padding:20px;border-left:1px solid var(--border);">
                <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:12px;">
                    Top Countries
                </div>
                <?php
                $totalCtry = array_sum(array_column($ga4Data['countries'], 'sessions')) ?: 1;
                foreach (array_slice($ga4Data['countries'], 0, 6) as $c):
                    $pct = round((int)$c['sessions'] / $totalCtry * 100);
                ?>
                <div style="margin-bottom:9px;">
                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px;">
                        <span><?= e($c['country']) ?></span>
                        <span style="color:var(--text-muted);"><?= number_format((int)$c['sessions']) ?></span>
                    </div>
                    <div style="height:4px;background:var(--border);border-radius:2px;">
                        <div style="height:4px;width:<?= $pct ?>%;background:#7f77dd;border-radius:2px;"></div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (!empty($ga4Data['devices'])): ?>
                <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin:16px 0 10px;">Devices</div>
                <?php
                $totalDev = array_sum(array_column($ga4Data['devices'], 'sessions')) ?: 1;
                $devIcons = ['desktop'=>'fa-desktop','mobile'=>'fa-mobile-screen','tablet'=>'fa-tablet-screen-button'];
                foreach ($ga4Data['devices'] as $d):
                    $cat  = strtolower($d['deviceCategory']);
                    $icon = $devIcons[$cat] ?? 'fa-display';
                    $pct  = round((int)$d['sessions'] / $totalDev * 100);
                ?>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:7px;font-size:13px;">
                    <i class="fa-solid <?= $icon ?>" style="color:#7f77dd;width:16px;text-align:center;"></i>
                    <span style="flex:1;text-transform:capitalize;"><?= e($cat) ?></span>
                    <span style="color:var(--text-muted);"><?= $pct ?>%</span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div style="padding:10px 20px;background:#f8fafc;border-top:1px solid var(--border);font-size:11px;color:var(--text-muted);display:flex;justify-content:space-between;">
            <span>Property: <?= e(GA4_PROPERTY_ID) ?> &middot; Cached 5 min</span>
            <span>Updated: <?= date('H:i:s', $ga4Data['fetched_at']) ?></span>
        </div>

        <?php endif; ?>
    </div>

    <style>
    @keyframes ga4pulse { 0%,100%{opacity:1}50%{opacity:.3} }
    </style>

    <!-- ── Quick Actions ────────────────────────────────────────────────── -->
    <div class="quick-actions">
        <a href="/register" class="quick-action-card">
            <div class="qa-icon"><i class="fa-solid fa-user-plus"></i></div>
            <div class="qa-label">Add User</div>
            <div class="qa-desc">Register a new account</div>
        </a>
        <a href="/create" class="quick-action-card">
            <div class="qa-icon"><i class="fa-solid fa-plus"></i></div>
            <div class="qa-label">Create Board</div>
            <div class="qa-desc">Start a new design board</div>
        </a>
        <a href="/admin/blog.php" class="quick-action-card">
            <div class="qa-icon"><i class="fa-solid fa-newspaper"></i></div>
            <div class="qa-label">View Blog</div>
            <div class="qa-desc">Manage blog posts</div>
        </a>
        <a href="/admin/analytics.php" class="quick-action-card">
            <div class="qa-icon"><i class="fa-solid fa-chart-line"></i></div>
            <div class="qa-label">Analytics</div>
            <div class="qa-desc">Growth &amp; insights</div>
        </a>
        <a href="/admin/sources.php" class="quick-action-card">
            <div class="qa-icon"><i class="fa-solid fa-link"></i></div>
            <div class="qa-label">Source Import</div>
            <div class="qa-desc">Add curated external references</div>
        </a>
        <a href="/admin/discovery.php" class="quick-action-card">
            <div class="qa-icon"><i class="fa-solid fa-rss"></i></div>
            <div class="qa-label">Discovery Queue</div>
            <div class="qa-desc">Moderate submitted references</div>
        </a>
        <a href="/admin/sponsors.php" class="quick-action-card">
            <div class="qa-icon"><i class="fa-solid fa-handshake"></i></div>
            <div class="qa-label">Sponsors</div>
            <div class="qa-desc">Review partnership leads</div>
        </a>
        <a href="/admin/features.php" class="quick-action-card">
            <div class="qa-icon"><i class="fa-solid fa-cubes-stacked"></i></div>
            <div class="qa-label">100 Features</div>
            <div class="qa-desc">Admin OS feature matrix</div>
        </a>
    </div>

    <!-- ── Two-column: Top Boards + Recent Signups ───────────────────────── -->
    <div class="section-grid">

        <!-- Top 5 Most Liked Boards -->
        <div class="admin-card">
            <div class="admin-card__header">
                <h2><i class="fa-solid fa-fire"></i> Top 5 Most Liked Boards</h2>
                <a href="/admin/boards.php">View all</a>
            </div>
            <?php if ($topBoards): ?>
            <div>
                <?php foreach ($topBoards as $i => $board): ?>
                <div class="top-board-item">
                    <span class="rank"><?= $i + 1 ?></span>
                    <div class="board-info">
                        <div class="board-title"><?= e($board['title']) ?></div>
                        <div class="board-author">by <?= e($board['username']) ?></div>
                    </div>
                    <div class="board-likes">
                        <i class="fa-solid fa-heart"></i>
                        <?= number_format($board['likes']) ?>
                    </div>
                    <a href="/board.php?id=<?= (int)$board['id'] ?>" target="_blank" class="btn btn-sm btn-accent" style="margin-left:8px;">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-heart"></i>
                <p>No boards with likes yet.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Signups -->
        <div class="admin-card">
            <div class="admin-card__header">
                <h2><i class="fa-solid fa-users"></i> Recent Signups</h2>
                <a href="/admin/users.php">View all</a>
            </div>
            <?php if ($recentUsers): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Boards</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentUsers as $user): ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="user-avatar-placeholder">
                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                </div>
                                <div class="user-info">
                                    <div class="user-name">
                                        <?= e($user['username']) ?>
                                        <?php if ($user['is_admin']): ?>
                                            <span class="badge badge-admin" style="margin-left:4px;">Admin</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="user-email"><?= e($user['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--text-muted);"><?= (int)$user['board_count'] ?></td>
                        <td style="color:var(--text-muted);font-size:12px;"><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-users"></i>
                <p>No users yet.</p>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ── Recent Boards (full width) ───────────────────────────────────── -->
    <div class="admin-card">
        <div class="admin-card__header">
            <h2><i class="fa-solid fa-border-all"></i> Recent Boards</h2>
            <a href="/admin/boards.php">View all boards</a>
        </div>
        <?php if ($recentBoards): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Cover</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Likes</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentBoards as $board): ?>
                <tr>
                    <td>
                        <?php if (!empty($board['cover_image'])): ?>
                            <img src="<?= e(imageUrl($board['cover_image'])) ?>"
                                 alt=""
                                 class="board-cover">
                        <?php else: ?>
                            <div class="board-cover-placeholder">
                                <i class="fa-solid fa-image"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="font-weight:600;color:var(--text);"><?= e($board['title']) ?></span>
                    </td>
                    <td>
                        <span style="color:var(--text-muted);font-size:13px;"><?= e($board['username']) ?></span>
                    </td>
                    <td>
                        <span style="display:flex;align-items:center;gap:4px;color:#f43f5e;font-weight:600;font-size:13px;">
                            <i class="fa-solid fa-heart" style="font-size:11px;"></i>
                            <?= number_format((int)$board['like_count']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($board['is_draft']): ?>
                            <span class="badge badge-draft"><i class="fa-solid fa-file-pen"></i> Draft</span>
                        <?php else: ?>
                            <span class="badge badge-published"><i class="fa-solid fa-circle-check"></i> Published</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--text-muted);font-size:12px;white-space:nowrap;">
                        <?= date('M j, Y', strtotime($board['created_at'])) ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="/board.php?id=<?= (int)$board['id'] ?>"
                               target="_blank"
                               class="btn btn-sm btn-accent"
                               title="View board">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="/admin/boards.php?edit=<?= (int)$board['id'] ?>"
                               class="btn btn-sm btn-warning"
                               title="Edit board">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fa-solid fa-border-all"></i>
            <p>No boards have been created yet.</p>
        </div>
        <?php endif; ?>
    </div>


    <!-- ── Newsletter Export ─────────────────────────────────────────────── -->
    <div class="admin-card" style="max-width:480px;">
        <div class="admin-card__header">
            <h2><i class="fa-solid fa-file-csv"></i> Export Newsletter Subscribers</h2>
        </div>
        <form method="post" action="/admin/export-subscribers.php" target="_blank" style="display:flex;align-items:center;gap:12px;padding:4px 0 8px;">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <label for="export-status" style="font-size:13px;font-weight:500;color:var(--text-muted);white-space:nowrap;">Filter by status</label>
            <select id="export-status" name="status" class="form-select" style="flex:1;min-width:0;">
                <option value="all">All subscribers</option>
                <option value="active">Active only</option>
                <option value="unsubscribed">Unsubscribed only</option>
            </select>
            <button type="submit" class="btn btn-sm btn-accent" style="white-space:nowrap;">
                <i class="fa-solid fa-download"></i> Download CSV
            </button>
        </form>
    </div>

</main>
</body>
</html>
