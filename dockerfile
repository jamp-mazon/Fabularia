# --- Etapa 1: Build de dependencias ---
FROM composer:2.7 AS composer-build

WORKDIR /app
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

# --- Etapa 2: Producción ---
FROM php:8.2-apache AS production

# Instalación de extensiones
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libssl-dev \
        ca-certificates \
        curl \
        unzip \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        opcache \
    && a2enmod rewrite ssl headers \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copiamos dependencias y código ANTES de cambiar permisos
COPY --from=composer-build /app/vendor ./vendor
COPY . .

# Creamos directorios y ajustamos permisos
RUN mkdir -p /app/certs && \
    chown -R www-data:www-data /var/www/html /app/certs

# Exponemos el puerto
EXPOSE 80

# CMD Final: Genera el .env y arranca el proceso PID 1
# He añadido comillas para evitar problemas con caracteres especiales en las API Keys
CMD sh -c "echo \"\
DB_HOST=${DB_HOST}\n\
DB_PORT=${DB_PORT}\n\
DB_NAME=${DB_NAME}\n\
DB_USER=${DB_USER}\n\
DB_PASS=${DB_PASS}\n\
DB_SSL_CA=${DB_SSL_CA}\n\
DB_SSL_CERT=${DB_SSL_CERT}\n\
DB_SSL_KEY=${DB_SSL_KEY}\n\
N8N_WEBHOOK_PRESTAMO=${N8N_WEBHOOK_PRESTAMO}\n\
TELEGRAM_BOT_URL_BASE=${TELEGRAM_BOT_URL_BASE}\n\
TELEGRAM_VINCULACION_TOKEN=${TELEGRAM_VINCULACION_TOKEN}\n\
GOOGLE_BOOKS_API_KEY=${GOOGLE_BOOKS_API_KEY}\" > .env && apache2-foreground"