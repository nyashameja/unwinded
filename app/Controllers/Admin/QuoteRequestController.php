<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;

class QuoteRequestController
{
    public function __construct(
        private Database       $db,
        private Request        $request,
        private View           $view,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(): Response
    {
        $status  = $this->request->str('status');
        $allowed = ['new','reviewing','info_required','quote_prepared','quote_sent',
                    'accepted','declined','expired','converted'];
        if (!in_array($status, $allowed, true)) {
            $status = '';
        }

        $where  = $status ? "WHERE status = ?" : "WHERE 1=1";
        $params = $status ? [$status] : [];

        $requests = $this->db->fetchAll(
            "SELECT qr.*, u.name AS assigned_name
               FROM quote_requests qr
               LEFT JOIN users u ON u.id = qr.assigned_to
               $where
             ORDER BY qr.created_at DESC
             LIMIT 200",
            $params
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/quote-requests/index', [
                'pageTitle' => 'Quote Requests',
                'requests'  => $requests,
                'status'    => $status,
                'statuses'  => $allowed,
            ])
        );
    }

    public function show(string $id): Response
    {
        $qr = $this->find((int) $id);
        if (!$qr) {
            flash('error', 'Quote request not found.');
            return Response::make()->redirect(url('/admin/quote-requests'));
        }

        $users  = $this->db->fetchAll("SELECT id, name FROM users WHERE deleted_at IS NULL ORDER BY name");
        $quotes = $this->db->fetchAll(
            "SELECT id, public_ref, status, total_cents, created_at FROM quotes WHERE request_id = ? ORDER BY created_at DESC",
            [(int) $id]
        );
        $history = $this->db->fetchAll(
            "SELECT * FROM quote_status_history WHERE request_id = ? ORDER BY created_at DESC",
            [(int) $id]
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/quote-requests/show', [
                'pageTitle' => 'Quote Request ' . $qr['public_ref'],
                'qr'        => $qr,
                'users'     => $users,
                'quotes'    => $quotes,
                'history'   => $history,
            ])
        );
    }

    public function update(string $id): Response
    {
        $qr = $this->find((int) $id);
        if (!$qr) {
            flash('error', 'Quote request not found.');
            return Response::make()->redirect(url('/admin/quote-requests'));
        }

        $newStatus   = $this->request->str('status');
        $assignedTo  = (int) $this->request->str('assigned_to') ?: null;
        $allowed     = ['new','reviewing','info_required','quote_prepared','quote_sent',
                        'accepted','declined','expired','converted'];

        if (!in_array($newStatus, $allowed, true)) {
            $newStatus = $qr['status'];
        }

        $this->db->execute(
            "UPDATE quote_requests SET status=?, assigned_to=?, updated_at=NOW() WHERE id=?",
            [$newStatus, $assignedTo, (int) $id]
        );

        if ($newStatus !== $qr['status']) {
            $this->db->execute(
                "INSERT INTO quote_status_history (request_id, from_status, to_status, changed_by, changed_name, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [(int) $id, $qr['status'], $newStatus, $_SESSION['user']['id'] ?? null, $_SESSION['user']['name'] ?? null]
            );
        }

        $this->activityLogger->log('quote_request.updated', 'quote_request', $id);
        flash('success', 'Quote request updated.');
        return Response::make()->redirect(url('/admin/quote-requests/' . $id));
    }

    public function addNote(string $id): Response
    {
        $qr = $this->find((int) $id);
        if (!$qr) {
            return Response::make()->status(404)->body('Not found');
        }

        $note = trim($this->request->str('note'));
        if (strlen($note) < 2) {
            flash('error', 'Note cannot be blank.');
            return Response::make()->redirect(url('/admin/quote-requests/' . $id));
        }

        $this->db->execute(
            "INSERT INTO quote_notes (quote_id, user_id, user_name, note, is_internal, created_at)
             SELECT null, ?, ?, ?, 1, NOW()
             WHERE FALSE
             UNION ALL
             SELECT null, ?, ?, ?, 1, NOW()",
            []
        );

        // notes stored against the request via a generic note on the request row
        $existing = $qr['extra_notes'] ?? '';
        $stamp    = date('[d M Y H:i] ');
        $userName = $_SESSION['user']['name'] ?? 'Admin';
        $appended = trim($existing . "\n\n" . $stamp . $userName . ': ' . $note);

        $this->db->execute(
            "UPDATE quote_requests SET extra_notes=?, updated_at=NOW() WHERE id=?",
            [$appended, (int) $id]
        );

        $this->activityLogger->log('quote_request.note_added', 'quote_request', $id);
        flash('success', 'Note added.');
        return Response::make()->redirect(url('/admin/quote-requests/' . $id));
    }

    private function find(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM quote_requests WHERE id = ?", [$id]) ?: null;
    }
}
