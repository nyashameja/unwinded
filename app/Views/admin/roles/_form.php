<div class="form-grid form-grid--2col" style="gap:1.5rem;">

  <div>
    <div class="card">
      <div class="card__header"><h2 style="margin:0;">Role Details</h2></div>
      <div class="card__body">
        <div class="form-group">
          <label class="form-label">Label <span class="required">*</span></label>
          <input type="text" name="label" class="form-input" value="<?= e($role['label'] ?? '') ?>"
                 required oninput="autoName(this)">
          <p class="form-hint">Human-readable name, e.g. "Event Manager"</p>
        </div>
        <div class="form-group">
          <label class="form-label">Name (slug) <span class="required">*</span></label>
          <input type="text" name="name" id="role-name" class="form-input" value="<?= e($role['name'] ?? '') ?>"
                 pattern="[a-z0-9_]+" required>
          <p class="form-hint">Lowercase, underscores only.</p>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-input" rows="3"><?= e($role['description'] ?? '') ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card__header"><h2 style="margin:0;">Permissions</h2></div>
      <div class="card__body">
        <?php if (empty($permissions)): ?>
          <p style="font-size:.875rem;color:#888;">No permissions seeded yet.</p>
        <?php else: ?>
        <?php
          $grouped = [];
          foreach ($permissions as $p) {
              $grouped[$p['group_name']][] = $p;
          }
          foreach ($grouped as $group => $perms):
        ?>
        <h3 style="font-size:.8rem;color:#666;text-transform:uppercase;letter-spacing:.05em;margin:1rem 0 .35rem;"><?= e(ucfirst(str_replace('_',' ',$group))) ?></h3>
        <?php foreach ($perms as $p): ?>
        <label style="display:flex;align-items:center;gap:.5rem;padding:.2rem 0;">
          <input type="checkbox" name="permission_ids[]" value="<?= (int) $p['id'] ?>"
                 <?= in_array((int) $p['id'], $rolePermIds ?? [], true) ? 'checked' : '' ?>>
          <span style="font-size:.875rem;"><?= e($p['label']) ?></span>
        </label>
        <?php endforeach; ?>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<script>
let nameLocked = <?= !empty($role['name']) ? 'true' : 'false' ?>;
function autoName(input) {
  if (nameLocked) return;
  document.getElementById('role-name').value =
    input.value.toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_|_$/g,'');
}
document.getElementById('role-name').addEventListener('input', () => { nameLocked = true; });
</script>
