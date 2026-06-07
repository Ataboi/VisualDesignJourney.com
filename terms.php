<?php
/**
 * terms.php — Terms of Service
 * Visual Design Journey · visualdesignjourney.com
 */

require_once __DIR__ . '/includes/functions.php';
if (vdjRenderLocalizedStaticPage('terms')) exit;

$pageTitle = 'Terms of Service — Visual Design Journey';
$metaDesc  = 'Read the Terms of Service for Visual Design Journey. These terms govern your use of our platform, user content, intellectual property, and more.';
$activeNav = '';

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ── LEGAL PAGE ─────────────────────────────────────────────────────────── */
.legal-wrap {
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: 56px;
  align-items: start;
  padding-bottom: var(--section-gap);
}

/* Sidebar TOC */
.legal-toc {
  position: sticky;
  top: calc(var(--nav-h) + 24px);
}
.legal-toc__label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--muted);
  margin-bottom: 12px;
}
.legal-toc__list {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.legal-toc__link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  border-radius: var(--r-md);
  font-size: 13px;
  font-weight: 500;
  color: var(--muted);
  transition: background 0.15s, color 0.15s;
  line-height: 1.35;
}
.legal-toc__link:hover,
.legal-toc__link.active {
  background: var(--accent-soft);
  color: var(--accent);
}
.legal-toc__link i {
  font-size: 12px;
  width: 14px;
  text-align: center;
  flex-shrink: 0;
  opacity: 0.75;
}

/* Mobile pill nav (hidden on desktop) */
.legal-pill-nav {
  display: none;
  gap: 8px;
  overflow-x: auto;
  padding-bottom: 4px;
  margin-bottom: 36px;
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.legal-pill-nav::-webkit-scrollbar { display: none; }
.legal-pill-nav__item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border-radius: var(--r-pill);
  border: 0.5px solid var(--border);
  font-size: 12px;
  font-weight: 600;
  color: var(--muted);
  white-space: nowrap;
  background: var(--bg);
  transition: border-color 0.15s, color 0.15s, background 0.15s;
  cursor: pointer;
  text-decoration: none;
}
.legal-pill-nav__item:hover,
.legal-pill-nav__item.active {
  border-color: var(--accent);
  color: var(--accent);
  background: var(--accent-soft);
}
.legal-pill-nav__item i { font-size: 11px; }

/* Content */
.legal-content {
  min-width: 0;
}
.legal-section {
  margin-bottom: 56px;
  scroll-margin-top: calc(var(--nav-h) + 32px);
}
.legal-section__heading {
  display: flex;
  align-items: center;
  gap: 12px;
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: var(--text);
  margin-bottom: 20px;
  padding-bottom: 14px;
  border-bottom: 0.5px solid var(--border);
}
.legal-section__heading i {
  width: 36px;
  height: 36px;
  border-radius: var(--r-md);
  background: var(--accent-soft);
  color: var(--accent);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
}
.legal-card {
  background: var(--surface);
  border: 0.5px solid var(--border);
  border-radius: var(--r-lg);
  padding: 24px 28px;
  margin-bottom: 16px;
  opacity: 0;
  transform: translateY(16px);
  transition: opacity 0.45s ease, transform 0.45s ease;
}
.legal-card.in-view {
  opacity: 1;
  transform: translateY(0);
}
.legal-card p {
  font-size: 15px;
  color: var(--muted);
  line-height: 1.75;
  margin-bottom: 14px;
}
.legal-card p:last-child { margin-bottom: 0; }
.legal-card ul {
  list-style: none;
  margin: 10px 0 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.legal-card ul li {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 15px;
  color: var(--muted);
  line-height: 1.65;
}
.legal-card ul li::before {
  content: '';
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--accent);
  flex-shrink: 0;
  margin-top: 9px;
}
.legal-card a {
  color: var(--accent);
  font-weight: 500;
  transition: opacity 0.15s;
}
.legal-card a:hover { opacity: 0.75; }
.legal-card strong {
  font-weight: 600;
  color: var(--text);
}

