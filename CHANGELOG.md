# Änderungen

## 0.1.1 (Pre-Release)

- Neuer Reiter "Updates" im Backend (MGD Update Client). Update prüfen, Paket herunterladen und prüfen, dann in der JTL-Plugin-Verwaltung installieren. Das Plugin installiert nie selbst.
- Update-Prüfung nur nach Einschalten oder Klick, nur mit Rechten und CSRF-Token.
- Der Update-Kanal arbeitet im einfachen Modus: Eine kleine Textdatei auf theme.michael-gahn.de (`plugin-updates/mgd-slider.json`) nennt Version und SHA-256-Prüfsumme, das ZIP kommt von GitHub, die Prüfsumme wird beim Download nachgerechnet. Das ist eine Prüfsumme, keine Signatur.
- Neues Test-Skript `tests/update-smoke.php`.

## 0.1.0 (Pre-Release)

- Läuft im JTL-Shop 5.8.2 (Demo-Shop): installieren, aktivieren, Slider anlegen und ausgeben.
- Admin-Reiter "Slider": Liste, anlegen, bearbeiten, löschen (Klassisch mit Slides, Prime mit Quelle und Anzahl).
- Prime-Abfrage ist jetzt echt: zufällig, meistverkauft, Kategorie (mit Unterkategorien) oder manuell. Preis und Bilder kommen aus der JTL-Artikelklasse.
- Limit Free: 5 Slider, der 6. wird mit deutscher Meldung abgelehnt (Prüfung auf dem Server).
- Barrierefrei: Tastatur, Pausenknopf bei Autoplay, reduzierte Bewegung, gesperrte Slides für Screenreader.
- info.xml bereinigt (MinShopVersion 5.8.2, XMLVersion 100), nicht benötigtes Skript `mgd-slider-model.js` entfernt.
- Button-Höhe im Slider korrigiert.
- Test-Skript `tests/smoke-jtl.php` für den Shop.
- Im echten Shop-Frontend geprüft (Browser, schmal und breit). Noch offen: Lizenzcode-Prüfung (Pro), Browser-Test im Backend-Reiter und im OPC-Editor, andere JTL-Versionen.

## 0.1.0 Gerüst (intern, nicht veröffentlicht)

- Slider-Skript mit Tastatur, Wischen, Pausenknopf, reduzierte Bewegung. Datenmodell Klassisch und Prime.
