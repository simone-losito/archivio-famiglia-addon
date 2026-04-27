#!/usr/bin/env bash
set -e

echo "[FamilyDocs] Starting add-on..."

mkdir -p /share/archivio
mkdir -p /var/www/html/backups
mkdir -p /var/www/html/config

# uploads deve essere sempre un symlink verso /share/archivio
# In questo modo i documenti restano persistenti anche dopo aggiornamenti/rebuild dell'add-on.
if [ -e /var/www/html/uploads ] && [ ! -L /var/www/html/uploads ]; then
    echo "[FamilyDocs] Replacing local uploads directory with persistent symlink..."
    rm -rf /var/www/html/uploads
fi

if [ ! -L /var/www/html/uploads ]; then
    echo "[FamilyDocs] Creating uploads symlink: /var/www/html/uploads -> /share/archivio"
    ln -s /share/archivio /var/www/html/uploads
fi

# Copio opzioni add-on in un punto leggibile da PHP.
# Se il file non esiste al primissimo avvio, il PHP userà i fallback già previsti.
if [ -f /data/options.json ]; then
    cp /data/options.json /var/www/html/config/addon_options.json
    chmod 644 /var/www/html/config/addon_options.json
    echo "[FamilyDocs] Add-on options loaded."
else
    echo "[FamilyDocs] No /data/options.json found, using application defaults."
fi

chown -R www-data:www-data /var/www/html
chown -R www-data:www-data /share/archivio

echo "[FamilyDocs] Ready."
echo "[FamilyDocs] Persistent documents path: /share/archivio"

exec apache2-foreground
