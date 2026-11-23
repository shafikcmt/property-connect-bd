# 19 — Progress Tracker

Statuses: Not Started · In Progress · Review · Completed · Blocked. Update after every completed task. Roadmap: [04](04-ROADMAP.md) (website-first, ADR-0016).

**Current phase:** Phase 2 — Property & Project Foundation. **Last updated:** 2026-11-23.

## Phase overview
| Phase | Name | Status | % | Notes |
|---|---|---|---|---|
| 0 | Planning & Documentation | Completed | 100 | Re-planned 2026-11-23 for website-first |
| 1 | Application Foundation | Review | 95 | 46 tests green. Open: CI run on GitHub, manual 2FA enrolment click-through, brand assets |
| 2 | Property & Project Foundation | In Progress | 0 | |
| 3 | Public Website | Not Started | 0 | |
| 4 | Buyer Experience | Not Started | 0 | |
| 5 | Premium UI/UX & Animation | Not Started | 0 | |
| 6 | Website QA | Not Started | 0 | |
| 7 | Deployment | Not Started | 0 | Hosting decision needed (R-05) |
| 8 | CRM & Lead Management | Not Started (post-launch) | 0 | |
| 9 | Sales & Commission | Not Started (post-launch) | 0 | |
| 10 | Marketing & Analytics | Not Started (post-launch) | 0 | |
| 11 | Partner & Agent Features | Not Started (post-launch) | 0 | |
| 12 | SaaS architecture | Not Started (on demand) | 0 | |

## Phase 1 verification record (2026-11-22)
Pint passed; 46 tests / 150 assertions passed; migrate fresh + rollback OK; composer audit clean; Playwright 375/1280 no overflow.
Defects fixed: Admin could assign Super Admin role (server-side rule added); per-role MFA via `EnforceAdminMfa`.

## Phase 2 tasks
(updated as work proceeds)
