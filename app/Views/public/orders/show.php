<section class="section">
  <div class="container" style="max-width:700px;">
    <h1 class="section__title">Order <?= e($order['public_ref']) ?></h1>

    <?php
      $statusColors = [
        'paid'      => '#155724',
        'pending'   => '#856404',
        'cancelled' => '#721c24',
        'expired'   => '#721c24',
        'refunded'  => '#0c5460',
      ];
      $statusBg = [
        'paid'      => '#d4edda',
        'pending'   => '#fff3cd',
        'cancelled' => '#f8d7da',
        'expired'   => '#f8d7da',
        'refunded'  => '#d1ecf1',
      ];
      $color  = $statusColors[$order['status']] ?? '#333';
      $bg     = $statusBg[$order['status']]     ?? '#eee';
    ?>
    <div style="display:inline-block;background:<?= $bg ?>;color:<?= $color ?>;padding:.4rem 1rem;border-radius:99px;font-weight:600;margin-bottom:1.5rem;">
      <?= e(ucwords(str_replace('_',' ',$order['status']))) ?>
    </div>

    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Event</h2></div>
      <div class="card__body">
        <table class="data-table">
          <tr><th>Event</th><td><?= e($order['event_title'] ?? '—') ?></td></tr>
          <?php if ($order['event_date']): ?>
            <tr><th>Date</th><td><?= e(date('l, d F Y', strtotime($order['event_date']))) ?></td></tr>
          <?php endif; ?>
          <?php if ($order['venue_name']): ?>
            <tr><th>Venue</th><td><?= e($order['venue_name']) ?><?= $order['venue_city'] ? ', ' . e($order['venue_city']) : '' ?></td></tr>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Order summary</h2></div>
      <div class="card__body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Ticket type</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead>
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
          <tfoot>
            <tr><td colspan="3"><strong>Total</strong></td><td><strong><?= e(money($order['total_cents'])) ?></strong></td></tr>
          </tfoot>
        </table>
      </div>
    </div>

    <?php if (!empty($tickets) && $order['status'] === 'paid'): ?>
    <div class="card">
      <div class="card__header"><h2>Your tickets</h2></div>
      <div class="card__body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Ticket</th><th>Attendee</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($tickets as $t): ?>
            <tr>
              <td><code style="font-size:.8em;"><?= e($t['ticket_uid']) ?></code></td>
              <td><?= e($t['attendee_name'] ?? '—') ?></td>
              <td><span class="badge badge--neutral"><?= e($t['status']) ?></span></td>
              <td><a href="<?= url('/t/' . urlencode($t['ticket_uid'])) ?>" class="btn btn--xs btn--secondary">View ticket</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php elseif ($order['status'] === 'pending'): ?>
    <div class="alert alert--warning">
      <strong>Awaiting payment.</strong> Once we confirm your EFT payment your tickets will be issued and emailed to you.
    </div>
    <?php endif; ?>
  </div>
</section>
