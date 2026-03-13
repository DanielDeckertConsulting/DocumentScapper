# MVP Spezifikation – Dokumenten-Upload, strukturierte Extraktion und Chat für Versicherungs- & Vertragsdokumente

## 1. Produktziel

Ziel des MVP ist ein schlankes, funktionsfähiges Webprodukt, mit dem Nutzer verschiedene Versicherungs- und Vertragsdokumente hochladen können, deren Inhalte automatisiert analysiert und strukturiert in einer Datenbank gespeichert werden. Anschließend sollen Nutzer in einem Chat gezielt Fragen zu den Dokumenten stellen können, um Informationen schnell wiederzufinden, Zusammenhänge zu verstehen und Vertragsdetails transparent auszulesen.

Das MVP soll bewusst klein gehalten werden, aber technisch so angelegt sein, dass später weitere Dokumenttypen, bessere Extraktionslogiken, Mandantenfähigkeit, Rollen, Freigaben und produktive Skalierung ergänzt werden können.

---

## 2. Problemstellung

Versicherungs- und Vertragsdokumente liegen häufig in unterschiedlichen Formaten, Strukturen und Schreibweisen vor. Wichtige Informationen wie Laufzeiten, Kündigungsfristen, Beitragshöhen, Versicherungsnehmer, Deckungssummen oder Ausschlüsse sind zwar enthalten, aber schwer vergleichbar und oft nur manuell auffindbar.

Das Produkt soll dieses Problem lösen, indem es:

* unstrukturierte Dokumente ingestiert,
* relevante Inhalte extrahiert,
* in ein einheitliches Datenmodell überführt,
* das Originaldokument referenzierbar hält,
* und eine Chat-Oberfläche bereitstellt, die auf diesen Daten plus Dokumentkontext grounded antwortet.

---

## 3. Zielgruppe im MVP

Primäre Zielgruppe im MVP:

* einzelne Endnutzer oder kleine Teams,
* die eigene Versicherungs- oder Vertragsdokumente verwalten wollen,
* und per natürlicher Sprache Informationen aus diesen Dokumenten abrufen möchten.

Später ausbaubar für:

* Makler,
* Kanzleien,
* Beratungen,
* Backoffice-Teams,
* B2B-Dokumentenservices.

---

## 4. MVP Scope

### Im Scope

* Benutzer kann sich anmelden
* Benutzer kann ein oder mehrere Dokumente hochladen
* System speichert Originaldatei
* System extrahiert Textinhalt aus dem Dokument
* System klassifiziert Dokument grob (z. B. Versicherung, Vertrag, Sonstiges)
* System extrahiert strukturierte Kernfelder
* System speichert extrahierte Daten in Datenbank
* System erstellt Chat-fähigen Wissenskontext pro Dokument
* Benutzer kann in einem Chat Fragen zu einzelnen oder mehreren Dokumenten stellen
* Antworten enthalten Bezug zum Quelldokument
* Benutzer kann Dokumente und Extraktionsstatus in einer Übersicht sehen

### Nicht im Scope für MVP

* Vollautomatische 100%-korrekte juristische Bewertung
* Bearbeiten der Dokumente im Browser
* OCR-Perfektion für schlechte Scans
* Mehrsprachigkeit
* Rollen-/Rechtesystem mit komplexen Freigaben
* Workflow-Automationen
* E-Mail-Import
* Mobile App
* Integrationen mit Versicherern oder Drittsystemen
* Rechnungsstellung / Payment

---

## 5. Kern-Use-Cases

### UC1 – Dokument hochladen

Als Nutzer möchte ich ein Versicherungs- oder Vertragsdokument hochladen, damit es analysiert und für spätere Fragen nutzbar gemacht wird.

### UC2 – Dokument verstehen

Als Nutzer möchte ich nach dem Upload strukturierte Kerndaten sehen, damit ich schnell erkenne, worum es im Dokument geht.

### UC3 – Details abfragen

Als Nutzer möchte ich im Chat Fragen zum Dokument stellen, damit ich Informationen wie Fristen, Beträge, Laufzeiten, Partner, Kündigungsbedingungen oder Ausschlüsse schnell finde.

### UC4 – Quellen nachvollziehen

Als Nutzer möchte ich sehen, auf welches Dokument bzw. welchen Dokumentabschnitt sich eine Antwort stützt, damit ich der Antwort vertrauen kann.

### UC5 – Mehrere Dokumente durchsuchen

Als Nutzer möchte ich mehrere Dokumente gleichzeitig befragen, damit ich Inhalte vergleichen oder Gesamtzusammenhänge verstehen kann.

---

## 6. Funktionale Anforderungen

## 6.1 Authentifizierung

MVP minimal:

* E-Mail/Passwort oder Google Sign-In
* jeder Nutzer sieht nur seine eigenen Dokumente und Chats
* saubere User-Isolation auf Datenbankebene vorbereiten

Akzeptanzkriterien:

* nicht eingeloggte Nutzer können keine Dokumente sehen oder hochladen
* eingeloggte Nutzer sehen ausschließlich eigene Daten

---

## 6.2 Dokumenten-Upload

Unterstützte Dateitypen im MVP:

* PDF
* optional DOCX in späterem MVP+1
* optional Bildformate nur wenn OCR sauber umsetzbar

Funktionen:

* Datei-Upload über UI
* Metadaten speichern: Dateiname, Dateityp, Größe, Upload-Zeitpunkt
* Ablage im Blob/Object Storage
* Start einer Verarbeitungspipeline nach Upload

Validierungen:

* Maximalgröße definieren, z. B. 20 MB pro Datei
* nur erlaubte MIME Types
* blockieren von leeren oder defekten Dateien

Statusmodell:

* uploaded
* processing
* processed
* failed

Akzeptanzkriterien:

* Upload zeigt Fortschritt oder klaren Ladezustand
* Nutzer sieht nach Upload sofort, dass Dokument verarbeitet wird
* Fehlerstatus ist sichtbar, falls Verarbeitung scheitert

---

## 6.3 Dokumentverarbeitung / Ingestion Pipeline

Die Verarbeitung soll in klaren Schritten erfolgen:

1. Datei speichern
2. Text extrahieren
3. Dokument klassifizieren
4. Strukturdaten extrahieren
5. Chunks für Chat/Retrieval erzeugen
6. Datenbankeinträge finalisieren

### 6.3.1 Textextraktion

Funktionen:

* PDF-Text auslesbar machen
* wenn PDF textbasiert: direkte Textextraktion
* wenn Scan: OCR-Fallback optional, falls im MVP vertretbar

Zu speichern:

* raw_text
* Seitenreferenzen wenn möglich
* Extraktionsqualität / Confidence optional

### 6.3.2 Dokumentklassifikation

Ziel:

Grobe Kategorisierung des Dokuments, z. B.:

* Haftpflichtversicherung
* Hausratversicherung
* Krankenversicherung
* Lebensversicherung
* Mietvertrag
* Arbeitsvertrag
* Allgemeiner Vertrag
* Unbekannt

Akzeptanzkriterien:

* jedes Dokument erhält einen document_type
* unbekannte Dokumente werden trotzdem verarbeitet

### 6.3.3 Strukturierte Datenextraktion

Da Dokumente unterschiedlich aufgebaut sind, soll mit einem flexiblen Extraktionsansatz gearbeitet werden