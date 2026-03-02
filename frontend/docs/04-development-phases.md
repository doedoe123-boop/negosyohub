# Frontend Development Phases

## Overview

The frontend is a Vue 3 SPA consuming the Laravel JSON API.
Development is split into phases that each deliver working, testable functionality.

---

## Phase 5A — Foundation & Core Layout _(current)_

**Goal:** Platform shell, routing, auth, and basic store browsing working end-to-end.

### Deliverables

- [ ] Design system tokens applied (colors, typography per `03-design-system.md`)
- [ ] Navbar redesign — platform feel, sector links, cart badge
- [ ] Homepage — hero, sector picker, featured stores grid
- [ ] Store listing page — filterable by sector/category
- [ ] Store detail page — store header, product grid
- [ ] Product detail page
- [ ] Auth pages — login, register (already scaffolded)
- [ ] Auth guards working (already done)
- [ ] Basic responsive layout (mobile-first)

### API endpoints needed (Laravel)

- `GET /api/stores` — paginated, filterable by `sector`
- `GET /api/stores/{slug}` — store + products
- `GET /api/products/{id}` — product detail

### Tests

- Unit: layout components, store/product cards
- E2E: homepage renders, store browsing, auth flow (already done)

---

## Phase 5B — Cart & Checkout

**Goal:** Full add-to-cart → checkout → order placed flow.

### Deliverables

- [ ] Cart page — line items, quantity controls, totals
- [ ] Cart drawer — slide-in, accessible from any page
- [ ] Cart restricted to one store at a time (warn on cross-store add)
- [ ] Checkout — 3 steps: address → shipping → payment
- [ ] PayPal / PayMongo integration
- [ ] Order confirmation page (`/checkout/success`)

### API endpoints needed

- `GET /api/cart`
- `POST /api/cart/lines` — add item
- `PATCH /api/cart/lines/{id}`
- `DELETE /api/cart/lines/{id}`
- `GET /api/cart/shipping-options`
- `POST /api/cart/shipping-option`
- `POST /api/cart/address`
- `POST /api/orders` — place order

### Tests

- E2E: cart add, checkout address → shipping step (already done)
- E2E: payment redirect, success page

---

## Phase 5C — Customer Account

**Goal:** Authenticated customer can manage their orders and profile.

### Deliverables

- [ ] Orders list (`/account/orders`)
- [ ] Order detail (`/account/orders/:id`) — status, line items, timeline
- [ ] Profile page (`/account/profile`) — update name, email, password
- [ ] Notification preferences (future)

### API endpoints needed

- `GET /api/orders`
- `GET /api/orders/{id}`
- `PUT /api/user/profile`
- `PUT /api/user/password`

### Tests

- E2E: orders list renders, order detail shows line items
- Unit: OrderDetail component status badge

---

## Phase 5D — Real Estate Sector

**Goal:** Property listings with a distinct UX from food/retail.

### Deliverables

- [ ] Properties listing page — list + map toggle
- [ ] Property detail page — gallery, details, agent info, inquiry form
- [ ] Inquiry submission (no cart, no checkout)
- [ ] Property search + filters (price range, bedrooms, type)
- [ ] "Contact Agent" CTA

### API endpoints needed

- `GET /api/properties` — paginated, filterable
- `GET /api/properties/{slug}`
- `POST /api/properties/{slug}/inquire`

### Tests

- E2E: properties list renders, inquiry form submits

---

## Phase 5E — Polish & Performance

**Goal:** Production-ready UX quality.

### Deliverables

- [ ] Loading skeletons (store cards, product grid, order list)
- [ ] Error states (empty states, API error banners)
- [ ] Toast notification system (cart add, order placed, errors)
- [ ] Image lazy loading + optimized thumbnail sizes
- [ ] SEO meta tags per route (`useHead` or `@vueuse/head`)
- [ ] Accessibility audit (ARIA labels, keyboard navigation, focus traps)
- [ ] Core Web Vitals pass (LCP < 2.5s, CLS < 0.1)
- [ ] PWA manifest + offline splash (optional)

### Tests

- Lighthouse CI in GitHub Actions
- A11y tests via Playwright `page.accessibility.snapshot()`

---

## Phase 6 — Delivery & Payouts _(deferred)_

**Goal:** Rider assignment and automated store payouts.

### Deliverables

- [ ] Lalamove delivery booking integration
- [ ] Order status tracking (real-time via polling or websockets)
- [ ] Stripe Connect payout dashboard for store owners
- [ ] Commission reports

---

## Principles Across All Phases

1. **API-first** — never hardcode data; all content comes from the backend
2. **Test before done** — each feature ships with at least one E2E test
3. **Mobile-first** — design at 375px width, enhance upward
4. **Route mocking in E2E** — never hit the real backend in Playwright tests
5. **Sector boundaries** — cart/checkout only appears for food/retail routes; real estate has inquiry flow

---

## Current Test Status

| Suite                     | Count | Status         |
| ------------------------- | ----- | -------------- |
| Unit (Vitest)             | 28    | ✅ All passing |
| E2E (Playwright/Chromium) | 19    | ✅ All passing |
