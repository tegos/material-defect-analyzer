FROM php:8.4-fpm-alpine

ARG user
ARG uid

RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    oniguruma-dev \
    icu-dev \
    zip \
    unzip

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        gd \
        zip \
        bcmath

RUN apk del freetype-dev libjpeg-turbo-dev libpng-dev \
    && rm -rf /var/cache/apk/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN addgroup -g ${uid} ${user} \
    && adduser -G ${user} -G www-data -u ${uid} -D ${user}

RUN mkdir -p /home/${user}/.composer \
    && chown -R ${user}:${user} /home/${user}

WORKDIR /var/www

USER ${user}
