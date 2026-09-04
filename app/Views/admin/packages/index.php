<div class="page-header">
  <div class="page-header__title">
    <h1>Packages</h1>
    <p><?= count($packages) ?> packages</p>
  </div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/packages/create') ?>" class="btn btn--primary">+ New package</a>
  </div>
</div>

<?php foreach (flash()->getAll() as $ftype => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($ftype) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<?php if (empty($packages)): ?>
  <p class="empty-state">No packages yet.</p>
<?php else: ?>
<div class="card">
  <table class="data-table">
    <thead>
      <tr><th>Name</th><th>Pricing</th><th>Price</th><th>Guests</th><th>Status</th><th>Features</th><th>Extras</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($packages as $p): ?>
      <tr>
        <td>
          <strong><?= e($p['name']) ?></strong>
          <?php if ($p['is_featured']): ?>
            <span class="badge badge--primary" style="margin-left:.25rem;">Featured</span>
          <?php endif; ?>
          <div style="font-size:.75rem;color:#888;"><?= e($p['tagline'] ?? '') ?></div>
        </td>
        <td style="font-size:.8rem;"><?= e(ucfirst(str_replace('_',' ',$p['pricing_model']))) ?></td>
        <td>
          <?php if ($p['pricing_model'] === 'price_on_request'): ?>
            <span style="font-size:.8rem;color:#888;">POR</span>
          <?php else: ?>
            <?= e(money((int) $p['base_price_cents'])) ?>
          <?php endif; ?>
        </td>
        <td style="font-size:.8rem;">
          <?= (int) $p['min_guests'] ?>
          <?= $p['max_guests'] ? '–' . (int) $p['max_guests'] : '+' ?>
        </td>
        <td>
          <?= $p['status'] === 'published'
            ? '<span class="badge badge--success">Published</span>'
            : '<span class="badge badge--neutral">Draft</span>' ?>
        </td>
        <td><?= (int) $p['feature_count'] ?></td>
        <td><a href="<?= url('/admin/packages/' . (int) $p['id'] . '/extras') ?>" class="btn btn--xs btn--secondary"><?= (int) $p['extra_count'] ?> extras</a></td>
        <td style="white-space:nowrap;">
          <a href="<?= url('/admin/packages/' . (int) $p['id'] . '/edit') ?>" class="btn btn--xs btn--secondary">Edit</a>
          <form method="POST" action="<?= url('/admin/packages/' . (int) $p['id'] . '/delete') ?>"
                style="display:inline;" onsubmit="return confirm('Delete this package?');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--xs btn--danger">Del</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
