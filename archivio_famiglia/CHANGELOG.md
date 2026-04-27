# 📜 Changelog

All notable changes to **FamilyDocs (ex Archivio Famiglia)** will be documented in this file.

---

## [1.1.2] - 2026-04-27

### Added

* 🌍 Multilanguage support (Italian / English)
* 🌙☀️ Theme toggle (Dark / Light)
* Language persistence via session + cookie

### Improved

* README fully internationalized
* UI consistency across pages
* Add-on metadata and naming aligned to **FamilyDocs**

### Fixed

* Fix language switch not persisting across pages
* Fix CSS inconsistencies (layout + pills)
* Minor UI alignment issues

---

## [1.1.1] - 2026-04-26

### Improved

* General Home Assistant add-on stabilization
* Improved upload security (basename, category validation)
* Better upload error handling
* Improved `/share/archivio` path handling
* Improved mobile compatibility

### Fixed

* Fix path traversal (upload/download)
* Fix public link generation
* Fix add-on config reading (`addon_options.json`)
* Minor UI fixes (light/dark theme)

---

## [1.1.0] - 2026-04-25

### Added

* 📷 Smartphone camera upload
* Dual upload fields:

  * file/PDF
  * take document photo

### Improved

* Mobile-optimized upload form
* Clearer UX for document upload

---

## [1.0.9] - 2026-04-25

### Added

* Info page
* Archive statistics:

  * total documents
  * total users
  * used space
* GitHub link
* PayPal support link

### Improved

* Project branding (SimoncinoProjects)
* README with logo and screenshots
* Documentation improvements

---

## [1.0.8] - 2026-04-25

### Added

* Telemetry system (disabled by default)

---

## [1.0.7] - 2026-04-25

### Fixed

* Installation wizard fix on empty database
* MariaDB compatibility for `SHOW TABLES LIKE`

---

## [1.0.6] - 2026-04-25

### Improved

* Robust installation detection:

  * wizard runs if users table missing
  * or users table empty

---

## [1.0.5] - 2026-04-25

### Added

* First setup wizard
* Automatic creation:

  * database tables
  * default categories
  * admin user
  * demo PDF

---

## [1.0.4] - 2026-04-25

### Fixed

* MariaDB config reading from add-on

---

## [1.0.3] - 2026-04-25

### Fixed

* Database connection issues

---

## [1.0.2] - 2026-04-25

### Fixed

* Add-on port mapping (Apache)

---

## [1.0.1] - 2026-04-25

### Fixed

* `run.sh` startup issue

---

## [1.0.0] - 2026-04-25

### Initial release

* Home Assistant add-on
* PHP + Apache + MariaDB
* Document upload
* Categories management
* Users management
* Backup system
* Document preview
