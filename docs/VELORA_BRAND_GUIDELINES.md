# Velora Brand Guidelines

## Brand Color System

This document is the single source of truth for Velora's visual color identity. Product pages, dashboards, landing pages, authentication screens, booking pages, email templates, and future UI components should use this palette unless a semantic system color is required.

## Primary Brand Gradient

The signature Velora gradient is:

`#6D46FF → #006CFF → #00B8FF`

Use this gradient for high-value brand moments such as the main logo treatment, primary CTAs, hero accents, key highlights, and major interactive brand surfaces.

### Gradient Stops

| Token | HEX | Role |
| --- | --- | --- |
| Primary Purple | `#6D46FF` | Start of the signature gradient; primary brand color |
| Primary Blue | `#006CFF` | Middle of the signature gradient; main action blue |
| Cyan | `#00B8FF` | End of the signature gradient; energetic highlight |

## Core Colors

| Token | HEX | Recommended use |
| --- | --- | --- |
| Primary Gradient | `#6D46FF → #006CFF` | Logo, CTA, major brand elements |
| Sky Blue | `#1677FF` | Links, actions, booking interactions |
| Mint | `#00D4A3` | Success, growth, confirmed states |
| Deep Navy | `#0D1226` | Main text, dark UI, dark backgrounds |
| Light Gray | `#F5F7FA` | Dashboard backgrounds, cards, soft sections |

## Supporting Colors

| Token | HEX | Recommended use |
| --- | --- | --- |
| Purple | `#8A5CFF` | Secondary purple accents |
| Violet | `#A855F7` | Decorative/secondary accent |
| Cyan | `#00B8FF` | Highlights and gradient endpoint |
| Green | `#22C55E` | Positive semantic states |
| Orange | `#FF9F0A` | Warning / attention states |
| Pink | `#FF4D8D` | Optional promotional/emphasis accent |

## Neutral System

| Token | HEX | Recommended use |
| --- | --- | --- |
| Dark | `#1A233A` | Strong text and UI surfaces |
| Gray Dark | `#2D3748` | Secondary text / dense UI |
| Gray | `#4B5563` | Body text |
| Gray Light | `#9CA3AF` | Muted text / placeholders |
| Border | `#E5E7EB` | Borders and separators |
| White | `#FFFFFF` | Cards, surfaces, contrast areas |

## Usage Rules

1. **The signature gradient is the primary Velora visual identifier.** It should be recognizable across the product.
2. Use **Deep Navy** for primary typography and dark surfaces instead of pure black.
3. Use **Sky Blue** for interactive links and booking-oriented actions where a solid color is preferable to the gradient.
4. Use **Mint** for success and confirmed states. Do not replace semantic success colors with the brand gradient.
5. Keep backgrounds calm with `#F5F7FA`, white, or Deep Navy. Avoid mixing unrelated background hues.
6. Purple, Violet, Cyan, Orange, Pink, and Green are supporting colors. Do not use all supporting colors in the same section.
7. Components should expose reusable CSS variables/tokens instead of hard-coding slightly different shades.
8. Responsive layouts must preserve the same color hierarchy on desktop, tablet, and mobile.

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
    --velora-white: #FFFFFF;
}
```

## Important Branding Decision

Earlier experimental Navy/Teal palettes are **deprecated**. New UI work must use this document as the authoritative Velora palette.
