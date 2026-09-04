<div class="page-header">
  <div class="page-header__title">
    <h1>Roles</h1>
  </div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/roles/create') ?>" class="btn btn--primary">+ New role</a>
  </div>
</div>

<?php foreach (flash()->getAll() as $ftype => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($ftype) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="card">
  <table class="data-table">
    <thead>
      <tr><th>Label</th><th>Name</th><th>Permissions</th><th>Users</th><th>Super</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($roles as $r): ?>
      <tr>
        <td><strong><?= e($r['label']) ?></strong></td>
        <td><code style="font-size:.8rem;"><?= e($r['name']) ?></code></td>
        <td><?= (int) $r['permission_count'] ?></td>
        <td><?= (int) $r['user_count'] ?></td>
        <td><?= $r['is_super'] ? '<span class="badge badge--danger">Yes</span>' : '—' ?></td>
        <td>
          <?php if (!$r['is_super']): ?>
          <a href="<?= url('/admin/roles/' . (int) $r['id'] . '/edit') ?>" class="btn btn--xs btn--secondary">Edit</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
