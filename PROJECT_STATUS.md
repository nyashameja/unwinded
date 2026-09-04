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

## Phase 3 — Admin Authentication ✅ COMPLETE

**Branch:** `claude/unwinded-cms-architecture-71ytmv`

### Deliverables

**Services**
- `ActivityLogger` — writes to `activity_logs` table; intentionally non-transactional

**Controllers (app/Controllers/Admin/)**
- `AuthController` — `showLogin`, `login` (rate-limited, 10/15 min per IP), `logout`
- `PasswordResetController` — forgot-password form, send reset link (5/hr per IP, email-enumeration-safe), show reset form, reset password (DB transaction, forces logout)
- `ProfileController` — show profile, update name, change password (forces re-login)
- `DashboardController` — 9 live stats + recent activity log (20 entries)

**Views (app/Views/admin/)**
- `auth/forgot-password.php` — self-contained HTML (no layout)
- `auth/reset-password.php` — self-contained HTML (no layout)
- `profile/edit.php` — tabbed profile/password form inside admin layout

**Container / DI**
- `Container::build()` — reflection-based auto-wiring for controller instantiation
- All core services registered by both alias and full class name
- `Request` registered in container from `public/index.php`

**Router fix**
- Controllers resolved via `container->build()` (auto-wired) instead of `new ControllerClass($container)`
- Route params passed as positional args to controller methods

**Routes (admin.php)**
- Guest group now includes CSRF middleware on POST routes
- Password reset routes added: GET/POST `/admin/password/reset`, GET/POST `/admin/password/reset/{token}`
- Route method names corrected to match controller methods

**Exit criteria met:** Can log in, reset password, view dashboard with live DB counts.

---

## Phase 4 — Settings, Media Library, Content Pages ✅ COMPLETE

**Branch:** `claude/unwinded-cms-architecture-71ytmv`

### Deliverables

**Services**
- `SettingsService` — lazy-loads all settings on first access; `get()`, `set()`, `bulkSet()`, `grouped()` for admin form
- `MailService` — PHPMailer wrapper; SMTP credentials from config (never DB); `send()`, `testConnection()`
- `MediaUploadService` — GD-based WebP resize; originals in `storage/private/media/`; public variants in `public/media/`; sizes: thumb 400×400 crop, medium 1000×1000 fit, large 1800×1800 fit

**Controllers (app/Controllers/Admin/)**
- `SettingsController` — grouped settings form, boolean checkbox handling, SMTP test (JSON response)
- `MediaController` — paginated grid (36/page), AJAX upload returning JSON, soft-delete
- `PageController` — full CRUD; auto-slug; revision history on every save; system pages cannot be deleted
- `HomepageController` — section config edit (JSON merge), visibility toggle, drag-to-reorder (AJAX)
- `NavigationController` — atomic menu rebuild: DELETE all items then re-INSERT from form data
- `RedirectController` — upsert on source URL, hit counter display
- `ExperienceController` — type/slug/sort/active/CTA/SEO fields

**Views (app/Views/admin/)**
- `settings/index.php` — grouped settings form; type-appropriate inputs; sticky Save; SMTP test
- `media/index.php` — CSS grid; JS `fetch()` upload; pagination
- `pages/index.php`, `pages/create.php`, `pages/edit.php`, `pages/_form.php` — full CRUD with shared partial
- `homepage/index.php` — expandable section cards; drag-to-reorder vanilla JS
- `navigation/index.php` — add/remove items JS; target select
- `redirects/index.php` — inline add form; hits counter
- `experiences/index.php`, `experiences/edit.php` — two-column edit layout

**Helpers**
- `setting(string $key, mixed $default)` — request-scoped setting lookup

**Media storage**
- Originals → `storage/private/media/{year}/{month}/` (outside web root)
- Public WebP variants → `public/media/{year}/{month}/` (gitignored, rebuilt on upload)
- `.gitkeep` files track both directories in git

**Exit criteria met:** Settings can be saved and tested; media can be uploaded, browsed, and deleted; pages/homepage/navigation/redirects/experiences can be edited via the CMS.

---

## Phase 5 — Public Website (All 35 Pages) ✅ COMPLETE

