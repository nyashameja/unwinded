<div class="page-header">
  <div class="page-header__title"><h1>Revenue Report</h1></div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/reports') ?>" class="btn btn--secondary">← Reports</a>
  </div>
</div>

<?php include __DIR__ . '/_date_filter.php'; ?>

<!-- Totals -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;">
  <?php foreach ([
    ['Total Revenue',  $totals['total'],   'success'],
    ['PayFast',        $totals['payfast'], ''],
    ['EFT',            $totals['eft'],     ''],
  ] as [$label, $cents, $color]): ?>
  <div class="card" style="text-align:center;padding:.75rem;">
    <div style="font-size:1.5rem;font-weight:700;<?= $color ? 'color:var(--badge-'.$color.'-bg,#333);' : '' ?>"><?= e(money($cents)) ?></div>
    <div style="font-size:.8rem;color:#666;"><?= $label ?></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="form-grid form-grid--2col" style="gap:1.5rem;">

  <!-- By month -->
  <div>
    <h2>By Month &amp; Gateway</h2>
    <?php if (empty($byMonth)): ?>
      <p class="empty-state">No payments in this period.</p>
    <?php else: ?>
    <div class="card">
      <table class="data-table">
        <thead>
          <tr><th>Month</th><th>Gateway</th><th>Transactions</th><th>Total</th></tr>
        </thead>
        <tbody>
          <?php foreach ($byMonth as $row): ?>
          <tr>
            <td><?= e($row['month']) ?></td>
            <td><?= e(ucfirst($row['gateway'])) ?></td>
            <td><?= (int) $row['txn_count'] ?></td>
            <td><strong><?= e(money((int) $row['total_cents'])) ?></strong></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- By type -->
  <div>
    <h2>By Allocation Type</h2>
    <?php if (empty($byType)): ?>
      <p class="empty-state">No allocations in this period.</p>
    <?php else: ?>
    <div class="card">
      <table class="data-table">
        <thead>
          <tr><th>Source</th><th>Type</th><th>Transactions</th><th>Total</th></tr>
        </thead>
        <tbody>
          <?php foreach ($byType as $row): ?>
          <tr>
            <td><?= e(ucfirst(str_replace('_',' ',$row['payable_type']))) ?></td>
            <td><?= e(ucfirst($row['allocation_type'])) ?></td>
            <td><?= (int) $row['txn_count'] ?></td>
            <td><strong><?= e(money((int) $row['total_cents'])) ?></strong></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>
