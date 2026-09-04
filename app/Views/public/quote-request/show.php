
<div class="page-hero page-hero--sm">
  <div class="container">
    <h1>Request a Quote</h1>
    <p>Tell us about your event and we'll send you a personalised proposal within one business day.</p>
  </div>
</div>

<section class="section">
  <div class="container container--narrow">
    <?php foreach (flash()->getAll() as $type => $msgs): ?>
      <?php foreach ($msgs as $msg): ?>
        <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
      <?php endforeach; ?>
    <?php endforeach; ?>

    <form method="POST" action="<?= url('/request-a-quote') ?>" novalidate>
      <?= csrf_field() ?>
      <!-- Honeypot -->
      <input type="text" name="website" class="visually-hidden" autocomplete="off" tabindex="-1" aria-hidden="true">

      <h2 class="form-section-heading">Your details</h2>

      <div class="form-row">
        <div class="form-group">
          <label for="customer_name" class="form-label">Full name <span class="required">*</span></label>
          <input type="text" id="customer_name" name="customer_name" class="form-input <?= !empty($errors['customer_name']) ? 'form-input--error' : '' ?>"
                 value="<?= attr($old['customer_name'] ?? '') ?>" required autocomplete="name">
          <?php if (!empty($errors['customer_name'])): ?><p class="form-error"><?= e($errors['customer_name']) ?></p><?php endif; ?>
        </div>
        <div class="form-group">
          <label for="customer_email" class="form-label">Email <span class="required">*</span></label>
          <input type="email" id="customer_email" name="customer_email" class="form-input <?= !empty($errors['customer_email']) ? 'form-input--error' : '' ?>"
                 value="<?= attr($old['customer_email'] ?? '') ?>" required autocomplete="email">
          <?php if (!empty($errors['customer_email'])): ?><p class="form-error"><?= e($errors['customer_email']) ?></p><?php endif; ?>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="customer_phone" class="form-label">Phone</label>
          <input type="tel" id="customer_phone" name="customer_phone" class="form-input"
                 value="<?= attr($old['customer_phone'] ?? '') ?>" autocomplete="tel">
        </div>
        <div class="form-group">
          <label for="customer_whatsapp" class="form-label">WhatsApp number</label>
          <input type="tel" id="customer_whatsapp" name="customer_whatsapp" class="form-input"
                 value="<?= attr($old['customer_whatsapp'] ?? '') ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="customer_company" class="form-label">Company (if applicable)</label>
          <input type="text" id="customer_company" name="customer_company" class="form-input"
                 value="<?= attr($old['customer_company'] ?? '') ?>" autocomplete="organization">
        </div>
        <div class="form-group">
          <label for="preferred_contact" class="form-label">Preferred contact method</label>
          <select id="preferred_contact" name="preferred_contact" class="form-input form-input--select">
            <?php foreach (['email' => 'Email', 'phone' => 'Phone', 'whatsapp' => 'WhatsApp'] as $val => $lbl): ?>
              <option value="<?= $val ?>" <?= ($old['preferred_contact'] ?? 'email') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <h2 class="form-section-heading">Event details</h2>

      <div class="form-row">
        <div class="form-group">
          <label for="event_type" class="form-label">Type of event</label>
          <select id="event_type" name="event_type" class="form-input form-input--select">
            <option value="">Select…</option>
            <?php foreach ([
                'birthday'           => 'Birthday / Celebration',
                'hens_party'         => 'Hen\'s party / Bachelorette',
                'baby_shower'        => 'Baby shower',
                'bridal_shower'      => 'Bridal shower',
                'couples'            => 'Couples event',
                'corporate_teamuild' => 'Corporate team-build',
                'corporate_function' => 'Corporate function / Year-end',
                'restaurant'         => 'Restaurant / Venue partnership',
                'other'              => 'Other',
            ] as $val => $lbl): ?>
              <option value="<?= $val ?>" <?= ($old['event_type'] ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="guest_count" class="form-label">Estimated guest count</label>
          <input type="number" id="guest_count" name="guest_count" class="form-input"
                 value="<?= attr($old['guest_count'] ?? '') ?>" min="1" max="500">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="preferred_date" class="form-label">Preferred date</label>
          <input type="date" id="preferred_date" name="preferred_date" class="form-input"
                 value="<?= attr($old['preferred_date'] ?? '') ?>"
                 min="<?= date('Y-m-d', strtotime('+7 days')) ?>">
        </div>
        <div class="form-group">
          <label for="alternative_date" class="form-label">Alternative date</label>
          <input type="date" id="alternative_date" name="alternative_date" class="form-input"
                 value="<?= attr($old['alternative_date'] ?? '') ?>"
                 min="<?= date('Y-m-d', strtotime('+7 days')) ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="start_time" class="form-label">Preferred start time</label>
          <input type="time" id="start_time" name="start_time" class="form-input"
                 value="<?= attr($old['start_time'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="package_id" class="form-label">Package of interest</label>
          <select id="package_id" name="package_id" class="form-input form-input--select">
            <option value="">Not sure yet</option>
            <?php foreach ($packages as $pkg): ?>
              <option value="<?= $pkg['id'] ?>" <?= ($old['package_id'] ?? '') == $pkg['id'] ? 'selected' : '' ?>>
                <?= e($pkg['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label for="has_venue" class="form-label">Do you have a venue?</label>
        <select id="has_venue" name="has_venue" class="form-input form-input--select">
          <option value="">Not sure</option>
          <option value="1" <?= ($old['has_venue'] ?? '') === '1' ? 'selected' : '' ?>>Yes, I have a venue</option>
          <option value="0" <?= ($old['has_venue'] ?? '') === '0' ? 'selected' : '' ?>>No, I need help with a venue</option>
        </select>
      </div>

      <div class="form-group">
        <label for="venue_name" class="form-label">Venue name / address (if known)</label>
        <input type="text" id="venue_name" name="venue_name" class="form-input"
               value="<?= attr($old['venue_name'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label for="preferred_artwork" class="form-label">Preferred artwork / theme</label>
        <textarea id="preferred_artwork" name="preferred_artwork" class="form-input form-input--textarea" rows="3"><?= e($old['preferred_artwork'] ?? '') ?></textarea>
        <p class="form-hint">Describe the painting style or theme you'd like, or leave blank and we'll suggest something.</p>
      </div>

      <div class="form-group">
        <label for="extra_notes" class="form-label">Anything else we should know?</label>
        <textarea id="extra_notes" name="extra_notes" class="form-input form-input--textarea" rows="4"><?= e($old['extra_notes'] ?? '') ?></textarea>
      </div>

      <h2 class="form-section-heading">Consent</h2>

      <div class="form-group">
        <label class="checkbox-label">
          <input type="checkbox" name="contact_consent" value="1"
                 <?= !empty($old['contact_consent']) ? 'checked' : '' ?>>
          I agree to receive marketing communications from Unwinded. I can unsubscribe at any time.
        </label>
      </div>

      <div class="form-group">
        <label class="checkbox-label">
          <input type="checkbox" name="terms_consent" value="1" required
                 <?= !empty($old['terms_consent']) ? 'checked' : '' ?>>
          I agree to the <a href="<?= url('/terms') ?>" target="_blank">Terms of Service</a> and
          <a href="<?= url('/privacy') ?>" target="_blank">Privacy Policy</a>. <span class="required">*</span>
        </label>
        <?php if (!empty($errors['terms_consent'])): ?><p class="form-error"><?= e($errors['terms_consent']) ?></p><?php endif; ?>
      </div>

      <button type="submit" class="btn btn--primary btn--lg">Submit request</button>
    </form>
  </div>
</section>
