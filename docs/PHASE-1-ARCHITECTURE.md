# Unwinded — Phase 1: Requirements, Architecture & Implementation Plan

**Status:** Awaiting approval. No application code has been written.
**Target:** Custom PHP 8.2 + MySQL 8 website and CMS on standard cPanel shared hosting.
**Date:** 2026-09-04

---

## 1. Understanding of Unwinded and the platform

### 1.1 The business

Unwinded is a South African **Sip & Paint experience company**. It does not sell a product; it sells a
*facilitated social experience* that is assembled per event from people, consumables, artwork and styling.
Brand positioning is *"We create fully personalised Sip & Paint experiences tailored to your event"*;
tagline *"SIP • PAINT • UNWIND."*

Revenue arrives through **two structurally different channels**, and almost every architectural decision
below follows from keeping them separate but sharing a common spine:

| | **Channel A — Private events** | **Channel B — Public ticketed events** |
|---|---|---|
| Who initiates | Customer requests a quote | Unwinded schedules an event |
| Price | Negotiated, per-event, quoted | Fixed, published per ticket type |
| Sales motion | Human: enquiry → quote → accept → deposit → balance | Self-service: browse → checkout → pay → ticket |
| Money shape | Two-or-more part payment against one invoice-like total | Single payment per order |
| Inventory | Guest count vs facilitator/kit capacity | Ticket stock, must not oversell |
| Fulfilment proof | Booking reference + event day operations | QR ticket + door check-in |
| Volume | Low count, high value, long-lived records | High count, low value, spiky at sale time |

Segments served: Corporate Experiences, Restaurant Partnerships, Private Celebrations
(bridal/baby showers, birthdays, couples, general celebrations), and Public Ticketed Events.

### 1.2 What the platform actually is

Four systems that must interlock, plus a marketing site in front of them:

1. **A marketing website** whose every meaningful block is editable by a non-technical administrator.
2. **A CRM + quoting + invoicing engine** for private events (the highest-value, highest-risk part).
3. **A lightweight e-commerce + ticketing + access-control engine** for public events.
4. **A digital asset system** — event photo galleries, some public marketing assets, some *confidential
   client photography* that must never leak.

Crossing all four: money, identity (customers), permissions, email, audit and SEO.

### 1.3 The five properties that constrain every design choice

1. **Shared cPanel hosting.** No daemons, no queue workers, no Redis, no Node at runtime, no root, a
   fixed disk quota, unpredictable CLI availability, a *shared outbound IP address*, and PHP processes
   that only exist for the duration of a request or a cron tick. Every "background" behaviour must
   therefore be a cron-driven, lock-protected, idempotent, resumable batch.
2. **Money must be exact and auditable.** Deposits, balances, partial refunds and VAT mean append-only
   financial history, integer-cent arithmetic, database transactions, and no client-supplied totals — ever.
3. **Privacy is a product feature, not a checkbox.** Corporate clients will supply staff photographs;
   private-celebration clients will not want their bridal shower indexed by Google. Private galleries need
   unguessable tokens, hashed passwords, expiry, revocation, access logs and storage outside the web root.
   POPIA applies.
4. **Concurrency exists in exactly two places** — ticket stock and private-event scheduling. Everything
   else is low-contention CRUD. Effort goes where the races are.
5. **Email deliverability is outside our server.** MX points at Google Workspace; cPanel does *not* host
   the mailboxes. Mail must be relayed to Google, authenticated, and never sent from the local MTA, or
   SPF/DMARC alignment breaks and quotations land in spam.

### 1.4 Explicit non-goals for v1

Automated WhatsApp messaging (links only, until a Business API is provisioned), bulk newsletter *sending*
(capture and export only — see open question OQ-21), multi-currency, multi-language, a drag-and-drop page
builder, a public customer login area (customers reach their records by signed links, not passwords),
and any mobile app.

---

## 2. Proposed application architecture

### 2.1 Shape

A **single PHP application, no framework**, organised MVC-style with a service layer, served through one
front controller. Composer is used for autoloading and for four libraries that would be irresponsible to
write by hand. There is no build step at deploy time.

```
HTTP request
   ↓ Apache + mod_rewrite  (everything not a real file → public/index.php)
   ↓ public/index.php      (front controller; only PHP file in the web root)
   ↓ bootstrap/app.php     (env, error handler, container, config, timezone, session)
   ↓ Router                (compiled route table → route + params)
   ↓ Middleware pipeline   (security headers → session → CSRF → auth → permission → rate limit)
   ↓ Controller            (thin: validate, delegate, respond)
   ↓ Validator             (server-side, per-form rule sets)
   ↓ Service               (ALL business rules; owns transactions)
   ↓ Repository            (PDO, prepared statements, no SQL anywhere else)
   ↓ MySQL (InnoDB, utf8mb4)
   ↑ View                  (plain PHP templates, escape-by-default helpers)
```

**Layer contract — enforced by review, not by magic:**

- **Controllers** never contain SQL, never compute a price, never decide authorisation. They are ≤ ~40 lines.
- **Validators** own shape and format. They return a typed result, never throw for user error.
- **Services** own business rules, own `beginTransaction()`, own money, emit domain events (`QuoteAccepted`,
  `OrderPaid`) that listeners turn into queued emails and activity-log rows.
- **Repositories** own SQL and mapping. One repository per aggregate, not per table.
- **Policies** answer "may *this user* act on *this record*" (record-level), separate from permissions
  ("may this role use this feature" — route-level).
- **Views** receive a prepared array. No repository or service call from a template.

### 2.2 Why no framework

Laravel is excluded by requirement, and correctly so for this deployment: it needs Composer at deploy
time, writable bootstrap caches, a queue worker for anything asynchronous, and a `php artisan` habit that
shared hosting punishes. But "no framework" is not "no structure" — the cost of a framework is paid back
by this small set of hand-built primitives, which is the whole of the framework surface we actually need:

`Container` (constructor-injection, no autowiring magic beyond type hints) · `Config` · `Env` ·
`Router` · `Request` · `Response` · `Validator` · `View` · `Database` (PDO wrapper) · `QueryBuilder`
(deliberately minimal — SELECT composition only) · `Session` · `Csrf` · `Auth` · `Gate` · `Logger` ·
`Mailer` · `Storage` · `ImageProcessor` · `Money` · `Clock` · `RateLimiter` · `EventBus` · `Console`.

Roughly 4,000 lines of framework, all of it inspectable, none of it upgraded out from under us.

### 2.3 Composer dependencies — the complete list

| Package | Why it is not hand-written |
|---|---|
| `phpmailer/phpmailer` | Required. SMTP, STARTTLS, XOAUTH2, header-injection-safe address handling. |
| `chillerlan/php-qrcode` | **Recommended over `endroid/qr-code`**: zero transitive dependencies, pure PHP, renders to SVG *or* GD PNG. Endroid pulls a larger tree and its best output paths lean on Imagick, which is not guaranteed on shared hosting. |
| `vlucas/phpdotenv` | `.env` parsing has real edge cases (quoting, multiline, escapes). Small, stable. |
| `league/oauth2-google` *(Phase 10, only if OAuth2 mail is chosen)* | Google refresh-token flow for XOAUTH2. Omitted if SMTP relay auth is used. |

That is the whole list. `vendor/` is **committed to the repository** and uploaded as-is, so no Composer
run is required on the production host. Dev-only tooling (PHPUnit) lives in `require-dev` and is excluded
from the deployment archive.

Deliberately rejected: Twig (adds a compile cache and a dependency for something plain PHP templates plus
a strict escaping helper already do), Intervention Image (a thin GD/Imagick wrapper of our own is ~200 lines
and lets us fail over between drivers), Monolog (a rotating file logger is ~80 lines), any CSS/JS framework.

### 2.4 Front-end approach — genuinely no build step

- **CSS:** hand-authored, layered (`tokens → reset → base → layout → components → pages → utilities`),
  using CSS custom properties for the entire design system. Concatenated and minified by a small PHP
  asset pipeline that writes `public/assets/dist/app.<hash>.css` and is triggered from the admin
  ("Rebuild assets") or by a cron tick — never required at request time in production.
