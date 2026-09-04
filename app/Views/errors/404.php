<?php $pageTitle = 'Page Not Found'; ?>
<?php $metaRobots = 'noindex'; ?>

<section class="error-page">
  <div class="container container--narrow">
    <p class="error-page__code">404</p>
    <h1 class="error-page__title">Page not found</h1>
    <p class="error-page__message">We couldn't find the page you were looking for. It may have moved or been removed.</p>
    <div class="error-page__actions">
      <a href="<?= url('/') ?>" class="btn btn-primary">Go to homepage</a>
      <a href="<?= url('/contact') ?>" class="btn btn-ghost">Contact us</a>
    </div>
  </div>
</section>
