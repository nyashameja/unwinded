<?php $exp = $experience; ?>

<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Edit Experience</h1>
  <div class="page-header__actions">
    <a href="<?= url('/admin/experiences') ?>" class="btn btn--secondary">← Experiences</a>
  </div>
</div>

<form method="POST" action="<?= url('/admin/experiences/' . $exp['id']) ?>" novalidate>
  <?= csrf_field() ?>

  <div class="form-layout" style="display:grid;grid-template-columns:1fr 280px;gap:24px;align-items:start;">

    <div>
      <div class="form-group">
        <label for="title" class="form-label">Title <span class="required">*</span></label>
        <input type="text" id="title" name="title" class="form-input <?= !empty($errors['title']) ? 'form-input--error' : '' ?>"
               value="<?= attr($exp['title']) ?>" required>
        <?php if (!empty($errors['title'])): ?>
          <p class="form-error"><?= e($errors['title']) ?></p>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="short_desc" class="form-label">Short description</label>
        <textarea id="short_desc" name="short_desc" class="form-input form-input--textarea" rows="3"><?= e($exp['short_desc'] ?? '') ?></textarea>
        <p class="form-hint">Shown in listing cards. Plain text.</p>
      </div>

      <div class="form-group">
        <label for="body" class="form-label">Body</label>
        <textarea id="body" name="body" class="form-input form-input--textarea" rows="18"
                  style="font-family:monospace;font-size:.875rem;"><?= e($exp['body'] ?? '') ?></textarea>
        <p class="form-hint">HTML is supported.</p>
      </div>
    </div>

    <div>
      <div class="card" style="margin-bottom:16px;">
        <div class="card__body">

          <div class="form-group">
            <label for="type" class="form-label">Type <span class="required">*</span></label>
            <select id="type" name="type" class="form-input form-input--select <?= !empty($errors['type']) ? 'form-input--error' : '' ?>">
              <?php foreach ($types as $t): ?>
                <option value="<?= e($t) ?>" <?= $exp['type'] === $t ? 'selected' : '' ?>><?= e(ucfirst($t)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="slug" class="form-label">Slug</label>
            <input type="text" id="slug" name="slug" class="form-input" value="<?= attr($exp['slug']) ?>">
          </div>

          <div class="form-group">
            <label for="sort_order" class="form-label">Sort order</label>
            <input type="number" id="sort_order" name="sort_order" class="form-input"
                   value="<?= attr($exp['sort_order']) ?>" min="0">
          </div>

          <div class="form-group">
            <label class="toggle">
              <input type="checkbox" name="is_active" value="1" <?= $exp['is_active'] ? 'checked' : '' ?>>
              <span class="toggle__track"></span>
              &nbsp; Active (shown on site)
            </label>
          </div>

        </div>
      </div>

      <div class="card" style="margin-bottom:16px;">
        <div class="card__body">
          <div class="form-group">
            <label for="cta_text" class="form-label">CTA button text</label>
            <input type="text" id="cta_text" name="cta_text" class="form-input" value="<?= attr($exp['cta_text'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="cta_url" class="form-label">CTA URL</label>
            <input type="text" id="cta_url" name="cta_url" class="form-input" value="<?= attr($exp['cta_url'] ?? '') ?>">
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card__body">
          <div class="form-group">
            <label for="meta_title" class="form-label">Meta title</label>
            <input type="text" id="meta_title" name="meta_title" class="form-input"
                   value="<?= attr($exp['meta_title'] ?? '') ?>" maxlength="500">
          </div>
          <div class="form-group">
            <label for="meta_description" class="form-label">Meta description</label>
            <textarea id="meta_description" name="meta_description"
                      class="form-input form-input--textarea" rows="3" maxlength="500"><?= e($exp['meta_description'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn--primary">Save experience</button>
  </div>
</form>