/* Hero */
.legal-hero {
  text-align: center;
  padding: 72px 0 56px;
  border-bottom: 0.5px solid var(--border);
  margin-bottom: 64px;
}
.legal-hero__eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--accent);
  margin-bottom: 20px;
}
.legal-hero__eyebrow i { font-size: 11px; }
.legal-hero__title {
  font-family: var(--font-display);
  font-size: clamp(32px, 5vw, 52px);
  font-weight: 700;
  letter-spacing: -0.03em;
  line-height: 1.1;
  color: var(--text);
  margin-bottom: 16px;
}
.legal-hero__sub {
  font-size: 17px;
  color: var(--muted);
  max-width: 540px;
  margin-inline: auto;
  line-height: 1.65;
  margin-bottom: 20px;
}
.legal-hero__date {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: var(--outline);
  letter-spacing: 0.06em;
  background: var(--surface);
  border: 0.5px solid var(--border);
  padding: 6px 14px;
  border-radius: var(--r-pill);
}
.legal-hero__date i { font-size: 11px; }

/* Responsive */
@media (max-width: 900px) {
  .legal-wrap {
    grid-template-columns: 1fr;
    gap: 0;
  }
  .legal-toc { display: none; }
  .legal-pill-nav { display: flex; }
}
@media (max-width: 600px) {
  .legal-hero { padding: 48px 0 40px; }
  .legal-card { padding: 18px 20px; }
}
</style>

