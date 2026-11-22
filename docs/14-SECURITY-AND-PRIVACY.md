# 14 — Security & Privacy

Applies to every change ([CLAUDE.md](../CLAUDE.md) §6). Not legal advice; legal review is an open item ([21](21-RISKS-AND-ASSUMPTIONS.md) R-03).

## 1. Threat model (summary)
Assets: lead/buyer PII, owner data, private documents, commission/financial records, admin accounts, brand trust (fake listings). Threats: credential stuffing, privilege escalation/IDOR, XSS via rich text/uploads, spam/bot inquiries, scraping, SQLi, malicious uploads, data leaks via logs/exports, insider misuse, fake/fraudulent listings, supply-chain (dependencies).

## 2. Controls
| Area | Control |
|---|---|
| Authentication | Laravel auth; bcrypt/argon2id hashing; password ≥ 10 chars + breached-password check (`Password::uncompromised()` in prod); login throttling; email verification; 2FA mandatory for Super Admin/Admin (optional others); session regeneration on login; logout invalidates; password reset tokens expire 60 min |
| Session | `secure`, `http_only`, `same_site=lax`; idle timeout 120 min (admin 60); DB sessions; concurrent session list P2 |
| Authorization | Spatie permissions + Policies; deny by default; resource scoping through queries (`visibleTo`); tests for each role ([07](07-USER-ROLES-AND-PERMISSIONS.md)); no authorization via hidden UI; IDOR prevention via scoped route binding & unguessable UUIDs for lead/deal exports |
| Validation | Form Requests/Livewire rules for every input; length/enum/format; normalise phone/email; reject unknown enum values; mass-assignment protection via explicit `$fillable` |
| CSRF | Laravel CSRF on all state-changing web routes; Livewire handled; SameSite cookies |
| XSS | Blade escaping by default; rich text sanitised server-side with allow-list (HTMLPurifier/`mews/purifier` — verify at install); `{!! !!}` banned except sanitised content (lint/grep check); CSP (report-only first, then enforce): `default-src 'self'`, allow analytics hosts only after consent; no inline scripts without nonce |
| SQL injection | Eloquent/query builder bindings; raw SQL only with bindings; code review rule |
| Rate limiting | Named limiters: login 5/min, register 5/h/IP, inquiry 5/h/IP + 3/h/phone, password reset 5/h, site search 60/min, tracking beacon 60/min; 429 UX page |
| Spam | Honeypot + minimum-fill-time + rate limit; Cloudflare Turnstile/hCaptcha optional if abuse appears (no Google reCAPTCHA by default for privacy) |
| File uploads | Allow-list MIME (jpeg, png, webp; pdf for documents) validated by content sniffing + extension; size caps (images 8 MB, docs 15 MB); images re-encoded (strips EXIF/GPS and embedded payloads); random filenames; stored off-webroot for private; AV scan hook (ClamAV) P2; never execute uploads; limit count/user/day |
| Private documents | `private` disk, no public URL; download via authorised controller or short-lived signed URL; access audited |
| Secrets | `.env` only, never committed; `.env.example` placeholders; CI secrets in GitHub Secrets; rotate on exposure; `APP_DEBUG=false` in prod; `APP_KEY` backed up securely |
| Transport | HTTPS only, HSTS, secure cookies; TLS via Let's Encrypt |
| Headers | `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN` (or CSP frame-ancestors), `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` minimal, CSP |
| Admin hardening | `/admin` auth + role + 2FA; optional IP allow-list/obscured path (backlog); failed-login alerts; audit of logins & privileged actions |
| Dependencies | `composer audit` and `npm audit` in CI; Dependabot; pin via lockfiles; avoid abandoned packages; review new packages before adding |
| Logging | Daily logs, no PII/secrets/tokens; audit log separate; log retention 30 days (app) / 24 months (audit) |
| Backups | Daily DB + weekly storage backups, encrypted, off-server, 30-day retention, **restore tested** before launch and quarterly ([16](16-DEPLOYMENT-AND-INFRASTRUCTURE.md)) |
| Error handling | Generic errors to users; details only in logs; no stack traces in prod |

## 3. Privacy by design
- **Principles:** collect minimum; purpose limitation; explicit consent for contact & marketing; transparency (Privacy Policy plain-language); access control; retention limits; deletion/anonymisation on request.
- **Never exposed publicly:** buyer/lead contact info, owner private contact (unless `show_owner_contact` with consent), private documents, internal notes, commission data.
- **Consent records:** `consent_given_at`, `consent_text_version`, `consent_channel` on leads/newsletter; separate checkboxes for (a) being contacted about the inquiry (required), (b) marketing (optional).
- **Cookies:** essential (session, CSRF, consent choice) always; analytics/marketing (GA4, Meta Pixel) only after opt-in; banner with granular choices; Cookie Policy lists cookies.
- **Data subject requests:** process in [06](06-USER-JOURNEYS.md) J12: verify identity → export/correct/anonymise within 30 days → audit. Provide contact email in Privacy Policy.
- **Retention:** per [10](10-DATABASE-ARCHITECTURE.md) §6; scheduled `pcbd:retention`; adjustable via settings after legal advice.
- **Data location/processors:** hosting provider, email provider, analytics — listed in Privacy Policy; DPA/terms reviewed.
- **IP addresses:** stored hashed (HMAC with app-key-derived salt) for abuse analysis; raw IP only in short-lived server logs.
- **Children:** service not directed at under-18.

## 4. Listing integrity & fraud
Verification is evidence-based and auditable; "Verified" badge only from a `verifications` row; owner submissions need admin approval; report-listing flow; sold/rented updates; price-change audit; duplicate-listing detection (P2); prohibit misleading prices (negotiable flag + "last updated"); watermark and image provenance backlog.

## 5. Secure development rules
Review checklist per PR: authz, validation, escaping, mass assignment, uploads, queries scoped, logs free of PII, secrets absent, tests cover 403/422 paths. Security tests listed in [15](15-TESTING-STRATEGY.md) §4. Responsible-disclosure contact in `/.well-known/security.txt` (Phase 9).

## 6. Incident response (lightweight)
1. Detect (alerts/log review/report). 2. Contain (disable account/feature, rotate secrets). 3. Assess scope using audit/logs. 4. Notify affected users and authorities as legally required. 5. Remediate and add regression test. 6. Record in changelog/ADR as appropriate.

## 7. Pre-launch security checklist (Phase 9)
Auth/authz test matrix green · `composer audit` clean · prod config review · CSP enforced · rate limits verified · upload abuse tests · backup restore tested · admin 2FA enforced · default accounts removed · logging PII scan · legal pages reviewed.
