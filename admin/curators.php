<?php
// FILE: admin/curators.php
// Curator application review — pending applicants + verified curator management.
require_once __DIR__ . '/_guard.php';

if (!isset($_SESSION['curator_dismissed'])) {
    $_SESSION['curator_dismissed'] = [];
}

// ── POST handling ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);
    if ($userId > 0) {
        if ($action === 'grant') {
            $db->prepare('UPDATE users SET is_verified=1 WHERE id=?')->execute([$userId]);
        } elseif ($action === 'revoke') {
            $db->prepare('UPDATE users SET is_verified=0 WHERE id=?')->execute([$userId]);
        } elseif ($action === 'dismiss') {
            $_SESSION['curator_dismissed'][] = $userId;
        }
    }
    header('Location: /admin/curators.php');
    exit;
}

$currentPage = 'curators';

// ── Section A: Pending applicants ────────────────────────────────────────────
$dismissed = $_SESSION['curator_dismissed'] ?? [];

$pendingSQL = "SELECT u.*,
    (SELECT COUNT(*) FROM boards b WHERE b.user_id = u.id AND b.is_draft = 0) AS board_count
FROM users u
WHERE u.is_verified = 0 AND u.is_admin = 0
ORDER BY board_count DESC
LIMIT 50";
$pendingStmt = $db->query($pendingSQL);
$pendingRaw  = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

// Filter dismissed IDs in PHP
$pending = array_filter($pendingRaw, fn($u) => !in_array((int)$u['id'], $dismissed, true));

// ── Section B: Verified curators ─────────────────────────────────────────────
$verifiedSQL = "SELECT u.*,
    (SELECT COUNT(*) FROM boards b WHERE b.user_id = u.id AND b.is_draft = 0) AS board_count
FROM users u
WHERE u.is_verified = 1
ORDER BY u.id DESC
LIMIT 100";
$verifiedStmt = $db->query($verifiedSQL);
$verified     = $verifiedStmt->fetchAll(PDO::FETCH_ASSOC);

// getFollowerCount() is defined in includes/functions.php (loaded via _guard.php)
$pendingFollowers  = [];
foreach ($pending as $u) {
    $pendingFollowers[(int)$u['id']] = getFollowerCount($db, (int)$u['id']);
}

$verifiedFollowers = [];
foreach ($verified as $u) {
    $verifiedFollowers[(int)$u['id']] = getFollowerCount($db, (int)$u['id']);
}

