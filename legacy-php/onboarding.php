<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/i18n.php';

requireLogin();
$currentLang = vdj_bootstrap_language();

$db     = getDB();
$userId = currentUserId();
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $skip = isset($_POST['skip']);

    if (!$skip) {
        $selected = $_POST['styles'] ?? [];
        $selected = array_filter(array_map('trim', (array)$selected));

        if (empty($selected)) {
            $error = __('onboarding.error_select', 'Please select at least one style to continue.');
        }
    }

    if ($error === '') {
        $db->prepare('UPDATE users SET is_onboarded = 1 WHERE id = ?')->execute([$userId]);
        header('Location: /index.php');
        exit;
    }
}

$stmt   = $db->prepare('SELECT * FROM vibe_styles ORDER BY label ASC');
$stmt->execute();
$styles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= e($currentLang) ?>">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= e(__('onboarding.title', 'Pick Your Styles')) ?> — Visual Design Journey</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link rel="stylesheet" href="/assets/css/style.css"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; }

    body {
      margin: 0;
      background: var(--bg);
      min-height: 100vh;
      padding-bottom: 100px;
    }

    /* ── Progress bar ── */
    .progress-bar-wrap {
      position: sticky;
      top: 0;
      z-index: 100;
      background: var(--bg);
      border-bottom: 0.5px solid var(--border);
      padding: 14px 24px;
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .progress-label {
      font-size: 13px;
      font-weight: 600;
      color: var(--muted);
      white-space: nowrap;
    }
    .progress-track {
      flex: 1;
      height: 6px;
      background: var(--border);
      border-radius: var(--r-pill);
      overflow: hidden;
    }
    .progress-fill {
      height: 100%;
      width: 66.66%;
      background: var(--accent);
      border-radius: var(--r-pill);
      transition: width 0.3s ease;
    }

    /* ── Content ── */
    .onboard-container {
      max-width: 960px;
      margin: 0 auto;
      padding: 48px 24px 32px;
    }
    .onboard-header {
      margin-bottom: 36px;
    }
    .onboard-header h1 {
      font-family: var(--font-display);
      font-size: 28px;
      font-weight: 700;
      letter-spacing: -0.03em;
      color: var(--text);
      margin: 0 0 8px;
    }
    .onboard-header p {
      font-size: 15px;
      color: var(--muted);
      margin: 0;
    }

    .alert-error {
      padding: 10px 16px;
      border-radius: var(--r-md);
      background: var(--danger-surface, #fef2f2);
      color: var(--danger, #dc2626);
      font-size: 13px;
      border: 0.5px solid #f5c6c6;
      margin-bottom: 24px;
    }

    /* ── Style grid ── */
    .style-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
    }
    @media (min-width: 600px) {
      .style-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (min-width: 900px) {
      .style-grid { grid-template-columns: repeat(4, 1fr); }
    }

    /* ── Tile ── */
    .style-tile {
      position: relative;
      aspect-ratio: 1 / 1;
      border-radius: var(--r-lg);
      overflow: hidden;
      cursor: pointer;
      border: 2px solid transparent;
      transition: border-color 0.15s, transform 0.15s;
    }
    .style-tile:hover { transform: scale(1.02); }
    .style-tile.is-selected {
      border-color: var(--accent);
    }
    .style-tile input[type="checkbox"] {
      position: absolute;
      opacity: 0;
      width: 0;
      height: 0;
    }
    .style-tile-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .style-tile-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent 55%);
      pointer-events: none;
    }
    .style-tile-label {
      position: absolute;
      bottom: 10px;
      left: 12px;
      font-size: 13px;
      font-weight: 600;
      color: #fff;
      letter-spacing: 0.01em;
      pointer-events: none;
    }
    .style-tile-check {
      position: absolute;
      top: 10px;
      right: 10px;
      width: 26px;
      height: 26px;
      border-radius: 50%;
      border: 2px solid rgba(255,255,255,0.7);
      background: rgba(255,255,255,0.15);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.15s, border-color 0.15s;
      pointer-events: none;
    }
    .style-tile-check .material-symbols-outlined {
      font-size: 16px;
      color: transparent;
      transition: color 0.15s;
    }
    .style-tile.is-selected .style-tile-check {
      background: var(--accent);
      border-color: var(--accent);
    }
    .style-tile.is-selected .style-tile-check .material-symbols-outlined {
      color: #fff;
    }

    /* ── Sticky bottom bar ── */
    .onboard-bottom {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: var(--bg);
      border-top: 0.5px solid var(--border);
      padding: 16px 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 24px;
      z-index: 100;
    }
    .onboard-bottom-inner {
      display: flex;
      align-items: center;
      gap: 24px;
      width: 100%;
      max-width: 480px;
    }
    .btn--skip {
      background: none;
      border: none;
      font-size: 14px;
      font-weight: 500;
      color: var(--muted);
      cursor: pointer;
      padding: 8px 0;
      transition: color 0.15s;
    }
    .btn--skip:hover { color: var(--text); }
    .btn--continue {
      flex: 1;
    }
    .btn--continue:disabled {
      opacity: 0.45;
      cursor: not-allowed;
    }
  </style>
</head>
<body>

<!-- Progress -->
<div class="progress-bar-wrap" role="progressbar" aria-valuenow="2" aria-valuemin="1" aria-valuemax="3" aria-label="<?= e(__('onboarding.step', 'Step 2 of 3')) ?>">
  <span class="progress-label"><?= e(__('onboarding.step', 'Step 2 of 3')) ?></span>
  <div class="progress-track">
    <div class="progress-fill"></div>
  </div>
</div>

<!-- Main -->
<main class="onboard-container">
  <div class="onboard-header">
    <h1><?= e(__('onboarding.title', 'What styles speak to you?')) ?></h1>
    <p><?= e(__('onboarding.sub', 'Pick at least 1 to personalise your feed — the more you choose, the better your recommendations.')) ?></p>
  </div>

  <?php if ($error): ?>
    <div class="alert-error" role="alert"><?= e($error) ?></div>
  <?php endif; ?>

  <form id="styleForm" method="POST" action="/onboarding.php">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>

    <div class="style-grid">
      <?php foreach ($styles as $style): ?>
        <?php $slug = e($style['slug']); $label = e($style['label']); ?>
        <label class="style-tile" data-slug="<?= $slug ?>">
          <input type="checkbox" name="styles[]" value="<?= $slug ?>" aria-label="<?= $label ?>"/>
          <img
            class="style-tile-img"
            src="https://picsum.photos/seed/<?= $slug ?>/400/400"
            alt="<?= $label ?>"
            loading="lazy"
            width="400"
            height="400"
          />
          <div class="style-tile-overlay"></div>
          <span class="style-tile-label"><?= $label ?></span>
          <span class="style-tile-check" aria-hidden="true">
            <span class="material-symbols-outlined">check</span>
          </span>
        </label>
      <?php endforeach; ?>
    </div>

  </form>
</main>

<!-- Bottom bar -->
<div class="onboard-bottom">
  <div class="onboard-bottom-inner">
    <form method="POST" action="/onboarding.php">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
      <input type="hidden" name="skip" value="1"/>
      <button type="submit" class="btn--skip"><?= e(__('onboarding.skip', 'Skip for now')) ?></button>
    </form>
    <button
      type="submit"
      form="styleForm"
      id="continueBtn"
      class="btn btn--primary btn--continue"
      disabled
      style="justify-content:center;padding:13px 24px;font-size:15px;"
    >
      <?= e(__('onboarding.continue', 'Continue →')) ?>
    </button>
  </div>
</div>

<script>
  (function() {
    var tiles      = document.querySelectorAll('.style-tile');
    var continueBtn = document.getElementById('continueBtn');

    function updateBtn() {
      var count = document.querySelectorAll('.style-tile.is-selected').length;
      continueBtn.disabled = count === 0;
    }

    tiles.forEach(function(tile) {
      var checkbox = tile.querySelector('input[type="checkbox"]');
      tile.addEventListener('click', function(e) {
        if (e.target === checkbox) return;
        checkbox.checked = !checkbox.checked;
        tile.classList.toggle('is-selected', checkbox.checked);
        updateBtn();
      });
      checkbox.addEventListener('change', function() {
        tile.classList.toggle('is-selected', checkbox.checked);
        updateBtn();
      });
    });
  })();
</script>
</body>
</html>
