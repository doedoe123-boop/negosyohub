# Makefile — NegosyoHub Marketplace
# Usage: make <target>
# Run `make help` to see all available commands.

# --------------------------------------------------------------------------
# Configuration
# --------------------------------------------------------------------------

# Use both compose files: base + dev override. For prod-only: make up ENV=prod
ENV ?= dev
ifeq ($(ENV),prod)
  DC = docker compose -f docker-compose.yml
else
  DC = docker compose -f docker-compose.yml -f docker-compose.dev.yml
endif
APP         = $(DC) exec app
NODE        = $(DC) exec node
DB          = $(DC) exec db

# --------------------------------------------------------------------------
# Docker / Lifecycle
# --------------------------------------------------------------------------

.PHONY: build up down restart ps logs

## Build all Docker images
build:
	$(DC) build

## Start all containers in detached mode
up:
	$(DC) up -d

## Stop and remove all containers
down:
	$(DC) down

## Restart all containers
restart: down up

## Show running containers
ps:
	$(DC) ps

## Tail logs for all services (Ctrl-C to stop)
logs:
	$(DC) logs -f

# --------------------------------------------------------------------------
# First-time Setup
# --------------------------------------------------------------------------

.PHONY: setup install

## Full first-time project setup (build, start, install deps, migrate, seed)
setup: build up env install key migrate seed npm-install npm-build fix-src-perms laravel-npm-install laravel-npm-build restart-nginx
	@echo ""
	@echo "\033[0;36m  ███╗   ██╗███████╗ ██████╗  ██████╗ ███████╗██╗   ██╗ ██████╗ ██╗  ██╗██╗   ██╗██████╗ \033[0m"
	@echo "\033[0;36m  ████╗  ██║██╔════╝██╔════╝ ██╔═══██╗██╔════╝╚██╗ ██╔╝██╔═══██╗██║  ██║██║   ██║██╔══██╗\033[0m"
	@echo "\033[0;36m  ██╔██╗ ██║█████╗  ██║  ███╗██║   ██║███████╗ ╚████╔╝ ██║   ██║███████║██║   ██║██████╔╝\033[0m"
	@echo "\033[0;36m  ██║╚██╗██║██╔══╝  ██║   ██║██║   ██║╚════██║  ╚██╔╝  ██║   ██║██╔══██║██║   ██║██╔══██╗\033[0m"
	@echo "\033[0;36m  ██║ ╚████║███████╗╚██████╔╝╚██████╔╝███████║   ██║   ╚██████╔╝██║  ██║╚██████╔╝██████╔╝\033[0m"
	@echo "\033[0;36m  ╚═╝  ╚═══╝╚══════╝ ╚═════╝  ╚═════╝ ╚══════╝   ╚═╝    ╚═════╝ ╚═╝  ╚═╝ ╚═════╝ ╚═════╝ \033[0m"
	@echo ""
	@echo "\033[1;32m  ✨ NEGOSYOHUB SETUP COMPLETE! ✨\033[0m"
	@echo "  \033[0;90m--------------------------------------------------\033[0m"
	@echo "  🌍 \033[1;37mMarketplace:\033[0m    \033[4;34mhttp://localhost:8080\033[0m"
	@echo "  👤 \033[1;37mAdmin:\033[0m       \033[4;34mhttp://localhost:8080/admin\033[0m"
	@echo "  📧 \033[1;37mMailHog:\033[0m     \033[4;34mhttp://localhost:8025\033[0m"
	@echo "  ⚡ \033[1;37mStore front:\033[0m    \033[4;34mhttp://localhost:5173\033[0m"
	@echo ""

## Install PHP dependencies inside the app container
install:
	$(APP) composer install

## Copy .env.example → .env for root, src/, and frontend/ (skips if already exists)
env:
	cp -n .env.example .env || true
	cp -n frontend/.env.example frontend/.env || true
	$(APP) cp -n .env.example .env || true

## Generate Laravel application key
key:
	$(APP) php artisan key:generate

# --------------------------------------------------------------------------
# Database
# --------------------------------------------------------------------------

