<div class="page-header">
  <div class="page-header__title">
    <h1>FAQs</h1>
    <p><?= count($faqs) ?> questions, <?= count($groups) ?> groups</p>
  </div>
</div>

<?php foreach (flash()->getAll() as $type => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="form-grid form-grid--2col" style="gap:2rem;">

  <!-- FAQ list -->
  <div>
    <h2>All FAQs</h2>
    <?php if (empty($faqs)): ?>
      <p class="empty-state">No FAQs yet. Add one using the form →</p>
    <?php else: ?>
    <?php $currentGroup = null; ?>
    <?php foreach ($faqs as $faq): ?>
      <?php if ($faq['group_name'] !== $currentGroup): ?>
        <?php $currentGroup = $faq['group_name']; ?>
        <h3 style="margin-top:1.5rem;color:#666;font-size:.875rem;text-transform:uppercase;letter-spacing:.05em;">
          <?= e($faq['group_name'] ?? 'Ungrouped') ?>
        </h3>
      <?php endif; ?>

      <div class="card" style="margin-bottom:.75rem;">
        <div class="card__body" style="padding:.75rem 1rem;">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;">
            <div style="flex:1;">
              <strong><?= e($faq['question']) ?></strong>
              <p style="font-size:.875rem;color:#555;margin:.25rem 0 0;"><?= e(substr($faq['answer'], 0, 100)) ?>…</p>
              <div style="margin-top:.5rem;display:flex;gap:.5rem;">
                <?= $faq['is_published'] ? '<span class="badge badge--success">Published</span>' : '<span class="badge badge--neutral">Draft</span>' ?>
                <?= $faq['is_featured']  ? '<span class="badge badge--primary">Featured</span>' : '' ?>
                <span style="font-size:.75rem;color:#999;">Order: <?= (int) $faq['sort_order'] ?></span>
              </div>
            </div>
            <div style="display:flex;gap:.5rem;flex-shrink:0;">
              <button type="button" class="btn btn--xs btn--secondary"
                      onclick="editFaq(<?= htmlspecialchars(json_encode($faq), ENT_QUOTES) ?>)">Edit</button>
              <form method="POST" action="<?= url('/admin/faqs/' . (int) $faq['id'] . '/delete') ?>" onsubmit="return confirm('Delete this FAQ?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--xs btn--danger">Del</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Add / edit form -->
  <div>
    <div class="card">
      <div class="card__header"><h2 id="faq-form-title" style="margin:0;">Add FAQ</h2></div>
      <div class="card__body">
        <form method="POST" id="faq-form" action="<?= url('/admin/faqs') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="_faq_id" id="faq-id-field" value="">

          <div class="form-group">
            <label class="form-label">Group</label>
            <select name="group_id" id="faq-group" class="form-input form-input--select">
              <option value="">— Ungrouped —</option>
              <?php foreach ($groups as $g): ?>
              <option value="<?= (int) $g['id'] ?>"><?= e($g['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Question <span class="required">*</span></label>
            <input type="text" name="question" id="faq-question" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label">Answer <span class="required">*</span></label>
            <textarea name="answer" id="faq-answer" class="form-input" rows="6" required></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Sort order</label>
              <input type="number" name="sort_order" id="faq-sort" class="form-input" value="0" min="0">
            </div>
            <div class="form-group" style="padding-top:1.75rem;">
              <label><input type="checkbox" name="is_published" id="faq-published" value="1" checked> Published</label><br>
              <label><input type="checkbox" name="is_featured"  id="faq-featured"  value="1"> Featured</label>
            </div>
          </div>
          <button type="submit" class="btn btn--primary" id="faq-submit-btn">Add FAQ</button>
          <button type="button" class="btn btn--secondary" id="faq-cancel-btn" style="display:none;" onclick="resetFaqForm()">Cancel</button>
        </form>

        <?php if (!empty($groups)): ?>
        <hr style="margin:1.5rem 0;">
        <details>
          <summary style="cursor:pointer;font-size:.875rem;">Manage groups</summary>
          <ul style="margin-top:.5rem;">
            <?php foreach ($groups as $g): ?>
            <li style="font-size:.875rem;padding:.25rem 0;"><?= e($g['name']) ?> (<?= $g['is_active'] ? 'active' : 'inactive' ?>)</li>
            <?php endforeach; ?>
          </ul>
        </details>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<script>
function editFaq(faq) {
  document.getElementById('faq-form-title').textContent = 'Edit FAQ';
  document.getElementById('faq-form').action = '/admin/faqs/' + faq.id;
  document.getElementById('faq-id-field').value = faq.id;
  document.getElementById('faq-question').value = faq.question;
  document.getElementById('faq-answer').value   = faq.answer;
  document.getElementById('faq-sort').value     = faq.sort_order || 0;
  document.getElementById('faq-group').value    = faq.group_id || '';
  document.getElementById('faq-published').checked = faq.is_published == 1;
  document.getElementById('faq-featured').checked  = faq.is_featured  == 1;
  document.getElementById('faq-submit-btn').textContent = 'Update FAQ';
  document.getElementById('faq-cancel-btn').style.display = '';
  document.getElementById('faq-form').scrollIntoView({behavior:'smooth'});
}
function resetFaqForm() {
  document.getElementById('faq-form-title').textContent = 'Add FAQ';
  document.getElementById('faq-form').action = '/admin/faqs';
  document.getElementById('faq-form').reset();
  document.getElementById('faq-id-field').value = '';
  document.getElementById('faq-submit-btn').textContent = 'Add FAQ';
  document.getElementById('faq-cancel-btn').style.display = 'none';
}
</script>
