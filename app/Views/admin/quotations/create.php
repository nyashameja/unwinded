<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">New Quotation</h1>
  <a href="<?= url('/admin/quotations') ?>" class="btn btn--secondary">&larr; Back</a>
</div>

<form method="POST" action="<?= url('/admin/quotations') ?>">
  <?= csrf_field() ?>
  <?php if ($qr): ?>
    <input type="hidden" name="request_id" value="<?= (int) $qr['id'] ?>">
    <div class="alert alert--info" style="margin-bottom:1.5rem;">
      Building quote for quote request <strong><?= e($qr['public_ref']) ?></strong> from <?= e($qr['customer_name']) ?>.
    </div>
  <?php endif; ?>

  <div class="grid grid--2col" style="gap:1.5rem;align-items:start;">
    <div>
      <div class="card" style="margin-bottom:1.5rem;">
        <div class="card__header"><h2>Customer &amp; event</h2></div>
        <div class="card__body">
          <?php if (!empty($errors['customer_id'])): ?>
            <p class="form-error"><?= e($errors['customer_id']) ?></p>
          <?php endif; ?>
          <div class="form-group">
            <label class="form-label">Customer <span class="required">*</span></label>
            <select name="customer_id" class="form-input form-input--select" required>
              <option value="">— Select customer —</option>
              <?php foreach ($customers as $c): ?>
                <?php $selected = ($qr && $qr['customer_id'] == $c['id']) || ($old['customer_id'] ?? '') == $c['id']; ?>
                <option value="<?= $c['id'] ?>" <?= $selected ? 'selected' : '' ?>>
                  <?= e($c['name']) ?> (<?= e($c['email']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Event type</label>
              <input type="text" name="event_type" class="form-input"
                     value="<?= attr($old['event_type'] ?? $qr['event_type'] ?? '') ?>" placeholder="e.g. Birthday">
            </div>
            <div class="form-group">
              <label class="form-label">Event date</label>
              <input type="date" name="event_date" class="form-input"
                     value="<?= attr($old['event_date'] ?? $qr['preferred_date'] ?? '') ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Guest count</label>
              <input type="number" name="guest_count" class="form-input" min="1"
                     value="<?= attr($old['guest_count'] ?? $qr['guest_count'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Valid until</label>
              <input type="date" name="valid_until" class="form-input"
                     value="<?= attr($old['valid_until'] ?? date('Y-m-d', strtotime('+14 days'))) ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Venue</label>
            <input type="text" name="venue_name" class="form-input"
                   value="<?= attr($old['venue_name'] ?? $qr['venue_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Venue address</label>
            <textarea name="venue_address" class="form-input form-input--textarea" rows="2"><?= e($old['venue_address'] ?? $qr['venue_address'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Assigned to</label>
            <select name="assigned_to" class="form-input form-input--select">
              <option value="">— Unassigned —</option>
              <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= ($old['assigned_to'] ?? '') == $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:1.5rem;">
        <div class="card__header"><h2>Notes</h2></div>
        <div class="card__body">
          <div class="form-group">
            <label class="form-label">Notes to customer</label>
            <textarea name="notes_to_customer" class="form-input form-input--textarea" rows="4"><?= e($old['notes_to_customer'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Internal notes</label>
            <textarea name="internal_notes" class="form-input form-input--textarea" rows="3"><?= e($old['internal_notes'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <div>
      <?php $quote = []; $items = []; ?>
      <?php include __DIR__ . '/_form.php'; ?>

      <div style="margin-top:1.5rem;">
        <button type="submit" class="btn btn--primary btn--lg">Create quote</button>
      </div>
    </div>
  </div>
</form>
