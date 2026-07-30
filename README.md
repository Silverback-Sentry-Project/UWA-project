# UWA-project
## Requirements
 
- Node.js 18+ (20+ recommended)
- A package manager — the project has both a `bun.lock` and a `package-lock.json` committed, so either **Bun** or **npm** will work. Pick one and stick with it so you don't end up with mismatched lockfiles.
- The `wildwatch-admin-api` backend running locally (see its own README) — this app has nothing to talk to without it.
## 1. Install dependencies
 
With npm:
 
```bash
npm install
```
 
or with Bun:
 
```bash
bun install
```
 
## 2. Set up the environment file
 
Create a `.env` file in the project root:
 
```bash
cp .env.example .env
```
 
It only needs one variable, pointing at the Laravel API (including the `/api` suffix):
 
```
VITE_API_URL=http://localhost:8000/api
```
 
If the backend is running elsewhere (different port, deployed URL, etc.), update this accordingly. This is read in `src/lib/api.ts`, which wraps all API calls and attaches the auth token as a Bearer header.
 
## 3. Add the logo (optional)
 
Drop a `logo.png` or `logo.svg` into `public/` — it's used on the login screen and the portal sidebar. If it's missing, both spots fall back to a default icon automatically, so this step isn't required to run the app.
 
## 4. Run the dev server
 
```bash
npm run dev
```
 
or
 
```bash
bun run dev
```
 
By default this runs on Vite's dev server (check your terminal output for the exact port/URL). Make sure the Laravel backend's `SANCTUM_STATEFUL_DOMAINS` / `FRONTEND_URL` includes this portal's URL so login works across origins.
 
## Other scripts
 
```bash
npm run build       # production build
npm run preview     # preview the production build locally
npm run lint         # eslint
npm run format       # prettier --write
```
 
## Project structure notes
 
- **File-based routing** — every `.tsx` file under `src/routes/` is a route (TanStack Start conventions, not Next.js/Remix). See `src/routes/README.md` for the naming rules. `routeTree.gen.ts` is auto-generated — don't hand-edit it.
- Route groups: `portal.*` (admin dashboard), `ranger.*` (ranger app), `community.*` (community-facing pages).
- `src/components/ui/` is a shadcn/ui component set (Radix + Tailwind).
- This project was built with Lovable — `vite.config.ts` pulls in a shared Lovable config (`@lovable.dev/vite-tanstack-config`) that already wires up TanStack Start, React, Tailwind, and dev-server sandboxing, so avoid adding those plugins manually.

- # WildWatch Admin API

Laravel backend for the WildWatch wildlife conflict management system. It serves the admin portal with incidents, SOS alerts, compensation claims, rangers, parks, species, and user management.

This copy has the `vendor/` folder and the `.env` file removed, so you need to reinstall dependencies and recreate your environment file before it will run.

## Requirements

- PHP 8.3 or newer, with the usual Laravel extensions (mbstring, openssl, pdo, tokenizer, xml, ctype, json, bcmath)
- Composer 2
- Node.js 18+ and npm (only needed to build the Vite/Tailwind assets used by the default Laravel welcome page; not required for the API itself)
- A database: SQLite (simplest) or MySQL

## 1. Install dependencies

```bash
composer install
npm install
```

## 2. Set up the environment file

Copy the example file and generate an app key:

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and check these values:

```
APP_NAME="WildWatch Admin API"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wildlife_conflict_system
DB_USERNAME=root
DB_PASSWORD=

# The admin portal (Lovable) will call this API from a different origin —
# add its dev URL here, e.g. http://localhost:5173
SANCTUM_STATEFUL_DOMAINS=
FRONTEND_URL=http://localhost:5173
```

**Database options:**

- **SQLite (quickest for local dev)** — the app defaults to sqlite if `DB_CONNECTION` is unset. Just create the file and skip the `DB_*` lines above:
  ```bash
  touch database/database.sqlite
  ```
- **MySQL** — create a database matching `DB_DATABASE` and fill in the `DB_*` values above.

**CORS / Sanctum:** since the admin portal (`wildwatch-portal-update`) runs on a different origin, set `SANCTUM_STATEFUL_DOMAINS` and `FRONTEND_URL` to match wherever the portal is running (e.g. `localhost:5173`) so authenticated requests work.

## 3. Run migrations and seed sample data

```bash
php artisan migrate --seed
```

This creates all tables (users, roles, parks, species, incidents, SOS alerts, claims, payments, notifications, etc.) and seeds:

- The 7 system roles (Community Member, Ranger, Community Wildlife Officer, Compensation Officer, UWA Official, Park Warden, System Administrator)
- 4 parks (Bwindi, Mgahinga, Queen Elizabeth, Murchison Falls)
- 4 species
- A default admin login: `admin@uwa.go.ug` / `Password123!` — **change this password after your first login.**

## 4. Serve the app

```bash
php artisan serve
```

The API will be available at `http://localhost:8000/api`. Login via `POST /login`, then use the returned Sanctum token as a Bearer token on the routes under `/api` (dashboard stats, parks, species, rangers, incidents, SOS alerts, claims, users, audit).

## Notes

- `storage/` needs to be writable by the web server (`chmod -R 775 storage bootstrap/cache` on Linux/macOS if you hit permission errors).
- If you deploy this somewhere other than local dev, update `config/cors.php` (`allowed_origins`) to your real frontend URL instead of `*`.
