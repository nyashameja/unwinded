<div class="page-header">
  <div class="page-header__title">
    <h1>Staff Assignments</h1>
  </div>
</div>

<?php foreach (flash()->getAll() as $ftype => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($ftype) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="form-grid form-grid--2col" style="gap:2rem;">

  <!-- Upcoming schedule -->
  <div>
    <h2>Upcoming Schedule</h2>

    <?php if (!empty($upcomingBookings)): ?>
    <h3 style="font-size:.875rem;color:#666;text-transform:uppercase;letter-spacing:.05em;margin:1rem 0 .5rem;">Private Bookings</h3>
    <div class="card" style="margin-bottom:1.5rem;">
      <table class="data-table">
        <thead>
          <tr><th>Date</th><th>Ref</th><th>Client</th><th>Staff</th></tr>
        </thead>
        <tbody>
          <?php foreach ($upcomingBookings as $b): ?>
          <tr>
            <td style="font-size:.8rem;"><?= e(date('d M Y', strtotime($b['event_date']))) ?></td>
            <td style="font-size:.8rem;"><?= e($b['public_ref']) ?></td>
            <td><?= e($b['customer_name']) ?></td>
            <td style="font-size:.8rem;"><?= e($b['staff_names'] ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($upcomingEvents)): ?>
    <h3 style="font-size:.875rem;color:#666;text-transform:uppercase;letter-spacing:.05em;margin:1rem 0 .5rem;">Public Events</h3>
    <div class="card" style="margin-bottom:1.5rem;">
      <table class="data-table">
        <thead>
          <tr><th>Date</th><th>Event</th><th>Staff</th></tr>
        </thead>
        <tbody>
          <?php foreach ($upcomingEvents as $ev): ?>
          <tr>
            <td style="font-size:.8rem;"><?= e(date('d M Y', strtotime($ev['event_date']))) ?></td>
            <td><?= e($ev['title']) ?></td>
            <td style="font-size:.8rem;"><?= e($ev['staff_names'] ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <?php if (empty($upcomingBookings) && empty($upcomingEvents)): ?>
      <p class="empty-state">No upcoming events.</p>
    <?php endif; ?>

    <!-- Recent assignments -->
    <h2 style="margin-top:2rem;">All Assignments</h2>
    <?php if (empty($assignments)): ?>
      <p class="empty-state">No assignments yet.</p>
    <?php else: ?>
    <div class="card">
      <table class="data-table">
        <thead>
          <tr><th>Staff</th><th>Type</th><th>ID</th><th>Role</th><th>Assigned</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($assignments as $a): ?>
          <tr>
            <td>
              <strong><?= e($a['user_name']) ?></strong>
              <div style="font-size:.75rem;color:#888;"><?= e($a['user_email']) ?></div>
            </td>
            <td><span class="badge badge--neutral"><?= e(str_replace('_',' ', ucfirst($a['assignable_type']))) ?></span></td>
            <td style="font-size:.8rem;">#<?= (int) $a['assignable_id'] ?></td>
            <td style="font-size:.8rem;"><?= e(ucfirst($a['role'])) ?></td>
            <td style="font-size:.8rem;"><?= e(date('d M Y', strtotime($a['assigned_at']))) ?></td>
            <td>
              <form method="POST" action="<?= url('/admin/staff/' . (int) $a['id'] . '/delete') ?>"
                    onsubmit="return confirm('Remove this assignment?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--xs btn--danger">Remove</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Add assignment form -->
  <div>
    <div class="card">
      <div class="card__header"><h2 style="margin:0;">Assign Staff</h2></div>
      <div class="card__body">
        <form method="POST" action="<?= url('/admin/staff') ?>">
          <?= csrf_field() ?>

          <div class="form-group">
            <label class="form-label">Staff member <span class="required">*</span></label>
            <select name="user_id" class="form-input form-input--select" required>
              <option value="">— select —</option>
              <?php foreach ($users as $u): ?>
              <option value="<?= (int) $u['id'] ?>"><?= e($u['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Type <span class="required">*</span></label>
            <select name="assignable_type" id="assignable-type" class="form-input form-input--select" required onchange="updateEventId()">
              <option value="private_booking">Private Booking</option>
              <option value="public_event">Public Event</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Event/Booking <span class="required">*</span></label>
            <select name="assignable_id" id="assignable-id" class="form-input form-input--select" required>
              <option value="">— select type first —</option>
              <?php foreach ($upcomingBookings as $b): ?>
              <option value="<?= (int) $b['id'] ?>" data-type="private_booking">
                <?= e(date('d M Y', strtotime($b['event_date']))) ?> — <?= e($b['public_ref']) ?> (<?= e($b['customer_name']) ?>)
              </option>
              <?php endforeach; ?>
              <?php foreach ($upcomingEvents as $ev): ?>
              <option value="<?= (int) $ev['id'] ?>" data-type="public_event">
                <?= e(date('d M Y', strtotime($ev['event_date']))) ?> — <?= e($ev['title']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Role</label>
            <select name="role" class="form-input form-input--select">
              <?php foreach ($roles as $r): ?>
              <option value="<?= e($r) ?>"><?= e(ucfirst($r)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-input" rows="3" placeholder="Optional notes…"></textarea>
          </div>

          <button type="submit" class="btn btn--primary btn--block">Assign</button>
        </form>
      </div>
    </div>
  </div>

</div>

<script>
function updateEventId() {
  const type = document.getElementById('assignable-type').value;
  const sel  = document.getElementById('assignable-id');
  Array.from(sel.options).forEach(opt => {
    if (opt.value === '') return;
    opt.hidden = opt.dataset.type !== type;
  });
  sel.value = '';
}
document.addEventListener('DOMContentLoaded', updateEventId);
</script>
