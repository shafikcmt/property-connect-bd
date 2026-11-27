# 18 — Decisions & ADRs

Format: ID · Date · Status (Accepted / Proposed / Superseded) · Context · Decision · Alternatives · Consequences. Never silently change a decision: add a new ADR that supersedes the old one.

---
### ADR-0001 — Unified Laravel monolith, server-rendered
- **Date:** 2026-11-22 · **Status:** Accepted
- **Context:** Solo/small team, SEO-critical public site, limited budget.
- **Decision:** One Laravel app: Blade + Livewire + Alpine + Tailwind for public/account; Filament for admin; no separate SPA/API/microservices.
- **Alternatives:** Laravel API + Next/Nuxt SPA; Inertia/React; microservices.
- **Consequences:** Fast delivery, one deploy, good SEO by default; limited to Livewire interactivity patterns; a public API (if ever needed) is added later deliberately.

### ADR-0002 — Framework versions verified at install time
- **Date:** 2026-11-22 · **Status:** Accepted (finalised 2026-11-22, Phase 1)
- **Outcome (verified via Packagist + install + green test suite):** PHP 8.3.6 · Laravel 13.43 · Filament 5.12 (includes Livewire 4.x) · Spatie Laravel Permission 8.5 · Pest 4.7 (PHPUnit 12) · Pint · Tailwind CSS 4 + Vite 8 · Alpine.js (npm) for public-site interactivity · PostgreSQL 16. Exact resolved versions are in `composer.lock` / `package-lock.json`.
- **Context:** Phase 0 has no code; package compatibility (Laravel × PHP × Livewire × Filament × Spatie) must be verified, not assumed. Env: PHP 8.3, Node 22, PostgreSQL 16.
- **Decision:** In Phase 1 install the latest stable Laravel supported by PHP 8.3 **and** by Filament and Spatie Permission; record exact versions here before continuing.
- **Alternatives:** Pin to an older LTS for stability.
- **Consequences:** Possible need to choose a slightly older Laravel major if Filament lags. Record outcome.

### ADR-0003 — Pest for testing
- **Date:** 2026-11-22 · **Status:** Accepted (confirmed Phase 1: Pest 4.7 installed; tests run against PostgreSQL `pcbd_testing`)
- **Decision:** Pest + Livewire/Filament testing helpers; PHPUnit remains the engine. **Alternatives:** plain PHPUnit. **Consequences:** concise tests; one more dev dependency.

### ADR-0004 — PostgreSQL as the only database
- **Date:** 2026-11-22 · **Status:** Accepted
- **Context:** Needs FTS, trigram search, partial indexes, check constraints, jsonb.
- **Decision:** PostgreSQL 16 in all environments incl. tests. **Alternatives:** MySQL/MariaDB (cheaper on shared hosting), SQLite for tests. **Consequences:** Hosting must offer PostgreSQL (R-05); no SQLite-only test shortcuts.

### ADR-0005 — No SaaS/multi-tenancy/billing in MVP
- **Date:** 2026-11-22 · **Status:** Accepted
- **Decision:** Defer all tenancy; adopt only "SaaS-ready habits" ([17](17-SAAS-FUTURE-ARCHITECTURE.md)). **Consequences:** a later migration will be needed; accepted trade-off for speed and simplicity.

### ADR-0006 — Spatie Media Library for media
- **Date:** 2026-11-22 · **Status:** Superseded by ADR-0017 (PHP 8.3 incompatible)
- **Decision:** Use `spatie/laravel-medialibrary` (conversions, collections, private disk) with Filament's Spatie media plugin. **Alternatives:** custom `media` table + Intervention Image; Filament FileUpload only. **Consequences:** less custom code, proven; adds dependency and a `media` table. Fallback: custom if incompatible.

