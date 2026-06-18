FROM php:8.4-fpm-alpine

ARG user
ARG uid

# Runtime libs (permanent)
RUN apk add --no-cache \
    git \
    curl \
    libpng \
    freetype \
    libjpeg-turbo \
    libzip \
    oniguruma \
    icu \
    zip \
    unzip

# Build deps (temporary virtual group)
RUN apk add --no-cache --virtual .build-deps \
    libpng-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        gd \
        zip \
        bcmath

RUN apk del .build-deps && rm -rf /var/cache/apk/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN addgroup -g ${uid} ${user} \
    && adduser -G ${user} -G www-data -u ${uid} -D ${user}

RUN mkdir -p /home/${user}/.composer \
    && chown -R ${user}:${user} /home/${user}

WORKDIR /var/www

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER ${user}

ENTRYPOINT ["entrypoint.sh"]
