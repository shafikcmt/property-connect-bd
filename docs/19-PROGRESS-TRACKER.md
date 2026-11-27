# 19 — Progress Tracker

Statuses: Not Started · In Progress · Review · Completed · Blocked. Update after every completed task. Roadmap: [04](04-ROADMAP.md) (website-first, ADR-0016).

**Current phase:** Website complete and QA-passed; **launch blocked only on owner inputs** — see [26-LAUNCH-CHECKLIST](26-LAUNCH-CHECKLIST.md). CRM and later phases remain paused until after launch. **Last updated:** 2026-11-23.

## Phase overview
| Phase | Name | Status | % | Notes |
|---|---|---|---|---|
| 0 | Planning & Documentation | Completed | 100 | Re-planned 2026-11-23 for website-first |
| 1 | Application Foundation | Review | 95 | 46 tests green. Open: CI run on GitHub, manual 2FA enrolment click-through, brand assets |
| 2 | Property & Project Foundation | Completed | 100 | Admin data layer, media, verification, featured, publish gate — 90 tests at close |
| 3 | Public Website | Completed | 100 | All required pages; demo content only until owner supplies real content |
| 4 | Buyer Experience | Completed | 100 | Favorites, compare, recently viewed, inquiries, site visits, accounts, submissions — E2E verified |
| 5 | Premium UI/UX & Animation | Completed | 95 | Self-hosted fonts, motion, skeleton image placeholders. Open: brand logo/fonts from owner |
| 6 | Website QA | Completed | 100 | See QA report below |
| 7 | Deployment | Ready (not executed) | 85 | Scripts, preflight, runbook done and exercised on a scratch production-mode DB. Blocked on owner inputs → [26](26-LAUNCH-CHECKLIST.md) |
| 8 | CRM & Lead Management | In Progress (slices 1–2 done) | 70 | Done: assignment, priority, status rules, timeline, follow-ups + reminders, SLA/stale flags, agent scoping, repeat-inquiry merge (90 days), site-visit workflow. Next: budget/preferred-location fields, email/WhatsApp notifications (needs providers), KPI reports, round-robin |
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

## Website QA report (2026-11-23)
### Automated and browser test results
| Check | Result |
|---|---|
| PHPUnit/Pest | **169 passed** (Pint clean) |
| Route sweep | 24+ public routes × 375/768/1280/1440 px: 0 overflow, 0 failing responses, 0 JS errors |
| axe-core (WCAG 2.0/2.1 A+AA + best practices) | **0 violations** on 23 routes × 2 widths (after fixes below) |
| Keyboard / focus / overlays / reduced motion | 15/15 checks pass |
| CSP (report-only) in browser | 0 violations on 9 pages + search overlay |
| Admin smoke (17 screens × 2 widths) | 0 errors; avatar, ৳ formatting, nav order fixed |
| End-to-end flow (developer → project → property → upload → publish → public page → search → filter → favorite → compare → inquiry → site visit → owner submission → admin approval) | **25/25 steps pass**, 0 JS errors |
| `pcbd:preflight` on clean production-mode DB | passes (1 warning: contact details not yet entered) |
| Migrations | fresh + full rollback + re-migrate OK on scratch DB; `composer audit` / `npm audit`: 0 issues |

### Lighthouse (production mode, local server)
| Page | Mobile perf / a11y / BP / SEO | Mobile LCP · CLS | Desktop perf (dev server → with gzip+cache) |
|---|---|---|---|
| Home | 99 / 100 / 100 / 100 | 1.7 s · 0 | 70 → 94–98 |
| Properties | 94 / 100 / 100 / 100 | 3.0 s · 0 | 76 → 88 |
| Property detail | 98 / 100 / 100 / 100 | 2.4 s · 0 | 82 → 87 |
| Project detail | 96 / 100 / 100 / 100 | 2.6 s · 0 | 81 → 86 |
| Developers / Contact / FAQ | 100 / 100 / 100 / 100 | 0.8 s · 0 | 95–99 |
| Blog | 98 / 100 / 100 / 100 | 2.4 s · 0 | 87 |
Lab data on a local single-thread server with simulated mobile throttling; **real Core Web Vitals (field data) cannot be measured until the site is live.** Non-production pages are `noindex` by design, so SEO audits were run with `APP_ENV=production`.

