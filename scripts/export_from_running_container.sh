#!/usr/bin/env bash
# Da eseguire su Home Assistant con Docker accessibile.
# Esporta il codice dal container archivio-php nella cartella corrente.

set -e

CONTAINER="${1:-archivio-php}"
OUT="./archivio_famiglia/rootfs/var/www/html"

mkdir -p "$OUT"

docker cp "$CONTAINER:/var/www/html/." "$OUT"

# Rimuove dati personali da non pubblicare
rm -rf "$OUT/uploads" "$OUT/backups"
rm -f "$OUT/config/database.php"

echo "Codice esportato in $OUT"
echo "Controlla bene che non ci siano dati personali prima di pubblicare su GitHub."
