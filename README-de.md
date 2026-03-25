# Asset Trash for Craft CMS 5

**[English](README.md)** | **[日本語](README-ja.md)** | Deutsch

Ein Craft CMS 5 Plugin, das einen Papierkorb fuer geloeschte Assets bereitstellt. Beim Loeschen eines Assets wird die Datei in ein `.trash/`-Verzeichnis innerhalb des Volumes kopiert und ein Datenbankeintrag gespeichert, sodass das Asset spaeter ueber das Control Panel wiederhergestellt oder endgueltig geloescht werden kann.

## Voraussetzungen

- Craft CMS 5.0.0 oder hoeher
- PHP 8.2 oder hoeher

## Installation

### Ueber Composer (empfohlen)

```bash
composer require bit-part/craft-asset-trash
```

Anschliessend das Plugin im Craft Control Panel unter **Einstellungen > Plugins** installieren oder ueber die CLI:

```bash
php craft plugin/install asset-trash
```

### Manuelle Installation

1. Release von [GitHub](https://github.com/bit-part/craft-asset-trash) herunterladen
2. Den Inhalt in ein Verzeichnis legen und ein [Path-Repository](https://getcomposer.org/doc/05-repositories.md#path) in der `composer.json` des Projekts hinzufuegen
3. `composer require bit-part/craft-asset-trash` ausfuehren
4. Ueber das Control Panel oder die CLI installieren

## Funktionsweise

Wenn ein Asset in Craft geloescht wird:

1. Das Plugin faengt das Loeschereignis ab
2. Die Datei wird in ein `.trash/`-Verzeichnis innerhalb des gleichen Volumes **kopiert** (z.B. `uploads/.trash/`)
3. Ein Datenbankeintrag mit Metadaten wird erstellt (Dateiname, Groesse, Volume, Pfad, wer geloescht hat, und Element-Referenzen zum Zeitpunkt der Loeschung)
4. Der normale Loeschvorgang von Craft wird fortgesetzt (das urspruengliche Asset-Element wird entfernt)

Die geloeschte Datei bleibt in `.trash/`, bis sie wiederhergestellt oder endgueltig geloescht wird.

## Funktionen

### Papierkorb-Uebersicht

Im Control Panel erscheint ein eigener **Asset Trash**-Bereich in der Hauptnavigation. Die Uebersicht zeigt:

- Dateiname (Link zur Detailansicht)
- Dateigroesse
- Wer die Datei geloescht hat
- Loeschdatum
- Anzahl der Element-Referenzen zum Zeitpunkt der Loeschung

### Volume-Filter

Bei mehreren Volumes kann ueber ein Dropdown-Filter nach einem bestimmten Volume oder allen Volumes gefiltert werden.

### Detailansicht

Durch Klick auf einen Dateinamen werden alle Metadaten angezeigt:

- Dateiname, Art und Dateigroesse
- Volume-Name und urspruenglicher Pfad (Volume-Basispfad + Dateiname)
- Titel und Alt-Text (falls gesetzt)
- Geloescht-von-Benutzer und Loeschdatum
- Urspruengliche Asset-Element-ID
- Interner Papierkorb-Pfad
- Tabelle der Element-Referenzen zum Zeitpunkt der Loeschung (Quell-Element-ID, Typ und Feld-ID)

### Wiederherstellen

Stellt ein geloeschtes Element in seinem urspruenglichen Volume und Ordner wieder her. Das Plugin erstellt ein neues Asset-Element und verschiebt die Datei aus `.trash/` zurueck an den urspruenglichen Speicherort. Falls eine Datei mit dem gleichen Namen bereits existiert, wird automatisch ein eindeutiger Suffix hinzugefuegt.

### Endgueltig loeschen

Entfernt ein geloeschtes Element dauerhaft. Die Datei wird aus `.trash/` geloescht und der Datenbankeintrag entfernt. Dieser Vorgang kann nicht rueckgaengig gemacht werden.

### Massenaktionen

Mehrere Eintraege ueber die Kontrollkaestchen auswaehlen und dann mit **Ausgewaehlte wiederherstellen** oder **Ausgewaehlte loeschen** alle auf einmal bearbeiten.

### Papierkorb leeren

Die Schaltflaeche **Papierkorb leeren** loescht alle Eintraege im Papierkorb (oder alle Eintraege des aktuell gefilterten Volumes) endgueltig. Vor der Ausfuehrung wird ein Bestaetigungsdialog angezeigt.

### Automatisches Bereinigen

Abgelaufene Eintraege koennen waehrend der Garbage Collection von Craft automatisch bereinigt werden. Der Aufbewahrungszeitraum und die automatische Bereinigung werden in den Plugin-Einstellungen konfiguriert.

## Einstellungen

Unter **Einstellungen > Plugins > Asset Trash** konfigurierbar:

| Einstellung | Standard | Beschreibung |
|-------------|----------|--------------|
| **Aufbewahrungstage** | `30` | Anzahl der Tage, die geloeschte Eintraege vor der automatischen Bereinigung aufbewahrt werden. `0` fuer unbegrenzte Aufbewahrung. |
| **Automatisches Bereinigen** | `Ein` | Wenn aktiviert, werden abgelaufene Eintraege waehrend der Garbage Collection von Craft automatisch geloescht. |
| **Papierkorb-Verzeichnisname** | `.trash` | Der Verzeichnisname innerhalb jedes Volumes, in dem geloeschte Dateien gespeichert werden. Es sind nur Buchstaben, Zahlen, Punkte, Bindestriche und Unterstriche erlaubt. |

## Berechtigungen

Das Plugin registriert vier Berechtigungen unter **Asset Trash**:

| Berechtigung | Beschreibung |
|--------------|--------------|
| **Papierkorb anzeigen** | Zugriff auf den Asset Trash-Bereich im Control Panel |
| **Assets wiederherstellen** | Geloeschte Eintraege an ihren urspruenglichen Speicherort zuruecksetzen |
| **Assets endgueltig loeschen** | Einzelne geloeschte Eintraege dauerhaft entfernen |
| **Papierkorb leeren** | Alle Eintraege auf einmal ueber die Schaltflaeche "Papierkorb leeren" loeschen |

Die Berechtigungen sind verschachtelt: Die drei Aktionsberechtigungen setzen die Berechtigung "Papierkorb anzeigen" voraus.

## Uebersetzungen

Das Plugin enthaelt Uebersetzungen fuer:

- Englisch (`en`)
- Japanisch (`ja`)

## Support

- [GitHub Issues](https://github.com/bit-part/craft-asset-trash/issues)
- [Dokumentation](https://github.com/bit-part/craft-asset-trash)

## Lizenz

Dieses Plugin ist unter der [MIT-Lizenz](LICENSE.md) lizenziert.

---

Entwickelt von [bit part LLC](https://bit-part.net)
