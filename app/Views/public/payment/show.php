
<div class="page-hero page-hero--sm">
  <div class="container">
    <h1>Pay Deposit</h1>
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

    <div class="card">
      <div class="card__body">
        <h2>Booking summary</h2>
        <table class="data-table">
          <tr><th>Ref</th><td><?= e($booking['public_ref']) ?></td></tr>
          <?php if ($booking['event_date']): ?>
          <tr><th>Date</th><td><?= e(date('d M Y', strtotime($booking['event_date']))) ?></td></tr>
          <?php endif; ?>
          <tr><th>Total</th><td><?= e(money($booking['total_cents'])) ?></td></tr>
          <tr><th>Deposit due</th><td><strong><?= e(money($booking['deposit_cents'])) ?></strong></td></tr>
        </table>

        <div class="alert alert--info" style="margin-top:1.5rem;">
          Online payment is coming soon. Please <a href="<?= url('/contact') ?>">contact us</a> to arrange your deposit payment by EFT.
        </div>
      </div>
    </div>

    <p style="margin-top:1.5rem;font-size:.875rem;color:#666;">
      Questions? <a href="<?= url('/contact') ?>">Contact us</a>
    </p>
  </div>
</section>
