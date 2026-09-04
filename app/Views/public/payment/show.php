
<div class="page-hero page-hero--sm">
  <div class="container">
    <h1>Secure Payment</h1>
    <p>Booking ref: <?= e($booking['public_ref']) ?></p>
  </div>
</div>

<section class="section">
  <div class="container container--narrow">

    <?php foreach (flash()->getAll() as $type => $msgs): ?>
      <?php foreach ($msgs as $msg): ?>
        <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
      <?php endforeach; ?>
    <?php endforeach; ?>

    <?php
      $totalCents      = (int) $booking['total_cents'];
      $depositCents    = (int) $booking['deposit_cents'];
      $paidCents       = (int) $booking['amount_paid_cents'];
      $outstandingCents = (int) $booking['outstanding_cents'];
      $depositOwed     = max(0, $depositCents - $paidCents);
      $isPaid          = $outstandingCents <= 0;
    ?>

    <!-- Booking summary -->
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__body">
        <h2 style="margin-top:0;">Booking summary</h2>
        <table class="data-table">
          <tr><th>Reference</th><td><?= e($booking['public_ref']) ?></td></tr>
          <?php if ($booking['event_type']): ?>
          <tr><th>Event type</th><td><?= e($booking['event_type']) ?></td></tr>
          <?php endif; ?>
          <?php if ($booking['event_date']): ?>
          <tr><th>Date</th><td><?= e(date('l, d F Y', strtotime($booking['event_date']))) ?></td></tr>
          <?php endif; ?>
          <tr><th>Total</th><td><?= e(money($totalCents)) ?></td></tr>
          <tr><th>Deposit</th><td><?= e(money($depositCents)) ?></td></tr>
          <?php if ($paidCents > 0): ?>
          <tr><th>Amount paid</th><td style="color:var(--color-success,#1a7f37);"><?= e(money($paidCents)) ?></td></tr>
          <?php endif; ?>
          <tr><th>Outstanding</th><td><strong><?= e(money($outstandingCents)) ?></strong></td></tr>
        </table>
      </div>
    </div>

    <?php if ($isPaid): ?>
      <div class="alert alert--success">
        <strong>Fully paid — thank you!</strong> This booking has been paid in full.
      </div>

    <?php else: ?>

      <!-- ── PayFast section ───────────────────────────────────────────── -->
      <div class="card" style="margin-bottom:1.5rem;">
        <div class="card__body">
          <h2 style="margin-top:0;">Pay by card or instant EFT</h2>
          <p style="color:#555;">Secure payment powered by PayFast. You will be redirected to the PayFast payment page.</p>

          <form method="POST" action="<?= e(url('/pay/' . $booking['public_ref'] . '/' . $token)) ?>">
            <?= csrf_field() ?>

            <?php if ($depositOwed > 0 && $outstandingCents > $depositOwed): ?>
              <!-- Both deposit and full-payment options available -->
              <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;">
                <label style="flex:1;min-width:200px;border:2px solid var(--color-border,#ddd);border-radius:8px;padding:1rem;cursor:pointer;">
                  <input type="radio" name="payment_type" value="deposit" checked style="margin-right:.5rem;">
                  <strong>Pay deposit — <?= e(money($depositOwed)) ?></strong>
                  <p style="margin:.25rem 0 0;font-size:.875rem;color:#555;">Secures your booking.</p>
                </label>
                <label style="flex:1;min-width:200px;border:2px solid var(--color-border,#ddd);border-radius:8px;padding:1rem;cursor:pointer;">
                  <input type="radio" name="payment_type" value="full" style="margin-right:.5rem;">
                  <strong>Pay in full — <?= e(money($outstandingCents)) ?></strong>
                  <p style="margin:.25rem 0 0;font-size:.875rem;color:#555;">Pay the full outstanding balance.</p>
                </label>
              </div>
            <?php else: ?>
              <!-- Only one option (full balance or deposit only) -->
              <input type="hidden" name="payment_type" value="<?= $depositOwed > 0 ? 'deposit' : 'full' ?>">
              <p><strong>Amount to pay: <?= e(money($outstandingCents)) ?></strong></p>
            <?php endif; ?>

            <button type="submit" class="btn btn--primary btn--lg" style="width:100%;">
              Continue to PayFast &rarr;
            </button>
          </form>
        </div>
      </div>

      <!-- ── EFT / Bank transfer section ──────────────────────────────── -->
      <?php $bankDetails = setting('bank.details', ''); ?>
      <?php if ($bankDetails): ?>
      <div class="card" style="margin-bottom:1.5rem;">
        <div class="card__body">
          <h2 style="margin-top:0;">Pay by EFT / bank transfer</h2>
          <p style="color:#555;">Transfer directly to our bank account using the details below. Please use your booking reference <strong><?= e($booking['public_ref']) ?></strong> as the payment reference.</p>
          <pre style="background:var(--color-surface-alt,#f5f5f5);border-radius:6px;padding:1rem;white-space:pre-wrap;word-break:break-word;font-size:.9rem;"><?= e($bankDetails) ?></pre>
          <p style="font-size:.875rem;color:#666;">Once you have made payment, please send proof of payment to <a href="mailto:<?= e(setting('contact.email', 'hello@unwinded.co.za')) ?>"><?= e(setting('contact.email', 'hello@unwinded.co.za')) ?></a>.</p>
        </div>
      </div>
      <?php endif; ?>

    <?php endif; ?>

    <p style="margin-top:1.5rem;font-size:.875rem;color:#666;">
      Questions? <a href="<?= url('/contact') ?>">Contact us</a>
    </p>

  </div>
</section>
