# 📁 Archivio Famiglia (Home Assistant Add-on)

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

<p align="center">
Supportare il progetto significa aiutare lo sviluppo di nuove funzionalità 🚀
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

Dopo l’installazione, configura:

* **db_host** → `core-mariadb`
* **db_name** → `homeassistant` (o altro DB)
* **db_user** → `homeassistant`
* **db_pass** → password MariaDB

---

## 🧠 Primo avvio (Wizard automatico)

Al primo avvio:

✔ crea automaticamente:

* Tabelle database
* Categorie base
* Documento PDF demo
* Primo utente amministratore

👉 Ti verrà chiesto solo:

* username
* password

---

## 📂 Storage

I file vengono salvati in:

```
/share/archivio
```

Questo significa:

* accessibili da Samba
* persistenti anche dopo aggiornamenti
* non vengono cancellati disinstallando l’add-on

---

## 🔐 Sicurezza

* Password hashate (bcrypt)
* Accesso controllato utenti attivi
* Link temporanei con scadenza

---

## ⚠️ Note importanti

* Se reinstalli l'add-on:

  * ❌ NON perdi i dati se non cancelli `/share`
  * ❌ NON perdi DB se non lo resetti

* Il wizard si avvia solo se:

  * database vuoto
  * nessun utente presente

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
* Vanilla JS + CSS custom

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

Se ti piace questo progetto:

👉 offri un caffè
👉 condividilo
👉 proponilo ad aziende

<p align="center">
  <a href="https://www.paypal.com/paypalme/simoncinoprojects" target="_blank">
    <img src="https://img.shields.io/badge/Supporta%20il%20progetto-PayPal-ffdd00?style=for-the-badge&logo=paypal&logoColor=black" />
  </a>
</p>

---

## 📜 Licenza

MIT License
