# 11 — Module Specifications

> **Phase numbering note (2026-11-23):** phase tags in this document use the original numbering. The website-first roadmap in [04](04-ROADMAP.md) supersedes it — see the mapping table there (e.g. CRM = Phase 8 now).

For each module: purpose, entities, behaviour/rules, admin UI, public UI, permissions, events/notifications, tests. Phase tags in brackets. Data model detail: [10](10-DATABASE-ARCHITECTURE.md); roles: [07](07-USER-ROLES-AND-PERMISSIONS.md).

## M1 Foundation & Auth [P1]
- Auth: Laravel's starter flows (login, register, forgot/reset password, email verification, logout) with Blade/Livewire UI in brand design (starter kit choice in ADR-0008: Breeze-Livewire-style, customised; no Jetstream teams). Registration creates `buyer` role. Login throttled (5/min per email+IP). Session regenerate on login; remember-me optional.
- Admin access: Filament `canAccessPanel()` = has any admin-panel role + `status=active` + verified email. 2FA for Super Admin/Admin.
- Settings: `SettingsService::get('contact.whatsapp')`, cached; Filament settings page grouped (General, Contact, Social, Analytics, SEO defaults, Business hours, Lead SLA).
- Audit: `Auditable` trait, `AuditLogger::log()`; admin viewer (Admin/SA).
- Acceptance: [23](23-ACCEPTANCE-CRITERIA.md) §P1.

## M2 Catalog: Locations, Types, Amenities [P2]
- Locations: tree CRUD (Filament tree/relationship select), type must be valid child of parent type (country→division→district→city→area→sector→road; levels may be skipped only via explicit allowed-map), unique `path` auto-built, slug unique among siblings. Deactivating hides from public but keeps data. Seed BD divisions/districts + a small set of real city areas curated by admin (not mass-seeded).
- Property types fixed list (admin-editable names/order; group controls form fields e.g. land hides bedrooms).
- Amenities: name, icon, group; used in filters.

## M3 Developers [P2]
- Fields per 10. Verified requires a `verifications` record; `established_year` shown publicly only when verified. Public status `active` only. Logo + optional cover; contact info published only if `show_contact_publicly`.
- Related: projects (count by status), commission agreements (P6), partnership records (P8).
- Delete restricted if projects exist (archive instead).

## M4 Projects [P2]
- Lifecycle: `draft → published → archived`; project `status` (upcoming/ongoing/ready/completed) separate from publication.
- Unit types and units (P1): units optional; "available units" shown publicly only if `available_units_verified` and counts derived from units (`status=available`) — never typed-in marketing numbers.
- Media: gallery (public), floor plans, brochure (public PDF or private), video URL (YouTube/Vimeo only, validated, rendered as lite embed after consent).
- Publish validator (service): developer active, location set, ≥ 3 images, description ≥ 150 chars, status set → returns list of unmet requirements shown in admin.
- Price range derived from unit types if present; otherwise manual `price_from/to` with a label "Starting from" and a `price_updated_at`.

## M5 Properties [P2]
- Types/purpose/fields per 10. Computed: `price_per_sqft = price/size_sqft` (sale only) — accessor, never stored unless needed for sorting (then generated column).
- Public code `PC-{id}` assigned post-create; slug `{beds}-bed-{type}-{area}-{id}`; slug changes recorded in `slug_histories`.
- Publish validator: type, purpose, location, price (or negotiable flag with note), size (except land measured by katha later), ≥ 1 image (≥ 3 recommended warning), description ≥ 80 chars, contact path defined (agent/owner/developer). Draft ↔ published ↔ archived/sold/rented. Price change writes audit + `price_updated_at`.
- Verification: separate action `Verify` (requires `properties.verify`, note, optional private evidence). Editing core fields (price, location, size, owner) of a verified listing flags it "needs re-verification" (badge hidden until reconfirmed or admin override with reason).
- Featured: `is_featured` + `featured_until`; max concurrent featured on homepage configurable; public label "Featured".
- Expiry: optional `expires_at` (default 90 days for owner-submitted) → status `archived` with email nudge (P2).
- Duplicate detection (P2): same developer+unit/title+location similarity warning.
- Public contact: "Call" and "WhatsApp" use the *agency* number (settings) or the assigned agent's business number — **never** the private owner number unless the owner consented (flag `show_owner_contact`).

