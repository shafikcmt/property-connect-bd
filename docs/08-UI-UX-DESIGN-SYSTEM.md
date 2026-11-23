# 08 — UI/UX Design System

Feel: premium, modern, minimal, spacious, trustworthy, mobile-first, fast, accessible. Avoid generic template look: strong typographic hierarchy, generous whitespace, real photography, restrained gold accent, no stock "fake" widgets. Tokens are implemented as Tailwind theme extensions (`tailwind.config`/CSS `@theme`) in Phase 1.

## 1. Colour system
| Token | Hex | Use |
|---|---|---|
| `navy-900` | `#0B1F3A` | Primary brand, headings, header/footer bg |
| `navy-700` | `#16345C` | Hover/active on navy |
| `navy-50` | `#EEF2F8` | Tinted sections |
| `brand-600` (emerald) | `#0A7459` | Primary CTA, success, links on light (5.6:1 on white — `#0E8F6E` failed AA at 4.05:1, changed after Lighthouse audit) |
| `brand-700` | `#075A46` | CTA hover |
| `teal-500` | `#14B8A6` | Secondary accents, charts |
| `gold-500` | `#C9A24B` | Subtle accent: Featured badge, dividers, star/premium (never body text) |
| `gray-50` `#F7F6F4` · `gray-100` `#EDEBE7` · `gray-300` `#CFCBC4` · `gray-500` `#6F6A62` · `gray-700` `#3D3A35` · `gray-900` `#1C1B19` | Warm grays for surfaces, borders, text |
| `white` | `#FFFFFF` | Surfaces |
| Semantic | success `#0E8F6E`, warning `#B7791F`, danger `#C0392B`, info `#1D6FB8` | Status |
Text on white: `gray-900` body, `gray-700` secondary (≥ 7:1), `gray-500` only for ≥ 4.5:1 large/meta text. Dark mode: not in MVP (backlog).

## 2. Typography
- Headings: **"Playfair Display"/serif display** *or* "Fraunces" for H1/H2 hero only (optional, decide at implementation; fallback Georgia); UI/body: **Inter** (variable) with system fallbacks. Fonts self-hosted (privacy + speed), `font-display: swap`. Bangla fallback: "Noto Sans Bengali".
- Scale (mobile → desktop): H1 32→56, H2 26→40, H3 20→28, H4 18→20, body 16→17, small 14, caption 12. Line-height 1.2 headings / 1.6 body. Max line length ~70ch.
- Numbers (prices): tabular figures; currency `৳ 1.25 Cr` / `৳ 85 Lac` helper `Money::short()`, full amount on hover/detail.

## 3. Spacing, layout, grid
- 4px base unit; scale 4/8/12/16/24/32/48/64/96. Section vertical padding 64 (mobile) / 96 (desktop).
- Containers: `max-w-7xl` (1280) default; reading content `max-w-3xl`; forms `max-w-xl`. Horizontal gutter 16 (mobile) / 24 / 32.
- Grid: 4 cols mobile, 8 tablet, 12 desktop. Property card grid: 1 / 2 / 3 / (4 on ≥1536 for dense lists).
- Breakpoints (Tailwind): `sm 640`, `md 768`, `lg 1024`, `xl 1280`, `2xl 1536`. Design from 360px up.
- Radius: 8 (inputs/buttons), 12 (cards), 16 (hero panels), full (badges/pills). Shadow: subtle `0 1px 2px` / hover `0 8px 24px rgba(11,31,58,.10)`.

## 4. Components
- **Buttons:** Primary (emerald bg, white text), Secondary (navy outline), Ghost, Danger, WhatsApp (brand green `#25D366` only on WhatsApp actions, dark text for contrast). Min 44×44px target; visible focus ring (2px navy + 2px white offset); loading state with spinner and `aria-busy`.
- **Cards:** Property card (image 4:3, badges top-left: Featured/Verified/Status; price large; location; specs row with icons and text labels; favorite button; whole-card link with accessible name). Project card (status badge, developer logo, handover). Developer card. Article card.
- **Inputs:** 48px height, visible label (no placeholder-only), helper text, error text with icon + `aria-describedby`, required marker, phone input with +880 default. Select/combobox accessible (native select first).
- **Navigation:** sticky header, transparent-over-hero → solid on scroll; mega-menu avoided; mobile drawer with focus trap; skip-to-content link.
- **Filters:** desktop sidebar/horizontal bar; mobile bottom-sheet "Filters (n)" with Apply/Reset; active filter chips removable; counts only when cheap.
- **Badges:** Featured (gold outline), Verified (emerald check + "Verified on {date}" tooltip, only if verification record exists), Status (Ready/Ongoing/Upcoming), New (≤14 days), Price Negotiable.
- **Tables (admin & compare):** sticky header, zebra optional, responsive (horizontal scroll with shadow cue), sortable indicators with `aria-sort`.
- **Modal/dialog:** native `<dialog>` or Alpine + focus trap, ESC close, scroll lock, returns focus.
- **Gallery:** main image + thumbnails, swipe on mobile, lightbox, keyboard arrows, alt text for each image.
- **Alerts/toasts:** `role="status"`/`alert`, auto-dismiss only for non-errors.
- **Empty states:** illustration-free, icon + explanation + next action ("No properties match — clear filters").
- **Loading states:** skeletons matching card layout; `wire:loading` indicators; no layout shift (reserve image aspect ratio).
- **Error states:** friendly 403/404/419/429/500 pages with search, home link and support contact.

## 5. Imagery & content rules
Real photos only (or clearly marked "illustrative"/"artist impression" for renders). No fake testimonials/ratings/verified marks. Alt text required on upload (fallback: generated from title+type+location). Image pipeline: max original 4000px, generate 480/768/1280/1920 WebP(+AVIF if available), `srcset`, `loading="lazy"` below fold, `fetchpriority=high` on LCP image. Watermark: optional, backlog.

## 6. Accessibility (WCAG 2.1 AA)
Semantic landmarks; heading order; colour contrast verified; focus visible; keyboard-operable everything (gallery, filters, compare); form labels/errors associated; `prefers-reduced-motion` respected; target size ≥ 44px; language attribute (`en`/`bn`); no information by colour alone; link purpose clear.

## 7. Motion
Subtle: 150–250ms ease-out for hover/focus/drawer; no parallax; hero image static.

## 8. Admin UI
Filament default theme customised with brand colours (primary emerald, navy sidebar optional). Do not restyle deeply — keep upgrade path cheap.

## 9. Voice & copy
Plain, confident, factual. Prefer "Verified by Property Connect BD on 12 Mar 2026" over superlatives. Avoid urgency manipulation ("Only 1 left!") unless backed by verified availability data.

## 10. Implementation notes
Blade components under `resources/views/components/ui/*` (`<x-ui.button>`, `<x-ui.card>`, `<x-ui.badge>`, `<x-ui.input>`); property/project cards in `components/property/*`. Icons: Heroicons (inline SVG via blade-heroicons) — single icon set. A living style guide page at `/dev/styleguide` (local env only) arrives in Phase 1.
