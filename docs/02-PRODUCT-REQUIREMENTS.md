# 02 — Product Requirements

> **Phase numbering note (2026-11-23):** phase tags in this document use the original numbering. The website-first roadmap in [04](04-ROADMAP.md) supersedes it — see the mapping table there (e.g. CRM = Phase 8 now).

Requirement IDs (`FR-xxx`) are referenced by [23-ACCEPTANCE-CRITERIA](23-ACCEPTANCE-CRITERIA.md) and tests. Priority per [03](03-MVP-SCOPE.md). Behavioural detail lives in [11](11-MODULE-SPECIFICATIONS.md).

## 1. Public discovery
- **FR-101 (P0)** Homepage sections: hero with search (Buy/Rent, location, type, budget), featured properties, latest listings, featured/upcoming projects, ready properties, commercial, popular locations, featured developers, why-us, how-it-works, articles, buyer/seller/developer CTAs, newsletter, footer. Sections with no real data are hidden (no placeholders in production).
- **FR-102 (P0)** Property listing: grid/list toggle, filters (sale/rent, location, type, min/max price, min/max size, bedrooms, bathrooms, status, furnishing, parking, facing, floor, developer, amenities), sorting (newest, price asc/desc, size, featured first), keyword search, pagination, "Featured"/"Verified" badges. Filter state reflected in the URL (shareable, crawl-controlled).
- **FR-103 (P0)** Property details page with gallery, price, price/sqft (computed), property ID, location, specs, amenities, floor plans, payment plan text, nearby facilities, owner/developer block, similar properties, share, favorite, report, call, WhatsApp, inquiry and site-visit forms.
- **FR-104 (P0)** Projects: listing with status (upcoming/ongoing/ready), filters, search; details with overview, developer, gallery, video, location, construction status, floors/units, unit types, floor plans, prices, payment plan, handover date, brochure, inquiry, site visit, similar projects. "Available units" shown only when marked verified.
- **FR-105 (P0)** Developer directory + detail (logo, profile, verified status, established year if verified, projects by status, contact, inquiry form).
- **FR-106 (P0)** Location hierarchy Country → Division → District → City → Area → Sector → Road; location pages with listings, projects, description, popular types, nearby places. Only locations with content are indexable.
- **FR-107 (P1)** Services, About, Contact, FAQ, Blog (categories), legal pages (Privacy, Terms, Cookie, Disclaimer).
- **FR-108 (P2)** Map view on listing page.

## 2. Buyers, sellers
- **FR-201 (P1)** Register/login/logout/password reset/email verification/profile.
- **FR-202 (P1)** Favorites (properties, projects); guests can favorite via session and merge on login.
- **FR-203 (P1)** Saved searches (store filter set; alerts are P2).
- **FR-204 (P1)** Compare up to 4 properties on the specified attributes.
- **FR-205 (P1)** Inquiry history, site-visit history, recently viewed.
- **FR-206 (P1)** Submit property: owner info, property details, location, price, size, type, images, description, contact; status `pending_review`; **never public until admin approval**.
- **FR-207 (P0/P1)** Sell, Rent, Valuation, Assistance request forms — each creates a lead with `request_type`.

## 3. CRM & operations
- **FR-301 (P0)** Every inquiry/request creates a lead (with consent, source, UTM, interested property/project). Duplicate detection by normalized phone/email within a window → flags/links rather than silently dropping.
- **FR-302 (P0)** Lead lifecycle: New → Contacted → Qualified → Interested → Site Visit → Negotiation → Converted; terminal: Lost, Not Interested, Invalid, Duplicate. Every transition recorded in history with actor and reason where required.
- **FR-303 (P0)** Assignment (manual; round-robin is P2), priority, notes, activity timeline (call, WhatsApp, email, meeting, visit, note, status change, assignment).
- **FR-304 (P0)** Follow-ups with due date; overdue surfaced on dashboard; reminder notification.
- **FR-305 (P0)** Site visits: request → confirm → reschedule → complete/cancel/no-show; assigned agent, developer contact, meeting point, feedback.
- **FR-306 (P1)** Deals/bookings/sales linked to lead, property or unit, developer, agent; negotiated vs sale price.
- **FR-307 (P1)** Commission agreements, calculated commission, approval, due, partial payments, outstanding balance.
- **FR-308 (P1)** Campaigns, UTM capture, conversion events, per-campaign lead/visit/sale counts, cost per lead when spend entered.

## 4. Admin / CMS
- **FR-401 (P0)** CRUD with authorization and audit for developers, projects, properties, locations, amenities, types, media, units, floor plans.
- **FR-402 (P0)** Verification workflow (who, when, evidence note) and featured-until date.
- **FR-403 (P0)** Moderation queue for submitted properties and reported listings.
- **FR-404 (P0/P1)** CMS: pages, FAQ, banners, settings (contact numbers, WhatsApp number, social, analytics IDs), SEO meta per entity; blog/testimonials/nav (P1).
- **FR-405 (P0)** Users, roles, permissions; audit log viewer.
- **FR-406 (P0)** Dashboard of real counts only.

## 5. Non-functional requirements
- **NFR-1 Performance:** Public pages LCP < 2.5s on mid-range mobile over 4G for cached pages; images responsive, lazy-loaded, WebP/AVIF where possible; N+1 prevention enforced in tests.
- **NFR-2 SEO:** server-rendered content; canonical URLs; sitemap; structured data ([12](12-SEO-AND-MARKETING-STRATEGY.md)).
- **NFR-3 Accessibility:** WCAG 2.1 AA target: contrast, labels, focus, keyboard, alt text, reduced motion.
- **NFR-4 Responsiveness:** mobile-first; supported from 360px.
- **NFR-5 Security/privacy:** [14](14-SECURITY-AND-PRIVACY.md).
- **NFR-6 Reliability:** daily DB backups, error logging, graceful error pages.
- **NFR-7 Maintainability:** conventional Laravel, ≥ feature tests for each module, Pint clean.
- **NFR-8 Localisation:** English first; strings via `__()`; Bangla-ready; BDT (৳) currency with lakh/crore display helper; area in sqft.
- **NFR-9 Browser support:** last 2 versions of Chrome, Safari, Firefox, Edge; Android Chrome and iOS Safari.
- **NFR-10 Data integrity:** monetary values never floating point; status changes validated by enums/state rules.
