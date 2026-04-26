# 📜 Changelog

Tutte le modifiche importanti di Archivio Famiglia.

---

## [1.1.1] - 2026-04-26

### Migliorato
- Stabilizzazione generale add-on Home Assistant
- Migliorata sicurezza upload (basename, validazione categorie)
- Migliore gestione errori upload file
- Ottimizzazione gestione percorsi `/share/archivio`
- Migliorata compatibilità mobile

### Corretto
- Fix path traversal upload/download
- Fix generazione link pubblici
- Fix lettura configurazione add-on (`addon_options.json`)
- Fix piccoli bug UI tema light/dark

---

## [1.1.0] - 2026-04-25

### Aggiunto
- Upload da fotocamera smartphone 📷
- Campo separato:
  - file/PDF
  - scatta foto documento

### Migliorato
- Form upload ottimizzato per mobile
- UX più chiara per caricamento documenti

---

## [1.0.9] - 2026-04-25

### Aggiunto
- Pagina **Info**
- Statistiche archivio:
  - documenti totali
  - utenti totali
  - spazio occupato
- Link GitHub
- Link PayPal supporto progetto

### Migliorato
- Branding progetto (SimoncinoProjects)
- README con logo e screenshot
- Documentazione migliorata

---

## [1.0.8] - 2026-04-25

### Aggiunto
- Preparazione sistema telemetry (disattivo di default)

---

## [1.0.7] - 2026-04-25

### Corretto
- Fix wizard installazione su database vuoto
- Fix `SHOW TABLES LIKE` compatibile MariaDB

---

## [1.0.6] - 2026-04-25

### Migliorato
- Controllo installazione robusto:
  - wizard solo se tabella utenti assente
  - oppure tabella utenti vuota

---

## [1.0.5] - 2026-04-25

### Aggiunto
- Wizard primo avvio
- Creazione automatica:
  - tabelle database
  - categorie base
  - primo utente admin
  - PDF demo

---

## [1.0.4] - 2026-04-25

### Corretto
- Lettura configurazione MariaDB da add-on

---

## [1.0.3] - 2026-04-25

### Corretto
- Fix connessione database add-on

---

## [1.0.2] - 2026-04-25

### Corretto
- Fix mapping porta add-on → Apache

---

## [1.0.1] - 2026-04-25

### Corretto
- Fix avvio `run.sh`

---

## [1.0.0] - 2026-04-25

### Prima versione
- Add-on Home Assistant installabile
- PHP + Apache + MariaDB
- Upload documenti
- Gestione categorie
- Gestione utenti
- Backup
- Preview documenti
