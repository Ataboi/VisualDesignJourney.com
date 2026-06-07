<?php
/**
 * privacy.php — Privacy Policy
 * Visual Design Journey · visualdesignjourney.com
 */

require_once __DIR__ . '/includes/functions.php';
if (vdjRenderLocalizedStaticPage('privacy')) exit;

$pageTitle = 'Privacy Policy — Visual Design Journey';
$metaDesc  = 'Learn how Visual Design Journey collects, uses, and protects your personal information. We are committed to your privacy and GDPR compliance.';
$activeNav = '';

require_once __DIR__ . '/includes/header.php';
?>

<main class="page-wrap">

  <!-- ── HERO ───────────────────────────────────────────────────────────── -->
  <section class="privacy-hero">
    <div class="container privacy-hero__inner">
      <div class="privacy-hero__badge">
        <i class="fa-solid fa-shield-halved"></i>
        Legal
      </div>
      <h1 class="t-display privacy-hero__title">Privacy Policy</h1>
      <p class="t-body-lg text-muted privacy-hero__sub">
        We believe privacy is a right, not a feature. Here is exactly what we collect,
        why we collect it, and how you stay in control.
      </p>
      <p class="privacy-hero__date">
        <i class="fa-regular fa-calendar"></i>
        Last updated: <strong>May 2026</strong>
      </p>
    </div>
  </section>

  <!-- ── BODY: SIDEBAR TOC + CONTENT ───────────────────────────────────── -->
  <section class="privacy-body">
    <div class="container privacy-body__inner">

      <!-- Sidebar TOC (desktop: sticky left column) -->
      <aside class="privacy-toc" aria-label="Table of contents">
        <p class="privacy-toc__label t-label">On this page</p>
        <nav>
          <ul class="privacy-toc__list">
            <li><a class="privacy-toc__link" href="#collect">
              <i class="fa-solid fa-user"></i> Information We Collect
            </a></li>
            <li><a class="privacy-toc__link" href="#use">
              <i class="fa-solid fa-chart-bar"></i> How We Use Information
            </a></li>
            <li><a class="privacy-toc__link" href="#sharing">
              <i class="fa-solid fa-share-nodes"></i> Information Sharing
            </a></li>
            <li><a class="privacy-toc__link" href="#retention">
              <i class="fa-solid fa-clock"></i> Data Retention
            </a></li>
            <li><a class="privacy-toc__link" href="#security">
              <i class="fa-solid fa-shield-halved"></i> Security
            </a></li>
            <li><a class="privacy-toc__link" href="#cookies">
              <i class="fa-solid fa-cookie-bite"></i> Cookies
            </a></li>
            <li><a class="privacy-toc__link" href="#rights">
              <i class="fa-solid fa-scale-balanced"></i> Your Rights
            </a></li>
            <li><a class="privacy-toc__link" href="#contact">
              <i class="fa-solid fa-envelope"></i> Contact Us
            </a></li>
          </ul>
        </nav>
      </aside>

      <!-- Mobile pill nav (replaces sidebar on small screens) -->
      <div class="privacy-toc-mobile" aria-label="Jump to section">
        <div class="privacy-toc-mobile__scroll">
          <a class="privacy-toc-mobile__pill" href="#collect">
            <i class="fa-solid fa-user"></i> Collect
          </a>
          <a class="privacy-toc-mobile__pill" href="#use">
            <i class="fa-solid fa-chart-bar"></i> Use
          </a>
          <a class="privacy-toc-mobile__pill" href="#sharing">
            <i class="fa-solid fa-share-nodes"></i> Sharing
          </a>
          <a class="privacy-toc-mobile__pill" href="#retention">
            <i class="fa-solid fa-clock"></i> Retention
          </a>
          <a class="privacy-toc-mobile__pill" href="#security">
            <i class="fa-solid fa-shield-halved"></i> Security
          </a>
          <a class="privacy-toc-mobile__pill" href="#cookies">
            <i class="fa-solid fa-cookie-bite"></i> Cookies
          </a>
          <a class="privacy-toc-mobile__pill" href="#rights">
            <i class="fa-solid fa-scale-balanced"></i> Rights
          </a>
          <a class="privacy-toc-mobile__pill" href="#contact">
            <i class="fa-solid fa-envelope"></i> Contact
          </a>
        </div>
      </div>

      <!-- Content sections -->
      <div class="privacy-content">

        <!-- 1. Information We Collect -->
        <article class="privacy-card" id="collect" data-animate>
          <h2 class="privacy-card__heading">
            <span class="privacy-card__icon">
              <i class="fa-solid fa-user"></i>
            </span>
            Information We Collect
          </h2>
          <p class="privacy-card__intro">
            We only ask for what we actually need. Here is a plain-language breakdown of
            the three categories of information we may gather.
          </p>

          <div class="privacy-highlight-grid">
            <div class="privacy-highlight">
              <div class="privacy-highlight__icon">
                <i class="fa-solid fa-user"></i>
              </div>
              <div>
                <h3 class="privacy-highlight__title">Account Information</h3>
                <p class="privacy-highlight__desc">
                  When you create an account we collect your username, email address,
                  and the password you choose (stored as a secure hash — we never see
                  your plain-text password). You may optionally add a display name,
                  bio, website URL, and profile photo.
                </p>
              </div>
            </div>

            <div class="privacy-highlight">
              <div class="privacy-highlight__icon">
                <i class="fa-solid fa-image"></i>
              </div>
              <div>
                <h3 class="privacy-highlight__title">Content You Create</h3>
                <p class="privacy-highlight__desc">
                  Any mood boards, images, titles, descriptions, tags, and comments you
                  post on Visual Design Journey are stored and associated with your
                  account. Public content is visible to all visitors; boards you mark
                  private are visible only to you.
                </p>
              </div>
            </div>

            <div class="privacy-highlight">
              <div class="privacy-highlight__icon">
                <i class="fa-solid fa-mouse-pointer"></i>
              </div>
              <div>
                <h3 class="privacy-highlight__title">Usage &amp; Technical Data</h3>
                <p class="privacy-highlight__desc">
                  We automatically record standard server logs including your IP address,
                  browser type, operating system, referring URL, pages visited, and
                  timestamps. This data helps us keep the service fast, secure, and
                  reliable. We do not build individual behavioural profiles from it.
                </p>
              </div>
            </div>
          </div>
        </article>

        <!-- 2. How We Use Information -->
        <article class="privacy-card" id="use" data-animate>
          <h2 class="privacy-card__heading">
            <span class="privacy-card__icon">
              <i class="fa-solid fa-chart-bar"></i>
            </span>
            How We Use Information
          </h2>
          <p class="privacy-card__intro">
            We use the information we collect to run the platform and improve your
            experience — nothing else.
          </p>

          <ul class="privacy-list">
            <li>
              <i class="fa-solid fa-circle-check"></i>
              <span><strong>Provide the service.</strong> Your account data lets you log in,
              create boards, follow curators, and interact with the community.</span>
            </li>
            <li>
              <i class="fa-solid fa-circle-check"></i>
              <span><strong>Personalise your feed.</strong> We use your follows, likes, and
              browsing activity to surface content that matches your tastes — entirely
              on our own infrastructure.</span>
            </li>
            <li>
              <i class="fa-solid fa-circle-check"></i>
              <span>
                <i class="fa-solid fa-envelope" style="color:var(--accent);margin-right:4px;"></i>
                <strong>Send you notifications.</strong> With your consent we send
                transactional emails (password resets, follower alerts) and, if you opt in,
                occasional digest emails about trending content. You can unsubscribe at any time
                from your settings page.
              </span>
            </li>
            <li>
              <i class="fa-solid fa-circle-check"></i>
              <span><strong>Maintain safety and integrity.</strong> Usage logs help us detect
              abuse, spam, and unauthorised access attempts so we can protect all users.</span>
            </li>
            <li>
              <i class="fa-solid fa-circle-check"></i>
              <span><strong>Improve the product.</strong> Aggregated, anonymised analytics
              tell us which features people love and where we can do better. We never sell
              or share this data with third parties for advertising.</span>
            </li>
          </ul>
        </article>

        <!-- 3. Information Sharing -->
        <article class="privacy-card" id="sharing" data-animate>
          <h2 class="privacy-card__heading">
            <span class="privacy-card__icon">
              <i class="fa-solid fa-share-nodes"></i>
            </span>
            Information Sharing
          </h2>
          <p class="privacy-card__intro">
            We do not sell your personal data. Full stop.
          </p>
          <p>
            We may share limited information only in these specific circumstances:
          </p>
          <ul class="privacy-list">
            <li>
              <i class="fa-solid fa-server"></i>
              <span><strong>Infrastructure providers.</strong> Our hosting and storage
              partners process data on our behalf under strict data-processing agreements.
              They act on our instructions only and are prohibited from using your data
              for their own purposes.</span>
            </li>
            <li>
              <i class="fa-solid fa-gavel"></i>
              <span><strong>Legal obligations.</strong> We may disclose information if
              required to do so by a valid court order, subpoena, or applicable law.
              We will notify you unless legally prohibited from doing so.</span>
            </li>
            <li>
              <i class="fa-solid fa-building"></i>
              <span><strong>Business transfers.</strong> In the unlikely event of a merger
              or acquisition, your data may transfer to the new entity, which would be
              bound by this policy or a materially equivalent one.</span>
            </li>
            <li>
              <i class="fa-solid fa-user-group"></i>
              <span><strong>Public content.</strong> Any board or comment you mark as
              public is visible to all visitors by design — that is the core function
              of the platform.</span>
            </li>
          </ul>
        </article>

        <!-- 4. Data Retention -->
        <article class="privacy-card" id="retention" data-animate>
          <h2 class="privacy-card__heading">
            <span class="privacy-card__icon">
              <i class="fa-solid fa-clock"></i>
            </span>
            Data Retention
          </h2>
          <p class="privacy-card__intro">
            We keep your data for as long as your account is active or as needed to
            provide you with the service.
          </p>
          <p>
            Specifically:
          </p>
          <ul class="privacy-list">
            <li>
              <i class="fa-solid fa-circle-dot"></i>
              <span><strong>Active accounts.</strong> Your profile, boards, and uploaded
              images are retained as long as your account exists.</span>
            </li>
            <li>
              <i class="fa-solid fa-circle-dot"></i>
              <span><strong>Deleted accounts.</strong> When you delete your account, your
              personal data is purged within <strong>30 days</strong>. Publicly visible
              content (e.g. comments on community boards) may be anonymised rather than
              removed to preserve the integrity of conversations.</span>
            </li>
            <li>
              <i class="fa-solid fa-circle-dot"></i>
              <span><strong>Server logs.</strong> Raw access logs are retained for up to
              <strong>90 days</strong> for security monitoring, then permanently deleted.</span>
            </li>
            <li>
              <i class="fa-solid fa-circle-dot"></i>
              <span><strong>Backups.</strong> Encrypted backups may retain data for up to
              <strong>30 additional days</strong> beyond the above windows before being
              overwritten.</span>
            </li>
          </ul>
        </article>

        <!-- 5. Security -->
        <article class="privacy-card" id="security" data-animate>
          <h2 class="privacy-card__heading">
            <span class="privacy-card__icon">
              <i class="fa-solid fa-shield-halved"></i>
            </span>
            Security
          </h2>
          <p class="privacy-card__intro">
            We take security seriously and apply industry-standard practices to protect
            your data.
          </p>
          <ul class="privacy-list">
            <li>
              <i class="fa-solid fa-lock"></i>
              <span><strong>Encryption in transit.</strong> All data between your browser
              and our servers is transmitted over HTTPS using TLS 1.2 or higher.</span>
            </li>
            <li>
              <i class="fa-solid fa-key"></i>
              <span><strong>Password hashing.</strong> Passwords are hashed using
              <code>bcrypt</code> with a high cost factor. We never store or log
              plain-text passwords.</span>
            </li>
            <li>
              <i class="fa-solid fa-database"></i>
              <span><strong>Encrypted backups.</strong> Database backups are encrypted
              at rest and stored in isolated, access-controlled environments.</span>
            </li>
            <li>
              <i class="fa-solid fa-triangle-exclamation"></i>
              <span><strong>Breach notification.</strong> If a breach affecting your data
              occurs, we will notify you within <strong>72 hours</strong> of becoming
              aware, consistent with GDPR Article 33 obligations.</span>
            </li>
          </ul>
          <div class="privacy-callout">
            <i class="fa-solid fa-circle-info"></i>
            <p>
              No system is 100% secure. If you discover a vulnerability, please report it
              responsibly to
              <a href="mailto:a.ozelbicer@gmail.com" class="privacy-link">a.ozelbicer@gmail.com</a>
              and we will respond promptly.
            </p>
          </div>
        </article>

        <!-- 6. Cookies -->
        <article class="privacy-card" id="cookies" data-animate>
          <h2 class="privacy-card__heading">
            <span class="privacy-card__icon">
              <i class="fa-solid fa-cookie-bite"></i>
            </span>
            Cookies
          </h2>
          <p class="privacy-card__intro">
            We use essential cookies to operate the platform. With your consent, we may
            also use analytics and advertising technologies, including Google Analytics
            and Google AdSense.
          </p>

          <div class="privacy-table-wrap">
            <table class="privacy-table">
              <thead>
                <tr>
                  <th>Cookie</th>
                  <th>Purpose</th>
                  <th>Duration</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><code>PHPSESSID</code></td>
                  <td>Maintains your login session</td>
                  <td>Session (deleted when you close your browser)</td>
                </tr>
                <tr>
                  <td><code>csrf_token</code></td>
                  <td>Protects against cross-site request forgery</td>
                  <td>Session</td>
                </tr>
                <tr>
                  <td><code>remember_me</code></td>
                  <td>Keeps you logged in if you check "remember me"</td>
                  <td>30 days</td>
                </tr>
                <tr>
                  <td><code>Google Analytics</code></td>
                  <td>Helps us understand aggregate site usage and improve content</td>
                  <td>Varies by Google settings</td>
                </tr>
                <tr>
                  <td><code>Google AdSense</code></td>
                  <td>May be used to serve, measure, and limit ads if advertising is enabled</td>
                  <td>Varies by Google settings</td>
                </tr>
              </tbody>
            </table>
          </div>

          <p style="margin-top:16px;">
            You can accept or reject optional cookies in our cookie banner. You can also
            disable cookies in your browser settings, but core features — including
            logging in and creating boards — rely on the session cookie and will not
            work without it. Google explains how it uses advertising cookies at
            <a href="https://policies.google.com/technologies/ads" rel="noopener noreferrer" target="_blank" class="privacy-link">Google Advertising Technologies</a>.
          </p>
        </article>

        <!-- 7. Your Rights -->
        <article class="privacy-card" id="rights" data-animate>
          <h2 class="privacy-card__heading">
            <span class="privacy-card__icon">
              <i class="fa-solid fa-scale-balanced"></i>
            </span>
            Your Rights
          </h2>
          <p class="privacy-card__intro">
            Under the GDPR and similar regulations, you have meaningful control over
            your personal data. We honour all of the following rights.
          </p>

          <div class="privacy-rights-grid">
            <div class="privacy-right-card">
              <div class="privacy-right-card__icon"><i class="fa-solid fa-eye"></i></div>
              <h3>Access</h3>
              <p>Request a copy of all personal data we hold about you.</p>
            </div>
            <div class="privacy-right-card">
              <div class="privacy-right-card__icon"><i class="fa-solid fa-pen-to-square"></i></div>
              <h3>Rectification</h3>
              <p>Ask us to correct inaccurate or incomplete information.</p>
            </div>
            <div class="privacy-right-card">
              <div class="privacy-right-card__icon"><i class="fa-solid fa-trash"></i></div>
              <h3>Erasure</h3>
              <p>Delete your account and personal data at any time from your settings page.</p>
            </div>
            <div class="privacy-right-card">
              <div class="privacy-right-card__icon"><i class="fa-solid fa-hand"></i></div>
              <h3>Objection</h3>
              <p>Object to processing of your data for analytics or marketing purposes.</p>
            </div>
            <div class="privacy-right-card">
              <div class="privacy-right-card__icon"><i class="fa-solid fa-file-export"></i></div>
              <h3>Portability</h3>
              <p>Receive your data in a structured, machine-readable format.</p>
            </div>
            <div class="privacy-right-card">
              <div class="privacy-right-card__icon"><i class="fa-solid fa-pause"></i></div>
              <h3>Restriction</h3>
              <p>Ask us to limit processing while a dispute is being resolved.</p>
            </div>
          </div>

          <p style="margin-top:24px;">
            To exercise any of these rights, email us at
            <a href="mailto:a.ozelbicer@gmail.com" class="privacy-link">a.ozelbicer@gmail.com</a>.
            We will respond within <strong>30 days</strong>. If you believe your rights
            have not been respected, you have the right to lodge a complaint with your
            local data protection authority.
          </p>
        </article>

        <!-- 8. Contact Us -->
        <article class="privacy-card privacy-card--contact" id="contact" data-animate>
          <h2 class="privacy-card__heading">
            <span class="privacy-card__icon">
              <i class="fa-solid fa-envelope"></i>
            </span>
            Contact Us
          </h2>
          <p class="privacy-card__intro">
            Have a question about this policy, want to exercise your rights, or spotted
            something we could do better? We would love to hear from you.
          </p>
          <div class="privacy-contact-box">
            <div class="privacy-contact-box__icon">
              <i class="fa-solid fa-envelope"></i>
            </div>
            <div>
              <p class="privacy-contact-box__label t-label">Email us</p>
              <a href="mailto:a.ozelbicer@gmail.com" class="privacy-contact-box__email privacy-link">
                a.ozelbicer@gmail.com
              </a>
              <p class="t-caption text-muted" style="margin-top:6px;">
                We aim to respond to all privacy enquiries within 2 business days.
              </p>
            </div>
          </div>
          <p style="margin-top:20px; font-size:14px; color:var(--muted);">
            This policy may be updated from time to time. When we make material changes
            we will notify registered users by email and update the "last updated" date
            at the top of this page. Continued use of Visual Design Journey after
            changes take effect constitutes acceptance of the revised policy.
          </p>
        </article>

      </div><!-- /privacy-content -->
    </div><!-- /privacy-body__inner -->
  </section>

