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
- **Date:** 2026-11-22 · **Status:** Proposed (finalised in Phase 1)
- **Context:** Phase 0 has no code; package compatibility (Laravel × PHP × Livewire × Filament × Spatie) must be verified, not assumed. Env: PHP 8.3, Node 22, PostgreSQL 16.
- **Decision:** In Phase 1 install the latest stable Laravel supported by PHP 8.3 **and** by Filament and Spatie Permission; record exact versions here before continuing.
- **Alternatives:** Pin to an older LTS for stability.
- **Consequences:** Possible need to choose a slightly older Laravel major if Filament lags. Record outcome.

### ADR-0003 — Pest for testing
- **Date:** 2026-11-22 · **Status:** Accepted (confirm at install)
- **Decision:** Pest + Livewire/Filament testing helpers; PHPUnit remains the engine. **Alternatives:** plain PHPUnit. **Consequences:** concise tests; one more dev dependency.

### ADR-0004 — PostgreSQL as the only database
- **Date:** 2026-11-22 · **Status:** Accepted
- **Context:** Needs FTS, trigram search, partial indexes, check constraints, jsonb.
- **Decision:** PostgreSQL 16 in all environments incl. tests. **Alternatives:** MySQL/MariaDB (cheaper on shared hosting), SQLite for tests. **Consequences:** Hosting must offer PostgreSQL (R-05); no SQLite-only test shortcuts.

### ADR-0005 — No SaaS/multi-tenancy/billing in MVP
- **Date:** 2026-11-22 · **Status:** Accepted
- **Decision:** Defer all tenancy; adopt only "SaaS-ready habits" ([17](17-SAAS-FUTURE-ARCHITECTURE.md)). **Consequences:** a later migration will be needed; accepted trade-off for speed and simplicity.

### ADR-0006 — Spatie Media Library for media
- **Date:** 2026-11-22 · **Status:** Proposed (verify compatibility in Phase 2)
- **Decision:** Use `spatie/laravel-medialibrary` (conversions, collections, private disk) with Filament's Spatie media plugin. **Alternatives:** custom `media` table + Intervention Image; Filament FileUpload only. **Consequences:** less custom code, proven; adds dependency and a `media` table. Fallback: custom if incompatible.

### ADR-0007 — Contact/inquiry submissions are leads
- **Date:** 2026-11-22 · **Status:** Accepted
- **Context:** Separate `contact_inquiries` table would duplicate CRM data.
- **Decision:** All public forms create `leads` with `request_type`; minimal `leads` table created in Phase 3, extended in Phase 5. **Consequences:** single pipeline; Phase 3 must keep lead schema forward-compatible; admin UI for leads arrives Phase 5 (until then inquiries are viewable via a basic read-only list in Phase 3 to avoid lost leads).

### ADR-0008 — Auth approach & admin 2FA
- **Date:** 2026-11-22 · **Status:** Proposed (finalised in Phase 1)
- **Decision:** Laravel starter-kit style auth (Blade/Livewire, customised UI; no Jetstream/teams) for public users; Filament panel auth for admin with 2FA (Filament native MFA if available in chosen version, else Fortify TOTP). **Consequences:** two login screens (public & admin) sharing the `users` table and guard.

### ADR-0009 — Owner submissions reuse `properties`
- **Date:** 2026-11-22 · **Status:** Accepted
- **Decision:** Submitted properties are `properties` rows with `publish_status=pending_review` and `submitted_by`; private owner contact columns; listing reports in `property_reports`. **Alternatives:** separate `property_submissions` table with copy-on-approve. **Consequences:** no data copy/sync bugs; strict scoping needed so pending rows never leak publicly (global guard via `published()` scope + tests).

### ADR-0010 — Status fields as string + PHP enum + CHECK
- **Date:** 2026-11-22 · **Status:** Accepted
- **Decision:** `string` columns, backed enums in `app/Enums`, DB CHECK for critical machines. **Alternatives:** native PG enums (hard to alter). **Consequences:** easy migrations; discipline needed to keep enum and CHECK aligned (test asserts).

### ADR-0011 — No Redis, no Docker requirement in MVP
- **Date:** 2026-11-22 · **Status:** Accepted
- **Decision:** DB queue/cache/session drivers; Docker optional for local dev. **Revisit when:** queue latency or cache load measured as a problem.

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
