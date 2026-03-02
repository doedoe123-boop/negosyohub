# NegosyoHub — Development Phasing Document

> Multi-Sector Marketplace SaaS — Laravel 12 · Lunar PHP 1.3 · Filament v3 · Vue 3 · PostgreSQL

Last updated: **March 2, 2026**

---

## Phase 1 — Core Platform ✅ COMPLETE

Foundation: user system, multi-tenant store architecture, authentication.

| #   | Feature                                                                        | Status |
| --- | ------------------------------------------------------------------------------ | ------ |
| 1   | User roles (Admin, StoreOwner, Staff, Customer)                                | ✅     |
| 2   | Customer registration (web + API with Sanctum)                                 | ✅     |
| 3   | Store owner 5-step registration (account → store → address → ID → compliance)  | ✅     |
| 4   | Sector selection before registration                                           | ✅     |
| 5   | Dynamic sectors with required document types                                   | ✅     |
| 6   | Store registration → approval flow (Pending → Approved / Rejected / Suspended) | ✅     |
| 7   | Multi-tenant `store_id` filtering across all models                            | ✅     |
| 8   | Subdomain-based store login (`{slug}.domain/portal/{token}/login`)             | ✅     |
| 9   | Unique non-guessable login token per store                                     | ✅     |
| 10  | Role-based panel routing (Admin → Admin, Realty → Realty, Others → Lunar)      | ✅     |
| 11  | Staff management (create/manage within store)                                  | ✅     |
| 12  | Philippine ID validation (6 types with regex)                                  | ✅     |
| 13  | File upload content validation (MIME check, double-extension block)            | ✅     |
| 14  | Activity logging (Spatie Activitylog)                                          | ✅     |
| 15  | Login history tracking (success + failure)                                     | ✅     |
| 16  | Soft deletes on core tables                                                    | ✅     |
| 17  | Encrypted KYC fields (id_number, business_permit, compliance_documents)        | ✅     |
| 18  | HTTPS enforcement + HSTS in production                                         | ✅     |
| 19  | Rate limiting on API auth routes                                               | ✅     |
| 20  | Email notifications (store approved / suspended / reinstated)                  | ✅     |
| 21  | Public supplier profile page (`/suppliers/{slug}`)                             | ✅     |
| 22  | Spatie permission seeder (granular per resource)                               | ✅     |
| 23  | Sector browsing page (public, with search + supplier counts)                   | ✅     |

---

## Phase 2 — Admin Panel & Compliance ✅ COMPLETE

Admin dashboard, support, legal, content management.

| #   | Feature                                                                               | Status |
| --- | ------------------------------------------------------------------------------------- | ------ |
| 1   | Admin dashboard — platform stats widget (stores, users, orders, tickets)              | ✅     |
| 2   | Admin dashboard — sector distribution chart                                           | ✅     |
| 3   | Admin dashboard — store status chart                                                  | ✅     |
| 4   | Admin dashboard — recent tickets table                                                | ✅     |
| 5   | Store management resource (list, view, edit, approve, suspend, reinstate, KYC review) | ✅     |
| 6   | KYC document review with signed URL download/preview                                  | ✅     |
| 7   | Support ticket system (CRUD, categories, priorities, assignment, resolution)          | ✅     |
| 8   | Announcement system (type, audience, scheduling, expiry)                              | ✅     |
| 9   | FAQ management                                                                        | ✅     |
| 10  | Legal pages (Terms, Privacy — rich text, published flag, versioning)                  | ✅     |
| 11  | Sector management (create/edit sectors with required documents)                       | ✅     |
| 12  | Data lifecycle automation (purge rejected docs 30d, soft-deletes 90d)                 | ✅     |

---

## Phase 3 — Sector-Specific Features ✅ COMPLETE

Per-sector feature buildout for Real Estate and E-Commerce.

### Real Estate Sector (31 features)

