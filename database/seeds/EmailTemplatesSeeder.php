<?php

declare(strict_types=1);

use Unwinded\Core\Database;

class EmailTemplatesSeeder
{
    public function run(Database $db): void
    {
        $templates = [
            [
                'slug'        => 'quote_request_received',
                'name'        => 'Quote Request Received (Customer)',
                'description' => 'Sent to the customer after they submit a quote request',
                'subject'     => 'We\'ve received your quote request — Unwinded',
                'variables'   => ['customer_name', 'ref', 'event_type', 'event_date', 'guest_count', 'admin_url'],
                'body_html'   => $this->quoteRequestReceived(),
            ],
            [
                'slug'        => 'quote_sent',
                'name'        => 'Quote Sent to Customer',
                'description' => 'Sent when a formal quote is issued',
                'subject'     => 'Your Unwinded Quote — {{ref}}',
                'variables'   => ['customer_name', 'ref', 'total', 'deposit_amount', 'valid_until', 'quote_url'],
                'body_html'   => $this->quoteSent(),
            ],
            [
                'slug'        => 'quote_reminder',
                'name'        => 'Quote Expiry Reminder',
                'description' => 'Sent on day 10 of validity to remind customer to accept',
                'subject'     => 'Your Unwinded quote expires soon — {{ref}}',
                'variables'   => ['customer_name', 'ref', 'valid_until', 'quote_url'],
                'body_html'   => $this->quoteReminder(),
            ],
            [
                'slug'        => 'booking_confirmed',
                'name'        => 'Booking Confirmed',
                'description' => 'Sent when a private booking is confirmed after deposit paid',
                'subject'     => 'Your Unwinded event is confirmed! — {{ref}}',
                'variables'   => ['customer_name', 'ref', 'event_date', 'event_time', 'venue', 'guest_count', 'balance_amount', 'balance_due'],
                'body_html'   => $this->bookingConfirmed(),
            ],
            [
                'slug'        => 'deposit_reminder',
                'name'        => 'Deposit Payment Reminder',
                'description' => 'Sent when deposit soft deadline approaches',
                'subject'     => 'Action needed: Deposit due soon — {{ref}}',
                'variables'   => ['customer_name', 'ref', 'deposit_amount', 'due_date', 'payment_url'],
                'body_html'   => $this->depositReminder(),
            ],
            [
                'slug'        => 'balance_reminder',
                'name'        => 'Balance Payment Reminder',
                'description' => 'Sent when balance is due before the event',
                'subject'     => 'Balance payment due — {{ref}}',
                'variables'   => ['customer_name', 'ref', 'balance_amount', 'due_date', 'payment_url'],
                'body_html'   => $this->balanceReminder(),
            ],
            [
                'slug'        => 'payment_received',
                'name'        => 'Payment Received',
                'description' => 'Sent when any payment is successfully processed',
                'subject'     => 'Payment received — {{ref}}',
                'variables'   => ['customer_name', 'ref', 'amount', 'payment_type', 'balance_remaining'],
                'body_html'   => $this->paymentReceived(),
            ],
            [
                'slug'        => 'ticket_order_confirmed',
                'name'        => 'Ticket Order Confirmed',
                'description' => 'Sent with QR tickets after successful ticket purchase',
                'subject'     => 'Your tickets are confirmed — {{event_name}}',
                'variables'   => ['customer_name', 'order_ref', 'event_name', 'event_date', 'event_time', 'venue', 'ticket_count'],
                'body_html'   => $this->ticketOrderConfirmed(),
            ],
            [
                'slug'        => 'gallery_access',
                'name'        => 'Private Gallery Access Link',
                'description' => 'Sent to grant access to a private event gallery',
                'subject'     => 'Your event photos are ready — Unwinded',
                'variables'   => ['customer_name', 'event_name', 'event_date', 'gallery_url', 'expires_at'],
                'body_html'   => $this->galleryAccess(),
            ],
            [
                'slug'        => 'newsletter_confirm',
                'name'        => 'Newsletter Subscription Confirmation',
                'description' => 'Double opt-in confirmation email for newsletter',
                'subject'     => 'Confirm your Unwinded newsletter subscription',
                'variables'   => ['name', 'confirm_url'],
                'body_html'   => $this->newsletterConfirm(),
            ],
            [
                'slug'        => 'enquiry_received',
                'name'        => 'Enquiry Received (Customer)',
                'description' => 'Auto-reply confirming enquiry was received',
                'subject'     => 'We\'ve received your message — Unwinded',
                'variables'   => ['name', 'ref'],
                'body_html'   => $this->enquiryReceived(),
            ],
            [
                'slug'        => 'refund_approved',
                'name'        => 'Refund Approved',
                'description' => 'Sent when admin approves a refund',
                'subject'     => 'Refund approved — {{ref}}',
                'variables'   => ['customer_name', 'ref', 'amount', 'due_by'],
                'body_html'   => $this->refundApproved(),
            ],
            [
                'slug'        => 'admin_new_quote_request',
                'name'        => 'Admin: New Quote Request',
                'description' => 'Internal notification when a new quote request is received',
                'subject'     => '[Unwinded] New quote request — {{ref}}',
                'variables'   => ['ref', 'customer_name', 'event_type', 'event_date', 'guest_count', 'admin_url'],
                'body_html'   => $this->adminNewQuoteRequest(),
            ],
        ];

        foreach ($templates as $tpl) {
            $existing = $db->fetchOne(
                "SELECT id FROM email_templates WHERE slug = ?",
                [$tpl['slug']]
            );

            if (!$existing) {
                $db->insert('email_templates', [
                    'slug'        => $tpl['slug'],
                    'name'        => $tpl['name'],
                    'description' => $tpl['description'],
                    'subject'     => $tpl['subject'],
                    'body_html'   => $tpl['body_html'],
                    'variables'   => json_encode($tpl['variables']),
                ]);
                echo "Email template created: {$tpl['slug']}" . PHP_EOL;
            } else {
                echo "Email template exists (skipped): {$tpl['slug']}" . PHP_EOL;
            }
        }
    }

