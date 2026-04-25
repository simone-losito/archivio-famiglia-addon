#!/usr/bin/env bash
set -e

mkdir -p /share/archivio
mkdir -p /var/www/html/uploads
mkdir -p /var/www/html/backups

# Nel pacchetto finale puoi usare /share/archivio come storage persistente documenti.
# Se /var/www/html/uploads non è già una cartella persistente, la colleghiamo a /share/archivio.
if [ ! -L /var/www/html/uploads ]; then
  rm -rf /var/www/html/uploads
  ln -s /share/archivio /var/www/html/uploads
fi

chown -R www-data:www-data /share/archivio || true
chown -R www-data:www-data /var/www/html/backups || true

apache2-foreground
