# Velora Public UI Architecture

## Objective

The Public-facing surfaces must share one visual system without forcing every page into one identical layout.

## Layering

```text
Velora Brand Guidelines
        ↓
public/css/velora-brand.css
        ↓
public/css/velora-public.css
        ↓
Public page compositions
```

`velora-brand.css` is the source of truth for brand colors and semantic tokens.
`velora-public.css` provides reusable Public primitives and responsive behavior.

## Shared Public Scope

The shared design system is intended for:

- Landing / marketing pages.
- Signup / conversion pages.
- Pricing pages.
- Public account discovery / Find Account.
- Tenant Login and other Tenant authentication surfaces.
- Future public-facing transactional pages such as verification and provisioning where appropriate.

Super Admin remains a separate product surface and is intentionally excluded from the Tenant/Public language lifecycle for now.

## Shared Primitives

The shared layer provides:

- Standard Public container width and responsive gutters.
- Surface/card geometry and borders.
- Glass treatment.
- Primary and secondary buttons.
- Form inputs and selects.
- Form labels.
- Public kicker/badge treatment.
- Gradient text/brand emphasis.
- Focus-visible behavior.
- Dark theme semantics.
- Reduced-motion handling.

## Page Composition Rules

Pages must not become clones of one another.

- Landing: marketing/marketing narrative and conversion CTA.
- Signup: registration form plus onboarding/value framing.
- Pricing: commercial comparison and conversion.
- Verification/Provisioning: focused transactional status/action UI.
- Login: authentication-focused composition.

All of them consume the same brand tokens and Public primitives so that typography, color, control geometry, spacing language, shadows and responsive behavior remain recognizably Velora.

## Current Integration

- `layouts/landing.blade.php` loads `velora-brand.css`; therefore pages such as Signup that extend the Landing layout inherit the shared Public layer.
- Tenant Login loads `velora-brand.css` and its auth-specific `velora-auth.css`; the auth composition sits on top of the same brand/Public foundation.
- `velora-brand.css` imports `velora-public.css`, keeping the dependency chain explicit.
- Existing page-specific CSS may remain during progressive migration, but it should consume Velora variables instead of inventing new brand palettes.

## Localization Contract

The shared visual layer must remain neutral to locale. Locale and direction are supplied by the page/application layer.

Supported platform locales currently are:

`ar`, `en`, `fr`, `es`, `de`, `it`, `pt`, `ru`, `zh`, `ja`, `tr`, `hi`, `ko`, `nl`, `id`.

Arabic uses RTL; the remaining supported locales use LTR.

## Validation

The design-system contract is protected by `tests/Feature/PublicDesignSystemContractTest.php`.

Runtime browser QA is still required for visual comparison across Landing, Signup and Tenant Login, including mobile, dark mode, RTL and non-Latin scripts.

## Migration Rule

Future Public pages should not create a new standalone brand stylesheet unless there is a documented reason. Reusable visual behavior belongs in the shared Public layer; page-specific composition belongs in the page stylesheet/view.
