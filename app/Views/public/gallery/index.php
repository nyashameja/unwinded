
<div class="page-hero page-hero--sm">
  <div class="container">
    <h1>Gallery</h1>
    <p>Memories from our sip-and-paint events.</p>
  </div>
</div>

<section class="section">
  <div class="container">
    <nav class="filter-tabs" aria-label="Gallery segments">
      <a href="<?= url('/gallery') ?>" class="filter-tab <?= empty($activeSegment) ? 'is-active' : '' ?>">All</a>
      <?php
      $segments = [
          'corporate'    => 'Corporate',
          'private'      => 'Private',
          'bridal'       => 'Bridal',
          'birthday'     => 'Birthday',
          'baby_shower'  => 'Baby Shower',
          'couples'      => 'Couples',
          'public_event' => 'Public Events',
      ];
      foreach ($segments as $key => $label):
      ?>
      <a href="<?= url('/gallery?' . http_build_query(['segment' => $key])) ?>"
         class="filter-tab <?= $activeSegment === $key ? 'is-active' : '' ?>">
        <?= e($label) ?>
      </a>
      <?php endforeach; ?>
    </nav>

    <?php if (empty($albums)): ?>
      <p class="empty-state">No albums to show yet.</p>
    <?php else: ?>
    <div class="gallery-grid">
      <?php foreach ($albums as $album): ?>
      <a href="<?= url('/gallery/' . $album['slug']) ?>" class="gallery-card">
        <?php if (!empty($album['cover_url'])): ?>
          <img src="<?= attr($album['cover_url']) ?>" alt="<?= attr($album['title']) ?>" loading="lazy">
        <?php else: ?>
          <div class="gallery-card__placeholder"></div>
        <?php endif; ?>
        <div class="gallery-card__overlay">
          <span class="gallery-card__title"><?= e($album['title']) ?></span>
          <?php if ($album['event_date']): ?>
            <span class="gallery-card__date"><?= e(date('M Y', strtotime($album['event_date']))) ?></span>
          <?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
