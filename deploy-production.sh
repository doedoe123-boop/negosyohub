#!/usr/bin/env bash

set -Eeuo pipefail

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BRANCH="${DEPLOY_BRANCH:-main}"
BUILD_FRONTEND="${BUILD_FRONTEND:-true}"
RELOAD_SERVICES="${RELOAD_SERVICES:-true}"
PHP_BINARY="${PHP_BINARY:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"

echo "==> Deploying NegosyoHub from branch: ${BRANCH}"
cd "${APP_ROOT}"

echo "==> Fetching latest code"
git fetch origin "${BRANCH}"
git reset --hard "origin/${BRANCH}"

echo "==> Installing backend dependencies"
cd "${APP_ROOT}/src"
"${COMPOSER_BIN}" install --no-dev --prefer-dist --no-interaction --optimize-autoloader

echo "==> Running Laravel maintenance tasks"
"${PHP_BINARY}" artisan migrate --force
"${PHP_BINARY}" artisan optimize:clear
"${PHP_BINARY}" artisan config:cache
"${PHP_BINARY}" artisan route:cache
"${PHP_BINARY}" artisan view:cache
"${PHP_BINARY}" artisan event:cache
"${PHP_BINARY}" artisan queue:restart || true

if [[ "${BUILD_FRONTEND}" == "true" && -f "${APP_ROOT}/frontend/package.json" ]]; then
    echo "==> Building storefront frontend"
    cd "${APP_ROOT}/frontend"
    "${NPM_BIN}" ci
    "${NPM_BIN}" run build
fi

reload_service_if_possible() {
    local service_name="$1"

    if [[ "${RELOAD_SERVICES}" != "true" ]]; then
        return 0
    fi

    if command -v sudo >/dev/null 2>&1 && sudo -n true >/dev/null 2>&1; then
        sudo systemctl reload "${service_name}" || true

        return 0
    fi

    if [[ "$(id -u)" -eq 0 ]]; then
        systemctl reload "${service_name}" || true
    fi
}

echo "==> Reloading runtime services when permitted"
reload_service_if_possible nginx
reload_service_if_possible php8.4-fpm

echo "==> Deployment complete"
