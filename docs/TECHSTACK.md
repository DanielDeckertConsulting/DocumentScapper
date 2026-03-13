# Tech Stack — DocumentScrapper MVP

> Letzte Aktualisierung: Phase-0 Analyse · 2026-03-13

---

## Entscheidungsübersicht

| Schicht | Technologie | Version | Entscheidungsstatus |
|---------|-------------|---------|---------------------|
| Frontend | Vue 3 + TypeScript | 3.4 / 5.x | **Entschieden** |
| Build-Tool | Vite | 5.x | **Entschieden** |
| State Management | Pinia | 2.x | **Entschieden** |
| CSS-Framework | Tailwind CSS | 4.x | **Entschieden** |
| Backend | Laravel 11 (PHP 8.3) | 11.x | **Entschieden** |
| Auth | Laravel Sanctum | 4.x | **Entschieden** |
| Datenbank | PostgreSQL 16 | 16 | **Entschieden** |
| Queue / Cache | Redis 7 | 7 | **Entschieden** |
| File Storage | Azure Blob Storage | - | **Entschieden** |
| LLM (MVP) | Ollama (lokal) | llama3.2 / mistral | **Entschieden** |
| LLM (Option) | OpenAI / Azure OpenAI | gpt-4o-mini | Per Config aktivierbar |
| PDF-Extraktion | smalot/pdfparser | 2.x | **Entschieden** |
| Container | Azure Container Apps | - | **Entschieden** |
| Secrets | Azure Key Vault | - | **Entschieden** |
| CI/CD | GitHub Actions | - | **Entschieden** |

---

## Frontend

### Warum Vue 3?

| Kriterium | Vue 3 | React | Angular |
|-----------|-------|-------|---------|
| Lernkurve | Niedrig | Mittel | Hoch |
| Bundle-Größe | Klein | Mittel | Groß |
| TypeScript-Support | Exzellent | Exzellent | Exzellent |
| Community | Groß | Sehr groß | Mittel |
| Composition API | Ja | Hooks | Nein |
| Vite-Integration | Nativ | Plugin | Kein |
| **Fazit** | ✅ MVP-geeignet | Valide Alternative | Überengineered für MVP |

### Vue-Abhängigkeiten

| Paket | Version | Zweck |
|-------|---------|-------|
| `vue` | ^3.4 | Framework |
| `vue-router` | ^4.3 | SPA-Routing |
| `pinia` | ^2.1 | State Management |
| `axios` | ^1.7 | HTTP-Client mit Interceptors |
| `vite` | ^5.x | Build-Tool |
| `@vitejs/plugin-vue` | latest | Vue-Vite-Integration |
| `tailwindcss` | ^4.x | Utility-CSS |
| `@tailwindcss/postcss` | latest | PostCSS-Integration für Tailwind v4 |
| `typescript` | ^5.x | Typsicherheit |

---

## Backend

### Warum Laravel 11?

| Kriterium | Laravel | Symfony | NestJS (Node) |
|-----------|---------|---------|----------------|
| Produktivität | Sehr hoch | Mittel | Mittel |
| Queues / Jobs | Eingebaut | Bundle | Extra |
| File-Storage | Filesystem Abstraction | Custom | Extra |
| ORM | Eloquent (exzellent) | Doctrine | TypeORM |
| Auth (SPA) | Sanctum eingebaut | Extra | Passport/JWT |
| PHP 8.3-Features | Vollständig | Vollständig | N/A |
| Azure-Integration | Via Packages | Via Packages | Via Packages |
| **Fazit** | ✅ Ideal für MVP | Mehr Boilerplate | Sprachbruch zum Team |

### Laravel-Abhängigkeiten

| Paket | Version | Zweck |
|-------|---------|-------|
| `laravel/framework` | ^11.x | Framework |
| `laravel/sanctum` | ^4.x | SPA-Auth via HttpOnly Cookie |
| `smalot/pdfparser` | ^2.x | PDF-Text-Extraktion |
| `openai-php/laravel` | ^0.10 | OpenAI-kompatibler SDK (für Ollama + OpenAI + Azure OpenAI) |
| `predis/predis` | ^2.x | Redis-Client |
| `league/flysystem-azure-blob-storage` | ^3.x | Azure Blob Filesystem Driver |
| Dev: `laravel/pint` | ^1.x | Code-Linter |
| Dev: `phpunit/phpunit` | ^11.x | Tests |

---

## Datenbank

### Warum PostgreSQL?

- JSONB für `custom_fields_json` und `citations_json`
- Row Level Security (RLS) für Multi-Tenancy-Vorbereitung
- `gen_random_uuid()` für UUID Primary Keys
- Robust, bewährt, kostengünstig auf Azure
- Gute ORM-Unterstützung via Eloquent

### Azure Database for PostgreSQL Flexible Server

