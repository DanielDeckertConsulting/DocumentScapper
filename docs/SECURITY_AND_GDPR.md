# Security und DSGVO — DocumentScrapper MVP

## 1. Authentifizierung und Session-Management

### Ansatz: Laravel Sanctum (SPA-Modus)

- Cookie-basierte Session-Auth für Vue SPA
- CSRF-Schutz via Sanctum Cookie
- Session-Timeout konfigurierbar
- Kein JWT im MVP (simplifiziert Security-Verwaltung)

### Passwort-Anforderungen

- Minimum 12 Zeichen (konfigurierbar)
- Laravel `Hash::make()` mit bcrypt
- Password Reset via E-Mail (Laravel Standard)

---

## 2. User Isolation

**Fundamentales Prinzip:** Jeder Nutzer sieht ausschließlich eigene Daten.

### Implementierung

1. **Laravel Policies** für alle Ressourcen:
   - `DocumentPolicy::view()` → `user_id === auth()->id()`
   - `ChatSessionPolicy::view()` → analog
2. **Query Scopes** auf allen Eloquent Models mit `user_id`
3. **Middleware** `EnsureDocumentOwnership` für Dokument-Routen
4. **Keine Ressourcen-IDs erraten** — UUIDs überall

### Verbotene Operationen

- Direktes SQL ohne `WHERE user_id = :id`
- Ressourcen-Listing ohne User-Filter
- File-Pfad-Auflösung ohne Ownership-Check

---

## 3. Datei-Upload-Sicherheit

### Validierungen

```php
// Erlaubte MIME Types
$allowed = ['application/pdf'];

// Regeln
'file' => [
    'required',
    'file',
    'mimes:pdf',
    'max:20480', // 20 MB
    Rule::notIn(['application/x-php', 'text/html']),
]
```

### Storage-Sicherheit

- Dateien werden **niemals** öffentlich zugänglich abgelegt
- Storage-Pfad ist nicht direkt erratbar (UUID-basiert)
- Download nur über authentifizierten API-Endpunkt mit Ownership-Check
- Kein direkter Zugriff auf Storage-Ordner via Webserver

### Dateinamen-Bereinigung

```php
$safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
$storagePath = 'documents/' . $userId . '/' . Str::uuid() . '.' . $ext;
```

---

## 4. Logging und PII-Schutz

### Prinzip: Keine Dokumentinhalte in Logs

**Erlaubt in Logs:**
- Dokument-ID (UUID)
- Status-Änderungen
- Technische Fehlercodes
- User-ID (opaque)
- Timestamps

**Verboten in Logs:**
- `raw_text` oder Chunks
- Extrahierte Namen, Adressen, Beträge
- Dateiinhalte
- LLM-Prompts oder Antworten

### Beispiel-Log-Format

```json
{
  "level": "info",
  "action": "document.processing_completed",
  "document_id": "uuid-here",
  "user_id": "uuid-here",
  "extraction_version": "v1-gpt4o-mini",
  "duration_ms": 4200
}
```

---

## 5. API-Security

### Schutzmaßnahmen

- **CORS:** Nur eigene Frontend-Domain erlaubt
- **Rate Limiting:** `throttle:60,1` auf Auth-Endpunkten
- **Input Validation:** Alle Inputs über Laravel Form Requests
- **SQL Injection:** Nur Eloquent/Query Builder (parameterisiert)
- **CSRF:** Sanctum-Cookie-Schutz
- **Sensible Antworten:** Keine Stack Traces in Production-Responses

### Security Headers (Empfehlung)

```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Content-Security-Policy: default-src 'self'
Referrer-Policy: strict-origin-when-cross-origin
```

---

## 6. KI-Datenschutz

### Prompt-Hygiene

- Prompts enthalten nur technisch notwendigen Dokumenttext
- Kein Logging von Prompts oder LLM-Antworten in Anwendungs-Logs
- LLM-API: OpenAI Data Privacy (kein Training auf API-Daten)

### Datenminimierung

- Nur der für die Klassifikation notwendige Text-Ausschnitt wird übergeben
- Chat-Retrieval begrenzt auf 5 relevante Chunks

---

## 7. Datenlöschung

### Dokumenten-Löschung

Wenn ein Nutzer ein Dokument löscht:
1. Dokument-Record wird gelöscht (CASCADE auf alle abhängigen Tabellen)
2. Original-Datei wird aus Storage gelöscht
3. Chunks werden gelöscht
4. Structured Data wird gelöscht
5. Chat-Sessions bleiben erhalten (document_id → NULL via `ON DELETE SET NULL`)
6. `audit_log` Eintrag wird erstellt

### Account-Löschung (Post-MVP)

- Alle `documents`, `chat_sessions`, `audit_logs` werden gelöscht
- Storage-Ordner des Nutzers wird gelöscht
- Implementierung in Post-MVP-Phase

---

## 8. DSGVO-Anforderungen (MVP-Baseline)

| Anforderung | MVP-Umsetzung |
|-------------|---------------|
| Rechtmäßigkeit | Nutzungsvertrag / Einwilligung via Registrierung |
| Datensparsamkeit | Nur notwendige Felder extrahiert; kein PII in Logs |
| Zweckbindung | Dokumente nur für Nutzer-eigene Verwendung |
| Auskunftsrecht | Account-Daten über API abrufbar (geplant) |
| Löschrecht | Dokumente einzeln löschbar; Account-Löschung Post-MVP |
| Datensicherheit | Verschlüsselung bei Übertragung (HTTPS), Auth, Isolation |
| Auftragsverarbeitung | OpenAI AVV (Auftragsverarbeitungsvertrag) einholen |

### Datenschutzhinweis im UI

- Einfacher Privacy-Hinweis beim ersten Login
- Hinweis: "Keine Rechtsberatung durch das System"

---

## 9. Audit Logs

Folgende Aktionen werden im `audit_logs`-Protokoll festgehalten:

| Aktion | Trigger |
|--------|---------|
| `auth.login` | Erfolgreicher Login |
| `auth.logout` | Logout |
| `document.upload_initiated` | Datei hochgeladen |
| `document.processing_failed` | Job schlägt fehl |
| `document.deleted` | Nutzer löscht Dokument |
| `chat_session.created` | Neue Chat-Sitzung |
| `chat_message.sent` | Nutzer stellt Frage |

Audit-Logs enthalten **keine** Dokumentinhalte.

---

## 10. Deployment-Security (Minimal für MVP)

- `.env`-Datei wird nicht committed
- `APP_ENV=production` deaktiviert Debug-Output
- `APP_DEBUG=false` in Prod
- Secrets nur aus Umgebungsvariablen
- Database-Passwort nicht in Source Code

---

*Letzte Aktualisierung: Phase 0 Bootstrap*
