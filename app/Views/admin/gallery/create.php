<div class="page-header">
  <div class="page-header__title">
    <h1>New Gallery Album</h1>
  </div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/gallery') ?>" class="btn btn--secondary">← Back</a>
  </div>
</div>

<?php foreach (flash()->getAll() as $type => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<form method="POST" action="<?= url('/admin/gallery') ?>">
  <?= csrf_field() ?>
  <?php $album = null; include __DIR__ . '/_form.php'; ?>
</form>