| #   | Feature                                                                           | Status |
| --- | --------------------------------------------------------------------------------- | ------ |
| 1   | Property listings CRUD (8 types, 4 listings, 6 statuses)                          | ✅     |
| 2   | Property details (bedrooms, bathrooms, garage, floor/lot area, year, floors)      | ✅     |
| 3   | Location data (address, barangay, city, province, zip, lat/lng)                   | ✅     |
| 4   | Property images gallery (JSON)                                                    | ✅     |
| 5   | Floor plans (JSON)                                                                | ✅     |
| 6   | Property documents (JSON)                                                         | ✅     |
| 7   | Video URL & virtual tour                                                          | ✅     |
| 8   | Neighborhood info / nearby places (JSON)                                          | ✅     |
| 9   | Featured listings flag + scope                                                    | ✅     |
| 10  | Property publish/unpublish with `published_at`                                    | ✅     |
| 11  | Views counter (`views_count` + `recordView()`)                                    | ✅     |
| 12  | Developments / Projects (building, subdivision, township)                         | ✅     |
| 13  | Development details (developer, type, units, floors, price range, amenities)      | ✅     |
| 14  | Properties linked to developments (unit number, unit floor)                       | ✅     |
| 15  | Property inquiries / leads CRM (New → Contacted → Viewing → Negotiating → Closed) | ✅     |
| 16  | Inquiry management (agent notes, contacted_at, viewing_date, source)              | ✅     |
| 17  | Open house events (date/time, max attendees, virtual option)                      | ✅     |
| 18  | Open house RSVPs (confirm, attended, no-show, cancel)                             | ✅     |
| 19  | Agent profile page (bio, photo, certs, PRC license, specializations, social)      | ✅     |
| 20  | Client testimonials (store + property-specific, rating, featured/published)       | ✅     |
| 21  | Testimonials relation manager on PropertyResource                                 | ✅     |
| 22  | Saved property searches (criteria JSON, notify frequency, active flag)            | ✅     |
| 23  | Mortgage calculator settings page                                                 | ✅     |
| 24  | Property analytics (daily: views, unique views, inquiries, clicks)                | ✅     |
| 25  | Dashboard — stats overview (6 stats)                                              | ✅     |
| 26  | Dashboard — listings by status chart                                              | ✅     |
| 27  | Dashboard — rating distribution chart                                             | ✅     |
| 28  | Dashboard — views over time (30-day line chart)                                   | ✅     |
| 29  | Dashboard — recent inquiries table                                                | ✅     |
| 30  | Dashboard — top performing listings                                               | ✅     |
| 31  | Dashboard — latest reviews table                                                  | ✅     |

### E-Commerce Sector (15 features)

| #   | Feature                                                                                | Status |
| --- | -------------------------------------------------------------------------------------- | ------ |
| 1   | Lunar product management (built-in)                                                    | ✅     |
| 2   | Lunar orders with marketplace columns (store_id, commission, earnings)                 | ✅     |
| 3   | Commission calculation (configurable per store)                                        | ✅     |
| 4   | Order placement from Lunar cart (validates cart, store, single-store constraint)       | ✅     |
| 5   | Order lifecycle enum (Pending → Confirmed → Preparing → Ready → Delivered → Cancelled) | ✅     |
| 6   | Payout model (amount, period, status, reference)                                       | ✅     |
| 7   | Polymorphic reviews (Store + Lunar Product in same table)                              | ✅     |
| 8   | Review management (publish, unpublish, featured, verified purchase)                    | ✅     |
| 9   | Dashboard — review stats (avg rating, counts, pending moderation)                      | ✅     |
| 10  | Dashboard — rating distribution chart                                                  | ✅     |
| 11  | Dashboard — review trend (30-day line chart)                                           | ✅     |
| 12  | Dashboard — latest reviews table                                                       | ✅     |
| 13  | Store management within Lunar panel                                                    | ✅     |
| 14  | Staff management within Lunar panel                                                    | ✅     |
| 15  | Lunar collections, discounts, shipping, taxes, media, search (configured)              | ✅     |

---

## Phase 4 — Backend Hardening & Operations ✅ COMPLETE (100%)

Close all backend gaps so admin & store panels are production-ready before any storefront work.

