import { createFileRoute } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { PortalShell } from "@/components/portal/PortalShell";
import { apiFetch } from "@/lib/api";
import type { Paginated } from "@/lib/api-types";
import { useMemo, useState } from "react";
import { Flame, Activity, Footprints, BarChart3 } from "lucide-react";
import { MapContainer, TileLayer, CircleMarker, Popup, Polyline, useMap } from "react-leaflet";
import "leaflet/dist/leaflet.css";

export const Route = createFileRoute("/portal/hotspots")({ component: Hotspots });

interface Park { park_id: number; park_name: string; }
interface Species { species_id: number; common_name: string; }
interface IncidentRow {
  incident_id: number;
  latitude: string | number;
  longitude: string | number;
  park_id: number;
  species?: { common_name: string } | null;
}

const MODES = [
  { id: "heat", label: "Heat map", icon: Flame },
  { id: "trend", label: "Time trend", icon: Activity },
  { id: "movement", label: "Wildlife movement", icon: Footprints },
  { id: "density", label: "Conflict density", icon: BarChart3 },
] as const;

// Default view centered roughly on Uganda's national parks, used until real incidents load.
const DEFAULT_CENTER: [number, number] = [0.5, 30.5];
const DEFAULT_ZOOM = 7;

const MODE_COLOR: Record<(typeof MODES)[number]["id"], string> = {
  heat: "oklch(0.65 0.22 30)",
  density: "oklch(0.5 0.18 30)",
  movement: "oklch(0.5 0.12 50)",
  trend: "oklch(0.4 0.12 140)",
};

