<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Edit Page</h1>
  <div class="page-header__actions">
    <?php if ($page['status'] === 'published'): ?>
      <form method="POST" action="<?= url('/admin/pages/' . $page['id'] . '/unpublish') ?>" style="display:inline;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--secondary">Unpublish</button>
      </form>
      <a href="<?= url('/' . $page['slug']) ?>" target="_blank" class="btn btn--secondary">View live →</a>
    <?php else: ?>
      <form method="POST" action="<?= url('/admin/pages/' . $page['id'] . '/publish') ?>" style="display:inline;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--primary">Publish</button>
      </form>
    <?php endif; ?>
    <a href="<?= url('/admin/pages') ?>" class="btn btn--secondary">← Pages</a>
  </div>
</div>

<div class="badge badge--<?= $page['status'] === 'published' ? 'success' : 'warning' ?>" style="margin-bottom:16px;">
  <?= e($page['status']) ?>
</div>

<form method="POST" action="<?= url('/admin/pages/' . $page['id']) ?>" novalidate>
  <?= csrf_field() ?>
  <?php include __DIR__ . '/_form.php'; ?>
  <div class="form-actions">
    <button type="submit" class="btn btn--primary">Save changes</button>
  </div>
</form>

<?php if (!empty($revisions)): ?>
<div class="card" style="margin-top:32px;">
  <div class="card__header">
    <h2 class="card__title">Revision history</h2>
  </div>
  <table class="data-table">
    <thead>
      <tr><th>Saved</th><th>Author</th><th>Title at save</th></tr>
    </thead>
    <tbody>
      <?php foreach ($revisions as $rev): ?>
      <tr>
        <td><?= e($rev['created_at']) ?></td>
        <td><?= e($rev['author_name'] ?? '—') ?></td>
        <td><?= e($rev['title']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
