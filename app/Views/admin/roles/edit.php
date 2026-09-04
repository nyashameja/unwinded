<div class="page-header">
  <div class="page-header__title">
    <h1><?= e($role['label']) ?></h1>
    <code style="font-size:.875rem;"><?= e($role['name']) ?></code>
  </div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/roles') ?>" class="btn btn--secondary">← Back</a>
  </div>
</div>

<?php foreach (flash()->getAll() as $ftype => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($ftype) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<form method="POST" action="<?= url('/admin/roles/' . (int) $role['id']) ?>">
  <?= csrf_field() ?>
  <?php include __DIR__ . '/_form.php'; ?>
  <div style="margin-top:1.5rem;">
    <button type="submit" class="btn btn--primary">Save role</button>
  </div>
</form>
