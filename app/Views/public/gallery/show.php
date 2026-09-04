
<div class="page-hero page-hero--sm">
  <div class="container">
    <nav aria-label="Breadcrumb" class="breadcrumb">
      <a href="<?= url('/gallery') ?>">Gallery</a>
      <span aria-hidden="true">/</span>
      <span><?= e($album['title']) ?></span>
    </nav>
    <h1><?= e($album['title']) ?></h1>
    <?php if (!empty($album['description'])): ?>
      <p><?= e($album['description']) ?></p>
    <?php endif; ?>
    <?php if ($album['event_date']): ?>
      <p class="page-hero__meta">
        <?= e(date('d M Y', strtotime($album['event_date']))) ?>
        <?php if ($album['venue']): ?>&middot; <?= e($album['venue']) ?><?php endif; ?>
        <?php if ($album['location']): ?>&middot; <?= e($album['location']) ?><?php endif; ?>
      </p>
    <?php endif; ?>
  </div>
</div>

<section class="section">
  <div class="container">
    <?php if (empty($images)): ?>
      <p class="empty-state">No images in this album yet.</p>
    <?php else: ?>
    <div class="gallery-grid gallery-grid--album">
      <?php foreach ($images as $img): ?>
      <div class="gallery-img-item">
        <img src="<?= attr($img['public_url']) ?>"
             alt="<?= attr($img['title'] ?: $album['title']) ?>"
             loading="lazy"
             class="gallery-img-item__img">
        <?php if ($img['caption']): ?>
          <p class="gallery-img-item__caption"><?= e($img['caption']) ?></p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
