FROM php:8.2-apache

# --------------------------------------------------
# SYSTEM + PHP EXTENSIONS
# --------------------------------------------------
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev \
        zip \
        unzip \
        tar \
        libonig-dev \
        libxml2-dev \
        tesseract-ocr \
        tesseract-ocr-ita \
        tesseract-ocr-eng \
    && docker-php-ext-install \
        mysqli \
        pdo \
        pdo_mysql \
        zip \
    && a2enmod rewrite headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# --------------------------------------------------
# PHP CONFIG
# --------------------------------------------------
RUN { \
        echo "upload_max_filesize=50M"; \
        echo "post_max_size=60M"; \
        echo "max_file_uploads=20"; \
        echo "memory_limit=256M"; \
        echo "max_execution_time=120"; \
    } > /usr/local/etc/php/conf.d/archivio-uploads.ini

# --------------------------------------------------
# APACHE CONFIG (importantissimo per routing)
# --------------------------------------------------
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/archivio.conf \
    && a2enconf archivio

# --------------------------------------------------
# FILES
# --------------------------------------------------
COPY rootfs/ /
COPY run.sh /run.sh
COPY config.yaml /etc/archivio_famiglia_config.yaml

# --------------------------------------------------
# PERMISSIONS (fondamentale per upload)
# --------------------------------------------------
RUN chmod +x /run.sh \
    && mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html

# --------------------------------------------------
# START
# --------------------------------------------------
CMD ["/run.sh"]
