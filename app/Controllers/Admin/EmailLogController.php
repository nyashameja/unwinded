<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\MailService;

class EmailLogController
{
    public function __construct(
        private Database    $db,
        private Request     $request,
        private View        $view,
        private MailService $mail,
    ) {}

    public function index(): Response
    {
        $status = $this->request->str('status') ?? '';
        $statuses = ['sent', 'failed', 'bounced', 'complained'];
        if (!in_array($status, $statuses, true)) {
            $status = '';
        }

        $where  = $status ? "WHERE el.status = ?" : "";
        $params = $status ? [$status] : [];

        $logs = $this->db->fetchAll(
            "SELECT el.*
               FROM email_logs el
               $where
             ORDER BY el.created_at DESC
             LIMIT 300",
            $params
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/email-logs/index', [
                'pageTitle' => 'Email Logs',
                'logs'      => $logs,
                'statuses'  => $statuses,
                'status'    => $status,
            ])
        );
    }

    public function retry(string $id): Response
    {
        $log = $this->db->fetchOne(
            "SELECT el.*, eq.body_html, eq.subject AS queue_subject, eq.to_address AS queue_to
               FROM email_logs el
               LEFT JOIN email_queue eq ON eq.id = el.queue_id
              WHERE el.id = ?",
            [(int) $id]
        );

        if (!$log || $log['status'] !== 'failed') {
            flash('error', 'Log entry not found or not retryable.');
            return Response::make()->redirect(url('/admin/email-logs'));
        }

        if (!$log['body_html'] || !$log['queue_to']) {
            flash('error', 'Original email data not available for retry.');
            return Response::make()->redirect(url('/admin/email-logs'));
        }

        try {
            $this->mail->send(
                $log['queue_to'],
                '',
                $log['queue_subject'] ?? $log['subject'],
                $log['body_html']
            );

            $this->db->execute(
                "UPDATE email_logs SET status='sent', sent_at=NOW(), error_message=NULL WHERE id=?",
                [(int) $id]
            );

            flash('success', 'Email resent successfully.');
        } catch (\Throwable $e) {
            flash('error', 'Retry failed: ' . $e->getMessage());
        }

        return Response::make()->redirect(url('/admin/email-logs'));
    }
}
