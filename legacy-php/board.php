<?php
/**
 * board.php — Board detail page (public).
 * GET  ?id=N           → render board
 * POST action=like     → toggle like, returns JSON {liked, count}
 * POST action=follow   → toggle follow on board owner, returns JSON {following, followers}
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

session_start_if_not_started();

$db = getDB();

/* ── Validate id param ─────────────────────────────────────────────────────── */
$boardId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($boardId <= 0) {
    header('Location: /404.php');
    exit;
}

$board = getBoardById($db, $boardId);
$currentUserId = currentUserId();

if (!$board) {
    header('Location: /404.php');
    exit;
}

/* Draft boards are only visible to the owner */
if ($board['is_draft'] && $board['user_id'] !== $currentUserId) {
    header('Location: /404.php');
    exit;
}

/* ── AJAX POST handlers ────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$currentUserId) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Login required']);
        exit;
    }

    verifyCsrf();
    $action = $_POST['action'] ?? '';
    header('Content-Type: application/json');

    if ($action === 'like') {
        $liked = hasLiked($db, $boardId, $currentUserId);
        if ($liked) {
            $db->prepare('DELETE FROM board_likes WHERE board_id = ? AND user_id = ?')
               ->execute([$boardId, $currentUserId]);
        } else {
            $db->prepare('INSERT IGNORE INTO board_likes (board_id, user_id) VALUES (?, ?)')
               ->execute([$boardId, $currentUserId]);
            createNotification($db, $board['user_id'], $currentUserId, 'like', $boardId);
        }
        $count = getLikeCount($db, $boardId);
        echo json_encode(['liked' => !$liked, 'count' => $count]);
        exit;
    }

    if ($action === 'follow') {
        $ownerId  = (int)$board['user_id'];
        $following = isFollowing($db, $currentUserId, $ownerId);
        if ($following) {
            $db->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?')
               ->execute([$currentUserId, $ownerId]);
        } else {
            $db->prepare('INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)')
               ->execute([$currentUserId, $ownerId]);
            createNotification($db, $ownerId, $currentUserId, 'follow');
        }
        $followers = getFollowerCount($db, $ownerId);
        echo json_encode(['following' => !$following, 'followers' => $followers]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
    exit;
}

/* ── Load board data ───────────────────────────────────────────────────────── */
$images       = getBoardImages($db, $boardId);
$likeCount    = getLikeCount($db, $boardId);
$saveCount    = getSaveCount($db, $boardId);
$isLiked      = $currentUserId ? hasLiked($db, $boardId, $currentUserId) : false;
$isOwnerFollowed = ($currentUserId && $currentUserId !== (int)$board['user_id'])
                   ? isFollowing($db, $currentUserId, (int)$board['user_id'])
                   : false;
$followerCount = getFollowerCount($db, (int)$board['user_id']);

/* Save state */
$isSaved = $currentUserId ? hasSaved($db, $boardId, $currentUserId) : false;

$tags     = parseTags($board['style_tags'] ?? '');
$boardTitle = localizedBoardTitle($board);
$boardDescription = localizedBoardDescription($board);
$coverUrl = '';
if (!empty($board['cover_image'])) {
    $coverUrl = thumbnailUrl($board['cover_image'] ?? '', 1280, 720);
} elseif (!empty($images)) {
    $coverUrl = thumbnailUrl($images[0]['image_url'] ?? '', 1280, 720);
}

/* Palette placeholder — actual colors loaded client-side via /api/palette.php */
$boardPalette   = [];
$paletteSource  = $board['cover_image'] ?? ($images[0]['image_url'] ?? null);
// Colors will be fetched via AJAX on the client

/* Related boards: same tag overlap, exclude current */
$relatedBoards = [];
if (!empty($tags)) {
    $firstTag = $tags[0];
    $stmt = $db->prepare(
        "SELECT b.*, u.username, u.avatar,
                (SELECT COUNT(*) FROM board_likes bl WHERE bl.board_id = b.id) AS like_count,
                " . (dbColumnExists($db, 'boards', 'save_count') ? 'b.save_count' : '0') . " AS save_count
         FROM boards b
         JOIN users u ON u.id = b.user_id
         WHERE b.id <> ? AND b.is_draft = 0
           AND FIND_IN_SET(?, b.style_tags)
         ORDER BY b.created_at DESC
         LIMIT 3"
    );
    $stmt->execute([$boardId, $firstTag]);
    $relatedBoards = $stmt->fetchAll();
}

