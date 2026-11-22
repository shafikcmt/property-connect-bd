# 17 — SaaS Future Architecture (NOT in MVP)

Purpose: ensure today's choices don't block a later multi-company product, **without building any of it now** (CLAUDE.md scope guard; ADR-0005).

## 1. Vision
Offer the platform (listings, CRM, site visits, commission tracking, marketing analytics, landing pages) to other real estate companies as subscription software, each with their own branded site/admin and isolated data.

## 2. Trigger conditions to start
Proven revenue from the in-house business; ≥ 3 external companies requesting access; stable core modules; capacity to support customers. Until then: no tenancy code, no billing, no plans.

## 3. Low-cost "SaaS-ready" habits adopted now
- Domain-oriented folders; services/actions not tied to HTTP.
- No hard-coded brand strings/contacts/rates — all from `settings` and config (a future `tenant settings`).
- Enums and policies centralised; permissions namespaced.
- Money/commission/lead logic decoupled from UI.
- Media on disks abstraction (S3-ready).
- No global state assumptions about "the one company" in queries except via settings; `Developer` ≠ tenant (a developer is a partner entity, a tenant would be a *company operating the platform*).
- UUIDs on externally exposed records.
**Not adopted now:** `tenant_id` columns, schema-per-tenant, per-tenant domains, feature flags, billing.

## 4. Options when the time comes
| Model | Pros | Cons |
|---|---|---|
| Single DB, `tenant_id` column + global scope (e.g. stancl/tenancy single-DB or custom) | Cheapest to run, simplest ops | Leak risk if a scope is missed; noisy neighbours |
| DB-per-tenant | Strong isolation, easy per-tenant backup | Ops complexity, migrations ×N |
| Separate deployments per customer | Simplest isolation, custom domains easy | Expensive/ops-heavy; slow rollout |
Recommendation (to be confirmed by a future ADR): single database with `tenant_id` + strict scoping + automated leak tests, then graduate large customers to dedicated DBs if required.

## 5. Capabilities required
Tenant onboarding, custom domains + TLS automation, white-label theming, plan/subscription/billing (Stripe or local gateways e.g. SSLCommerz/bKash — verify availability), usage limits, per-tenant roles, data export/delete, tenant-aware audit/backup, support tooling (impersonation with audit), SLA/monitoring, terms/DPA.

## 6. Migration path sketch
1. ADR + threat model. 2. Introduce `tenants` + `tenant_id` on core tables via migrations (backfill = the in-house company as tenant #1). 3. Global scope + `BelongsToTenant` trait + tests that fail on unscoped queries. 4. Tenant-aware settings/media/cache keys. 5. Tenant admin onboarding. 6. Billing. 7. Marketplace aggregation (optional — cross-tenant public portal) decisions.

## 7. Risks
Premature complexity; support burden; data-protection obligations as a processor; competing with own customers; pricing in a price-sensitive market.
