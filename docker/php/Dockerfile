# Using a specific version is better for stability
FROM php:8.4-fpm-bullseye

# Professional Metadata
LABEL org.opencontainers.image.source=https://github.com/doedoe123-boop/negosyohub
LABEL org.opencontainers.image.description="Production container for Negosyohub"

# Install system dependencies
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
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Optimized PHP settings
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.fast_shutdown=1'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory.ini
RUN echo "upload_max_filesize=20M\npost_max_size=25M" > /usr/local/etc/php/conf.d/uploads.ini

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Handle User Permissions
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Production Code Injection
COPY src/composer.json src/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY --chown=www-data:www-data ./src /var/www

RUN composer dump-autoload --optimize --no-dev

USER www-data

# Healthcheck
HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
    CMD php-fpm -t || exit 1

EXPOSE 9000
CMD ["php-fpm"]