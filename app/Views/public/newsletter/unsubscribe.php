
<section class="section" style="padding-top:80px;padding-bottom:80px;">
  <div class="container container--narrow" style="text-align:center;">
    <?php if ($unsubscribed): ?>
      <h1>You've been unsubscribed</h1>
      <p class="thank-you__body">
        You've been removed from our newsletter list. We're sorry to see you go!
      </p>
      <p class="thank-you__sub">
        Changed your mind? You can always <a href="<?= url('/contact') ?>">get in touch</a> with us.
      </p>
    <?php else: ?>
      <h1>Invalid link</h1>
      <p class="thank-you__body">
        This unsubscribe link is not valid. It may have already been used.
      </p>
      <p class="thank-you__sub">
        <a href="<?= url('/contact') ?>">Contact us</a> if you need help.
      </p>
    <?php endif; ?>
    <a href="<?= url('/') ?>" class="btn btn--secondary" style="margin-top:2rem;">Back to home</a>
  </div>
</section>
