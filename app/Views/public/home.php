<?php
$pageTitle   = null;
$canonicalUrl = url('/');

// Hero section
$hero = $sections['hero']['config'] ?? [];
$intro = $sections['intro']['config'] ?? [];
$pkgSection = $sections['featured_packages']['config'] ?? [];
$evtSection = $sections['upcoming_events']['config'] ?? [];
$testSection = $sections['testimonials']['config'] ?? [];
$gallerySection = $sections['gallery_preview']['config'] ?? [];
$ctaSection = $sections['cta_banner']['config'] ?? [];
?>

<?php if (!empty($sections['hero']['is_visible'])): ?>
<section class="hero">
  <div class="container">
    <div class="hero__inner">
      <h1 class="hero__heading"><?= e($hero['heading'] ?? 'Sip. Paint. Unwind.') ?></h1>
      <p class="hero__subheading"><?= e($hero['subheading'] ?? '') ?></p>
      <a href="<?= url($hero['cta_url'] ?? '/request-a-quote') ?>" class="btn btn--primary btn--lg">
        <?= e($hero['cta_label'] ?? 'Book an Experience') ?>
      </a>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($sections['intro']['is_visible'])): ?>
<section class="section section--intro">
  <div class="container container--narrow">
    <h2 class="section__heading"><?= e($intro['heading'] ?? '') ?></h2>
    <p class="section__body"><?= e($intro['body'] ?? '') ?></p>
    <?php if (!empty($intro['cta_url'])): ?>
      <a href="<?= url($intro['cta_url']) ?>" class="btn btn--outline"><?= e($intro['cta_label'] ?? 'Learn more') ?></a>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($sections['featured_packages']['is_visible']) && $featuredPackages): ?>
<section class="section section--packages section--light">
  <div class="container">
    <h2 class="section__heading"><?= e($pkgSection['heading'] ?? 'Our Packages') ?></h2>
    <?php if (!empty($pkgSection['subheading'])): ?>
      <p class="section__subheading"><?= e($pkgSection['subheading']) ?></p>
    <?php endif; ?>
    <div class="packages-grid">
      <?php foreach ($featuredPackages as $pkg): ?>
      <div class="package-card<?= $pkg['is_featured'] ? ' package-card--featured' : '' ?>"
           <?php if (!empty($pkg['highlight_colour'])): ?>style="--pkg-color:<?= attr($pkg['highlight_colour']) ?>"<?php endif; ?>>
        <div class="package-card__body">
          <h3 class="package-card__name"><?= e($pkg['name']) ?></h3>
          <?php if ($pkg['tagline']): ?>
            <p class="package-card__tagline"><?= e($pkg['tagline']) ?></p>
          <?php endif; ?>
          <?php if ($pkg['pricing_model'] !== 'price_on_request'): ?>
            <p class="package-card__price">
              From <strong><?= e(money($pkg['base_price_cents'])) ?></strong>
              <?= $pkg['pricing_model'] === 'per_person' ? 'per person' : '' ?>
            </p>
          <?php else: ?>
            <p class="package-card__price">Price on request</p>
          <?php endif; ?>
          <?php if (!empty($pkg['feature_labels'])): ?>
          <ul class="package-card__features">
            <?php foreach (explode('|||', $pkg['feature_labels']) as $fl): ?>
              <li><?= e($fl) ?></li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </div>
        <div class="package-card__footer">
          <a href="<?= url('/request-a-quote') ?>" class="btn btn--primary btn--sm">Get a quote</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if (!empty($pkgSection['cta_url'])): ?>
      <div class="section__cta">
        <a href="<?= url($pkgSection['cta_url']) ?>" class="btn btn--secondary"><?= e($pkgSection['cta_label'] ?? 'View all packages') ?></a>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($sections['upcoming_events']['is_visible']) && $upcomingEvents): ?>
