<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Edit Quote <?= e($quote['public_ref']) ?></h1>
  <div class="page-header__actions">
    <a href="<?= url('/admin/quotations/' . $quote['id']) ?>" class="btn btn--secondary">View</a>
    <a href="<?= url('/admin/quotations') ?>" class="btn btn--secondary">&larr; All quotes</a>
  </div>
</div>

<?php if ($quote['status'] !== 'draft'): ?>
  <div class="alert alert--warning">This quote is <?= e($quote['status']) ?> and cannot be edited.</div>
<?php else: ?>
<form method="POST" action="<?= url('/admin/quotations/' . $quote['id']) ?>">
  <?= csrf_field() ?>

  <div class="grid grid--2col" style="gap:1.5rem;align-items:start;">
    <div>
      <div class="card" style="margin-bottom:1.5rem;">
        <div class="card__header"><h2>Customer &amp; event</h2></div>
        <div class="card__body">
          <div class="form-group">
            <label class="form-label">Customer</label>
            <select name="customer_id" class="form-input form-input--select" required>
              <?php foreach ($customers as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $quote['customer_id'] == $c['id'] ? 'selected' : '' ?>>
                  <?= e($c['name']) ?> (<?= e($c['email']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Event type</label>
              <input type="text" name="event_type" class="form-input" value="<?= attr($quote['event_type'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Event date</label>
              <input type="date" name="event_date" class="form-input" value="<?= attr($quote['event_date'] ?? '') ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Guest count</label>
              <input type="number" name="guest_count" class="form-input" min="1" value="<?= attr($quote['guest_count'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Valid until</label>
              <input type="date" name="valid_until" class="form-input" value="<?= attr($quote['valid_until'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Venue</label>
            <input type="text" name="venue_name" class="form-input" value="<?= attr($quote['venue_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Venue address</label>
            <textarea name="venue_address" class="form-input form-input--textarea" rows="2"><?= e($quote['venue_address'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Assigned to</label>
            <select name="assigned_to" class="form-input form-input--select">
              <option value="">— Unassigned —</option>
              <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= ($quote['assigned_to'] ?? 0) == $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card__header"><h2>Notes</h2></div>
        <div class="card__body">
          <div class="form-group">
            <label class="form-label">Notes to customer</label>
            <textarea name="notes_to_customer" class="form-input form-input--textarea" rows="4"><?= e($quote['notes_to_customer'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Internal notes</label>
            <textarea name="internal_notes" class="form-input form-input--textarea" rows="3"><?= e($quote['internal_notes'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <div>
      <?php include __DIR__ . '/_form.php'; ?>
      <div style="margin-top:1.5rem;">
        <button type="submit" class="btn btn--primary btn--lg">Save changes</button>
      </div>
    </div>
  </div>
</form>
<?php endif; ?>
