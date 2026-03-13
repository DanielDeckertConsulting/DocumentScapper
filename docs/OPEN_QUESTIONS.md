# Offene Fragen — DocumentScrapper MVP

> Letzte Aktualisierung: 2026-03-13 (Entscheidungen nach Review)  
> Status: Open | In Progress | Resolved

---

## Produkt & Scope

| ID | Frage | Priorität | Status | Anmerkung |
|----|-------|-----------|--------|-----------|
| OQ-P01 | Was ist die maximale Anzahl Dokumente pro Nutzer im MVP? | Hoch | Open | Relevant für Storage-Limits und UI-Paginierung |
| OQ-P02 | Welche Sprachen sollen von der LLM-Extraktion unterstützt werden? Nur Deutsch? | Hoch | Open | gpt-4o-mini unterstützt mehrere Sprachen, aber Prompt-Guardrails sind auf Deutsch |
| OQ-P03 | Soll es eine Demo-Version / Gastzugang ohne Registrierung geben? | Mittel | Open | Würde Auth und Storage-Planung verändern |
| OQ-P04 | Wie werden Nutzer im MVP registriert? Selbst oder nur via Einladung? | Hoch | **Resolved** | **Nur via Einladung** — kein offenes Self-Sign-up in MVP |
| OQ-P05 | Welche Fehlermeldung sieht der Nutzer, wenn ein Scan-PDF nicht extrahiert werden kann? | Mittel | Open | Technik-nahe Nachricht vs. nutzerfreundliche Erklärung |
| OQ-P06 | Soll der Chat-Verlauf exportierbar sein (z. B. als PDF oder Text)? | Niedrig | Open | Post-MVP Kandidat |

---

## Identität & Authentifizierung

| ID | Frage | Priorität | Status | Anmerkung |
|----|-------|-----------|--------|-----------|
| OQ-A01 | Google Sign-In im MVP oder Post-MVP? | Mittel | **Resolved** | **Kein Google Sign-In im MVP** — nur E-Mail/Passwort |
| OQ-A02 | Wie lang soll die Session-Lifetime sein? (aktuell: 120min) | Mittel | Open | Abhängig von Nutzungskontext (kurze Sessions vs. längere Arbeitssitzungen) |
| OQ-A03 | Soll es einen "Remember me"-Mechanismus geben? | Niedrig | Open | Datenschutz-Abwägung: längere Session = mehr Risiko |
| OQ-A04 | Passwort-Reset über E-Mail: Welchen E-Mail-Provider nutzen wir? (SendGrid, Azure Communication Services, etc.) | Hoch | Open | Betrifft Infrastruktur-Setup |
| OQ-A05 | Soll Multi-Factor-Authentication (MFA) im MVP angeboten werden? | Mittel | Open | Empfohlen für Versicherungsdaten; erhöht aber Registrierungsaufwand |

---

## Technologie & Infrastruktur

| ID | Frage | Priorität | Status | Anmerkung |
|----|-------|-----------|--------|-----------|
| OQ-T01 | Welcher LLM-Provider wird genutzt? | Hoch | **Resolved** | **Provider-agnostisch** — Einstieg mit lokalem Modell (Ollama); Cloud-Provider (Azure OpenAI, OpenAI) als Upgrade-Option. Kein Hard-Dependency auf Azure OpenAI. |
| OQ-T02 | Welche Azure-Region primär? (West Europe für DSGVO, aber Latenz-Abwägung) | Hoch | Open | West Europe (Amsterdam) empfohlen für EU-Datenspeicherung |
| OQ-T03 | GitHub Actions oder Azure DevOps für CI/CD? | Mittel | Open | Aktuell GitHub Actions im Skeleton; Azure DevOps alternativ |
| OQ-T04 | Welche Container-Registry? Azure Container Registry oder GitHub Container Registry? | Mittel | Open | ACR für Azure-native Integration; GHCR für einfacheres Setup |
| OQ-T05 | Wird Azure Document Intelligence (OCR) als optionaler Fallback in MVP+ berücksichtigt, oder Tesseract lokal? | Mittel | Open | Azure DI kostet ca. 0,001 USD/Seite; Tesseract ist free aber komplexer |
| OQ-T06 | Lokal: Docker-only oder PHP/Node lokal installiert? | Mittel | Resolved | Beide Wege dokumentiert (siehe README) |
| OQ-T07 | pgvector-Extension auf Azure PostgreSQL aktivierbar? (für Post-MVP) | Niedrig | Open | Azure PostgreSQL Flexible Server unterstützt pgvector ab 1.0.0 |

---

## LLM & KI

