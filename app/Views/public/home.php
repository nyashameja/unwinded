<?php
/**
 * Public homepage placeholder — Phase 2.
 * Full implementation is Phase 5 (public website).
 */
$pageTitle   = config('app.name');
$metaRobots  = 'index,follow';
?>

<!-- Hero -->
<section class="hero" style="background:#2c1810;color:#f5e6d3;padding:120px 0;text-align:center;">
  <div class="container">
    <h1 style="font-size:clamp(2.5rem,6vw,5rem);letter-spacing:.15em;color:#d4a853;margin:0 0 .5em;">Unwinded</h1>
    <p style="font-size:1.25rem;letter-spacing:.2em;text-transform:uppercase;opacity:.85;margin:0 0 2em;">Sip &middot; Paint &middot; Unwind</p>
    <p style="font-size:1.1rem;max-width:550px;margin:0 auto 2.5em;line-height:1.7;opacity:.9;">
      Johannesburg's favourite sip-and-paint experience for private parties, corporate events, and public classes.
    </p>
    <a href="<?= url('/quote') ?>"
       style="display:inline-block;background:#d4a853;color:#2c1810;text-decoration:none;
              padding:16px 40px;border-radius:4px;font-size:1rem;letter-spacing:.1em;font-weight:bold;">
      Book an Experience
    </a>
  </div>
</section>

<!-- Coming soon notice (remove in Phase 5) -->
<section style="padding:80px 0;text-align:center;background:#faf7f3;">
  <div class="container">
    <h2 style="color:#2c1810;">Site launching soon</h2>
    <p style="color:#4a3728;max-width:480px;margin:0 auto 1.5em;line-height:1.7;">
      Full website is under development. The CMS is operational — admin access is at
      <a href="<?= url('/admin') ?>" style="color:#c4862b;">/admin</a>.
    </p>
    <p style="color:#8b7355;font-size:.9rem;">
      Questions? Email us at
      <a href="mailto:<?= attr(config('contact.email', 'hello@unwinded.co.za')) ?>"
         style="color:#c4862b;"><?= e(config('contact.email', 'hello@unwinded.co.za')) ?></a>
    </p>
  </div>
</section>
