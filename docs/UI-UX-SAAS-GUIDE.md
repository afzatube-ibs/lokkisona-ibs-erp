# IBS-LK Business Manager — UI/UX SaaS Guide (v1.6 revamp)

This is the design system reference for the modern-SaaS UI. Phase 1 (shell + tokens)
is implemented; Phases 2–3 (page-level passes) build on these tokens and classes.

## Theme system (unchanged contract)
- `data-theme="dark"` on `<html>`; storage key `ibs-theme` (values: `dark` / unset).
- Anti-flash inline script in `resources/views/layouts/admin.php` now also follows
  `prefers-color-scheme: dark` when nothing is saved.
- `public/assets/js/app.js` toggles + persists, and falls back to OS preference.
- Smooth theme transitions are applied but disabled under `prefers-reduced-motion`.

## Token layers (public/assets/css/app.css `:root` + `[data-theme="dark"]`)
Prefer semantic + status tokens in new component CSS. Do NOT hardcode hex.

Surfaces:    --surface-0 (app bg), --surface-1 (cards), --surface-2 (raised/hover), --overlay
Borders:     --border-default, --border-subtle, --border-strong, --ring-focus
Text:        --text-primary, --text-secondary, --text-tertiary, --text-on-brand
Brand:       --brand-50/100/300/500/600/700, --color-primary (alias of brand-500)
Status:      --success/-soft, --warn/-soft, --error/-soft, --info/-soft
Radii:       --radius-sm 8, --radius 10, --radius-lg 14, --radius-xl 18, --radius-full
Shadow:      --shadow-sm, --shadow, --shadow-md, --shadow-lg
Motion:      --transition-fast, --transition

Legacy `--color-*` names still exist but are ALIASES of the tokens above, so older
component CSS keeps working. New code should use the semantic names.

## Core component classes
- Page header: `.page-header` > `.page-title` + `.page-description` (+ right-aligned CTA)
- Cards: `.card` > `.card-header` > `.card-title`; `.card-body`
- Buttons: `.btn` + `.btn-primary` / `.btn-success` / `.btn-danger` / `.btn-outline`
  / `.btn-ghost`; sizes `.btn-sm` / `.btn-lg`; `.btn-block`
  - primary has a subtle hover lift + shadow; focus uses `--ring-focus`
- Forms: `.form-group` + `.form-label`; `.form-input` (inputs/select/textarea);
  `.form-grid` / `.form-grid-wide`; focus ring via `--ring-focus`
- Tables: `.data-table` (sticky header, row hover); wrap in `.table-scroll` on mobile
- Badges/pills: `.badge` + `.badge-ok|success / -warn / -fail|error / -info / -neutral`
  - now pill-shaped, token-driven soft backgrounds, single definition for both themes
- KPI: `.stat-card` (+ `.stat-icon`, `.stat-content`, `.stat-label`, `.stat-value`)
- Empty state: `.empty-state`
- Collapsibles: `<details class="planning-collapsible">` >
  `.planning-collapsible-summary` + `.planning-collapsible-body`
- Safety/dev strips: `.ops-safety-strip`, `.dev-mode-banner` — restyled to look
  intentional (token warn colors), keep them; do not remove.

## Layout
- Sidebar `.sidebar` (modernized navy); `.nav-item` active = filled pill + accent
  bar via `::before` (replaced the old left-border).
- Topbar `.topbar` is sticky with a frosted-glass blur.
- Shell: fixed sidebar + `.main-wrapper` + `.main-content`.

## Do / Don't
- DO use semantic tokens; DO keep 8/12px spacing rhythm; DO wrap wide tables in
  `.table-scroll`; DO keep safety strips and write-gate warnings.
- DON'T hardcode hex in components; DON'T add per-theme overrides when a token already
  flips with the theme; DON'T remove features/permissions UI; DON'T change controllers,
  routes, schema, or business logic for styling.

