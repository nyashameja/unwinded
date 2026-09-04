<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Media Library</h1>
  <div class="page-header__actions">
    <label class="btn btn--primary" for="file-upload" style="cursor:pointer;">
      Upload images
    </label>
    <input id="file-upload" type="file" multiple accept="image/jpeg,image/png,image/webp"
           style="display:none;" onchange="uploadFiles(this.files)">
  </div>
</div>

<!-- Upload progress area -->
<div id="upload-status" style="display:none;" class="card" style="margin-bottom:16px;">
  <div class="card__body">
    <p id="upload-message" style="font-family:Arial,sans-serif;font-size:.875rem;"></p>
  </div>
</div>

<!-- Media grid -->
<div class="media-grid" id="media-grid">
  <?php if (empty($items)): ?>
    <div class="empty-state" style="grid-column:1/-1;">
      <p>No media yet. Upload your first image above.</p>
    </div>
  <?php endif; ?>
  <?php foreach ($items as $item): ?>
  <div class="media-card" data-id="<?= e($item['id']) ?>">
    <?php if (!empty($item['urls']['thumb'])): ?>
      <img src="<?= e($item['urls']['thumb']) ?>" alt="<?= attr($item['alt_text'] ?? $item['original_name']) ?>"
           loading="lazy" class="media-card__thumb">
    <?php else: ?>
      <div class="media-card__thumb media-card__thumb--no-preview">
        <span>Private</span>
      </div>
    <?php endif; ?>
    <div class="media-card__info">
      <p class="media-card__name" title="<?= attr($item['original_name']) ?>">
        <?= e(Unwinded\Support\Str::truncate($item['original_name'], 28)) ?>
      </p>
      <p class="media-card__meta">
        <?= e(number_format($item['size_bytes'] / 1024, 0)) ?> KB
        <?php if ($item['width']): ?>
          &nbsp;·&nbsp; <?= e($item['width']) ?>×<?= e($item['height']) ?>
        <?php endif; ?>
      </p>
    </div>
    <div class="media-card__actions">
      <?php if (!empty($item['urls']['medium'])): ?>
        <a href="<?= e($item['urls']['medium']) ?>" target="_blank" class="btn btn--xs btn--secondary">View</a>
      <?php endif; ?>
      <form method="POST" action="<?= url('/admin/media/' . $item['id'] . '/delete') ?>"
            onsubmit="return confirm('Delete this file?');" style="display:inline;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--xs btn--danger">Delete</button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div class="pagination" style="margin-top:24px;">
  <?php for ($p = 1; $p <= $pages; $p++): ?>
    <a href="<?= url('/admin/media', ['page' => $p]) ?>"
       class="pagination__link <?= $p === $page ? 'pagination__link--active' : '' ?>"><?= $p ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<style>
.media-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:16px; }
.media-card { background:#fff; border:1px solid #e8e0d5; border-radius:6px; overflow:hidden; }
.media-card__thumb { width:100%; height:140px; object-fit:cover; display:block; }
.media-card__thumb--no-preview { height:140px; display:flex; align-items:center; justify-content:center;
  background:#f5f0eb; color:#8b7355; font-family:Arial,sans-serif; font-size:.875rem; }
.media-card__info { padding:8px 10px 4px; }
.media-card__name { font-family:Arial,sans-serif; font-size:.8125rem; color:#2c1810;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin:0; }
.media-card__meta { font-family:Arial,sans-serif; font-size:.75rem; color:#8b7355; margin:2px 0 0; }
.media-card__actions { padding:6px 10px 10px; display:flex; gap:6px; flex-wrap:wrap; }
</style>

<script>
function uploadFiles(files) {
  const status = document.getElementById('upload-status');
  const msg    = document.getElementById('upload-message');
  status.style.display = '';
  let done = 0;
  let errors = [];

  Array.from(files).forEach(function(file) {
    const fd = new FormData();
    fd.append('file', file);
    fd.append('_csrf_token', <?= js(app('csrf')->token()) ?>);
    msg.textContent = 'Uploading ' + (done + 1) + ' of ' + files.length + '…';

    fetch(<?= js(url('/admin/media')) ?>, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        done++;
        if (data.error) {
          errors.push(file.name + ': ' + data.error);
        }
        if (done === files.length) {
          if (errors.length) {
            msg.textContent = 'Errors: ' + errors.join('; ');
          } else {
            msg.textContent = done + ' file(s) uploaded. Refreshing…';
            setTimeout(() => location.reload(), 800);
          }
        }
      })
      .catch(() => {
        errors.push(file.name + ': upload failed');
        done++;
      });
  });
}
</script>
