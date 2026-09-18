<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — <?= e(config('app.name')) ?></title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" href="<?= asset('img/favicon.ico') ?>">
  <link rel="stylesheet" href="<?= asset('css/login.css') ?>">
</head>
<body>
  <div class="login-card">
    <div class="login-logo">
      <span>Unwinded</span>
      <small>CMS Admin</small>
    </div>

    <?php foreach (flash()->getAll() as $type => $messages): ?>
      <?php foreach ($messages as $message): ?>
        <div class="alert alert--<?= e($type) ?>"><?= e($message) ?></div>
      <?php endforeach; ?>
    <?php endforeach; ?>

    <?php if (!empty($errors['auth'])): ?>
      <div class="alert alert--error"><?= e($errors['auth']) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/admin/login') ?>" novalidate>
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email"
               value="<?= attr($old['email'] ?? '') ?>"
               autocomplete="username" required autofocus>
        <?php if (!empty($errors['email'])): ?>
          <p class="field-error"><?= e($errors['email']) ?></p>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               autocomplete="current-password" required>
        <?php if (!empty($errors['password'])): ?>
          <p class="field-error"><?= e($errors['password']) ?></p>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn-login">Sign in</button>
    </form>

    <p class="form-footer">
      Forgotten your password?
      <a href="<?= url('/admin/password/reset') ?>">Reset it</a>
    </p>
  </div>
</body>
</html>
