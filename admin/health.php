<?php
require_once __DIR__ . '/_guard.php';
$currentPage = 'health';

// ── Helper ─────────────────────────────────────────────────────────────────
function hc(string $status, string $label, string $detail = ''): array {
    return ['status' => $status, 'label' => $label, 'detail' => $detail];
}

$results = []; // keyed by section

// ── 1. PHP Environment ─────────────────────────────────────────────────────
$phpVer = PHP_VERSION;
$verOk  = version_compare($phpVer, '8.0.0', '>=');
$ver74  = version_compare($phpVer, '7.4.0', '>=');
if ($verOk) {
    $results['PHP Environment'][] = hc('pass', 'PHP Version', $phpVer);
} elseif ($ver74) {
    $results['PHP Environment'][] = hc('warn', 'PHP Version', $phpVer . ' (upgrade to 8.0+ recommended)');
} else {
    $results['PHP Environment'][] = hc('fail', 'PHP Version', $phpVer . ' (8.0+ required)');
}

$extensions = [
    'pdo_mysql' => 'pass',
    'json'      => 'pass',
    'mbstring'  => 'pass',
    'fileinfo'  => 'pass',
];
foreach ($extensions as $ext => $defaultStatus) {
    if (extension_loaded($ext)) {
        $results['PHP Environment'][] = hc('pass', 'Extension: ' . $ext, 'loaded');
    } else {
        $results['PHP Environment'][] = hc('fail', 'Extension: ' . $ext, 'not loaded');
    }
}
// gd or imagick — warn if neither
if (extension_loaded('gd') || extension_loaded('imagick')) {
    $which = extension_loaded('imagick') ? 'imagick' : 'gd';
    $results['PHP Environment'][] = hc('pass', 'Extension: gd / imagick', $which . ' loaded');
} else {
    $results['PHP Environment'][] = hc('warn', 'Extension: gd / imagick', 'neither loaded — image processing unavailable');
}

// ── 2. Database connection ─────────────────────────────────────────────────
// If we got here, _guard.php already connected successfully
$results['Database'][] = hc('pass', 'Database connection', 'PDO connected (via _guard.php)');

// ── 3. Schema tables ───────────────────────────────────────────────────────
$tables = [
    'users', 'boards', 'board_likes', 'board_saves', 'board_comments', 'board_images',
    'newsletter_subscribers', 'sponsor_leads', 'source_bookmarks', 'source_feeds',
];
$anyTableFail = false;
foreach ($tables as $table) {
    try {
        $db->query('SELECT 1 FROM `' . $table . '` LIMIT 1');
        $results['Database Tables'][] = hc('pass', 'Table: ' . $table, 'exists');
    } catch (PDOException $ex) {
        $results['Database Tables'][] = hc('fail', 'Table: ' . $table, 'missing or inaccessible');
        $anyTableFail = true;
    }
}

// ── 4. Schema columns ──────────────────────────────────────────────────────
$columnChecks = [
    ['table' => 'boards',                 'col' => 'view_count'],
    ['table' => 'boards',                 'col' => 'save_count'],
    ['table' => 'users',                  'col' => 'is_verified'],
    ['table' => 'board_images',           'col' => 'source_url'],
    ['table' => 'newsletter_subscribers', 'col' => 'unsubscribe_token'],
];
$anyColFail = false;
foreach ($columnChecks as $chk) {
    $label = $chk['table'] . '.' . $chk['col'];
    try {
        $db->query('SELECT `' . $chk['col'] . '` FROM `' . $chk['table'] . '` LIMIT 1');
        $results['Database Columns'][] = hc('pass', 'Column: ' . $label, 'exists');
    } catch (PDOException $ex) {
        $results['Database Columns'][] = hc('fail', 'Column: ' . $label, 'missing — run schema-update.sql');
        $anyColFail = true;
    }
}

