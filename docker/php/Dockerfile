FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
        libjpeg-dev \
        libpng-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j$(nproc) gd mysqli \
    && rm -rf /var/lib/apt/lists/*

COPY php.ini /usr/local/etc/php/conf.d/docker-dev.ini
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && sed -i 's/\r//' /usr/local/bin/docker-entrypoint.sh

RUN printf 'RedirectMatch ^/$ /public/\n<DirectoryMatch "^/var/www/html/(config|db|lib|docker)">\n    Require all denied\n</DirectoryMatch>\n' > /etc/apache2/conf-enabled/redirect-root.conf

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
