# Security Baseline — DocumentScrapper MVP

> Letzte Aktualisierung: Phase-0 Analyse · 2026-03-13  
> Referenz: OWASP Top 10 2021, DSGVO Art. 32

---

## 1. Threat Model (STRIDE, vereinfacht)

| Bedrohung | Angriffsziel | Gegenmaßnahme |
|-----------|-------------|---------------|
| **Spoofing** | Session-Hijacking | HttpOnly Cookie, CSRF-Schutz (Sanctum) |
| **Tampering** | Dokument-Pfad manipulieren | UUID-basierte Pfade, Ownership-Check vor Zugriff |
| **Repudiation** | Aktionen nicht nachweisbar | `audit_logs` Tabelle, unveränderliche Einträge |
| **Information Disclosure** | Cross-User-Dokumente sehen | Laravel Policies + Query Scopes |
| **Denial of Service** | Upload-Flooding | Rate Limiting, Dateigröße-Limits |
| **Elevation of Privilege** | Admin-Endpunkte nutzen | Kein Admin-Interface in MVP; Rate Limiting |

---

## 2. Authentifizierung und Session

### Laravel Sanctum (SPA-Modus)

| Eigenschaft | Wert |
|-------------|------|
| Mechanismus | HttpOnly Cookie (keine Tokens im LocalStorage) |
| CSRF-Schutz | Sanctum Cookie + `X-XSRF-TOKEN` Header |
| Session-Lifetime | 120 Minuten (konfigurierbar) |
| Remember-Me | Nicht aktiviert in MVP |
| Cookie SameSite | `Lax` |
| Cookie Secure | `true` (nur HTTPS) |

### Registrierung und Zugangskontrolle

**Entscheidung (2026-03-13):** Nur Einladung (Invitation-only). Kein offenes Self-Sign-up in MVP.

| Aspekt | Entscheidung |
|--------|-------------|
| Registrierungsmodel | Nur via Einladungslink (invitation token) |
| Kein Self-Sign-up | Verhindert Missbrauch und ungewollte Datenverarbeitung |
| Kein Google/OAuth | E-Mail + Passwort, vereinfacht Auth-Stack |
| Einladungslink | Zeitlich begrenzt, single-use, server-seitig validiert |

### Passwortanforderungen

```php
Password::min(12)
       ->letters()
       ->numbers()
       ->mixedCase()
       ->uncompromised() // HaveIBeenPwned-Check
```

### Session-Invalidierung

- Bei Logout
- Bei Passwort-Änderung
- Bei erkanntem Missbrauch (Post-MVP)

---

## 3. Zugriffssteuerung

### Prinzipien

1. **Deny-by-default:** Alle API-Routen erfordern Auth (`auth:sanctum`)
2. **Ownership-First:** Jede Ressource prüft `user_id === auth()->id()`
3. **Policy-Pattern:** Eloquent Policies für alle Modelle
4. **No trust in client:** Alle Ownership-Checks sind serverseitig

### Gesicherte Endpunkte

| Route | Auth | Ownership-Check |
|-------|------|----------------|
| `GET /api/documents` | ✅ | ✅ (nur eigene) |
| `POST /api/documents` | ✅ | N/A (create own) |
| `GET /api/documents/:id` | ✅ | ✅ DocumentPolicy::view |
| `DELETE /api/documents/:id` | ✅ | ✅ DocumentPolicy::delete |
| `POST /api/chat-sessions` | ✅ | ✅ (document_id muss eigen sein) |
| `GET /api/chat-sessions/:id/messages` | ✅ | ✅ ChatSessionPolicy::view |
| `POST /api/chat-sessions/:id/messages` | ✅ | ✅ ChatSessionPolicy::view |
| `GET /api/health` | ❌ | N/A |
| `GET /api/ready` | ❌ | N/A |

---

## 4. Input-Validierung

### Upload-Validierung

```php
'file' => [
    'required',
    'file',
    'mimes:pdf',          // MIME-Type-Whitelist
    'max:20480',          // 20 MB
    // Kein php, exe, sh etc.
]
```

### Dateinamen-Bereinigung

```php
// Originaldateiname wird NUR für Anzeige gespeichert
// Storage-Pfad ist immer UUID-basiert
$storagePath = 'users/' . $userId . '/' . Str::uuid() . '.pdf';
```

### API-Input-Validierung

- Alle Endpunkte verwenden Laravel Form Requests
- `uuid` Validierung für alle ID-Parameter
- Maximale Längen für Strings
- `nullable` explizit deklariert

### SQL-Injection

- Nur Eloquent ORM und Query Builder verwendet
- Parameterisierte Queries
- Kein raw SQL mit Nutzereingaben

---

## 5. File Upload Security

### Validierungskette

