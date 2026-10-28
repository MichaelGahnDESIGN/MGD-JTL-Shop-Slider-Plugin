# MGD JTL-Shop 5 Slider Plugin

> Pre-Alpha 0.1.0. Das Plugin ist ein frühes Gerüst. Es ist noch nicht für echte Shops gedacht.

Ein "Super-Slider" für JTL-Shop 5 von Michael Gahn DESIGN. Zwei Arten:

- **Klassisch:** Du legst Slides selbst an (nur Bild oder Bild mit Text und Button).
- **Prime:** Die Slides entstehen automatisch aus deinen Shop-Artikeln. Du stellst nur ein, wie viele Slides es sein sollen. Quelle: zufällig, meistverkauft, Kategorie oder manuell (du wählst Artikel, daraus wird zufällig gezeigt).

In der kostenlosen Version kannst du bis zu 5 Slider anlegen. Mit einer Pro-Lizenz sind es unbegrenzt viele.

## Was schon geht und was nicht

- ☑️ Nur Syntax geprüft (php -l, xmllint). Noch nie in einem echten JTL-Shop gelaufen.
- ☑️ Enthalten: info.xml, Admin-Tabs, Tabelle, Limit-Prüfung, OPC-Portlet (Struktur nach Doku, zu prüfen).
- ☑️ Prime liefert noch keine Artikel (Datenabfrage ist ein Platzhalter), Slides lassen sich noch nicht bearbeiten.

## Installation, Schritt für Schritt

1. Ordner `mgd_slider` nach `<Shop>/plugins/` kopieren.
2. Im JTL-Backend unter Plugins: Plugin installieren und aktivieren.
3. Im OPC-Editor das Portlet "MGD Slider" nutzen. (Pre-Alpha: noch ungetestet)

## Slider-Regeln für Barrierefreiheit

- Es gibt einen Pausenknopf, sobald sich der Slider von selbst bewegt.
- Bedienung mit Tastatur: Pfeiltasten links und rechts, Pos1 und Ende.
- Wer "Bewegung reduzieren" im System eingestellt hat, bekommt keinen automatischen Wechsel.
- Keine externen Schriften und kein CDN: Alles liegt im Plugin (Datenschutz).

## Aufbau (Kurzfassung)

Der Kern (Slider-Skript, Stil, Auswahl-Logik) ist auf allen Plattformen gleich und wird aus einem gemeinsamen Quellordner erzeugt. Nur die Anbindung an JTL-Shop 5 ist je Plattform anders. Den ganzen Plan gibt es im privaten Hauptprojekt (docs/SLIDER-PLUGINS.md).

## Hilfe und Fehler melden

Bitte als Issue in diesem Repository melden. Das ist keine Rechtsberatung: Die Lizenzfrage wird noch geprüft (siehe LICENSE).
