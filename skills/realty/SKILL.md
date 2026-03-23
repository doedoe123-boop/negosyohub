---
name: realty
description: domain guidance for property listings, developments, agent profiles, inquiries, open houses, saved searches, compare flows, maps, and real estate storefront and panel workflows in negosyohub
---

# Realty

Use this skill for real-estate listing, inquiry, development, and property discovery work. Pair it with `negosyohub-core` when shared auth, localization, analytics, or PHASING updates are involved.

## Trigger Conditions

Use this skill when the task touches:
- properties, developments, agent profiles, realty stores
- inquiry submission, inquiry status, saved searches, compare flows
- open houses and RSVP flows
- maps, location data, property analytics, listing detail pages
- realty panel resources and listing lifecycle actions

## Domain Overview

Realty is lead-driven rather than checkout-driven. Trust, listing accuracy, publishing state, inquiry handling, and location context matter more than payment capture. Developments, properties, agents, inquiries, and saved searches are tightly related and should stay consistent across public pages and the realty panel.

## Implementation Guidance

- Audit property and development relationships first before changing listing detail or browse flows.
- Preserve property publishing and availability rules; do not expose draft, pending, or suspended listings unintentionally.
- Keep inquiry lifecycle rules explicit. If status logic already exists, extend it instead of inventing parallel states.
- Maintain consistency between property detail, development detail, agent detail, compare, and saved-search flows.
- Treat maps and location fields as supporting trust and discovery; do not silently drop or overwrite location metadata.
- If development data feeds property listings, keep the relationship one-way and predictable.

## Bug-Fixing Guidance

- Check whether the issue originates in model scopes, API filtering, resource serialization, or page-level theming/state.
- For mixed listing-state bugs, verify public visibility rules and panel visibility rules separately.
- For map/location issues, verify coordinates, address normalization, and fallback behavior.
- For inquiry bugs, test guest vs authenticated flows and ownership visibility.

## Refactor Guidance

- Refactor toward shared listing/detail primitives only when the semantics are genuinely shared.
- Avoid forcing ecommerce assumptions into realty pages.
- Preserve distinct handling for properties, developments, and agents even when the UI looks similar.

## Testing Expectations

- Cover property browse/filter APIs, property detail, inquiry submission, saved searches, open house RSVP, compare behavior, and key location/map fallbacks.
- Add tests for public visibility, inquiry authorization, and realty panel scoping.
- On the frontend, test filters, detail rendering, inquiry forms, compare state, and saved-search interactions.

## Documentation And PHASING Rules

- Update [`PHASING.md`](../../PHASING.md) when realty discovery, inquiry, RSVP, compare, or development features materially move status.
- Mark a realty feature complete only when public flow, backend logic, and panel/admin support line up where expected.

## Security And Data Isolation Cautions

- Preserve store/agent ownership boundaries in realty panel actions.
- Do not expose unpublished or cross-tenant inquiries.
- Sanitize any seller-managed rich text that renders on public listing pages.

## Common Pitfalls

- Treating realty like ecommerce checkout flows
- Breaking development-to-property consistency
- Exposing suspended or unpublished listings through broad scopes
- Fixing listing detail visuals without checking inquiry, compare, and saved-search behavior

## Common Files And Folders

- [`src/app/Models/Property.php`](../../src/app/Models/Property.php)
- [`src/app/Models/Development.php`](../../src/app/Models/Development.php)
- [`src/app/Http/Controllers/Api/V1/PropertyController.php`](../../src/app/Http/Controllers/Api/V1/PropertyController.php)
- [`src/app/Http/Resources/Api/V1`](../../src/app/Http/Resources/Api/V1)
- [`src/app/Filament/Realty`](../../src/app/Filament/Realty)
- [`frontend/src/pages/realty`](../../frontend/src/pages/realty)
- [`frontend/src/api/properties.js`](../../frontend/src/api/properties.js)
- [`frontend/src/api/inquiries.js`](../../frontend/src/api/inquiries.js)