// Sort verified by follower count DESC (PHP-side so we keep the query simple)
usort($verified, fn($a, $b) => $verifiedFollowers[(int)$b['id']] <=> $verifiedFollowers[(int)$a['id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Curators — Admin — Visual Design Journey</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/admin/admin.css?v=1.1">
    <style>
        /* ── Curator table ── */
        .curator-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 28px;
        }
        .curator-card__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 22px 14px;
            border-bottom: 1px solid var(--border);
            background: #fafbfc;
        }
        .curator-card__head h2 {
            font-size: 16px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .curator-card__count {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 600;
            background: #f1f5f9;
            border-radius: 999px;
            padding: 3px 10px;
        }

        /* ── Rows ── */
        .curator-row {
            display: grid;
            grid-template-columns: 48px minmax(0, 1.4fr) minmax(0, 1fr) 80px 80px minmax(0, 1.5fr) 160px;
            gap: 12px;
            align-items: center;
            padding: 14px 22px;
            border-bottom: 1px solid #f1f5f9;
        }
        .curator-row:last-child { border-bottom: 0; }
        .curator-row:hover { background: #fafbfc; }
        .curator-row--header {
            background: #f8fafc;
            padding: 9px 22px;
            border-bottom: 1px solid var(--border);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--text-muted);
        }
        .curator-row--header:hover { background: #f8fafc; }

        /* Avatar */
        .curator-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border);
            flex-shrink: 0;
        }
        .curator-avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 700;
            flex-shrink: 0;
        }

        /* User info cell */
        .curator-info { min-width: 0; }
        .curator-info__name {
            font-weight: 700;
            font-size: 14px;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .curator-info__email {
            font-size: 12px;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Bio snippet */
        .curator-bio {
            font-size: 12px;
            color: var(--text-muted);
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.4;
        }

        /* Stat pill */
        .curator-stat {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
        }
        .curator-stat__num {
            font-size: 18px;
            font-weight: 800;
            line-height: 1;
            color: var(--accent);
        }
        .curator-stat__label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-muted);
            font-weight: 700;
        }

        /* Low activity chip */
        .chip-warn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: var(--warning-light, #fef9c3);
            color: #92400e;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .04em;
            border-radius: 999px;
            padding: 3px 8px;
            white-space: nowrap;
        }

        /* Action buttons */
        .curator-actions {
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: flex-start;
        }
        .btn-grant {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #16a34a;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: background .15s;
        }
        .btn-grant:hover { background: #15803d; }
        .btn-dismiss {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #f1f5f9;
            color: var(--text-muted);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            transition: background .15s;
        }
        .btn-dismiss:hover { background: #e2e8f0; }
        .btn-revoke {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff;
            color: #dc2626;
            border: 1px solid #fca5a5;
            border-radius: 6px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: background .15s, border-color .15s;
        }
        .btn-revoke:hover { background: #fef2f2; border-color: #ef4444; }

        /* Profile link */
        .curator-profile-link {
            font-size: 12px;
            color: var(--accent);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-weight: 600;
        }
        .curator-profile-link:hover { text-decoration: underline; }

        /* Empty state */
        .curator-empty {
            padding: 40px 22px;
            text-align: center;
            color: var(--text-muted);
        }
        .curator-empty i {
            font-size: 32px;
            margin-bottom: 10px;
            display: block;
            opacity: .35;
        }

        /* Verified grid uses fewer columns */
        .curator-row--verified {
            grid-template-columns: 48px minmax(0, 1.4fr) minmax(0, 1fr) 80px 80px minmax(0, 1fr) 120px;
        }

        @media (max-width: 900px) {
            .curator-row,
            .curator-row--verified {
                grid-template-columns: 40px minmax(0, 1fr) auto;
            }
            .curator-row > *:nth-child(4),
            .curator-row > *:nth-child(5),
            .curator-row > *:nth-child(6),
            .curator-row--header { display: none; }
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/_sidebar.php'; ?>

<main class="admin-main">
    <div class="admin-topbar">
        <div class="admin-header">
            <h1><i class="fa-solid fa-id-badge" style="color:var(--accent);margin-right:8px;"></i>Curator Applications</h1>
            <p>Review pending curator applications and manage verified curator status.</p>
        </div>
        <button class="hamburger" id="hamburger" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <!-- ── Section A: Pending ─────────────────────────────────────────────── -->
    <section class="curator-card">
        <div class="curator-card__head">
            <h2>
                <i class="fa-solid fa-hourglass-half" style="color:#f59e0b;"></i>
                Pending Applications
            </h2>
            <span class="curator-card__count"><?= count($pending) ?> pending</span>
        </div>

        <?php if (empty($pending)): ?>
        <div class="curator-empty">
            <i class="fa-solid fa-inbox"></i>
            No pending applications — all caught up!
        </div>
        <?php else: ?>

        <!-- Header row -->
        <div class="curator-row curator-row--header">
            <span></span>
            <span>User</span>
            <span>Email</span>
            <span>Boards</span>
            <span>Followers</span>
            <span>Bio</span>
            <span>Actions</span>
        </div>

        <?php foreach ($pending as $u):
            $uid        = (int)$u['id'];
            $boardCount = (int)$u['board_count'];
            $followers  = $pendingFollowers[$uid] ?? 0;
            $bioSnippet = mb_strimwidth($u['bio'] ?? '', 0, 100, '…');
            $joinDate   = $u['created_at'] ? date('M j, Y', strtotime($u['created_at'])) : '—';
            $avatarSrc  = !empty($u['avatar']) ? imageUrl($u['avatar']) : null;
            $initial    = mb_strtoupper(mb_substr($u['username'], 0, 1));
        ?>
        <div class="curator-row">
            <!-- Avatar -->
            <div>
                <?php if ($avatarSrc): ?>
                <img src="<?= e($avatarSrc) ?>" alt="" class="curator-avatar">
                <?php else: ?>
                <div class="curator-avatar-placeholder"><?= e($initial) ?></div>
                <?php endif; ?>
            </div>

            <!-- User info -->
            <div class="curator-info">
                <div class="curator-info__name"><?= e($u['username']) ?></div>
                <div class="curator-info__email" style="font-size:11px;color:var(--text-muted);">
                    Joined <?= e($joinDate) ?>
                </div>
            </div>

            <!-- Email -->
            <div class="curator-info">
                <div class="curator-info__email"><?= e($u['email']) ?></div>
            </div>

            <!-- Board count -->
            <div class="curator-stat">
                <span class="curator-stat__num"><?= $boardCount ?></span>
                <span class="curator-stat__label">Boards</span>
                <?php if ($boardCount < 3): ?>
                <span class="chip-warn" style="margin-top:4px;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Low activity
                </span>
                <?php endif; ?>
            </div>

            <!-- Follower count -->
            <div class="curator-stat">
                <span class="curator-stat__num"><?= $followers ?></span>
                <span class="curator-stat__label">Followers</span>
            </div>

            <!-- Bio snippet -->
            <div class="curator-bio"><?= e($bioSnippet) ?></div>

            <!-- Actions -->
            <div class="curator-actions">
                <!-- Grant verified -->
                <form method="POST" action="/admin/curators.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action"  value="grant">
                    <input type="hidden" name="user_id" value="<?= $uid ?>">
                    <button type="submit" class="btn-grant">
                        <i class="fa-solid fa-badge-check"></i> Grant Verified
                    </button>
                </form>
                <!-- Dismiss -->
                <form method="POST" action="/admin/curators.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action"  value="dismiss">
                    <input type="hidden" name="user_id" value="<?= $uid ?>">
                    <button type="submit" class="btn-dismiss">
                        <i class="fa-solid fa-xmark"></i> Dismiss
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <!-- ── Section B: Verified curators ──────────────────────────────────── -->
    <section class="curator-card">
        <div class="curator-card__head">
            <h2>
                <i class="fa-solid fa-circle-check" style="color:#16a34a;"></i>
                Verified Curators
            </h2>
            <span class="curator-card__count"><?= count($verified) ?> verified</span>
        </div>

        <?php if (empty($verified)): ?>
        <div class="curator-empty">
            <i class="fa-solid fa-users"></i>
            No verified curators yet.
        </div>
        <?php else: ?>

        <!-- Header row -->
        <div class="curator-row curator-row--header curator-row--verified">
            <span></span>
            <span>User</span>
            <span>Email</span>
            <span>Boards</span>
            <span>Followers</span>
            <span>Profile</span>
            <span>Actions</span>
        </div>

        <?php foreach ($verified as $u):
            $uid        = (int)$u['id'];
            $boardCount = (int)$u['board_count'];
            $followers  = $verifiedFollowers[$uid] ?? 0;
            $joinDate   = $u['created_at'] ? date('M j, Y', strtotime($u['created_at'])) : '—';
            $avatarSrc  = !empty($u['avatar']) ? imageUrl($u['avatar']) : null;
            $initial    = mb_strtoupper(mb_substr($u['username'], 0, 1));
            $profileUrl = publicUrl('/profile.php?u=' . urlencode($u['username']));
        ?>
        <div class="curator-row curator-row--verified">
            <!-- Avatar -->
            <div>
                <?php if ($avatarSrc): ?>
                <img src="<?= e($avatarSrc) ?>" alt="" class="curator-avatar">
                <?php else: ?>
                <div class="curator-avatar-placeholder"><?= e($initial) ?></div>
                <?php endif; ?>
            </div>

            <!-- User info -->
            <div class="curator-info">
                <div class="curator-info__name"><?= e($u['username']) ?></div>
                <div class="curator-info__email" style="font-size:11px;color:var(--text-muted);">
                    Since <?= e($joinDate) ?>
                </div>
            </div>

            <!-- Email -->
            <div class="curator-info">
                <div class="curator-info__email"><?= e($u['email']) ?></div>
            </div>

            <!-- Board count -->
            <div class="curator-stat">
                <span class="curator-stat__num"><?= $boardCount ?></span>
                <span class="curator-stat__label">Boards</span>
            </div>

            <!-- Follower count -->
            <div class="curator-stat">
                <span class="curator-stat__num"><?= $followers ?></span>
                <span class="curator-stat__label">Followers</span>
            </div>

            <!-- Profile link -->
            <div>
                <a href="<?= e($profileUrl) ?>" target="_blank" rel="noopener noreferrer" class="curator-profile-link">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    View profile
                </a>
            </div>

            <!-- Revoke action -->
            <div class="curator-actions">
                <form method="POST" action="/admin/curators.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action"  value="revoke">
                    <input type="hidden" name="user_id" value="<?= $uid ?>">
                    <button type="submit" class="btn-revoke"
                        onclick="return confirm('Revoke verified status for <?= e(addslashes($u['username'])) ?>?')">
                        <i class="fa-solid fa-shield-xmark"></i> Revoke
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </section>

</main>
</body>
</html>
