FROM php:8.2-fpm-alpine

RUN apk update && apk add --update linux-headers \
    && apk add --no-cache --virtual .build-deps ${PHPIZE_DEPS} \
    && pecl channel-update pecl.php.net \
    && pecl install xdebug-3.2.2 \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

COPY php.ini /usr/local/etc/php/conf.d
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY docker-entrypoint.sh /docker-entrypoint.sh

WORKDIR /var/www/

ENTRYPOINT ["/docker-entrypoint.sh"]