</main>

<!-- ── PAGE-SCOPED STYLES ─────────────────────────────────────────────────── -->
<style>
/* ── Hero ────────────────────────────────────────────────────────────────── */
.privacy-hero {
  background: linear-gradient(160deg, var(--accent-soft) 0%, var(--bg) 60%);
  border-bottom: 0.5px solid var(--border);
  padding: 72px 0 56px;
  text-align: center;
}
.privacy-hero__inner {
  max-width: 680px;
  margin-inline: auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}
.privacy-hero__badge {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 5px 14px;
  background: var(--accent-soft);
  color: var(--accent);
  border-radius: var(--r-pill);
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  border: 0.5px solid color-mix(in srgb, var(--accent) 30%, transparent);
}
.privacy-hero__title { color: var(--text); }
.privacy-hero__sub { color: var(--muted); max-width: 520px; }
.privacy-hero__date {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--muted);
  background: var(--surface);
  border: 0.5px solid var(--border);
  border-radius: var(--r-pill);
  padding: 6px 16px;
}
.privacy-hero__date i { color: var(--accent); }

/* ── Body layout ─────────────────────────────────────────────────────────── */
.privacy-body { padding: 64px 0 96px; }
.privacy-body__inner {
  display: grid;
  grid-template-columns: 280px 1fr;
  grid-template-rows: auto 1fr;
  gap: 0 48px;
  align-items: start;
}