### 4A — Admin Panel Gaps ✅ COMPLETE

| #   | Feature                                 | Priority | Status | Notes                                                                                  |
| --- | --------------------------------------- | -------- | ------ | -------------------------------------------------------------------------------------- |
| 1   | **UserResource**                        | Critical | ✅     | List, view, edit, disable/enable, login history relation manager                       |
| 2   | **OrderResource (Admin)**               | Critical | ✅     | Admin-wide order list, store filter, status, commission breakdown                      |
| 3   | **PayoutResource (Admin)**              | Critical | ✅     | Create/manage payouts, mark processed/paid                                             |
| 4   | **Revenue/Commission Dashboard Widget** | Critical | ✅     | RevenueOverviewWidget + LatestOrdersWidget on admin dashboard                          |
| 5   | **Store RelationManagers**              | High     | ✅     | OrdersRelationManager, PayoutsRelationManager, StaffRelationManager on StoreResource   |
| 6   | **Store Reject Action**                 | High     | ✅     | `StoreService::reject()` + rejection email + reject action on StoreResource            |
| 7   | **Property/Review Moderation**          | High     | ✅     | ReviewResource (Lunar panel), ReviewPolicy, PropertyPolicy in place                    |
| 8   | **Missing Policies**                    | High     | ✅     | ReviewPolicy, PropertyPolicy, PayoutPolicy, AnnouncementPolicy, UserPolicy all created |

### 4B — E-Commerce / Lunar Panel Gaps ✅ COMPLETE

| #   | Feature                                   | Priority | Status | Notes                                                                              |
| --- | ----------------------------------------- | -------- | ------ | ---------------------------------------------------------------------------------- |
| 9   | **Store Profile Page (self-service)**     | Critical | ✅     | Full profile: logo, tagline, phone, website, social links, business hours, address |
| 10  | **Scope Lunar Orders per store**          | Critical | ✅     | `ScopedOrderResource` overrides `getEloquentQuery()` with `store_id` filter        |
| 11  | **Scope Lunar Products per store**        | Critical | ✅     | `ScopedProductResource` filters to store's own products                            |
| 12  | **Earnings/Payout view for store owners** | Critical | ✅     | `StoreEarnings` page showing commission history and payout status                  |
| 13  | **Financial Dashboard Widgets**           | High     | ✅     | StoreOrdersOverview, StoreRevenueOverview, StoreStatsOverview widgets              |
| 14  | **Order Status Transitions**              | High     | ✅     | `OrderService` with confirm/ship/deliver/cancel methods                            |
| 15  | **Support Ticket Submission**             | Medium   | ✅     | `StoreSupportTickets` page in Lunar panel — table of tickets + header action modal |

### 4C — Real Estate Panel Gaps ✅ COMPLETE

| #   | Feature                                   | Priority | Status | Notes                                                                                       |
| --- | ----------------------------------------- | -------- | ------ | ------------------------------------------------------------------------------------------- |
| 16  | **OpenHouse RSVP RelationManager**        | High     | ✅     | `RsvpsRelationManager` on OpenHouseResource                                                 |
| 17  | **Development PropertiesRelationManager** | High     | ✅     | `PropertiesRelationManager` on DevelopmentResource                                          |
| 18  | **File Upload for Media**                 | High     | ✅     | `FileUpload` for images (multi/reorderable), floor plans, and documents in PropertyResource |

### 4D — Code Cleanup & Quality ✅ COMPLETE

| #   | Feature                           | Priority | Status | Notes                                                                       |
| --- | --------------------------------- | -------- | ------ | --------------------------------------------------------------------------- |
| 19  | **Remove empty controller stubs** | Low      | ✅     | Only active controllers remain (StoreController, OrderController, etc.)     |
| 20  | **Missing model scopes**          | Medium   | ✅     | `scopeForStore()`, `scopePending()` on Order model                          |
| 21  | **Missing model relationships**   | Medium   | ✅     | `User::orders()`, `User::loginHistory()`, `User::supportTickets()` in place |
| 22  | **Missing factories**             | Low      | ✅     | OrderFactory, PayoutFactory, LoginHistoryFactory all created                |
| 23  | **Comprehensive test coverage**   | Medium   | ✅     | 255 tests, 546 assertions — all passing                                     |

