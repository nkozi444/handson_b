# Use official PHP image
FROM php:8.2-cli

# Install dependencies required by Symfony & MySQL
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libicu-dev libzip-dev zip \
    && docker-php-ext-install intl pdo pdo_mysql zip fileinfo

# Install Composer (Symfony needs this)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Set working directory inside the container
WORKDIR /var/www/html

# Expose port 8000 for Symfony local server
EXPOSE 8000

# Default command: run Symfony dev server
CMD ["symfony", "serve", "--no-tls", "--port=8000", "--dir=/var/www/html"]