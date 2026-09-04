
<div class="page-hero page-hero--sm">
  <div class="container">
    <h1>Your Order</h1>
    <p>Ref: <?= e($order['public_ref']) ?></p>
  </div>
</div>

<section class="section">
  <div class="container container--narrow">

    <?php foreach (flash()->getAll() as $type => $msgs): ?>
      <?php foreach ($msgs as $msg): ?>
        <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
      <?php endforeach; ?>
    <?php endforeach; ?>

    <div class="card">
      <div class="card__body">
        <table class="data-table">
          <tr><th>Order ref</th><td><?= e($order['public_ref']) ?></td></tr>
          <tr><th>Status</th><td><?= e(ucfirst(str_replace('_', ' ', $order['status']))) ?></td></tr>
          <tr><th>Event</th><td><?= e($order['event_title']) ?></td></tr>
          <tr><th>Date</th><td><?= e(date('d M Y', strtotime($order['event_date_utc']))) ?></td></tr>
          <tr><th>Total paid</th><td><strong><?= e(money($order['total_cents'])) ?></strong></td></tr>
        </table>

        <?php if (!empty($order['items'])): ?>
        <h3 style="margin-top:1.5rem;">Tickets</h3>
        <table class="data-table">
          <thead><tr><th>Ticket type</th><th>Qty</th><th>Unit price</th><th>Subtotal</th></tr></thead>
          <tbody>
            <?php foreach ($order['items'] as $item): ?>
            <tr>
              <td><?= e($item['ticket_type_name']) ?></td>
              <td><?= e($item['qty']) ?></td>
              <td><?= e(money($item['unit_price_cents'])) ?></td>
              <td><?= e(money($item['qty'] * $item['unit_price_cents'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>

    <p style="margin-top:1.5rem;font-size:.875rem;color:#666;">
      Questions? <a href="<?= url('/contact') ?>">Contact us</a>
    </p>
  </div>
</section>
