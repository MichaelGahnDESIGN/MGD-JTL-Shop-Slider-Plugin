# MGD Slider für JTL-Shop 5

> Release 1.4.0. Läuft im JTL-Shop 5.8.2 (Demo-Shop). Andere Versionen sind nicht getestet.

Mit diesem Plugin baust du einen Bild-Slider für deinen JTL-Shop. Es gibt zwei Arten:

- **Klassisch:** Du legst die Slides selbst an. Eine Slide ist ein Bild, optional mit Titel, Text und Button.
- **Prime:** Die Slides entstehen von allein aus deinen Shop-Artikeln. Du sagst nur, woher die Artikel kommen und wie viele Slides es sein sollen.
  - Zufällig
  - Meistverkauft (gibt es noch keine Verkäufe, werden zufällige Artikel genommen)
  - Aus Kategorien (Unterkategorien zählen mit)
  - Manuell: Du nennst Artikel-ID oder Artikelnummer, daraus wird zufällig gewählt

Es gibt nie einen Artikel ohne Bild, nie einen nicht lieferbaren Artikel und nie ein Variations-Kind.

## Kostenlos und Pro

- Kostenlos: bis zu **5 Slider**. Beim 6. Slider kommt die Meldung "Das Limit von 5 Slidern ist erreicht. Mit einer Pro-Lizenz sind es unbegrenzt viele."
- Pro: unbegrenzt viele Slider. ☑️ Die Prüfung des Lizenzcodes ist in dieser Version noch nicht eingebaut.

## Installation, Schritt für Schritt

1. Lade die Datei `mgd_slider-1.4.0.zip` von der Release-Seite herunter.
2. Melde dich im JTL-Shop-Backend an.
3. Gehe zu **Plugins > Plugin-Verwaltung**.
4. Wähle **Hochladen** und nimm die ZIP-Datei. (Alternativ: den Ordner `mgd_slider` per FTP nach `<Shop>/plugins/` kopieren.)
5. Gehe auf den Reiter **Vorhanden**, setze bei "MGD Slider" ein Häkchen und klicke auf **Installieren**.
6. Wechsle auf den Reiter **Aktiviert/Installiert** und aktiviere das Plugin.

## Updates

Im Plugin gibt es den Reiter **Updates**. Dort siehst du, ob es eine neue Version gibt.

So geht es, Schritt für Schritt:

1. Backend > Plugins > "MGD Slider" > Reiter **Updates**.
2. Setze das Häkchen **Update-Prüfung einschalten** und klicke auf **Speichern**. Ohne Häkchen ruft das Plugin nichts ab.
3. Oder klicke einfach auf **Jetzt nach Updates suchen**. Das gilt nur für diesen einen Abruf.
4. Gibt es ein Update, setze das Häkchen bei der Bestätigung und lade das Paket herunter. Das Plugin prüft es (Signatur, Prüfsumme, Inhalt).
5. Speichere die geprüfte ZIP-Datei. Mache vorher eine Datensicherung.
6. Lade die ZIP in der **JTL-Plugin-Verwaltung** hoch. JTL macht das Update.

Wichtig:

- Das Plugin installiert **nie** selbst. Es tauscht keine Dateien aus und aktiviert nichts.
- Beim Prüfen sieht der Update-Server die IP-Adresse deines Servers. Es werden keine Besucher-, Kunden- oder Bestelldaten gesendet.
- Der Zustand liegt in `<Shop>/templates_c/mgd_slider_update/` (mit Zugriffssperre für Apache; bei nginx bitte den Ordner selbst sperren).
- ⚠️ Der Update-Kanal arbeitet im einfachen Modus: Eine kleine Textdatei auf theme.michael-gahn.de (`plugin-updates/mgd-slider.json`) nennt Version und SHA-256-Prüfsumme, das ZIP kommt von GitHub, die Prüfsumme wird beim Download nachgerechnet. Das ist eine Prüfsumme, keine Signatur.
- Details zum Update-Client: `docs/JTL-UPDATE-CLIENT.md` im Pro-Repository.

