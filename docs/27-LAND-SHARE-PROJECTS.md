# 27 — Land-share projects (domain model and plan)

Status: **planning only** (2026-11-23). Nothing here is built yet except what the website already offers for ordinary projects. Source: the founder's first partner developer, who runs "land share" projects. No personal data from any buyer document is stored in this repository.

## 1. How the business works
1. The developer buys land.
2. The land is divided into **shares**; each share is sold to one buyer with the promise of **one flat** in the building that will be built (e.g. a project of 90 shares → 90 flats in two 10-storey buildings).
3. Each shareholder receives a **registered sale deed (সাফ-কবলা দলিল)** for their land portion, in **batches** (one deed can list many buyers, e.g. 30 at a time), followed by **mutation (নামজারি)** in the buyer's name.
4. Shareholders pay **instalments (কিস্তি)** while the developer builds.
5. Flats are **allocated** and **handed over**.

Glossary: শতাংশ = decimal (1 শতাংশ ≈ 435.6 sq ft) · দলিল = deed · নামজারি = mutation · খতিয়ান = record of rights · কিস্তি = instalment.

Observed example: a recently completed project had 90 shares; 60 shareholders had received registered deeds in two batches of 30, 30 remaining.

## 2. Website (public)
**Built 2026-11-24:** share summary, instalment schedule, updates timeline, risk note, branded landing page (items below marked ✔). Bangla/English remains planned.
Maps onto the existing project model: status (Upcoming = shares on sale, Ongoing = under construction, Ready/Completed), total units, floors, payment plan (text), handover date, progress %.
Planned small additions (owner approval pending):
- ✔ **Share summary:** total shares, sold, remaining, price per share (price shown only when verified).
- ✔ **Registration progress:** "60 of 90 shareholders have registered deeds" (aggregate only, never names).
- ✔ **Instalment schedule** table.
- ✔ **Project updates / milestones timeline** (e.g. "Second deed batch completed — 30 shareholders").
- ✔ **Branded landing page** for a developer's project (logo, optional brand colour), always labelled "Marketed by Property Connect BD" — never presented as the developer's official site; logo use needs written permission.
- **Bangla/English** switch (UI strings now; Bangla fields for listing content).
Wording rules: no guaranteed returns or "risk-free" claims; "proposed" infrastructure labelled as proposed; full cost shown (land share **plus** construction instalments); risk note on every share project.

## 3. Developer ERP (future — builds on Phases 8, 9, 11 and, for several developers, 12)
Goal: a developer can run the whole project in the platform.

| Concept | Notes |
|---|---|
| Developer organisation | Staff roles: owner, accountant, sales, site engineer (needs tenancy only when more than one developer is onboarded — ADR-0005, Phase 12) |
| Project, land parcel | Parcel details, area in শতাংশ (decimal(10,4)), documents |
| Share | Number, area, price, status (available / reserved / sold / cancelled), linked flat |
| Shareholder (party) | Contact, consent, KYC fields **encrypted and masked**; never public |
| Deed batch | One deed ↔ many shareholders and shares (m:n); deed no., office, date, status |
| Mutation record | Per share: applied / completed / date |
| Instalment plan and payments | Schedules, receipts, append-only payments, outstanding and overdue, reminders (SMS/WhatsApp/email) |
| Flat allocation | Rule (e.g. lottery or choice order), audit trail |
| Construction milestones | Progress %, photos, updates pushed to the public timeline and to shareholders |
| Document vault | Private by default; per-shareholder access |
| Reports | Collections, outstanding, deed/mutation status, remaining shares |

Rules carried over from the project: money is `decimal`, payments append-only, every sensitive change audited, no buyer data exposed publicly, consent recorded, data minimised. NID numbers and similar identifiers are stored only when strictly required, encrypted, and shown masked.

## 4. Sequencing
1. Website launch with the first partner developer (ordinary project model + share summary/instalment table/branded page/Bangla as approved).
2. Phase 8 CRM → Phase 9 sales/payments (reused for instalments) → Phase 11 developer portal (this document's ERP) → Phase 12 multi-tenant if several developers join.

## 5. Open questions for the owner/developer
Is the "30%" in the plan a land percentage or an area? Who holds title during construction? What happens to unsold shares? Is money held in an escrow or project account? Which approvals exist (plan sanction, developer registration)? **Legal review is required before publishing any share project.**
