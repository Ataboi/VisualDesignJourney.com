<?php
/**
 * login.php — Authentication page
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/i18n.php';

session_start_if_not_started();
$currentLang = vdj_bootstrap_language();

// Already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

$error = '';
$emailVal = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verifyCsrf();

    $email    = normalizeEmail($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $emailVal = htmlspecialchars($email);

    if ($email === '' || $password === '') {
        $error = __('login.error_empty', 'Please fill in both fields.');
    } elseif (isBotUserAgent() || !rateLimitCheck('login-ip', 8, 15 * 60) || !rateLimitCheckKey('login-email', $email, 8, 15 * 60)) {
        $error = __('login.error_attempts', 'Too many sign-in attempts. Please wait a few minutes and try again.');
    } else {
        $db   = getDB();
        $user = getUserByEmail($db, $email);

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];

            $next = $_GET['next'] ?? '';
            $next = (strncmp($next, '/', 1) === 0 && strncmp($next, '//', 2) !== 0) ? $next : '/index.php';

            if (empty($user['is_onboarded'])) {
                header('Location: /onboarding.php');
            } else {
                header('Location: ' . $next);
            }
            exit;
        } else {
            $error = __('login.error_invalid', 'Incorrect email or password.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e($currentLang) ?>">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= e(__('login.title', 'Sign In — Visual Design Journey')) ?></title>
  <meta name="description" content="Sign in to Visual Design Journey and explore curated mood boards."/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link rel="stylesheet" href="/assets/css/style.css"/>
  <style>
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
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
      margin-bottom: 40px;
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
    .auth-card {
      width: 100%;
      background: #fff;
      border: 0.5px solid var(--border);
      border-radius: var(--r-lg);
      padding: 40px;
      display: flex;
      flex-direction: column;
      gap: 20px;
      margin-bottom: 20px;
    }
    .auth-card form {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }
    .auth-label-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .auth-forgot {
      font-size: 12px;
      color: var(--muted);
      transition: color 0.15s;
    }
    .auth-forgot:hover { color: var(--accent); }
    .auth-divider {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .auth-divider::before,
    .auth-divider::after {
      content: '';
      flex: 1;
      height: 0.5px;
      background: var(--border);
    }
    .auth-divider span {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--muted);
    }
    .auth-google-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      padding: 12px 24px;
      border: 0.5px solid var(--border);
      border-radius: var(--r-pill);
      background: transparent;
      font-size: 15px;
      font-weight: 500;
      color: var(--text);
      transition: background 0.15s;
    }
    .auth-google-btn:hover { background: var(--surface); }
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
      background: var(--danger-surface);
      color: var(--danger);
      font-size: 13px;
      border: 0.5px solid #f5c6c6;
    }
    @media (max-width: 520px) {
      .auth-card { padding: 28px 20px; }
    }
  </style>
</head>
<body>

<main class="auth-wrap">
  <!-- Brand -->
  <div class="auth-brand">
    <h1>Visual Design<span> Journey</span></h1>
    <p><?= e(__('login.welcome', 'Welcome back to the gallery.')) ?></p>
  </div>

  <!-- Card -->
  <div class="auth-card">

    <?php if ($error): ?>
      <div class="alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/login.php<?= isset($_GET['next']) ? '?next=' . urlencode($_GET['next']) : '' ?>" novalidate>
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>

      <!-- Email -->
      <div class="form-group">
        <label class="form-label" for="email"><?= e(__('login.email', 'Email Address')) ?></label>
        <input
          class="form-input"
          type="email"
          id="email"
          name="email"
          placeholder="name@example.com"
          value="<?= $emailVal ?>"
          autocomplete="email"
          required
        />
      </div>

      <!-- Password -->
      <div class="form-group">
        <div class="auth-label-row">
          <label class="form-label" for="password"><?= e(__('login.password', 'Password')) ?></label>
          <a class="auth-forgot" href="#"><?= e(__('login.forgot_password', 'Forgot password?')) ?></a>
        </div>
        <input
          class="form-input"
          type="password"
          id="password"
          name="password"
          placeholder="••••••••"
          autocomplete="current-password"
          required
        />
      </div>

      <button class="btn btn--primary" type="submit" style="width:100%;justify-content:center;padding:13px 24px;font-size:15px;">
        <?= e(__('login.sign_in', 'Sign In')) ?>
      </button>
    </form>

    <div class="auth-divider"><span><?= e(__('login.or', 'Or')) ?></span></div>

    <button class="auth-google-btn" type="button">
      <span class="material-symbols-outlined" style="font-size:20px;">account_circle</span>
      <?= e(__('login.google', 'Continue with Google')) ?>
    </button>

  </div>

  <!-- Footer -->
  <p class="auth-footer">
    <?= e(__('login.no_account', "Don't have an account?")) ?>
    <a href="/register.php"><?= e(__('login.register_now', 'Register now')) ?></a>
  </p>
</main>

<script src="/assets/js/main.js"></script>
</body>
</html>
