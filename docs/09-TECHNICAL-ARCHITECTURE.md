# 09 — Technical Architecture

## 1. Stack (see ADR-0001/0002)
PHP 8.3+, Laravel (current stable verified at Phase 1; record exact versions in ADR-0002), Blade, Livewire, Alpine.js (bundled with Livewire), Tailwind CSS, Vite, Filament admin, PostgreSQL 16, Spatie Laravel Permission, Spatie Media Library (ADR-0006), Laravel Queue (database driver), Scheduler, Notifications (mail + database), Pest, Laravel Pint. Optional later: Redis, Meilisearch/Typesense (only on demonstrated need).
Not used: microservices, separate SPA, GraphQL, public REST API, Docker (optional dev convenience only), Redis (MVP).

## 2. Runtime topology (MVP)
Single Laravel app → PostgreSQL. Web server (Nginx/Apache) → PHP-FPM. One queue worker (`queue:work --max-time=3600` via Supervisor/cron) and `schedule:run` every minute. Storage: local `public` disk for public images (served via CDN-optional), `private` disk (outside webroot) for documents. See [16](16-DEPLOYMENT-AND-INFRASTRUCTURE.md).

## 3. Application structure (domain-oriented inside `app/`)
```
app/
  Actions/            single-purpose operations (e.g. Leads/CreateLeadFromInquiry)
  Services/           multi-step workflows (e.g. Properties/PropertyPublisher, Commissions/CommissionCalculator)
  Enums/              PropertyStatus, LeadStatus, SiteVisitStatus, CommissionStatus, Purpose, ...
  Models/             Eloquent models grouped by domain subfolders only if >15 models (Catalog, Crm, Sales, Cms)
  Policies/
  Http/
    Controllers/      thin; Public/ (SEO pages), Account/, Webhooks/ (future)
    Requests/         Form Requests
    Middleware/
  Livewire/           Public/ (filters, favorites, compare, forms), Account/
  Filament/
    Resources/        per domain group; Widgets/; Pages/
  Notifications/
  Jobs/
  Support/            Money, Slug, Phone normalizer, SeoMeta, helpers
  Observers/          AuditObserver, slug history
resources/views/{layouts,components/ui,components/property,pages,livewire,emails}
routes/{web.php,account.php,console.php}
database/{migrations,factories,seeders}
tests/{Unit,Feature,Browser?}
docs/
```
Rationale: modular by domain without packaging overhead. If the SaaS path ever happens, domain folders map to future modules ([17](17-SAAS-FUTURE-ARCHITECTURE.md)).

## 4. Layering rules
- **Routes/Controllers/Livewire** → authorise, validate, delegate, return view/redirect. No business rules.
- **Actions/Services** → business logic, transactions, events. Receive validated DTO/array, not Request.
- **Models** → relationships, scopes (`published()`, `featured()`), casts, accessors; no workflow logic beyond invariants.
- **Policies** → authorization; **Form Requests** → validation (shared by Livewire via rules classes where practical).
- **Events/Listeners** only where decoupling is valuable (`LeadCreated` → notify, audit). Avoid event spaghetti.
- No repository layer. No generic service base classes.

## 5. Routing
Public routes in `routes/web.php` with named routes (`properties.index`, `properties.show`). Account routes require `auth` + `verified`. Admin routes owned by Filament panel at `/admin`. Route model binding by `slug` (public) with explicit scoping to published records; ID suffix in property slug for uniqueness; `Route::bind` enforces `Published` scope for guests.

## 6. Validation
Form Requests per form; shared rule helpers for phone (BD format, E.164 normalisation), price, size. Server-side always; client hints only. Max lengths aligned with DB columns. Uploads: `image`, `mimes`, `max`, dimensions, re-encode through image library (strips EXIF/GPS from user uploads).

## 7. Authorization
Spatie permissions + Policies ([07](07-USER-ROLES-AND-PERMISSIONS.md)). `Gate::before` grants Super Admin. Global scopes are **not** used for authorization (they hide bugs); use explicit query scoping helpers (`Lead::visibleTo($user)`).

