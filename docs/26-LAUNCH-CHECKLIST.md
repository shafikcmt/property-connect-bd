# 26 — Launch Checklist

Status as of 2026-11-23. The website itself is QA-complete (see [19](19-PROGRESS-TRACKER.md)); what remains is **content, decisions and infrastructure that only the owner can provide**. Nothing has been deployed and nothing will be without explicit authorisation. Procedures: [25](25-DEPLOYMENT-GUIDE.md).

## A. What I need from you (blocking launch)
| # | Item | Why | Where it goes |
|---|---|---|---|
| 1 | **Hosting decision** — a VPS (Ubuntu LTS, SSH) with PostgreSQL 16 and PHP 8.3, e.g. 2 vCPU / 4 GB | The schema relies on PostgreSQL (ADR-0004); shared hosting without PostgreSQL will not work | Server for docs/25 |
| 2 | **Domain name** and DNS access | HTTPS, canonical URLs, sitemap | `APP_URL`, nginx, certbot |
| 3 | **Business contact details**: phone, WhatsApp number (+880…), email, office address, social links | Header/footer, contact page, Call/WhatsApp buttons, schema | Admin → Settings |
| 4 | **Logo** (SVG/PNG, light + dark background) and **favicon**; brand colour confirmation (currently navy/emerald/gold) | The site uses a text wordmark and generic icon | Header/footer, favicon, `resources/css/app.css` |
| 5 | **Real content**: partner developers (name, logo, about, contact permission), projects (photos, status, handover, unit types, verified prices), properties (photos with alt text, verified details) | The site currently runs only on **demo** data that must not ship | Admin → Developers / Projects / Properties |
| 6 | **Real photography** (or written permission to use developer images) — min. 1600 px wide, no watermarks of other companies | Image rights; demo artwork is illustrative only | Admin → Media |
| 7 | **Legal review** of Privacy Policy, Terms, Cookie Policy, Disclaimer by a qualified advisor (Bangladesh) — and the consent wording on forms | The pages are plain-language drafts, **not legal advice** (R-03) | `resources/views/pages/legal/*` |
| 8 | **Email provider** (SMTP: Brevo/Mailgun/SES/etc.) and a sending address on your domain (SPF/DKIM/DMARC) | Password-reset and email-verification mails; locally mail is only logged | `.env` `MAIL_*` |
| 9 | **A named person** who will respond to inquiries, and the response-time promise you want to make | The inbox is manual until the CRM phase; WhatsApp/phone follow-up | Process |
| 10 | Whether you want **GA4 / Meta Pixel** at launch (IDs) | Tags must load only after a consent banner, which is not built yet (Phase 10). Either launch without analytics or approve a small consent-banner task first | Admin → Settings |

## B. Decisions to confirm
- Launch language: English only (Bangla fonts fall back to system fonts; full Bangla content/UI is backlog).
- Featured placements are labelled "Featured"; "Verified" appears only after a recorded verification — confirm this is how you will operate.
- Submitted-by-owner listings: login required + admin approval (current behaviour). Fee/terms for owners to be stated on the FAQ (the FAQ currently says "contact us").
- Backups off-server destination (object storage account or another host).

## C. Steps I will perform once A1–A3 are provided (and you say "go")
1. Provision/harden the server (firewall, nginx, PHP-FPM, PostgreSQL, certbot) — [25 §1–4](25-DEPLOYMENT-GUIDE.md).
2. Configure `.env` (secrets typed by you or placed on the server by you; never committed), run `scripts/deploy.sh`, create the Super Admin interactively, **enrol 2FA**.
3. Load content with you / verify settings, run `php artisan pcbd:preflight` until it shows no FAIL.
4. Schedule cron (scheduler, nightly backup), supervisor queue worker; **restore-test a backup on a scratch database**.
5. Point DNS (your action), confirm TLS, run Lighthouse against the live site, submit `sitemap.xml` to Google Search Console / Bing.
6. Remove the demo banner/data (never seeded in production), final smoke test, go-live; rollback plan in docs/25 §11.

## D. Already done (verified — details in the tracker)
Website pages and flows, admin panel, search/filters, favorites/compare/inquiries/site visits/owner submissions, accessibility pass (axe: 0 violations), Lighthouse (mobile 94–100 perf, 100 a11y/best-practices/SEO), SEO output, CSP (report-only), security headers, preflight command, deploy + backup scripts, migrations reversible, `composer audit`/`npm audit` clean, 169 automated tests + 8 browser QA scripts.

## E. Known limitations at launch (accepted, documented)
- Inquiry handling is a basic inbox (status + notes). Assignment, follow-up reminders, pipeline, notifications to staff and email to buyers arrive in the CRM phase (after launch). Until then, **check the Inquiries inbox daily** (the sidebar shows a count badge) — or, once SMTP (A8) works, enter an address in Admin → Settings → "Email new inquiries to" (built, tested with fake mail; real delivery untested until SMTP exists).
- No analytics until the consent banner exists (item A10).
- Desktop Lighthouse numbers (70–88 on the dev server) rise to 86–98 once nginx gzip/caching is in place (measured with a gzip proxy); **re-measure on the real server**.
- CSP is report-only; enforcing it is a post-launch hardening step after watching real traffic.
- Map view is a link to OpenStreetMap (no embedded map) to avoid paid APIs.

## F. Before real launch
- If `PCBD_ALLOW_DEMO=true` was used for a preview: run `php artisan pcbd:demo-clear`, set `PCBD_ALLOW_DEMO=false`, redeploy, confirm `pcbd:preflight` shows no demo warnings.- Set `PCBD_NOINDEX=false` (it hides the site from search engines) and redeploy; submit `sitemap.xml` afterwards.

