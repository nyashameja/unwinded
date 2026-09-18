<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password — <?= e(config('app.name')) ?></title>
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

    <h2>Reset your password</h2>
    <p class="hint">Enter your admin email address and we'll send you a link to reset your password.</p>

    <form method="POST" action="<?= url('/admin/password/reset') ?>" novalidate>
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" autocomplete="username" required autofocus>
        <?php if (!empty($errors['email'])): ?>
          <p class="field-error"><?= e($errors['email']) ?></p>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn">Send reset link</button>
    </form>

    <p class="form-footer">
      <a href="<?= url('/admin/login') ?>">← Back to sign in</a>
    </p>
  </div>
</body>
</html>
