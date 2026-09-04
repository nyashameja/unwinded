
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Install — Unwinded</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:system-ui,sans-serif;background:#f5f5f5;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:2rem}
    .card{background:#fff;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,.1);max-width:520px;width:100%;padding:2.5rem}
    h1{font-size:1.5rem;margin-bottom:.5rem}
    p{color:#555;margin-bottom:1.5rem;line-height:1.6}
    .alert{padding:.875rem 1rem;border-radius:6px;margin-bottom:1.5rem;font-size:.9375rem}
    .alert--success{background:#d1fae5;color:#065f46}
    .alert--error{background:#fee2e2;color:#991b1b}
    .alert--warning{background:#fef3c7;color:#92400e}
    .btn{display:inline-block;padding:.75rem 1.5rem;border-radius:6px;font-size:1rem;font-weight:600;cursor:pointer;border:none;text-decoration:none}
    .btn--primary{background:#7c3aed;color:#fff}
    .btn--primary:hover{background:#6d28d9}
    pre{background:#1e1e1e;color:#d4d4d4;padding:1rem;border-radius:6px;font-size:.8125rem;overflow-x:auto;margin-bottom:1.5rem}
  </style>
</head>
<body>
  <div class="card">
    <h1>&#128640; Unwinded — Install</h1>
    <p>This wizard runs database migrations and seeds initial data. Run it once after first deployment.</p>

    <?php if (!empty($message)): ?>
      <div class="alert alert--<?= e($messageType ?? 'success') ?>"><?= nl2br(e($message)) ?></div>
    <?php endif; ?>

    <?php if (!empty($output)): ?>
      <pre><?= e($output) ?></pre>
    <?php endif; ?>

    <?php if (empty($alreadyInstalled) && empty($done)): ?>
      <div class="alert alert--warning">
        &#9888; This will modify the database. Only run this on a fresh installation.
      </div>
      <form method="POST" action="<?= url('/install') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--primary">Run migrations &amp; seed</button>
      </form>
    <?php elseif (!empty($done)): ?>
      <p><a href="<?= url('/admin') ?>" class="btn btn--primary">Go to admin &rarr;</a></p>
    <?php else: ?>
      <div class="alert alert--success">Already installed. <a href="<?= url('/admin') ?>">Go to admin &rarr;</a></div>
    <?php endif; ?>
  </div>
</body>
</html>
