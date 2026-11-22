# 19 — Progress Tracker

Statuses: Not Started · In Progress · Review · Completed · Blocked. Update after every completed task. Phase detail: [04-ROADMAP](04-ROADMAP.md). Criteria: [23](23-ACCEPTANCE-CRITERIA.md).

**Current phase:** Phase 1 — Application Foundation (next). **Last updated:** 2026-11-22.

## Phase overview
| Phase | Name | Status | % | Depends on | Notes / Blockers |
|---|---|---|---|---|---|
| 0 | Planning & Documentation | Completed | 100 | — | Reviewed 2026-11-22 (see below) |
| 1 | Application Foundation | Not Started | 0 | 0 | Verify Laravel/Filament/Spatie compatibility first (ADR-0002) |
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

## Phase 1 tasks (planned)
| Task | Status | % | Dependencies | Notes |
|---|---|---|---|---|
| Verify package compatibility; record ADR-0002 | Not Started | 0 | — | |
| Scaffold Laravel, config, PostgreSQL, `.env.example` | Not Started | 0 | above | |
| Auth (login/register/reset/verify) | Not Started | 0 | scaffold | |
| Spatie Permission, roles/permissions seeder, policies base | Not Started | 0 | auth | |
| Filament panel + access control (+2FA decision) | Not Started | 0 | roles | |
| Settings service + admin page | Not Started | 0 | panel | |
| Audit logging | Not Started | 0 | panel | |
| Design tokens, public layout, nav, error pages | Not Started | 0 | scaffold | |
| Pest, Pint, CI workflow | Not Started | 0 | scaffold | |
| Docs/changelog/acceptance sign-off | Not Started | 0 | all | |
