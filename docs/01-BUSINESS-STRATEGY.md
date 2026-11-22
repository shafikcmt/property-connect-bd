# 01 — Business Strategy

## 1. Positioning
An **independent** real estate marketing and sales-facilitation brand. We are not a developer and do not own inventory; we earn by connecting verified supply with qualified demand. Differentiators: verified listings, transparent information, fast response, professional presentation, multi-developer choice.

## 2. Revenue models
| ID | Model | Mechanism | MVP stage |
|---|---|---|---|
| A | Sales commission | Written partnership agreement with developer/owner; commission on successful transactions | Tracked from Phase 6; lead capture from Phase 3–5 |
| B | Marketing services | Project promotion, campaigns, lead generation, landing pages, reporting sold to developers | Manual service at launch; tooling Phase 7 |
| C | Featured listings | Paid featured property/project, homepage and premium placement | Flag + ordering in Phase 2; invoicing manual |
| D | Developer digital solutions | Websites, landing pages, CRM setup, maintenance | Services page only; delivery outside platform |
| E | SaaS subscriptions | Software for other real estate companies | **Not in MVP** (P3, [17](17-SAAS-FUTURE-ARCHITECTURE.md)) |

**Commission rule:** rates are negotiable and agreement-specific. The system stores a `commission_agreement` (type: percentage / fixed / tiered-later, basis, triggers, payment terms, validity) and computes from it. No universal rate exists anywhere in code or seed data.

**Featured listing rule:** featured status has a start/end date and is set by an admin. Paid placement must be labelled "Featured" or "Sponsored" publicly (trust and advertising-standards hygiene).

## 3. Target segments
1. Home buyers (apartments/flats) in Dhaka and major cities — primary.
2. Investors (plots, commercial, off-plan) — secondary.
3. Owners wanting to sell/rent — supply feeder.
4. Developers needing marketing and lead flow — revenue partner.
5. Expatriate Bangladeshi buyers — important: WhatsApp-first, remote-friendly flows (site-visit by proxy is a backlog item).

## 4. Go-to-market (practical)
1. Sign 2–5 developers with written agreements; onboard 10–30 verified projects/properties.
2. Launch with SEO foundation: location pages and developer/project pages with unique content.
3. Meta/Google campaigns to landing pages with UTM tracking; WhatsApp as primary conversion channel.
4. Respond to every lead promptly (SLA target in [13](13-LEAD-AND-SALES-WORKFLOW.md)).
5. Content (guides, location guides) for organic growth.
6. Learn unit economics (cost per lead, per visit, per sale) before scaling spend.

## 5. Success metrics (measured, never invented)
- Inquiries/leads per week and by source
- Lead → contacted time; lead → site visit rate; visit → deal rate
- Cost per lead and per sale (when spend entered)
- Commission earned / received / outstanding
- Verified listings count; listing freshness
- Organic traffic and ranking for target locations

## 6. Business constraints & principles
- Legal: real-estate advertising, consumer-protection and data-protection obligations in Bangladesh must be reviewed by a qualified advisor before launch (open item in [21](21-RISKS-AND-ASSUMPTIONS.md)). Disclaimers are drafted but not legal advice.
- Never publish a listing as "verified" without a recorded verification.
- Prices must show their basis (total/per sqft/negotiable) and a "last updated" date.
- Lean operations: founder can run the entire loop (publish → lead → visit → deal) from the admin panel.

## 7. Partnership model
Developer onboarding checklist: legal entity docs (private), written agreement (commission/marketing terms), project documents (approvals, RAJUK/other as applicable — private), content/licence to use images, named contact. Stored as partnership records ([11](11-MODULE-SPECIFICATIONS.md) §Partners).
