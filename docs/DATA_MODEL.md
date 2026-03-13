# Datenmodell — DocumentScrapper MVP

## Überblick

Das Datenmodell besteht aus sieben Kerntabellen, die den vollständigen Lebenszyklus von Dokumenten, strukturierter Extraktion, Chat und Audit abbilden.

```
users
  └── documents (user_id)
        ├── document_structured_data (document_id)
        ├── document_chunks (document_id)
        └── chat_sessions (document_id nullable, user_id)
              └── chat_messages (chat_session_id)
audit_logs (user_id nullable)
```

---

## Tabellen

### users

Wird von Laravel Auth/Sanctum verwaltet.

```sql
CREATE TABLE users (
  id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name         VARCHAR(255) NOT NULL,
  email        VARCHAR(255) UNIQUE NOT NULL,
  password     VARCHAR(255) NOT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT now(),
  updated_at   TIMESTAMP NOT NULL DEFAULT now()
);
```

---

### documents

Kern-Entität. Enthält Metadaten, Rohdaten und Extraktionsfelder in einer Tabelle.

```sql
CREATE TABLE documents (
  id                    UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id               UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,

  -- Datei-Metadaten
  original_filename     VARCHAR(500) NOT NULL,
  mime_type             VARCHAR(100) NOT NULL,
  size_bytes            BIGINT NOT NULL,
  storage_path          TEXT NOT NULL,

  -- Verarbeitungs-Status
  status                VARCHAR(50) NOT NULL DEFAULT 'uploaded',
                        -- uploaded | processing | processed | failed
  processing_error      TEXT,
  processed_at          TIMESTAMP,
  extraction_version    VARCHAR(50),

  -- Extrahierte Kerndaten
  document_type         VARCHAR(100),
  title                 TEXT,
  summary               TEXT,
  raw_text              TEXT,

  -- Normalisierte Strukturfelder
  counterparty_name     TEXT,
  contract_holder_name  TEXT,
  contract_number       TEXT,
  policy_number         TEXT,
  start_date            DATE,
  end_date              DATE,
  duration_text         TEXT,
  cancellation_period   TEXT,
  payment_amount        NUMERIC(15,2),
  payment_currency      VARCHAR(10),
  payment_interval      VARCHAR(50),
  important_terms       TEXT,
  exclusions            TEXT,
  contact_details       TEXT,

  -- Flexible Erweiterungsfelder
  custom_fields_json    JSONB NOT NULL DEFAULT '{}',

  created_at            TIMESTAMP NOT NULL DEFAULT now(),
  updated_at            TIMESTAMP NOT NULL DEFAULT now()
);

CREATE INDEX idx_documents_user_id ON documents(user_id);
CREATE INDEX idx_documents_status ON documents(status);
```

**Status-Übergänge:**
```
uploaded → processing → processed
                      → failed
```

---

### document_structured_data

Separate Tabelle für zukünftige Versioning oder Mehrfachextraktionen.
Im MVP primary use case: 1:1 zu `documents`.

```sql
CREATE TABLE document_structured_data (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  document_id     UUID NOT NULL REFERENCES documents(id) ON DELETE CASCADE,
  extraction_run  INT NOT NULL DEFAULT 1,
  extractor       VARCHAR(100) NOT NULL DEFAULT 'openai-gpt4o-mini',
  raw_response    JSONB,
  is_latest       BOOLEAN NOT NULL DEFAULT TRUE,
  created_at      TIMESTAMP NOT NULL DEFAULT now(),
  updated_at      TIMESTAMP NOT NULL DEFAULT now()
);

CREATE INDEX idx_doc_structured_document_id ON document_structured_data(document_id);
```

---

### document_chunks

Fragmentierter Dokumentinhalt für Retrieval und Chat-Kontext.

```sql
CREATE TABLE document_chunks (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  document_id     UUID NOT NULL REFERENCES documents(id) ON DELETE CASCADE,
  chunk_index     INT NOT NULL,
  chunk_text      TEXT NOT NULL,
  page_reference  INT,
  token_count     INT,
  created_at      TIMESTAMP NOT NULL DEFAULT now(),
  updated_at      TIMESTAMP NOT NULL DEFAULT now(),
  UNIQUE (document_id, chunk_index)
);

CREATE INDEX idx_chunks_document_id ON document_chunks(document_id);
```

---

### chat_sessions

Chat-Sitzung eines Nutzers. Kann auf ein einzelnes Dokument oder alle Dokumente des Nutzers referenzieren.

```sql
CREATE TABLE chat_sessions (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id         UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  document_id     UUID REFERENCES documents(id) ON DELETE SET NULL,
                  -- NULL = "alle Dokumente"
  title           VARCHAR(500),
  created_at      TIMESTAMP NOT NULL DEFAULT now(),
  updated_at      TIMESTAMP NOT NULL DEFAULT now()
);

CREATE INDEX idx_chat_sessions_user_id ON chat_sessions(user_id);
CREATE INDEX idx_chat_sessions_document_id ON chat_sessions(document_id);
```

---

### chat_messages

Einzelne Nachrichten in einer Chat-Sitzung.

```sql
CREATE TABLE chat_messages (
  id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  chat_session_id  UUID NOT NULL REFERENCES chat_sessions(id) ON DELETE CASCADE,
  role             VARCHAR(20) NOT NULL,
                   -- user | assistant | system
  content          TEXT NOT NULL,
  citations_json   JSONB NOT NULL DEFAULT '[]',
  created_at       TIMESTAMP NOT NULL DEFAULT now(),
  updated_at       TIMESTAMP NOT NULL DEFAULT now()
);

CREATE INDEX idx_chat_messages_session_id ON chat_messages(chat_session_id);
```

**Citations-Format (JSON-Array):**
```json
[
  {
    "document_id": "...",
    "document_title": "Haftpflichtversicherung 2024",
    "chunk_index": 3,
    "excerpt": "Die Kündigungsfrist beträgt 3 Monate..."
  }
]
```

---

### audit_logs

Unveränderliche Audit-Aufzeichnungen für sicherheitsrelevante Operationen.

```sql
CREATE TABLE audit_logs (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id     UUID REFERENCES users(id) ON DELETE SET NULL,
  action      VARCHAR(100) NOT NULL,
  entity_type VARCHAR(100),
  entity_id   UUID,
  meta_json   JSONB NOT NULL DEFAULT '{}',
  created_at  TIMESTAMP NOT NULL DEFAULT now()
);

CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_action ON audit_logs(action);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);
```

**Vordefinierte Aktionen:**
- `document.upload_initiated`
- `document.processing_failed`
- `document.deleted`
- `chat_session.created`
- `chat_message.sent`
- `auth.login`
- `auth.logout`

---

## Ownership-Regeln

| Tabelle | Zugriff beschränkt auf |
|---------|----------------------|
| documents | `user_id = auth()->id()` |
| document_structured_data | via `documents.user_id` |
| document_chunks | via `documents.user_id` |
| chat_sessions | `user_id = auth()->id()` |
| chat_messages | via `chat_sessions.user_id` |
| audit_logs | nur lesbar durch eigene `user_id` |

---

## Migrations-Reihenfolge

1. `create_users_table` (Standard Laravel)
2. `create_documents_table`
3. `create_document_structured_data_table`
4. `create_document_chunks_table`
5. `create_chat_sessions_table`
6. `create_chat_messages_table`
7. `create_audit_logs_table`

---

*Letzte Aktualisierung: Phase 0 Bootstrap*
