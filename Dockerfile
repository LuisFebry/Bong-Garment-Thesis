# ==============================================================================
# Dockerfile: Bong Garment CMT Production & Inventory Management System
# Base OS: Ubuntu 24.04 LTS (Noble Numbat)
# Stack: PHP 8.4 FPM + Nginx + Node.js 20 + Composer
# Author: Luis Febry - Universitas Tarumanagara
# ==============================================================================

FROM ubuntu:24.04

# Prevent interactive prompts during package installations
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=Asia/Jakarta

# Install system dependencies, Nginx, PHP 8.3, Git, Curl, and Zip
RUN apt-get update && apt-get install -y --no-install-recommends \
    software-properties-common \
    curl \
    wget \
    gnupg2 \
    ca-certificates \
    lsb-release \
    git \
    unzip \
    supervisor \
    nginx \
    mysql-client \
    && LC_ALL=C.UTF-8 add-apt-repository -y ppa:ondrej/php \
    && apt-get update && apt-get install -y --no-install-recommends \
    php8.4-fpm \
    php8.4-cli \
    php8.4-common \
    php8.4-mysql \
    php8.4-pdo \
    php8.4-mbstring \
    php8.4-xml \
    php8.4-curl \
    php8.4-zip \
    php8.4-bcmath \
    php8.4-intl \
    php8.4-gd \
    php8.4-opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js (v20 LTS) & NPM
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Pre-install PHP dependencies so container boots ready even without manual composer install
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Configure PHP-FPM (pass Docker environment variables to workers), Nginx, and Supervisor
RUN sed -i 's/;clear_env = no/clear_env = no/g' /etc/php/8.4/fpm/pool.d/www.conf \
    && (grep -q '^clear_env = no' /etc/php/8.4/fpm/pool.d/www.conf || echo 'clear_env = no' >> /etc/php/8.4/fpm/pool.d/www.conf)
COPY docker/nginx/default.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Configure executable permissions & writable Laravel directories
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh /var/www/html/docker/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh /var/www/html/docker/entrypoint.sh /var/www/html/artisan \
    && mkdir -p /var/www/html/storage/framework/sessions \
                /var/www/html/storage/framework/views \
                /var/www/html/storage/framework/cache/data \
                /var/www/html/storage/logs \
                /var/www/html/bootstrap/cache \
                /var/run/php \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/run/php \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose HTTP port
EXPOSE 80

# Run startup entrypoint before handing over to supervisord
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

