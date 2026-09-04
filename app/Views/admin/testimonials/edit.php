<div class="page-header">
  <div class="page-header__title">
    <h1>Edit Testimonial</h1>
    <p><?= e($testimonial['customer_name']) ?></p>
  </div>
  <div class="page-header__actions">
    <a href="<?= url('/admin/testimonials') ?>" class="btn btn--secondary">← Back</a>
  </div>
</div>

<?php foreach (flash()->getAll() as $type => $msgs): ?>
  <?php foreach ($msgs as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<form method="POST" action="<?= url('/admin/testimonials/' . (int) $testimonial['id']) ?>">
  <?= csrf_field() ?>
  <?php include __DIR__ . '/_form.php'; ?>
</form>
