<div class="grid grid--2col" style="gap:1.5rem;align-items:start;">
  <div>
    <!-- Basic details -->
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Event details</h2></div>
      <div class="card__body">
        <div class="form-group">
          <label class="form-label">Title <span class="required">*</span></label>
          <input type="text" name="title" class="form-input <?= !empty($errors['title']) ? 'form-input--error' : '' ?>"
                 value="<?= attr($event['title'] ?? '') ?>" required>
          <?php if (!empty($errors['title'])): ?><p class="form-error"><?= e($errors['title']) ?></p><?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label">URL slug</label>
          <input type="text" name="slug" class="form-input" value="<?= attr($event['slug'] ?? '') ?>"
                 placeholder="auto-generated from title">
        </div>

        <div class="form-group">
          <label class="form-label">Short description</label>
          <textarea name="short_description" class="form-input form-input--textarea" rows="2"><?= e($event['short_description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Full description</label>
          <textarea name="body" class="form-input form-input--textarea" rows="6"><?= e($event['body'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Event date</label>
            <input type="date" name="event_date" class="form-input <?= !empty($errors['event_date']) ? 'form-input--error' : '' ?>"
                   value="<?= attr($event['event_date'] ?? '') ?>">
            <?php if (!empty($errors['event_date'])): ?><p class="form-error"><?= e($errors['event_date']) ?></p><?php endif; ?>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-input form-input--select">
              <?php foreach ($statuses as $s): ?>
                <option value="<?= e($s) ?>" <?= ($event['status'] ?? 'draft') === $s ? 'selected' : '' ?>>
                  <?= e(ucwords(str_replace('_',' ',$s))) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start time</label>
            <input type="time" name="start_time" class="form-input" value="<?= attr($event['start_time'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">End time</label>
            <input type="time" name="end_time" class="form-input" value="<?= attr($event['end_time'] ?? '') ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Sales open</label>
            <input type="datetime-local" name="sales_open_at" class="form-input"
                   value="<?= attr($event['sales_open_at'] ? date('Y-m-d\TH:i', strtotime($event['sales_open_at'])) : '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Sales close</label>
            <input type="datetime-local" name="sales_close_at" class="form-input"
                   value="<?= attr($event['sales_close_at'] ? date('Y-m-d\TH:i', strtotime($event['sales_close_at'])) : '') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Venue -->
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Venue</h2></div>
      <div class="card__body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Venue name</label>
            <input type="text" name="venue_name" class="form-input" value="<?= attr($event['venue_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">City</label>
            <input type="text" name="venue_city" class="form-input" value="<?= attr($event['venue_city'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Address</label>
          <input type="text" name="venue_address" class="form-input" value="<?= attr($event['venue_address'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Google Maps link</label>
          <input type="url" name="map_link" class="form-input" value="<?= attr($event['map_link'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Total capacity</label>
          <input type="number" name="total_capacity" class="form-input" min="0" value="<?= attr($event['total_capacity'] ?? '0') ?>">
        </div>
      </div>
    </div>

    <!-- Policy -->
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Event info</h2></div>
      <div class="card__body">
        <div class="form-group">
          <label class="form-label">What's included</label>
          <textarea name="what_is_included" class="form-input form-input--textarea" rows="3"><?= e($event['what_is_included'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">What to bring</label>
          <textarea name="what_to_bring" class="form-input form-input--textarea" rows="3"><?= e($event['what_to_bring'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Dress code</label>
          <input type="text" name="dress_code" class="form-input" value="<?= attr($event['dress_code'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Cancellation policy</label>
          <textarea name="cancellation_policy" class="form-input form-input--textarea" rows="3"><?= e($event['cancellation_policy'] ?? '') ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <div>
    <!-- Featured image -->
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Featured image</h2></div>
      <div class="card__body">
        <?php if (!empty($event['featured_image'])): ?>
          <img src="<?= attr($event['featured_image']) ?>" alt="" style="max-width:100%;margin-bottom:1rem;border-radius:4px;">
        <?php endif; ?>
        <div class="form-group">
          <label class="form-label">Image URL</label>
          <input type="text" name="featured_image" class="form-input" value="<?= attr($event['featured_image'] ?? '') ?>"
                 placeholder="Paste a media URL from the media library">
          <p class="form-hint">Browse the <a href="<?= url('/admin/media') ?>" target="_blank">media library</a> and paste the URL here.</p>
        </div>
      </div>
    </div>

    <!-- SEO -->
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>SEO</h2></div>
      <div class="card__body">
        <div class="form-group">
          <label class="form-label">Meta title</label>
          <input type="text" name="meta_title" class="form-input" maxlength="500" value="<?= attr($event['meta_title'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Meta description</label>
          <textarea name="meta_description" class="form-input form-input--textarea" rows="2" maxlength="500"><?= e($event['meta_description'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <button type="submit" class="btn btn--primary" style="width:100%;">Save event</button>
  </div>
</div>
