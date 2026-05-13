# TamalStore — AGENTS.md

## Project

Monorepo: **PHP 8.2 backend** (no framework, no Composer) + **SolidJS/TypeScript frontend** (Vite).

```
/backend        — PHP API (custom autoloader via bootstrap.php, no Composer)
/frontend       — SolidJS + Vite + TypeScript
db-schema.sql   — PostgreSQL schema (port 5433 externally)
postman.json    — API collection
API.md          — docs at backend/API.md
```

## Dev commands

| What | Where | Command |
|------|-------|---------|
| Frontend dev server | `frontend/` | `npm run dev` or `npm start` (port 3000) |
| Frontend build | `frontend/` | `npm run build` (output to `dist/`) |
| Backend (Docker) | `backend/` | `docker compose up` (port 8084) |

**No test, lint, or typecheck scripts are configured.** The `tsconfig.json` has `noEmit: true` + `strict`, but no `tsc` script — Vite handles TS at build time only.

**Package manager:** `pnpm` preferred (see `pnpm-lock.yaml`). npm works as fallback.

## Architecture quirks

- **Backend is plain PHP** — no framework, no Composer. Class autoloading via hardcoded map in `backend/bootstrap.php`. To add a new class, update the `$classMap` there.
- **Router is a manual switch statement** in `Application.php:120` (`dispatch()`) — no router library.
- **Container has two fetch modes:** `get()` returns a cached singleton; `make()` returns a fresh instance (used specifically for DB connections to avoid stale PDO objects).
- **Person ID goes in the URL path**, not headers. Protected endpoints: `/expenses/{personId}`, `/account/{personId}`, `/tamalbits/{personId}`. Public: `/status`, `/auth`, `/products`.
- **Frontend dev server proxies `/api` → `localhost:8084`** (configured in `vite.config.ts`). In Docker, nginx on port 8084 serves the PHP API.
- **External bank API** required on port 8083 (`BANK_API_URL` env var, defaults to `http://host.docker.internal:8083`). All expense creation calls this external API to deduct balance.
- **Expense creation uses manual DB transactions** (`$db->begin()` / `commit()` / `rollback()`) — deducts from external bank API, then records locally. Both must succeed.
- **SolidJS patterns skill** (`solidjs-patterns`) is loaded and should be followed for frontend state management (fine-grained signals, no global busy flags, etc.).

## DB

- PostgreSQL env vars: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- Docker Compose exposes port **5433** (not 5432) on host
- Schema & seed data in root `db-schema.sql`
