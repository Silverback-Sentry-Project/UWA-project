# WildWatch Backend

Laravel 13 + Sanctum API for the WildWatch portal. Serves the React frontend (`../frontend/`) with incidents, SOS alerts, parks, species, rangers, and the Firebase ↔ Laravel bridge webhooks.

## Requirements

- PHP 8.3+ with the usual Laravel extensions (mbstring, openssl, pdo, tokenizer, xml, ctype, json, bcmath)
- Composer 2
- A database: SQLite (simplest) or MySQL

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# configure DB_* in .env (defaults to SQLite via database/database.sqlite)
php artisan migrate --seed
php artisan serve
```

The API is available at `http://localhost:8000/api`. Login via `POST /api/login`, then use the returned Sanctum token as a Bearer token.

Seeded login: `admin@uwa.go.ug` / `Password123!` — change after first login.

## Test / lint

```bash
php artisan test                       # feature tests use sqlite :memory:
./vendor/bin/pint                      # code style (phpstan is not installed)
```

## Bridge

`FIREBASE_BRIDGE_SECRET` must match the value set on the Firebase emulator/Cloud Functions side. Webhook routes (`/api/webhooks/*`) are authenticated by HMAC signature (`X-WildWatch-Signature`), not Sanctum. Laravel observers write changes back to Firestore tagged `source_system: "laravel"`. See `../BRIDGE-CONTRACT.md`.

For the full local Docker stack, see `../wildwatch-local-development-env-setup/SETUP.md`.
