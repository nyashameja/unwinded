<div class="page-header">
  <div class="page-header__title"><h1>New Discount Code</h1></div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/discounts') ?>" class="btn btn--secondary">← Back</a>
  </div>
</div>

<?php foreach (flash()->getAll() as $ftype => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($ftype) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div style="max-width:520px;">
  <div class="card">
    <div class="card__body">
      <form method="POST" action="<?= url('/admin/discounts') ?>">
        <?= csrf_field() ?>

        <div class="form-group">
          <label class="form-label">Code <span class="required">*</span></label>
          <input type="text" name="code" class="form-input" placeholder="SUMMER20" required
                 style="text-transform:uppercase;" oninput="this.value=this.value.toUpperCase()">
          <p class="form-hint">Letters, numbers, dashes, underscores only.</p>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Type <span class="required">*</span></label>
            <select name="discount_type" class="form-input form-input--select">
              <?php foreach ($types as $t): ?>
              <option value="<?= e($t) ?>"><?= e(ucfirst($t)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Value <span class="required">*</span></label>
            <input type="number" name="discount_value" class="form-input" step="0.01" min="0.01" required placeholder="20">
            <p class="form-hint">% or Rand depending on type.</p>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Applies to</label>
          <select name="applies_to" class="form-input form-input--select">
            <?php foreach ($appliesTo as $a): ?>
            <option value="<?= e($a) ?>"><?= e(ucfirst($a)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Starts at</label>
            <input type="datetime-local" name="starts_at" class="form-input">
          </div>
          <div class="form-group">
            <label class="form-label">Expires at</label>
            <input type="datetime-local" name="expires_at" class="form-input">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Max uses (blank = unlimited)</label>
          <input type="number" name="max_uses" class="form-input" min="1">
        </div>

        <div class="form-group">
          <label class="form-label">Description</label>
          <input type="text" name="description" class="form-input" placeholder="Optional internal note">
        </div>

        <div class="form-group">
          <label><input type="checkbox" name="is_active" value="1" checked> Active</label>
        </div>

        <button type="submit" class="btn btn--primary">Create code</button>
      </form>
    </div>
  </div>
</div>
