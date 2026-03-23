# NegosyoHub — Development Phasing Document

> Multi-Sector Marketplace SaaS — Laravel 12 · Lunar PHP 1.3 · Filament v3 · Vue 3 · PostgreSQL

Last updated: **March 23, 2026**

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

| #   | Feature                                                                                | Status |
| --- | -------------------------------------------------------------------------------------- | ------ |
| 1   | Admin dashboard — platform stats widget (stores, customers, orders, revenue)           | ✅     |
| 2   | Admin dashboard — revenue overview (store earnings, payouts, active orders)            | ✅     |
| 3   | Admin dashboard — sector distribution chart (doughnut)                                 | ✅     |
| 4   | Admin dashboard — store status chart (doughnut)                                        | ✅     |
| 5   | Admin dashboard — sector performance chart (bar: orders per sector)                    | ✅     |
| 6   | Admin dashboard — order trend chart (30-day line chart)                                | ✅     |
| 7   | Admin dashboard — recent store registrations table                                     | ✅     |
| 8   | Admin dashboard — recent customers table                                               | ✅     |
| 9   | Admin dashboard — marketing insights widget (campaigns, promotions, coupons, featured) | ✅     |
| 10  | Admin dashboard — latest orders table                                                  | ✅     |
| 11  | Admin dashboard — latest tickets table                                                 | ✅     |
| 12  | Store management resource (list, view, edit, approve, suspend, reinstate, KYC review)  | ✅     |
| 13  | KYC document review with signed URL download/preview                                   | ✅     |
| 14  | Support ticket system (CRUD, categories, priorities, assignment, resolution)           | ✅     |
| 15  | Announcement system (type, audience, scheduling, expiry)                               | ✅     |
| 16  | FAQ management                                                                         | ✅     |
| 17  | Legal pages (Terms, Privacy — rich text, published flag, versioning)                   | ✅     |
| 18  | Sector management (create/edit sectors with required documents)                        | ✅     |
| 19  | Data lifecycle automation (purge rejected docs 30d, soft-deletes 90d)                  | ✅     |
| 20  | E-commerce admin resources (channels, currencies, customer groups, languages)          | ✅     |
| 21  | Tax management admin resources (tax classes, tax rates, tax zones)                     | ✅     |
| 22  | Shipping management (table-rate shipping under admin panel)                            | ✅     |
| 23  | Payout generation system (PayoutService, PayoutLine, GeneratePayoutsCommand)           | ✅     |
| 24  | Marketing module — Campaign management with relation managers                          | ✅     |
| 25  | Marketing module — Advertisement management (placement, creative, status)              | ✅     |
| 26  | Marketing module — Promotion management (percentage/fixed discount)                    | ✅     |
| 27  | Marketing module — Coupon management (scope: global/sector/store)                      | ✅     |
| 28  | Marketing module — Featured listing management (polymorphic targets)                   | ✅     |

---

## Phase 3 — Sector-Specific Features ✅ COMPLETE

Per-sector feature buildout for Real Estate, E-Commerce, and Moving Services.

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

### LipatBahay / Moving Services Sector (10 features)

| #   | Feature                                                                | Status |
| --- | ---------------------------------------------------------------------- | ------ |
| 1   | MovingBooking model (pickup/delivery addresses, schedule, pricing)     | ✅     |
| 2   | MovingAddOn model (optional add-on services with pricing)              | ✅     |
| 3   | MovingReview model (customer reviews for movers)                       | ✅     |
| 4   | MovingBookingStatus enum (lifecycle states)                            | ✅     |
| 5   | MovingBookingService (business logic)                                  | ✅     |
| 6   | LipatBahay Filament panel (MovingBookingResource, MovingAddOnResource) | ✅     |
| 7   | MovingReviewResource in LipatBahay panel                               | ✅     |
| 8   | SendMovingReminderJob (automated reminders)                            | ✅     |
| 9   | Mover browsing frontend (search by city/province, mover profiles)      | ✅     |
| 10  | Booking flow frontend (add-ons, schedule, address, payment)            | ✅     |

---

## Phase 4 — Backend Hardening & Operations ✅ COMPLETE

Close all backend gaps so admin & store panels are production-ready before any storefront work.

### 4A — Admin Panel Gaps ✅ COMPLETE

| #   | Feature                                 | Priority | Status | Notes                                                                                  |
| --- | --------------------------------------- | -------- | ------ | -------------------------------------------------------------------------------------- |
| 1   | **UserResource**                        | Critical | ✅     | List, view, edit, disable/enable, login history relation manager                       |
| 2   | **OrderResource (Admin)**               | Critical | ✅     | Admin-wide order list, store filter, status, commission breakdown                      |
| 3   | **PayoutResource (Admin)**              | Critical | ✅     | Create/manage payouts, mark processed/paid, lines relation manager                     |
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
| 23  | **Comprehensive test coverage**   | Medium   | ✅     | 446 tests, 997 assertions — all passing                                     |

