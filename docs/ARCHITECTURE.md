# Systemarchitektur — DocumentScrapper MVP

> Letzte Aktualisierung: Phase-1.1 Auth-Refactor (Bearer Token) · 2026-03-13

---

## 1. Systemkontext (C4 Level 1)

```mermaid
C4Context
  title System Context — DocumentScrapper MVP

  Person(user, "Nutzer", "Authentifizierter Endnutzer (Einzelperson / kleines Team)")

  System(app, "DocumentScrapper", "Upload, Extraktion, grounded Chat für Versicherungs- & Vertragsdokumente")

  System_Ext(azure_openai, "Azure OpenAI Service", "LLM-Extraktion + Chat (GPT-4o-mini, EU-Region)")
  System_Ext(azure_blob, "Azure Blob Storage", "Originaldokumente (verschlüsselt at-rest)")
  System_Ext(azure_pg, "Azure Database for PostgreSQL", "Primäre relationale Datenbank")
  System_Ext(azure_redis, "Azure Cache for Redis", "Queue-Broker + Session-Cache")
  System_Ext(azure_kv, "Azure Key Vault", "Secrets + App-Konfiguration")

  Rel(user, app, "HTTPS (Browser)")
  Rel(app, azure_openai, "HTTPS API (AVV geschlossen)")
  Rel(app, azure_blob, "Azure SDK / SAS-Token")
  Rel(app, azure_pg, "TLS-verschlüsselt")
  Rel(app, azure_redis, "TLS-verschlüsselt")
  Rel(app, azure_kv, "Managed Identity")
```

---

## 2. Container-Architektur (C4 Level 2)

```mermaid
C4Container
  title Container Diagram — DocumentScrapper MVP

  Person(user, "Nutzer")

  System_Boundary(app_boundary, "DocumentScrapper") {
    Container(spa, "Vue SPA", "Vue 3 + TypeScript + Vite", "Single-Page-Application; statisch ausgeliefert")
    Container(api, "Laravel API", "PHP 8.3 / Laravel 11", "REST-API; Auth; Business-Logik; Ownership-Enforcement")
    Container(worker, "Queue Worker", "Laravel Queue / Redis", "Async Dokument-Verarbeitung")
  }

  ContainerDb(postgres, "PostgreSQL 16", "Azure Database for PostgreSQL Flexible Server", "Dokumente, Extraktion, Chat, Audit")
  ContainerDb(redis, "Redis 7", "Azure Cache for Redis", "Queue-Broker; Session-Cache")
  Container(blob, "Blob Storage", "Azure Blob Storage", "Originaldateien; User-Namespace-isoliert")
  Container(kv, "Key Vault", "Azure Key Vault", "API-Keys; DB-Passwörter; App-Secrets")

  Rel(user, spa, "HTTPS")
  Rel(spa, api, "JSON REST; Sanctum Bearer Token (Authorization: Bearer)")
  Rel(api, postgres, "Eloquent ORM; TLS")
  Rel(api, redis, "Queue-Dispatch; TLS")
  Rel(api, blob, "Azure SDK; User-Pfad-Isolation")
  Rel(api, kv, "Managed Identity; Secrets lesen")
  Rel(worker, postgres, "Job-Daten lesen/schreiben")
  Rel(worker, blob, "Dateien laden")
  Rel(worker, api, "AI-Service-Interfaces (LLM-Calls)")
```

---

## 3. Azure Service-Mapping

