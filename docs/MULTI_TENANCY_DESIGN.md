# Multi-Tenancy & Row Level Security — DocumentScrapper

> Letzte Aktualisierung: Phase-0 Analyse · 2026-03-13

---

## 1. MVP-Strategie: User-Level-Isolation

Im MVP gibt es **keine Organisationen oder Mandanten** — jeder Nutzer ist sein eigener "Tenant". Trotzdem wird die Architektur so aufgebaut, dass echter Multi-Tenancy-Support ohne grundlegende Umstrukturierung ergänzt werden kann.

### MVP-Isolation (aktiv)

```
Isolation Level: USER
Jeder Nutzer sieht und verwaltet ausschließlich eigene Dokumente, Chats, Extrakte.
```

### Post-MVP-Isolation (vorbereitet)

```
Isolation Level: TENANT (Organisation)
Nutzer gehören einer Organisation an.
Innerhalb der Organisation: optional Rollen (Admin, Viewer).
```

---

## 2. Datenbankstruktur für Tenancy-Evolution

### MVP: Nur `user_id` als Isolationsschlüssel

Alle ownership-relevanten Tabellen haben `user_id`:

```sql
documents.user_id      UUID NOT NULL REFERENCES users(id)
chat_sessions.user_id  UUID NOT NULL REFERENCES users(id)
audit_logs.user_id     UUID REFERENCES users(id)
```

### Post-MVP: `tenant_id` ergänzen

Für echte Multi-Tenancy wird `tenant_id` eingeführt:

```sql
-- Neue Tabelle
CREATE TABLE tenants (
  id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name         VARCHAR(255) NOT NULL,
  slug         VARCHAR(100) UNIQUE NOT NULL,
  is_active    BOOLEAN NOT NULL DEFAULT TRUE,
  created_at   TIMESTAMP NOT NULL DEFAULT now(),
  updated_at   TIMESTAMP NOT NULL DEFAULT now()
);

-- Users erhalten tenant_id
ALTER TABLE users ADD COLUMN tenant_id UUID REFERENCES tenants(id);

-- Dokumente über tenant_id isolieren
ALTER TABLE documents ADD COLUMN tenant_id UUID NOT NULL REFERENCES tenants(id);
```

**Migrations-Strategie bei Einführung:**
1. `tenants` Tabelle anlegen
2. Default-Tenant pro existierendem Nutzer anlegen
3. `tenant_id` Spalten mit NOT NULL DEFAULT befüllen
4. RLS-Policies aktivieren (siehe unten)

---

## 3. PostgreSQL Row Level Security (RLS)

RLS erzwingt Datenisolation auf Datenbankebene — eine zweite Schutzlinie neben Application-Level-Policies.

### Aktivierung (Post-MVP vorbereitet)

```sql
-- Schritt 1: RLS auf Tabelle aktivieren
ALTER TABLE documents ENABLE ROW LEVEL SECURITY;

-- Schritt 2: Policy für authentifizierten Datenbanknutzer
CREATE POLICY documents_user_isolation ON documents
  FOR ALL
  TO app_user
  USING (user_id = current_setting('app.current_user_id')::uuid);

-- Schritt 3: App setzt user_id vor jeder Abfrage
SET LOCAL app.current_user_id = '<uuid>';
```

### Laravel-Integration für RLS

```php
// app/Database/RlsConnectionMiddleware.php
class RlsConnectionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            DB::statement("SET LOCAL app.current_user_id = ?", [$user->id]);
        }
        return $next($request);
    }
}
```

### RLS-Strategie im MVP vs. Post-MVP

| Feature | MVP | Post-MVP |
|---------|-----|---------|
| App-Level Policies | ✅ Aktiv (Laravel Policies) | ✅ Bleibt |
| RLS auf DB-Ebene | ⬜ Tabellen-RLS deaktiviert | ✅ Aktiviert |
| `tenant_id` in Tabellen | ⬜ Nicht vorhanden | ✅ Migriert |
| Query-Scopes | ✅ `WHERE user_id = ?` | ✅ `WHERE tenant_id = ?` |

**Begründung für MVP-Verzicht auf vollständiges RLS:**
- App-Level-Policies (Laravel) sind im MVP ausreichend
- Datenbank-RLS erhöht Komplexität für Tests + Migrations
- Klare Migrationspfad dokumentiert (kein Lock-in)

---

## 4. Storage-Isolation

Azure Blob Storage isoliert Dokumente per Nutzer-Namespace:

```
Container: documents (privat)
  Pfadstruktur: users/{user_id}/{uuid}.pdf
```

- Zugriff immer über Ownership-geprüfte API
- Kein direkter Storage-URL für Nutzer
- SAS-Token nur nach Auth-Check generieren (Post-MVP)

### Post-MVP Tenant-Pfadstruktur

```
Container: documents
  Pfadstruktur: tenants/{tenant_id}/users/{user_id}/{uuid}.pdf
```

---

## 5. Query-Scope-Muster (Laravel)

### MVP — User-Scope

```php
// app/Models/Scopes/UserScope.php
class UserScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($userId = auth()->id()) {
            $builder->where($model->getTable().'.user_id', $userId);
        }
    }
}

// Automatisch auf Document, ChatSession anwenden:
protected static function booted(): void
{
    static::addGlobalScope(new UserScope());
}
```

### Post-MVP — Tenant-Scope

```php
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($tenantId = auth()->user()?->tenant_id) {
            $builder->where($model->getTable().'.tenant_id', $tenantId);
        }
    }
}
```

---

## 6. Rollen-System (Post-MVP)

Im MVP gibt es keine Rollen. Für Post-MVP:

| Rolle | Berechtigungen |
|-------|---------------|
| `tenant_admin` | Alle Dokumente des Tenants, Nutzer verwalten |
| `member` | Eigene Dokumente, shared Dokumente lesen |
| `viewer` | Nur lesen (keine Uploads) |

Implementierung via `spatie/laravel-permission` (Post-MVP).

---

## 7. Checkliste für Multi-Tenancy-Aktivierung

Wenn Multi-Tenancy benötigt wird, sind folgende Schritte notwendig:

- [ ] `tenants` Tabelle anlegen + befüllen
- [ ] `tenant_id` zu `users`, `documents`, `chat_sessions` hinzufügen
- [ ] Migrations-Safety-Check durchführen (Backwards-Compatibility)
- [ ] Laravel Global Scopes auf `TenantScope` umstellen
- [ ] PostgreSQL RLS aktivieren
- [ ] Laravel Policy-Tests für Cross-Tenant-Zugriff erweitern
- [ ] Azure Blob Storage Pfade migrieren
- [ ] Audit-Logs um `tenant_id` erweitern

---

*Multi-Tenancy Design — DocumentScrapper Phase 0*
