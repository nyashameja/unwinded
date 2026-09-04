<section class="section">
  <div class="container" style="max-width:600px;">
    <h1 class="section__title">Confirm your order</h1>

    <?php if ($secondsLeft > 0): ?>
    <div class="alert alert--warning" style="margin-bottom:1.5rem;">
      <strong>Your tickets are held for <span id="countdown"><?= (int) gmdate('i', $secondsLeft) ?>:<?= gmdate('s', $secondsLeft) ?></span>.</strong>
      Complete payment before the timer runs out.
    </div>
    <?php endif; ?>

    <!-- Order summary -->
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Order <?= e($order['public_ref']) ?></h2></div>
      <div class="card__body">
        <table class="data-table">
          <tr><th>Event</th><td><?= e($order['event_title'] ?? '—') ?></td></tr>
          <?php if ($order['event_date']): ?>
            <tr><th>Date</th><td><?= e(date('d M Y', strtotime($order['event_date']))) ?></td></tr>
          <?php endif; ?>
          <?php if ($order['venue_name']): ?>
            <tr><th>Venue</th><td><?= e($order['venue_name']) ?><?= $order['venue_city'] ? ', ' . e($order['venue_city']) : '' ?></td></tr>
          <?php endif; ?>
        </table>

        <table class="data-table" style="margin-top:1rem;">
          <thead><tr><th>Ticket type</th><th>Qty</th><th>Total</th></tr></thead>
          <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
              <td><?= e($item['ticket_type_name'] ?? '—') ?></td>
              <td><?= (int) $item['quantity'] ?></td>
              <td><?= e(money($item['line_total_cents'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><td colspan="2"><strong>Total</strong></td><td><strong><?= e(money($order['total_cents'])) ?></strong></td></tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- EFT payment instructions -->
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Payment instructions</h2></div>
      <div class="card__body">
        <p>Please make an EFT payment of <strong><?= e(money($order['total_cents'])) ?></strong> using the banking details below.</p>
        <div style="background:var(--color-bg-secondary,#f9fafb);padding:1rem;border-radius:4px;margin:1rem 0;font-family:monospace;">
          <?= nl2br(e(setting('bank.details', 'Banking details will be provided. Please contact us.'))) ?>
        </div>
        <p><strong>Payment reference: <?= e($order['public_ref']) ?></strong></p>
        <p style="color:var(--color-text-secondary,#6b7280);font-size:.875rem;">
          Use your order reference as the payment reference. Your tickets will be issued once payment is confirmed by our team.
          Payment must be received within 48 hours.
        </p>
      </div>
    </div>

    <form method="POST" action="<?= url('/checkout/' . $order['public_ref'] . '/confirm') ?>">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn--primary btn--lg" style="width:100%;">
        I have made the payment — send confirmation email
      </button>
    </form>

    <p style="text-align:center;margin-top:1rem;font-size:.875rem;color:var(--color-text-secondary,#6b7280);">
      You will receive an email with payment instructions and your order reference.
    </p>
  </div>
</section>

<?php if ($secondsLeft > 0): ?>
<script>
let secs = <?= (int) $secondsLeft ?>;
const el = document.getElementById('countdown');
const iv = setInterval(() => {
  secs--;
  if (secs <= 0) {
    clearInterval(iv);
    el.textContent = 'EXPIRED';
    el.style.color = 'red';
    return;
  }
  const m = String(Math.floor(secs / 60)).padStart(2, '0');
  const s = String(secs % 60).padStart(2, '0');
  el.textContent = m + ':' + s;
}, 1000);
</script>
<?php endif; ?>
