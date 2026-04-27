# 📁 FamilyDocs – Quick Guide

Welcome 👋
This add-on allows you to manage a **family document archive directly inside Home Assistant**.

---

## 🚀 New in 1.1.3

* 🧠 Automatic OCR (local – no cloud)
* 🔍 Smart search inside documents
* ✨ Soft suggestions (title, tags, date)
* 📱 Improved mobile experience
* 🔗 Enhanced sharing system
* 🔒 Improved upload security

---

## ☕ Support the project

<p align="center">
  <a href="https://www.paypal.com/paypalme/simoncinoprojects" target="_blank">
    <img src="https://img.shields.io/badge/☕%20Support%20the%20project-PayPal-blue?style=for-the-badge&logo=paypal" />
  </a>
</p>

<p align="center">
Supporting the project helps development 🚀
</p>

🔗 Official repository
👉 https://github.com/simone-losito/archivio-famiglia-addon

---

# 🇬🇧 ENGLISH

## 🚀 First start

At first launch you will see a setup screen.

You will create:

* 👤 Username
* 🔐 Password

The system will automatically create:

* Database
* Tables
* Default categories
* Demo PDF document
* Admin account

---

## 🧠 OCR (NEW)

FamilyDocs automatically reads text from uploaded images.

### ✔ Supported files

* JPG / JPEG
* PNG
* WEBP

### 🔍 What it does

* Extracts text from images
* Saves it in database (`ocr_text`)
* Makes documents searchable

---

## ✨ Smart Suggestions (Soft Mode)

OCR does **NOT overwrite your data**.

It suggests:

* Title
* Tags
* Date

👉 Suggestions are added to notes or tags
👉 You remain in full control

---

## 🔍 Smart Search (NEW)

Search now works on:

* Title
* File name
* Notes
* Tags
* 🔥 OCR content

### Example

Upload a bill image with text:

```text
ENEL – March 120€
```

Search:

```text
enel
```

👉 Document is found even without title or tags

---

## 🧭 Interface

### 📂 Categories

Organize documents by type:

* Medical records
* Reports
* Home
* Car
* Other

You can edit or create new ones.

---

### 📄 Documents

You can:

* Upload files (PDF, images, etc.)
* 📷 Take photos from smartphone
* Add title
* Add notes
* Add tags
* Mark as ⭐ favorite
* Preview files
* Download files

---

### 👥 Users

* Create family users
* Roles: admin / user
* Enable / disable users

---

### 🔗 Sharing

You can generate temporary public links:

* access only to that file
* automatic expiration
* secure token system

---

### 💾 Backup

You can backup:

* Database
* Files

The system keeps only the latest backups automatically.

---

## 📂 Storage

All documents are stored in:

```text
/share/archivio
```

✔ persistent
✔ accessible via network (Samba)
✔ not deleted on add-on reinstall

---

## ⚠️ Common issues

Check:

* MariaDB add-on is running
* Database credentials are correct
* Configuration is valid

---

## 🔄 Reinstall

If you reinstall the add-on:

* ❌ files are NOT deleted
* ❌ database is NOT deleted (unless manually removed)

---

# 🇮🇹 ITALIANO

## 🚀 Primo utilizzo

Al primo avvio vedrai una schermata di configurazione.

Dovrai creare:

* 👤 Nome utente
* 🔐 Password

Il sistema creerà automaticamente:

* Database
* Tabelle
* Categorie base
* Documento PDF demo
* Account amministratore

---

## 🧠 OCR (NOVITÀ)

FamilyDocs legge automaticamente il testo dalle immagini caricate.

### ✔ Formati supportati

* JPG / JPEG
* PNG
* WEBP

### 🔍 Cosa fa

* Estrae il testo dalle immagini
* Lo salva nel database (`ocr_text`)
* Permette ricerca avanzata

---

## ✨ Suggerimenti intelligenti (Soft)

Il sistema NON modifica automaticamente i dati.

Suggerisce:

* Titolo
* Tag
* Data

👉 aggiunti come suggerimenti
👉 sempre sotto controllo utente

---

## 🔍 Ricerca intelligente (NOVITÀ)

La ricerca ora include:

* Titolo
* Nome file
* Note
* Tag
* 🔥 Testo OCR

### Esempio

Carichi una bolletta con scritto:

```text
ENEL – Marzo 120€
```

Cerchi:

```text
enel
```

👉 il documento viene trovato anche senza titolo corretto

---

## 🧭 Interfaccia

### 📂 Categorie

Organizza i documenti:

* Cartelle cliniche
* Referti
* Casa
* Auto
* Altro

---

### 📄 Documenti

Puoi:

* Caricare file
* 📷 Scattare foto
* Inserire titolo
* Aggiungere note
* Inserire tag
* Segnare ⭐ preferiti
* Visualizzare anteprima
* Scaricare file

---

### 👥 Utenti

* Creazione utenti familiari
* Ruoli: admin / user
* Attivazione / disattivazione

---

### 🔗 Condivisione

* link temporanei
* accesso solo al file
* scadenza automatica
* sistema sicuro con token

---

### 💾 Backup

* backup database
* backup documenti

---

## 📂 Dove vengono salvati i file

```text
/share/archivio
```

✔ persistente
✔ accessibile via rete
✔ non cancellato reinstallando

---

## ⚠️ Problemi comuni

Controlla:

* MariaDB attivo
* credenziali corrette
* configurazione DB

---

## 🔄 Reinstallazione

* ❌ non perdi i file
* ❌ non perdi il database

---

# 🔧 Technical section

## Database

Engine: MariaDB

Tables:

* utenti
* categorie
* documenti
* share_links
* 🔥 documenti.ocr_text (NEW)

---

## Ports

* Container: `80`
* Host: `8091` (configurable)

---

## Storage

* `/share/archivio` → documents
* `/data/options.json` → add-on config
* MariaDB → database

---

## Architecture

* PHP 8.2
* Apache
* Docker
* Home Assistant Add-on
* Tesseract OCR (local)

---

## 🧠 Important note

The setup wizard runs ONLY if:

* database is empty
* no users exist

---

## 👨‍💻 Author

**Simone Losito**
SimoncinoProjects 🚀
