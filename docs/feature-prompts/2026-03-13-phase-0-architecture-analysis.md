# Prompt Documentation — Phase-0 Architecture Analysis

## Metadata
- **Date:** `2026-03-13T14:00:00+01:00`
- **Command:** `#ship-feature-auto`
- **Feature:** `Phase-0 Architecture Analysis & Documentation`

## Intent
- Anforderungsanalyse aus MVP_INPUT_SPEC.md extrahieren
- Azure-zentrierte Zielarchitektur mit Mermaid-Diagrammen definieren
- Multi-Tenancy + RLS-Strategie dokumentieren
- LLM-Sicherheit + DSGVO-Anforderungen definieren
- Tech-Stack mit Entscheidungsbegründungen dokumentieren
- Risiken und offene Fragen erfassen

## Prompt (Verbatim)

```md
/ship-feature-auto 
Input: Read docs/MVP_INPUT_SPEC.md as the authoritative specification

Tasks:
- Extract requirements, user journeys, NFRs
- Define target architecture and Azure services
- Define multi-tenancy + RLS approach
- Define LLM security + anonymization approach (MVP)
- Recommend tech stack (frontend/backend) and justify
- Produce Phase-0 docs listed below

OUTPUT FILES (write to /docs):
- docs/REQUIREMENTS_ANALYSIS.md
- docs/ARCHITECTURE.md (Mermaid diagrams)
- docs/TECHSTACK.md
- docs/MULTI_TENANCY_DESIGN.md
- docs/LLM_SECURITY_AND_GDPR.md
- docs/SECURITY_BASELINE.md
- docs/FRONTEND_ARCHITECTURE.md
- docs/MVP_SIMPLICITY_JUSTIFICATION.md
- docs/RISKS.md
- docs/OPEN_QUESTIONS.md

NO CODE IN THIS PHASE.
```

## Outcome Summary
- `REQUIREMENTS_ANALYSIS.md`: FA-01–FA-06, 4 NFR-Kategorien, 4 User-Journey-Maps
- `ARCHITECTURE.md`: C4 Level 1+2, Azure Service-Mapping, Sequenzdiagramme (Ingestion + Chat), Deployment-Diagramm
- `TECHSTACK.md`: Stack-Tabellen mit Entscheidungsmatrizen (Vue vs React, Laravel vs alternatives, Azure OpenAI vs direkt)
- `MULTI_TENANCY_DESIGN.md`: MVP User-Isolation, RLS-Strategie, Query-Scopes, Post-MVP Migration-Pfad
- `LLM_SECURITY_AND_GDPR.md`: Azure OpenAI Begründung, Datenminimierung, Prompt-Injection-Schutz, DSGVO-Mapping
- `SECURITY_BASELINE.md`: STRIDE Threat Model, Auth-Konfiguration, Rate Limiting, Audit Logs, Azure Security
- `FRONTEND_ARCHITECTURE.md`: Vue-Komponenten, Stores, Auth-Flow (Mermaid), Mobile-First-Regeln
- `MVP_SIMPLICITY_JUSTIFICATION.md`: 8 bewusste Vereinfachungen mit Metriken für Umstieg, Entscheidungs-Log
- `RISKS.md`: 10 Risiken mit STRIDE-Bewertung, Risiko-Matrix, Mitigationsmaßnahmen
- `OPEN_QUESTIONS.md`: 27 offene Fragen in 6 Kategorien, 5 priorisierte Sofort-Klärungen
