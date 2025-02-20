FROM php:8.4-apache  # Use the official PHP 8.4 Apache image

# Install necessary extensions (from php_requirements.txt - adapt as needed)
RUN apt-get update && \
    apt-get install -y \
        libzip-dev \
        zip \
        unzip \
        libldap-2.4-2 \
        libldap-dev \
        && docker-php-ext-configure zip --with-libzip \
        && docker-php-ext-install zip ldap pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . /var/www/html/

# Set file permissions (important!)
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Install Composer dependencies
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader

# Copy the config.php file (you'll create this outside the Docker context)
COPY config.php /var/www/html/config.php

# Expose port 80 (or your desired port)
EXPOSE 80

# Apache configuration (optional - if you need custom settings)
# COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Create installed.lock file (if needed - depends on your install.php logic)
# RUN touch /var/www/html/installed.lock

# Set the entrypoint (if you have any specific commands to run on startup)
# CMD ["apache2-foreground"] # This is the default for the php:apache image
