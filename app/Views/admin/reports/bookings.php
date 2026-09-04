<div class="page-header">
  <div class="page-header__title"><h1>Bookings Report</h1></div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/reports') ?>" class="btn btn--secondary">← Reports</a>
  </div>
</div>

<?php include __DIR__ . '/_date_filter.php'; ?>

<div class="form-grid form-grid--2col" style="gap:1.5rem;">

  <!-- Status breakdown -->
  <div>
    <h2>Status Breakdown</h2>
    <?php if (empty($byStatus)): ?>
      <p class="empty-state">No bookings in this period.</p>
    <?php else: ?>
    <div class="card">
      <table class="data-table">
        <thead>
          <tr><th>Status</th><th>Count</th><th>Total Value</th></tr>
        </thead>
        <tbody>
          <?php foreach ($byStatus as $row): ?>
          <?php $sc = match($row['booking_status'] ?? '') {
            'confirmed','completed','ready' => 'success',
            'planning','awaiting_deposit'   => 'warning',
            'cancelled','refunded'          => 'danger',
            default                         => 'neutral',
          }; ?>
          <tr>
            <td><span class="badge badge--<?= $sc ?>"><?= e(ucfirst(str_replace('_',' ',$row['booking_status'] ?? ''))) ?></span></td>
            <td><?= (int) $row['cnt'] ?></td>
            <td><?= e(money((int) $row['total_cents'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Recent bookings -->
  <div>
    <h2>Recent Bookings</h2>
    <?php if (empty($recent)): ?>
      <p class="empty-state">No bookings in this period.</p>
    <?php else: ?>
    <div class="card">
      <table class="data-table">
        <thead>
          <tr><th>Ref</th><th>Client</th><th>Date</th><th>Total</th><th>Outstanding</th></tr>
        </thead>
        <tbody>
          <?php foreach ($recent as $b): ?>
          <tr>
            <td style="font-size:.8rem;"><a href="<?= url('/admin/bookings/' . (int) $b['id']) ?>"><?= e($b['public_ref']) ?></a></td>
            <td><?= e($b['customer_name']) ?></td>
            <td style="font-size:.8rem;"><?= e(date('d M Y', strtotime($b['event_date']))) ?></td>
            <td><?= e(money((int) $b['total_cents'])) ?></td>
            <td>
              <?php if ($b['outstanding_cents'] > 0): ?>
                <span style="color:#ef4444;"><?= e(money((int) $b['outstanding_cents'])) ?></span>
              <?php else: ?>
                <span style="color:#22c55e;">Paid</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>
