FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y libzip-dev zip unzip tar \
    && docker-php-ext-install mysqli pdo pdo_mysql zip \
    && a2enmod rewrite headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN echo "upload_max_filesize=50M" > /usr/local/etc/php/conf.d/archivio-uploads.ini \
    && echo "post_max_size=60M" >> /usr/local/etc/php/conf.d/archivio-uploads.ini \
    && echo "max_file_uploads=20" >> /usr/local/etc/php/conf.d/archivio-uploads.ini \
    && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/archivio-uploads.ini

COPY rootfs/ /
COPY run.sh /run.sh
COPY config.yaml /etc/archivio_famiglia_config.yaml

RUN chmod a+x /run.sh \
    && chown -R www-data:www-data /var/www/html || true

CMD ["/run.sh"]
