<div class="page-header">
  <div class="page-header__title">
    <h1>Edit Album</h1>
    <p><?= e($album['title']) ?></p>
  </div>
  <div class="page-header__actions">
    <?php if ($album['status'] === 'published'): ?>
      <a href="<?= url('/gallery/' . e($album['slug'])) ?>" target="_blank" class="btn btn--secondary">View public ↗</a>
    <?php endif; ?>
    <a href="<?= url('/admin/gallery') ?>" class="btn btn--secondary">← Back</a>
  </div>
</div>

<?php foreach (flash()->getAll() as $type => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<!-- Album details form -->
<form method="POST" action="<?= url('/admin/gallery/' . (int) $album['id']) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="cover_image_id" id="cover-image-id" value="<?= (int) ($album['cover_image_id'] ?? 0) ?: '' ?>">
  <?php include __DIR__ . '/_form.php'; ?>
</form>

<hr style="margin:2rem 0;">

<!-- ── Image manager ─────────────────────────────────────────────────────── -->
<div class="card" style="margin-bottom:2rem;">
  <div class="card__header" style="display:flex;justify-content:space-between;align-items:center;">
    <h2 style="margin:0;">Images (<?= count($images) ?>)</h2>
  </div>
  <div class="card__body">

    <!-- Upload form -->
    <form method="POST" action="<?= url('/admin/gallery/' . (int) $album['id'] . '/images') ?>"
          enctype="multipart/form-data" style="margin-bottom:1.5rem;">
      <?= csrf_field() ?>
      <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;">
        <input type="file" name="images[]" multiple accept="image/*" class="form-input" style="flex:1;min-width:220px;">
        <button type="submit" class="btn btn--primary">Upload images</button>
      </div>
    </form>

    <?php if (empty($images)): ?>
      <p class="empty-state">No images yet. Upload some above.</p>
    <?php else: ?>

    <!-- Image grid with drag-to-reorder -->
    <div id="image-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:1rem;">
      <?php foreach ($images as $img): ?>
      <div class="img-thumb" data-id="<?= (int) $img['id'] ?>"
           style="position:relative;border:2px solid transparent;border-radius:6px;overflow:hidden;cursor:grab;background:#f5f5f5;">
        <img src="<?= attr($img['public_url']) ?>" alt=""
             style="width:100%;height:120px;object-fit:cover;display:block;">
        <div style="padding:.25rem .5rem;font-size:.7rem;color:#666;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
          <?= e($img['file_name'] ?? '') ?>
        </div>
        <!-- Set as cover -->
        <button type="button" title="Set as cover"
                onclick="setCover(<?= (int) $img['id'] ?>, <?= (int) $img['media_id'] ?>)"
                style="position:absolute;top:4px;left:4px;background:rgba(0,0,0,.6);color:#fff;border:none;border-radius:3px;padding:2px 5px;font-size:.7rem;cursor:pointer;">
          <?= (int) $album['cover_image_id'] === (int) $img['media_id'] ? '★ Cover' : '☆ Cover' ?>
        </button>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Reorder save form (hidden) -->
    <form method="POST" action="<?= url('/admin/gallery/' . (int) $album['id'] . '/images/reorder') ?>"
          id="reorder-form" style="margin-top:1rem;">
      <?= csrf_field() ?>
      <div id="reorder-inputs"></div>
      <button type="submit" class="btn btn--secondary btn--sm" id="save-order-btn" style="display:none;">
        Save order
      </button>
    </form>

    <script>
    // Minimal drag-to-reorder
    const grid = document.getElementById('image-grid');
    let dragged;
    grid.querySelectorAll('.img-thumb').forEach(el => {
      el.draggable = true;
      el.addEventListener('dragstart', () => { dragged = el; el.style.opacity = '.5'; });
      el.addEventListener('dragend',   () => { el.style.opacity = '1'; rebuildOrder(); });
      el.addEventListener('dragover',  e => { e.preventDefault(); });
      el.addEventListener('drop',      e => { e.preventDefault(); if (dragged !== el) grid.insertBefore(dragged, el); });
    });
    function rebuildOrder() {
      const inp = document.getElementById('reorder-inputs');
      inp.innerHTML = '';
      grid.querySelectorAll('.img-thumb').forEach((el, i) => {
        const h = document.createElement('input');
        h.type = 'hidden'; h.name = 'order[]'; h.value = el.dataset.id;
        inp.appendChild(h);
      });
      document.getElementById('save-order-btn').style.display = '';
    }
    function setCover(imageId, mediaId) {
      document.getElementById('cover-image-id').value = mediaId;
      document.querySelector('[name=cover_image_id]') && (document.querySelector('[name=cover_image_id]').value = mediaId);
      // Visual feedback
      document.querySelectorAll('.img-thumb button').forEach(b => {
        b.textContent = b.closest('.img-thumb').dataset.id == imageId ? '★ Cover' : '☆ Cover';
      });
    }
    </script>
    <?php endif; ?>

  </div>
</div>

<!-- ── Private access tokens ────────────────────────────────────────────── -->
<div class="card" style="margin-bottom:2rem;">
  <div class="card__header">
    <h2 style="margin:0;">Private access links (<?= count($tokens) ?>)</h2>
  </div>
  <div class="card__body">

    <details style="margin-bottom:1.5rem;">
      <summary class="btn btn--sm btn--primary">+ Generate new link</summary>
      <form method="POST" action="<?= url('/admin/gallery/' . (int) $album['id'] . '/token') ?>"
            style="margin-top:1rem;background:#f9f9f9;padding:1rem;border-radius:6px;">
        <?= csrf_field() ?>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Password (optional)</label>
            <input type="text" name="password" class="form-input" placeholder="Leave blank for no password">
          </div>
          <div class="form-group">
            <label class="form-label">Expires in (days, 0 = never)</label>
            <input type="number" name="expiry_days" class="form-input" value="30" min="0">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Email to (optional)</label>
            <input type="email" name="send_email" class="form-input" placeholder="customer@example.com">
          </div>
          <div class="form-group">
            <label class="form-label">Recipient name</label>
            <input type="text" name="send_name" class="form-input" placeholder="Jane Smith">
          </div>
        </div>
        <button type="submit" class="btn btn--primary">Generate link</button>
      </form>
    </details>

    <?php if (empty($tokens)): ?>
      <p class="empty-state">No private links yet.</p>
    <?php else: ?>
    <table class="data-table">
      <thead><tr><th>Created</th><th>Expires</th><th>Password?</th><th>Active?</th><th>URL</th></tr></thead>
      <tbody>
        <?php foreach ($tokens as $t): ?>
        <tr>
          <td><?= e(date('d M Y H:i', strtotime($t['created_at']))) ?></td>
          <td><?= $t['expires_at'] ? e(date('d M Y', strtotime($t['expires_at']))) : 'Never' ?></td>
          <td><?= $t['password_hash'] ? 'Yes' : 'No' ?></td>
          <td>
            <?php if ($t['revoked_at']): ?>
              <span class="badge badge--danger">Revoked</span>
            <?php elseif (!$t['is_active']): ?>
              <span class="badge badge--neutral">Inactive</span>
            <?php else: ?>
              <span class="badge badge--success">Active</span>
            <?php endif; ?>
          </td>
          <td style="font-size:.8rem;word-break:break-all;">
            <em>Token hash: <?= substr(e($t['token_hash']), 0, 12) ?>…</em>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- ── Status change ────────────────────────────────────────────────────── -->
<div class="card">
  <div class="card__header"><h2 style="margin:0;">Quick status change</h2></div>
  <div class="card__body">
    <form method="POST" action="<?= url('/admin/gallery/' . (int) $album['id'] . '/publish') ?>"
          style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;">
      <?= csrf_field() ?>
      <select name="status" class="form-input form-input--select" style="width:auto;">
        <?php foreach (['draft','scheduled','published','private','archived'] as $s): ?>
        <option value="<?= e($s) ?>" <?= $album['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn--secondary">Update status</button>
    </form>
  </div>
</div>
