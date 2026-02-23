FROM php:8.2-apache

# Install required PHP extensions for MySQL connection
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli

# Copy application files to the standard Apache document root
COPY . /var/www/html/

# Replace the default port 80 with Railway's dynamic $PORT variable in Apache's configuration
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Fix AH00534 "More than one MPM loaded" by explicitly disabling conflicting modules
RUN a2dismod mpm_event mpm_worker && a2enmod mpm_prefork

# Configure directory permissions
RUN chown -R www-data:www-data /var/www/html
