FROM php:8.3-fpm-alpine
RUN apk add --no-cache git unzip libpq-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev $PHPIZE_DEPS \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_pgsql zip gd pcntl bcmath \
 && pecl install redis && docker-php-ext-enable redis \
 && apk del $PHPIZE_DEPS
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