/* ── Sidebar TOC ─────────────────────────────────────────────────────────── */
.privacy-toc {
  grid-column: 1;
  grid-row: 1 / 3;
  position: sticky;
  top: calc(var(--nav-h) + 24px);
  background: var(--surface);
  border: 0.5px solid var(--border);
  border-radius: var(--r-lg);
  padding: 24px 20px;
}
.privacy-toc__label {
  color: var(--muted);
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 0.5px solid var(--border);
}
.privacy-toc__list { display: flex; flex-direction: column; gap: 2px; }
.privacy-toc__link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  border-radius: var(--r-md);
  font-size: 13.5px;
  font-weight: 500;
  color: var(--muted);
  transition: background 0.15s, color 0.15s;
}
.privacy-toc__link i {
  width: 16px;
  text-align: center;
  color: var(--accent);
  font-size: 13px;
  flex-shrink: 0;
}
.privacy-toc__link:hover {
  background: var(--accent-soft);
  color: var(--text);
}
.privacy-toc__link.is-active {
  background: var(--accent-soft);
  color: var(--accent);
  font-weight: 600;
}

/* ── Mobile pill nav ─────────────────────────────────────────────────────── */
.privacy-toc-mobile { display: none; }

/* ── Content ─────────────────────────────────────────────────────────────── */
.privacy-content {
  grid-column: 2;
  grid-row: 1 / 3;
  display: flex;
  flex-direction: column;
  gap: 32px;
}

