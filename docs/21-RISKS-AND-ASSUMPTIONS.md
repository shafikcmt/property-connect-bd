# 21 — Risks & Assumptions

## Assumptions
| ID | Assumption | Validate by |
|---|---|---|
| A-01 | Primary market is Bangladesh; currency BDT; area in sqft; English-first UI | Owner confirmation |
| A-02 | Founder/solo developer maintains the system; ≤ 5 internal users at launch | Owner |
| A-03 | Hosting can provide PostgreSQL 16 and SSH/cron (VPS preferred) | Hosting decision (R-05) |
| A-04 | WhatsApp is the main buyer communication channel; manual click-to-chat suffices for MVP | Early lead feedback |
| A-05 | Developers will supply content and permit its use under written agreement | Partnership contracts |
| A-06 | Commission terms are negotiated per agreement; none universal | Owner/contracts |
| A-07 | No online payments handled by the platform | Owner |
| A-08 | Listing volume < 5,000 at launch/year 1 → PostgreSQL search adequate | Monitor |
| A-09 | Map display via external links/OSM; no paid maps API at launch | Cost review |
| A-10 | Brand name, logo, domain to be provided by owner; placeholders only until then | Owner |

## Risk register
| ID | Risk | Likelihood | Impact | Mitigation | Owner |
|---|---|---|---|---|---|
| R-01 | Scope creep / solo overload | High | High | Strict MVP ([03](03-MVP-SCOPE.md)), phases, backlog discipline | Product |
| R-02 | Package incompatibility (Laravel/Filament/Livewire) | Medium | Medium | Verify before install; ADR-0002; pin versions | Eng |
| R-03 | Legal/regulatory gaps (data protection, real-estate advertising, consent, T&C) | Medium | High | Qualified legal review before launch; draft legal pages marked pending; minimise data | Owner |
| R-04 | Fake/misleading listings damage trust | Medium | High | Verification model, moderation, report flow, no fabricated data | Ops |
| R-05 | Hosting undecided/unsuitable (no PG, no SSH) | Medium | Medium | Decide by start of Phase 9 (or earlier for staging); VPS recommended | Owner |
| R-06 | Email deliverability for notifications | Medium | Medium | SPF/DKIM/DMARC, reputable SMTP provider, in-app notifications | Eng |
| R-07 | Lead PII breach | Low | Very high | Controls in [14](14-SECURITY-AND-PRIVACY.md), access scoping, audit, backups, incident plan | Eng |
| R-08 | Poor SEO (thin/duplicate pages, slow) | Medium | High | Crawl control, unique content, performance budgets | Marketing |
| R-09 | Developer data staleness (availability/prices) | High | Medium | `price_updated_at`, verified availability flag, expiry, partner update process | Ops |
| R-10 | Search/query performance at scale | Low | Medium | Indexes, EXPLAIN reviews, Scout upgrade path | Eng |
| R-11 | Spam/bot inquiries | High | Low–Med | Honeypot, rate limits, optional Turnstile | Eng |
| R-12 | Commission disputes | Medium | Medium | Written agreements stored, calculation snapshots, immutable payment ledger | Finance |
| R-13 | Image storage/bandwidth cost | Medium | Low | Conversions, size caps, CDN, object storage later | Eng |
| R-14 | Single point of failure (one server/person) | Medium | High | Backups + restore drills, documentation, runbooks | Ops |
| R-15 | Cookie/analytics consent noncompliance | Medium | Medium | Consent gating ADR-0015 | Eng |
| R-16 | Bangla content/RTL-like layout issues (font rendering) | Low | Low | Noto Sans Bengali fallback, test with Bangla strings | Design |
| R-17 | Long-running sessions hitting cost/time limits | Medium | Low | Small commits, tracker updates, resumable docs | Eng |

## Open questions for the owner (non-blocking unless stated)
1. Hosting choice & budget (blocks Phase 9; staging earlier is nice).
2. Brand assets: logo, exact brand colours, fonts, domain (blocks launch, not development).
3. Business contact details (phone, WhatsApp, email, office address) for settings.
4. First developer partners & whether they will share data/media.
5. Legal counsel for T&C/Privacy/Disclaimers (blocks launch).
6. Email provider and optional SMS/WhatsApp provider.
7. Is Bangla content required at launch?
