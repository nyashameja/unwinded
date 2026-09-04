<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Payments</h1>
</div>

<div class="toolbar" style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
  <a href="<?= url('/admin/payments') ?>" class="btn btn--xs <?= $status === '' ? 'btn--primary' : 'btn--secondary' ?>">All</a>
  <?php foreach ($statuses as $s): ?>
    <a href="<?= url('/admin/payments?status=' . $s) ?>"
       class="btn btn--xs <?= $status === $s ? 'btn--primary' : 'btn--secondary' ?>">
      <?= e(ucwords(str_replace('_', ' ', $s))) ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="card">
  <table class="data-table">
    <thead>
      <tr>
        <th>Ref</th>
        <th>Customer</th>
        <th>Amount</th>
        <th>Gateway</th>
        <th>Method</th>
        <th>Status</th>
        <th>Date</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($payments)): ?>
        <tr><td colspan="8" class="empty-row">No payments found.</td></tr>
      <?php endif; ?>
      <?php foreach ($payments as $p): ?>
      <tr>
        <td><a href="<?= url('/admin/payments/' . $p['id']) ?>" class="table-link"><?= e($p['public_ref']) ?></a></td>
        <td><?= e($p['customer_name'] ?? '—') ?></td>
        <td><?= e(money($p['amount_cents'])) ?></td>
        <td><?= e($p['gateway']) ?></td>
        <td><?= e($p['payment_method'] ?? '—') ?></td>
        <td>
          <?php $badge = match($p['status']) {
              'successful' => 'success',
              'pending'    => 'warning',
              'failed','cancelled','expired' => 'danger',
              default      => 'neutral',
          }; ?>
          <span class="badge badge--<?= $badge ?>"><?= e(ucwords(str_replace('_', ' ', $p['status']))) ?></span>
        </td>
        <td><?= e(date('d M Y', strtotime($p['created_at']))) ?></td>
        <td class="table-actions">
          <a href="<?= url('/admin/payments/' . $p['id']) ?>" class="btn btn--xs btn--secondary">View</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
