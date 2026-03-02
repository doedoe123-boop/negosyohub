# NegosyoHub — Multi-Sector Marketplace

A multi-sector marketplace SaaS built with **Laravel 12**, **Lunar PHP**, **Filament v3**, **Vue 3**, and **PostgreSQL**, running on Docker.

---

## Requirements

- [Docker](https://docs.docker.com/get-docker/) & Docker Compose v2+
- `make` (pre-installed on Linux/macOS; use WSL2 on Windows)

---

## Getting Started

```bash
git clone <your-repo-url> kain-hub
cd kain-hub
cp src/.env.example src/.env
make setup
```

Open **http://localhost:8080** in your browser.

---

## Docker Commands

```bash
make build       # Build images
make up          # Start containers
make down        # Stop containers
make restart     # Restart containers
make logs        # View logs
make ps          # List containers
```

### Without Make

```bash
# Start
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build

# Stop
docker compose -f docker-compose.yml -f docker-compose.dev.yml down
```

### Production

```bash
make up ENV=prod
```

---

## URLs

| Service    | URL                                      |
| ---------- | ---------------------------------------- |
| App        | http://localhost:8080                    |
| Admin      | http://localhost:8080/moon/portal/…      |
| Store      | http://localhost:8080/store/dashboard/…  |
| Realty     | http://localhost:8080/realty/dashboard/… |
| Vite (HMR) | http://localhost:5173                    |
| MailHog    | http://localhost:8025                    |
| PostgreSQL | localhost:5433                           |

---

## Common Commands

```bash
make migrate          # Run migrations
make migrate-fresh    # Drop & re-run migrations
make seed             # Seed database
make test             # Run Pest tests (224 tests)
make dusk             # Run Dusk browser tests (37 tests)
make pint             # Format code
make shell            # Shell into app container
make tinker           # Laravel Tinker REPL
make artisan CMD="…"  # Run any artisan command
```

### Filament Panel Cache

Run this after adding any new Filament plugin, resource, or page — otherwise Livewire component discovery breaks and returns 404:

```bash
make artisan CMD="filament:clear-cached-components"
make artisan CMD="filament:cache-components"
```

### Frontend

```bash
make npm-install      # Install node packages
make npm-dev          # Start Vite dev server
make npm-build        # Build for production
```

### Database

```bash
make db-shell         # Open psql shell
```

---

## Containers

| Container        | Service    | Port |
| ---------------- | ---------- | ---- |
| `laravel_app`    | PHP-FPM    | 9000 |
| `laravel_nginx`  | Nginx      | 8080 |
| `marketplace_db` | PostgreSQL | 5433 |
| `frontend_node`  | Vite       | 5173 |
| `mailhog`        | MailHog    | 8025 |
| `selenium`       | Chromium   | 4444 |

---

## Project Structure

```
kain-hub/
├── src/                           # Laravel 12 application
│   ├── app/
│   │   ├── Filament/
│   │   │   ├── Admin/             # Admin panel (Resources, Pages, Widgets)
│   │   │   ├── Realty/            # Realty panel (Resources, Pages, Widgets)
│   │   │   ├── Pages/             # Shared store panel pages
│   │   │   ├── Resources/         # Shared store panel resources
│   │   │   └── Widgets/           # Shared store panel widgets
│   │   ├── Models/                # Eloquent models (20+)
│   │   ├── Services/              # Business logic (Order, Commission, Store)
│   │   ├── Http/                  # Controllers, Form Requests
│   │   ├── Policies/              # Authorization policies
│   │   ├── Jobs/                  # Queued jobs
│   │   └── Observers/             # Model observers
│   ├── database/                  # Migrations, factories, seeders
│   ├── routes/                    # web.php, console.php
│   └── tests/
│       ├── Feature/               # Pest feature tests (13 files)
│       ├── Unit/                  # Pest unit tests
│       └── Browser/               # Dusk E2E tests (3 panels)
├── agent/                         # AI agent instructions
├── skills/                        # AI skill definitions
├── docker/
│   ├── php/Dockerfile
│   └── nginx/default.conf
├── docker-compose.yml             # Base config
├── docker-compose.dev.yml         # Dev overrides (node, mailhog, selenium)
└── Makefile
```

---

## User Roles

| Role         | Panel  | Access                                        |
| ------------ | ------ | --------------------------------------------- |
| Admin        | Admin  | Full platform management, all orders, payouts |
| Store Owner  | Store  | Own store, products, orders, staff, earnings  |
| Staff        | Store  | Limited store access via Spatie permissions   |
| Realty Agent | Realty | Properties, inquiries, open houses            |
| Customer     | —      | Browse stores, place orders                   |

---

## Testing

```bash
# Pest — unit & feature tests
make test                               # 224 tests, 503 assertions
make test-filter FILTER="OrderPlacement" # Specific test

# Dusk — browser E2E tests
make dusk                               # 37 tests across 3 panels
make dusk-filter FILTER="admin can log in"
```

---

## Resources

- [Laravel](https://laravel.com/docs) · [Lunar PHP](https://lunarphp.com) · [Filament v3](https://filamentphp.com/docs) · [Vue 3](https://vuejs.org) · [Pest](https://pestphp.com) · [Laravel Dusk](https://laravel.com/docs/dusk) · [PostgreSQL](https://www.postgresql.org/docs)