- **JavaScript:** native ES modules, no bundler. Progressive enhancement only — every form works without
  JS. Modules: lightbox, gallery filter, multi-step quote form, checkout, QR scanner, admin tables,
  media picker, sortable lists.
- **QR scanning** uses the browser's native `BarcodeDetector` where available (Chrome/Android), with a
  manual ticket-number entry field as the universal fallback. A self-hosted `jsQR` fallback module is
  included for iOS Safari. No camera library is loaded from a CDN.
- **Fonts self-hosted** as woff2 under `public/assets/fonts/`. Not Google Fonts CDN — that is a POPIA
  third-party-transfer question we do not need to answer, and self-hosting is faster anyway.

### 2.5 Cross-cutting technical decisions

**Money.** MySQL `DECIMAL(12,2)`. In PHP, a `Money` value object holding **integer cents** plus currency
`ZAR`. Arithmetic only through `Money::add/subtract/multiply/allocate`. `allocate()` distributes remainder
cents deterministically so a split never loses or invents a cent. Floats never touch a total. Display via
`R 1 234.56` (non-breaking thin space, SA convention).

**VAT.** Modelled as a per-line `tax_rate` snapshot plus a document-level VAT mode
(`none | inclusive | exclusive`) captured **on the document at creation time**, never read live from
settings — a quotation issued last year must still recompute to its historical total. Rounding is applied
per line, half-up, then summed. *Blocked on OQ-01.*

**Time.** All storage in UTC `DATETIME(3)`. All display in `Africa/Johannesburg` via a `Clock` service.
Event dates are stored as a local `DATE` + `TIME` pair *plus* a derived UTC datetime for sorting and
reminder scheduling — an event is "7pm at the venue", not an instant, and SA has no DST so this stays simple.

**Public identifiers.** Sequential IDs are never exposed. Every externally-visible record carries a
`public_ref`: a prefix plus 10 characters of Crockford Base32 from `random_bytes()` (excludes I/L/O/U, so
it is dictatable over the phone and un-typo-able).
`UNW-Q-7F3K9XQ2TB` (quote) · `UNW-B-…` (booking) · `UNW-O-…` (order) · `UNW-T-…` (ticket) ·
`UNW-P-…` (payment). Uniqueness enforced by a unique index with retry-on-collision.

**Tokens.** Private-gallery links, password resets, ticket links and unsubscribe links use 32 bytes of
`random_bytes()`, base64url-encoded, **stored only as a SHA-256 hash**, compared with `hash_equals()`.
The plaintext exists once, in the URL we hand out.

**Sessions.** `session.save_path` is repointed to `storage/sessions` (mode 0700). This matters
specifically on cPanel: the default shared `/tmp` is readable by other tenants on some configurations.
Cookies: `HttpOnly`, `Secure`, `SameSite=Lax` (Lax, not Strict — payment gateways return via cross-site
redirects and Strict would drop the session on return). ID regenerated on login and on privilege change.

**Idempotency.** Two hard rules: (a) webhook events are `INSERT` first against a unique
`(provider, provider_event_id)` index — a duplicate delivery collides and is acknowledged without
reprocessing; (b) every state transition that moves money is guarded by a conditional `UPDATE … WHERE
status = <expected>` inside a transaction, so a double-submit changes zero rows instead of double-charging.

**Ticket stock.** No `SELECT` then `UPDATE`. Reservation is a single conditional statement —
`UPDATE event_ticket_types SET qty_reserved = qty_reserved + :n WHERE id = :id AND (qty_available -
qty_sold - qty_reserved) >= :n` — inside a transaction that also inserts the reservation row. Zero
affected rows means sold out. Expired reservations are released by cron *and* opportunistically at the
start of every checkout, so stock recovers even if cron is late.

**Background work.** One cPanel cron entry every five minutes runs `bin/console schedule:run`, which
holds an `flock()` on a lock file, reads a `scheduled_tasks` table for what is due, and runs each task in
a bounded batch (e.g. 50 emails per tick) so no tick can exceed the host's `max_execution_time`. A
token-authenticated web endpoint (`/cron/run?token=…`) is available as a fallback for hosts without CLI PHP.

**Caching.** No Redis. A file-backed cache in `storage/cache` (atomic write-then-rename) for the compiled
route table, settings, navigation menus and rendered public fragments. OPcache does the heavy lifting.

**Errors.** `display_errors=0` in production. A single handler converts errors/exceptions into a logged
entry with a short reference code and shows the user a branded page carrying only that code. Stack traces
go to `storage/logs`, never to the browser, never to the database.

---

## 3. Proposed cPanel-compatible folder structure

### 3.1 Layout on the server (recommended: application outside the web root)

```
/home/<cpanel_user>/
├── unwinded_app/                    ← APPLICATION ROOT — not web accessible
│   ├── app/
│   │   ├── Controllers/{Admin,Public,Api,Webhook}/
│   │   ├── Models/
│   │   ├── Services/                # Quoting, Booking, Ticketing, Payment, Gallery, Mail, Reporting
│   │   ├── Repositories/
│   │   ├── Middleware/
│   │   ├── Validators/
│   │   ├── Policies/
│   │   ├── Helpers/
│   │   ├── Mail/                    # Mailable classes + renderer
│   │   ├── Payments/                # Gateway interface + drivers
│   │   ├── Support/                 # Money, Clock, Str, Ref, Token, Csv
│   │   └── Views/
│   │       ├── layouts/  partials/  components/
│   │       ├── public/   admin/     emails/   errors/
│   ├── bootstrap/                   # app.php, container.php, helpers.php, paths.php
│   ├── config/                      # app, database, mail, payments, uploads, security, seo
│   ├── database/
│   │   ├── migrations/              # 0001_create_users.php …  (up/down)
│   │   └── seeds/
│   ├── routes/                      # web.php, admin.php, api.php, webhooks.php
│   ├── storage/                     # 0700 — never web accessible
│   │   ├── cache/  logs/  sessions/  temp/
│   │   └── private/
│   │       ├── galleries/           # ORIGINAL private-event photography
│   │       ├── documents/           # quotations, contracts, proof of payment
│   │       └── uploads/             # customer inspiration images, pending review
│   ├── bin/console                  # migrate, seed, schedule:run, user:create, assets:build
│   ├── tests/
│   ├── vendor/                      # committed; no Composer needed on host
│   ├── .env                         # 0600 — the only secret store
│   └── .env.example
│
└── public_html/                     ← DOCUMENT ROOT (contents of the repo's public/)
    ├── index.php                    # the ONLY PHP entry point
    ├── .htaccess
    ├── robots.txt   sitemap.xml     # generated
    ├── favicon.ico
    ├── assets/{css,js,fonts,img,dist}/
    └── uploads/                     # PUBLIC media + PUBLIC gallery derivatives only
        └── <year>/<month>/
```

`public_html/index.php` locates the application through one line in `bootstrap/paths.php`, set by the
installer — so moving the app is a one-value change, and nothing else in the codebase knows a filesystem path.

### 3.2 Fallback layout (host forbids execution outside `public_html`)

Everything lives in `public_html/`, with `public/`'s contents at the top level and the application in
`public_html/_app/` protected by a `.htaccess` containing `Require all denied` **plus** a
`php_flag engine off`. This is measurably weaker (one server misconfiguration exposes `.env`), so the
installer detects and warns. Recommended layout 3.1 is used unless the host refuses.

### 3.3 Subdirectory and root-domain support

`APP_URL` in `.env` is authoritative for every generated absolute URL (emails, webhooks, canonical tags,
OG tags) — deliberately **not** `$_SERVER['HTTP_HOST']`, which is attacker-controlled and is the standard
vector for password-reset-link poisoning. For relative links, `BASE_PATH` is derived once from
`SCRIPT_NAME`, so the same build runs at `https://unwinded.co.za/` or `https://staging.host/unwinded/`
with no code change. `RewriteBase` is written by the installer.

