<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Ticket Orders</h1>
</div>

<form method="GET" action="<?= url('/admin/orders') ?>" style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap;">
  <select name="status" class="form-input form-input--select" style="width:auto;">
    <option value="">All statuses</option>
    <?php foreach ($statuses as $s): ?>
      <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_',' ',$s))) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="event_id" class="form-input form-input--select" style="width:auto;">
    <option value="">All events</option>
    <?php foreach ($events as $ev): ?>
      <option value="<?= (int) $ev['id'] ?>" <?= $eventId === (int) $ev['id'] ? 'selected' : '' ?>><?= e($ev['title']) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn--secondary">Filter</button>
  <?php if ($status || $eventId): ?>
    <a href="<?= url('/admin/orders') ?>" class="btn btn--secondary">Clear</a>
  <?php endif; ?>
</form>

<div class="card">
  <table class="data-table">
    <thead>
      <tr>
        <th>Ref</th>
        <th>Event</th>
        <th>Purchaser</th>
        <th>Total</th>
        <th>Status</th>
        <th>Date</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($orders)): ?>
        <tr><td colspan="7" class="empty-row">No orders found.</td></tr>
      <?php endif; ?>
      <?php foreach ($orders as $o): ?>
      <tr>
        <td><a href="<?= url('/admin/orders/' . $o['id']) ?>" class="table-link"><?= e($o['public_ref']) ?></a></td>
        <td><?= $o['event_date'] ? '<small>' . e(date('d M Y', strtotime($o['event_date']))) . '</small> ' : '' ?><?= e($o['event_title'] ?? '—') ?></td>
        <td><?= e($o['purchaser_name']) ?><br><small><?= e($o['purchaser_email']) ?></small></td>
        <td><?= e(money($o['total_cents'])) ?></td>
        <td>
          <?php $badgeClass = match($o['status']) {
            'paid'   => 'badge--success',
            'cancelled','expired','refunded' => 'badge--danger',
            default  => 'badge--neutral',
          }; ?>
          <span class="badge <?= $badgeClass ?>"><?= e(str_replace('_',' ',$o['status'])) ?></span>
        </td>
        <td><?= e(date('d M Y', strtotime($o['created_at']))) ?></td>
        <td><a href="<?= url('/admin/orders/' . $o['id']) ?>" class="btn btn--xs btn--secondary">View</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
