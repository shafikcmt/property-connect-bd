# 05 — Sitemap & Navigation

## 1. Primary navigation (public header)
Home · Properties · Projects · Developers · Locations · Services · About · Blog · Contact
Header actions: **Search** (opens overlay), **Favorites** (heart + count), **Submit Property** (button), **Login / Register** (becomes account menu when signed in).
Mobile: logo + search + hamburger; drawer contains the same items; "Submit Property" and WhatsApp stay as persistent CTAs. Header stays uncluttered: secondary items (Compare, Valuation, FAQ) live in the footer or contextual UI.

## 2. Public URL map (canonical, lowercase, hyphenated)
| URL | Page | Indexable |
|---|---|---|
| `/` | Home | yes |
| `/properties` | Listing (all) | yes (unfiltered) |
| `/properties/{purpose}` e.g. `/properties/for-sale`, `/properties/for-rent` | Purpose landing | yes |
| `/properties?{filters}` | Filtered listing | **noindex,follow**, canonical → base (except curated combos below) |
| `/properties/{slug}` | Property detail (slug has id suffix: `3-bed-apartment-gulshan-2-1042`) | yes |
| `/projects`, `/projects/{status}` (upcoming/ongoing/ready) | Project listing | yes |
| `/projects/{slug}` | Project detail | yes |
| `/developers`, `/developers/{slug}` | Directory, profile | yes |
| `/locations`, `/locations/{path}` e.g. `/locations/dhaka/gulshan` | Location hub/page | yes if it has content |
| `/locations/{path}/{property-type}` | Curated location+type landing | yes only if ≥ N listings and unique copy |
| `/services`, `/about`, `/contact`, `/faq` | Static | yes |
| `/blog`, `/blog/category/{slug}`, `/blog/{slug}` | Blog | yes |
| `/privacy-policy`, `/terms-and-conditions`, `/cookie-policy`, `/disclaimer` | Legal | yes |
| `/submit-property`, `/sell`, `/rent-out`, `/valuation`, `/assistance` | Forms | `/submit-property` yes; others noindex optional |
| `/compare` | Compare | noindex |
| `/favorites` | Favorites | noindex |
| `/search?q=` | Site search | noindex |
| `/login`, `/register`, `/forgot-password`, `/reset-password/{token}`, `/verify-email` | Auth | noindex |
| `/account`, `/account/profile`, `/account/favorites`, `/account/saved-searches`, `/account/inquiries`, `/account/site-visits`, `/account/submissions`, `/account/notifications` | Buyer/owner area | noindex, auth |
| `/lp/{slug}` | Campaign landing pages (Phase 7) | per-page |
| `/sitemap.xml`, `/robots.txt` | SEO | — |
| `/admin/*` | Filament admin | noindex, auth, `Disallow` in robots |
| `/portal/*` | Developer/agent portal (Phase 8) | noindex, auth |

Rule: no thousands of low-quality indexable pages — see [12](12-SEO-AND-MARKETING-STRATEGY.md) §Crawl control.
Redirects: renamed slugs keep a `slug_history`-based 301 (Phase 3).

## 3. Footer
Columns: **Explore** (Properties, Projects, Developers, Locations), **Services** (Buying, Selling, Rental, Developer marketing), **Company** (About, Blog, Contact, FAQ), **Legal** (Privacy, Terms, Cookie, Disclaimer), contact block (phone, WhatsApp, email, office — all from settings), social links (from settings), newsletter.

## 4. Admin navigation (Filament groups)
| Group | Items |
|---|---|
| Dashboard | Overview |
| Listings | Properties, Projects, Units, Developers, Moderation Queue, Reports (listing reports) |
| Catalog | Locations, Property Types, Amenities |
| CRM | Leads, Follow-ups, Site Visits, Lead Sources |
| Sales | Deals, Bookings, Commission Agreements, Commissions |
| Marketing | Campaigns, Landing Pages, Tracking Events |
| Content | Pages, Blog Posts, Categories, FAQs, Banners, Testimonials, Navigation |
| Partners | Developers (contacts), Owners, Agents, Partnership Records |
| System | Users, Roles, Permissions, Settings, Notifications, Audit Logs, Reports |
Items appear only when the user has the permission and the phase has shipped.

## 5. Breadcrumbs
Home › Properties › {Location} › {Title}; Home › Projects › {Project}; Home › Developers › {Name}; Home › Locations › Dhaka › Gulshan. Emitted as HTML + `BreadcrumbList` JSON-LD.

## 6. Search behaviour
Header search overlay: keyword + quick suggestions (locations, projects, developers) → `/search`; hero search → `/properties` with filters.
