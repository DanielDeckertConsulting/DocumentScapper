# Anforderungsanalyse — DocumentScrapper MVP

> Quelle: `docs/MVP_INPUT_SPEC.md` (authoritative)

---

## 1. Produktziel (destilliert)

Ein webbasiertes Dokumentenintelligenz-Produkt, das Nutzern erlaubt:
1. Versicherungs- und Vertragsdokumente hochzuladen
2. Strukturierte Informationen automatisiert zu extrahieren
3. Per natürlichsprachlichem Chat Fragen an die Dokumente zu stellen
4. Antworten mit Quellenangaben zu erhalten

**Kernprinzip:** Nutzer vertrauen dem System ihre Vertragsdaten an — Datenschutz, Isolation und Transparenz sind daher nicht-verhandelbar.

---

## 2. Stakeholder & Nutzersegmente

| Segment | Beschreibung | Priorität |
|---------|-------------|-----------|
| Einzelnutzer | Privatpersonen mit eigenen Verträgen | **MVP** |
| Kleine Teams | 2–5 Personen, gemeinsamer Zugang (kein Sharing in MVP) | Post-MVP |
| Makler | Professionelle Verwaltung vieler Kunden-Dokumente | Post-MVP |
| Kanzleien / Beratungen | Rechtlich geprüfte Vertragsverwaltung | Post-MVP |
| B2B-Dokumentenservices | Mandantenfähige API-Nutzung | Post-MVP |

---

## 3. Funktionale Anforderungen

### FA-01 Authentifizierung

| ID | Anforderung | Priorität |
|----|------------|-----------|
| FA-01-1 | E-Mail/Passwort-Login | **Must** |
| FA-01-2 | Google Sign-In (OAuth) | Should |
| FA-01-3 | Strikte User-Isolation: jeder Nutzer sieht nur eigene Daten | **Must** |
| FA-01-4 | Session-Management mit Timeout | **Must** |

### FA-02 Dokumenten-Upload

| ID | Anforderung | Priorität |
|----|------------|-----------|
| FA-02-1 | Datei-Upload über Browser-UI | **Must** |
| FA-02-2 | Unterstützte Formate: PDF (MVP), DOCX (Post-MVP) | **Must** |
| FA-02-3 | Maximalgröße: 20 MB pro Datei | **Must** |
| FA-02-4 | MIME-Type-Validierung | **Must** |
| FA-02-5 | Ablage in Blob/Object Storage | **Must** |
| FA-02-6 | Statusanzeige nach Upload | **Must** |
| FA-02-7 | Verarbeitungspipeline automatisch starten | **Must** |

### FA-03 Dokumentverarbeitung (Ingestion Pipeline)

| ID | Anforderung | Priorität |
|----|------------|-----------|
| FA-03-1 | Textextraktion aus text-basierten PDFs | **Must** |
| FA-03-2 | OCR-Fallback für Scan-PDFs | Could |
| FA-03-3 | Dokumentklassifikation (Haftpflicht, Hausrat, KV, LV, KFZ, Mietvertrag, Arbeitsvertrag, Allgemein, Unbekannt) | **Must** |
| FA-03-4 | Strukturierte Datenextraktion: Normalisierte Felder | **Must** |
| FA-03-5 | Flexible Custom-Fields (JSONB) für unbekannte Inhalte | **Must** |
| FA-03-6 | Chunking für Retrieval-Kontext | **Must** |
| FA-03-7 | Status-Tracking: uploaded → processing → processed/failed | **Must** |
| FA-03-8 | Idempotente Pipeline (Wiederholung sicher) | **Must** |

### FA-04 Strukturierte Extraktion — Zielfelder

| Feld | Typ | Beschreibung |
|------|-----|-------------|
| document_type | VARCHAR | Klassifikationsergebnis |
| title | TEXT | Dokumenttitel |
| summary | TEXT | 2–3 Satz-Zusammenfassung |
| counterparty_name | TEXT | Versicherer / Vertragspartner |
| contract_holder_name | TEXT | Versicherungsnehmer |
| contract_number | TEXT | Vertragsnummer |
| policy_number | TEXT | Policennummer |
| start_date | DATE | Vertragsbeginn |
| end_date | DATE | Vertragsende |
| duration_text | TEXT | Laufzeit in Textform |
| cancellation_period | TEXT | Kündigungsfrist |
| payment_amount | NUMERIC | Beitragshöhe |
| payment_currency | VARCHAR | Währung |
| payment_interval | VARCHAR | Zahlungsintervall |
| important_terms | TEXT | Wichtige Konditionen |
| exclusions | TEXT | Ausschlüsse |
| contact_details | TEXT | Kontaktdaten |
| custom_fields_json | JSONB | Flexible Erweiterungsfelder |

### FA-05 Dokumentenübersicht

| ID | Anforderung | Priorität |
|----|------------|-----------|
| FA-05-1 | Liste aller eigenen Dokumente mit Status | **Must** |
| FA-05-2 | Dokumentdetailseite mit extrahierten Feldern | **Must** |
| FA-05-3 | Fehlerstatus sichtbar anzeigen | **Must** |
| FA-05-4 | Dokument löschen (inkl. Storage + DB) | **Must** |

### FA-06 Chat

