<div class="page-header">
  <div class="page-header__title">
    <h1><?= e($user['name']) ?></h1>
    <?= $user['is_active']
      ? '<span class="badge badge--success">Active</span>'
      : '<span class="badge badge--danger">Suspended</span>' ?>
  </div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/users') ?>" class="btn btn--secondary">← Back</a>
  </div>
</div>

<?php foreach (flash()->getAll() as $ftype => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($ftype) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<form method="POST" action="<?= url('/admin/users/' . (int) $user['id']) ?>">
  <?= csrf_field() ?>
  <?php include __DIR__ . '/_form.php'; ?>
  <div style="margin-top:1.5rem;">
    <button type="submit" class="btn btn--primary">Save changes</button>
  </div>
</form>
