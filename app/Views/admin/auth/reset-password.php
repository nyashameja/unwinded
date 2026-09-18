<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Set New Password — <?= e(config('app.name')) ?></title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" href="<?= asset('img/favicon.ico') ?>">
  <link rel="stylesheet" href="<?= asset('css/login.css') ?>">
</head>
<body>
  <div class="card">
    <div class="logo">
      <span>Unwinded</span>
      <small>CMS Admin</small>
    </div>

    <?php foreach (flash()->getAll() as $type => $messages): ?>
      <?php foreach ($messages as $message): ?>
        <div class="alert alert--<?= e($type) ?>"><?= e($message) ?></div>
      <?php endforeach; ?>
    <?php endforeach; ?>

    <h2>Set a new password</h2>
    <p class="hint">Minimum 10 characters.</p>

    <form method="POST" action="<?= url('/admin/password/reset/' . urlencode($rawToken)) ?>" novalidate>
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="password">New password</label>
        <input type="password" id="password" name="password" autocomplete="new-password" required autofocus>
        <?php if (!empty($errors['password'])): ?>
          <p class="field-error"><?= e($errors['password']) ?></p>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="password_confirmation">Confirm new password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
        <?php if (!empty($errors['password_confirmation'])): ?>
          <p class="field-error"><?= e($errors['password_confirmation']) ?></p>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn">Set new password</button>
    </form>

    <p class="form-footer">
      <a href="<?= url('/admin/login') ?>">← Back to sign in</a>
    </p>
  </div>
</body>
</html>
