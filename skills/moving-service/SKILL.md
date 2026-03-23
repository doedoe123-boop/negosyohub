---
name: moving-service
description: domain guidance for lipat-bahay booking workflows, moving schedules, pickup and dropoff handling, add-on services, relocation coordination, and rental or realty move assistance in negosyohub
---

# Moving Service

Use this skill for household relocation booking and coordination. Do not use it for parcel shipping; that belongs to `logistics`. Use it alongside `rental` or `realty` when the move is derived from a signed agreement or selected property.

## Trigger Conditions

Use this skill when the task touches:
- mover listings, mover details, moving-service checkout or booking
- pickup/dropoff addresses, scheduled move date/time, household relocation add-ons
- moving booking status, provider coordination, moving reviews
- prefilled moving data coming from rental or realty context

## Domain Overview

Moving-service is about transporting household belongings between residences. It is a scheduled service workflow with origin, destination, timing, service options, and provider coordination. It is not generic shipping and should not be modeled like parcel logistics.

## Implementation Guidance

- Audit current booking flow, mover provider models, and address handling before extending behavior.
- Preserve distinction between a moving booking and an ecommerce shipment.
- Keep pickup and dropoff addresses explicit and validated.
- If rental or realty prefill exists, reuse origin/destination context instead of asking users to re-enter data.
- Keep add-ons, pricing, and schedule rules server-backed where possible.
- If reviews exist, confirm whether they target the mover, booking, or overall service.

## Bug-Fixing Guidance

- Check whether the bug is in booking creation, schedule validation, address persistence, or provider-facing state updates.
- Reproduce with both standalone move bookings and property/rental-driven prefills when those paths exist.
- Verify user-facing schedule text and backend stored timestamps together.

## Refactor Guidance

- Avoid collapsing moving-service into generic logistics or checkout abstractions unless the existing code already shares them.
- Keep move coordination fields cohesive: addresses, schedule, add-ons, and provider assignment should stay readable together.
- Preserve existing prefilling or recommendation integrations from rental/realty flows.

## Testing Expectations

- Cover booking creation, schedule handling, pickup/dropoff validation, add-on pricing behavior, and review flow if touched.
- Add tests for prefilled origin/destination behavior when invoked from rental or realty context.
- On the frontend, test form validation, date/time flow, and booking confirmation behavior.

## Documentation And PHASING Rules

- Update [`PHASING.md`](../../PHASING.md) when moving-service booking, coordination, or rental handoff support materially changes.
- Document explicitly if a workflow is a recommendation/prefill only rather than a full automatic handoff.

## Security And Data Isolation Cautions

- Preserve customer ownership over bookings and addresses.
- Avoid exposing another customer’s move schedule or location details.
- Validate provider/staff actions carefully when status updates are panel-driven.

## Common Pitfalls

- Modeling moving-service like parcel delivery
- Requiring users to re-enter addresses that are already available from rental/realty context
- Treating move dates as cosmetic fields without backend validation

## Common Files And Folders

- [`src/app/Models`](../../src/app/Models)
- [`src/app/Services`](../../src/app/Services)
- [`src/tests/Feature`](../../src/tests/Feature)
- [`frontend/src/pages/movers`](../../frontend/src/pages/movers)
- [`frontend/src/api`](../../frontend/src/api)
