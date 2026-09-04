
<div class="page-hero page-hero--sm">
  <div class="container">
    <h1>Your Ticket</h1>
    <p><?= e($ticket['event_title']) ?></p>
  </div>
</div>

<section class="section">
  <div class="container container--narrow">

    <?php foreach (flash()->getAll() as $type => $msgs): ?>
      <?php foreach ($msgs as $msg): ?>
        <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
      <?php endforeach; ?>
    <?php endforeach; ?>

    <div class="card">
      <div class="card__body" style="text-align:center;">
        <div style="font-size:4rem;margin-bottom:1rem;">🎟</div>
        <h2><?= e($ticket['event_title']) ?></h2>
        <p style="color:#666;margin-bottom:1.5rem;"><?= e($ticket['ticket_type_name']) ?></p>

        <table class="data-table" style="text-align:left;margin:0 auto 1.5rem;">
          <tr><th>Ticket ref</th><td><?= e($ticket['public_ref']) ?></td></tr>
          <tr><th>Date</th><td><?= e(date('d M Y', strtotime($ticket['event_date_utc']))) ?></td></tr>
          <?php if ($ticket['event_time_local']): ?>
          <tr><th>Time</th><td><?= e($ticket['event_time_local']) ?></td></tr>
          <?php endif; ?>
          <?php if ($ticket['venue_name']): ?>
          <tr><th>Venue</th><td><?= e($ticket['venue_name']) ?></td></tr>
          <?php endif; ?>
          <tr><th>Attendee</th><td><?= e($ticket['attendee_name']) ?></td></tr>
          <tr><th>Status</th><td><?= e(ucfirst($ticket['status'])) ?></td></tr>
        </table>

        <?php if ($ticket['qr_code_url']): ?>
          <img src="<?= attr($ticket['qr_code_url']) ?>" alt="QR code" style="max-width:180px;margin:0 auto;">
        <?php endif; ?>
      </div>
    </div>

    <p style="margin-top:1.5rem;font-size:.875rem;color:#666;text-align:center;">
      Questions? <a href="<?= url('/contact') ?>">Contact us</a>
    </p>
  </div>
</section>
