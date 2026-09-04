<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Payment <?= e($payment['public_ref']) ?></h1>
  <a href="<?= url('/admin/payments') ?>" class="btn btn--secondary">&larr; All payments</a>
</div>

<div class="grid grid--2col" style="gap:1.5rem;align-items:start;">
  <div>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Payment details</h2></div>
      <div class="card__body">
        <table class="data-table">
          <tr><th>Ref</th><td><?= e($payment['public_ref']) ?></td></tr>
          <tr><th>Customer</th><td><?= e($payment['customer_name'] ?? '—') ?></td></tr>
          <tr><th>Amount</th><td><strong><?= e(money($payment['amount_cents'])) ?></strong></td></tr>
          <tr><th>Currency</th><td><?= e($payment['currency']) ?></td></tr>
          <tr><th>Gateway</th><td><?= e($payment['gateway']) ?></td></tr>
          <tr><th>Method</th><td><?= e($payment['payment_method'] ?? '—') ?></td></tr>
          <?php if ($payment['gateway_ref']): ?><tr><th>Gateway ref</th><td><?= e($payment['gateway_ref']) ?></td></tr><?php endif; ?>
          <tr><th>Status</th><td><span class="badge badge--neutral"><?= e(ucwords(str_replace('_',' ',$payment['status']))) ?></span></td></tr>
          <tr><th>Date</th><td><?= e(date('d M Y H:i', strtotime($payment['created_at']))) ?></td></tr>
          <?php if ($payment['notes']): ?><tr><th>Notes</th><td><?= nl2br(e($payment['notes'])) ?></td></tr><?php endif; ?>
        </table>
      </div>
    </div>

    <?php if ($allocations): ?>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Allocations</h2></div>
      <div class="card__body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Payable</th><th>Amount</th><th>Type</th></tr></thead>
          <tbody>
            <?php foreach ($allocations as $a): ?>
            <tr>
              <td>
                <?= e($a['payable_type']) ?> #<?= e($a['payable_id']) ?>
                <?php if ($a['payable_type'] === 'booking'): ?>
                  — <a href="<?= url('/admin/bookings/' . $a['payable_id']) ?>">View booking</a>
                <?php elseif ($a['payable_type'] === 'order'): ?>
                  — <a href="<?= url('/admin/orders/' . $a['payable_id']) ?>">View order</a>
                <?php endif; ?>
              </td>
              <td><?= e(money($a['amount_cents'])) ?></td>
              <td><span class="badge badge--neutral"><?= e($a['allocation_type']) ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($eftProof): ?>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>EFT proof</h2></div>
      <div class="card__body">
        <?php if ($eftProof['thumb_url']): ?>
          <img src="<?= attr($eftProof['thumb_url']) ?>" alt="EFT proof" style="max-width:200px;margin-bottom:1rem;">
        <?php endif; ?>
        <table class="data-table">
          <?php if ($eftProof['bank_ref']): ?><tr><th>Bank ref</th><td><?= e($eftProof['bank_ref']) ?></td></tr><?php endif; ?>
          <?php if ($eftProof['notes']): ?><tr><th>Notes</th><td><?= e($eftProof['notes']) ?></td></tr><?php endif; ?>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div>
    <?php if ($payment['status'] === 'successful'): ?>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Request refund</h2></div>
      <div class="card__body">
        <form method="POST" action="<?= url('/admin/payments/' . $payment['id'] . '/refund') ?>">
          <?= csrf_field() ?>
          <div class="form-group">
            <label class="form-label">Refund amount (R) <span class="required">*</span></label>
            <input type="number" name="amount_rand" class="form-input" step="0.01" min="0.01"
                   max="<?= number_format($payment['amount_cents'] / 100, 2, '.', '') ?>" required>
            <p class="form-hint">Maximum: <?= e(money($payment['amount_cents'])) ?>. Refunds are processed within 10 business days.</p>
          </div>
          <div class="form-group">
            <label class="form-label">Reason</label>
            <textarea name="reason" class="form-input form-input--textarea" rows="2"></textarea>
          </div>
          <button type="submit" class="btn btn--danger"
                  onclick="return confirm('Request a refund for this payment?')">Request refund</button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($refunds): ?>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Refunds</h2></div>
      <div class="card__body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Ref</th><th>Amount</th><th>Status</th><th>Requested</th></tr></thead>
          <tbody>
            <?php foreach ($refunds as $r): ?>
            <tr>
              <td><?= e($r['public_ref']) ?></td>
              <td><?= e(money($r['amount_cents'])) ?></td>
              <td><span class="badge badge--neutral"><?= e($r['status']) ?></span></td>
              <td><?= e(date('d M Y', strtotime($r['requested_at']))) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($logs): ?>
    <div class="card">
      <div class="card__header"><h2>Payment log</h2></div>
      <div class="card__body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Event</th><th>When</th></tr></thead>
          <tbody>
            <?php foreach ($logs as $l): ?>
            <tr>
              <td><?= e($l['event']) ?></td>
              <td><?= e(date('d M Y H:i', strtotime($l['created_at']))) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
