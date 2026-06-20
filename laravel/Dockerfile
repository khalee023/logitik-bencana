FROM php:8.2-apache

# 1. Install sistem dependensi
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd zip

# 2. Aktifkan Apache mod_rewrite
RUN a2enmod rewrite

# 3. Ubah Apache document root ke folder /public milik Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Sesuaikan Port Apache (Render menggunakan env PORT, default 80 untuk Docker kalau kita paksa)
RUN sed -i 's/80/${PORT:-80}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# 5. Install Node.js (untuk compile Vite CSS/JS)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs

# 6. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 7. Set direktori kerja
WORKDIR /var/www/html

# 8. Copy file project
COPY . .

# 9. Install dependensi PHP
RUN composer install --no-interaction --no-dev --optimize-autoloader

# 10. Install dependensi Node & Build Vite (Tailwind/CSS/JS)
RUN npm install && npm run build

# 11. Set hak akses untuk folder storage & bootstrap/cache (penting untuk Laravel)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 12. Script saat Container berjalan (Cache config & jalankan Apache)
CMD php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    apache2-foreground
