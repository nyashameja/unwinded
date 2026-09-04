<section class="section">
  <div class="container" style="max-width:480px;text-align:center;">

    <?php if ($ticket['status'] === 'cancelled' || $ticket['status'] === 'refunded'): ?>
      <div class="alert alert--error" style="margin-bottom:1.5rem;">
        This ticket is <strong><?= e($ticket['status']) ?></strong> and is no longer valid for entry.
      </div>
    <?php elseif ($ticket['status'] === 'used'): ?>
      <div class="alert alert--warning" style="margin-bottom:1.5rem;">
        This ticket has already been <strong>checked in</strong>.
      </div>
    <?php else: ?>
      <div style="background:#d4edda;color:#155724;padding:.75rem 1rem;border-radius:4px;margin-bottom:1.5rem;font-weight:600;">
        ✓ Valid ticket
      </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card__header">
        <h1 style="font-size:1.25rem;margin:0;"><?= e($ticket['event_title'] ?? 'Event') ?></h1>
      </div>
      <div class="card__body">
        <?php if ($ticket['event_date']): ?>
          <p style="margin:.25rem 0;">📅 <?= e(date('l, d F Y', strtotime($ticket['event_date']))) ?></p>
        <?php endif; ?>
        <?php if ($ticket['start_time']): ?>
          <p style="margin:.25rem 0;">🕐 <?= e(date('g:i A', strtotime($ticket['start_time']))) ?></p>
        <?php endif; ?>
        <?php if ($ticket['venue_name']): ?>
          <p style="margin:.25rem 0;">📍 <?= e($ticket['venue_name']) ?><?= !empty($ticket['venue_city']) ? ', ' . e($ticket['venue_city']) : '' ?></p>
        <?php endif; ?>

        <hr style="margin:1rem 0;">

        <p style="margin:.25rem 0;"><strong><?= e($ticket['ticket_type_name'] ?? 'Ticket') ?></strong></p>
        <?php if ($ticket['attendee_name']): ?>
          <p style="margin:.25rem 0;color:var(--color-text-secondary,#6b7280);"><?= e($ticket['attendee_name']) ?></p>
        <?php endif; ?>

        <!-- QR code -->
        <div style="margin:1.5rem auto;display:flex;justify-content:center;">
          <?php
            $qrPayload = $ticket['qr_payload'] ?: url('/t/' . urlencode($ticket['ticket_uid']));
            try {
              $qrOptions = new \chillerlan\QRCode\QROptions([
                'outputType' => \chillerlan\QRCode\Output\QROutputInterface::MARKUP_SVG,
                'addQuietzone' => true,
              ]);
              $qrSvg = (new \chillerlan\QRCode\QRCode($qrOptions))->render($qrPayload);
              echo '<div style="max-width:250px;">' . $qrSvg . '</div>';
            } catch (\Throwable $e) {
              echo '<div style="width:250px;height:250px;border:2px dashed #ccc;display:flex;align-items:center;justify-content:center;color:#999;font-size:.875rem;">QR code unavailable</div>';
            }
          ?>
        </div>

        <p style="font-family:monospace;font-size:.85rem;word-break:break-all;color:var(--color-text-secondary,#6b7280);">
          <?= e($ticket['ticket_uid']) ?>
        </p>
      </div>
    </div>

    <p style="font-size:.875rem;color:var(--color-text-secondary,#6b7280);">
      Present this QR code at the entrance. One admission per ticket.
    </p>
  </div>
</section>
