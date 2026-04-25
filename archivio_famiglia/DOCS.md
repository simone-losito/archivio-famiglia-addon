# 📁 Archivio Famiglia – Guida Rapida

Benvenuto 👋  
Questo add-on ti permette di gestire un archivio documentale familiare direttamente dentro Home Assistant.

---
## ☕ Supporta il progetto

Se questo progetto ti è utile e vuoi supportarlo:

<p align="center">
  <a href="https://www.paypal.com/paypalme/simoncinoprojects" target="_blank">
    <img src="https://img.shields.io/badge/☕%20Offrimi%20un%20caffè-PayPal-blue?style=for-the-badge&logo=paypal" />
  </a>
</p>

<p align="center">
Supportare il progetto significa aiutare lo sviluppo di nuove funzionalità 🚀
</p>

🔗 Repository ufficiale

Puoi trovare il codice qui:

👉 https://github.com/simone-losito/archivio-famiglia-addon

## 🚀 Primo utilizzo

Al primo avvio vedrai una schermata di configurazione.

Ti verrà chiesto di creare:

- 👤 Nome utente
- 🔐 Password

Una volta fatto, il sistema creerà automaticamente:

- Database
- Categorie base
- Documento di prova
- Account amministratore

---

## 🧭 Interfaccia

Dopo il login troverai:

### 📂 Categorie
Organizza i tuoi documenti per tipo:
- Cartelle cliniche
- Referti
- Casa
- Auto
- Altro

Puoi modificarle o crearne di nuove.

---

### 📄 Documenti

Puoi:

- Caricare file
- Aggiungere titolo
- Inserire note
- Aggiungere tag
- Segnarli come ⭐ preferiti

---

### 👥 Utenti

Puoi creare più utenti per la tua famiglia.

---

### 🔗 Condivisione

Puoi generare link temporanei per condividere documenti.

---

### 💾 Backup

Puoi esportare i dati dal sistema.

---

## 📂 Dove sono salvati i file?

Tutti i documenti vengono salvati in:
/share/archivio

👉 Questo significa che:
- non vengono persi
- sono accessibili anche da PC (Samba)
- restano anche dopo aggiornamenti

---

## ⚠️ Se qualcosa non funziona

Controlla:

- configurazione database
- add-on MariaDB attivo
- password corretta

---

## 🔄 Reinstallazione

Se reinstalli l’add-on:

- ❌ NON perdi i file
- ❌ NON perdi il database (se non lo cancelli)

---

# 🔧 (Sezione tecnica – opzionale)

## Database

- Motore: MariaDB
- Tabelle create automaticamente:
  - utenti
  - categorie
  - documenti
  - share_links

---

## Porte

- Interna: 8090
- Esterna: configurabile

---

## Storage

- `/share/archivio` → file
- DB → MariaDB

---

## Architettura

- PHP + Apache
- Docker Add-on Home Assistant
- Volume persistente `/share`

---

## 🧠 Note

Il wizard iniziale parte SOLO se:

- database vuoto
- nessun utente presente

---

## ☕ Supporta il progetto

Se ti è utile:

👉 offri un caffè  
👉 condividilo  

---
## ☕ Supporta il progetto

Se questo progetto ti è utile e vuoi supportarlo:

<p align="center">
  <a href="https://www.paypal.com/paypalme/simoncinoprojects" target="_blank">
    <img src="https://img.shields.io/badge/☕%20Offrimi%20un%20caffè-PayPal-blue?style=for-the-badge&logo=paypal" />
  </a>
</p>

<p align="center">
Supportare il progetto significa aiutare lo sviluppo di nuove funzionalità 🚀
</p>

## 👨‍💻 Autore

Simone Losito
