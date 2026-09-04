<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;
use Unwinded\Services\ActivityLogger;
use Unwinded\Services\MailService;
use Unwinded\Support\Ref;
use Unwinded\Support\Token;

class QuotationController
{
    public function __construct(
        private Database       $db,
        private Request        $request,
        private View           $view,
        private ActivityLogger $activityLogger,
        private MailService    $mail,
    ) {}

    public function index(): Response
    {
        $status  = $this->request->str('status');
        $allowed = ['draft','sent','accepted','declined','expired','superseded'];
        if (!in_array($status, $allowed, true)) {
            $status = '';
        }

        $where  = $status ? "WHERE q.status = ?" : "WHERE 1=1";
        $params = $status ? [$status] : [];

        $quotes = $this->db->fetchAll(
            "SELECT q.*, c.name AS customer_name, c.email AS customer_email
               FROM quotes q
               JOIN customers c ON c.id = q.customer_id
               $where
             ORDER BY q.created_at DESC
             LIMIT 200",
            $params
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/quotations/index', [
                'pageTitle' => 'Quotations',
                'quotes'    => $quotes,
                'status'    => $status,
                'statuses'  => $allowed,
            ])
        );
    }

    public function create(): Response
    {
        $requestId = (int) $this->request->str('request_id');
        $qr        = $requestId ? $this->db->fetchOne("SELECT * FROM quote_requests WHERE id=?", [$requestId]) : null;
        $customers = $this->db->fetchAll("SELECT id, name, email FROM customers WHERE deleted_at IS NULL ORDER BY name LIMIT 500");
        $packages  = $this->db->fetchAll("SELECT id, name FROM packages WHERE is_published=1 ORDER BY sort_order");
        $users     = $this->db->fetchAll("SELECT id, name FROM users WHERE deleted_at IS NULL ORDER BY name");

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/quotations/create', [
                'pageTitle' => 'New Quotation',
                'qr'        => $qr,
                'customers' => $customers,
                'packages'  => $packages,
                'users'     => $users,
                'old'       => [],
                'errors'    => [],
            ])
        );
    }

    public function store(): Response
    {
        [$data, $errors] = $this->validate();
        if ($errors) {
            $customers = $this->db->fetchAll("SELECT id, name, email FROM customers WHERE deleted_at IS NULL ORDER BY name LIMIT 500");
            $packages  = $this->db->fetchAll("SELECT id, name FROM packages WHERE is_published=1 ORDER BY sort_order");
            $users     = $this->db->fetchAll("SELECT id, name FROM users WHERE deleted_at IS NULL ORDER BY name");
            return Response::make()->html(
                $this->view->renderWithLayout('admin', 'admin/quotations/create', [
                    'pageTitle' => 'New Quotation',
                    'qr'        => null,
                    'customers' => $customers,
                    'packages'  => $packages,
                    'users'     => $users,
                    'old'       => $this->request->all(),
                    'errors'    => $errors,
                ])
            );
        }

        [$totals, $items] = $this->buildTotals($data);

        $this->db->beginTransaction();
        try {
            $ref = Ref::generate('QUO');
            $this->db->execute(
                "INSERT INTO quotes
                    (public_ref,version,request_id,customer_id,created_by,assigned_to,
                     event_type,event_date,guest_count,venue_name,venue_address,
                     notes_to_customer,internal_notes,
                     subtotal_cents,discount_type,discount_value,discount_amount_cents,
                     total_cents,deposit_type,deposit_value,deposit_cents,valid_until,
                     status,created_at,updated_at)
                 VALUES (?,1,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())",
                [
                    $ref,
                    $data['request_id'] ?: null,
                    $data['customer_id'],
                    $_SESSION['user']['id'] ?? null,
                    $data['assigned_to'] ?: null,
                    $data['event_type'],
                    $data['event_date'] ?: null,
                    $data['guest_count'] ?: null,
                    $data['venue_name'],
                    $data['venue_address'],
                    $data['notes_to_customer'],
                    $data['internal_notes'],
                    $totals['subtotal'],
                    $data['discount_type'],
                    $data['discount_value'],
                    $totals['discount_amount'],
                    $totals['total'],
                    $data['deposit_type'],
                    $data['deposit_value'],
                    $totals['deposit'],
                    $data['valid_until'] ?: null,
                    'draft',
                ]
            );
            $quoteId = (int) $this->db->lastInsertId();

            foreach ($items as $i => $item) {
                $this->db->execute(
                    "INSERT INTO quote_items (quote_id,sort_order,type,description,unit_price_cents,quantity,line_total_cents)
                     VALUES (?,?,?,?,?,?,?)",
                    [$quoteId, $i + 1, $item['type'], $item['description'],
                     $item['unit_price_cents'], $item['quantity'], $item['line_total_cents']]
                );
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->activityLogger->log('quotation.created', 'quote', (string) $quoteId);
        flash('success', 'Quote ' . $ref . ' created.');
        return Response::make()->redirect(url('/admin/quotations/' . $quoteId . '/edit'));
    }

    public function show(string $id): Response
    {
        $quote = $this->findWithCustomer((int) $id);
        if (!$quote) {
            flash('error', 'Quote not found.');
            return Response::make()->redirect(url('/admin/quotations'));
        }

        $items   = $this->db->fetchAll("SELECT * FROM quote_items WHERE quote_id=? ORDER BY sort_order", [(int) $id]);
        $notes   = $this->db->fetchAll("SELECT * FROM quote_notes WHERE quote_id=? ORDER BY created_at DESC", [(int) $id]);
        $history = $this->db->fetchAll("SELECT * FROM quote_status_history WHERE quote_id=? ORDER BY created_at DESC", [(int) $id]);
        $tokens  = $this->db->fetchAll("SELECT * FROM quote_access_tokens WHERE quote_id=? ORDER BY created_at DESC", [(int) $id]);

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/quotations/show', [
                'pageTitle' => 'Quote ' . $quote['public_ref'],
                'quote'     => $quote,
                'items'     => $items,
                'notes'     => $notes,
                'history'   => $history,
                'tokens'    => $tokens,
            ])
        );
    }

    public function edit(string $id): Response
    {
        $quote = $this->findWithCustomer((int) $id);
        if (!$quote || $quote['status'] !== 'draft') {
            flash('error', !$quote ? 'Quote not found.' : 'Only draft quotes can be edited.');
            return Response::make()->redirect(url('/admin/quotations'));
        }

        $items     = $this->db->fetchAll("SELECT * FROM quote_items WHERE quote_id=? ORDER BY sort_order", [(int) $id]);
        $customers = $this->db->fetchAll("SELECT id, name, email FROM customers WHERE deleted_at IS NULL ORDER BY name LIMIT 500");
        $packages  = $this->db->fetchAll("SELECT id, name FROM packages WHERE is_published=1 ORDER BY sort_order");
        $users     = $this->db->fetchAll("SELECT id, name FROM users WHERE deleted_at IS NULL ORDER BY name");

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/quotations/edit', [
                'pageTitle' => 'Edit Quote ' . $quote['public_ref'],
                'quote'     => $quote,
                'items'     => $items,
                'customers' => $customers,
                'packages'  => $packages,
                'users'     => $users,
                'old'       => [],
                'errors'    => [],
            ])
        );
    }

    public function update(string $id): Response
    {
        $quote = $this->findWithCustomer((int) $id);
        if (!$quote || $quote['status'] !== 'draft') {
            flash('error', !$quote ? 'Quote not found.' : 'Only draft quotes can be edited.');
            return Response::make()->redirect(url('/admin/quotations'));
        }

        [$data, $errors] = $this->validate();
        if ($errors) {
            $items     = $this->db->fetchAll("SELECT * FROM quote_items WHERE quote_id=? ORDER BY sort_order", [(int) $id]);
            $customers = $this->db->fetchAll("SELECT id, name, email FROM customers WHERE deleted_at IS NULL ORDER BY name LIMIT 500");
            $packages  = $this->db->fetchAll("SELECT id, name FROM packages WHERE is_published=1 ORDER BY sort_order");
            $users     = $this->db->fetchAll("SELECT id, name FROM users WHERE deleted_at IS NULL ORDER BY name");
            return Response::make()->html(
                $this->view->renderWithLayout('admin', 'admin/quotations/edit', [
                    'pageTitle' => 'Edit Quote ' . $quote['public_ref'],
                    'quote'     => $quote,
                    'items'     => $items,
                    'customers' => $customers,
                    'packages'  => $packages,
                    'users'     => $users,
                    'old'       => $this->request->all(),
                    'errors'    => $errors,
                ])
            );
        }

        [$totals, $items] = $this->buildTotals($data);

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "UPDATE quotes SET
                    customer_id=?,assigned_to=?,event_type=?,event_date=?,guest_count=?,
                    venue_name=?,venue_address=?,notes_to_customer=?,internal_notes=?,
                    subtotal_cents=?,discount_type=?,discount_value=?,discount_amount_cents=?,
                    total_cents=?,deposit_type=?,deposit_value=?,deposit_cents=?,valid_until=?,
                    updated_at=NOW()
                 WHERE id=?",
                [
                    $data['customer_id'],
                    $data['assigned_to'] ?: null,
                    $data['event_type'],
                    $data['event_date'] ?: null,
                    $data['guest_count'] ?: null,
                    $data['venue_name'],
                    $data['venue_address'],
                    $data['notes_to_customer'],
                    $data['internal_notes'],
                    $totals['subtotal'],
                    $data['discount_type'],
                    $data['discount_value'],
                    $totals['discount_amount'],
                    $totals['total'],
                    $data['deposit_type'],
                    $data['deposit_value'],
                    $totals['deposit'],
                    $data['valid_until'] ?: null,
                    (int) $id,
                ]
            );

            $this->db->execute("DELETE FROM quote_items WHERE quote_id=?", [(int) $id]);
            foreach ($items as $i => $item) {
                $this->db->execute(
                    "INSERT INTO quote_items (quote_id,sort_order,type,description,unit_price_cents,quantity,line_total_cents)
                     VALUES (?,?,?,?,?,?,?)",
                    [(int) $id, $i + 1, $item['type'], $item['description'],
                     $item['unit_price_cents'], $item['quantity'], $item['line_total_cents']]
                );
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->activityLogger->log('quotation.updated', 'quote', $id);
        flash('success', 'Quote updated.');
        return Response::make()->redirect(url('/admin/quotations/' . $id . '/edit'));
    }

    public function send(string $id): Response
    {
        $quote = $this->findWithCustomer((int) $id);
        if (!$quote) {
            flash('error', 'Quote not found.');
            return Response::make()->redirect(url('/admin/quotations'));
        }
        if (!in_array($quote['status'], ['draft'], true)) {
            flash('error', 'Only draft quotes can be sent.');
            return Response::make()->redirect(url('/admin/quotations/' . $id));
        }

        $rawToken  = Token::generate();
        $tokenHash = Token::hash($rawToken);
        $validDays = (int) ($quote['valid_until'] ? max(1, (strtotime($quote['valid_until']) - time()) / 86400) : 14);

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO quote_access_tokens (quote_id,token_hash,expires_at,created_at)
                 VALUES (?,?,?,NOW())",
                [(int) $id, $tokenHash, date('Y-m-d H:i:s', strtotime("+{$validDays} days"))]
            );

            $this->db->execute(
                "UPDATE quotes SET status='sent', updated_at=NOW() WHERE id=?",
                [(int) $id]
            );

            $this->db->execute(
                "INSERT INTO quote_status_history (quote_id,from_status,to_status,changed_by,changed_name,created_at)
                 VALUES (?,?,?,?,?,NOW())",
                [(int) $id, $quote['status'], 'sent', $_SESSION['user']['id'] ?? null, $_SESSION['user']['name'] ?? null]
            );

            $viewUrl = url('/quote/' . $quote['public_ref'] . '/' . base64_encode(hex2bin($rawToken)));

            try {
                $this->mail->send(
                    to: $quote['customer_email'],
                    toName: $quote['customer_name'],
                    subject: 'Your quote from Unwinded — ' . $quote['public_ref'],
                    html: '<p>Dear ' . htmlspecialchars($quote['customer_name'], ENT_QUOTES) . ',</p>'
                        . '<p>Please find your personalised quote at the link below. It is valid until '
                        . date('d M Y', strtotime($quote['valid_until'] ?? '+14 days')) . '.</p>'
                        . '<p><a href="' . htmlspecialchars($viewUrl, ENT_QUOTES) . '">' . htmlspecialchars($viewUrl, ENT_QUOTES) . '</a></p>'
                        . '<p>Kind regards,<br>The Unwinded Team</p>',
                    text: "View your quote: $viewUrl"
                );
            } catch (\Throwable) {
                // non-fatal: continue even if mail fails
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->activityLogger->log('quotation.sent', 'quote', $id);
        flash('success', 'Quote sent to ' . $quote['customer_email'] . '.');
        return Response::make()->redirect(url('/admin/quotations/' . $id));
    }

    public function duplicate(string $id): Response
    {
        $quote = $this->findWithCustomer((int) $id);
        if (!$quote) {
            flash('error', 'Quote not found.');
            return Response::make()->redirect(url('/admin/quotations'));
        }

        $items  = $this->db->fetchAll("SELECT * FROM quote_items WHERE quote_id=? ORDER BY sort_order", [(int) $id]);
        $newRef = Ref::generate('QUO');

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO quotes
                    (public_ref,version,request_id,customer_id,created_by,assigned_to,
                     event_type,event_date,guest_count,venue_name,venue_address,
                     notes_to_customer,internal_notes,
                     subtotal_cents,discount_type,discount_value,discount_amount_cents,
                     total_cents,deposit_type,deposit_value,deposit_cents,valid_until,
                     status,created_at,updated_at)
                 SELECT ?,version+1,request_id,customer_id,?,assigned_to,
                     event_type,event_date,guest_count,venue_name,venue_address,
                     notes_to_customer,internal_notes,
                     subtotal_cents,discount_type,discount_value,discount_amount_cents,
                     total_cents,deposit_type,deposit_value,deposit_cents,valid_until,
                     'draft',NOW(),NOW()
                 FROM quotes WHERE id=?",
                [$newRef, $_SESSION['user']['id'] ?? null, (int) $id]
            );
            $newId = (int) $this->db->lastInsertId();

            foreach ($items as $item) {
                $this->db->execute(
                    "INSERT INTO quote_items (quote_id,sort_order,type,description,unit_price_cents,quantity,line_total_cents)
                     VALUES (?,?,?,?,?,?,?)",
                    [$newId, $item['sort_order'], $item['type'], $item['description'],
                     $item['unit_price_cents'], $item['quantity'], $item['line_total_cents']]
                );
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->activityLogger->log('quotation.duplicated', 'quote', (string) $newId);
        flash('success', 'Quote duplicated as ' . $newRef . '.');
        return Response::make()->redirect(url('/admin/quotations/' . $newId . '/edit'));
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function validate(): array
    {
        $data   = [
            'customer_id'       => (int) $this->request->str('customer_id'),
            'request_id'        => (int) $this->request->str('request_id') ?: null,
            'assigned_to'       => (int) $this->request->str('assigned_to') ?: null,
            'event_type'        => substr(trim($this->request->str('event_type')), 0, 100),
            'event_date'        => $this->request->str('event_date'),
            'guest_count'       => (int) $this->request->str('guest_count') ?: null,
            'venue_name'        => substr(trim($this->request->str('venue_name')), 0, 300),
            'venue_address'     => trim($this->request->str('venue_address')),
            'notes_to_customer' => trim($this->request->str('notes_to_customer')),
            'internal_notes'    => trim($this->request->str('internal_notes')),
            'discount_type'     => $this->request->str('discount_type') ?: 'none',
            'discount_value'    => (float) $this->request->str('discount_value'),
            'deposit_type'      => $this->request->str('deposit_type') ?: 'percentage',
            'deposit_value'     => (float) $this->request->str('deposit_value') ?: 50.0,
            'valid_until'       => $this->request->str('valid_until'),
            'line_desc'         => $this->request->all()['line_desc']  ?? [],
            'line_price'        => $this->request->all()['line_price'] ?? [],
            'line_qty'          => $this->request->all()['line_qty']   ?? [],
            'line_type'         => $this->request->all()['line_type']  ?? [],
        ];
        $errors = [];

        if (!$data['customer_id']) {
            $errors['customer_id'] = 'Customer is required.';
        }
        if (!in_array($data['discount_type'], ['none','percentage','fixed'], true)) {
            $data['discount_type'] = 'none';
        }
        if (!in_array($data['deposit_type'], ['percentage','fixed'], true)) {
            $data['deposit_type'] = 'percentage';
        }

        return [$data, $errors];
    }

    private function buildTotals(array $data): array
    {
        $descs  = (array) ($data['line_desc']  ?? []);
        $prices = (array) ($data['line_price'] ?? []);
        $qtys   = (array) ($data['line_qty']   ?? []);
        $types  = (array) ($data['line_type']  ?? []);
        $validTypes = ['package','extra','custom','discount','travel'];

        $items    = [];
        $subtotal = 0;

        foreach ($descs as $i => $desc) {
            $desc = trim((string) $desc);
            if ($desc === '') {
                continue;
            }
            $priceCents   = (int) round((float) ($prices[$i] ?? 0) * 100);
            $qty          = max(0.01, (float) ($qtys[$i] ?? 1));
            $lineCents    = (int) round($priceCents * $qty);
            $type         = in_array($types[$i] ?? '', $validTypes, true) ? $types[$i] : 'custom';

            $items[]   = [
                'type'             => $type,
                'description'      => substr($desc, 0, 500),
                'unit_price_cents' => $priceCents,
                'quantity'         => $qty,
                'line_total_cents' => $lineCents,
            ];
            $subtotal += $lineCents;
        }

        // Discount
        $discountAmount = 0;
        if ($data['discount_type'] === 'percentage') {
            $discountAmount = (int) round($subtotal * min(100, max(0, $data['discount_value'])) / 100);
        } elseif ($data['discount_type'] === 'fixed') {
            $discountAmount = (int) round($data['discount_value'] * 100);
        }
        $total = max(0, $subtotal - $discountAmount);

        // Deposit
        $deposit = 0;
        if ($data['deposit_type'] === 'percentage') {
            $deposit = (int) round($total * min(100, max(0, $data['deposit_value'])) / 100);
        } else {
            $deposit = (int) round($data['deposit_value'] * 100);
        }

        return [
            [
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmount,
                'total'           => $total,
                'deposit'         => $deposit,
            ],
            $items,
        ];
    }

    private function findWithCustomer(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT q.*, c.name AS customer_name, c.email AS customer_email
               FROM quotes q
               JOIN customers c ON c.id = q.customer_id
              WHERE q.id = ?",
            [$id]
        ) ?: null;
    }
}
