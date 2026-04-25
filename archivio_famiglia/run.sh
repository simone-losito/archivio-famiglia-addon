#!/usr/bin/env bash
set -e

echo "Starting Archivio Famiglia..."

mkdir -p /share/archivio
mkdir -p /var/www/html/backups

if [ ! -L /var/www/html/uploads ]; then
    rm -rf /var/www/html/uploads
    ln -s /share/archivio /var/www/html/uploads
fi

# Copio opzioni add-on in un punto leggibile da PHP
if [ -f /data/options.json ]; then
    cp /data/options.json /var/www/html/config/addon_options.json
    chmod 644 /var/www/html/config/addon_options.json
fi

chown -R www-data:www-data /var/www/html
chown -R www-data:www-data /share/archivio

echo "Contenuto /var/www/html:"
ls -la /var/www/html

exec apache2-foreground
