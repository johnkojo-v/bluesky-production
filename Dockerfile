FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libcurl4-openssl-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql curl

WORKDIR /var/www/html
COPY . /var/www/html

RUN a2enmod rewrite
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
