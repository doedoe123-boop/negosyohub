FROM php:8.4-fpm-bookworm

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
    libfcgi-bin \
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

# Increase PHP memory limit for CLI & FPM
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory.ini

# Increase upload limits for media library
RUN echo "upload_max_filesize=20M\npost_max_size=25M\nmax_file_uploads=50" > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy only composer files first for caching
COPY src/composer.json src/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY ./src /var/www

RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

RUN composer dump-autoload --optimize --no-dev

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD SCRIPT_NAME=/up SCRIPT_FILENAME=/var/www/public/index.php REQUEST_METHOD=GET \
        cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

EXPOSE 9000

CMD ["php-fpm"]