FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev \
        zip \
        unzip \
        tar \
    && docker-php-ext-install mysqli pdo pdo_mysql zip \
    && a2enmod rewrite headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN { \
        echo "upload_max_filesize=50M"; \
        echo "post_max_size=60M"; \
        echo "max_file_uploads=20"; \
        echo "memory_limit=256M"; \
    } > /usr/local/etc/php/conf.d/archivio-uploads.ini

COPY rootfs/ /
COPY run.sh /run.sh
COPY config.yaml /etc/archivio_famiglia_config.yaml

RUN chmod a+x /run.sh \
    && chown -R www-data:www-data /var/www/html || true

CMD ["/run.sh"]
