<?php
require_once __DIR__ . '/_guard.php';

$currentPage = 'features';

$featureGroups = [
    'Content Control' => [
        ['Board moderation queue', 'live', '/admin/boards.php'],
        ['Draft/published board status', 'live', '/admin/boards.php'],
        ['Board delete controls', 'live', '/admin/boards.php'],
        ['Board edit shortcut', 'live', '/admin/boards.php'],
        ['Source URL importer', 'live', '/admin/sources.php'],
        ['Discovery approval queue', 'live', '/admin/discovery.php'],
        ['RSS/Atom source feeds', 'live', '/admin/discovery.php'],
        ['Reference reject workflow', 'live', '/admin/discovery.php'],
        ['Featured board workflow', 'live', '/admin/boards.php'],
        ['Editorial collection planning', 'live', '/admin/digest.php'],
    ],
    'Curator & Community' => [
        ['User directory', 'live', '/admin/users.php'],
        ['Admin role toggle', 'live', '/admin/users.php'],
        ['Verified curator badge', 'live', '/admin/users.php'],
        ['User delete controls', 'live', '/admin/users.php'],
        ['Profile preview links', 'live', '/admin/users.php'],
        ['Curator portfolio profiles', 'live', '/curators.php'],
        ['Follower graph overview', 'live', '/admin/analytics.php'],
        ['Featured curator slot', 'live', '/curators.php'],
        ['Curator application review', 'live', '/admin/curators.php'],
        ['Community submission scoring', 'live', '/admin/discovery.php'],
    ],
    'Blog & SEO' => [
        ['Blog post browser', 'live', '/admin/blog.php'],
        ['Blog search/filter tools', 'live', '/admin/blog.php'],
        ['Imported WordPress archive view', 'live', '/admin/blog.php'],
        ['Article canonical URLs', 'live', '/blog.php'],
        ['Article Open Graph tags', 'live', '/blog.php'],
        ['Article schema markup', 'live', '/blog.php'],
        ['Dynamic sitemap', 'live', '/sitemap.xml'],
        ['RSS feed', 'live', '/feed.xml'],
        ['Board RSS feed', 'live', '/boards-feed.xml'],
        ['SEO issue checklist', 'live', '/admin/health.php'],
    ],
    'Discovery Engine' => [
        ['Public submit-reference form', 'live', '/submit-source.php'],
        ['Open Graph metadata extraction', 'live', '/admin/discovery.php'],
        ['Pending source queue', 'live', '/admin/discovery.php'],
        ['Approve into board', 'live', '/admin/discovery.php'],
        ['Feed discovery runner', 'live', '/cron/discover.php'],
        ['Suggested tags on sources', 'live', '/admin/discovery.php'],
        ['Source platform tracking', 'live', '/admin/discovery.php'],
        ['Credit/caption metadata', 'live', '/admin/sources.php'],
        ['Duplicate source prevention', 'live', '/admin/discovery.php'],
        ['Auto-style matching', 'live', '/submit-source.php'],
    ],
    'Analytics' => [
        ['Admin analytics dashboard', 'live', '/admin/analytics.php'],
        ['User growth chart', 'live', '/admin/analytics.php'],
        ['Board growth chart', 'live', '/admin/analytics.php'],
        ['Top boards report', 'live', '/admin/analytics.php'],
        ['Top style tags report', 'live', '/admin/analytics.php'],
        ['Published vs draft chart', 'live', '/admin/analytics.php'],
        ['Weekly signup stat', 'live', '/admin/'],
        ['Weekly board stat', 'live', '/admin/'],
        ['GA4 tracking hooks', 'live', '/admin/analytics.php'],
        ['Revenue analytics', 'live', '/admin/analytics.php'],
    ],
    'Monetization' => [
        ['Sponsor landing page', 'live', '/sponsor.php'],
        ['Sponsor lead form', 'live', '/sponsor.php'],
        ['Sponsor lead inbox', 'live', '/admin/sponsors.php'],
        ['Sponsor lead statuses', 'live', '/admin/sponsors.php'],
        ['Newsletter placement model', 'live', '/admin/sponsors.php'],
        ['Sponsored board format', 'live', '/admin/boards.php'],
        ['Partner resource slots', 'soon', '/admin/roadmap.php?feature=partner-resource-slots#partner-resource-slots'],
        ['Manual invoice workflow', 'live', '/admin/sponsors.php'],
        ['PayTR payment link workflow', 'soon', '/admin/roadmap.php?feature=paytr-payment-link-workflow#paytr-payment-link-workflow'],
        ['iyzico checkout workflow', 'soon', '/admin/roadmap.php?feature=iyzico-checkout-workflow#iyzico-checkout-workflow'],
    ],
    'Newsletter & Retention' => [
        ['Footer newsletter signup', 'live', '/'],
        ['Newsletter subscriber table', 'live', '/admin/features.php'],
        ['Re-subscribe handling', 'live', '/api/newsletter.php'],
        ['Weekly curation concept', 'live', '/admin/digest.php'],
        ['New board digest', 'live', '/admin/digest.php'],
        ['New trend digest', 'live', '/admin/digest.php'],
        ['Curator spotlight digest', 'live', '/admin/digest.php'],
        ['Subscriber export', 'live', '/admin/export-subscribers.php'],
        ['Preference center', 'live', '/newsletter-prefs.php'],
        ['Unsubscribe workflow', 'live', '/unsubscribe.php'],
        ['Subscriber preference center', 'live', '/newsletter-prefs.php'],
        ['Editorial digest preview', 'live', '/admin/digest.php'],
    ],
    'System & Deploy' => [
        ['ZIP deploy panel', 'live', '/admin/deploy.php'],
        ['Git pull deploy action', 'live', '/admin/deploy.php'],
        ['Webhook URL display', 'live', '/admin/deploy.php'],
        ['Deploy log viewer', 'live', '/admin/deploy.php'],
        ['Protected config files', 'live', '/admin/deploy.php'],
        ['Schema update workflow', 'live', '/schema-update.sql'],
        ['Install lock protection', 'live', '/install.php'],
        ['PHP lint-ready structure', 'live', '/admin/deploy.php'],
        ['Rollback package workflow', 'live', '/admin/deploy.php'],
        ['Environment health checks', 'live', '/admin/health.php'],
        ['Admin health check page', 'live', '/admin/health.php'],
    ],
    'UI / UX Controls' => [
        ['Dark mode toggle', 'live', '/'],
        ['Mobile drawer navigation', 'live', '/'],
        ['Avatar account dropdown', 'live', '/'],
        ['Responsive portfolio profiles', 'live', '/profile.php?u=' . urlencode($adminUser['username'])],
        ['Design Masters slider', 'live', '/'],
        ['Premium coming-soon strip', 'live', '/'],
        ['Compact/comfortable board view', 'live', '/'],
        ['Skeleton image loading', 'live', '/'],
        ['Accessibility labels', 'live', '/'],
        ['Reduced-motion setting', 'live', '/settings.php'],
    ],
    'Future Premium' => [
        ['Private collections', 'soon', '/premium.php#private-collections'],
        ['Advanced source discovery', 'soon', '/premium.php#advanced-source-discovery'],
        ['PDF board export', 'live', '/board.php'],
        ['ZIP board export', 'soon', '/premium.php#zip-board-export'],
        ['Moodboard presentation mode', 'soon', '/premium.php#moodboard-presentation-mode'],
        ['Curator analytics pages', 'soon', '/premium.php#curator-analytics-pages'],
        ['Portfolio custom domain', 'soon', '/premium.php#portfolio-custom-domain'],
        ['Brandable curator page', 'soon', '/premium.php#brandable-curator-page'],
        ['Weekly trend reports', 'soon', '/premium.php#weekly-trend-reports'],
        ['Team curation workspace', 'soon', '/premium.php#team-curation-workspace'],
    ],
];

