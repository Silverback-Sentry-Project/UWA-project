# WildWatch Web Portal

The portal side of the WildWatch platform: a Laravel 13 + Sanctum API (`backend/`) and a React + TanStack Start frontend (`frontend/`).

**Architecture:** MySQL is authoritative for portal data (claims, payments, audit). Mobile-originated data lives in Firebase/Firestore. A bridge layer syncs between them via Cloud Functions webhooks and Laravel Firestore observers, guarded by the `source_system` field to prevent echo loops. See `../REPOS.md` (repo map) and `../BRIDGE-CONTRACT.md` (field-level sync contract).

## Layout

- `backend/` — Laravel 13 + Sanctum API (PHP 8.3+, MySQL or SQLite). See `backend/README.md`.
- `frontend/` — React + TanStack Start portal (Node 18+, npm or Bun). See `README` notes below.

## Frontend

- Depends on the Laravel API (with the `/api` suffix). Point `VITE_API_URL` at it — `auto` derives the URL from the browser hostname (LAN-friendly).
- **File-based routing** — every `.tsx` under `src/routes/` is a route (TanStack Start, not Next.js/Remix). `src/routes/routeTree.gen.ts` is auto-generated — don't hand-edit it.
- Route groups: `portal.*` (admin/warden), `ranger.*`, `community.*`.
- `src/components/ui/` is a minimal shadcn-style component set; `src/components/ui-prototype.tsx` holds the mobile-style portal shell.
- Built with Lovable — `vite.config.ts` wraps `@lovable.dev/vite-tanstack-config`; don't add those plugins manually. Never force-push or rewrite published git history on the connected branch (see `frontend/AGENTS.md`).

### Commands (from `frontend/`)

```bash
npm install            # or: bun install   (pick one, keep lockfiles in sync)
npm run dev            # Vite dev server
npm run build          # production build
npm run lint           # eslint
npm run format         # prettier --write
```

## Backend

See `backend/README.md` for setup (composer install, `.env`, migrations, seeding) and `../HOSTED-CUTOVER-PLAN.md` for the hosted-services path this now runs against — the local Docker stack that used to bundle this with Firebase emulators has been retired.

## Bridge surface

The canonical backend exposes the Firebase-bridge routes: `/api/webhooks/*` (HMAC-signed, `FIREBASE_BRIDGE_SECRET`), `/api/news-articles` (warden/UWA), and incident assignment. Field mappings and known gaps live in `../BRIDGE-CONTRACT.md`.
