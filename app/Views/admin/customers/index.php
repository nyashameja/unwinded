<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Customers</h1>
</div>

<form method="GET" action="<?= url('/admin/customers') ?>" style="margin-bottom:1rem;display:flex;gap:.5rem;">
  <input type="search" name="q" class="form-input" style="max-width:320px;"
         placeholder="Search by name, email, phone…" value="<?= attr($q) ?>">
  <button type="submit" class="btn btn--secondary">Search</button>
  <?php if ($q): ?><a href="<?= url('/admin/customers') ?>" class="btn btn--secondary">Clear</a><?php endif; ?>
</form>

<div class="card">
  <table class="data-table">
    <thead>
      <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Company</th>
        <th>Bookings</th>
        <th>Quotes</th>
        <th>Since</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($customers)): ?>
        <tr><td colspan="8" class="empty-row">No customers found.</td></tr>
      <?php endif; ?>
      <?php foreach ($customers as $c): ?>
      <tr>
        <td><a href="<?= url('/admin/customers/' . $c['id']) ?>" class="table-link"><?= e($c['name']) ?></a></td>
        <td><a href="mailto:<?= attr($c['email']) ?>"><?= e($c['email']) ?></a></td>
        <td><?= e($c['phone'] ?? '—') ?></td>
        <td><?= e($c['company'] ?? '—') ?></td>
        <td><?= e($c['booking_count']) ?></td>
        <td><?= e($c['quote_count']) ?></td>
        <td><?= e(date('d M Y', strtotime($c['created_at']))) ?></td>
        <td class="table-actions">
          <a href="<?= url('/admin/customers/' . $c['id']) ?>" class="btn btn--xs btn--secondary">View</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
