<!-- Line items -->
<div class="card" style="margin-bottom:1.5rem;">
  <div class="card__header">
    <h2>Line items</h2>
  </div>
  <div class="card__body">
    <table class="data-table" id="line-items-table">
      <thead>
        <tr>
          <th style="width:40%">Description</th>
          <th>Type</th>
          <th style="width:14%">Unit price (R)</th>
          <th style="width:10%">Qty</th>
          <th style="width:14%">Total</th>
          <th style="width:40px"></th>
        </tr>
      </thead>
      <tbody id="line-items-body">
        <?php $existingItems = $items ?? []; ?>
        <?php if (empty($existingItems)): ?>
          <?php $existingItems = [['type'=>'custom','description'=>'','unit_price_cents'=>0,'quantity'=>1,'line_total_cents'=>0]]; ?>
        <?php endif; ?>
        <?php foreach ($existingItems as $item): ?>
        <tr class="line-item-row">
          <td><input type="text" name="line_desc[]" class="form-input" value="<?= attr($item['description']) ?>" required></td>
          <td>
            <select name="line_type[]" class="form-input form-input--select" style="min-width:100px;">
              <?php foreach (['custom','package','extra','travel','discount'] as $t): ?>
                <option value="<?= $t ?>" <?= ($item['type'] ?? 'custom') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td><input type="number" name="line_price[]" class="form-input line-price" step="0.01" min="0" value="<?= attr(number_format($item['unit_price_cents'] / 100, 2, '.', '')) ?>"></td>
          <td><input type="number" name="line_qty[]" class="form-input line-qty" step="0.01" min="0.01" value="<?= attr($item['quantity']) ?>"></td>
          <td class="line-total">R <?= number_format($item['line_total_cents'] / 100, 2, '.', '') ?></td>
          <td><button type="button" class="btn btn--xs btn--danger remove-line-btn" title="Remove">&times;</button></td>
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

<!-- Discount + deposit -->
<div class="form-row">
  <div class="form-group">
    <label class="form-label">Discount type</label>
    <select name="discount_type" id="discount_type" class="form-input form-input--select">
      <?php foreach (['none'=>'None','percentage'=>'Percentage (%)','fixed'=>'Fixed amount (R)'] as $v=>$l): ?>
        <option value="<?= $v ?>" <?= ($quote['discount_type'] ?? $old['discount_type'] ?? 'none') === $v ? 'selected' : '' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label class="form-label">Discount value</label>
    <input type="number" name="discount_value" class="form-input" step="0.01" min="0"
           value="<?= attr($quote['discount_value'] ?? $old['discount_value'] ?? '0') ?>">
  </div>
</div>

<div class="form-row">
  <div class="form-group">
    <label class="form-label">Deposit type</label>
    <select name="deposit_type" class="form-input form-input--select">
      <option value="percentage" <?= ($quote['deposit_type'] ?? $old['deposit_type'] ?? 'percentage') === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
      <option value="fixed" <?= ($quote['deposit_type'] ?? $old['deposit_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed amount (R)</option>
    </select>
  </div>
  <div class="form-group">
    <label class="form-label">Deposit value</label>
    <input type="number" name="deposit_value" class="form-input" step="0.01" min="0"
           value="<?= attr($quote['deposit_value'] ?? $old['deposit_value'] ?? '50') ?>">
    <p class="form-hint">Default 50% of total.</p>
  </div>
</div>

<script>
(function () {
  const tbody  = document.getElementById('line-items-body');
  const addBtn = document.getElementById('add-line-btn');
  const subEl  = document.getElementById('subtotal-display');

  function rowTemplate() {
    return `<tr class="line-item-row">
      <td><input type="text" name="line_desc[]" class="form-input" required></td>
      <td><select name="line_type[]" class="form-input form-input--select" style="min-width:100px;">
        <option value="custom">Custom</option><option value="package">Package</option>
        <option value="extra">Extra</option><option value="travel">Travel</option><option value="discount">Discount</option>
      </select></td>
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
      const total = price * qty;
      row.querySelector('.line-total').textContent = 'R ' + total.toFixed(2);
      sub += total;
    });
    subEl.textContent = sub.toFixed(2);
  }

  addBtn.addEventListener('click', () => {
    tbody.insertAdjacentHTML('beforeend', rowTemplate());
    recalc();
  });

  tbody.addEventListener('click', e => {
    if (e.target.classList.contains('remove-line-btn')) {
      e.target.closest('tr').remove();
      recalc();
    }
  });

  tbody.addEventListener('input', recalc);
  recalc();
})();
</script>
