---
name: rental
description: domain guidance for rental listings, tenant-landlord matching, rental inquiry and approval workflows, rental agreements, and move-in readiness flows in negosyohub
---

# Rental

Use this skill for paupahan and rental-agreement work. Use `realty` for broader property browsing/listing concerns, and use `moving-service` only when the task shifts into relocation booking.

## Trigger Conditions

Use this skill when the task touches:
- rental-specific listing states or availability
- rental inquiry to approval flow
- rental agreements, tenant/landlord coordination, move-in readiness
- account pages or panel workflows tied to rental agreements
- realty-to-rental handoff logic

## Domain Overview

Rental is about securing and managing occupancy, not moving belongings. The core workflow is rental inquiry -> evaluation/approval -> agreement -> move-in readiness. This domain may trigger moving-service recommendations later, but should not absorb relocation logic itself.

## Implementation Guidance

- Audit existing rental agreement and inquiry flow before extending it.
- Keep rental listing lifecycle separate from generic property publishing concerns when the code already distinguishes them.
- Preserve agreement state transitions and account visibility rules.
- If move-in readiness or scheduling exists, keep it tied to the agreement lifecycle rather than generic logistics.
- When a rental flow needs relocation support, hand off to moving-service explicitly instead of adding moving fields directly into rental models unless the repo already does that.

## Bug-Fixing Guidance

- Identify whether the bug belongs to listing state, inquiry review, agreement generation, or tenant account visibility.
- Check both public and account/panel experiences when fixing rental issues.
- Verify agreement artifacts, statuses, and notifications together if the bug affects tenant progression.

## Refactor Guidance

- Do not merge rental agreement logic into generic property flows unless the codebase already shares that abstraction.
- Keep tenant approval, agreement status, and move-in readiness explicit and traceable.
- Avoid duplicating moving-service scheduling inside rental workflows.

## Testing Expectations

- Cover rental inquiry, agreement creation/progression, tenant visibility, and move-in readiness state if behavior changes.
- Add tests around approval/rejection transitions and account access to agreements.
- Prefer extending existing rental agreement tests instead of creating parallel suites.

## Documentation And PHASING Rules

- Update [`PHASING.md`](../../PHASING.md) when rental agreement or move-in workflows materially change implementation status.
- Call out explicitly when a change is rental-only vs broader realty coverage.

## Security And Data Isolation Cautions

- Preserve tenant/landlord/store access boundaries on agreements and inquiry records.
- Protect private documents and agreement details from public or cross-tenant exposure.
- Verify account pages do not leak another customer’s rental records.

## Common Pitfalls

- Mixing rental agreement logic with generic realty browse logic
- Treating moving-service as part of rental instead of a downstream recommendation
- Exposing agreement states without tenant/store authorization checks

## Common Files And Folders

- [`src/app/Models/RentalAgreement.php`](../../src/app/Models/RentalAgreement.php)
- [`src/app/Services`](../../src/app/Services)
- [`src/tests/Feature/RentalAgreementProcessTest.php`](../../src/tests/Feature/RentalAgreementProcessTest.php)
- [`frontend/src/pages/account/RentalAgreementsPage.vue`](../../frontend/src/pages/account/RentalAgreementsPage.vue)
- [`frontend/src/pages/realty`](../../frontend/src/pages/realty)
