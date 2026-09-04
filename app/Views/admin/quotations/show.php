<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Quote <?= e($quote['public_ref']) ?></h1>
  <div class="page-header__actions">
    <?php if ($quote['status'] === 'draft'): ?>
      <a href="<?= url('/admin/quotations/' . $quote['id'] . '/edit') ?>" class="btn btn--secondary">Edit</a>
      <form method="POST" action="<?= url('/admin/quotations/' . $quote['id'] . '/send') ?>" style="display:inline;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--primary"
                onclick="return confirm('Send this quote to <?= attr($quote['customer_email']) ?>?')">
          Send to customer
        </button>
      </form>
    <?php endif; ?>
    <form method="POST" action="<?= url('/admin/quotations/' . $quote['id'] . '/duplicate') ?>" style="display:inline;">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn--secondary">Duplicate</button>
    </form>
    <a href="<?= url('/admin/quotations') ?>" class="btn btn--secondary">&larr; All quotes</a>
  </div>
</div>

<div class="grid grid--2col" style="gap:1.5rem;align-items:start;">
  <div>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Summary</h2></div>
      <div class="card__body">
        <table class="data-table">
          <tr><th>Status</th><td><span class="badge badge--neutral"><?= e(ucfirst($quote['status'])) ?></span></td></tr>
          <tr><th>Customer</th><td><?= e($quote['customer_name']) ?> &lt;<?= e($quote['customer_email']) ?>&gt;</td></tr>
          <?php if ($quote['event_type']): ?><tr><th>Event type</th><td><?= e($quote['event_type']) ?></td></tr><?php endif; ?>
          <?php if ($quote['event_date']): ?><tr><th>Event date</th><td><?= e(date('d M Y', strtotime($quote['event_date']))) ?></td></tr><?php endif; ?>
          <?php if ($quote['guest_count']): ?><tr><th>Guests</th><td><?= e($quote['guest_count']) ?></td></tr><?php endif; ?>
          <?php if ($quote['venue_name']): ?><tr><th>Venue</th><td><?= e($quote['venue_name']) ?></td></tr><?php endif; ?>
          <?php if ($quote['valid_until']): ?><tr><th>Valid until</th><td><?= e(date('d M Y', strtotime($quote['valid_until']))) ?></td></tr><?php endif; ?>
        </table>
      </div>
    </div>

    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Line items</h2></div>
      <div class="card__body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Description</th><th>Qty</th><th>Unit price</th><th>Total</th></tr></thead>
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
          <tfoot>
            <?php if ($quote['discount_type'] !== 'none'): ?>
            <tr><td colspan="3"><em>Discount (<?= e($quote['discount_type']) ?> <?= e($quote['discount_value']) ?>)</em></td><td>&minus;<?= e(money($quote['discount_amount_cents'])) ?></td></tr>
            <?php endif; ?>
            <tr><td colspan="3"><strong>Total</strong></td><td><strong><?= e(money($quote['total_cents'])) ?></strong></td></tr>
            <tr><td colspan="3">Deposit (<?= e($quote['deposit_type']) ?> <?= e($quote['deposit_value']) ?>)</td><td><?= e(money($quote['deposit_cents'])) ?></td></tr>
          </tfoot>
        </table>
      </div>
    </div>

    <?php if ($quote['notes_to_customer']): ?>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Notes to customer</h2></div>
      <div class="card__body prose"><?= nl2br(e($quote['notes_to_customer'])) ?></div>
    </div>
    <?php endif; ?>
  </div>

  <div>
    <?php if ($tokens): ?>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Customer link</h2></div>
      <div class="card__body">
        <?php foreach ($tokens as $tok): ?>
          <?php $url = url('/quote/' . $quote['public_ref'] . '/' . base64_encode(hex2bin($tok['token_hash']))); ?>
          <p style="font-size:.8125rem;word-break:break-all;">
            <strong>Sent <?= e(date('d M Y', strtotime($tok['created_at']))) ?>:</strong><br>
            <a href="<?= attr($url) ?>"><?= e($url) ?></a>
          </p>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

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
