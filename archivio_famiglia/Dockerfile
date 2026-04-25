FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y libzip-dev zip unzip tar \
    && docker-php-ext-install mysqli pdo pdo_mysql zip \
    && a2enmod rewrite headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY rootfs/ /
COPY run.sh /run.sh

RUN chmod a+x /run.sh \
    && chown -R www-data:www-data /var/www/html || true

CMD ["/run.sh"]
