<?php
$types = ['corporate' => 'Corporate', 'restaurant' => 'Restaurant Partnership', 'private' => 'Private Celebration', 'other' => 'Other'];
?>
<div class="page-hero page-hero--sm">
  <div class="container">
    <h1><?= e($pageTitle) ?></h1>
    <?php if (!empty($metaDescription)): ?>
      <p><?= e($metaDescription) ?></p>
    <?php endif; ?>
  </div>
</div>

<section class="section">
  <div class="container">
    <nav class="filter-tabs" aria-label="Experience types">
      <a href="<?= url('/experiences') ?>" class="filter-tab <?= empty($activeType) ? 'is-active' : '' ?>">All</a>
      <a href="<?= url('/corporate') ?>" class="filter-tab <?= ($activeType ?? '') === 'corporate' ? 'is-active' : '' ?>">Corporate</a>
      <a href="<?= url('/restaurant-partnerships') ?>" class="filter-tab <?= ($activeType ?? '') === 'restaurant' ? 'is-active' : '' ?>">Restaurant</a>
      <a href="<?= url('/private-celebrations') ?>" class="filter-tab <?= ($activeType ?? '') === 'private' ? 'is-active' : '' ?>">Private</a>
    </nav>

    <?php if (empty($experiences)): ?>
      <p class="empty-state">No experiences to show right now. Check back soon!</p>
    <?php else: ?>
    <div class="experiences-grid">
      <?php foreach ($experiences as $exp): ?>
      <div class="experience-card">
        <div class="experience-card__body">
          <span class="badge badge--type"><?= e($types[$exp['type']] ?? ucfirst($exp['type'])) ?></span>
          <h2 class="experience-card__title">
            <a href="<?= url('/experiences/' . $exp['slug']) ?>"><?= e($exp['title']) ?></a>
          </h2>
          <?php if ($exp['short_desc']): ?>
            <p class="experience-card__desc"><?= e($exp['short_desc']) ?></p>
          <?php endif; ?>
          <div class="experience-card__actions">
            <a href="<?= url('/experiences/' . $exp['slug']) ?>" class="btn btn--secondary btn--sm">Learn more</a>
            <a href="<?= url('/request-a-quote') ?>" class="btn btn--primary btn--sm">Get a quote</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section section--cta-banner">
  <div class="container container--narrow">
    <h2>Ready to book?</h2>
    <p>Tell us about your event and we'll put together a personalised quote.</p>
    <a href="<?= url('/request-a-quote') ?>" class="btn btn--primary">Request a quote</a>
  </div>
</section>
