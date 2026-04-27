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

# Piccolo miglioramento UX mobile iniettato in modo idempotente.
# Non cambia logica PHP: migliora soltanto input file, pulsanti e griglie su smartphone.
CSS_FILE="/var/www/html/assets/css/archivio.css"
if [ -f "$CSS_FILE" ] && ! grep -q "FamilyDocs mobile upload UX" "$CSS_FILE"; then
    cat >> "$CSS_FILE" <<'CSS'

/* FamilyDocs mobile upload UX */
input[type="file"]{
    cursor:pointer;
    min-height:52px;
    padding:10px;
    border-style:dashed;
    background:rgba(34,211,238,.06);
}
input[type="file"]::file-selector-button{
    border:0;
    border-radius:999px;
    padding:10px 14px;
    margin-right:12px;
    background:linear-gradient(90deg,var(--cyan),var(--violet));
    color:#ffffff;
    font-weight:800;
    cursor:pointer;
}
:root[data-theme="light"] input[type="file"]{
    background:#f8fafc;
}
@media(max-width:700px){
    .upload-grid,
    .upload-choice-grid{
        grid-template-columns:1fr !important;
        gap:12px;
    }
    .upload-choice{
        padding:15px;
        border-radius:19px;
        background:rgba(34,211,238,.055) !important;
    }
    .upload-choice strong{
        font-size:17px;
    }
    .upload-choice small{
        font-size:13px;
    }
    input[type="file"]{
        font-size:15px;
        min-height:58px;
        padding:11px;
    }
    input[type="file"]::file-selector-button{
        display:block;
        width:100%;
        margin:0 0 9px 0;
        padding:12px 14px;
    }
    button,
    .btn{
        min-height:48px;
    }
}
CSS
    echo "[FamilyDocs] Mobile upload UX CSS applied."
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
