# Project Status

## Phase 1 — Architecture ✅ COMPLETE

**Deliverable:** `docs/PHASE-1-ARCHITECTURE.md`

Full architecture document covering:
- 35 public pages, 38 CMS modules
- 4 roles, 7 user journeys
- 72-table database plan
- 26 security controls
- 12-phase implementation checklist
- 30 open questions (OQ-01–OQ-06 resolved)

**Resolved business decisions:**
- VAT: Not registered (schema ready for future activation)
- Deposit: 50% | soft deadline: 48 hrs after acceptance | hard deadline: 5 business days before event
- Quote validity: 14 days | reminder on day 10 | on expiry: flag for human follow-up
- Refunds: within 10 business days of admin approval
- Payment gateway: **PayFast** (ITN webhooks)
- Tickets: **separate QR code per admission**

---

## Phase 2 — Foundation ✅ COMPLETE

**Branch:** `claude/unwinded-cms-architecture-71ytmv`

### Deliverables

**Framework (app/Core/)**
- Container, Config, Database (PDO wrapper), Logger
- Request, Response, Session (private save_path, SameSite=Lax)
- CSRF (exempt for /webhooks prefix), Auth (idle timeout, RBAC)
- RateLimiter (DB-backed sliding window), EventBus (synchronous)
- Router (named routes, group/middleware), View (PHP templates, layouts)
- Migrator (tracks migrations table, run/rollback/fresh)

**Support Value Objects (app/Support/)**
- `Money` — integer cents, immutable, `allocate()` for remainder-safe splits
- `Ref` — Crockford Base32 public references (`UNW-B-7F3K9XQ2TB`)
- `Token` — SHA-256 hashed tokens (plaintext exists once in URL)
- `Str`, `Csv`, `Clock`

**Middleware**
- SecurityHeaders (CSP no unsafe-inline, HSTS, X-Frame-Options)
- Auth, CSRF (route-level), Guest

**Configuration** — 7 config files, all reading from `.env`

**Database Migrations (0001–0016)** — 72 tables total:
- Auth, Settings/Content, Media, Packages, Customers, Quotes, Bookings,
  Events, Discounts, Ticketing, Payments, Gallery, Operations, Marketing,
  Email, System

**Database Seeds**
- Roles & Permissions (4 roles, 50+ permissions)
- Admin user
- Settings (40+ default values)
- Homepage sections (7)
- Email templates (13 transactional templates)
- Scheduled tasks (8)

**Routes**
- `routes/web.php` — 35 public routes
- `routes/admin.php` — 38 CMS modules under /admin
- `routes/api.php` — internal AJAX
- `routes/webhooks.php` — PayFast ITN (CSRF-exempt)

**Views**
- Layouts: `public.php`, `admin.php`, `email.php`
- Error pages: 404, 403, 405, 500, 503
- Admin login, dashboard placeholder
- Public homepage placeholder

**CLI** — `bin/console`:
- `migrate`, `migrate:rollback`, `migrate:fresh`
- `db:seed`, `key:generate`, `schedule:run`, `user:create`

**Vendor** — Composer dependencies committed (PHPMailer, QR code, dotenv, OAuth2 Google)

**Docs** — README, DATABASE, DEPLOYMENT, CHANGELOG

---

## Phase 3 — Admin Authentication (NEXT)

**Scope:**
- `AdminController` — login (rate-limited), logout, session regeneration
- Password reset flow (token → hash, email via queue, time-limited)
- Profile/password change
- Activity logging on login/logout
- Functional admin dashboard with real stats

**Exit criteria:** Can log in, reset password, view dashboard with live DB counts.

---

## Phases 4–12 (Pending Phase 3 Approval)

| Phase | Description |
|-------|-------------|
| 4 | Settings, media library, content pages |
| 5 | Public website (all 35 pages) |
| 6 | Quotes & bookings CMS |
| 7 | Public events & ticket sales |
| 8 | Payments (PayFast + EFT) |
| 9 | Gallery (public + private) |
| 10 | Marketing (testimonials, FAQs, enquiries, newsletter) |
| 11 | Operations (checklists, staff, reports) |
| 12 | QA, security audit, launch |
