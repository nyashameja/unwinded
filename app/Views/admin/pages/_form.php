<?php
$p = $page ?? $old ?? [];
$errors = $errors ?? [];
?>
<div class="form-layout" style="display:grid;grid-template-columns:1fr 280px;gap:24px;align-items:start;">

  <div>
    <!-- Title -->
    <div class="form-group">
      <label for="title" class="form-label">Title <span class="required">*</span></label>
      <input type="text" id="title" name="title" class="form-input <?= !empty($errors['title']) ? 'form-input--error' : '' ?>"
             value="<?= attr($p['title'] ?? '') ?>" required autofocus>
      <?php if (!empty($errors['title'])): ?>
        <p class="form-error"><?= e($errors['title']) ?></p>
      <?php endif; ?>
    </div>

    <!-- Slug -->
    <div class="form-group">
      <label for="slug" class="form-label">Slug</label>
      <div style="display:flex;gap:8px;align-items:center;">
        <span style="color:#8b7355;font-family:monospace;">/</span>
        <input type="text" id="slug" name="slug" class="form-input <?= !empty($errors['slug']) ? 'form-input--error' : '' ?>"
               value="<?= attr($p['slug'] ?? '') ?>" placeholder="auto-generated from title">
      </div>
      <?php if (!empty($errors['slug'])): ?>
        <p class="form-error"><?= e($errors['slug']) ?></p>
      <?php endif; ?>
    </div>

    <!-- Content -->
    <div class="form-group">
      <label for="content" class="form-label">Content</label>
      <textarea id="content" name="content" class="form-input form-input--textarea"
                rows="20" style="font-family:monospace;font-size:.875rem;"><?= e($p['content'] ?? '') ?></textarea>
      <p class="form-hint">HTML is supported.</p>
    </div>
  </div>

  <!-- Sidebar -->
  <div>
    <div class="card" style="margin-bottom:16px;">
      <div class="card__body">
        <div class="form-group">
          <label for="template" class="form-label">Template</label>
          <select id="template" name="template" class="form-input form-input--select">
            <?php foreach (['default', 'full-width', 'minimal'] as $t): ?>
              <option value="<?= e($t) ?>" <?= ($p['template'] ?? 'default') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card__body">
        <div class="form-group">
          <label for="meta_description" class="form-label">Meta description</label>
          <textarea id="meta_description" name="meta_description" class="form-input form-input--textarea"
                    rows="3" maxlength="500"><?= e($p['meta_description'] ?? '') ?></textarea>
          <p class="form-hint">Max 500 characters.</p>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
/* Auto-fill slug from title when slug is empty */
document.getElementById('title').addEventListener('blur', function() {
  var slug = document.getElementById('slug');
  if (slug.value === '') {
    slug.value = this.value
      .toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '')
      .trim()
      .replace(/[\s-]+/g, '-');
  }
});
</script>
