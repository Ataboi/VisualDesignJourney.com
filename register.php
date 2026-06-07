<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/i18n.php';

session_start_if_not_started();
$currentLang = vdj_bootstrap_language();

if (!empty($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

$errors   = [];
$fields   = ['username' => '', 'email' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verifyCsrf();

    $username        = trim($_POST['username'] ?? '');
    $email           = normalizeEmail($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $honeypot        = trim($_POST['company_website'] ?? '');

    $fields['username'] = $username;
    $fields['email']    = $email;

    if ($honeypot !== '' || !verifySignupTiming()) {
        $errors['form'] = __('register.error_verify', 'We could not verify this signup. Please refresh and try again.');
    }

    if (isBotUserAgent()) {
        $errors['form'] = __('register.error_verify', 'We could not verify this signup. Please use a normal browser and try again.');
    }

    if (!rateLimitCheck('register-ip', 3, 15 * 60) || !rateLimitCheck('register-ip-day', 12, 24 * 60 * 60)) {
        $errors['form'] = __('register.error_attempts', 'Too many signup attempts. Please wait a few minutes and try again.');
    }

    if ($email !== '' && !rateLimitCheckKey('register-email', $email, 2, 24 * 60 * 60)) {
        $errors['email'] = __('register.error_attempts', 'Too many signup attempts for this email address. Please try again later.');
    }

    if ($username === '') {
        $errors['username'] = __('register.error_username_required', 'Username is required.');
    } elseif (!preg_match('/^\w+$/', $username)) {
        $errors['username'] = __('register.error_username_chars', 'Username may only contain letters, numbers and underscores.');
    } elseif (strlen($username) < 3 || strlen($username) > 30) {
        $errors['username'] = __('register.error_username_readable', 'Username must be between 3 and 30 characters.');
    } elseif (!preg_match('/[a-zA-Z]/', $username) || preg_match('/(.)\1{4,}/', $username)) {
        $errors['username'] = __('register.error_username_readable', 'Please choose a more readable username.');
    } elseif (in_array(strtolower($username), ['admin', 'administrator', 'moderator', 'support', 'root', 'system'], true)) {
        $errors['username'] = __('register.error_username_reserved', 'This username is reserved.');
    }

    if ($email === '') {
        $errors['email'] = __('register.error_email_required', 'Email address is required.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = __('register.error_email_invalid', 'Please enter a valid email address.');
    } elseif (!isAllowedEmail($email)) {
        $errors['email'] = __('register.error_email_perm', 'Please use a permanent email address.');
    }

    if (!isset($errors['username']) && !isset($errors['email'])) {
        $spamReason = signupSpamReason($username, $email);
        if ($spamReason !== null) {
            $errors['form'] = $spamReason;
        }
    }

    if ($password === '') {
        $errors['password'] = __('register.error_password_required', 'Password is required.');
    } elseif (strlen($password) < 8) {
        $errors['password'] = __('register.error_password_length', 'Password must be at least 8 characters.');
    }

    if ($confirmPassword === '') {
        $errors['confirm_password'] = __('register.error_confirm_required', 'Please confirm your password.');
    } elseif ($password !== $confirmPassword) {
        $errors['confirm_password'] = __('register.error_password_match', 'Passwords do not match.');
    }

    if (empty($errors)) {
        $db = getDB();

        if (getUserByEmail($db, $email)) {
            $errors['email'] = __('register.error_email_taken', 'An account with this email already exists.');
        }
        if (getUserByUsername($db, $username)) {
            $errors['username'] = __('register.error_username_taken', 'This username is already taken.');
        }
    }

    if (empty($errors)) {
        $db   = getDB();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare(
            'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)'
        );
        $stmt->execute([$username, $email, $hash]);
        $userId = (int)$db->lastInsertId();

        session_regenerate_id(true);
        $_SESSION['user_id']  = $userId;
        $_SESSION['username'] = $username;

        header('Location: /onboarding.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e($currentLang) ?>">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= e(__('register.title', 'Create Account — Visual Design Journey')) ?></title>
  <meta name="description" content="Join Visual Design Journey and start exploring curated mood boards."/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link rel="stylesheet" href="/assets/css/style.css"/>
  <style>
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 16px;
      background: var(--bg);
    }
    .auth-wrap {
      width: 100%;
      max-width: 480px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .auth-brand {
      text-align: center;
      margin-bottom: 32px;
    }
    .auth-brand h1 {
      font-family: var(--font-display);
      font-size: 22px;
      font-weight: 700;
      letter-spacing: -0.02em;
      margin-bottom: 6px;
    }
    .auth-brand h1 span { color: var(--accent); }
    .auth-brand p {
      font-size: 14px;
      color: var(--muted);
    }

    /* ── Stepper ── */
    .stepper {
      display: flex;
      align-items: center;
      gap: 0;
      width: 100%;
      margin-bottom: 28px;
    }
    .stepper-step {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      flex: 1;
      position: relative;
    }
    .stepper-step:not(:last-child)::after {
      content: '';
      position: absolute;
      top: 16px;
      left: calc(50% + 16px);
      right: calc(-50% + 16px);
      height: 1px;
      background: var(--border);
    }
    .stepper-circle {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 600;
      border: 1.5px solid var(--border);
      background: var(--bg);
      color: var(--muted);
      z-index: 1;
    }
    .stepper-step.active .stepper-circle {
      background: var(--accent);
      border-color: var(--accent);
      color: #fff;
    }
    .stepper-label {
      font-size: 11px;
      font-weight: 500;
      color: var(--muted);
      letter-spacing: 0.02em;
    }
    .stepper-step.active .stepper-label {
      color: var(--accent);
      font-weight: 600;
    }

    /* ── Card ── */
    .auth-card {
      width: 100%;
      background: #fff;
      border: 0.5px solid var(--border);
      border-radius: var(--r-lg);
      overflow: hidden;
      margin-bottom: 20px;
    }
    .auth-card-accent {
      height: 4px;
      background: var(--accent);
    }
    .auth-card-body {
      padding: 36px 40px 40px;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    .auth-card-title {
      font-family: var(--font-display);
      font-size: 20px;
      font-weight: 700;
      letter-spacing: -0.02em;
      color: var(--text);
      margin-bottom: 2px;
    }
    .auth-card-subtitle {
      font-size: 14px;
      color: var(--muted);
    }
    .auth-card form {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    /* ── Password toggle ── */
    .pw-wrap {
      position: relative;
    }
    .pw-wrap .form-input {
      padding-right: 44px;
    }
    .pw-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      padding: 4px;
      cursor: pointer;
      color: var(--muted);
      display: flex;
      align-items: center;
    }
    .pw-toggle:hover { color: var(--text); }
    .pw-toggle .material-symbols-outlined { font-size: 20px; }

    /* ── Footer link ── */
    .auth-footer {
      font-size: 13px;
      color: var(--muted);
      text-align: center;
    }
    .auth-footer a {
      color: var(--accent);
      font-weight: 500;
      margin-left: 4px;
    }
    .auth-footer a:hover { color: var(--text); }

    .alert-error {
      padding: 10px 14px;
      border-radius: var(--r-md);
      background: var(--danger-surface, #fef2f2);
      color: var(--danger, #dc2626);
      font-size: 13px;
      border: 0.5px solid #f5c6c6;
    }

    @media (max-width: 520px) {
      .auth-card-body { padding: 28px 20px 32px; }
    }
  </style>
</head>
<body>

<main class="auth-wrap">

  <!-- Brand -->
  <div class="auth-brand">
    <h1>Visual Design<span> Journey</span></h1>
    <p><?= e(__('register.subtitle', 'Create your account and start exploring.')) ?></p>
  </div>

  <!-- Stepper -->
  <div class="stepper" role="list" aria-label="Registration steps">
    <div class="stepper-step active" role="listitem">
      <div class="stepper-circle" aria-current="step">1</div>
      <span class="stepper-label"><?= e(__('register.step1', 'Account')) ?></span>
    </div>
    <div class="stepper-step" role="listitem">
      <div class="stepper-circle">2</div>
      <span class="stepper-label"><?= e(__('register.step2', 'Style')) ?></span>
    </div>
    <div class="stepper-step" role="listitem">
      <div class="stepper-circle">3</div>
      <span class="stepper-label"><?= e(__('register.step3', 'Explore')) ?></span>
    </div>
  </div>

  <!-- Card -->
  <div class="auth-card">
    <div class="auth-card-accent" aria-hidden="true"></div>
    <div class="auth-card-body">
      <div>
        <p class="auth-card-title"><?= e(__('register.submit', 'Create your account')) ?></p>
        <p class="auth-card-subtitle"><?= e(__('register.step_label', 'Step 1 of 3 — just the basics.')) ?></p>
      </div>

      <?php if (!empty($errors) && count($errors) > 1): ?>
        <div class="alert-error" role="alert">Please fix the errors below to continue.</div>
      <?php endif; ?>
      <?php if (isset($errors['form'])): ?>
        <div class="alert-error" role="alert"><?= e($errors['form']) ?></div>
      <?php endif; ?>

      <form method="POST" action="/register.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
        <input type="hidden" name="form_started_at" value="<?= e(signupTimestamp()) ?>"/>
        <div style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
          <label for="company_website">Company website</label>
          <input type="text" id="company_website" name="company_website" tabindex="-1" autocomplete="off"/>
        </div>

        <!-- Username -->
        <div class="form-group">
          <label class="form-label" for="username"><?= e(__('register.username', 'Username')) ?></label>
          <input
            class="form-input<?= isset($errors['username']) ? ' form-input--error' : '' ?>"
            type="text"
            id="username"
            name="username"
            placeholder="yourname"
            value="<?= e($fields['username']) ?>"
            autocomplete="username"
            required
          />
          <?php if (isset($errors['username'])): ?>
            <span class="form-error"><?= e($errors['username']) ?></span>
          <?php else: ?>
            <span class="form-hint">Letters, numbers and underscores only.</span>
          <?php endif; ?>
        </div>

        <!-- Email -->
        <div class="form-group">
          <label class="form-label" for="email"><?= e(__('register.email', 'Email Address')) ?></label>
          <input
            class="form-input<?= isset($errors['email']) ? ' form-input--error' : '' ?>"
            type="email"
            id="email"
            name="email"
            placeholder="name@example.com"
            value="<?= e($fields['email']) ?>"
            autocomplete="email"
            required
          />
          <?php if (isset($errors['email'])): ?>
            <span class="form-error"><?= e($errors['email']) ?></span>
          <?php endif; ?>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label class="form-label" for="password"><?= e(__('register.password', 'Password')) ?></label>
          <div class="pw-wrap">
            <input
              class="form-input<?= isset($errors['password']) ? ' form-input--error' : '' ?>"
              type="password"
              id="password"
              name="password"
              placeholder="Min. 8 characters"
              autocomplete="new-password"
              required
            />
            <button type="button" class="pw-toggle" aria-label="Toggle password visibility" data-target="password">
              <span class="material-symbols-outlined" data-icon="password">visibility</span>
            </button>
          </div>
          <?php if (isset($errors['password'])): ?>
            <span class="form-error"><?= e($errors['password']) ?></span>
          <?php endif; ?>
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
          <label class="form-label" for="confirm_password"><?= e(__('register.confirm_password', 'Confirm Password')) ?></label>
          <div class="pw-wrap">
            <input
              class="form-input<?= isset($errors['confirm_password']) ? ' form-input--error' : '' ?>"
              type="password"
              id="confirm_password"
              name="confirm_password"
              placeholder="Repeat your password"
              autocomplete="new-password"
              required
            />
            <button type="button" class="pw-toggle" aria-label="Toggle confirm password visibility" data-target="confirm_password">
              <span class="material-symbols-outlined" data-icon="confirm_password">visibility</span>
            </button>
          </div>
          <?php if (isset($errors['confirm_password'])): ?>
            <span class="form-error"><?= e($errors['confirm_password']) ?></span>
          <?php endif; ?>
        </div>

        <button
          class="btn btn--primary"
          type="submit"
          style="width:100%;justify-content:center;padding:13px 24px;font-size:15px;margin-top:4px;"
        >
          Continue &rarr;
        </button>
      </form>
    </div>
  </div>

  <!-- Footer -->
  <p class="auth-footer">
    <?= e(__('register.have_account', 'Already have an account?')) ?>
    <a href="/login.php"><?= e(__('register.sign_in', 'Sign in')) ?></a>
  </p>

</main>

<script>
  document.querySelectorAll('.pw-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var input = document.getElementById(btn.dataset.target);
      var icon  = btn.querySelector('.material-symbols-outlined');
      if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = 'visibility_off';
      } else {
        input.type = 'password';
        icon.textContent = 'visibility';
      }
    });
  });
</script>
</body>
</html>
