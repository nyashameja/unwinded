<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Quote Request <?= e($qr['public_ref']) ?></h1>
  <div class="page-header__actions">
    <a href="<?= url('/admin/quotations/create?request_id=' . $qr['id']) ?>" class="btn btn--primary">Create quote</a>
    <a href="<?= url('/admin/quote-requests') ?>" class="btn btn--secondary">&larr; All requests</a>
  </div>
</div>

<div class="grid grid--2col" style="gap:1.5rem;align-items:start;">

  <!-- Left: detail + update form -->
  <div>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Customer</h2></div>
      <div class="card__body">
        <table class="data-table">
          <tr><th>Name</th><td><?= e($qr['customer_name']) ?></td></tr>
          <tr><th>Email</th><td><a href="mailto:<?= attr($qr['customer_email']) ?>"><?= e($qr['customer_email']) ?></a></td></tr>
          <?php if ($qr['customer_phone']): ?><tr><th>Phone</th><td><?= e($qr['customer_phone']) ?></td></tr><?php endif; ?>
          <?php if ($qr['customer_whatsapp']): ?><tr><th>WhatsApp</th><td><?= e($qr['customer_whatsapp']) ?></td></tr><?php endif; ?>
          <?php if ($qr['customer_company']): ?><tr><th>Company</th><td><?= e($qr['customer_company']) ?></td></tr><?php endif; ?>
          <tr><th>Preferred contact</th><td><?= e(ucfirst($qr['preferred_contact'])) ?></td></tr>
        </table>
      </div>
    </div>

    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Event details</h2></div>
      <div class="card__body">
        <table class="data-table">
          <?php if ($qr['event_type']): ?><tr><th>Event type</th><td><?= e(ucwords(str_replace('_',' ',$qr['event_type']))) ?></td></tr><?php endif; ?>
          <?php if ($qr['preferred_date']): ?><tr><th>Preferred date</th><td><?= e(date('d M Y', strtotime($qr['preferred_date']))) ?></td></tr><?php endif; ?>
          <?php if ($qr['alternative_date']): ?><tr><th>Alternative date</th><td><?= e(date('d M Y', strtotime($qr['alternative_date']))) ?></td></tr><?php endif; ?>
          <?php if ($qr['start_time']): ?><tr><th>Start time</th><td><?= e($qr['start_time']) ?></td></tr><?php endif; ?>
          <?php if ($qr['guest_count']): ?><tr><th>Guest count</th><td><?= e($qr['guest_count']) ?></td></tr><?php endif; ?>
          <?php if ($qr['venue_name']): ?><tr><th>Venue</th><td><?= e($qr['venue_name']) ?></td></tr><?php endif; ?>
          <?php if ($qr['budget_estimate_cents']): ?><tr><th>Budget estimate</th><td><?= e(money($qr['budget_estimate_cents'])) ?></td></tr><?php endif; ?>
        </table>
        <?php if ($qr['preferred_artwork']): ?>
          <p style="margin-top:1rem;"><strong>Artwork/theme:</strong> <?= nl2br(e($qr['preferred_artwork'])) ?></p>
        <?php endif; ?>
        <?php if ($qr['extra_notes']): ?>
          <div class="prose" style="margin-top:1rem;"><strong>Notes:</strong><br><?= nl2br(e($qr['extra_notes'])) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Linked quotes -->
    <?php if ($quotes): ?>
    <div class="card">
      <div class="card__header"><h2>Quotes</h2></div>
      <div class="card__body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Ref</th><th>Status</th><th>Total</th><th>Created</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($quotes as $q): ?>
            <tr>
              <td><a href="<?= url('/admin/quotations/' . $q['id']) ?>"><?= e($q['public_ref']) ?></a></td>
              <td><span class="badge badge--neutral"><?= e($q['status']) ?></span></td>
              <td><?= e(money($q['total_cents'])) ?></td>
              <td><?= e(date('d M Y', strtotime($q['created_at']))) ?></td>
              <td><a href="<?= url('/admin/quotations/' . $q['id']) ?>" class="btn btn--xs btn--secondary">View</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Right: status update, notes, history -->
  <div>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Update status</h2></div>
      <div class="card__body">
        <form method="POST" action="<?= url('/admin/quote-requests/' . $qr['id']) ?>">
          <?= csrf_field() ?>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-input form-input--select">
              <?php foreach ($statuses as $s): ?>
                <option value="<?= $s ?>" <?= $qr['status'] === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $s)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Assigned to</label>
            <select name="assigned_to" class="form-input form-input--select">
              <option value="">— Unassigned —</option>
              <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= ($qr['assigned_to'] ?? 0) == $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn--primary">Update</button>
        </form>
      </div>
    </div>

    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Add note</h2></div>
      <div class="card__body">
        <form method="POST" action="<?= url('/admin/quote-requests/' . $qr['id'] . '/note') ?>">
          <?= csrf_field() ?>
          <div class="form-group">
            <textarea name="note" class="form-input form-input--textarea" rows="3" placeholder="Internal note…"></textarea>
          </div>
          <button type="submit" class="btn btn--secondary">Add note</button>
        </form>
      </div>
    </div>

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
