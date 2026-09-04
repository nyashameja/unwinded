<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Edit Booking <?= e($booking['public_ref']) ?></h1>
  <div class="page-header__actions">
    <a href="<?= url('/admin/bookings/' . $booking['id']) ?>" class="btn btn--secondary">View</a>
    <a href="<?= url('/admin/bookings') ?>" class="btn btn--secondary">&larr; All bookings</a>
  </div>
</div>

<form method="POST" action="<?= url('/admin/bookings/' . $booking['id']) ?>">
  <?= csrf_field() ?>

  <div class="grid grid--2col" style="gap:1.5rem;align-items:start;">
    <div>
      <div class="card" style="margin-bottom:1.5rem;">
        <div class="card__header"><h2>Event details</h2></div>
        <div class="card__body">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Event type</label>
              <input type="text" name="event_type" class="form-input" value="<?= attr($old['event_type'] ?? $booking['event_type'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Event date <span class="required">*</span></label>
              <input type="date" name="event_date" class="form-input <?= !empty($errors['event_date']) ? 'form-input--error' : '' ?>"
                     value="<?= attr($old['event_date'] ?? $booking['event_date']) ?>" required>
              <?php if (!empty($errors['event_date'])): ?><p class="form-error"><?= e($errors['event_date']) ?></p><?php endif; ?>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Start time</label>
              <input type="time" name="start_time" class="form-input" value="<?= attr($old['start_time'] ?? $booking['start_time'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">End time</label>
              <input type="time" name="end_time" class="form-input" value="<?= attr($old['end_time'] ?? $booking['end_time'] ?? '') ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Guest count</label>
              <input type="number" name="guest_count" class="form-input" min="1" value="<?= attr($old['guest_count'] ?? $booking['guest_count']) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Package</label>
              <select name="package_id" class="form-input form-input--select">
                <option value="">— None —</option>
                <?php foreach ($packages as $p): ?>
                  <option value="<?= $p['id'] ?>" <?= ($old['package_id'] ?? $booking['package_id']) == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Venue</label>
            <input type="text" name="venue_name" class="form-input" value="<?= attr($old['venue_name'] ?? $booking['venue_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Venue address</label>
            <textarea name="venue_address" class="form-input form-input--textarea" rows="2"><?= e($old['venue_address'] ?? $booking['venue_address'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Assigned to</label>
            <select name="assigned_to" class="form-input form-input--select">
              <option value="">— Unassigned —</option>
              <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= ($old['assigned_to'] ?? $booking['assigned_to'] ?? 0) == $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:1.5rem;">
        <div class="card__header"><h2>Financials</h2></div>
        <div class="card__body">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Deposit (R)</label>
              <input type="number" name="deposit_cents" class="form-input" step="0.01" min="0"
                     value="<?= attr(number_format(($old['deposit_cents'] ?? $booking['deposit_cents']) / 100, 2, '.', '')) ?>">
              <p class="form-hint">Stored in cents — enter rand value.</p>
            </div>
            <div class="form-group">
              <label class="form-label">Balance due date</label>
              <input type="date" name="balance_due_date" class="form-input"
                     value="<?= attr($old['balance_due_date'] ?? $booking['balance_due_date'] ?? '') ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Deposit soft deadline</label>
              <input type="datetime-local" name="deposit_soft_deadline" class="form-input"
                     value="<?= attr(substr($old['deposit_soft_deadline'] ?? $booking['deposit_soft_deadline'] ?? '', 0, 16)) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Deposit hard deadline</label>
              <input type="datetime-local" name="deposit_hard_deadline" class="form-input"
                     value="<?= attr(substr($old['deposit_hard_deadline'] ?? $booking['deposit_hard_deadline'] ?? '', 0, 16)) ?>">
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card__header"><h2>Notes</h2></div>
        <div class="card__body">
          <div class="form-group">
            <label class="form-label">Creative direction</label>
            <textarea name="creative_direction" class="form-input form-input--textarea" rows="3"><?= e($old['creative_direction'] ?? $booking['creative_direction'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Internal notes</label>
            <textarea name="internal_notes" class="form-input form-input--textarea" rows="3"><?= e($old['internal_notes'] ?? $booking['internal_notes'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <div>
      <!-- Line items -->
      <div class="card" style="margin-bottom:1.5rem;">
        <div class="card__header"><h2>Line items</h2></div>
        <div class="card__body">
          <table class="data-table" id="line-items-table">
            <thead><tr><th style="width:50%">Description</th><th>Unit price (R)</th><th>Qty</th><th>Total</th><th style="width:40px"></th></tr></thead>
            <tbody id="line-items-body">
              <?php if (empty($items)): ?>
                <?php $items = [['description'=>'','unit_price_cents'=>0,'quantity'=>1,'line_total_cents'=>0]]; ?>
              <?php endif; ?>
              <?php foreach ($items as $item): ?>
              <tr class="line-item-row">
                <td><input type="text" name="line_desc[]" class="form-input" value="<?= attr($item['description']) ?>"></td>
                <td><input type="number" name="line_price[]" class="form-input line-price" step="0.01" min="0" value="<?= attr(number_format($item['unit_price_cents'] / 100, 2, '.', '')) ?>"></td>
                <td><input type="number" name="line_qty[]" class="form-input line-qty" step="0.01" min="0.01" value="<?= attr($item['quantity']) ?>"></td>
                <td class="line-total">R <?= number_format($item['line_total_cents'] / 100, 2, '.', '') ?></td>
                <td><button type="button" class="btn btn--xs btn--danger remove-line-btn">&times;</button></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <button type="button" id="add-line-btn" class="btn btn--xs btn--secondary" style="margin-top:.5rem;">+ Add line</button>
          <div style="text-align:right;margin-top:1rem;font-weight:600;">
            Subtotal: R <span id="subtotal-display">0.00</span>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn--primary btn--lg">Save changes</button>
    </div>
  </div>
</form>

<script>
(function () {
  const tbody = document.getElementById('line-items-body');
  const addBtn = document.getElementById('add-line-btn');
  const subEl = document.getElementById('subtotal-display');

  function rowTemplate() {
    return `<tr class="line-item-row">
      <td><input type="text" name="line_desc[]" class="form-input"></td>
      <td><input type="number" name="line_price[]" class="form-input line-price" step="0.01" min="0" value="0.00"></td>
      <td><input type="number" name="line_qty[]" class="form-input line-qty" step="0.01" min="0.01" value="1"></td>
      <td class="line-total">R 0.00</td>
      <td><button type="button" class="btn btn--xs btn--danger remove-line-btn">&times;</button></td>
    </tr>`;
  }

  function recalc() {
    let sub = 0;
    tbody.querySelectorAll('.line-item-row').forEach(row => {
      const price = parseFloat(row.querySelector('.line-price').value) || 0;
      const qty   = parseFloat(row.querySelector('.line-qty').value)   || 1;
      const t = price * qty;
      row.querySelector('.line-total').textContent = 'R ' + t.toFixed(2);
      sub += t;
    });
    subEl.textContent = sub.toFixed(2);
  }

  addBtn.addEventListener('click', () => { tbody.insertAdjacentHTML('beforeend', rowTemplate()); recalc(); });
  tbody.addEventListener('click', e => {
    if (e.target.classList.contains('remove-line-btn')) { e.target.closest('tr').remove(); recalc(); }
  });
  tbody.addEventListener('input', recalc);
  recalc();
})();
</script>
