<div class="page-header">
  <div class="page-header__title">
    <h1>Email Templates</h1>
    <p><?= count($templates) ?> templates</p>
  </div>
</div>

<?php foreach (flash()->getAll() as $type => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<?php if (empty($templates)): ?>
  <p class="empty-state">No email templates found. Seed them via the database seeder.</p>
<?php else: ?>
<div class="card">
  <table class="data-table">
    <thead>
      <tr><th>Name</th><th>Slug</th><th>Subject</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($templates as $t): ?>
      <tr>
        <td><strong><?= e($t['name']) ?></strong></td>
        <td style="font-size:.8rem;"><code><?= e($t['slug']) ?></code></td>
        <td style="font-size:.875rem;"><?= e($t['subject']) ?></td>
        <td><?= $t['is_active']
          ? '<span class="badge badge--success">Active</span>'
          : '<span class="badge badge--neutral">Inactive</span>' ?>
        </td>
        <td><a href="<?= url('/admin/email-templates/' . (int) $t['id'] . '/edit') ?>" class="btn btn--xs btn--secondary">Edit</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
