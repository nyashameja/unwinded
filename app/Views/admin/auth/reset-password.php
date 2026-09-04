<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Set New Password — <?= e(config('app.name')) ?></title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" href="<?= asset('img/favicon.ico') ?>">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { min-height: 100vh; display: flex; align-items: center; justify-content: center;
           background: #f5f0eb; font-family: Georgia, serif; }
    .card { background: #fff; border-radius: 8px; padding: 48px 40px;
            width: 100%; max-width: 420px; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
    .logo { text-align: center; margin-bottom: 32px; }
    .logo span { display: block; font-size: 2rem; letter-spacing: .2em; color: #2c1810; font-style: italic; }
    .logo small { font-size: .75rem; letter-spacing: .2em; color: #8b7355;
                  text-transform: uppercase; font-style: normal; font-family: Arial, sans-serif; }
    h2 { font-size: 1.125rem; color: #2c1810; margin-bottom: 8px; font-family: Arial, sans-serif; }
    p.hint { font-size: .875rem; color: #6b5744; margin-bottom: 24px; font-family: Arial, sans-serif; }
    .form-group { margin-bottom: 20px; }
    label { display: block; font-size: .875rem; color: #4a3728; margin-bottom: 6px; font-family: Arial, sans-serif; }
    input[type=password] { width: 100%; padding: 12px 14px; border: 1px solid #d4c5b0; border-radius: 4px;
                           font-size: 1rem; font-family: Arial, sans-serif; background: #faf7f3; color: #2c1810; }
    input:focus { outline: none; border-color: #c4862b; background: #fff; }
    .field-error { color: #991b1b; font-size: .8125rem; margin-top: 4px; font-family: Arial, sans-serif; }
    .btn { width: 100%; padding: 14px; background: #2c1810; color: #d4a853;
           border: none; border-radius: 4px; font-size: 1rem; cursor: pointer;
           letter-spacing: .1em; font-family: Arial, sans-serif; }
    .btn:hover { background: #3d261a; }
    .alert { padding: 12px 16px; border-radius: 4px; margin-bottom: 20px;
             font-family: Arial, sans-serif; font-size: .875rem; }
    .alert--error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    .form-footer { text-align: center; margin-top: 20px; font-family: Arial, sans-serif;
                   font-size: .8125rem; color: #8b7355; }
  </style>
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
      <a href="<?= url('/admin/login') ?>" style="color:#c4862b;">← Back to sign in</a>
    </p>
  </div>
</body>
</html>