## M6 Public Discovery [P3]
- Listing query object `PropertySearch` (filters → Eloquent query, validated via `PropertyFilterRequest`), supports: purpose, location (includes descendants via `path LIKE`), type(s), price/size ranges, beds/baths min, status, furnishing, parking, facing, floor range, developer, project, amenities (all-of), keyword (FTS), sort, view mode. Only `published`, not soft deleted, not expired.
- Filter state in query string; Livewire component updates URL; no-JS fallback is a plain GET form.
- Cards use eager-loaded cover media + location + developer.
- Details: sections per FR-103; "Similar" = same location & type & ±25% price, capped 6; view counter incremented via queued/throttled job (bot-filtered) — optional.
- Project/developer/location pages per FR-104–106; location pages show a unique description (admin-written) and are indexable only when description + ≥ N published items.
- Homepage sections driven by `HomepageSections` service with cache; hides empty sections.
- Inquiry component: `InquiryForm` (Livewire) with `subject` (property/project/developer/general), fields name/phone/email/message/consent, honeypot + time trap, `RateLimiter` (e.g. 5/hour/IP + 3/hour/phone), creates lead through `CreateLeadFromInquiry` action, returns confirmation. WhatsApp/phone buttons are plain links (`wa.me/{number}?text=…`) with `data-track` hooks.
- Report listing: reason enum + details → `property_reports` → moderation queue.

## M7 Buyer & Seller [P4]
- Favorites: session-backed for guests (`favorites` cookie/session), persisted on login with merge. Max 200.
- Compare: session list ≤ 4 (same purpose recommended, not enforced); table columns per spec; shareable? No (backlog).
- Saved searches: serialise validated filter array; "Run" re-applies; limit 20/user.
- Recently viewed: session ring buffer (20) + DB for logged-in.
- Property submission: multi-step Livewire form with draft autosave; images via temporary upload → validated/re-encoded; creates `properties` row (`pending_review`, `submitted_by`); confirmation email; status tracker; owner can edit while `pending_review`/`rejected` (resubmits), not after publish (requests change via note → admin).
- Sell/Rent/Valuation/Assistance: leads with `request_type` and extra fields in `meta` (property type, area, expected price).
- Newsletter: double opt-in email (P1); unsubscribe link; no marketing without consent.

## M8 CRM — Leads [P5]
- Create: public forms (auto), admin manual (walk-in/phone/WhatsApp), CSV import (P2). Normalise phone → E.164; dedupe: same phone (or email) with non-terminal status in last 90 days → new lead marked `duplicate` of existing **or** appended as activity on existing (setting; default append + notify assignee).
- Assignment: manual or "Assign to me"; auto-assign rules P2 (round-robin backlog). On assign: activity + notification. Unassigned leads highlighted to Sales Manager.
- Pipeline: statuses & transitions in [13](13-LEAD-AND-SALES-WORKFLOW.md); `LeadStatusService::transition($lead, $to, $reason)` validates, writes history + activity, updates timestamps, fires events. Reason mandatory for lost/not interested/invalid/duplicate.
- Timeline: unified activities + notes + status changes + follow-ups + visits, newest first; quick-log buttons (Call, WhatsApp, Email, Meeting, Note). `tel:`/`wa.me` links from admin log activity "attempted" optionally.
- Priority: low/normal/high/urgent; default `normal`; budget ≥ threshold or site visit request → `high` (configurable).
- Views: table with filters (status, assignee, source, priority, date, overdue), saved views P2; kanban-lite P1 within phase.
- Privacy: contact reveal logged for roles with masked view; export requires permission + audit; consent fields visible; anonymise action.
- SLA: first-response target (setting, default 30 min business hours); breached leads highlighted.

