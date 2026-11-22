# 03 — MVP Scope & Priorities

**Priorities:** P0 must-have for launch · P1 important (soon after launch / late MVP) · P2 advanced · P3 future SaaS.
**Launch (MVP) = all P0 + selected P1** delivered by Roadmap Phases 1–5 plus the minimum of Phase 6 and 9. Solo-developer feasibility is the filter.

## Public website
| Feature | Pri | Phase |
|---|---|---|
| Homepage (hero search, featured, latest, projects, locations, CTAs, footer) | P0 | 3 |
| Property listing: filters, sort, search, pagination, grid/list | P0 | 3 |
| Property details (gallery, specs, amenities, map link, similar, inquiry, WhatsApp, call) | P0 | 3 |
| Project listing + details | P0 | 3 |
| Developer directory + details | P0 | 3 |
| Location pages (hierarchy, listings, description) | P0 | 3 |
| Inquiry forms (property, project, developer, contact) | P0 | 3/5 |
| Site-visit request form | P0 | 5 |
| Static pages (About, Contact, FAQ, legal) | P0 | 3 |
| SEO (meta, canonical, sitemap, robots, schema, breadcrumbs) | P0 | 3 |
| Services page | P1 | 3 |
| Blog + categories | P1 | 3 |
| Share, report-listing | P1 | 3/4 |
| Interactive map view (listing page) | P2 | backlog |
| Video embeds | P1 | 3 |
| Newsletter signup (store consent; sending later) | P1 | 4 |
| Testimonials (real only) | P1 | 3 |

## Buyer / seller
| Feature | Pri | Phase |
|---|---|---|
| Register/login/profile | P1 | 1/4 |
| Favorites (guest via session → account) | P1 | 4 |
| Saved searches (without alerts) | P1 | 4 |
| Comparison (≤4) | P1 | 4 |
| Recently viewed | P1 | 4 |
| Submit property (moderated) | P1 | 4 |
| Sell / Rent / Valuation / Assistance requests (become leads) | P0 (as plain leads) / P1 (account tracking) | 4/5 |
| Saved-search email alerts, price-drop alerts | P2 | backlog |
| Admin in-app notifications (bell) | P1 | 5 |
| Buyer-facing notification centre | P2 | backlog |

## Admin
| Feature | Pri | Phase |
|---|---|---|
| Auth, roles (Super Admin, Admin, Sales Manager, Sales Agent, Content Manager, Property Manager), audit log | P0 | 1 |
| Property/project/developer/location/amenity/media management | P0 | 2 |
| Verification + featured flag | P0 | 2 |
| Units & floor plans | P1 | 2 |
| CMS: pages, FAQ, banners, settings, SEO fields | P0 | 1–3 |
| Blog, testimonials, navigation/footer editing | P1 | 3 |
| Leads: capture, status, assignment, notes, timeline | P0 | 5 |
| Follow-ups + reminders | P0 | 5 |
| Site visits (request, confirm, reschedule, status) | P0 | 5 |
| Dashboard (counts, new leads, follow-ups due) | P0 | 5 |
| Deals, bookings, commission agreements & payments | P1 | 6 |
| Dashboard money widgets, reports | P1 | 6 |
| Campaigns, UTM capture on leads, GA4/Meta Pixel IDs in settings | P1 | 7 (UTM capture minimal in 5) |
| Tracking events (WhatsApp/phone clicks) | P1 | 7 |
| Campaign cost/ROI, landing page builder | P2 | 7+ |
| Developer/agent portal | P2 | 8 |
| Reports exports, performance dashboards | P2 | 8 |

## Cross-cutting
| Feature | Pri |
|---|---|
| WhatsApp click-to-chat links | P0 |
| Backups, error logging, HTTPS, rate limiting | P0 |
| Bangla (bn) content support | P1 (UI strings prepared for i18n from Phase 1; full translation backlog) |
| Basic analytics (GA4 via settings; internal counts) | P1 |
| Map embeds/coordinates | P1 (coordinates stored; link to external maps; no paid maps API) |

## Explicitly out of MVP (P2/P3)
Multi-company tenancy, subscriptions/billing, mobile apps, AI recommendations/valuation, automated drip campaigns, WhatsApp Business API automation, payment gateway, e-signature, public developer API, advanced BI, Redis/queues beyond DB driver.
Tracked in [20](20-BACKLOG-AND-FUTURE-FEATURES.md).

## MVP feasibility check
Phases 1–5 (+ a thin slice of 6 and 9) are the launch path. If time is short, the **minimum viable launch** is: Phase 1 + 2 + 3 + the lead/site-visit parts of Phase 5 + Phase 9 hardening. Buyer accounts (Phase 4) and commission (Phase 6) can follow within weeks of launch because early leads are managed manually in admin.
