# MVP-Simplizitätsbegründung — DocumentScrapper

> Letzte Aktualisierung: Phase-0 Analyse · 2026-03-13

---

## 1. Leitprinzip

> "The simplest architecture that could possibly work — without painting ourselves into a corner."

Jede Technologie- und Architekturentscheidung wird durch die Frage geprüft:
1. Löst sie ein **tatsächliches, jetzt vorhandenes** Problem?
2. Verhindert sie eine **spätere Erweiterung** signifikant?
3. Ist der **Aufwand verhältnismäßig** zum MVP-Nutzen?

---

## 2. Bewusste Vereinfachungen (und ihre Begründung)

### 2.1 Keyword-Retrieval statt Vektordatenbank

| Alternative | MVP-Wahl | Begründung |
|-------------|----------|-----------|
| pgvector / Weaviate / Pinecone | PostgreSQL `ILIKE` | Dokumentvolumen in MVP ist klein (< 100 Dokumente pro Nutzer). Keyword-Suche ist ausreichend für die zu erwartenden Chat-Fragen ("Was ist meine Kündigungsfrist?"). Keine Embedding-Kosten. Einfacheres Testing. Migration zu Vektoren ist möglich ohne API-Änderung (RetrieverInterface). |

**Metriken für Umstieg:** >500 Chunks pro Nutzer, oder Retrieval-Recall < 70% in Tests.

### 2.2 Monolithischer Laravel-Backend statt Microservices

| Alternative | MVP-Wahl | Begründung |
|-------------|----------|-----------|
| Python AI-Service + PHP-Backend | Einheitlicher PHP/Laravel-Monolith | Im MVP gibt es keinen messbaren Vorteil durch Servicetrennung. Jede Service-Grenze erhöht Deployment-Komplexität, Netzwerk-Latenz und Testaufwand exponentiell. AI-Services sind hinter Interfaces abstrahiert — Extraktion kann ohne API-Änderung ausgelagert werden. |

**Metriken für Umstieg:** Queue-Worker-CPU > 80% dauerhaft, oder Extraktion dauert > 120s.

### 2.3 Session-basierte Auth statt JWT

| Alternative | MVP-Wahl | Begründung |
|-------------|----------|-----------|
| JWT / OAuth2 | Laravel Sanctum (HttpOnly Cookie) | JWT im LocalStorage ist unsicher (XSS). Refresh-Token-Rotation ist komplex. Sanctum ist battle-tested für SPA+Laravel-Kombination. Kein zusätzlicher Token-Service nötig. |

**Metriken für Umstieg:** Mobile App geplant, API-only Client ohne Browser benötigt.

### 2.4 Einfaches Chunking statt semantischem Splitting

| Alternative | MVP-Wahl | Begründung |
|-------------|----------|-----------|
| Satzgrenzen-Erkennung, semantisches Chunking | Wort-basiertes Sliding-Window | Für Versicherungs-/Vertragsdokumente funktioniert einfaches Wort-basiertes Chunking ausreichend gut. Semantisches Splitting erfordert ein zusätzliches NLP-Modell oder LLM-Calls. |

**Metriken für Umstieg:** Chat-Answer-Quality-Score < 60%.

### 2.5 Keine Organisationen / Multi-Tenancy im MVP

| Alternative | MVP-Wahl | Begründung |
|-------------|----------|-----------|
| Vollständige Mandantenfähigkeit von Anfang an | User-Level-Isolation | Keine einzige Anforderung im MVP-Spec verlangt Tenant-Sharing. Tenant-Isolation-Code ohne echten Use-Case führt zu unnötiger Komplexität. `tenant_id`-Felder sind vorbereitet, aber inaktiv. |

**Metriken für Umstieg:** Erster B2B-Kunde mit Shared-Access-Anforderung.

### 2.6 Kein OCR in MVP

| Alternative | MVP-Wahl | Begründung |
|-------------|----------|-----------|
| Tesseract OCR / Azure Document Intelligence | Nur text-basierte PDFs | OCR-Integration (Tesseract) erhöht Infrastruktur-Komplexität erheblich. Azure Document Intelligence wäre die einfachere Alternative, kostet aber zusätzlich. 80% der in der Praxis genutzten PDFs sind text-basiert. |

