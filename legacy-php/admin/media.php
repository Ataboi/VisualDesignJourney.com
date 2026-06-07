<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/_metrics.php';

$uploadsRoot = realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads');
$cacheRoot = __DIR__ . '/../cache';
$mediaCacheRoot = $cacheRoot . '/media';

if (!is_dir($cacheRoot)) @mkdir($cacheRoot, 0755, true);
if (!is_dir($mediaCacheRoot)) @mkdir($mediaCacheRoot, 0755, true);

function adminRmDir(string $dir): int {
    if (!is_dir($dir)) return 0;
    $count = 0;
    $rit = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($rit as $item) {
        if ($item->isDir()) {
            @rmdir($item->getPathname());
        } elseif (@unlink($item->getPathname())) {
            $count++;
        }
    }
    return $count;
}

$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'clear_cache') {
        $count = adminRmDir($cacheRoot);
        @mkdir($cacheRoot, 0755, true);
        @mkdir($mediaCacheRoot, 0755, true);
        $flash = "Cache cleared. {$count} file(s) removed.";
    }

    if ($action === 'delete_media') {
        $delete = $_POST['delete'] ?? [];
        $refs = adminCollectMediaRefs($db);
        $removed = 0;
        foreach ($delete as $rel) {
            $rel = ltrim(str_replace('\\', '/', (string)$rel), '/');
            if ($rel === '' || str_contains($rel, '..') || isset($refs[$rel])) continue;
            $file = $uploadsRoot . '/' . $rel;
            if (is_file($file) && str_starts_with(realpath($file) ?: '', $uploadsRoot) && @unlink($file)) {
                $removed++;
            }
        }
        $flash = "{$removed} unused media file(s) deleted.";
    }

    if ($action === 'clean_unused') {
        $limit = max(100, min(5000, (int)($_POST['limit'] ?? 1000)));
        $mode = $_POST['mode'] ?? 'single';
        $refs = adminCollectMediaRefs($db);
        [$scanFiles] = adminScanMedia($uploadsRoot, $refs);
        $removed = 0;
        $freed = 0;

        foreach ($scanFiles as $fileInfo) {
            if ($fileInfo['used']) continue;
            $rel = $fileInfo['rel'];
            if ($rel === '' || str_contains($rel, '..')) continue;
            $file = $uploadsRoot . '/' . $rel;
            $real = realpath($file);
            if (!$real || !str_starts_with($real, $uploadsRoot) || !is_file($real)) continue;
            $size = (int)filesize($real);
            if (@unlink($real)) {
                $removed++;
                $freed += $size;
            }
            if ($removed >= $limit) break;
        }

        adminRmDir($cacheRoot);
        @mkdir($cacheRoot, 0755, true);
        @mkdir($mediaCacheRoot, 0755, true);

        $flash = "Cleaned {$removed} unused media file(s), freed " . adminBytes($freed) . ".";
        if ($removed >= $limit) {
            $flash .= " Run clean again to continue with the next batch.";
        }
        if ($mode === 'all' && $removed >= $limit) {
            $nextLimit = (int)$limit;
            $flash .= " Clean all will continue with the next batch automatically.";
            header('Refresh: 2; url=/admin/media.php?continue_clean=1&limit=' . $nextLimit);
        }
    }
}

