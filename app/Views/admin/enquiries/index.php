<div class="page-header">
  <div class="page-header__title">
    <h1>Enquiries</h1>
    <p><?= count($enquiries) ?> showing</p>
  </div>
</div>

<?php foreach (flash()->getAll() as $type => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<form method="GET" class="filter-bar" style="margin-bottom:1.5rem;">
  <label class="form-label" style="margin:0 .5rem 0 0;">Status:</label>
  <select name="status" class="form-input form-input--select form-input--sm" onchange="this.form.submit()">
    <option value="">All</option>
    <?php foreach ($statuses as $s): ?>
    <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_',' ',$s))) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<?php if (empty($enquiries)): ?>
  <p class="empty-state">No enquiries found.</p>
<?php else: ?>
<div class="card">
  <table class="data-table">
    <thead>
      <tr><th>Ref</th><th>Name</th><th>Subject</th><th>Status</th><th>Received</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($enquiries as $e): ?>
      <tr>
        <td style="font-size:.8rem;"><?= e($e['ref']) ?></td>
        <td>
          <strong><?= e($e['name']) ?></strong>
          <div style="font-size:.8rem;color:#666;"><?= e($e['email']) ?></div>
        </td>
        <td><?= e($e['subject']) ?></td>
        <td>
          <?php $sc = match($e['status']) {
            'new'         => 'warning',
            'in_progress' => 'info',
            'resolved'    => 'success',
            'spam'        => 'danger',
            default       => 'neutral',
          }; ?>
          <span class="badge badge--<?= $sc ?>"><?= e(ucfirst(str_replace('_',' ',$e['status']))) ?></span>
        </td>
        <td style="font-size:.8rem;"><?= e(date('d M Y H:i', strtotime($e['created_at']))) ?></td>
        <td><a href="<?= url('/admin/enquiries/' . (int) $e['id']) ?>" class="btn btn--xs btn--secondary">View</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
