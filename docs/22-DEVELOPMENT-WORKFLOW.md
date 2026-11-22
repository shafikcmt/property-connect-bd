# 22 — Development Workflow

## 1. Session start routine
Read `CLAUDE.md` → `docs/00` → `docs/04` → `docs/19` → relevant specs → inspect code and `git status`/`git log` → confirm dependencies → pick the next tracker task.

## 2. Git
- `main` — stable, deployable. Feature branches for substantial work; no branches for trivial edits.
- Branch names: `feat/project-foundation`, `feat/authentication`, `feat/property-management`, `feat/project-management`, `feat/public-property-listings`, `feat/property-search`, `feat/buyer-account`, `feat/lead-management`, `feat/site-visits`, `feat/sales-commission`, `feat/marketing-analytics`, `feat/partner-portal`, `chore/…`, `fix/…`, `docs/…`.
- **Environment override:** when a session is assigned a development branch by its environment (e.g. `claude/<name>`), commit there instead and push only to that branch; phase work is then separated by commits/tags rather than branches. Never push elsewhere without permission.
- Commits: small, logical, imperative, conventional prefix (`feat`, `fix`, `docs`, `test`, `refactor`, `chore`, `perf`, `security`) with scope: `feat(leads): add status transition service`. Body explains why when non-obvious. No secrets, no `.env`, no vendor/node_modules/build artifacts.
- PRs only when requested; description lists changes, tests run, docs updated, screenshots for UI, risks.
- Never rewrite shared history; never overwrite another person's work.

## 3. Task loop
1. Pick task (tracker) → mark `In Progress`.
2. Write/adjust tests alongside code.
3. Implement minimal change following existing patterns.
4. `vendor/bin/pint` → `php artisan test` → (UI) manual check at 375 / 768 / 1280 px, keyboard pass.
5. Review diff (`git diff`), check for secrets/debug code, N+1, missing authorization.
6. Update docs, tracker (status + %), changelog (significant), backlog (deferred ideas), ADR (decisions).
7. Commit. Mark `Completed` only after verification.

## 4. Phase gate
Before the next major phase: all phase acceptance criteria met ([23](23-ACCEPTANCE-CRITERIA.md)); tests green; no critical bugs; migrations reviewed; docs/tracker current; phase summary written (completed work, test results, remaining issues, next phase).

## 5. Coding standards
PSR-12 via Pint; `declare(strict_types=1)` in new files; typed properties/returns; enums for statuses; thin controllers; Form Requests; Policies; no business logic in Blade; no queries in Blade; avoid static helpers with hidden state; naming: models singular, tables plural, controllers `XController`, actions verb-first (`CreateLeadFromInquiry`), services noun (`CommissionCalculator`). Comments for *why*. No commented-out code. No `dd()/dump()/ray()` committed. Blade components for reuse; Tailwind tokens from [08](08-UI-UX-DESIGN-SYSTEM.md); avoid inline styles.

## 6. Dependency policy
Add a package only if: it solves a real, current need; is maintained and compatible with the pinned Laravel/PHP; has acceptable licence; cheaper than writing/maintaining the code. Record notable additions in changelog (and ADR if architectural). Run `composer audit`.

## 7. Documentation policy
Docs describe the *intended and current* system. When code changes behaviour/architecture, update the doc in the same commit. Unknowns go to [21](21-RISKS-AND-ASSUMPTIONS.md). Keep cross-references valid.

## 8. Definition of Done
See CLAUDE.md §5 and [23](23-ACCEPTANCE-CRITERIA.md).

## 9. Handling blockers
Document in tracker (Blocked + reason), continue with unblocked tasks; ask the owner only for decisions/credentials/paid services/legal items.