## 8. Database conventions → [10](10-DATABASE-ARCHITECTURE.md)
## 9. Media storage
Spatie Media Library on `media` disk config: `public` collection (property/project images, developer logos) and `private` collection (verification docs, owner documents, brochures if restricted). Conversions queued. Private files served by authorised controller (`Storage::download`/temporary signed URL). Filenames randomised; original filenames kept as metadata only. Max upload sizes configured; limit count per listing (e.g. 30).

## 10. Search architecture
MVP: PostgreSQL. Filters via indexed columns; keyword via `tsvector` generated column (`english` or `simple` config) + GIN index and `ILIKE`/trigram (`pg_trgm`) for names/locations. Location autocomplete from `locations` with `pg_trgm`. Sorting uses indexed columns. Pagination: `simplePaginate`/cursor for deep pages; page cap to prevent scraping depth abuse. Upgrade path: Laravel Scout + Meilisearch when listing volume > ~50k or relevance needs grow (backlog).

## 11. Caching
Driver: `database` or `file` (MVP). Cache: settings, nav/footer, location tree, amenity lists, homepage sections (5–15 min), sitemap (1h, invalidated on publish). Full-page caching of guest HTML (e.g. Spatie ResponseCache) is an optional Phase 9 optimisation — must exclude forms with CSRF tokens (use JS token refresh) → decide with measured need. Never cache per-user or lead data in shared keys.

## 12. Notifications, queues, scheduler
- Notifications: mail + database channels. Events: new lead (to assignee/managers), assignment, follow-up due, visit requested/confirmed/rescheduled, submission received/approved, commission due.
- Queue: `database` driver; queues: `default`, `mail`, `media`. Failed jobs table + alert. All mail and image conversions queued.
- Scheduler: follow-up reminders (every 5 min), visit reminders (hourly), expire featured listings (daily), sitemap regeneration (daily), prune old data/retention tasks (daily), DB backup (daily, e.g. spatie/laravel-backup), `queue:prune-failed`, `model:prune`.

## 13. Error handling & logging
Environment-aware error pages; `report()` to log channel `daily`; optional Sentry/Flare (backlog, paid/free-tier decision). Structured logs; **never** log PII/secrets (lead phone, tokens). Domain exceptions (e.g. `InvalidStatusTransition`) rendered as validation errors. Health route `/up` (Laravel default).

## 14. Audit
`audit_logs` (who, what, auditable type/id, old/new values for whitelisted fields, ip, user agent, at). Written by an Observer trait on key models (`Auditable`) and explicitly for events like login-as-admin, export, verification, commission payment, role change. Sensitive fields (passwords, tokens) excluded; PII in audit stored minimally.

## 15. SEO implementation
`SeoMeta` value object + `<x-seo>` component (title, description, canonical, robots, OG, Twitter, JSON-LD). Sitemap via scheduled generator (or spatie/laravel-sitemap). See [12](12-SEO-AND-MARKETING-STRATEGY.md).

## 16. Performance
Eager loading (`Model::preventLazyLoading(! app()->isProduction())`), indexes, image conversions, HTTP caching headers for assets, Vite hashed assets, gzip/brotli at web server, OPcache, `config:cache/route:cache/view:cache/event:cache` in deploy, pagination everywhere, limit homepage queries with cache. Budget: ≤ 25 queries per public page.

## 17. Internationalisation
`__()` for all strings; `lang/en`, `lang/bn` (bn mostly empty at launch). Entity content translations are a backlog item (single-language content in MVP; `name_bn` optional columns only on locations/amenities/types where cheap).

## 18. Configuration
All environment-specific values in `.env` (never committed); `config/pcbd.php` for app-specific non-secret defaults (currency, area unit, featured limits, SLA hours, upload limits). Business-editable values (phones, WhatsApp, social, analytics IDs) live in the `settings` table.

## 19. Testing & quality gates → [15](15-TESTING-STRATEGY.md); CI in [16](16-DEPLOYMENT-AND-INFRASTRUCTURE.md)
