<?php
/**
 * settings.php — Account settings
 * Sections: profile, password, danger (delete account)
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();
session_start_if_not_started();

$db        = getDB();
$currentId = currentUserId();
$user      = getUserById($db, $currentId);

if (!$user) {
    // Session refers to deleted user — log out and redirect
    session_destroy();
    header('Location: /login.php');
    exit;
}

$section  = $_GET['section'] ?? 'profile';
$validSections = ['profile', 'password', 'danger'];
if (!in_array($section, $validSections, true)) {
    $section = 'profile';
}

$errors  = [];
$success = '';

/* ── Flash messages via session ────────────────────────────────────────────── */
if (!empty($_SESSION['settings_success'])) {
    $success = $_SESSION['settings_success'];
    unset($_SESSION['settings_success']);
}
if (!empty($_SESSION['settings_errors'])) {
    $errors = $_SESSION['settings_errors'];
    unset($_SESSION['settings_errors']);
}

/* ── POST HANDLERS ──────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postSection = $_POST['section'] ?? '';

    /* ── Profile update ──────────────────────────────────────────────────────── */
    if ($postSection === 'profile') {
        $newUsername = trim($_POST['username'] ?? '');
        $bio         = trim($_POST['bio'] ?? '');
        $website     = trim($_POST['website'] ?? '');
        $location    = trim($_POST['location'] ?? '');

        // Validate username
        if ($newUsername === '') {
            $errors[] = 'Username is required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $newUsername)) {
            $errors[] = 'Username must be 3–30 characters (letters, numbers, _ . - only).';
        } elseif ($newUsername !== $user['username']) {
            // Check uniqueness
            $existing = getUserByUsername($db, $newUsername);
            if ($existing && (int)$existing['id'] !== $currentId) {
                $errors[] = 'That username is already taken.';
            }
        }

        if (mb_strlen($bio) > 300) {
            $errors[] = 'Bio must be 300 characters or fewer.';
        }

        // Validate website URL if provided
        if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
            // Try prepending https://
            if (filter_var('https://' . $website, FILTER_VALIDATE_URL)) {
                $website = 'https://' . $website;
            } else {
                $errors[] = 'Please enter a valid website URL.';
            }
        }

        // Handle avatar upload
        $avatarPath = $user['avatar'];
        if (!empty($_FILES['avatar']['name'])) {
            try {
                $newPath    = handleImageUpload($_FILES['avatar'], 'avatars/');
                // Remove old avatar file
                if ($avatarPath && file_exists(UPLOAD_DIR . $avatarPath)) {
                    unlink(UPLOAD_DIR . $avatarPath);
                }
                $avatarPath = $newPath;
            } catch (RuntimeException $ex) {
                $errors[] = 'Avatar: ' . $ex->getMessage();
            }
        }

        if (empty($errors)) {
            $stmt = $db->prepare(
                'UPDATE users SET username=?, bio=?, website=?, location=?, avatar=? WHERE id=?'
            );
            $stmt->execute([$newUsername, $bio, $website, $location, $avatarPath, $currentId]);
            // Refresh user data in session if needed
            $_SESSION['settings_success'] = 'Profile updated successfully.';
            header('Location: /settings.php?section=profile');
            exit;
        }
    }

    /* ── Password change ─────────────────────────────────────────────────────── */
    elseif ($postSection === 'password') {
        $currentPw  = $_POST['current_password']  ?? '';
        $newPw      = $_POST['new_password']       ?? '';
        $confirmPw  = $_POST['confirm_new']        ?? '';

        if (!password_verify($currentPw, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }
        if (strlen($newPw) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }
        if ($newPw !== $confirmPw) {
            $errors[] = 'New passwords do not match.';
        }

        if (empty($errors)) {
            $hash = password_hash($newPw, PASSWORD_DEFAULT);
            $db->prepare('UPDATE users SET password_hash=? WHERE id=?')
               ->execute([$hash, $currentId]);
            $_SESSION['settings_success'] = 'Password changed successfully.';
            header('Location: /settings.php?section=password');
            exit;
        }
    }

    /* ── Delete account ──────────────────────────────────────────────────────── */
    elseif ($postSection === 'delete_account') {
        $confirmPw = $_POST['confirm_password'] ?? '';

        if (!password_verify($confirmPw, $user['password_hash'])) {
            $errors[] = 'Password is incorrect. Account not deleted.';
        }

        if (empty($errors)) {
            // Remove all uploaded image files for this user's boards
            $imgStmt = $db->prepare(
                'SELECT bi.image_url FROM board_images bi
                 JOIN boards b ON b.id = bi.board_id
                 WHERE b.user_id = ?'
            );
            $imgStmt->execute([$currentId]);
            foreach ($imgStmt->fetchAll() as $img) {
                $fp = UPLOAD_DIR . $img['image_url'];
                if (file_exists($fp)) unlink($fp);
            }
            // Remove avatar file
            if (!empty($user['avatar']) && file_exists(UPLOAD_DIR . $user['avatar'])) {
                unlink(UPLOAD_DIR . $user['avatar']);
            }
            // CASCADE in schema removes boards, board_images, board_likes, follows, notifications
            $db->prepare('DELETE FROM users WHERE id=?')->execute([$currentId]);

            session_destroy();
            header('Location: /login.php?deleted=1');
            exit;
        }
    }

    // If errors, flash and redirect back to same section
    if (!empty($errors)) {
        $_SESSION['settings_errors'] = $errors;
        header('Location: /settings.php?section=' . $postSection);
        exit;
    }
}

