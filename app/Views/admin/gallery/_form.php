<?php
$isEdit  = !empty($album);
$v       = fn(string $k, $fallback = '') => htmlspecialchars((string) (($old[$k] ?? '') !== '' ? $old[$k] : ($album[$k] ?? $fallback)), ENT_QUOTES);
$err     = fn(string $k) => !empty($errors[$k]) ? '<p class="form-error">' . e($errors[$k]) . '</p>' : '';
$checked = fn(string $k, $def = 0) => ($old[$k] ?? ($album[$k] ?? $def)) ? 'checked' : '';
$sel     = fn(string $field, string $val) => (($old[$field] ?? ($album[$field] ?? '')) === $val) ? 'selected' : '';

$segments = [
    'corporate'    => 'Corporate',
    'restaurant'   => 'Restaurant',
    'bridal'       => 'Bridal',
    'baby_shower'  => 'Baby Shower',
    'birthday'     => 'Birthday',
    'couples'      => 'Couples',
    'private'      => 'Private Party',
    'public_event' => 'Public Event',
    'other'        => 'Other',
];
?>

<div class="form-grid form-grid--2col">
  <!-- Left column -->
  <div>
    <div class="form-group">
      <label class="form-label">Title <span class="required">*</span></label>
      <input type="text" name="title" class="form-input" value="<?= $v('title') ?>" required
             oninput="if(!document.getElementById('slug-locked').checked){document.getElementById('slug-field').value=makeSlug(this.value)}">
      <?= $err('title') ?>
    </div>

    <div class="form-group">
      <label class="form-label">Slug</label>
      <div style="display:flex;gap:.5rem;align-items:center;">
        <input type="text" name="slug" id="slug-field" class="form-input" value="<?= $v('slug') ?>">
        <label style="white-space:nowrap;font-size:.875rem;">
          <input type="checkbox" id="slug-locked" <?= $isEdit ? 'checked' : '' ?>> Lock
        </label>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-input" rows="4"><?= $v('description') ?></textarea>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Segment</label>
        <select name="segment" class="form-input form-input--select">
          <?php foreach ($segments as $k => $l): ?>
          <option value="<?= e($k) ?>" <?= $sel('segment', $k) ?>><?= e($l) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Event type (free text)</label>
        <input type="text" name="event_type" class="form-input" value="<?= $v('event_type') ?>" placeholder="e.g. Baby shower">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Event date</label>
        <input type="date" name="event_date" class="form-input" value="<?= $v('event_date') ?>">
        <?= $err('event_date') ?>
      </div>
      <div class="form-group">
        <label class="form-label">Sort order</label>
        <input type="number" name="sort_order" class="form-input" value="<?= $v('sort_order', 0) ?>" min="0">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Venue</label>
        <input type="text" name="venue" class="form-input" value="<?= $v('venue') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Location</label>
        <input type="text" name="location" class="form-input" value="<?= $v('location') ?>" placeholder="e.g. Sandton">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Linked public event</label>
        <select name="linked_event_id" class="form-input form-input--select">
          <option value="">— none —</option>
          <?php foreach ($events as $ev): ?>
          <option value="<?= (int) $ev['id'] ?>" <?= $sel('linked_event_id', (string) $ev['id']) ?>>
            <?= e($ev['title']) ?> (<?= e($ev['event_date']) ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Linked booking</label>
        <select name="linked_booking_id" class="form-input form-input--select">
          <option value="">— none —</option>
          <?php foreach ($bookings as $bk): ?>
          <option value="<?= (int) $bk['id'] ?>" <?= $sel('linked_booking_id', (string) $bk['id']) ?>>
            <?= e($bk['public_ref']) ?> — <?= e($bk['customer_name']) ?> (<?= e($bk['event_date']) ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <!-- Right column -->
  <div>
    <div class="card" style="margin-bottom:1rem;">
      <div class="card__header"><h3>Status</h3></div>
      <div class="card__body">
        <?php
        $statuses = ['draft','scheduled','published','private','archived'];
        foreach ($statuses as $s):
        ?>
        <label style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;">
          <input type="radio" name="status" value="<?= e($s) ?>" <?= $sel('status', $s) === 'selected' || (($old['status'] ?? ($album['status'] ?? 'draft')) === $s) ? 'checked' : '' ?>>
          <?= e(ucfirst($s)) ?>
        </label>
        <?php endforeach; ?>

        <div class="form-group" style="margin-top:1rem;">
          <label class="form-label">Scheduled publish at</label>
          <input type="datetime-local" name="scheduled_at" class="form-input"
                 value="<?= $v('scheduled_at') ?>">
          <?= $err('scheduled_at') ?>
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:1rem;">
      <div class="card__header"><h3>Options</h3></div>
      <div class="card__body">
        <label style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;">
          <input type="checkbox" name="is_featured" value="1" <?= $checked('is_featured') ?>>
          Featured on gallery page
        </label>
        <label style="display:flex;align-items:center;gap:.5rem;">
          <input type="checkbox" name="is_downloadable" value="1" <?= $checked('is_downloadable') ?>>
          Allow image downloads (private links)
        </label>
      </div>
    </div>

    <div class="card" style="margin-bottom:1rem;">
      <div class="card__header"><h3>SEO</h3></div>
      <div class="card__body">
        <div class="form-group">
          <label class="form-label">Meta title</label>
          <input type="text" name="meta_title" class="form-input" value="<?= $v('meta_title') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Meta description</label>
          <textarea name="meta_description" class="form-input" rows="3"><?= $v('meta_description') ?></textarea>
        </div>
      </div>
    </div>

    <div style="text-align:right;">
      <button type="submit" class="btn btn--primary btn--lg">Save album</button>
    </div>
  </div>
</div>

<script>
function makeSlug(str) {
  return str.toLowerCase().replace(/[^a-z0-9\s-]/g,'').replace(/[\s-]+/g,'-').replace(/^-|-$/g,'');
}
</script>
