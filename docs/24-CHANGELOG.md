# 24 — Changelog

Format: newest first; grouped by date/phase. Only significant changes (architecture, behaviour, schema, dependencies, security). Follows "Keep a Changelog" categories: Added / Changed / Fixed / Security / Removed.

## [Unreleased]

## 2026-11-23 — Website-first: Phases 3–7
### Added
- Public site: home (animated hero, smart search, featured/latest/projects/locations/developers/blog/CTAs), properties (filters, sort, grid/list, saved searches), property details (gallery + lightbox, specs, similar, inquiry, visit, report), projects, developers, locations, services, about, contact, FAQ, blog, legal pages, sell/rent-out/valuation/assistance/submit-property forms, search with autosuggest, favorites, compare, sitemap + robots, JSON-LD, breadcrumbs.
- Buyer features: guest+member favorites (merge on login), compare (≤4), recently viewed, inquiries and site-visit requests (leads), account area, saved searches, owner submissions moderated as `pending_review`.
- Lead capture (`CreateLead`): consent, UTM attribution, honeypot, time trap, rate limits, dedupe; admin Inquiries inbox, FAQ and Blog resources; permissions `leads.manage`, `content.manage`.
- Deployment: `pcbd:preflight`, `scripts/deploy.sh`, `scripts/backup-db.sh`, `.env.production.example`, `docs/25-DEPLOYMENT-GUIDE.md`.
### Changed
- Public disk URLs are relative (`/storage`); `Location::fullName()` is "Area, City".

## 2026-11-23 — Phase 2: Property & Project Foundation
### Added
- Roadmap re-planned website-first (ADR-0016); custom media layer replaces Spatie Media Library, which needs PHP 8.4 (ADR-0017).
- Schema: locations, property_types, amenities, developers, projects, project_unit_types, project_units, properties, amenity pivots, media, verifications, slug_histories (pg_trgm, CHECK constraints).
- Admin (Filament): Properties, Projects (+ unit types, units), Developers, Locations, Property Types, Amenities; media uploads; publish/unpublish, verify/revoke, feature actions; permissions `listings.*`, `catalog.manage`.
- `ListingPublisher` publish gate, `ImageProcessor` (GD, WebP, EXIF stripped), `ReferenceDataSeeder`, `DemoSeeder` (demo artwork, production-guarded).

## 2026-11-22 — Phase 1: Application Foundation
### Added
- Laravel 13 application scaffold (PHP 8.3, PostgreSQL 16), Filament 5 admin at `/admin`, Spatie Laravel Permission 8, Pest 4, Pint, Tailwind 4 + Alpine (ADR-0002).
- Public auth (register, login with throttling, logout, password reset, email verification); registrants receive only the `buyer` role.
- Roles (super-admin, admin, property-manager, sales-manager, sales-agent, content-manager, buyer) and Phase-1 permissions via idempotent `RolesAndPermissionsSeeder`; `UserPolicy` (only Super Admin may manage Super Admins).
- Admin: Users resource (role assignment guarded server-side), Settings page (contact/social/analytics/SEO/SLA), read-only Audit Log viewer; staff panel access requires active status + verified email + staff role.
- App-based 2FA via Filament MFA, enforced for Super Admin/Admin by `EnforceAdminMfa` (ADR-0008); `pcbd:create-super-admin` command (no default credentials).
- `SettingsService` (cached), `AuditLogger` (append-only, hashed IPs), login auditing for staff.
- Public layout (header, mobile drawer, footer), design tokens (docs/08), UI components (`x-ui.button`, `x-ui.input`, `x-ui.flash`), error pages (403/404/419/429/500/503), baseline security headers.
- Migrations: user profile fields (phone, status, last_login_at, locale, MFA columns, soft deletes, case-insensitive unique email), `settings`, `audit_logs`, Spatie tables, notifications.
- GitHub Actions CI (Pint, migrate fresh + rollback, tests, `composer audit`) and Dependabot config.
- 46 automated tests (auth, admin access, roles/permissions, settings/audit/users, public site).
### Changed
- Removed Laravel skeleton extras (AGENTS.md, upstream CHANGELOG/workflows) and the Vite font-download plugin (fonts will be self-hosted).

## 2026-11-22 — Phase 0: Planning & Documentation
### Added
- Root `CLAUDE.md` project rules for all Claude Code sessions.
- `README.md` with project overview, stack, doc index, status and roadmap.
- Documentation set `docs/00`–`docs/24`: overview, business strategy, requirements, MVP scope, roadmap, sitemap, journeys, roles/permissions, design system, technical architecture, database architecture (ERD + phased schema), module specs, SEO/marketing, lead & sales workflow, security/privacy, testing, deployment, SaaS future, ADRs (0001–0015), progress tracker, backlog, risks/assumptions, development workflow, acceptance criteria, changelog.
### Notes
- No application code, packages or migrations were created in this phase (by design).
