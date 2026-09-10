# Design System

<!-- impeccable:design-schema 1 -->

## World

Operate · light · desk work. A calm academic logbook for internship weeks — paper-like surface, ink typography, one teal accent for primary actions and current focus. No marketing gradients, glass, or dashboard card farms.

## Mode

Operate

## Palette

| Token | Value | Role |
|-------|-------|------|
| `--color-paper` | `#F4F1EA` | Page background |
| `--color-surface` | `#FFFdf8` | Panels, forms |
| `--color-ink` | `#1C1917` | Primary text |
| `--color-ink-muted` | `#57534E` | Secondary text |
| `--color-line` | `#E7E5E4` | Dividers, borders |
| `--color-accent` | `#0F6B5C` | Primary actions, focus, links |
| `--color-accent-hover` | `#0A5246` | Hover |
| `--color-accent-soft` | `#E6F2EF` | Soft selected/bg |
| `--color-ok` | `#166534` | Completed |
| `--color-ok-soft` | `#DCFCE7` | Completed chip bg |
| `--color-warn` | `#92400E` | In progress |
| `--color-warn-soft` | `#FEF3C7` | In progress chip bg |
| `--color-locked` | `#78716C` | Locked |
| `--color-danger` | `#B91C1C` | Errors |
| `--color-danger-soft` | `#FEE2E2` | Error bg |

## Typography

- Family: **IBM Plex Sans** (400/500/600) — academic/technical, not Inter/Roboto
- Scale (fixed rem): 0.75 meta · 0.875 label/ui · 1 body · 1.125 lead · 1.5 section · 1.875 page title
- Line height: body 1.5; headings 1.2
- No gradient text; emphasis via weight

## Layout

- Max content width: 72rem; padding 1.25–1.5rem
- Profile strip then week list (not a 16-card grid)
- Weeks as a single scannable list/table: number, status, preview, action
- Forms: stacked labels above fields; one primary button per section
- Modal only for profile edit (short interrupt)

## Components

- **Nav:** solid surface, bottom border, text links; mobile hamburger or wrap links (no hidden-only nav)
- **Status chip:** soft bg + ink status color; short labels (Not started / In progress / Done / Locked)
- **Primary button:** accent fill, white text, 2.5rem min height touch
- **Secondary button:** border + ink
- **Flash:** soft tinted bars, no heavy icons required
- **Empty:** short sentence + one next action (Open week 1 / Edit profile)

## Motion

- Prefer `prefers-reduced-motion: reduce` → instant
- Otherwise: 150–200ms opacity/transform on modal open; list row hover is color only

## Anti-patterns (banned here)

- Indigo/violet gradients, glass/blur chrome, Inter/Roboto stacks
- Equal card grids for the 16 weeks
- Eyebrow/kicker labels above titles
- Decorative left accent bars on every row
- Emoji as UI icons
