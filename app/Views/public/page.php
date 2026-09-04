<?php
// Shared view for static CMS pages: about, how-it-works, terms, privacy, refund-policy.
// $page is the DB row (may be null if not seeded yet); $pageTitle is always set.
?>
<div class="page-content">
  <div class="container container--narrow">
    <?php if ($page && !empty($page['content'])): ?>
      <h1><?= e($page['title']) ?></h1>
      <div class="prose"><?= $page['content'] ?></div>
    <?php else: ?>
      <?php $slug = $page['slug'] ?? ''; ?>

      <?php if (str_contains($slug, 'about') || $pageTitle === 'About Us'): ?>
        <h1>About Unwinded</h1>
        <div class="prose">
          <p>Unwinded is Johannesburg's favourite sip-and-paint studio, bringing creativity, connection, and good vibes to private parties, corporate events, and public classes.</p>
          <p>No artistic experience needed — just arrive ready to enjoy yourself. We provide all the materials, step-by-step guidance, and a warm, welcoming space where you can let go, pick up a brush, and enjoy a glass of something good.</p>
          <h2>Our story</h2>
          <p>Born out of a love for art, hospitality, and community, Unwinded was founded to make creative experiences accessible and genuinely fun for everyone — from first-timers to seasoned painters.</p>
          <h2>What we offer</h2>
          <ul>
            <li>Private sip-and-paint parties (birthdays, hens, corporate team-builds)</li>
            <li>Public events — grab a ticket and join the fun</li>
            <li>Corporate packages tailored to your team</li>
            <li>Restaurant partnerships to elevate your venue's offering</li>
          </ul>
          <p><a href="<?= url('/request-a-quote') ?>">Get a personalised quote &rarr;</a></p>
        </div>

      <?php elseif (str_contains($slug, 'how')): ?>
        <h1>How It Works</h1>
        <div class="prose">
          <ol>
            <li><strong>Choose your experience</strong> — pick a private event, corporate package, or public class.</li>
            <li><strong>Request a quote</strong> — tell us about your event and we'll put together a personalised proposal.</li>
            <li><strong>Confirm and pay your deposit</strong> — a 50% deposit secures your date.</li>
            <li><strong>We take care of everything</strong> — materials, instruction, and good vibes included.</li>
            <li><strong>Arrive and unwind</strong> — relax, paint, and leave with a canvas you made yourself.</li>
          </ol>
          <p><a href="<?= url('/faqs') ?>">See our FAQs</a> or <a href="<?= url('/contact') ?>">get in touch</a> if you have any questions.</p>
        </div>

      <?php elseif (str_contains($slug, 'terms')): ?>
        <h1>Terms of Service</h1>
        <div class="prose">
          <p><em>Last updated: <?= date('F Y') ?>.</em></p>
          <p>By booking an Unwinded experience you agree to these terms.</p>
          <h2>Bookings and deposits</h2>
          <p>A 50% deposit is required to secure your booking. The remaining balance is due no later than 5 business days before your event.</p>
          <h2>Cancellations and refunds</h2>
          <p>Please see our <a href="<?= url('/refund-policy') ?>">Refund Policy</a> for full details.</p>
          <h2>Conduct</h2>
          <p>Unwinded reserves the right to remove any guest whose behaviour is deemed inappropriate, without refund.</p>
          <h2>Liability</h2>
          <p>Unwinded is not liable for loss of personal items or injuries arising from failure to follow instructions provided during the event.</p>
          <h2>Changes to terms</h2>
          <p>These terms may change at any time. Continued use of our services constitutes acceptance of the current terms.</p>
        </div>

      <?php elseif (str_contains($slug, 'privacy')): ?>
        <h1>Privacy Policy</h1>
        <div class="prose">
          <p><em>Last updated: <?= date('F Y') ?>.</em></p>
          <p>Your privacy matters to us. This policy explains what personal information we collect and how we use it.</p>
          <h2>What we collect</h2>
          <ul>
            <li>Name, email, and phone number when you make an enquiry or booking</li>
            <li>Payment details — processed securely via PayFast; we never store card numbers</li>
            <li>Usage data (IP address, browser type) for security and analytics</li>
          </ul>
          <h2>How we use it</h2>
          <ul>
            <li>To process and communicate about your bookings</li>
            <li>To send you marketing emails (only with your consent)</li>
            <li>To improve our website and services</li>
          </ul>
          <h2>Your rights</h2>
          <p>You may request access to, correction of, or deletion of your personal data at any time by emailing us at <?= e(config('contact.email', 'hello@unwinded.co.za')) ?>.</p>
          <h2>Data sharing</h2>
          <p>We do not sell your personal data. We share it only with service providers necessary to operate our business (e.g., payment processor, email service).</p>
        </div>

      <?php elseif (str_contains($slug, 'refund')): ?>
        <h1>Refund Policy</h1>
        <div class="prose">
          <p><em>Last updated: <?= date('F Y') ?>.</em></p>
          <h2>Private events</h2>
          <ul>
            <li>Cancellations more than 14 days before the event: full deposit refund.</li>
            <li>Cancellations 7–14 days before: 50% of deposit refunded.</li>
            <li>Cancellations less than 7 days before: no refund.</li>
            <li>Rescheduling is subject to availability and must be requested at least 7 days in advance.</li>
          </ul>
          <h2>Public event tickets</h2>
          <ul>
            <li>Tickets are non-refundable once purchased.</li>
            <li>Tickets may be transferred to another person at no charge.</li>
          </ul>
          <h2>Refund processing</h2>
          <p>Approved refunds are processed within 10 business days. Please contact us at <?= e(config('contact.email', 'hello@unwinded.co.za')) ?> to initiate a refund request.</p>
        </div>

      <?php else: ?>
        <h1><?= e($pageTitle) ?></h1>
        <p>Content coming soon.</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