## Einen Slider anlegen

1. Backend > Plugins > "MGD Slider" > Reiter **Slider**.
2. Klicke auf **Neuen Slider anlegen**.
3. Gib einen Namen ein und wähle **Klassisch** oder **Prime**.
4. Klassisch: Trage in den Zeilen Bild, Alt-Text, Titel, Text und Button ein. Leere Zeilen werden ignoriert.
5. Prime: Wähle die Quelle und die Anzahl.
6. Klicke auf **Speichern**.

## Slider auf einer Seite zeigen

1. Öffne die Seite im OPC-Editor (Online-Page-Composer).
2. Ziehe das Portlet **MGD Slider** in einen Bereich.
3. Wähle im Portlet deinen Slider aus.
4. Speichere und veröffentliche die Seite.

## Barrierefreiheit

- Mit der Tastatur: Pfeil links und rechts, Pos1 und Ende. Die Pfeile, Punkte und der Pausenknopf sind echte Knöpfe mit Beschriftung.
- Wechselt der Slider von selbst, gibt es einen Pausenknopf. Der Wechsel stoppt auch bei Maus darüber und bei Tastatur-Fokus.
- Wer im System "Bewegung reduzieren" eingestellt hat, bekommt keinen automatischen Wechsel und keine Animation.
- Nicht sichtbare Slides sind für Screenreader und Tab-Taste gesperrt.
- Keine externen Schriften und kein CDN. Alles liegt im Plugin.

## Was geprüft ist und was nicht

✅ Geprüft im Demo-Shop (JTL-Shop 5.8.2):

- Plugin lässt sich installieren und aktivieren, Tabelle wird angelegt, Portlet wird registriert.
- Slider anlegen, ändern, löschen. Limit von 5. Alles per Test-Skript `tests/smoke-jtl.php`.
- Prime-Abfrage mit allen 4 Quellen gegen die echten Demo-Artikel.
- Echtes Shop-Frontend im Browser (Startseite mit Portlet, Theme MichaThemeV5, 375 und 1280 Pixel breit): Slider klassisch und Prime werden angezeigt, Plugin-CSS und -JS sind eingebunden, Tastatur, Pausenknopf und Pfeile funktionieren, kein seitliches Scrollen.
- Reduzierte Bewegung: im Browser nur simuliert (nicht über die System-Einstellung).

☑️ Noch nicht geprüft:

- Andere Templates als MichaThemeV5 (zum Beispiel reines NOVA).
- Der Admin-Reiter im Browser (dafür ist ein Backend-Login nötig). Nur der Code dahinter ist getestet.
- Das Platzieren im OPC-Editor mit der Maus.
- Andere JTL-Versionen als 5.8.2.
- Lizenzcode-Prüfung für Pro.

## Entwicklung

Der Kern (`src/Core`: Slider-Daten, Auswahl, HTML) ist auf allen Plattformen gleich und wird aus einem gemeinsamen Quellordner erzeugt. Nur die Anbindung an JTL (`Bootstrap.php`, `src/AdminPage.php`, `src/PrimeCandidates.php`, `Portlets/`) ist hier von Hand geschrieben.

Test im Shop (auf dem Server, per CLI):

```
php tests/smoke-jtl.php /pfad/zum/shop https://shop-adresse
```

Das Skript legt Test-Slider an und räumt sie wieder auf.

Test für den Reiter Updates (ohne JTL, ohne Netz):

```
php tests/update-smoke.php
```

Der Update-Client in `src/Update` wird vom Generator erzeugt (`bin/build-update-client.php` im Pro-Repository). Bitte nicht von Hand ändern.

## Hilfe und Fehler melden

Bitte als Issue in diesem Repository melden. Das ist keine Rechtsberatung: Die Lizenzfrage wird noch geprüft (siehe LICENSE).
