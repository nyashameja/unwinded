<?php foreach (flash()->getAll() as $type => $messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php endforeach; ?>

<div class="page-header">
  <h1 class="page-header__title"><?= e($customer['name']) ?></h1>
  <a href="<?= url('/admin/customers') ?>" class="btn btn--secondary">&larr; All customers</a>
</div>

<div class="grid grid--2col" style="gap:1.5rem;align-items:start;">

  <!-- Left: edit form -->
  <div>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Customer details</h2></div>
      <div class="card__body">
        <form method="POST" action="<?= url('/admin/customers/' . $customer['id']) ?>">
          <?= csrf_field() ?>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Full name <span class="required">*</span></label>
              <input type="text" name="name" class="form-input <?= !empty($errors['name']) ? 'form-input--error' : '' ?>"
                     value="<?= attr($customer['name']) ?>" required>
              <?php if (!empty($errors['name'])): ?><p class="form-error"><?= e($errors['name']) ?></p><?php endif; ?>
            </div>
            <div class="form-group">
              <label class="form-label">Email <span class="required">*</span></label>
              <input type="email" name="email" class="form-input <?= !empty($errors['email']) ? 'form-input--error' : '' ?>"
                     value="<?= attr($customer['email']) ?>" required>
              <?php if (!empty($errors['email'])): ?><p class="form-error"><?= e($errors['email']) ?></p><?php endif; ?>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Phone</label>
              <input type="tel" name="phone" class="form-input" value="<?= attr($customer['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">WhatsApp</label>
              <input type="tel" name="whatsapp" class="form-input" value="<?= attr($customer['whatsapp'] ?? '') ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Company</label>
              <input type="text" name="company" class="form-input" value="<?= attr($customer['company'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Preferred contact</label>
              <select name="preferred_contact" class="form-input form-input--select">
                <?php foreach (['email'=>'Email','phone'=>'Phone','whatsapp'=>'WhatsApp'] as $v=>$l): ?>
                  <option value="<?= $v ?>" <?= $customer['preferred_contact'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Internal notes</label>
            <textarea name="notes" class="form-input form-input--textarea" rows="3"><?= e($customer['notes'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="btn btn--primary">Save changes</button>
        </form>
      </div>
    </div>

    <!-- Merge -->
    <div class="card">
      <div class="card__header"><h2>Merge into another customer</h2></div>
      <div class="card__body">
        <form method="POST" action="<?= url('/admin/customers/' . $customer['id'] . '/merge') ?>"
              onsubmit="return confirm('This will move all bookings, quotes, and payments to the target customer and archive this one. Continue?')">
          <?= csrf_field() ?>
          <div class="form-group">
            <label class="form-label">Target customer ID</label>
            <input type="number" name="target_customer_id" class="form-input" min="1" required>
            <p class="form-hint">Enter the numeric ID of the customer to merge into. This action cannot be undone.</p>
          </div>
          <button type="submit" class="btn btn--danger">Merge</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Right: activity -->
  <div>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Meta</h2></div>
      <div class="card__body">
        <table class="data-table">
          <tr><th>Ref</th><td><?= e($customer['public_ref']) ?></td></tr>
          <tr><th>Marketing consent</th><td><?= $customer['marketing_consent'] ? 'Yes' : 'No' ?></td></tr>
          <?php if ($customer['consent_at']): ?>
            <tr><th>Consent given</th><td><?= e(date('d M Y', strtotime($customer['consent_at']))) ?></td></tr>
          <?php endif; ?>
          <tr><th>Customer since</th><td><?= e(date('d M Y', strtotime($customer['created_at']))) ?></td></tr>
        </table>
      </div>
    </div>

    <?php if ($bookings): ?>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Bookings (<?= count($bookings) ?>)</h2></div>
      <div class="card__body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Ref</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($bookings as $b): ?>
            <tr>
              <td><a href="<?= url('/admin/bookings/' . $b['id']) ?>"><?= e($b['public_ref']) ?></a></td>
              <td><?= e(date('d M Y', strtotime($b['event_date']))) ?></td>
              <td><?= e(money($b['total_cents'])) ?></td>
              <td><span class="badge badge--neutral"><?= e(str_replace('_',' ',$b['booking_status'])) ?></span></td>
              <td><a href="<?= url('/admin/bookings/' . $b['id']) ?>" class="btn btn--xs btn--secondary">View</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($quotes): ?>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Quotes (<?= count($quotes) ?>)</h2></div>
      <div class="card__body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Ref</th><th>Total</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($quotes as $q): ?>
            <tr>
              <td><a href="<?= url('/admin/quotations/' . $q['id']) ?>"><?= e($q['public_ref']) ?></a></td>
              <td><?= e(money($q['total_cents'])) ?></td>
              <td><span class="badge badge--neutral"><?= e($q['status']) ?></span></td>
              <td><a href="<?= url('/admin/quotations/' . $q['id']) ?>" class="btn btn--xs btn--secondary">View</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($requests): ?>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header"><h2>Quote requests (<?= count($requests) ?>)</h2></div>
      <div class="card__body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Ref</th><th>Event type</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($requests as $r): ?>
            <tr>
              <td><a href="<?= url('/admin/quote-requests/' . $r['id']) ?>"><?= e($r['public_ref']) ?></a></td>
              <td><?= e($r['event_type'] ?? '—') ?></td>
              <td><span class="badge badge--neutral"><?= e($r['status']) ?></span></td>
              <td><a href="<?= url('/admin/quote-requests/' . $r['id']) ?>" class="btn btn--xs btn--secondary">View</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($payments): ?>
    <div class="card">
      <div class="card__header"><h2>Payments (<?= count($payments) ?>)</h2></div>
      <div class="card__body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Ref</th><th>Amount</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($payments as $p): ?>
            <tr>
              <td><a href="<?= url('/admin/payments/' . $p['id']) ?>"><?= e($p['public_ref']) ?></a></td>
              <td><?= e(money($p['amount_cents'])) ?></td>
              <td><span class="badge badge--neutral"><?= e($p['status']) ?></span></td>
              <td><a href="<?= url('/admin/payments/' . $p['id']) ?>" class="btn btn--xs btn--secondary">View</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
