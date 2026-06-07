<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (vdjRenderLocalizedStaticPage('sponsor')) exit;

session_start_if_not_started();

$pageTitle    = 'Partnerships — Visual Design Journey';
$activeNav    = 'sponsor';
$metaDesc     = 'Partner with Visual Design Journey through tasteful sponsorships, newsletter placements, sponsored boards, and design-tool features.';
$canonicalUrl = publicUrl('/sponsor');

require_once __DIR__ . '/includes/header.php';
?>
<main class="page-wrap" id="main-content">
  <div class="container" style="max-width:1120px;">
    <section class="sponsor-hero" data-animate>
      <p class="eyebrow"><i class="fa-solid fa-handshake"></i> Partnerships</p>
      <h1>Monetization that fits a curated design archive.</h1>
      <p>
        We removed intrusive ad-network scripts. The cleaner path is sponsorship:
        tasteful placements for design tools, studios, courses, newsletters, and creative products.
      </p>
    </section>

    <section class="sponsor-grid" aria-label="Partnership formats">
      <?php
      $plans = [
          ['icon' => 'fa-newspaper', 'title' => 'Newsletter placement', 'desc' => 'A short native mention in the weekly curation email. Best for tools, books, courses, and launch campaigns.'],
          ['icon' => 'fa-border-all', 'title' => 'Sponsored board', 'desc' => 'A curated board built around a campaign, movement, tool, or design resource, clearly marked as sponsored.'],
          ['icon' => 'fa-star', 'title' => 'Featured resource', 'desc' => 'A persistent slot on relevant archive, colors, glossary, or blog pages. No popups, no layout-breaking iframes.'],
          ['icon' => 'fa-pen-nib', 'title' => 'Editorial collaboration', 'desc' => 'Long-form article, design guide, case study, or curated collection with on-page SEO and social assets.'],
      ];
      foreach ($plans as $plan): ?>
      <article class="sponsor-plan" data-animate>
        <i class="fa-solid <?= e($plan['icon']) ?>"></i>
        <h2><?= e($plan['title']) ?></h2>
        <p><?= e($plan['desc']) ?></p>
      </article>
      <?php endforeach; ?>
    </section>

    <section class="sponsor-payment-note" data-animate>
      <div>
        <h2>Payment routes for Türkiye</h2>
        <p>
          For Turkish payments, the practical v1 options are invoice/manual transfer,
          iyzico or PayTR payment links, and later a full checkout integration.
          This keeps the site stable now while leaving a clean upgrade path.
        </p>
      </div>
      <ul>
        <li><strong>Fastest:</strong> manual invoice + bank transfer.</li>
        <li><strong>Best local checkout:</strong> iyzico or PayTR payment link/API.</li>
        <li><strong>Global partners:</strong> Wise/business wire where available.</li>
      </ul>
    </section>

    <section class="sponsor-form-wrap" data-animate>
      <div>
        <p class="eyebrow">Partner inquiry</p>
        <h2>Tell us what you want to promote.</h2>
        <p>We will reply by email with placement options and pricing.</p>
      </div>
      <form id="sponsor-form" class="sponsor-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label>
          Name
          <input class="form-input" name="name" required maxlength="120">
        </label>
        <label>
          Email
          <input class="form-input" type="email" name="email" required maxlength="190">
        </label>
        <label>
          Company / product
          <input class="form-input" name="company" maxlength="160">
        </label>
        <label>
          Budget range
          <select class="form-input" name="budget">
            <option value="manual">Manual quote</option>
            <option value="starter">Starter placement</option>
            <option value="campaign">Campaign package</option>
            <option value="annual">Annual partnership</option>
          </select>
        </label>
        <label class="sponsor-form__full">
          Message
          <textarea class="form-input" name="message" rows="5" required maxlength="1200"></textarea>
        </label>
        <button class="btn btn--primary sponsor-form__full" type="submit">
          <i class="fa-solid fa-paper-plane"></i> Send inquiry
        </button>
        <p id="sponsor-form-msg" class="sponsor-form__full" role="status"></p>
      </form>
    </section>
  </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
