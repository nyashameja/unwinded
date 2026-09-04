<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Events</h1>
  <a href="<?= url('/admin/events/create') ?>" class="btn btn--primary">+ New Event</a>
</div>

<form method="GET" action="<?= url('/admin/events') ?>" style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap;">
  <select name="status" class="form-input form-input--select" style="width:auto;">
    <option value="">All statuses</option>
    <?php foreach ($statuses as $s): ?>
      <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_',' ',$s))) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn--secondary">Filter</button>
  <?php if ($status): ?><a href="<?= url('/admin/events') ?>" class="btn btn--secondary">Clear</a><?php endif; ?>
</form>

<div class="card">
  <table class="data-table">
    <thead>
      <tr>
        <th>Title</th>
        <th>Date</th>
        <th>Venue</th>
        <th>Status</th>
        <th>Ticket types</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($events)): ?>
        <tr><td colspan="6" class="empty-row">No events found.</td></tr>
      <?php endif; ?>
      <?php foreach ($events as $ev): ?>
      <tr>
        <td><a href="<?= url('/admin/events/' . $ev['id'] . '/edit') ?>" class="table-link"><?= e($ev['title']) ?></a></td>
        <td><?= $ev['event_date'] ? e(date('d M Y', strtotime($ev['event_date']))) : '—' ?></td>
        <td><?= e($ev['venue_name'] ?? '—') ?><?php if ($ev['venue_city']): ?>, <?= e($ev['venue_city']) ?><?php endif; ?></td>
        <td><span class="badge badge--neutral"><?= e(str_replace('_',' ',$ev['status'])) ?></span></td>
        <td><?= (int) $ev['ticket_type_count'] ?></td>
        <td class="table-actions" style="white-space:nowrap;">
          <a href="<?= url('/admin/events/' . $ev['id'] . '/edit') ?>" class="btn btn--xs btn--secondary">Edit</a>
          <a href="<?= url('/admin/events/' . $ev['id'] . '/checkin') ?>" class="btn btn--xs btn--secondary">Check-in</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
