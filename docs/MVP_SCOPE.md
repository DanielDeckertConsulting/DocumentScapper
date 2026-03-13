# MVP Scope — DocumentScrapper

## Produktziel

Ein schlankes Webprodukt, mit dem authentifizierte Nutzer Versicherungs- und Vertragsdokumente hochladen, automatisiert analysieren lassen und per Chat gezielt befragen können.

---

## Im Scope (MVP v1)

### Authentifizierung
- E-Mail/Passwort-Login
- Session-basierte Auth (Sanctum)
- Jeder Nutzer sieht nur eigene Daten

### Dokumenten-Upload
- PDF-Upload (max. 20 MB)
- Datei-Validierung (MIME, Größe, Extension)
- Async-Verarbeitung nach Upload
- Statusanzeige (uploaded / processing / processed / failed)

### Dokumenten-Verarbeitung
- PDF-Textextraktion (text-basierte PDFs)
- Dokumentklassifikation (Versicherungstyp / Vertragstyp)
- Strukturierte Datenextraktion (normalisierte Kernfelder)
- Flexible Custom-Fields für unbekannte Felder
- Chunking für Chat-Retrieval

### Dokumenten-Übersicht
- Liste aller eigenen Dokumente mit Status
- Dokumentdetailseite mit extrahierten Feldern
- Löschfunktion

### Chat
- Chat-Sitzung zu einem einzelnen Dokument
- Chat-Sitzung über alle eigenen Dokumente
- Grounded Antworten (nur auf Basis der Dokumente)
- Citations (Quellenangaben) in Antworten
- Chat-Verlauf persistieren + laden

### Sicherheit / Datenschutz
- User-Isolation serverseitig durchgesetzt
- Kein Dokumentinhalt in Logs
- Dokumenten-Löschung inkl. abhängiger Daten
- Audit-Logs für kritische Operationen

---

## Explizit NICHT im Scope (MVP v1)

| Feature | Begründung |
|---------|-----------|
| OCR für Scan-PDFs | Zu komplex für MVP; Tesseract optional in v2 |
| DOCX / Bildformate | Nur PDF in MVP |
| Multi-Tenant / Organisationen | Post-MVP B2B-Feature |
| Rollen und Berechtigungssystem | Einzelnutzer reicht für MVP |
| Dokumenten-Bearbeitung im Browser | Post-MVP |
| E-Mail-Import | Post-MVP |
| Mobile App | Post-MVP |
| Externe Integrationen (Versicherer, etc.) | Post-MVP |
| Payment / Abonnements | Post-MVP |
| Kubernetes / Cloud-Orchestrierung | Single-Server für MVP |
| Erweiterte Suche / Filter | Einfache Liste reicht für MVP |
| Dokumentenvergleich | Post-MVP |
| Vektordatenbank / Embedding-Suche | Keyword-Suche reicht für MVP |
| Vollständige Rechtsberatung | Keine juristische KI-Leistung |

---

## Erfolgskriterien MVP

Das MVP ist erfolgreich, wenn ein authentifizierter Nutzer folgendes vollständig durchführen kann:

1. **Upload:** Mehrere PDF-Dokumente hochladen
2. **Status:** Verarbeitungsstatus sehen (processing / processed / failed)
3. **Inspektion:** Extrahierte strukturierte Informationen pro Dokument lesen
4. **Chat (Einzeldokument):** Eine Frage zu einem Dokument stellen und eine grounded Antwort mit Citation erhalten
5. **Chat (Alle Dokumente):** Eine Frage über alle eigenen Dokumente stellen
6. **Isolation:** Sicherstellung, dass keine anderen Nutzerdaten sichtbar sind
7. **Löschung:** Ein Dokument löschen

---

## Explizite MVP-Limitierungen (für Nutzer kommunizieren)

1. **PDF only** — Andere Formate werden nicht verarbeitet
2. **Nur text-basierte PDFs** — Gescannte Dokumente ohne OCR werden nicht extrahiert
3. **Keine Rechtsberatung** — Das System interpretiert, nicht berät
4. **Extraktion ist unvollständig möglich** — Nicht alle Felder werden immer gefunden
5. **Keine Garantie der Vollständigkeit** — Dokumente sollten immer direkt geprüft werden
6. **Chat-Antworten sind keine rechtlich bindenden Aussagen**

---

## Phasen nach MVP

| Phase | Inhalt |
|-------|--------|
| MVP+1 | DOCX-Support, OCR-Fallback, erweiterte Chat-Filter |
| MVP+2 | Organisationen, Rollen, Team-Sharing |
| MVP+3 | Vektordatenbank, bessere Retrieval-Qualität |
| MVP+4 | Externe Integrationen, E-Mail-Import |

---

*Letzte Aktualisierung: Phase 0 Bootstrap*
