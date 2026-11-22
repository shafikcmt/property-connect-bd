# 06 — User Journeys

Each journey lists steps, system actions and success/failure states. Journeys drive feature tests ([15](15-TESTING-STRATEGY.md)).

## J1 — Buyer: discover → inquire (P0)
1. Arrives from Google/Meta (UTM captured in session) at a property or project page.
2. Browses gallery, specs; may tap **WhatsApp** (click event logged, link prefilled with property ID/title) or **Call**.
3. Submits inquiry form: name, phone, optional email, message, consent checkbox (required).
4. System: validates, rate-limits, creates Lead (`source=website`, interested property, UTM, consent timestamp+text version), notifies assigned/unassigned queue, shows confirmation with expected response time.
5. Sales agent contacts within SLA → lead moves forward ([13](13-LEAD-AND-SALES-WORKFLOW.md)).
Failure: validation error inline; spam honeypot/rate limit → generic success without lead (or 429 message); duplicate phone → attached to existing lead as new activity.

## J2 — Buyer: search → compare → save (P1)
Filters → results (URL reflects filters) → favorite (guest: session; prompted to register to persist) → add up to 4 to compare → compare table → save search (requires login) → later view in `/account`.

## J3 — Buyer: request site visit (P0)
From property/project page: choose preferred date (≥ tomorrow) and time window, contact details, consent. System creates SiteVisit (`requested`) + Lead (or links to existing). Admin confirms/reschedules; buyer gets SMS/WhatsApp/email by agent manually in MVP (automated email notification P1). Visit completes with feedback; lead status moves to Site Visit → Negotiation.

## J4 — Owner: submit property (P1)
Login/register → `/submit-property` multi-section form (owner, property, location, price, images, description, contact) → saved as `pending_review` → admin reviews (approve/request changes/reject) → owner sees status in `/account/submissions` → on approval property publishes (verification separate from approval).
Rules: images validated/re-encoded; owner documents (if uploaded) private.

## J5 — Visitor: sell/rent/valuation/assistance request (P0/P1)
Short form with request type → Lead (`request_type`) → assigned to Property Manager/Sales → follow-up.

## J6 — Sales agent: daily work (P0)
Dashboard shows my new leads, overdue follow-ups, today's visits → open lead → call/WhatsApp (logs activity) → update status (reason required for Lost/Not Interested/Invalid) → set next follow-up → schedule site visit → after visit record feedback → create deal when negotiating.

## J7 — Admin/Property manager: publish a listing (P0)
Create property (draft) → fill required fields (type, purpose, location, price, size, ≥1 image) → optional developer/project → attach amenities/media → verify (upload private evidence, note) → publish (validator checks completeness) → optionally feature until date. Edits to published price record audit entry and update `price_updated_at`.

## J8 — Sales manager: deal → commission (P1)
Lead at Negotiation → create deal (property/unit, buyer, developer, agent, negotiated price) → booking (amount, date) → sale confirmed → commission computed from the linked agreement → approve → becomes due → payments recorded (partial allowed) → outstanding balance visible → reports.

## J9 — Marketing manager: campaign (P1, Phase 7)
Create campaign with channel + UTM defaults + budget/spend entries → generate tracked links/landing page → leads auto-attributed by UTM → campaign report: clicks (if entered/imported), leads, visits, sales, cost per lead; no ROI shown unless spend and sale data exist.

## J10 — Developer partner (P2, Phase 8)
Login to portal → sees own projects and the leads assigned/shared to them (limited fields) → updates unit availability (verified by admin if public) → views campaign reports.

## J11 — Content manager (P1)
Edit pages/blog/FAQs/banners with SEO fields; cannot access leads, finance or users.

## J12 — Privacy requests (P0 process)
User asks to delete/correct data → admin locates by phone/email → export or anonymise lead (soft-anonymisation preserving aggregate stats) → audit entry. Documented in [14](14-SECURITY-AND-PRIVACY.md).