/* ── Section cards ───────────────────────────────────────────────────────── */
.privacy-card {
  background: var(--bg);
  border: 0.5px solid var(--border);
  border-radius: var(--r-xl);
  padding: 40px 40px 36px;
  scroll-margin-top: calc(var(--nav-h) + 32px);

  /* Animate-in state */
  opacity: 0;
  transform: translateY(20px);
  transition: opacity 0.45s ease, transform 0.45s ease;
}
.privacy-card.visible {
  opacity: 1;
  transform: translateY(0);
}
.privacy-card__heading {
  display: flex;
  align-items: center;
  gap: 14px;
  font-family: var(--font-display);
  font-size: 22px;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 16px;
}
.privacy-card__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border-radius: var(--r-md);
  background: var(--accent-soft);
  color: var(--accent);
  font-size: 17px;
  flex-shrink: 0;
}
.privacy-card__intro {
  font-size: 15px;
  color: var(--muted);
  margin-bottom: 24px;
  line-height: 1.65;
}

/* ── Highlight grid (collect section) ───────────────────────────────────── */
.privacy-highlight-grid {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.privacy-highlight {
  display: flex;
  gap: 16px;
  background: var(--surface);
  border: 0.5px solid var(--border);
  border-radius: var(--r-lg);
  padding: 20px;
}
.privacy-highlight__icon {
  display: flex;
  align-items: flex-start;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: var(--r-md);
  background: var(--accent-soft);
  color: var(--accent);
  font-size: 15px;
  flex-shrink: 0;
  padding-top: 9px;
}
.privacy-highlight__title {
  font-family: var(--font-display);
  font-size: 15px;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 6px;
}
.privacy-highlight__desc {
  font-size: 14px;
  color: var(--muted);
  line-height: 1.6;
}

/* ── List style ──────────────────────────────────────────────────────────── */
.privacy-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-top: 8px;
}
.privacy-list li {
  display: flex;
  gap: 14px;
  align-items: flex-start;
  font-size: 15px;
  line-height: 1.65;
  color: var(--text);
}
.privacy-list li > i:first-child {
  color: var(--accent);
  font-size: 14px;
  margin-top: 4px;
  flex-shrink: 0;
}

