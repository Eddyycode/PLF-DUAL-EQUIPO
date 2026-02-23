FROM php:8.2-apache

# Install required PHP extensions for MySQL connection
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli

# Configure directory permissions
RUN chown -R www-data:www-data /var/www/html

# Expose the default Apache port so Railway automatically routes traffic here
EXPOSE 80
