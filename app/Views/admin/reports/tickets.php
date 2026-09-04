<div class="page-header">
  <div class="page-header__title"><h1>Ticket Sales Report</h1></div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/reports') ?>" class="btn btn--secondary">← Reports</a>
  </div>
</div>

<?php include __DIR__ . '/_date_filter.php'; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;">
  <?php foreach ([
    ['Orders',      $totals['orders'],  ''],
    ['Tickets Sold',$totals['tickets'], 'success'],
    ['Revenue',     money($totals['revenue']), 'success'],
  ] as [$label, $val, $color]): ?>
  <div class="card" style="text-align:center;padding:.75rem;">
    <div style="font-size:1.5rem;font-weight:700;<?= $color ? 'color:var(--badge-'.$color.'-bg,#333);' : '' ?>"><?= e((string) $val) ?></div>
    <div style="font-size:.8rem;color:#666;"><?= $label ?></div>
  </div>
  <?php endforeach; ?>
</div>

<h2>By Event</h2>
<?php if (empty($byEvent)): ?>
  <p class="empty-state">No ticket sales in this period.</p>
<?php else: ?>
<div class="card">
  <table class="data-table">
    <thead>
      <tr><th>Event</th><th>Date</th><th>Orders</th><th>Tickets</th><th>Revenue</th></tr>
    </thead>
    <tbody>
      <?php foreach ($byEvent as $row): ?>
      <tr>
        <td>
          <strong><?= e($row['title']) ?></strong>
          <div style="font-size:.75rem;color:#888;"><?= e($row['public_ref']) ?></div>
        </td>
        <td style="font-size:.8rem;"><?= e(date('d M Y', strtotime($row['event_date']))) ?></td>
        <td><?= (int) $row['order_count'] ?></td>
        <td><strong><?= (int) $row['tickets_sold'] ?></strong></td>
        <td><?= e(money((int) $row['revenue_cents'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
