
<div class="page-hero page-hero--sm">
  <div class="container">
    <h1>What Our Guests Say</h1>
    <p>Real reviews from people who've attended Unwinded events.</p>
  </div>
</div>

<section class="section">
  <div class="container">
    <?php if (empty($testimonials)): ?>
      <p class="empty-state">Testimonials coming soon.</p>
    <?php else: ?>
    <div class="testimonials-grid testimonials-grid--full">
      <?php foreach ($testimonials as $t): ?>
      <div class="testimonial-card <?= $t['is_featured'] ? 'testimonial-card--featured' : '' ?>">
        <div class="testimonial-card__stars" aria-label="<?= attr($t['rating']) ?> out of 5 stars">
          <?= str_repeat('★', (int)$t['rating']) . str_repeat('☆', 5 - (int)$t['rating']) ?>
        </div>
        <blockquote class="testimonial-card__body"><?= e($t['body']) ?></blockquote>
        <footer class="testimonial-card__footer">
          <?php if (!empty($t['photo_url'])): ?>
            <img src="<?= attr($t['photo_url']) ?>" alt="<?= attr($t['customer_name']) ?>" class="testimonial-card__avatar" loading="lazy">
          <?php endif; ?>
          <div>
            <strong><?= e($t['customer_name']) ?></strong>
            <?php if ($t['customer_title']): ?>
              <span class="testimonial-card__role"><?= e($t['customer_title']) ?></span>
            <?php endif; ?>
            <?php if ($t['event_type']): ?>
              <span class="testimonial-card__event"><?= e($t['event_type']) ?></span>
            <?php endif; ?>
          </div>
        </footer>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section section--cta-banner">
  <div class="container container--narrow">
    <h2>Ready to create your own memories?</h2>
    <a href="<?= url('/request-a-quote') ?>" class="btn btn--primary">Book an experience</a>
  </div>
</section>
