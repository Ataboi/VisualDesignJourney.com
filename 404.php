<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

http_response_code(404);

$pageTitle = __('404.page_title', 'Page Not Found');
$activeNav = '';

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-wrap" id="main-content">
<div class="container">
  <div style="text-align:center;padding:5rem 1rem;max-width:480px;margin:0 auto;">

    <div style="font-size:7rem;font-weight:900;line-height:1;color:var(--border);letter-spacing:-.04em;margin-bottom:1rem;">
      404
    </div>

    <h1 class="t-headline-md" style="margin:0 0 .75rem;"><?= e(__('404.heading', 'Page not found')) ?></h1>

    <p class="t-body-md text-muted" style="margin:0 0 2rem;">
      <?= e(__('404.sub', "The page you're looking for doesn't exist or has been moved.")) ?>
    </p>

    <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;">
      <button onclick="history.back()" class="btn btn--secondary">
        <?= e(__('404.back', '← Go Back')) ?>
      </button>
      <a href="/index.php" class="btn btn--primary">
        <?= e(__('404.explore', 'Explore Boards')) ?>
      </a>
    </div>

  </div>
</div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
