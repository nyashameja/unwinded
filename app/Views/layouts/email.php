<?php
// Email layout — plain PHP used to compose transactional emails before
// passing to PHPMailer. Not rendered via View::renderWithLayout().
// Instead, call View::render('emails/some_template', $data) which
// returns the full HTML string using this layout via the helper below.
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($subject ?? '') ?></title>
</head>
<body style="margin:0;padding:0;background:#f5f0eb;font-family:Georgia,'Times New Roman',serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="background:#f5f0eb;padding:40px 20px;">
  <tr><td align="center">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
           style="max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;">

      <!-- Header -->
      <tr><td style="background:#2c1810;padding:28px 40px;text-align:center;">
        <p style="margin:0;color:#d4a853;font-size:26px;letter-spacing:5px;font-style:italic;">Unwinded</p>
        <p style="margin:6px 0 0;color:#f5e6d3;font-size:11px;letter-spacing:3px;text-transform:uppercase;">Sip &middot; Paint &middot; Unwind</p>
      </td></tr>

      <!-- Body -->
      <tr><td style="padding:40px;">
        <?= $content ?>
      </td></tr>

      <!-- Divider -->
      <tr><td style="padding:0 40px;">
        <hr style="border:none;border-top:1px solid #e8ddd0;margin:0;">
      </td></tr>

      <!-- Footer -->
      <tr><td style="padding:24px 40px;text-align:center;">
        <p style="margin:0 0 8px;font-size:12px;color:#8b7355;font-family:Arial,sans-serif;">
          <?= e(config('contact.address', 'Johannesburg, South Africa')) ?>
        </p>
        <p style="margin:0;font-size:12px;color:#8b7355;font-family:Arial,sans-serif;">
          <a href="mailto:<?= attr(config('contact.email')) ?>" style="color:#8b7355;"><?= e(config('contact.email')) ?></a>
          &nbsp;&middot;&nbsp;
          <a href="<?= attr(url('/')) ?>" style="color:#8b7355;"><?= e(config('app.url')) ?></a>
        </p>
        <?php if (!empty($unsubscribeUrl)): ?>
        <p style="margin:12px 0 0;font-size:11px;color:#b0a090;font-family:Arial,sans-serif;">
          <a href="<?= attr($unsubscribeUrl) ?>" style="color:#b0a090;">Unsubscribe</a>
        </p>
        <?php endif; ?>
      </td></tr>

    </table>
  </td></tr>
</table>
</body>
</html>
