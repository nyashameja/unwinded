<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\MailService;

class EmailTemplateController
{
    public function __construct(
        private Database    $db,
        private Request     $request,
        private View        $view,
        private MailService $mail,
    ) {}

    public function index(): Response
    {
        $templates = $this->db->fetchAll(
            "SELECT * FROM email_templates ORDER BY name"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/email-templates/index', [
                'pageTitle' => 'Email Templates',
                'templates' => $templates,
            ])
        );
    }

    public function edit(string $id): Response
    {
        $template = $this->db->fetchOne(
            "SELECT * FROM email_templates WHERE id = ?",
            [(int) $id]
        );

        if (!$template) {
            flash('error', 'Template not found.');
            return Response::make()->redirect(url('/admin/email-templates'));
        }

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/email-templates/edit', [
                'pageTitle' => 'Edit: ' . $template['name'],
                'template'  => $template,
            ])
        );
    }

    public function update(string $id): Response
    {
        $template = $this->db->fetchOne(
            "SELECT * FROM email_templates WHERE id = ?",
            [(int) $id]
        );

        if (!$template) {
            flash('error', 'Template not found.');
            return Response::make()->redirect(url('/admin/email-templates'));
        }

        $subject   = trim($this->request->str('subject') ?? '');
        $bodyHtml  = trim($this->request->str('body_html') ?? '');
        $bodyPlain = trim($this->request->str('body_plain') ?? '');
        $isActive  = (bool) $this->request->str('is_active');

        if (!$subject || !$bodyHtml) {
            flash('error', 'Subject and HTML body are required.');
            return Response::make()->redirect(url('/admin/email-templates/' . (int) $id . '/edit'));
        }

        $this->db->execute(
            "UPDATE email_templates SET subject=?, body_html=?, body_plain=?, is_active=?, updated_at=NOW() WHERE id=?",
            [$subject, $bodyHtml, $bodyPlain ?: null, $isActive ? 1 : 0, (int) $id]
        );

        flash('success', 'Template updated.');
        return Response::make()->redirect(url('/admin/email-templates/' . (int) $id . '/edit'));
    }

    public function test(string $id): Response
    {
        $template = $this->db->fetchOne(
            "SELECT * FROM email_templates WHERE id = ?",
            [(int) $id]
        );

        if (!$template) {
            flash('error', 'Template not found.');
            return Response::make()->redirect(url('/admin/email-templates'));
        }

        $toEmail = trim($this->request->str('test_email') ?? '');
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please enter a valid email address for the test send.');
            return Response::make()->redirect(url('/admin/email-templates/' . (int) $id . '/edit'));
        }

        try {
            $this->mail->send(
                $toEmail,
                '',
                '[TEST] ' . $template['subject'],
                $template['body_html']
            );
            flash('success', 'Test email sent to ' . $toEmail);
        } catch (\Throwable $e) {
            flash('error', 'Send failed: ' . $e->getMessage());
        }

        return Response::make()->redirect(url('/admin/email-templates/' . (int) $id . '/edit'));
    }
}
