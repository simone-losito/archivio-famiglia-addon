# 📁 Archivio Famiglia (Home Assistant Add-on)
<p align="center">

<img src="https://img.shields.io/badge/version-1.0.8-blue?style=for-the-badge">
<img src="https://img.shields.io/badge/Home%20Assistant-Addon-41BDF5?style=for-the-badge&logo=homeassistant&logoColor=white">
<img src="https://img.shields.io/badge/status-stable-success?style=for-the-badge">
<img src="https://img.shields.io/badge/license-MIT-green?style=for-the-badge">

</p>


<p align="center">
  <img src="assets/logo.png" width="180">
</p>

<p align="center">
<b>by SimoncinoProjects / Simone Losito</b>
</p>

<p align="center">
Archivio documentale familiare completo, integrato in Home Assistant.
</p>

<p align="center">
Gestisci documenti, categorie, utenti e link temporanei direttamente da browser, con un'interfaccia semplice, veloce e moderna.
</p>

---

## ☕ Supporta il progetto

<p align="center">
  <a href="https://www.paypal.com/paypalme/simoncinoprojects" target="_blank">
    <img src="https://img.shields.io/badge/☕%20Offrimi%20un%20caffè-PayPal-blue?style=for-the-badge&logo=paypal" />
  </a>
</p>

---

## 🖼️ Anteprima

### 🌙 Tema Dark

<p align="center">
  <img src="assets/screens/dashboard-dark.png" width="900">
</p>

---

### ☀️ Tema Light

<p align="center">
  <img src="assets/screens/dashboard-light.png" width="900">
</p>

---

### 📂 Gestione categorie

<p align="center">
  <img src="assets/screens/categorie.png" width="900">
</p>

---

## ✨ Funzionalità principali

* 📂 Gestione categorie (Cartelle cliniche, Referti, Casa, Auto, ecc.)
* 📄 Upload documenti con anteprima
* ⭐ Sistema preferiti
* 👥 Multi utente con ruoli
* 🔗 Link temporanei condivisibili
* 🧾 Tag e note documenti
* 🗂️ Archivio organizzato per categoria
* 💾 Backup locale
* 🎨 UI dark moderna
* ⚡ Integrato in Home Assistant
* 📷 Upload da fotocamera smartphone

---

## 🚀 Installazione

### Metodo 1 – Repository personalizzato

1. Vai in **Home Assistant → Add-on Store**
2. Clicca sui 3 puntini → **Repository**
3. Inserisci:

```
https://github.com/simone-losito/archivio-famiglia-addon
```

4. Installa **Archivio Famiglia**

---

## ⚙️ Configurazione

* **db_host** → `core-mariadb`
* **db_name** → `homeassistant`
* **db_user** → `homeassistant`
* **db_pass** → password MariaDB

---

## 🧠 Primo avvio (Wizard automatico)

✔ crea automaticamente:

* Tabelle database
* Categorie base
* Documento PDF demo
* Primo utente amministratore

👉 Inserisci solo:

* username
* password

---

## 📂 Storage

```
/share/archivio
```

✔ accessibile da rete
✔ persistente
✔ NON viene cancellato

---

## 🔐 Sicurezza

* Password hashate (bcrypt)
* Controllo utenti attivi
* Link temporanei con scadenza

---

## ⚠️ Note importanti

* Reinstallando:

  * ❌ NON perdi file
  * ❌ NON perdi database (se non lo resetti)

* Il wizard parte solo se:

  * database vuoto
  * nessun utente

---

## 🧪 Porte

* Container: `8090`
* Host: configurabile (es: `8091`)

---

## 🛠️ Tecnologie

* PHP 8.2
* Apache
* MariaDB
* Docker (Home Assistant Add-on)

---

## 📌 Roadmap

* [ ] Upload multiplo
* [ ] Ricerca avanzata
* [ ] Notifiche Home Assistant
* [ ] App mobile
* [ ] OCR documenti
* [ ] Ruoli avanzati

---

## 👨‍💻 Autore

**Simone Losito**
SimoncinoProjects

---

## ☕ Supporta il progetto

<p align="center">
  <a href="https://www.paypal.com/paypalme/simoncinoprojects" target="_blank">
    <img src="https://img.shields.io/badge/Supporta%20il%20progetto-PayPal-ffdd00?style=for-the-badge&logo=paypal&logoColor=black" />
  </a>
</p>

## ⭐ Se ti piace il progetto

Lascia una ⭐ su GitHub
Condividilo
Supporta lo sviluppo 🚀


---

## 📜 Licenza

MIT License
