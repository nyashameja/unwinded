<?php

declare(strict_types=1);

namespace Unwinded\Controllers\Admin;

use Unwinded\Core\Database;
use Unwinded\Core\Request;
use Unwinded\Core\Response;
use Unwinded\Core\View;

class DiscountController
{
    private const TYPES      = ['percentage', 'fixed'];
    private const APPLIES_TO = ['tickets', 'bookings', 'both'];

    public function __construct(
        private Database $db,
        private Request  $request,
        private View     $view,
    ) {}

    public function index(): Response
    {
        $codes = $this->db->fetchAll(
            "SELECT dc.*, COUNT(du.id) AS use_count
               FROM discount_codes dc
               LEFT JOIN discount_code_usage du ON du.discount_id = dc.id
             GROUP BY dc.id
             ORDER BY dc.created_at DESC"
        );

        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/discounts/index', [
                'pageTitle'  => 'Discount Codes',
                'codes'      => $codes,
                'types'      => self::TYPES,
                'appliesTo'  => self::APPLIES_TO,
            ])
        );
    }

    public function create(): Response
    {
        return Response::make()->html(
            $this->view->renderWithLayout('admin', 'admin/discounts/create', [
                'pageTitle' => 'New Discount Code',
                'types'     => self::TYPES,
                'appliesTo' => self::APPLIES_TO,
            ])
        );
    }

    public function store(): Response
    {
        [$data, $errors] = $this->validate();

        if ($errors) {
            foreach ($errors as $e) {
                flash('error', $e);
            }
            return Response::make()->redirect(url('/admin/discounts/create'));
        }

        $existing = $this->db->fetchScalar(
            "SELECT id FROM discount_codes WHERE code = ?",
            [$data['code']]
        );
        if ($existing) {
            flash('error', 'That code already exists.');
            return Response::make()->redirect(url('/admin/discounts/create'));
        }

        $this->db->insert('discount_codes', $data);

        flash('success', 'Discount code created.');
        return Response::make()->redirect(url('/admin/discounts'));
    }

    public function update(string $id): Response
    {
        $code = $this->db->fetchOne(
            "SELECT * FROM discount_codes WHERE id = ?",
            [(int) $id]
        );

        if (!$code) {
            flash('error', 'Code not found.');
            return Response::make()->redirect(url('/admin/discounts'));
        }

        [$data, $errors] = $this->validate($code['code']);

        if ($errors) {
            foreach ($errors as $e) {
                flash('error', $e);
            }
            return Response::make()->redirect(url('/admin/discounts'));
        }

        $set = implode(', ', array_map(fn($k) => "$k=?", array_keys($data)));
        $this->db->execute(
            "UPDATE discount_codes SET $set, updated_at=NOW() WHERE id=?",
            [...array_values($data), (int) $id]
        );

        flash('success', 'Discount code updated.');
        return Response::make()->redirect(url('/admin/discounts'));
    }

    private function validate(string $existingCode = ''): array
    {
        $errors = [];
        $data   = [];

        $code = strtoupper(trim($this->request->str('code') ?? ''));
        if (!$code) {
            $errors[] = 'Code is required.';
        } elseif (!preg_match('/^[A-Z0-9_-]{2,50}$/', $code)) {
            $errors[] = 'Code may only contain letters, numbers, dashes, and underscores (2–50 chars).';
        } else {
            if ($code !== $existingCode) {
                $dup = $this->db->fetchScalar("SELECT id FROM discount_codes WHERE code = ?", [$code]);
                if ($dup) {
                    $errors[] = 'That code already exists.';
                }
            }
            $data['code'] = $code;
        }

        $discountType = $this->request->str('discount_type') ?? '';
        if (!in_array($discountType, self::TYPES, true)) {
            $errors[] = 'Invalid discount type.';
        } else {
            $data['discount_type'] = $discountType;
        }

        $discountValue = (float) ($this->request->str('discount_value') ?? 0);
        if ($discountValue <= 0) {
            $errors[] = 'Discount value must be greater than zero.';
        } elseif ($discountType === 'percentage' && $discountValue > 100) {
            $errors[] = 'Percentage discount cannot exceed 100%.';
        } else {
            $data['discount_value'] = $discountValue;
        }

        $appliesTo = $this->request->str('applies_to') ?? 'tickets';
        $data['applies_to'] = in_array($appliesTo, self::APPLIES_TO, true) ? $appliesTo : 'tickets';

        $data['min_order_cents'] = (int) ($this->request->str('min_order_cents') ?? 0) * 100;
        $data['max_uses']        = (($v = (int) ($this->request->str('max_uses') ?? 0)) > 0) ? $v : null;

        $startsAt  = $this->request->str('starts_at');
        $expiresAt = $this->request->str('expires_at');
        $data['starts_at']  = ($startsAt  && strtotime($startsAt))  ? date('Y-m-d H:i:s', strtotime($startsAt))  : null;
        $data['expires_at'] = ($expiresAt && strtotime($expiresAt)) ? date('Y-m-d H:i:s', strtotime($expiresAt)) : null;
        $data['is_active']  = (bool) $this->request->str('is_active') ? 1 : 0;
        $data['description'] = trim($this->request->str('description') ?? '') ?: null;

        return [$data, $errors];
    }
}