function FitToPoints({ points }: { points: Array<{ lat: number; lng: number }> }) {
  const map = useMap();
  useMemo(() => {
    if (points.length === 0) {
      map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
      return;
    }
    if (points.length === 1) {
      map.setView([points[0].lat, points[0].lng], 12);
      return;
    }
    const bounds: [number, number][] = points.map((p) => [p.lat, p.lng]);
    map.fitBounds(bounds, { padding: [32, 32] });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [points, map]);
  return null;
}

function Hotspots() {
  const [parkId, setParkId] = useState<number | null>(null);
  const [mode, setMode] = useState<typeof MODES[number]["id"]>("heat");

  const { data: parks } = useQuery({ queryKey: ["parks"], queryFn: () => apiFetch<Park[]>("/parks") });
  const activeParkId = parkId ?? parks?.[0]?.park_id ?? null;
  const activePark = parks?.find((p) => p.park_id === activeParkId);

  const { data: incidentsData, isLoading } = useQuery({
    queryKey: ["incidents-by-park", activeParkId],
    queryFn: () => apiFetch<Paginated<IncidentRow>>(`/incidents?park_id=${activeParkId}&per_page=200`),
    enabled: activeParkId != null,
  });

  const incidents = incidentsData?.data ?? [];

  // Real reported coordinates, plotted directly on an OpenStreetMap tile layer —
  // true geographic position and scale, not a relative projection.
  const points = useMemo(() => {
    return incidents
      .map((i) => ({
        lat: Number(i.latitude),
        lng: Number(i.longitude),
        species: i.species?.common_name ?? "Unknown",
        id: i.incident_id,
      }))
      .filter((p) => Number.isFinite(p.lat) && Number.isFinite(p.lng));
  }, [incidents]);

  const speciesCounts = useMemo(() => {
    const counts = new Map<string, number>();
    for (const p of points) counts.set(p.species, (counts.get(p.species) ?? 0) + 1);
    return [...counts.entries()].sort((a, b) => b[1] - a[1]).slice(0, 5);
  }, [points]);

  const color = MODE_COLOR[mode];

  return (
    <PortalShell title="Human-Wildlife Conflict Hotspots" subtitle="Plotted from real incident coordinates for the selected park."
      helpText="Markers use each incident's actual reported GPS coordinates on an OpenStreetMap base layer.">
      <div className="flex gap-2 mb-4 border-b border-[var(--p-olive-line)]">
        {(parks ?? []).map((p) => (
          <button key={p.park_id} onClick={() => setParkId(p.park_id)}
            className={`px-4 py-2.5 text-[13px] font-semibold border-b-2 -mb-px ${activeParkId === p.park_id ? "border-[var(--p-gold-deep)] text-[var(--p-ink)]" : "border-transparent text-[var(--p-ink-soft)] hover:text-[var(--p-ink)]"}`}>
            {p.park_name}
          </button>
        ))}
      </div>

      <div className="grid grid-cols-4 gap-4">
        <div className="col-span-3 portal-card overflow-hidden">
          <div className="p-4 border-b border-[var(--p-olive-line)] flex items-center justify-between">
            <div>
              <h3 className="portal-display text-sm font-bold">{activePark?.park_name ?? "—"} · {MODES.find((m) => m.id === mode)?.label}</h3>
              <p className="text-[11px] text-[var(--p-ink-soft)]">{isLoading ? "Loading…" : `${points.length} incidents plotted`}</p>
            </div>
            <div className="flex gap-1">
              {MODES.map((m) => (
                <button key={m.id} onClick={() => setMode(m.id)}
                  className={`flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-[11px] font-semibold ${mode === m.id ? "bg-[var(--p-olive)] text-white" : "text-[var(--p-ink-soft)] hover:bg-[var(--p-olive-soft)]"}`}>
                  <m.icon size={12} /> {m.label}
                </button>
              ))}
            </div>
          </div>

          <div className="relative h-[460px] bg-[var(--p-olive-soft)] overflow-hidden">
            <MapContainer center={DEFAULT_CENTER} zoom={DEFAULT_ZOOM} scrollWheelZoom style={{ height: "100%", width: "100%" }}>
              <TileLayer
                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
              />
              <FitToPoints points={points} />

              {mode !== "movement" && points.map((p) => (
                <CircleMarker
                  key={p.id}
                  center={[p.lat, p.lng]}
                  radius={mode === "heat" ? 14 : mode === "density" ? 8 : 5}
                  pathOptions={{
                    color,
                    fillColor: color,
                    fillOpacity: mode === "heat" ? 0.35 : 0.75,
                    weight: mode === "trend" ? 1 : 0,
                  }}
                >
                  <Popup>
                    <div className="text-xs font-semibold">Incident #{p.id}</div>
                    <div className="text-xs">{p.species}</div>
                  </Popup>
                </CircleMarker>
              ))}

              {mode === "movement" && points.map((p, i) => {
                const n = points[(i + 1) % Math.max(points.length, 1)];
                if (!n || points.length < 2) return null;
                return (
                  <Polyline
                    key={p.id}
                    positions={[[p.lat, p.lng], [n.lat, n.lng]]}
                    pathOptions={{ color, weight: 2, dashArray: "4,4" }}
                  />
                );
              })}
              {mode === "movement" && points.map((p) => (
                <CircleMarker key={p.id} center={[p.lat, p.lng]} radius={5} pathOptions={{ color, fillColor: color, fillOpacity: 0.8, weight: 0 }}>
                  <Popup>
                    <div className="text-xs font-semibold">Incident #{p.id}</div>
                    <div className="text-xs">{p.species}</div>
                  </Popup>
                </CircleMarker>
              ))}
            </MapContainer>
            {points.length === 0 && !isLoading && (
              <div className="absolute inset-0 grid place-items-center text-[12px] text-[var(--p-ink-soft)] bg-[var(--p-olive-soft)]/90 pointer-events-none z-[500]">
                No incidents recorded for this park yet.
              </div>
            )}
          </div>
        </div>

        <div className="space-y-4">
          <div className="portal-card p-4">
            <h4 className="portal-display text-sm font-bold">Top species reported</h4>
            <div className="mt-3 space-y-1.5">
              {speciesCounts.length === 0 && <div className="text-[12px] text-[var(--p-ink-soft)]">No data yet.</div>}
              {speciesCounts.map(([name, count]) => (
                <div key={name} className="flex items-center justify-between text-[12px]">
                  <span>{name}</span>
                  <span className="font-semibold text-[var(--p-olive-deep)]">{count}</span>
                </div>
              ))}
            </div>
          </div>
          <div className="portal-card p-4">
            <h4 className="portal-display text-sm font-bold">Total for park</h4>
            <div className="mt-2 text-[12px] text-[var(--p-ink-soft)]">{activePark?.park_name} has {points.length} incidents on record.</div>
          </div>
        </div>
      </div>
    </PortalShell>
  );
}