### Defects found and fixed in this pass
1. **Admin lists crashed with real data** (closures named `$s`/`$r` are not injected by Filament; tests used empty tables) → fixed + regression tests that render every list with rows and the media manager.
2. **Developers could not be published** (missing `published_at` column) → migration + test.
3. Developer uploads defaulted to "gallery" so the required logo was missed → defaults to "Logo".
4. Static `public/robots.txt` from the skeleton overrode the dynamic one and allowed `/admin` → removed.
5. Listing page CLS up to 0.54 intermittently (filter sidebar rendered open until Alpine loaded) → static `hidden` default; CLS 0 over repeated runs.
6. Brand emerald failed AA contrast (4.05:1) → darker brand colours (5.6:1); footer links too small to tap; heading-order on card grids; gallery button accessible name; scrollable tables not keyboard-focusable; pagination (default view had invalid ARIA and off-brand styling) → rebuilt; search overlay did not focus its input.
7. Home title too long (71 chars) → shortened; inline `onchange` violated CSP → Alpine handler.
8. Staff avatars were fetched from a third-party service (privacy + broken offline) → local initials avatar; money shown as "BDT 220,000.00" → ৳ lakh/crore; admin nav groups reordered; real-count dashboard overview added.
9. Fonts: self-hosted Inter + Fraunces (Latin) with preload and metric-adjusted fallbacks; card images get a skeleton placeholder.

### Accessibility findings summary
Keyboard: skip link first, visible focus on all tested stops, menus/search/lightbox operable and closable with Escape, arrow keys in gallery. Forms: all controls labelled, errors associated, native + server validation. Contrast: AA after brand colour change (axe clean). Semantics: landmarks, one h1, ordered headings, `aria-current`, table headers with scope. Reduced motion: content visible, animations off. **Not done:** manual screen-reader walkthrough (NVDA/VoiceOver) and Bangla-language checks — recommended before launch by a person with the tools.

### Remaining defects / limitations (none blocking)
- Mobile LCP on listing/project pages ~2.6–3.0 s under simulated slow 4G (image-heavy); expect better with a CDN/HTTP caching; re-measure live.
- Email delivery untested (no SMTP configured); verification/reset mails are logged locally.
- CSP is report-only; no analytics/consent banner yet (Phase 10).
- Demo data only; real content, logo, legal review pending (owner).

### Deployment blockers
All owner-side: hosting, domain, SMTP, content/photos, legal review, contact details — see [26](26-LAUNCH-CHECKLIST.md).

## Phase 8 — slice 1 (CRM core), 2026-11-25
- Built: `leads` assignment/priority/follow-up/response fields, `lead_activities`, `lead_status_histories`, `lead_follow_ups`; `LeadWorkflow` service enforcing docs/13 transition rules (reasons, duplicate original, manager-only conversion/reopen/backwards moves with audit); `leads.work` (agents see only assigned leads) and `leads.assign` permissions; Filament inbox with assign/change status/log activity/schedule follow-up actions, timeline and follow-up managers, filters (mine, unassigned, overdue, response overdue, stale); dashboard CRM counts; in-app (bell) alerts to managers on new inquiries and to assignees; hourly `pcbd:crm-reminders`; `notifications.data` converted to jsonb for Filament.
- Verified: 206 automated tests pass; admin list/edit/dashboard checked in a browser.
- Not yet: 90-day duplicate merge, budget/preferred-location fields, site-visit scheduling workflow, email/WhatsApp/SMS notifications (needs SMTP/WhatsApp provider), KPI reports, round-robin assignment.

## Phase 8 — slice 2 (2026-11-27)
- Repeat inquiries from the same phone are merged into the buyer's open lead (≤ 90 days, not closed): timeline entry, inquiry counter, priority normal → high, assignee (or managers) alerted. Identical requests within 10 minutes are still ignored as double clicks.
- Site-visit workflow (`SiteVisitWorkflow`, Site visits board, lead tab): confirm/reschedule (future time, agent, meeting point; lead moves to Site visit and is assigned), complete (feedback, only after the visit time, auto follow-up), cancel (reason), no-show (auto re-engagement follow-up). Agents see their own visits only. Tests: 214.

