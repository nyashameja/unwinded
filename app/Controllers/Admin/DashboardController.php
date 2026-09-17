<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Support\Money;

class DashboardController
{
    public function __construct(
        private Database $db,
        private View     $view,
    ) {}

    public function index(): Response
    {
        $stats = [
            'new_quote_requests' => $this->db->fetchScalar(
                "SELECT COUNT(*) FROM quote_requests WHERE status = 'new'"
            ) ?? 0,

            'pending_quotes' => $this->db->fetchScalar(
                "SELECT COUNT(*) FROM quotes WHERE status = 'sent'"
            ) ?? 0,

            'upcoming_bookings' => $this->db->fetchScalar(
                "SELECT COUNT(*) FROM private_bookings
                 WHERE event_date_utc >= NOW() AND booking_status NOT IN ('cancelled','refunded')"
            ) ?? 0,

            'deposits_overdue' => $this->db->fetchScalar(
                "SELECT COUNT(*) FROM private_bookings
                 WHERE deposit_hard_deadline < NOW()
                   AND payment_status = 'unpaid'
                   AND booking_status NOT IN ('cancelled','refunded')"
            ) ?? 0,

            'upcoming_events' => $this->db->fetchScalar(
                "SELECT COUNT(*) FROM public_events
                 WHERE event_date_utc >= NOW() AND status = 'published'"
            ) ?? 0,

            'tickets_sold_30d' => $this->db->fetchScalar(
                "SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi
                 JOIN ticket_orders o ON o.id = oi.order_id
                 WHERE o.status = 'paid' AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            ) ?? 0,

            'revenue_30d' => $this->formatMoney(
                (int) ($this->db->fetchScalar(
                    "SELECT COALESCE(SUM(amount_cents), 0) FROM payments
                     WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
                ) ?? 0)
            ),

            'new_enquiries' => $this->db->fetchScalar(
                "SELECT COUNT(*) FROM enquiries WHERE status = 'new'"
            ) ?? 0,

            'quotes_expiring_soon' => $this->db->fetchScalar(
                "SELECT COUNT(*) FROM quotes
                 WHERE status = 'sent'
                   AND valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)"
            ) ?? 0,
        ];

        $recentActivity = $this->db->fetchAll(
            "SELECT al.*, u.name AS user_name FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC
             LIMIT 20"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/dashboard', [
                'pageTitle'      => 'Dashboard',
                'stats'          => $stats,
                'recentActivity' => $recentActivity,
            ])
        );
    }

    private function formatMoney(int $cents): string
    {
        return Money::ofCents($cents)->format();
    }
}
