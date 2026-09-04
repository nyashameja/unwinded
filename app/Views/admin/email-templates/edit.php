<div class="page-header">
  <div class="page-header__title">
    <h1><?= e($template['name']) ?></h1>
    <code style="font-size:.875rem;"><?= e($template['slug']) ?></code>
  </div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/email-templates') ?>" class="btn btn--secondary">← Back</a>
  </div>
</div>

<?php foreach (flash()->getAll() as $type => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<?php if ($template['variables']): ?>
<?php $vars = json_decode($template['variables'], true) ?? []; ?>
<?php if ($vars): ?>
<div class="card" style="margin-bottom:1.5rem;padding:.75rem 1rem;background:#f0f9ff;">
  <strong style="font-size:.875rem;">Available variables:</strong>
  <div style="margin-top:.25rem;display:flex;gap:.5rem;flex-wrap:wrap;">
    <?php foreach ($vars as $var): ?>
    <code style="background:#dbeafe;padding:.1rem .4rem;border-radius:4px;font-size:.8rem;">{{<?= e($var) ?>}}</code>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>

<div class="form-grid form-grid--2col" style="gap:1.5rem;">

  <div>
    <div class="card">
      <div class="card__header"><h2 style="margin:0;">Edit Template</h2></div>
      <div class="card__body">
        <form method="POST" action="<?= url('/admin/email-templates/' . (int) $template['id']) ?>">
          <?= csrf_field() ?>

          <div class="form-group">
            <label class="form-label">Subject <span class="required">*</span></label>
            <input type="text" name="subject" class="form-input" value="<?= e($template['subject']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">HTML Body <span class="required">*</span></label>
            <textarea name="body_html" class="form-input" rows="16" required style="font-family:monospace;font-size:.85rem;"><?= e($template['body_html']) ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Plain Text Body</label>
            <textarea name="body_plain" class="form-input" rows="6" style="font-family:monospace;font-size:.85rem;"><?= e($template['body_plain'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label><input type="checkbox" name="is_active" value="1" <?= $template['is_active'] ? 'checked' : '' ?>> Active</label>
          </div>

          <button type="submit" class="btn btn--primary">Save template</button>
        </form>
      </div>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card__header"><h2 style="margin:0;">Send test email</h2></div>
      <div class="card__body">
        <form method="POST" action="<?= url('/admin/email-templates/' . (int) $template['id'] . '/test') ?>">
          <?= csrf_field() ?>
          <div class="form-group">
            <label class="form-label">Send to</label>
            <input type="email" name="test_email" class="form-input" placeholder="you@example.com" required>
          </div>
          <p style="font-size:.8rem;color:#888;">Sends the template as-is with no variable substitution.</p>
          <button type="submit" class="btn btn--secondary">Send test</button>
        </form>
      </div>
    </div>

    <div class="card" style="margin-top:1.5rem;">
      <div class="card__header"><h2 style="margin:0;">Preview</h2></div>
      <div class="card__body" style="padding:0;">
        <iframe id="template-preview" style="width:100%;height:400px;border:0;border-radius:0 0 6px 6px;"></iframe>
      </div>
    </div>
  </div>

</div>

<script>
(function () {
  const ta = document.querySelector('textarea[name="body_html"]');
  const preview = document.getElementById('template-preview');
  function update() {
    const doc = preview.contentDocument || preview.contentWindow.document;
    doc.open(); doc.write(ta.value); doc.close();
  }
  ta.addEventListener('input', update);
  update();
})();
</script>
