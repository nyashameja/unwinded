
<div class="page-hero page-hero--sm">
  <div class="container">
    <h1>Upcoming Events</h1>
    <p>Join us at an open sip-and-paint session — no group needed, just bring yourself!</p>
  </div>
</div>

<section class="section">
  <div class="container">
    <?php if (empty($upcoming)): ?>
      <p class="empty-state">No upcoming events right now. Follow us on social media so you don't miss the next one!</p>
    <?php else: ?>
    <div class="events-list">
      <?php foreach ($upcoming as $evt): ?>
      <a href="<?= url('/events/' . $evt['slug']) ?>" class="event-list-item">
        <div class="event-list-item__date">
          <span class="event-list-item__day"><?= date('d', strtotime($evt['event_date'])) ?></span>
          <span class="event-list-item__month"><?= date('M', strtotime($evt['event_date'])) ?></span>
        </div>
        <?php if ($evt['featured_image']): ?>
        <div class="event-list-item__image">
          <img src="<?= attr($evt['featured_image']) ?>" alt="<?= attr($evt['title']) ?>" loading="lazy">
        </div>
        <?php endif; ?>
        <div class="event-list-item__body">
          <h2 class="event-list-item__title"><?= e($evt['title']) ?></h2>
          <p class="event-list-item__meta">
            <?= e(date('D, d M Y', strtotime($evt['event_date']))) ?> &middot;
            <?= e(substr($evt['start_time'], 0, 5)) ?>
            <?= $evt['end_time'] ? '– ' . e(substr($evt['end_time'], 0, 5)) : '' ?>
            <?php if ($evt['venue_name']): ?>
              &middot; <?= e($evt['venue_name']) ?><?= $evt['venue_city'] ? ', ' . e($evt['venue_city']) : '' ?>
            <?php endif; ?>
          </p>
          <?php if ($evt['short_description']): ?>
            <p class="event-list-item__desc"><?= e($evt['short_description']) ?></p>
          <?php endif; ?>
          <?php
          $statusLabel = match($evt['status']) {
              'on_sale'      => ['label' => 'Tickets available', 'class' => 'badge--success'],
              'sold_out'     => ['label' => 'Sold out', 'class' => 'badge--danger'],
              'sales_closed' => ['label' => 'Sales closed', 'class' => 'badge--neutral'],
              default        => ['label' => ucfirst($evt['status']), 'class' => 'badge--neutral'],
          };
          ?>
          <span class="badge <?= $statusLabel['class'] ?>"><?= e($statusLabel['label']) ?></span>
        </div>
        <div class="event-list-item__cta">
          <span class="btn btn--primary btn--sm">View event</span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php if (!empty($past)): ?>
<section class="section section--light">
  <div class="container">
    <h2>Past events</h2>
    <div class="events-grid events-grid--past">
      <?php foreach ($past as $evt): ?>
      <div class="event-card event-card--past">
        <?php if ($evt['featured_image']): ?>
          <div class="event-card__image">
            <img src="<?= attr($evt['featured_image']) ?>" alt="<?= attr($evt['title']) ?>" loading="lazy">
          </div>
        <?php endif; ?>
        <div class="event-card__body">
          <p class="event-card__date"><?= e(date('d M Y', strtotime($evt['event_date']))) ?></p>
          <h3 class="event-card__title"><?= e($evt['title']) ?></h3>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