| Parameter | MVP-Wert |
|-----------|---------|
| Tier | Burstable (B1ms, 1 vCore, 2 GB RAM) |
| Storage | 32 GB (erweiterbar) |
| Backup | 7 Tage automatisch |
| SSL | Erzwungen (require_ssl=on) |
| Firewall | Azure-internal only (Private Link) |

---

## AI / LLM

### Provider-Strategie: Agnostische Abstraktion

**Entscheidung (2026-03-13):** MVP startet mit lokalem Modell via Ollama. Cloud-Provider werden als Config-Option vorgehalten, sind aber kein Pflicht-Deployment.

| Provider | Status | Zweck |
|----------|--------|-------|
| **Ollama (lokal)** | **MVP Default** | Keine Datenweitergabe, kein AVV, 0 Token-Kosten |
| OpenAI API | Optional / Config | Bessere Qualität, einfaches API-Key-Setup |
| Azure OpenAI | Post-MVP Option | EU-Datenspeicherung, AVV via Microsoft DPA |

### Warum Ollama als Einstieg?

| Kriterium | Ollama (lokal) | Azure OpenAI |
|-----------|---------------|-------------|
| Datenweitergabe | Keine | Ja (Azure EU-Region) |
| AVV erforderlich | Nein | Ja |
| Token-Kosten | 0 | ~$0.0002/1k Token |
| Latenz | Abhängig von Hardware | ~500ms–3s |
| Modell-Qualität | Gut (llama3.2, mistral) | Sehr gut (gpt-4o-mini) |
| Vendor Lock-in | Keiner | Azure-Abhängigkeit |
| **Fazit** | ✅ MVP Default (DSGVO-safe) | Upgrade-Option wenn Qualität nötig |

### Interface-Abstraktion

Das `LLM_PROVIDER`-Env-Setting schaltet den Provider um — kein Code-Change nötig:
```env
LLM_PROVIDER=ollama          # Lokal (MVP Default)
LLM_PROVIDER=openai          # OpenAI API
LLM_PROVIDER=azure_openai    # Azure OpenAI (Post-MVP)
```

Ollama implementiert die OpenAI-kompatible REST-API (`/v1/chat/completions`), daher ist der `openai-php/laravel`-SDK ohne Änderungen nutzbar.

### Modell-Konfiguration (Ollama MVP)

| Zweck | Modell (Ollama) | num_predict | Temperatur |
|-------|----------------|-------------|-----------|
| Klassifikation | llama3.2 / mistral | 100 | 0.0 |
| Strukturierte Extraktion | llama3.2 / mistral | 1500 | 0.1 |
| Chat-Antwort | llama3.2 / mistral | 1000 | 0.1 |

---

## Infrastructure / Azure

### Azure Container Apps

- Serverless Container-Hosting
- Auto-scale auf 0 (spart Kosten im MVP)
- Separate Container für API und Worker
- Integrierte DAPR-Unterstützung (Post-MVP optional)

### Azure Key Vault

Alle Secrets werden hier gespeichert, nie in `.env`:
- `DB_PASSWORD`
- `REDIS_PASSWORD`
- `AZURE_OPENAI_API_KEY`
- `AZURE_BLOB_STORAGE_KEY`

Zugriff via Managed Identity — kein Service-Principal in Code.

### Azure Blob Storage

```
Container: documents (private)
  Pfad:   users/{user_id}/{uuid}.{ext}
```

- Private Container (kein anonymer Zugriff)
- Zugriff nur via signierte SAS-URLs oder SDK
- Soft-Delete: 7 Tage (Schutz vor versehentlichem Löschen)

---

## Lokal-Dev (Non-Azure)

Für lokale Entwicklung werden Azure-Services durch Docker ersetzt:

| Produktions-Service | Lokal-Ersatz |
|--------------------|-------------|
| Azure Database for PostgreSQL | Docker `postgres:16-alpine` |
| Azure Cache for Redis | Docker `redis:7-alpine` |
| Azure Blob Storage | Lokales Filesystem oder Azurite |
| Azure Key Vault | `.env`-Datei (nicht committed) |
| Azure OpenAI | Ollama (http://localhost:11434) |

---

## Nicht verwendet (und warum)

| Technologie | Begründung |
|------------|-----------|
| Kubernetes | Overengineered für MVP; Container Apps einfacher |
| Python FastAPI | AI-Interfaces abstrahiert; PHP ausreichend für MVP |
| ElasticSearch | Keyword-Suche via PostgreSQL reicht für MVP |
| pgvector | Post-MVP wenn Embedding-Suche benötigt |
| GraphQL | REST reicht; GraphQL-Overhead nicht gerechtfertigt |
| WebSockets | Chat ist Request/Response; SSE Post-MVP |
| Microservices | Monolith-first; Aufteilung bei konkretem Bedarf |

---

*Tech Stack Dokument — DocumentScrapper Phase 0*
