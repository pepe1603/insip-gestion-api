# backend/Dockerfile

# Usamos una imagen de PHP-FPM basada en Debian para una mejor compatibilidad con los drivers MSSQL
FROM php:8.2-fpm

# Instala dependencias del sistema y extensiones de PHP necesarias
# Usamos apt en lugar de apk
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
    # Dependencias específicas para el controlador ODBC de Microsoft
    unixodbc-dev \
    # build-essential para compilar extensiones PHP y otras dependencias de desarrollo
    build-essential \
    supervisor \
    # PAQUETES DE GMP CORREGIDOS
    libgmp-dev \
    # Dependencia de ICU para 'intl'
    libicu-dev \
    # Limpia el cache de apt
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# --- INICIO: Instalación del Microsoft ODBC Driver 17 for SQL Server en Debian ---
# Este bloque instala el controlador ODBC de Microsoft para SQL Server en Debian.

# 1. Importar la clave GPG de Microsoft
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -

# 2. Añadir el repositorio de Microsoft para Ubuntu (compatible con Debian)
# Usamos 'ubuntu/22.04' (Jammy) que es una versión LTS reciente y compatible con Debian Bookworm (base de PHP 8.2-fpm)
RUN curl https://packages.microsoft.com/config/ubuntu/22.04/prod.list > /etc/apt/sources.list.d/mssql-release.list

# 3. Actualizar los índices de paquetes
RUN apt-get update

# 4. Instalar el controlador ODBC de Microsoft para SQL Server y sus herramientas
# Aquí los paquetes son msodbcsql17 y mssql-tools
# Acepta la licencia EULA automáticamente con ACCEPT_EULA
ENV ACCEPT_EULA=Y
RUN apt-get install -y --no-install-recommends \
    msodbcsql17 \
    mssql-tools \
    # Limpiar el cache de apt después de la instalación
    && apt-get clean && rm -rf /var/lib/apt/lists/*
# --- FIN: Instalación del Microsoft ODBC Driver 17 for SQL Server en Debian ---


# Configurar y construir extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        exif \
        bcmath \
        gmp \
        intl \
        zip \
    # Instalar los controladores de SQL Server para PHP (de PECL)
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    # Limpiar el cache de PECL y otros temporales
    && rm -rf /tmp/pear/download/* /var/tmp/* /var/cache/apt/*

# Instala Composer (desde la imagen oficial de Composer)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define el directorio de trabajo dentro del contenedor
WORKDIR /var/www/html

# Copia los archivos de la aplicación Laravel al contenedor
COPY . .

# Instala las dependencias de Composer (solo las de producción)
RUN composer install \
    --optimize-autoloader \
#	--no-dev \ # no apto para pridcucion epro se agregara par aevitar eerro de pail class la ejecutarse Laravel
    --no-interaction \
    --prefer-dist

# Asegura los permisos de los directorios 'storage' y 'bootstrap/cache'
# 'www-data' es el usuario por defecto para Apache/Nginx en estas imágenes
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Exponer el puerto 9000 para PHP-FPM
EXPOSE 9000

# El comando por defecto cuando el contenedor se inicia: arranca PHP-FPM
CMD ["php-fpm"]