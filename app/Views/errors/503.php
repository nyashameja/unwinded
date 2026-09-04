<?php $pageTitle = 'Maintenance Mode'; ?>
<?php $metaRobots = 'noindex'; ?>

<section class="error-page">
  <div class="container container--narrow">
    <p class="error-page__code">503</p>
    <h1 class="error-page__title">Back soon</h1>
    <p class="error-page__message"><?= e($message ?? 'We\'re making some improvements. Check back shortly.') ?></p>
    <div class="error-page__actions">
      <a href="mailto:<?= attr(config('contact.email', 'hello@unwinded.co.za')) ?>" class="btn btn-primary">Email us</a>
    </div>
  </div>
</section>
