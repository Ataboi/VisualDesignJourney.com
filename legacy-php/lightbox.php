<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$db      = getDB();
$boardId = (int)($_GET['board'] ?? 0);
$imgId   = (int)($_GET['img']   ?? 0);

if ($boardId <= 0) {
    http_response_code(404);
    exit('Board not found.');
}

$boardStmt = $db->prepare(
    'SELECT b.*, u.username, u.avatar
     FROM boards b
     JOIN users u ON u.id = b.user_id
     WHERE b.id = ? AND b.is_draft = 0'
);
$boardStmt->execute([$boardId]);
$board = $boardStmt->fetch(PDO::FETCH_ASSOC);

if (!$board) {
    http_response_code(404);
    exit('Board not found.');
}

$imgStmt = $db->prepare(
    'SELECT * FROM board_images WHERE board_id = ? ORDER BY sort_order ASC, id ASC'
);
$imgStmt->execute([$boardId]);
$allImages = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

$currentIndex = 0;
$currentImage = null;

foreach ($allImages as $i => $img) {
    if ((int)$img['id'] === $imgId || ($imgId === 0 && $i === 0)) {
        $currentIndex = $i;
        $currentImage = $img;
        break;
    }
}

if (!$currentImage && !empty($allImages)) {
    $currentIndex = 0;
    $currentImage = $allImages[0];
}

if (!$currentImage) {
    http_response_code(404);
    exit('Image not found.');
}

$prevImage = $allImages[$currentIndex - 1] ?? null;
$nextImage = $allImages[$currentIndex + 1] ?? null;

$tags = !empty($board['style_tags']) ? parseTags($board['style_tags']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($board['title']) ?> — Lightbox</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
  height: 100%;
  background: #0a0a0a;
  color: #eee;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  overflow: hidden;
}

#lightbox {
  display: flex;
  height: 100vh;
  width: 100vw;
  position: relative;
}

#lb-close {
  position: fixed;
  top: 1rem;
  right: 1rem;
  z-index: 100;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  background: rgba(255,255,255,.12);
  border: none;
  color: #fff;
  font-size: 1.4rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  transition: background .15s;
  line-height: 1;
}
#lb-close:hover { background: rgba(255,255,255,.25); }

#lb-image-area {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  min-width: 0;
}

#lb-image-area img {
  max-height: 90vh;
  max-width: 100%;
  object-fit: contain;
  display: block;
  user-select: none;
  border-radius: .5rem;
}

.lb-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 50%;
  background: rgba(255,255,255,.12);
  border: none;
  color: #fff;
  font-size: 1.25rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  transition: background .15s;
  z-index: 10;
}
.lb-nav:hover { background: rgba(255,255,255,.28); }
.lb-nav--prev { left: 1rem; }
.lb-nav--next { right: 1rem; }

#lb-panel {
  width: 280px;
  flex-shrink: 0;
  background: #141414;
  border-left: 1px solid rgba(255,255,255,.08);
  padding: 1.5rem 1.25rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.lb-panel__label {
  font-size: .7rem;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: rgba(255,255,255,.4);
  margin-bottom: .4rem;
}

.lb-author {
  display: flex;
  align-items: center;
  gap: .6rem;
  text-decoration: none;
  color: #fff;
}
.lb-author img,
.lb-author-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  background: rgba(255,255,255,.1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: .9rem;
  flex-shrink: 0;
}
.lb-author:hover { color: #c5c2f5; }

.lb-tag {
  display: inline-block;
  padding: .25rem .6rem;
  border-radius: 9999px;
  background: rgba(127,119,221,.25);
  color: #c5c2f5;
  font-size: .75rem;
  font-weight: 600;
  text-decoration: none;
}
.lb-tag:hover { background: rgba(127,119,221,.45); }

.lb-counter {
  font-size: .8rem;
  color: rgba(255,255,255,.4);
  text-align: center;
  margin-top: auto;
  padding-top: 1rem;
  border-top: 1px solid rgba(255,255,255,.08);
}

@media (max-width: 640px) {
  #lb-panel { display: none; }
}
</style>
</head>
<body>

<div id="lightbox">
  <a href="/board.php?id=<?= $boardId ?>" id="lb-close" title="Close">×</a>

  <div id="lb-image-area">
    <?php if ($prevImage): ?>
    <a href="/lightbox.php?board=<?= $boardId ?>&img=<?= (int)$prevImage['id'] ?>"
       class="lb-nav lb-nav--prev" title="Previous image">&#8249;</a>
    <?php endif; ?>

    <img src="<?= e(imageUrl($currentImage['image_url'] ?? "")) ?>"
         alt="Image <?= $currentIndex + 1 ?> of <?= count($allImages) ?> from <?= e($board['title']) ?>">

    <?php if ($nextImage): ?>
    <a href="/lightbox.php?board=<?= $boardId ?>&img=<?= (int)$nextImage['id'] ?>"
       class="lb-nav lb-nav--next" title="Next image">&#8250;</a>
    <?php endif; ?>
  </div>

  <aside id="lb-panel">
    <div>
      <div class="lb-panel__label">Board</div>
      <a href="/board.php?id=<?= $boardId ?>"
         style="font-size:1.05rem;font-weight:700;color:#fff;text-decoration:none;line-height:1.3;display:block;">
        <?= e($board['title']) ?>
      </a>
    </div>

    <div>
      <div class="lb-panel__label">Curator</div>
      <a href="/profile.php?u=<?= e($board['username']) ?>" class="lb-author">
        <?php if (!empty($board['avatar'])): ?>
          <img src="<?= e(imageUrl($board['avatar'] ?? "")) ?>" alt="<?= e($board['username']) ?>">
        <?php else: ?>
          <div class="lb-author-avatar"><?= strtoupper(mb_substr($board['username'], 0, 1)) ?></div>
        <?php endif; ?>
        <span style="font-weight:600;">@<?= e($board['username']) ?></span>
      </a>
    </div>

    <?php if (!empty($tags)): ?>
    <div>
      <div class="lb-panel__label">Style Tags</div>
      <div style="display:flex;flex-wrap:wrap;gap:.35rem;">
        <?php foreach ($tags as $tag): ?>
        <a href="/trends.php?tag=<?= urlencode($tag) ?>" class="lb-tag"><?= e($tag) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($board['description'])): ?>
    <div>
      <div class="lb-panel__label">About</div>
      <p style="font-size:.875rem;color:rgba(255,255,255,.65);line-height:1.6;margin:0;">
        <?= e($board['description']) ?>
      </p>
    </div>
    <?php endif; ?>

    <?php if (count($allImages) > 1): ?>
    <div class="lb-counter">
      Image <?= $currentIndex + 1 ?> of <?= count($allImages) ?>
    </div>
    <?php endif; ?>
  </aside>
</div>

<script>
document.addEventListener('keydown', function(e) {
  <?php if ($prevImage): ?>
  if (e.key === 'ArrowLeft') {
    window.location.href = '/lightbox.php?board=<?= $boardId ?>&img=<?= (int)$prevImage['id'] ?>';
  }
  <?php endif; ?>
  <?php if ($nextImage): ?>
  if (e.key === 'ArrowRight') {
    window.location.href = '/lightbox.php?board=<?= $boardId ?>&img=<?= (int)$nextImage['id'] ?>';
  }
  <?php endif; ?>
  if (e.key === 'Escape') {
    window.location.href = '/board.php?id=<?= $boardId ?>';
  }
});
</script>
</body>
</html>
