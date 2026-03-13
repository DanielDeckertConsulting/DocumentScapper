# API-Spezifikation — DocumentScrapper MVP

## Basis-URL

```
http://localhost:8000/api
```

Alle Endpunkte erfordern `Accept: application/json`.

---

## Authentifizierung

Alle geschützten Endpunkte benötigen den Header:

```
Authorization: Bearer <token>
```

Der Token wird beim Login zurückgegeben und muss client-seitig gespeichert werden (z. B. `localStorage`).

### Login

```
POST /auth/login
```

**Request:**
```json
{
  "email": "user@example.com",
  "password": "securepassword"
}
```

**Response 200:**
```json
{
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "user": {
    "id": "uuid",
    "name": "Max Mustermann",
    "email": "user@example.com"
  }
}
```

**Response 422:**
```json
{
  "message": "Diese Anmeldedaten sind nicht korrekt.",
  "errors": { "email": ["..."] }
}
```

### Logout

```
POST /auth/logout
Authorization: Bearer <token>
```

Löscht den aktuellen Token serverseitig.

**Response 204**

### Aktuellen Nutzer abrufen

```
GET /auth/user
```

**Response 200:**
```json
{
  "id": "uuid",
  "name": "Max Mustermann",
  "email": "user@example.com",
  "created_at": "2025-01-01T00:00:00Z"
}
```

---

## Dokumente

### Dokumente auflisten

```
GET /documents
```

**Response 200:**
```json
{
  "data": [
    {
      "id": "uuid",
      "original_filename": "haftpflicht_2024.pdf",
      "mime_type": "application/pdf",
      "size_bytes": 1234567,
      "status": "processed",
      "document_type": "haftpflichtversicherung",
      "title": "Haftpflichtversicherung 2024",
      "summary": "Haftpflichtversicherung mit 10 Mio. Deckung...",
      "processed_at": "2025-01-01T10:00:00Z",
      "created_at": "2025-01-01T09:00:00Z"
    }
  ]
}
```

### Dokument hochladen

```
POST /documents
Content-Type: multipart/form-data
```

**Request:**
```
file: <binary PDF>
```

**Response 201:**
```json
{
  "data": {
    "id": "uuid",
    "original_filename": "haftpflicht_2024.pdf",
    "status": "uploaded",
    "created_at": "2025-01-01T09:00:00Z"
  }
}
```

**Response 422:**
```json
{
  "message": "Validation failed.",
  "errors": {
    "file": ["Nur PDF-Dateien sind erlaubt.", "Maximale Dateigröße: 20 MB"]
  }
}
```

### Dokument-Detail abrufen

```
GET /documents/{id}
```

**Response 200:**
```json
{
  "data": {
    "id": "uuid",
    "original_filename": "haftpflicht_2024.pdf",
    "mime_type": "application/pdf",
    "size_bytes": 1234567,
    "status": "processed",
    "document_type": "haftpflichtversicherung",
    "title": "Haftpflichtversicherung Beispiel AG",
    "summary": "Haftpflichtversicherung für Privatpersonen...",
    "extraction_version": "v1-gpt4o-mini",
    "processed_at": "2025-01-01T10:00:00Z",
    "created_at": "2025-01-01T09:00:00Z",
    "counterparty_name": "Beispiel AG",
    "contract_holder_name": "Max Mustermann",
    "contract_number": "HV-2024-000123",
    "policy_number": null,
    "start_date": "2024-01-01",
    "end_date": "2024-12-31",
    "duration_text": "1 Jahr",
    "cancellation_period": "3 Monate zum Jahresende",
    "payment_amount": 120.00,
    "payment_currency": "EUR",
    "payment_interval": "jährlich",
    "important_terms": "Deckung bis 10 Mio. EUR...",
    "exclusions": "Vorsätzliche Handlungen...",
    "contact_details": "service@beispiel-ag.de",
    "custom_fields_json": {}
  }
}
```

**Response 403:** Dokument gehört nicht dem Nutzer
**Response 404:** Dokument nicht gefunden

### Dokument löschen

```
DELETE /documents/{id}
```

**Response 204**

---

## Chat-Sitzungen

### Sitzungen auflisten

```
GET /chat-sessions
```

**Response 200:**
```json
{
  "data": [
    {
      "id": "uuid",
      "document_id": "uuid-or-null",
      "title": "Fragen zur Haftpflicht",
      "created_at": "2025-01-01T10:00:00Z"
    }
  ]
}
```

### Sitzung erstellen

```
POST /chat-sessions
```

**Request:**
```json
{
  "document_id": "uuid",     // Optional: null = alle Dokumente
  "title": "Meine Fragen"   // Optional
}
```

**Response 201:**
```json
{
  "data": {
    "id": "uuid",
    "document_id": "uuid",
    "title": "Meine Fragen",
    "created_at": "2025-01-01T10:00:00Z"
  }
}
```

### Nachrichten einer Sitzung abrufen

```
GET /chat-sessions/{sessionId}/messages
```

**Response 200:**
```json
{
  "data": [
    {
      "id": "uuid",
      "role": "user",
      "content": "Was ist meine Kündigungsfrist?",
      "citations_json": [],
      "created_at": "2025-01-01T10:01:00Z"
    },
    {
      "id": "uuid",
      "role": "assistant",
      "content": "Laut dem Dokument beträgt Ihre Kündigungsfrist 3 Monate...",
      "citations_json": [
        {
          "document_id": "uuid",
          "document_title": "Haftpflichtversicherung Beispiel AG",
          "chunk_index": 3,
          "page_reference": 2,
          "excerpt": "Die Kündigung muss 3 Monate vor Vertragsende eingehen."
        }
      ],
      "created_at": "2025-01-01T10:01:05Z"
    }
  ]
}
```

### Nachricht senden

```
POST /chat-sessions/{sessionId}/messages
```

**Request:**
```json
{
  "content": "Was ist meine Kündigungsfrist?"
}
```

**Response 201:**
```json
{
  "data": {
    "id": "uuid",
    "role": "assistant",
    "content": "Laut dem Dokument beträgt Ihre Kündigungsfrist 3 Monate...",
    "citations_json": [
      {
        "document_id": "uuid",
        "document_title": "Haftpflichtversicherung Beispiel AG",
        "chunk_index": 3,
        "page_reference": 2,
        "excerpt": "Die Kündigung muss 3 Monate vor Vertragsende eingehen."
      }
    ],
    "created_at": "2025-01-01T10:01:05Z"
  }
}
```

---

## System-Endpunkte

### Health Check

```
GET /health
```

**Response 200:**
```json
{
  "status": "ok",
  "timestamp": "2025-01-01T10:00:00Z"
}
```

### Readiness Check

```
GET /ready
```

**Response 200:**
```json
{
  "status": "ready",
  "database": "ok",
  "storage": "ok"
}
```

**Response 503:**
```json
{
  "status": "not_ready",
  "database": "error",
  "storage": "ok"
}
```

---

## Fehler-Format (konsistent)

```json
{
  "message": "Beschreibung des Fehlers",
  "errors": {
    "field": ["Fehlermeldung"]
  }
}
```

| HTTP Code | Bedeutung |
|-----------|-----------|
| 200 | OK |
| 201 | Erstellt |
| 204 | Gelöscht (kein Body) |
| 401 | Nicht authentifiziert |
| 403 | Kein Zugriff (falscher Nutzer) |
| 404 | Nicht gefunden |
| 422 | Validierungsfehler |
| 429 | Rate Limit |
| 500 | Serverfehler |

---

*Letzte Aktualisierung: Phase 0 Bootstrap*
