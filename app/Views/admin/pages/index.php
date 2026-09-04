<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Pages</h1>
  <div class="page-header__actions">
    <a href="<?= url('/admin/pages/create') ?>" class="btn btn--primary">+ New page</a>
  </div>
</div>

<div class="card">
  <table class="data-table">
    <thead>
      <tr>
        <th>Title</th>
        <th>Slug</th>
        <th>Template</th>
        <th>Status</th>
        <th>Updated</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($pages)): ?>
        <tr><td colspan="6" class="empty-row">No pages yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($pages as $page): ?>
      <tr>
        <td>
          <a href="<?= url('/admin/pages/' . $page['id'] . '/edit') ?>" class="table-link">
            <?= e($page['title']) ?>
          </a>
          <?php if ($page['is_system']): ?>
            <span class="badge badge--info">system</span>
          <?php endif; ?>
        </td>
        <td><code>/<?= e($page['slug']) ?></code></td>
        <td><?= e($page['template']) ?></td>
        <td>
          <span class="badge badge--<?= $page['status'] === 'published' ? 'success' : 'warning' ?>">
            <?= e($page['status']) ?>
          </span>
        </td>
        <td><?= e(substr($page['updated_at'], 0, 10)) ?></td>
        <td class="table-actions">
          <a href="<?= url('/admin/pages/' . $page['id'] . '/edit') ?>" class="btn btn--xs btn--secondary">Edit</a>
          <?php if (!$page['is_system']): ?>
          <form method="POST" action="<?= url('/admin/pages/' . $page['id'] . '/delete') ?>"
                onsubmit="return confirm('Delete this page?');" style="display:inline;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--xs btn--danger">Delete</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
