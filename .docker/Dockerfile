# Use an official PHP Apache runtime
FROM php:8-apache
# Enable Apache modules
RUN a2enmod rewrite
# Install PostgreSQL client and its PHP extensions
RUN apt-get update \
    && apt-get install -y libpq-dev libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_pgsql intl
# Set the working directory to /var/www/html
WORKDIR /var/www/html