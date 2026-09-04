<?php
$groupLabels = [
    'brand'        => 'Brand',
    'contact'      => 'Contact',
    'social'       => 'Social Media',
    'business'     => 'Business Rules',
    'cancellation' => 'Cancellation Fees',
    'seo'          => 'SEO Defaults',
    'system'       => 'System',
    'mail'         => 'Mail / SMTP',
];
?>

<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Settings</h1>
</div>

<form method="POST" action="<?= url('/admin/settings') ?>">
  <?= csrf_field() ?>

  <?php foreach ($grouped as $group => $rows): ?>
  <div class="card" id="<?= e($group) ?>" style="margin-bottom:24px;">
    <div class="card__header">
      <h2 class="card__title"><?= e($groupLabels[$group] ?? ucfirst($group)) ?></h2>
    </div>
    <div class="card__body">
      <?php foreach ($rows as $row): ?>
      <div class="form-group">
        <label for="setting-<?= e($row['key']) ?>" class="form-label"><?= e($row['label']) ?></label>
        <?php if ($row['type'] === 'boolean'): ?>
          <label class="toggle">
            <input type="checkbox" id="setting-<?= e($row['key']) ?>"
                   name="<?= e($row['key']) ?>" value="1"
                   <?= $row['value'] ? 'checked' : '' ?>>
            <span class="toggle__track"></span>
          </label>
        <?php elseif ($row['type'] === 'text'): ?>
          <textarea id="setting-<?= e($row['key']) ?>" name="<?= e($row['key']) ?>"
                    class="form-input form-input--textarea" rows="4"><?= e($row['value'] ?? '') ?></textarea>
        <?php else: ?>
          <input type="text" id="setting-<?= e($row['key']) ?>" name="<?= e($row['key']) ?>"
                 class="form-input" value="<?= attr($row['value'] ?? '') ?>">
        <?php endif; ?>
        <p class="form-hint" style="font-size:.8rem;color:#8b7355;"><?= e($row['key']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="form-actions" style="position:sticky;bottom:0;background:#f5f0eb;padding:16px 0;z-index:10;">
    <button type="submit" class="btn btn--primary">Save all settings</button>
  </div>
</form>

<div class="card" id="mail" style="margin-top:24px;">
  <div class="card__header">
    <h2 class="card__title">Test Mail Connection</h2>
  </div>
  <div class="card__body">
    <p style="margin-bottom:16px;font-family:Arial,sans-serif;font-size:.875rem;color:#4a3728;">
      Sends an SMTP handshake to verify your mail settings are correct.
      No email is sent — this only tests the connection.
    </p>
    <form method="POST" action="<?= url('/admin/settings/mail/test') ?>">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn--secondary">Test SMTP connection</button>
    </form>
  </div>
</div>