| Concern | Azure Service | Tier (MVP) | Begründung |
|---------|--------------|-----------|-----------|
| Frontend-Hosting | Azure Static Web Apps oder Azure CDN + Blob | Free/Standard | Statische SPA; keine Server-Kosten |
| Backend API | Azure Container Apps | Consumption | Auto-scale to zero; pay-per-use |
| Queue Worker | Azure Container Apps (separate Job) | Consumption | Gleiche Plattform; Worker skalierbar |
| Datenbank | Azure Database for PostgreSQL Flexible Server | Burstable B1ms | Günstig; JSONB; RLS-fähig |
| File Storage | Azure Blob Storage | LRS Hot Tier | AVV-konform; private Container |
| Queue / Cache | Azure Cache for Redis | C0 Basic | Einfach; Laravel Queue out-of-the-box |
| Secrets | Azure Key Vault | Standard | Kein Secret in Umgebungsvariablen direkt |
| Monitoring | Azure Monitor + Application Insights | Basic | Error-Tracking; keine PII in Traces |
| Container-Registry | Azure Container Registry | Basic | API + Worker Images |

---

## 4. Datenfluss — Authentifizierung

```mermaid
sequenceDiagram
  participant U as Nutzer (Browser)
  participant SPA as Vue SPA
  participant API as Laravel API
  participant PG as PostgreSQL

  U->>SPA: Login-Formular absenden (E-Mail + Passwort)
  SPA->>API: POST /api/auth/login {email, password}
  API->>PG: User anhand E-Mail laden
  API->>API: Passwort-Hash prüfen (bcrypt)
  API->>PG: Altes "spa"-Token löschen
  API->>PG: Neues Personal Access Token erstellen
  API-->>SPA: 200 {token, user: {id, name, email}}
  SPA->>SPA: Token in localStorage speichern

  Note over SPA,API: Alle folgenden Requests
  SPA->>API: GET /api/auth/user\nAuthorization: Bearer <token>
  API->>API: Token via Sanctum validieren
  API-->>SPA: {id, name, email, created_at}

  Note over SPA,API: Logout
  SPA->>API: POST /api/auth/logout\nAuthorization: Bearer <token>
  API->>PG: Token löschen (currentAccessToken()->delete())
  API-->>SPA: 204 No Content
  SPA->>SPA: Token aus localStorage entfernen
```

---

## 5. Datenfluss — Dokument-Ingestion

```mermaid
sequenceDiagram
  participant U as Nutzer (Browser)
  participant SPA as Vue SPA
  participant API as Laravel API
  participant Q as Queue Worker
  participant PG as PostgreSQL
  participant Blob as Azure Blob
  participant LLM as Azure OpenAI

  U->>SPA: PDF auswählen und hochladen
  SPA->>API: POST /api/documents (multipart)
  API->>API: MIME + Größe validieren
  API->>Blob: Datei in users/{user_id}/{uuid}.pdf speichern
  API->>PG: document record anlegen (status=uploaded)
  API->>Q: ProcessDocumentJob dispatchen
  API-->>SPA: 201 {id, status: "uploaded"}

  Q->>Blob: Datei laden
  Q->>Q: Schritt 1: Text extrahieren (smalot/pdfparser)
  Q->>PG: raw_text speichern, status=processing

  Q->>LLM: Klassifikation (Auszug, max 2.000 Zeichen)
  LLM-->>Q: {document_type, confidence}
  Q->>PG: document_type aktualisieren

  Q->>LLM: Strukturierte Extraktion (max 24.000 Zeichen)
  LLM-->>Q: {title, summary, felder…}
  Q->>PG: Strukturfelder + extraction_version speichern

  Q->>Q: Schritt 4: Text in Chunks aufteilen
  Q->>PG: document_chunks speichern
  Q->>PG: status=processed, processed_at setzen
```

---

## 6. Datenfluss — Chat

```mermaid
sequenceDiagram
  participant U as Nutzer
  participant SPA as Vue SPA
  participant API as Laravel API
  participant PG as PostgreSQL
  participant LLM as Azure OpenAI

  U->>SPA: Frage eingeben
  SPA->>API: POST /api/chat-sessions/{id}/messages {content}
  API->>PG: User-Message speichern
  API->>PG: Scope auflösen (ein Dok oder alle)
  API->>PG: Strukturierte Felder laden (documents)
  API->>PG: Relevante Chunks via ILIKE-Suche laden
  API->>API: Grounded Prompt zusammenbauen
  API->>LLM: Chat Completion (Kontext + Guardrails)
  LLM-->>API: Antwort
  API->>API: Citations aus verwendeten Chunks extrahieren
  API->>PG: Assistent-Nachricht + citations_json speichern
  API-->>SPA: {content, citations_json}
  SPA-->>U: Antwort mit Quellen anzeigen
```

