<div class="page-header">
  <div class="page-header__title">
    <h1>Checklists</h1>
    <p><?= count($checklists) ?> showing</p>
  </div>
</div>

<?php foreach (flash()->getAll() as $ftype => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($ftype) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<form method="GET" class="filter-bar" style="margin-bottom:1.5rem;">
  <label class="form-label" style="margin:0 .5rem 0 0;">Type:</label>
  <select name="type" class="form-input form-input--select form-input--sm" onchange="this.form.submit()">
    <option value="">All</option>
    <option value="private_booking" <?= $type==='private_booking' ? 'selected' : '' ?>>Private Bookings</option>
    <option value="public_event"   <?= $type==='public_event'    ? 'selected' : '' ?>>Public Events</option>
  </select>
</form>

<?php if (empty($checklists)): ?>
  <p class="empty-state">No checklists found.</p>
<?php else: ?>
<div class="card">
  <table class="data-table">
    <thead>
      <tr>
        <th>Name</th>
        <th>Type</th>
        <th>Template</th>
        <th>Progress</th>
        <th>Required</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($checklists as $cl): ?>
      <?php
        $total     = (int) $cl['total_items'];
        $done      = (int) $cl['completed_items'];
        $overdue   = (int) $cl['overdue_required'];
        $pct       = $total > 0 ? round(($done / $total) * 100) : 0;
      ?>
      <tr>
        <td><strong><?= e($cl['name']) ?></strong></td>
        <td><span class="badge badge--neutral"><?= e(str_replace('_',' ', ucfirst($cl['checkable_type']))) ?></span></td>
        <td style="font-size:.8rem;"><?= e($cl['template_name'] ?? '—') ?></td>
        <td>
          <div style="display:flex;align-items:center;gap:.5rem;">
            <div style="background:#e5e7eb;border-radius:4px;height:8px;width:80px;overflow:hidden;">
              <div style="background:<?= $pct===100 ? 'var(--badge-success-bg,#22c55e)' : '#3b82f6' ?>;height:100%;width:<?= $pct ?>%;"></div>
            </div>
            <span style="font-size:.8rem;"><?= $done ?>/<?= $total ?></span>
          </div>
        </td>
        <td>
          <?php if ($overdue > 0): ?>
            <span class="badge badge--danger"><?= $overdue ?> pending</span>
          <?php else: ?>
            <span class="badge badge--success">OK</span>
          <?php endif; ?>
        </td>
        <td><a href="<?= url('/admin/checklists/' . (int) $cl['id']) ?>" class="btn btn--xs btn--secondary">View</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
