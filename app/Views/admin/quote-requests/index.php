<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Quote Requests</h1>
</div>

<div class="toolbar" style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
  <span style="font-size:.875rem;color:#666;">Filter:</span>
  <a href="<?= url('/admin/quote-requests') ?>" class="btn btn--xs <?= $status === '' ? 'btn--primary' : 'btn--secondary' ?>">All</a>
  <?php foreach ($statuses as $s): ?>
    <a href="<?= url('/admin/quote-requests?status=' . $s) ?>"
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
        <th>Event type</th>
        <th>Preferred date</th>
        <th>Status</th>
        <th>Assigned</th>
        <th>Received</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($requests)): ?>
        <tr><td colspan="8" class="empty-row">No quote requests found.</td></tr>
      <?php endif; ?>
      <?php foreach ($requests as $qr): ?>
      <tr>
        <td><a href="<?= url('/admin/quote-requests/' . $qr['id']) ?>" class="table-link"><?= e($qr['public_ref']) ?></a></td>
        <td>
          <?= e($qr['customer_name']) ?><br>
          <small class="text-muted"><?= e($qr['customer_email']) ?></small>
        </td>
        <td><?= e(ucwords(str_replace('_', ' ', $qr['event_type'] ?? '—'))) ?></td>
        <td><?= $qr['preferred_date'] ? e(date('d M Y', strtotime($qr['preferred_date']))) : '—' ?></td>
        <td>
          <?php $badge = match($qr['status']) {
              'new'            => 'info',
              'reviewing'      => 'warning',
              'quote_prepared','quote_sent' => 'primary',
              'accepted','converted' => 'success',
              'declined','expired'   => 'neutral',
              default          => 'neutral',
          }; ?>
          <span class="badge badge--<?= $badge ?>"><?= e(ucwords(str_replace('_', ' ', $qr['status']))) ?></span>
        </td>
        <td><?= e($qr['assigned_name'] ?? '—') ?></td>
        <td><?= e(date('d M Y', strtotime($qr['created_at']))) ?></td>
        <td class="table-actions">
          <a href="<?= url('/admin/quote-requests/' . $qr['id']) ?>" class="btn btn--xs btn--secondary">View</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
