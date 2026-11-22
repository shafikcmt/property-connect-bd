# 00 — Project Overview

**Project:** Property Connect BD  **Tagline:** Connecting Properties, People & Opportunities
**Type:** Independent real estate marketing, property marketplace, sales and lead-generation platform (Bangladesh market).
**Status:** see [19-PROGRESS-TRACKER](19-PROGRESS-TRACKER.md).

## 1. Vision
A trusted, premium real estate brand and the software behind it. The website attracts buyers, sellers and investors; the internal system turns that attention into managed leads, site visits, deals and commission revenue. Over time the same infrastructure can become a multi-company SaaS (see [17](17-SAAS-FUTURE-ARCHITECTURE.md)) — but not in the MVP.

## 2. Parties
| Party | Relationship to the platform |
|---|---|
| Buyers / investors | Search, save, compare, inquire, request site visits |
| Sellers / owners | Submit properties (reviewed before publishing), request sell/rent/valuation help |
| Developers | Partners whose projects we market; later get a portal (Phase 8) |
| Agents / sales consultants | Work assigned leads and visits |
| Internal admins | Manage content, listings, CRM, sales, commission |

## 3. Business objectives (ranked by launch relevance)
1. Independent brand with a credible, verified catalogue (P0).
2. Developer partnerships; publish verified projects/properties (P0).
3. Generate buyer inquiries via SEO, social and ads (P0).
4. Manage leads, follow-ups and site visits (P0/P1).
5. Coordinate sales and bookings; track commission per written agreement (P1).
6. Marketing services and featured-listing revenue (P1/P2).
7. Reusable marketing infrastructure, then SaaS (P3).

Revenue-first, cost-conscious, manageable by one person: shared hosting/VPS-level infrastructure, no mandatory paid services at launch.

## 4. Product shape
- **Public website** (Blade/Livewire/Tailwind): discovery, SEO, inquiry capture. See [05](05-SITEMAP-AND-NAVIGATION.md).
- **Admin panel** (Filament at `/admin`): property/project/partner management, CRM, site visits, sales, commission, CMS, system. Separate from public navigation.
- **Buyer/owner account area** (Livewire pages): favorites, saved searches, inquiries, submissions.
- One Laravel codebase, one PostgreSQL database.

## 5. Guiding principles
1. Documentation first; docs are the source of truth.
2. Phased delivery; never start a phase with the previous one failing.
3. Practical MVP; defer complexity ([03](03-MVP-SCOPE.md), [20](20-BACKLOG-AND-FUTURE-FEATURES.md)).
4. Trust over hype: no fabricated data, verification is real and auditable.
5. Privacy by design: data minimisation, consent, access control ([14](14-SECURITY-AND-PRIVACY.md)).
6. Server-rendered, fast, SEO-friendly, mobile-first.
7. Conventional Laravel; avoid over-engineering.

## 6. Document map
| # | Document | Purpose |
|---|---|---|
| 01 | Business strategy | Revenue models, positioning, partnerships |
| 02 | Product requirements | Functional requirements by area |
| 03 | MVP scope | P0–P3 classification |
| 04 | Roadmap | Phases 0–9 |
| 05 | Sitemap & navigation | URLs and menus |
| 06 | User journeys | Flows per persona |
| 07 | Roles & permissions | RBAC matrix |
| 08 | UI/UX design system | Tokens and components |
| 09 | Technical architecture | App structure and patterns |
| 10 | Database architecture | ERD, tables, indexes |
| 11 | Module specifications | Per-module behaviour |
| 12 | SEO & marketing | Technical SEO, tracking |
| 13 | Lead & sales workflow | Lifecycles, commission rules |
| 14 | Security & privacy | Controls and policies |
| 15 | Testing strategy | Test plan |
| 16 | Deployment & infrastructure | Hosting, backups, CI |
| 17 | SaaS future architecture | Post-MVP direction |
| 18 | Decisions & ADRs | Decision log |
| 19 | Progress tracker | Status |
| 20 | Backlog | Deferred features |
| 21 | Risks & assumptions | Risk register |
| 22 | Development workflow | Git, branching, reviews |
| 23 | Acceptance criteria | Per-phase DoD |
| 24 | Changelog | Notable changes |

## 7. Glossary
- **Property** — a single sellable/rentable unit or parcel listing (apartment, plot, shop…).
- **Project** — a developer's building/community containing many units; has unit types and (optionally) individual units.
- **Developer** — a real-estate company (stored in the `developers` table; "company" in partner UI).
- **Lead** — a person-with-intent record in the CRM, created from any inquiry source.
- **Deal** — a negotiated sale/booking connected to a lead and a property or project unit.
- **Commission agreement** — the written, per-developer/per-deal terms that define how commission is computed.
- **Verified** — a listing/developer that an admin has checked against documents; recorded with who/when/how.
