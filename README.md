# WildWatch Web Portal

The portal side of the WildWatch platform: a Laravel 13 + Sanctum API (`backend/`) and a React + TanStack Start frontend (`frontend/`).

**Architecture:** Postgres (Neon, hosted on Render) is authoritative for portal data (claims, payments, audit). Mobile-originated data lives in Firebase/Firestore. A bridge layer syncs between them — mobile calls Laravel directly after a Firestore write (the Firebase project is on the Spark plan, which can't run Cloud Functions), and Laravel's own Firestore observers push portal-originated changes back out — guarded by the `source_system` field to prevent echo loops. See `../REPOS.md` (repo map) and `../BRIDGE-CONTRACT.md` (field-level sync contract).

**Live as of 2026-08-13**: both halves are deployed and auto-deploy on every push (backend to Render, frontend to Cloudflare Workers) — see "CI/CD" below. `../HOSTED-CUTOVER-PLAN.md` has the full cutover history and current live endpoints.

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

The canonical backend exposes the Firebase-bridge routes: `/api/webhooks/*` (HMAC-signed, `FIREBASE_BRIDGE_SECRET`), `/api/mobile/*` (the mobile-direct bridge — Firebase ID token auth, not HMAC, since the Spark-plan Firebase project can't run the Cloud Function that would otherwise sign webhook calls), `/api/news-articles` (warden/UWA — full CRUD plus an image-upload endpoint as of 2026-08-13, authored from the frontend's `/portal/feed` screen), and incident assignment. Field mappings and known gaps live in `../BRIDGE-CONTRACT.md`.

## CI/CD

`.github/workflows/backend-deploy.yml` and `frontend-deploy.yml` (added 2026-08-13) run on every push to `cleanup/aug-2026` or `main`, path-filtered to their own subtree: tests/typecheck+build, then deploy only on success (a Render deploy-hook POST; `wrangler deploy` to Cloudflare as `wildwatch-portal`). Both need repo secrets set (`RENDER_DEPLOY_HOOK_URL`; `CLOUDFLARE_API_TOKEN`/`CLOUDFLARE_ACCOUNT_ID`) — already done as of 2026-08-13, both confirmed to have actually deployed (not just "tests passed, deploy skipped"). Render's own native GitHub auto-deploy applies independently of the backend workflow — it adds a test gate Render doesn't have on its own, not a replacement for it.
