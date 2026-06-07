<?php
/**
 * includes/footer.php
 * Global footer.
 */
?>
</main><!-- /page-wrap -->

<footer class="footer" role="contentinfo">
  <div class="container">

    <!-- Top row: brand + nav columns -->
    <div class="footer__grid">

      <!-- Brand -->
      <div class="footer__brand">
        <p style="font-family:var(--font-display);font-weight:700;font-size:17px;letter-spacing:-0.02em;margin-bottom:10px;">
          Visual Design<span style="color:var(--accent);"> Journey</span>
        </p>
        <p style="font-size:13px;color:var(--muted);max-width:240px;line-height:1.6;">
          <?= e(__('footer.brand_copy', 'A gallery for visual designers and curators to share mood boards and discover creative inspiration.')) ?>
        </p>
        <div style="display:flex;gap:12px;margin-top:20px;">
          <!-- href assembled by JS to prevent bot scraping -->
          <a href="#" data-contact="wa" target="_blank" rel="noopener"
             class="footer__social-btn" aria-label="Contact via WhatsApp" data-tip="WhatsApp only">
            <i class="fa-brands fa-whatsapp"></i>
          </a>
          <a href="#" data-contact="mail"
             class="footer__social-btn" aria-label="Send us an email" data-tip="Email us">
            <i class="fa-solid fa-envelope"></i>
          </a>
        </div>
        <a href="https://www.buymeacoffee.com/atao"
           target="_blank"
           rel="noopener noreferrer"
           aria-label="Buy me a coffee"
           style="display:inline-flex;margin-top:16px;line-height:0;">
          <img src="https://img.buymeacoffee.com/button-api/?text=Buy%20me%20a%20coffee&emoji=%E2%98%95&slug=atao&button_colour=BD5FFF&font_colour=ffffff&font_family=Inter&outline_colour=000000&coffee_colour=FFDD00"
               alt="Buy me a coffee"
               width="217"
               height="60"
               loading="lazy"
               style="width:180px;max-width:100%;height:auto;border:0;display:block;">
        </a>
      </div>

      <!-- Explore -->
      <div>
        <p class="footer__col-heading">
          <i class="fa-solid fa-compass" style="color:var(--accent);margin-right:6px;"></i><?= e(__('footer.explore', 'Explore')) ?>
        </p>
        <ul class="footer__col-links">
          <li><a href="<?= e(vdj_localized_url('/')) ?>" class="footer__link"><i class="fa-solid fa-border-all fa-fw"></i><?= e(__('nav.explore', 'Boards')) ?></a></li>
          <li><a href="<?= e(vdj_localized_url('/curators')) ?>" class="footer__link"><i class="fa-solid fa-users fa-fw"></i><?= e(__('nav.curators', 'Curators')) ?></a></li>
          <li><a href="<?= e(vdj_localized_url('/trends')) ?>" class="footer__link"><i class="fa-solid fa-arrow-trend-up fa-fw"></i><?= e(__('nav.trends', 'Trends')) ?></a></li>
          <li><a href="<?= e(vdj_localized_url('/vibe')) ?>" class="footer__link"><i class="fa-solid fa-wand-magic-sparkles fa-fw"></i><?= e(__('nav.vibe', 'Vibe')) ?></a></li>
          <li><a href="<?= e(vdj_localized_url('/archive')) ?>" class="footer__link"><i class="fa-solid fa-box-archive fa-fw"></i><?= e(__('nav.archive', 'Archive')) ?></a></li>
          <li><a href="<?= e(vdj_localized_url('/archive')) ?>" class="footer__link"><i class="fa-solid fa-layer-group fa-fw"></i><?= e(__('footer.design_movements', 'Design Movements')) ?></a></li>
          <li><a href="<?= e(vdj_localized_url('/blog')) ?>" class="footer__link"><i class="fa-solid fa-pen-nib fa-fw"></i><?= e(__('nav.blog', 'Blog')) ?></a></li>
          <li><a href="/tools" class="footer__link"><i class="fa-solid fa-screwdriver-wrench fa-fw"></i>Design Tools</a></li>
          <li><a href="<?= e(vdj_localized_url('/glossary')) ?>" class="footer__link"><i class="fa-solid fa-book-open fa-fw"></i><?= e(__('nav.glossary', 'Glossary')) ?></a></li>
          <li><a href="<?= e(vdj_localized_url('/colors')) ?>" class="footer__link"><i class="fa-solid fa-palette fa-fw"></i><?= e(__('footer.color_palettes', 'Color Palettes')) ?></a></li>
          <li><a href="<?= e(vdj_localized_url('/feed.xml')) ?>" class="footer__link"><i class="fa-solid fa-rss fa-fw"></i><?= e(__('footer.rss_feed', 'RSS Feed')) ?></a></li>
          <li><a href="<?= e(vdj_localized_url('/submit-source')) ?>" class="footer__link"><i class="fa-solid fa-bookmark fa-fw"></i><?= e(__('nav.submit', 'Submit Reference')) ?></a></li>
        </ul>
      </div>

      <!-- Account -->
      <div>
        <p class="footer__col-heading">
          <i class="fa-solid fa-circle-user" style="color:var(--accent);margin-right:6px;"></i><?= e(__('footer.account', 'Account')) ?>
        </p>
        <ul class="footer__col-links">
          <li><a href="<?= e(vdj_localized_url('/register')) ?>" class="footer__link"><i class="fa-solid fa-user-plus fa-fw"></i><?= e(__('nav.create_account', 'Register')) ?></a></li>
          <li><a href="<?= e(vdj_localized_url('/login')) ?>"    class="footer__link"><i class="fa-solid fa-right-to-bracket fa-fw"></i><?= e(__('nav.sign_in', 'Sign In')) ?></a></li>
          <li><a href="<?= e(vdj_localized_url('/create')) ?>"   class="footer__link"><i class="fa-solid fa-plus fa-fw"></i><?= e(__('nav.create_board', 'Create Board')) ?></a></li>
          <li><a href="<?= e(vdj_localized_url('/settings')) ?>" class="footer__link"><i class="fa-solid fa-gear fa-fw"></i><?= e(__('nav.settings', 'Settings')) ?></a></li>
        </ul>
      </div>

      <!-- Newsletter -->
      <div>
        <p class="footer__col-heading">
          <i class="fa-solid fa-envelope" style="color:var(--accent);margin-right:6px;"></i><?= e(__('footer.newsletter', 'Newsletter')) ?>
        </p>
        <p style="font-size:12px;color:var(--muted);margin-bottom:12px;line-height:1.5;"><?= e(__('footer.newsletter_copy', 'Weekly curated design inspiration, delivered to your inbox.')) ?></p>
        <form id="newsletter-form" style="display:flex;flex-direction:column;gap:8px;">
          <input type="email" name="email" placeholder="<?= e(__('footer.email_placeholder', 'your@email.com')) ?>"
                 class="form-input" style="font-size:13px;height:36px;"
                 required aria-label="Email address"/>
          <button type="submit" class="btn btn--primary btn--sm" style="width:100%;justify-content:center;">
            <?= e(__('footer.subscribe', 'Subscribe')) ?>
          </button>
          <p id="newsletter-msg" style="font-size:12px;color:var(--accent);display:none;"></p>
        </form>
      </div>

      <!-- Legal -->
      <div>
        <p class="footer__col-heading">
          <i class="fa-solid fa-scale-balanced" style="color:var(--accent);margin-right:6px;"></i><?= e(__('footer.legal', 'Legal')) ?>
        </p>
        <ul class="footer__col-links">
          <li><a href="<?= e(vdj_localized_url('/privacy')) ?>" class="footer__link"><i class="fa-solid fa-shield-halved fa-fw"></i><?= e(__('footer.privacy', 'Privacy Policy')) ?></a></li>
          <li><a href="<?= e(vdj_localized_url('/terms')) ?>" class="footer__link"><i class="fa-solid fa-file-contract fa-fw"></i><?= e(__('footer.terms', 'Terms of Service')) ?></a></li>
          <li><a href="<?= e(vdj_localized_url('/about')) ?>" class="footer__link"><i class="fa-solid fa-circle-info fa-fw"></i><?= e(__('footer.about', 'About')) ?></a></li>
          <li><a href="<?= e(vdj_localized_url('/editorial-policy')) ?>" class="footer__link"><i class="fa-solid fa-pen-ruler fa-fw"></i><?= e(__('footer.editorial_policy', 'Editorial Policy')) ?></a></li>
          <li><a href="<?= e(vdj_localized_url('/premium')) ?>" class="footer__link"><i class="fa-solid fa-crown fa-fw"></i><?= e(__('footer.premium_roadmap', 'Premium Roadmap')) ?></a></li>
          <li><a href="<?= e(vdj_localized_url('/sponsor')) ?>" class="footer__link"><i class="fa-solid fa-handshake fa-fw"></i><?= e(__('footer.partnerships', 'Partnerships')) ?></a></li>
          <li><a href="<?= e(vdj_localized_url('/contact')) ?>" class="footer__link"><i class="fa-solid fa-envelope fa-fw"></i><?= e(__('footer.contact', 'Contact')) ?></a></li>
        </ul>
      </div>

    </div>

    <!-- Ad: Leaderboard (728×90, desktop) / Mobile Banner (320×50, mobile) -->
    <?php if (function_exists('vdj_ad')): ?>
    <div style="padding:24px 0 8px;border-top:1px solid var(--border);text-align:center;">
      <?= vdj_ad('leaderboard') ?>
      <?= vdj_ad('mobile_320x50') ?>
    </div>
    <?php endif; ?>

    <!-- Bottom bar -->
    <div class="footer__bottom">
      <p style="font-size:12px;color:var(--muted);">
        © <?= date('Y') ?> Visual Design Journey. <?= e(__('footer.rights', 'All rights reserved.')) ?>
      </p>
      <p style="font-size:12px;color:var(--muted);display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
        <i class="fa-solid fa-location-dot" style="color:var(--accent);"></i>İzmir, Türkiye
        &nbsp;·&nbsp;
        <i class="fa-solid fa-heart" style="color:var(--accent);"></i><?= e(__('footer.made', 'Made with love for visual design')) ?>
      </p>
    </div>

  </div>
</footer>

<script src="/assets/js/main.min.js?v=1.9" defer></script>
<script>
(function () {
  var banner = document.getElementById('cookie-banner');
  if (!banner || localStorage.getItem('vdj_cookie_consent')) return;
  banner.hidden = false;

  function updateConsent(value) {
    localStorage.setItem('vdj_cookie_consent', value);
    if (typeof gtag === 'function') {
      gtag('consent', 'update', {
        ad_storage: value === 'accepted' ? 'granted' : 'denied',
        ad_user_data: value === 'accepted' ? 'granted' : 'denied',
        ad_personalization: value === 'accepted' ? 'granted' : 'denied',
        analytics_storage: value === 'accepted' ? 'granted' : 'denied'
      });
    }
    banner.hidden = true;
  }

  document.getElementById('cookie-accept')?.addEventListener('click', function () { updateConsent('accepted'); });
  document.getElementById('cookie-reject')?.addEventListener('click', function () { updateConsent('rejected'); });
})();
</script>

<?php if (function_exists('vdj_ad')) echo vdj_ad('social_bar'); ?>
</body>
</html>
