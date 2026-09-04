<div class="page-header">
  <div class="page-header__title"><h1>Customer Report</h1></div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/reports') ?>" class="btn btn--secondary">← Reports</a>
  </div>
</div>

<?php include __DIR__ . '/_date_filter.php'; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;">
  <div class="card" style="text-align:center;padding:.75rem;">
    <div style="font-size:1.75rem;font-weight:700;"><?= (int) $newCustomers ?></div>
    <div style="font-size:.8rem;color:#666;">New Customers</div>
  </div>
  <div class="card" style="text-align:center;padding:.75rem;">
    <div style="font-size:1.75rem;font-weight:700;"><?= count($topCustomers) ?></div>
    <div style="font-size:.8rem;color:#666;">Active Customers</div>
  </div>
</div>

<h2>Top Customers by Spend</h2>
<?php if (empty($topCustomers)): ?>
  <p class="empty-state">No customer spend data available.</p>
<?php else: ?>
<div class="card">
  <table class="data-table">
    <thead>
      <tr><th>Customer</th><th>Email</th><th>Bookings</th><th>Ticket Orders</th><th>Lifetime Spend</th></tr>
    </thead>
    <tbody>
      <?php foreach ($topCustomers as $c): ?>
      <tr>
        <td><strong><?= e($c['name']) ?></strong></td>
        <td style="font-size:.8rem;"><?= e($c['email']) ?></td>
        <td><?= (int) $c['bookings'] ?></td>
        <td><?= (int) $c['ticket_orders'] ?></td>
        <td><strong><?= e(money((int) $c['lifetime_spend'])) ?></strong></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
