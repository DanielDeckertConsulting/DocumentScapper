# Risikoregister — DocumentScrapper MVP

> Letzte Aktualisierung: Phase-0 Analyse · 2026-03-13  
> Bewertung: Eintrittswahrscheinlichkeit (E) × Auswirkung (A) = Risikoscore (1–9)

---

## Risiko-Matrix

```
Auswirkung
   3 (Hoch)  │  R06  │  R01  │  R03
             │  R09  │  R02  │  R05
   2 (Mittel)│  R08  │  R04  │  R07
   1 (Niedrig│       │  R10  │
             └───────────────────────
                1      2      3   Eintritt
             (Niedrig)(Mittel)(Hoch)
```

---

## Technische Risiken

### R01 — LLM-Extraktionsqualität unzureichend
| | |
|---|---|
| **Beschreibung** | Azure OpenAI extrahiert bei heterogenen Dokumentlayouts viele Felder als `null` oder mit falschen Werten |
| **Eintrittswahrsch.** | Mittel (2) — Dokumentlayouts variieren stark |
| **Auswirkung** | Hoch (3) — Kernfeature funktioniert nicht nutzbar |
| **Score** | 6 — **HOCH** |
| **Minderung** | Extraktion als "Best-Effort" kommunizieren; `null`-Felder gracefully rendern; Extraktionsversion speichern für spätere Wiederholung; manuelle Nachbesserung in Post-MVP |
| **Status** | Offen |

### R02 — Scan-PDFs scheitern an Textextraktion
| | |
|---|---|
| **Beschreibung** | Nutzer laden gescannte PDFs ohne Text-Layer hoch; `raw_text` ist leer; gesamte Pipeline schlägt fehl |
| **Eintrittswahrsch.** | Mittel (2) — viele Versicherungsverträge sind Scans |
| **Auswirkung** | Hoch (3) — kritische Use-Cases nicht abgedeckt |
| **Score** | 6 — **HOCH** |
| **Minderung** | Klare Fehlermeldung im UI; MVP-Limitation dokumentieren; Azure Document Intelligence als OCR-Upgrade in Post-MVP |
| **Status** | Offen — bewusste MVP-Einschränkung |

### R03 — Azure OpenAI API Verfügbarkeit / Latenz
| | |
|---|---|
| **Beschreibung** | Azure OpenAI hat Outage oder Latenz > 60s; Extraktion und Chat nicht nutzbar |
| **Eintrittswahrsch.** | Hoch (3) — LLM-APIs haben reguläre Maintenance-Fenster |
| **Auswirkung** | Hoch (3) — Core-Produktfunktionen down |
| **Score** | 9 — **KRITISCH** |
| **Minderung** | Retry-Mechanismus in Jobs (max 3, exponentiell); Graceful Failure: Dokument-Upload bleibt nutzbar ohne Extraktion; Status `failed` mit verständlicher Meldung; Circuit Breaker Post-MVP |
| **Status** | Teilweise mitigiert |

### R04 — Prompt Injection via Dokumentinhalt
| | |
|---|---|
| **Beschreibung** | Dokument enthält manipulativen Text, der System-Prompt-Verhalten ändert |
| **Eintrittswahrsch.** | Mittel (2) — gezielter Angriff möglich |
| **Auswirkung** | Mittel (2) — Antwortqualität verschlechtert; kein direkter Datenverlust |
| **Score** | 4 — **MITTEL** |
| **Minderung** | Strukturierte Prompt-Trennung (System/Context/User); Scope-Enforcement vor LLM-Call; Output-Länge begrenzen; Monitoring auf ungewöhnliche Antwort-Patterns |
| **Status** | Teilweise mitigiert |

### R05 — Cross-User-Datenzugriff (Ownership-Bug)
| | |
|---|---|
| **Beschreibung** | Durch einen Bug in Policies oder Query-Scopes können Nutzer Dokumente anderer Nutzer sehen |
| **Eintrittswahrsch.** | Hoch (3) — Ownership-Bugs sind häufig in frühen Iterationen |
| **Auswirkung** | Hoch (3) — DSGVO-Verletzung; Vertrauensverlust |
| **Score** | 9 — **KRITISCH** |
| **Minderung** | Explizite Ownership-Tests für jeden API-Endpunkt; Policy-Tests in CI; Code-Review-Pflicht für alle DB-Queries; keine Queries ohne `WHERE user_id = ?` |
| **Status** | Tests implementiert; laufend überwachen |

