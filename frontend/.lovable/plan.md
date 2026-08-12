## Goal

Split the prototype into two surfaces:

1. **Mobile app** (existing `PhoneFrame` layout) — Community Member + Ranger only. UWA Official is removed.
2. **UWA Web Portal** — new full-width desktop admin experience in olive green / gold / white / black, rendered outside the phone frame.

Both still run in the same TanStack Start project so reviewers can navigate between them from the landing page.

---

## 1. Remove UWA from the mobile app

- Delete the mobile UWA routes: `src/routes/uwa.tsx`, `uwa.index.tsx`, `uwa.analytics.tsx`, `uwa.reports.tsx`, `uwa.users.tsx`.
- These will be replaced by a new portal route tree under `/portal/*` (see §2), so the old `/uwa/*` URLs go away entirely.
- Update `src/routes/index.tsx` landing screen: remove the "UWA Official" role card from the mobile chooser. Add a separate "Open UWA Web Portal" entry styled as a desktop link (distinct visual treatment — not a phone preview) that routes to `/portal`.
- Update `src/routes/auth.tsx` copy and any role hints to reference only Community Member and Ranger.
- Remove UWA references from any shared bottom-nav / role helpers if present.

## 2. New UWA Web Portal (`/portal/*`)

A new route tree that does NOT use `PhoneFrame`. Full-bleed desktop layout with a fixed left sidebar (logo, nav, signed-in admin), a top bar (park selector, search, profile menu), and a main content area.

### Route map

- `/portal` — login screen (email + password, MFA code field, "Authorized personnel only" notice).
- `/portal/dashboard` — Incident Monitoring Dashboard (default after login).
- `/portal/incidents` — full incidents table with filters.
- `/portal/hotspots` — Human-Wildlife Conflict hotspot maps.
- `/portal/assignments` — Ranger / Game Warden assignment workspace.
- `/portal/claims` — Compensation Claim Management.
- `/portal/conflicts` — Human-Wildlife Conflict Records (report archive).
- `/portal/audit` — Audit log viewer (security/traceability surface).
- `/portal/settings` — RBAC / personnel admin (read-only mock).

### Shared portal shell

- `src/components/portal/PortalShell.tsx` — sidebar + topbar + `<Outlet/>`.
- `src/components/portal/StatCard.tsx`, `DataTable.tsx`, `FilterBar.tsx`, `HeatmapCanvas.tsx`, `StatusBadge.tsx`, `Timeline.tsx` — reusable building blocks.
- Pathless layout route `src/routes/portal/route.tsx` wraps all `/portal/*` children in `PortalShell` (except `/portal` login).

### Screen contents

**Dashboard** — KPI row (Active / Resolved / Pending / High-risk areas), incidents-over-time line chart (SVG), incidents-by-park bar chart, recent incidents feed, mini hotspot preview.

**Incidents** — Filter bar: National park, Date range, Incident type, Status, Assigned ranger. Sortable table with row → detail drawer showing reporter, evidence thumbnails, timeline, assignment controls.

**Hotspots** — Park selector tabs (Bwindi, Mgahinga, +future). Heat map rendered as an SVG/Canvas overlay on a stylized park outline. Side panel toggles: Heat map, Time trend, Wildlife movement, Conflict density. Filters: species, time period, conflict type.

**Assignments** — Two-column board: unassigned incidents on the left, ranger roster on the right with availability/load. Assign action opens a confirm dialog; assigned items move to an "In progress" lane with progress updates and resolution history.

**Claims** — Pipeline view with status columns (Submitted → Under Verification → Approved / Rejected → Paid). Claim detail panel: linked incident, ranger investigation notes, evidence, Approve / Reject (with reason) / Mark Paid actions, full status history.

**Conflicts** — Searchable archive of conflict reports with filters (park, community, species, severity, date) and detail view with evidence gallery.

**Audit log** — Append-only table of approvals, assignments, compensation decisions, logins. Filter by actor, action type, date.

**Settings** — Personnel list with roles (Super Admin, Reviewer, Finance, Warden Coordinator), MFA status, last login. Visual only.

### Design system (portal only, additive)

Extend `src/styles.css` with a `.portal` scope defining portal tokens:

- `--portal-olive: oklch(...)` deep olive green (primary)
- `--portal-olive-soft` for surfaces
- `--portal-gold` for highlights, KPI accents, primary CTAs
- `--portal-ink` near-black for text
- `--portal-paper` white surfaces
- Typography: keep existing sans for body, add a slightly heavier display weight for dashboard headers. Government-grade feel: generous spacing, hairline dividers, subtle shadows, rounded-lg (not rounded-2xl) corners, data-dense tables.

Mobile app tokens are untouched.

### Security surface (visual only — this is a prototype)

- Login screen shows MFA field + "Authorized UWA personnel only" banner.
- Topbar shows signed-in role badge.
- Audit log page demonstrates traceability.
- Claim/assignment actions show "Action will be logged" confirmation dialogs.
  No real auth, encryption, or RBAC enforcement is implemented — this is a high-fidelity mockup, consistent with the rest of the prototype.

## 3. Out of scope

- Real authentication, RBAC enforcement, encryption, or backend persistence (prototype only; mock data in-memory).
- Real GIS / Mapbox integration — hotspots are stylized SVG/Canvas visualizations.
- Translating portal UI by selected language (portal is English-only for admins).
- Changes to existing Community or Ranger mobile screens beyond removing the UWA chooser entry.

## 4. Files touched (summary)

- Delete: `src/routes/uwa*.tsx` (5 files).
- Edit: `src/routes/index.tsx`, `src/routes/auth.tsx`, `src/styles.css`.
- Add: `src/routes/portal/route.tsx`, `src/routes/portal/index.tsx` (login), `dashboard.tsx`, `incidents.tsx`, `hotspots.tsx`, `assignments.tsx`, `claims.tsx`, `conflicts.tsx`, `audit.tsx`, `settings.tsx`.
- Add: `src/components/portal/{PortalShell,StatCard,DataTable,FilterBar,HeatmapCanvas,StatusBadge,Timeline}.tsx`.
- Add: `src/lib/portal-mock-data.ts` (shared mock incidents, claims, rangers, audit entries).
