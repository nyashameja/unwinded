<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Order <?= e($order['public_ref']) ?></h1>
  <a href="<?= url('/admin/orders') ?>" class="btn btn--secondary">&larr; All orders</a>
</div>

<div class="grid grid--2col" style="gap:1.5rem;align-items:start;">
  <div>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Order details</h2></div>
      <div class="card__body">
        <table class="data-table">
          <tr><th>Ref</th><td><?= e($order['public_ref']) ?></td></tr>
          <tr><th>Event</th><td>
            <?= e($order['event_title'] ?? '—') ?>
            <?php if ($order['event_date']): ?><br><small><?= e(date('d M Y', strtotime($order['event_date']))) ?></small><?php endif; ?>
            <?php if ($order['venue_name']): ?><br><small><?= e($order['venue_name']) ?></small><?php endif; ?>
          </td></tr>
          <tr><th>Purchaser</th><td><?= e($order['purchaser_name']) ?><br><?= e($order['purchaser_email']) ?><br><?= e($order['purchaser_phone'] ?? '') ?></td></tr>
          <tr><th>Subtotal</th><td><?= e(money($order['subtotal_cents'])) ?></td></tr>
          <?php if ($order['discount_amount_cents']): ?>
            <tr><th>Discount</th><td>−<?= e(money($order['discount_amount_cents'])) ?></td></tr>
          <?php endif; ?>
          <tr><th>Total</th><td><strong><?= e(money($order['total_cents'])) ?></strong></td></tr>
          <tr><th>Status</th><td><span class="badge badge--neutral"><?= e(str_replace('_',' ',$order['status'])) ?></span></td></tr>
          <?php if ($order['paid_at']): ?>
            <tr><th>Paid at</th><td><?= e(date('d M Y H:i', strtotime($order['paid_at']))) ?></td></tr>
          <?php endif; ?>
          <tr><th>Created</th><td><?= e(date('d M Y H:i', strtotime($order['created_at']))) ?></td></tr>
        </table>
      </div>
    </div>

    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Line items</h2></div>
      <div class="card__body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Type</th><th>Qty</th><th>Unit</th><th>Line total</th></tr></thead>
          <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
              <td><?= e($item['ticket_type_name'] ?? '—') ?></td>
              <td><?= (int) $item['quantity'] ?></td>
              <td><?= e(money($item['unit_price_cents'])) ?></td>
              <td><?= e(money($item['line_total_cents'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Tickets (<?= count($tickets) ?>)</h2></div>
      <div class="card__body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>UID</th><th>Attendee</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php if (empty($tickets)): ?>
              <tr><td colspan="4" class="empty-row">No tickets issued.</td></tr>
            <?php endif; ?>
            <?php foreach ($tickets as $t): ?>
            <tr>
              <td><code style="font-size:.8em;"><?= e($t['ticket_uid']) ?></code></td>
              <td><?= e($t['attendee_name'] ?? '—') ?></td>
              <td><span class="badge badge--neutral"><?= e($t['status']) ?></span></td>
              <td><a href="<?= url('/tickets/' . urlencode($t['ticket_uid'])) ?>" target="_blank" class="btn btn--xs btn--secondary">View</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if (in_array($order['status'], ['pending','paid'], true)): ?>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Actions</h2></div>
      <div class="card__body" style="display:flex;gap:.5rem;flex-wrap:wrap;">
        <?php if ($order['status'] === 'paid'): ?>
          <form method="POST" action="<?= url('/admin/orders/' . $order['id'] . '/resend') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--secondary">Resend tickets</button>
          </form>
        <?php endif; ?>
        <form method="POST" action="<?= url('/admin/orders/' . $order['id'] . '/cancel') ?>"
              onsubmit="return confirm('Cancel this order? All tickets will be voided and quantities released.')">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn--danger">Cancel order</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
