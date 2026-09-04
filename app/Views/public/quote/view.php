
<div class="page-hero page-hero--sm">
  <div class="container">
    <h1>Your Quote</h1>
    <p>Ref: <?= e($quote['public_ref']) ?></p>
  </div>
</div>

<section class="section">
  <div class="container container--narrow">

    <?php foreach (flash()->getAll() as $type => $msgs): ?>
      <?php foreach ($msgs as $msg): ?>
        <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
      <?php endforeach; ?>
    <?php endforeach; ?>

    <?php
    $canAct = $quote['status'] === 'sent'
        && (!$quote['valid_until'] || strtotime($quote['valid_until']) >= strtotime('today'));
    ?>

    <div class="card">
      <div class="card__body">
        <table class="data-table">
          <tr><th>Status</th><td><?= e(ucfirst($quote['status'])) ?></td></tr>
          <?php if ($quote['valid_until']): ?>
          <tr><th>Valid until</th><td><?= e(date('d M Y', strtotime($quote['valid_until']))) ?></td></tr>
          <?php endif; ?>
          <?php if ($quote['event_type']): ?><tr><th>Event type</th><td><?= e($quote['event_type']) ?></td></tr><?php endif; ?>
          <?php if ($quote['event_date']): ?><tr><th>Event date</th><td><?= e(date('d M Y', strtotime($quote['event_date']))) ?></td></tr><?php endif; ?>
          <?php if ($quote['guest_count']): ?><tr><th>Guests</th><td><?= e($quote['guest_count']) ?></td></tr><?php endif; ?>
          <?php if ($quote['venue_name']): ?><tr><th>Venue</th><td><?= e($quote['venue_name']) ?></td></tr><?php endif; ?>
          <tr><th>Total</th><td><strong><?= e(money($quote['total_cents'])) ?></strong></td></tr>
          <tr><th>Deposit required</th><td><?= e(money($quote['deposit_cents'])) ?></td></tr>
        </table>

        <?php if ($quote['notes_to_customer']): ?>
          <div class="prose" style="margin-top:1.5rem;">
            <h3>Notes from Unwinded</h3>
            <?= nl2br(e($quote['notes_to_customer'])) ?>
          </div>
        <?php endif; ?>

        <?php if ($canAct): ?>
        <div style="display:flex;gap:12px;margin-top:2rem;flex-wrap:wrap;">
          <form method="POST" action="<?= url('/quote/' . $quote['public_ref'] . '/' . $token . '/accept') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--primary">Accept quote</button>
          </form>
          <form method="POST" action="<?= url('/quote/' . $quote['public_ref'] . '/' . $token . '/decline') ?>"
                onsubmit="return confirm('Are you sure you want to decline this quote?');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--danger btn--outline">Decline</button>
          </form>
        </div>
        <?php elseif ($quote['status'] === 'accepted'): ?>
          <p class="alert alert--success" style="margin-top:1.5rem;">&#10003; You accepted this quote. We'll be in touch shortly.</p>
        <?php elseif ($quote['status'] === 'declined'): ?>
          <p class="alert alert--neutral" style="margin-top:1.5rem;">This quote was declined. <a href="<?= url('/contact') ?>">Contact us</a> if you change your mind.</p>
        <?php elseif ($quote['status'] === 'expired'): ?>
          <p class="alert alert--warning" style="margin-top:1.5rem;">This quote has expired. <a href="<?= url('/contact') ?>">Contact us</a> to request a new one.</p>
        <?php endif; ?>
      </div>
    </div>

    <p style="margin-top:1.5rem;font-size:.875rem;color:#666;">
      Questions? <a href="<?= url('/contact') ?>">Contact us</a>
    </p>
  </div>
</section>