<main class="page-wrap">
  <div class="container">

    <!-- Hero -->
    <header class="legal-hero">
      <p class="legal-hero__eyebrow">
        <i class="fa-solid fa-scale-balanced"></i>
        Legal
      </p>
      <h1 class="legal-hero__title">Terms of Service</h1>
      <p class="legal-hero__sub">
        These terms govern your access to and use of Visual Design Journey.
        Please read them carefully before using our platform.
      </p>
      <span class="legal-hero__date">
        <i class="fa-regular fa-calendar"></i>
        Effective date: May 1, 2025
      </span>
    </header>

    <!-- Mobile pill TOC -->
    <nav class="legal-pill-nav" aria-label="Jump to section">
      <a href="#acceptance"      class="legal-pill-nav__item"><i class="fa-solid fa-handshake"></i> Acceptance</a>
      <a href="#use"             class="legal-pill-nav__item"><i class="fa-solid fa-laptop-code"></i> Use of Platform</a>
      <a href="#user-content"    class="legal-pill-nav__item"><i class="fa-solid fa-images"></i> User Content</a>
      <a href="#ip"              class="legal-pill-nav__item"><i class="fa-solid fa-copyright"></i> Intellectual Property</a>
      <a href="#prohibited"      class="legal-pill-nav__item"><i class="fa-solid fa-ban"></i> Prohibited Conduct</a>
      <a href="#termination"     class="legal-pill-nav__item"><i class="fa-solid fa-circle-xmark"></i> Termination</a>
      <a href="#disclaimers"     class="legal-pill-nav__item"><i class="fa-solid fa-triangle-exclamation"></i> Disclaimers</a>
      <a href="#contact"         class="legal-pill-nav__item"><i class="fa-solid fa-envelope"></i> Contact</a>
    </nav>

    <!-- Two-column layout -->
    <div class="legal-wrap">

      <!-- Sidebar TOC -->
      <aside class="legal-toc" aria-label="Table of contents">
        <p class="legal-toc__label">On this page</p>
        <ul class="legal-toc__list" role="list">
          <li>
            <a href="#acceptance" class="legal-toc__link">
              <i class="fa-solid fa-handshake"></i>
              Acceptance of Terms
            </a>
          </li>
          <li>
            <a href="#use" class="legal-toc__link">
              <i class="fa-solid fa-laptop-code"></i>
              Use of the Platform
            </a>
          </li>
          <li>
            <a href="#user-content" class="legal-toc__link">
              <i class="fa-solid fa-images"></i>
              User Content
            </a>
          </li>
          <li>
            <a href="#ip" class="legal-toc__link">
              <i class="fa-solid fa-copyright"></i>
              Intellectual Property
            </a>
          </li>
          <li>
            <a href="#prohibited" class="legal-toc__link">
              <i class="fa-solid fa-ban"></i>
              Prohibited Conduct
            </a>
          </li>
          <li>
            <a href="#termination" class="legal-toc__link">
              <i class="fa-solid fa-circle-xmark"></i>
              Termination
            </a>
          </li>
          <li>
            <a href="#disclaimers" class="legal-toc__link">
              <i class="fa-solid fa-triangle-exclamation"></i>
              Disclaimers &amp; Liability
            </a>
          </li>
          <li>
            <a href="#contact" class="legal-toc__link">
              <i class="fa-solid fa-envelope"></i>
              Contact
            </a>
          </li>
        </ul>
      </aside>

      <!-- Content -->
      <div class="legal-content">

        <!-- 1. Acceptance of Terms -->
        <section class="legal-section" id="acceptance">
          <h2 class="legal-section__heading">
            <i class="fa-solid fa-handshake"></i>
            Acceptance of Terms
          </h2>

          <div class="legal-card" data-animate>
            <p>
              By accessing or using Visual Design Journey
              (<strong>visualdesignjourney.com</strong>), you confirm that you have
              read, understood, and agree to be bound by these Terms of Service
              ("<strong>Terms</strong>") and our Privacy Policy. If you do not agree,
              please discontinue use of the platform immediately.
            </p>
            <p>
              These Terms constitute a legally binding agreement between you
              ("<strong>User</strong>") and Visual Design Journey
              ("<strong>we</strong>," "<strong>us</strong>," or "<strong>our</strong>").
              We may update these Terms from time to time. Continued use of the platform
              after changes are posted constitutes acceptance of the revised Terms.
            </p>
          </div>

          <div class="legal-card" data-animate>
            <p>
              <strong>Eligibility.</strong> You must be at least 13 years old to use
              Visual Design Journey. By using the platform, you represent and warrant
              that you meet this age requirement and that all information you provide
              is accurate and current.
            </p>
          </div>
        </section>

        <!-- 2. Use of the Platform -->
        <section class="legal-section" id="use">
          <h2 class="legal-section__heading">
            <i class="fa-solid fa-laptop-code"></i>
            Use of the Platform
          </h2>

          <div class="legal-card" data-animate>
            <p>
              Visual Design Journey is a creative community platform designed for
              designers and visual curators to share mood boards, discover inspiration,
              and connect with peers. You are granted a limited, non-exclusive,
              non-transferable, revocable license to access and use the platform
              solely for its intended purposes and in accordance with these Terms.
            </p>
          </div>

          <div class="legal-card" data-animate>
            <p><strong>Account Responsibilities.</strong> When you create an account, you agree to:</p>
            <ul>
              <li>Provide accurate, current, and complete registration information.</li>
              <li>Maintain the confidentiality of your login credentials and not share them with others.</li>
              <li>Notify us promptly at <a href="mailto:a.ozelbicer@gmail.com">a.ozelbicer@gmail.com</a> if you suspect unauthorized access to your account.</li>
              <li>Accept responsibility for all activity that occurs under your account.</li>
            </ul>
            <p>
              We reserve the right to suspend or terminate accounts that we reasonably
              believe are involved in misuse of the platform or violation of these Terms.
            </p>
          </div>
        </section>

        <!-- 3. User Content -->
        <section class="legal-section" id="user-content">
          <h2 class="legal-section__heading">
            <i class="fa-solid fa-images"></i>
            User Content
          </h2>

          <div class="legal-card" data-animate>
            <p>
              "<strong>User Content</strong>" refers to any text, images, links, mood boards,
              tags, comments, or other materials you submit, post, or display on Visual
              Design Journey. You retain full ownership of your User Content.
            </p>
            <p>
              By posting User Content, you grant Visual Design Journey a worldwide,
              royalty-free, non-exclusive license to use, reproduce, display, and
              distribute that content solely for the purpose of operating and improving
              the platform. This license ends when you delete the content or close
              your account, except where copies have been made for backup or caching
              purposes.
            </p>
          </div>

          <div class="legal-card" data-animate>
            <p><strong>Your Warranties.</strong> By submitting User Content, you represent that:</p>
            <ul>
              <li>You own or have the necessary rights and permissions to post the content.</li>
              <li>The content does not infringe any third-party intellectual property, privacy, or publicity rights.</li>
              <li>The content does not violate any applicable law or regulation.</li>
              <li>You have obtained all required consents from individuals depicted in the content.</li>
            </ul>
          </div>

          <div class="legal-card" data-animate>
            <p>
              We do not pre-screen User Content but reserve the right to remove any
              content that violates these Terms, our community guidelines, or applicable
              law. We are not liable for any User Content posted by others on the platform.
            </p>
          </div>
        </section>

        <!-- 4. Intellectual Property -->
        <section class="legal-section" id="ip">
          <h2 class="legal-section__heading">
            <i class="fa-solid fa-copyright"></i>
            Intellectual Property
          </h2>

          <div class="legal-card" data-animate>
            <p>
              All platform software, design, code, trademarks, logos, and original
              content created by Visual Design Journey — excluding User Content — are
              the exclusive property of Visual Design Journey and are protected by
              applicable copyright, trademark, and other intellectual property laws.
            </p>
            <p>
              You may not copy, modify, distribute, sell, sublicense, or create
              derivative works from any part of the platform without our prior written
              consent, except as expressly permitted by these Terms or applicable law.
            </p>
          </div>

          <div class="legal-card" data-animate>
            <p>
              <strong>Third-Party Content.</strong> Some content displayed on the platform
              may originate from third-party sources. Visual Design Journey does not
              claim ownership of third-party content and makes no representations
              regarding its accuracy or ownership. If you believe content on our
              platform infringes your copyright, please contact us with a detailed
              notice at <a href="mailto:a.ozelbicer@gmail.com">a.ozelbicer@gmail.com</a>.
            </p>
          </div>
        </section>

        <!-- 5. Prohibited Conduct -->
        <section class="legal-section" id="prohibited">
          <h2 class="legal-section__heading">
            <i class="fa-solid fa-ban"></i>
            Prohibited Conduct
          </h2>

          <div class="legal-card" data-animate>
            <p>
              To maintain a respectful and creative community, the following activities
              are strictly prohibited on Visual Design Journey:
            </p>
            <ul>
              <li>Posting content that is hateful, abusive, harassing, discriminatory, or threatening toward any individual or group.</li>
              <li>Uploading content that infringes on the intellectual property rights of others without appropriate authorization.</li>
              <li>Using the platform to distribute spam, unsolicited commercial messages, or deceptive content.</li>
              <li>Attempting to gain unauthorized access to other user accounts, our servers, or associated systems.</li>
              <li>Using automated tools, bots, or scrapers to collect data from the platform without our express written permission.</li>
              <li>Impersonating any person, business, or entity, or misrepresenting your affiliation with any party.</li>
              <li>Engaging in any activity that disrupts, degrades, or interferes with the normal operation of the platform.</li>
              <li>Posting content that promotes or facilitates illegal activities, including fraud, violence, or exploitation.</li>
            </ul>
          </div>

          <div class="legal-card" data-animate>
            <p>
              We reserve the right to investigate suspected violations and, where
              appropriate, cooperate with law enforcement authorities. Violations may
              result in content removal, account suspension, or permanent termination
              without prior notice.
            </p>
          </div>
        </section>

        <!-- 6. Termination -->
        <section class="legal-section" id="termination">
          <h2 class="legal-section__heading">
            <i class="fa-solid fa-circle-xmark"></i>
            Termination
          </h2>

          <div class="legal-card" data-animate>
            <p>
              You may stop using Visual Design Journey and request deletion of your
              account at any time by visiting your account settings or contacting us
              at <a href="mailto:a.ozelbicer@gmail.com">a.ozelbicer@gmail.com</a>.
              Upon account deletion, your profile and public boards will be removed.
              Some information may be retained as required by law or for legitimate
              business purposes such as fraud prevention.
            </p>
          </div>

          <div class="legal-card" data-animate>
            <p>
              We reserve the right to suspend or permanently terminate your access
              to the platform, with or without notice, if we determine in our sole
              discretion that you have violated these Terms, engaged in harmful conduct,
              or if we are required to do so by law.
            </p>
            <p>
              Termination does not limit any other rights or remedies available to us.
              Provisions of these Terms that by their nature should survive termination
              — including ownership clauses, warranty disclaimers, and limitations of
              liability — shall continue to apply.
            </p>
          </div>
        </section>

        <!-- 7. Disclaimers & Liability -->
        <section class="legal-section" id="disclaimers">
          <h2 class="legal-section__heading">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Disclaimers &amp; Liability
          </h2>

          <div class="legal-card" data-animate>
            <p>
              <strong>Platform Provided "As Is."</strong> Visual Design Journey is
              provided on an "<strong>as is</strong>" and "<strong>as available</strong>"
              basis without warranties of any kind, whether express, implied, or
              statutory. We do not warrant that the platform will be uninterrupted,
              error-free, secure, or free of viruses or harmful components.
            </p>
            <p>
              We expressly disclaim all implied warranties, including merchantability,
              fitness for a particular purpose, and non-infringement, to the fullest
              extent permitted by applicable law.
            </p>
          </div>

          <div class="legal-card" data-animate>
            <p>
              <strong>Limitation of Liability.</strong> To the maximum extent permitted
              by law, Visual Design Journey and its operators shall not be liable for
              any indirect, incidental, special, consequential, or punitive damages —
              including loss of data, profits, or goodwill — arising out of or in
              connection with your use of, or inability to use, the platform, even if
              we have been advised of the possibility of such damages.
            </p>
            <p>
              Our total liability to you for any claims arising under these Terms shall
              not exceed the greater of (a) the amount you paid us in the twelve months
              preceding the claim, or (b) fifty US dollars (USD&nbsp;$50).
            </p>
          </div>

          <div class="legal-card" data-animate>
            <p>
              <strong>Indemnification.</strong> You agree to defend, indemnify, and
              hold harmless Visual Design Journey and its operators from any claims,
              damages, losses, liabilities, costs, and expenses (including reasonable
              legal fees) arising from your use of the platform, your User Content,
              or your breach of these Terms.
            </p>
          </div>

          <div class="legal-card" data-animate>
            <p>
              <strong>Governing Law.</strong> These Terms are governed by and construed
              in accordance with applicable law. Any disputes shall be resolved through
              good-faith negotiation; if unresolved, through binding arbitration or
              the courts of competent jurisdiction, as required.
            </p>
          </div>
        </section>

        <!-- 8. Contact -->
        <section class="legal-section" id="contact">
          <h2 class="legal-section__heading">
            <i class="fa-solid fa-envelope"></i>
            Contact
          </h2>

          <div class="legal-card" data-animate>
            <p>
              If you have any questions, concerns, or requests regarding these Terms
              of Service, please reach out to us directly. We aim to respond to all
              inquiries within a reasonable timeframe.
            </p>
            <p>
              <strong>Email:</strong>
              <a href="mailto:a.ozelbicer@gmail.com">a.ozelbicer@gmail.com</a>
            </p>
            <p>
              For privacy-related requests, including data access or deletion, please
              also reference our <a href="/privacy.php">Privacy Policy</a>.
            </p>
          </div>
        </section>

      </div><!-- /legal-content -->
    </div><!-- /legal-wrap -->

  </div><!-- /container -->
</main>

<script>
(function () {
  // ── Animate cards on scroll ──────────────────────────────────────────────
  var cards = document.querySelectorAll('[data-animate]');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('in-view');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    cards.forEach(function (card) { io.observe(card); });
  } else {
    cards.forEach(function (card) { card.classList.add('in-view'); });
  }

  // ── Sidebar TOC active state on scroll ──────────────────────────────────
  var sections = document.querySelectorAll('.legal-section');
  var tocLinks = document.querySelectorAll('.legal-toc__link');
  var pillLinks = document.querySelectorAll('.legal-pill-nav__item');

  function setActive(id) {
    var allLinks = Array.prototype.slice.call(tocLinks).concat(Array.prototype.slice.call(pillLinks));
    allLinks.forEach(function (link) {
      var href = link.getAttribute('href');
      if (href === '#' + id) {
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });
  }

  if ('IntersectionObserver' in window && sections.length) {
    var navH = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--nav-h')) || 60;
    var sectionObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          setActive(entry.target.id);
        }
      });
    }, {
      rootMargin: '-' + (navH + 40) + 'px 0px -60% 0px',
      threshold: 0
    });
    sections.forEach(function (sec) { sectionObserver.observe(sec); });
  }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
