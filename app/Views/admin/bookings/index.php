<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Private Bookings</h1>
</div>

<div class="toolbar" style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
  <a href="<?= url('/admin/bookings') ?>" class="btn btn--xs <?= $status === '' ? 'btn--primary' : 'btn--secondary' ?>">All</a>
  <?php foreach ($statuses as $s): ?>
    <a href="<?= url('/admin/bookings?status=' . $s) ?>"
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
        <th>Event date</th>
        <th>Event type</th>
        <th>Total</th>
        <th>Paid</th>
        <th>Booking status</th>
        <th>Payment</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($bookings)): ?>
        <tr><td colspan="9" class="empty-row">No bookings found.</td></tr>
      <?php endif; ?>
      <?php foreach ($bookings as $b): ?>
      <tr>
        <td><a href="<?= url('/admin/bookings/' . $b['id']) ?>" class="table-link"><?= e($b['public_ref']) ?></a></td>
        <td>
          <?= e($b['customer_name']) ?><br>
          <small class="text-muted"><?= e($b['customer_email']) ?></small>
        </td>
        <td><?= e(date('d M Y', strtotime($b['event_date']))) ?></td>
        <td><?= e($b['event_type'] ?? '—') ?></td>
        <td><?= e(money($b['total_cents'])) ?></td>
        <td><?= e(money($b['amount_paid_cents'])) ?></td>
        <td>
          <?php $badge = match($b['booking_status']) {
              'confirmed','completed' => 'success',
              'planning','ready'      => 'info',
              'provisional','awaiting_deposit' => 'warning',
              'cancelled','refunded'  => 'neutral',
              default => 'neutral',
          }; ?>
          <span class="badge badge--<?= $badge ?>"><?= e(ucwords(str_replace('_',' ',$b['booking_status']))) ?></span>
        </td>
        <td>
          <?php $pbadge = match($b['payment_status']) {
              'paid'           => 'success',
              'partially_paid' => 'warning',
              'unpaid','payment_failed' => 'danger',
              default => 'neutral',
          }; ?>
          <span class="badge badge--<?= $pbadge ?>"><?= e(ucwords(str_replace('_',' ',$b['payment_status']))) ?></span>
        </td>
        <td class="table-actions">
          <a href="<?= url('/admin/bookings/' . $b['id']) ?>" class="btn btn--xs btn--secondary">View</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