**Branch:** `claude/unwinded-cms-architecture-71ytmv`

### Deliverables

**Routes (routes/web.php)**
- All 35 public routes registered and grouped under `CsrfMiddleware` for POST endpoints
- Routes: home, about, how-it-works, experiences (all/corporate/restaurant/private/show), packages, FAQs, testimonials, events (index/show), gallery (index/show/private/private-auth), contact, request-a-quote, quote view/accept/decline, newsletter (subscribe/confirm/unsubscribe), search, SEO (sitemap/robots), install, cron, checkout (show/reserve/confirm/return), order show, ticket show, payment show

**Controllers (app/Controllers/Public/)**
- `HomeController` — homepage sections + featured packages (GROUP_CONCAT features) + upcoming events + testimonials + gallery albums
- `PageController` — DB-driven static pages (about/how-it-works/terms/privacy/refund-policy) with hardcoded fallbacks
- `ExperienceController` — index, show, corporate/restaurant/private filtered views
- `PackageController` — batch-loads features and extras (2 queries, IN placeholders)
- `FaqController` — groups + ungrouped FAQs
- `TestimonialController` — JOIN to media for photos
- `EventController` — upcoming/past split; show with ticket_types
- `GalleryController` — public index/show; private token lookup + SHA-256 hash; password_verify(); session unlock; access log
- `ContactController` — honeypot + rate-limit (5/60 min) + enquiries table + admin email
- `QuoteRequestController` — honeypot + rate-limit (3/60 min) + quote_requests table + admin email
- `NewsletterController` — double opt-in; upsert; confirm/unsubscribe via hashed tokens; referer validation
- `SeoController` — XML sitemap (Content-Type: application/xml) + robots.txt from settings
- `SearchController` — LIKE search across pages/experiences/packages/events
- `QuoteViewController` — token-gated quote view; accept/decline POST
- `CheckoutController` — stub (coming soon)
- `OrderController` — stub order view
- `TicketController` — stub ticket view
- `PaymentController` — stub payment view
- `InstallController` — checks existing users; runs migrate + db:seed via exec()
- `CronController` — token via setting('cron.web_token'); hash_equals(); runs schedule:run

**Views (app/Views/public/)**
- `home.php` — hero, intro, featured packages, upcoming events, testimonials (stars), gallery preview, CTA
- `page.php` — shared static page; DB content or hardcoded fallback per slug
- `experiences/index.php` — filter tabs (all/corporate/restaurant/private), experiences grid
- `experiences/show.php` — breadcrumb, prose body, sidebar quote CTA
- `packages/index.php` — full detail cards, features (included/excluded), extras, pricing models
- `faqs.php` — `<details>/<summary>` accordion, groups + ungrouped
- `testimonials.php` — star ratings, featured badge, photo avatar
- `events/index.php` — upcoming list + past grid
- `events/show.php` — hero, description, includes/bring/policy, sidebar meta + ticket types
- `gallery/index.php` — segment filter tabs, album grid
- `gallery/show.php` — album breadcrumb, image grid
- `gallery/private.php` — password lock form (locked) or image grid with download links (unlocked)
- `contact.php` — honeypot, rate-limited, errors, sidebar with contact details
- `quote-request/show.php` — multi-section form (details/event/consent), honeypot
- `quote-request/thank-you.php` — confirmation
- `quote/view.php` — quote meta table, notes, accept/decline buttons (sent+valid), status messages
- `checkout/show.php` — coming soon stub
- `checkout/return.php` — payment return reference display
- `orders/show.php` — order summary + items table (stub)
- `tickets/show.php` — ticket detail + QR code (stub)
- `payment/show.php` — booking summary + EFT coming soon (stub)
- `newsletter/confirm.php` — confirmed / invalid token states
- `newsletter/unsubscribe.php` — unsubscribed / invalid link states
- `search.php` — search form + results grouped by type (pages/experiences/packages/events)
- `install/index.php` — self-contained install wizard (outside public layout)

