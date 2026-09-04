<?php
$evt = $event;
$isSaleOpen = $evt['status'] === 'on_sale'
    && (empty($evt['sales_open_at'])  || strtotime($evt['sales_open_at'])  <= time())
    && (empty($evt['sales_close_at']) || strtotime($evt['sales_close_at']) >= time());
?>
<div class="page-hero page-hero--sm">
  <div class="container">
    <nav aria-label="Breadcrumb" class="breadcrumb">
      <a href="<?= url('/events') ?>">Events</a>
      <span aria-hidden="true">/</span>
      <span><?= e($evt['title']) ?></span>
    </nav>
    <h1><?= e($evt['title']) ?></h1>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="content-layout">

      <div class="content-layout__main">
        <?php if ($evt['featured_image']): ?>
          <img src="<?= attr($evt['featured_image']) ?>" alt="<?= attr($evt['title']) ?>" class="event-detail__hero-img" loading="eager">
        <?php endif; ?>

        <?php if ($evt['short_description']): ?>
          <p class="event-detail__intro"><?= e($evt['short_description']) ?></p>
        <?php endif; ?>

        <?php if ($evt['description']): ?>
          <div class="prose"><?= $evt['description'] ?></div>
        <?php endif; ?>

        <?php if ($evt['what_is_included']): ?>
          <h3>What's included</h3>
          <div class="prose"><?= nl2br(e($evt['what_is_included'])) ?></div>
        <?php endif; ?>

        <?php if ($evt['what_to_bring']): ?>
          <h3>What to bring</h3>
          <div class="prose"><?= nl2br(e($evt['what_to_bring'])) ?></div>
        <?php endif; ?>

        <?php if ($evt['cancellation_policy']): ?>
          <h3>Cancellation policy</h3>
          <div class="prose"><?= nl2br(e($evt['cancellation_policy'])) ?></div>
        <?php endif; ?>
      </div>

      <aside class="content-layout__sidebar">
        <div class="card card--sticky">
          <div class="card__body">
            <dl class="event-detail__meta">
              <dt>Date</dt>
              <dd><?= e(date('D, d M Y', strtotime($evt['event_date']))) ?></dd>
              <dt>Time</dt>
              <dd><?= e(substr($evt['start_time'], 0, 5)) ?><?= $evt['end_time'] ? '–' . e(substr($evt['end_time'], 0, 5)) : '' ?></dd>
              <?php if ($evt['venue_name']): ?>
              <dt>Venue</dt>
              <dd>
                <?= e($evt['venue_name']) ?>
                <?php if ($evt['venue_address']): ?>
                  <br><span class="text-muted"><?= e($evt['venue_address']) ?></span>
                <?php endif; ?>
                <?php if ($evt['map_link']): ?>
                  <br><a href="<?= attr($evt['map_link']) ?>" target="_blank" rel="noopener noreferrer">View on map</a>
                <?php endif; ?>
              </dd>
              <?php endif; ?>
              <?php if ($evt['age_restriction']): ?>
              <dt>Age</dt>
              <dd><?= e($evt['age_restriction']) ?></dd>
              <?php endif; ?>
              <?php if ($evt['dress_code']): ?>
              <dt>Dress code</dt>
              <dd><?= e($evt['dress_code']) ?></dd>
              <?php endif; ?>
            </dl>

            <?php if ($isSaleOpen && $ticketTypes): ?>
              <h3>Tickets</h3>
              <?php foreach ($ticketTypes as $tt): ?>
              <div class="ticket-type">
                <div class="ticket-type__info">
                  <strong><?= e($tt['name']) ?></strong>
                  <?php if ($tt['description']): ?><p class="ticket-type__desc"><?= e($tt['description']) ?></p><?php endif; ?>
                  <span class="ticket-type__price"><?= e(money($tt['price_cents'])) ?></span>
                  <?php
                  $remaining = $tt['qty_available'] - $tt['qty_reserved'] - $tt['qty_sold'];
                  if ($remaining <= 5 && $remaining > 0):
                  ?>
                    <span class="badge badge--warning">Only <?= $remaining ?> left!</span>
                  <?php elseif ($remaining === 0): ?>
                    <span class="badge badge--danger">Sold out</span>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
              <a href="<?= url('/events/' . $evt['slug'] . '/checkout') ?>" class="btn btn--primary btn--block" style="margin-top:1rem;">
                Get tickets
              </a>
            <?php elseif ($evt['status'] === 'sold_out'): ?>
              <p class="text-center"><strong>Sold out</strong> — follow us on social media for the next event.</p>
            <?php elseif ($evt['status'] === 'sales_closed'): ?>
              <p class="text-center">Ticket sales have closed for this event.</p>
            <?php endif; ?>
          </div>
        </div>
      </aside>
    </div>
  </div>
</section>