### R06 — Datenverlust durch Queue-Job-Failure
| | |
|---|---|
| **Beschreibung** | Queue Worker crashed während Extraktion; Job-Status unklar; Dokument bleibt in `processing` |
| **Eintrittswahrsch.** | Niedrig (1) — Laravel Queue hat eingebaute Fehlerbehandlung |
| **Auswirkung** | Hoch (3) — Nutzer sieht Dokument als "verarbeitet" ohne Daten |
| **Score** | 3 — **NIEDRIG** |
| **Minderung** | Job-Retry max 3; `failed` Job Handler markiert Dokument als `failed`; Job-Timeout konfiguriert (300s); Audit-Log bei Failure |
| **Status** | Implementiert |

---

## Datenschutz-Risiken

### R07 — PII-Leakage in Application Logs
| | |
|---|---|
| **Beschreibung** | Entwickler loggt versehentlich `raw_text`, Prompt oder Extraktionsergebnisse |
| **Eintrittswahrsch.** | Hoch (3) — häufiger Debug-Fehler |
| **Auswirkung** | Mittel (2) — DSGVO-Verstoß; Datenpersistenz in Logs |
| **Score** | 6 — **HOCH** |
| **Minderung** | Klare Logging-Policy dokumentiert; Code-Review-Checkliste; `$document->raw_text` nie in Log-Calls; Azure Monitor Log-Filter für sensible Patterns |
| **Status** | Policy definiert; Review-Checklist ausstehend |

### R08 — Azure OpenAI AVV nicht aktiv
| | |
|---|---|
| **Beschreibung** | Produkt geht live ohne formalen Auftragsverarbeitungsvertrag mit Microsoft |
| **Eintrittswahrsch.** | Niedrig (1) — standardmäßig im Azure-Vertrag |
| **Auswirkung** | Hoch (3) — DSGVO Art. 28 verletzt |
| **Score** | 3 — **NIEDRIG** |
| **Minderung** | AVV-Status vor Go-Live prüfen; Microsoft Customer Agreement enthält DPA standardmäßig |
| **Status** | Zu prüfen |

### R09 — Nutzerdaten nach Account-Löschung nicht entfernt
| | |
|---|---|
| **Beschreibung** | Account-Löschung ist in MVP nicht implementiert; DSGVO-Löschrecht kann nicht erfüllt werden |
| **Eintrittswahrsch.** | Niedrig (1) |
| **Auswirkung** | Hoch (3) — DSGVO Art. 17 verletzt bei Nutzeranfrage |
| **Score** | 3 — **NIEDRIG** |
| **Minderung** | Dokument-Löschung implementiert; Account-Löschung als Post-MVP-Ticket; Support-Prozess für manuelle Löschung in Übergangszeit dokumentieren |
| **Status** | MVP-Limitation dokumentiert |

---

## Produkt-Risiken

### R10 — Nutzer vertraut LLM-Antwort als Rechtsberatung
| | |
|---|---|
| **Beschreibung** | Nutzer handelt auf Basis einer falschen oder unvollständigen LLM-Antwort rechtlich |
| **Eintrittswahrsch.** | Mittel (2) — schwer messbar |
| **Auswirkung** | Niedrig (1) — Haftung liegt beim Nutzer (AGB); kein technischer Schaden |
| **Score** | 2 — **NIEDRIG** |
| **Minderung** | Persistenter UI-Disclaimer; "Keine Rechtsberatung"-Hinweis in Chat; Guardrails im System-Prompt |
| **Status** | Implementiert |

---

## Risiko-Zusammenfassung

| Priorität | Risiken | Handlungsbedarf |
|-----------|---------|----------------|
| **KRITISCH** | R03 (LLM Verfügbarkeit), R05 (Ownership-Bug) | Sofort mitigieren vor Go-Live |
| **HOCH** | R01 (Extraktionsqualität), R02 (Scan-PDFs), R07 (PII in Logs) | Mitigationsmaßnahmen aktiv |
| **MITTEL** | R04 (Prompt Injection) | Monitoring + regelmäßige Review |
| **NIEDRIG** | R06, R08, R09, R10 | Dokumentiert; im Blick behalten |

---

## Offene Risiko-Maßnahmen

- [ ] R03: Circuit Breaker für Azure OpenAI implementieren (Post-MVP)
- [ ] R05: Ownership-Tests in CI Pflicht (vor Go-Live)
- [ ] R07: Log-Review-Checklist erstellen
- [ ] R08: Azure AVV-Status vor Go-Live bestätigen
- [ ] R09: Account-Löschungs-Prozess dokumentieren

---

*Risikoregister — DocumentScrapper Phase 0*
