<div class="page-header">
  <div class="page-header__title">
    <h1>Extras — <?= e($package['name']) ?></h1>
  </div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/packages/' . (int) $package['id'] . '/edit') ?>" class="btn btn--secondary">← Package</a>
  </div>
</div>

<?php foreach (flash()->getAll() as $ftype => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($ftype) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="form-grid form-grid--2col" style="gap:2rem;">

  <!-- Linked extras -->
  <div>
    <h2>Linked to this package</h2>
    <?php if (empty($linked)): ?>
      <p class="empty-state">No extras linked yet.</p>
    <?php else: ?>
    <div class="card">
      <table class="data-table">
        <thead>
          <tr><th>Name</th><th>Price model</th><th>Price</th><th>Default</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($linked as $e): ?>
          <tr>
            <td><?= e($e['name']) ?></td>
            <td style="font-size:.8rem;"><?= e(ucfirst(str_replace('_',' ',$e['price_model']))) ?></td>
            <td><?= e(money((int)$e['price_cents'])) ?></td>
            <td>
              <form method="POST" action="<?= url('/admin/packages/' . (int) $package['id'] . '/extras/' . (int) $e['id']) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_default">
                <input type="hidden" name="is_default" value="<?= $e['is_default'] ? '0' : '1' ?>">
                <button type="submit" class="btn btn--xs <?= $e['is_default'] ? 'btn--primary' : 'btn--secondary' ?>">
                  <?= $e['is_default'] ? 'Default' : 'Set default' ?>
                </button>
              </form>
            </td>
            <td>
              <form method="POST" action="<?= url('/admin/packages/' . (int) $package['id'] . '/extras/' . (int) $e['id']) ?>"
                    onsubmit="return confirm('Unlink this extra?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="unlink">
                <button type="submit" class="btn btn--xs btn--danger">Unlink</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Add extras -->
  <div>
    <!-- Link existing -->
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2 style="margin:0;">Link existing extra</h2></div>
      <div class="card__body">
        <form method="POST" action="<?= url('/admin/packages/' . (int) $package['id'] . '/extras') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="link">
          <div class="form-group">
            <select name="extra_id" class="form-input form-input--select" required>
              <option value="">— select —</option>
              <?php foreach ($allExtras as $e): ?>
              <option value="<?= (int) $e['id'] ?>"><?= e($e['name']) ?> (<?= e(money((int)$e['price_cents'])) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn--secondary btn--sm">Link</button>
        </form>
      </div>
    </div>

    <!-- Create new -->
    <div class="card">
      <div class="card__header"><h2 style="margin:0;">Create new extra</h2></div>
      <div class="card__body">
        <form method="POST" action="<?= url('/admin/packages/' . (int) $package['id'] . '/extras') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="create">

          <div class="form-group">
            <label class="form-label">Name <span class="required">*</span></label>
            <input type="text" name="name" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label">Description</label>
            <input type="text" name="description" class="form-input">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Price model</label>
              <select name="price_model" class="form-input form-input--select">
                <?php foreach ($priceModels as $pm): ?>
                <option value="<?= e($pm) ?>"><?= e(ucfirst(str_replace('_',' ',$pm))) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Price (R)</label>
              <input type="number" name="price" class="form-input" step="0.01" min="0" value="0">
            </div>
          </div>
          <button type="submit" class="btn btn--primary btn--sm">Create &amp; link</button>
        </form>
      </div>
    </div>
  </div>

</div>
