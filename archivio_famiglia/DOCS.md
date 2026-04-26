# 📁 Archivio Famiglia – Guida Rapida

Benvenuto 👋  
Questo add-on ti permette di gestire un archivio documentale familiare direttamente dentro Home Assistant.

---

## ☕ Supporta il progetto

<p align="center">
  <a href="https://www.paypal.com/paypalme/simoncinoprojects" target="_blank">
    <img src="https://img.shields.io/badge/☕%20Offrimi%20un%20caffè-PayPal-blue?style=for-the-badge&logo=paypal" />
  </a>
</p>

<p align="center">
Supportare il progetto significa aiutare lo sviluppo 🚀
</p>

🔗 Repository ufficiale  
👉 https://github.com/simone-losito/archivio-famiglia-addon

---

## 🚀 Primo utilizzo

Al primo avvio vedrai una schermata di configurazione.

Ti verrà chiesto di creare:

- 👤 Nome utente
- 🔐 Password

Il sistema creerà automaticamente:

- Database
- Tabelle
- Categorie base
- Documento PDF demo
- Account amministratore

---

## 🧭 Interfaccia

### 📂 Categorie
Organizza i documenti per tipo:

- Cartelle cliniche  
- Referti  
- Casa  
- Auto  
- Altro  

Puoi modificarle o crearne di nuove.

---

### 📄 Documenti

Puoi:

- Caricare file (PDF, immagini, ecc.)
- 📷 Scattare foto direttamente da smartphone
- Aggiungere titolo
- Inserire note
- Aggiungere tag
- Segnare come ⭐ preferiti
- Visualizzare anteprima
- Scaricare file

---

### 👥 Utenti

- Creazione utenti familiari
- Ruoli: admin / user
- Attivazione / disattivazione utenti

---

### 🔗 Condivisione

Puoi generare link temporanei per condividere documenti.

- accesso solo al singolo file
- scadenza automatica

---

### 💾 Backup

Puoi creare backup di:

- Database
- Documenti

Il sistema mantiene automaticamente gli ultimi backup.

---

## 📂 Dove vengono salvati i file

Tutti i documenti sono salvati in:

/share/archivio


✔ persistente  
✔ accessibile via rete (Samba)  
✔ non viene cancellato reinstallando l’add-on  

---

## ⚠️ Problemi comuni

Controlla:

- Add-on **MariaDB attivo**
- Credenziali corrette
- Configurazione DB corretta

---

## 🔄 Reinstallazione

Se reinstalli l’add-on:

- ❌ NON perdi i file
- ❌ NON perdi il database (se non lo cancelli manualmente)

---

# 🔧 Sezione tecnica

## Database

Motore: MariaDB

Tabelle:

- utenti
- categorie
- documenti
- share_links

---

## Porte

- Container: `80`
- Host: `8091` (configurabile)

---

## Storage

- `/share/archivio` → documenti
- `/data/options.json` → configurazione add-on
- MariaDB → database

---

## Architettura

- PHP 8.2
- Apache
- Docker
- Home Assistant Add-on

---

## 🧠 Note importanti

Il wizard iniziale parte SOLO se:

- database vuoto
- nessun utente presente

---

## 👨‍💻 Autore

**Simone Losito**  
SimoncinoProjects
