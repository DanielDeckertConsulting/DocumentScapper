# Cursor Project Template

This repository contains **Cursor configuration only**: rules, skills, agents, commands, and hooks. Use it as a template so any new project gets the same workflow, quality gates, and agent orchestration.

---

## How to use this template

1. **Create a new GitHub repo** (e.g. `my-new-app`) or use an existing empty repo.
2. **Copy the `.cursor` folder** from this template into your project root:
   - Clone this template repo, then copy its `.cursor` directory into your project, or
   - Download the contents of this repo and place the `.cursor` folder in your project root.
3. **In Cursor:** Open your project. Commands, rules, skills, and agents are loaded from `.cursor/`.
4. **Optional:** Replace project name placeholders (e.g. "EasyHeadHunter") in `.cursor/commands/` and `.cursor/agents/` with your project name (search/replace).
5. **Hooks:** If you use the stop-hook (`hooks.json` → `ops_orchestrator.ts`), ensure you have **Bun** installed and that `scripts/test.sh` exists when you run feature delivery (or adjust the hook to your test script).

---

## What’s in `.cursor/`

| Folder / file   | Purpose |
|-----------------|--------|
| `commands/`     | Chat commands you can run with `#command_name` (e.g. `#project_kickoff`, `#ship_feature`). |
| `agents/`       | Agent definitions (e.g. Foundation Architect, DEV, PM, Review). Invoke via Cursor’s agent picker or @ mention. |
| `rules/`        | Always-on rules (simplicity, no PII, event-log, backend/frontend, etc.). |
| `skills/`       | Specialized skills (AC generator, scope-guard, event-schema-guard, GDPR scan, etc.) used by commands/agents. |
| `hooks/`        | Optional stop-hook (`ops_orchestrator.ts`) for post-chat orchestration (e.g. run tests, suggest next steps). |
| `hooks.json`    | Hook configuration (e.g. run `ops_orchestrator.ts` when a chat session stops). |
| `scratchpad.md` | Used by orchestrator/commands to store mode, plan, and PR notes. |

---

## Workflow: From idea to shipped feature

When you have an **idea** or a **generated MVP specification**, follow these steps in order. Each step has a **command** to run in Cursor and an **example prompt** you can paste.

---

### Step 0 — Project Kickoff

**Goal:** Turn a raw product idea into a clear MVP specification and success criteria.

**Command:** `#project_kickoff`

**Example prompt:**

```text
Product idea: A small web app for therapists to write session notes and get AI-assisted summaries. Target users: solo practitioners. MVP success: they can create a note, get a one-paragraph summary, and export it as PDF. Constraints: German UI, must be deployable on a single server, no patient data in logs.
```

---

### Step 1 — Feature Definition

**Goal:** Turn a feature idea into a single implementable ticket (scope, events, API, acceptance criteria).

**Command:** `#new_feature`

**Example prompt:**

```text
Feature: As a therapist I want to create a session note with title and body, so that I can store it and get an AI summary. Constraints: one note at a time for MVP; summary via existing LLM API; no PII in logs.
```

---

### Step 2 — Open Questions

**Goal:** Capture and track product, tech, and legal open questions so they can be resolved before or during implementation.

**Command:** `#open_questions`

**Example prompt:**

```text
Context: MVP scope from #project_kickoff (therapist session notes app). Create docs/OPEN_QUESTIONS.md with sections: Product & scope, Identity & auth, Compliance, Tech & ops. Propose 8–12 open questions and mark all as Open.
```

---

### Step 3 — Architecture Blueprint

**Goal:** Define initial architecture, tech stack, and repository blueprint before writing code.

**Command:** `#architecture_blueprint` (and invoke the **Foundation Architect** agent when prompted)

**Example prompt:**

```text
Using MVP from #project_kickoff: therapist session notes, AI summary, PDF export. Data sensitivity: PII in notes (we'll anonymize in logs). Scale: low (single server). Compliance: GDPR, 2-year retention. Produce architecture summary, tech stack, repo blueprint, NFRs, and ADR-0001/ADR-0002. Recommend next init command.
```

---

### Step 4 — Project Bootstrap

**Goal:** Create the repo structure, event store, minimal backend/frontend, docs, and optional UI design system.

**Commands (in order):**

1. `#init_project` — Creates monorepo skeleton (backend FastAPI + Postgres/event store, frontend, shared, scripts, docs).
2. `#init_docs_diagrams` — Creates `docs/diagrams/` with Mermaid templates (C4, flows, deployment).
3. `#init_futuristic_ui` — Optional: design tokens and core UI primitives (dark theme, electric blue accent).

**Example prompt for `#init_project`:**

```text
Bootstrap the project per the Foundation Architect blueprint: Event-Log-first, FastAPI backend, Postgres + Alembic, minimal frontend. Create folder structure, /health and /events API, in-memory fallback when DB unavailable, shared event schema, and docs (architecture, events, dev-process, testing). Run #init_futuristic_ui after the skeleton is in place.
```

**Example prompt for `#init_docs_diagrams`:**

```text
Initialize docs/diagrams with C4 context/container, golden-path sequence and eventflow, deployment sketch, and projection structure. Update docs/architecture.md and docs/events.md with links to these diagrams.
```

---

### Step 5 — Vertical Slice (ship one feature)

**Goal:** Deliver one feature end-to-end: ticket → AC → manual test cases → DEV → unit tests → test automation → review → PM acceptance → diagram updates.

**Commands:**

- **Standard (you choose gates):** `#ship_feature`
- **Auto mode (picks standard vs healthcare by sensitivity):** `#ship_feature_auto`
- **Healthcare / regulated:** `#ship_feature_healthcare`

**Example prompt for `#ship_feature`:**

```text
Feature: User can create a session note (title + body) and get an AI summary. Backend: POST /notes, GET /notes/:id, POST /notes/:id/summarize. Emit events: note.created, note.summary.requested, note.summary.completed. No PII in logs. Optional gates: DOCS.
```

**Example prompt for `#ship_feature_auto`:**

```text
Feature: Export session note as PDF. Use existing note content; add a “Export PDF” button that calls backend and returns a PDF. No new PII; anonymization already applied. [No constraints.]
```

---

## Quick reference: commands by step

| Step | Command | Purpose |
|------|---------|--------|
| 0 | `#project_kickoff` | Idea → MVP spec |
| 1 | `#new_feature` | Feature → single ticket (events, API, AC) |
| 2 | `#open_questions` | Create/update `docs/OPEN_QUESTIONS.md` |
| 3 | `#architecture_blueprint` + Foundation Architect | Architecture + tech stack + repo blueprint |
| 4 | `#init_project` then `#init_docs_diagrams` then `#init_futuristic_ui` | Bootstrap repo + docs + UI |
| 5 | `#ship_feature` or `#ship_feature_auto` or `#ship_feature_healthcare` | Deliver one vertical slice |

---

## After copying: what you need in Cursor

- **Nothing else required** for commands and rules; they load from `.cursor/`.
- **Agents:** Ensure the agents under `.cursor/agents/` are available in your Cursor workspace (they usually are when the project is open).
- **Hooks:** To use the stop-hook, install **Bun** and keep `scripts/test.sh` (or point the hook to your test script).
- **Skills:** Referenced by commands/agents (e.g. AC generator, scope-guard); no extra setup beyond having the `.cursor/skills/` folder.

---

## License

Use and adapt this template for your own projects. No warranty.
