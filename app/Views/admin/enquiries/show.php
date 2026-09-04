<div class="page-header">
  <div class="page-header__title">
    <h1>Enquiry <?= e($enquiry['ref']) ?></h1>
    <?php $sc = match($enquiry['status']) {
      'new' => 'warning', 'in_progress' => 'info', 'resolved' => 'success', 'spam' => 'danger', default => 'neutral'
    }; ?>
    <span class="badge badge--<?= $sc ?>"><?= e(ucfirst(str_replace('_',' ',$enquiry['status']))) ?></span>
  </div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/enquiries') ?>" class="btn btn--secondary">← Back</a>
  </div>
</div>

<?php foreach (flash()->getAll() as $type => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="form-grid form-grid--2col" style="gap:1.5rem;">

  <!-- Enquiry details -->
  <div>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2 style="margin:0;">Message</h2></div>
      <div class="card__body">
        <table class="data-table" style="margin-bottom:1rem;">
          <tr><th>Name</th><td><?= e($enquiry['name']) ?></td></tr>
          <tr><th>Email</th><td><a href="mailto:<?= e($enquiry['email']) ?>"><?= e($enquiry['email']) ?></a></td></tr>
          <?php if ($enquiry['phone']): ?>
          <tr><th>Phone</th><td><?= e($enquiry['phone']) ?></td></tr>
          <?php endif; ?>
          <tr><th>Subject</th><td><?= e($enquiry['subject']) ?></td></tr>
          <tr><th>Source</th><td><?= e($enquiry['source'] ?? '—') ?></td></tr>
          <tr><th>Received</th><td><?= e(date('d M Y H:i', strtotime($enquiry['created_at']))) ?></td></tr>
        </table>
        <div style="background:#f9f9f9;border-radius:6px;padding:1rem;white-space:pre-wrap;font-size:.95rem;">
          <?= e($enquiry['message']) ?>
        </div>
      </div>
    </div>

    <?php if ($enquiry['reply_body']): ?>
    <div class="card">
      <div class="card__header"><h2 style="margin:0;">Reply sent</h2></div>
      <div class="card__body">
        <p style="font-size:.8rem;color:#666;">Sent: <?= e($enquiry['replied_at'] ? date('d M Y H:i', strtotime($enquiry['replied_at'])) : '—') ?></p>
        <div style="background:#f0fff4;border-radius:6px;padding:1rem;white-space:pre-wrap;font-size:.95rem;">
          <?= e($enquiry['reply_body']) ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Actions -->
  <div>
    <div class="card">
      <div class="card__header"><h2 style="margin:0;">Actions</h2></div>
      <div class="card__body">
        <form method="POST" action="<?= url('/admin/enquiries/' . (int) $enquiry['id'] . '/status') ?>">
          <?= csrf_field() ?>

          <div class="form-group">
            <label class="form-label">Update status</label>
            <select name="status" class="form-input form-input--select">
              <?php foreach ($statuses as $s): ?>
              <option value="<?= e($s) ?>" <?= $enquiry['status'] === $s ? 'selected' : '' ?>>
                <?= e(ucfirst(str_replace('_',' ',$s))) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Assign to</label>
            <select name="assigned_to" class="form-input form-input--select">
              <option value="">— unassigned —</option>
              <?php foreach ($users as $u): ?>
              <option value="<?= (int) $u['id'] ?>" <?= (int) $enquiry['assigned_to'] === (int) $u['id'] ? 'selected' : '' ?>>
                <?= e($u['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Reply email (optional — sends email to <?= e($enquiry['email']) ?>)</label>
            <textarea name="reply_body" class="form-input" rows="6" placeholder="Type a reply to send to the customer…"><?= e($enquiry['reply_body'] ?? '') ?></textarea>
          </div>

          <button type="submit" class="btn btn--primary btn--block">Save &amp; send reply</button>
        </form>
      </div>
    </div>
  </div>

</div>