// Re-fetch user (may have been updated by a previous redirect)
$user = getUserById($db, $currentId);

$pageTitle = __('settings.title', 'Settings') . ' — Visual Design Journey';
$activeNav = '';

require_once __DIR__ . '/includes/header.php';
?>

<main class="page-wrap" id="main-content">
<div class="container" style="padding-top:40px;padding-bottom:72px;">

  <div style="display:grid;grid-template-columns:240px 1fr;gap:48px;align-items:start;">

    <!-- ── Sidebar ────────────────────────────────────────────────────────────── -->
    <nav aria-label="Settings navigation"
         style="position:sticky;top:88px;background:var(--surface);border:1px solid var(--border);
                border-radius:var(--r-lg);padding:12px 0;overflow:hidden;">
      <p class="t-label text-muted" style="padding:10px 20px 6px;text-transform:uppercase;letter-spacing:.06em;font-size:11px;"><?= e(__('settings.title', 'Account Settings')) ?></p>
      <?php
      $sidebarLinks = [
          'profile'  => ['label' => __('settings.tab_profile','Profile'),    'icon' => 'person'],
          'password' => ['label' => __('settings.tab_password','Password'),   'icon' => 'lock'],
          'danger'   => ['label' => __('settings.tab_danger','Danger Zone'),  'icon' => 'warning'],
      ];
      foreach ($sidebarLinks as $slug => $meta):
        $isActive = ($section === $slug);
      ?>
        <a href="/settings.php?section=<?= $slug ?>"
           style="display:flex;align-items:center;gap:10px;padding:10px 20px;
                  text-decoration:none;font-size:14px;font-weight:<?= $isActive ? '600' : '500' ?>;
                  color:<?= $isActive ? 'var(--accent)' : 'var(--muted)' ?>;
                  background:<?= $isActive ? '#f0eefb' : 'transparent' ?>;
                  border-left:3px solid <?= $isActive ? 'var(--accent)' : 'transparent' ?>;
                  transition:background .15s,color .15s;"
           aria-current="<?= $isActive ? 'page' : 'false' ?>">
          <span class="material-symbols-outlined" style="font-size:18px;"><?= $meta['icon'] ?></span>
          <?= $meta['label'] ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <!-- ── Main content ───────────────────────────────────────────────────────── -->
    <div style="max-width:560px;">

      <!-- Flash messages -->
      <?php if ($success): ?>
        <div style="background:#eef9f1;border:1px solid #b7e5c8;border-radius:var(--r-lg);padding:12px 16px;margin-bottom:24px;color:#1a6636;display:flex;align-items:center;gap:8px;">
          <span class="material-symbols-outlined" style="font-size:18px;">check_circle</span>
          <span class="t-body-md"><?= e($success) ?></span>
        </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:var(--r-lg);padding:12px 16px;margin-bottom:24px;">
          <?php foreach ($errors as $err): ?>
            <p class="t-body-md" style="color:#b91c1c;margin:0 0 4px;"><?= e($err) ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- ================================================================== -->
      <!-- PROFILE SECTION -->
      <!-- ================================================================== -->
      <?php if ($section === 'profile'): ?>
        <h1 class="t-headline-md" style="margin-bottom:28px;"><?= e(__('settings.tab_profile', 'Profile')) ?></h1>

        <form method="POST" action="/settings.php?section=profile" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"/>
          <input type="hidden" name="section" value="profile"/>

          <!-- Avatar upload widget -->
          <div class="form-group" style="margin-bottom:28px;">
            <label class="form-label" style="display:block;margin-bottom:10px;"><?= e(__('settings.avatar_label', 'Profile Photo')) ?></label>
            <div style="display:flex;align-items:center;gap:16px;">
              <label for="avatar-file-input" style="cursor:pointer;position:relative;display:block;flex-shrink:0;">
                <div style="width:80px;height:80px;border-radius:50%;overflow:hidden;background:var(--surface);
                            border:2px solid var(--border);position:relative;display:flex;align-items:center;justify-content:center;">
                  <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= e(imageUrl($user['avatar'] ?? "")) ?>"
                         alt="Current avatar"
                         id="avatar-preview"
                         style="width:100%;height:100%;object-fit:cover;display:block;"/>
                  <?php else: ?>
                    <span id="avatar-initials"
                          style="font-size:28px;font-weight:700;color:var(--accent);">
                      <?= strtoupper(substr($user['username'], 0, 1)) ?>
                    </span>
                    <img id="avatar-preview" src="" alt="Avatar preview"
                         style="width:100%;height:100%;object-fit:cover;display:none;position:absolute;top:0;left:0;"/>
                  <?php endif; ?>
                  <!-- Camera overlay -->
                  <div style="position:absolute;inset:0;border-radius:50%;background:rgba(0,0,0,.45);
                              display:flex;align-items:center;justify-content:center;
                              opacity:0;transition:opacity .15s;"
                       class="avatar-overlay">
                    <span class="material-symbols-outlined" style="font-size:22px;color:#fff;">photo_camera</span>
                  </div>
                </div>
                <input type="file"
                       id="avatar-file-input"
                       name="avatar"
                       accept="image/jpeg,image/png,image/webp"
                       style="display:none;"
                       onchange="previewAvatar(this)"/>
              </label>
              <div>
                <p class="t-body-md" style="margin:0 0 4px;font-weight:600;">Upload a photo</p>
                <p class="t-caption text-muted" style="margin:0;">JPG, PNG or WebP &middot; max 5 MB</p>
              </div>
            </div>
          </div>

          <!-- Username -->
          <div class="form-group">
            <label class="form-label" for="s-username"><?= e(__('settings.username', 'Username')) ?></label>
            <input type="text"
                   id="s-username"
                   name="username"
                   class="form-input"
                   value="<?= e($user['username']) ?>"
                   pattern="[a-zA-Z0-9_.\-]{3,30}"
                   maxlength="30"
                   required/>
            <p class="form-hint">3–30 characters. Letters, numbers, _ . - allowed.</p>
          </div>

          <!-- Bio -->
          <div class="form-group">
            <label class="form-label" for="s-bio"><?= e(__('settings.bio', 'Bio')) ?></label>
            <textarea id="s-bio"
                      name="bio"
                      class="form-textarea"
                      rows="4"
                      maxlength="300"
                      oninput="updateBioCount(this)"
                      placeholder="Tell the community about yourself..."><?= e($user['bio'] ?? '') ?></textarea>
            <p class="form-hint" style="display:flex;justify-content:space-between;">
              <span>Short bio shown on your profile.</span>
              <span id="bio-counter" style="color:var(--muted);">
                <?= mb_strlen($user['bio'] ?? '') ?>/300
              </span>
            </p>
          </div>

          <!-- Website -->
          <div class="form-group">
            <label class="form-label" for="s-website"><?= e(__('settings.website', 'Website')) ?></label>
            <div style="position:relative;">
              <span class="material-symbols-outlined"
                    style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:18px;pointer-events:none;">link</span>
              <input type="url"
                     id="s-website"
                     name="website"
                     class="form-input"
                     style="padding-left:40px;"
                     value="<?= e($user['website'] ?? '') ?>"
                     placeholder="https://yoursite.com"/>
            </div>
          </div>

          <!-- Location -->
          <div class="form-group">
            <label class="form-label" for="s-location"><?= e(__('settings.location', 'Location')) ?></label>
            <div style="position:relative;">
              <span class="material-symbols-outlined"
                    style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:18px;pointer-events:none;">location_on</span>
              <input type="text"
                     id="s-location"
                     name="location"
                     class="form-input"
                     style="padding-left:40px;"
                     value="<?= e($user['location'] ?? '') ?>"
                     maxlength="80"
                     placeholder="City, Country"/>
            </div>
          </div>

          <!-- Actions -->
          <div style="display:flex;gap:12px;margin-top:28px;">
            <a href="/profile.php?u=<?= urlencode($user['username']) ?>"
               class="btn btn--secondary"><?= e(__('settings.cancel', 'Cancel')) ?></a>
            <button type="submit" class="btn btn--primary">
              <span class="material-symbols-outlined" style="font-size:16px;">save</span>
              <?= e(__('settings.save_changes', 'Save Changes')) ?>
            </button>
          </div>
        </form>

      <!-- ================================================================== -->
      <!-- PASSWORD SECTION -->
      <!-- ================================================================== -->
      <?php elseif ($section === 'password'): ?>
        <h1 class="t-headline-md" style="margin-bottom:28px;"><?= e(__('settings.change_password', 'Change Password')) ?></h1>

        <form method="POST" action="/settings.php?section=password">
          <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"/>
          <input type="hidden" name="section" value="password"/>

          <!-- Current password -->
          <div class="form-group">
            <label class="form-label" for="s-current-pw"><?= e(__('settings.current_password', 'Current Password')) ?></label>
            <input type="password"
                   id="s-current-pw"
                   name="current_password"
                   class="form-input"
                   required
                   autocomplete="current-password"/>
          </div>

          <!-- New password -->
          <div class="form-group">
            <label class="form-label" for="s-new-pw"><?= e(__('settings.new_password', 'New Password')) ?></label>
            <input type="password"
                   id="s-new-pw"
                   name="new_password"
                   class="form-input"
                   required
                   minlength="8"
                   autocomplete="new-password"
                   oninput="checkPwStrength(this)"/>
            <p class="form-hint"><?= e(__('settings.min_8', 'Minimum 8 characters.')) ?></p>
            <!-- Strength bar -->
            <div style="margin-top:6px;height:4px;background:var(--border);border-radius:4px;overflow:hidden;">
              <div id="pw-strength-bar" style="height:100%;width:0%;background:var(--border);border-radius:4px;transition:width .3s,background .3s;"></div>
            </div>
            <p id="pw-strength-label" class="t-caption text-muted" style="margin-top:4px;"></p>
          </div>

          <!-- Confirm new password -->
          <div class="form-group">
            <label class="form-label" for="s-confirm-pw"><?= e(__('settings.confirm_password', 'Confirm New Password')) ?></label>
            <input type="password"
                   id="s-confirm-pw"
                   name="confirm_new"
                   class="form-input"
                   required
                   minlength="8"
                   autocomplete="new-password"/>
          </div>

          <!-- Actions -->
          <div style="display:flex;gap:12px;margin-top:28px;">
            <a href="/settings.php?section=profile" class="btn btn--secondary"><?= e(__('settings.cancel', 'Cancel')) ?></a>
            <button type="submit" class="btn btn--primary">
              <span class="material-symbols-outlined" style="font-size:16px;">lock_reset</span>
              <?= e(__('settings.save_changes', 'Update Password')) ?>
            </button>
          </div>
        </form>

      <!-- ================================================================== -->
      <!-- DANGER ZONE SECTION -->
      <!-- ================================================================== -->
      <?php elseif ($section === 'danger'): ?>
        <h1 class="t-headline-md" style="margin-bottom:8px;"><?= e(__('settings.danger_title', 'Danger Zone')) ?></h1>
        <p class="t-body-lg text-muted" style="margin-bottom:28px;">
          <?= e(__('settings.danger_desc', 'Irreversible and destructive actions. Please proceed with caution.')) ?>
        </p>

        <div style="border:1px solid #fca5a5;border-radius:var(--r-lg);padding:24px;background:#fef2f2;">
          <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:20px;">
            <span class="material-symbols-outlined" style="font-size:24px;color:#b91c1c;flex-shrink:0;margin-top:2px;">warning</span>
            <div>
              <p class="t-body-md" style="font-weight:700;margin:0 0 4px;color:#b91c1c;"><?= e(__('settings.delete_account', 'Delete Account')) ?></p>
              <p class="t-body-md text-muted" style="margin:0;">
                <?= e(__('settings.delete_desc', 'This will permanently delete your account, all boards, images, likes, and follower data. This action cannot be undone.')) ?>
              </p>
            </div>
          </div>

          <form method="POST" action="/settings.php?section=danger"
                id="delete-account-form"
                onsubmit="return confirmDelete()">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"/>
            <input type="hidden" name="section" value="delete_account"/>

            <div class="form-group">
              <label class="form-label" for="s-delete-pw"
                     style="color:#b91c1c;">Confirm your password to continue</label>
              <input type="password"
                     id="s-delete-pw"
                     name="confirm_password"
                     class="form-input"
                     required
                     autocomplete="current-password"
                     placeholder="Enter your current password"/>
            </div>

            <button type="submit"
                    class="btn btn--danger"
                    style="margin-top:8px;">
              <span class="material-symbols-outlined" style="font-size:16px;">delete_forever</span>
              <?= e(__('settings.delete_account', 'Permanently Delete My Account')) ?>
            </button>
          </form>
        </div>

      <?php endif; ?>

    </div><!-- /content -->
  </div><!-- /grid -->
