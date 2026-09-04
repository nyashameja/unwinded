<?php $activeTab = $tab ?? 'profile'; ?>

<div class="page-header">
  <h1 class="page-header__title">My Profile</h1>
</div>

<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $message): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($message) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="tabs" style="margin-bottom:24px;">
  <a href="#profile" class="tab <?= $activeTab === 'profile' ? 'tab--active' : '' ?>"
     onclick="switchTab('profile');return false;">Profile</a>
  <a href="#password" class="tab <?= $activeTab === 'password' ? 'tab--active' : '' ?>"
     onclick="switchTab('password');return false;">Change Password</a>
</div>

<!-- Profile tab -->
<div id="tab-profile" class="card" style="<?= $activeTab !== 'profile' ? 'display:none;' : '' ?>">
  <div class="card__body">
    <form method="POST" action="<?= url('/admin/profile') ?>" novalidate>
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="name" class="form-label">Name <span class="required">*</span></label>
        <input type="text" id="name" name="name" class="form-input <?= !empty($errors['name']) ? 'form-input--error' : '' ?>"
               value="<?= attr($user['name'] ?? '') ?>" required>
        <?php if (!empty($errors['name'])): ?>
          <p class="form-error"><?= e($errors['name']) ?></p>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label">Email address</label>
        <input type="email" class="form-input" value="<?= attr($user['email'] ?? '') ?>" disabled>
        <p class="form-hint">Contact a super-admin to change your email address.</p>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Password tab -->
<div id="tab-password" class="card" style="<?= $activeTab !== 'password' ? 'display:none;' : '' ?>">
  <div class="card__body">
    <form method="POST" action="<?= url('/admin/profile/password') ?>" novalidate>
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="current_password" class="form-label">Current password <span class="required">*</span></label>
        <input type="password" id="current_password" name="current_password"
               class="form-input <?= !empty($errors['current_password']) ? 'form-input--error' : '' ?>"
               autocomplete="current-password" required>
        <?php if (!empty($errors['current_password'])): ?>
          <p class="form-error"><?= e($errors['current_password']) ?></p>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="password" class="form-label">New password <span class="required">*</span></label>
        <input type="password" id="password" name="password"
               class="form-input <?= !empty($errors['password']) ? 'form-input--error' : '' ?>"
               autocomplete="new-password" required>
        <p class="form-hint">Minimum 10 characters.</p>
        <?php if (!empty($errors['password'])): ?>
          <p class="form-error"><?= e($errors['password']) ?></p>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="password_confirmation" class="form-label">Confirm new password <span class="required">*</span></label>
        <input type="password" id="password_confirmation" name="password_confirmation"
               class="form-input <?= !empty($errors['password_confirmation']) ? 'form-input--error' : '' ?>"
               autocomplete="new-password" required>
        <?php if (!empty($errors['password_confirmation'])): ?>
          <p class="form-error"><?= e($errors['password_confirmation']) ?></p>
        <?php endif; ?>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Update password</button>
        <p class="form-hint" style="margin-top:8px;">You will be signed out and asked to log in with your new password.</p>
      </div>
    </form>
  </div>
</div>

<script>
function switchTab(name) {
  document.getElementById('tab-profile').style.display  = name === 'profile'  ? '' : 'none';
  document.getElementById('tab-password').style.display = name === 'password' ? '' : 'none';
  document.querySelectorAll('.tab').forEach(function(t) {
    t.classList.toggle('tab--active', t.getAttribute('href') === '#' + name);
  });
}
</script>
