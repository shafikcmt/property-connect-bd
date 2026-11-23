# 19 — Progress Tracker

Statuses: Not Started · In Progress · Review · Completed · Blocked. Update after every completed task. Roadmap: [04](04-ROADMAP.md) (website-first, ADR-0016).

**Current phase:** Phases 3–7 (website-first) — in review; launch blocked only on owner inputs (hosting, domain, content, legal review). **Last updated:** 2026-11-23.

## Phase overview
| Phase | Name | Status | % | Notes |
|---|---|---|---|---|
| 0 | Planning & Documentation | Completed | 100 | Re-planned 2026-11-23 for website-first |
| 1 | Application Foundation | Review | 95 | 46 tests green. Open: CI run on GitHub, manual 2FA enrolment click-through, brand assets |
| 2 | Property & Project Foundation | Completed | 100 | Admin data layer, media, verification, featured, publish gate — 90 tests at close |
| 3 | Public Website | Review | 90 | All required pages built and route-swept (28 routes × 4 widths, 0 layout issues); content is demo data only — real content needed |
| 4 | Buyer Experience | Review | 90 | Favorites (guest+member), compare, recently viewed, inquiry, WhatsApp, site-visit request, saved searches, profile, history, owner submissions — tested |
| 5 | Premium UI/UX & Animation | Review | 75 | Animated hero, reveal/stagger, hover zoom, counters, lightbox, suggest, dropdowns done (CSS + Alpine, no animation library). Open: self-hosted fonts/brand assets, skeleton loaders unused in pages, tablet/1440 visual pass of inner pages |
| 6 | Website QA | In Progress | 55 | 164 tests; Playwright sweep clean. Open: Lighthouse/perf numbers, accessibility audit (axe), manual visual review of admin screens, email/notification flows |
| 7 | Deployment | In Progress | 60 | Preflight command, deploy/backup scripts, env template, runbook written. NOT executed on a server (no host/domain); needs owner inputs |
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

## Website-first verification record (2026-11-23)
- `vendor/bin/pint --test` passed; `php artisan test` → **164 passed**.
- Playwright sweep: 28 public routes × 375/768/1280/1440 px → 0 horizontal overflow, 0 failing responses, 0 JS errors; viewport screenshots of home (desktop + mobile) and listing reviewed.
- Defects found and fixed: `<x-icon>` clashed with Filament's blade-icons; Eloquent objects cannot be cached (Laravel 13 refuses to unserialize them) so listing options/home are uncached; lazy-loading guard exposed a 3-level `fullName()`; header overflow at 375 and 1280; grid `min-w-0` overflow on detail pages; error pages rendered before session middleware.
- Not yet verified: Lighthouse scores; screen-reader pass; admin UI visuals; real email delivery; anything on a production server.
