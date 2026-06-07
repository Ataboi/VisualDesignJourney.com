<?php
// FILE: admin/_sidebar.php
// Shared sidebar partial — $currentPage and $adminUser must be set before including this file.
// No doctype or html wrapper; just the sidebar HTML + overlay + hamburger JS.
?>

<div class="sidebar-overlay" id="overlay"></div>

<aside class="admin-sidebar" id="sidebar">
    <div class="admin-sidebar__logo">
        <a href="/admin/" class="brand">
            <i class="fa-solid fa-compass-drafting"></i>
            Visual Design v2
        </a>
        <div class="admin-chip"><?= e(__('admin.panel', 'Admin Panel')) ?></div>
        <div class="admin-lang-switcher" aria-label="<?= e(__('nav.language', 'Choose language')) ?>">
            <?php foreach (vdj_supported_languages() as $langCode => $langMeta): ?>
                <a href="<?= e(vdj_language_url($langCode)) ?>"
                   class="<?= vdj_current_language() === $langCode ? 'active' : '' ?>"
                   hreflang="<?= e($langCode) ?>"
                   aria-label="<?= e($langMeta['label']) ?>">
                    <span><?= e($langMeta['short']) ?></span>
                    <?= e($langMeta['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <nav class="admin-sidebar__nav">
        <div class="nav-section-label"><?= e(__('admin.main', 'Main')) ?></div>

        <a href="/admin/" class="<?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i>
            <?= e(__('admin.dashboard', 'Dashboard')) ?>
        </a>

        <a href="/admin/features.php" class="<?= ($currentPage ?? '') === 'features' ? 'active' : '' ?>">
            <i class="fa-solid fa-cubes-stacked"></i>
            <?= e(__('admin.features', '100 Features')) ?>
        </a>

        <a href="/admin/roadmap.php" class="<?= ($currentPage ?? '') === 'roadmap' ? 'active' : '' ?>">
            <i class="fa-solid fa-route"></i>
            <?= e(__('admin.roadmap', 'Roadmap')) ?>
        </a>

        <a href="/admin/users.php" class="<?= ($currentPage ?? '') === 'users' ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i>
            <?= e(__('admin.users', 'Users')) ?>
        </a>

        <a href="/admin/boards.php" class="<?= ($currentPage ?? '') === 'boards' ? 'active' : '' ?>">
            <i class="fa-solid fa-border-all"></i>
            <?= e(__('admin.boards', 'Boards')) ?>
        </a>

        <a href="/admin/sources.php" class="<?= ($currentPage ?? '') === 'sources' ? 'active' : '' ?>">
            <i class="fa-solid fa-link"></i>
            <?= e(__('admin.sources', 'Sources')) ?>
        </a>

        <a href="/admin/discovery.php" class="<?= ($currentPage ?? '') === 'discovery' ? 'active' : '' ?>">
            <i class="fa-solid fa-rss"></i>
            <?= e(__('admin.discovery', 'Discovery')) ?>
        </a>

        <a href="/admin/curators.php" class="<?= ($currentPage ?? '') === 'curators' ? 'active' : '' ?>">
            <i class="fa-solid fa-id-badge"></i>
            <?= e(__('admin.curators', 'Curators')) ?>
        </a>

        <a href="/admin/blog.php" class="<?= ($currentPage ?? '') === 'blog' ? 'active' : '' ?>">
            <i class="fa-solid fa-newspaper"></i>
            <?= e(__('admin.blog', 'Blog')) ?>
        </a>

        <a href="/admin/seo-audit.php" class="<?= ($currentPage ?? '') === 'seo-audit' ? 'active' : '' ?>">
            <i class="fa-solid fa-magnifying-glass-chart"></i>
            <?= e(__('admin.seo_audit', 'SEO Audit')) ?>
        </a>

        <a href="/admin/media.php" class="<?= ($currentPage ?? '') === 'media' ? 'active' : '' ?>">
            <i class="fa-solid fa-images"></i>
            <?= e(__('admin.media', 'Media & Cache')) ?>
        </a>

        <div class="nav-section-label"><?= e(__('admin.insights', 'Insights')) ?></div>

        <a href="/admin/analytics.php" class="<?= ($currentPage ?? '') === 'analytics' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-line"></i>
            <?= e(__('admin.analytics', 'Analytics')) ?>
        </a>

        <a href="/admin/sponsors.php" class="<?= ($currentPage ?? '') === 'sponsors' ? 'active' : '' ?>">
            <i class="fa-solid fa-handshake"></i>
            <?= e(__('admin.sponsors', 'Sponsors')) ?>
        </a>

        <div class="nav-section-label"><?= e(__('admin.system', 'System')) ?></div>

        <a href="/admin/deploy.php" class="<?= ($currentPage ?? '') === 'deploy' ? 'active' : '' ?>">
            <i class="fa-solid fa-rocket"></i>
            <?= e(__('admin.deploy', 'Deploy')) ?>
        </a>

        <a href="/admin/adsense.php" class="<?= ($currentPage ?? '') === 'adsense' ? 'active' : '' ?>">
            <i class="fa-brands fa-google"></i>
            <?= e(__('admin.adsense', 'AdSense')) ?>
        </a>

        <a href="/admin/adsterra.php" class="<?= ($currentPage ?? '') === 'adsterra' ? 'active' : '' ?>">
            <i class="fa-solid fa-rectangle-ad"></i>
            Adsterra
        </a>

        <a href="/admin/health.php" class="<?= ($currentPage ?? '') === 'health' ? 'active' : '' ?>">
            <i class="fa-solid fa-heart-pulse"></i>
            <?= e(__('admin.health', 'Health Check')) ?>
        </a>

        <a href="/" target="_blank" rel="noopener noreferrer">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
            <?= e(__('admin.view_site', 'View Site')) ?>
        </a>
    </nav>

    <div class="admin-sidebar__footer">
        <a href="/logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>
            <?= e(__('admin.sign_out', 'Sign out')) ?> (<?= e($adminUser['username']) ?>)
        </a>
    </div>
</aside>

<script>
(function () {
    var sidebar   = document.getElementById('sidebar');
    var overlay   = document.getElementById('overlay');
    var hamburger = document.getElementById('hamburger');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    }
    function toggleSidebar() {
        if (sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    if (hamburger) hamburger.addEventListener('click', toggleSidebar);
    if (overlay)   overlay.addEventListener('click', closeSidebar);

    // Close sidebar on escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
})();
</script>
