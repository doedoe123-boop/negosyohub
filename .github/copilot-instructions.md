# Negosyo Hub — Coding Agent Instructions

Trust these instructions. Only search the codebase if information here is incomplete or appears incorrect.

## What This Repository Is

A multi-sector marketplace (hobby project). Store owners register under industry sectors (e-commerce, real estate). Customers browse stores and place orders. The platform collects commission on each order.

**Two separate apps in one repo:**
- `src/` — Laravel 12 backend (REST API + Blade/Livewire store portal + Filament admin panel)
- `frontend/` — Standalone Vue 3 SPA (customer-facing storefront)

**Runtimes:** PHP 8.4, Composer 2.9, Node.js 24, npm 11  
**Key packages:** Laravel 12, Filament v3, Livewire v3, Lunar PHP v1, Laravel Sanctum v4, Pest v4, Tailwind CSS v4, Vue 3, Pinia, Vue Router 4, Vitest v4, Playwright v1  
**Database:** PostgreSQL (production/CI) — SQLite in-memory for local test runs  
**Payments:** PayMongo (webhooks in `PayMongoController`)

---

## CI Pipeline — `.github/workflows/php.yml`

Triggers on push/PR to `main`. Working directory is `src/`. These steps must all pass:

1. `composer install --prefer-dist --no-progress --no-interaction`
2. `cp .env.example .env.testing` + configure DB/Redis env vars + `php artisan key:generate --env=testing`
3. `php artisan migrate --env=testing --force`
4. `npm ci && npm run build` (builds Laravel Vite assets in `src/`)
5. `./vendor/bin/pint --test` — **fails CI if any PHP file violates code style**
6. `php artisan test --compact` — **fails CI if any backend test fails**

Frontend unit and E2E tests are NOT run in CI.

---

## Build & Validate Commands

All commands must be run from their respective directories.

### Backend (`cd src/`)

```bash
# Install PHP deps (already installed in vendor/)
composer install

# Run backend tests (SQLite in-memory, no DB service needed)
php artisan test --compact

# Check PHP code style (read-only, used in CI)
./vendor/bin/pint --test

# Fix PHP code style (always run after editing PHP files)
./vendor/bin/pint --dirty --format agent

# Build Laravel Vite assets (CSS/JS for Blade views)
npm run build
# NOTE: If this fails with EACCES on node_modules/.vite-temp, fix with:
#   rm -rf node_modules/.vite-temp && mkdir node_modules/.vite-temp
# then retry npm run build
```

### Frontend SPA (`cd frontend/`)

```bash
# Build production assets
npm run build              # takes ~2s, outputs to frontend/dist/

# Run unit tests (Vitest)
npm test
# NOTE: 14 unit tests are pre-existing failures (API path mismatch in test
# assertions). This is a known issue and does NOT block CI.

# Run E2E tests (requires Vite dev server — auto-started by Playwright)
npm run test:e2e
```

### Validation checklist before submitting changes

1. `cd src && ./vendor/bin/pint --dirty --format agent` — must exit 0
2. `cd src && php artisan test --compact` — all 379 tests must pass
3. If Blade/Livewire views changed: `cd src && npm run build`
4. If frontend SPA changed: `cd frontend && npm run build`

---

## Architecture & Key File Locations

### Backend (`src/`)