<section class="section section--events">
  <div class="container">
    <h2 class="section__heading"><?= e($evtSection['heading'] ?? 'Upcoming Events') ?></h2>
    <?php if (!empty($evtSection['subheading'])): ?>
      <p class="section__subheading"><?= e($evtSection['subheading']) ?></p>
    <?php endif; ?>
    <div class="events-grid">
      <?php foreach ($upcomingEvents as $evt): ?>
      <a href="<?= url('/events/' . $evt['slug']) ?>" class="event-card">
        <?php if ($evt['featured_image']): ?>
          <div class="event-card__image">
            <img src="<?= attr($evt['featured_image']) ?>" alt="<?= attr($evt['title']) ?>" loading="lazy">
          </div>
        <?php endif; ?>
        <div class="event-card__body">
          <p class="event-card__date"><?= e(date('D, d M Y', strtotime($evt['event_date']))) ?> at <?= e(substr($evt['start_time'], 0, 5)) ?></p>
          <h3 class="event-card__title"><?= e($evt['title']) ?></h3>
          <?php if ($evt['venue_name']): ?>
            <p class="event-card__venue"><?= e($evt['venue_name']) ?><?= $evt['venue_city'] ? ', ' . e($evt['venue_city']) : '' ?></p>
          <?php endif; ?>
          <span class="event-card__cta">Get tickets &rarr;</span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php if (!empty($evtSection['cta_url'])): ?>
      <div class="section__cta">
        <a href="<?= url($evtSection['cta_url']) ?>" class="btn btn--secondary"><?= e($evtSection['cta_label'] ?? 'See all events') ?></a>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($sections['testimonials']['is_visible']) && $testimonials): ?>
<section class="section section--testimonials section--light">
  <div class="container">
    <h2 class="section__heading"><?= e($testSection['heading'] ?? 'What Our Guests Say') ?></h2>
    <div class="testimonials-grid">
      <?php foreach ($testimonials as $t): ?>
      <div class="testimonial-card">
        <div class="testimonial-card__stars" aria-label="<?= attr($t['rating']) ?> out of 5 stars">
          <?= str_repeat('★', (int)$t['rating']) . str_repeat('☆', 5 - (int)$t['rating']) ?>
        </div>
        <blockquote class="testimonial-card__body"><?= e($t['body']) ?></blockquote>
        <footer class="testimonial-card__footer">
          <strong><?= e($t['customer_name']) ?></strong>
          <?php if ($t['customer_title']): ?><span><?= e($t['customer_title']) ?></span><?php endif; ?>
          <?php if ($t['event_type']): ?><span><?= e($t['event_type']) ?></span><?php endif; ?>
        </footer>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="section__cta">
      <a href="<?= url('/testimonials') ?>" class="btn btn--outline">Read more reviews</a>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($sections['gallery_preview']['is_visible']) && $galleryAlbums): ?>
<section class="section section--gallery">
  <div class="container">
    <h2 class="section__heading"><?= e($gallerySection['heading'] ?? 'Gallery') ?></h2>
    <div class="gallery-grid gallery-grid--preview">
      <?php foreach ($galleryAlbums as $album): ?>
      <a href="<?= url('/gallery/' . $album['slug']) ?>" class="gallery-card">
        <?php if (!empty($album['cover_url'])): ?>
          <img src="<?= attr($album['cover_url']) ?>" alt="<?= attr($album['title']) ?>" loading="lazy">
        <?php else: ?>
          <div class="gallery-card__placeholder"></div>
        <?php endif; ?>
        <div class="gallery-card__overlay">
          <span><?= e($album['title']) ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php if (!empty($gallerySection['cta_url'])): ?>
      <div class="section__cta">
        <a href="<?= url($gallerySection['cta_url']) ?>" class="btn btn--secondary"><?= e($gallerySection['cta_label'] ?? 'View gallery') ?></a>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($sections['cta_banner']['is_visible'])): ?>
<section class="section section--cta-banner">
  <div class="container container--narrow">
    <h2 class="section__heading"><?= e($ctaSection['heading'] ?? 'Ready to unwind?') ?></h2>
    <?php if (!empty($ctaSection['subheading'])): ?>
      <p class="section__subheading"><?= e($ctaSection['subheading']) ?></p>
    <?php endif; ?>
    <a href="<?= url($ctaSection['cta_url'] ?? '/request-a-quote') ?>" class="btn btn--primary btn--lg">
      <?= e($ctaSection['cta_label'] ?? 'Get a quote') ?>
    </a>
  </div>
</section>
<?php endif; ?>
