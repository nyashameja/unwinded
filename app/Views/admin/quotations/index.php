<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Quotations</h1>
  <div class="page-header__actions">
    <a href="<?= url('/admin/quotations/create') ?>" class="btn btn--primary">+ New quote</a>
  </div>
</div>

<div class="toolbar" style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
  <a href="<?= url('/admin/quotations') ?>" class="btn btn--xs <?= $status === '' ? 'btn--primary' : 'btn--secondary' ?>">All</a>
  <?php foreach ($statuses as $s): ?>
    <a href="<?= url('/admin/quotations?status=' . $s) ?>"
       class="btn btn--xs <?= $status === $s ? 'btn--primary' : 'btn--secondary' ?>">
      <?= e(ucfirst($s)) ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="card">
  <table class="data-table">
    <thead>
      <tr>
        <th>Ref</th>
        <th>Customer</th>
        <th>Event date</th>
        <th>Total</th>
        <th>Deposit</th>
        <th>Valid until</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($quotes)): ?>
        <tr><td colspan="8" class="empty-row">No quotations found.</td></tr>
      <?php endif; ?>
      <?php foreach ($quotes as $q): ?>
      <tr>
        <td><a href="<?= url('/admin/quotations/' . $q['id']) ?>" class="table-link"><?= e($q['public_ref']) ?></a></td>
        <td>
          <?= e($q['customer_name']) ?><br>
          <small class="text-muted"><?= e($q['customer_email']) ?></small>
        </td>
        <td><?= $q['event_date'] ? e(date('d M Y', strtotime($q['event_date']))) : '—' ?></td>
        <td><?= e(money($q['total_cents'])) ?></td>
        <td><?= e(money($q['deposit_cents'])) ?></td>
        <td>
          <?php if ($q['valid_until']): ?>
            <?php $expired = strtotime($q['valid_until']) < strtotime('today'); ?>
            <span class="<?= $expired ? 'text-danger' : '' ?>"><?= e(date('d M Y', strtotime($q['valid_until']))) ?></span>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td>
          <?php $badge = match($q['status']) {
              'draft'     => 'neutral',
              'sent'      => 'info',
              'accepted'  => 'success',
              'declined'  => 'danger',
              'expired'   => 'warning',
              default     => 'neutral',
          }; ?>
          <span class="badge badge--<?= $badge ?>"><?= e(ucfirst($q['status'])) ?></span>
        </td>
        <td class="table-actions">
          <a href="<?= url('/admin/quotations/' . $q['id']) ?>" class="btn btn--xs btn--secondary">View</a>
          <?php if ($q['status'] === 'draft'): ?>
            <a href="<?= url('/admin/quotations/' . $q['id'] . '/edit') ?>" class="btn btn--xs btn--secondary">Edit</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
