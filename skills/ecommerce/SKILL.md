---
name: ecommerce
description: domain guidance for products, stores, cart, checkout, orders, payments, cash on delivery, discounts, commissions, reviews, and ecommerce storefront workflows in negosyohub
---

# Ecommerce

Use this skill for ecommerce storefront, checkout, order, payment, discount, and review work. Pair it with `negosyohub-core` for shared architecture or PHASING-sensitive changes.

## Trigger Conditions

Use this skill when the task touches:
- products, variants, categories, collections, storefront product browsing
- cart, checkout, addresses, shipping options, payments, PayPal, COD
- orders, order history, order detail, commissions, payouts
- coupons, promo codes, totals, reviews for products or stores

## Domain Overview

Ecommerce is the most stateful sector in the repo. It depends on accurate pricing, cart integrity, store scoping, and payment/order state correctness. This area commonly overlaps with Lunar models, shared order services, and customer account pages.

## Implementation Guidance

- Audit existing cart, checkout, order, and payment flows before touching anything. Do not create a parallel checkout path.
- Preserve the single-store cart constraint.
- Keep discount and total calculation on the backend. The frontend may display totals but must not be the source of truth.
- Preserve payment method extensibility. Use the existing payment architecture rather than hardcoding PayPal or COD logic into controllers.
- Keep `payment_method` and `payment_status` accurate and independent from seller workflow.
- Treat COD as an unpaid order until explicitly marked paid.
- Preserve commission and payout calculations through shared services.
- For reviews, confirm whether the flow is product review, store review, or both before changing UI or policies.

## Bug-Fixing Guidance

- Check whether the bug is in API validation, service calculation, panel action, or SPA state synchronization.
- For price, coupon, or payment bugs, verify server-side totals, stored order totals, and customer-facing amounts together.
- For checkout bugs, inspect cart state, address/shipping selection, and payment path transitions end-to-end.

## Refactor Guidance

- Consolidate duplicated price/discount formatting only if the logic is presentation-only.
- Move business rules into services, requests, or resources rather than Vue pages or controllers.
- Preserve Lunar integration patterns already in use.

## Testing Expectations

- Cover cart mutation, single-store enforcement, checkout progression, payment method selection, order placement, and order history.
- Add backend tests for payment amount integrity, discount application, and authorization.
- Add frontend tests for checkout steps, cart quantity changes, coupon behavior, and COD vs PayPal selection.

## Documentation And PHASING Rules

- Update [`PHASING.md`](../../PHASING.md) when discounts, payment methods, reviews, or payout behavior materially change implementation status.
- Do not mark discount or payment work complete unless backend totals and UI/account flows are both wired.

## Security And Data Isolation Cautions

- Never trust client-side prices, discount totals, shipping totals, or payment success claims.
- Scope store-owned order data correctly in panels and APIs.
- Verify order detail and review endpoints cannot expose other stores’ or customers’ records.

## Common Pitfalls

- Trusting frontend totals or coupon math
- Mixing payment state with order fulfillment state
- Allowing COD or shipping logic for unsupported sectors by accident
- Updating checkout UI without covering order confirmation and account history

## Common Files And Folders

- [`src/app/Services/PaymentManager.php`](../../src/app/Services/PaymentManager.php)
- [`src/app/Services/CheckoutDiscountService.php`](../../src/app/Services/CheckoutDiscountService.php)
- [`src/app/Services/OrderService.php`](../../src/app/Services/OrderService.php)
- [`src/app/Http/Controllers/Api/V1/CartController.php`](../../src/app/Http/Controllers/Api/V1/CartController.php)
- [`src/app/Http/Controllers/Api/V1/OrderController.php`](../../src/app/Http/Controllers/Api/V1/OrderController.php)
- [`src/app/Filament/Admin/Resources/OrderResource.php`](../../src/app/Filament/Admin/Resources/OrderResource.php)
- [`frontend/src/pages/cart`](../../frontend/src/pages/cart)
- [`frontend/src/pages/checkout`](../../frontend/src/pages/checkout)
- [`frontend/src/pages/account`](../../frontend/src/pages/account)
- [`frontend/src/stores/cart.js`](../../frontend/src/stores/cart.js)
- [`frontend/src/api/orders.js`](../../frontend/src/api/orders.js)
