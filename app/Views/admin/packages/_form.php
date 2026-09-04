<div class="form-grid form-grid--2col" style="gap:1.5rem;">

  <div>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2 style="margin:0;">Package Details</h2></div>
      <div class="card__body">

        <div class="form-group">
          <label class="form-label">Name <span class="required">*</span></label>
          <input type="text" name="name" class="form-input" value="<?= e($package['name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Slug</label>
          <input type="text" name="slug" id="pkg-slug" class="form-input" value="<?= e($package['slug'] ?? '') ?>" placeholder="auto-generated">
          <p class="form-hint">Lowercase letters, numbers, hyphens only.</p>
        </div>

        <div class="form-group">
          <label class="form-label">Tagline</label>
          <input type="text" name="tagline" class="form-input" value="<?= e($package['tagline'] ?? '') ?>" maxlength="500">
        </div>

        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-input" rows="6"><?= e($package['description'] ?? '') ?></textarea>
        </div>

      </div>
    </div>

    <div class="card">
      <div class="card__header"><h2 style="margin:0;">Features</h2></div>
      <div class="card__body">
        <div id="features-list">
          <?php $features = $features ?? []; ?>
          <?php if (empty($features)): ?>
          <div class="feature-row" style="display:flex;gap:.5rem;margin-bottom:.5rem;">
            <input type="text" name="feature_label[]" class="form-input" placeholder="e.g. Venue styling" style="flex:1;">
            <label style="display:flex;align-items:center;gap:.25rem;white-space:nowrap;">
              <input type="checkbox" name="feature_included[]" value="1" checked> Included
            </label>
          </div>
          <?php else: ?>
          <?php foreach ($features as $f): ?>
          <div class="feature-row" style="display:flex;gap:.5rem;margin-bottom:.5rem;">
            <input type="text" name="feature_label[]" class="form-input" value="<?= e($f['label']) ?>" style="flex:1;">
            <label style="display:flex;align-items:center;gap:.25rem;white-space:nowrap;">
              <input type="checkbox" name="feature_included[]" value="1" <?= $f['is_included'] ? 'checked' : '' ?>> Included
            </label>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <button type="button" class="btn btn--xs btn--secondary" onclick="addFeature()">+ Add feature</button>
      </div>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card__header"><h2 style="margin:0;">Pricing &amp; Settings</h2></div>
      <div class="card__body">

        <div class="form-group">
          <label class="form-label">Pricing model</label>
          <select name="pricing_model" id="pricing-model" class="form-input form-input--select" onchange="togglePrice()">
            <?php foreach ($pricingModels as $pm): ?>
            <option value="<?= e($pm) ?>" <?= ($package['pricing_model'] ?? '') === $pm ? 'selected' : '' ?>>
              <?= e(ucfirst(str_replace('_',' ',$pm))) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" id="price-field">
          <label class="form-label">Base price (R)</label>
          <input type="number" name="base_price" class="form-input" step="0.01" min="0"
                 value="<?= isset($package['base_price_cents']) ? number_format($package['base_price_cents'] / 100, 2, '.', '') : '0' ?>">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Min guests</label>
            <input type="number" name="min_guests" class="form-input" min="1" value="<?= (int) ($package['min_guests'] ?? 1) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Max guests</label>
            <input type="number" name="max_guests" class="form-input" min="1" value="<?= ($package['max_guests'] ?? '') ?: '' ?>" placeholder="Unlimited">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Highlight colour</label>
          <input type="color" name="highlight_colour" class="form-input" style="height:2.5rem;padding:.25rem;"
                 value="<?= e($package['highlight_colour'] ?? '#ffffff') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Sort order</label>
          <input type="number" name="sort_order" class="form-input" value="<?= (int) ($package['sort_order'] ?? 0) ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" class="form-input form-input--select">
            <option value="draft"      <?= ($package['status'] ?? '') === 'draft'      ? 'selected' : '' ?>>Draft</option>
            <option value="published"  <?= ($package['status'] ?? '') === 'published'  ? 'selected' : '' ?>>Published</option>
          </select>
        </div>

        <div class="form-group">
          <label><input type="checkbox" name="is_featured" value="1" <?= !empty($package['is_featured']) ? 'checked' : '' ?>> Featured</label>
        </div>

      </div>
    </div>
  </div>

</div>

<script>
function addFeature() {
  const list = document.getElementById('features-list');
  const div  = document.createElement('div');
  div.className = 'feature-row';
  div.style = 'display:flex;gap:.5rem;margin-bottom:.5rem;';
  div.innerHTML = '<input type="text" name="feature_label[]" class="form-input" placeholder="Feature label" style="flex:1;"><label style="display:flex;align-items:center;gap:.25rem;white-space:nowrap;"><input type="checkbox" name="feature_included[]" value="1" checked> Included</label>';
  list.appendChild(div);
}
function togglePrice() {
  const m = document.getElementById('pricing-model').value;
  document.getElementById('price-field').style.display = m === 'price_on_request' ? 'none' : '';
}
document.addEventListener('DOMContentLoaded', togglePrice);
</script>
