# Dbscript 4 — Design (arch-spa)

**Branch:** `arch-spa` · **Audience:** SPA UI implementers and dj--alex review.

Single source of truth for UX/UI decisions. Every UI PR must follow this document.

---

## Principles

1. **Functional parity first** — preserve w.php workflow (toolbar, grid, form, pagination), not a retro 2008 skin.
2. **Minimal modern UI** — neutral palette, accent from `styles.toml`, 4px spacing grid.
3. **Feedback always** — loading, success toast, field-level errors; never raw API dumps in `alert()`.
4. **Permissions visible** — hide or disable actions the user cannot perform.

---

## Design tokens

Source: [`frontend/src/styles/tokens.css`](frontend/src/styles/tokens.css) — synced with [`public/css/app.css`](public/css/app.css) variable names via `ThemeService`.

| Token | Usage |
|-------|--------|
| `--color-bg` | Page background |
| `--color-text` | Body text |
| `--color-accent` | Header, primary buttons, links |
| `--color-border` | Tables, inputs |
| `--color-surface` | Cards, grid background |
| `--color-danger` | Delete, errors |

Typography: system-ui, 14px base, 12px meta, 16px section titles.

---

## Layout mapping (w.php → SPA)

| Original | SPA |
|----------|-----|
| frameset + indexmenu | `AppShell` sidebar + header |
| groupdbprint / tbl picker | Combobox in Editor header |
| KEY_* toolbar | Fixed toolbar: Add, Edit, Delete, SQL |
| HTML table `#myTable` | `DataGrid` sticky header, row select |
| POST form modal | `RecordForm` in `Modal` |
| printlimit / pagenow | Footer pagination bar |
| msgexiterror | Toast + inline field errors |

---

## Component library (v1)

| Component | Variants |
|-----------|----------|
| `Button` | primary, secondary, danger, ghost |
| `Input` | label + error |
| `Modal` | focus trap, Esc to close |
| `Toast` | info, success, error (3s auto-dismiss) |
| `DataGrid` | Phase 2 — selection, double-click edit |
| `RecordForm` | Phase 2 — dynamic fields from ColumnMeta |
| `Toolbar` | Phase 2 — permission-gated |

---

## Accessibility baseline

- WCAG AA contrast for text on `--color-bg` / `--color-surface`
- `:focus-visible` on all interactive elements
- Modal: `role="dialog"`, `aria-modal`, Esc closes
- Toasts: `aria-live="polite"`
- Login: Enter submits; labels on all inputs

---

## UX acceptance checklist (per UI phase)

1. Original actions reachable without extra clicks (or documented gap in handoff)
2. Permission-gated controls hidden/disabled — no 403 after click
3. Loading + error state on every fetch
4. No hardcoded RU/EN strings in components (i18n phase)
5. Focus visible; modal traps focus
6. Screenshot in handoff for dj--alex

---

## Responsive minimum

- Sidebar collapses to horizontal nav below 1024px
- Editor usable at 1280px+ width
- Login card centered on all viewports