</div><!-- /container -->
</main>

<script>
/* ── Avatar preview ─────────────────────────────────────────────────── */
function previewAvatar(input) {
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = function(e) {
    const preview  = document.getElementById('avatar-preview');
    const initials = document.getElementById('avatar-initials');
    if (preview) {
      preview.src          = e.target.result;
      preview.style.display = 'block';
    }
    if (initials) initials.style.display = 'none';
  };
  reader.readAsDataURL(input.files[0]);
}

/* Show camera overlay on hover */
const avatarLabel = document.querySelector('label[for="avatar-file-input"]');
const overlay     = avatarLabel && avatarLabel.querySelector('.avatar-overlay');
if (avatarLabel && overlay) {
  avatarLabel.addEventListener('mouseenter', () => overlay.style.opacity = '1');
  avatarLabel.addEventListener('mouseleave', () => overlay.style.opacity = '0');
}

/* ── Bio character counter ──────────────────────────────────────────── */
function updateBioCount(textarea) {
  const counter = document.getElementById('bio-counter');
  if (!counter) return;
  const len = textarea.value.length;
  counter.textContent = len + '/300';
  counter.style.color = len > 270 ? '#b91c1c' : 'var(--muted)';
}

/* ── Password strength indicator ────────────────────────────────────── */
function checkPwStrength(input) {
  const val  = input.value;
  const bar  = document.getElementById('pw-strength-bar');
  const lbl  = document.getElementById('pw-strength-label');
  if (!bar || !lbl) return;

  let score = 0;
  if (val.length >= 8)                    score++;
  if (val.length >= 12)                   score++;
  if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
  if (/[0-9]/.test(val))                  score++;
  if (/[^A-Za-z0-9]/.test(val))          score++;

  const levels = [
    { label: '',                                               color: 'var(--border)', pct: '0%' },
    { label: '<?= e(__('settings.pw_weak','Weak')) ?>',        color: '#ef4444',        pct: '25%' },
    { label: '<?= e(__('settings.pw_fair','Fair')) ?>',        color: '#f97316',        pct: '50%' },
    { label: '<?= e(__('settings.pw_good','Good')) ?>',        color: '#eab308',        pct: '75%' },
    { label: '<?= e(__('settings.pw_strong','Strong')) ?>',    color: '#22c55e',        pct: '100%' },
  ];
  const lvl       = levels[Math.min(score, 4)];
  bar.style.width      = val.length === 0 ? '0%' : lvl.pct;
  bar.style.background = lvl.color;
  lbl.textContent      = val.length === 0 ? '' : lvl.label;
}

/* ── Delete account confirmation ────────────────────────────────────── */
function confirmDelete() {
  return confirm(
    'Are you absolutely sure?\n\nThis will permanently delete your account and all your data. There is no going back.'
  );
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
