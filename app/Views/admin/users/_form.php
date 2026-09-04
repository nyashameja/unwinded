<div class="form-grid form-grid--2col" style="gap:1.5rem;">
  <div>
    <div class="card">
      <div class="card__body">
        <div class="form-group">
          <label class="form-label">Full name <span class="required">*</span></label>
          <input type="text" name="name" class="form-input" value="<?= e($user['name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email <span class="required">*</span></label>
          <input type="email" name="email" class="form-input" value="<?= e($user['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Password <?= isset($user) ? '(leave blank to keep)' : '<span class="required">*</span>' ?></label>
          <input type="password" name="password" class="form-input" <?= isset($user) ? '' : 'required' ?> autocomplete="new-password">
        </div>
        <div class="form-group">
          <label class="form-label">Confirm password</label>
          <input type="password" name="password_confirm" class="form-input" autocomplete="new-password">
        </div>
        <?php if (isset($user)): ?>
        <div class="form-group">
          <label><input type="checkbox" name="is_active" value="1" <?= $user['is_active'] ? 'checked' : '' ?>> Active</label>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div>
    <div class="card">
      <div class="card__header"><h2 style="margin:0;">Roles</h2></div>
      <div class="card__body">
        <?php if (empty($roles)): ?>
          <p style="font-size:.875rem;color:#888;">No roles defined yet. <a href="<?= url('/admin/roles/create') ?>">Create one</a>.</p>
        <?php else: ?>
        <?php foreach ($roles as $r): ?>
        <label style="display:flex;align-items:center;gap:.5rem;padding:.35rem 0;">
          <input type="checkbox" name="role_ids[]" value="<?= (int) $r['id'] ?>"
                 <?= in_array((int) $r['id'], $userRoleIds ?? [], true) ? 'checked' : '' ?>>
          <span>
            <strong><?= e($r['label']) ?></strong>
            <?php if ($r['is_super']): ?>
              <span class="badge badge--danger" style="margin-left:.25rem;">Super</span>
            <?php endif; ?>
            <?php if ($r['description']): ?>
              <div style="font-size:.75rem;color:#888;"><?= e($r['description']) ?></div>
            <?php endif; ?>
          </span>
        </label>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
