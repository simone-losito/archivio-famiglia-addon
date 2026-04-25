#!/usr/bin/env bash
set -e

mkdir -p /share/archivio
mkdir -p /var/www/html/backups

if [ -e /var/www/html/uploads ] && [ ! -L /var/www/html/uploads ]; then
  rm -rf /var/www/html/uploads
fi

if [ ! -L /var/www/html/uploads ]; then
  ln -s /share/archivio /var/www/html/uploads
fi

chown -R www-data:www-data /share/archivio || true
chown -R www-data:www-data /var/www/html/backups || true
chown -R www-data:www-data /var/www/html || true

apache2-foreground
