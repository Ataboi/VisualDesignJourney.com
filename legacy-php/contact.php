<?php
/**
 * contact.php — Contact page
 * Visual Design Journey · vibe.visualdesignjourney.com
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (vdjRenderLocalizedStaticPage('contact')) exit;

session_start_if_not_started();

$pageTitle = __('contact.get_in_touch', 'Contact') . ' — Visual Design Journey';
$activeNav = '';

$errors  = [];
$success = false;

/* ── PRG: show success banner after redirect ──────────────────────────────── */
if (isset($_GET['sent']) && $_GET['sent'] === '1') {
    $success = true;
}

/* ── POST handler ─────────────────────────────────────────────────────────── */
$formName    = '';
$formEmail   = '';
$formSubject = 'General Inquiry';
$formMessage = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verifyCsrf();

    $formName    = trim($_POST['contact_name']    ?? '');
    $formEmail   = normalizeEmail($_POST['contact_email'] ?? '');
    $formSubject = trim($_POST['contact_subject']  ?? 'General Inquiry');
    $formMessage = trim($_POST['contact_message'] ?? '');

    $allowedSubjects = ['General Inquiry', 'Partnership', 'Bug Report', 'Feature Request'];

    /* ── Validation ──────────────────────────────────────────────────────── */
    if ($formName === '') {
        $errors[] = __('contact.error_name', 'Your name is required.');
    }
    if ($formEmail === '' || !filter_var($formEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('contact.error_email', 'A valid email address is required.');
    } elseif (!isAllowedEmail($formEmail)) {
        $errors[] = __('contact.error_email_perm', 'Please use a permanent email address.');
    }
    if (isBotUserAgent() || !rateLimitCheck('contact-ip', 4, 60 * 60) || ($formEmail !== '' && !rateLimitCheckKey('contact-email', $formEmail, 2, 24 * 60 * 60))) {
        $errors[] = __('contact.error_attempts', 'Too many contact attempts. Please try again later.');
    }
    if (!in_array($formSubject, $allowedSubjects, true)) {
        $formSubject = 'General Inquiry';
    }
    if (mb_strlen($formMessage) < 10) {
        $errors[] = __('contact.error_message', 'Please write a message (at least 10 characters).');
    } elseif (preg_match('/https?:\/\/|casino|crypto|forex|viagra|porn|escort|telegram/i', $formName . ' ' . $formSubject . ' ' . $formMessage)) {
        $errors[] = __('contact.error_invalid', 'Please enter a valid message.');
    }

    /* ── Send ────────────────────────────────────────────────────────────── */
    if (empty($errors)) {
        $to      = 'a.ozelbicer@gmail.com';
        $subject = '[VDJ Contact] ' . $formSubject . ' from ' . $formName;

        $body  = "You have a new message from the Visual Design Journey contact form.\n\n";
        $body .= "Name:    " . $formName    . "\n";
        $body .= "Email:   " . $formEmail   . "\n";
        $body .= "Subject: " . $formSubject . "\n\n";
        $body .= "Message:\n" . $formMessage . "\n";

        $headers  = "From: noreply@visualdesignjourney.com\r\n";
        $headers .= "Reply-To: " . $formEmail . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        $sent = mail($to, $subject, $body, $headers);

        if ($sent) {
            header('Location: /contact.php?sent=1');
            exit;
        } else {
            $errors[] = __('contact.error_send_fail', 'Sorry, the message could not be sent right now. Please try emailing directly.');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="page-wrap">

  <style>
    /* ── Contact page scoped styles ───────────────────────────────────────── */
    .contact-hero {
      text-align: center;
      padding: 64px 0 48px;
    }
    .contact-hero__eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: color-mix(in srgb, var(--accent) 12%, transparent);
      color: var(--accent);
      border-radius: 100px;
      padding: 5px 14px;
      font-family: 'Manrope', sans-serif;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: .04em;
      margin-bottom: 18px;
    }
    .contact-hero__title {
      font-family: 'Manrope', sans-serif;
      font-size: clamp(2rem, 5vw, 3rem);
      font-weight: 800;
      line-height: 1.15;
      margin: 0 0 14px;
    }
    .contact-hero__sub {
      font-size: 1.05rem;
      color: var(--text-muted, #888);
      max-width: 480px;
      margin: 0 auto;
      line-height: 1.7;
    }

    /* ── Two-column layout ────────────────────────────────────────────────── */
    .contact-grid {
      display: grid;
      grid-template-columns: 60fr 40fr;
      gap: 40px;
      align-items: start;
      margin-bottom: 72px;
    }
    @media (max-width: 800px) {
      .contact-grid {
        grid-template-columns: 1fr;
      }
    }

    /* ── Alert banners ────────────────────────────────────────────────────── */
    .alert {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 14px 18px;
      border-radius: 12px;
      margin-bottom: 24px;
      font-size: 0.95rem;
      line-height: 1.55;
    }
    .alert i { margin-top: 2px; flex-shrink: 0; }
    .alert--success {
      background: color-mix(in srgb, #22c55e 12%, transparent);
      color: #16a34a;
      border: 1px solid color-mix(in srgb, #22c55e 30%, transparent);
    }
    .alert--error {
      background: color-mix(in srgb, #ef4444 10%, transparent);
      color: #b91c1c;
      border: 1px solid color-mix(in srgb, #ef4444 25%, transparent);
    }
    .alert ul { margin: 6px 0 0 16px; padding: 0; }
    .alert li { margin-bottom: 3px; }

    /* ── Contact form card ────────────────────────────────────────────────── */
    .contact-form-card {
      background: var(--card-bg, #fff);
      border: 1px solid var(--border, #e5e7eb);
      border-radius: 20px;
      padding: 36px 32px;
    }
    .contact-form-card__title {
      font-family: 'Manrope', sans-serif;
      font-size: 1.25rem;
      font-weight: 700;
      margin: 0 0 24px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .contact-form-card__title i {
      color: var(--accent);
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }
    @media (max-width: 500px) {
      .form-row { grid-template-columns: 1fr; }
    }
    .form-group {
      margin-bottom: 18px;
    }
    .form-group label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-muted, #555);
      margin-bottom: 6px;
      letter-spacing: .02em;
    }
    .form-group label .req {
      color: var(--accent);
      margin-left: 2px;
    }
    .form-control {
      width: 100%;
      padding: 11px 14px;
      border: 1.5px solid var(--border, #e5e7eb);
      border-radius: 10px;
      font-family: 'Inter', sans-serif;
      font-size: 0.95rem;
      background: var(--input-bg, #fafafa);
      color: var(--text, #111);
      transition: border-color .18s, box-shadow .18s;
      box-sizing: border-box;
    }
    .form-control:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 18%, transparent);
    }
    textarea.form-control {
      resize: vertical;
      min-height: 140px;
    }
    .form-control.is-invalid {
      border-color: #ef4444;
    }

    .btn-send {
      display: inline-flex;
      align-items: center;
      gap: 9px;
      background: var(--accent);
      color: #fff;
      font-family: 'Manrope', sans-serif;
      font-weight: 700;
      font-size: 0.95rem;
      padding: 12px 28px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: opacity .18s, transform .15s;
      letter-spacing: .02em;
    }
    .btn-send:hover {
      opacity: .88;
      transform: translateY(-1px);
    }
    .btn-send:active { transform: translateY(0); }

    /* ── Info cards sidebar ───────────────────────────────────────────────── */
    .info-cards {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }
    .info-card {
      background: var(--card-bg, #fff);
      border: 1px solid var(--border, #e5e7eb);
      border-radius: 16px;
      padding: 22px 22px 20px;
      display: flex;
      align-items: flex-start;
      gap: 16px;
      transition: box-shadow .2s, transform .2s;
    }
    .info-card:hover {
      box-shadow: 0 8px 28px color-mix(in srgb, var(--accent) 12%, transparent);
      transform: translateY(-2px);
    }
    .info-card__icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: color-mix(in srgb, var(--accent) 12%, transparent);
      color: var(--accent);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.15rem;
      flex-shrink: 0;
    }
    .info-card__body {}
    .info-card__label {
      font-family: 'Manrope', sans-serif;
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--text-muted, #888);
      letter-spacing: .06em;
      text-transform: uppercase;
      margin: 0 0 4px;
    }
    .info-card__value {
      font-size: 0.95rem;
      font-weight: 600;
      color: var(--text, #111);
      margin: 0 0 2px;
      word-break: break-all;
    }
    .info-card__value a {
      color: var(--accent);
      text-decoration: none;
    }
    .info-card__value a:hover { text-decoration: underline; }
    .info-card__note {
      font-size: 0.78rem;
      color: var(--text-muted, #888);
      margin: 4px 0 0;
    }

    /* ── FAQ section ──────────────────────────────────────────────────────── */
    .faq-section {
      margin-bottom: 72px;
    }
    .faq-section__header {
      text-align: center;
      margin-bottom: 36px;
    }
    .faq-section__eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: color-mix(in srgb, var(--accent) 12%, transparent);
      color: var(--accent);
      border-radius: 100px;
      padding: 5px 14px;
      font-family: 'Manrope', sans-serif;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: .04em;
      margin-bottom: 14px;
    }
    .faq-section__title {
      font-family: 'Manrope', sans-serif;
      font-size: clamp(1.4rem, 3vw, 2rem);
      font-weight: 800;
      margin: 0;
    }
    .faq-list {
      max-width: 760px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .faq-item {
      border: 1px solid var(--border, #e5e7eb);
      border-radius: 14px;
      overflow: hidden;
      transition: box-shadow .2s;
    }
    .faq-item.open {
      box-shadow: 0 4px 20px color-mix(in srgb, var(--accent) 10%, transparent);
    }
    .faq-item__question {
      width: 100%;
      background: var(--card-bg, #fff);
      border: none;
      padding: 18px 22px;
      text-align: left;
      font-family: 'Manrope', sans-serif;
      font-size: 1rem;
      font-weight: 700;
      color: var(--text, #111);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      transition: background .15s;
    }
    .faq-item__question:hover {
      background: color-mix(in srgb, var(--accent) 5%, var(--card-bg, #fff));
    }
    .faq-item__question i.faq-icon {
      color: var(--accent);
      font-size: 0.85rem;
      transition: transform .25s;
      flex-shrink: 0;
    }
    .faq-item.open .faq-icon {
      transform: rotate(180deg);
    }
    .faq-item__answer {
      display: none;
      padding: 0 22px 18px;
      font-size: 0.95rem;
      line-height: 1.7;
      color: var(--text-muted, #555);
      background: var(--card-bg, #fff);
    }
    .faq-item.open .faq-item__answer {
      display: block;
    }
  </style>

  <div class="container">

    <!-- ── Hero ──────────────────────────────────────────────────────────── -->
    <section class="contact-hero" data-animate="fade-up">
      <div class="contact-hero__eyebrow">
        <i class="fa-solid fa-paper-plane"></i>
        <?= e(__('contact.say_hello', 'Say Hello')) ?>
      </div>
      <h1 class="contact-hero__title"><?= e(__('contact.get_in_touch', 'Get in Touch')) ?></h1>
      <p class="contact-hero__sub">
        <?= e(__('contact.sub', "Have a question, idea, or just want to chat about design? We'd love to hear from you. Fill in the form or reach out directly — we read every message.")) ?>
      </p>
    </section>

    <!-- ── Success banner (PRG) ───────────────────────────────────────────── -->
    <?php if ($success): ?>
    <div class="alert alert--success" role="alert" data-animate="fade-up">
      <i class="fa-solid fa-circle-check"></i>
      <div>
        <strong><?= e(__('contact.success_title', 'Message sent!')) ?></strong> <?= e(__('contact.success_body', "Thank you for reaching out. We'll get back to you within 24–48 hours.")) ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Error banner ───────────────────────────────────────────────────── -->
    <?php if (!empty($errors)): ?>
    <div class="alert alert--error" role="alert" data-animate="fade-up">
      <i class="fa-solid fa-triangle-exclamation"></i>
      <div>
        <strong><?= e(__('contact.error_title', 'Please fix the following:')) ?></strong>
        <ul>
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Two-column grid ────────────────────────────────────────────────── -->
    <div class="contact-grid">

      <!-- LEFT — Contact form -->
      <div class="contact-form-card" data-animate="fade-up">
        <h2 class="contact-form-card__title">
          <i class="fa-solid fa-envelope-open-text"></i>
          <?= e(__('contact.send_message', 'Send Us a Message')) ?>
        </h2>

        <form method="POST" action="/contact.php" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">

          <div class="form-row">
            <div class="form-group">
              <label for="contact_name"><?= e(__('contact.your_name', 'Your Name')) ?> <span class="req">*</span></label>
              <input
                type="text"
                id="contact_name"
                name="contact_name"
                class="form-control<?= !empty($errors) && $formName === '' ? ' is-invalid' : '' ?>"
                placeholder="Jane Smith"
                value="<?= htmlspecialchars($formName) ?>"
                required
                autocomplete="name"
              >
            </div>
            <div class="form-group">
              <label for="contact_email"><?= e(__('contact.email', 'Email Address')) ?> <span class="req">*</span></label>
              <input
                type="email"
                id="contact_email"
                name="contact_email"
                class="form-control<?= !empty($errors) && (!filter_var($formEmail, FILTER_VALIDATE_EMAIL)) ? ' is-invalid' : '' ?>"
                placeholder="jane@example.com"
                value="<?= htmlspecialchars($formEmail) ?>"
                required
                autocomplete="email"
              >
            </div>
          </div>

          <div class="form-group">
            <label for="contact_subject">
              <i class="fa-solid fa-tag" style="color:var(--accent);margin-right:4px;"></i>
              <?= e(__('contact.subject', 'Subject')) ?>
            </label>
            <select id="contact_subject" name="contact_subject" class="form-control">
              <?php foreach (['General Inquiry', 'Partnership', 'Bug Report', 'Feature Request'] as $opt): ?>
                <option value="<?= htmlspecialchars($opt) ?>"<?= $formSubject === $opt ? ' selected' : '' ?>>
                  <?= htmlspecialchars($opt) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="contact_message"><?= e(__('contact.message', 'Message')) ?> <span class="req">*</span></label>
            <textarea
              id="contact_message"
              name="contact_message"
              class="form-control<?= !empty($errors) && mb_strlen($formMessage) < 10 ? ' is-invalid' : '' ?>"
              placeholder="Tell us what's on your mind…"
              required
            ><?= htmlspecialchars($formMessage) ?></textarea>
          </div>

          <button type="submit" class="btn-send">
            <i class="fa-solid fa-paper-plane"></i>
            <?= e(__('contact.send_btn', 'Send Message')) ?>
          </button>
        </form>
      </div><!-- /contact-form-card -->

      <!-- RIGHT — Info cards -->
      <aside class="info-cards">

        <!-- Email -->
        <div class="info-card" data-animate="fade-up">
          <div class="info-card__icon">
            <i class="fa-solid fa-envelope"></i>
          </div>
          <div class="info-card__body">
            <p class="info-card__label"><?= e(__('contact.email_us', 'Email Us')) ?></p>
            <p class="info-card__value">
              <a href="mailto:a.ozelbicer@gmail.com">a.ozelbicer@gmail.com</a>
            </p>
          </div>
        </div>

        <!-- WhatsApp -->
        <div class="info-card" data-animate="fade-up">
          <div class="info-card__icon">
            <i class="fa-brands fa-whatsapp"></i>
          </div>
          <div class="info-card__body">
            <p class="info-card__label"><?= e(__('contact.whatsapp_label', 'WhatsApp Only')) ?></p>
            <p class="info-card__value">
              <a href="https://wa.me/447928082595" target="_blank" rel="noopener noreferrer">
                +44 7928 082595
              </a>
            </p>
            <p class="info-card__note">
              <i class="fa-solid fa-circle-info" style="margin-right:4px;"></i>
              <?= e(__('contact.whatsapp_note', 'WhatsApp messages only — no calls')) ?>
            </p>
          </div>
        </div>

        <!-- Response time -->
        <div class="info-card" data-animate="fade-up">
          <div class="info-card__icon">
            <i class="fa-solid fa-clock"></i>
          </div>
          <div class="info-card__body">
            <p class="info-card__label"><?= e(__('contact.response_label', 'Response Time')) ?></p>
            <p class="info-card__value"><?= e(__('contact.response_value', 'Usually within 24–48 hours')) ?></p>
          </div>
        </div>

        <!-- Location -->
        <div class="info-card" data-animate="fade-up">
          <div class="info-card__icon">
            <i class="fa-solid fa-location-dot"></i>
          </div>
          <div class="info-card__body">
            <p class="info-card__label"><?= e(__('contact.based_label', 'Based In')) ?></p>
            <p class="info-card__value">London, UK</p>
          </div>
        </div>

      </aside><!-- /info-cards -->

    </div><!-- /contact-grid -->

    <!-- ── FAQ section ────────────────────────────────────────────────────── -->
    <section class="faq-section" data-animate="fade-up">
      <div class="faq-section__header">
        <div class="faq-section__eyebrow">
          <i class="fa-solid fa-circle-question"></i>
          <?= e(__('contact.faq_eyebrow', 'FAQ')) ?>
        </div>
        <h2 class="faq-section__title"><?= e(__('contact.faq_title', 'Frequently Asked Questions')) ?></h2>
      </div>

      <div class="faq-list">

        <!-- FAQ 1 -->
        <div class="faq-item" data-animate="fade-up">
          <button class="faq-item__question" aria-expanded="false" aria-controls="faq-1">
            <span><i class="fa-solid fa-bug" style="color:var(--accent);margin-right:10px;"></i>How do I report a bug?</span>
            <i class="fa-solid fa-chevron-down faq-icon"></i>
          </button>
          <div class="faq-item__answer" id="faq-1" role="region">
            Found something broken? Use the contact form above and choose <strong>Bug Report</strong> as the subject. Please describe what you were doing, what you expected to happen, and what actually happened. Screenshots are always helpful — you can include links to image uploads in your message.
          </div>
        </div>

        <!-- FAQ 2 -->
        <div class="faq-item" data-animate="fade-up">
          <button class="faq-item__question" aria-expanded="false" aria-controls="faq-2">
            <span><i class="fa-solid fa-handshake" style="color:var(--accent);margin-right:10px;"></i>Can I collaborate or partner with Visual Design Journey?</span>
            <i class="fa-solid fa-chevron-down faq-icon"></i>
          </button>
          <div class="faq-item__answer" id="faq-2" role="region">
            Absolutely — we love creative partnerships. Whether you're a brand, a design studio, a tool maker, or an independent creator, reach out via the form above with <strong>Partnership</strong> selected. Tell us about yourself and what you have in mind, and we'll get back to you.
          </div>
        </div>

        <!-- FAQ 3 -->
        <div class="faq-item" data-animate="fade-up">
          <button class="faq-item__question" aria-expanded="false" aria-controls="faq-3">
            <span><i class="fa-solid fa-user-xmark" style="color:var(--accent);margin-right:10px;"></i>How do I delete my account?</span>
            <i class="fa-solid fa-chevron-down faq-icon"></i>
          </button>
          <div class="faq-item__answer" id="faq-3" role="region">
            You can delete your account from your <a href="/settings.php?section=danger" style="color:var(--accent);font-weight:600;">Account Settings</a> page under the <strong>Danger Zone</strong> section. This is permanent and will remove your profile, boards, and all associated content. If you run into any trouble, contact us and we'll handle it manually.
          </div>
        </div>

        <!-- FAQ 4 -->
        <div class="faq-item" data-animate="fade-up">
          <button class="faq-item__question" aria-expanded="false" aria-controls="faq-4">
            <span><i class="fa-solid fa-key" style="color:var(--accent);margin-right:10px;"></i>I forgot my password — what do I do?</span>
            <i class="fa-solid fa-chevron-down faq-icon"></i>
          </button>
          <div class="faq-item__answer" id="faq-4" role="region">
            Head to the <a href="/login.php" style="color:var(--accent);font-weight:600;">Login page</a> and click <strong>"Forgot password?"</strong> to request a reset link via email. If you don't receive the email within a few minutes, check your spam folder. Still stuck? Drop us a message through the contact form and we'll sort it out.
          </div>
        </div>

      </div><!-- /faq-list -->
    </section><!-- /faq-section -->

  </div><!-- /container -->

</main>

<script>
  /* ── FAQ accordion ────────────────────────────────────────────────────────── */
  document.querySelectorAll('.faq-item__question').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var item = this.closest('.faq-item');
      var isOpen = item.classList.contains('open');

      // Close all
      document.querySelectorAll('.faq-item').forEach(function (el) {
        el.classList.remove('open');
        el.querySelector('.faq-item__question').setAttribute('aria-expanded', 'false');
      });

      // Toggle clicked
      if (!isOpen) {
        item.classList.add('open');
        this.setAttribute('aria-expanded', 'true');
      }
    });
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
