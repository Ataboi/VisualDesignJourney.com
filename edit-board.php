<?php
/**
 * edit-board.php — Edit an existing board
 * Owner-only. Supports image management, tag editing, draft toggle, and board deletion.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();
session_start_if_not_started();

$db        = getDB();
$currentId = currentUserId();
$boardId   = (int)($_GET['id'] ?? 0);
$boardI18nReady = dbColumnExists($db, 'boards', 'title_tr');

if ($boardId < 1) {
    header('Location: /profile.php?u=' . urlencode((getUserById($db, $currentId)['username'] ?? '')));
    exit;
}

$board = getBoardById($db, $boardId);
if (!$board) {
    header('Location: /404.php');
    exit;
}
// Ownership check
if ((int)$board['user_id'] !== $currentId) {
    header('Location: /board.php?id=' . $boardId);
    exit;
}

$errors  = [];
$success = '';

/* ── POST HANDLERS ──────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    /* ── Save board details ──────────────────────────────────────────────────── */
    if ($action === 'save') {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $titleTr     = trim($_POST['title_tr'] ?? '');
        $titleDe     = trim($_POST['title_de'] ?? '');
        $slug        = slugify($_POST['slug'] ?? $title);
        $slugTr      = slugify($_POST['slug_tr'] ?? $titleTr);
        $slugDe      = slugify($_POST['slug_de'] ?? $titleDe);
        $descriptionTr = trim($_POST['description_tr'] ?? '');
        $descriptionDe = trim($_POST['description_de'] ?? '');
        $rawTags     = trim($_POST['style_tags'] ?? '');
        $isDraft     = isset($_POST['is_draft']) ? 1 : 0;

        if ($title === '') {
            $errors[] = __('edit.error_title_req', 'Board title is required.');
        } elseif (mb_strlen($title) > 120) {
            $errors[] = __('edit.error_title_long', 'Title must be 120 characters or fewer.');
        }

        if (empty($errors)) {
            // Normalise tags: trim each, remove empties, re-join
            $tags = implode(',', parseTags($rawTags));
            if ($boardI18nReady) {
                $stmt = $db->prepare(
                    'UPDATE boards SET slug=?, slug_tr=?, slug_de=?, title=?, title_tr=?, title_de=?, description=?, description_tr=?, description_de=?, style_tags=?, is_draft=? WHERE id=?'
                );
                $stmt->execute([$slug, $slugTr, $slugDe, $title, $titleTr, $titleDe, $description, $descriptionTr, $descriptionDe, $tags, $isDraft, $boardId]);
            } else {
                $stmt = $db->prepare(
                    'UPDATE boards SET title=?, description=?, style_tags=?, is_draft=? WHERE id=?'
                );
                $stmt->execute([$title, $description, $tags, $isDraft, $boardId]);
            }
            $success = __('edit.success_saved', 'Board saved successfully.');
            // Refresh board data
            $board = getBoardById($db, $boardId);
        }
    }

    /* ── Delete a single image ───────────────────────────────────────────────── */
    elseif ($action === 'delete_image') {
        $imageId = (int)($_POST['image_id'] ?? 0);
        if ($imageId > 0) {
            $imgStmt = $db->prepare(
                'SELECT bi.image_url FROM board_images bi
                 JOIN boards b ON b.id = bi.board_id
                 WHERE bi.id = ? AND b.user_id = ?'
            );
            $imgStmt->execute([$imageId, $currentId]);
            $img = $imgStmt->fetch();
            if ($img) {
                // Delete file from disk
                $filePath = UPLOAD_DIR . $img['image_url'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $db->prepare('DELETE FROM board_images WHERE id = ?')->execute([$imageId]);
                $success = 'Image removed.';
            }
        }
    }

    /* ── Add new images ──────────────────────────────────────────────────────── */
    elseif ($action === 'add_images') {
        $uploaded = 0;
        if (!empty($_FILES['new_images']['name'][0])) {
            // Determine next sort_order
            $orderStmt = $db->prepare(
                'SELECT COALESCE(MAX(sort_order), 0) FROM board_images WHERE board_id = ?'
            );
            $orderStmt->execute([$boardId]);
            $nextOrder = (int)$orderStmt->fetchColumn() + 1;

            $insertStmt = $db->prepare(
                'INSERT INTO board_images (board_id, image_url, sort_order) VALUES (?, ?, ?)'
            );
            $fileCount = count($_FILES['new_images']['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                $file = [
                    'name'     => $_FILES['new_images']['name'][$i],
                    'type'     => $_FILES['new_images']['type'][$i],
                    'tmp_name' => $_FILES['new_images']['tmp_name'][$i],
                    'error'    => $_FILES['new_images']['error'][$i],
                    'size'     => $_FILES['new_images']['size'][$i],
                ];
                if ($file['error'] === UPLOAD_ERR_NO_FILE) continue;
                try {
                    $relPath = handleImageUpload($file, 'boards/');
                    $insertStmt->execute([$boardId, $relPath, $nextOrder++]);
                    $uploaded++;
                } catch (RuntimeException $ex) {
                    $errors[] = 'Image ' . ($i + 1) . ': ' . $ex->getMessage();
                }
            }
            if ($uploaded > 0 && empty($errors)) {
                $success = $uploaded . ' image' . ($uploaded > 1 ? 's' : '') . ' added.';
            }
        }
    }

    /* ── Delete entire board ─────────────────────────────────────────────────── */
    elseif ($action === 'delete_board') {
        // Delete all image files first
        $imgStmt = $db->prepare('SELECT image_url FROM board_images WHERE board_id = ?');
        $imgStmt->execute([$boardId]);
        foreach ($imgStmt->fetchAll() as $img) {
            $filePath = UPLOAD_DIR . $img['image_url'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        // CASCADE in schema handles board_images and board_likes rows
        $db->prepare('DELETE FROM boards WHERE id = ? AND user_id = ?')
           ->execute([$boardId, $currentId]);

        $ownerUsername = getUserById($db, $currentId)['username'] ?? '';
        header('Location: /profile.php?u=' . urlencode($ownerUsername) . '&deleted=1');
        exit;
    }
}

// Re-load images after any mutations
$images = getBoardImages($db, $boardId);

$pageTitle = 'Edit Board — ' . localizedBoardTitle($board);
$activeNav = '';

require_once __DIR__ . '/includes/header.php';
?>

<main class="page-wrap" id="main-content">
<div class="container" style="padding-top:32px;padding-bottom:64px;">

  <?php if ($success): ?>
    <div class="form-hint" style="background:#eef9f1;border:1px solid #b7e5c8;border-radius:var(--r-lg);padding:12px 16px;margin-bottom:20px;color:#1a6636;">
      <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">check_circle</span>
      <?= e($success) ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="form-error" style="background:#fef2f2;border:1px solid #fca5a5;border-radius:var(--r-lg);padding:12px 16px;margin-bottom:20px;">
      <?php foreach ($errors as $err): ?>
        <p><?= e($err) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- 2-col layout: 7/12 media + 5/12 form -->
  <div style="display:grid;grid-template-columns:7fr 5fr;gap:40px;align-items:start;">

    <!-- ── LEFT: Media ──────────────────────────────────────────────────────── -->
    <section aria-label="Board images">

      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <h2 class="t-headline-md" style="margin:0;"><?= e(__('edit.media', 'Media')) ?></h2>
        <span class="t-caption text-muted"><?= count($images) ?> item<?= count($images) !== 1 ? 's' : '' ?></span>
      </div>

      <!-- Existing images grid -->
      <?php if ($images): ?>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:24px;">
        <?php foreach ($images as $img): ?>
        <div style="position:relative;aspect-ratio:4/5;border-radius:var(--r-lg);overflow:hidden;background:var(--surface);">
          <img src="<?= e(imageUrl($img['image_url'] ?? "")) ?>"
               alt="Board image"
               loading="lazy"
               style="width:100%;height:100%;object-fit:cover;display:block;"/>
          <!-- Remove button -->
          <form method="POST" action="/edit-board.php?id=<?= $boardId ?>"
                style="position:absolute;top:8px;right:8px;"
                onsubmit="return confirm('<?= e(__('edit.confirm_remove_image', 'Remove this image?')) ?>');">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"/>
            <input type="hidden" name="action" value="delete_image"/>
            <input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>"/>
            <button type="submit"
                    class="btn btn--sm btn--danger"
                    style="width:28px;height:28px;padding:0;border-radius:50%;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .15s;"
                    aria-label="Remove image"
                    onmouseenter="this.style.opacity='1'"
                    onmouseleave="this.style.opacity='0'">
              <span class="material-symbols-outlined" style="font-size:14px;">close</span>
            </button>
          </form>
          <!-- Always-visible remove for accessibility -->
          <noscript>
            <form method="POST" action="/edit-board.php?id=<?= $boardId ?>" style="position:absolute;top:8px;right:8px;">
              <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"/>
              <input type="hidden" name="action" value="delete_image"/>
              <input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>"/>
              <button type="submit" class="btn btn--sm btn--danger" style="width:28px;height:28px;padding:0;border-radius:50%;" aria-label="Remove image">×</button>
            </form>
          </noscript>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Add more images upload zone -->
      <form method="POST" action="/edit-board.php?id=<?= $boardId ?>"
            enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"/>
        <input type="hidden" name="action" value="add_images"/>

        <label for="new-images-input"
               style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                      gap:8px;padding:32px;border:2px dashed var(--border);border-radius:var(--r-lg);
                      background:var(--surface);cursor:pointer;text-align:center;
                      transition:border-color .15s,background .15s;"
               onmouseover="this.style.borderColor='var(--accent)';this.style.background='#f5f4fc';"
               onmouseout="this.style.borderColor='var(--border)';this.style.background='var(--surface)';">
          <span class="material-symbols-outlined" style="font-size:32px;color:var(--muted);">upload</span>
          <span class="t-body-md" style="font-weight:600;"><?= e(__('edit.add_images', 'Add more images')) ?></span>
          <span class="t-caption text-muted">JPG, PNG or WebP &middot; max 5 MB each</span>
          <input type="file"
                 id="new-images-input"
                 name="new_images[]"
                 accept="image/jpeg,image/png,image/webp"
                 multiple
                 style="display:none;"
                 onchange="this.closest('form').querySelector('#upload-submit').click();"/>
        </label>
        <button type="submit" id="upload-submit" style="display:none;" aria-hidden="true">Upload</button>
      </form>

    </section>

    <!-- ── RIGHT: Form (sticky) ──────────────────────────────────────────────── -->
    <aside style="position:sticky;top:88px;">

      <!-- Breadcrumb -->
      <nav aria-label="Breadcrumb" style="margin-bottom:20px;">
        <ol style="display:flex;align-items:center;gap:6px;list-style:none;margin:0;padding:0;">
          <li><a href="/profile.php?u=<?= urlencode($board['username']) ?>" class="t-caption text-muted" style="text-decoration:none;"><?= e(__('edit.my_boards', 'My Boards')) ?></a></li>
          <li><span class="t-caption text-muted">/</span></li>
          <li><a href="<?= e(boardUrl($board)) ?>" class="t-caption text-muted" style="text-decoration:none;"><?= e(localizedBoardTitle($board)) ?></a></li>
          <li><span class="t-caption text-muted">/</span></li>
          <li><span class="t-caption" style="color:var(--accent);">Edit</span></li>
        </ol>
      </nav>

      <h1 class="t-headline-md" style="margin-bottom:24px;"><?= e(__('edit.title', 'Edit Board')) ?></h1>

      <!-- Save form -->
      <form method="POST" action="/edit-board.php?id=<?= $boardId ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"/>
        <input type="hidden" name="action" value="save"/>

        <!-- Title -->
        <div class="form-group">
          <label class="form-label" for="board-title"><?= e(__('edit.board_title_label', 'Title')) ?> <span aria-hidden="true" style="color:var(--accent);">*</span></label>
          <input type="text"
                 id="board-title"
                 name="title"
                 class="form-input"
                 value="<?= e($board['title'] ?? '') ?>"
                 maxlength="120"
                 required
                 placeholder="Give your board a name"/>
        </div>

        <!-- Description -->
        <div class="form-group">
          <label class="form-label" for="board-desc"><?= e(__('edit.description_label', 'Description')) ?></label>
          <textarea id="board-desc"
                    name="description"
                    class="form-textarea"
                    rows="4"
                    maxlength="1000"
                    placeholder="What's the mood of this board?"><?= e($board['description'] ?? '') ?></textarea>
        </div>

        <?php if ($boardI18nReady): ?>
        <div class="form-group" style="border-top:1px solid var(--border);padding-top:18px;">
          <label class="form-label" for="board-slug">English SEO slug</label>
          <input type="text" id="board-slug" name="slug" class="form-input"
                 value="<?= e($board['slug'] ?? '') ?>"
                 placeholder="<?= e(slugify($board['title'] ?? '')) ?>"/>
        </div>

        <div class="form-group">
          <label class="form-label" for="board-title-tr">Türkçe başlık</label>
          <input type="text" id="board-title-tr" name="title_tr" class="form-input"
                 value="<?= e($board['title_tr'] ?? '') ?>"
                 maxlength="150"
                 placeholder="Türkçe board başlığı"/>
        </div>
        <div class="form-group">
          <label class="form-label" for="board-slug-tr">Türkçe SEO slug</label>
          <input type="text" id="board-slug-tr" name="slug_tr" class="form-input"
                 value="<?= e($board['slug_tr'] ?? '') ?>"
                 placeholder="turkce-board-slug"/>
        </div>
        <div class="form-group">
          <label class="form-label" for="board-desc-tr">Türkçe açıklama</label>
          <textarea id="board-desc-tr" name="description_tr" class="form-textarea" rows="3" maxlength="1000"
                    placeholder="Board açıklaması Türkçe"><?= e($board['description_tr'] ?? '') ?></textarea>
        </div>

        <div class="form-group" style="border-top:1px solid var(--border);padding-top:18px;">
          <label class="form-label" for="board-title-de">Deutscher Titel</label>
          <input type="text" id="board-title-de" name="title_de" class="form-input"
                 value="<?= e($board['title_de'] ?? '') ?>"
                 maxlength="150"
                 placeholder="Deutscher Board-Titel"/>
        </div>
        <div class="form-group">
          <label class="form-label" for="board-slug-de">Deutscher SEO slug</label>
          <input type="text" id="board-slug-de" name="slug_de" class="form-input"
                 value="<?= e($board['slug_de'] ?? '') ?>"
                 placeholder="deutscher-board-slug"/>
        </div>
        <div class="form-group">
          <label class="form-label" for="board-desc-de">Deutsche Beschreibung</label>
          <textarea id="board-desc-de" name="description_de" class="form-textarea" rows="3" maxlength="1000"
                    placeholder="Board-Beschreibung auf Deutsch"><?= e($board['description_de'] ?? '') ?></textarea>
        </div>
        <?php endif; ?>

        <!-- Style tags chip input -->
        <div class="form-group">
          <label class="form-label"><?= e(__('edit.style_tags_label', 'Style Tags')) ?></label>
          <div id="tags-container"
               style="display:flex;flex-wrap:wrap;gap:8px;padding:10px;border:1px solid var(--border);border-radius:var(--r-lg);background:var(--bg);min-height:46px;align-items:center;">
            <?php foreach (parseTags($board['style_tags'] ?? '') as $tag): ?>
              <span class="tag tag--accent" data-tag="<?= e($tag) ?>"
                    style="display:inline-flex;align-items:center;gap:4px;">
                <?= e($tag) ?>
                <button type="button"
                        aria-label="Remove tag <?= e($tag) ?>"
                        onclick="removeTag(this.closest('[data-tag]'))"
                        style="background:none;border:none;cursor:pointer;padding:0;line-height:1;color:inherit;font-size:14px;">&times;</button>
              </span>
            <?php endforeach; ?>
            <input type="text"
                   id="tag-input"
                   placeholder="Add tag, press Enter"
                   style="border:none;outline:none;font-size:14px;min-width:120px;flex:1;background:transparent;"
                   onkeydown="handleTagInput(event)"/>
          </div>
          <input type="hidden" id="style-tags-hidden" name="style_tags"
                 value="<?= e($board['style_tags'] ?? '') ?>"/>
          <p class="form-hint"><?= e(__('edit.tags_hint', 'Press Enter or comma to add a tag.')) ?></p>
        </div>

        <!-- Draft toggle -->
        <div class="form-group" style="display:flex;align-items:center;gap:10px;">
          <input type="checkbox"
                 id="is-draft"
                 name="is_draft"
                 style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer;"
                 <?= !empty($board['is_draft']) ? 'checked' : '' ?>/>
          <label for="is-draft" class="form-label" style="margin:0;cursor:pointer;"><?= e(__('edit.draft_label', 'Save as draft (hide from public)')) ?></label>
        </div>

        <!-- Action row -->
        <div style="display:flex;gap:12px;align-items:center;margin-top:24px;">
          <a href="<?= e(boardUrl($board)) ?>"
             class="btn btn--secondary"><?= e(__('edit.cancel', 'Cancel')) ?></a>
          <button type="submit" class="btn btn--primary" style="flex:1;">
            <span class="material-symbols-outlined" style="font-size:16px;">save</span>
            <?= e(__('edit.save', 'Save Changes')) ?>
          </button>
        </div>
      </form>

      <!-- Delete board (separate danger action) -->
      <div style="margin-top:32px;padding-top:24px;border-top:1px solid var(--border);">
        <p class="t-label" style="margin-bottom:12px;color:var(--muted);"><?= e(__('edit.danger', 'Danger Zone')) ?></p>
        <form method="POST" action="/edit-board.php?id=<?= $boardId ?>"
              onsubmit="return confirm('<?= e(__('edit.confirm_delete_board', 'Delete this board? This cannot be undone.')) ?>');">
          <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"/>
          <input type="hidden" name="action" value="delete_board"/>
          <button type="submit" class="btn btn--danger" style="width:100%;">
            <span class="material-symbols-outlined" style="font-size:16px;">delete</span>
            <?= e(__('edit.delete_board', 'Delete Board')) ?>
          </button>
        </form>
      </div>

    </aside>

  </div><!-- /grid -->

</div><!-- /container -->
</main>

<script>
/* ── Tag chip management ─────────────────────────────────────────── */
function syncHiddenTags() {
  const chips = document.querySelectorAll('#tags-container [data-tag]');
  const tags  = Array.from(chips).map(c => c.dataset.tag);
  document.getElementById('style-tags-hidden').value = tags.join(',');
}

function removeTag(chip) {
  chip.remove();
  syncHiddenTags();
}

function addTag(raw) {
  const val = raw.trim().replace(/,+$/, '').trim();
  if (!val) return;
  // Prevent duplicates
  const existing = Array.from(document.querySelectorAll('#tags-container [data-tag]'))
                        .map(c => c.dataset.tag.toLowerCase());
  if (existing.includes(val.toLowerCase())) return;

  const span = document.createElement('span');
  span.className  = 'tag tag--accent';
  span.dataset.tag = val;
  span.style.cssText = 'display:inline-flex;align-items:center;gap:4px;';
  span.innerHTML =
    `${val.replace(/[<>"'&]/g, c => ({'<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','&':'&amp;'}[c]))}
     <button type="button" aria-label="Remove tag ${val}"
             onclick="removeTag(this.closest('[data-tag]'))"
             style="background:none;border:none;cursor:pointer;padding:0;line-height:1;color:inherit;font-size:14px;">&times;</button>`;

  const input = document.getElementById('tag-input');
  document.getElementById('tags-container').insertBefore(span, input);
  syncHiddenTags();
}

function handleTagInput(e) {
  if (e.key === 'Enter' || e.key === ',') {
    e.preventDefault();
    addTag(e.target.value);
    e.target.value = '';
  }
}

/* Hover reveal for remove buttons */
document.querySelectorAll('.board-card__img, [style*="aspect-ratio:4/5"]').forEach(card => {
  const btn = card.querySelector('button[type="submit"]');
  if (!btn) return;
  card.addEventListener('mouseenter', () => btn.style.opacity = '1');
  card.addEventListener('mouseleave', () => btn.style.opacity = '0');
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
