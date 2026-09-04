<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Experiences</h1>
</div>

<div class="card">
  <table class="data-table">
    <thead>
      <tr>
        <th>Title</th>
        <th>Type</th>
        <th>Slug</th>
        <th>Order</th>
        <th>Active</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($experiences)): ?>
        <tr><td colspan="6" class="empty-row">No experiences yet. Add them via the database seeder.</td></tr>
      <?php endif; ?>
      <?php foreach ($experiences as $exp): ?>
      <tr>
        <td>
          <a href="<?= url('/admin/experiences/' . $exp['id'] . '/edit') ?>" class="table-link">
            <?= e($exp['title']) ?>
          </a>
        </td>
        <td><?= e($exp['type']) ?></td>
        <td><code><?= e($exp['slug']) ?></code></td>
        <td><?= e($exp['sort_order']) ?></td>
        <td><?= $exp['is_active'] ? '<span class="badge badge--success">Yes</span>' : '<span class="badge badge--neutral">No</span>' ?></td>
        <td class="table-actions">
          <a href="<?= url('/admin/experiences/' . $exp['id'] . '/edit') ?>" class="btn btn--xs btn--secondary">Edit</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