### ADR-0007 — Contact/inquiry submissions are leads
- **Date:** 2026-11-22 · **Status:** Accepted
- **Context:** Separate `contact_inquiries` table would duplicate CRM data.
- **Decision:** All public forms create `leads` with `request_type`; minimal `leads` table created in Phase 3, extended in Phase 5. **Consequences:** single pipeline; Phase 3 must keep lead schema forward-compatible; admin UI for leads arrives Phase 5 (until then inquiries are viewable via a basic read-only list in Phase 3 to avoid lost leads).

### ADR-0008 — Auth approach & admin 2FA
- **Date:** 2026-11-22 · **Status:** Accepted (finalised Phase 1)
- **Decision:** Public auth = small first-party controllers + Blade views (login, register, password reset, email verification), no starter-kit/Jetstream dependency. Admin auth = Filament panel login with **Filament's native app-based MFA** (TOTP + recovery codes, `users.app_authentication_*` encrypted columns). Because Filament's "required" flag is panel-wide and resolved at boot, per-role enforcement (Super Admin/Admin) is done by `App\Http\Middleware\EnforceAdminMfa`, which redirects un-enrolled privileged users to the profile page (where MFA is set up). Toggle: `PCBD_ADMIN_MFA_REQUIRED` (default true; false only in the test environment).
- **Consequences:** two login screens sharing one `users` table/guard; staff accounts also need a verified email and `active` status (`User::canAccessPanel`). The complete TOTP enrolment flow is Filament's own and has been verified only up to the enforcement redirect in automated tests (see tracker).

### ADR-0009 — Owner submissions reuse `properties`
- **Date:** 2026-11-22 · **Status:** Accepted
- **Decision:** Submitted properties are `properties` rows with `publish_status=pending_review` and `submitted_by`; private owner contact columns; listing reports in `property_reports`. **Alternatives:** separate `property_submissions` table with copy-on-approve. **Consequences:** no data copy/sync bugs; strict scoping needed so pending rows never leak publicly (global guard via `published()` scope + tests).

### ADR-0010 — Status fields as string + PHP enum + CHECK
- **Date:** 2026-11-22 · **Status:** Accepted
- **Decision:** `string` columns, backed enums in `app/Enums`, DB CHECK for critical machines. **Alternatives:** native PG enums (hard to alter). **Consequences:** easy migrations; discipline needed to keep enum and CHECK aligned (test asserts).

### ADR-0011 — No Redis, no Docker requirement in MVP
- **Date:** 2026-11-22 · **Status:** Accepted
- **Decision:** DB queue/cache/session drivers; Docker optional for local dev. **Revisit when:** queue latency or cache load measured as a problem.

### ADR-0016 — Website-first reprioritisation
- **Date:** 2026-11-23 · **Status:** Accepted (owner directive)
- **Context:** The founder wants the complete public website live before any CRM/sales/SaaS software.
- **Decision:** Reorder phases (docs/04): 2 Property & Project Foundation → 3 Public Website → 4 Buyer Experience → 5 Premium UI/Animation → 6 QA → 7 Deployment → launch → CRM and the rest. A minimal forward-compatible `leads` and `site_visits` table plus a basic admin list ship pre-launch so inquiries are never lost (extends ADR-0007).
- **Alternatives:** keep original order (CRM before launch). **Consequences:** faster launch; lead handling is manual/basic at launch; Phase 8 later adds the full pipeline over the same tables (no data migration).

### ADR-0017 — Custom media layer instead of Spatie Media Library (supersedes ADR-0006)
- **Date:** 2026-11-23 · **Status:** Accepted
- **Context:** `spatie/laravel-medialibrary` (11.23.x) requires PHP ^8.4; project runs PHP 8.3 (ADR-0002) and the GD extension is available.
- **Decision:** Own `media` table (polymorphic, collection, disk, path, alt, caption, sort) and `ImageProcessor` (GD) generating WebP derivatives (480/960/1600 px); public images on the `public` disk, private documents on `local`. Admin upload via Filament FileUpload in a relation manager.
- **Alternatives:** upgrade PHP to 8.4; Intervention Image. **Consequences:** ~200 lines of own code, no extra dependency, easy swap later.

