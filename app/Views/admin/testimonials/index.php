<div class="page-header">
  <div class="page-header__title">
    <h1>Testimonials</h1>
    <p><?= count($testimonials) ?> total</p>
  </div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/testimonials/create') ?>" class="btn btn--primary">+ New</a>
  </div>
</div>

<?php foreach (flash()->getAll() as $type => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<?php if (empty($testimonials)): ?>
  <p class="empty-state">No testimonials yet.</p>
<?php else: ?>
<div class="card">
  <table class="data-table">
    <thead>
      <tr>
        <th>Photo</th>
        <th>Name</th>
        <th>Rating</th>
        <th>Event type</th>
        <th>Published</th>
        <th>Featured</th>
        <th>Order</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($testimonials as $t): ?>
      <tr>
        <td>
          <?php if ($t['photo_url']): ?>
            <img src="<?= attr($t['photo_url']) ?>" alt="" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
          <?php else: ?>
            <div style="width:40px;height:40px;border-radius:50%;background:#ddd;"></div>
          <?php endif; ?>
        </td>
        <td>
          <strong><?= e($t['customer_name']) ?></strong>
          <?php if ($t['customer_title']): ?>
            <div style="font-size:.8rem;color:#666;"><?= e($t['customer_title']) ?></div>
          <?php endif; ?>
          <div style="font-size:.8rem;color:#999;margin-top:.25rem;"><?= e(substr($t['body'], 0, 80)) ?>…</div>
        </td>
        <td>
          <?= str_repeat('★', (int) $t['rating']) ?><?= str_repeat('☆', 5 - (int) $t['rating']) ?>
        </td>
        <td><?= e($t['event_type'] ?? '—') ?></td>
        <td><?= $t['is_published'] ? '<span class="badge badge--success">Yes</span>' : '<span class="badge badge--neutral">No</span>' ?></td>
        <td><?= $t['is_featured']  ? '<span class="badge badge--primary">Yes</span>' : '—' ?></td>
        <td><?= (int) $t['sort_order'] ?></td>
        <td>
          <a href="<?= url('/admin/testimonials/' . (int) $t['id'] . '/edit') ?>" class="btn btn--xs btn--secondary">Edit</a>
          <form method="POST" action="<?= url('/admin/testimonials/' . (int) $t['id'] . '/delete') ?>" style="display:inline;" onsubmit="return confirm('Delete this testimonial?');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--xs btn--danger">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
