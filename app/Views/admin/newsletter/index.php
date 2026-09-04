<div class="page-header">
  <div class="page-header__title">
    <h1>Newsletter Subscribers</h1>
  </div>
  <div class="page-header__actions">
    <form method="POST" action="<?= url('/admin/newsletter/export') ?>" style="display:inline;">
      <?= csrf_field() ?>
      <select name="export_status" class="form-input form-input--select form-input--sm" style="margin-right:.5rem;">
        <option value="confirmed">Confirmed only</option>
        <option value="">All</option>
      </select>
      <button type="submit" class="btn btn--secondary">Export CSV</button>
    </form>
  </div>
</div>

<?php foreach (flash()->getAll() as $type => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<!-- Stats -->
<div class="stat-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:1.5rem;">
  <?php
  $statItems = [
    ['Total',        $stats['total'],         ''],
    ['Confirmed',    $stats['confirmed'],      'success'],
    ['Pending',      $stats['pending'],        'warning'],
    ['Unsubscribed', $stats['unsubscribed'],   'neutral'],
  ];
  foreach ($statItems as [$label, $val, $color]):
  ?>
  <div class="card" style="text-align:center;padding:.75rem;">
    <div style="font-size:1.75rem;font-weight:700;<?= $color ? 'color:var(--badge-' . $color . '-bg,#333);' : '' ?>"><?= (int) $val ?></div>
    <div style="font-size:.8rem;color:#666;"><?= $label ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filter -->
<form method="GET" class="filter-bar" style="margin-bottom:1rem;">
  <label class="form-label" style="margin:0 .5rem 0 0;">Status:</label>
  <select name="status" class="form-input form-input--select form-input--sm" onchange="this.form.submit()">
    <option value="">All</option>
    <?php foreach ($statuses as $s): ?>
    <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<?php if (empty($subscribers)): ?>
  <p class="empty-state">No subscribers found.</p>
<?php else: ?>
<div class="card">
  <table class="data-table">
    <thead>
      <tr><th>Email</th><th>Name</th><th>Status</th><th>Confirmed</th><th>Source</th><th>Joined</th></tr>
    </thead>
    <tbody>
      <?php foreach ($subscribers as $s): ?>
      <tr>
        <td><?= e($s['email']) ?></td>
        <td><?= e($s['name'] ?? '—') ?></td>
        <td>
          <?php $sc = match($s['status']) {
            'confirmed'    => 'success',
            'pending'      => 'warning',
            'unsubscribed' => 'neutral',
            'bounced'      => 'danger',
            'complained'   => 'danger',
            default        => 'neutral',
          }; ?>
          <span class="badge badge--<?= $sc ?>"><?= e(ucfirst($s['status'])) ?></span>
        </td>
        <td style="font-size:.8rem;"><?= $s['confirmed_at'] ? e(date('d M Y', strtotime($s['confirmed_at']))) : '—' ?></td>
        <td style="font-size:.8rem;"><?= e($s['source'] ?? '—') ?></td>
        <td style="font-size:.8rem;"><?= e(date('d M Y', strtotime($s['created_at']))) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
