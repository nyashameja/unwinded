# PROJECT_STATUS.md — Unwinded CMS

**Last updated:** 2026-09-04
**Current phase:** Phase 1 — Requirements & Architecture (awaiting approval)
**Branch:** claude/unwinded-cms-architecture-71ytmv

---

## Phase status

| Phase | Description | Status |
|---|---|---|
| 1 | Requirements, architecture, design & planning | ✅ Complete — awaiting approval |
| 2 | Project foundation & database | ⬜ Not started |
| 3 | Authentication & CMS foundation | ⬜ Not started |
| 4 | Public site & content management | ⬜ Not started |
| 5 | Media & galleries | ⬜ Not started |
| 6 | Quote management | ⬜ Not started |
| 7 | Private bookings | ⬜ Not started |
| 8 | Public events & ticketing | ⬜ Not started |
| 9 | Payments | ⬜ Not started — blocked on OQ-05 (provider selection) |
| 10 | Google Workspace email | ⬜ Not started |
| 11 | Event operations & reporting | ⬜ Not started |
| 12 | Security, testing & deployment | ⬜ Not started |

---

## Completed in Phase 1

- [x] Business analysis and platform understanding documented
- [x] Application architecture designed (PHP 8.2 / MySQL 8, no framework, MVC-inspired)
- [x] cPanel-compatible folder structure specified (two layouts: preferred + fallback)
- [x] 35 public pages mapped with routes and data sources
- [x] 38 CMS modules defined with primary permission prefixes
- [x] 4 seeded roles and permission matrix defined
- [x] 7 customer journeys mapped
- [x] Quotation and private-booking workflow designed
- [x] Public-event ticketing workflow designed (with concurrency guarantees)
- [x] Event gallery workflow designed (public and private)
- [x] Google Workspace email approach defined (authenticated SMTP, XOAUTH2 or App Password)
- [x] 72-table database entity plan with key relationships
- [x] 26 security controls mapped to specific threats
- [x] 12-phase implementation checklist produced
- [x] 30 open business questions identified and categorised
- [x] Design system tokens proposed

## Files created in Phase 1

- `docs/PHASE-1-ARCHITECTURE.md` — the full Phase 1 deliverable
- `PROJECT_STATUS.md` — this file

---

## Pending decisions (blocking Phase 2)

1. **OQ-01** — VAT registration status and pricing model
2. **OQ-02** — Deposit percentage and payment deadline
3. **OQ-03** — Quotation validity period and expiry behaviour
4. **OQ-04** — Cancellation and refund terms
5. **OQ-05** — Payment provider (PayFast / Paystack / Yoco / other) — ask again before Phase 9
6. **OQ-06** — Group/couple tickets: one QR or multiple?

## Known issues / risks

- Disk quota (OQ-22) may require off-site storage for private gallery originals — answer needed before Phase 5.
- PHP extension availability on the cPanel host (OQ-26) must be confirmed before Phase 2.
- Newsletter bulk-sending needs a separate platform decision (OQ-21); system captures and exports only unless told otherwise.
- POPIA information-officer designation and retention policy needed for the privacy policy page (OQ-25).

---

## Testing status

No application code exists yet. Test plan is in `docs/PHASE-1-ARCHITECTURE.md §38`.

## Deployment status

Not deployed. cPanel host not yet confirmed.