```
1. Laravel mimes:pdf  → MIME-Type prüfen
2. Extension check    → .pdf Extension erzwingen
3. Size check         → max 20 MB
4. Content check      → pdfparser kann Datei öffnen
5. Storage           → UUID-basierter Pfad
6. Access control    → Nur via Auth-API zugänglich
```

### Storage-Sicherheit

- **Kein Public Access** auf Azure Blob Container
- Downloads nur nach Ownership-Check + SAS-Token (Post-MVP)
- **In MVP:** Download über Laravel `Storage::download()` nach Auth
- Directory Traversal: Unmöglich durch UUID-Pfade
- Dateinamen vom Nutzer werden niemals im Pfad verwendet

---

## 6. Security Headers

### Empfohlene HTTP-Response-Headers

```
Strict-Transport-Security: max-age=31536000; includeSubDomains
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=()
```

### Laravel-Konfiguration

```php
// config/cors.php
'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],
'supports_credentials' => true,
```

---

## 7. Rate Limiting

| Endpunkt | Limit | Fenster |
|----------|-------|---------|
| `POST /api/auth/login` | 5 | 1 Minute |
| `POST /api/documents` | 10 | 1 Stunde |
| `POST /api/chat-sessions/*/messages` | 30 | 1 Minute |
| Alle API-Routen | 120 | 1 Minute |

```php
Route::middleware(['throttle:login'])->group(function () {
    Route::post('/auth/login', ...);
});

RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});
```

---

## 8. Logging und Monitoring

### Application Logs (strukturiert, kein PII)

```json
{
  "level": "warning",
  "event": "auth.login_failed",
  "ip": "redacted",
  "timestamp": "2026-03-13T10:00:00Z"
}
```

### Audit Logs (unveränderlich in DB)

| Aktion | Zeitpunkt |
|--------|-----------|
| `auth.login` | Jeder Login |
| `auth.logout` | Jeder Logout |
| `document.upload_initiated` | Nach erfolgreichem Upload |
| `document.processing_failed` | Nach Job-Failure |
| `document.deleted` | Vor Löschung |
| `chat_session.created` | Neue Chat-Sitzung |
| `chat_message.sent` | Jede Nutzer-Nachricht |

**Wichtig:** Audit-Logs enthalten niemals Dokumentinhalt, Prompts oder LLM-Antworten.

### Azure Monitor

- Unhandled Exceptions → Application Insights
- Performance-Metriken: API-Latenz, Queue-Tiefe
- Alerting: Fehlerrate > 5% in 5 Minuten

---

## 9. Secrets Management

### Verboten

```
❌ Secrets in Quellcode
❌ Secrets in .env (committed)
❌ Secrets in Docker Images
❌ Secrets in Logs oder Error Messages
❌ API-Keys in HTML/JavaScript ausgeliefert
```

### Erlaubt (MVP)

```
✅ Azure Key Vault (Produktion)
✅ .env.local (nur lokal, im .gitignore)
✅ GitHub Actions Secrets (CI/CD)
```

### Key Vault Secrets

| Secret Name | Inhalt |
|-------------|--------|
| `db-password` | PostgreSQL Passwort |
| `redis-password` | Redis Auth-Passwort |
| `azure-openai-key` | Azure OpenAI API Key |
| `app-key` | Laravel APP_KEY |
| `sanctum-secret` | Session Signing Secret |

---

## 10. Dependency Security

```bash
# PHP
composer audit            # Known CVEs in Composer dependencies

# JavaScript
npm audit                 # Known CVEs in npm dependencies

# CI: wöchentlich automatisch via GitHub Actions Dependabot
```

---

## 11. DSGVO-Security-Anforderungen (Art. 32)

| Maßnahme | Status MVP |
|---------|-----------|
| Verschlüsselung in Transit (TLS 1.2+) | ✅ Erzwungen |
| Verschlüsselung at-rest | ✅ Azure-Managed-Keys |
| Pseudonymisierung | ✅ UUID-basierte IDs |
| Verfügbarkeit (Backups) | ✅ Azure Backup |
| Belastbarkeit | ⬜ Single-Server MVP |
| Wiederherstellung | ⬜ Documented Recovery Plan (Post-MVP) |
| Regelmäßige Tests | ⬜ Penetration Test (Post-MVP vor Go-Live) |

---

## 12. Deployment-Security (Azure)

| Maßnahme | Implementierung |
|---------|----------------|
| Container läuft als non-root | `USER www-data` in Dockerfile |
| Read-Only Filesystem | Container Apps Konfiguration |
| Network Policy | Container Apps VNet Integration (Post-MVP) |
| Private Endpoints | PostgreSQL + Redis nur intern erreichbar |
| Managed Identity | Für Key Vault + Blob Storage (kein SPN) |
| Image Scanning | Azure Defender for Containers |

---

*Security Baseline — DocumentScrapper Phase 0*