### 3.4 What `.htaccess` must do

Root `.htaccess`: force HTTPS · strip `index.php` · route non-files to the front controller · deny
dotfiles, `.env`, `.md`, `.sql`, `.log`, `composer.*` · security headers (HSTS, `X-Content-Type-Options`,
`X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, a CSP, `Permissions-Policy`) · far-future cache headers
for `assets/dist` · block access to `uploads/` for anything that is not an image/PDF.
`public_html/uploads/.htaccess`: `php_flag engine off` + `RemoveHandler .php .phtml` + a
`FilesMatch` denial of every executable extension. **Defence in depth behind upload validation, not
instead of it.**

---

## 4. Full list of public pages

| # | Page | Route | Source | Notes |
|---|---|---|---|---|
| 1 | Home | `/` | Homepage sections | 17 toggleable, reorderable CMS-managed sections |
| 2 | About Unwinded | `/about` | Page + sections | |
| 3 | Experiences | `/experiences` | Experiences index | |
| 4 | Experience detail | `/experiences/{slug}` | Experience | Generic detail for any segment |
| 5 | Corporate Experiences | `/corporate` | Experience (pinned) | Own CTAs, own quote-form preset |
| 6 | Restaurant Partnerships | `/restaurant-partnerships` | Experience (pinned) | |
| 7 | Private Celebrations | `/private-celebrations` | Experience (pinned) | |
| 8 | Packages | `/packages` | Packages + comparison | Server-rendered comparison table |
| 9 | Upcoming Events | `/events` | Public events | Filter: month, city, availability |
| 10 | Event Details | `/events/{slug}` | Public event | Ticket types, live availability, Event schema |
| 11 | Gallery | `/gallery` | Published albums | Filter: type, year, segment, location |
| 12 | Gallery Album | `/gallery/{slug}` | Album | Lightbox, related albums, CTAs |
| 13 | Private Gallery | `/g/{token}` | Album (private) | Unlisted, optional password, `noindex`, expiring |
| 14 | Testimonials | `/testimonials` | Approved testimonials | |
| 15 | How It Works | `/how-it-works` | Page + sections | |
| 16 | FAQs | `/faqs` | FAQ groups | FAQPage schema |
| 17 | Contact | `/contact` | Enquiry form | Rate-limited, honeypot + timing check |
| 18 | Request a Quote | `/request-a-quote` | Multi-step form | 4 steps, server-side per-step validation |
| 19 | Quote request received | `/request-a-quote/thank-you` | — | Non-replayable confirmation |
| 20 | Book an Experience | `/book` | Booking intent | Routes to quote flow or event checkout |
| 21 | View a quotation | `/quote/{ref}/{token}` | Quotation | Signed link from email — accept / decline / query |
| 22 | Pay a deposit / balance | `/pay/{ref}/{token}` | Payment intent | Server-computed amount only |
| 23 | Ticket Checkout | `/events/{slug}/checkout` | Cart → order | Reservation held; totals server-side |
| 24 | Payment return | `/checkout/return/{ref}` | — | Displays pending; **never** marks paid |
| 25 | Order Confirmation | `/orders/{ref}/{token}` | Order | Tickets, receipt, resend |
| 26 | Ticket View | `/t/{uid}` | Ticket | Mobile ticket + QR; HMAC-validated |
| 27 | Newsletter confirm | `/newsletter/confirm/{token}` | — | Double opt-in |
| 28 | Newsletter unsubscribe | `/newsletter/unsubscribe/{token}` | — | One click, no login |
| 29 | Terms and Conditions | `/terms` | Page | |
| 30 | Privacy Policy | `/privacy` | Page | POPIA disclosures |
| 31 | Refund & Cancellation Policy | `/refund-policy` | Page | CPA s17 language — see OQ-04 |
| 32 | Search results | `/search` | — | Events, albums, pages |
| 33 | 404 | any | — | Checks `redirects` table before rendering |
| 34 | 403 / 410 / 500 / 503 | — | — | Branded, reference code only |
| 35 | `sitemap.xml`, `robots.txt` | — | Generated | Private albums and events excluded |

Global: sticky mobile "Book Your Experience" bar, floating WhatsApp button, cookie-consent banner,
newsletter footer capture.

---

## 5. Full list of CMS modules

| # | Module | Primary permission prefix | Notes |
|---|---|---|---|
| 1 | Dashboard | `dashboard.view` | Role-filtered widgets |
| 2 | Pages | `pages.*` | Draft, schedule, revisions, soft delete |
| 3 | Homepage Sections | `homepage.*` | Show/hide, reorder, content, linked records |
| 4 | Navigation | `navigation.*` | Multiple menus, nested items |
| 5 | Experiences | `experiences.*` | Segment landing content |
| 6 | Packages | `packages.*` | Pricing model, min/max guests, draft/published |
| 7 | Package Features | `packages.features.*` | Inclusions / exclusions, reusable |
| 8 | Package Extras | `packages.extras.*` | Priced extras, quantity rules |
| 9 | Public Events | `events.*` | Full lifecycle, SEO, capacity |
| 10 | Ticket Types | `events.tickets.*` | Price, stock, sales window, admissions |
| 11 | Ticket Orders | `orders.*` | Search, resend, cancel, refund request |
| 12 | Tickets & Check-ins | `checkin.*` | Scanner, manual entry, override |
| 13 | Private Bookings | `bookings.*` | The operational heart of Channel A |
| 14 | Quote Requests | `quote_requests.*` | Triage, assign, internal notes |
| 15 | Quotations | `quotes.*` | Line items, discounts, VAT, expiry, PDF |
| 16 | Payments | `payments.*` | Allocations, EFT capture, proof of payment |
| 17 | Refunds | `payments.refund` | Separate, tightly held permission |
| 18 | Customers | `customers.*` | Timeline, consent, merge |
| 19 | Gallery Albums | `gallery.albums.*` | Privacy, scheduling, linking |
| 20 | Gallery Images | `gallery.images.*` | Bulk upload, reorder, move, rotate |
| 21 | Gallery Access | `gallery.access.*` | Tokens, passwords, expiry, access log |
| 22 | Testimonials | `testimonials.*` | Approval gate before publish |
| 23 | FAQs | `faqs.*` | Grouped, ordered |
| 24 | Enquiries | `enquiries.*` | Contact-form inbox, status, assignment |
| 25 | Newsletter Subscribers | `newsletter.*` | Double opt-in state, CSV export |
| 26 | Discount Codes | `discounts.*` | Scope, limits, usage log |
| 27 | Email Templates | `email_templates.*` | Allow-listed variables only |
| 28 | Email Queue & Logs | `email_logs.*` | Retry, failure reasons, test send |
| 29 | Event Checklists | `checklists.*` | Per booking / per event |
| 30 | Checklist Templates | `checklists.templates.*` | Per-guest default quantities |
| 31 | Staff Assignments | `staff.*` | Facilitators, roles on the day |
| 32 | Media Library | `media.*` | Search, alt text, usage, safe delete |
| 33 | Users | `users.*` | Invite, suspend, force reset |
| 34 | Roles & Permissions | `roles.*` | Super Administrator only |
| 35 | Reports | `reports.*` | 15 reports, CSV + print |
| 36 | Redirects | `redirects.*` | 301/302, 404 log → suggested redirects |
| 37 | Activity Log | `activity.view` | Read-only, never editable |
| 38 | Website Settings | `settings.*` | Brand, contact, social, SEO, payments, mail |

---

## 6. User roles and permission boundaries

### 6.1 Model

Users have many **roles**; roles have many **permissions**; permissions are dotted string keys
(`bookings.edit`). A user's effective permission set is the union of their roles'. A `is_super` flag on the
role short-circuits every check — held only by Super Administrator, and the system refuses to delete the
last remaining super-admin user or to let a user edit their own roles.

Two distinct layers, both enforced server-side on every request:

- **Permission (feature-level):** middleware on the route. "May you open Refunds at all?"
- **Policy (record-level):** invoked in the controller/service. "May you refund *this* payment, given that
  it is on a booking you are not assigned to and is older than 30 days?"

Menus, buttons and table actions are rendered from the same `can()` calls, so the UI never offers an action
that would then be refused — but hiding a button is presentation, never protection.

### 6.2 The four seeded roles

| Capability | Super Admin | Administrator | Event Manager | Content Editor |
|---|:--:|:--:|:--:|:--:|
| Pages, homepage, navigation, FAQs, testimonials | ✔ | ✔ | view | ✔ |
| Media library | ✔ | ✔ | ✔ | ✔ |
| Packages & pricing | ✔ | ✔ | view | view |
| Public events & ticket types | ✔ | ✔ | ✔ | view |
| Ticket orders — view / resend | ✔ | ✔ | ✔ | ✖ |
| Ticket orders — cancel | ✔ | ✔ | ✖ | ✖ |
| Check-in (scan, check in) | ✔ | ✔ | ✔ | ✖ |
| Check-in **override** (re-admit used ticket) | ✔ | ✔ | ✔ ¹ | ✖ |
| Quote requests — triage, notes | ✔ | ✔ | ✔ | ✖ |
| Quotations — create, send | ✔ | ✔ | ✔ | ✖ |
| Quotations — discount above threshold | ✔ | ✔ | ✖ ² | ✖ |
| Private bookings | ✔ | ✔ | ✔ | ✖ |
| Payments — view | ✔ | ✔ | ✔ ³ | ✖ |
| Payments — record manual / EFT | ✔ | ✔ | ✖ | ✖ |
| **Refunds** | ✔ | ✔ | ✖ | ✖ |
| Customers — view / edit | ✔ | ✔ | ✔ | ✖ |
| Customers — merge / delete | ✔ | ✔ | ✖ | ✖ |
| Gallery — upload, edit, publish | ✔ | ✔ | ✔ | ✔ |
| Gallery — change privacy, issue/revoke tokens | ✔ | ✔ | ✔ | ✖ |
| Checklists & staff assignments | ✔ | ✔ | ✔ | ✖ |
| Discount codes | ✔ | ✔ | ✖ | ✖ |
| Email templates | ✔ | ✔ | ✖ | view |
| Reports — operational | ✔ | ✔ | ✔ | ✖ |
| Reports — financial / revenue | ✔ | ✔ | ✖ ³ | ✖ |
| Users | ✔ | ✔ ⁴ | ✖ | ✖ |
| Roles & permissions | ✔ | ✖ | ✖ | ✖ |
| Website settings — brand/SEO | ✔ | ✔ | ✖ | ✖ |
| Website settings — mail/payments | ✔ | ✖ | ✖ | ✖ |
| Activity log | ✔ | ✔ | own | ✖ |

¹ Every override writes an activity-log entry naming the user, ticket and reason.
² Above a configurable percentage — see OQ-11.
³ Whether an Event Manager sees money at all is OQ-16.
⁴ An Administrator cannot create or edit a Super Administrator, nor grant a permission they do not hold —
this closes the standard privilege-escalation path.

A Super Administrator can create further roles (e.g. "Facilitator" — check-in and own-event checklist only,
nothing financial) and assign any subset of permissions.

---

## 7. Main customer journeys

**J1 — Corporate quote → confirmed event (the core revenue path).**
Google/Instagram → `/corporate` → *Request a Quote* → 4-step form (contact · event · venue · experience)
→ auto-acknowledgement email + admin alert → admin triages, assigns, may request more information →
admin builds a formal quotation from a package plus extras plus custom lines → emailed as a signed link
→ customer accepts online → booking created in *Awaiting Deposit* → deposit request email → customer pays
online or by EFT with proof of payment → *Confirmed* → planning, checklist, staff assigned →
balance reminder → *Ready* → event day → *Completed* → gallery album created and shared →
testimonial request. **9 emails, 4 status machines, 2 payments, 1 gallery.**

**J2 — Public event ticket purchase.** Instagram → `/events` → event detail → select ticket types →
checkout (purchaser, attendees, discount code) → **stock reserved for N minutes** → server recomputes the
total → pending order → gateway → webhook confirms → order paid → tickets generated with QR → email →
mobile ticket → door scan → checked in.

**J3 — Restaurant partnership enquiry.** `/restaurant-partnerships` → quote form preset to that segment →
partnership discussion → recurring bookings. (Recurrence/commission is OQ-19.)

**J4 — Private client receives their photographs.** Event completed → admin creates an album from the
booking → uploads → sets Private, sets expiry, optionally sets a password → copies the link → sends to the
customer → customer views (and downloads, if permitted) → later, if the customer consents, the album is
converted to Public and appears in `/gallery`.

**J5 — Casual browser.** Home → gallery → album → *Request a Quote* CTA, or newsletter signup, or WhatsApp.

**J6 — Staff on event day.** Log in on a phone → check-in screen for tonight's event → scan → admit;
or open the booking's checklist and tick off setup.

---

## 8. Quotation and private-booking workflow

### 8.1 Quote request lifecycle

`New → Reviewing → (Information Required ⇄ Reviewing) → Quote Prepared → Quote Sent →
Accepted | Declined | Expired → Converted to Booking`

Every transition writes to `quote_status_history` with actor, timestamp and optional reason. Transitions
are validated against an allow-list — an arbitrary status cannot be set even by an administrator, and the
few permitted manual overrides require a permission and are logged.

### 8.2 The quotation document

A quotation is an **immutable-once-sent financial document**. Editing a sent quotation creates
**version 2**, leaving version 1 intact and viewable; the customer's link always resolves to the current
version, and the history is auditable. Line items snapshot the package name, description, unit price and
tax rate **at the time of issue**, so changing a package price next month never mutates a quotation
already in a customer's inbox.

Composition: package lines (per-person × guests, or fixed) · extras (unit × quantity) · custom lines ·
travel/venue lines · discount (percentage or fixed, against subtotal) · VAT per mode · total ·
deposit (percentage or fixed) · balance · validity/expiry date.

**All arithmetic is server-side.** The browser submits only identifiers and quantities. A submitted price
is ignored, and a mismatch between an expected and computed total is logged as a tampering attempt.

### 8.3 Acceptance and conversion

Customer opens `/quote/{ref}/{token}` → Accept → the service, in **one transaction**: locks the quotation,
verifies status is `Quote Sent` and not expired, marks it `Accepted`, creates a `private_bookings` row in
`Awaiting Deposit` copying totals and lines, links the customer, computes the deposit and its deadline,
and queues the deposit-request email. Re-clicking Accept changes zero rows and shows the existing booking.

### 8.4 Booking lifecycle

`Provisional → Awaiting Deposit → Confirmed → Planning → Ready → Completed`, with
`Cancelled` and `Refunded` reachable from most states.
Payment status is **derived, never typed**: recomputed from the sum of allocated payments against the
total after every payment event (`Unpaid | Partially Paid | Paid | Payment Pending | Payment Failed |
Partially Refunded | Refunded`).

**Scheduling conflict detection.** On save, the service checks for other non-cancelled bookings and public
events whose local time window overlaps, and warns (does not block) with an explicit override that is
logged — because Unwinded may genuinely run two events in a day if staff and kit allow. Capacity is
therefore modelled as a **daily resource budget** (concurrent events, facilitators available) rather than a
hard single-event-per-day rule. *The actual budget is OQ-14.*

**Duplicate prevention.** A form nonce plus a natural-key check (same customer, same date, same guest
count, created within 10 minutes) prompts before creating a second booking.

**Financial history is append-only.** Payments and refunds are never edited or deleted; a correction is a
new offsetting record with a reason. Totals may only change while a booking is `Provisional` or
`Awaiting Deposit`; afterwards a change requires a permission, records a variation line, and is logged.

---

## 9. Public-event ticketing workflow

```
Event: Draft → Scheduled → On Sale → (Sold Out) → Sales Closed → Completed
                                   ↘ Cancelled / Postponed
```

**Checkout, step by step, with the guarantees at each step:**

1. Customer selects quantities per ticket type. → Client-side display only.
2. Server validates: event is `On Sale`, now is inside each type's sales window, min/max per order
   respected, and event capacity is not exceeded in aggregate.
3. **Reserve stock** — the conditional `UPDATE` in §2.5. Zero rows affected ⇒ "only N left".
   A `ticket_reservations` row is written with an expiry (OQ-08, proposed 15 minutes).
4. Collect purchaser details, and attendee names if the event requires them (OQ-09).
5. Apply a discount code — validated server-side against scope, window, usage limits, per-customer limit
   and minimum order value. A discount can never take the total below zero.
6. **Recompute the total from database prices.** The browser's number is discarded.
7. Create the order as `Pending` with a `public_ref`, then redirect to the gateway.
8. The customer returns to `/checkout/return/{ref}`, which displays *"Confirming your payment…"* and
   **never marks anything paid**.
9. The **webhook** arrives, is signature-verified, is inserted against its unique event id, and — in one
   transaction — marks the payment successful, converts the reservation into `qty_sold`, sets the order
   `Paid`, generates one row per admission in `tickets` with a unique `ticket_uid` and QR payload, and
   queues the confirmation + ticket email.
10. Cron releases reservations whose expiry has passed and whose order never reached `Paid`.

**A payment that arrives after its reservation expired** is not dropped: the service re-attempts the stock
claim; if stock is gone the order is flagged `Requires Attention` and an administrator is emailed, because
silently refunding a paying customer is worse than a human looking at it.

**QR payload:** `{APP_URL}/t/{ticket_uid}?k={first 12 chars of HMAC-SHA256(ticket_uid, TICKET_KEY)}` —
unguessable, verifiable without a database round trip, and useless if copied to another event.

**Check-in:** mobile screen scoped to one event → scan or type → shows attendee, ticket type, admissions →
Check In. Already-used tickets show the original check-in time, the operator and a blocked state; only a
user with `checkin.override` may re-admit, must give a reason, and the override is logged.

---

## 10. Event gallery workflow

### 10.1 Data shape

An album is **one entity with an optional, polymorphic link** to its source — a public event, a private
booking, or nothing (manually created). Event details are **not copied** into the album; they are joined.
An album carries only what is genuinely album-specific (title, slug, description, cover, ordering, privacy,
SEO) plus denormalised `event_date`, `location` and `segment` **only where the album has no linked record**,
so a standalone album can still be filtered alongside linked ones.

### 10.2 Privacy is a first-class state, not a flag

`Draft → Scheduled → Published (public) | Private → Archived`

| | Public album | Private album |
|---|---|---|
| URL | `/gallery/{slug}` | `/g/{token}` — token is 32 random bytes, stored hashed |
| Indexing | Indexed, in sitemap | `noindex, nofollow`, absent from sitemap and search |
| Originals | `public_html/uploads/…` | `storage/private/galleries/…` — **outside the web root** |
| Derivatives | Static files, cached | Streamed by PHP after authorisation, `Cache-Control: private` |
| Password | — | Optional, `password_hash()`, attempts rate-limited per token+IP |
| Expiry | — | Optional date; afterwards returns 410 Gone |
| Downloads | Configurable | Configurable, default off |
| Access log | — | Every view and download: token, IP, user agent, timestamp |
| Revocation | — | Token regenerated; old links die immediately |

Private image bytes are served by a controller that (a) validates the token/session, (b) checks expiry and
revocation, (c) resolves the requested id to a path via the database — never by concatenating user input —
and (d) streams via `X-Sendfile` if `mod_xsendfile` is present, else `readfile()` with correct headers.
Path traversal is structurally impossible because a filesystem path never comes from the request.

Converting private → public **requires an explicit "customer has approved publication" checkbox** with the
approving user and timestamp recorded, and physically moves originals into the public tree.

### 10.3 Image pipeline

Upload → verify true MIME via `finfo` **and** `getimagesize()` (not the extension, not the client
Content-Type) → reject anything not JPG/PNG/WebP → strip the original filename entirely and generate a new
random one with a single safe extension (which defeats `photo.php.jpg` double-extension attacks outright) →
correct orientation from EXIF → strip EXIF **including GPS** from public derivatives, retain the original
untouched in private storage → generate `thumb 400w`, `medium 1000w`, `large 1800w` plus WebP siblings →
store originals separately from derivatives → serve with `srcset`/`sizes` and `loading="lazy"`.
Grids never load originals. GD is the default driver; Imagick is used if present.

**Disk is the real constraint.** A 200-photo event at 6 MB each is 1.2 GB of originals before derivatives —
shared hosting quotas will not absorb many of those. See **OQ-22**, which needs an early answer because it
may change where originals live.

---

## 11. Google Workspace email approach

### 11.1 The constraint

`unwinded.co.za` MX → Google Workspace. The website is on cPanel, which does **not** hold the mailboxes.
Mail sent through cPanel's local MTA would originate from an IP that Unwinded's SPF record does not
authorise, would fail DMARC alignment, and would land quotations in spam. **All transactional mail is
therefore relayed to Google over authenticated SMTP with TLS, via PHPMailer. The local `mail()` function is
never used.**

### 11.2 Recommended configuration, and a warning about the alternative

Google offers two relay paths:

- **SMTP relay (`smtp-relay.gmail.com:587`) authorised by sender IP.** *Not recommended here.* On shared
  cPanel the outbound IP is shared with other tenants and can be changed by the host without notice.
  Allow-listing it would authorise **strangers on the same server** to relay as `unwinded.co.za`, and a
  silent IP change breaks all mail at once.
- **Authenticated submission as a dedicated Workspace user** — `smtp-relay.gmail.com:587` *or*
  `smtp.gmail.com:587`, STARTTLS, with **"Require SMTP Authentication" enabled**. ✅ **Recommended.**
  Credentials, not network position, prove identity; an IP change is harmless.

Within that, authentication is configurable:

1. **`XOAUTH2` (preferred, long-term).** A Google Cloud service account or OAuth client with a stored
   *refresh token*; the app exchanges it for short-lived access tokens. No password exists to leak, and it
   is unaffected by any future tightening of password-based access.
2. **App Password** on a 2SV-enabled dedicated account (e.g. `no-reply@unwinded.co.za`), as a supported
   fallback. This is **not** the deprecated "less secure app access" path, which is not used anywhere.

`MAIL_AUTH_METHOD` (`login` | `xoauth2`) selects the driver, so switching is a config change rather than a
rewrite.

### 11.3 Configuration and secret handling

`.env` only, `0600`, outside the web root:
`MAIL_HOST` `MAIL_PORT` `MAIL_ENCRYPTION` `MAIL_USERNAME` `MAIL_PASSWORD` `MAIL_FROM_ADDRESS`
`MAIL_FROM_NAME` `MAIL_REPLY_TO` `MAIL_AUTH_METHOD` `MAIL_OAUTH_CLIENT_ID` `MAIL_OAUTH_CLIENT_SECRET`
`MAIL_OAUTH_REFRESH_TOKEN`.
Never in the database, never in the repository, never in `public_html`, never in JavaScript, never in a log.
The admin's mail-settings screen shows host/port/from/reply-to and a masked indicator for the credential;
it can *test* the connection but can never *display* or *store* the secret.

### 11.4 Sending model

Everything is **queued**. Application code writes to `email_queue`; cron drains a bounded batch every five
minutes; failures retry with exponential backoff (1, 5, 15, 60, 240 minutes) up to five attempts, then land
in a Failed state that raises a dashboard warning. Every attempt is logged (recipient, template, subject,
status, SMTP response, timestamp) — **never the body's personal data beyond what is needed to diagnose**.

`MAIL_MODE=live | log | catch_all` gives a development mode in which nothing can reach a real customer.

Both HTML and plain-text parts are generated for every message. Templates are **variable-substitution only**
against a per-template allow-list — `{{customer_name}}`, `{{booking_reference}}`, etc. No PHP, no
expressions, no includes, no callable is reachable from template content, and unknown placeholders are
stripped rather than passed through.

**Bulk newsletter sending is deliberately out of scope** for Workspace: Google's per-user daily send limits
and its anti-bulk policies make it the wrong tool, and a suspension would take down transactional mail too.
See **OQ-21**.

### 11.5 DNS, documented in DEPLOYMENT.md

SPF must authorise Google and nothing surprising (`v=spf1 include:_spf.google.com ~all`); DKIM must be
generated and published in Workspace Admin; DMARC starts at `p=none` with `rua` reporting, then moves to
quarantine once clean. Plus: Workspace Admin routing settings, allowed senders, TLS enforcement, a test-send
procedure, and a delivery-troubleshooting decision tree.

---

## 12. Proposed database entities and relationships

MySQL 8 / MariaDB 10.6+, InnoDB, `utf8mb4_0900_ai_ci`, foreign keys throughout, `DECIMAL(12,2)` for money,
`created_at`/`updated_at` everywhere, `deleted_at` where recovery matters.

The full field-level plan is in **`DATABASE.md`**. Summary of the 72 tables and the relationships that carry
real weight:

**Auth & admin (8):** `users` · `roles` · `permissions` · `role_permissions` · `user_roles` ·
`password_resets` · `login_attempts` · `activity_logs`

**Content (10):** `pages` · `page_revisions` · `page_sections` · `homepage_sections` · `menus` ·
`menu_items` · `experiences` · `settings` · `redirects` · `not_found_log`

**Packages (4):** `packages` · `package_features` · `package_extras` · `package_extra_links`

**Customers & quoting (7):** `customers` · `customer_consents` · `quote_requests` ·
`quote_request_attachments` · `quotes` · `quote_items` · `quote_notes` · `quote_status_history`

**Private bookings (6):** `private_bookings` · `booking_items` · `booking_extras` · `booking_notes` ·
`booking_status_history` · `booking_documents`

**Public events & ticketing (8):** `public_events` · `event_images` · `event_ticket_types` ·
`ticket_orders` · `order_items` · `ticket_reservations` · `tickets` · `ticket_checkins`

**Payments (6):** `payments` · `payment_allocations` · `payment_webhooks` · `payment_logs` · `refunds` ·
`eft_proofs`

**Galleries & media (7):** `media` · `media_usages` · `gallery_albums` · `gallery_images` ·
`gallery_access_tokens` · `gallery_access_logs` · `gallery_publish_consents`

**Operations (5):** `checklist_templates` · `checklist_template_items` · `event_checklists` ·
`checklist_items` · `staff_assignments`

**Marketing & comms (10):** `testimonials` · `faqs` · `faq_groups` · `enquiries` ·
`newsletter_subscribers` · `discount_codes` · `discount_code_usage` · `email_templates` · `email_queue` ·
`email_logs`

**System (3):** `migrations` · `scheduled_tasks` · `rate_limits`

### 12.1 The relationships that matter

```
customers ─1:N─ quote_requests ─1:N─ quotes ─1:N─ quote_items
                                       │
                                       └─1:1─ private_bookings ─1:N─ booking_items
                                                     │              booking_extras
                                                     │              booking_documents
                                                     │              booking_status_history
                                                     ├─1:N─ staff_assignments
                                                     ├─1:1─ event_checklists ─1:N─ checklist_items
                                                     └─0:1─ gallery_albums

public_events ─1:N─ event_ticket_types ─1:N─ order_items ─1:N─ tickets ─0:1─ ticket_checkins
       │                    │                     │
       │                    └─1:N─ ticket_reservations
       │              ticket_orders ─1:N─ order_items
       └─0:1─ gallery_albums

payments ─1:N─ payment_allocations ──→ private_bookings | ticket_orders   (polymorphic allocation)
   │
   └─1:N─ refunds

gallery_albums ─1:N─ gallery_images ──→ media
               ─1:N─ gallery_access_tokens ─1:N─ gallery_access_logs
```

### 12.2 Design decisions worth stating

- **Payments are not owned by bookings or orders.** A `payment` is a real-world money movement; a
  `payment_allocation` applies some or all of it to a payable. This is what makes deposits, part-payments,
  a single EFT covering two bookings, and partial refunds representable without ever editing history.
- **`payment_webhooks` has a unique index on `(provider, provider_event_id)`** — the entire idempotency
  guarantee rests on that one constraint.
- **`tickets` has a unique index on `ticket_uid`**, and `ticket_checkins` a unique index on `ticket_id`, so
  double check-in is impossible at the storage layer, not merely discouraged in code.
- **`event_ticket_types` holds `qty_available`, `qty_reserved`, `qty_sold`** with a `CHECK` constraint that
  reserved + sold never exceeds available. Overselling would require the database itself to fail.
- **Quote and booking line items snapshot name, description, unit price and tax rate.** They reference the
  package for reporting but never rely on it for money.
- **`settings` is key/value with a type column and a group**, cached to a file. Settings are content, not
  configuration — secrets stay in `.env`.
- **Soft deletes** on customer- and content-bearing tables. **Never** on `payments`, `refunds`, `tickets`,
  `activity_logs`, `payment_webhooks` — financial and audit records are immutable.
- **Indexes** on every foreign key, on all status columns, on `(event_date)` for bookings and events, on
  `slug` (unique), on `public_ref` (unique), on `email` for customers, and composite indexes on
  `(status, event_date)` for the dashboard's hot queries.

---

## 13. Principal security controls

### 13.1 Threats specific to this application, and the control for each

| # | Risk | Why it matters here | Control |
|---|---|---|---|
| 1 | **Price tampering** | Quote and checkout totals are money | Client sends ids and quantities only; totals recomputed server-side and compared; mismatch logged as an incident |
| 2 | **Webhook spoofing** | A forged webhook = free tickets | Signature/HMAC verification per provider, source validation, unique event id, insert-before-process |
| 3 | **Duplicate webhook delivery** | Providers retry by design | Unique `(provider, provider_event_id)`; conditional status transitions; whole confirmation in one transaction |
| 4 | **"Paid" inferred from the return URL** | The classic ticketing exploit | The return page *never* changes state. Only a verified webhook or a verified server-to-server poll does |
| 5 | **Overselling under concurrency** | Two buyers, one last seat | Single conditional `UPDATE` + `CHECK` constraint; no read-then-write |
| 6 | **IDOR** on quotes, orders, bookings, galleries | Records are reachable without a login | Random `public_ref` + a separate hashed token; `hash_equals()`; no sequential ids in URLs |
| 7 | **Private gallery enumeration / leakage** | Client photographs, reputational | 32-byte tokens stored hashed, originals outside web root, PHP-mediated delivery, `noindex`, sitemap exclusion, expiry, revocation, access logs |
| 8 | **Malicious upload → RCE** | Public upload of inspiration images | True-MIME verification, extension allow-list, filename regeneration, `php_flag engine off` + handler removal in `uploads/`, size caps, re-encode images |
| 9 | **Path traversal** on private files | `../../.env` | Filesystem paths are resolved from database ids only; no request value ever reaches a path |
| 10 | **XSS** — stored (admin content) and reflected | Rich text is admin-entered | Escape by default in views (`e/attr/js/url`); allow-list DOM sanitiser for rich text; CSP without `unsafe-inline` |
| 11 | **SQL injection** | Everywhere | PDO prepared statements exclusively, `PDO::ATTR_EMULATE_PREPARES=false`; identifiers (sort columns) from an allow-list |
| 12 | **CSRF** | Financial state changes | Per-session token on every non-GET request, `hash_equals()`, `SameSite=Lax` cookies |
| 13 | **Credential stuffing / brute force** | Admin login | `password_hash()` (bcrypt/argon2id), throttling per username **and** per IP, progressive delay, lockout, generic error text, all attempts logged |
| 14 | **Session fixation / theft on shared hosting** | cPanel `/tmp` is shared | Private `session.save_path` at 0700, regeneration on login and privilege change, absolute + idle timeout, `HttpOnly`/`Secure`/`SameSite` |
| 15 | **Privilege escalation** | Multiple admin roles | Server-side check on every route *and* record; a user cannot grant a permission they lack, edit their own roles, or touch a super-admin |
| 16 | **Password-reset link poisoning** | `HTTP_HOST` is attacker-controlled | Links built from `APP_URL` config; single-use, 60-minute, hashed tokens |
| 17 | **Email header injection** | User-supplied names in mail | PHPMailer address validation; CR/LF stripped from every header input |
| 18 | **Template injection via email templates** | Admins edit templates | Substitution against a per-template variable allow-list; no code evaluation path exists |
| 19 | **CSV injection in exports** | Reports open in Excel | Values beginning `= + - @ TAB CR` are prefixed with `'` on export |
| 20 | **Form spam** | Public quote/contact forms | Honeypot + submission-timing check + per-IP rate limit + optional captcha, all before any DB write |
| 21 | **Secret exposure** | `.env`, mail, gateway keys | Outside web root, 0600, `.htaccess` denial, `.gitignore`, never logged, never rendered, masked in the UI |
| 22 | **Information disclosure via errors** | Stack traces | `display_errors=0`, branded error pages with a reference code only, traces to `storage/logs` |
| 23 | **Clickjacking / MIME sniffing / mixed content** | | HSTS, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, CSP, `Permissions-Policy`, HTTPS forced |
| 24 | **Open redirect** | Post-login and post-payment returns | Redirect targets validated against an internal-path allow-list |
| 25 | **Insider / accidental damage** | Several admin users | Audit log on every sensitive action, soft deletes, confirmation dialogs, immutable financial records |
| 26 | **POPIA non-compliance** | SA law, personal data | Consent captured with timestamp and source, purpose-limited retention, export/erasure procedure, breach-notification runbook |

### 13.2 Baseline

`password_hash`/`password_verify` · server-side validation on every input · context-aware output escaping ·
soft deletes · database transactions on every financial and inventory operation · audit logging · rate
limiting on all sensitive endpoints · production error logging · security headers · least-privilege
database user (no `DROP`/`GRANT` in production) · dependency review before any addition.

---

## 14. Phased implementation checklist

Each phase ends with: what was built · every file created or modified · test steps · unresolved issues ·
`PROJECT_STATUS.md` updated · **then I stop and wait for your approval.**

| Phase | Scope | Exit criteria |
|---|---|---|
| **1. Requirements & architecture** *(this document)* | Architecture, structure, roles, modules, pages, ER plan, risks, plan, open questions | You approve; OQ-01…06 answered |
| **2. Foundation & database** | Structure, bootstrap, env, PDO, router, middleware, errors, logging, migration runner, all 72 migrations, seeds, base layouts, design tokens, docs | `bin/console migrate` builds a clean schema; rollback works; homepage renders a styled placeholder |
| **3. Auth & CMS foundation** | Login, logout, throttling, password reset, roles, permissions, users, policies, activity log, admin layout, shared components (tables, filters, flash, modals, forms) | An administrator logs in; permissions demonstrably block; every action is logged |
| **4. Public site & content** | Pages, revisions, scheduling, navigation, homepage sections, experiences, packages, FAQs, testimonials, contact, SEO, sitemap, redirects, full public design | Full site editable end-to-end by a non-technical user; Lighthouse ≥ 90; WCAG AA verified |
| **5. Media & galleries** | Media library, image pipeline, albums, public gallery, lightbox, **private galleries**, tokens, expiry, access logs, homepage integration | Private album is unreachable without its token, absent from the sitemap, expires, and revokes |
| **6. Quote management** | Multi-step form, admin triage, quotation builder, server-side calculations, VAT, discounts, versioning, PDF, emails, acceptance, conversion | Quote → accept → booking, with correct money to the cent, tested |
| **7. Private bookings** | Booking management, deposits, balances, scheduling, conflict detection, customers, documents, EFT + proof of payment | Deposit and balance tracked correctly; conflicts warn; history immutable |
| **8. Public events & ticketing** | Events, ticket types, capacity, reservations, checkout, discounts, orders, ticket + QR generation, mobile ticket, check-in | Concurrent-purchase test cannot oversell; duplicate check-in blocked |
| **9. Payments** ⚠️ | Gateway abstraction, chosen provider, webhooks, allocations, refunds, logs | **Blocked on OQ-05 — I will ask before starting.** Sandbox end-to-end, including replayed and forged webhooks |
| **10. Google Workspace email** | PHPMailer service, auth drivers, templates, queue, retries, logs, test tool, dev mode, DNS docs | Live test delivers, passes SPF/DKIM/DMARC, queue retries, failures surface |
| **11. Operations & reporting** | Checklists, templates, per-guest quantities, staff assignments, 15 reports, CSV, print, dashboard | Quantities compute from guest count; every report exports and respects permissions |
| **12. Security, testing & deployment** | Security review, permission matrix test, payment test, gallery privacy test, performance, docs, cPanel deployment, production verification | Full test suite green; production checklist signed off; live site verified |

Cumulative test coverage targets the 21 areas in §38 of the brief; nothing is reported as working until it
has actually been run.

---

## 15. Unresolved business rules requiring your decision

### 15.1 Blocking — needed before Phase 2 (they shape the schema)

| # | Question | Why it is blocking | Proposed default if you have no preference |
|---|---|---|---|
| **OQ-01** | Is Unwinded **VAT registered**? If so: VAT number, effective date, and are advertised prices VAT-**inclusive** or **exclusive**? | Determines columns on quotes, bookings, orders and every total. Retrofitting VAT into issued financial documents is expensive and error-prone. | Build the fields and a `none` mode now, switchable later without migration |
| **OQ-02** | **Deposit rule**: percentage or fixed? What percentage? Payable within how many days of accepting a quote? | Encoded in quote → booking conversion and reminder scheduling | 50%, due within 5 business days |
| **OQ-03** | **Quotation validity period** and what happens on expiry — auto-expire, or flag for follow-up? | Drives the expiry cron and status machine | Valid 14 days; auto-expire with a reminder at day 10 |
| **OQ-04** | **Cancellation & refund terms** for (a) private bookings and (b) public tickets. Are deposits non-refundable? Sliding scale by notice period? Are tickets refundable at all? ⚠️ Note: **CPA s17** gives SA consumers a right to cancel an advance booking subject to a *reasonable* cancellation fee — a blanket "no refunds" clause is likely unenforceable and should be reviewed by your attorney. | Refund logic, policy page, and legal exposure | Deposit non-refundable; balance refundable if cancelled >14 days out; tickets transferable but not refundable |
| **OQ-05** | **Payment provider** — PayFast, Paystack, Yoco, or another? *(I will ask again formally before Phase 9, but knowing now shapes the abstraction.)* | Each has a different webhook/verification model | None assumed — abstraction built to fit all three |
| **OQ-06** | **Group/Couple tickets**: one QR admitting N people, or N separate QR tickets? | Fundamentally different `tickets` shape and check-in flow | One ticket, `admissions = N`, with a partial check-in counter |

### 15.2 Needed before their own phase

| # | Question | Needed by |
|---|---|---|
| **OQ-07** | Package pricing: which of Essential / Signature / Bespoke are per-person vs fixed, and are prices shown publicly or "price on request"? | Phase 4 |
| **OQ-08** | Ticket reservation hold duration (proposed **15 minutes**)? | Phase 8 |
| **OQ-09** | Are attendee names required for every ticket, only for some types, or never? | Phase 8 |
| **OQ-10** | Are tickets **transferable** (name change) and can a purchaser do it themselves? | Phase 8 |
| **OQ-11** | Maximum discount an Event Manager may apply without an Administrator's approval? | Phase 6 |
| **OQ-12** | Do discount codes apply to **private quotations** as well as tickets? | Phase 6 |
| **OQ-13** | Minimum and maximum guest counts per package, and is there a **travel surcharge** beyond a radius? Which areas do you serve? | Phase 4 |
| **OQ-14** | How many events can run on the same day (facilitators, kit)? Should a conflict **warn** or **block**? | Phase 7 |
| **OQ-15** | Should the system offer a **waitlist** for sold-out public events? *(Not in the brief; commonly wanted.)* | Phase 8 |
| **OQ-16** | Should an **Event Manager see money** — quote totals, payment status, revenue reports — or only operational data? | Phase 3 |
| **OQ-17** | Are **facilitators CMS users with logins** (needed for check-in and checklists), or just names on an assignment? | Phase 3 / 11 |
| **OQ-18** | Do you need an **offline-capable check-in** mode for venues with poor signal? | Phase 8 |
| **OQ-19** | Do restaurant partnerships involve **commission or revenue share** that must be tracked and reported? | Phase 7 / 11 |
| **OQ-20** | Default per-guest quantities for the operations checklist (paint, brushes, canvases, aprons, cups…). | Phase 11 |
| **OQ-21** | **Newsletter**: capture + CSV export only, or actual bulk sending? *(Strong recommendation: capture here, send via Mailchimp/Brevo. Bulk sending through Google Workspace risks suspending the account that also carries your transactional mail.)* | Phase 10 |
| **OQ-22** | **Storage**: what is the hosting disk quota, and how many photos per event at what size? Originals may exceed a shared plan quickly — options are (a) store originals off-site, (b) keep only optimised versions, (c) auto-archive after N months. ⚠️ **Answer early — it can change where gallery originals live.** | Phase 5 |
| **OQ-23** | Default for a new album: **Private** or Public? Default private-gallery expiry (proposed 90 days) and downloads on or off by default? | Phase 5 |
| **OQ-24** | Where is **publication consent** captured — a checkbox on the quote form, in the contract, or recorded manually per album? | Phase 5 |
| **OQ-25** | **POPIA**: who is the designated Information Officer, what retention period applies to customer data, and do you have a data-subject request procedure? | Phase 4 (privacy policy) |

### 15.3 Operational confirmations

| # | Question |
|---|---|
| **OQ-26** | cPanel plan details: PHP 8.2+ available? Extensions `pdo_mysql`, `gd`, `mbstring`, `openssl`, `fileinfo`, `zip`, `curl`, `intl`? SSH/CLI PHP for cron? Disk and inode quota? |
| **OQ-27** | Is there an **existing unwinded.co.za site** with content, photographs, past bookings or a subscriber list to migrate? Existing URLs needing 301 redirects? |
| **OQ-28** | Do you want a **staging subdomain** (e.g. `staging.unwinded.co.za`) for approving each phase before it reaches production? *(Strongly recommended.)* |
| **OQ-29** | Do you have a **logo in vector format**, brand fonts, and licensed photography? Or should the design system carry defaults until assets arrive? |
| **OQ-30** | Business address, registration/company details, WhatsApp number, and the email addresses that should receive admin notifications. |

---

## 16. Proposed design system (for approval alongside the architecture)

Working tokens, offered so you can react to something concrete rather than adjectives:

| Token | Value | Use |
|---|---|---|
| `--c-blush` | `#E8C4C0` | Section washes, soft surfaces |
| `--c-blush-deep` | `#C98F88` | Accents, hover states |
| `--c-green` | `#6B7F6E` | Secondary brand, badges |
| `--c-green-deep` | `#3F4E42` | Headings on light, **7.9:1** on off-white ✅ |
| `--c-gold` | `#C9A227` | Rules, icons, small flourishes |
| `--c-gold-deep` | `#8A6D14` | Gold **text** — 5.1:1 ✅ (the light gold is **3.1:1** and is therefore restricted to large text, borders and icons) |
| `--c-cream` | `#FAF6F1` | Page background |
| `--c-ink` | `#2B2724` | Body text, **14.2:1** ✅ |

Type: an elegant serif/script (Cormorant Garamond or Italiana) for *selected* headings only — never for
body, never for buttons, never for anything a screen reader user needs read precisely; a clean humanist
sans (Inter or Karla) for everything else. Both self-hosted as woff2, subset to Latin.

Mobile-first, 8px spacing scale, generous whitespace, one card component, one button component with four
variants, `prefers-reduced-motion` respected on every transition, visible focus rings that are never
removed, and no text baked into images.

---

## Next step

**I am waiting for your approval before writing any code.**

The most useful reply answers **OQ-01 through OQ-06** (they shape the database schema) and flags anything in
this architecture you want changed. **OQ-22** and **OQ-26** are also worth an early answer — hosting quota
and PHP extension availability can change decisions in Phase 5 and Phase 2 respectively.

Once approved, Phase 2 delivers the project skeleton, configuration, routing, error handling, the complete
migration set, seed data, base layouts and the documentation files.

---

## 17. Resolved business rules — OQ-01 to OQ-06

*Resolved 2026-09-04.*

### OQ-01 — VAT: NOT registered
Unwinded is **not VAT registered**. No VAT is applied to quotes, bookings or orders.
The schema includes a `tax_rate` column on line-item tables and a `vat_mode` column on financial documents,
both defaulting to `none`/`0.00`. If Unwinded registers for VAT in future, an administrator can switch the
global VAT mode in Website Settings without a migration.

### OQ-02 — Deposit: 50%, due immediately / 5 business days before the event
- **Amount:** 50% of the accepted quotation total.
- **Deadline logic:** The system computes and stores **two deposit deadlines**:
  - `deposit_soft_deadline` = quote-acceptance date + 48 hours (prompt payment expected)
  - `deposit_hard_deadline` = event date − 5 business days (absolute latest)
  - The booking's displayed deadline is the **earlier** of the two.
- **Reminder schedule:** reminder email at soft deadline, escalation at hard deadline.
- **No booking is Confirmed until the deposit is received.** A booking stays `Awaiting Deposit`
  and can be cancelled by the administrator if neither deadline is met.

### OQ-03 — Quotation validity: 14 days, flag for follow-up on expiry
- Quotations are valid for **14 calendar days** from the date of issue.
- A reminder email is sent at **day 10**.
- On day 14, if no response: status → `Expired`; a dashboard flag and a notification email
  alert the assigned team member to follow up. The quotation is **not silently closed** —
  a human must decide whether to reissue or archive it.
- Reissuing creates a new quotation version (the expired one is preserved in history).

### OQ-04 — Refunds: processed within 10 business days of a refund request
- An administrator reviews and approves or declines a refund request.
- Once approved, the refund is processed and must be completed within **10 business days**.
- The system tracks: request date, approval date, processing deadline, completion date.
- **Cancellation fee tiers** (e.g. sliding scale by notice period, non-refundable deposit) are
  configurable in Website Settings by an Administrator — they are not hard-coded.
  *Note: CPA s17 applies; the default configuration should be reviewed with your attorney
  before going live.*
- A "Refund Due By" date is displayed on every approved refund in the payments module.

### OQ-05 — Payment provider: PayFast
PayFast is the selected provider.
Integration notes that shape the Phase 9 build:
- **Webhook mechanism:** ITN (Instant Transaction Notification) — a server-to-server POST.
- **Signature:** MD5 of alphabetically sorted key=value pairs + seller passphrase; verified
  server-side before any state change. The passphrase lives in `.env` only.
- **Return/cancel URLs:** used only to route the customer; they never trigger payment confirmation.
- **Sandbox:** full sandbox environment available for Phase 9 testing.
- **Payment methods supported:** credit/debit card, Instant EFT, SnapScan, Mobicred, etc.
- The payment-gateway abstraction layer will be built with PayFast as the first and only driver
  in v1; the interface is designed so that a second provider can be added as a new driver class
  without touching booking or ticketing code.

### OQ-06 — Group/couple tickets: separate QR ticket per admission
A ticket type with `admissions = 2` (e.g. "Couple Ticket") generates **2 individual ticket records**,
each with its own unique `ticket_uid` and QR code, at the time the order is confirmed.
- Each person presents their own ticket at the door.
- The order confirmation email lists all tickets and includes all QR codes.
- Check-in is per individual ticket; the system shows both tickets under the order for context.
- This simplifies the check-in flow (no partial-use state on a single ticket) at the cost of
  slightly larger order confirmation emails. Accepted trade-off.
