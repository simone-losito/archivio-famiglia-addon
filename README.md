# 📁 Archivio Famiglia

<p align="center">
  <img src="assets/logo.png" width="180" alt="Archivio Famiglia">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/version-1.1.1-blue?style=for-the-badge">
  <img src="https://img.shields.io/badge/Home%20Assistant-Add--on-41BDF5?style=for-the-badge&logo=homeassistant&logoColor=white">
  <img src="https://img.shields.io/badge/status-stable-success?style=for-the-badge">
  <img src="https://img.shields.io/badge/license-MIT-green?style=for-the-badge">
</p>

<p align="center">
  <b>Archivio documentale familiare per Home Assistant</b><br>
  by <b>SimoncinoProjects / Simone Losito</b>
</p>

---

## ✨ Cos’è

**Archivio Famiglia** è un add-on Home Assistant per gestire documenti familiari, pratiche, referti, bollette, file casa, auto e documenti importanti.

Permette di caricare, cercare, visualizzare, scaricare, modificare e condividere documenti da browser, smartphone o tablet.

---

## 🖼️ Anteprima

### 🌙 Tema Dark

<p align="center">
  <img src="assets/screens/dashboard-dark.png" width="900" alt="Dashboard dark">
</p>

### ☀️ Tema Light

<p align="center">
  <img src="assets/screens/dashboard-light.png" width="900" alt="Dashboard light">
</p>

### 📂 Gestione categorie

<p align="center">
  <img src="assets/screens/categorie.png" width="900" alt="Categorie">
</p>

---

## ✅ Funzioni principali

- 📂 Gestione categorie con immagini
- 📄 Upload documenti
- 📷 Upload foto da smartphone con fotocamera
- 👁️ Anteprima PDF e immagini
- ⬇️ Download documenti
- ✏️ Modifica dati documento
- 🗑️ Eliminazione documenti
- ⭐ Preferiti
- 🔎 Ricerca per nome, categoria, tag, note, data, anno e mese
- 👥 Gestione utenti
- 🔐 Ruoli admin/user
- 🔗 Link pubblici temporanei
- 💾 Backup database e file
- 🌙 Tema dark
- ☀️ Tema light
- ℹ️ Pagina info con statistiche
- 🏠 Integrazione Home Assistant

---

## 🚀 Installazione su Home Assistant

1. Vai in **Impostazioni → Componenti aggiuntivi → Add-on Store**
2. Clicca sui **tre puntini** in alto a destra
3. Apri **Repository**
4. Inserisci questo URL:
5. # 📁 Archivio Famiglia

<p align="center">
  <img src="assets/logo.png" width="180" alt="Archivio Famiglia">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/version-1.1.1-blue?style=for-the-badge">
  <img src="https://img.shields.io/badge/Home%20Assistant-Add--on-41BDF5?style=for-the-badge&logo=homeassistant&logoColor=white">
  <img src="https://img.shields.io/badge/status-stable-success?style=for-the-badge">
  <img src="https://img.shields.io/badge/license-MIT-green?style=for-the-badge">
</p>

<p align="center">
  <b>Archivio documentale familiare per Home Assistant</b><br>
  by <b>SimoncinoProjects / Simone Losito</b>
</p>

---

## ✨ Cos’è

**Archivio Famiglia** è un add-on Home Assistant per gestire documenti familiari, pratiche, referti, bollette, file casa, auto e documenti importanti.

Permette di caricare, cercare, visualizzare, scaricare, modificare e condividere documenti da browser, smartphone o tablet.

---

## 🖼️ Anteprima

### 🌙 Tema Dark

<p align="center">
  <img src="assets/screens/dashboard-dark.png" width="900" alt="Dashboard dark">
</p>

### ☀️ Tema Light

<p align="center">
  <img src="assets/screens/dashboard-light.png" width="900" alt="Dashboard light">
</p>

### 📂 Gestione categorie

<p align="center">
  <img src="assets/screens/categorie.png" width="900" alt="Categorie">
</p>

---

## ✅ Funzioni principali

- 📂 Gestione categorie con immagini
- 📄 Upload documenti
- 📷 Upload foto da smartphone con fotocamera
- 👁️ Anteprima PDF e immagini
- ⬇️ Download documenti
- ✏️ Modifica dati documento
- 🗑️ Eliminazione documenti
- ⭐ Preferiti
- 🔎 Ricerca per nome, categoria, tag, note, data, anno e mese
- 👥 Gestione utenti
- 🔐 Ruoli admin/user
- 🔗 Link pubblici temporanei
- 💾 Backup database e file
- 🌙 Tema dark
- ☀️ Tema light
- ℹ️ Pagina info con statistiche
- 🏠 Integrazione Home Assistant

---

## 🚀 Installazione su Home Assistant

1. Vai in **Impostazioni → Componenti aggiuntivi → Add-on Store**
2. Clicca sui **tre puntini** in alto a destra
3. Apri **Repository**
4. Inserisci questo URL:

https://github.com/simone-losito/archivio-famiglia-addon

5. Installa Archivio Famiglia
6. Configura i dati MariaDB
7. Avvia l’add-on
8. Apri la Web UI

---

⚙️ Configurazione add-on

Esempio configurazione:

db_host: core-mariadb
db_name: homeassistant
db_user: homeassistant
db_pass: LA_TUA_PASSWORD_MARIADB
telemetry_enabled: false
telemetry_endpoint: ""

---

🧠 Primo avvio

Al primo avvio parte il wizard automatico.

Il wizard crea:

tabelle database
categorie iniziali
primo utente admin
PDF dimostrativo

Il wizard parte solo se non esiste nessun utente.

---

📷 Upload da smartphone

Da smartphone puoi usare:

- caricamento file/PDF normale
- pulsante Scatta foto documento

La foto viene salvata come documento immagine dentro l’archivio.

---

📂 Storage persistente

I documenti vengono salvati in:
/share/archivio
Dentro l’add-on la cartella:
/var/www/html/uploads
è un collegamento verso:
/share/archivio
Quindi i documenti restano persistenti anche reinstallando l’add-on, purché non venga eliminata la cartella /share/archivio.

---

🌐 Porta

Porta predefinita:

8091

Configurazione add-on:

ports:
  80/tcp: 8091

  ---

 🔐 Sicurezza base
- password salvate con hash sicuro
- accesso con login
- utenti attivabili/disattivabili
- ultimo admin protetto
- categorie validate
- nomi file sanitizzati
- protezione base contro path traversal
- link pubblici temporanei con token e scadenza
- limite upload PHP aumentato a 50 MB

  ---

 💾 Backup

La sezione backup permette di creare:

backup database .sql
backup documenti .tar.gz

Vengono mantenuti gli ultimi 2 backup per tipo.

---

🛠️ Tecnologie
PHP 8.2
Apache
MariaDB
Docker
Home Assistant Add-on

---

📌 Roadmap
 Upload multiplo
 OCR documenti
 Notifiche Home Assistant
 Ruoli avanzati
 Esportazione archivio completa
 Miglioramenti mobile

 ---

 ☕ Supporta il progetto
<p align="center"> <a href="https://www.paypal.com/paypalme/simoncinoprojects" target="_blank"> <img src="https://img.shields.io/badge/Supporta%20il%20progetto-PayPal-blue?style=for-the-badge&logo=paypal" alt="Supporta il progetto con PayPal"> </a> </p>

---

👨‍💻 Autore

Simone Losito
SimoncinoProjects

---

⭐ Se ti piace

Lascia una ⭐ su GitHub e condividi il progetto.

---

📜 Licenza

MIT License



