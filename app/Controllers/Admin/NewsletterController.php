<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;

class NewsletterController
{
    public function __construct(
        private Database       $db,
        private Request        $request,
        private View           $view,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(): Response
    {
        $status = $this->request->str('status');
        $allowed = ['pending', 'confirmed', 'unsubscribed', 'bounced', 'complained'];
        if (!in_array($status, $allowed, true)) {
            $status = '';
        }

        $where  = "1=1";
        $params = [];
        if ($status) {
            $where  .= " AND status = ?";
            $params[] = $status;
        }

        $subscribers = $this->db->fetchAll(
            "SELECT * FROM newsletter_subscribers WHERE {$where} ORDER BY created_at DESC LIMIT 500",
            $params
        );

        $stats = $this->db->fetchOne(
            "SELECT
                COUNT(*) AS total,
                SUM(status='confirmed') AS confirmed,
                SUM(status='pending') AS pending,
                SUM(status='unsubscribed') AS unsubscribed
             FROM newsletter_subscribers"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/newsletter/index', [
                'pageTitle'   => 'Newsletter Subscribers',
                'subscribers' => $subscribers,
                'stats'       => $stats,
                'status'      => $status,
                'statuses'    => $allowed,
            ])
        );
    }

    public function export(): Response
    {
        $status = $this->request->str('export_status');
        $allowed = ['confirmed', 'pending', 'unsubscribed', 'bounced', 'complained', ''];
        if (!in_array($status, $allowed, true)) {
            $status = 'confirmed';
        }

        $where  = $status ? "WHERE status = ?" : "WHERE status = 'confirmed'";
        $params = $status ? [$status] : [];

        $rows = $this->db->fetchAll(
            "SELECT email, name, status, confirmed_at, created_at FROM newsletter_subscribers {$where} ORDER BY email",
            $params
        );

        $csv = "email,name,status,confirmed_at,created_at\n";
        foreach ($rows as $r) {
            $csv .= implode(',', array_map(
                fn($v) => '"' . str_replace('"', '""', (string) ($v ?? '')) . '"',
                [$r['email'], $r['name'], $r['status'], $r['confirmed_at'], $r['created_at']]
            )) . "\n";
        }

        $this->activityLogger->log('newsletter.exported', 'newsletter', null, ['status' => $status, 'count' => count($rows)]);

        return Response::make()
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="subscribers-' . date('Y-m-d') . '.csv"')
            ->body($csv);
    }
}
