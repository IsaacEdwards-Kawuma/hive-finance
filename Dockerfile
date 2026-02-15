# Laravel API for Render (PHP + Composer)
FROM php:8.2-cli

# Install system deps + PHP extensions Laravel needs (libpq-dev for PostgreSQL)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring xml zip bcmath opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# Copy app (excluding node_modules, vendor, .env via .dockerignore)
COPY . .

# Install PHP deps only (no dev)
RUN composer install --no-dev --no-interaction --optimize-autoloader

# Generate app key at runtime via Render env; or leave and set APP_KEY in Render
# RUN php artisan key:generate --force

EXPOSE 8000

# Run migrations and seed (idempotent) then start server — no Shell needed on Render
CMD ["sh", "-c", "php artisan migrate --force && php artisan db:seed --class=RolePresetsSeeder --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
