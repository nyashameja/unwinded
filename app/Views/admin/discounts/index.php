<div class="page-header">
  <div class="page-header__title">
    <h1>Discount Codes</h1>
  </div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/discounts/create') ?>" class="btn btn--primary">+ New code</a>
  </div>
</div>

<?php foreach (flash()->getAll() as $ftype => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($ftype) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<?php if (empty($codes)): ?>
  <p class="empty-state">No discount codes yet.</p>
<?php else: ?>
<div class="card">
  <table class="data-table">
    <thead>
      <tr><th>Code</th><th>Type</th><th>Value</th><th>Applies to</th><th>Expires</th><th>Uses</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($codes as $c): ?>
      <tr>
        <td><strong><?= e($c['code']) ?></strong></td>
        <td><?= e(ucfirst($c['discount_type'])) ?></td>
        <td>
          <?php if ($c['discount_type'] === 'percentage'): ?>
            <?= e($c['discount_value']) ?>%
          <?php else: ?>
            <?= e(money((int)($c['discount_value'] * 100))) ?>
          <?php endif; ?>
        </td>
        <td style="font-size:.8rem;"><?= e(ucfirst($c['applies_to'])) ?></td>
        <td style="font-size:.8rem;">
          <?php if ($c['expires_at']): ?>
            <?php $expired = strtotime($c['expires_at']) < time(); ?>
            <span style="<?= $expired ? 'color:#ef4444;' : '' ?>"><?= e(date('d M Y', strtotime($c['expires_at']))) ?></span>
          <?php else: ?>
            Never
          <?php endif; ?>
        </td>
        <td><?= (int) $c['use_count'] ?><?= $c['max_uses'] ? ' / ' . (int) $c['max_uses'] : '' ?></td>
        <td><?= $c['is_active']
          ? '<span class="badge badge--success">Active</span>'
          : '<span class="badge badge--neutral">Inactive</span>' ?>
        </td>
        <td>
          <button type="button" class="btn btn--xs btn--secondary"
                  onclick="editCode(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)">Edit</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- Inline edit modal -->
<div id="edit-modal" hidden style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:flex;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:8px;padding:1.5rem;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;">
    <h2 style="margin:0 0 1rem;">Edit Discount Code</h2>
    <form id="edit-form" method="POST">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label">Code</label>
        <input type="text" name="code" id="edit-code" class="form-input" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Type</label>
          <select name="discount_type" id="edit-type" class="form-input form-input--select">
            <?php foreach ($types as $t): ?>
            <option value="<?= e($t) ?>"><?= e(ucfirst($t)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Value</label>
          <input type="number" name="discount_value" id="edit-value" class="form-input" step="0.01" min="0.01" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Applies to</label>
        <select name="applies_to" id="edit-applies" class="form-input form-input--select">
          <?php foreach ($appliesTo as $a): ?>
          <option value="<?= e($a) ?>"><?= e(ucfirst($a)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Starts at</label>
          <input type="datetime-local" name="starts_at" id="edit-starts" class="form-input">
        </div>
        <div class="form-group">
          <label class="form-label">Expires at</label>
          <input type="datetime-local" name="expires_at" id="edit-expires" class="form-input">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Max uses (blank = unlimited)</label>
        <input type="number" name="max_uses" id="edit-max-uses" class="form-input" min="1">
      </div>
      <div class="form-group">
        <label><input type="checkbox" name="is_active" id="edit-active" value="1"> Active</label>
      </div>
      <div style="display:flex;gap:.5rem;margin-top:1rem;">
        <button type="submit" class="btn btn--primary">Update</button>
        <button type="button" class="btn btn--secondary" onclick="closeModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
const modal = document.getElementById('edit-modal');
function editCode(c) {
  document.getElementById('edit-form').action = '/admin/discounts/' + c.id;
  document.getElementById('edit-code').value   = c.code;
  document.getElementById('edit-type').value   = c.discount_type;
  document.getElementById('edit-value').value  = c.discount_value;
  document.getElementById('edit-applies').value = c.applies_to;
  document.getElementById('edit-starts').value  = c.starts_at  ? c.starts_at.replace(' ','T').slice(0,16)  : '';
  document.getElementById('edit-expires').value = c.expires_at ? c.expires_at.replace(' ','T').slice(0,16) : '';
  document.getElementById('edit-max-uses').value = c.max_uses || '';
  document.getElementById('edit-active').checked = c.is_active == 1;
  modal.hidden = false;
}
function closeModal() { modal.hidden = true; }
modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
</script>
