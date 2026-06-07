<?php
require_once __DIR__ . '/_guard.php';

$currentPage = 'roadmap';

$roadmapGroups = [
    'Now / Ship Queue' => [
        ['Editorial collection planning', 'Content', 'Plan weekly hand-picked collections from existing boards.', 'High', 'This can run manually from boards + archive until a collection table is needed.'],
        ['Curator application review', 'Community', 'Review curator applicants before giving verified or publishing privileges.', 'High', 'Start with a simple inbox workflow, then add email notifications.'],
        ['Community submission scoring', 'Community', 'Score pending submissions by source quality, tag fit, and copyright risk.', 'High', 'Use this to keep public submissions curated instead of random.'],
        ['Subscriber export', 'Newsletter', 'Export active subscribers for email tools or sponsor reporting.', 'Medium', 'CSV export is enough for v1.'],
        ['Environment health checks', 'System', 'Check PHP version, DB connection, writable uploads, schema columns, and feed endpoints.', 'High', 'This prevents silent deploy breaks.'],
    ],
    'Monetization Build' => [
        ['Partner resource slots', 'Monetization', 'Sell curated placements for design tools, courses, templates, and events.', 'High', 'Best fit for Turkey/global payments without intrusive ad networks.'],
        ['Manual invoice workflow', 'Monetization', 'Track sponsor invoice state outside automated payment integrations.', 'Medium', 'Useful before iyzico/PayTR is worth the complexity.'],
        ['PayTR payment link workflow', 'Monetization', 'Store PayTR payment links for sponsors or premium access.', 'Medium', 'Can begin as manual links in admin.'],
        ['iyzico checkout workflow', 'Monetization', 'Future checkout integration for premium curator plans.', 'Medium', 'Only after product-market fit.'],
        ['Revenue analytics', 'Analytics', 'Track sponsor leads, won/lost status, estimated revenue, and monthly growth.', 'High', 'Use sponsor lead statuses already in place.'],
    ],
    'Curator Premium' => [
        ['Private collections', 'Premium', 'Let curators build private moodboards before publishing.', 'High', 'Maps cleanly to board visibility states.'],
        ['Advanced source discovery', 'Premium', 'Power-user source library with filters, saved references, and approved-only feeds.', 'High', 'Build on source_bookmarks + source_feeds.'],
        ['ZIP board export', 'Premium', 'Package board images, credits, captions, and metadata into a ZIP.', 'Medium', 'Must respect external source credits.'],
        ['Moodboard presentation mode', 'Premium', 'Fullscreen board deck mode for portfolio review or client presentation.', 'Medium', 'Can be front-end only first.'],
        ['Portfolio custom domain', 'Premium', 'Curator portfolio page mapped to a custom domain.', 'Low', 'Later, after main domain migration.'],
        ['Brandable curator page', 'Premium', 'Curator profile with custom colors, hero layout, social links, and featured boards.', 'Medium', 'Profile portfolio redesign already gives the base.'],
        ['Curator analytics pages', 'Premium', 'Views, saves, profile visits, board growth, and top styles per curator.', 'High', 'Needs event table or GA4 export later.'],
        ['Weekly trend reports', 'Premium', 'Editorial PDF/email reports for styles, boards, sources, and blog traffic.', 'Medium', 'Start as manually generated reports.'],
        ['Team curation workspace', 'Premium', 'Shared boards, reviewer roles, approval comments, and team collections.', 'Low', 'V2+ feature after user growth.'],
    ],
    'Retention' => [
        ['New board digest', 'Newsletter', 'Weekly email of new curated boards.', 'High', 'Can use board RSS + newsletter table.'],
        ['New trend digest', 'Newsletter', 'Monthly trend digest by movement, color, and popular tags.', 'Medium', 'Good sponsor inventory too.'],
        ['Curator spotlight digest', 'Newsletter', 'Feature one curator and their boards each week.', 'Medium', 'Pairs well with featured curator module.'],
        ['Preference center', 'Newsletter', 'Let subscribers pick blog, boards, trends, or sponsor updates.', 'Low', 'Needs tokenized subscriber preferences.'],
        ['Unsubscribe workflow', 'Newsletter', 'One-click unsubscribe for compliance and trust.', 'High', 'Add before sending real campaigns.'],
    ],
    'System Safety' => [
        ['Rollback package workflow', 'Deploy', 'Keep last working ZIP and restore it from admin if deploy breaks.', 'High', 'Important while deploying manually.'],
        ['Reduced-motion setting', 'UX', 'Respect users who prefer less animation.', 'Medium', 'CSS and localStorage toggle.'],
        ['Auto-style matching', 'Discovery', 'Suggest existing style tags for submitted references.', 'Medium', 'Keyword matching only; no AI needed.'],
    ],
];