## M9 Follow-ups [P5]
Create from lead (due date/time, type, notes, assignee); completing records activity; next follow-up date denormalised on lead; scheduler sends reminders (due within 15 min / overdue digest daily 9am BST); dashboard widget "My follow-ups" and "Overdue".

## M10 Site Visits [P5]
- Request sources: public form, admin. Fields per FR-305. State machine: `requested → confirmed → (rescheduled → confirmed)* → completed | cancelled | no_show`.
- Confirm requires scheduled_at, assigned agent, meeting point; double-booking warning for same agent within ±1h.
- Reschedule keeps history (activity). Calendar view in Filament (list + month widget; calendar package only if cheap).
- Reminders: to agent 24h/2h before; buyer reminder is **manual/WhatsApp link** in MVP (automated SMS/WhatsApp API = backlog).
- Completion: feedback (interest level, notes) → suggests lead status change.

## M11 Notifications [P5]
Laravel Notifications: database + mail (queued). Events listed in 09 §12. Preferences per user (P2). Admin bell via Filament database notifications.

## M12 Sales: Deals & Bookings [P6]
Deal created from lead (requires lead in `negotiation` or `site_visit`+ manager override). Links property **or** project unit + developer + agent. State: `negotiation → booked → sold`; `cancelled/lost` terminal. Booking records amount/date; moving to `sold` requires sale price & date and marks property `sold` (or unit `sold`) and lead `converted` — in one transaction. Cancelling reverses unit/property state with audit.

## M13 Commission [P6]
- Agreement: scope (developer / project / property), type percentage|fixed, rate or amount, basis, trigger event, validity, document, terms, status. Overlapping active agreements resolved by most specific scope then latest `valid_from`; ambiguity blocks calculation with an error asking for explicit selection.
- Calculation: `CommissionCalculator::calculate(Deal, Agreement)` → value object (basis, rate snapshot, amount, rounding to 2 dp half-up). Fixed amounts supported; tiered later. **No default rate.**
- Lifecycle: `pending` (calculated) → `approved` (manager) → `due` (trigger met, due date set) → `partial_paid` → `paid`; `cancelled`. Payments append-only; outstanding = payable − Σpayments. Overpayment rejected.
- Reports: earned (approved+), received (Σpayments), outstanding; by developer/agent/period; CSV export (permission + audit).
- Agent share (internal incentive) — backlog.

## M14 Marketing & Analytics [P7]
- Campaigns: channel enum (meta, google, instagram, organic, offline, referral, other), UTM defaults, budget, costs entries, status.
- UTM capture: middleware stores first-touch + last-touch UTM/referrer/landing URL in session (30 days cookie only with consent for non-essential? — attribution cookie is first-party functional; document in cookie policy, gate analytics tags by consent). Lead creation snapshots them; campaign matched via `utm_campaign` slug.
- Tracking events: `track()` endpoint (throttled, CSRF-exempt via signed beacon or Livewire) for whatsapp_click, phone_click, inquiry_submit, visit_request; stored minimally.
- GA4/Meta Pixel: IDs from settings; scripts injected only after consent (cookie banner); events pushed to dataLayer/fbq. Server-side CAPI = backlog.
- Reports: per campaign leads, visits, deals, cost per lead (= spend ÷ leads, shown only when spend > 0), ROI only when commission data exists; impressions/clicks only if manually entered/imported and labelled as such.
- Landing pages: template-based (hero, benefits, form, FAQ) bound to campaign/project.

## M15 CMS [P1–P3]
Pages (rich text sanitised with an allow-list purifier), blog (draft/published, scheduled), FAQs (with FAQPage schema), banners (placement/dates), testimonials (**real, with consent date**), SEO fields on all public entities, navigation & footer from settings/menu items. Legal pages seeded as **draft templates requiring legal review** before publish.

## M16 Partner Portal [P8]
Role `developer`/agent: scoped dashboards, own projects' availability updates (admin-approved before public), shared leads with masked fields as permitted, performance reports. Agent management: profiles, assigned leads, targets (P2).

## M17 System [P1+]
Users/roles/permissions UI (SA), settings, audit viewer, notifications, reports hub, data-retention commands (`pcbd:retention`), backups health widget (P9).