**Security controls applied in Phase 5:**
- CSRF middleware on all public POST routes
- Honeypot on contact and quote-request forms
- Rate limiting on contact (5/60 min) and quote-request (3/60 min) per IP
- Gallery private access via SHA-256 token hash + `password_verify()`; session unlock flag
- Newsletter tokens stored as SHA-256 hashes; plaintext exists once in the confirmation URL
- Referer validation before redirect in newsletter subscribe
- `hash_equals()` for cron web token comparison
- All server-side totals recalculated; no browser-submitted amounts trusted
- Webhook signatures validated (PayFast stub in routes/webhooks.php)

**Exit criteria met:** All 35 public routes render without 404; forms have CSRF, honeypot, and rate-limiting; gallery private access is secure; newsletter uses double opt-in; checkout/order/ticket/payment stubs are in place for Phase 7/8.

---

## Phase 6 — Quotes & Bookings CMS ✅ COMPLETE

**Branch:** `claude/unwinded-cms-architecture-71ytmv`

### Deliverables

**Controllers (app/Controllers/Admin/)**
- `QuoteRequestController` — index (filterable by status), show (with linked quotes + status history), update (status + assignment), addNote (appends to extra_notes with timestamp + author)
- `QuotationController` — index, create, store (full line-item build + deposit/discount calculation), show (with customer link URL), edit (draft-only guard), update, send (generates access token hash, sends email, updates status), duplicate (copies items + increments version)
- `BookingController` — index (filterable), show (items/notes/history/documents/payments), edit, update (line items recalculated server-side), status (with history log), addNote, upload (document via MediaUploadService)
- `PaymentController` — index, show (allocations/refunds/logs/EFT proof), recordEft (creates payment + allocation + EFT proof + updates booking amounts), refund (creates refund record with 10-day due date)
- `CustomerController` — index (search by name/email/phone/company), show (with bookings/quotes/requests/payments), update, merge (reassigns all records to target customer)

**Views (app/Views/admin/)**
- `quote-requests/index.php` — status filter chips, requests table with event type and assigned user
- `quote-requests/show.php` — customer detail, event detail, linked quotes, status update form, add-note form, status history
- `quotations/index.php` — status filter, quotes table with validity and expired state
- `quotations/create.php` — customer select, event fields, notes, line-items editor with live JS subtotal
- `quotations/edit.php` — same as create, draft-only guard
- `quotations/show.php` — line items table, totals, customer link URL, notes, status history, send/duplicate actions
- `quotations/_form.php` — shared line-items partial with vanilla-JS add/remove/recalculate; discount + deposit fields
- `bookings/index.php` — status filter, booking + payment status badges
- `bookings/show.php` — full booking detail, line items, payments with inline EFT form, document upload, add-note form, status change form, history
- `bookings/edit.php` — event fields, financial fields (deposit/deadlines), line-items editor, notes
- `payments/index.php` — status filter, payments table
- `payments/show.php` — payment detail, allocations, refund request form, EFT proof display, payment logs
- `customers/index.php` — search form, customers table with booking/quote counts
- `customers/show.php` — edit form, merge form, activity tabs (bookings/quotes/requests/payments)

**Business rules enforced:**
- All totals (subtotal, discount, total, deposit) recalculated server-side — browser values never trusted
- Quote access tokens stored as SHA-256 hashes; plaintext exists once in the customer link URL
- Only draft quotes can be edited or sent
- Status changes are always logged to `quote_status_history` / `booking_status_history`
- EFT recording atomically updates `payments`, `payment_allocations`, `eft_proofs`, and `private_bookings.amount_paid_cents`
- Refunds create a `refunds` row with `due_by = +10 business days` and never directly credit money
- Customer merge reassigns all foreign keys before soft-deleting the source record in a transaction

**Exit criteria met:** Quote requests can be triaged and converted to quotes; quotes can be built with line items, discounted, sent to customers with token-gated links; accepted quotes become bookings; EFT payments can be recorded and allocated; refunds can be requested; customer records can be searched, edited, and merged.

---

---

## Phase 7 — Public Events & Ticket Sales ✅ COMPLETE

**Branch:** `claude/unwinded-cms-architecture-71ytmv`

### Deliverables

