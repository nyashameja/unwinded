
<div class="page-hero page-hero--sm">
  <div class="container">
    <h1>Our Packages</h1>
    <p>From intimate gatherings to large corporate events, we have a package for every occasion.</p>
  </div>
</div>

<section class="section">
  <div class="container">
    <?php if (empty($packages)): ?>
      <p class="empty-state">Packages coming soon. <a href="<?= url('/contact') ?>">Contact us</a> for details.</p>
    <?php else: ?>
    <div class="packages-list">
      <?php foreach ($packages as $pkg): ?>
      <div id="<?= attr($pkg['slug']) ?>" class="package-detail <?= $pkg['is_featured'] ? 'package-detail--featured' : '' ?>">
        <div class="package-detail__header"
             <?php if (!empty($pkg['highlight_colour'])): ?>style="border-color:<?= attr($pkg['highlight_colour']) ?>"<?php endif; ?>>
          <div>
            <h2 class="package-detail__name"><?= e($pkg['name']) ?></h2>
            <?php if ($pkg['tagline']): ?>
              <p class="package-detail__tagline"><?= e($pkg['tagline']) ?></p>
            <?php endif; ?>
          </div>
          <div class="package-detail__price">
            <?php if ($pkg['pricing_model'] === 'price_on_request'): ?>
              <span>Price on request</span>
            <?php elseif ($pkg['pricing_model'] === 'per_person'): ?>
              <span>From <strong><?= e(money($pkg['base_price_cents'])) ?></strong> per person</span>
            <?php else: ?>
              <span>From <strong><?= e(money($pkg['base_price_cents'])) ?></strong></span>
            <?php endif; ?>
            <?php if ($pkg['min_guests'] || $pkg['max_guests']): ?>
            <small>
              <?php if ($pkg['min_guests'] && $pkg['max_guests']): ?>
                <?= $pkg['min_guests'] ?>–<?= $pkg['max_guests'] ?> guests
              <?php elseif ($pkg['min_guests']): ?>
                Min <?= $pkg['min_guests'] ?> guests
              <?php else: ?>
                Up to <?= $pkg['max_guests'] ?> guests
              <?php endif; ?>
            </small>
            <?php endif; ?>
          </div>
        </div>

        <div class="package-detail__body">
          <?php if ($pkg['description']): ?>
            <div class="prose"><?= $pkg['description'] ?></div>
          <?php endif; ?>

          <?php $pkgFeatures = $features[$pkg['id']] ?? []; ?>
          <?php if ($pkgFeatures): ?>
          <ul class="features-list">
            <?php foreach ($pkgFeatures as $f): ?>
              <li class="<?= $f['is_included'] ? 'features-list__item--included' : 'features-list__item--excluded' ?>">
                <?= e($f['label']) ?>
              </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>

          <?php $pkgExtras = $extras[$pkg['id']] ?? []; ?>
          <?php if ($pkgExtras): ?>
          <div class="extras">
            <h4>Optional extras</h4>
            <ul>
              <?php foreach ($pkgExtras as $ex): ?>
              <li>
                <?= e($ex['name']) ?>
                <?php if ($ex['price_cents'] > 0): ?>
                  — <?= e(money($ex['price_cents'])) ?>
                  <?php if ($ex['price_model'] === 'per_person'): ?>/person<?php endif; ?>
                <?php endif; ?>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>
        </div>

        <div class="package-detail__cta">
          <a href="<?= url('/request-a-quote?package_id=' . $pkg['id']) ?>" class="btn btn--primary">Get a quote for this package</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
