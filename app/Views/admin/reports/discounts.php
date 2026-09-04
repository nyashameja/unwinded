<div class="page-header">
  <div class="page-header__title"><h1>Discount Code Report</h1></div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/reports') ?>" class="btn btn--secondary">← Reports</a>
  </div>
</div>

<?php include __DIR__ . '/_date_filter.php'; ?>

<?php if (empty($usage)): ?>
  <p class="empty-state">No discount codes found.</p>
<?php else: ?>
<div class="card">
  <table class="data-table">
    <thead>
      <tr><th>Code</th><th>Type</th><th>Value</th><th>Status</th><th>Uses</th><th>Savings</th></tr>
    </thead>
    <tbody>
      <?php foreach ($usage as $row): ?>
      <tr>
        <td><strong><?= e($row['code']) ?></strong></td>
        <td><?= e(ucfirst($row['discount_type'])) ?></td>
        <td>
          <?php if ($row['discount_type'] === 'percentage'): ?>
            <?= e($row['discount_value']) ?>%
          <?php else: ?>
            <?= e(money((int) ($row['discount_value'] * 100))) ?>
          <?php endif; ?>
        </td>
        <td>
          <?= $row['is_active']
            ? '<span class="badge badge--success">Active</span>'
            : '<span class="badge badge--neutral">Inactive</span>' ?>
        </td>
        <td><?= (int) $row['uses'] ?></td>
        <td><?= e(money((int) $row['saved_cents'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