    private function baseLayout(string $title, string $content): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title}</title>
</head>
<body style="margin:0;padding:0;background:#f5f0eb;font-family:Georgia,serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f0eb;padding:40px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;max-width:600px;">
      <tr><td style="background:#2c1810;padding:30px 40px;text-align:center;">
        <p style="margin:0;color:#d4a853;font-size:28px;letter-spacing:4px;font-style:italic;">Unwinded</p>
        <p style="margin:6px 0 0;color:#f5e6d3;font-size:13px;letter-spacing:2px;">SIP · PAINT · UNWIND</p>
      </td></tr>
      <tr><td style="padding:40px;">
        {$content}
      </td></tr>
      <tr><td style="background:#f5f0eb;padding:24px 40px;text-align:center;border-top:1px solid #e8ddd0;">
        <p style="margin:0;font-size:12px;color:#8b7355;">
          © Unwinded · Johannesburg, South Africa<br>
          <a href="{{unsubscribe_url}}" style="color:#8b7355;">Unsubscribe</a>
        </p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }

    private function quoteRequestReceived(): string
    {
        $content = <<<HTML
<h2 style="color:#2c1810;margin:0 0 16px;">Hi {{customer_name}},</h2>
<p style="color:#4a3728;line-height:1.7;">Thank you for your quote request! We've received your enquiry and one of our coordinators will be in touch within 1–2 business days.</p>
<p style="color:#4a3728;line-height:1.7;"><strong>Reference:</strong> {{ref}}<br>
<strong>Event type:</strong> {{event_type}}<br>
<strong>Preferred date:</strong> {{event_date}}<br>
<strong>Guest count:</strong> {{guest_count}}</p>
<p style="color:#4a3728;line-height:1.7;">In the meantime, feel free to browse our <a href="{{packages_url}}" style="color:#c4862b;">packages and extras</a>.</p>
<p style="color:#4a3728;line-height:1.7;">Warm regards,<br>The Unwinded Team</p>
HTML;
        return $this->baseLayout('Quote Request Received', $content);
    }

    private function quoteSent(): string
    {
        $content = <<<HTML
<h2 style="color:#2c1810;margin:0 0 16px;">Hi {{customer_name}},</h2>
<p style="color:#4a3728;line-height:1.7;">Your personalised quote is ready! Please click below to review it.</p>
<table cellpadding="0" cellspacing="0" style="margin:24px 0;background:#faf7f3;border-radius:6px;padding:20px;width:100%;">
  <tr><td><strong>Reference:</strong></td><td>{{ref}}</td></tr>
  <tr><td><strong>Total:</strong></td><td>{{total}}</td></tr>
  <tr><td><strong>Deposit (50%):</strong></td><td>{{deposit_amount}}</td></tr>
  <tr><td><strong>Quote valid until:</strong></td><td>{{valid_until}}</td></tr>
</table>
<p style="text-align:center;">
  <a href="{{quote_url}}" style="display:inline-block;background:#c4862b;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:4px;font-size:16px;">View My Quote</a>
</p>
<p style="color:#4a3728;line-height:1.7;">Questions? Reply to this email or WhatsApp us at {{whatsapp}}.</p>
<p style="color:#4a3728;line-height:1.7;">Warm regards,<br>The Unwinded Team</p>
HTML;
        return $this->baseLayout('Your Quote', $content);
    }

    private function quoteReminder(): string
    {
        $content = <<<HTML
<h2 style="color:#2c1810;margin:0 0 16px;">Hi {{customer_name}},</h2>
<p style="color:#4a3728;line-height:1.7;">Just a friendly reminder — your Unwinded quote <strong>{{ref}}</strong> expires on <strong>{{valid_until}}</strong>.</p>
<p style="color:#4a3728;line-height:1.7;">To secure your date, please accept the quote and pay your deposit before then.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{{quote_url}}" style="display:inline-block;background:#c4862b;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:4px;font-size:16px;">View &amp; Accept My Quote</a>
</p>
<p style="color:#4a3728;line-height:1.7;">Warm regards,<br>The Unwinded Team</p>
HTML;
        return $this->baseLayout('Your Quote Expires Soon', $content);
    }

    private function bookingConfirmed(): string
    {
        $content = <<<HTML
<h2 style="color:#2c1810;margin:0 0 16px;">You're booked, {{customer_name}}!</h2>
<p style="color:#4a3728;line-height:1.7;">Your Unwinded event is confirmed. We can't wait to paint with you!</p>
<table cellpadding="0" cellspacing="0" style="margin:24px 0;background:#faf7f3;border-radius:6px;padding:20px;width:100%;">
  <tr><td><strong>Booking ref:</strong></td><td>{{ref}}</td></tr>
  <tr><td><strong>Date:</strong></td><td>{{event_date}}</td></tr>
  <tr><td><strong>Time:</strong></td><td>{{event_time}}</td></tr>
  <tr><td><strong>Venue:</strong></td><td>{{venue}}</td></tr>
  <tr><td><strong>Guests:</strong></td><td>{{guest_count}}</td></tr>
  <tr><td><strong>Balance due:</strong></td><td>{{balance_amount}} by {{balance_due}}</td></tr>
</table>
<p style="color:#4a3728;line-height:1.7;">We'll be in touch closer to the date with your run-of-show. See you soon!</p>
<p style="color:#4a3728;line-height:1.7;">Warm regards,<br>The Unwinded Team</p>
HTML;
        return $this->baseLayout('Booking Confirmed', $content);
    }

    private function depositReminder(): string
    {
        $content = <<<HTML
<h2 style="color:#2c1810;margin:0 0 16px;">Hi {{customer_name}},</h2>
<p style="color:#4a3728;line-height:1.7;">Your deposit of <strong>{{deposit_amount}}</strong> for booking <strong>{{ref}}</strong> is due by <strong>{{due_date}}</strong>.</p>
<p style="color:#4a3728;line-height:1.7;">Please pay your deposit to secure your date.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{{payment_url}}" style="display:inline-block;background:#c4862b;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:4px;font-size:16px;">Pay Deposit Now</a>
</p>
<p style="color:#4a3728;line-height:1.7;">Warm regards,<br>The Unwinded Team</p>
HTML;
        return $this->baseLayout('Deposit Due', $content);
    }

    private function balanceReminder(): string
    {
        $content = <<<HTML
<h2 style="color:#2c1810;margin:0 0 16px;">Hi {{customer_name}},</h2>
<p style="color:#4a3728;line-height:1.7;">Your balance of <strong>{{balance_amount}}</strong> for booking <strong>{{ref}}</strong> is due by <strong>{{due_date}}</strong>.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{{payment_url}}" style="display:inline-block;background:#c4862b;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:4px;font-size:16px;">Pay Balance Now</a>
</p>
<p style="color:#4a3728;line-height:1.7;">Warm regards,<br>The Unwinded Team</p>
HTML;
        return $this->baseLayout('Balance Due', $content);
    }

    private function paymentReceived(): string
    {
        $content = <<<HTML
<h2 style="color:#2c1810;margin:0 0 16px;">Payment received, {{customer_name}}!</h2>
<p style="color:#4a3728;line-height:1.7;">We've received your payment of <strong>{{amount}}</strong> for <strong>{{ref}}</strong>.</p>
<p style="color:#4a3728;line-height:1.7;"><strong>Payment type:</strong> {{payment_type}}<br>
<strong>Balance remaining:</strong> {{balance_remaining}}</p>
<p style="color:#4a3728;line-height:1.7;">Thank you! See you soon.</p>
<p style="color:#4a3728;line-height:1.7;">Warm regards,<br>The Unwinded Team</p>
HTML;
        return $this->baseLayout('Payment Received', $content);
    }

    private function ticketOrderConfirmed(): string
    {
        $content = <<<HTML
<h2 style="color:#2c1810;margin:0 0 16px;">You're going, {{customer_name}}!</h2>
<p style="color:#4a3728;line-height:1.7;">Your tickets for <strong>{{event_name}}</strong> are confirmed. Your individual QR tickets are attached to this email.</p>
<table cellpadding="0" cellspacing="0" style="margin:24px 0;background:#faf7f3;border-radius:6px;padding:20px;width:100%;">
  <tr><td><strong>Order ref:</strong></td><td>{{order_ref}}</td></tr>
  <tr><td><strong>Event:</strong></td><td>{{event_name}}</td></tr>
  <tr><td><strong>Date:</strong></td><td>{{event_date}}</td></tr>
  <tr><td><strong>Time:</strong></td><td>{{event_time}}</td></tr>
  <tr><td><strong>Venue:</strong></td><td>{{venue}}</td></tr>
  <tr><td><strong>Tickets:</strong></td><td>{{ticket_count}}</td></tr>
</table>
<p style="color:#4a3728;line-height:1.7;"><strong>Please bring your QR code(s)</strong> — one per admission — to the door for scanning.</p>
<p style="color:#4a3728;line-height:1.7;">See you there!</p>
<p style="color:#4a3728;line-height:1.7;">Warm regards,<br>The Unwinded Team</p>
HTML;
        return $this->baseLayout('Your Tickets Are Confirmed', $content);
    }

    private function galleryAccess(): string
    {
        $content = <<<HTML
<h2 style="color:#2c1810;margin:0 0 16px;">Your event photos are ready, {{customer_name}}!</h2>
<p style="color:#4a3728;line-height:1.7;">We've uploaded your photos from <strong>{{event_name}}</strong> on <strong>{{event_date}}</strong>. Use the link below to view and download them.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{{gallery_url}}" style="display:inline-block;background:#c4862b;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:4px;font-size:16px;">View My Photos</a>
</p>
<p style="color:#4a3728;line-height:1.7;font-size:13px;"><em>This link expires on {{expires_at}}. Please download your photos before then.</em></p>
<p style="color:#4a3728;line-height:1.7;">Warm regards,<br>The Unwinded Team</p>
HTML;
        return $this->baseLayout('Your Event Photos', $content);
    }

    private function newsletterConfirm(): string
    {
        $content = <<<HTML
<h2 style="color:#2c1810;margin:0 0 16px;">Hi {{name}},</h2>
<p style="color:#4a3728;line-height:1.7;">Please confirm your subscription to the Unwinded newsletter for updates on upcoming events and exclusive offers.</p>
<p style="text-align:center;margin:24px 0;">
  <a href="{{confirm_url}}" style="display:inline-block;background:#c4862b;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:4px;font-size:16px;">Confirm Subscription</a>
</p>
<p style="color:#4a3728;line-height:1.7;font-size:13px;"><em>If you didn't sign up, simply ignore this email.</em></p>
HTML;
        return $this->baseLayout('Confirm Your Subscription', $content);
    }

    private function enquiryReceived(): string
    {
        $content = <<<HTML
<h2 style="color:#2c1810;margin:0 0 16px;">Hi {{name}},</h2>
<p style="color:#4a3728;line-height:1.7;">Thank you for getting in touch! We've received your message (ref: <strong>{{ref}}</strong>) and will respond within 1–2 business days.</p>
<p style="color:#4a3728;line-height:1.7;">In the meantime, feel free to explore our website for inspiration.</p>
<p style="color:#4a3728;line-height:1.7;">Warm regards,<br>The Unwinded Team</p>
HTML;
        return $this->baseLayout('Message Received', $content);
    }

    private function refundApproved(): string
    {
        $content = <<<HTML
<h2 style="color:#2c1810;margin:0 0 16px;">Refund approved, {{customer_name}}</h2>
<p style="color:#4a3728;line-height:1.7;">Your refund of <strong>{{amount}}</strong> for booking <strong>{{ref}}</strong> has been approved and will be processed by <strong>{{due_by}}</strong>.</p>
<p style="color:#4a3728;line-height:1.7;">Please allow a few additional business days for your bank to clear the funds. We hope to welcome you back to Unwinded in the future!</p>
<p style="color:#4a3728;line-height:1.7;">Warm regards,<br>The Unwinded Team</p>
HTML;
        return $this->baseLayout('Refund Approved', $content);
    }

    private function adminNewQuoteRequest(): string
    {
        $content = <<<HTML
<h2 style="color:#2c1810;margin:0 0 16px;">New Quote Request</h2>
<p style="color:#4a3728;line-height:1.7;">A new quote request has been submitted.</p>
<table cellpadding="0" cellspacing="0" style="margin:16px 0;background:#faf7f3;border-radius:6px;padding:20px;width:100%;">
  <tr><td><strong>Reference:</strong></td><td>{{ref}}</td></tr>
  <tr><td><strong>Customer:</strong></td><td>{{customer_name}}</td></tr>
  <tr><td><strong>Event type:</strong></td><td>{{event_type}}</td></tr>
  <tr><td><strong>Preferred date:</strong></td><td>{{event_date}}</td></tr>
  <tr><td><strong>Guests:</strong></td><td>{{guest_count}}</td></tr>
</table>
<p style="text-align:center;">
  <a href="{{admin_url}}" style="display:inline-block;background:#2c1810;color:#d4a853;text-decoration:none;padding:14px 32px;border-radius:4px;">View in CMS</a>
</p>
HTML;
        return $this->baseLayout('New Quote Request', $content);
    }
}