| Path | Purpose |
|---|---|
| `app/Models/` | Eloquent models. `Order` extends `Lunar\Models\Order`. `User` implements `FilamentUser`. |
| `app/Services/` | Business logic: `OrderService`, `CommissionService`, `AuthService`, `StoreService`, `PayMongoService`, `ProductService`, `PropertyService` |
| `app/Http/Controllers/Api/V1/` | REST API controllers (12 controllers) |
| `app/Http/Requests/` | Form Request validation classes — always create these, never validate inline |
| `app/Filament/` | Filament admin panel resources, pages, widgets |
| `app/Livewire/` | Livewire components for store registration and store-owner portal |
| `app/Policies/` | Authorization policies |
| `app/Observers/` | Eloquent observers |
| `routes/api.php` | API routes, all prefixed `/api/v1/` |
| `routes/web.php` | Blade/Livewire routes (public + store registration) |
| `routes/store.php` | Store subdomain routes (`{slug}.{APP_DOMAIN}`) |
| `routes/admin.php` | Admin document routes (security-obscured path) |
| `routes/console.php` | Console command scheduling |
| `bootstrap/app.php` | Middleware, routing, exception handling (Laravel 12 — no `Kernel.php`) |
| `bootstrap/providers.php` | Service providers |
| `database/migrations/` | 50+ migrations (PostgreSQL, JSONB columns used throughout) |
| `database/seeders/` | Seeders: `DatabaseSeeder`, `LunarSeeder`, `SectorSeeder`, `StoreSeeder`, `ProductSeeder`, etc. |
| `config/app.php` | `app.domain`, `app.admin_path_token`, `app.store_path_token` |
| `phpunit.xml` | Test config — uses SQLite in-memory, `RefreshDatabase` trait in Feature tests |
| `pint.json` | PSR-12 + Laravel preset with `ordered_imports`, `no_unused_imports` |

### Frontend SPA (`frontend/`)

| Path | Purpose |
|---|---|
| `src/api/` | Axios-based API modules (one file per resource, all use `/api/v1/` prefix) |
| `src/stores/` | Pinia stores: `auth.js`, `cart.js` |
| `src/pages/` | Route-level Vue components (Home, Stores, account/, auth/, checkout/, realty/, etc.) |
| `src/components/` | Shared UI components |
| `src/layouts/` | `DefaultLayout.vue`, `AuthLayout.vue` |
| `src/router/` | Vue Router 4 config |
| `vite.config.js` | Proxies `/api` and `/sanctum` to backend (`BACKEND_URL` env or `http://localhost:8080`) |
| `vitest.config.js` | Unit tests in `tests/unit/**/*.test.js`, jsdom environment |
| `playwright.config.js` | E2E in `tests/e2e/`, Chromium + Mobile Chrome, base URL `http://localhost:5173` |

---

## Key Conventions

- **PHP style:** PSR-12, curly braces always, constructor property promotion, explicit return types, PHPDoc with array shapes, no inline comments unless logic is complex. Run Pint after every PHP edit.
- **Enums:** All enums are in `app/` root — `UserRole`, `OrderStatus`, `StoreStatus`, `IndustrySector`, `PaymentStatus`, `ListingType`, `PropertyStatus`, `PropertyType`, `TicketStatus`, etc. Enum keys are TitleCase.
- **Never use `env()` outside config files.** Always use `config('key')`.
- **Never use `DB::`.** Use `Model::query()` and Eloquent relationships.
- **Always eager-load** to prevent N+1 queries.
- **API routes** are always versioned under `/api/v1/`.
- **Admin panel URL:** `/moon/portal/itsec_tk_{ADMIN_PATH_TOKEN}` (security-obscured).
- **Store portal URL:** `/store/dashboard/tk_{STORE_PATH_TOKEN}` (subdomain: `{slug}.{APP_DOMAIN}`).
- **Order commissions:** stored as integers (cents) in `commission_amount`, `store_earning`, `platform_earning` on the Lunar orders table. Use `CommissionService`.
- **Tests:** Use Pest v4. Create with `php artisan make:test --pest {Name}`. Feature tests use `RefreshDatabase`. Use factories; check for existing factory states before manual setup.
- **New models:** Always create factory and seeder alongside.
- **`make:` commands:** Always pass `--no-interaction` to Artisan make commands.
- **Middleware registration:** In `bootstrap/app.php` via `withMiddleware()` — not a `Kernel.php`.
- **Queued jobs:** Implement `ShouldQueue` for time-consuming operations.
