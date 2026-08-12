# WildWatch Portal Frontend

React 19 and TanStack Start (file-based routing, server-side rendering) admin and warden portal for WildWatch, styled with Tailwind and shadcn-derived components, using TanStack Query for data fetching and Leaflet for maps. Talks to the Laravel API in `../backend/`.

## What this is for

The primary, currently-scoped surface is the `portal.*` route group: dashboard, incidents, claims, conflicts, hotspots, ranger assignments, audit, and settings — the warden and UWA Official administrative experience. The codebase also contains `ranger.*` and `community.*` route groups, real implemented screens that largely mirror functionality the native mobile app (`android-native-master-branch/`) already provides for rangers and the public. Whether those two groups should grow into a maintained parallel web experience or stay deprioritized in favor of the native app is a product decision, not a technical one; see `../wildwatch-local-development-env-setup/WildWatch-Platform-Plan.md` section 9.4 for the original reasoning behind treating them as lower priority.

Who can reach which part of the portal is governed by the same role model documented for the backend — see `../REPOS.md`'s `web-portal/` section for the current role-to-middleware mapping, and `../BRIDGE-CONTRACT.md` for how data moves between this portal, the Laravel API, and the mobile app's Firestore data.

## Requirements

Node.js and a package manager compatible with this project's lockfile. No other local services are required to install dependencies or run the build.

## Running against the hosted API

Set the `VITE_API_URL` environment variable to the deployed Laravel API's base URL, including the `/api` path segment, once that API is running on Render per `../HOSTED-CUTOVER-PLAN.md`. Setting `VITE_API_URL` to the literal value `auto` instead derives the API origin from whatever hostname the browser is currently using, which is convenient for LAN-based testing but should be pointed at an explicit URL for any deployment where the frontend and API are not reachable at the same hostname.

## Running locally

Install dependencies, copy `.env.example` to `.env` and adjust `VITE_API_URL` if the API isn't running at its default local address, then start the development server. The local Docker stack at `../wildwatch-local-development-env-setup/` can run this alongside the Laravel API and Firebase emulators if a full-fidelity local environment is wanted; see that directory's own setup runbook. That stack has not been retired, but the hosted-services path above is now the primary documented workflow.

## Authentication

Login happens against the Laravel API's Sanctum-based endpoint; the returned token is currently stored in the browser's local storage and sent as a bearer token on API requests. This is a known, deliberately-scoped tradeoff rather than an oversight — see `../HOSTED-CUTOVER-PLAN.md` section 7 for the reasoning, the partial mitigation already in place, and what a fuller fix would involve.

## Content security policy

The application ships a Content Security Policy restricting where scripts, styles, fonts, and images may load from, set in `src/routes/__root.tsx`. It currently allows inline scripts because TanStack Start's own server-rendered hydration logic requires them; tightening this further would need a per-request nonce threaded through the server request handler rather than a static policy. Map tiles are loaded from OpenStreetMap and are explicitly allowed in the image-source policy; adding any other external resource (a font provider, an analytics script, a different map tile host) requires updating this policy or it will be silently blocked by the browser.

## Testing and code style

ESLint and Prettier are configured for linting and formatting. There is no automated test suite for this frontend at present.
