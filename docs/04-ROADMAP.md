# 04 — Development Roadmap (website-first, revised 2026-11-23)

**Direction (ADR-0016):** build, test and deploy the complete public real-estate website first; CRM, sales, commission, marketing analytics, portals and SaaS follow *after* launch. Phases are sequential; a phase closes only when its criteria in [23](23-ACCEPTANCE-CRITERIA.md) are met and [19](19-PROGRESS-TRACKER.md) is updated.

Common Definition of Done: implementation complete, validation + authorization, tests added and run green, Pint clean, responsive/accessible UI verified with screenshots, docs/tracker/changelog updated, no known critical bug.

## Phase map
| Phase | Name (user "Stage") | Goal |
|---|---|---|
| 0 ✅ | Planning & Documentation | Blueprint |
| 1 ✅ | Application Foundation | Laravel, auth, roles, admin shell, layout |
| **2** | **Property & Project Foundation** (Stage 1) | Admin data layer that powers the site |
| **3** | **Public Website** (Stage 2) | Every public page, search, filters, forms, SEO |
| **4** | **Buyer Experience** (Stage 3) | Favorites, compare, recently viewed, inquiry, WhatsApp, site-visit request, profile, history |
| **5** | **Premium UI/UX & Animation** (Stage 4) | Homepage experience, cards, motion, skeletons, design polish |
| **6** | **Website QA** (Stage 5) | Tests, screenshots at 375/768/1280/1440, fixes, perf/SEO/security review |
| **7** | **Deployment** (Stage 6) | Production config, docs, backups, rollback — launch-ready |
| — | **LAUNCH** | |
| 8 | CRM & Lead Management | Pipeline, assignment, follow-ups, site-visit management |
| 9 | Sales & Commission | Deals, bookings, commission |
| 10 | Marketing & Analytics | Campaigns, UTM reports, tracking |
| 11 | Partner & Agent Features | Developer portal, agents |
| 12 | SaaS architecture | Only after proven demand ([17](17-SAAS-FUTURE-ARCHITECTURE.md)) |

**Legacy phase labels.** Docs 02, 03, 10, 11, 12, 13, 23 were written with the original numbering. Map: old P2 → 2, old P3 → 3 (+5 for UI), old P4 → 4, old P5 → 8, old P6 → 9, old P7 → 10, old P8 → 11, old P9 → 6+7. Where a doc says "Phase 5" for CRM, read "Phase 8".

## Pre-launch scope guard
Inquiry capture must exist at launch, otherwise the site generates nothing. Therefore Phase 3/4 include a **minimal, forward-compatible** `leads` table (all public forms write to it) and a `site_visits` request table, plus a basic Filament list/status editor so the founder can see and mark inquiries. Assignment, follow-ups, activity timelines, pipeline views, SLA, dedupe, deals and commission wait until Phase 8+ (ADR-0007 still applies).

---
## Phase 2 — Property & Project Foundation (`feat/property-management`)
**Scope:** locations tree; property types; amenities; developers; projects (status, progress, handover, price range); unit types + units with basic availability; properties (purpose, status, specs, price, publish workflow); media (images/galleries/brochure/floor plans) with GD-generated WebP conversions; verification records; featured flags with expiry; slugs + slug history; SEO fields; polished Filament resources with navigation groups, filters, badges, publish validators; policies/permissions (`properties.*`, `projects.*`, `developers.*`, `catalog.manage`); clearly-labelled demo seeder (generated illustrative images).
**DB:** locations, property_types, amenities, developers, projects, project_unit_types, project_units, properties, amenity_property, amenity_project, media, floor_plans, verifications, slug_histories.
**Tests:** CRUD authorization per role, publish validator, verification record, featured expiry scope, slug history, media processing, demo seeder refuses production.
**Acceptance:** admin can create a complete developer → project → unit types → property chain with images, verify and feature it; unpublished data never leaks (tested).

## Phase 3 — Public Website (`feat/public-property-listings`)
**Pages:** Home, Properties (+filters/sort/search/grid-list/pagination), Property details, Projects, Project details, Developers, Developer details, Locations, Location details, Services, About, Contact, Blog, Blog details, FAQ, Privacy, Terms (+Cookie, Disclaimer), Sell property, Rent property, Submit property, Valuation request, 404/search.
**Backend:** query objects (`PropertySearch`, `ProjectSearch`), SEO component (meta/canonical/OG/JSON-LD/breadcrumbs), sitemap + robots, CMS tables (pages, faqs, blog_posts/categories) with Filament resources, `leads` (minimal) + form actions with honeypot/rate-limit/consent, contact page, submissions (`properties.publish_status=pending_review`).
**Acceptance:** every route returns real content, no placeholder UI; forms validate and persist; SEO output tested.

## Phase 4 — Buyer Experience (`feat/buyer-account`)
Favorites (guest session → account merge), compare (≤4), recently viewed, property/project inquiry, request info, WhatsApp click-to-chat links, site-visit request (minimal `site_visits`), registration/login integration, profile, saved properties/searches, inquiry + visit history — all functional and owner-scoped (IDOR tests).

## Phase 5 — Premium UI/UX & Animation (`feat/premium-ui`)
Design system finalisation (tokens, self-hosted fonts if assets available), animated architectural hero, premium property/project cards, scroll reveal (CSS + one tiny IntersectionObserver), staggered cards, image-zoom hovers, animated stats, skeleton loaders, gallery/lightbox transitions, smooth menus/dropdowns, interactive search. CSS first; **no animation library unless justified by ADR**. `prefers-reduced-motion` respected; no layout shift; reduced motion on mobile.

## Phase 6 — Website QA (`chore/website-qa`)
Route sweep tests, Playwright screenshots (375/768/1280/1440) reviewed and defects fixed, forms/validation/auth/authz/security tests, SEO audit, Lighthouse-style performance review, empty/error/loading states.

## Phase 7 — Deployment (`chore/deployment`)
Production `.env` template, web-server/PHP config, SSL, PostgreSQL production setup, storage/image config, cache/queue/scheduler, backups + restore drill doc, logging, security checks, production build, SEO/analytics config, deployment guide + rollback plan. **No irreversible production action without owner authorisation.**

---
## Post-launch phases (unchanged content, renumbered)
**8 CRM & Lead Management** (lead sources, pipeline, assignment, notes, activity timeline, follow-ups, site-visit management, notifications, dashboard). **9 Sales & Commission** (deals, bookings, sales, agreements, calculation, payments, reports). **10 Marketing & Analytics** (campaigns, UTM attribution, tracking events, GA4/Meta Pixel with consent, reports). **11 Partner & Agent** (developer portal, agents, performance). **12 SaaS** (tenancy, billing — only on demand).
Details of each remain in docs 11, 13, 10 §4.

## Dependency graph
0 → 1 → 2 → 3 → 4 → 5 → 6 → 7 → LAUNCH → 8 → 9 → 10 → 11 → 12. Phases 4 and 5 may interleave with 3 where a page needs both; they close in order.