/* Comments */
$comments = [];
try {
    $cStmt = $db->prepare(
        'SELECT bc.*, u.username, u.avatar FROM board_comments bc
         JOIN users u ON u.id = bc.user_id
         WHERE bc.board_id = ?
         ORDER BY bc.created_at ASC LIMIT 50'
    );
    $cStmt->execute([$boardId]);
    $comments = $cStmt->fetchAll();
} catch (Exception $e) {}

/* Current user record (for comment form avatar) */
$currentUser = [];
if ($currentUserId) {
    $cuStmt = $db->prepare('SELECT username, avatar FROM users WHERE id = ? LIMIT 1');
    $cuStmt->execute([$currentUserId]);
    $currentUser = $cuStmt->fetch() ?: [];
}

/* ── View counter (session-based dedup) ────────────────────────────────────── */
$viewKey = 'viewed_board_' . $boardId;
if (!isset($_SESSION[$viewKey])) {
    try {
        $db->prepare('UPDATE boards SET view_count = view_count + 1 WHERE id = ?')->execute([$boardId]);
        $_SESSION[$viewKey] = true;
    } catch (Exception $e) {}
}
$viewCount = 0;
try {
    $vcStmt = $db->prepare('SELECT view_count FROM boards WHERE id = ? LIMIT 1');
    $vcStmt->execute([$boardId]);
    $viewCount = (int)($vcStmt->fetchColumn() ?: 0);
} catch (Exception $e) {}

