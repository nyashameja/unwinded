
<section class="section" style="padding-top:80px;padding-bottom:80px;">
  <div class="container container--narrow" style="text-align:center;">
    <?php if ($confirmed): ?>
      <div class="thank-you-icon" aria-hidden="true">&#10003;</div>
      <h1>You're subscribed!</h1>
      <p class="thank-you__body">
        Thanks for confirming your email address. You'll be the first to hear about upcoming events, workshops, and special offers from Unwinded.
      </p>
      <p class="thank-you__sub">
        <a href="<?= url('/events') ?>">Browse upcoming events</a> &middot;
        <a href="<?= url('/experiences') ?>">Our experiences</a>
      </p>
    <?php else: ?>
      <div style="font-size:3rem;margin-bottom:1rem;">&#10007;</div>
      <h1>Invalid or expired link</h1>
      <p class="thank-you__body">
        This confirmation link is no longer valid. It may have already been used or may have expired.
      </p>
      <p class="thank-you__sub">
        <a href="<?= url('/contact') ?>">Contact us</a> if you need help.
      </p>
    <?php endif; ?>
    <a href="<?= url('/') ?>" class="btn btn--secondary" style="margin-top:2rem;">Back to home</a>
  </div>
</section>