$flat = [];
foreach ($featureGroups as $group => $items) {
    foreach ($items as $item) {
        $flat[] = ['group' => $group, 'name' => $item[0], 'status' => $item[1], 'href' => $item[2]];
    }
}
$counts = ['live' => 0, 'beta' => 0, 'soon' => 0];
foreach ($flat as $feature) {
    $counts[$feature['status']]++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>100 Features — Admin — Visual Design Journey</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/admin/admin.css?v=1.1">
    <style>
        .feature-matrix-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) minmax(320px, .7fr);
            gap: 24px;
            align-items: stretch;
            background: linear-gradient(135deg, rgba(127,119,221,.14), rgba(255,255,255,0));
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px;
            margin-bottom: 22px;
            box-shadow: var(--shadow);
        }
        .feature-matrix-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--accent);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .feature-matrix-hero h2 {
            font-family: 'Manrope', sans-serif;
            font-size: clamp(26px, 4vw, 42px);
            line-height: 1.05;
            letter-spacing: -.04em;
            margin-bottom: 12px;
        }
        .feature-matrix-hero p {
            color: var(--text-muted);
            max-width: 680px;
        }
        .feature-matrix-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .feature-matrix-stats div {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px;
        }
        .feature-matrix-stats strong {
            display: block;
            font-size: 28px;
            line-height: 1;
            color: var(--accent);
            margin-bottom: 6px;
        }
        .feature-matrix-stats span {
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .feature-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }
        .feature-filter {
            border: 1px solid var(--border);
            border-radius: 999px;
            background: #fff;
            color: var(--text-muted);
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 700;
        }
        .feature-filter.active {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }
        .feature-group-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }
        .feature-group-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        .feature-group-card__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 18px 14px;
            border-bottom: 1px solid var(--border);
            background: #fff;
        }
        .feature-group-card__head h2 {
            font-size: 16px;
            margin: 0;
        }
        .feature-group-card__head span {
            color: var(--text-muted);
            font-size: 12px;
        }
        .feature-list {
            display: grid;
        }
        .feature-row {
            display: grid;
            grid-template-columns: 44px minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
            padding: 11px 14px;
            text-decoration: none;
            border-bottom: 1px solid #f1f5f9;
            color: var(--text);
        }
        .feature-row:last-child { border-bottom: 0; }
        .feature-row:hover { background: #f8fafc; }
        .feature-row[aria-disabled="true"] { cursor: default; }
        .feature-row__num {
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
        }
        .feature-row__name {
            min-width: 0;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.35;
        }
        .feature-status {
            border-radius: 999px;
            padding: 4px 8px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .feature-status--live {
            background: var(--success-light);
            color: #15803d;
        }
        .feature-status--beta {
            background: var(--warning-light);
            color: #92400e;
        }
        .feature-status--soon {
            background: #f1f5f9;
            color: var(--text-muted);
        }
        @media (max-width: 1100px) {
            .feature-matrix-hero,
            .feature-group-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 560px) {
            .feature-row { grid-template-columns: 38px minmax(0, 1fr); }
            .feature-status { grid-column: 2; width: max-content; }
            .feature-matrix-stats { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/_sidebar.php'; ?>

<main class="admin-main">
    <div class="admin-topbar">
        <div class="admin-header">
            <h1>100 Feature Matrix</h1>
            <p>Admin OS overview for content, discovery, SEO, monetization, and future premium modules.</p>
        </div>
        <button class="hamburger" id="hamburger" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <section class="feature-matrix-hero">
        <div>
            <span class="feature-matrix-kicker"><i class="fa-solid fa-cubes-stacked"></i> Admin OS</span>
            <h2>100 controls and product capabilities in one operational map.</h2>
            <p>Use this page as the command checklist while the platform moves from subdomain prototype to the main Visual Design Journey product.</p>
        </div>
        <div class="feature-matrix-stats">
            <div><strong><?= count($flat) ?></strong><span>Total</span></div>
            <div><strong><?= $counts['live'] ?></strong><span>Live</span></div>
            <div><strong><?= $counts['beta'] ?></strong><span>Beta</span></div>
            <div><strong><?= $counts['soon'] ?></strong><span>Soon</span></div>
        </div>
    </section>

    <div class="feature-toolbar">
        <button class="feature-filter active" data-filter="all">All</button>
        <button class="feature-filter" data-filter="live">Live</button>
        <button class="feature-filter" data-filter="beta">Beta</button>
        <button class="feature-filter" data-filter="soon">Coming soon</button>
    </div>

    <section class="feature-group-grid">
        <?php $number = 1; foreach ($featureGroups as $group => $features): ?>
        <article class="feature-group-card">
            <div class="feature-group-card__head">
                <h2><?= e($group) ?></h2>
                <span><?= count($features) ?> features</span>
            </div>
            <div class="feature-list">
                <?php foreach ($features as $feature):
                    [$name, $status, $href] = $feature;
                    $tagClass = 'feature-status feature-status--' . $status;
                    $label = $status === 'soon' ? 'Coming soon' : ucfirst($status);
                ?>
                <a href="<?= e($href) ?>"
                   class="feature-row"
                   data-status="<?= e($status) ?>"
                   <?= $href === '#' ? 'aria-disabled="true" onclick="return false;"' : '' ?>>
                    <span class="feature-row__num"><?= str_pad((string)$number, 3, '0', STR_PAD_LEFT) ?></span>
                    <span class="feature-row__name"><?= e($name) ?></span>
                    <span class="<?= e($tagClass) ?>"><?= e($label) ?></span>
                </a>
                <?php $number++; endforeach; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </section>
</main>

<script>
(function () {
    const filters = document.querySelectorAll('.feature-filter');
    const rows = document.querySelectorAll('.feature-row');
    filters.forEach(btn => {
        btn.addEventListener('click', function () {
            filters.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const filter = this.dataset.filter;
            rows.forEach(row => {
                row.style.display = (filter === 'all' || row.dataset.status === filter) ? 'grid' : 'none';
            });
        });
    });
})();
</script>
</body>
</html>
