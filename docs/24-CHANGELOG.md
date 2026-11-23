# 24 — Changelog

Format: newest first; grouped by date/phase. Only significant changes (architecture, behaviour, schema, dependencies, security). Follows "Keep a Changelog" categories: Added / Changed / Fixed / Security / Removed.

## [Unreleased]
### Added
- `docs/27-LAND-SHARE-PROJECTS.md`: domain model, website mapping and future developer-ERP plan for land-share projects (planning only; no buyer personal data stored).
### Added
- `php artisan pcbd:mail-test {to}` sends one test email to verify SMTP settings.
### Added
- `PCBD_NOINDEX=true` keeps search engines out (robots.txt, meta robots, X-Robots-Tag) until launch; preflight warns while it is on. Tests: 173.
### Added
- Pre-launch preview mode: `PCBD_ALLOW_DEMO=true` lets DemoSeeder run on the production-mode server (visible demo banner, preflight warns instead of failing); `php artisan pcbd:demo-clear` removes all demo content and demo areas and leaves real records untouched. Test added (172).
### Added
- Docker/Cloudflare-Tunnel production stack (`docker-compose.prod.yml`, `deploy/`, `scripts/deploy-docker.sh`, `scripts/backup-docker.sh`, manual `deploy.yml` workflow for a self-hosted runner), read-only `scripts/server-audit.sh`; ADR-0018. Not yet built or run on the server.
### Added
- Optional staff email alert on new inquiries (Settings → "Email new inquiries to"); no message body in the mail; failures are logged and never lose the lead. Tests: 171.

## 2026-11-23 — Website QA and launch preparation
### Fixed
- Admin list pages crashed when tables had rows; developers could not be published (`published_at` migration); developer logo upload default; skeleton `public/robots.txt` overriding the app's; listing-page layout shift; contrast, tap-target, heading-order, ARIA, focus and keyboard issues found by Lighthouse/axe; CSP inline handler; avatars no longer fetched from a third party.
### Added
- Self-hosted Inter/Fraunces fonts with preload and fallback metrics; branded accessible pagination; report-only CSP on public pages; admin dashboard overview (real counts, permission-aware); local initials avatar; ৳ formatting in admin tables; `tests/e2e/` browser QA scripts; `docs/26-LAUNCH-CHECKLIST.md`; gzip/cache guidance (measured).
- Tests: 169 (admin lists with real rows, media manager, developer publishing, CSP, preflight).

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
