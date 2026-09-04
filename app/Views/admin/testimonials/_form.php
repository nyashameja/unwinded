<?php
$isEdit  = !empty($testimonial);
$v = fn(string $k, $def = '') => e((string) (($old[$k] ?? '') !== '' ? $old[$k] : ($testimonial[$k] ?? $def)));
$err = fn(string $k) => !empty($errors[$k]) ? '<p class="form-error">' . e($errors[$k]) . '</p>' : '';
$chk = fn(string $k) => ($old[$k] ?? ($testimonial[$k] ?? 0)) ? 'checked' : '';
?>

<div class="form-grid form-grid--2col">
  <div>
    <div class="form-group">
      <label class="form-label">Customer name <span class="required">*</span></label>
      <input type="text" name="customer_name" class="form-input" value="<?= $v('customer_name') ?>" required>
      <?= $err('customer_name') ?>
    </div>
    <div class="form-group">
      <label class="form-label">Title / role</label>
      <input type="text" name="customer_title" class="form-input" value="<?= $v('customer_title') ?>" placeholder="e.g. Corporate client, Birthday guest">
    </div>
    <div class="form-group">
      <label class="form-label">Testimonial <span class="required">*</span></label>
      <textarea name="body" class="form-input" rows="6" required><?= $v('body') ?></textarea>
      <?= $err('body') ?>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Star rating</label>
        <select name="rating" class="form-input form-input--select">
          <?php for ($i = 5; $i >= 1; $i--): ?>
          <option value="<?= $i ?>" <?= (int)($old['rating'] ?? ($testimonial['rating'] ?? 5)) === $i ? 'selected' : '' ?>>
            <?= $i ?> star<?= $i !== 1 ? 's' : '' ?>
          </option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Event type</label>
        <input type="text" name="event_type" class="form-input" value="<?= $v('event_type') ?>" placeholder="e.g. Birthday, Corporate">
      </div>
    </div>
  </div>

  <div>
    <div class="card" style="margin-bottom:1rem;">
      <div class="card__header"><h3>Photo (optional)</h3></div>
      <div class="card__body">
        <?php $currentPhotoId = (int)($old['photo_media_id'] ?? ($testimonial['photo_media_id'] ?? 0)); ?>
        <?php if (!empty($testimonial['photo_url'])): ?>
          <img src="<?= attr($testimonial['photo_url']) ?>" alt="" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:.75rem;">
        <?php endif; ?>
        <select name="photo_media_id" class="form-input form-input--select">
          <option value="">— no photo —</option>
          <?php foreach ($media as $m): ?>
          <option value="<?= (int) $m['id'] ?>" <?= (int) $m['id'] === $currentPhotoId ? 'selected' : '' ?>>
            <?= e($m['file_name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="card" style="margin-bottom:1rem;">
      <div class="card__header"><h3>Publishing</h3></div>
      <div class="card__body">
        <label style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;">
          <input type="checkbox" name="is_published" value="1" <?= $chk('is_published') ?>>
          Published (visible on website)
        </label>
        <label style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;">
          <input type="checkbox" name="is_featured" value="1" <?= $chk('is_featured') ?>>
          Featured (shown on homepage)
        </label>
        <div class="form-group">
          <label class="form-label">Sort order</label>
          <input type="number" name="sort_order" class="form-input" value="<?= $v('sort_order', 0) ?>" min="0">
        </div>
      </div>
    </div>

    <button type="submit" class="btn btn--primary btn--lg" style="width:100%;">Save testimonial</button>
  </div>
</div>
