# CLAUDE.md — Property Connect BD Project Rules

This file is the entry point for every Claude Code session. Read it fully before doing anything.

## 1. What this project is

Property Connect BD is a **real estate business platform** (not just a listing site): property marketing, developer partnerships, discovery, inquiries, lead management, site visits, sales coordination and commission tracking. Tagline: *Connecting Properties, People & Opportunities.*
It is a single Laravel application, built phase by phase, documentation-first, by/for a small team (solo founder initially).

## 2. Mandatory reading order (every session)

1. `CLAUDE.md` (this file)
2. `docs/00-PROJECT-OVERVIEW.md`
3. `docs/04-ROADMAP.md`
4. `docs/19-PROGRESS-TRACKER.md` — find the current phase and next task
5. The module specs relevant to your task: `docs/11-MODULE-SPECIFICATIONS.md`, and as needed `10-DATABASE-ARCHITECTURE`, `07-USER-ROLES-AND-PERMISSIONS`, `08-UI-UX-DESIGN-SYSTEM`, `13-LEAD-AND-SALES-WORKFLOW`, `14-SECURITY-AND-PRIVACY`, `15-TESTING-STRATEGY`, `18-DECISIONS-AND-ADRS`
6. Then **inspect the existing code** before changing anything.

The docs are the single source of truth. If code and docs disagree, stop, work out which is right, and fix the wrong one (documenting the reason).

## 3. Non-negotiable rules

- Inspect existing code before modifying; follow the established architecture; preserve working functionality.
- Work incrementally. No unrelated refactoring. Do not start the next major phase while the current one has failing tests, unresolved critical bugs or unmet acceptance criteria (`docs/23-ACCEPTANCE-CRITERIA.md`).
- Never silently change an architectural decision. Add/supersede an ADR in `docs/18-DECISIONS-AND-ADRS.md` first.
- Avoid unnecessary dependencies. Verify package compatibility (Laravel/PHP version) before installing. Avoid duplicated functionality.
- Never expose or commit credentials, tokens, API keys, secrets or `.env`. Only `.env.example` (with placeholders) is committed.
- Never skip unfinished tasks or hide failures. Never claim a feature is complete, or a test passed, without having run/verified it. Report failures plainly.
- Never invent data presented as real: no fake testimonials, fake "verified" badges, fabricated developer credentials, invented prices or analytics. Seeders must use clearly-labelled demo data that is impossible to confuse with real data and never runs in production.
- Never hardcode a universal commission rate. Commission is configured per written agreement.
- Never expose buyer/lead contact data publicly; property documents are private unless explicitly published.

## 4. Stack & architecture (summary — details in docs/09)

PHP + Laravel (unified monolith), Blade + Livewire, Tailwind CSS, Filament admin, PostgreSQL, Spatie Laravel Permission, Laravel queue (database driver) / scheduler / notifications. No microservices, no separate SPA, no Redis, no Docker requirement, no extra JS frameworks unless an ADR approves it.

Conventions:
- Thin controllers/Livewire components; business logic in `app/Services` (stateful workflows) or `app/Actions` (single-purpose operations). No repository pattern — use Eloquent + scopes.
- Form Requests (or Livewire/Filament validation) for all input. Policies for all authorization (`Gate`/`authorize`), plus Spatie permissions. Never rely on hidden UI for security.
- Enums (PHP backed enums) for statuses. Money stored as integer minor units or `decimal(14,2)` per docs/10 — never float.
- Public pages are server-rendered Blade with Livewire only where interactivity is needed (progressive enhancement).
- Modules are organised by domain folders inside `app/` (see docs/09 §3), not separate packages.
- Migrations: one concern per migration, reversible, with indexes and FKs. Never edit a migration that has been merged/deployed; add a new one.

Coding standards: PSR-12 via Laravel Pint (`vendor/bin/pint`); strict types in new PHP files; type-hint everything; descriptive names; no dead code; comments explain *why*. Blade: semantic HTML, accessible labels, Tailwind utility classes using the design tokens in docs/08. Tests: Pest or PHPUnit as set in docs/15 (Pest decided in ADR-0003 once installed).

## 5. Development workflow

1. Read the docs per §2; confirm phase dependencies in the roadmap.
2. Create/use the phase feature branch (`feat/...`, see docs/22). `main` stays stable; the session's designated development branch takes precedence if the environment specifies one.
3. Implement in small logical tasks; commit with meaningful messages (imperative, scoped, e.g. `feat(properties): add property status enum`).
4. Write tests with the code (feature tests for behaviour, unit tests for calculations such as commission).
5. Run: `vendor/bin/pint --test`, `php artisan test`, `npm run build` (when frontend changed). Review migrations (`php artisan migrate:fresh` on a scratch DB).
6. Verify UI at mobile (375px) and desktop widths; keyboard navigation and focus states.
7. Update docs: affected specs, `docs/19-PROGRESS-TRACKER.md` after every completed task, `docs/24-CHANGELOG.md` for significant changes, `docs/20-BACKLOG...` for deferred ideas, ADRs for decisions.
8. At the end of a phase: summarise completed work, test results, remaining issues, next phase. Do not commit/push unless the environment or user authorises it; never create a PR unless asked.

Definition of Done (per task): implementation complete; requirements met; validation in place; authorization checked; tests added and run; no known critical errors; responsive UI; docs + tracker updated; changes reviewed.

## 6. Security checklist (apply to every change)

Validate input · authorize every action (policy + permission) · escape output (Blade `{{ }}`; sanitize any rich text) · CSRF on state-changing routes · rate-limit public forms/login · validate uploads by MIME+extension+size and store outside the public disk when private · no raw SQL with user input · log (audit) sensitive changes · minimise personal data and record consent · never log secrets/PII.

## 7. Scope guard

MVP priorities are in `docs/03-MVP-SCOPE.md` (P0–P3). Do not build P2/P3 (SaaS, billing, multi-tenancy, mobile apps, AI, complex automation) unless a phase in the roadmap explicitly says so or the owner approves. Park new ideas in `docs/20-BACKLOG-AND-FUTURE-FEATURES.md`.

## 8. Asking questions

Ask the owner only when blocked by: a business decision, credential, paid service, external API, legal requirement or genuinely ambiguous requirement. Document the question and assumption in `docs/21-RISKS-AND-ASSUMPTIONS.md` and continue with other work where possible.
