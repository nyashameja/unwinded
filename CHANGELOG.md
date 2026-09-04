# Changelog

All notable changes to Unwinded are documented here.

## [Unreleased]

### Phase 2 — Foundation & Schema

**Added**
- Core framework: Container, Config, Database (PDO wrapper), Logger, Request, Response, Session, CSRF, Auth, RateLimiter, EventBus, Router, View, Migrator
- Support classes: Money (integer-cents), Ref (Crockford Base32), Token (SHA-256 hashed), Str, Csv, Clock
- Middleware: SecurityHeaders, Auth, CSRF, Guest
- Bootstrap: paths, helpers, app bootstrap pipeline
- Configuration: app, database, mail, payments, uploads, security, SEO
- Database migrations 0001–0016 covering all 72 tables
- Database seeds: roles/permissions, admin user, settings, email templates, homepage sections, scheduled tasks
- Routes: public web, admin CMS, API, PayFast webhook
- View layouts: public, admin, email
- Error pages: 404, 403, 405, 500, 503
- Admin login view and dashboard placeholder
- Public homepage placeholder
- CLI console (`bin/console`) with migrate, seed, key:generate, schedule:run, user:create
- Composer dependencies: PHPMailer, QR code, dotenv, OAuth2 Google

## [Phase 1] — Architecture

**Added**
- Full architecture document (`docs/PHASE-1-ARCHITECTURE.md`)
- 72-table database plan
- 38 CMS modules defined
- 35 public pages mapped
- Security architecture (26 controls)
- 12-phase implementation checklist
