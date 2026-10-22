# =====================================================================
# Smart Lost & Found - Production Dockerfile (PHP 8.2 Apache)
# =====================================================================

FROM php:8.2-apache

# Install required system packages and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable required Apache modules
RUN a2enmod rewrite headers

# Configure Apache DocumentRoot to allow .htaccess overrides
RUN echo '<Directory /var/www/html>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/lostfound.conf \
    && a2enconf lostfound

# Use PHP production configuration and configure upload limits
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "upload_max_filesize = 12M\npost_max_size = 15M\nmemory_limit = 256M\n" > "$PHP_INI_DIR/conf.d/uploads.ini"

# Copy application source code
WORKDIR /var/www/html
COPY . /var/www/html/

# Set proper ownership and permissions for the web server
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/assets/uploads

# Expose standard HTTP port (can be overridden by cloud $PORT)
EXPOSE 80

# Run Apache in foreground, dynamically adapting to cloud $PORT if provided (Render, Railway, Heroku)
CMD ["sh", "-c", "if [ -n \"$PORT\" ]; then sed -i \"s/80/$PORT/g\" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf; fi && exec apache2-foreground"]
