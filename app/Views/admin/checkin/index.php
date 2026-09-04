<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Check-in — <?= e($event['title']) ?></h1>
  <a href="<?= url('/admin/events/' . $event['id'] . '/edit') ?>" class="btn btn--secondary">&larr; Event</a>
</div>

<!-- Stats -->
<div class="grid grid--3col" style="gap:1rem;margin-bottom:1.5rem;">
  <div class="card">
    <div class="card__body" style="text-align:center;">
      <p style="font-size:2.5rem;font-weight:700;margin:0;"><?= (int) ($stats['checked_in'] ?? 0) ?></p>
      <p style="margin:0;color:var(--color-text-secondary,#666);">Checked in</p>
    </div>
  </div>
  <div class="card">
    <div class="card__body" style="text-align:center;">
      <p style="font-size:2.5rem;font-weight:700;margin:0;"><?= (int) ($stats['remaining'] ?? 0) ?></p>
      <p style="margin:0;color:var(--color-text-secondary,#666);">Remaining</p>
    </div>
  </div>
  <div class="card">
    <div class="card__body" style="text-align:center;">
      <p style="font-size:2.5rem;font-weight:700;margin:0;"><?= (int) ($stats['total_sold'] ?? 0) ?></p>
      <p style="margin:0;color:var(--color-text-secondary,#666);">Total sold</p>
    </div>
  </div>
</div>

<div class="grid grid--2col" style="gap:1.5rem;align-items:start;">

  <!-- Check-in panel -->
  <div>
    <div class="card">
      <div class="card__header"><h2>Scan / manual entry</h2></div>
      <div class="card__body">
        <div id="checkin-result" style="display:none;margin-bottom:1rem;padding:1rem;border-radius:4px;"></div>

        <div class="form-group">
          <label class="form-label">Ticket UID</label>
          <input type="text" id="ticket-uid" class="form-input" placeholder="Scan QR or type UID"
                 autocomplete="off" autofocus style="font-family:monospace;">
          <p class="form-hint">Ticket UIDs start with TKT-. Scan the QR code or type manually.</p>
        </div>

        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;">
          <button type="button" class="btn btn--primary" onclick="doCheckin('qr_scan')">Check in (QR)</button>
          <button type="button" class="btn btn--secondary" onclick="doCheckin('manual')">Check in (manual)</button>
        </div>

        <details>
          <summary style="cursor:pointer;color:var(--color-danger,#dc3545);">Override (already-used ticket)</summary>
          <div style="margin-top:.75rem;">
            <div class="form-group">
              <label class="form-label">Override reason <span class="required">*</span></label>
              <input type="text" id="override-reason" class="form-input" placeholder="e.g. System error, manager approval">
            </div>
            <button type="button" class="btn btn--danger" onclick="doCheckin('manual', true)">Force check-in (override)</button>
          </div>
        </details>
      </div>
    </div>
  </div>

  <!-- Recent check-ins -->
  <div>
    <div class="card">
      <div class="card__header"><h2>Recent check-ins</h2></div>
      <div class="card__body" style="padding:0;">
        <table class="data-table" id="recent-table">
          <thead><tr><th>Attendee</th><th>Type</th><th>Time</th></tr></thead>
          <tbody id="recent-body">
            <?php if (empty($recentCheckins)): ?>
              <tr id="no-checkins"><td colspan="3" class="empty-row">No check-ins yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($recentCheckins as $ci): ?>
            <tr>
              <td><?= e($ci['attendee_name'] ?? $ci['ticket_uid']) ?></td>
              <td><?= e($ci['ticket_type_name'] ?? '—') ?></td>
              <td><?= e(date('H:i:s', strtotime($ci['checked_in_at']))) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const CHECKIN_URL = '<?= url('/admin/events/' . $event['id'] . '/checkin') ?>';
const CSRF_TOKEN  = '<?= csrf_token() ?>';

async function doCheckin(method, override) {
  const uid    = document.getElementById('ticket-uid').value.trim();
  const reason = document.getElementById('override-reason')?.value.trim() || '';
  const result = document.getElementById('checkin-result');

  if (!uid) {
    showResult('error', 'Please enter a ticket UID.');
    return;
  }

  const body = new URLSearchParams({
    _token: CSRF_TOKEN,
    ticket_uid: uid,
    method,
    override: override ? '1' : '',
    override_reason: reason,
  });

  try {
    const resp = await fetch(CHECKIN_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: body.toString(),
    });
    const data = await resp.json();

    if (data.success) {
      showResult('success', `✓ Checked in: ${data.attendee_name || uid} (${data.ticket_type_name || ''})`);
      prependRecent(data.attendee_name || uid, data.ticket_type_name || '—');
      document.getElementById('ticket-uid').value = '';
      document.getElementById('ticket-uid').focus();
    } else {
      showResult('error', data.message || 'Check-in failed.');
    }
  } catch (e) {
    showResult('error', 'Network error. Please try again.');
  }
}

function showResult(type, msg) {
  const el = document.getElementById('checkin-result');
  el.style.display = 'block';
  el.style.background = type === 'success' ? '#d4edda' : '#f8d7da';
  el.style.color      = type === 'success' ? '#155724' : '#721c24';
  el.style.border     = '1px solid ' + (type === 'success' ? '#c3e6cb' : '#f5c6cb');
  el.textContent = msg;
}

function prependRecent(name, type) {
  const body = document.getElementById('recent-body');
  const noRow = document.getElementById('no-checkins');
  if (noRow) noRow.remove();
  const now = new Date();
  const time = now.toTimeString().slice(0,8);
  const tr = document.createElement('tr');
  tr.innerHTML = `<td>${name}</td><td>${type}</td><td>${time}</td>`;
  tr.style.background = '#d4edda';
  body.insertBefore(tr, body.firstChild);
  setTimeout(() => tr.style.background = '', 2000);
}

document.getElementById('ticket-uid').addEventListener('keydown', function(e) {
  if (e.key === 'Enter') doCheckin('qr_scan');
});
</script>
