<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Edit: <?= e($event['title']) ?></h1>
  <div style="display:flex;gap:.5rem;">
    <a href="<?= url('/events/' . $event['slug']) ?>" target="_blank" class="btn btn--secondary">View public page &rarr;</a>
    <a href="<?= url('/admin/events/' . $event['id'] . '/checkin') ?>" class="btn btn--secondary">Check-in</a>
    <a href="<?= url('/admin/events') ?>" class="btn btn--secondary">&larr; All events</a>
  </div>
</div>

<form method="POST" action="<?= url('/admin/events/' . $event['id']) ?>">
  <?= csrf_field() ?>
  <?php include __DIR__ . '/_form.php'; ?>
</form>

<!-- Ticket types -->
<div class="card" style="margin-top:2rem;">
  <div class="card__header">
    <h2>Ticket types</h2>
  </div>
  <div class="card__body" style="padding:0;">
    <table class="data-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Price</th>
          <th>Available</th>
          <th>Reserved</th>
          <th>Sold</th>
          <th>Active</th>
          <th>Sort</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="tt-rows">
        <?php if (empty($ticketTypes)): ?>
          <tr id="tt-empty"><td colspan="8" class="empty-row">No ticket types yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($ticketTypes as $tt): ?>
        <tr>
          <td><?= e($tt['name']) ?></td>
          <td><?= e(money($tt['price_cents'])) ?></td>
          <td><?= (int) $tt['qty_available'] ?></td>
          <td><?= (int) $tt['qty_reserved'] ?></td>
          <td><?= (int) $tt['qty_sold'] ?></td>
          <td><?= $tt['is_active'] ? '✓' : '—' ?></td>
          <td><?= (int) $tt['sort_order'] ?></td>
          <td class="table-actions">
            <button type="button" class="btn btn--xs btn--secondary"
                    onclick="openTtModal(<?= htmlspecialchars(json_encode($tt), ENT_QUOTES) ?>)">Edit</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card__footer" style="padding:1rem;">
    <button type="button" class="btn btn--secondary" onclick="openTtModal(null)">+ Add ticket type</button>
  </div>
</div>

<!-- Danger zone -->
<div class="card" style="margin-top:2rem;border-color:var(--color-danger,#dc3545);">
  <div class="card__header"><h2>Danger zone</h2></div>
  <div class="card__body">
    <form method="POST" action="<?= url('/admin/events/' . $event['id'] . '/delete') ?>"
          onsubmit="return confirm('Delete this event? This cannot be undone.')">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn--danger">Delete event</button>
    </form>
  </div>
</div>

<!-- Ticket type modal -->
<div id="tt-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;overflow-y:auto;">
  <div style="background:#fff;max-width:560px;margin:2rem auto;border-radius:8px;padding:1.5rem;">
    <h2 id="tt-modal-title" style="margin:0 0 1rem;">Ticket type</h2>
    <form id="tt-form" method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="tt_id" id="tt-id">
      <div class="form-group">
        <label class="form-label">Name <span class="required">*</span></label>
        <input type="text" name="name" id="tt-name" class="form-input" required>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" id="tt-desc" class="form-input form-input--textarea" rows="2"></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Price (R)</label>
          <input type="number" name="price_rand" id="tt-price" class="form-input" step="0.01" min="0" value="0">
        </div>
        <div class="form-group">
          <label class="form-label">Admissions per ticket</label>
          <input type="number" name="admissions" id="tt-admissions" class="form-input" min="1" value="1">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Qty available</label>
          <input type="number" name="qty_available" id="tt-qty" class="form-input" min="0" value="0">
        </div>
        <div class="form-group">
          <label class="form-label">Sort order</label>
          <input type="number" name="sort_order" id="tt-sort" class="form-input" value="0">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Min per order</label>
          <input type="number" name="min_per_order" id="tt-min" class="form-input" min="1" value="1">
        </div>
        <div class="form-group">
          <label class="form-label">Max per order</label>
          <input type="number" name="max_per_order" id="tt-max" class="form-input" min="1" value="10">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Sales open</label>
          <input type="datetime-local" name="sales_open_at" id="tt-open" class="form-input">
        </div>
        <div class="form-group">
          <label class="form-label">Sales close</label>
          <input type="datetime-local" name="sales_close_at" id="tt-close" class="form-input">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label checkbox-label">
          <input type="checkbox" name="is_active" id="tt-active" value="1" checked> Active
        </label>
      </div>
      <div style="display:flex;gap:.5rem;justify-content:space-between;">
        <div style="display:flex;gap:.5rem;">
          <button type="submit" class="btn btn--primary">Save</button>
          <button type="button" class="btn btn--secondary" onclick="closeTtModal()">Cancel</button>
        </div>
        <button type="submit" name="_delete" value="1" id="tt-delete-btn" class="btn btn--danger"
                onclick="return confirm('Delete this ticket type?')" style="display:none;">Delete</button>
      </div>
    </form>
  </div>
</div>

<script>
function openTtModal(tt) {
  const modal = document.getElementById('tt-modal');
  const form  = document.getElementById('tt-form');
  const delBtn = document.getElementById('tt-delete-btn');

  if (tt) {
    document.getElementById('tt-modal-title').textContent = 'Edit ticket type';
    form.action = '<?= url('/admin/events/' . $event['id'] . '/tickets/') ?>' + tt.id;
    document.getElementById('tt-id').value    = tt.id;
    document.getElementById('tt-name').value  = tt.name;
    document.getElementById('tt-desc').value  = tt.description || '';
    document.getElementById('tt-price').value = (tt.price_cents / 100).toFixed(2);
    document.getElementById('tt-admissions').value = tt.admissions;
    document.getElementById('tt-qty').value   = tt.qty_available;
    document.getElementById('tt-sort').value  = tt.sort_order;
    document.getElementById('tt-min').value   = tt.min_per_order;
    document.getElementById('tt-max').value   = tt.max_per_order;
    document.getElementById('tt-open').value  = tt.sales_open_at  ? tt.sales_open_at.slice(0,16)  : '';
    document.getElementById('tt-close').value = tt.sales_close_at ? tt.sales_close_at.slice(0,16) : '';
    document.getElementById('tt-active').checked = tt.is_active == 1;
    delBtn.style.display = 'block';
  } else {
    document.getElementById('tt-modal-title').textContent = 'Add ticket type';
    form.action = '<?= url('/admin/events/' . $event['id'] . '/tickets') ?>';
    form.reset();
    document.getElementById('tt-id').value = '';
    document.getElementById('tt-price').value = '0.00';
    document.getElementById('tt-admissions').value = '1';
    document.getElementById('tt-min').value = '1';
    document.getElementById('tt-max').value = '10';
    document.getElementById('tt-active').checked = true;
    delBtn.style.display = 'none';
  }
  modal.style.display = 'block';
}
function closeTtModal() {
  document.getElementById('tt-modal').style.display = 'none';
}
document.getElementById('tt-modal').addEventListener('click', function(e) {
  if (e.target === this) closeTtModal();
});
</script>
