<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;
use Unwinded\Services\MailService;

class EnquiryController
{
    private const STATUSES = ['new', 'in_progress', 'resolved', 'spam'];

    public function __construct(
        private Database       $db,
        private Request        $request,
        private View           $view,
        private ActivityLogger $activityLogger,
        private MailService    $mail,
    ) {}

    public function index(): Response
    {
        $status = $this->request->str('status');
        if (!in_array($status, self::STATUSES, true)) {
            $status = '';
        }

        $where  = "1=1";
        $params = [];
        if ($status) {
            $where  .= " AND status = ?";
            $params[] = $status;
        }

        $enquiries = $this->db->fetchAll(
            "SELECT * FROM enquiries WHERE {$where} ORDER BY created_at DESC LIMIT 300",
            $params
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/enquiries/index', [
                'pageTitle' => 'Enquiries',
                'enquiries' => $enquiries,
                'status'    => $status,
                'statuses'  => self::STATUSES,
            ])
        );
    }

    public function show(string $id): Response
    {
        $enquiry = $this->db->fetchOne("SELECT * FROM enquiries WHERE id=?", [(int) $id]);
        if (!$enquiry) {
            flash('error', 'Enquiry not found.');
            return Response::make()->redirect(url('/admin/enquiries'));
        }

        // Mark as in_progress if still new
        if ($enquiry['status'] === 'new') {
            $this->db->execute("UPDATE enquiries SET status='in_progress', updated_at=NOW() WHERE id=?", [(int) $id]);
            $enquiry['status'] = 'in_progress';
        }

        $users = $this->db->fetchAll("SELECT id, name FROM users WHERE deleted_at IS NULL ORDER BY name");

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/enquiries/show', [
                'pageTitle' => 'Enquiry — ' . $enquiry['ref'],
                'enquiry'   => $enquiry,
                'users'     => $users,
                'statuses'  => self::STATUSES,
            ])
        );
    }

    public function status(string $id): Response
    {
        $enquiry = $this->db->fetchOne("SELECT * FROM enquiries WHERE id=?", [(int) $id]);
        if (!$enquiry) {
            flash('error', 'Enquiry not found.');
            return Response::make()->redirect(url('/admin/enquiries'));
        }

        $newStatus = $this->request->str('status');
        if (!in_array($newStatus, self::STATUSES, true)) {
            flash('error', 'Invalid status.');
            return Response::make()->redirect(url('/admin/enquiries/' . $id));
        }

        $replyBody = trim($this->request->str('reply_body') ?? '');
        $assignedTo = (int) $this->request->str('assigned_to') ?: null;

        $this->db->execute(
            "UPDATE enquiries
             SET status=?, assigned_to=?, reply_body=IF(?!='',?,reply_body),
                 replied_at=IF(?!='' AND replied_at IS NULL,NOW(),replied_at),
                 replied_by=IF(?!='',?,replied_by), updated_at=NOW()
             WHERE id=?",
            [
                $newStatus, $assignedTo,
                $replyBody, $replyBody,
                $replyBody, $replyBody, $_SESSION['user']['id'] ?? null,
                (int) $id,
            ]
        );

        // Send reply email if provided
        if ($replyBody !== '' && $newStatus !== 'spam') {
            try {
                $siteName = setting('site.name', 'Unwinded');
                $this->mail->send(
                    $enquiry['email'],
                    $enquiry['name'],
                    'Re: ' . $enquiry['subject'],
                    '<p>Dear ' . htmlspecialchars($enquiry['name']) . ',</p>'
                        . '<p>' . nl2br(htmlspecialchars($replyBody)) . '</p>'
                        . '<p>— ' . htmlspecialchars($siteName) . ' team</p>',
                    setting('contact.email', '')
                );
                flash('success', 'Status updated and reply sent.');
            } catch (\Throwable $e) {
                flash('warning', 'Status updated but email failed: ' . $e->getMessage());
            }
        } else {
            flash('success', 'Status updated.');
        }

        $this->activityLogger->log('enquiry.status_changed', 'enquiry', $id, ['to' => $newStatus]);
        return Response::make()->redirect(url('/admin/enquiries/' . $id));
    }
}
