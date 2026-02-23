FROM php:8.2-apache

# Install required PHP extensions for MySQL connection
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli

# Copy application files to the standard Apache document root
COPY . /var/www/html/

# Replace the default port 80 with Railway's dynamic $PORT variable in Apache's configuration
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Fix AH00534 by physically deleting conflicting module configurations and cleanly enabling prefork
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load && \
    ln -s /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load && \
    ln -s /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

# Configure directory permissions
RUN chown -R www-data:www-data /var/www/html

# Start apache directly in the foreground to prevent Railway overrides
CMD ["apache2-foreground"]
