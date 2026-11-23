# 23 — Acceptance Criteria

> **Phase numbering note (2026-11-23):** phase tags in this document use the original numbering. The website-first roadmap in [04](04-ROADMAP.md) supersedes it — see the mapping table there (e.g. CRM = Phase 8 now).

Global DoD applies to every phase: tests green and actually run; Pint clean; validation + authorization present; responsive & accessible UI verified; docs/tracker/changelog updated; no known critical bug; migrations reviewed (fresh + rollback).

## Phase 0 — Planning & Documentation
- [x] Docs 00–24, CLAUDE.md, README exist with substantive content (no placeholders).
- [x] Documents cross-reference consistently; roadmap, MVP and DB docs agree.
- [x] Review pass recorded in tracker; no code added.

## Phase 1 — Foundation
- [x] `composer install && npm ci && npm run build && php artisan migrate --seed` works on a clean PG database following README.
- [x] Versions recorded in ADR-0002; `.env.example` complete; `.env` not committed.
- [x] Register/login/logout/reset/verify work; throttling active; suspended users blocked.
- [x] Roles and permissions seeded idempotently; buyer default role.
- [x] `/admin` accessible only to admin-panel roles; others 403; Super Admin/Admin 2FA enforced via middleware (ADR-0008); full TOTP enrolment click-through to be exercised manually before Phase 9.
- [x] Settings service + admin page; audit log records key events and is viewable.
- [x] Public layout (header/nav/footer), design tokens, error pages; checked at 375 and 1280 px (no horizontal overflow); skip link, focus styles. *(Self-hosted fonts and `/dev/styleguide` deferred — see backlog.)*
- [x] Baseline security headers, CSRF and session configuration in place. *(CSP deferred to Phase 9 — tracked.)*
- [x] Pest + Pint + CI workflow; test suite green.

## Phase 2 — Property & Project Management
- [ ] CRUD for locations, types, amenities, developers, projects, units/unit types, properties, floor plans, media.
- [ ] Publish validators enforce completeness; verification creates record; featured-until works; expiry job.
- [ ] Private docs not publicly accessible (tested).
- [ ] Policies per role tested; audit entries for price/verification/publish.
- [ ] Slug generation + history; Larastan baseline passes.

## Phase 3 — Public Website & Discovery
- [ ] All P0 pages live with real published data only; empty sections hidden.
- [ ] Filters/sort/search/pagination verified by tests; URL state shareable; query count budget met.
- [ ] Inquiry form → lead with consent, UTM, honeypot, rate limit; basic admin lead list available.
- [ ] SEO: meta, canonical, OG, JSON-LD (valid), sitemap, robots; filtered pages noindex.
- [ ] Lighthouse mobile: Performance ≥ 85, Accessibility ≥ 95, SEO ≥ 95 on home/listing/detail (measured, recorded).
- [ ] Static/legal pages present (legal marked pending review).

## Phase 4 — Buyer & Seller Experience
- [ ] Favorites, saved searches, compare (≤ 4), recently viewed, inquiry/visit history work and are owner-scoped (IDOR tests).
- [ ] Property submission goes through moderation; pending items never public.
- [ ] Sell/rent/valuation/assistance requests create leads.
- [ ] Newsletter double opt-in + unsubscribe.

## Phase 5 — CRM & Lead Management
- [ ] Full lifecycle with enforced transitions/reasons; history + activity timeline.
- [ ] Assignment, priority, notes, follow-ups, reminders (scheduler tested).
- [ ] Site visit state machine, reminders, feedback; dashboard widgets show real data.
- [ ] Sales Agents see only assigned data; masked-contact roles audited; export permission + audit.
- [ ] Duplicate handling and anonymisation tool tested.

## Phase 6 — Sales & Commission
- [ ] Deals/bookings/sales workflow transactional; unit/property + lead state consistent.
- [ ] Agreements stored; calculation unit-tested incl. rounding; no default rate anywhere (grep test).
- [ ] Payments append-only; overpayment rejected; outstanding correct; status derived.
- [ ] Finance permissions tested; reports reconcile with ledger.

## Phase 7 — Marketing & Analytics
- [ ] UTM capture and attribution to campaigns; tracking events stored minimally.
- [ ] GA4/Meta Pixel only after consent (tested); IDs from settings.
- [ ] Reports show only recorded data; cost per lead only when spend exists.

## Phase 8 — Partner & Agent Features
- [ ] Developer/agent portal scoped correctly (tests); limited lead fields; availability updates moderated.
- [ ] Performance reports accurate.

## Phase 9 — Production Readiness
- [ ] Security checklist ([14](14-SECURITY-AND-PRIVACY.md) §7) complete; `composer audit` clean.
- [ ] Full test suite + browser smoke green; accessibility audit; performance targets met.
- [ ] Backup + restore drill done; monitoring/alerts live; deployment documented and rehearsed on staging.
- [ ] Legal pages reviewed; real content loaded; launch checklist ([16](16-DEPLOYMENT-AND-INFRASTRUCTURE.md) §10) signed off.
