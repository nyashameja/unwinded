<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;

class ReportController
{
    public function __construct(
        private Database $db,
        private Request  $request,
        private View     $view,
    ) {}

    public function index(): Response
    {
        $summary = [
            'revenue_30d'     => (int) ($this->db->fetchScalar(
                "SELECT COALESCE(SUM(amount_cents),0) FROM payments WHERE status='successful' AND created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)"
            ) ?? 0),
            'revenue_ytd'     => (int) ($this->db->fetchScalar(
                "SELECT COALESCE(SUM(amount_cents),0) FROM payments WHERE status='successful' AND YEAR(created_at)=YEAR(NOW())"
            ) ?? 0),
            'bookings_active' => (int) ($this->db->fetchScalar(
                "SELECT COUNT(*) FROM private_bookings WHERE booking_status NOT IN ('cancelled','refunded') AND event_date_utc >= NOW()"
            ) ?? 0),
            'tickets_30d'     => (int) ($this->db->fetchScalar(
                "SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi
                  JOIN ticket_orders o ON o.id=oi.order_id
                 WHERE o.status='paid' AND o.created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)"
            ) ?? 0),
            'new_customers_30d' => (int) ($this->db->fetchScalar(
                "SELECT COUNT(*) FROM customers WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)"
            ) ?? 0),
            'open_quotes'     => (int) ($this->db->fetchScalar(
                "SELECT COUNT(*) FROM quotes WHERE status='sent'"
            ) ?? 0),
        ];

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/reports/index', [
                'pageTitle' => 'Reports',
                'summary'   => $summary,
            ])
        );
    }

    public function revenue(): Response
    {
        [$from, $to] = $this->dateRange();

        $byMonth = $this->db->fetchAll(
            "SELECT DATE_FORMAT(created_at,'%Y-%m') AS month,
                    gateway,
                    COUNT(*) AS txn_count,
                    SUM(amount_cents) AS total_cents
               FROM payments
              WHERE status = 'successful'
                AND created_at BETWEEN ? AND ?
             GROUP BY month, gateway
             ORDER BY month DESC",
            [$from, $to]
        );

        $byType = $this->db->fetchAll(
            "SELECT pa.payable_type,
                    pa.allocation_type,
                    COUNT(*) AS txn_count,
                    SUM(pa.amount_cents) AS total_cents
               FROM payment_allocations pa
               JOIN payments p ON p.id = pa.payment_id
              WHERE p.status = 'successful'
                AND p.created_at BETWEEN ? AND ?
             GROUP BY pa.payable_type, pa.allocation_type
             ORDER BY pa.payable_type, total_cents DESC",
            [$from, $to]
        );

        $totals = [
            'total'   => (int) ($this->db->fetchScalar(
                "SELECT COALESCE(SUM(amount_cents),0) FROM payments WHERE status='successful' AND created_at BETWEEN ? AND ?",
                [$from, $to]
            ) ?? 0),
            'payfast' => (int) ($this->db->fetchScalar(
                "SELECT COALESCE(SUM(amount_cents),0) FROM payments WHERE status='successful' AND gateway='payfast' AND created_at BETWEEN ? AND ?",
                [$from, $to]
            ) ?? 0),
            'eft'     => (int) ($this->db->fetchScalar(
                "SELECT COALESCE(SUM(amount_cents),0) FROM payments WHERE status='successful' AND gateway='eft' AND created_at BETWEEN ? AND ?",
                [$from, $to]
            ) ?? 0),
        ];

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/reports/revenue', [
                'pageTitle' => 'Revenue Report',
                'byMonth'   => $byMonth,
                'byType'    => $byType,
                'totals'    => $totals,
                'from'      => $from,
                'to'        => $to,
            ])
        );
    }

    public function bookings(): Response
    {
        [$from, $to] = $this->dateRange();

        $byStatus = $this->db->fetchAll(
            "SELECT booking_status, COUNT(*) AS cnt, COALESCE(SUM(total_cents),0) AS total_cents
               FROM private_bookings
              WHERE created_at BETWEEN ? AND ?
             GROUP BY booking_status
             ORDER BY cnt DESC",
            [$from, $to]
        );

        $recent = $this->db->fetchAll(
            "SELECT pb.id, pb.public_ref, pb.event_date, pb.booking_status, pb.total_cents,
                    pb.outstanding_cents, c.name AS customer_name
               FROM private_bookings pb
               JOIN customers c ON c.id = pb.customer_id
              WHERE pb.created_at BETWEEN ? AND ?
             ORDER BY pb.created_at DESC
             LIMIT 100",
            [$from, $to]
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/reports/bookings', [
                'pageTitle' => 'Bookings Report',
                'byStatus'  => $byStatus,
                'recent'    => $recent,
                'from'      => $from,
                'to'        => $to,
            ])
        );
    }

    public function tickets(): Response
    {
        [$from, $to] = $this->dateRange();

        $byEvent = $this->db->fetchAll(
            "SELECT pe.title, pe.event_date, pe.public_ref,
                    COUNT(DISTINCT o.id)     AS order_count,
                    COALESCE(SUM(oi.quantity),0) AS tickets_sold,
                    COALESCE(SUM(o.total_cents),0) AS revenue_cents
               FROM ticket_orders o
               JOIN order_items oi ON oi.order_id = o.id
               JOIN public_events pe ON pe.id = o.event_id
              WHERE o.status = 'paid'
                AND o.created_at BETWEEN ? AND ?
             GROUP BY pe.id
             ORDER BY pe.event_date DESC",
            [$from, $to]
        );

        $totals = [
            'tickets' => (int) ($this->db->fetchScalar(
                "SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi
                  JOIN ticket_orders o ON o.id=oi.order_id
                 WHERE o.status='paid' AND o.created_at BETWEEN ? AND ?",
                [$from, $to]
            ) ?? 0),
            'revenue' => (int) ($this->db->fetchScalar(
                "SELECT COALESCE(SUM(total_cents),0) FROM ticket_orders WHERE status='paid' AND created_at BETWEEN ? AND ?",
                [$from, $to]
            ) ?? 0),
            'orders'  => (int) ($this->db->fetchScalar(
                "SELECT COUNT(*) FROM ticket_orders WHERE status='paid' AND created_at BETWEEN ? AND ?",
                [$from, $to]
            ) ?? 0),
        ];

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/reports/tickets', [
                'pageTitle' => 'Ticket Sales Report',
                'byEvent'   => $byEvent,
                'totals'    => $totals,
                'from'      => $from,
                'to'        => $to,
            ])
        );
    }

    public function customers(): Response
    {
        [$from, $to] = $this->dateRange();

        $newCustomers = (int) ($this->db->fetchScalar(
            "SELECT COUNT(*) FROM customers WHERE created_at BETWEEN ? AND ?",
            [$from, $to]
        ) ?? 0);

        $topCustomers = $this->db->fetchAll(
            "SELECT c.name, c.email,
                    COUNT(DISTINCT pb.id) AS bookings,
                    COUNT(DISTINCT o.id)  AS ticket_orders,
                    COALESCE(SUM(p.amount_cents),0) AS lifetime_spend
               FROM customers c
               LEFT JOIN private_bookings pb ON pb.customer_id = c.id AND pb.booking_status NOT IN ('cancelled','refunded')
               LEFT JOIN ticket_orders o ON o.customer_id = c.id AND o.status = 'paid'
               LEFT JOIN payments p ON p.customer_id = c.id AND p.status = 'successful'
             GROUP BY c.id
             HAVING lifetime_spend > 0
             ORDER BY lifetime_spend DESC
             LIMIT 50"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/reports/customers', [
                'pageTitle'    => 'Customer Report',
                'newCustomers' => $newCustomers,
                'topCustomers' => $topCustomers,
                'from'         => $from,
                'to'           => $to,
            ])
        );
    }

    public function quotes(): Response
    {
        [$from, $to] = $this->dateRange();

        $byStatus = $this->db->fetchAll(
            "SELECT status, COUNT(*) AS cnt, COALESCE(SUM(total_cents),0) AS total_cents
               FROM quotes
              WHERE created_at BETWEEN ? AND ?
             GROUP BY status
             ORDER BY cnt DESC",
            [$from, $to]
        );

        $conversionRate = 0.0;
        $sent     = array_sum(array_column(array_filter($byStatus, fn($r) => in_array($r['status'], ['sent','accepted','declined','expired'], true)), 'cnt'));
        $accepted = array_sum(array_column(array_filter($byStatus, fn($r) => $r['status'] === 'accepted'), 'cnt'));
        if ($sent > 0) {
            $conversionRate = round(($accepted / $sent) * 100, 1);
        }

        $recent = $this->db->fetchAll(
            "SELECT q.public_ref, q.status, q.total_cents, q.expires_at, q.created_at,
                    c.name AS customer_name
               FROM quotes q
               JOIN customers c ON c.id = q.customer_id
              WHERE q.created_at BETWEEN ? AND ?
             ORDER BY q.created_at DESC
             LIMIT 100",
            [$from, $to]
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/reports/quotes', [
                'pageTitle'       => 'Quotes Report',
                'byStatus'        => $byStatus,
                'conversionRate'  => $conversionRate,
                'recent'          => $recent,
                'from'            => $from,
                'to'              => $to,
            ])
        );
    }

    public function discounts(): Response
    {
        [$from, $to] = $this->dateRange();

        $usage = $this->db->fetchAll(
            "SELECT dc.code, dc.discount_type, dc.discount_value, dc.is_active,
                    COUNT(du.id) AS uses,
                    COALESCE(SUM(du.discount_cents),0) AS saved_cents
               FROM discount_codes dc
               LEFT JOIN discount_code_usage du ON du.discount_id = dc.id
                   AND du.created_at BETWEEN ? AND ?
             GROUP BY dc.id
             ORDER BY uses DESC",
            [$from, $to]
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/reports/discounts', [
                'pageTitle' => 'Discount Code Report',
                'usage'     => $usage,
                'from'      => $from,
                'to'        => $to,
            ])
        );
    }

    public function gallery(): Response
    {
        $albums = $this->db->fetchAll(
            "SELECT ga.id, ga.title, ga.segment, ga.status, ga.is_featured, ga.created_at,
                    COUNT(gi.id) AS image_count,
                    COUNT(gat.id) AS token_count
               FROM gallery_albums ga
               LEFT JOIN gallery_images gi ON gi.album_id = ga.id
               LEFT JOIN gallery_access_tokens gat ON gat.album_id = ga.id AND (gat.expires_at IS NULL OR gat.expires_at > NOW())
             WHERE ga.deleted_at IS NULL
             GROUP BY ga.id
             ORDER BY ga.created_at DESC"
        );

        $totals = [
            'albums'   => count($albums),
            'images'   => (int) ($this->db->fetchScalar("SELECT COUNT(*) FROM gallery_images") ?? 0),
            'public'   => (int) ($this->db->fetchScalar("SELECT COUNT(*) FROM gallery_albums WHERE status='published'") ?? 0),
            'private'  => (int) ($this->db->fetchScalar("SELECT COUNT(*) FROM gallery_albums WHERE status='private'") ?? 0),
        ];

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/reports/gallery', [
                'pageTitle' => 'Gallery Report',
                'albums'    => $albums,
                'totals'    => $totals,
            ])
        );
    }

    private function dateRange(): array
    {
        $from = $this->request->str('from');
        $to   = $this->request->str('to');

        if (!$from || !strtotime($from)) {
            $from = date('Y-m-01');
        }
        if (!$to || !strtotime($to)) {
            $to = date('Y-m-t');
        }

        // Normalise to full-day boundaries
        $from = date('Y-m-d', strtotime($from)) . ' 00:00:00';
        $to   = date('Y-m-d', strtotime($to))   . ' 23:59:59';

        return [$from, $to];
    }
}