| ID | Anforderung | Priorität |
|----|------------|-----------|
| FA-06-1 | Chat-Sitzung für einzelnes Dokument | **Must** |
| FA-06-2 | Chat-Sitzung über alle eigenen Dokumente | **Must** |
| FA-06-3 | Antworten nur auf Basis der Dokumentdaten (grounded) | **Must** |
| FA-06-4 | Citations (Quellenangabe) pro Antwort | **Must** |
| FA-06-5 | Chat-Verlauf persistieren | **Must** |
| FA-06-6 | Transparenz bei fehlenden Informationen | **Must** |
| FA-06-7 | Keine Rechtsberatung durch das System | **Must** |

---

## 4. Nicht-funktionale Anforderungen (NFRs)

### NFR-01 Datenschutz & Sicherheit

| ID | Anforderung |
|----|------------|
| NFR-01-1 | DSGVO-Konformität: User-Isolation, Recht auf Löschung |
| NFR-01-2 | Kein Dokumentinhalt in Anwendungs-Logs |
| NFR-01-3 | Verschlüsselung in Transit (HTTPS/TLS 1.2+) und at-rest |
| NFR-01-4 | Dateien nie öffentlich zugänglich |
| NFR-01-5 | Audit-Log für sicherheitsrelevante Operationen |
| NFR-01-6 | LLM-Provider AVV (Auftragsverarbeitungsvertrag) erforderlich |

### NFR-02 Performance

| ID | Anforderung |
|----|------------|
| NFR-02-1 | Upload-API: Antwort < 3s |
| NFR-02-2 | Dokumentverarbeitung: < 60s für typische PDFs (20 Seiten) |
| NFR-02-3 | Chat-Antwort: < 30s (LLM-abhängig) |
| NFR-02-4 | UI-Render: First Contentful Paint < 2s |

### NFR-03 Verfügbarkeit

| ID | Anforderung |
|----|------------|
| NFR-03-1 | Single-Server in MVP — kein SLA |
| NFR-03-2 | Queue-Worker-Failure darf keine Datenverluste erzeugen |

### NFR-04 Skalierbarkeit (Designprinzip)

| ID | Anforderung |
|----|------------|
| NFR-04-1 | Architektur muss Multi-Tenancy vorbereiten (tenant_id-Felder) |
| NFR-04-2 | AI-Services müssen hinter Interfaces abstrahiert sein |
| NFR-04-3 | Storage muss Cloud-abstrahiert sein (lokal ↔ S3/Azure) |

### NFR-05 Wartbarkeit

| ID | Anforderung |
|----|------------|
| NFR-05-1 | Strukturierte, konsistente Logging-Formate |
| NFR-05-2 | Alle Secrets aus Umgebungsvariablen / Key Vault |
| NFR-05-3 | Migrations-basiertes DB-Schema (keine manuellen Änderungen) |

---

## 5. User Journey Maps

### Journey 1 — Erstnutzung: Dokument hochladen und verstehen

```
Anmelden → Dokument hochladen → 
  Status "Wird verarbeitet" abwarten →
  Status "Verarbeitet" → 
  Detailseite öffnen →
  Extrahierte Felder lesen →
  Zusammenfassung lesen
```

### Journey 2 — Chat mit einem Dokument

```
Dokumentdetailseite → 
  "Chat starten" klicken → 
  Chat öffnet sich im Kontext dieses Dokuments →
  Frage stellen → 
  Antwort mit Quellenangabe erhalten →
  Weitere Fragen stellen
```

### Journey 3 — Chat über alle Dokumente

```
Alle Dokumente anzeigen → 
  "Chat starten (alle Dokumente)" →
  Vergleichsfrage stellen →
  Antwort mit Quellen aus mehreren Dokumenten erhalten
```

### Journey 4 — Fehlender Upload (Scan-PDF)

```
Dokument hochladen → 
  Status "Verarbeitung fehlgeschlagen" →
  Fehlermeldung: "Text konnte nicht extrahiert werden" →
  Dokument löschen + korrektes PDF hochladen
```

---

## 6. Qualitätskriterien für Extraktion

- **Korrektheit:** Fehlende Felder → `null`, nie halluzinieren
- **Transparenz:** Extraktionsversion wird gespeichert (für Reproduzierbarkeit)
- **Fehlertoleranz:** Unbekannte Dokumente erhalten `document_type = "unknown"`, Pipeline bricht nicht ab
- **Auditierbarkeit:** Rohes LLM-Response als `raw_response` gespeichert

---

## 7. Explizite MVP-Abgrenzungen (aus Spec)

| Nicht im MVP | Begründung |
|-------------|-----------|
| Juristische 100%-Bewertung | Technisch nicht lösbar; ethisch nicht vertretbar |
| Browser-Dokumentbearbeitung | Zu komplex; kein klarer MVP-Nutzen |
| OCR-Perfektion | Tesseract-Integration komplex; separate Phase |
| Mehrsprachigkeit | Fokus auf DE; i18n Post-MVP |
| Rollen/Berechtigungen | Single-User-MVP; Rollen Post-MVP |
| E-Mail-Import | Out of Scope |
| Mobile App | Web-first; App Post-MVP |
| Payment | Post-MVP |

---

*Quelle: MVP_INPUT_SPEC.md · Analysiert: 2026-03-13*
