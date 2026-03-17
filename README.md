# NegosyoHub

A multi-sector marketplace SaaS platform where store owners register under industry sectors (e-commerce, real estate, moving services), customers browse stores and place orders, and the platform collects commission on each transaction.

Built with **Laravel 12**, **Lunar PHP**, **Filament v3**, **Vue 3**, and **PostgreSQL**, running on Docker.

[![Tests](https://github.com/doedoe123-boop/negosyohub/actions/workflows/php.yml/badge.svg)](https://github.com/doedoe123-boop/negosyohub/actions/workflows/php.yml)
[![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](LICENSE)

---

## Tech Stack

| Layer             | Technology                        |
| ----------------- | --------------------------------- |
| Backend Framework | Laravel 12 (PHP 8.4)              |
| E-Commerce Engine | Lunar PHP v1                      |
| Admin Panels      | Filament v3                       |
| Realtime          | Livewire v3                       |
| Frontend SPA      | Vue 3 + Pinia + Vue Router 4      |
| Styling           | Tailwind CSS v4                   |
| Database          | PostgreSQL 15                     |
| Auth              | Laravel Sanctum v4                |
| Testing           | Pest v4, Vitest v4, Playwright v1 |
| Code Style        | Laravel Pint (PSR-12)             |
| Payments          | PayMongo, Paypal                  |

---

## Repository Structure

```
negosyohub/
├── src/              # Laravel 12 backend (REST API + Blade/Livewire panels + Filament admin)
├── frontend/         # Vue 3 SPA (customer-facing storefront)
├── docker/           # Nginx & PHP Dockerfiles
├── docs/             # Design documents
├── agent/            # AI agent instructions
├── skills/           # Agent skills
├── Makefile          # Docker commands
└── docker-compose.yml
```

### Backend (`src/`)

| Path                           | Purpose                                                   |
| ------------------------------ | --------------------------------------------------------- |
| `app/Models/`                  | Eloquent models                                           |
| `app/Services/`                | Business logic (Order, Commission, Store, PayMongo, etc.) |
| `app/Http/Controllers/Api/V1/` | Versioned REST API controllers                            |
| `app/Filament/Admin/`          | Platform admin panel resources                            |
| `app/Filament/Resources/`      | Store owner panel resources                               |
| `app/Filament/Realty/`         | Real estate panel resources                               |
| `app/Livewire/`                | Livewire components                                       |
| `routes/api.php`               | API routes (`/api/v1/`)                                   |
| `routes/web.php`               | Web routes                                                |
| `routes/store.php`             | Store subdomain routes                                    |

### Frontend SPA (`frontend/`)

| Path              | Purpose                    |
| ----------------- | -------------------------- |
| `src/api/`        | Axios API modules          |
| `src/stores/`     | Pinia stores (auth, cart)  |
| `src/pages/`      | Route-level Vue components |
| `src/components/` | Shared UI components       |
| `src/router/`     | Vue Router config          |

---

## Requirements

- [Docker](https://docs.docker.com/get-docker/) & Docker Compose v2+
- `make` (pre-installed on Linux/macOS; use WSL2 on Windows)

---

## Getting Started

```bash
git clone git@github.com:doedoe123-boop/negosyohub.git
cd negosyohub
make setup
```

| URL                   | Purpose               |
| --------------------- | --------------------- |
| http://localhost:8080 | Backend / Marketplace |
| http://localhost:5173 | Vue 3 Storefront SPA  |

---

## Docker Commands

```bash
make build       # Build images
make up          # Start containers (dev)
make up ENV=prod # Start containers (production)
make down        # Stop containers
make restart     # Restart containers
make logs        # View logs
make ps          # List containers
```

### Without Make

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build
docker compose -f docker-compose.yml -f docker-compose.dev.yml down
```

---

## Common Commands

```bash
make migrate          # Run migrations
make migrate-fresh    # Drop & re-run migrations
make seed             # Seed database
make test             # Run Pest tests
make dusk             # Run Dusk browser tests
make pint             # Format code with Pint
make shell            # Shell into app container
make tinker           # Laravel Tinker REPL
make artisan CMD="…"  # Run any artisan command
```

### Filament Panel Cache

Run after adding any new Filament plugin, resource, or page:

```bash
make artisan CMD="filament:clear-cached-components"
make artisan CMD="filament:cache-components"
make cache-clear
```

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development setup, coding standards, and pull request guidelines.

## Security

See [SECURITY.md](SECURITY.md) for our security policy and how to report vulnerabilities.

## License

This project is licensed under the Apache License 2.0 — see [LICENSE](LICENSE) for details.

## Resources

- [Laravel](https://laravel.com/docs) · [Lunar PHP](https://lunarphp.com) · [Filament v3](https://filamentphp.com/docs) · [Vue 3](https://vuejs.org) · [Pest](https://pestphp.com) · [Laravel Dusk](https://laravel.com/docs/dusk) · [PostgreSQL](https://www.postgresql.org/docs)
