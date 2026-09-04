<div class="page-header">
  <div class="page-header__title">
    <h1><?= e($package['name']) ?></h1>
    <span class="badge badge--<?= $package['status'] === 'published' ? 'success' : 'neutral' ?>"><?= e(ucfirst($package['status'])) ?></span>
  </div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/packages/' . (int) $package['id'] . '/extras') ?>" class="btn btn--secondary">Manage extras</a>
    <a href="<?= url('/admin/packages') ?>" class="btn btn--secondary">← Back</a>
  </div>
</div>

<?php foreach (flash()->getAll() as $ftype => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($ftype) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<form method="POST" action="<?= url('/admin/packages/' . (int) $package['id']) ?>">
  <?= csrf_field() ?>
  <?php include __DIR__ . '/_form.php'; ?>
  <div style="margin-top:1.5rem;">
    <button type="submit" class="btn btn--primary">Save changes</button>
  </div>
</form>
