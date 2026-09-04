
<div class="page-hero page-hero--sm">
  <div class="container">
    <h1>Contact Us</h1>
    <p>We'd love to hear from you.</p>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="content-layout">

      <div class="content-layout__main">
        <?php foreach (flash()->getAll() as $type => $msgs): ?>
          <?php foreach ($msgs as $msg): ?>
            <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
          <?php endforeach; ?>
        <?php endforeach; ?>

        <form method="POST" action="<?= url('/contact') ?>" novalidate>
          <?= csrf_field() ?>
          <!-- Honeypot: hidden from real users, bots fill it -->
          <input type="text" name="website" class="visually-hidden" autocomplete="off" tabindex="-1" aria-hidden="true">

          <div class="form-group">
            <label for="name" class="form-label">Name <span class="required">*</span></label>
            <input type="text" id="name" name="name" class="form-input <?= !empty($errors['name']) ? 'form-input--error' : '' ?>"
                   value="<?= attr($old['name'] ?? '') ?>" required autocomplete="name">
            <?php if (!empty($errors['name'])): ?><p class="form-error"><?= e($errors['name']) ?></p><?php endif; ?>
          </div>

          <div class="form-group">
            <label for="email" class="form-label">Email <span class="required">*</span></label>
            <input type="email" id="email" name="email" class="form-input <?= !empty($errors['email']) ? 'form-input--error' : '' ?>"
                   value="<?= attr($old['email'] ?? '') ?>" required autocomplete="email">
            <?php if (!empty($errors['email'])): ?><p class="form-error"><?= e($errors['email']) ?></p><?php endif; ?>
          </div>

          <div class="form-group">
            <label for="phone" class="form-label">Phone</label>
            <input type="tel" id="phone" name="phone" class="form-input"
                   value="<?= attr($old['phone'] ?? '') ?>" autocomplete="tel">
          </div>

          <div class="form-group">
            <label for="subject" class="form-label">Subject <span class="required">*</span></label>
            <input type="text" id="subject" name="subject" class="form-input <?= !empty($errors['subject']) ? 'form-input--error' : '' ?>"
                   value="<?= attr($old['subject'] ?? '') ?>" required>
            <?php if (!empty($errors['subject'])): ?><p class="form-error"><?= e($errors['subject']) ?></p><?php endif; ?>
          </div>

          <div class="form-group">
            <label for="message" class="form-label">Message <span class="required">*</span></label>
            <textarea id="message" name="message" class="form-input form-input--textarea <?= !empty($errors['message']) ? 'form-input--error' : '' ?>"
                      rows="6" required><?= e($old['message'] ?? '') ?></textarea>
            <?php if (!empty($errors['message'])): ?><p class="form-error"><?= e($errors['message']) ?></p><?php endif; ?>
          </div>

          <button type="submit" class="btn btn--primary">Send message</button>
        </form>
      </div>

      <aside class="content-layout__sidebar">
        <div class="card">
          <div class="card__body">
            <h3>Get in touch</h3>
            <?php if ($email = config('contact.email')): ?>
              <p><strong>Email</strong><br>
                <a href="mailto:<?= attr($email) ?>"><?= e($email) ?></a></p>
            <?php endif; ?>
            <?php if ($phone = config('contact.phone')): ?>
              <p><strong>Phone</strong><br>
                <a href="tel:<?= attr(preg_replace('/\s/', '', $phone)) ?>"><?= e($phone) ?></a></p>
            <?php endif; ?>
            <?php if ($wa = config('contact.whatsapp')): ?>
              <p><strong>WhatsApp</strong><br>
                <a href="https://wa.me/<?= attr(preg_replace('/[^0-9]/', '', $wa)) ?>" target="_blank" rel="noopener noreferrer">
                  Chat on WhatsApp
                </a></p>
            <?php endif; ?>
            <?php if ($area = config('contact.area')): ?>
              <p><strong>Area</strong><br><?= e($area) ?></p>
            <?php endif; ?>
          </div>
        </div>

        <div class="card" style="margin-top:16px;">
          <div class="card__body">
            <h3>Looking to book an event?</h3>
            <p>For quotes and bookings, please use our dedicated form.</p>
            <a href="<?= url('/request-a-quote') ?>" class="btn btn--secondary btn--block">Request a quote</a>
          </div>
        </div>
      </aside>

    </div>
  </div>
</section>
