---
name: logistics
description: domain guidance for parcel and goods delivery, shipment tracking, sender and receiver information, provider-agnostic courier integrations, and delivery status workflows in negosyohub
---

# Logistics

Use this skill for parcel and goods delivery workflows. Do not use it for household relocation; that belongs to `moving-service`. Pair it with `ecommerce` when logistics work is attached to physical order fulfillment.

## Trigger Conditions

Use this skill when the task touches:
- shipments, delivery records, tracking references, courier provider integration
- sender/receiver logistics information
- parcel status progression, tracking UI, provider booking scaffolding
- delivery provider architecture such as Lalamove-style integration points

## Domain Overview

Logistics in NegosyoHub is provider-agnostic shipment handling for goods and items, not whole-home relocation. It overlaps with ecommerce fulfillment but should stay separated from order payment and seller workflow. Shipment state must remain independently traceable.

## Implementation Guidance

- Audit the existing shipment architecture first. Reuse `Shipment`, `DeliveryStatus`, and logistics manager patterns rather than creating another delivery table or status system.
- Keep shipment state separate from `order_status` and `payment_status`.
- Preserve provider-agnostic design. Do not hardwire business rules to one courier.
- Keep tracking references, driver metadata, pickup/dropoff details, and provider payloads in shipment-related models/services.
- If logistics updates trigger webhooks or notifications, verify that side effects already go through shared infrastructure.

## Bug-Fixing Guidance

- Check whether the bug is in shipment creation, provider assignment, tracking serialization, status update, or customer-facing delivery progress.
- Verify store/admin actions and customer order detail together when a shipment bug is reported.
- If a provider-specific issue appears, isolate it inside the logistics manager or provider adapter path instead of leaking it into shared order code.

## Refactor Guidance

- Refactor toward explicit shipment/provider abstractions, not hidden flags on orders.
- Keep provider response payloads and manual override fields readable and audit-friendly.
- Do not merge logistics status fields into order fulfillment status.

## Testing Expectations

- Cover shipment creation, status updates, tracking serialization, provider filtering, webhook side effects, and customer delivery progress when behavior changes.
- Add tests for both success and failure paths of provider-facing jobs or manual status transitions.
- Prefer extending shipment/logistics service tests before adding controller-only smoke tests.

## Documentation And PHASING Rules

- Update [`PHASING.md`](../../PHASING.md) when logistics architecture, provider integration readiness, or customer delivery progress materially changes implementation status.
- Distinguish between internal logistics architecture and live third-party provider integration in documentation notes.

## Security And Data Isolation Cautions

- Preserve store/customer visibility boundaries for shipment details.
- Avoid leaking driver contact details or provider payloads beyond authorized contexts.
- Validate manual status update actions in admin/store panels.

## Common Pitfalls

- Confusing logistics with moving-service
- Embedding shipment state into orders instead of related shipment records
- Hardcoding one courier provider into shared delivery logic
- Updating tracking UI without validating underlying shipment status transitions

## Common Files And Folders

- [`src/app/Models/Shipment.php`](../../src/app/Models/Shipment.php)
- [`src/app/Services/LogisticsManager.php`](../../src/app/Services/LogisticsManager.php)
- [`src/app/DeliveryStatus.php`](../../src/app/DeliveryStatus.php)
- [`src/app/ShipmentProvider.php`](../../src/app/ShipmentProvider.php)
- [`src/app/Jobs/DeliverWebhookJob.php`](../../src/app/Jobs/DeliverWebhookJob.php)
- [`src/tests/Feature/LogisticsManagerTest.php`](../../src/tests/Feature/LogisticsManagerTest.php)
- [`frontend/src/pages/account/OrderDetail.vue`](../../frontend/src/pages/account/OrderDetail.vue)
- [`frontend/src/pages/checkout`](../../frontend/src/pages/checkout)
