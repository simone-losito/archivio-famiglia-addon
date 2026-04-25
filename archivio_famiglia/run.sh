#!/usr/bin/env bash
set -e

echo "Starting Archivio Famiglia..."

# cartelle
mkdir -p /share/archivio
mkdir -p /var/www/html/backups

# symlink upload
if [ ! -L /var/www/html/uploads ]; then
    rm -rf /var/www/html/uploads
    ln -s /share/archivio /var/www/html/uploads
fi

# permessi
chown -R www-data:www-data /var/www/html
chown -R www-data:www-data /share/archivio

# DEBUG
echo "Contenuto /var/www/html:"
ls -la /var/www/html

# AVVIO APACHE
exec apache2-foreground