$priorityCounts = ['High' => 0, 'Medium' => 0, 'Low' => 0];
$total = 0;
foreach ($roadmapGroups as $items) {
    foreach ($items as $item) {
        $priorityCounts[$item[3]]++;
        $total++;
    }
}

$focus = $_GET['feature'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roadmap — Admin — Visual Design Journey</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/admin/admin.css?v=1.2">
    <style>
        .roadmap-hero {
            display:grid;
            grid-template-columns:minmax(0,1.35fr) minmax(280px,.65fr);
            gap:18px;
            align-items:stretch;
            margin-bottom:22px;
        }
        .roadmap-card,
        .roadmap-stat,
        .roadmap-group {
            background:#fff;
            border:1px solid var(--border);
            border-radius:var(--radius);
            box-shadow:var(--shadow);
        }
        .roadmap-card {
            padding:26px;
            background:
                radial-gradient(circle at top right, rgba(127,119,221,.16), transparent 38%),
                #fff;
        }
        .roadmap-kicker {
            display:inline-flex;
            align-items:center;
            gap:8px;
            color:var(--accent);
            font-size:12px;
            font-weight:800;
            letter-spacing:.08em;
            text-transform:uppercase;
            margin-bottom:10px;
        }
        .roadmap-card h2 {
            font-family:'Manrope',sans-serif;
            font-size:clamp(26px,4vw,40px);
            line-height:1.05;
            letter-spacing:-.04em;
            margin-bottom:12px;
        }
        .roadmap-card p { color:var(--text-muted); max-width:760px; }
        .roadmap-stats {
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:12px;
        }
        .roadmap-stat { padding:16px; }
        .roadmap-stat strong {
            display:block;
            color:var(--accent);
            font-size:30px;
            line-height:1;
            margin-bottom:6px;
        }
        .roadmap-stat span {
            font-size:11px;
            color:var(--text-muted);
            font-weight:800;
            text-transform:uppercase;
            letter-spacing:.06em;
        }
        .roadmap-grid {
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:18px;
        }
        .roadmap-group { overflow:hidden; }
        .roadmap-group__head {
            padding:17px 18px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            border-bottom:1px solid var(--border);
        }
        .roadmap-group__head h2 { font-size:16px; margin:0; }
        .roadmap-group__head span {
            color:var(--text-muted);
            font-size:12px;
            font-weight:700;
        }
        .roadmap-item {
            display:grid;
            grid-template-columns:minmax(0,1fr) auto;
            gap:12px;
            padding:15px 18px;
            border-bottom:1px solid #f1f5f9;
        }
        .roadmap-item:last-child { border-bottom:0; }
        .roadmap-item.is-focused {
            background:rgba(127,119,221,.08);
            box-shadow:inset 3px 0 0 var(--accent);
        }
        .roadmap-item h3 {
            font-size:14px;
            margin-bottom:5px;
        }
        .roadmap-item p {
            font-size:13px;
            color:var(--text-muted);
            margin-bottom:8px;
        }
        .roadmap-note {
            display:flex;
            gap:8px;
            color:#475569;
            font-size:12px;
            line-height:1.45;
        }
        .roadmap-note i { color:var(--accent); margin-top:2px; }
        .roadmap-meta {
            display:flex;
            gap:7px;
            flex-wrap:wrap;
            margin-top:9px;
        }
        .roadmap-pill {
            display:inline-flex;
            align-items:center;
            border-radius:999px;
            padding:4px 8px;
            background:#f8fafc;
            border:1px solid var(--border);
            color:var(--text-muted);
            font-size:10px;
            font-weight:800;
            letter-spacing:.04em;
            text-transform:uppercase;
            white-space:nowrap;
        }
        .roadmap-priority {
            border-radius:999px;
            padding:5px 9px;
            font-size:10px;
            font-weight:900;
            letter-spacing:.04em;
            text-transform:uppercase;
            height:max-content;
            white-space:nowrap;
        }
        .roadmap-priority--high { background:rgba(239,68,68,.1); color:#b91c1c; }
        .roadmap-priority--medium { background:var(--warning-light); color:#92400e; }
        .roadmap-priority--low { background:#f1f5f9; color:#475569; }
        .roadmap-actions {
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            margin-top:18px;
        }
        .roadmap-actions a {
            text-decoration:none;
            display:inline-flex;
            align-items:center;
            gap:8px;
            border-radius:999px;
            padding:9px 14px;
            font-size:13px;
            font-weight:800;
            border:1px solid var(--border);
            background:#fff;
        }
        .roadmap-actions a:first-child {
            background:var(--accent);
            color:#fff;
            border-color:var(--accent);
        }
        @media (max-width:1100px) {
            .roadmap-hero,
            .roadmap-grid { grid-template-columns:1fr; }
        }
        @media (max-width:600px) {
            .roadmap-stats { grid-template-columns:1fr 1fr; }
            .roadmap-item { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/_sidebar.php'; ?>

<main class="admin-main">
    <div class="admin-topbar">
        <div class="admin-header">
            <h1>Roadmap</h1>
            <p>Coming soon modüllerini gerçek ürün kuyruğu olarak takip et.</p>
        </div>
        <button class="hamburger" id="hamburger" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <section class="roadmap-hero">
        <div class="roadmap-card">
            <span class="roadmap-kicker"><i class="fa-solid fa-route"></i> Product Roadmap</span>
            <h2>Coming soon listesi artık aksiyon planı.</h2>
            <p>Bu sayfa hangi özelliğin önce yapılacağını, hangi modüle bağlandığını ve v1 sonrası ana domaine taşınırken neyin kritik olduğunu gösterir.</p>
            <div class="roadmap-actions">
                <a href="/admin/features.php"><i class="fa-solid fa-cubes-stacked"></i> 100 Feature Matrix</a>
                <a href="/premium.php" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-crown"></i> Premium Preview</a>
                <a href="/admin/sponsors.php"><i class="fa-solid fa-handshake"></i> Sponsor Leads</a>
            </div>
        </div>
        <div class="roadmap-stats">
            <div class="roadmap-stat"><strong><?= $total ?></strong><span>Queued modules</span></div>
            <div class="roadmap-stat"><strong><?= $priorityCounts['High'] ?></strong><span>High priority</span></div>
            <div class="roadmap-stat"><strong><?= $priorityCounts['Medium'] ?></strong><span>Medium</span></div>
            <div class="roadmap-stat"><strong><?= $priorityCounts['Low'] ?></strong><span>Later</span></div>
        </div>
    </section>

    <section class="roadmap-grid">
        <?php foreach ($roadmapGroups as $group => $items): ?>
            <article class="roadmap-group">
                <div class="roadmap-group__head">
                    <h2><?= e($group) ?></h2>
                    <span><?= count($items) ?> modules</span>
                </div>
                <?php foreach ($items as $item):
                    [$title, $area, $desc, $priority, $note] = $item;
                    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
                    $slug = trim($slug, '-');
                    $priorityClass = strtolower($priority);
                ?>
                    <div class="roadmap-item <?= $focus === $slug ? 'is-focused' : '' ?>" id="<?= e($slug) ?>">
                        <div>
                            <h3><?= e($title) ?></h3>
                            <p><?= e($desc) ?></p>
                            <div class="roadmap-note">
                                <i class="fa-solid fa-circle-info"></i>
                                <span><?= e($note) ?></span>
                            </div>
                            <div class="roadmap-meta">
                                <span class="roadmap-pill"><?= e($area) ?></span>
                                <span class="roadmap-pill">No AI dependency</span>
                            </div>
                        </div>
                        <span class="roadmap-priority roadmap-priority--<?= e($priorityClass) ?>"><?= e($priority) ?></span>
                    </div>
                <?php endforeach; ?>
            </article>
        <?php endforeach; ?>
    </section>
</main>
</body>
</html>
