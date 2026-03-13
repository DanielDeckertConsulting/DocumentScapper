# DocumentScrapper

Ein Webprodukt zum Upload, zur automatischen Analyse und zum Chat mit Versicherungs- und Vertragsdokumenten.

---

## Stack

| Schicht | Technologie |
|---------|-------------|
| Frontend | Vue 3 + TypeScript + Tailwind CSS |
| Backend | Laravel 11 (PHP 8.3) |
| Datenbank | PostgreSQL 16 |
| Queue | Redis + Laravel Queue |
| Storage | Lokal (Dev) / S3-kompatibel (Prod) |
| AI | OpenAI API (GPT-4o-mini) |

---

## Schnellstart (Lokal)

### Voraussetzungen

- PHP 8.3+ mit Composer
- Node.js 20+
- Docker + Docker Compose

### 1. Infrastruktur starten

```bash
cd infra
docker compose up -d
```

PostgreSQL läuft auf Port `5432`, Redis auf Port `6379`.

### 2. Backend einrichten

```bash
cd api
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
```

Für Development mit Testnutzer:

```bash
php artisan db:seed
```

Backend starten:

```bash
php artisan serve
# Läuft auf http://localhost:8000
```

Queue Worker starten (separates Terminal):

```bash
php artisan queue:work
```

### 3. Frontend einrichten

```bash
cd web
cp .env.example .env
npm install
npm run dev
# Läuft auf http://localhost:5173
```

---

## Umgebungsvariablen

### Backend (`api/.env`)

| Variable | Beschreibung |
|----------|-------------|
| `DB_*` | PostgreSQL Verbindung |
| `REDIS_*` | Redis Verbindung |
| `OPENAI_API_KEY` | OpenAI API Schlüssel (erforderlich für Extraktion + Chat) |
| `OPENAI_MODEL_EXTRACTION` | Modell für Extraktion (default: gpt-4o-mini) |
| `OPENAI_MODEL_CHAT` | Modell für Chat (default: gpt-4o-mini) |
| `SANCTUM_STATEFUL_DOMAINS` | Frontend-Domain für Sanctum Cookie |

### Frontend (`web/.env`)

| Variable | Beschreibung |
|----------|-------------|
| `VITE_API_URL` | URL des Laravel Backends |

---

## Tests

```bash
# Backend
cd api && php artisan test

# Frontend Build
cd web && npm run build
```

---

## Lokale Entwicklung

Der Entwicklungsflow:

1. `docker compose up -d` (PostgreSQL + Redis)
2. `php artisan serve` (Backend API)
3. `php artisan queue:work` (Async-Verarbeitung)
4. `npm run dev` (Frontend Dev-Server)

---

## Upload-Flow

1. Nutzer wählt PDF in der UI aus (Dateiauswahl oder Drag & Drop)
2. Datei wird per `POST /api/documents` (multipart/form-data) hochgeladen
3. Backend validiert MIME-Typ, Dateiendung und Dateigröße (max 20 MB)
4. Datei wird im Storage unter `documents/{user_id}/{uuid}.pdf` abgelegt
5. Dokument-Record wird mit `status=uploaded` angelegt
6. `ProcessDocumentJob` wird in die Queue gestellt
7. Worker verarbeitet asynchron: Text-Extraktion → Klassifikation → Strukturextraktion → Chunking
8. Status wird sichtbar aktualisiert: `uploaded → processing → processed/failed`
9. Dokument-Detailseite zeigt extrahierte Felder nach erfolgter Verarbeitung

---

## Queue Worker

Der Queue Worker muss separat gestartet werden, um PDF-Dokumente zu verarbeiten:

```bash
# Standard (verarbeitet alle Jobs)
php artisan queue:work

# Mit explizitem Queue-Namen und Retry-Limit
php artisan queue:work --queue=default --tries=3

# Für Entwicklung (Fehler sofort sichtbar)
php artisan queue:work --tries=1
```

Ohne laufenden Worker werden Dokumente dauerhaft im Status `uploaded` verbleiben.

---

## Bekannte MVP-Limitierungen

1. **PDF only** — Nur text-basierte PDFs werden vollständig verarbeitet; kein DOCX/XLSX
2. **Keine OCR** — Gescannte PDFs ohne Text-Layer werden mit `status=failed` markiert
3. **Kein Chat in Phase 1.1** — Chat-Funktion folgt in Phase 1.2; CTA-Platzhalter ist sichtbar
4. **Extraktion unvollständig möglich** — Nicht alle Felder werden immer gefunden; fehlende Felder sind `null`, nie halluziniert
5. **Keine Account-Löschung** — Muss in Post-MVP implementiert werden
6. **Single-Server-Deployment** — Kein Kubernetes/HA-Setup in MVP
7. **OpenAI API erforderlich** — Ohne gültigen `OPENAI_API_KEY` schlägt die Verarbeitung fehl (Textextraktion funktioniert, Klassifikation + Strukturextraktion nicht)

---

## Projektdokumentation

Alle Architekturdokumente liegen in `/docs`:

| Dokument | Inhalt |
|----------|--------|
| `ARCHITECTURE.md` | Systemkontext, Building Blocks, C4-Diagramme |
| `TECHSTACK.md` | Alle verwendeten Technologien |
| `DATA_MODEL.md` | Datenbankschema, Ownership-Regeln |
| `DOCUMENT_INGESTION_PIPELINE.md` | Verarbeitungspipeline, Job-Strategie |
| `CHAT_RAG_ARCHITECTURE.md` | Chat, Retrieval, Prompt-Aufbau |
| `SECURITY_AND_GDPR.md` | Auth, Isolation, DSGVO-Baseline |
| `FRONTEND_ARCHITECTURE.md` | Vue-Struktur, Stores, Komponenten |
| `MVP_SCOPE.md` | Was im/nicht im Scope ist |
| `API_SPEC.md` | REST-Endpunkte, Request/Response-Formate |
| `TEST_STRATEGY.md` | Teststrategie, kritische Pfade |

---

## Lizenz

Proprietär — alle Rechte vorbehalten.
