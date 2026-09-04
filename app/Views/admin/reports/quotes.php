<div class="page-header">
  <div class="page-header__title"><h1>Quotes Report</h1></div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/reports') ?>" class="btn btn--secondary">← Reports</a>
  </div>
</div>

<?php include __DIR__ . '/_date_filter.php'; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;">
  <div class="card" style="text-align:center;padding:.75rem;">
    <div style="font-size:1.75rem;font-weight:700;"><?= e((string) $conversionRate) ?>%</div>
    <div style="font-size:.8rem;color:#666;">Conversion Rate</div>
  </div>
  <div class="card" style="text-align:center;padding:.75rem;">
    <div style="font-size:1.75rem;font-weight:700;"><?= count($recent) ?></div>
    <div style="font-size:.8rem;color:#666;">Quotes Created</div>
  </div>
</div>

<div class="form-grid form-grid--2col" style="gap:1.5rem;">

  <div>
    <h2>Status Breakdown</h2>
    <?php if (empty($byStatus)): ?>
      <p class="empty-state">No quotes in this period.</p>
    <?php else: ?>
    <div class="card">
      <table class="data-table">
        <thead>
          <tr><th>Status</th><th>Count</th><th>Total Value</th></tr>
        </thead>
        <tbody>
          <?php foreach ($byStatus as $row): ?>
          <?php $sc = match($row['status']) {
            'accepted'  => 'success',
            'sent'      => 'info',
            'declined','expired' => 'danger',
            default     => 'neutral',
          }; ?>
          <tr>
            <td><span class="badge badge--<?= $sc ?>"><?= e(ucfirst($row['status'])) ?></span></td>
            <td><?= (int) $row['cnt'] ?></td>
            <td><?= e(money((int) $row['total_cents'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <div>
    <h2>Recent Quotes</h2>
    <?php if (empty($recent)): ?>
      <p class="empty-state">No quotes in this period.</p>
    <?php else: ?>
    <div class="card">
      <table class="data-table">
        <thead>
          <tr><th>Ref</th><th>Client</th><th>Status</th><th>Value</th><th>Expires</th></tr>
        </thead>
        <tbody>
          <?php foreach ($recent as $q): ?>
          <?php $sc = match($q['status']) {
            'accepted'  => 'success',
            'sent'      => 'info',
            'declined','expired' => 'danger',
            default     => 'neutral',
          }; ?>
          <tr>
            <td style="font-size:.8rem;"><?= e($q['public_ref']) ?></td>
            <td><?= e($q['customer_name']) ?></td>
            <td><span class="badge badge--<?= $sc ?>"><?= e(ucfirst($q['status'])) ?></span></td>
            <td><?= e(money((int) $q['total_cents'])) ?></td>
            <td style="font-size:.8rem;">
              <?= $q['expires_at'] ? e(date('d M Y', strtotime($q['expires_at']))) : '—' ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>
