# 20 — Backlog & Future Features

All deferred ideas live here. Priority per [03](03-MVP-SCOPE.md). Moving an item into a phase requires updating roadmap + tracker.

## P1 (soon after launch / late MVP)
- Enforce the Content-Security-Policy after watching report-only traffic; add analytics hosts only with the consent banner.
- Email the sales contact when a new inquiry arrives (small; needs SMTP).
- Manual screen-reader pass (NVDA/VoiceOver); field Core Web Vitals review after 28 days of traffic; CDN/object storage for images.
- Pages CMS (static pages are Blade views today) and Settings-driven homepage sections.
- Self-hosted brand fonts (Inter, display serif) — Phase 1 uses system stack/serif fallbacks; `/dev/styleguide` living style guide (local only).
- Content-Security-Policy (report-only → enforced) — Phase 9; Filament/Livewire require nonce-aware setup.
- `php artisan test --parallel` (needs paratest) if the suite grows.
- Bangla translations for UI and key content; `name_bn` fields exposed in UI.
- Automated email to buyers on visit confirmation/reschedule.
- Image sitemap; blog scheduling; related-posts.
- CSV import of leads/properties (developer price lists).
- Lead dedupe merge tool.

## P2 (advanced)
- Buyer-facing notification centre (admin bell ships in Phase 5).
- Interactive map view/clustered map (OpenStreetMap + Leaflet) and draw-to-search.
- Saved-search email alerts, price-drop alerts, new-listing digests.
- Round-robin/rule-based lead auto-assignment; SLA escalation.
- WhatsApp Business API integration (templates, inbound logging); SMS gateway (local providers).
- Duplicate listing detection; listing expiry reminders; owner re-confirmation emails.
- ClamAV scanning of uploads; image watermarking.
- Calendar sync (Google Calendar/ICS feed) for site visits.
- Campaign cost import (Meta/Google), Meta Conversions API, Google Ads conversion import.
- Landing page builder blocks; A/B testing.
- Agent targets/incentive splits; performance leaderboards.
- Report builder / scheduled report emails; Excel exports.
- Server-side page caching; CDN image transformation.
- Admin IP allow-list, concurrent sessions management, impersonation (audited).
- Typesense/Meilisearch via Scout when volume warrants.
- Land area units (katha/bigha/decimal) conversions; BD-specific fields (RAJUK approval, mutation records) for verification.
- Virtual tours / 360° media; video hosting.
- Mortgage/EMI calculator, price trends from own data.
- Developer portal enhancements, owner dashboard (Phase 8 extension).

## P3 (SaaS / platform)
- Multi-company tenancy, custom domains, white-label themes.
- Subscriptions & billing (gateway selection).
- Public/partner REST API & webhooks; developer integration feeds.
- Mobile apps (PWA first).
- AI: description drafting, lead scoring, chatbot, valuation estimates (with disclosure & accuracy review).
- Marketplace aggregation across tenants.
- E-signature and document management for agreements.
- Online booking payments (bKash/Nagad/SSLCommerz/Stripe) with reconciliation.
- Accounting integration.

## Ideas to evaluate (unprioritised)
Expat-buyer proxy visit service; neighbourhood price index from verified data; developer rating system (needs moderation policy); virtual open-house events; referral program for brokers; lead-sharing between developers (consent!).

## Land-share projects (added 2026-11-23)
Share summary, instalment table, project-updates timeline, branded project landing page, Bangla/English switch, and the developer ERP (shareholder register, deed batches, mutation, instalment collection, flat allocation, document vault). Full plan: [27](27-LAND-SHARE-PROJECTS.md).
