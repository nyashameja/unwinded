<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">URL Redirects</h1>
</div>

<div class="card" style="margin-bottom:24px;">
  <div class="card__header"><h2 class="card__title">Add redirect</h2></div>
  <div class="card__body">
    <form method="POST" action="<?= url('/admin/redirects') ?>" novalidate>
      <?= csrf_field() ?>
      <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="flex:2;min-width:180px;">
          <label class="form-label">Source URL (old)</label>
          <input type="text" name="source_url" class="form-input" placeholder="/old-path" required>
        </div>
        <div class="form-group" style="flex:2;min-width:180px;">
          <label class="form-label">Target URL (new)</label>
          <input type="text" name="target_url" class="form-input" placeholder="/new-path or https://…" required>
        </div>
        <div class="form-group" style="flex:1;min-width:120px;">
          <label class="form-label">Status code</label>
          <select name="status_code" class="form-input form-input--select">
            <option value="301">301 Permanent</option>
            <option value="302">302 Temporary</option>
            <option value="307">307 Temporary</option>
          </select>
        </div>
        <div class="form-group">
          <button type="submit" class="btn btn--primary">Add</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <table class="data-table">
    <thead>
      <tr>
        <th>Source</th>
        <th>Target</th>
        <th>Code</th>
        <th>Hits</th>
        <th>Active</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($redirects)): ?>
        <tr><td colspan="6" class="empty-row">No redirects configured.</td></tr>
      <?php endif; ?>
      <?php foreach ($redirects as $r): ?>
      <tr>
        <td><code><?= e($r['source_url']) ?></code></td>
        <td><?= e($r['target_url']) ?></td>
        <td><?= e($r['status_code']) ?></td>
        <td><?= e(number_format($r['hit_count'])) ?></td>
        <td><?= $r['is_active'] ? '✓' : '—' ?></td>
        <td class="table-actions">
          <form method="POST" action="<?= url('/admin/redirects/' . $r['id'] . '/delete') ?>"
                onsubmit="return confirm('Remove this redirect?');" style="display:inline;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--xs btn--danger">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
