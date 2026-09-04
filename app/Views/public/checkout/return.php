
<section class="section" style="padding:80px 0;text-align:center;">
  <div class="container container--narrow">
    <?php foreach (flash()->getAll() as $type => $msgs): ?>
      <?php foreach ($msgs as $msg): ?>
        <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
      <?php endforeach; ?>
    <?php endforeach; ?>
    <h1>Payment Return</h1>
    <p>Processing your order… If you are not redirected shortly, please <a href="<?= url('/contact') ?>">contact us</a> with reference <strong><?= e($ref) ?></strong>.</p>
  </div>
</section>