/* ── Callout box ─────────────────────────────────────────────────────────── */
.privacy-callout {
  display: flex;
  gap: 14px;
  align-items: flex-start;
  background: var(--accent-soft);
  border: 0.5px solid color-mix(in srgb, var(--accent) 25%, transparent);
  border-radius: var(--r-lg);
  padding: 16px 20px;
  margin-top: 24px;
  font-size: 14px;
  color: var(--text);
  line-height: 1.6;
}
.privacy-callout > i {
  color: var(--accent);
  font-size: 16px;
  margin-top: 2px;
  flex-shrink: 0;
}

/* ── Table ───────────────────────────────────────────────────────────────── */
.privacy-table-wrap {
  overflow-x: auto;
  border-radius: var(--r-lg);
  border: 0.5px solid var(--border);
  margin-top: 8px;
}
.privacy-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}
.privacy-table thead { background: var(--surface); }
.privacy-table th {
  text-align: left;
  padding: 12px 16px;
  font-weight: 600;
  font-size: 12px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--muted);
  border-bottom: 0.5px solid var(--border);
}
.privacy-table td {
  padding: 14px 16px;
  color: var(--text);
  border-bottom: 0.5px solid var(--border);
  vertical-align: top;
}
.privacy-table tbody tr:last-child td { border-bottom: none; }
.privacy-table code {
  background: var(--surface-hi);
  border-radius: var(--r-sm);
  padding: 2px 7px;
  font-family: 'Menlo', 'Monaco', monospace;
  font-size: 12px;
  color: var(--accent-dim);
}

