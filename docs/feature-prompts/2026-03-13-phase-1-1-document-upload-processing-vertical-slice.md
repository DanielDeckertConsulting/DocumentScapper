# Prompt Documentation — Phase 1.1: Document Upload + Processing + Detail View

## Metadata
- **Date:** `2026-03-13T00:00:00`
- **Command:** `#ship-feature-auto`
- **Feature:** `Phase 1.1 — First Vertical Slice: Document Upload + Processing + Detail View E2E`

## Intent
- Erster vollständiger End-to-End-Slice: UI Upload → API → File Storage → Async Processing → Text-Extraktion → Strukturierte Extraktion → Document Detail View
- Beweis, dass das vollständige Produkt-Backbone vor dem Chat-Feature funktioniert
- GDPR-first Handling von hochgeladenen Nutzerdokumenten
- User-Isolation server-seitig erzwungen
- Extractions-Service-Abstraktion für späteren Python/FastAPI-Split

## Prompt (Verbatim)
```md
PHASE 1.1 — First Vertical Slice: Document Upload + Processing + Detail View E2E (User-Isolated, GDPR-first)

GOAL:
Implement the first real end-to-end slice for the product:
UI Upload -> API -> file storage -> async processing -> text extraction -> structured extraction -> persisted result -> document detail view.

This slice should prove the full product backbone works before chat is added.

CONSTRAINTS:
- Vue frontend
- Laravel backend
- PostgreSQL
- PDF only
- No prompt/response or raw document content in logs
- User isolation enforced server-side
- No Kubernetes; keep minimal
- Architecture must stay ready for later AI-service extraction split
- GDPR-first handling of uploaded user documents

[... full prompt as submitted ...]
```

## Outcome Summary
- **Modus:** HEALTHCARE (PII + GDPR-first + sensitives Dokument-Handling)
- **Backend komplett:** Alle Controller, Models, Migrationen, Jobs, Services, Tests
- **Frontend komplett:** DocumentsPage mit Drag & Drop, DocumentDetailPage mit Chat-CTA-Placeholder
- **Neue Lücken geschlossen:**
  - `config/filesystems.php` mit `document_disk` angelegt
  - `bootstrap/app.php` ungenutzter HandleCors-Import entfernt
  - `PdfTextExtractor` verwendet jetzt konsistent `document_disk`
  - `DatabaseSeeder.php` mit Demo-Nutzer erstellt
  - `DocumentUploadTest.php` (7 Tests: Upload-Erfolg, MIME-Validierung, Größe, Auth)
  - `ProcessDocumentJobTest.php` (5 Tests: Erfolg, Fehlerpfade, Audit-Log-Inhalt, Idempotenz)
  - Drag & Drop Upload auf DocumentsPage
  - "Chat folgt"-CTA-Platzhalter auf DocumentDetailPage
- **README** mit Upload-Flow, Queue-Worker-Anweisungen, bekannten Limitierungen erweitert
- **TypeScript-Check:** ✓ sauber
- **Frontend-Build:** ✓ erfolgreich