## Phase status
- Phase 1 (done): tokens refactor, buttons, forms focus, cards, badges, sidebar active,
  topbar glass, dev banner, theme JS + OS preference.
- Phase 2 (next): dashboard, product-control, order-workflow + cards, sync-preview,
  manual-orders.
- Phase 3 (next): finance & admin pages, auth/login.

## Screenshot checklist (verify in browser, light + dark)
/dashboard, /product-control, /order-workflow, /sync-preview, /manual-orders,
/supplier-payables, /settlements, /dispatch-reports, /return-receive, /suppliers,
/business-sources, /users, /roles-permissions, /status-mapping, /auth/login
On each: no horizontal overflow at 375px; cards/tables/buttons/badges consistent;
theme persists across reload; no flash of wrong theme.

---

## Phase 2 — high-traffic ops (done)

Finding: the app had a substantial prior revamp, so most Phase 2 pages already used
strong patterns (dashboard sparklines/gauges, workflow stage cards, product workspace
modal, manual-order builder). Phase 2 therefore focused on real gaps + polish:

- **`.btn-secondary` was USED but never DEFINED** — buttons on sync-preview and the
  manual-order "Add Product Row" rendered unstyled. Now defined as a proper neutral
  secondary (token-driven, hover state). Audited all btn-* classes: used vs defined now
  fully matched.
- **Sync Preview action hierarchy** — the three actions are now a labeled action bar
  (Step 1 Pull · Step 2 Test Sync · Step 3 Owner import) with clear primary/secondary/
  success emphasis instead of three inline forms. New classes: `.sync-action-bar`,
  `.sync-action`, `.sync-action-step`, `.sync-import-confirm`.
- **Sync Preview hardcoded form actions** → `url()` helper (matches settlements fix;
  avoids base-path breakage on staging).
- **Product Control read-only vs editable** — platform/OpenCart inputs now render with a
  dashed border + not-allowed cursor + muted surface, so they read clearly as locked vs
  the editable supplier fields. Section label gains an icon slot.
- Cards/badges/buttons/forms already inherit the Phase 1 token refresh.

New component classes added this phase: `.btn-secondary`, `.sync-action-bar` family.

## Phase 3 — finance & admin (next)
supplier-payables, settlements, dispatch-reports, return-receive, suppliers,
business-sources, users, roles-permissions, status-mapping, and auth/login.

---

## Phase 3 — finance & admin + login (done)

- **auth/login.php** — added the anti-flash theme script (dark-mode users no longer get
  a white flash before CSS loads) and fixed the login title which used a dark-navy token
  that was low-contrast on the dark card; now `--text-primary`. Card elevated to
  `--radius-xl` + `--shadow-lg` with a subtle border for dark mode. version-pill and
  title now theme correctly.
- **status-mapping** — 3 hardcoded form actions → `url()` helper (last instance of that
  bug class in the app; settlements + sync-preview already fixed).
- **`.form-input-sm`** was used on supplier-payables but never defined — now defined as a
  compact inline select size (the supplier filter dropdown was rendering full-width/odd).

### Two token bugs introduced by the Phase 1 refactor — found and fixed
The Phase 1 token rewrite dropped two names that existing CSS/markup referenced:
  - **`--radius-md`** (5 references) — would have fallen back to 0 = sharp corners on
    safety strips and a few panels. Re-added (= 10px).
  - **`--surface-muted`** (verification panel) — had a light-only hex fallback that looked
    wrong in dark mode. Added as a proper themed alias.
Final audit: every `var(--token)` referenced anywhere in CSS or views is now defined,
and every `class="..."` button class is defined. No orphaned tokens, no undefined
buttons.

## Revamp complete (Phases 1–3)
Token system modernized, all shared components (cards, buttons, forms, tables, badges,
sidebar, topbar) refreshed, page-specific gaps fixed (sync action hierarchy, product
read-only distinction, login theming), and full token/class integrity verified. Both
themes are first-class. Run check-local.ps1 and the screenshot checklist above to verify
live in the browser.