### 4E — Nice-to-Have Enhancements

| #   | Feature                                 | Priority | Status | Notes                                                                                                                                                        |
| --- | --------------------------------------- | -------- | ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 24  | Activity Log resource (admin)           | Low      | ✅     | `ActivityLogResource` in admin panel                                                                                                                         |
| 25  | Login History resource (admin)          | Low      | ✅     | `LoginHistoryResource` + relation manager on UserResource                                                                                                    |
| 26  | Bulk approve stores action              | Low      | ✅     | Inline `BulkAction` in `StoreResource` — loops Pending stores, approves, sends `StoreApproved` mail, shows count notification                                |
| 27  | Announcement auto-expire job            | Low      | ✅     | `ExpireAnnouncementsJob` scheduled                                                                                                                           |
| 28  | FaqResource & SectorResource View pages | Low      | ✅     | Both have dedicated View pages                                                                                                                               |
| 29  | Staff role/permission granularity       | Medium   | ✅     | Role `CheckboxList` with dynamic descriptions per role; collapsible Direct Permissions section with `saveRelationshipsUsing` for per-staff permission grants |
| 30  | Property clone/duplicate action         | Low      | ✅     | `ReplicateAction` in `PropertyResource` — excludes `slug/published_at/views_count`, prepends `[Copy]`, sets Draft status                                     |
| 31  | Bulk property status change             | Low      | ✅     | `bulk_publish` and `bulk_archive` BulkActions in `PropertyResource`                                                                                          |
| 32  | Lead source analytics widget            | Low      | ✅     | `LeadSourceChart` widget on Realty dashboard                                                                                                                 |
| 33  | Inquiry auto-responder email            | Medium   | ✅     | `InquiryAutoResponder` mail queued in `PropertyInquiryObserver::created()`                                                                                   |
| 34  | Agent reply to testimonials             | Low      | ✅     | Reply and Edit Reply table actions in `TestimonialResource`; `agent_reply`/`replied_at` form section in Edit page                                            |

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

## Phase 5 — Customer Storefront (Vue 3 SPA) 🔶 ~90% COMPLETE

Customer-facing frontend built with Vue 3 + Pinia + Vue Router + Tailwind CSS.

### 5A — E-Commerce Storefront 🔶 MOSTLY COMPLETE

| #   | Feature                        | Status | Notes                                                                            |
| --- | ------------------------------ | ------ | -------------------------------------------------------------------------------- |
| 1   | Product browsing & search API  | ✅     | `GET /api/v1/products` + `GET /api/v1/stores/:slug/products` with filters        |
| 2   | Store listing page             | ✅     | `Stores.vue` — search + sector filter dropdown + pagination                      |
| 3   | Store detail page              | ✅     | `StoreDetail.vue` — banner, store info, products grid with add-to-cart           |
| 4   | Product detail page            | ✅     | `ProductDetail.vue` — gallery, variant selector, stock labels, pricing, Buy Now  |
| 5   | Cart management (API + UI)     | ✅     | `CartPage.vue` + `CartDrawer.vue` — qty controls, subtotals, delete, empty state |
| 6   | Single-store cart constraint   | ✅     | Backend enforced in order placement pipeline                                     |
| 7   | Checkout flow                  | ✅     | `CheckoutPage.vue` — 3-step wizard: address → shipping → payment                 |
| 8   | Payment integration (PayPal)   | ✅     | PayPal via `paypalApi.createOrder()` / `captureOrder()`, sandbox + live          |
| 9   | Order confirmation page        | ✅     | `CheckoutSuccess.vue` — captures PayPal order, displays order ID + next steps    |
| 10  | Customer order history         | ✅     | `OrdersPage.vue` + `OrderDetail.vue` — list orders, view details, track status   |
| 11  | Customer review submission     | ✅     | Product and store review submission are live in `ProductDetail.vue` and `StoreDetail.vue`, both backed by moderated review APIs |
| 12  | Customer account/profile pages | ✅     | Profile, addresses, payment methods, change password, settings, account deletion |
| 13  | Deals & offers page            | ✅     | `DealsPage.vue` is now routed in the Vue SPA and powered by promotions, featured listings, and announcements APIs |
| 14  | Market insights page           | ✅     | `MarketInsightsPage.vue` is now routed in the Vue SPA and backed by the public `GET /api/v1/market-insights` endpoint |

