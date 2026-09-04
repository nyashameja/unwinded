<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Public;

use Unwinded\Core\Database;
use Unwinded\Core\RateLimiter;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\MailService;
use Unwinded\Support\Ref;

class QuoteRequestController
{
    public function __construct(
        private Database    $db,
        private Request     $request,
        private View        $view,
        private RateLimiter $rateLimiter,
        private MailService $mail,
    ) {}

    public function show(): Response
    {
        $packages = $this->db->fetchAll(
            "SELECT id, name FROM packages WHERE status = 'published' AND deleted_at IS NULL ORDER BY sort_order, name"
        );
        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/quote-request/show', [
                'pageTitle'       => 'Request a Quote',
                'metaDescription' => 'Tell us about your event and we\'ll put together a personalised quote.',
                'packages'        => $packages,
                'bodyClass'       => 'page page--quote-request',
            ])
        );
    }

    public function submit(): Response
    {
        // Honeypot
        if ($this->request->str('website')) {
            return Response::make()->redirect(url('/request-a-quote/thank-you'));
        }

        if ($this->rateLimiter->tooManyAttempts('quote_req:' . $this->request->ip(), 3, 60)) {
            flash('error', 'Too many requests. Please try again later.');
            return Response::make()->redirect(url('/request-a-quote'));
        }

        $name  = substr(trim($this->request->str('customer_name') ?? ''),  0, 200);
        $email = substr(trim($this->request->str('customer_email') ?? ''), 0, 254);
        $phone = substr(trim($this->request->str('customer_phone') ?? ''), 0, 30);

        $errors = [];
        if (strlen($name) < 2)  $errors['customer_name']  = 'Please enter your name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))    $errors['customer_email'] = 'Please enter a valid email address.';
        if (!$this->request->str('terms_consent'))         $errors['terms_consent']  = 'You must agree to the terms.';

        if ($errors) {
            $packages = $this->db->fetchAll(
                "SELECT id, name FROM packages WHERE status = 'published' AND deleted_at IS NULL ORDER BY sort_order, name"
            );
            return Response::make()->html(
                $this->view->renderWithLayout('public', 'public/quote-request/show', [
                    'pageTitle'  => 'Request a Quote',
                    'packages'   => $packages,
                    'bodyClass'  => 'page page--quote-request',
                    'errors'     => $errors,
                    'old'        => $this->request->all(),
                ])
            );
        }

        $ref = Ref::generate('QRQ');

        $preferredDate  = $this->request->str('preferred_date');
        $alternativeDate = $this->request->str('alternative_date');
        $packageId      = $this->request->int('package_id');
        $guestCount     = $this->request->int('guest_count');
        $budgetCents    = null;
        if ($b = $this->request->str('budget_estimate')) {
            $budgetCents = (int) (floatval(str_replace(['R', ' ', ','], '', $b)) * 100);
        }

        $honeypotFlagged = 0;

        $this->db->insert('quote_requests', [
            'public_ref'          => $ref,
            'customer_name'       => $name,
            'customer_email'      => $email,
            'customer_phone'      => $phone ?: null,
            'customer_company'    => substr(trim($this->request->str('customer_company') ?? ''), 0, 200) ?: null,
            'customer_whatsapp'   => substr(trim($this->request->str('customer_whatsapp') ?? ''), 0, 30) ?: null,
            'preferred_contact'   => in_array($this->request->str('preferred_contact'), ['email','phone','whatsapp'], true)
                                         ? $this->request->str('preferred_contact') : 'email',
            'event_type'          => substr($this->request->str('event_type') ?? '', 0, 100) ?: null,
            'preferred_date'      => $preferredDate ?: null,
            'alternative_date'    => $alternativeDate ?: null,
            'start_time'          => $this->request->str('start_time') ?: null,
            'expected_duration'   => $this->request->int('expected_duration'),
            'guest_count'         => $guestCount,
            'age_group'           => substr($this->request->str('age_group') ?? '', 0, 100) ?: null,
            'location_pref'       => in_array($this->request->str('location_pref'), ['indoor','outdoor','either'], true)
                                         ? $this->request->str('location_pref') : null,
            'has_venue'           => $this->request->str('has_venue') !== null ? (int) (bool) $this->request->str('has_venue') : null,
            'venue_name'          => substr($this->request->str('venue_name') ?? '', 0, 300) ?: null,
            'venue_city'          => substr($this->request->str('venue_city') ?? '', 0, 100) ?: null,
            'package_id'          => $packageId,
            'preferred_artwork'   => $this->request->str('preferred_artwork') ?: null,
            'event_theme'         => $this->request->str('event_theme') ?: null,
            'food_drink_req'      => $this->request->str('food_drink_req') ?: null,
            'budget_estimate_cents' => $budgetCents,
            'extra_notes'         => $this->request->str('extra_notes') ?: null,
            'contact_consent'     => $this->request->str('contact_consent') ? 1 : 0,
            'terms_consent'       => 1,
            'honeypot_flagged'    => $honeypotFlagged,
            'source_ip'           => $this->request->ip(),
        ]);

        $adminEmail = config('contact.email', '');
        if ($adminEmail) {
            try {
                $this->mail->send(
                    $adminEmail,
                    config('app.name', 'Unwinded'),
                    "New quote request from {$name} [{$ref}]",
                    "<p><strong>Ref:</strong> {$ref}<br>" .
                    "<strong>Name:</strong> " . e($name) . "<br>" .
                    "<strong>Email:</strong> " . e($email) . "<br>" .
                    ($phone ? "<strong>Phone:</strong> " . e($phone) . "<br>" : '') .
                    ($guestCount ? "<strong>Guests:</strong> " . e((string)$guestCount) . "<br>" : '') .
                    ($preferredDate ? "<strong>Preferred date:</strong> " . e($preferredDate) . "<br>" : '') .
                    "</p>"
                );
            } catch (\Throwable) {
                // Non-fatal
            }
        }

        return Response::make()->redirect(url('/request-a-quote/thank-you'));
    }

    public function thankYou(): Response
    {
        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/quote-request/thank-you', [
                'pageTitle'  => 'Thank You — Request Received',
                'metaRobots' => 'noindex,nofollow',
                'bodyClass'  => 'page page--thank-you',
            ])
        );
    }
}
