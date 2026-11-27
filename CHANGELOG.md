# Änderungen

## [2.1.4] - 2026-11-27

- Kit 1.16.1: inaktive Plugins in der Übersicht, Weitere Plugins vollständig.

## [2.1.1] - 2026-11-25

- Aktueller Baukasten, einheitliche Version.

## 1.6.0

- Brückenversion: Das Plugin nutzt jetzt den gemeinsamen Baukasten von theme.michael-gahn.de (Ordner `Kit/`).
- Neuer Reiter „Übersicht“ mit Info, Updates, Weitere Plugins und dem Schalter „Installation melden“. Er ersetzt den alten Reiter „Updates“. Neuer Reiter „Weitere Plugins“.
- Der alte Update-Code im Ordner `src/Update` und `src/Install` wird nicht mehr angemeldet. Er bleibt noch liegen und fliegt in der Endversion raus.
- Die Installation meldet sich bei Installieren, Aktivieren und Update, dann höchstens einmal am Tag im Admin. Beim Deinstallieren wird der Eintrag auf der Plattform gelöscht.
- Die alte Installations-Kennung wird automatisch übernommen. Die Einstellungen bleiben.
- Beim Update meldet die Installation gleich die neue Version (nicht erst am nächsten Tag).
- Der Slider selbst ist unverändert.

## 1.5.1

- Fix: Beim Update hat die Install-Meldung noch die alte Versionsnummer geschickt (das Plugin-Objekt kennt in dem Moment noch die alte Version). Die Version kommt jetzt direkt aus der `info.xml`.

## 1.5.0

- Neu: Install-Meldung (Free-Lizenz). Meldet sich höchstens einmal am Tag bei theme.michael-gahn.de (Plugin, Version, Shopsystem, Domain, zufällige ID). Abschaltbar in den Einstellungen.
- Gemeldet wird beim Installieren, Aktivieren und Aktualisieren, danach höchstens einmal am Tag im Admin. Im Shop-Frontend wird nie gemeldet.
- Der Schalter steht im Reiter „Updates“. Die Meldung läuft auch, wenn die Update-Prüfung aus ist. Abgeschaltet wird der Eintrag auf der Plattform gelöscht.
- Der Slider läuft immer voll weiter, auch ohne Plattform.
- Update Client neu erzeugt (mit Schalter für die Install-Meldung). Neuer Test: `tests/install-smoke.php`.

## 1.4.0

- Neue Versionsnummer 1.4.0: Alle MGD-Produkte haben jetzt die gleiche Version.
- Slider-Kern abgeglichen: Text auf dem Bild besser lesbar (stärkerer Verlauf, leichter Schatten), auf dem Handy eine feste Höhe, damit kein Text abgeschnitten wird. Beschriftungen der Knöpfe lassen sich übersetzen (sonst Deutsch).
- Update Client neu erzeugt: Nach dem Download nennt die Meldung im einfachen Modus nur, was wirklich geprüft wurde (SHA-256-Prüfsumme und Inhalt, keine Signatur).
- Pro-Lizenz: gibt es noch nicht, kommt als nächstes.

## 0.1.3

- Update Client neu erzeugt (aus MichaThemeV5-Pro): Der veraltete Aufruf `curl_close()` ist entfernt. Unter PHP 8.5 gab er bei jeder Update-Prüfung eine Deprecated-Warnung.
- Generator im Pro-Repo mit dem Plugin abgeglichen: Button-Höhe im Kern-CSS übernommen, das nicht benötigte `mgd-slider-model.js` kommt nicht mehr ins JTL-Paket. Das Plugin selbst bleibt dabei gleich.
- Erstes Release, das der Bereich "Updates" in 0.1.2 als neue Version anzeigen kann.
- Tests: `tests/update-smoke.php` grün, keine PHP-Syntaxfehler.

## 0.1.2 (Pre-Release)

- Wartungs-Release: Der Update-Client-Zweig ist jetzt in `main`. Am Slider selbst hat sich nichts geändert.
- Im Demo-Shop eingespielt. Dort wird damit auch der Weg von 0.1.0 auf die neue Version mit der Update-Anzeige geprüft.
- Tests: `tests/update-smoke.php` grün, keine PHP-Syntaxfehler.

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
