<?php
$exp = $experience;
?>
<div class="page-hero page-hero--sm">
  <div class="container">
    <nav aria-label="Breadcrumb" class="breadcrumb">
      <a href="<?= url('/experiences') ?>">Experiences</a>
      <span aria-hidden="true">/</span>
      <span><?= e($exp['title']) ?></span>
    </nav>
    <h1><?= e($exp['title']) ?></h1>
    <?php if ($exp['short_desc']): ?>
      <p class="page-hero__subheading"><?= e($exp['short_desc']) ?></p>
    <?php endif; ?>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="content-layout">
      <div class="prose content-layout__main">
        <?php if (!empty($exp['body'])): ?>
          <?= $exp['body'] ?>
        <?php else: ?>
          <p>Contact us to learn more about this experience.</p>
        <?php endif; ?>
      </div>
      <aside class="content-layout__sidebar">
        <div class="card">
          <div class="card__body">
            <h3>Interested?</h3>
            <p>Request a personalised quote and we'll get back to you within one business day.</p>
            <?php if (!empty($exp['cta_url'])): ?>
              <a href="<?= attr($exp['cta_url']) ?>" class="btn btn--primary btn--block">
                <?= e($exp['cta_text'] ?: 'Get a quote') ?>
              </a>
            <?php else: ?>
              <a href="<?= url('/request-a-quote') ?>" class="btn btn--primary btn--block">Request a quote</a>
            <?php endif; ?>
            <p style="margin-top:1rem;text-align:center;font-size:.875rem;">
              or <a href="<?= url('/contact') ?>">contact us</a> directly
            </p>
          </div>
        </div>
      </aside>
    </div>
  </div>
</section>
