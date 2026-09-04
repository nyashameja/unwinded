<div class="page-header">
  <div class="page-header__title">
    <h1><?= e($checklist['name']) ?></h1>
    <p style="font-size:.875rem;color:#666;"><?= e(str_replace('_',' ', ucfirst($checklist['checkable_type']))) ?>: <strong><?= e($eventLabel) ?></strong></p>
  </div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/checklists') ?>" class="btn btn--secondary">← Back</a>
  </div>
</div>

<?php foreach (flash()->getAll() as $ftype => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($ftype) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<?php
  $total = count($items);
  $done  = count(array_filter($items, fn($i) => $i['is_completed']));
  $pct   = $total > 0 ? round(($done / $total) * 100) : 0;
?>

<div style="margin-bottom:1.5rem;padding:1rem;background:#f9fafb;border-radius:8px;display:flex;gap:2rem;align-items:center;">
  <div>
    <div style="font-size:2rem;font-weight:700;"><?= $pct ?>%</div>
    <div style="font-size:.8rem;color:#666;"><?= $done ?> of <?= $total ?> complete</div>
  </div>
  <div style="flex:1;background:#e5e7eb;border-radius:6px;height:12px;overflow:hidden;">
    <div style="background:<?= $pct===100 ? '#22c55e' : '#3b82f6' ?>;height:100%;width:<?= $pct ?>%;transition:width .3s;"></div>
  </div>
</div>

<?php if (empty($items)): ?>
  <p class="empty-state">No items in this checklist.</p>
<?php else: ?>
<div class="card">
  <table class="data-table">
    <thead>
      <tr><th style="width:32px;"></th><th>Task</th><th>Required</th><th>Due</th><th>Completed</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
      <tr style="<?= $item['is_completed'] ? 'opacity:.6;' : '' ?>">
        <td>
          <?php if ($item['is_completed']): ?>
            <span style="color:#22c55e;font-size:1.1rem;">✓</span>
          <?php else: ?>
            <span style="color:#d1d5db;font-size:1.1rem;">○</span>
          <?php endif; ?>
        </td>
        <td>
          <strong style="<?= $item['is_completed'] ? 'text-decoration:line-through;' : '' ?>"><?= e($item['label']) ?></strong>
          <?php if ($item['description']): ?>
            <div style="font-size:.8rem;color:#666;"><?= e($item['description']) ?></div>
          <?php endif; ?>
          <?php if ($item['notes']): ?>
            <div style="font-size:.8rem;color:#888;font-style:italic;">Note: <?= e($item['notes']) ?></div>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($item['is_required']): ?>
            <span class="badge badge--warning">Required</span>
          <?php else: ?>
            <span style="font-size:.8rem;color:#aaa;">Optional</span>
          <?php endif; ?>
        </td>
        <td style="font-size:.8rem;">
          <?php if ($item['due_date_utc']): ?>
            <?php $isPast = strtotime($item['due_date_utc']) < time(); ?>
            <span style="<?= $isPast && !$item['is_completed'] ? 'color:#ef4444;font-weight:600;' : '' ?>">
              <?= e(date('d M Y', strtotime($item['due_date_utc']))) ?>
            </span>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
        <td style="font-size:.8rem;">
          <?php if ($item['is_completed']): ?>
            <?= e(date('d M Y H:i', strtotime($item['completed_at']))) ?>
            <?php if ($item['completed_by_name']): ?>
              <div style="color:#888;"><?= e($item['completed_by_name']) ?></div>
            <?php endif; ?>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
        <td>
          <form method="POST" action="<?= url('/admin/checklists/' . (int) $checklist['id'] . '/items/' . (int) $item['id']) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--xs <?= $item['is_completed'] ? 'btn--secondary' : 'btn--primary' ?>">
              <?= $item['is_completed'] ? 'Uncheck' : 'Complete' ?>
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