| ID | Frage | Priorität | Status | Anmerkung |
|----|-------|-----------|--------|-----------|
| OQ-L01 | Welches LLM-Modell wird initial eingesetzt? | Hoch | **Resolved** | **Lokales Modell via Ollama als Einstieg** (z. B. llama3, mistral, oder qwen2.5). Cloud-Upgrade (GPT-4o-mini, Claude) über Interface-Swap jederzeit möglich. |
| OQ-L02 | Wie wird die LLM-Antwortqualität gemessen und überwacht? | Mittel | Open | Kein Feedback-Loop in MVP; geplant in Post-MVP |
| OQ-L03 | Soll eine Wiederverarbeitungs-Funktion für Dokumente (re-extraction) angeboten werden? | Mittel | Open | Nützlich wenn Extraktionsmodell verbessert; Infrastruktur vorbereitet |
| OQ-L04 | Wie gehen wir mit sehr langen Dokumenten um (> 100 Seiten)? | Hoch | Open | Aktuell: Text auf 24.000 Zeichen begrenzt; vollständige Lösung fehlt |
| OQ-L05 | Soll der Nutzer die LLM-Extraktion korrigieren können? (manuelles Editing) | Niedrig | Open | Post-MVP Kandidat |

---

## Compliance & Recht

| ID | Frage | Priorität | Status | Anmerkung |
|----|-------|-----------|--------|-----------|
| OQ-C01 | Ist ein DPIA (Datenschutz-Folgenabschätzung) für das Produkt erforderlich? | Hoch | Open | Abhängig von Nutzungsart; Krankenversicherungsdaten können DPIA erfordern |
| OQ-C02 | Welche Datenschutzerklärung und AGB werden beim Start benötigt? | **Kritisch** | **In Progress** | **Beauftragt** — juristischer Input läuft |
| OQ-C03 | Wie lange werden Dokumente und Chats aufbewahrt? Gibt es eine automatische Löschregel? | Mittel | Open | DSGVO Datensparsamkeit; aktuell keine Retention-Policy implementiert |
| OQ-C04 | Gilt die App als ein Verarbeitungswerkzeug für sensible Kategorien (Gesundheitsdaten in Krankenversicherungen)? | Hoch | Open | Wenn ja: verschärfte DSGVO-Anforderungen (Art. 9) |
| OQ-C05 | Azure OpenAI AVV aktiv und dokumentiert? | Mittel | **Resolved** | **Entfällt vorerst** — mit lokalem Modell verlassen keine Daten das System; AVV erst relevant wenn Cloud-Provider aktiviert wird |

---

## UX & Design

| ID | Frage | Priorität | Status | Anmerkung |
|----|-------|-----------|--------|-----------|
| OQ-U01 | Gibt es ein Marken-Design oder Farbschema für das Produkt? | Mittel | Open | Aktuell: Tailwind-Standard + Blau; kein explizites Branding |
| OQ-U02 | Soll das Frontend auf Deutsch gebaut sein (Standard laut User-Rules), oder mehrsprachig? | Hoch | Resolved | Deutsch als Standardsprache per User-Rule |
| OQ-U03 | Wie wird der Nutzer über den Datenschutz informiert? Modal beim ersten Login? | Hoch | Open | DSGVO-Anforderung; Consent-Flow nicht implementiert |

---

## Resolved Questions

| ID | Frage | Entscheidung | Datum |
|----|-------|-------------|-------|
| OQ-T06 | Lokal: Docker oder PHP lokal? | Beides dokumentiert | 2026-03-13 |
| OQ-U02 | Sprache Frontend | Deutsch (User-Rule) | 2026-03-13 |

---

## Priorisierung für sofortige Klärung (aktualisiert nach Review 2026-03-13)

### Geklärt / Entschieden
| ID | Entscheidung |
|----|-------------|
| OQ-T01 | Provider-agnostische LLM-Abstraktion; Einstieg mit lokalem Modell (Ollama) |
| OQ-C02 | Datenschutzerklärung + AGB beauftragt (In Progress) |
| OQ-C05 | Entfällt vorerst — kein Cloud-LLM-Provider in MVP |
| OQ-P04 | Nur Einladung — kein offenes Self-Sign-up |
| OQ-A01 | Kein Google Sign-In — nur E-Mail/Passwort |

### Noch offen (nächste Klärungsrunde)
1. **OQ-A04** — E-Mail-Provider für Passwort-Reset / Einladungsversand (erforderlich für Einladungs-Flow)
2. **OQ-P01** — Max. Dokumente pro Nutzer (Storage-Limits, Paginierung)
3. **OQ-P02** — Sprachunterstützung: nur Deutsch oder mehrsprachig?
4. **OQ-L04** — Umgang mit Dokumenten > 100 Seiten
5. **OQ-C02** — Warten auf juristischen Input (beauftragt)

---

*Offene Fragen — DocumentScrapper Phase 0*