### 5B — Real Estate Storefront ✅ COMPLETE

| #   | Feature                        | Status | Notes                                                                                                                 |
| --- | ------------------------------ | ------ | --------------------------------------------------------------------------------------------------------------------- |
| 15  | Property search/browse page    | ✅     | `Properties.vue` — filters: type, listing, price range, bedrooms, city, search                                        |
| 16  | Property detail page           | ✅     | `PropertyDetail.vue` — gallery, specs, floor area, pricing, inquiry form                                              |
| 17  | Mortgage calculator frontend   | ✅     | Pag-IBIG calculator integrated in PropertyDetail                                                                      |
| 18  | Property inquiry form          | ✅     | In PropertyDetail — name, email, phone, message → `propertiesApi.submitInquiry()`                                     |
| 19  | Open house listing + RSVP form | ✅     | `OpenHouseController::rsvp()`, open houses section + RSVP Teleport modal in `PropertyDetail.vue`                      |
| 20  | Agent profile public page      | ✅     | `AgentDetail.vue` — profile, verified badge, contact info, property listings                                          |
| 21  | Development/project pages      | ✅     | `DevelopmentController`, `Developments.vue`, `DevelopmentDetail.vue` with units grid + map                            |
| 22  | Property comparison tool       | ✅     | `useCompare.js` composable, floating compare bar in `Properties.vue`, `CompareProperties.vue` side-by-side spec table |
| 23  | Saved search management        | ✅     | `SavedSearchController`, `SavedSearches.vue` account page, save-search modal in `Properties.vue`                      |
| 24  | Saved search notifications job | ✅     | `NotifySavedSearchesJob` — daily/weekly frequency, `SavedSearchResultsMail`, scheduled at 08:00                       |
| 25  | Map/geolocation view           | ✅     | OpenStreetMap iframe in `PropertyDetail.vue` + `DevelopmentDetail.vue` using lat/lng data                             |
| 26  | Property analytics tracker     | ✅     | `PropertyController::track()`, `propertiesApi.track()`, phone/share click events in `PropertyDetail.vue`              |

### 5C — Shared Storefront ✅ COMPLETE

| #   | Feature                  | Status | Notes                                                                                                                                                                                                                       |
| --- | ------------------------ | ------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 27  | Email verification flow  | ✅     | `MustVerifyEmail` on User model; `EmailVerificationController` (resend + web verify route); `EmailVerify.vue` page with resend button; verified state redirect                                                              |
| 28  | Password reset flow      | ✅     | `ForgotPassword.vue` + `ResetPassword.vue` with API integration                                                                                                                                                             |
| 29  | Customer dashboard       | ✅     | `AccountDashboard.vue` — recent orders, quick-link cards to account pages                                                                                                                                                   |
| 30  | FAQ public page          | ✅     | `FaqController` + `GET /api/v1/faqs`; `FaqPage.vue` with accordion; routed at `/faq`                                                                                                                                        |
| 31  | Notification preferences | ✅     | `SettingsPage.vue` — toggles for order updates and promotions                                                                                                                                                               |
| 32  | Multi-city support       | ✅     | `useCityStore` (Pinia) — browser geolocation → Nominatim reverse geocode → city; `CityBanner.vue` on listing pages; Properties/Developments/Movers pre-fill city filter; "Browse all Philippines" fallback on empty results |

### 5D — LipatBahay / Moving Services Storefront ✅ COMPLETE

| #   | Feature                   | Status | Notes                                                                       |
| --- | ------------------------- | ------ | --------------------------------------------------------------------------- |
| 33  | Mover browsing page       | ✅     | `MoversPage.vue` — city/province filters, mover cards, pagination           |
| 34  | Mover detail page         | ✅     | `MoverDetail.vue` — profile, add-ons with pricing, booking form             |
| 35  | Moving booking flow       | ✅     | Pickup/delivery addresses, schedule, add-ons, contact info, payment         |
| 36  | Moving booking management | ✅     | `MovingBookingsPage.vue` + `MovingBookingDetail.vue` — list, status, cancel |
| 37  | Moving review submission  | ✅     | Review form on completed bookings in MovingBookingDetail                    |

---

## Phase 6 — Operations & Scale (In Progress)

