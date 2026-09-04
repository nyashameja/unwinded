<div class="page-header">
  <div class="page-header__title">
    <h1>Activity Log</h1>
    <p><?= count($logs) ?> entries showing</p>
  </div>
</div>

<form method="GET" class="filter-bar" style="margin-bottom:1.5rem;display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;">
  <div>
    <label class="form-label" style="margin-bottom:.25rem;">Entity type</label>
    <select name="entity_type" class="form-input form-input--select form-input--sm" onchange="this.form.submit()">
      <option value="">All</option>
      <?php foreach ($entityTypes as $et): ?>
      <option value="<?= e($et) ?>" <?= $entityType === $et ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_',' ',$et))) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="form-label" style="margin-bottom:.25rem;">User</label>
    <select name="user_id" class="form-input form-input--select form-input--sm" onchange="this.form.submit()">
      <option value="">All users</option>
      <?php foreach ($users as $u): ?>
      <option value="<?= (int) $u['id'] ?>" <?= $userId === (int) $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<?php if (empty($logs)): ?>
  <p class="empty-state">No activity found.</p>
<?php else: ?>
<div class="card">
  <table class="data-table">
    <thead>
      <tr><th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>IP</th></tr>
    </thead>
    <tbody>
      <?php foreach ($logs as $log): ?>
      <tr>
        <td style="font-size:.75rem;white-space:nowrap;"><?= e(date('d M Y H:i', strtotime($log['created_at']))) ?></td>
        <td><?= e($log['user_display_name'] ?? $log['user_name'] ?? '—') ?></td>
        <td><code style="font-size:.8rem;"><?= e($log['action']) ?></code></td>
        <td style="font-size:.8rem;">
          <?= e(ucfirst(str_replace('_',' ',$log['entity_type']))) ?>
          <?php if ($log['entity_id']): ?>
            <span style="color:#888;">#<?= e($log['entity_id']) ?></span>
          <?php endif; ?>
        </td>
        <td style="font-size:.75rem;color:#999;"><?= e($log['ip_address'] ?? '—') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
