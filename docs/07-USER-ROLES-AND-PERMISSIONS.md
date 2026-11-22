# 07 — User Roles & Permissions

Implemented with **Spatie Laravel Permission** (roles + permissions) plus **Laravel Policies** for resource-level rules (ownership, assignment). Filament resources call policies; all routes/Livewire actions authorize server-side. Roles are introduced only when their phase needs them.

## 1. Roles
| Role | Slug | Phase | Purpose | Panel access |
|---|---|---|---|---|
| Super Admin | `super-admin` | 1 | Owner; all permissions (via `Gate::before`) incl. settings, roles, destructive ops | admin |
| Admin | `admin` | 1 | Operations lead; everything except role/permission editing & system config | admin |
| Property Manager | `property-manager` | 1 (seeded) / 2 (used) | Listings, projects, developers, verification, moderation | admin |
| Sales Manager | `sales-manager` | 1 / 5 | All leads, assignment, visits, deals, commission view/approve | admin |
| Sales Agent | `sales-agent` | 1 / 5 | Only assigned leads/visits/follow-ups; creates deals for own leads | admin (limited) |
| Content Manager | `content-manager` | 1 / 3 | Pages, blog, FAQs, banners, SEO, testimonials | admin (limited) |
| Marketing Manager | `marketing-manager` | 7 | Campaigns, landing pages, analytics; lead view without contact details by default | admin |
| Developer | `developer` | 8 | Portal: own company's projects/leads (limited fields) | portal |
| Property Owner | `property-owner` | 4 | Own submissions | account |
| Buyer | `buyer` | 1 (default for registrants) | Own favorites, searches, inquiries | account |

Users may hold multiple roles. Registration assigns `buyer` only; privileged roles are granted by Super Admin/Admin. A user with an admin-panel role must have verified email; 2FA is required for Super Admin/Admin (Filament 2FA or Fortify — decided in Phase 1, ADR-0008).

## 2. Permission naming
`{resource}.{action}` e.g. `properties.view`, `properties.create`, `properties.update`, `properties.delete`, `properties.publish`, `properties.verify`, `leads.view-all`, `leads.view-assigned`, `leads.assign`, `leads.view-contact`, `leads.export`, `deals.approve`, `commissions.view`, `commissions.pay`, `settings.manage`, `users.manage`, `roles.manage`, `audit.view`.

## 3. Matrix (✔ full · ◐ restricted (see notes) · — none)
| Capability | SA | Admin | PropMgr | SalesMgr | SalesAgent | ContentMgr | MktgMgr | Developer | Owner | Buyer |
|---|---|---|---|---|---|---|---|---|---|---|
| Admin panel | ✔ | ✔ | ✔ | ✔ | ◐ | ◐ | ◐ | portal | — | — |
| Manage users/roles | ✔ | ◐(users, no roles) | — | — | — | — | — | — | — | — |
| Settings | ✔ | ◐ | — | — | — | — | — | — | — | — |
| Properties/Projects CRUD | ✔ | ✔ | ✔ | view | view | — | view | ◐ own | ◐ own submissions | — |
| Publish / verify / feature | ✔ | ✔ | ✔ | — | — | — | — | — | — | — |
| Moderate submissions | ✔ | ✔ | ✔ | — | — | — | — | — | — | — |
| Developers/partners | ✔ | ✔ | ✔ | view | view | — | view | own | — | — |
| Leads (all) | ✔ | ✔ | ◐ property-related | ✔ | — | — | ◐ no contact | — | — | — |
| Leads (assigned) | ✔ | ✔ | — | ✔ | ✔ | — | — | ◐ shared | — | — |
| Lead contact details | ✔ | ✔ | ◐ | ✔ | ◐ assigned | — | — (masked) | ◐ shared | — | own |
| Lead export | ✔ | ✔ | — | ✔ | — | — | — | — | — | — |
| Site visits | ✔ | ✔ | view | ✔ | ◐ assigned | — | — | ◐ own project | — | own |
| Deals | ✔ | ✔ | — | ✔ | ◐ own leads | — | — | ◐ own | — | — |
| Commission view | ✔ | ✔ | — | ✔ | ◐ own deals | — | — | — | — | — |
| Commission approve/pay | ✔ | ✔ | — | ◐ approve | — | — | — | — | — | — |
| Campaigns/analytics | ✔ | ✔ | — | view | — | — | ✔ | ◐ own | — | — |
| CMS (pages/blog/FAQ/banners) | ✔ | ✔ | — | — | — | ✔ | ◐ landing pages | — | — | — |
| Audit log | ✔ | ✔ view | — | — | — | — | — | — | — | — |
| Favorites/saved/compare/inquiries | — | — | — | — | — | — | — | — | own | own |

## 4. Resource-level rules (policies)
- **Lead:** viewable if user has `leads.view-all`, or `leads.view-assigned` and `lead.assigned_to = user.id`. Contact fields hidden unless `leads.view-contact` + access to the lead. Reassigning requires `leads.assign`.
- **SiteVisit:** same assignment scoping via lead/assigned agent.
- **Deal/Commission:** Sales Agent sees deals where `agent_id = user.id`; commission amounts visible only with `commissions.view`.
- **Property/Project:** Developer role limited to `developer_id` of their linked company (Phase 8). Owner limited to `submitted_by = user.id`.
- **Buyer data:** favorites, saved searches, inquiries filtered by `user_id`; route-model binding scoped through relationships (never `find($id)` unscoped).
- **Private media/documents:** served through authorised signed/streamed routes only.
- **Marketing Manager** default: lead rows visible with name initials/phone masked; aggregated stats unaffected.

## 5. Rules of implementation
- Deny by default; policies for every model exposed in Filament/Livewire/controllers.
- Tests assert 403 for each role/resource combination in the matrix ([15](15-TESTING-STRATEGY.md)).
- Role/permission seeding is idempotent (`RolesAndPermissionsSeeder`); permission changes ship as migrations/seeder updates and are noted in the changelog.
- Impersonation: not in MVP (backlog).
