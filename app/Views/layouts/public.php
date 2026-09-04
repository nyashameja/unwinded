<!DOCTYPE html>
<html lang="en" class="<?= e($htmlClass ?? '') ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? config('app.name')) ?><?= isset($pageTitle) ? ' ' . e(config('seo.default_title_suffix', '| Unwinded')) : '' ?></title>

  <?php if (!empty($metaDescription)): ?>
  <meta name="description" content="<?= attr($metaDescription) ?>">
  <?php endif; ?>

  <meta name="robots" content="<?= attr($metaRobots ?? config('seo.robots', 'index,follow')) ?>">

  <?php if (!empty($canonicalUrl)): ?>
  <link rel="canonical" href="<?= attr($canonicalUrl) ?>">
  <?php endif; ?>

  <!-- Open Graph -->
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= attr(config('app.name')) ?>">
  <meta property="og:title" content="<?= attr($pageTitle ?? config('app.name')) ?>">
  <?php if (!empty($metaDescription)): ?>
  <meta property="og:description" content="<?= attr($metaDescription) ?>">
  <?php endif; ?>
  <?php if (!empty($ogImage)): ?>
  <meta property="og:image" content="<?= attr($ogImage) ?>">
  <?php endif; ?>

  <link rel="icon" href="<?= asset('img/favicon.ico') ?>">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">

  <?php if (!empty($headExtra)): ?>
  <?= $headExtra ?>
  <?php endif; ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">

  <?php if (config('app.maintenance_mode')): ?>
  <div class="maintenance-banner">
    <?= e(config('app.maintenance_message', 'Site undergoing maintenance.')) ?>
  </div>
  <?php endif; ?>

  <a href="#main-content" class="skip-link">Skip to main content</a>

  <header class="site-header" role="banner">
    <div class="container">
      <a href="<?= url('/') ?>" class="site-logo" aria-label="<?= attr(config('app.name')) ?> — home">
        <span class="logo-wordmark">Unwinded</span>
      </a>

      <button class="nav-toggle" aria-controls="primary-nav" aria-expanded="false" aria-label="Toggle navigation">
        <span></span><span></span><span></span>
      </button>

      <nav id="primary-nav" class="primary-nav" role="navigation" aria-label="Main navigation">
        <ul>
          <li><a href="<?= url('/experiences') ?>">Experiences</a></li>
          <li><a href="<?= url('/packages') ?>">Packages</a></li>
          <li><a href="<?= url('/events') ?>">Events</a></li>
          <li><a href="<?= url('/gallery') ?>">Gallery</a></li>
          <li><a href="<?= url('/about') ?>">About</a></li>
          <li><a href="<?= url('/contact') ?>">Contact</a></li>
          <li class="nav-cta"><a href="<?= url('/request-a-quote') ?>" class="btn btn-primary">Book an Event</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main id="main-content" tabindex="-1">
    <?php foreach (flash()->getAll() as $type => $messages): ?>
      <?php foreach ($messages as $message): ?>
        <div class="flash flash--<?= e($type) ?>" role="alert">
          <p><?= e($message) ?></p>
          <button class="flash__close" aria-label="Dismiss">&times;</button>
        </div>
      <?php endforeach; ?>
    <?php endforeach; ?>

    <?= $content ?>
  </main>

  <footer class="site-footer" role="contentinfo">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <p class="footer-wordmark">Unwinded</p>
          <p class="footer-tagline"><?= e(config('app.tagline', 'Sip. Paint. Unwind.')) ?></p>
          <div class="social-links">
            <?php if ($ig = config('social.instagram')): ?>
              <a href="<?= attr($ig) ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
              </a>
            <?php endif; ?>
            <?php if ($fb = config('social.facebook')): ?>
              <a href="<?= attr($fb) ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
              </a>
            <?php endif; ?>
          </div>
        </div>

        <nav class="footer-nav" aria-label="Footer navigation">
          <h3>Experiences</h3>
          <ul>
            <li><a href="<?= url('/experiences') ?>">All Experiences</a></li>
            <li><a href="<?= url('/packages') ?>">Packages</a></li>
            <li><a href="<?= url('/events') ?>">Public Events</a></li>
            <li><a href="<?= url('/quote') ?>">Book a Private Event</a></li>
          </ul>
        </nav>

        <nav class="footer-nav" aria-label="Company navigation">
          <h3>Company</h3>
          <ul>
            <li><a href="<?= url('/about') ?>">About Us</a></li>
            <li><a href="<?= url('/gallery') ?>">Gallery</a></li>
            <li><a href="<?= url('/faqs') ?>">FAQs</a></li>
            <li><a href="<?= url('/contact') ?>">Contact</a></li>
          </ul>
        </nav>

        <div class="footer-contact">
          <h3>Get in touch</h3>
          <?php if ($email = config('contact.email')): ?>
            <a href="mailto:<?= attr($email) ?>"><?= e($email) ?></a>
          <?php endif; ?>
          <?php if ($phone = config('contact.phone')): ?>
            <a href="tel:<?= attr(preg_replace('/\s/', '', $phone)) ?>"><?= e($phone) ?></a>
          <?php endif; ?>
          <?php if ($wa = config('contact.whatsapp')): ?>
            <a href="https://wa.me/<?= attr(preg_replace('/[^0-9]/', '', $wa)) ?>" target="_blank" rel="noopener noreferrer">WhatsApp us</a>
          <?php endif; ?>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> <?= e(config('app.name')) ?>. All rights reserved.</p>
        <nav aria-label="Legal navigation">
          <a href="<?= url('/privacy') ?>">Privacy Policy</a>
          <a href="<?= url('/terms') ?>">Terms of Service</a>
        </nav>
      </div>
    </div>
  </footer>

  <script src="<?= asset('js/app.js') ?>"></script>
  <?php if (!empty($footerExtra)): ?>
  <?= $footerExtra ?>
  <?php endif; ?>
</body>
</html>
