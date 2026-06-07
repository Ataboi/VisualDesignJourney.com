<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();

function curatorTopTags(PDO $pdo, int $userId, int $limit = 3): array {
    try {
        $stmt = $pdo->prepare('SELECT style_tags FROM boards WHERE user_id = ? AND is_draft = 0 AND COALESCE(style_tags, "") <> ""');
        $stmt->execute([$userId]);
        $counts = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $raw) {
            foreach (parseTags((string)$raw) as $tag) {
                if ($tag !== '') $counts[$tag] = ($counts[$tag] ?? 0) + 1;
            }
        }
        arsort($counts);
        return array_slice(array_keys($counts), 0, $limit);
    } catch (Throwable $e) {
        return [];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    verifyCsrf();
    $action   = $_POST['action'] ?? '';
    $targetId = (int)($_POST['target_id'] ?? 0);
    $me       = currentUserId();
    if ($targetId > 0 && $me !== $targetId && in_array($action, ['follow', 'unfollow'])) {
        if ($action === 'follow') {
            $stmt = $pdo->prepare('INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)');
            $stmt->execute([$me, $targetId]);
        } else {
            $stmt = $pdo->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?');
            $stmt->execute([$me, $targetId]);
        }
    }
    header('Location: /curators.php');
    exit;
}

$stmt = $pdo->query(
    'SELECT u.*,
            COUNT(DISTINCT b.id)  AS board_count,
            COUNT(DISTINCT f.id)  AS follower_count
     FROM users u
     LEFT JOIN boards  b ON b.user_id = u.id AND b.is_draft = 0
     LEFT JOIN follows f ON f.following_id = u.id
     GROUP BY u.id
     ORDER BY follower_count DESC
     LIMIT 40'
);
$curators = $stmt->fetchAll(PDO::FETCH_ASSOC);

$me           = currentUserId();
$followingMap = [];
if ($me) {
    foreach ($curators as $c) {
        $followingMap[$c['id']] = isFollowing($pdo, $me, $c['id']);
    }
}

$focusMap = [];
foreach ($curators as $c) {
    $focusMap[(int)$c['id']] = curatorTopTags($pdo, (int)$c['id']);
}

// Featured curator: most-followed verified curator
$featuredCurator = null;
try {
    $fcStmt = $pdo->prepare(
        'SELECT u.*,
                (SELECT COUNT(*) FROM follows f WHERE f.following_id = u.id) AS follower_count,
                (SELECT COUNT(*) FROM boards b WHERE b.user_id = u.id AND b.is_draft = 0) AS board_count
         FROM users u
         WHERE u.is_verified = 1
         ORDER BY follower_count DESC LIMIT 1'
    );
    $fcStmt->execute();
    $featuredCurator = $fcStmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Exception $e) {}
$featuredFocus = $featuredCurator ? curatorTopTags($pdo, (int)$featuredCurator['id'], 4) : [];

