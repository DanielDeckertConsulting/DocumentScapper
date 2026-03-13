# Test-Strategie — DocumentScrapper MVP

## Prinzipien

1. **Vertrauen schaffen** — Tests sollen das Kernverhalten schützen, nicht 100% Coverage erzwingen
2. **Golden Path zuerst** — Upload → Verarbeitung → Chat muss immer getestet sein
3. **Ownership-Tests obligatorisch** — Cross-User-Zugriff muss in jedem kritischen Pfad getestet werden
4. **Keine echten API-Calls in Unit-Tests** — LLM und externe Services werden gemockt

---

## Test-Pyramide

```
                    ┌─────────┐
                    │  E2E    │ (Playwright, wenige, kritische Flows)
                  ┌─┴─────────┴─┐
                  │  Feature    │ (Laravel Feature Tests, HTTP-Level)
                ┌─┴─────────────┴─┐
                │   Unit Tests    │ (Services, Jobs, Policies)
              └───────────────────┘
```

---

## Backend Tests (PHPUnit)

### Unit Tests

| Zu testende Klasse | Testfälle |
|-------------------|-----------|
| `ProcessDocumentJob` | Success, leere Datei, API-Fehler, Idempotenz |
| `PdfTextExtractor` | Text-basierte PDF, verschlüsselte PDF |
| `OpenAiStructuredExtractor` | Vollständige Extraktion, Teilextraktion, null-Felder |
| `SimpleChunker` | Korrekte Chunk-Größe, Ordering, Überlappung |
| `DocumentPolicy` | Besitzer hat Zugriff, Fremder hat keinen Zugriff |
| `ChatSessionPolicy` | Analog DocumentPolicy |

### Feature Tests (HTTP-Level)

| Endpunkt | Testfälle |
|----------|-----------|
| `POST /auth/login` | Erfolg, falsches Passwort, fehlende Felder |
| `POST /documents` | PDF-Upload-Erfolg, ungültiger MIME-Type, zu große Datei |
| `GET /documents` | Nur eigene Dokumente, leere Liste |
| `GET /documents/{id}` | Eigenes Dokument, fremdes Dokument (403), nicht gefunden (404) |
| `DELETE /documents/{id}` | Erfolg, fremdes Dokument (403) |
| `POST /chat-sessions` | Erfolg (single doc), Erfolg (alle docs), fremdes Dokument |
| `POST /chat-sessions/{id}/messages` | Antwort erhalten, leere Frage, fremde Session (403) |
| `GET /health` | Status ok |

### Ownership-Tests (Critical)

```php
/** @test */
public function user_cannot_access_another_users_document()
{
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $document = Document::factory()->for($owner)->create();

    $this->actingAs($other)
         ->getJson("/api/documents/{$document->id}")
         ->assertForbidden();
}

/** @test */
public function user_cannot_chat_in_another_users_session()
{
    // ...analog
}
```

---

## Frontend Tests (Vitest)

| Komponente/Store | Testfälle |
|-----------------|-----------|
| `useAuthStore` | Login, Logout, Unauthenticated state |
| `useDocumentsStore` | Upload-Flow, Polling-Logik, Error-State |
| `DocumentUploadForm` | File Picker, Validation-Feedback |
| `ChatPage` | Message senden, Citations rendern |
| API-Client | 401-Interceptor leitet zu Login |

---

## E2E Tests (Playwright)

### Golden Path (kritisch, muss immer grün sein)

```gherkin
Scenario: Vollständiger Happy Path
  Given Ich bin eingeloggt
  When Ich ein PDF hochlade
  And Ich auf "verarbeitet" warte
  Then Sehe ich die extrahierten Felder
  When Ich eine Chat-Sitzung öffne
  And Ich eine Frage stelle
  Then Erhalte ich eine Antwort mit Citation
```

### Weitere E2E-Szenarien

- Login / Logout
- Upload mit ungültigem Format (Fehlermeldung)
- Dokument löschen
- Chat über alle Dokumente

---

## Mocking-Strategie

### LLM-Mocking

```php
// In Tests
$this->mock(DocumentClassifierInterface::class, function ($mock) {
    $mock->shouldReceive('classify')->andReturn(
        new ClassificationResult('haftpflichtversicherung', 0.95)
    );
});
```

### Storage-Mocking

```php
Storage::fake('documents');
```

### Queue-Mocking

```php
Queue::fake();
// ...
Queue::assertPushed(ProcessDocumentJob::class);
```

---

## Test-Daten

- Alle Test-Fixtures verwenden **Faker** mit deutschsprachigen Daten
- Kein echtes PII in Tests
- Factory-Klassen für alle Eloquent-Modelle
- Beispiel-PDF (10 KB text-basiert) für Upload-Tests

---

## CI/CD Test-Pipeline

```yaml
# .github/workflows/test.yml
steps:
  - name: Backend Lint (Pint)
    run: cd api && ./vendor/bin/pint --test

  - name: Backend Tests
    run: cd api && php artisan test --coverage-min=60

  - name: Frontend Lint
    run: cd web && npm run lint

  - name: Frontend Tests
    run: cd web && npm run test

  - name: Frontend Build
    run: cd web && npm run build
```

---

## Test-Kommandos

```bash
# Backend Unit + Feature Tests
cd api && php artisan test

# Mit Coverage
cd api && php artisan test --coverage

# Einzelne Test-Klasse
cd api && php artisan test --filter=DocumentUploadTest

# Frontend Tests
cd web && npm run test

# E2E (Playwright)
cd web && npx playwright test

# E2E (Golden Path only)
cd web && npx playwright test --grep @goldenpath
```

---

## Definition of Done für Tests

- Jeder neue API-Endpunkt: mind. 1 Success + 1 Ownership-Test
- Jeder neue Service/Job: Unit-Test vorhanden
- Jede Ownership-Grenze: explizit getesteter 403-Fall
- Golden Path E2E: läuft durch

---

*Letzte Aktualisierung: Phase 0 Bootstrap*
