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

## Phases 5–12 (Pending Phase 4 Approval)

| Phase | Description |
|-------|-------------|
| 5 | Public website (all 35 pages) |
| 6 | Quotes & bookings CMS |
| 7 | Public events & ticket sales |
| 8 | Payments (PayFast + EFT) |
| 9 | Gallery (public + private) |
| 10 | Marketing (testimonials, FAQs, enquiries, newsletter) |
| 11 | Operations (checklists, staff, reports) |
| 12 | QA, security audit, launch |
