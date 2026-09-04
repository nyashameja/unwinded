<?php $pageTitle = 'Method Not Allowed'; ?>
<?php $metaRobots = 'noindex'; ?>

<section class="error-page">
  <div class="container container--narrow">
    <p class="error-page__code">405</p>
    <h1 class="error-page__title">Method not allowed</h1>
    <p class="error-page__message">This request method is not supported for this URL.</p>
    <div class="error-page__actions">
      <a href="<?= url('/') ?>" class="btn btn-primary">Go to homepage</a>
    </div>
  </div>
</section>
