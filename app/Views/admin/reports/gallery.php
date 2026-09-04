<div class="page-header">
  <div class="page-header__title"><h1>Gallery Report</h1></div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/reports') ?>" class="btn btn--secondary">← Reports</a>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:1.5rem;">
  <?php foreach ([
    ['Albums',  $totals['albums'],  ''],
    ['Images',  $totals['images'],  ''],
    ['Public',  $totals['public'],  'success'],
    ['Private', $totals['private'], 'neutral'],
  ] as [$label, $val, $color]): ?>
  <div class="card" style="text-align:center;padding:.75rem;">
    <div style="font-size:1.75rem;font-weight:700;<?= $color ? 'color:var(--badge-'.$color.'-bg,#333);' : '' ?>"><?= (int) $val ?></div>
    <div style="font-size:.8rem;color:#666;"><?= $label ?></div>
  </div>
  <?php endforeach; ?>
</div>

<?php if (empty($albums)): ?>
  <p class="empty-state">No gallery albums found.</p>
<?php else: ?>
<div class="card">
  <table class="data-table">
    <thead>
      <tr><th>Title</th><th>Segment</th><th>Status</th><th>Images</th><th>Active Tokens</th><th>Featured</th><th>Created</th></tr>
    </thead>
    <tbody>
      <?php foreach ($albums as $a): ?>
      <tr>
        <td>
          <a href="<?= url('/admin/gallery/' . (int) $a['id'] . '/edit') ?>"><?= e($a['title']) ?></a>
        </td>
        <td style="font-size:.8rem;"><?= e(ucfirst($a['segment'] ?? '—')) ?></td>
        <td>
          <?php $sc = match($a['status']) {
            'published' => 'success',
            'private'   => 'info',
            default     => 'neutral',
          }; ?>
          <span class="badge badge--<?= $sc ?>"><?= e(ucfirst($a['status'])) ?></span>
        </td>
        <td><?= (int) $a['image_count'] ?></td>
        <td><?= (int) $a['token_count'] ?></td>
        <td><?= $a['is_featured'] ? '<span class="badge badge--primary">Yes</span>' : '—' ?></td>
        <td style="font-size:.8rem;"><?= e(date('d M Y', strtotime($a['created_at']))) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