.PHONY: migrate migrate-fresh seed db-shell

## Run database migrations
migrate:
	$(APP) php artisan migrate --no-interaction

## Drop all tables and re-run migrations
migrate-fresh:
	$(APP) php artisan migrate:fresh --no-interaction

## Seed the database
seed:
	$(APP) php artisan db:seed --no-interaction

## Open an interactive psql shell
db-shell:
	$(DB) psql -U laravel marketplace

# --------------------------------------------------------------------------
# Frontend / Vite
# --------------------------------------------------------------------------

.PHONY: npm-install npm-dev npm-build

## Install Node dependencies
npm-install:
	$(NODE) npm install

## Start Vite dev server (HMR)
npm-dev:
	$(NODE) npm run dev -- --host

## Build frontend assets for production
npm-build:
	$(NODE) npm run build

## Install Node deps for the Laravel app (Filament/Blade assets)
laravel-npm-install:
	$(NODE) npm --prefix /src install

## Build Laravel Vite assets (generates public/build/manifest.json)
laravel-npm-build:
	$(NODE) npm --prefix /src run build

## Fix src/ ownership so the node container (uid 1000) can write build output
fix-src-perms:
	$(DC) exec -u root app chown -R 1000:1000 /var/www/public/build 2>/dev/null || true
	$(DC) exec -u root app chown -R 1000:1000 /var/www/node_modules 2>/dev/null || true

## Restart nginx to pick up fresh upstream DNS (needed after app container rebuild)
restart-nginx:
	$(DC) restart nginx

# --------------------------------------------------------------------------
# Artisan Helpers
# --------------------------------------------------------------------------

.PHONY: artisan tinker queue cache-clear optimize

## Run any artisan command — usage: make artisan CMD="make:model Foo"
artisan:
	$(APP) php artisan $(CMD)

## Open Laravel Tinker REPL
tinker:
	$(APP) php artisan tinker

## Start the queue worker
queue:
	$(APP) php artisan queue:work --no-interaction

## Restart the queue worker container (picks up code changes)
queue-restart:
	$(DC) restart queue

## Tail queue worker logs
queue-logs:
	$(DC) logs -f queue

## Tail scheduler logs
scheduler-logs:
	$(DC) logs -f scheduler

## Clear all caches
cache-clear:
	$(APP) php artisan config:clear
	$(APP) php artisan cache:clear
	$(APP) php artisan route:clear
	$(APP) php artisan view:clear

## Cache config, routes, and views for production
optimize:
	$(APP) php artisan optimize

# --------------------------------------------------------------------------
# Testing & Quality
# --------------------------------------------------------------------------

.PHONY: test test-filter lint pint

## Run the full test suite (Pest)
test:
	$(APP) php artisan test --compact

## Run tests matching a filter — usage: make test-filter FILTER="OrderPlacement"
test-filter:
	$(APP) php artisan test --compact --filter=$(FILTER)

## Run Dusk browser tests (requires selenium container)
dusk:
	$(APP) php artisan dusk

## Run Dusk tests matching a filter — usage: make dusk-filter FILTER="admin can log in"
dusk-filter:
	$(APP) php artisan dusk --filter="$(FILTER)"

## Run Laravel Pint code formatter
pint:
	$(APP) vendor/bin/pint --format agent

## Run Pint on changed files only
pint-dirty:
	$(APP) vendor/bin/pint --dirty --format agent

# --------------------------------------------------------------------------
# Shell Access
# --------------------------------------------------------------------------

.PHONY: shell shell-node

## Open a bash shell inside the app container
shell:
	$(DC) exec app bash

## Open a shell inside the node container
shell-node:
	$(DC) exec node sh

# --------------------------------------------------------------------------
# Help
# --------------------------------------------------------------------------

.PHONY: help

## Show this help message
help:
	@echo ""
	@echo "Multi-Restaurant Marketplace — Make Targets"
	@echo "============================================"
	@echo ""
	@grep -E '^## ' Makefile | sed 's/^## /  /' | while IFS= read -r line; do echo "$$line"; done
	@echo ""
	@echo "Usage: make <target>"
	@echo ""
