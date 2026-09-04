<section class="section">
  <div class="container" style="max-width:600px;text-align:center;">
    <div style="font-size:4rem;margin-bottom:1rem;">✉️</div>
    <h1 class="section__title">Thank you for your order!</h1>

    <?php if (!empty($order)): ?>
      <p style="font-size:1.125rem;margin-bottom:1.5rem;">
        Your order reference is <strong><?= e($order['public_ref']) ?></strong>.
      </p>
      <p>
        We've sent payment instructions to <strong><?= e($order['purchaser_email']) ?></strong>.
        Once we confirm your EFT payment, your tickets will be issued and emailed to you.
      </p>
      <p style="margin-top:1.5rem;">
        <a href="<?= url('/events') ?>" class="btn btn--secondary">Browse more events</a>
      </p>
    <?php else: ?>
      <p>Your order reference is <strong><?= e($ref) ?></strong>.</p>
      <p>
        Payment instructions have been emailed to you. Tickets will be issued once we confirm receipt of payment.
      </p>
      <a href="<?= url('/events') ?>" class="btn btn--secondary" style="margin-top:1.5rem;">Browse more events</a>
    <?php endif; ?>
  </div>
</section>
