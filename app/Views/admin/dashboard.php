<?php $pageTitle = 'Dashboard'; ?>

<div class="dashboard-grid">

  <div class="stat-card">
    <h3 class="stat-card__label">New Quote Requests</h3>
    <p class="stat-card__value"><?= e($stats['new_quote_requests'] ?? 0) ?></p>
    <a href="<?= url('/admin/quote-requests?status=new') ?>" class="stat-card__link">View all →</a>
  </div>

  <div class="stat-card">
    <h3 class="stat-card__label">Pending Quotes</h3>
    <p class="stat-card__value"><?= e($stats['pending_quotes'] ?? 0) ?></p>
    <a href="<?= url('/admin/quotes?status=sent') ?>" class="stat-card__link">View all →</a>
  </div>

  <div class="stat-card">
    <h3 class="stat-card__label">Upcoming Bookings</h3>
    <p class="stat-card__value"><?= e($stats['upcoming_bookings'] ?? 0) ?></p>
    <a href="<?= url('/admin/bookings') ?>" class="stat-card__link">View all →</a>
  </div>

  <div class="stat-card">
    <h3 class="stat-card__label">Deposits Overdue</h3>
    <p class="stat-card__value <?= ($stats['deposits_overdue'] ?? 0) > 0 ? 'stat-card__value--alert' : '' ?>">
      <?= e($stats['deposits_overdue'] ?? 0) ?>
    </p>
    <a href="<?= url('/admin/bookings?deposit_status=overdue') ?>" class="stat-card__link">View all →</a>
  </div>

  <div class="stat-card">
    <h3 class="stat-card__label">Upcoming Events</h3>
    <p class="stat-card__value"><?= e($stats['upcoming_events'] ?? 0) ?></p>
    <a href="<?= url('/admin/events') ?>" class="stat-card__link">View all →</a>
  </div>

  <div class="stat-card">
    <h3 class="stat-card__label">Tickets Sold (30 days)</h3>
    <p class="stat-card__value"><?= e($stats['tickets_sold_30d'] ?? 0) ?></p>
    <a href="<?= url('/admin/orders') ?>" class="stat-card__link">View orders →</a>
  </div>

  <div class="stat-card">
    <h3 class="stat-card__label">Revenue (30 days)</h3>
    <p class="stat-card__value"><?= e($stats['revenue_30d'] ?? 'R 0.00') ?></p>
    <a href="<?= url('/admin/reports') ?>" class="stat-card__link">Full report →</a>
  </div>

  <div class="stat-card">
    <h3 class="stat-card__label">New Enquiries</h3>
    <p class="stat-card__value"><?= e($stats['new_enquiries'] ?? 0) ?></p>
    <a href="<?= url('/admin/enquiries?status=new') ?>" class="stat-card__link">View all →</a>
  </div>

</div>

<?php if (!empty($recentActivity)): ?>
<section class="dashboard-section">
  <h2>Recent Activity</h2>
  <table class="data-table">
    <thead>
      <tr>
        <th>Time</th>
        <th>User</th>
        <th>Action</th>
        <th>Target</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($recentActivity as $entry): ?>
      <tr>
        <td><?= e($entry['created_at']) ?></td>
        <td><?= e($entry['user_name'] ?? '—') ?></td>
        <td><?= e($entry['action']) ?></td>
        <td><?= e($entry['subject_type'] . ' #' . $entry['subject_id']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php endif; ?>
