# Use FrankenPHP - it includes the web server and PHP 8.4
FROM dunglas/frankenphp:1.3-php8.4-bookworm

# Metadata
LABEL org.opencontainers.image.source=https://github.com/doedoe123-boop/negosyohub
LABEL org.opencontainers.image.description="Production container for Negosyohub on Cloud Run"

# Install system dependencies (Keeping your specific list)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libonig-dev \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        bcmath \
        intl \
        gd \
        mbstring \
        opcache \
        exif \
        pcntl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy only composer files first for caching
COPY src/composer.json src/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy the rest of your source code
COPY ./src /var/www

# Fix permissions for Laravel
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Copy custom Caddyfile — disables auto-HTTPS and sets correct document root.
# Cloud Run terminates TLS, so the container only needs plain HTTP on $PORT.
COPY docker/php/Caddyfile /etc/caddy/Caddyfile

# Finalize composer
RUN composer dump-autoload --optimize --no-dev

# Fail the container if the app stops responding (Laravel's built-in /up route).
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -f http://localhost:${PORT:-8080}/up || exit 1

# Expose the port Cloud Run expects
EXPOSE 8080

# No need for a separate CMD for php-fpm, FrankenPHP handles it
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]