| #   | Feature                                       | Status | Notes                                                            |
| --- | --------------------------------------------- | ------ | ---------------------------------------------------------------- |
| 1   | Stripe Connect automatic payouts              | ❌     | Not implemented; payout generation exists, but there is no Stripe Connect integration |
| 2   | Multi-method payments (PayPal + COD)          | ✅     | `PaymentManager` supports PayPal and Cash on Delivery with separate `payment_method` and `payment_status` |
| 3   | Delivery/rider assignment system              | 🔶     | `Shipment` model, `LogisticsManager`, delivery statuses, admin/store shipment management, and customer delivery progress UI exist; no live Lalamove/provider booking yet |
| 4   | Promo codes & marketplace-level discounts     | ✅     | Coupons validate and apply to cart/order totals, checkout summaries, and PayPal amount calculation across global/sector/store scope |
| 5   | Analytics dashboards (store owners)           | ✅     | Financial widgets in Lunar + property analytics/review dashboards in sector panels |
| 6   | Multi-language support                        | 🔶     | Laravel lang files + DB translation overrides + admin translation management + frontend i18n now cover shared shell, auth, cart/checkout/orders, homepage, reviews, FAQ, about, deals, and market insights; listing/detail coverage still needs a broader pass |
| 7   | Mobile app (API-first)                        | 🔶     | Broad customer API surface, JSON resources, locale-aware requests, and structured pagination exist; no dedicated mobile client or fully standardized response envelope |
| 8   | Webhook system for integrations               | 🔶     | Inbound PayMongo webhook plus outbound webhook endpoints/deliveries/jobs are implemented, with admin + store panel endpoint management and delivery logs; manual retry and broader inbound provider support are still missing |
| 9   | Advanced search (Laravel Scout + Meilisearch) | 🔶     | Scout-ready searchable models and fallback search services exist, but Meilisearch is not provisioned/enabled in runtime config |

---

## Phase 7 — CI/CD & DevOps ✅ COMPLETE

| #   | Feature                                               | Status | Notes                                                        |
| --- | ----------------------------------------------------- | ------ | ------------------------------------------------------------ |
| 1   | GitHub Actions CI pipeline (Pint + Pest + build)      | ✅     | `.github/workflows/php.yml` — PostgreSQL 16 + Redis services |
| 2   | Parallel test execution                               | ✅     | `--parallel --recreate-databases` across multiple processes  |
| 3   | Composer + Node module caching                        | ✅     | `actions/cache@v3` for vendor/ and node_modules/             |
| 4   | Docker security scan (Checkov + Trivy)                | ✅     | SARIF uploads to GitHub Security tab                         |
| 5   | Dockerfile healthcheck                                | ✅     | `HEALTHCHECK` instruction in `docker/php/Dockerfile`         |
| 6   | GitHub Issue templates (bug report + feature request) | ✅     | `.github/ISSUE_TEMPLATE/` with structured forms              |
| 7   | Repository documentation (README, CONTRIBUTING, etc)  | ✅     | README, CONTRIBUTING, LICENSE (Apache 2.0), SECURITY, DESIGN |
| 8   | CommonMark XSS vulnerability patched                  | ✅     | `league/commonmark` 2.8.0 → 2.8.1                            |
| 9   | Secure token storage (sessionStorage)                 | ✅     | Migrated from localStorage to sessionStorage for API tokens  |

---

## Test Suite Status

| Metric          | Count           |
| --------------- | --------------- |
| **Total Tests** | 446             |
| **Assertions**  | 997             |
| **Duration**    | ~50s            |
| **Status**      | ✅ ALL PASSING  |

---

## Architecture Summary

```
┌───────────────────────────────────────────────────────────┐
│                     NegosyoHub Platform                    │
├───────────┬──────────────┬────────────┬───────────────────┤
│  Admin    │  Store/Lunar │  Realty    │  LipatBahay       │
│  Panel    │  Panel       │  Panel     │  Panel            │
│  ✅ P2    │  ✅ P3       │  ✅ P3     │  ✅ P3            │
│  25 res   │  5 resources │  6 res     │  3 resources      │
│  11 wdgt  │              │            │                   │
├───────────┴──────────────┴────────────┴───────────────────┤
│  Storefront (Vue 3 SPA) — Phase 5 🔶 ~90%               │
│  E-Commerce 🔶 · Realty ✅ · Moving ✅ · Account ✅     │
├───────────────────────────────────────────────────────────┤
│  Backend Hardening — Phase 4 ✅ 100%                     │
│  20 services · 38 models · 23 enums · 446 tests          │
├───────────────────────────────────────────────────────────┤
│  CI/CD & DevOps — Phase 7 ✅                             │
│  GitHub Actions · Docker scanning · Security patching     │
├───────────────────────────────────────────────────────────┤
│  Core Platform — Phase 1 ✅                               │
│  Users · Stores · Auth · Multi-tenant · Subdomain login   │
└───────────────────────────────────────────────────────────┘
```
