<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Booking <?= e($booking['public_ref']) ?></h1>
  <div class="page-header__actions">
    <a href="<?= url('/admin/bookings/' . $booking['id'] . '/edit') ?>" class="btn btn--secondary">Edit</a>
    <a href="<?= url('/admin/bookings') ?>" class="btn btn--secondary">&larr; All bookings</a>
  </div>
</div>

<div class="grid grid--2col" style="gap:1.5rem;align-items:start;">

  <!-- Left column -->
  <div>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Booking details</h2></div>
      <div class="card__body">
        <table class="data-table">
          <tr><th>Customer</th><td><?= e($booking['customer_name']) ?> &lt;<a href="mailto:<?= attr($booking['customer_email']) ?>"><?= e($booking['customer_email']) ?></a>&gt;</td></tr>
          <?php if ($booking['customer_phone']): ?><tr><th>Phone</th><td><?= e($booking['customer_phone']) ?></td></tr><?php endif; ?>
          <tr><th>Event date</th><td><?= e(date('d M Y', strtotime($booking['event_date']))) ?></td></tr>
          <?php if ($booking['start_time']): ?><tr><th>Start time</th><td><?= e($booking['start_time']) ?></td></tr><?php endif; ?>
          <?php if ($booking['end_time']): ?><tr><th>End time</th><td><?= e($booking['end_time']) ?></td></tr><?php endif; ?>
          <?php if ($booking['event_type']): ?><tr><th>Event type</th><td><?= e($booking['event_type']) ?></td></tr><?php endif; ?>
          <tr><th>Guest count</th><td><?= e($booking['guest_count']) ?></td></tr>
          <?php if ($booking['venue_name']): ?><tr><th>Venue</th><td><?= e($booking['venue_name']) ?></td></tr><?php endif; ?>
        </table>
      </div>
    </div>

    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Financials</h2></div>
      <div class="card__body">
        <table class="data-table">
          <tr><th>Total</th><td><strong><?= e(money($booking['total_cents'])) ?></strong></td></tr>
          <tr><th>Deposit</th><td><?= e(money($booking['deposit_cents'])) ?></td></tr>
          <?php if ($booking['deposit_soft_deadline']): ?>
            <tr><th>Deposit soft deadline</th><td><?= e(date('d M Y', strtotime($booking['deposit_soft_deadline']))) ?></td></tr>
          <?php endif; ?>
          <?php if ($booking['deposit_hard_deadline']): ?>
            <tr><th>Deposit hard deadline</th><td><?= e(date('d M Y', strtotime($booking['deposit_hard_deadline']))) ?></td></tr>
          <?php endif; ?>
          <tr><th>Amount paid</th><td><?= e(money($booking['amount_paid_cents'])) ?></td></tr>
          <tr><th>Outstanding</th><td class="<?= $booking['outstanding_cents'] > 0 ? 'text-danger' : '' ?>"><?= e(money($booking['outstanding_cents'])) ?></td></tr>
          <?php if ($booking['balance_due_date']): ?>
            <tr><th>Balance due</th><td><?= e(date('d M Y', strtotime($booking['balance_due_date']))) ?></td></tr>
          <?php endif; ?>
          <tr><th>Booking status</th><td><span class="badge badge--info"><?= e(ucwords(str_replace('_',' ',$booking['booking_status']))) ?></span></td></tr>
          <tr><th>Payment status</th><td><span class="badge badge--info"><?= e(ucwords(str_replace('_',' ',$booking['payment_status']))) ?></span></td></tr>
        </table>
      </div>
    </div>

    <?php if ($items): ?>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Line items</h2></div>
      <div class="card__body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Description</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead>
          <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
              <td><?= e($item['description']) ?></td>
              <td><?= e($item['quantity']) ?></td>
              <td><?= e(money($item['unit_price_cents'])) ?></td>
              <td><?= e(money($item['line_total_cents'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Payments -->
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header">
        <h2>Payments</h2>
      </div>
      <div class="card__body">
        <?php if ($payments): ?>
        <table class="data-table" style="margin-bottom:1rem;">
          <thead><tr><th>Ref</th><th>Amount</th><th>Type</th><th>Date</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($payments as $p): ?>
            <tr>
              <td><a href="<?= url('/admin/payments/' . $p['id']) ?>"><?= e($p['public_ref']) ?></a></td>
              <td><?= e(money($p['allocated_cents'])) ?></td>
              <td><span class="badge badge--neutral"><?= e($p['allocation_type']) ?></span></td>
              <td><?= e(date('d M Y', strtotime($p['created_at']))) ?></td>
              <td><a href="<?= url('/admin/payments/' . $p['id']) ?>" class="btn btn--xs btn--secondary">View</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>

        <?php if ((int) $booking['outstanding_cents'] > 0): ?>
        <form method="POST" action="<?= url('/admin/bookings/' . (int) $booking['id'] . '/send-payment-link') ?>" style="margin-bottom:1rem;" onsubmit="return confirm('Send a payment link to <?= e(addslashes($booking['customer_email'])) ?>?');">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn--sm btn--primary">&#9993; Send payment link</button>
          <span style="margin-left:.5rem;font-size:.875rem;color:#555;">Emails a secure 30-day payment link to <?= e($booking['customer_email']) ?></span>
        </form>
        <?php endif; ?>

        <details style="margin-top:1rem;">
          <summary class="btn btn--xs btn--secondary">Record EFT payment</summary>
          <form method="POST" action="<?= url('/admin/payments/eft') ?>" style="margin-top:1rem;">
            <?= csrf_field() ?>
            <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Amount (R) <span class="required">*</span></label>
                <input type="number" name="amount_rand" class="form-input" step="0.01" min="0.01" required>
              </div>
              <div class="form-group">
                <label class="form-label">Allocation type</label>
                <select name="allocation_type" class="form-input form-input--select">
                  <option value="deposit">Deposit</option>
                  <option value="balance">Balance</option>
                  <option value="full">Full payment</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Bank reference</label>
              <input type="text" name="bank_ref" class="form-input" placeholder="e.g. TXN12345">
            </div>
            <div class="form-group">
              <label class="form-label">Notes</label>
              <input type="text" name="notes" class="form-input">
            </div>
            <button type="submit" class="btn btn--primary">Record payment</button>
          </form>
        </details>
      </div>
    </div>

    <!-- Documents -->
    <div class="card">
      <div class="card__header"><h2>Documents</h2></div>
      <div class="card__body">
        <?php if ($documents): ?>
        <ul style="list-style:none;padding:0;margin-bottom:1rem;">
          <?php foreach ($documents as $doc): ?>
          <li style="display:flex;align-items:center;gap:.75rem;padding:.5rem 0;border-bottom:1px solid var(--border-color);">
            <?php if ($doc['thumb_url']): ?>
              <img src="<?= attr($doc['thumb_url']) ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">
            <?php endif; ?>
            <div>
              <strong><?= e($doc['label']) ?></strong>
              <span class="badge badge--neutral" style="margin-left:.5rem;"><?= e($doc['document_type']) ?></span><br>
              <small class="text-muted"><?= e(date('d M Y', strtotime($doc['created_at']))) ?></small>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <details>
          <summary class="btn btn--xs btn--secondary">Upload document</summary>
          <form method="POST" action="<?= url('/admin/bookings/' . $booking['id'] . '/document') ?>" enctype="multipart/form-data" style="margin-top:1rem;">
            <?= csrf_field() ?>
            <div class="form-group">
              <label class="form-label">File <span class="required">*</span></label>
              <input type="file" name="document" class="form-input" required>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Label</label>
                <input type="text" name="label" class="form-input" placeholder="e.g. Signed contract">
              </div>
              <div class="form-group">
                <label class="form-label">Type</label>
                <select name="document_type" class="form-input form-input--select">
                  <option value="contract">Contract</option>
                  <option value="proof_of_payment">Proof of payment</option>
                  <option value="inspiration">Inspiration</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>
            <button type="submit" class="btn btn--primary">Upload</button>
          </form>
        </details>
      </div>
    </div>
  </div>

  <!-- Right column -->
  <div>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Change status</h2></div>
      <div class="card__body">
        <form method="POST" action="<?= url('/admin/bookings/' . $booking['id'] . '/status') ?>">
          <?= csrf_field() ?>
          <div class="form-group">
            <label class="form-label">Booking status</label>
            <select name="booking_status" class="form-input form-input--select">
              <?php foreach ($statuses as $s): ?>
                <option value="<?= $s ?>" <?= $booking['booking_status'] === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Reason (required for cancellation)</label>
            <input type="text" name="reason" class="form-input">
          </div>
          <button type="submit" class="btn btn--primary">Update status</button>
        </form>
      </div>
    </div>

    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Add note</h2></div>
      <div class="card__body">
        <form method="POST" action="<?= url('/admin/bookings/' . $booking['id'] . '/note') ?>">
          <?= csrf_field() ?>
          <div class="form-group">
            <textarea name="note" class="form-input form-input--textarea" rows="3" placeholder="Internal note…"></textarea>
          </div>
          <button type="submit" class="btn btn--secondary">Add note</button>
        </form>
      </div>
    </div>

    <?php if ($notes): ?>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Notes</h2></div>
      <div class="card__body">
        <?php foreach ($notes as $n): ?>
          <div style="border-bottom:1px solid var(--border-color);padding:.75rem 0;font-size:.875rem;">
            <p><?= nl2br(e($n['note'])) ?></p>
            <p class="text-muted" style="font-size:.75rem;"><?= e($n['user_name'] ?? 'System') ?> &middot; <?= e(date('d M Y H:i', strtotime($n['created_at']))) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($history): ?>
    <div class="card">
      <div class="card__header"><h2>Status history</h2></div>
      <div class="card__body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>From</th><th>To</th><th>By</th><th>When</th></tr></thead>
          <tbody>
            <?php foreach ($history as $h): ?>
            <tr>
              <td><?= e($h['from_status'] ?? '—') ?></td>
              <td><?= e($h['to_status']) ?></td>
              <td><?= e($h['changed_name'] ?? '—') ?></td>
              <td><?= e(date('d M Y H:i', strtotime($h['created_at']))) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
