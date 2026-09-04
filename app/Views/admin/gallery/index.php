<div class="page-header">
  <div class="page-header__title">
    <h1>Gallery Albums</h1>
    <p><?= count($albums) ?> album(s)</p>
  </div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/gallery/create') ?>" class="btn btn--primary">+ New Album</a>
  </div>
</div>

<?php foreach (flash()->getAll() as $type => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<!-- Status filter -->
<form method="GET" class="filter-bar" style="margin-bottom:1.5rem;">
  <label class="form-label" style="margin:0 .5rem 0 0;">Status:</label>
  <select name="status" class="form-input form-input--select form-input--sm" onchange="this.form.submit()">
    <option value="">All</option>
    <?php foreach ($statuses as $s): ?>
    <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<?php if (empty($albums)): ?>
  <p class="empty-state">No albums found.</p>
<?php else: ?>
<div class="card">
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:60px;">Cover</th>
        <th>Title</th>
        <th>Segment</th>
        <th>Date</th>
        <th>Status</th>
        <th>Images</th>
        <th>Links</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($albums as $a): ?>
      <tr>
        <td>
          <?php if ($a['cover_url']): ?>
            <img src="<?= attr($a['cover_url']) ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:4px;">
          <?php else: ?>
            <div style="width:48px;height:48px;background:#eee;border-radius:4px;"></div>
          <?php endif; ?>
        </td>
        <td>
          <a href="<?= url('/admin/gallery/' . (int) $a['id'] . '/edit') ?>" style="font-weight:600;"><?= e($a['title']) ?></a>
          <div style="font-size:.75rem;color:#888;"><?= e($a['slug']) ?></div>
        </td>
        <td><?= e(ucfirst(str_replace('_', ' ', $a['segment']))) ?></td>
        <td><?= $a['event_date'] ? e(date('d M Y', strtotime($a['event_date']))) : '—' ?></td>
        <td>
          <?php $sc = match($a['status']) {
            'published' => 'success',
            'draft'     => 'neutral',
            'private'   => 'warning',
            'archived'  => 'muted',
            default     => 'info',
          }; ?>
          <span class="badge badge--<?= $sc ?>"><?= e(ucfirst($a['status'])) ?></span>
          <?php if ($a['is_featured']): ?>
            <span class="badge badge--primary" style="margin-left:.25rem;">Featured</span>
          <?php endif; ?>
        </td>
        <td><?= (int) $a['image_count'] ?></td>
        <td><?= (int) $a['token_count'] ?> private link<?= (int) $a['token_count'] !== 1 ? 's' : '' ?></td>
        <td>
          <a href="<?= url('/admin/gallery/' . (int) $a['id'] . '/edit') ?>" class="btn btn--xs btn--secondary">Edit</a>
          <?php if ($a['status'] === 'published'): ?>
            <a href="<?= url('/gallery/' . e($a['slug'])) ?>" target="_blank" class="btn btn--xs btn--secondary">View</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
