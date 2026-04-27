# 📁 FamilyDocs

<p align="center">
  <img src="assets/logo.png" width="180" alt="FamilyDocs">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/version-1.1.2-blue?style=for-the-badge">
  <img src="https://img.shields.io/badge/Home%20Assistant-Add--on-41BDF5?style=for-the-badge&logo=homeassistant&logoColor=white">
  <img src="https://img.shields.io/badge/status-stable-success?style=for-the-badge">
  <img src="https://img.shields.io/badge/license-MIT-green?style=for-the-badge">
</p>

<p align="center">
  <b>Document Manager for Home Assistant</b><br>
  <b>ex Archivio Famiglia</b><br>
  by <b>SimoncinoProjects / Simone Losito</b>
</p>

---

## 🇬🇧 What is FamilyDocs?

**FamilyDocs** is a Home Assistant add-on designed to manage family documents, medical records, bills, home files, car documents and other important files.

It allows you to upload, search, preview, download, edit, organize and temporarily share documents from a browser, smartphone or tablet.

FamilyDocs was originally created as **Archivio Famiglia** and is now evolving into an international project.

---

## 🇮🇹 Cos’è FamilyDocs?

**FamilyDocs**, ex **Archivio Famiglia**, è un add-on Home Assistant per gestire documenti familiari, pratiche, referti, bollette, file casa, auto e documenti importanti.

Permette di caricare, cercare, visualizzare, scaricare, modificare, organizzare e condividere temporaneamente documenti da browser, smartphone o tablet.

---

## 🖼️ Preview

### 🌙 Dark theme

<p align="center">
  <img src="assets/screens/dashboard-dark.png" width="900">
</p>

### ☀️ Light theme

<p align="center">
  <img src="assets/screens/dashboard-light.png" width="900">
</p>

### 📂 Categories

<p align="center">
  <img src="assets/screens/categorie.png" width="900">
</p>

---

## ✅ Main features

* 📂 Category management with images
* 📄 Document upload
* 📷 Smartphone photo upload
* 👁️ PDF preview
* 🖼️ Image preview
* ⬇️ Document download
* ✏️ Document editing
* 🗑️ Document deletion
* ⭐ Favorites
* 🔎 Advanced search (name, category, tags, date)
* 👥 User management
* 🔐 Admin/user roles
* 🔗 Temporary public links
* 💾 Backup database + files
* 🌙 Dark theme
* ☀️ Light theme
* 🌍 Multilanguage (IT / EN)
* 🏠 Home Assistant integration

---

## 🚀 Installation on Home Assistant

1. Vai in **Settings → Add-ons → Add-on Store**
2. Clicca sui **tre puntini**
3. Apri **Repositories**
4. Inserisci:

https://github.com/simone-losito/archivio-famiglia-addon

5. Installa **FamilyDocs**
6. Configura MariaDB
7. Avvia
8. Apri la Web UI

---

## ⚠️ Non è HACS

FamilyDocs è un **add-on**, NON un’integrazione HACS.

Si installa da:
**Add-on Store → Repository**

---

## ⚙️ Configurazione

```yaml
db_host: core-mariadb
db_name: homeassistant
db_user: homeassistant
db_pass: PASSWORD
telemetry_enabled: false
telemetry_endpoint: ""
```

---

## 🧠 Primo avvio

Il sistema crea automaticamente:

* tabelle database
* categorie base
* primo admin
* PDF demo

---

## 🌍 Lingue

Supportate:

* 🇮🇹 Italiano
* 🇬🇧 English

Cambio lingua via:

```
?lang=it
?lang=en
```

---

## 📷 Upload da smartphone

* Upload file normale
* Pulsante **Scatta foto documento**

---

## 📂 Storage

Percorso reale:

```
/share/archivio
```

Interno container:

```
/var/www/html/uploads
```

---

## 🌐 Porta

```
8091
```

---

## 🔐 Sicurezza

* password hashate
* login protetto
* utenti attivi/disattivi
* protezione ultimo admin
* validazione input
* link temporanei sicuri

---

## 💾 Backup

* database `.sql`
* file `.tar.gz`
* mantiene ultimi 2

---

## 🛠️ Stack

* PHP 8.2
* Apache
* MariaDB
* Docker
* Home Assistant

---

## 📌 Roadmap

* upload multiplo
* OCR
* notifiche HA
* ruoli avanzati
* export completo
* miglioramenti mobile

---

## ☕ Supporta il progetto

https://www.paypal.com/paypalme/simoncinoprojects

---

## 👨‍💻 Autore

Simone Losito
SimoncinoProjects

---

## ⭐ Support

Lascia una ⭐ su GitHub

---

## 📜 Licenza

MIT
