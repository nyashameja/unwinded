# Unwinded — Website & CMS

Sip & Paint experience website and content management system for Unwinded (South Africa).

## Technology

- **PHP 8.2+** — no framework, MVC-inspired architecture
- **MySQL 8** — all monetary values as integer cents, never floats
- **Apache** with mod_rewrite — front controller pattern
- **Vanilla JS** — no build step required after deployment
- **PHPMailer** — all transactional email
- **PayFast** — South African payment gateway (ITN webhooks)
- **Composer** — vendor directory committed; no Composer needed on production host

## Quick Start (Local Development)

### Prerequisites

- PHP 8.2+
- MySQL 8
- Apache with mod_rewrite
- Composer (for updating dependencies only)

### Setup

```bash
# 1. Copy environment file
cp .env.example .env

# 2. Edit .env with your local database credentials and settings
nano .env

# 3. Generate an application key
php bin/console key:generate

# 4. Create the database schema
php bin/console migrate

# 5. Seed the database
php bin/console db:seed

# 6. Visit http://localhost/admin to log in
```

### Default Admin Credentials

Set `SEED_ADMIN_EMAIL` and `SEED_ADMIN_PASSWORD` in `.env` before seeding,
or the seeder defaults to `admin@unwinded.co.za` / `ChangeMe!2024`.

**Change the password immediately after first login.**

## Project Structure

```
├── app/
│   ├── Controllers/        Public, Admin, API, Webhook controllers
│   ├── Core/               Framework: Router, DB, Auth, CSRF, Session…
│   ├── Mail/               Mailable classes
│   ├── Middleware/         SecurityHeaders, Auth, CSRF, Guest
│   ├── Models/             Data access models
│   ├── Payments/           PayFast integration
│   ├── Policies/           Record-level authorization
│   ├── Repositories/       Data access layer
│   ├── Services/           Business logic
│   ├── Support/            Value objects: Money, Ref, Token, Clock…
│   ├── Tasks/              Scheduled task handlers
│   ├── Validators/         Input validation
│   └── Views/              PHP templates (layouts, public, admin, emails, errors)
├── bin/
│   └── console             CLI entry point
├── bootstrap/              App bootstrap, path registry, helpers
├── config/                 Configuration files (read from .env)
├── database/
│   ├── migrations/         72-table schema migrations (0001–0016)
│   └── seeds/              Data seeders
├── docs/
│   └── PHASE-1-ARCHITECTURE.md
├── public/                 Web root (maps to public_html on cPanel)
│   ├── index.php           Front controller
│   ├── .htaccess
│   ├── assets/             CSS, JS, fonts, images
│   └── uploads/            User-uploaded public files
├── routes/                 web.php, admin.php, api.php, webhooks.php
├── storage/
│   ├── cache/
│   ├── logs/
│   ├── sessions/
│   ├── temp/
│   └── private/            Gallery originals, documents (outside web root)
└── vendor/                 Composer dependencies (committed)
```

## CLI Commands

```bash
php bin/console migrate               # Run pending migrations
php bin/console migrate:rollback [n]  # Roll back N migrations
php bin/console migrate:fresh         # Drop all + re-migrate (local only)
php bin/console db:seed [Seeder]      # Run all seeds or a specific one
php bin/console key:generate          # Generate APP_KEY value
php bin/console schedule:run          # Run due scheduled tasks
php bin/console user:create           # Interactively create an admin user
```

## Architecture Notes

See [`docs/PHASE-1-ARCHITECTURE.md`](docs/PHASE-1-ARCHITECTURE.md) for the full architecture document.

Key decisions:
- Money stored as **integer cents** — `Money` class prevents float arithmetic
- Public references use **Crockford Base32** (`UNW-B-7F3K9XQ2TB`) — never sequential IDs in URLs
- Tokens stored only as **SHA-256 hashes** — plaintext exists once in the URL
- PayFast webhooks are **idempotent** — `UNIQUE KEY(provider, provider_event_id)`
- Ticket stock uses **atomic SQL UPDATE** — overselling impossible at DB level
- Sessions stored in **private directory** (not shared `/tmp`)
- CSP headers — **no `unsafe-inline`**
- Private gallery files served by **PHP after auth check** — never directly accessible

## Implementation Phases

| Phase | Status | Description |
|-------|--------|-------------|
| 1 | ✅ Complete | Architecture & planning |
| 2 | ✅ Complete | Foundation: framework, migrations, seeds, console, views |
| 3 | Pending | Admin auth: login, password reset, profile |
| 4 | Pending | Settings, media library, content pages |
| 5 | Pending | Public website (all 35 pages) |
| 6 | Pending | Quotes & bookings CMS modules |
| 7 | Pending | Public events & ticket sales |
| 8 | Pending | Payments (PayFast + EFT) |
| 9 | Pending | Gallery: public + private with QR tokens |
| 10 | Pending | Marketing: testimonials, FAQs, enquiries, newsletter |
| 11 | Pending | Operations: checklists, staff, reports |
| 12 | Pending | QA, security audit, launch |

## License

Proprietary — all rights reserved. Not open source.