### ADR-0012 — Documentation-first with progress tracker as source of truth
- **Date:** 2026-11-22 · **Status:** Accepted
- **Decision:** Docs in `docs/`, rules in `CLAUDE.md`; each task updates tracker/changelog. **Consequences:** small overhead per task; prevents drift across sessions.

### ADR-0013 — Verification model
- **Date:** 2026-11-22 · **Status:** Accepted
- **Decision:** "Verified" is derived from `verifications` records (who/when/method/evidence); parent flags are denormalised caches; editing core facts flags re-verification. **Consequences:** trustworthy badges; slightly more admin work.

### ADR-0014 — Money & commission
- **Date:** 2026-11-22 · **Status:** Accepted
- **Decision:** `decimal(14,2)` BDT; commission derived from documented agreements with snapshotting; no default rate; payments append-only. **Consequences:** auditable finance, no float errors.

### ADR-0015 — Analytics consent gating
- **Date:** 2026-11-22 · **Status:** Accepted
- **Decision:** GA4/Meta Pixel load only after consent; internal event tracking stores minimal, hashed identifiers. **Consequences:** lower measured volumes; privacy-compliant.

---
### ADR-0018 — Docker + Cloudflare Tunnel deployment on the shared VPS (refines ADR-0011)
- **Date:** 2026-11-23 · **Status:** Accepted (owner-approved hosting plan)
- **Context:** The VPS already runs another production project in Docker whose Nginx owns ports 80/443; the `deploy` user has Docker access but no sudo, and the other project's Nginx config is regenerated from its releases (editing it is fragile and risks its downtime). DNS is on Cloudflare, where the owner already uses tunnels.
- **Decision:** Run this app as its own compose project (`pcbd`): php-fpm app, nginx web, queue worker, scheduler, PostgreSQL 16 and `cloudflared`. No ports are published; traffic enters through an outbound Cloudflare Tunnel (TLS handled by Cloudflare). Deploys are manual-trigger GitHub Actions on a dedicated self-hosted runner. Secrets live only in `/home/deploy/pcbd/.env` on the server.
- **Alternatives:** install PHP/Nginx on the host (needs root, port conflict); add a vhost to the other project's Nginx (overwritten by its deploys, risks its downtime).
- **Consequences:** Isolated from the other project; Docker is now a production dependency (still optional for local dev); depends on Cloudflare availability; real client IP comes from `CF-Connecting-IP`.

---
### ADR-0019 — Developer ERP as a second panel with developer-scoped data (extends ADR-0005, docs/27)
- **Date:** 2026-11-28 · **Status:** Accepted (owner asked to build it; defaults chosen, owner can still change)
- **Context:** A partner developer runs land-share projects (shares → registered deeds → instalments → flats) and needs to manage them. Their staff must never see the platform's CRM, other developers' data or site settings.
- **Decision:** (1) A second Filament panel `erp` at `/erp` with its own login, 2FA (mandatory for owner and accountant) and branding; developer users cannot enter `/admin`. (2) `users.developer_id` links a user to one developer; every ERP table carries `developer_id` and a global scope (`BelongsToDeveloper`) filters by the signed-in user's developer, with policies as a second check. (3) Roles `developer-owner`, `developer-accountant`, `developer-sales` and permissions `erp.view|sell|collect|manage`. (4) Money is `decimal(14,2)`; payments are **append-only** (enforced by a PostgreSQL trigger); corrections are reversal rows; receipt numbers are sequential per developer. (5) ERP records data only — it does not process payments. (6) No SaaS features (sign-up, billing, packages): still one database and one deployment; the `developer_id` boundary keeps a later move to SaaS possible.
- **Alternatives:** same `/admin` panel with role filtering (too easy to leak platform data); separate application (duplicates auth, deploys and data).
- **Consequences:** more tables and tests; the platform Super Admin can technically read everything (must be stated in the agreement with the developer); email-based invitations need SMTP, so admins create accounts and mark emails verified until then.

