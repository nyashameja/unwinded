
<div class="page-hero page-hero--sm">
  <div class="container">
    <h1>Book Tickets</h1>
    <p><?= e($event['title']) ?></p>
  </div>
</div>

<section class="section">
  <div class="container container--narrow">
    <div class="alert alert--info">
      Online ticket checkout is coming soon. Please <a href="<?= url('/contact') ?>">contact us</a> to arrange your booking.
    </div>
    <p style="margin-top:1.5rem;">
      <a href="<?= url('/events/' . $event['slug']) ?>" class="btn btn--secondary">&larr; Back to event</a>
    </p>
  </div>
</section>