// ── 5. File system ─────────────────────────────────────────────────────────
$uploadsDir = __DIR__ . '/../uploads';
if (is_dir($uploadsDir) && is_writable($uploadsDir)) {
    $results['File System'][] = hc('pass', 'uploads/ writable', realpath($uploadsDir));
} elseif (!is_dir($uploadsDir)) {
    $results['File System'][] = hc('fail', 'uploads/ directory', 'directory does not exist');
} else {
    $results['File System'][] = hc('fail', 'uploads/ writable', 'not writable — check permissions');
}

$configDb = __DIR__ . '/../config/db.php';
if (file_exists($configDb)) {
    $results['File System'][] = hc('pass', 'config/db.php', 'present');
} else {
    $results['File System'][] = hc('fail', 'config/db.php', 'missing');
}

// ── 6. Security ────────────────────────────────────────────────────────────
$installed = __DIR__ . '/../config/.installed';
if (file_exists($installed)) {
    $results['Security'][] = hc('pass', 'Install lock (config/.installed)', 'present — install.php protected');
} else {
    $results['Security'][] = hc('warn', 'Install lock (config/.installed)', 'missing — install.php may be accessible');
}

// ── 7. Feed endpoints (informational) ─────────────────────────────────────
$feedLinks = [
    'RSS Feed'  => '/feed.php',
    'Sitemap'   => '/sitemap.php',
];
foreach ($feedLinks as $name => $path) {
    $url = rtrim(defined('SITE_URL') ? SITE_URL : '', '/') . $path;
    $results['Feed Endpoints'][] = hc('info', $name, $url);
}

// ── Tally ──────────────────────────────────────────────────────────────────
$counts = ['pass' => 0, 'warn' => 0, 'fail' => 0, 'info' => 0];
foreach ($results as $section) {
    foreach ($section as $r) {
        $counts[$r['status']] = ($counts[$r['status']] ?? 0) + 1;
    }
}
$dbSchemaFail = $anyTableFail || $anyColFail;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Health Check — Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/admin/admin.css">
<style>
:root {
    --accent:        #7F77DD;
    --accent-light:  rgba(127,119,221,.12);
    --sidebar-bg:    #0f172a;
    --sidebar-width: 240px;
    --text:          #1e293b;
    --text-muted:    #64748b;
    --border:        #e2e8f0;
    --bg:            #f8fafc;
    --white:         #ffffff;
    --danger:        #ef4444;
    --danger-light:  #fee2e2;
    --success:       #22c55e;
    --warn:          #f59e0b;
    --warn-light:    #fef3c7;
    --radius:        10px;
    --shadow:        0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
    --shadow-md:     0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -1px rgba(0,0,0,.06);
}
*,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }

/* Sidebar */
.admin-sidebar { width: var(--sidebar-width); background: var(--sidebar-bg); color: #fff; display: flex; flex-direction: column; position: fixed; top: 0; left: 0; bottom: 0; z-index: 100; transition: transform .25s ease; }
.admin-sidebar__logo { padding: 24px 20px 20px; border-bottom: 1px solid rgba(255,255,255,.08); }
.admin-sidebar__logo .brand { font-family: 'Manrope', sans-serif; font-weight: 700; font-size: 15px; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; }
.admin-sidebar__logo .brand i { color: var(--accent); font-size: 18px; }
.admin-sidebar__logo .admin-chip { display: inline-block; background: var(--accent); color: #fff; font-size: 10px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; padding: 2px 7px; border-radius: 20px; margin-top: 6px; }
.admin-sidebar__nav { flex: 1; padding: 16px 0; overflow-y: auto; }
.admin-sidebar__nav a { display: flex; align-items: center; gap: 12px; padding: 10px 20px; color: rgba(255,255,255,.65); text-decoration: none; font-size: 14px; font-weight: 500; border-left: 3px solid transparent; transition: all .15s ease; }
.admin-sidebar__nav a i { width: 16px; text-align: center; font-size: 14px; }
.admin-sidebar__nav a:hover { color: #fff; background: rgba(255,255,255,.06); }
.admin-sidebar__nav a.active { color: #fff; background: rgba(127,119,221,.18); border-left-color: var(--accent); }
.nav-section-label { padding: 16px 20px 6px; font-size: 10px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: rgba(255,255,255,.3); }
.admin-sidebar__footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,.08); }
.admin-sidebar__footer a { display: flex; align-items: center; gap: 10px; color: rgba(255,255,255,.5); text-decoration: none; font-size: 13px; transition: color .15s; }
.admin-sidebar__footer a:hover { color: #fff; }

/* Main */
.admin-main { margin-left: var(--sidebar-width); flex: 1; overflow-y: auto; padding: 32px; }
.admin-topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; gap: 16px; }
.admin-header h1 { font-family: 'Manrope', sans-serif; font-size: 24px; font-weight: 700; }
.admin-header p { font-size: 14px; color: var(--text-muted); margin-top: 3px; }
.hamburger { display: none; background: var(--white); border: 1px solid var(--border); border-radius: 8px; padding: 8px 11px; cursor: pointer; font-size: 16px; color: var(--text); }

/* Summary banner */
.health-banner { border-radius: var(--radius); padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 14px; }
.health-banner.ok   { background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; }
.health-banner.warn { background: var(--warn-light); border: 1px solid #fde68a; color: #92400e; }
.health-banner.fail { background: var(--danger-light); border: 1px solid #fecaca; color: #991b1b; }
.health-banner i { font-size: 20px; }
.health-banner__title { font-weight: 700; font-size: 15px; }
.health-banner__detail { font-size: 13px; margin-top: 2px; opacity: .85; }
.banner-action { margin-left: auto; }

/* Tally pills */
.tally { display: flex; gap: 10px; margin-bottom: 28px; flex-wrap: wrap; }
.tally-pill { display: flex; align-items: center; gap: 7px; padding: 7px 14px; border-radius: 30px; font-size: 13px; font-weight: 600; }
.tally-pill.pass { background: #dcfce7; color: #166534; }
.tally-pill.warn { background: var(--warn-light); color: #92400e; }
.tally-pill.fail { background: var(--danger-light); color: #991b1b; }

/* Section cards */
.health-section { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 20px; overflow: hidden; }
.health-section__header { padding: 14px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; }
.health-section__header h2 { font-size: 15px; font-weight: 600; color: var(--text); }
.health-section__header i { color: var(--accent); }

/* Check rows */
.check-row { display: flex; align-items: flex-start; gap: 12px; padding: 11px 20px; border-bottom: 1px solid var(--border); }
.check-row:last-child { border-bottom: none; }
.check-icon { margin-top: 1px; width: 18px; text-align: center; font-size: 15px; flex-shrink: 0; }
.check-icon.pass { color: var(--success); }
.check-icon.warn { color: var(--warn); }
.check-icon.fail { color: var(--danger); }
.check-icon.info { color: var(--accent); }
.check-label { font-size: 14px; font-weight: 500; color: var(--text); }
.check-detail { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
.check-detail a { color: var(--accent); text-decoration: none; }
.check-detail a:hover { text-decoration: underline; }

/* Responsive */
@media (max-width: 768px) {
    .admin-sidebar { transform: translateX(-100%); }
    .admin-sidebar.open { transform: translateX(0); }
    .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 99; }
    .sidebar-overlay.open { display: block; }
    .admin-main { margin-left: 0; padding: 20px 16px; }
    .hamburger { display: block; }
}
</style>
</head>
<body>
<?php require_once __DIR__ . '/_sidebar.php'; ?>
<main class="admin-main">
    <div class="admin-topbar">
        <button class="hamburger" id="hamburger" aria-label="Toggle menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="admin-header">
            <h1><i class="fa-solid fa-heart-pulse" style="color:var(--accent);margin-right:8px;"></i>Health Check</h1>
            <p>Environment diagnostics — run at <?= e(date('Y-m-d H:i:s')) ?></p>
        </div>
        <a href="/admin/health.php" class="btn btn-secondary btn-sm" style="display:inline-flex;align-items:center;gap:7px;padding:8px 14px;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none;background:var(--white);border:1px solid var(--border);color:var(--text);">
            <i class="fa-solid fa-rotate-right"></i> Re-run
        </a>
    </div>

    <?php
    // Banner
    if ($counts['fail'] > 0):
        $bannerClass = 'fail'; $bannerIcon = 'fa-circle-xmark'; $bannerTitle = 'Action required';
    elseif ($counts['warn'] > 0):
        $bannerClass = 'warn'; $bannerIcon = 'fa-triangle-exclamation'; $bannerTitle = 'Warnings present';
    else:
        $bannerClass = 'ok'; $bannerIcon = 'fa-circle-check'; $bannerTitle = 'System healthy';
    endif;
    ?>
    <div class="health-banner <?= $bannerClass ?>">
        <i class="fa-solid <?= $bannerIcon ?>"></i>
        <div>
            <div class="health-banner__title"><?= $bannerTitle ?></div>
            <div class="health-banner__detail">
                <?= $counts['pass'] ?> passed &nbsp;&middot;&nbsp;
                <?= $counts['warn'] ?> warning<?= $counts['warn'] !== 1 ? 's' : '' ?> &nbsp;&middot;&nbsp;
                <?= $counts['fail'] ?> failure<?= $counts['fail'] !== 1 ? 's' : '' ?>
            </div>
        </div>
        <?php if ($dbSchemaFail): ?>
        <div class="banner-action">
            <a href="/admin/schema-update.sql" class="btn btn-secondary btn-sm" download
               style="display:inline-flex;align-items:center;gap:7px;padding:7px 13px;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none;background:var(--white);border:1px solid var(--border);color:var(--text);">
                <i class="fa-solid fa-file-code"></i> Download schema-update.sql
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tally pills -->
    <div class="tally">
        <div class="tally-pill pass"><i class="fa-solid fa-circle-check"></i> <?= $counts['pass'] ?> passed</div>
        <?php if ($counts['warn']): ?>
        <div class="tally-pill warn"><i class="fa-solid fa-triangle-exclamation"></i> <?= $counts['warn'] ?> warning<?= $counts['warn'] !== 1 ? 's' : '' ?></div>
        <?php endif; ?>
        <?php if ($counts['fail']): ?>
        <div class="tally-pill fail"><i class="fa-solid fa-circle-xmark"></i> <?= $counts['fail'] ?> failure<?= $counts['fail'] !== 1 ? 's' : '' ?></div>
        <?php endif; ?>
    </div>

    <?php
    $sectionIcons = [
        'PHP Environment'  => 'fa-code',
        'Database'         => 'fa-database',
        'Database Tables'  => 'fa-table',
        'Database Columns' => 'fa-table-columns',
        'File System'      => 'fa-folder-open',
        'Security'         => 'fa-shield-halved',
        'Feed Endpoints'   => 'fa-rss',
    ];
    $statusIcons = [
        'pass' => 'fa-circle-check',
        'warn' => 'fa-triangle-exclamation',
        'fail' => 'fa-circle-xmark',
        'info' => 'fa-circle-info',
    ];
    foreach ($results as $sectionName => $checks):
    ?>
    <div class="health-section">
        <div class="health-section__header">
            <i class="fa-solid <?= $sectionIcons[$sectionName] ?? 'fa-circle' ?>"></i>
            <h2><?= e($sectionName) ?></h2>
        </div>
        <?php foreach ($checks as $chk): ?>
        <div class="check-row">
            <div class="check-icon <?= $chk['status'] ?>">
                <i class="fa-solid <?= $statusIcons[$chk['status']] ?>"></i>
            </div>
            <div>
                <div class="check-label"><?= e($chk['label']) ?></div>
                <?php if ($chk['detail'] !== ''): ?>
                <div class="check-detail">
                    <?php if ($chk['status'] === 'info' && filter_var($chk['detail'], FILTER_VALIDATE_URL)): ?>
                        <a href="<?= e($chk['detail']) ?>" target="_blank" rel="noopener noreferrer"><?= e($chk['detail']) ?> <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i></a>
                    <?php else: ?>
                        <?= e($chk['detail']) ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

</main>
</body>
</html>
