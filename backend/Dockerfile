# backend/Dockerfile

# =========================================================
# Backend Laravel – PHP 8.2 FPM + SQL Server (ODBC 17)
# Base Debian (php:8.2-fpm)
# =========================================================

FROM php:8.2-fpm

# ---------------------------------------------------------
# Dependencias del sistema
# ---------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
    apt-transport-https \
    gnupg \
    curl \
    git \
    libxml2-dev \
    libfreetype6-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libzip-dev \
    unixodbc-dev \
    build-essential \
    supervisor \
    libgmp-dev \
    libicu-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------
# Microsoft ODBC Driver 17 (FORMA MODERNA – sin apt-key)
# Compatible con Debian
# ---------------------------------------------------------
RUN apt-get update && apt-get install -y gnupg curl && \
    curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
    | gpg --dearmor \
    | tee /usr/share/keyrings/microsoft.gpg > /dev/null && \
    echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft.gpg] \
https://packages.microsoft.com/debian/11/prod bullseye main" \
    > /etc/apt/sources.list.d/mssql-release.list && \
    apt-get update && \
    ACCEPT_EULA=Y apt-get install -y msodbcsql17 mssql-tools && \
    apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------
# Extensiones PHP
# ---------------------------------------------------------
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        exif \
        bcmath \
        gmp \
        intl \
        zip \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    && rm -rf /tmp/pear /var/tmp/*

# ---------------------------------------------------------
# Composer
# ---------------------------------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------
# App Laravel
# ---------------------------------------------------------
WORKDIR /var/www/html
COPY . .

RUN composer install \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

# ---------------------------------------------------------
# Permisos
# ---------------------------------------------------------
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ---------------------------------------------------------
# PHP-FPM
# ---------------------------------------------------------
EXPOSE 9000
CMD ["php-fpm"]
