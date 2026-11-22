# 04 — Development Roadmap

Phases are sequential. A phase is closed only when its Definition of Done in [23-ACCEPTANCE-CRITERIA](23-ACCEPTANCE-CRITERIA.md) is met and [19-PROGRESS-TRACKER](19-PROGRESS-TRACKER.md) is updated. Branch names per [22](22-DEVELOPMENT-WORKFLOW.md).

Common to all phases — **Definition of Done:** implementation complete, validation + authorization in place, tests added and run green, Pint clean, responsive/accessible UI verified, docs/tracker/changelog updated, no known critical bugs.

---
## Phase 0 — Product Planning & Documentation ✅
**Goal:** Complete blueprint. **Scope:** repo inspection, docs 00–24, CLAUDE.md, README. **Dependencies:** none. **DB changes:** none. **Tests:** doc consistency review. **Risks:** over-planning. **Deliverables:** docs/, CLAUDE.md, README.md. **DoD:** all docs written and cross-checked; no code.

## Phase 1 — Application Foundation  (`feat/project-foundation`, `feat/authentication`)
**Goal:** A running, tested Laravel app with auth, admin shell, roles, design system and layouts.
**Scope:** Laravel install (version verified), PostgreSQL config, `.env.example`, Spatie Permission, Filament panel at `/admin`, auth (login/register/reset/verify), seed roles/permissions (Super Admin, Admin, Property Manager, Sales Manager, Sales Agent, Content Manager, Buyer), settings store, audit logging basics, Tailwind design tokens, public layout (header/footer/nav), error pages, Pest + Pint, CI workflow.
**Dependencies:** Phase 0.
**Tasks:** scaffold → config → DB → auth → roles/permissions + policies base → Filament panel + access control → settings → layouts/nav/design tokens → testing infra → CI → docs.
**DB changes:** users (+phone, status), Spatie tables, settings, audit_logs, jobs/failed_jobs/cache/sessions, notifications.
**UI pages:** home placeholder (real layout), login/register/reset, account stub, `/admin` dashboard shell, 403/404/419/500.
**Backend:** `App\Enums\RoleName`, `RolesAndPermissionsSeeder`, `SettingsService`, `AuditLogger`, panel provider with `canAccessPanel`.
**Tests:** auth flows, role gating of `/admin`, settings, audit log written, security headers.
**Acceptance:** see 23 §Phase 1. **Risks:** Filament/Laravel version mismatch → mitigate by verifying before install (ADR-0002).
**Deliverables:** working app skeleton.

## Phase 2 — Property & Project Management  (`feat/property-management`, `feat/project-management`)
**Goal:** Admin can fully manage supply data.
**Scope:** locations hierarchy, amenities, property types/categories, developers, projects, properties, media (images, brochure, floor plans) with private document storage, units & unit types, verification, featured-until, status workflow, slugs, SEO fields.
**Dependencies:** Phase 1.
**DB:** locations, developers, property_types, amenities, projects, project_units(+unit_types), properties, media (Spatie Media Library or own `media` table — ADR-0006), floor_plans, pivots, verifications.
**UI:** Filament resources and relation managers.
**Backend:** enums, policies, `PropertyService::publish()`, slug service, price-per-sqft accessor, image conversions.
**Tests:** CRUD authorization per role, publish rules (cannot publish without required fields), verification recorded, featured expiry scope.
**Risks:** media complexity → use proven package, limit conversions.

## Phase 3 — Public Website & Discovery  (`feat/public-property-listings`, `feat/property-search`)
**Goal:** Fast, SEO-friendly public site showing real published data.
**Scope:** homepage, listing + filters + search (PostgreSQL FTS/ILIKE + indexes), details, projects, developers, locations, CMS pages, blog (P1), sitemap/robots/schema/breadcrumbs, inquiry form → lead (minimal lead table arrives here as dependency of FR-301, ADR-0007) plus a basic read-only admin inquiry list so no lead is lost before Phase 5 delivers the full CRM UI.
**Dependencies:** Phase 2.
**DB:** pages, faqs, blog tables (P1), leads (minimal columns), contact_inquiries merged into leads.
**Tests:** filters, sort, pagination, only-published visibility, SEO output, inquiry validation + rate limit, N+1 guard, accessibility smoke.
**Risks:** search performance → indexes + EXPLAIN review.

## Phase 4 — Buyer & Seller Experience  (`feat/buyer-account`)
**Scope:** account area, favorites, saved searches, compare, recently viewed, property submission with moderation, sell/rent/valuation/assistance forms, newsletter consent capture.
**DB:** favorites, saved_searches, recently_viewed, newsletter_subscribers, property_reports; submissions reuse `properties` (ADR-0009) ([10](10-DATABASE-ARCHITECTURE.md)).
**Tests:** ownership authorization (users only see own data), moderation gate, compare limit, upload validation.

## Phase 5 — CRM & Lead Management  (`feat/lead-management`, `feat/site-visits`)
**Scope:** lead sources, pipeline (list + kanban-lite), assignment, notes, activity timeline, status history, follow-ups, site visits (+calendar view), notifications (database + mail), dashboard widgets, duplicate handling, consent/retention tools.
**DB:** lead_sources, lead_notes, lead_activities, lead_status_histories, follow_ups, site_visits.
**Tests:** lifecycle transitions, assignment visibility (agents see only assigned), follow-up reminders (scheduler), visit status machine, PII access control.

## Phase 6 — Sales & Commission  (`feat/sales-commission`)
**Scope:** deals, bookings, sales, commission agreements, calculation, approval, payments (partial), outstanding, reports, dashboard finance widgets.
**DB:** deals, bookings, commission_agreements, commissions, commission_payments.
**Tests:** calculation unit tests (percentage/fixed, rounding), payment sums never exceed due, status transitions, finance permission gating.

## Phase 7 — Marketing & Analytics  (`feat/marketing-analytics`)
**Scope:** cookie-consent banner (required before any analytics tag loads), campaigns, UTM capture + attribution, tracking events (WhatsApp/phone/inquiry), GA4 + Meta Pixel via settings with consent gating, campaign reports, landing pages (simple template-based).
**DB:** campaigns, campaign_leads, landing_pages, tracking_events, campaign_costs.

## Phase 8 — Partner & Agent Features
**Scope:** developer portal (view own projects/leads, update availability), agent management, partner access, assigned leads, performance reports.
**DB:** agents, owner/developer user links, partnership_records (if not earlier).

## Phase 9 — Production Readiness
**Scope:** security audit, QA pass, performance, SEO audit, deployment, backups + restore test, monitoring/alerts, legal pages review, launch checklist, content seeding (real data only).

---
## Timeline guidance (solo developer, indicative)
P1 2 wks · P2 3 wks · P3 4 wks · P4 2 wks · P5 3 wks · P6 2 wks · P7 2 wks · P8 3 wks · P9 2 wks. Launch candidate after Phases 1–3 + 5 + 9 (≈ 14 wks); Phases 4, 6 follow. Estimates are planning aids, not commitments.

## Dependency graph
0 → 1 → 2 → 3 → 5 → 6; 3 → 4; 5 → 7; 6,7 → 8; all → 9 (hardening runs continuously; formal pass last).
Note: Phase 3 introduces a *minimal* `leads` table so inquiries are never lost; Phase 5 extends it (ADR-0007).