$pageTitle    = $boardTitle . ' — Visual Design Journey';
$metaDesc     = trim(strip_tags($boardDescription));
if ($metaDesc === '') {
    $metaDesc = sprintf(__('board.meta_fallback', 'Explore %s, a curated visual design mood board by %s.'), $boardTitle, $board['username']);
}
$metaDesc     = mb_substr($metaDesc, 0, 155);
$canonicalUrl = publicUrl(boardUrl($board));
$metaImage    = $coverUrl ?: publicUrl('/assets/img/og-default.jpg');
$shareBoardUrl = publicUrl(boardUrl($board));
$activeNav = 'explore';
require_once __DIR__ . '/includes/header.php';
?>
<main class="page-wrap" id="main-content">

  <!-- ── Hero Cover ──────────────────────────────────────────────────────── -->
  <div class="board-hero" style="width:100%;height:400px;overflow:hidden;background:var(--accent);position:relative;">
    <?php if ($coverUrl): ?>
      <img src="<?= e($coverUrl) ?>"
           alt="<?= e($boardTitle) ?> cover"
           decoding="async"
           fetchpriority="high"
           style="width:100%;height:100%;object-fit:cover;display:block;"/>
      <div style="position:absolute;inset:0;background:linear-gradient(to bottom,transparent 50%,rgba(0,0,0,.45));"></div>
    <?php else: ?>
      <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
        <span class="material-symbols-outlined" style="font-size:72px;color:rgba(255,255,255,.4);">collections</span>
      </div>
    <?php endif; ?>
  </div>

  <div class="container" style="padding-top:var(--gutter);padding-bottom:48px;">
    <div style="display:grid;grid-template-columns:1fr minmax(0,320px);gap:32px;align-items:start;" class="board-detail-grid">

      <!-- ── Main Column ──────────────────────────────────────────────────── -->
      <div>

        <!-- Board title -->
        <h1 style="font-size:2rem;font-weight:700;line-height:1.15;margin:0 0 16px;">
          <?= e($boardTitle) ?>
          <?php if ($board['is_draft']): ?>
            <span class="tag" style="font-size:.75rem;vertical-align:middle;margin-left:8px;background:var(--surface);">Draft</span>
          <?php endif; ?>
        </h1>

        <!-- Author row -->
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
          <a href="/profile.php?u=<?= urlencode($board['username']) ?>"
             style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;">
            <?php if (!empty($board['avatar'])): ?>
              <img src="<?= e(imageUrl($board['avatar'] ?? "")) ?>"
                   alt="<?= e($board['username']) ?>"
                   class="avatar avatar--md"
                   style="border:1px solid var(--border);"/>
            <?php else: ?>
              <span class="avatar avatar--md"
                    style="background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:15px;border:1px solid var(--border);">
                <?= strtoupper(substr($board['username'], 0, 1)) ?>
              </span>
            <?php endif; ?>
            <span>
              <span style="font-size:.75rem;color:var(--muted);display:block;line-height:1;">Curated by</span>
              <span style="font-weight:600;font-size:.9375rem;"><?= e($board['username']) ?></span>
            </span>
          </a>

          <?php if ($currentUserId && $currentUserId !== (int)$board['user_id']): ?>
            <button id="follow-btn"
                    class="btn btn--secondary btn--sm"
                    data-owner-id="<?= (int)$board['user_id'] ?>"
                    data-following="<?= $isOwnerFollowed ? '1' : '0' ?>"
                    data-csrf="<?= e(csrfToken()) ?>"
                    style="margin-left:4px;">
              <span class="material-symbols-outlined" style="font-size:15px;"><?= $isOwnerFollowed ? 'person_remove' : 'person_add' ?></span>
              <span id="follow-label"><?= $isOwnerFollowed ? 'Following' : 'Follow' ?></span>
            </button>
          <?php endif; ?>

          <span style="color:var(--muted);font-size:.8125rem;margin-left:auto;">
            <?= timeAgo($board['created_at']) ?>
          </span>
        </div>

        <?php if ($boardDescription !== ''): ?>
          <p style="color:var(--muted);font-size:.9375rem;line-height:1.6;margin:0 0 20px;max-width:640px;">
            <?= nl2br(e($boardDescription)) ?>
          </p>
        <?php endif; ?>

        <!-- Dynamic color palette (loaded via AJAX) -->
        <div class="board-palette" id="board-palette" data-board="<?= $boardId ?>" style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
          <span class="board-palette__label" style="font-size:.8125rem;color:var(--muted);margin-right:4px;">Palette</span>
          <span style="color:var(--muted);font-size:12px;">Loading&hellip;</span>
        </div>
        <script>
        fetch('/api/palette.php?board_id=<?= $boardId ?>')
          .then(r => r.json())
          .then(d => {
            const el = document.getElementById('board-palette');
            if (!el || !d.colors || !d.colors.length) { if (el) el.remove(); return; }
            el.innerHTML = '<span class="board-palette__label" style="font-size:.8125rem;color:var(--muted);margin-right:4px;">Palette</span>'
              + d.colors.map(c =>
                  `<div class="board-palette__swatch"
                        style="width:28px;height:28px;border-radius:50%;background:${c};border:2px solid var(--bg);box-shadow:0 0 0 1px var(--border);cursor:pointer;"
                        data-hex="${c}"
                        title="${c}"
                        onclick="navigator.clipboard.writeText('${c}')"></div>`
                ).join('');
          })
          .catch(() => document.getElementById('board-palette')?.remove());
        </script>

        <!-- Style tags -->
        <?php if (!empty($tags)): ?>
          <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:28px;">
            <?php foreach ($tags as $tag): ?>
              <a href="<?= e(vdj_localized_url('/')) ?>?tag=<?= urlencode($tag) ?>" class="tag tag--accent"><?= e($tag) ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Image masonry grid -->
        <?php if (!empty($images)): ?>
          <div class="masonry" id="board-masonry" style="margin-top:8px;">
            <?php foreach ($images as $img):
                $imgSrc     = imageUrl($img['image_url'] ?? '');
                $isExternal = str_starts_with($imgSrc, 'http://') || str_starts_with($imgSrc, 'https://');
                $lbRel      = $isExternal ? 'rel="nofollow noopener noreferrer"' : 'rel="noopener"';
              ?>
              <figure style="break-inside:avoid;margin:0 0 var(--gutter);">
              <a href="<?= e($imgSrc) ?>"
                 class="board-card__img"
                 data-lightbox="board-<?= $boardId ?>"
                 <?= $lbRel ?>
                 style="display:block;border-radius:var(--r-lg);overflow:hidden;cursor:zoom-in;">
                <img src="<?= e($imgSrc) ?>"
                     alt="<?= e(($img['caption'] ?? '') ?: $boardTitle . ' reference image') ?>"
                     loading="lazy"
                     decoding="async"
                     style="width:100%;height:auto;display:block;border-radius:var(--r-lg);transition:transform .2s ease;"
                     onmouseover="this.style.transform='scale(1.02)'"
                     onmouseout="this.style.transform='scale(1)'"/>
              </a>
              <?php if (!empty($img['caption']) || !empty($img['credit']) || !empty($img['source_platform']) || !empty($img['source_url'])): ?>
                <figcaption style="font-size:.75rem;color:var(--muted);line-height:1.45;margin-top:6px;padding:0 4px;display:flex;align-items:baseline;flex-wrap:wrap;gap:4px;">
                  <?php if (!empty($img['caption'])): ?>
                    <span><?= e($img['caption']) ?></span>
                  <?php endif; ?>
                  <?php if (!empty($img['credit'])): ?>
                    <span style="opacity:.7;"><?= !empty($img['caption']) ? '·' : '' ?> Credit: <?= e($img['credit']) ?></span>
                  <?php elseif (!empty($img['source_platform'])): ?>
                    <span style="opacity:.7;"><?= !empty($img['caption']) ? '·' : '' ?> <?= e($img['source_platform']) ?></span>
                  <?php endif; ?>
                  <?php if (!empty($img['source_url'])): ?>
                    <a href="<?= e($img['source_url']) ?>"
                       target="_blank"
                       rel="nofollow noopener noreferrer"
                       style="color:var(--accent);text-decoration:none;font-size:.7rem;margin-left:auto;white-space:nowrap;"
                       aria-label="View original source">
                      View source <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:.6rem;"></i>
                    </a>
                  <?php endif; ?>
                </figcaption>
              <?php endif; ?>
              </figure>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div style="text-align:center;padding:64px 24px;background:var(--surface);border-radius:var(--r-lg);color:var(--muted);">
            <span class="material-symbols-outlined" style="font-size:48px;display:block;margin-bottom:8px;">image_not_supported</span>
            No images in this board yet.
          </div>
        <?php endif; ?>

        <!-- ── Comments Section ──────────────────────────────────────────── -->
        <section class="comments-section" aria-label="Comments" style="margin-top:48px;">
          <h3 style="font-size:1rem;font-weight:700;margin:0 0 20px;display:flex;align-items:center;gap:8px;">
            <i class="fa-regular fa-comment" style="color:var(--accent);"></i>
            Comments <span id="comment-count"><?= count($comments) ?></span>
          </h3>

          <?php if (isset($_SESSION['user_id'])): ?>
          <form id="comment-form" class="comment-form" style="display:flex;align-items:flex-start;gap:12px;margin-bottom:28px;">
            <input type="hidden" name="board_id" value="<?= $boardId ?>">
            <?php if (!empty($currentUser) && !empty($currentUser['avatar'])): ?>
              <img src="<?= e(imageUrl($currentUser['avatar'])) ?>"
                   class="comment-item__avatar"
                   style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;"
                   alt="">
            <?php else: ?>
              <div class="comment-item__avatar-placeholder"
                   style="width:36px;height:36px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;">
                <?= strtoupper(substr($currentUser['username'] ?? 'U', 0, 1)) ?>
              </div>
            <?php endif; ?>
            <textarea name="content"
                      placeholder="Add a comment&hellip;"
                      maxlength="500"
                      aria-label="Write a comment"
                      style="flex:1;resize:vertical;min-height:72px;padding:10px 12px;border:1px solid var(--border);border-radius:var(--r-lg);font-size:.9375rem;background:var(--bg);color:var(--text);line-height:1.5;"></textarea>
            <button type="submit" class="btn btn--primary btn--sm">Post</button>
          </form>
          <?php else: ?>
          <p style="font-size:14px;color:var(--muted);margin-bottom:20px;">
            <a href="/login.php?next=<?= urlencode('/board.php?id=' . $boardId) ?>" style="color:var(--accent);">Sign in</a> to leave a comment.
          </p>
          <?php endif; ?>

          <div id="comments-list">
            <?php if (empty($comments)): ?>
              <div class="comments-empty" style="padding:24px;text-align:center;color:var(--muted);font-size:.9375rem;">No comments yet. Be the first!</div>
            <?php else: ?>
              <?php foreach ($comments as $c): ?>
              <div class="comment-item" data-comment-id="<?= (int)$c['id'] ?>"
                   style="display:flex;gap:12px;margin-bottom:20px;">
                <?php if (!empty($c['avatar'])): ?>
                  <img src="<?= e(imageUrl($c['avatar'])) ?>"
                       class="comment-item__avatar"
                       style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;"
                       alt="<?= e($c['username']) ?>">
                <?php else: ?>
                  <div class="comment-item__avatar-placeholder"
                       style="width:36px;height:36px;border-radius:50%;background:var(--surface);color:var(--muted);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;">
                    <?= strtoupper(substr($c['username'], 0, 1)) ?>
                  </div>
                <?php endif; ?>
                <div class="comment-item__body" style="flex:1;min-width:0;">
                  <div class="comment-item__header" style="display:flex;align-items:center;gap:8px;margin-bottom:4px;flex-wrap:wrap;">
                    <a href="/profile.php?u=<?= urlencode($c['username']) ?>"
                       class="comment-item__username"
                       style="font-weight:600;font-size:.875rem;color:inherit;text-decoration:none;">
                      @<?= e($c['username']) ?>
                    </a>
                    <span class="comment-item__time"
                          style="font-size:.75rem;color:var(--muted);">
                      <?= date('M j, Y', strtotime($c['created_at'])) ?>
                    </span>
                    <?php if ($currentUserId && (int)$c['user_id'] === $currentUserId): ?>
                    <button class="comment-item__delete"
                            data-comment="<?= (int)$c['id'] ?>"
                            aria-label="Delete comment"
                            style="background:none;border:none;cursor:pointer;color:var(--muted);margin-left:auto;padding:2px 4px;font-size:.75rem;">
                      <i class="fa-solid fa-trash-can"></i>
                    </button>
                    <?php endif; ?>
                  </div>
                  <p class="comment-item__text"
                     style="margin:0;font-size:.9375rem;line-height:1.5;word-break:break-word;">
                    <?= e($c['content']) ?>
                  </p>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </section>

      </div><!-- /main col -->

      <!-- ── Sidebar ──────────────────────────────────────────────────────── -->
      <aside style="position:sticky;top:calc(var(--nav-h) + 24px);">
        <div style="text-align:center;margin-bottom:20px;"><?= vdj_ad('rectangle_small') ?></div>

        <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--r-lg);padding:24px;margin-bottom:20px;">

          <!-- Save / like / share row -->
          <?php if ($currentUserId && $currentUserId !== (int)$board['user_id']): ?>
            <a href="/create.php?save_from=<?= $boardId ?>"
               class="btn btn--primary"
               style="width:100%;justify-content:center;margin-bottom:16px;">
              <span class="material-symbols-outlined" style="font-size:18px;">bookmark_add</span>
              Save Board
            </a>
          <?php elseif (!$currentUserId): ?>
            <a href="/login.php?next=<?= urlencode('/board.php?id=' . $boardId) ?>"
               class="btn btn--primary"
               style="width:100%;justify-content:center;margin-bottom:16px;">
              <span class="material-symbols-outlined" style="font-size:18px;">bookmark_add</span>
              Save Board
            </a>
          <?php elseif ($currentUserId === (int)$board['user_id']): ?>
            <a href="/edit-board.php?id=<?= $boardId ?>"
               class="btn btn--secondary"
               style="width:100%;justify-content:center;margin-bottom:16px;">
              <span class="material-symbols-outlined" style="font-size:18px;">edit</span>
              Edit Board
            </a>
          <?php endif; ?>

          <div style="display:flex;gap:12px;">
            <!-- Like button -->
            <button id="like-btn"
                    class="btn <?= $isLiked ? 'btn--primary' : 'btn--secondary' ?>"
                    style="flex:1;justify-content:center;gap:6px;"
                    data-board-id="<?= $boardId ?>"
                    data-liked="<?= $isLiked ? '1' : '0' ?>"
                    data-csrf="<?= e(csrfToken()) ?>"
                    aria-label="<?= $isLiked ? 'Unlike' : 'Like' ?> this board (<?= formatCount($likeCount) ?> likes)"
                    aria-pressed="<?= $isLiked ? 'true' : 'false' ?>"
                    <?= !$currentUserId ? 'onclick="window.location=\'/login.php?next=' . urlencode('/board.php?id=' . $boardId) . '\'"' : '' ?>>
              <span class="material-symbols-outlined" id="like-icon" aria-hidden="true" style="font-size:18px;"><?= $isLiked ? 'favorite' : 'favorite_border' ?></span>
              <span id="like-count" aria-live="polite"><?= formatCount($likeCount) ?></span>
            </button>

            <!-- Save button -->
            <button class="save-btn btn btn--secondary btn--icon"
                    data-board="<?= $boardId ?>"
                    data-saved="<?= $isSaved ? '1' : '0' ?>"
                    aria-label="<?= $isSaved ? 'Unsave' : 'Save' ?> this board">
              <span class="material-symbols-outlined <?= $isSaved ? 'icon-filled text-accent' : '' ?>"
                    aria-hidden="true"
                    style="font-size:18px;">bookmark</span>
              <span class="save-count" aria-live="polite"><?= formatCount($saveCount) ?></span>
            </button>

            <!-- Export PDF button -->
            <button id="export-pdf-btn"
                    class="btn btn--secondary btn--icon"
                    title="Export as PDF"
                    aria-label="Export board as PDF">
              <span class="material-symbols-outlined" aria-hidden="true" style="font-size:18px;">picture_as_pdf</span>
            </button>

            <!-- Share button -->
            <button class="btn btn--secondary btn--icon"
                    id="share-btn"
                    aria-label="Copy link to this board"
                    onclick="navigator.clipboard.writeText(window.location.href).then(()=>{
                      this.setAttribute('aria-label','Link copied!');
                      setTimeout(()=>this.setAttribute('aria-label','Copy link to this board'),2000)
                    });">
              <span class="material-symbols-outlined" aria-hidden="true" style="font-size:18px;">share</span>
            </button>
          </div>

          <!-- Follower count -->
          <p style="text-align:center;font-size:.8125rem;color:var(--muted);margin:12px 0 0;">
            <span id="follower-count"><?= formatCount($followerCount) ?></span>
            follower<?= $followerCount !== 1 ? 's' : '' ?> of <?= e($board['username']) ?>
          </p>

          <!-- View count -->
          <p style="text-align:center;font-size:.8125rem;color:var(--muted);margin:4px 0 0;">
            <span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">visibility</span>
            <?= formatCount($viewCount) ?> views
          </p>

          <!-- Social share row -->
          <div class="board-social-share" style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);">
            <p style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-bottom:8px;">Share</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
              <!-- X/Twitter -->
              <a href="https://x.com/intent/tweet?url=<?= urlencode($shareBoardUrl) ?>&text=<?= urlencode($boardTitle . ' — Visual Design Journey') ?>"
                 target="_blank" rel="noopener"
                 class="btn btn--secondary btn--sm" style="gap:6px;padding:6px 10px;"
                 aria-label="Share on X">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.259 5.632L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                X
              </a>
              <!-- Pinterest -->
              <a href="https://pinterest.com/pin/create/button/?url=<?= urlencode($shareBoardUrl) ?>&media=<?= urlencode($coverUrl ?: publicUrl('/assets/img/og-default.jpg')) ?>&description=<?= urlencode($boardTitle) ?>"
                 target="_blank" rel="noopener"
                 class="btn btn--secondary btn--sm" style="gap:6px;padding:6px 10px;color:#e60023;"
                 aria-label="Save to Pinterest">
                <i class="fa-brands fa-pinterest" style="font-size:14px;"></i>
                Save
              </a>
              <!-- WhatsApp -->
              <a href="https://wa.me/?text=<?= urlencode($boardTitle . ' — ' . $shareBoardUrl) ?>"
                 target="_blank" rel="noopener"
                 class="btn btn--secondary btn--sm" style="gap:6px;padding:6px 10px;color:#25D366;"
                 aria-label="Share via WhatsApp">
                <i class="fa-brands fa-whatsapp" style="font-size:14px;"></i>
              </a>
              <!-- LinkedIn -->
              <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($shareBoardUrl) ?>"
                 target="_blank" rel="noopener"
                 class="btn btn--secondary btn--sm" style="gap:6px;padding:6px 10px;color:#0077B5;"
                 aria-label="Share on LinkedIn">
                <i class="fa-brands fa-linkedin" style="font-size:14px;"></i>
              </a>
              <!-- Embed code button -->
              <button onclick="document.getElementById('embed-modal').classList.add('open')"
                      class="btn btn--secondary btn--sm" style="gap:6px;padding:6px 10px;"
                      aria-label="Get embed code">
                <span class="material-symbols-outlined" style="font-size:14px;">code</span>
              </button>
            </div>
          </div>

        </div><!-- /action card -->

        <!-- Related boards -->
        <?php if (!empty($relatedBoards)): ?>
          <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--r-lg);padding:24px;">
            <h3 style="font-size:.875rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin:0 0 16px;">Related Boards</h3>
            <div style="display:flex;flex-direction:column;gap:12px;">
              <?php foreach ($relatedBoards as $rb):
                $rbTitle = localizedBoardTitle($rb);
                $rbImages  = getBoardImages($db, (int)$rb['id']);
                $rbThumb   = '';
                if (!empty($rb['cover_image'])) {
                    $rbThumb = e(thumbnailUrl($rb['cover_image'] ?? "", 112, 112));
                } elseif (!empty($rbImages)) {
                    $rbThumb = e(thumbnailUrl($rbImages[0]['image_url'] ?? "", 112, 112));
                }
              ?>
                <a href="<?= e(boardUrl($rb)) ?>"
                   style="display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;padding:8px;border-radius:var(--r-lg);transition:background .15s;"
                   onmouseover="this.style.background='var(--surface)'"
                   onmouseout="this.style.background='transparent'">
                  <?php if ($rbThumb): ?>
                    <img src="<?= $rbThumb ?>"
                         alt="<?= e($rbTitle) ?>"
                         loading="lazy"
                         decoding="async"
                         width="112"
                         height="112"
                         style="width:56px;height:56px;object-fit:cover;border-radius:8px;flex-shrink:0;border:1px solid var(--border);"/>
                  <?php else: ?>
                    <span style="width:56px;height:56px;border-radius:8px;background:var(--accent);flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                      <span class="material-symbols-outlined" style="color:#fff;font-size:22px;">collections</span>
                    </span>
                  <?php endif; ?>
                  <div style="min-width:0;">
                    <p style="font-weight:600;font-size:.875rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin:0 0 2px;"><?= e($rbTitle) ?></p>
                    <p style="font-size:.75rem;color:var(--muted);margin:0;">by <?= e($rb['username']) ?> · <?= formatCount((int)($rb['like_count'] ?? 0)) ?> likes · <?= formatCount((int)($rb['save_count'] ?? 0)) ?> saves</p>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <div class="sponsor-card">
          <span class="sponsor-card__eyebrow">For design tools and studios</span>
          <h3>Reach a focused design audience without interruptive ads.</h3>
          <p>Use sponsored boards, newsletter placements, or editorial partnerships instead of popups and broken iframes.</p>
          <a href="/sponsor.php" class="btn btn--primary btn--sm">
            <i class="fa-solid fa-handshake"></i> Partner with us
          </a>
        </div>

      </aside><!-- /sidebar -->
    </div><!-- /grid -->
  </div><!-- /container -->

  <!-- Embed code modal -->
  <div id="embed-modal" class="modal-overlay" role="dialog" aria-label="Embed code" aria-modal="true">
    <div class="modal" style="max-width:480px;">
      <div class="modal__header">
        <span class="modal__title">Embed this board</span>
        <button class="modal__close" aria-label="Close">&times;</button>
      </div>
      <div class="modal__body">
        <p style="font-size:.875rem;color:var(--muted);margin-bottom:12px;">Copy and paste into your website or blog:</p>
        <textarea id="embed-code-textarea" readonly
                  style="width:100%;font-family:monospace;font-size:12px;padding:10px;border:1px solid var(--border);border-radius:var(--r-md);background:var(--surface);color:var(--text);resize:vertical;min-height:100px;"
                  onclick="this.select()"><?= htmlspecialchars('<iframe src="' . $shareBoardUrl . '" width="600" height="400" frameborder="0" style="border-radius:12px;border:1px solid #e9ecef;" title="' . addslashes($boardTitle) . ' — Visual Design Journey"></iframe>') ?></textarea>
        <button onclick="navigator.clipboard.writeText(document.getElementById('embed-code-textarea').value);this.textContent='Copied!';"
                class="btn btn--primary btn--sm" style="margin-top:10px;">Copy code</button>
      </div>
    </div>
  </div>

  <!-- ── Lightbox overlay ─────────────────────────────────────────────────── -->
  <div id="lightbox"
       style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9000;align-items:center;justify-content:center;padding:24px;"
       onclick="document.getElementById('lightbox').style.display='none'">
    <img id="lightbox-img" src="" alt="Enlarged view"
         style="max-width:90vw;max-height:90vh;border-radius:var(--r-lg);object-fit:contain;"/>
    <button onclick="event.stopPropagation();document.getElementById('lightbox').style.display='none';"
            style="position:absolute;top:20px;right:20px;background:rgba(255,255,255,.15);border:none;border-radius:50%;width:40px;height:40px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
      <span class="material-symbols-outlined" style="color:#fff;font-size:20px;">close</span>
    </button>
  </div>

