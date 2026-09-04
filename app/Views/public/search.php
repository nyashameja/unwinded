
<div class="page-hero page-hero--sm">
  <div class="container">
    <h1>Search</h1>
  </div>
</div>

<section class="section">
  <div class="container container--narrow">

    <form method="GET" action="<?= url('/search') ?>" class="search-form" role="search">
      <div style="display:flex;gap:8px;">
        <input type="search" name="q" class="form-input" style="flex:1;"
               placeholder="Search experiences, events, packages…"
               value="<?= attr($query) ?>" aria-label="Search">
        <button type="submit" class="btn btn--primary">Search</button>
      </div>
    </form>

    <?php if ($query !== ''): ?>
      <p style="margin-top:1.5rem;color:#666;">
        <?php if ($totalResults > 0): ?>
          <?= $totalResults ?> result<?= $totalResults !== 1 ? 's' : '' ?> for <strong>"<?= e($query) ?>"</strong>
        <?php else: ?>
          No results found for <strong>"<?= e($query) ?>"</strong>
        <?php endif; ?>
      </p>
    <?php endif; ?>

    <?php if (!empty($results['pages'])): ?>
    <div class="search-section" style="margin-top:2rem;">
      <h2 class="search-section__heading">Pages</h2>
      <ul class="search-results">
        <?php foreach ($results['pages'] as $item): ?>
        <li class="search-result">
          <a href="<?= url('/' . ltrim($item['slug'], '/')) ?>" class="search-result__title"><?= e($item['title']) ?></a>
          <?php if ($item['meta_description']): ?>
          <p class="search-result__excerpt"><?= e($item['meta_description']) ?></p>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($results['experiences'])): ?>
    <div class="search-section" style="margin-top:2rem;">
      <h2 class="search-section__heading">Experiences</h2>
      <ul class="search-results">
        <?php foreach ($results['experiences'] as $item): ?>
        <li class="search-result">
          <a href="<?= url('/experiences/' . $item['slug']) ?>" class="search-result__title"><?= e($item['name']) ?></a>
          <?php if ($item['short_description']): ?>
          <p class="search-result__excerpt"><?= e(mb_strimwidth($item['short_description'], 0, 160, '…')) ?></p>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($results['packages'])): ?>
    <div class="search-section" style="margin-top:2rem;">
      <h2 class="search-section__heading">Packages</h2>
      <ul class="search-results">
        <?php foreach ($results['packages'] as $item): ?>
        <li class="search-result">
          <a href="<?= url('/packages#' . $item['slug']) ?>" class="search-result__title"><?= e($item['name']) ?></a>
          <?php if ($item['tagline']): ?>
          <p class="search-result__excerpt"><?= e($item['tagline']) ?></p>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($results['events'])): ?>
    <div class="search-section" style="margin-top:2rem;">
      <h2 class="search-section__heading">Events</h2>
      <ul class="search-results">
        <?php foreach ($results['events'] as $item): ?>
        <li class="search-result">
          <a href="<?= url('/events/' . $item['slug']) ?>" class="search-result__title"><?= e($item['title']) ?></a>
          <p class="search-result__excerpt"><?= e(date('d M Y', strtotime($item['event_date_utc']))) ?>
            <?php if ($item['venue_name']): ?> &middot; <?= e($item['venue_name']) ?><?php endif; ?>
          </p>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <?php if ($query !== '' && $totalResults === 0): ?>
    <div style="margin-top:3rem;text-align:center;">
      <p>Can't find what you're looking for?</p>
      <a href="<?= url('/contact') ?>" class="btn btn--secondary" style="margin-top:1rem;">Contact us</a>
    </div>
    <?php endif; ?>

  </div>
</section>
