FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    iputils-ping \
    traceroute \
    mtr-tiny \
    bind9-dnsutils \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-install \
        pdo_pgsql \
        zip \
        intl \
        pcntl \
        bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
