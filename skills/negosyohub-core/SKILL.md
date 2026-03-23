---
name: negosyohub-core
description: repo-wide guidance for shared architecture, multi-tenant marketplace rules, testing expectations, security-sensitive changes, cross-sector workflows, phasing audits, and documentation synchronization in negosyohub
---

# NegosyoHub Core

Use this skill first for any task that touches shared architecture, multiple sectors, shared UI, shared API contracts, shared services, PHASING updates, or security-sensitive workflows.

## Trigger Conditions

Use this skill when the task:
- touches more than one domain or sector
- changes shared Laravel or Vue infrastructure
- changes auth, multi-tenancy, payments, orders, notifications, localization, search, or webhooks
- requires an audit before implementation
- asks for documentation, PHASING synchronization, test coverage, or security review

Use this skill together with a sector skill when the task is domain-specific but still affects shared flows.

## Domain Overview

NegosyoHub is a multi-sector marketplace with shared customer auth, store onboarding, tenant-aware panels, sector storefronts, and overlapping commerce flows. The same repo contains:
- shared marketplace models and services in [`src/app`](../../src/app)
- sector pages and shared SPA logic in [`frontend/src`](../../frontend/src)
- admin, store, and sector-specific Filament resources in [`src/app/Filament`](../../src/app/Filament)
- project status documentation in [`PHASING.md`](../../PHASING.md)

## Implementation Guidance

- Audit existing implementation before building. Check backend, frontend, migrations, tests, and docs together.
- If behavior already exists and works, do not rebuild it. Extend partial implementations instead.
- Reuse existing services, enums, requests, resources, composables, and stores before creating new ones.
- Preserve multi-tenant isolation. Scope store-owned data by `store_id`, ownership, or panel context.
- Keep status models separated when applicable:
  - `order_status` for seller/order workflow
  - `payment_status` for payment lifecycle
  - `delivery_status` for logistics lifecycle
- Keep payment, shipping, inquiry, and review logic out of controllers when a service layer already exists or is warranted.
- Check existing API response patterns before introducing new response shapes.
- Prefer additive refactors over parallel systems.

## Bug-Fixing Guidance

- Reproduce or inspect the bug path first: route, controller/service, model/resource, frontend page/store, and existing tests.
- Fix root cause, not only the surface symptom.
- Look for cross-sector impact before patching shared code.
- If the issue is security-sensitive, review authorization, validation, serialization, and tenant scoping before finalizing.

## Refactor Guidance

- Refactor toward existing conventions already present in sibling files.
- Collapse duplicated sector logic into shared services only when the rules are truly shared.
- Avoid renaming or restructuring broad shared systems unless the task requires it.
- When refactoring for testability, keep business behavior unchanged and call that out explicitly.

## Testing Expectations

- Add or update tests whenever behavior changes or a bug is fixed.
- Prefer Pest feature tests for backend workflows and Vitest behavior tests for SPA logic.
- Skip duplicate tests if meaningful coverage already exists.
- For shared changes, verify both backend and frontend impact.
- Re-run targeted tests first, then broader suites when the change affects shared architecture.

## Documentation And PHASING Rules

- Update [`PHASING.md`](../../PHASING.md) when a feature moves from not implemented to partial or complete, or when audits show the current doc is stale.
- Do not mark a feature complete unless backend logic, UI/panel/API flow, and integrations are connected end-to-end where applicable.
- Keep notes honest about partial coverage and missing live integrations.

## Security And Data Isolation Cautions

- Preserve store/customer/admin authorization boundaries.
- Never trust client-provided totals, statuses, or ownership identifiers.
- Check mass assignment, request validation, serialization, and file access on shared changes.
- Review session/token behavior and sensitive data exposure when touching auth or API clients.

## Common Pitfalls

- Mixing sector-specific business rules into shared marketplace services
- Treating a model or admin resource as proof that a feature is complete
- Using unscoped queries in store-owned or tenant-owned data
- Collapsing payment, order, and delivery states into one enum or field
- Updating code without reflecting confirmed status changes in [`PHASING.md`](../../PHASING.md)

## Common Files And Folders

- [`src/app`](../../src/app)
- [`src/app/Services`](../../src/app/Services)
- [`src/app/Http`](../../src/app/Http)
- [`src/app/Filament`](../../src/app/Filament)
- [`src/routes`](../../src/routes)
- [`src/database`](../../src/database)
- [`src/tests`](../../src/tests)
- [`frontend/src`](../../frontend/src)
- [`frontend/tests`](../../frontend/tests)
- [`PHASING.md`](../../PHASING.md)
