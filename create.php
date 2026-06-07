<?php
/**
 * create.php — Create a new board. Requires login.
 * GET  → show empty form
 * POST action=publish → insert board with is_draft=0, redirect to SEO board URL
 * POST action=draft   → insert board with is_draft=1, redirect to SEO board URL
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$db            = getDB();
$currentUserId = currentUserId();
$errors        = [];
$boardI18nReady = dbColumnExists($db, 'boards', 'slug');

/* ── Preset style tags ─────────────────────────────────────────────────────── */
$presetTags = ['Minimalist', 'Bauhaus', 'Y2K', 'Art Nouveau', 'Brutalist', 'Japandi', 'Maximalist'];

/* ── POST handler ──────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (!rateLimitCheck('create-board-user-' . (int)$currentUserId, 8, 60 * 60)) {
        $errors[] = __('create.error_rate_limit', 'You are creating boards very quickly. Please wait a little and try again.');
    }

    $action      = $_POST['action'] ?? 'publish';
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $rawTags     = trim($_POST['style_tags'] ?? '');
    $rawImageUrls = trim($_POST['image_urls'] ?? '');
    $isDraft     = ($action === 'draft') ? 1 : 0;

    /* Validate */
    if ($title === '') {
        $errors[] = __('create.error_title_req', 'Board title is required.');
    } elseif (mb_strlen($title) > 150) {
        $errors[] = __('create.error_title_long', 'Title must be 150 characters or fewer.');
    }

    /* Process image uploads */
    $uploadedFiles = [];
    $uploadErrors  = [];

    if (!empty($_FILES['images']['name'][0])) {
        $fileCount = count($_FILES['images']['name']);
        $fileCount = min($fileCount, 10); // cap at 10

        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $file = [
                'name'     => $_FILES['images']['name'][$i],
                'type'     => $_FILES['images']['type'][$i],
                'tmp_name' => $_FILES['images']['tmp_name'][$i],
                'error'    => $_FILES['images']['error'][$i],
                'size'     => $_FILES['images']['size'][$i],
            ];
            try {
                $path = handleImageUpload($file, 'boards');
                $uploadedFiles[] = $path;
            } catch (RuntimeException $ex) {
                $uploadErrors[] = 'Image ' . ($i + 1) . ': ' . $ex->getMessage();
            }
        }
    }

    if (!empty($uploadErrors)) {
        $errors = array_merge($errors, $uploadErrors);
    }

    /* External image URLs for curated references. Store internally as board images;
       credit/source metadata can be expanded later from admin. */
    $externalUrls = [];
    if ($rawImageUrls !== '') {
        $lines = preg_split('/\R+/', $rawImageUrls);
        foreach ($lines as $line) {
            $url = trim($line);
            if ($url === '') continue;
            if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
                $errors[] = 'External image URL is invalid: ' . htmlspecialchars($url, ENT_QUOTES);
                continue;
            }
            $externalUrls[] = $url;
            if ((count($uploadedFiles) + count($externalUrls)) >= 10) break;
        }
    }

    /* Insert if no errors */
    if (empty($errors)) {
        $normalizedTags = implode(',', array_map('trim', array_filter(explode(',', $rawTags))));
        $allImages      = array_merge($uploadedFiles, $externalUrls);
        $coverImage     = !empty($allImages) ? $allImages[0] : null;

        if ($boardI18nReady) {
            $stmt = $db->prepare(
                'INSERT INTO boards (user_id, slug, title, description, cover_image, style_tags, is_draft, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $currentUserId,
                slugify($title),
                $title,
                $description ?: null,
                $coverImage,
                $normalizedTags,
                $isDraft,
            ]);
        } else {
            $stmt = $db->prepare(
                'INSERT INTO boards (user_id, title, description, cover_image, style_tags, is_draft, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $currentUserId,
                $title,
                $description ?: null,
                $coverImage,
                $normalizedTags,
                $isDraft,
            ]);
        }
        $boardId = (int)$db->lastInsertId();

        /* Insert board_images */
        if (!empty($allImages)) {
            $basicImgStmt = $db->prepare(
                'INSERT INTO board_images (board_id, image_url, sort_order) VALUES (?, ?, ?)'
            );
            $extendedImgStmt = null;
            try {
                $extendedImgStmt = $db->prepare(
                    'INSERT INTO board_images (board_id, image_url, sort_order, source_url, source_platform)
                     VALUES (?, ?, ?, ?, ?)'
                );
            } catch (Exception $e) {
                $extendedImgStmt = null;
            }

            foreach ($allImages as $order => $imgPath) {
                $isExternal = preg_match('#^https?://#i', $imgPath);
                if ($isExternal && $extendedImgStmt) {
                    $host = parse_url($imgPath, PHP_URL_HOST) ?: 'external';
                    $extendedImgStmt->execute([$boardId, $imgPath, $order, $imgPath, $host]);
                } else {
                    $basicImgStmt->execute([$boardId, $imgPath, $order]);
                }
            }
        }

        header('Location: ' . boardUrl(['id' => $boardId, 'slug' => slugify($title)]));
        exit;
    }

    /* Re-populate on error */
    $formTitle       = htmlspecialchars($title, ENT_QUOTES);
    $formDescription = htmlspecialchars($description, ENT_QUOTES);
    $formTags        = htmlspecialchars($rawTags, ENT_QUOTES);
    $formImageUrls   = htmlspecialchars($rawImageUrls, ENT_QUOTES);
} else {
    $formTitle       = '';
    $formDescription = '';
    $formTags        = '';
    $formImageUrls   = '';
}

