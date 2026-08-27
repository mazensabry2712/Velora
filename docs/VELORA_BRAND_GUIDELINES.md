# Velora Brand Guidelines

## Purpose

This document is the single source of truth for Velora's visual identity. Landing pages, authentication screens, booking flows, customer portal, admin dashboard, emails, and future UI components must use these tokens unless a semantic system color is required.

## Brand Color System

### Primary Signature Gradient

The signature Velora gradient is:

`#6D46FF → #006CFF → #00B8FF`

Use it selectively for high-value brand moments: logo treatments, primary CTAs, hero accents, active/focus highlights, and major interactive surfaces. Avoid applying the gradient to every card or section.

| Token | HEX | Role |
| --- | --- | --- |
| Primary Purple | `#6D46FF` | Gradient start / primary brand |
| Primary Blue | `#006CFF` | Gradient middle / strong action |
| Cyan | `#00B8FF` | Gradient endpoint / energetic highlight |

### Core Light UI Colors

| Token | HEX | Recommended use |
| --- | --- | --- |
| Sky Blue | `#1677FF` | Links, actions, booking interactions |
| Mint | `#00D4A3` | Success, growth, confirmed states |
| Deep Navy | `#0D1226` | Main text, dark UI, strong surfaces |
| Light Gray | `#F5F7FA` | Dashboard backgrounds, cards, soft sections |
| White | `#FFFFFF` | Cards, primary light surfaces, contrast |

### Supporting Colors

| Token | HEX | Recommended use |
| --- | --- | --- |
| Purple | `#8A5CFF` | Secondary purple accents |
| Violet | `#A855F7` | Decorative / secondary accent |
| Green | `#22C55E` | Positive semantic states |
| Orange | `#FF9F0A` | Warning / attention |
| Pink | `#FF4D8D` | Optional promotional emphasis |

### Neutral System

| Token | HEX | Recommended use |
| --- | --- | --- |
| Dark | `#1A233A` | Strong text / UI surfaces |
| Gray Dark | `#2D3748` | Secondary text / dense UI |
| Gray | `#4B5563` | Body text |
| Gray Light | `#9CA3AF` | Muted text / placeholders |
| Border | `#E5E7EB` | Borders and separators |

## Dark Mode System

Dark Mode is a first-class Velora theme. It is not a simple inversion of Light Mode.

| Token | HEX | Recommended use |
| --- | --- | --- |
| Dark Background | `#080B18` | Main page background |
| Dark Surface | `#0D1226` | Primary cards / shells |
| Dark Elevated | `#151C32` | Raised cards / menus / inputs |
| Dark Text | `#F8FAFC` | Primary text |
| Dark Muted | `#A7B0C0` | Secondary text |
| Dark Border | `#252E45` | Borders / separators |
| Dark Primary Purple | `#8A5CFF` | Dark-theme gradient start / accents |
| Dark Primary Blue | `#1677FF` | Dark-theme actions |
| Dark Cyan | `#00B8FF` | Highlights / gradient endpoint |
| Dark Success | `#00D4A3` | Success / confirmed states |

### Dark Mode Signature Gradient

For dark surfaces, the signature treatment remains recognizable while using brighter supporting stops when needed:

`#8A5CFF → #1677FF → #00B8FF`

The gradient should remain an accent, not the default background for every section.

## Semantic State Rules

Brand colors and semantic colors have different responsibilities:

- **Success / Confirmed:** `#00D4A3` or `#22C55E`
- **Warning:** `#FF9F0A`
- **Error / Destructive:** use the product's semantic red token; do not substitute purple/blue.
- **Info / Links:** `#1677FF`

Semantic states must remain understandable regardless of Light or Dark Mode.

## Usage Rules

1. The purple → blue → cyan gradient is the primary Velora visual identifier.
2. Deep Navy is the foundation for typography and dark UI.
3. Sky Blue is preferred for solid links and booking actions when a gradient is unnecessary.
4. Mint is reserved for success, growth, and confirmed states.
5. Light Mode should stay calm with white and `#F5F7FA` surfaces.
6. Dark Mode should stay calm with `#080B18`, `#0D1226`, and `#151C32` surfaces.
7. Supporting colors must be used intentionally; do not place every accent color in the same section.
8. Do not mix the deprecated Navy/Teal experimental palette with the current Velora identity.
9. Prefer reusable CSS variables/tokens over hard-coded one-off shades.
10. Responsive layouts must preserve the same brand hierarchy on desktop, tablet, and mobile.
11. Primary CTAs should normally use the signature gradient; secondary CTAs can use Deep Navy, white, or outlined surfaces according to the active theme.
12. Logo and brand assets must remain visually clear against their surrounding surface; do not recolor the original logo asset unless a dedicated approved asset exists.

## Suggested CSS Tokens

```css
:root {
    --velora-primary-purple: #6D46FF;
    --velora-primary-blue: #006CFF;
    --velora-cyan: #00B8FF;
    --velora-gradient: linear-gradient(90deg, #6D46FF 0%, #006CFF 52%, #00B8FF 100%);

    --velora-sky-blue: #1677FF;
    --velora-mint: #00D4A3;
    --velora-deep-navy: #0D1226;
    --velora-light-gray: #F5F7FA;
    --velora-white: #FFFFFF;

    --velora-purple: #8A5CFF;
    --velora-violet: #A855F7;
    --velora-green: #22C55E;
    --velora-orange: #FF9F0A;
    --velora-pink: #FF4D8D;

    --velora-dark: #1A233A;
    --velora-gray-dark: #2D3748;
    --velora-gray: #4B5563;
    --velora-gray-light: #9CA3AF;
    --velora-border: #E5E7EB;

    --velora-dark-bg: #080B18;
    --velora-dark-surface: #0D1226;
    --velora-dark-elevated: #151C32;
    --velora-dark-text: #F8FAFC;
    --velora-dark-muted: #A7B0C0;
    --velora-dark-border: #252E45;
    --velora-dark-gradient: linear-gradient(90deg, #8A5CFF 0%, #1677FF 52%, #00B8FF 100%);
}
```

## Important Branding Decision

The earlier experimental Navy/Teal palettes are deprecated. New UI work must use this document as the authoritative Velora palette, including its Light Mode and Dark Mode systems.
