# 15 — Testing Strategy

## 1. Tools
Pest (on PHPUnit) for unit + feature tests (ADR-0003), Livewire testing helpers, Filament testing helpers, Laravel Pint for style, optional Larastan (level ≥ 5, introduced Phase 2) for static analysis, Playwright (available in the environment) for a small set of browser smoke/responsive/accessibility checks (axe-core) from Phase 3. Test DB: PostgreSQL (`pcbd_testing`) to match production (SQLite avoided where features differ — FTS, partial indexes, check constraints); `RefreshDatabase`/`LazilyRefreshDatabase`.

## 2. Pyramid
- **Unit:** pure logic — `CommissionCalculator`, `Money` formatting, phone normaliser, slug generator, status transition tables, price/sqft, SEO meta builders.
- **Feature (majority):** HTTP/Livewire/Filament behaviour incl. validation, authorization, DB effects, notifications (`Notification::fake`), queues (`Queue::fake`), mail (`Mail::fake`), scheduler commands.
- **Browser smoke (small):** home, listing filters, property detail, inquiry submission, mobile menu, admin login — run in CI nightly or pre-release.
- **Manual QA:** checklist per phase (responsiveness on 360/768/1280, keyboard nav, screen-reader spot check, real device test before launch).

## 3. Coverage map (what must have tests)
| Area | Tests |
|---|---|
| Authentication | register/login/logout/reset/verify; throttling; suspended user blocked; admin 2FA enforcement |
| Authorization | per-role × resource 200/403 matrix (data provider); Sales Agent sees only assigned leads; Buyer only own favorites/inquiries; Developer scoping; private media download denied; Policy unit tests |
| Property listing | only published visible; drafts/pending/rejected/archived hidden; soft-deleted hidden; pagination |
| Filters & search | each filter alone and combined; ranges; amenity all-of; location descendants; keyword FTS; sorting; invalid params → 422/ignored safely; query-count assertion |
| Property details | 404 for unpublished; price/sqft; badges only with verification; owner contact hidden by default; similar properties |
| Inquiry forms | validation, consent required, honeypot, rate limit, lead created with UTM/source/property, duplicate handling, no PII in logs |
| Lead creation/assignment | `CreateLeadFromInquiry`, assign/reassign notifications, unassigned queue |
| Lead lifecycle | allowed/forbidden transitions, reason required, history rows, converted only via deal |
| Follow-up | create/complete, reminders via scheduler (time travel), overdue scopes |
| Site visit | state machine, double-book warning, reminders, completion feedback |
| Deal & commission | calculator (percentage, fixed, rounding, zero/negative guards), agreement selection, partial payments, overpayment rejected, outstanding balance, status derivation, cancellation reversal, finance permission gating |
| Admin access | non-admin 403/redirect; role-based nav visibility; Filament resource policies; audit log written |
| Validation | Form Request rule tests for each public form; upload type/size/dimension |
| Security | CSRF present; XSS payload escaped in titles/notes/rich text; SQLi-ish payloads inert; mass assignment of protected fields (`is_verified`, `status`) ignored; IDOR attempts; security headers; `robots.txt` blocks admin; no `.env`/storage exposure; rate limiters |
| SEO output | title/description/canonical/OG per template; JSON-LD parses & required fields; sitemap contents (only published/indexable); robots meta on filtered pages; breadcrumbs; redirects from slug history |
| Mobile/responsive | Playwright viewport smoke (no horizontal scroll at 360px, nav usable, forms usable) |
| Accessibility | axe-core on key templates (no critical/serious violations), labels/alt present |
| Data/migrations | `migrate:fresh --seed` succeeds on PG; migrations reversible (`migrate:rollback` in CI job); seeders idempotent; demo seeder refuses to run in production |

## 4. Security test list (must pass before Phase 9 close)
Role matrix · IDOR on leads/deals/files · stored XSS in all rich/plain text inputs · upload of polyglot/oversized/wrong-MIME files · brute-force throttling · mass assignment · open redirect checks (`redirect` params) · header checks · dependency audit.

## 5. Quality gates
Local/CI on each push/PR: `vendor/bin/pint --test`, `php artisan test`, (Phase 2+) Larastan, `composer audit`, `npm run build`. Merge/phase close requires green. Coverage target (informational): ≥ 70% lines overall, ≥ 90% on commission/lead-transition/authorization code; do not chase coverage numbers over meaningful tests.

## 6. Test data
Model factories for every model; states (`published()`, `featured()`, `verified()`); no real personal data; seeded reference data via `ReferenceDataSeeder` in tests where needed. Time travel with `Carbon::setTestNow`.

## 7. Defect handling
Bug → failing test first → fix → note in changelog if user-visible. Known failing tests are never skipped/ignored silently: if unavoidable, record in tracker blockers with reason.

## 8. Completion rule
Never claim "tests pass" unless run in this session and output seen. Record exact commands and results in the phase summary and progress tracker.