**Admin Controllers (app/Controllers/Admin/)**
- `EventController` — full CRUD (index/create/store/edit/update/destroy); slug auto-generation with uniqueness guarantee; status management (draft/scheduled/on_sale/sold_out/sales_closed/completed/cancelled/postponed); sales window fields; SEO fields
- `TicketTypeController` — store/update per event via edit-page modal; soft-delete; validates qty_available >= qty_reserved + qty_sold; price as Rand → cents
- `OrderController` — index (filterable by status + event); show with items and tickets; resend (emails tickets to purchaser); cancel (releases qty_reserved/qty_sold atomically in transaction)
- `CheckinController` — check-in dashboard with live stats (sold/checked-in/remaining); JS-driven QR/manual entry; override support; AJAX POST returns JSON; recent check-ins prepended live

**Admin Views (app/Views/admin/)**
- `events/index.php` — status filter; events table with date, venue, ticket type count; link to check-in
- `events/create.php` — event form via `_form.php` partial
- `events/edit.php` — event form + ticket types panel; modal for add/edit ticket types; delete zone
- `events/_form.php` — full event fields: title/slug/description/body/date/times/sales window/venue/capacity/policy/featured image/SEO; two-column grid
- `ticket-types/index.php` — (standalone listing, reached via modal in edit page)
- `orders/index.php` — status + event filter; orders table with purchaser and event details
- `orders/show.php` — order detail, line items, ticket list with view links; resend and cancel actions
- `checkin/index.php` — stats cards; AJAX check-in form (QR or manual entry); override section; live recent check-ins list

**Public Controllers (app/Controllers/Public/)**
- `CheckoutController` — full implementation replacing stub:
  - `show()` — loads on-sale events and active ticket types; checks sales windows
  - `reserve()` — validates quantities (min/max per order); re-reads qty with `FOR UPDATE` lock; calculates all totals server-side from DB prices (never browser values); creates pending `ticket_orders` + `order_items` + `ticket_reservations` (15-min expiry); atomically increments `qty_reserved`
  - `confirm()` — checks reservation hasn't expired; shows EFT payment instructions; on POST extends reservation to 48 hours and emails payment details
  - `return()` — shows post-payment landing page
- `OrderController` — functional: loads order with event details, items, and tickets; renders by public_ref (Crockford Base32, unguessable)
- `TicketController` — functional: loads ticket via `ticket_uid`; joins event and ticket type; renders QR code via `chillerlan/php-qrcode` SVG output

**Public Views (app/Views/public/)**
- `checkout/show.php` — ticket type selection with remaining count and sold-out state; purchaser details; live JS total (display-only; server recalculates); CSRF protected
- `checkout/confirm.php` — order summary; EFT banking details from `setting('bank.details')`; countdown timer; "I have paid" button extends reservation and sends email
- `checkout/return.php` — thank-you landing page
- `orders/show.php` — order status, event details, line items, ticket list with status and direct links; EFT pending message
- `tickets/show.php` — ticket status badge; event details; inline SVG QR code (chillerlan library); ticket UID display

**Security controls applied in Phase 7:**
- All totals recalculated server-side; browser-submitted prices never used
- Seat reservation uses `SELECT ... FOR UPDATE` to prevent race conditions
- qty_reserved incremented atomically in the same transaction as order creation
- Reservation expires after 15 minutes; confirmed EFT orders extended to 48 hours
- Cancellation atomically decrements qty_reserved and qty_sold
- Check-in uses DB transaction; `ticket_checkins` unique on ticket_id prevents double check-in
- CSRF middleware on all checkout POST routes

**Exit criteria met:** Events can be created and managed with ticket types; ticket sales flow works end-to-end (reserve → confirm → EFT payment instructions); admin can view and cancel orders; check-in dashboard allows QR/manual entry with override support; public ticket page shows SVG QR code.

---

## Phases 8–12 (Pending)

| Phase | Description |
|-------|-------------|
| 8 | Payments (PayFast + EFT) |
| 9 | Gallery (public + private) |
| 10 | Marketing (testimonials, FAQs, enquiries, newsletter) |
| 11 | Operations (checklists, staff, reports) |
| 12 | QA, security audit, launch |