$pageTitle = __('create.title', 'Create Board') . ' — Visual Design Journey';
$activeNav = '';
require_once __DIR__ . '/includes/header.php';
?>
<main class="page-wrap" id="main-content">
  <div class="container" style="max-width:1100px;padding-top:32px;padding-bottom:64px;">

    <div style="margin-bottom:24px;">
      <h1 style="font-size:1.625rem;font-weight:700;margin:0 0 4px;"><?= e(__('create.title', 'Create a Board')) ?></h1>
      <p style="color:var(--muted);font-size:.9375rem;margin:0;"><?= e(__('create.subtitle', 'Collect and curate images that inspire your visual aesthetic.')) ?></p>
    </div>

    <!-- Error banner -->
    <?php if (!empty($errors)): ?>
      <div role="alert"
           style="background:#FFF0F0;border:1px solid #FFC5C5;border-radius:var(--r-lg);padding:16px 20px;margin-bottom:24px;display:flex;gap:12px;align-items:flex-start;">
        <span class="material-symbols-outlined" style="color:#D7373F;flex-shrink:0;margin-top:2px;">error</span>
        <ul style="margin:0;padding:0;list-style:none;">
          <?php foreach ($errors as $err): ?>
            <li style="color:#B91C1C;font-size:.875rem;"><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form id="create-form"
          method="POST"
          action="/create.php"
          enctype="multipart/form-data"
          novalidate>

      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"/>
      <input type="hidden" name="action" id="form-action" value="publish"/>

      <div style="display:grid;grid-template-columns:1fr 380px;gap:28px;align-items:start;">

        <!-- ── Left: Upload area ──────────────────────────────────────────── -->
        <div>
          <!-- Drop zone -->
          <div id="drop-zone"
               style="border:2px dashed var(--border);border-radius:var(--r-lg);padding:48px 24px;text-align:center;cursor:pointer;transition:border-color .2s,background .2s;background:var(--surface);margin-bottom:20px;"
               onmouseover="this.style.borderColor='var(--accent)'"
               onmouseout="this.style.borderColor='var(--border)'"
               onclick="document.getElementById('file-input').click()">
            <span class="material-symbols-outlined"
                  style="font-size:48px;color:var(--accent);display:block;margin-bottom:12px;">cloud_upload</span>
            <p style="font-weight:600;font-size:1rem;margin:0 0 6px;"><?= e(__('create.drop_title', 'Drag and drop images here')) ?></p>
            <p style="color:var(--muted);font-size:.875rem;margin:0 0 16px;"><?= e(__('create.drop_sub', 'or click to browse — JPG, PNG, WebP · max 5MB each · up to 10 images')) ?></p>
            <span class="btn btn--secondary btn--sm" style="pointer-events:none;">
              <span class="material-symbols-outlined" style="font-size:16px;">upload</span>
              <?= e(__('create.browse', 'Browse Files')) ?>
            </span>
          </div>

          <!-- Hidden file input -->
          <input type="file"
                 id="file-input"
                 name="images[]"
                 accept="image/jpeg,image/png,image/webp"
                 multiple
                 style="display:none;"/>

          <!-- Thumbnail preview grid -->
          <div id="preview-grid"
               style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
            <!-- JS-injected thumbnails + add-more cell appear here -->
          </div>

          <p id="image-count-note"
             style="display:none;font-size:.8125rem;color:var(--muted);margin-top:8px;text-align:right;">
            <span id="image-count">0</span> / 10 images selected
          </p>
        </div><!-- /left -->

        <!-- ── Right: Details card ────────────────────────────────────────── -->
        <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--r-lg);padding:28px;">

          <!-- Title -->
          <div class="form-group" style="margin-bottom:20px;">
            <label class="form-label" for="title">
              <?= e(__('create.board_title', 'Board Title')) ?> <span style="color:#D7373F;" aria-hidden="true">*</span>
            </label>
            <input type="text"
                   id="title"
                   name="title"
                   class="form-input"
                   value="<?= $formTitle ?>"
                   placeholder="e.g. Minimal Tokyo Living"
                   maxlength="150"
                   required
                   autocomplete="off"
                   style="width:100%;"/>
            <span style="font-size:.75rem;color:var(--muted);margin-top:4px;display:block;">
              <span id="title-chars">0</span> / 150
            </span>
          </div>

          <!-- Description -->
          <div class="form-group" style="margin-bottom:20px;">
            <label class="form-label" for="description"><?= e(__('create.description', 'Description')) ?></label>
            <textarea id="description"
                      name="description"
                      class="form-input"
                      placeholder="<?= e(__('create.desc_placeholder', "What's the mood or concept of this board?")) ?>"
                      rows="4"
                      style="width:100%;resize:vertical;min-height:90px;"><?= $formDescription ?></textarea>
          </div>

          <!-- Style tags -->
          <div class="form-group" style="margin-bottom:28px;">
            <label class="form-label" for="style-tags-input"><?= e(__('create.style_tags', 'Style Tags')) ?></label>
            <p style="font-size:.8125rem;color:var(--muted);margin:0 0 10px;">
              <?= e(__('create.tags_hint', 'Click presets or type your own, comma-separated.')) ?>
            </p>
            <!-- Preset buttons -->
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px;" id="preset-tags">
              <?php foreach ($presetTags as $pt): ?>
                <button type="button"
                        class="tag"
                        data-tag="<?= e($pt) ?>"
                        onclick="togglePresetTag(this)"
                        style="cursor:pointer;border:1px solid var(--border);background:var(--surface);padding:4px 12px;border-radius:var(--r-pill);">
                  <?= e($pt) ?>
                </button>
              <?php endforeach; ?>
            </div>
            <input type="text"
                   id="style-tags-input"
                   name="style_tags"
                   class="form-input"
                   value="<?= $formTags ?>"
                   placeholder="Minimalist, Bauhaus, custom-tag…"
                   style="width:100%;"/>
            <span style="font-size:.75rem;color:var(--muted);margin-top:4px;display:block;"><?= e(__('create.tags_sep', 'Separate tags with commas')) ?></span>
          </div>

          <!-- External references -->
          <div class="form-group" style="margin-bottom:28px;">
            <label class="form-label" for="image-urls"><?= e(__('create.external_urls', 'External Image URLs')) ?></label>
            <p style="font-size:.8125rem;color:var(--muted);margin:0 0 10px;line-height:1.5;">
              <?= e(__('create.external_hint', 'Optional. Paste direct image/reference URLs, one per line. Use this for curated Pinterest, Behance, portfolio, or publication references you have permission to include.')) ?>
            </p>
            <textarea id="image-urls"
                      name="image_urls"
                      class="form-input"
                      rows="4"
                      placeholder="https://..."
                      style="width:100%;resize:vertical;min-height:96px;"><?= $formImageUrls ?></textarea>
            <span style="font-size:.75rem;color:var(--muted);margin-top:4px;display:block;">
              Uploaded files and external URLs are capped at 10 images total.
            </span>
          </div>

          <!-- Action buttons -->
          <div style="display:flex;flex-direction:column;gap:10px;">
            <button type="submit"
                    id="btn-publish"
                    class="btn btn--primary"
                    style="width:100%;justify-content:center;"
                    onclick="setAction('publish')">
              <span class="material-symbols-outlined" style="font-size:18px;">publish</span>
              <?= e(__('create.publish', 'Publish Board')) ?>
            </button>
            <button type="submit"
                    id="btn-draft"
                    class="btn btn--secondary"
                    style="width:100%;justify-content:center;"
                    onclick="setAction('draft')">
              <span class="material-symbols-outlined" style="font-size:18px;">draft</span>
              <?= e(__('create.draft', 'Save Draft')) ?>
            </button>
          </div>

        </div><!-- /card -->
      </div><!-- /grid -->
    </form>

  </div><!-- /container -->
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
(function () {
  /* ── State ─────────────────────────────────────────────────────────────── */
  const MAX_FILES  = 10;
  let   fileList   = []; // { file, objectUrl }

  const dropZone    = document.getElementById('drop-zone');
  const fileInput   = document.getElementById('file-input');
  const previewGrid = document.getElementById('preview-grid');
  const countNote   = document.getElementById('image-count-note');
  const countSpan   = document.getElementById('image-count');
  const titleInput  = document.getElementById('title');
  const titleChars  = document.getElementById('title-chars');
  const tagsInput   = document.getElementById('style-tags-input');

  /* ── Drag & drop ───────────────────────────────────────────────────────── */
  dropZone.addEventListener('dragover', function (e) {
    e.preventDefault();
    this.style.borderColor = 'var(--accent)';
    this.style.background  = 'rgba(127,119,221,.06)';
  });
  dropZone.addEventListener('dragleave', function () {
    this.style.borderColor = 'var(--border)';
    this.style.background  = 'var(--surface)';
  });
  dropZone.addEventListener('drop', function (e) {
    e.preventDefault();
    this.style.borderColor = 'var(--border)';
    this.style.background  = 'var(--surface)';
    addFiles(Array.from(e.dataTransfer.files));
  });

  /* ── File input change ─────────────────────────────────────────────────── */
  fileInput.addEventListener('change', function () {
    addFiles(Array.from(this.files));
    this.value = ''; // reset so same files can be added again if removed
  });

  /* ── Add files ─────────────────────────────────────────────────────────── */
  function addFiles(newFiles) {
    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
    newFiles.forEach(function (f) {
      if (fileList.length >= MAX_FILES) return;
      if (!allowed.includes(f.type)) return;
      if (f.size > 5 * 1024 * 1024) return; // client-side size hint
      fileList.push({ file: f, objectUrl: URL.createObjectURL(f) });
    });
    renderPreviews();
    syncFileInput();
  }

  /* ── Remove file ───────────────────────────────────────────────────────── */
  function removeFile(index) {
    URL.revokeObjectURL(fileList[index].objectUrl);
    fileList.splice(index, 1);
    renderPreviews();
    syncFileInput();
  }

  /* ── Render preview thumbnails ─────────────────────────────────────────── */
  function renderPreviews() {
    previewGrid.innerHTML = '';

    fileList.forEach(function (item, idx) {
      const cell = document.createElement('div');
      cell.style.cssText = 'position:relative;border-radius:8px;overflow:hidden;aspect-ratio:1;background:var(--surface);border:1px solid var(--border);';

      const img = document.createElement('img');
      img.src   = item.objectUrl;
      img.alt   = 'Preview ' + (idx + 1);
      img.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block;';

      const btn = document.createElement('button');
      btn.type  = 'button';
      btn.title = 'Remove';
      btn.style.cssText = 'position:absolute;top:4px;right:4px;background:rgba(0,0,0,.6);border:none;border-radius:50%;width:24px;height:24px;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;';
      btn.innerHTML = '<span class="material-symbols-outlined" style="color:#fff;font-size:14px;">close</span>';
      btn.addEventListener('click', function () { removeFile(idx); });

      cell.appendChild(img);
      cell.appendChild(btn);
      previewGrid.appendChild(cell);
    });

    /* Add-more cell */
    if (fileList.length < MAX_FILES) {
      const addCell = document.createElement('div');
      addCell.style.cssText = 'border:2px dashed var(--border);border-radius:8px;aspect-ratio:1;display:flex;align-items:center;justify-content:center;cursor:pointer;background:var(--surface);transition:border-color .15s;';
      addCell.title = 'Add more images';
      addCell.innerHTML = '<span class="material-symbols-outlined" style="font-size:28px;color:var(--muted);">add_photo_alternate</span>';
      addCell.addEventListener('click', function () { document.getElementById('file-input').click(); });
      addCell.addEventListener('mouseover', function () { this.style.borderColor = 'var(--accent)'; });
      addCell.addEventListener('mouseout',  function () { this.style.borderColor = 'var(--border)'; });
      previewGrid.appendChild(addCell);
    }

    /* Counter */
    if (fileList.length > 0) {
      countSpan.textContent   = fileList.length;
      countNote.style.display = 'block';
    } else {
      countNote.style.display = 'none';
    }
  }

  /* ── Sync FileList to a hidden input set via DataTransfer ──────────────── */
  function syncFileInput() {
    const dt = new DataTransfer();
    fileList.forEach(function (item) { dt.items.add(item.file); });
    fileInput.files = dt.files;
  }

  /* ── Title char counter ────────────────────────────────────────────────── */
  titleInput.addEventListener('input', function () {
    titleChars.textContent = this.value.length;
  });
  titleChars.textContent = titleInput.value.length;

  /* ── Preset tag toggles ────────────────────────────────────────────────── */
  window.togglePresetTag = function (btn) {
    const tag     = btn.dataset.tag;
    const current = tagsInput.value;
    const tags    = current.split(',').map(function (t) { return t.trim(); }).filter(Boolean);
    const idx     = tags.indexOf(tag);
    if (idx > -1) {
      tags.splice(idx, 1);
      btn.classList.remove('tag--active');
      btn.style.borderColor = 'var(--border)';
      btn.style.background  = 'var(--surface)';
      btn.style.color       = '';
    } else {
      tags.push(tag);
      btn.classList.add('tag--active');
      btn.style.borderColor = 'var(--accent)';
      btn.style.background  = 'var(--accent)';
      btn.style.color       = '#fff';
    }
    tagsInput.value = tags.join(', ');
  };

  /* Mark active presets if form re-populated on error */
  (function syncPresetsFromInput() {
    const existing = tagsInput.value.split(',').map(function (t) { return t.trim().toLowerCase(); });
    document.querySelectorAll('#preset-tags [data-tag]').forEach(function (btn) {
      if (existing.includes(btn.dataset.tag.toLowerCase())) {
        btn.classList.add('tag--active');
        btn.style.borderColor = 'var(--accent)';
        btn.style.background  = 'var(--accent)';
        btn.style.color       = '#fff';
      }
    });
  })();

  /* ── Form action toggle ────────────────────────────────────────────────── */
  window.setAction = function (action) {
    document.getElementById('form-action').value = action;
  };

  /* ── Basic client-side validation ──────────────────────────────────────── */
  document.getElementById('create-form').addEventListener('submit', function (e) {
    if (titleInput.value.trim() === '') {
      e.preventDefault();
      titleInput.focus();
      titleInput.style.borderColor = '#D7373F';
      setTimeout(function () { titleInput.style.borderColor = ''; }, 2000);
    }
  });
})();
</script>
