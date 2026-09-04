<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Homepage Sections</h1>
</div>

<p style="font-family:Arial,sans-serif;font-size:.875rem;color:#6b5744;margin-bottom:24px;">
  Each section below corresponds to a content block on the homepage.
  Edit the text, toggle visibility, and drag to reorder.
</p>

<div id="sections-list">
  <?php foreach ($sections as $section): ?>
  <div class="card section-card" data-id="<?= e($section['id']) ?>" style="margin-bottom:16px;">
    <div class="card__header" style="display:flex;align-items:center;gap:12px;">
      <span class="drag-handle" title="Drag to reorder" style="cursor:grab;font-size:1.2rem;color:#c4862b;">☰</span>
      <h2 class="card__title" style="flex:1;"><?= e($section['label']) ?></h2>
      <span class="badge badge--<?= $section['is_visible'] ? 'success' : 'neutral' ?>">
        <?= $section['is_visible'] ? 'Visible' : 'Hidden' ?>
      </span>
      <button type="button" class="btn btn--xs btn--secondary"
              onclick="toggleSection(this)">Edit</button>
    </div>

    <div class="section-edit" style="display:none;">
      <div class="card__body">
        <form method="POST" action="<?= url('/admin/homepage/' . $section['id']) ?>" novalidate>
          <?= csrf_field() ?>

          <div class="form-group">
            <label class="toggle">
              <input type="checkbox" name="is_visible" value="1"
                     <?= $section['is_visible'] ? 'checked' : '' ?>>
              <span class="toggle__track"></span>
              &nbsp; Show this section on the homepage
            </label>
          </div>

          <?php foreach (($section['config'] ?? []) as $key => $value): ?>
          <div class="form-group">
            <label for="cfg-<?= e($section['id']) ?>-<?= e($key) ?>" class="form-label">
              <?= e(ucwords(str_replace('_', ' ', $key))) ?>
            </label>
            <?php if (strlen((string) $value) > 100 || str_contains($key, 'body') || str_contains($key, 'text')): ?>
              <textarea id="cfg-<?= e($section['id']) ?>-<?= e($key) ?>"
                        name="<?= e($key) ?>" class="form-input form-input--textarea" rows="4"><?= e($value) ?></textarea>
            <?php else: ?>
              <input type="text" id="cfg-<?= e($section['id']) ?>-<?= e($key) ?>"
                     name="<?= e($key) ?>" class="form-input" value="<?= attr($value) ?>">
            <?php endif; ?>
          </div>
          <?php endforeach; ?>

          <div class="form-actions">
            <button type="submit" class="btn btn--primary">Save section</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<script>
function toggleSection(btn) {
  var edit = btn.closest('.section-card').querySelector('.section-edit');
  var isOpen = edit.style.display !== 'none';
  edit.style.display = isOpen ? 'none' : '';
  btn.textContent = isOpen ? 'Edit' : 'Close';
}

// Drag-to-reorder (pure vanilla JS)
(function() {
  var list = document.getElementById('sections-list');
  var dragging = null;

  list.querySelectorAll('.drag-handle').forEach(function(handle) {
    handle.addEventListener('mousedown', function(e) {
      dragging = handle.closest('.section-card');
      dragging.style.opacity = '.5';
    });
  });

  document.addEventListener('mouseup', function() {
    if (!dragging) return;
    dragging.style.opacity = '';
    dragging = null;
    saveOrder();
  });

  list.addEventListener('mouseover', function(e) {
    if (!dragging) return;
    var target = e.target.closest('.section-card');
    if (target && target !== dragging) {
      var rect = target.getBoundingClientRect();
      var mid  = rect.top + rect.height / 2;
      if (e.clientY < mid) {
        list.insertBefore(dragging, target);
      } else {
        list.insertBefore(dragging, target.nextSibling);
      }
    }
  });

  function saveOrder() {
    var ids = Array.from(list.querySelectorAll('.section-card')).map(function(c) {
      return c.dataset.id;
    });
    var fd = new FormData();
    ids.forEach(function(id, i) { fd.append('order[]', id); });
    fd.append('_csrf_token', <?= js(app('csrf')->token()) ?>);
    fetch(<?= js(url('/admin/homepage/reorder')) ?>, { method: 'POST', body: fd });
  }
})();
</script>