### 4E — Nice-to-Have Enhancements

| #   | Feature                                 | Priority | Status | Notes                                                     |
| --- | --------------------------------------- | -------- | ------ | --------------------------------------------------------- |
| 24  | Activity Log resource (admin)           | Low      | ✅     | `ActivityLogResource` in admin panel                      |
| 25  | Login History resource (admin)          | Low      | ✅     | `LoginHistoryResource` + relation manager on UserResource |
| 26  | Bulk approve stores action              | Low      | ❌     |                                                           |
| 27  | Announcement auto-expire job            | Low      | ❌     |                                                           |
| 28  | FaqResource & SectorResource View pages | Low      | ✅     | Both have dedicated View pages                            |
| 29  | Staff role/permission granularity       | Medium   | ❌     |                                                           |
| 30  | Property clone/duplicate action         | Low      | ❌     |                                                           |
| 31  | Bulk property status change             | Low      | ❌     |                                                           |
| 32  | Lead source analytics widget            | Low      | ✅     | `LeadSourceChart` widget on Realty dashboard              |
| 33  | Inquiry auto-responder email            | Medium   | ❌     |                                                           |
| 34  | Agent reply to testimonials             | Low      | ❌     |                                                           |

### 4F — Bonus (Added beyond original scope)

| #   | Feature                                      | Status | Notes                                                                                                                 |
| --- | -------------------------------------------- | ------ | --------------------------------------------------------------------------------------------------------------------- |
| 35  | **Store Setup Wizard**                       | ✅     | 3-step onboarding (branding → contact → hours/profile) with sector variants                                           |
| 36  | **Structured operating hours (JSONB)**       | ✅     | Day-by-day toggle + time picker grid, pre-filled defaults                                                             |
| 37  | **`EnsureStoreSetupComplete` middleware**    | ✅     | Redirects approved stores to wizard until setup is complete                                                           |
| 38  | **Dynamic brand name + logo in Lunar panel** | ✅     | Sidebar shows store logo & name instead of Lunar defaults                                                             |
| 39  | **TaxZoneResource vendor bug fix**           | ✅     | Proper override pattern (no vendor edits) — null-safe `->first()?->id`                                                |
| 40  | **CORS fix for cross-subdomain storage**     | ✅     | nginx `^~ /storage/` block with `Access-Control-Allow-Origin: *`                                                      |
| 41  | **Table Rate Shipping add-on**               | ✅     | `lunarphp/table-rate-shipping` installed, 13 migrations run, `ShippingPlugin` registered in Lunar panel config        |
| 42  | **PayPal payment add-on**                    | ✅     | `lunarphp/paypal` installed; sandbox/live env separation; `PAYPAL_ENV`, `PAYPAL_CLIENT_ID`, `PAYPAL_SECRET` in `.env` |

---

## Phase 5 — Customer Storefront (Vue 3 + Inertia)

Customer-facing frontend. Depends on Phase 4 being complete.

### 5A — E-Commerce Storefront

| #   | Feature                                 | Description                                                 |
| --- | --------------------------------------- | ----------------------------------------------------------- |
| 1   | Product browsing & search API           | `GET /api/stores/{store}/products` with filters, pagination |
| 2   | Store listing page                      | Browse all approved stores by sector                        |
| 3   | Store detail page                       | Store info, products grid, reviews                          |
| 4   | Product detail page                     | Images, variants, pricing, reviews, add to cart             |
| 5   | Cart management (API + UI)              | Add, update quantity, remove items, cart sidebar/page       |
| 6   | Single-store cart constraint            | Clear cart or warn if switching stores                      |
| 7   | Checkout flow                           | Address, delivery method, order summary                     |
| 8   | Payment integration (Stripe / PayMongo) | Payment processing, webhooks, receipt                       |
| 9   | Order confirmation page                 | Summary, estimated delivery, tracking number                |
| 10  | Customer order history                  | List orders, view order details, track status               |
| 11  | Customer review submission              | Rate & review products + stores after delivery              |
| 12  | Customer account/profile pages          | Edit name, email, phone, password, addresses                |
| 13  | Deals & offers page                     | Active promotions from `/deals` route                       |
| 14  | Market insights page                    | Content from `/insights` route                              |

