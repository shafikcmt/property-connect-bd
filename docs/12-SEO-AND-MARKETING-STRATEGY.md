# 12 — SEO & Marketing Strategy

## 1. Technical SEO requirements
- Server-rendered HTML for all public content (Livewire components render initial HTML; no content that requires JS to appear).
- **Slugs:** lowercase, hyphenated, human-readable; properties end in `-{id}`; stable (changes → 301 via `slug_histories`).
- **Titles & descriptions:** templates with fallbacks, overridable per entity (`seo_title`, `seo_description`):
  - Property: `{beds} Bed {Type} for {Sale|Rent} in {Area}, {City} | Property Connect BD` (≤ 60 chars where possible).
  - Project: `{Project} by {Developer} – {Area} | Property Connect BD`.
  - Location: `Properties in {Area}, {City} – Apartments, Plots & Projects`.
  - Unique meta description (≤ 155 chars) generated from real fields, never keyword-stuffed.
- **Canonical:** every page self-canonical; filtered/sorted/paginated listing variants canonicalise to the base listing (pagination pages self-canonical with `rel=prev/next` optional), `noindex,follow` for filter combinations except curated landing pages.
- **Open Graph / Twitter cards:** title, description, image (cover 1200×630 conversion), URL, type.
- **Sitemap:** `/sitemap.xml` index → properties, projects, developers, locations, pages, blog; only published/indexable; `lastmod` from `updated_at`; regenerated daily and on publish (queued); split at 5,000 URLs.
- **robots.txt:** allow public; `Disallow: /admin`, `/account`, `/portal`, `/search`, `/compare`, `/favorites`, `/lp/*?*` utm variants; link to sitemap; env-aware (staging fully disallowed + `X-Robots-Tag: noindex`).
- **Structured data (JSON-LD):** `Organization`/`RealEstateAgent` (site), `WebSite` + `SearchAction`, `BreadcrumbList`, `FAQPage`, `Article`, `Residence/Apartment/House`/`Product`+`Offer`-style `RealEstateListing` for properties (name, image, address, geo, price+currency, floorSize, numberOfRooms) — only with real values; `Organization` for developers; `Place` for locations. Validate with a test that parses JSON-LD.
- **Breadcrumbs** UI + JSON-LD.
- **Images:** descriptive alt (enforced at upload), `width/height` attrs, lazy loading, WebP/AVIF, descriptive filenames in sitemaps (image sitemap P2).
- **Performance/Core Web Vitals:** LCP image preload; no layout shift; minimal JS; font self-hosting; cache headers; critical CSS inline optional. Targets in [02](02-PRODUCT-REQUIREMENTS.md) NFR-1.
- **Mobile:** mobile-first, tap targets, viewport meta.
- **International:** `lang="en"`; hreflang only when Bangla content exists.
- **Status codes:** unpublished/removed property → 410 or 301 to project/location when sold (policy: sold/rented stays 200 with "No longer available" + similar listings for 90 days, then 410).

## 2. Crawl control — avoid low-quality page explosions
Indexable: base listings, purpose pages, location pages **with unique copy and ≥ 3 published items**, curated location+type pages (admin-flagged `is_seo_landing`), projects, developers (≥ 1 published project), blog, static pages. Everything else (arbitrary filter combos, deep pagination > page 5 beyond sitemap, search, compare, favourites, empty locations) is `noindex` and excluded from the sitemap. No auto-generated doorway pages. Thin/expired content is archived.

## 3. Content strategy
- Location guides (one genuinely useful guide per priority area: transport, schools, price ranges *citing dated sources*), buying/selling/legal/investment guides, market notes. Each article: author, date, last reviewed, sources. Legal content marked informational and reviewed by a professional.
- Developer & project pages carry unique, verified descriptions (avoid copying developer brochures verbatim).
- Internal linking: property → location, project, developer, similar; location → child areas, top projects; blog → relevant listings/locations; footer links to hubs; breadcrumbs.
- Editorial calendar tracked in backlog (not in repo code).

## 4. Local & off-site
Google Business Profile for office; consistent NAP; developer partner backlinks; social profiles (Facebook, Instagram, YouTube, LinkedIn); Google Search Console + Bing Webmaster verification via settings/meta tag.

## 5. Marketing channels & tracking
| Channel | Use | Tracking |
|---|---|---|
| Organic search | Long-term | GSC, GA4 |
| Meta (Facebook/Instagram) | Project promotion, lead ads/landing pages | UTM, Meta Pixel (consent), CAPI later |
| Google Ads | High-intent location/project searches | UTM, GA4, Ads conversion (later) |
| WhatsApp | Primary conversational channel | click events, manual logging |
| Email/newsletter | Retention, listings digest | consent, unsubscribe |
| Offline/referral | Events, brokers | source = `offline/referral`, manual |
UTM convention: `utm_source` (facebook|instagram|google|newsletter|…), `utm_medium` (cpc|social|email|organic|referral), `utm_campaign` (campaign slug), optional `utm_content`, `utm_term`.

## 6. Analytics & consent
GA4 and Meta Pixel IDs live in settings; scripts load **only after cookie consent** (banner records choice, versioned; Cookie Policy documents each tag). Event taxonomy: `view_property`, `view_project`, `generate_lead` (inquiry), `request_site_visit`, `click_whatsapp`, `click_call`, `favorite_add`, `compare_add`, `search`. Internal analytics (tracking_events + leads) reports only what was recorded. **No invented metrics**; impressions/clicks only if imported/entered.

## 7. Conversion principles
Single clear CTA per section; WhatsApp + call + form on every detail page (sticky mobile bar); short forms (name, phone, optional message); consent text plain-language; confirmation states; response-time promise only if SLA is operationally met.

## 8. SEO acceptance checklist (Phase 3/9)
Titles/descriptions unique; canonical correct; robots/sitemap valid; JSON-LD valid; no indexable duplicates; Lighthouse SEO ≥ 95 & Performance (mobile) ≥ 85 on key templates; 404s/redirects reviewed; GSC submitted.
