FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpq-dev \
    libsqlite3-dev \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pdo_sqlite \
    zip \
    opcache \
    pcntl

RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php && \
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    rm /tmp/composer-setup.php

# Configure PHP to suppress deprecation warnings (works for PHP 8.0+)
RUN { \
    echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_WARNING"; \
    echo "display_errors = Off"; \
    echo "log_errors = On"; \
    echo "ignore_repeated_errors = Off"; \
} > /usr/local/etc/php/conf.d/docker-php-errorreporting.ini

WORKDIR /var/www/html

COPY . /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chown -R www-data:www-data /var/www/html

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
