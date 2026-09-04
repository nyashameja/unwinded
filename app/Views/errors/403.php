<?php $pageTitle = 'Access Denied'; ?>
<?php $metaRobots = 'noindex'; ?>

<section class="error-page">
  <div class="container container--narrow">
    <p class="error-page__code">403</p>
    <h1 class="error-page__title">Access denied</h1>
    <p class="error-page__message">You don't have permission to view this page.</p>
    <div class="error-page__actions">
      <a href="<?= url('/') ?>" class="btn btn-primary">Go to homepage</a>
    </div>
  </div>
</section>
