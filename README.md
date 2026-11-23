# Property Connect BD

> Connecting Properties, People & Opportunities.

Property Connect BD is an independent **real estate marketing, marketplace and sales platform** for Bangladesh. It is not just a listing site: it supports property and project marketing, developer partnerships, buyer discovery, inquiry and lead management, site visits, sales coordination and commission tracking — and is designed to grow into a multi-company SaaS later.

## Business concept
We connect buyers, sellers/owners, developers, agents and investors. Revenue comes from (A) negotiated sales commissions under written agreements, (B) marketing services for developers, (C) featured listings, (D) developer digital solutions, and — much later — (E) SaaS subscriptions. Details: [docs/01-BUSINESS-STRATEGY.md](docs/01-BUSINESS-STRATEGY.md).

## Technology stack
PHP 8.3 · Laravel 13 · Blade · Livewire 4 · Tailwind CSS 4 · Alpine.js · Filament 5 (admin) · PostgreSQL 16 · Spatie Laravel Permission · Laravel Queue/Scheduler/Notifications · Pest · GitHub. One unified Laravel application — no microservices, no separate SPA. Versions: ADR-0002. See [docs/09-TECHNICAL-ARCHITECTURE.md](docs/09-TECHNICAL-ARCHITECTURE.md).

## Documentation (single source of truth)
Start with [`CLAUDE.md`](CLAUDE.md), then:

| Doc | Topic |
|---|---|
| [00 Overview](docs/00-PROJECT-OVERVIEW.md) | Vision, parties, glossary, doc map |
| [01 Business strategy](docs/01-BUSINESS-STRATEGY.md) | Revenue models, go-to-market |
| [02 Requirements](docs/02-PRODUCT-REQUIREMENTS.md) | Functional & non-functional |
| [03 MVP scope](docs/03-MVP-SCOPE.md) | P0–P3 priorities |
| [04 Roadmap](docs/04-ROADMAP.md) | Phases 0–9 |
| [05 Sitemap](docs/05-SITEMAP-AND-NAVIGATION.md) · [06 Journeys](docs/06-USER-JOURNEYS.md) | Structure & flows |
| [07 Roles](docs/07-USER-ROLES-AND-PERMISSIONS.md) | RBAC |
| [08 Design system](docs/08-UI-UX-DESIGN-SYSTEM.md) | UI/UX tokens & components |
| [09 Architecture](docs/09-TECHNICAL-ARCHITECTURE.md) · [10 Database](docs/10-DATABASE-ARCHITECTURE.md) · [11 Modules](docs/11-MODULE-SPECIFICATIONS.md) | Engineering |
| [12 SEO & marketing](docs/12-SEO-AND-MARKETING-STRATEGY.md) · [13 Lead & sales workflow](docs/13-LEAD-AND-SALES-WORKFLOW.md) | Growth & operations |
| [14 Security & privacy](docs/14-SECURITY-AND-PRIVACY.md) · [15 Testing](docs/15-TESTING-STRATEGY.md) · [16 Deployment](docs/16-DEPLOYMENT-AND-INFRASTRUCTURE.md) | Quality & ops |
| [17 SaaS future](docs/17-SAAS-FUTURE-ARCHITECTURE.md) · [18 ADRs](docs/18-DECISIONS-AND-ADRS.md) | Direction & decisions |
| [19 Progress](docs/19-PROGRESS-TRACKER.md) · [20 Backlog](docs/20-BACKLOG-AND-FUTURE-FEATURES.md) · [21 Risks](docs/21-RISKS-AND-ASSUMPTIONS.md) | Management |
| [22 Workflow](docs/22-DEVELOPMENT-WORKFLOW.md) · [23 Acceptance](docs/23-ACCEPTANCE-CRITERIA.md) · [24 Changelog](docs/24-CHANGELOG.md) | Process |

## Current status
See [docs/19-PROGRESS-TRACKER.md](docs/19-PROGRESS-TRACKER.md) for the live status. At the time of writing: **Phases 0–2 complete; the public website (Phases 3–7) is built and in review — launch needs owner inputs (hosting, domain, real content, legal review). CRM and later phases follow after launch.** See [docs/25-DEPLOYMENT-GUIDE.md](docs/25-DEPLOYMENT-GUIDE.md).

## Setup instructions
Prerequisites: PHP 8.3+ (with `pdo_pgsql`, `intl`, `gd`, `mbstring`, `zip`), Composer 2, Node.js 22, PostgreSQL 16.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create a PostgreSQL role/database (example for local development — choose your own password) and put the credentials in `.env`:

```bash
sudo -u postgres psql -c "CREATE USER pcbd WITH PASSWORD 'change-me' CREATEDB;" \
  -c "CREATE DATABASE pcbd OWNER pcbd;" -c "CREATE DATABASE pcbd_testing OWNER pcbd;"
```

```bash
php artisan migrate --seed          # schema + roles/permissions (no demo data, no default users)
php artisan pcbd:create-super-admin # prompts for a password; no default credentials exist
php artisan db:seed --class=DemoSeeder   # optional, local only: clearly-labelled demo listings and artwork
npm install && npm run build
php artisan storage:link
php artisan serve                   # public site: /   ·   admin panel: /admin
```

Super Admin and Admin accounts must enrol in app-based two-factor authentication (profile page) before using the admin panel.

Quality checks (run before every commit):

```bash
vendor/bin/pint --test
php artisan test
```

## Development roadmap (summary)
0 Planning ✅ → 1 Foundation 🔎 → 2 Property & Project Management → 3 Public Website & Discovery → 4 Buyer & Seller Experience → 5 CRM & Lead Management → 6 Sales & Commission → 7 Marketing & Analytics → 8 Partner & Agent Features → 9 Production Readiness. Full detail: [docs/04-ROADMAP.md](docs/04-ROADMAP.md).

## Contributing / workflow
Documentation-first, phase-based, small commits, tests before completion, no secrets in git. See [docs/22-DEVELOPMENT-WORKFLOW.md](docs/22-DEVELOPMENT-WORKFLOW.md).