$autoClean = isset($_GET['continue_clean']) && (int)($_GET['limit'] ?? 0) > 0;
$refs = adminCollectMediaRefs($db);
[$files, $totalSize, $unusedSize] = adminScanMedia($uploadsRoot, $refs);
$unused = array_values(array_filter($files, fn($f) => !$f['used']));
$view = $_GET['view'] ?? 'unused';
$shown = $view === 'all' ? $files : $unused;
$shown = array_slice($shown, 0, 120);
$cacheFiles = is_dir($cacheRoot) ? iterator_count(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cacheRoot, FilesystemIterator::SKIP_DOTS))) : 0;
$csrf = csrfToken();
$currentPage = 'media';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Media & Cache — Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/admin/admin.css">
  <style>
    .media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:14px}
    .media-card{border:1px solid var(--border);border-radius:10px;background:#fff;overflow:hidden}
    .media-card img{width:100%;aspect-ratio:4/3;object-fit:cover;background:#f8fafc;display:block}
    .media-card__body{padding:10px;font-size:12px;color:var(--text-muted)}
    .media-path{font-family:monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--text)}
    .stat-mini{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
    .stat-mini div{background:#fff;border:1px solid var(--border);border-radius:10px;padding:16px}
    .stat-mini strong{display:block;font-size:22px;color:var(--text);margin-bottom:3px}
    .clean-panel{display:flex;align-items:center;justify-content:space-between;gap:16px;background:#fff;border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:16px}
    .clean-panel p{margin:0;color:var(--text-muted);font-size:13px}
    .clean-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
    .warning-note{padding:12px 14px;border:1px solid #fde68a;border-radius:8px;background:#fffbeb;color:#92400e;font-size:13px;line-height:1.55;margin-bottom:16px}
    @media(max-width:760px){.stat-mini{grid-template-columns:1fr 1fr}}
  </style>
</head>
<body>
<?php require_once __DIR__ . '/_sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-topbar">
    <div class="admin-header">
      <h1>Media & Cache</h1>
      <p>Clean unused uploaded images and clear generated media cache.</p>
    </div>
    <button class="hamburger" id="hamburger"><i class="fa-solid fa-bars"></i></button>
  </div>

  <?php if ($flash): ?><div class="flash flash-<?= e($flashType) ?>"><?= e($flash) ?></div><?php endif; ?>
  <?php if ($autoClean): ?>
    <form id="autoCleanForm" method="post" action="/admin/media.php">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <input type="hidden" name="action" value="clean_unused">
      <input type="hidden" name="mode" value="all">
      <input type="hidden" name="limit" value="<?= (int)$_GET['limit'] ?>">
    </form>
    <script>setTimeout(function(){ document.getElementById('autoCleanForm').submit(); }, 600);</script>
  <?php endif; ?>

  <div class="stat-mini">
    <div><strong><?= number_format(count($files)) ?></strong><span>Total images</span></div>
    <div><strong><?= adminBytes($totalSize) ?></strong><span>Upload size</span></div>
    <div><strong><?= number_format(count($unused)) ?></strong><span>Unused candidates</span></div>
    <div><strong><?= number_format($cacheFiles) ?></strong><span>Cache files</span></div>
  </div>

  <div class="warning-note">
    <strong>Important:</strong> "Unused" means no direct database reference was found. It is a strong cleanup signal, but not a guarantee that the file is useless if it is linked from custom HTML, email templates, or an external page.
  </div>

  <div class="toolbar">
    <div class="filter-tabs">
      <a class="filter-tab <?= $view === 'unused' ? 'active' : '' ?>" href="/admin/media.php?view=unused">Unused</a>
      <a class="filter-tab <?= $view === 'all' ? 'active' : '' ?>" href="/admin/media.php?view=all">All media</a>
    </div>
    <form method="post" action="/admin/media.php" onsubmit="return confirm('Clear generated cache?');">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <input type="hidden" name="action" value="clear_cache">
      <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-broom"></i> Clear Cache</button>
    </form>
  </div>

  <div class="clean-panel">
    <div>
      <strong>Clean unused media</strong>
      <p><?= number_format(count($unused)) ?> unused candidate(s), about <?= adminBytes($unusedSize) ?>. Cleaning runs in safe batches to avoid server timeout.</p>
    </div>
    <div class="clean-actions">
      <button type="button" class="btn btn-secondary" onclick="selectVisibleUnused()">
        <i class="fa-solid fa-check-double"></i> Select Visible
      </button>
      <form method="post" action="/admin/media.php" onsubmit="return confirm('Delete the next batch of unused files? This cannot be undone.');" style="display:flex;gap:8px;align-items:center;">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="clean_unused">
        <input type="hidden" name="mode" value="single">
        <select name="limit" class="form-input" style="width:130px;">
          <option value="1000">1,000 files</option>
          <option value="5000">5,000 files</option>
        </select>
        <button class="btn btn-danger" type="submit">
          <i class="fa-solid fa-wand-magic-sparkles"></i> Clean Batch
        </button>
      </form>
      <form method="post" action="/admin/media.php" onsubmit="return confirm('Clean all unused media in repeated 1,000-file batches? You can stop by closing this page.');">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="clean_unused">
        <input type="hidden" name="mode" value="all">
        <input type="hidden" name="limit" value="1000">
        <button class="btn btn-danger" type="submit">
          <i class="fa-solid fa-rotate"></i> Clean All In Batches
        </button>
      </form>
    </div>
  </div>

  <form method="post" action="/admin/media.php" onsubmit="return confirm('Delete selected unused files? This cannot be undone.');">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
    <input type="hidden" name="action" value="delete_media">
    <div class="admin-card" style="padding:16px;">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;">
        <div>
          <h2 style="font-size:16px;margin:0 0 4px;">Showing <?= count($shown) ?> image<?= count($shown) === 1 ? '' : 's' ?></h2>
          <p style="margin:0;color:var(--text-muted);font-size:13px;">Unused means no direct reference was found in users, boards, board images, or blog posts.</p>
        </div>
        <?php if ($view === 'unused'): ?>
          <button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i> Delete Selected</button>
        <?php endif; ?>
      </div>
      <div class="media-grid">
        <?php foreach ($shown as $file): ?>
          <label class="media-card">
            <img src="<?= e($file['url']) ?>" alt="" loading="lazy">
            <div class="media-card__body">
              <div class="media-path" title="<?= e($file['rel']) ?>"><?= e($file['rel']) ?></div>
              <div style="display:flex;justify-content:space-between;margin-top:6px;">
                <span><?= adminBytes($file['size']) ?></span>
                <span><?= $file['used'] ? 'Used' : 'Unused' ?></span>
              </div>
              <?php if (!$file['used']): ?>
                <div style="margin-top:8px;"><input class="unused-check" type="checkbox" name="delete[]" value="<?= e($file['rel']) ?>"> Select</div>
              <?php endif; ?>
            </div>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
  </form>
</main>
<script>
function selectVisibleUnused() {
  document.querySelectorAll('.unused-check').forEach(function (input) {
    input.checked = true;
  });
}
</script>
</body>
</html>
