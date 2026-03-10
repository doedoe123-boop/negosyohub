FROM php:8.4-fpm

# Professional Metadata (Labels)
LABEL org.opencontainers.image.source=https://github.com/doedoe123-boop/negosyohub
LABEL org.opencontainers.image.description="container for Negosyohub"
LABEL org.opencontainers.image.licenses=Apache-2.0

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
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Increase PHP memory limit for CLI & FPM
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan
RUN groupadd -g 1000 www && \
    useradd -u 1000 -ms /bin/bash -g www www

# Set working directory
WORKDIR /var/www

# Copy existing application directory permissions
COPY --chown=www:www ./src /var/www

USER www

HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
    CMD php -v || exit 1

EXPOSE 9000
CMD ["php-fpm"]
