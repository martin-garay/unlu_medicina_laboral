# PHP 8.3 CLI image to run Laravel via `php artisan serve`
FROM php:8.3-cli

# Build arguments allow matching host UID/GID to avoid root-owned files in the project.
ARG UID=1000
ARG GID=1000

# Install OS packages required for common PHP extensions and tooling
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        curl \
        libpq-dev \
        libzip-dev \
        libxml2-dev \
        libonig-dev \
        zlib1g-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions needed by Laravel + PostgreSQL
RUN docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        mbstring \
        xml \
        zip

# Create a non-root user that matches the host (helps prevent permission issues)
RUN groupadd -g ${GID} appuser \
    && useradd -u ${UID} -g appuser -m appuser

WORKDIR /var/www/html

# Ensure runtime directories exist and are writable by the non-root user
RUN mkdir -p storage bootstrap/cache \
    && chown -R appuser:appuser storage bootstrap/cache

USER appuser

# Default command keeps it simple for juniors: serve Laravel directly
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
