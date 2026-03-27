# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
