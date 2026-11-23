# Browser QA scripts (manual, not run in CI)

Node scripts used for the website QA pass (docs/19, docs/26). They drive a **local** app with Playwright (preinstalled in the Claude Code environment; otherwise `npm i -g playwright`), Lighthouse and axe-core (`npm i lighthouse axe-core` in a scratch folder; set `QA_NODE_MODULES` to it).

Prepare a local database with `php artisan migrate:fresh --seed && php artisan db:seed --class=DemoSeeder`, create QA users (an admin with the `super-admin` role and a verified `buyer`) with passwords of your choice, and export `QA_ADMIN_PASSWORD` / `QA_BUYER_PASSWORD`. Emails used by the scripts: `qa-admin@example.test`, `qa-buyer@example.test`. Run the admin scripts against an instance started with `PCBD_ADMIN_MFA_REQUIRED=false` (local only). Clear the cache (`php artisan cache:clear`) between runs — the rate limiters will otherwise (correctly) throttle repeated submissions.

| Script | What it checks |
|---|---|
| `route-sweep.cjs` | 28 public routes × 375/768/1280/1440 px: overflow, failed responses, JS errors, screenshots |
| `lighthouse.cjs` / `lighthouse-details.cjs` | Lighthouse scores / failing audit details (run the app with `APP_ENV=production` so SEO audits are meaningful) |
| `axe.cjs` | axe-core WCAG 2.0/2.1 A+AA + best-practice rules on 23 routes at 375 and 1280 px |
| `keyboard.cjs` | Skip link, focus indicators, overlays/menus/lightbox with keyboard, labels, reduced motion |
| `csp.cjs` | Content-Security-Policy violations in the browser console |
| `admin-smoke.cjs` | Every admin list/create page loads at 1280 and 375 px |
| `full-flow.cjs` | End-to-end: developer → project → property → upload → publish → public page, search, filter, favorite, compare, inquiry, site visit → owner submission → admin approval |
