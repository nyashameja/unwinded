<div class="page-header">
  <div class="page-header__title"><h1>New Role</h1></div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/roles') ?>" class="btn btn--secondary">← Back</a>
  </div>
</div>

<?php foreach (flash()->getAll() as $ftype => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($ftype) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<form method="POST" action="<?= url('/admin/roles') ?>">
  <?= csrf_field() ?>
  <?php $role = []; $rolePermIds = []; include __DIR__ . '/_form.php'; ?>
  <div style="margin-top:1.5rem;">
    <button type="submit" class="btn btn--primary">Create role</button>
  </div>
</form>
