# 19 — Progress Tracker

Statuses: Not Started · In Progress · Review · Completed · Blocked. Update after every completed task. Phase detail: [04-ROADMAP](04-ROADMAP.md). Criteria: [23](23-ACCEPTANCE-CRITERIA.md).

**Current phase:** Phase 1 — Application Foundation (in Review; Phase 2 not started). **Last updated:** 2026-11-22.

## Phase overview
| Phase | Name | Status | % | Depends on | Notes / Blockers |
|---|---|---|---|---|---|
| 0 | Planning & Documentation | Completed | 100 | — | Reviewed 2026-11-22 (see below) |
| 1 | Application Foundation | Review | 95 | 0 | Implemented & tested (46 tests green). Open items listed under Phase 1 below |
| 2 | Property & Project Management | Not Started | 0 | 1 | |
| 3 | Public Website & Discovery | Not Started | 0 | 2 | |
| 4 | Buyer & Seller Experience | Not Started | 0 | 3 | |
| 5 | CRM & Lead Management | Not Started | 0 | 3 | |
| 6 | Sales & Commission | Not Started | 0 | 5 | |
| 7 | Marketing & Analytics | Not Started | 0 | 5 | |
| 8 | Partner & Agent Features | Not Started | 0 | 6, 7 | |
| 9 | Production Readiness | Not Started | 0 | all | Hosting decision (R-05), legal review (R-03) |

## Phase 0 tasks
| Task | Status | % | Notes |
|---|---|---|---|
| Repository inspection (empty repo, no commits, PHP 8.3/Composer/Node 22/PG 16 available) | Completed | 100 | |
| docs/00–24 written | Completed | 100 | |
| CLAUDE.md | Completed | 100 | |
| README.md | Completed | 100 | |
| Consistency review | Completed | 100 | Fixes applied: submissions model (ADR-0009) in roadmap; Phase 3 read-only inquiry list added to roadmap; cookie banner moved to Phase 7 scope; notification priorities aligned (admin bell P1 / buyer centre P2); removed stray `newsletter` request_type |

### Phase 0 review findings
- **Conflicts:** none outstanding after the fixes above.
- **Scope-creep watch:** Phase 8 and many P2 items are explicitly deferred; MVP launch path = Phases 1–3 + 5 + 9 (+4/6 shortly after).
- **Open items (not blockers for Phase 1):** hosting (R-05), brand assets, legal review (R-03), email provider (R-06) — see [21](21-RISKS-AND-ASSUMPTIONS.md).
- **Verify at implementation:** exact package versions (ADR-0002), Spatie Media Library compatibility (ADR-0006), admin 2FA mechanism (ADR-0008), purifier package (14 §2).
- **Security gaps considered:** upload scanning (P2), admin IP allow-list (P2) — accepted for MVP.

## Phase 1 tasks
| Task | Status | % | Notes |
|---|---|---|---|
| Verify package compatibility; ADR-0002 | Completed | 100 | Laravel 13.43, Filament 5.12, Spatie Permission 8.5, Pest 4.7 |
| Scaffold Laravel, config, PostgreSQL, `.env.example` | Completed | 100 | `.env` never committed |
| Auth (login/register/reset/verify) | Completed | 100 | Throttling, no user enumeration, buyer-only registration |
| Spatie Permission, roles/permissions seeder, policies base | Completed | 100 | Idempotent seeder; UserPolicy |
| Filament panel + access control + 2FA | Completed | 90 | Enforcement tested; full TOTP enrolment click-through **not yet exercised manually** |
| Settings service + admin page | Completed | 100 | Audit records changed keys only |
| Audit logging | Completed | 100 | Staff logins, settings, user create/update; viewer for Admin/SA |
| Design tokens, public layout, nav, error pages | Completed | 95 | Verified 375/1280 px via Playwright (no overflow); self-hosted fonts + `/dev/styleguide` deferred (backlog) |
| Pest, Pint, CI workflow | Completed | 95 | Pint clean, 46/46 tests pass locally; **CI workflow not yet run on GitHub** |
| Docs/changelog/acceptance sign-off | Completed | 100 | Updated README, ADR-0002/0003/0008, acceptance, changelog |

### Phase 1 verification record (run in this session)
- `vendor/bin/pint --test` → passed. `php artisan test` → 46 passed / 150 assertions.
- `php artisan migrate:fresh --seed` and `migrate:rollback` → succeeded on PostgreSQL 16.
- `composer audit` → no advisories. `npm run build` → succeeded.
- Playwright at 375 px and 1280 px: `/`, `/login`, `/register` have no horizontal overflow.
- Defect found and fixed during review: Admin could assign the Super Admin role (option filtering is not validation) → server-side rule + regression test; per-role MFA could not use Filament's boot-time flag → `EnforceAdminMfa` middleware + test.

### Open items before closing Phase 1
1. Push and confirm the GitHub Actions workflow is green.
2. Manually enrol a Super Admin in 2FA end-to-end and view the admin UI visually.
3. Decide brand fonts/logo (owner) — currently fallback fonts and a text wordmark.