</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
(function () {
  /* ── Like toggle ──────────────────────────────────────────────────────── */
  const likeBtn   = document.getElementById('like-btn');
  const likeIcon  = document.getElementById('like-icon');
  const likeCount = document.getElementById('like-count');

  if (likeBtn && likeBtn.dataset.boardId) {
    likeBtn.addEventListener('click', async function () {
      const csrf = this.dataset.csrf;
      const body = new URLSearchParams({ action: 'like', csrf_token: csrf });
      try {
        const res  = await fetch(window.location.pathname + '?id=<?= $boardId ?>', {
          method: 'POST', body, headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });
        const data = await res.json();
        if (data.error) return;
        const liked = data.liked;
        likeIcon.textContent = liked ? 'favorite' : 'favorite_border';
        likeCount.textContent = data.count >= 1000
          ? (data.count / 1000).toFixed(1) + 'k'
          : data.count;
        likeBtn.className = 'btn ' + (liked ? 'btn--primary' : 'btn--secondary');
        likeBtn.dataset.liked = liked ? '1' : '0';
      } catch (e) { console.error(e); }
    });
  }

  /* ── Follow toggle ────────────────────────────────────────────────────── */
  const followBtn   = document.getElementById('follow-btn');
  const followLabel = document.getElementById('follow-label');
  const followerEl  = document.getElementById('follower-count');

  if (followBtn) {
    followBtn.addEventListener('click', async function () {
      const csrf = this.dataset.csrf;
      const body = new URLSearchParams({ action: 'follow', csrf_token: csrf });
      try {
        const res  = await fetch(window.location.pathname + '?id=<?= $boardId ?>', {
          method: 'POST', body, headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });
        const data = await res.json();
        if (data.error) return;
        const following = data.following;
        followLabel.textContent = following ? 'Following' : 'Follow';
        followBtn.querySelector('.material-symbols-outlined').textContent =
          following ? 'person_remove' : 'person_add';
        if (followerEl) {
          followerEl.textContent = data.followers >= 1000
            ? (data.followers / 1000).toFixed(1) + 'k'
            : data.followers;
        }
        followBtn.dataset.following = following ? '1' : '0';
      } catch (e) { console.error(e); }
    });
  }

  /* ── Lightbox ─────────────────────────────────────────────────────────── */
  const lb    = document.getElementById('lightbox');
  const lbImg = document.getElementById('lightbox-img');
  document.querySelectorAll('[data-lightbox]').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      lbImg.src = this.href;
      lb.style.display = 'flex';
    });
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') lb.style.display = 'none';
  });

  /* ── Export PDF ───────────────────────────────────────────────────────── */
  const exportPdfBtn = document.getElementById('export-pdf-btn');
  if (exportPdfBtn) {
    exportPdfBtn.addEventListener('click', function () {
      window.print();
    });
  }

  /* Comment form & delete are handled globally by main.js */
})();
</script>
