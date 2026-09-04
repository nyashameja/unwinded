<div class="page-header">
  <div class="page-header__title"><h1>New User</h1></div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/users') ?>" class="btn btn--secondary">← Back</a>
  </div>
</div>

<?php foreach (flash()->getAll() as $ftype => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($ftype) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<form method="POST" action="<?= url('/admin/users') ?>">
  <?= csrf_field() ?>
  <?php $user = null; $userRoleIds = []; include __DIR__ . '/_form.php'; ?>
  <div style="margin-top:1.5rem;">
    <button type="submit" class="btn btn--primary">Create user</button>
  </div>
</form>
