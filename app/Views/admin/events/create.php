<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">New Event</h1>
  <a href="<?= url('/admin/events') ?>" class="btn btn--secondary">&larr; All events</a>
</div>

<form method="POST" action="<?= url('/admin/events') ?>">
  <?= csrf_field() ?>
  <?php include __DIR__ . '/_form.php'; ?>
</form>