### 5B — Real Estate Storefront

| #   | Feature                        | Description                                               |
| --- | ------------------------------ | --------------------------------------------------------- |
| 15  | Property search/browse page    | Filter by type, price, location, bedrooms, listing type   |
| 16  | Property detail page           | Gallery, specs, floor plans, documents, neighborhood, map |
| 17  | Mortgage calculator frontend   | Interactive calculator using store's default settings     |
| 18  | Property inquiry form          | Visitor submits inquiry from listing page                 |
| 19  | Open house listing + RSVP form | Public open house calendar with RSVP submission           |
| 20  | Agent profile public page      | Agent bio, certifications, listing portfolio              |
| 21  | Development/project pages      | Browse developments with linked available units           |
| 22  | Property comparison tool       | Side-by-side property comparison                          |
| 23  | Saved search management        | Save/manage property search criteria (authenticated)      |
| 24  | Saved search notifications job | Scheduled job: notify users when matching listings appear |
| 25  | Map/geolocation view           | Interactive map with property pins (lat/lng data exists)  |
| 26  | Property analytics tracker     | Middleware/service to record actual views and clicks      |

### 5C — Shared Storefront

| #   | Feature                     | Description                                            |
| --- | --------------------------- | ------------------------------------------------------ |
| 27  | Email verification flow     | Enforce email verification for customers               |
| 28  | Password reset flow         | Forgot password controller + routes                    |
| 29  | Customer dashboard          | Unified account area (orders, saved searches, reviews) |
| 30  | FAQ public page             | Display active FAQs with search                        |
| 31  | Notification preferences    | Push/email settings per user                           |
| 32  | Multi-city support (future) | City-based store/property filtering                    |

---

## Phase 6 — Operations & Scale (Future)

| #   | Feature                                       | Description                                                 |
| --- | --------------------------------------------- | ----------------------------------------------------------- |
| 1   | Stripe Connect automatic payouts              | Direct bank transfers to store owners                       |
| 2   | Delivery/rider assignment system              | Rider management + order delivery tracking                  |
| 3   | Promo codes & marketplace-level discounts     | Platform-wide promotions beyond Lunar's per-store discounts |
| 4   | Analytics dashboards (store owners)           | Detailed sales, traffic, conversion analytics               |
| 5   | Multi-language support                        | i18n for Filipino, Cebuano, English                         |
| 6   | Mobile app (API-first)                        | React Native or Flutter using existing API                  |
| 7   | Webhook system for integrations               | Third-party integration support                             |
| 8   | Advanced search (Laravel Scout + Meilisearch) | Full-text search across products and properties             |

---

## Test Suite Status

| Metric          | Count          |
| --------------- | -------------- |
| **Total Tests** | 255            |
| **Assertions**  | 546            |
| **Status**      | ✅ ALL PASSING |

---

## Architecture Summary

```
┌─────────────────────────────────────────────────────────┐
│                    NegosyoHub Platform                   │
├──────────┬──────────────┬──────────────┬────────────────┤
│  Admin   │  Lunar/Store │   Realty     │  Storefront    │
│  Panel   │  Panel       │   Panel      │  (Phase 5)     │
│  ✅ P2   │  ✅ P3       │   ✅ P3      │  🔜 P5        │
├──────────┴──────────────┴──────────────┴────────────────┤
│  Phase 4: Backend Hardening & Operations ✅ 100% DONE       │
│  All items complete — ready for Phase 5                     │
├─────────────────────────────────────────────────────────┤
│  Phase 1: Core Platform ✅                               │
│  (Users, stores, auth, multi-tenant, subdomain login)    │
└─────────────────────────────────────────────────────────┘
```
