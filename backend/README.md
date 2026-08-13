# WildWatch Backend

Laravel 13 + Sanctum API for the WildWatch admin/warden portal. Serves the React frontend (`../frontend/`) with parks, species, rangers, incidents, SOS alerts, compensation claims, evidence forms, news articles, and the Firebase-to-Laravel bridge webhooks that keep this database and the mobile app's Firestore data reconciled.

## What this is for

This is the relational side of WildWatch's two-backend architecture: the mobile app (`android-native-master-branch/`) is offline-first against Firebase/Firestore, while this API is the system of record for anything genuinely relational — compensation claims that reference incidents that reference ranger assignments, with payments and an audit trail hanging off claims. A Cloud Function bridges mobile-originated writes (incidents, sightings, SOS alerts) into this database via signed webhooks; this API's own model observers push portal-originated changes (status updates, claim decisions) back out to Firestore so the mobile app's real-time listeners see them. The full field-level mapping, including which system is authoritative for which entity, is in `../BRIDGE-CONTRACT.md`.

Who can do what in the portal is governed by a role model with several distinct roles (System Administrator, UWA Official, Park Warden, Gamepark Officer, Ranger, Community Wildlife Officer, and public/community accounts), expressed as a handful of route-level middleware aliases rather than one blanket admin gate. `../REPOS.md`'s `web-portal/` section has the current auth-mapping write-up — which middleware alias gates which route group, and how it maps onto the underlying role names.

## Requirements

PHP 8.3 or newer with the extensions Laravel 13 expects (mbstring, openssl, pdo, tokenizer, xml, ctype, json, bcmath), Composer 2, and a database — SQLite is the simplest option for local development and is what the automated test suite uses; MySQL and Postgres are both supported by the underlying schema, with Postgres being the target for hosted deployment (see below).

## Running against hosted services (Neon, Render, a real Firebase project)

This is now the intended default rather than the local Docker stack. Provision a Neon Postgres database and point this application's database configuration at it; the schema migrations use Laravel's database-agnostic schema builder, so they should apply cleanly to Postgres, though this should be verified with an actual migration run against Neon rather than assumed, since every migration so far has only been exercised against MySQL and SQLite. Deploy this application itself to Render's web-service tier, keeping in mind that Render's free tier spins down after roughly fifteen minutes of inactivity, so the first request after a quiet period will be slow to respond — a known, accepted limitation of the free tier, not something to work around. Generate a fresh application key for the hosted environment rather than reusing a local one, set the database connection details to the Neon instance, and set `FIREBASE_BRIDGE_SECRET` to a real rotated secret that matches whatever is configured on the real Firebase project's Cloud Functions side exactly. The full cutover procedure, including the specific environment variables involved and the open questions that still need a decision (a production mail provider chief among them), is documented in `../HOSTED-CUTOVER-PLAN.md`.

## Running locally

Copy `.env.example` to `.env`, generate an application key with the Artisan `key:generate` command, configure the database connection (SQLite needs no further configuration beyond the default `database/database.sqlite` path), and run the Artisan `migrate --seed` command followed by `artisan serve`. The API is then available at `http://localhost:8000/api`. Authenticate with a `POST` to `/api/login` and use the returned Sanctum token as a bearer token on subsequent requests. A seeded System Administrator login is documented in `../BRIDGE-CONTRACT.md`'s seed fixture mapping section — change that password after first login in any environment that isn't purely local and disposable.

The full local Docker stack that used to wire Firebase emulators, MySQL, Redis, Mailpit, this API, and the portal frontend together has been retired; the two options are running this API standalone as described above, or the hosted-services path.

## Testing and code style

The feature test suite runs against an in-memory SQLite database and does not require any external service to be running. Pint enforces code style; static analysis beyond that is not currently wired into this project.

## The bridge webhooks

Webhook routes under `/api/webhooks/` are authenticated by an HMAC signature header rather than Sanctum — `FIREBASE_BRIDGE_SECRET` must match the value configured on the Firebase Functions side exactly, in every environment. Routes under `/api/mobile/` instead authenticate via a Firebase ID token (the live Firebase project is on the Spark plan and can't run the Cloud Function that would otherwise sign HMAC webhook calls, so the mobile app calls Laravel directly). See `../BRIDGE-CONTRACT.md` for the full contract: which fields map to which, which system is authoritative for what, and how echo prevention keeps a webhook-originated write from bouncing back out as a second webhook call.

A Postman collection covering this API's full route surface, including example request bodies and a self-signing setup for the webhook routes, is at `wildwatch.json` in this directory's root.

## CI/CD and recent schema changes

`../.github/workflows/backend-deploy.yml` runs `vendor/bin/phpunit` on every push (to `cleanup/aug-2026` or `main`, path-filtered to `backend/**`) and pings a Render deploy hook on success — see the root `web-portal/README.md` for the full CI/CD picture including the frontend side.

The Neon schema was cleaned up 2026-08-13: 8 confirmed-dead tables dropped via a reversible migration (`incident_routes`, `reports`, `community_feedback`, plus the unused Laravel cache/queue scaffolding tables — `CACHE_STORE=file` and `QUEUE_CONNECTION=sync` in production, no `ShouldQueue` class exists anywhere). `news_articles` gained `image_url` the same day, along with real update/destroy/image-upload endpoints (previously only create/read existed despite the model/observer being fully built) — see `../BRIDGE-CONTRACT.md`'s "Community feed" and "Database schema cleanup" sections for the full detail.
