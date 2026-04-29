# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.2] - 2026-03-30

### Changed
- **E-commerce Checkout**: The storefront `v1` cart now supports multi-store marketplace carts for the first public release instead of enforcing a single-store checkout.
- **Marketplace Checkout UX**: Cart and checkout now group items by store and clearly explain that one checkout will split into separate store orders behind the scenes.
- **Moving Service Workflow**: Lipat Bahay bookings now use provider-controlled base rates, consistent `Pending Confirmation -> Confirmed -> In Progress -> Completed` lifecycle language, and clearer customer/provider progress states.
- **SEO Host Split**: The storefront now generates its own deploy-time sitemap and `robots.txt`, while the Laravel marketplace host now serves a separate marketplace sitemap and host-specific robots directives.

### Fixed
- **Multi-Store Ordering**: Cash on Delivery checkout now creates separate store orders from one marketplace cart instead of forcing customers to clear their cart between stores.
- **PayPal Marketplace Capture**: PayPal checkout can now complete a grouped multi-store checkout and fan it out into per-store orders after capture.
- **Cart API Shape**: Cart responses now include grouped store sections so the frontend can render marketplace-level carts consistently.
- **Review Trust**: Product and store reviews now expose purchase-eligibility context so the storefront can guide customers toward verified review submission.
- **Ecommerce Store Bootstrap**: Production permission seeding now creates the Lunar catalog, sales, and store settings permissions required for ecommerce store owners to see product-management modules without running the full demo seeders.
- **Frontend 404 Handling**: Vercel deployments now rewrite unknown SPA paths to the storefront app instead of showing the platform's default Vercel 404 page on refresh.
- **Error Page Experience**: The storefront now has a richer custom 404 page, and Laravel production errors now consistently render the custom NegosyoHub error screen instead of the default framework page.
- **Moving Booking Price Integrity**: Moving bookings no longer trust client-submitted base pricing and now calculate totals from backend-controlled provider rates plus validated add-ons.
- **Moving Booking Lifecycle**: Invalid status jumps are now rejected by a transition matrix, and the provider panel now offers explicit `Confirm`, `Start Move`, `Complete`, and `Cancel` actions instead of relying on generic edits.
- **Moving Booking Tracking UX**: The customer booking detail page now shows a visible progress stepper so the current move stage is easy to understand.

## [1.1.1] - 2026-03-29

### Added
- **Operations**: Added `app:create-super-admin` to seed the required platform roles and permissions and provision a production-ready admin user without running the full demo seeders.
- **Admin Recovery**: Added a `Resend Approval Email` action for approved stores so admins can resend seller onboarding emails when delivery fails.

### Changed
- **Seller Onboarding**: Seller email verification now happens after store approval instead of immediately at registration time.
- **Seller Login**: Unverified approved sellers are now blocked from the store portal until they verify their email, and a fresh verification notification is resent automatically.

### Fixed
- **Seller Registration Wizard**: Fixed multi-step validation so errors from a previous step no longer block progress after the user corrects them.
- **Seller Registration Resilience**: Registration no longer depends on immediate email verification dispatch to complete the seller application flow.
- **Seller Verification Redirect**: Fixed the signed verification flow so store owners can verify successfully without already being logged in.
- **Seller Approval Flow**: Store approval now sends the seller verification notification at the correct stage of the lifecycle.
- **Seller Approval Recovery**: Approval email resend now reuses the same seller communication flow instead of requiring a status change workaround.

## [1.1.0] - 2026-03-27

### Added
- **Analytics**: Added `@vercel/speed-insights` for deeper frontend performance monitoring.
- **LipatBahay Dashboard**: Completely populated the Movers control panel with real data widgets:
  - `MovingBookingsOverview` displaying total bookings, pending count, and revenue.
  - `BookingsTrendChart` showing a 30-day graphical trend of moving requests.
  - `RecentMovingBookingsTable` highlighting the 5 most recent service requests.

### Changed
- **UX Improvement**: Overhauled the top `AnnouncementBar`. Instead of expanding inline and pushing site content down, reading an announcement now triggers a sleek, centered modal overlay with native scrolling.

### Fixed
- **Analytics Build Issue**: Corrected the Vite compilation error by utilizing the framework-specific `@vercel/analytics/vue` entry point.
- **Frontend Rich Text Escaping**: Fixed an issue where the backend text editor's `<p>` tags were rendering literally inside the `PromotionBanner` and `AdBanner` cards.
- **Dark Mode Contrast**: Fixed glaring contrast and legibility issues across multiple frontend components:
  - `RentalAgreementsPage`: Fixed unreadable text across the Pending Action boxes ("Review and Sign"), the Q&A conversation thread, and the Safe Move-In Journey steps.
  - `PromotionBanner`: Implemented dark-mode specific text colors for titles, dates, and discounted prices so they remain completely legible against dark backgrounds.

## [1.0.0] - 2026-03-27

### Added
#### Core Platform (Phase 1)
- User roles system (Admin, StoreOwner, Staff, Customer).
- Multi-tenant store architecture with `store_id` filtering.
- Subdomain-based store login (`{slug}.domain/portal/{token}/login`).
- Sector selection and dynamic document requirement system.
- Customer and Store Owner registration and approval flows.
- KYC validation, including Philippine ID regex checks and encrypted fields.
- Security enhancements: rate limiting, HTTPS enforcement, HSTS, and file upload validation.
- Activity logging and login history tracking.

#### Admin Panel & Compliance (Phase 2)
- Comprehensive admin dashboard with analytics for revenue, orders, and store status.
- Management sections for stores, users, support tickets, and FAQ/Legal pages.
- Marketing module: campaigns, promotions, coupons, and featured listing management.
- E-commerce administrative resources: shipping, taxes, currencies.

#### Sector-Specific Modules (Phase 3)
- **Real Estate**: Property listings CRUD, lead/inquiry CRM, open houses, agent profiles, and mortgage calculator.
- **E-Commerce**: Lunar products/orders integration, marketplace commission handling, payout models, and reviews.
- **LipatBahay (Moving Services)**: MovingBooking model, add-on management, and mover browsing.

#### Storefronts / Frontend (Phase 5)
- Shared Storefront: Vue 3 SPA with customer dashboard, password reset, FAQ/Legal pages, multi-city geolocation support.
- E-commerce frontend: Product browsing, cart management, checkout with PayPal integration, order history.
- Real estate frontend: Property search, map views, inquiry parsing, RSVP system, property comparison tool.
- Moving services frontend: Mover browsing, add-on selection, and booking flows.

#### Backend Hardening (Phase 4)
- Comprehensive REST APIs and policies for models.
- Stripe/PayPal multi-method payment support.
- Store setup wizard and localized vendor adjustments.
- Table Rate Shipping Add-on integration.

#### DevOps & CI/CD (Phase 7)
- GitHub Actions CI pipeline (Pint + Pest + build).
- Docker environment configurations and scanning tools (Checkov + Trivy).
- Secured token storage (migrated to sessionStorage).

### Fixed
- CORS issues across cross-subdomain storage.
- TaxZoneResource overrides bug.
- CommonMark XSS vulnerability.
