# Database Reference

## Overview

- Engine: MySQL 8.0+
- Charset: `utf8mb4` / Collation: `utf8mb4_unicode_ci`
- All monetary values stored as `INT` (cents), never `FLOAT` or `DECIMAL` for arithmetic
- All timestamps stored in UTC; displayed in Africa/Johannesburg
- Migrations tracked in `migrations` table; run with `php bin/console migrate`

## Migration Files

| File | Tables |
|------|--------|
| 0001_create_auth_tables | users, roles, permissions, role_permissions, user_roles, password_resets, login_attempts, activity_logs |
| 0002_create_settings_content_tables | settings, redirects, not_found_log, menus, menu_items, pages, page_revisions, homepage_sections, experiences |
| 0003_create_media_tables | media, media_usages |
| 0004_create_package_tables | packages, package_features, package_extras, package_extra_links |
| 0005_create_customer_tables | customers, customer_consents |
| 0006_create_quote_tables | quote_requests, quote_request_attachments, quotes, quote_access_tokens, quote_items, quote_notes, quote_status_history |
| 0007_create_booking_tables | private_bookings, booking_items, booking_notes, booking_status_history, booking_documents |
| 0008_create_event_tables | public_events, event_images, event_ticket_types |
| 0009_create_discount_tables | discount_codes, discount_code_usage |
| 0010_create_ticketing_tables | ticket_orders, order_items, ticket_reservations, tickets, ticket_checkins |
| 0011_create_payment_tables | payments, payment_allocations, payment_webhooks, payment_logs, refunds, eft_proofs |
| 0012_create_gallery_tables | gallery_albums, gallery_images, gallery_access_tokens, gallery_access_logs, gallery_publish_consents |
| 0013_create_operations_tables | checklist_templates, checklist_template_items, event_checklists, checklist_items, staff_assignments |
| 0014_create_marketing_tables | testimonials, faq_groups, faqs, enquiries, newsletter_subscribers |
| 0015_create_email_tables | email_templates, email_queue, email_logs |
| 0016_create_system_tables | scheduled_tasks, rate_limits |

**Total: 72 tables**

## Key Design Decisions

### Money as Integer Cents

All monetary columns use `INT` (or `BIGINT`) storing cent values:

```sql
subtotal_cents INT NOT NULL DEFAULT 0
```

The `Money` class (`app/Support/Money.php`) wraps this, preventing float arithmetic.
`Money::allocate()` distributes rounding remainders deterministically.

### Idempotent Webhooks

```sql
UNIQUE KEY uq_webhook (provider, provider_event_id)
```

Duplicate PayFast ITN deliveries are silently ignored at the database constraint level.

### Atomic Ticket Stock

```sql
UPDATE event_ticket_types
SET    qty_reserved = qty_reserved + ?
WHERE  id = ?
  AND  (qty_available - qty_reserved - qty_sold) >= ?
```

A `CHECK` constraint also enforces `qty_reserved + qty_sold <= qty_available`.
Overselling is impossible even under concurrent requests.

### Separate QR Tickets

A "Couple" ticket type with `admissions_per_ticket = 2` generates **2 individual**
`tickets` records at order confirmation. Each ticket has its own `ticket_uid` and QR code.

### Private Gallery Originals

Gallery originals are stored in `storage/private/galleries/` (outside the web root).
PHP delivers them after verifying token + expiry. Filenames are never derived from user input.

### Token Storage

Tokens are never stored in plaintext:

```php
['raw' => random_bytes(32), 'hash' => hash('sha256', $raw)]
```

Store `hash`; put `raw` (URL-safe base64) in the link. Compare with `hash_equals()`.

### Soft Deletes

Content tables (pages, packages, customers, testimonials, faqs) use `deleted_at` columns.
Financial and audit records (payments, refunds, quotes, bookings, tickets) are **never** soft-deleted.

### Public References

Human-readable, URL-safe references use Crockford Base32 (excludes I/L/O/U):

```
UNW-B-7F3K9XQ2TB   (booking)
UNW-Q-4R8MXNP1KD   (quote)
UNW-T-2JC5W9YEGV   (ticket)
```

Never expose auto-increment IDs in URLs.

## Seed Data

Run `php bin/console db:seed` to populate:

- Roles & Permissions (`RolesAndPermissionsSeeder`)
- Admin user (`AdminUserSeeder`) — credentials from `.env` `SEED_ADMIN_*`
- Default settings (`SettingsSeeder`)
- Homepage sections (`HomepageSectionsSeeder`)
- Email templates (`EmailTemplatesSeeder`)
- Scheduled tasks (`ScheduledTasksSeeder`)