/* ── Rights grid ─────────────────────────────────────────────────────────── */
.privacy-rights-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-top: 8px;
}
.privacy-right-card {
  background: var(--surface);
  border: 0.5px solid var(--border);
  border-radius: var(--r-lg);
  padding: 20px 18px;
  text-align: center;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.privacy-right-card:hover {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 12%, transparent);
}
.privacy-right-card__icon {
  width: 44px;
  height: 44px;
  border-radius: var(--r-md);
  background: var(--accent-soft);
  color: var(--accent);
  font-size: 17px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 12px;
}
.privacy-right-card h3 {
  font-family: var(--font-display);
  font-size: 14px;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 6px;
}
.privacy-right-card p {
  font-size: 13px;
  color: var(--muted);
  line-height: 1.5;
}

/* ── Contact section ─────────────────────────────────────────────────────── */
.privacy-card--contact {
  background: linear-gradient(135deg, var(--accent-soft) 0%, var(--bg) 100%);
  border-color: color-mix(in srgb, var(--accent) 30%, transparent);
}
.privacy-contact-box {
  display: flex;
  align-items: flex-start;
  gap: 18px;
  background: var(--bg);
  border: 0.5px solid var(--border);
  border-radius: var(--r-lg);
  padding: 22px 24px;
  margin-top: 8px;
}
.privacy-contact-box__icon {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: var(--accent-soft);
  color: var(--accent);
  font-size: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.privacy-contact-box__label { color: var(--muted); margin-bottom: 4px; }
.privacy-contact-box__email {
  font-family: var(--font-display);
  font-size: 17px;
  font-weight: 600;
}

/* ── Link ────────────────────────────────────────────────────────────────── */
.privacy-link {
  color: var(--accent);
  text-decoration: underline;
  text-underline-offset: 3px;
  transition: opacity 0.15s;
}
.privacy-link:hover { opacity: 0.75; }

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 1024px) {
  .privacy-rights-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 860px) {
  .privacy-body__inner {
    grid-template-columns: 1fr;
    grid-template-rows: auto auto;
  }
  .privacy-toc { display: none; }
  .privacy-toc-mobile {
    display: block;
    grid-column: 1;
    grid-row: 1;
    margin-bottom: 32px;
    position: sticky;
    top: var(--nav-h);
    z-index: 10;
    background: var(--bg);
    padding: 12px 0;
    border-bottom: 0.5px solid var(--border);
    margin-left: calc(-1 * var(--margin-tab));
    margin-right: calc(-1 * var(--margin-tab));
    padding-left: var(--margin-tab);
    padding-right: var(--margin-tab);
  }
  .privacy-toc-mobile__scroll {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding-bottom: 2px;
  }
  .privacy-toc-mobile__scroll::-webkit-scrollbar { display: none; }
  .privacy-toc-mobile__pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: var(--r-pill);
    background: var(--surface);
    border: 0.5px solid var(--border);
    font-size: 12.5px;
    font-weight: 500;
    color: var(--muted);
    white-space: nowrap;
    flex-shrink: 0;
    transition: background 0.15s, color 0.15s, border-color 0.15s;
  }
  .privacy-toc-mobile__pill i { color: var(--accent); font-size: 11px; }
  .privacy-toc-mobile__pill:hover,
  .privacy-toc-mobile__pill.is-active {
    background: var(--accent-soft);
    color: var(--accent);
    border-color: color-mix(in srgb, var(--accent) 30%, transparent);
  }
  .privacy-content { grid-column: 1; grid-row: 2; }
  .privacy-card { padding: 28px 24px; }
}

