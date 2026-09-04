<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">New Page</h1>
  <div class="page-header__actions">
    <a href="<?= url('/admin/pages') ?>" class="btn btn--secondary">← Pages</a>
  </div>
</div>

<form method="POST" action="<?= url('/admin/pages') ?>" novalidate>
  <?= csrf_field() ?>
  <?php include __DIR__ . '/_form.php'; ?>
  <div class="form-actions">
    <button type="submit" class="btn btn--primary">Create page</button>
  </div>
</form>
