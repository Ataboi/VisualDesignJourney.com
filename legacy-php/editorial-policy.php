<?php
require_once __DIR__ . '/includes/functions.php';
if (vdjRenderLocalizedStaticPage('editorial-policy')) exit;

$pageTitle = 'Editorial Policy — Visual Design Journey';
$metaDesc = 'How Visual Design Journey curates, edits, attributes, and reviews design inspiration, blog articles, references, and sponsored content.';
$activeNav = '';
$canonicalUrl = publicUrl('/editorial-policy.php');

require_once __DIR__ . '/includes/header.php';
?>

<main class="page-wrap" id="main-content">
  <section class="container" style="max-width:860px;padding:64px 0 84px;">
    <p class="t-label text-muted" style="text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px;">Editorial Policy</p>
    <h1 class="t-display" style="margin-bottom:18px;">How We Curate And Publish</h1>
    <p class="t-body-lg text-muted" style="line-height:1.75;margin-bottom:32px;">
      Visual Design Journey exists to help people learn from visual culture. This page explains how we choose references, handle attribution, and separate editorial work from commercial partnerships.
    </p>

    <div style="display:grid;gap:22px;">
      <section class="admin-card" style="padding:24px;">
        <h2 style="font-size:20px;margin-bottom:10px;">Curation Standards</h2>
        <p style="color:var(--muted);line-height:1.7;margin:0;">
          References are selected for educational value, visual clarity, historical relevance, or usefulness to practicing designers. We avoid publishing misleading, deceptive, hateful, sexually explicit, or unsafe material.
        </p>
      </section>

      <section class="admin-card" style="padding:24px;">
        <h2 style="font-size:20px;margin-bottom:10px;">Attribution And Copyright</h2>
        <p style="color:var(--muted);line-height:1.7;margin:0;">
          We make reasonable efforts to credit sources and link to original pages where available. If you own work that appears on the site and want credit corrected or content removed, contact us and we will review the request promptly.
        </p>
      </section>

      <section class="admin-card" style="padding:24px;">
        <h2 style="font-size:20px;margin-bottom:10px;">Advertising And Sponsorship</h2>
        <p style="color:var(--muted);line-height:1.7;margin:0;">
          Advertising and sponsorship may support the site, but paid relationships do not determine editorial conclusions. Sponsored or partner placements are labelled when they appear.
        </p>
      </section>

      <section class="admin-card" style="padding:24px;">
        <h2 style="font-size:20px;margin-bottom:10px;">Corrections</h2>
        <p style="color:var(--muted);line-height:1.7;margin:0;">
          We correct broken links, attribution issues, factual errors, and outdated references when they are found. Send corrections through the <a href="/contact.php">contact page</a>.
        </p>
      </section>
    </div>
  </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