@media (max-width: 640px) {
  .privacy-hero { padding: 48px 0 40px; }
  .privacy-rights-grid { grid-template-columns: 1fr 1fr; }
  .privacy-toc-mobile {
    margin-left: calc(-1 * var(--margin-mob));
    margin-right: calc(-1 * var(--margin-mob));
    padding-left: var(--margin-mob);
    padding-right: var(--margin-mob);
  }
}

@media (max-width: 480px) {
  .privacy-rights-grid { grid-template-columns: 1fr; }
  .privacy-highlight { flex-direction: column; gap: 10px; }
  .privacy-card { padding: 22px 18px; }
  .privacy-contact-box { flex-direction: column; }
}
</style>

<!-- ── TOC active-state script ────────────────────────────────────────────── -->
<script>
(function () {
  'use strict';

  const sections  = Array.from(document.querySelectorAll('.privacy-card[id]'));
  const tocLinks  = Array.from(document.querySelectorAll('.privacy-toc__link'));
  const mobPills  = Array.from(document.querySelectorAll('.privacy-toc-mobile__pill'));
  const navH      = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--nav-h')) || 60;

  function setActive (id) {
    [...tocLinks, ...mobPills].forEach(function (el) {
      const href = el.getAttribute('href');
      if (href === '#' + id) {
        el.classList.add('is-active');
      } else {
        el.classList.remove('is-active');
      }
    });
  }

  // Scroll-spy via IntersectionObserver
  const observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        setActive(entry.target.id);
      }
    });
  }, { rootMargin: '-' + (navH + 40) + 'px 0px -55% 0px', threshold: 0 });

  sections.forEach(function (s) { observer.observe(s); });

  // Scroll active mobile pill into view on click
  mobPills.forEach(function (pill) {
    pill.addEventListener('click', function () {
      setTimeout(function () {
        pill.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
      }, 80);
    });
  });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
