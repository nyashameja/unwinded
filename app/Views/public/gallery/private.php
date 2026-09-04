<?php if (!empty($locked)): ?>

<div class="page-hero page-hero--sm">
  <div class="container">
    <h1>Private Gallery</h1>
    <p>This gallery is password protected.</p>
  </div>
</div>

<section class="section">
  <div class="container container--xs">
    <?php foreach (flash()->getAll() as $type => $msgs): ?>
      <?php foreach ($msgs as $msg): ?>
        <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
      <?php endforeach; ?>
    <?php endforeach; ?>
    <div class="card">
      <div class="card__body">
        <h2>Enter password</h2>
        <form method="POST" action="<?= url('/g/' . urlencode($token)) ?>" novalidate>
          <?= csrf_field() ?>
          <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-input" required autofocus>
          </div>
          <button type="submit" class="btn btn--primary btn--block">Unlock gallery</button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php else: ?>

<div class="page-hero page-hero--sm">
  <div class="container">
    <h1><?= e($access['album_title'] ?? 'Private Gallery') ?></h1>
  </div>
</div>

<section class="section">
  <div class="container">
    <?php if (empty($images)): ?>
      <p class="empty-state">This gallery has no images yet.</p>
    <?php else: ?>
    <div class="gallery-grid gallery-grid--album">
      <?php foreach ($images as $img): ?>
      <div class="gallery-img-item">
        <img src="<?= attr($img['public_url']) ?>"
             alt="<?= attr($img['file_name']) ?>"
             loading="lazy"
             class="gallery-img-item__img">
        <?php if (!empty($isDownloadable)): ?>
          <a href="<?= attr($img['public_url']) ?>" target="_blank" rel="noopener" class="gallery-img-item__download" aria-label="View full size">
            &#x2197;
          </a>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php endif; ?>
