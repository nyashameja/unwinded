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

class ContactController
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
        return Response::make()->html(
            $this->view->renderWithLayout('public', 'public/contact', [
                'pageTitle'       => 'Contact Us',
                'metaDescription' => 'Get in touch with Unwinded — we\'d love to hear from you.',
                'bodyClass'       => 'page page--contact',
            ])
        );
    }

    public function submit(): Response
    {
        // Honeypot
        if ($this->request->str('website')) {
            return Response::make()->redirect(url('/contact'));
        }

        if ($this->rateLimiter->tooManyAttempts('contact:' . $this->request->ip(), 5, 60)) {
            flash('error', 'Too many submissions. Please wait a while before trying again.');
            return Response::make()->redirect(url('/contact'));
        }

        $name    = substr(trim($this->request->str('name') ?? ''),    0, 150);
        $email   = substr(trim($this->request->str('email') ?? ''),   0, 250);
        $phone   = substr(trim($this->request->str('phone') ?? ''),   0, 30);
        $subject = substr(trim($this->request->str('subject') ?? ''), 0, 300);
        $message = substr(trim($this->request->str('message') ?? ''), 0, 5000);

        $errors = [];
        if (strlen($name) < 2)    $errors['name']    = 'Please enter your name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
        if (strlen($subject) < 2) $errors['subject'] = 'Please enter a subject.';
        if (strlen($message) < 10) $errors['message'] = 'Please enter a message (at least 10 characters).';

        if ($errors) {
            return Response::make()->html(
                $this->view->renderWithLayout('public', 'public/contact', [
                    'pageTitle' => 'Contact Us',
                    'bodyClass' => 'page page--contact',
                    'errors'    => $errors,
                    'old'       => compact('name', 'email', 'phone', 'subject', 'message'),
                ])
            );
        }

        $ref = Ref::generate('ENQ');
        $this->db->insert('enquiries', [
            'ref'        => $ref,
            'name'       => $name,
            'email'      => $email,
            'phone'      => $phone ?: null,
            'subject'    => $subject,
            'message'    => $message,
            'source'     => 'contact_form',
            'ip_address' => $this->request->ip(),
            'user_agent' => substr($this->request->header('User-Agent') ?? '', 0, 500),
        ]);

        $adminEmail = config('contact.email', '');
        if ($adminEmail) {
            try {
                $this->mail->send(
                    $adminEmail,
                    config('app.name', 'Unwinded'),
                    "New enquiry from {$name}: {$subject}",
                    "<p><strong>From:</strong> " . e($name) . " &lt;" . e($email) . "&gt;<br>" .
                    ($phone ? "<strong>Phone:</strong> " . e($phone) . "<br>" : '') .
                    "<strong>Subject:</strong> " . e($subject) . "</p>" .
                    "<p>" . nl2br(e($message)) . "</p>",
                    $email
                );
            } catch (\Throwable) {
                // Non-fatal — enquiry is saved to DB
            }
        }

        flash('success', 'Thank you for your message! We\'ll be in touch shortly.');
        return Response::make()->redirect(url('/contact'));
    }
}