**Metriken für Umstieg:** > 20% der hochgeladenen PDFs scheitern an Textextraktion.

### 2.7 Kein WebSocket für Chat

| Alternative | MVP-Wahl | Begründung |
|-------------|----------|-----------|
| WebSocket / Server-Sent Events | Request/Response | Chat-Anfragen dauern 3–20 Sekunden (LLM-abhängig). Ein einfacher Request mit Ladeanzeige ist UX-technisch ausreichend für MVP. WebSocket-Infrastruktur (Laravel Echo, Pusher) erhöht Komplexität. |

**Metriken für Umstieg:** User-Feedback zeigt Streaming-Erlebnis als kritisch.

### 2.8 Queue Worker ohne horizontale Skalierung

| Alternative | MVP-Wahl | Begründung |
|-------------|----------|-----------|
| Kubernetes Job-Scaling, Azure Service Bus | Single Queue Worker auf Container Apps | Für MVP-Volumen (< 50 Dokumente/Tag) ist ein Worker ausreichend. Azure Container Apps erlaubt einfaches Scaling per Konfiguration ohne Code-Änderungen. |

**Metriken für Umstieg:** Queue-Backlog > 100 Jobs konstant.

---

## 3. Nicht-Vereinfachungen (bewusste Investitionen)

Diese Bereiche wurden **nicht** vereinfacht, weil die Kosten einer späteren Korrektur zu hoch wären:

| Bereich | Begründung der Investition |
|---------|--------------------------|
| **AI-Service-Interfaces** | Ohne Interfaces ist der Wechsel zu Python-Service oder anderer LLM-API eine vollständige Refaktorierung. Mit 6 Interfaces ist es ein Drop-in-Replacement. |
| **UUID-basierte IDs** | Nachträgliche Migration von Auto-Increment zu UUIDs ist aufwändig und fehleranfällig. |
| **DSGVO-Baseline** | Nachträgliche DSGVO-Compliance ist sehr teuer. Frühe Investition in Logging-Disziplin, User-Isolation und Löschbarkeit spart erheblich. |
| **PostgreSQL statt SQLite** | SQLite-zu-PostgreSQL-Migration ist schmerzhaft. PostgreSQL von Anfang an kostet kaum mehr. |
| **Strukturiertes Logging** | Chaotische Logs früh einzuführen und später zu bereinigen ist extrem aufwändig. |
| **Ownership-Policies** | Security-Fehler nachträglich zu fixen nach einem Datenleck ist um Größenordnungen teurer. |

---

## 4. Komplexitäts-Radar

```
                    Komplexität
                         │
  Microservices ─────────┼──── AUSGESCHLOSSEN (MVP)
  Vektordatenbank ───────┼──── Post-MVP
  OCR Pipeline ──────────┼──── Post-MVP
  Multi-Tenancy ─────────┼──── Vorbereitet, Post-MVP
  WebSockets ────────────┼──── Post-MVP
                         │
  AI-Interfaces ─────────┼──── HEUTE (bewusste Investition)
  DSGVO-Baseline ────────┼──── HEUTE (non-negotiable)
  Auth + Isolation ──────┼──── HEUTE (non-negotiable)
  UUID IDs ──────────────┼──── HEUTE (kein overhead)
  Keyword-Retrieval ─────┼──── HEUTE (ausreichend)
  Monolith ──────────────┼──── HEUTE (MVP-richtig)
```

---

## 5. Entscheidungs-Log

| Datum | Entscheidung | Alternative | Grund |
|-------|-------------|-------------|-------|
| 2026-03-13 | Vue 3 statt React | React | Geringere Lernkurve, nativere Vite-Integration |
| 2026-03-13 | Laravel statt FastAPI | FastAPI | Team-Kenntnisse, eingebaute Queue + Auth |
| 2026-03-13 | Azure OpenAI statt OpenAI direkt | OpenAI API | EU-Datenspeicherung, AVV auto |
| 2026-03-13 | Keyword-Retrieval | pgvector | MVP-Volumen, kein Overhead |
| 2026-03-13 | Monolith | Microservices | Kein messbarer Vorteil im MVP |
| 2026-03-13 | Sanctum Cookie-Auth | JWT | HttpOnly Cookie sicherer gegen XSS |

---

*MVP-Simplizitätsbegründung — DocumentScrapper Phase 0*
