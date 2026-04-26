# Changelog

Tutte le modifiche importanti di Archivio Famiglia.

---

## [1.1.0] - 2026-04-25

### Aggiunto
- Upload da fotocamera smartphone.
- Campo separato per caricare file/PDF o scattare foto documento.

### Migliorato
- Form di caricamento documento più chiaro su mobile.
-
- ## [1.0.9] - 2026-04-25

### Aggiunto
- Pagina **Info** interna all’app.
- Collegamento alla pagina Info dal menu laterale.
- Statistiche base nella pagina Info:
  - documenti totali
  - utenti totali
  - spazio occupato
- Link GitHub e PayPal nella pagina Info.

### Migliorato
- Branding progetto con SimoncinoProjects / Simone Losito.
- README con logo, screenshot e pulsanti PayPal.
- Documentazione più chiara per utenti base.

---

## [1.0.8] - 2026-04-25

### Aggiunto
- Preparazione opzioni telemetry disattivata di default.

---

## [1.0.7] - 2026-04-25

### Corretto
- Fix controllo installazione wizard su database vuoto.
- Fix `SHOW TABLES LIKE` compatibile con MariaDB.

---

## [1.0.6] - 2026-04-25

### Migliorato
- Controllo robusto installazione:
  - wizard solo se tabella utenti assente
  - oppure tabella utenti vuota.

---

## [1.0.5] - 2026-04-25

### Aggiunto
- Wizard primo avvio.
- Creazione automatica tabelle.
- Creazione categorie base.
- Creazione primo utente admin.
- Documento PDF demo.

---

## [1.0.4] - 2026-04-25

### Corretto
- Lettura configurazione MariaDB da opzioni add-on.

---

## [1.0.3] - 2026-04-25

### Corretto
- Fix connessione database add-on.

---

## [1.0.2] - 2026-04-25

### Corretto
- Fix mapping porta add-on verso Apache.

---

## [1.0.1] - 2026-04-25

### Corretto
- Fix avvio `run.sh`.

---

## [1.0.0] - 2026-04-25

### Prima versione
- Add-on Home Assistant installabile.
- PHP + Apache + MariaDB.
- Upload documenti.
- Categorie.
- Utenti.
- Backup.
- Preview documenti.
