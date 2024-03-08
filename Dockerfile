FROM php:8.3.3-zts-bookworm
COPY --from=composer/composer:latest-bin /composer /usr/bin/composer
RUN pecl install parallel && docker-php-ext-enable parallel

WORKDIR /app
COPY . /app
RUN composer install
CMD ["composer", "run", "test"]

