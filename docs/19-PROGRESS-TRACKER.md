# 19 — Progress Tracker

Statuses: Not Started · In Progress · Review · Completed · Blocked. Update after every completed task. Roadmap: [04](04-ROADMAP.md) (website-first, ADR-0016).

**Current phase:** Phase 2 — Property & Project Foundation. **Last updated:** 2026-11-23.

## Phase overview
| Phase | Name | Status | % | Notes |
|---|---|---|---|---|
| 0 | Planning & Documentation | Completed | 100 | Re-planned 2026-11-23 for website-first |
| 1 | Application Foundation | Review | 95 | 46 tests green. Open: CI run on GitHub, manual 2FA enrolment click-through, brand assets |
| 2 | Property & Project Foundation | Review | 95 | 90 tests green; see Phase 2 record |
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

## Phase 2 record (Property & Project Foundation)
Done: locations tree (auto path, descendants), property types, amenities, developers, projects (status/progress/handover/price range gated by `price_verified`), unit types + units (availability), properties (publish workflow, price tracking, public code, private owner fields hidden), custom media layer (GD → WebP 480/960/1600, private documents, floor plans collection), verification records, featured flag with expiry, slug uniqueness + history, SEO fields, publish gate (`ListingPublisher`), Filament resources with tabs/filters/badges/actions, permissions (`listings.*`, `catalog.manage`), reference seeder (production-safe), demo seeder (procedural artwork, refuses production).
Verification: 90 tests pass; Pint clean; migrate fresh/rollback checked before Phase 2 additions (re-check at close).
Open: manual visual review of admin screens; admin 'Verified/Featured' bulk tools not needed; owner-submission fields exist but UI is Phase 4.
Defects found & fixed: Filament injects query by parameter name (`$query`), unnamed Builder closures got empty builders.
