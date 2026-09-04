<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title">Navigation</h1>
</div>

<?php if (empty($menus)): ?>
  <div class="card"><div class="card__body"><p>No navigation menus have been created.</p></div></div>
<?php endif; ?>

<?php foreach ($menus as $menu): ?>
<div class="card" style="margin-bottom:24px;">
  <div class="card__header">
    <h2 class="card__title"><?= e($menu['label']) ?> <small style="color:#8b7355;font-size:.875rem;">(<?= e($menu['name']) ?>)</small></h2>
  </div>
  <div class="card__body">
    <form method="POST" action="<?= url('/admin/navigation/' . $menu['id']) ?>" novalidate>
      <?= csrf_field() ?>

      <div id="menu-items-<?= e($menu['id']) ?>">
        <?php foreach ($menu['items'] as $i => $item): ?>
        <div class="nav-item-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
          <input type="text" name="items[<?= $i ?>][label]"
                 class="form-input" style="flex:1;" placeholder="Label"
                 value="<?= attr($item['label']) ?>">
          <input type="text" name="items[<?= $i ?>][url]"
                 class="form-input" style="flex:2;" placeholder="URL e.g. /about"
                 value="<?= attr($item['url']) ?>">
          <select name="items[<?= $i ?>][target]" class="form-input form-input--select" style="width:100px;">
            <option value="_self"  <?= $item['target'] === '_self'  ? 'selected' : '' ?>>Same tab</option>
            <option value="_blank" <?= $item['target'] === '_blank' ? 'selected' : '' ?>>New tab</option>
          </select>
          <label title="Active">
            <input type="checkbox" name="items[<?= $i ?>][is_active]" value="1"
                   <?= $item['is_active'] ? 'checked' : '' ?>>
            On
          </label>
          <button type="button" onclick="this.closest('.nav-item-row').remove()"
                  class="btn btn--xs btn--danger">✕</button>
        </div>
        <?php endforeach; ?>
      </div>

      <button type="button" class="btn btn--xs btn--secondary" style="margin-top:8px;"
              onclick="addNavItem(<?= e($menu['id']) ?>)">+ Add item</button>

      <div class="form-actions" style="margin-top:16px;">
        <button type="submit" class="btn btn--primary">Save navigation</button>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>

<script>
var navCounters = {};
function addNavItem(menuId) {
  navCounters[menuId] = (navCounters[menuId] || 100) + 1;
  var i = navCounters[menuId];
  var container = document.getElementById('menu-items-' + menuId);
  var row = document.createElement('div');
  row.className = 'nav-item-row';
  row.style.cssText = 'display:flex;gap:8px;align-items:center;margin-bottom:8px;';
  row.innerHTML =
    '<input type="text" name="items[' + i + '][label]" class="form-input" style="flex:1;" placeholder="Label">' +
    '<input type="text" name="items[' + i + '][url]" class="form-input" style="flex:2;" placeholder="URL e.g. /about">' +
    '<select name="items[' + i + '][target]" class="form-input form-input--select" style="width:100px;">' +
      '<option value="_self">Same tab</option><option value="_blank">New tab</option>' +
    '</select>' +
    '<label title="Active"><input type="checkbox" name="items[' + i + '][is_active]" value="1" checked> On</label>' +
    '<button type="button" onclick="this.closest(\'.nav-item-row\').remove()" class="btn btn--xs btn--danger">✕</button>';
  container.appendChild(row);
}
</script>