---

## 7. Deployment-Architektur (MVP)

```mermaid
graph TD
  subgraph Internet
    USR[Nutzer / Browser]
  end

  subgraph Azure ["Azure (Region: West Europe)"]
    subgraph Frontend ["Azure Static Web Apps"]
      SPA[Vue SPA\ndist/]
    end

    subgraph ACA ["Azure Container Apps Environment"]
      API[Laravel API Container\nPort 8000]
      WRK[Queue Worker Container\nBackground Job]
    end

    subgraph Data ["Data Services"]
      PG[(PostgreSQL\nFlexible Server)]
      RED[(Azure Cache\nfor Redis)]
      BLOB[(Blob Storage\nPrivate Container)]
    end

    KV[Azure Key Vault]
    ACR[Azure Container Registry]
    MON[Azure Monitor +\nApplication Insights]
  end

  subgraph External
    OAI[Azure OpenAI\nGPT-4o-mini]
  end

  USR -->|HTTPS| SPA
  USR -->|HTTPS API calls| API
  API --> PG
  API --> RED
  API --> BLOB
  API --> OAI
  API --> KV
  WRK --> PG
  WRK --> BLOB
  WRK --> OAI
  WRK --> RED
  API -.->|telemetry| MON
  WRK -.->|telemetry| MON
  ACR -.->|image pull| ACA
```

---

## 8. Komponentenverantwortlichkeiten

### Vue SPA
- Benutzeroberfläche
- Kein Dokumentinhalt wird gecacht
- Auth via Sanctum Personal Access Token; Token in `localStorage`, gesendet als `Authorization: Bearer <token>`
- Status-Polling für Verarbeitungsstatus

### Laravel API
- Auth via Sanctum Token-Auth (`auth:sanctum` Middleware)
- Ownership-Enforcement via Policies
- Upload-Validierung + Storage-Delegation
- Chat-Orchestrierung (Retrieval → Prompt → LLM → Citations)
- Strukturiertes Logging (kein PII, keine Dokumentinhalte)
- Audit-Log-Schreibung

### Queue Worker
- Asynchrone Dokumentverarbeitung
- Idempotentes Job-Handling (Retries sicher)
- Kein HTTP-Zugriff von außen
- Fehler → `document.status = failed` + `audit_log`

---

## 9. AI-Service-Abstraktion

Alle KI-relevanten Operationen laufen hinter Interfaces:

```
app/Services/AI/
  Contracts/
    TextExtractorInterface         → PdfTextExtractor (MVP)
    DocumentClassifierInterface    → AzureOpenAiClassifier (MVP)
    StructuredExtractorInterface   → AzureOpenAiExtractor (MVP)
    ChunkerInterface               → SimpleChunker (MVP)
    RetrieverInterface             → DbRetriever (MVP) → EmbeddingRetriever (Post-MVP)
    ChatAnswererInterface          → AzureOpenAiChatAnswerer (MVP)
```

**Ziel:** Laravel bleibt der produktive Backend-Core. KI-Logik kann später in einen dedizierten Python/FastAPI-Service ausgelagert werden, ohne API-Änderungen.

---

## 10. Skalierungsevolution

| Phase | Änderung |
|-------|---------|
| MVP | Keyword-Retrieval (PostgreSQL ILIKE), SimpleChunker |
| Post-MVP 1 | pgvector-Embeddings, semantische Suche |
| Post-MVP 2 | Dedizierter Python AI-Service (FastAPI) hinter Interface |
| Post-MVP 3 | Azure AI Search statt selbst-gehosteter Vektorsuche |

---

*Architekturdokument — DocumentScrapper Phase 0*
