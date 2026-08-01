FROM php:8.4-cli

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

# Copy full app (incl. vendor) into the image so vendor is not served via slow Windows bind mounts
COPY . /var/www/html

RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
