<div class="page-header">
  <div class="page-header__title">
    <h1>Email Logs</h1>
    <p><?= count($logs) ?> entries showing</p>
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
    <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<?php if (empty($logs)): ?>
  <p class="empty-state">No email logs found.</p>
<?php else: ?>
<div class="card">
  <table class="data-table">
    <thead>
      <tr><th>Time</th><th>To</th><th>Subject</th><th>Template</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($logs as $log): ?>
      <?php $sc = match($log['status']) {
        'sent'      => 'success',
        'failed'    => 'danger',
        'bounced'   => 'warning',
        'complained'=> 'danger',
        default     => 'neutral',
      }; ?>
      <tr>
        <td style="font-size:.75rem;white-space:nowrap;"><?= e(date('d M Y H:i', strtotime($log['created_at']))) ?></td>
        <td style="font-size:.875rem;"><?= e($log['to_address']) ?></td>
        <td style="font-size:.875rem;"><?= e($log['subject']) ?></td>
        <td style="font-size:.75rem;"><code><?= e($log['template_slug'] ?? '—') ?></code></td>
        <td><span class="badge badge--<?= $sc ?>"><?= e(ucfirst($log['status'])) ?></span></td>
        <td>
          <?php if ($log['status'] === 'failed'): ?>
          <form method="POST" action="<?= url('/admin/email-logs/' . (int) $log['id'] . '/retry') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--xs btn--secondary">Retry</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php if ($log['error_message']): ?>
      <tr>
        <td colspan="6" style="padding-top:0;padding-bottom:.5rem;">
          <code style="font-size:.75rem;color:#ef4444;"><?= e($log['error_message']) ?></code>
        </td>
      </tr>
      <?php endif; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