$pageTitle    = __('curators.page_title', 'Curators — Visual Design Journey');
$metaDesc     = __('curators.meta_desc', 'Discover the creative minds curating mood boards on Visual Design Journey. Follow designers and explore their visual collections.');
$canonicalUrl = publicUrl('/curators');
$activeNav    = 'curators';
$headExtra = '<script type="application/ld+json">' . json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => strip_tags($pageTitle),
    'description' => $metaDesc,
    'url' => $canonicalUrl,
    'isPartOf' => [
        '@type' => 'WebSite',
        'name' => 'Visual Design Journey',
        'url' => publicUrl('/'),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-wrap" id="main-content">
<div class="container">

  <div style="text-align:center;margin-bottom:2.5rem;">
    <h1 class="t-headline-md" style="margin:0 0 .5rem;"><?= e(__('curators.hero', 'Curators')) ?></h1>
    <p class="t-body-md text-muted"><?= e(__('curators.sub', 'Discover the creative minds behind the collections.')) ?></p>
  </div>

  <?php if ($featuredCurator): ?>
  <div class="featured-curator-card" style="background:linear-gradient(135deg,var(--accent-soft) 0%,var(--surface) 100%);border:1px solid var(--border);border-radius:var(--r-lg);padding:2rem 1.75rem;margin-bottom:2.5rem;">
    <span style="display:inline-block;background:var(--accent);color:#fff;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;padding:3px 10px;border-radius:var(--r-pill);margin-bottom:1rem;"><?= e(__('curators.featured', 'Featured Curator')) ?></span>
    <div style="display:flex;gap:24px;align-items:center;flex-wrap:wrap;">
      <a href="/profile.php?u=<?= urlencode($featuredCurator['username']) ?>" style="flex-shrink:0;text-decoration:none;" aria-label="View <?= e($featuredCurator['username']) ?>'s profile">
        <?php if (!empty($featuredCurator['avatar'])): ?>
          <img src="<?= e(imageUrl($featuredCurator['avatar'])) ?>"
               alt="<?= e($featuredCurator['username']) ?>'s profile picture"
               decoding="async"
               style="width:96px;height:96px;border-radius:50%;object-fit:cover;display:block;border:3px solid var(--accent);">
        <?php else: ?>
          <span style="width:96px;height:96px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:700;color:#fff;flex-shrink:0;"
                role="img" aria-label="<?= e($featuredCurator['username']) ?>'s avatar">
            <?= strtoupper(mb_substr($featuredCurator['username'], 0, 1)) ?>
          </span>
        <?php endif; ?>
      </a>
      <div style="flex:1;min-width:200px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
          <a href="/profile.php?u=<?= urlencode($featuredCurator['username']) ?>"
             style="font-weight:700;font-size:1.15rem;text-decoration:none;color:inherit;">
            @<?= e($featuredCurator['username']) ?>
          </a>
          <?php if (!empty($featuredCurator['is_verified'])): ?>
            <span class="badge-verified"><i class="fa-solid fa-circle-check"></i> Verified</span>
          <?php endif; ?>
        </div>
        <?php $featuredBio = localizedField($featuredCurator, 'bio'); ?>
        <?php if ($featuredBio !== ''): ?>
        <p class="t-body-md text-muted" style="margin:0 0 .75rem;line-height:1.6;"><?= e($featuredBio) ?></p>
        <?php endif; ?>
        <?php if ($featuredFocus): ?>
        <div class="curator-focus-chips" aria-label="Featured curator focus">
          <?php foreach ($featuredFocus as $tag): ?><a href="<?= e(vdj_localized_url('/')) ?>?tag=<?= urlencode($tag) ?>"><?= e($tag) ?></a><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <dl style="display:flex;gap:1.5rem;margin-bottom:.75rem;">
          <div>
            <dt class="t-caption text-muted"><?= e(__('curators.boards', 'Boards')) ?></dt>
            <dd style="font-weight:700;font-size:1rem;margin:0;"><?= formatCount((int)$featuredCurator['board_count']) ?></dd>
          </div>
          <div style="width:1px;background:var(--border);" role="separator" aria-hidden="true"></div>
          <div>
            <dt class="t-caption text-muted"><?= e(__('curators.followers', 'Followers')) ?></dt>
            <dd style="font-weight:700;font-size:1rem;margin:0;"><?= formatCount((int)$featuredCurator['follower_count']) ?></dd>
          </div>
        </dl>
        <a href="/profile.php?u=<?= urlencode($featuredCurator['username']) ?>" class="btn btn--primary btn--sm" style="border-radius:var(--r-pill);"><?= e(__('curators.view_profile', 'View Profile')) ?></a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (empty($curators)): ?>
  <div style="text-align:center;padding:4rem 1rem;color:var(--muted);">
    <p class="t-body-md"><?= e(__('curators.empty', 'No curators yet. Be the first!')) ?></p>
  </div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.25rem;"
       role="list" aria-label="Curators">
    <?php foreach ($curators as $c): ?>
    <?php $bio = localizedField($c, 'bio'); $focus = $focusMap[(int)$c['id']] ?? []; ?>
    <article class="curator-card" role="listitem"
             aria-label="<?= e($c['username']) ?> — <?= formatCount((int)$c['board_count']) ?> boards, <?= formatCount((int)$c['follower_count']) ?> followers"
             style="background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);padding:1.75rem 1.25rem;display:flex;flex-direction:column;align-items:center;text-align:center;gap:.6rem;transition:border-color .18s;">

      <a href="/profile.php?u=<?= urlencode($c['username']) ?>"
         aria-label="View <?= e($c['username']) ?>'s profile"
         style="text-decoration:none;">
        <?php if (!empty($c['avatar'])): ?>
          <img src="<?= e(imageUrl($c['avatar'])) ?>"
               alt="<?= e($c['username']) ?>'s profile picture"
               class="avatar"
               loading="lazy"
               decoding="async"
               style="width:80px;height:80px;border-radius:50%;object-fit:cover;display:block;">
        <?php else: ?>
          <span style="width:80px;height:80px;border-radius:50%;background:var(--surface);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;color:var(--muted);"
                role="img"
                aria-label="<?= e($c['username']) ?>'s avatar">
            <?= strtoupper(mb_substr($c['username'], 0, 1)) ?>
          </span>
        <?php endif; ?>
      </a>

      <a href="/profile.php?u=<?= urlencode($c['username']) ?>"
         aria-label="View <?= e($c['username']) ?>'s profile"
         style="font-weight:700;font-size:1rem;text-decoration:none;color:inherit;">
        @<?= e($c['username']) ?>
      </a>

      <?php if (!empty($c['is_verified'])): ?>
        <span class="badge-verified"><i class="fa-solid fa-circle-check"></i> Verified</span>
      <?php endif; ?>

      <?php if ($bio !== ''): ?>
      <p class="t-caption text-muted" style="margin:0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.5;">
        <?= e($bio) ?>
      </p>
      <?php endif; ?>
      <?php if ($focus): ?>
      <div class="curator-focus-chips" aria-label="Top styles for <?= e($c['username']) ?>">
        <?php foreach ($focus as $tag): ?><a href="<?= e(vdj_localized_url('/')) ?>?tag=<?= urlencode($tag) ?>"><?= e($tag) ?></a><?php endforeach; ?>
      </div>
      <?php endif; ?>

      <dl style="display:flex;gap:1.25rem;margin-top:.25rem;" aria-label="Stats for <?= e($c['username']) ?>">
        <div>
          <dt class="t-caption text-muted"><?= e(__('curators.boards', 'Boards')) ?></dt>
          <dd style="font-weight:700;font-size:.95rem;margin:0;"><?= formatCount((int)$c['board_count']) ?></dd>
        </div>
        <div style="width:1px;background:var(--border);" role="separator" aria-hidden="true"></div>
        <div>
          <dt class="t-caption text-muted"><?= e(__('curators.followers', 'Followers')) ?></dt>
          <dd style="font-weight:700;font-size:.95rem;margin:0;"><?= formatCount((int)$c['follower_count']) ?></dd>
        </div>
      </dl>

      <?php if ($me && $me !== (int)$c['id']): ?>
      <?php $alreadyFollowing = $followingMap[$c['id']] ?? false; ?>
      <form method="post" action="/curators.php" style="margin-top:.25rem;">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="target_id" value="<?= (int)$c['id'] ?>">
        <input type="hidden" name="action" value="<?= $alreadyFollowing ? 'unfollow' : 'follow' ?>">
        <button type="submit"
                class="btn btn--sm <?= $alreadyFollowing ? 'btn--secondary' : 'btn--primary' ?>"
                aria-label="<?= $alreadyFollowing ? e(__('curators.unfollow', 'Unfollow')) : e(__('curators.follow', 'Follow')) ?> <?= e($c['username']) ?>"
                style="border-radius:var(--r-pill);min-width:90px;">
          <?= $alreadyFollowing ? e(__('curators.unfollow', 'Unfollow')) : e(__('curators.follow', 'Follow')) ?>
        </button>
      </form>
      <?php elseif (!$me): ?>
      <a href="<?= e(vdj_localized_url('/login')) ?>?next=<?= urlencode(vdj_localized_url('/curators')) ?>"
         class="btn btn--sm btn--primary"
         aria-label="Sign in to follow <?= e($c['username']) ?>"
         style="border-radius:var(--r-pill);min-width:90px;margin-top:.25rem;">
        <?= e(__('curators.follow', 'Follow')) ?>
      </a>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>
</main>

<style>
.curator-card:hover { border-color: var(--accent); }
.curator-focus-chips { display:flex;gap:6px;flex-wrap:wrap;justify-content:center;margin:.1rem 0 .25rem; }
.curator-focus-chips a { display:inline-flex;align-items:center;border:1px solid var(--border);border-radius:var(--r-pill);padding:3px 8px;color:var(--muted);font-size:11px;font-weight:700;text-decoration:none;background:var(--surface); }
.curator-focus-chips a:hover { border-color:var(--accent);color:var(--accent); }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
