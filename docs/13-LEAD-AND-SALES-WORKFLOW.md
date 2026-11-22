# 13 — Lead & Sales Workflow

## 1. Lead lifecycle
```
New → Contacted → Qualified → Interested → Site Visit → Negotiation → Converted
 \________________ any non-terminal state ________________/→ Lost | Not Interested | Invalid | Duplicate
```
### Statuses
| Status | Meaning | Entry condition | Required on entry |
|---|---|---|---|
| `new` | Captured, untouched | auto | — |
| `contacted` | First contact attempt succeeded | agent action | activity logged |
| `qualified` | Genuine buyer, budget/need/timeline known | | budget or preferred location filled |
| `interested` | Wants specific property/project | | interested property/project set |
| `site_visit` | A visit is requested/scheduled/done | auto when visit confirmed (or manual) | linked site visit |
| `negotiation` | Price/terms discussion | | note |
| `converted` | Deal sold | **auto only** by deal sale (M12) or manager override with note | deal link |
| `lost` | Bought elsewhere / unresponsive | | reason (picklist + text) |
| `not_interested` | Declined | | reason |
| `invalid` | Fake/wrong number/spam | | reason |
| `duplicate` | Same person already has a lead | | `duplicate_of_id` |
### Transition rules
- Forward moves may skip stages (e.g. New → Qualified) but each is recorded; backwards moves allowed only for managers (with reason). Terminal states (`converted`, `lost`, `not_interested`, `invalid`, `duplicate`) can be reopened by a manager → `contacted` with reason.
- `converted` cannot be set manually without a deal (manager override logged).
- Every transition → `lead_status_histories` + `lead_activities` (type `status_change`) + audit when manager override.
### SLA & hygiene
- First response target: **30 min during business hours / next morning otherwise** (setting). `new` leads older than SLA are flagged red on the dashboard.
- Stale rule: non-terminal lead with no activity for 14 days appears in "Needs attention" list.
- A lead always has either an assignee or sits in the "Unassigned" queue visible to Sales Managers.

## 2. Lead fields
Name · Phone (E.164) · Email (optional) · Request type · Interested property / project / developer · Budget (min/max) · Preferred location · Property type · Purpose · Source · Campaign · UTM snapshot · Assigned user · Status · Priority · Notes · Next follow-up · Consent (timestamp + text version + channel) · Duplicate link · Closed reason.
Data minimisation: email optional; no national ID/passport/financial docs stored on leads; request only what is needed to respond.

## 3. Capture → triage
1. Form submitted → validation, honeypot, rate limits.
2. Phone normalised; duplicate check (90 days, non-terminal). Duplicate → append activity "New inquiry for {property}" to existing lead, notify assignee, no new lead (default).
3. Lead created `new`, source set from form/UTM, priority computed.
4. Notification: assignee (if rule) or Sales Managers; email + database.
5. Manager/agent assigns; agent contacts; logging begins.

## 4. Follow-up discipline
Every non-terminal lead past `new` should have a `next_follow_up_at`. After logging a contact activity the UI prompts "Schedule next follow-up?". Reminder notifications; overdue shown prominently. Completing a follow-up requires an outcome note (≥ 1 line).

## 5. Site-visit workflow
`requested → confirmed → completed` (happy path); `rescheduled` returns to confirmed with new time; `cancelled`; `no_show`. Rules: confirmation needs date/time, agent, meeting point; completion needs feedback; no-show prompts re-engagement follow-up. Visit events update the lead (status `site_visit` on confirm; suggestions after completion). Buyer-facing confirmation is sent by the agent via WhatsApp/phone in MVP using a prefilled message template.

## 6. Deal workflow (Phase 6)
`Lead (negotiation) → Deal: negotiation → booked (booking amount recorded) → sold (sale price, date)`; `cancelled` or `lost` otherwise.
- Creating a deal requires: lead, developer (or owner), property or unit/project, agent, listed price. Negotiated price is mutable with history; sale price fixed at `sold`.
- Marking `sold`: property/unit → sold/unavailable, lead → `converted`, commission calculation triggered/suggested; all in one DB transaction.
- Cancelling a booked/sold deal requires manager + reason; reverses availability; commission cancelled/adjusted with audit.

## 7. Commission workflow (Phase 6)
1. **Agreement first.** A commission can be created only from an active, documented agreement (developer/project/property scope). No agreement → deal flagged "No commission agreement" (manager decision).
2. **Calculation:** per agreement: percentage × basis (sale price | negotiated | booking amount) or fixed amount; rounding half-up to 2 dp; rate/terms snapshotted on the commission record.
3. **Statuses:** `pending` (calculated) → `approved` (Sales Manager/Admin) → `due` (trigger event reached, e.g. registration/handover/payment milestone, with due date) → `partial_paid` → `paid`; `cancelled` possible before `paid` (or via adjustment after).
4. **Payments:** recorded against commission, append-only, each with date/method/reference; Σ payments ≤ payable; status derives automatically.
5. **Reporting:** earned (approved+), received, outstanding, due this month, by developer/agent; exports audited.
6. **Controls:** only `commissions.approve` can approve; only `commissions.pay` can record payments; Sales Agents see commission only on own deals (if permission granted); changes audited; agreement documents private.
Illustration only (not a business rule): *an agreement stating 2% of sale price on registration would yield 2% × sale price once the registration trigger is reached.* The platform stores no default rate.

## 8. Roles in the workflow
Sales Agent: works assigned leads/visits. Sales Manager: assigns, monitors SLA, approves deals/commission. Property Manager: supports listing questions/verification. Marketing Manager: sources & campaigns (masked contacts). Admin/SA: everything + overrides.

## 9. Consent & privacy in workflow
Consent captured at form (explicit checkbox, versioned text). Marketing messages (WhatsApp/email/SMS) only to contacts with consent for that purpose. Leads can be anonymised on request; invalid/duplicate purged per retention. Agents see only assigned leads; contact reveal for masked roles is audited. See [14](14-SECURITY-AND-PRIVACY.md).

## 10. KPIs (computed from recorded data only)
New leads/day · time-to-first-contact · contact rate · qualified rate · visit rate · visit→deal rate · average days to convert · lost reasons distribution · commission earned/received/outstanding · cost per lead (when spend entered).
