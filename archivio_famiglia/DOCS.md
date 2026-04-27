# 📁 FamilyDocs – Quick Guide

Welcome 👋
This add-on allows you to manage a **family document archive directly inside Home Assistant**.

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

* Caricare file (PDF, immagini, ecc.)
* 📷 Scattare foto da smartphone
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

---

### 💾 Backup

* backup database
* backup documenti

Il sistema mantiene automaticamente gli ultimi backup.

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

---

## 🧠 Important note

The setup wizard runs ONLY if:

* database is empty
* no users exist

---

## 👨‍💻 Author

**Simone Losito**
SimoncinoProjects
