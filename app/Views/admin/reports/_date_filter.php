<form method="GET" style="display:flex;gap:.75rem;align-items:flex-end;margin-bottom:1.5rem;flex-wrap:wrap;">
  <div>
    <label class="form-label" style="margin-bottom:.25rem;">From</label>
    <input type="date" name="from" value="<?= e(substr($from, 0, 10)) ?>" class="form-input form-input--sm">
  </div>
  <div>
    <label class="form-label" style="margin-bottom:.25rem;">To</label>
    <input type="date" name="to" value="<?= e(substr($to, 0, 10)) ?>" class="form-input form-input--sm">
  </div>
  <button type="submit" class="btn btn--secondary btn--sm">Apply</button>
  <span style="font-size:.8rem;color:#888;padding-bottom:.25rem;">
    Showing <?= e(date('d M Y', strtotime($from))) ?> – <?= e(date('d M Y', strtotime($to))) ?>
  </span>
</form>
