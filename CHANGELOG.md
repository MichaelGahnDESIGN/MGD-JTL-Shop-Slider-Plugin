# Änderungen

## 0.1.0 (Pre-Release)

- Läuft im JTL-Shop 5.8.2 (Demo-Shop): installieren, aktivieren, Slider anlegen und ausgeben.
- Admin-Reiter "Slider": Liste, anlegen, bearbeiten, löschen (Klassisch mit Slides, Prime mit Quelle und Anzahl).
- Prime-Abfrage ist jetzt echt: zufällig, meistverkauft, Kategorie (mit Unterkategorien) oder manuell. Preis und Bilder kommen aus der JTL-Artikelklasse.
- Limit Free: 5 Slider, der 6. wird mit deutscher Meldung abgelehnt (Prüfung auf dem Server).
- Barrierefrei: Tastatur, Pausenknopf bei Autoplay, reduzierte Bewegung, gesperrte Slides für Screenreader.
- info.xml bereinigt (MinShopVersion 5.8.2, XMLVersion 100), nicht benötigtes Skript `mgd-slider-model.js` entfernt.
- Button-Höhe im Slider korrigiert.
- Test-Skript `tests/smoke-jtl.php` für den Shop.
- Noch offen: Lizenzcode-Prüfung (Pro), Browser-Test im Shop-Frontend und im Backend-Reiter, andere JTL-Versionen.

## 0.1.0 Gerüst (intern, nicht veröffentlicht)

- Slider-Skript mit Tastatur, Wischen, Pausenknopf, reduzierte Bewegung. Datenmodell Klassisch und Prime.
