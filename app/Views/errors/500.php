<?php $pageTitle = 'Server Error'; ?>
<?php $metaRobots = 'noindex'; ?>

<section class="error-page">
  <div class="container container--narrow">
    <p class="error-page__code">500</p>
    <h1 class="error-page__title">Something went wrong</h1>
    <p class="error-page__message">We've encountered an unexpected error<?php if (!empty($errorRef)): ?> (ref: <?= e($errorRef) ?>)<?php endif; ?>. Our team has been notified. Please try again in a moment.</p>
    <div class="error-page__actions">
      <a href="<?= url('/') ?>" class="btn btn-primary">Go to homepage</a>
      <a href="<?= url('/contact') ?>" class="btn btn-ghost">Contact us</a>
    </div>
  </div>
</section>
