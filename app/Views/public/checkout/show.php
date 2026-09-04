<section class="section">
  <div class="container" style="max-width:720px;">
    <div class="breadcrumb">
      <a href="<?= url('/events') ?>">Events</a> &rsaquo;
      <a href="<?= url('/events/' . $event['slug']) ?>"><?= e($event['title']) ?></a> &rsaquo;
      Book tickets
    </div>

    <h1 class="section__title"><?= e($event['title']) ?></h1>
    <p>
      <?php if ($event['event_date']): ?><?= e(date('l, d F Y', strtotime($event['event_date']))) ?><?php endif; ?>
      <?php if ($event['start_time']): ?> at <?= e(date('g:i A', strtotime($event['start_time']))) ?><?php endif; ?>
      <?php if ($event['venue_name']): ?> &bull; <?= e($event['venue_name']) ?><?php endif; ?>
    </p>

    <?php if (!empty($errors)): ?>
      <div class="alert alert--error" style="margin-bottom:1.5rem;">
        <ul style="margin:0;padding-left:1.25rem;">
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/events/' . $event['slug'] . '/checkout') ?>">
      <?= csrf_field() ?>

      <!-- Ticket selection -->
      <div class="card" style="margin-bottom:1.5rem;">
        <div class="card__header"><h2>Select tickets</h2></div>
        <div class="card__body">
          <?php foreach ($ticketTypes as $tt): ?>
            <?php
              $remaining = max(0, (int) $tt['qty_available'] - (int) $tt['qty_reserved'] - (int) $tt['qty_sold']);
              $soldOut   = $remaining === 0;
            ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.75rem 0;border-bottom:1px solid var(--color-border,#e5e7eb);">
              <div>
                <strong><?= e($tt['name']) ?></strong>
                <?php if ($tt['description']): ?>
                  <br><small style="color:var(--color-text-secondary,#6b7280);"><?= e($tt['description']) ?></small>
                <?php endif; ?>
                <br>
                <span style="font-weight:600;color:var(--color-primary,#1a1a2e);"><?= e(money($tt['price_cents'])) ?></span>
                <?php if ($soldOut): ?>
                  <span style="margin-left:.5rem;color:var(--color-danger,#dc3545);font-size:.875rem;">Sold out</span>
                <?php else: ?>
                  <small style="color:var(--color-text-secondary,#6b7280);margin-left:.5rem;"><?= $remaining ?> left</small>
                <?php endif; ?>
              </div>
              <div>
                <?php if ($soldOut): ?>
                  <input type="hidden" name="qty_<?= (int) $tt['id'] ?>" value="0">
                  <select class="form-input form-input--select" style="width:80px;" disabled>
                    <option>0</option>
                  </select>
                <?php else: ?>
                  <select name="qty_<?= (int) $tt['id'] ?>" class="form-input form-input--select" style="width:80px;"
                          onchange="updateTotal()">
                    <?php for ($i = 0; $i <= min($remaining, (int) $tt['max_per_order']); $i++): ?>
                      <option value="<?= $i ?>"><?= $i ?></option>
                    <?php endfor; ?>
                  </select>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>

          <div style="text-align:right;margin-top:1rem;">
            <strong>Total: <span id="order-total">R 0.00</span></strong>
          </div>
        </div>
      </div>

      <!-- Purchaser details -->
      <div class="card" style="margin-bottom:1.5rem;">
        <div class="card__header"><h2>Your details</h2></div>
        <div class="card__body">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Full name <span class="required">*</span></label>
              <input type="text" name="name" class="form-input" value="<?= attr($_POST['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Email <span class="required">*</span></label>
              <input type="email" name="email" class="form-input" value="<?= attr($_POST['email'] ?? '') ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="tel" name="phone" class="form-input" value="<?= attr($_POST['phone'] ?? '') ?>">
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn--primary btn--lg" style="width:100%;">Reserve tickets →</button>
      <p style="text-align:center;margin-top:.5rem;font-size:.875rem;color:var(--color-text-secondary,#6b7280);">
        Your tickets will be held for 15 minutes while you complete payment.
      </p>
    </form>
  </div>
</section>

<script>
const prices = {
  <?php foreach ($ticketTypes as $tt): ?>
  <?= (int) $tt['id'] ?>: <?= (int) $tt['price_cents'] ?>,
  <?php endforeach; ?>
};

function updateTotal() {
  let totalCents = 0;
  for (const [id, price] of Object.entries(prices)) {
    const sel = document.querySelector(`select[name="qty_${id}"]`);
    if (sel) totalCents += parseInt(sel.value, 10) * price;
  }
  const rand = (totalCents / 100).toLocaleString('en-ZA', { style: 'currency', currency: 'ZAR' });
  document.getElementById('order-total').textContent = rand;
}
</script>
