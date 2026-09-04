<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;

class CustomerController
{
    public function __construct(
        private Database       $db,
        private Request        $request,
        private View           $view,
        private ActivityLogger $activityLogger,
    ) {}

    public function index(): Response
    {
        $q       = trim($this->request->str('q'));
        $where   = "WHERE c.deleted_at IS NULL";
        $params  = [];

        if ($q !== '') {
            $like    = '%' . $q . '%';
            $where  .= " AND (c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.company LIKE ?)";
            $params  = [$like, $like, $like, $like];
        }

        $customers = $this->db->fetchAll(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM private_bookings pb WHERE pb.customer_id=c.id AND pb.deleted_at IS NULL) AS booking_count,
                    (SELECT COUNT(*) FROM quotes q WHERE q.customer_id=c.id) AS quote_count
               FROM customers c
               $where
             ORDER BY c.created_at DESC
             LIMIT 200",
            $params
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/customers/index', [
                'pageTitle' => 'Customers',
                'customers' => $customers,
                'q'         => $q,
            ])
        );
    }

    public function show(string $id): Response
    {
        $customer = $this->find((int) $id);
        if (!$customer) {
            flash('error', 'Customer not found.');
            return Response::make()->redirect(url('/admin/customers'));
        }

        $bookings = $this->db->fetchAll(
            "SELECT * FROM private_bookings WHERE customer_id=? AND deleted_at IS NULL ORDER BY event_date DESC",
            [(int) $id]
        );
        $quotes = $this->db->fetchAll(
            "SELECT * FROM quotes WHERE customer_id=? ORDER BY created_at DESC",
            [(int) $id]
        );
        $payments = $this->db->fetchAll(
            "SELECT * FROM payments WHERE customer_id=? ORDER BY created_at DESC LIMIT 50",
            [(int) $id]
        );
        $requests = $this->db->fetchAll(
            "SELECT * FROM quote_requests WHERE customer_id=? ORDER BY created_at DESC",
            [(int) $id]
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/customers/show', [
                'pageTitle' => 'Customer: ' . $customer['name'],
                'customer'  => $customer,
                'bookings'  => $bookings,
                'quotes'    => $quotes,
                'payments'  => $payments,
                'requests'  => $requests,
            ])
        );
    }

    public function update(string $id): Response
    {
        $customer = $this->find((int) $id);
        if (!$customer) {
            flash('error', 'Customer not found.');
            return Response::make()->redirect(url('/admin/customers'));
        }

        $name    = trim($this->request->str('name'));
        $email   = strtolower(trim($this->request->str('email')));
        $errors  = [];

        if (strlen($name) < 2) {
            $errors['name'] = 'Name must be at least 2 characters.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email address is required.';
        }

        if ($errors) {
            return Response::make()->html(
                $this->view->renderWithLayout('admin', 'admin/customers/show', [
                    'pageTitle' => 'Customer: ' . $customer['name'],
                    'customer'  => array_merge($customer, $this->request->all()),
                    'bookings'  => [],
                    'quotes'    => [],
                    'payments'  => [],
                    'requests'  => [],
                    'errors'    => $errors,
                ])
            );
        }

        $preferred = $this->request->str('preferred_contact');
        if (!in_array($preferred, ['email','phone','whatsapp'], true)) {
            $preferred = 'email';
        }

        $this->db->execute(
            "UPDATE customers SET
                name=?,email=?,phone=?,whatsapp=?,company=?,
                preferred_contact=?,notes=?,updated_at=NOW()
             WHERE id=?",
            [
                substr($name, 0, 200),
                substr($email, 0, 254),
                substr(trim($this->request->str('phone')), 0, 30) ?: null,
                substr(trim($this->request->str('whatsapp')), 0, 30) ?: null,
                substr(trim($this->request->str('company')), 0, 200) ?: null,
                $preferred,
                trim($this->request->str('notes')),
                (int) $id,
            ]
        );

        $this->activityLogger->log('customer.updated', 'customer', $id);
        flash('success', 'Customer updated.');
        return Response::make()->redirect(url('/admin/customers/' . $id));
    }

    public function merge(string $id): Response
    {
        $customer = $this->find((int) $id);
        if (!$customer) {
            flash('error', 'Customer not found.');
            return Response::make()->redirect(url('/admin/customers'));
        }

        $targetId = (int) $this->request->str('target_customer_id');
        $target   = $this->find($targetId);
        if (!$target || $targetId === (int) $id) {
            flash('error', 'Invalid target customer.');
            return Response::make()->redirect(url('/admin/customers/' . $id));
        }

        $this->db->beginTransaction();
        try {
            foreach (['private_bookings','quotes','quote_requests','payments'] as $table) {
                $this->db->execute(
                    "UPDATE $table SET customer_id=? WHERE customer_id=?",
                    [$targetId, (int) $id]
                );
            }
            $this->db->execute(
                "UPDATE customers SET deleted_at=NOW() WHERE id=?",
                [(int) $id]
            );
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->activityLogger->log('customer.merged', 'customer', $id, ['into' => $targetId]);
        flash('success', 'Customer merged into ' . $target['name'] . '.');
        return Response::make()->redirect(url('/admin/customers/' . $targetId));
    }

    private function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM customers WHERE id=? AND deleted_at IS NULL",
            [$id]
        ) ?: null;
    }
}
