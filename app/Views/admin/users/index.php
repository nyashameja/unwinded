<div class="page-header">
  <div class="page-header__title">
    <h1>Admin Users</h1>
    <p><?= count($users) ?> users</p>
  </div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/users/create') ?>" class="btn btn--primary">+ New user</a>
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
      <tr><th>Name</th><th>Email</th><th>Roles</th><th>Last login</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td><strong><?= e($u['name']) ?></strong></td>
        <td style="font-size:.875rem;"><?= e($u['email']) ?></td>
        <td style="font-size:.8rem;"><?= e($u['role_labels'] ?? '—') ?></td>
        <td style="font-size:.8rem;"><?= $u['last_login_at'] ? e(date('d M Y H:i', strtotime($u['last_login_at']))) : 'Never' ?></td>
        <td>
          <?= $u['is_active']
            ? '<span class="badge badge--success">Active</span>'
            : '<span class="badge badge--danger">Suspended</span>' ?>
        </td>
        <td style="white-space:nowrap;">
          <a href="<?= url('/admin/users/' . (int) $u['id'] . '/edit') ?>" class="btn btn--xs btn--secondary">Edit</a>
          <?php if ($u['is_active'] && (int) $u['id'] !== ($_SESSION['user']['id'] ?? 0)): ?>
          <form method="POST" action="<?= url('/admin/users/' . (int) $u['id'] . '/suspend') ?>"
                style="display:inline;" onsubmit="return confirm('Suspend this user?');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--xs btn--danger">Suspend</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
