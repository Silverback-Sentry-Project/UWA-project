import { createFileRoute } from "@tanstack/react-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { PortalShell, StatusBadge } from "@/components/portal/PortalShell";
import { apiFetch, ApiError } from "@/lib/api";
import type { Paginated } from "@/lib/api-types";
import { Flame } from "lucide-react";
import { useState } from "react";

export const Route = createFileRoute("/portal/conflicts")({ component: Conflicts });

interface SosAlert {
  sos_id: number;
  emergency_type: string;
  description: string | null;
  status: "Pending" | "Responding" | "Resolved";
  created_at: string;
  resolved_at: string | null;
  reporter?: { first_name: string; last_name: string } | null;
  park?: { park_name: string } | null;
}

const STATUSES = ["Pending", "Responding", "Resolved"] as const;
function fmtDate(iso: string) {
  return new Date(iso).toLocaleString("en-GB", { day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit" });
}

function Conflicts() {
  const queryClient = useQueryClient();
  const [statusFilter, setStatusFilter] = useState("");
  const [busyId, setBusyId] = useState<number | null>(null);
  const [err, setErr] = useState<string | null>(null);

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ["sos-alerts", statusFilter],
    queryFn: () => apiFetch<Paginated<SosAlert>>(`/sos-alerts${statusFilter ? `?status=${encodeURIComponent(statusFilter)}` : ""}`),
  });
  const alerts = data?.data ?? [];

  async function setStatus(id: number, status: string) {
    setBusyId(id); setErr(null);
    try {
      await apiFetch(`/sos-alerts/${id}/status`, { method: "PATCH", body: JSON.stringify({ status }) });
      await queryClient.invalidateQueries({ queryKey: ["sos-alerts"] });
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Couldn't update alert status.");
    } finally { setBusyId(null); }
  }

  return (
    <PortalShell title="Human-Wildlife Conflict / SOS Alerts" subtitle="Emergency reports from the field, requiring active response."
      helpText="Move an alert from Pending → Responding → Resolved as rangers act on it.">
      <div className="portal-card p-3 flex flex-wrap gap-2 mb-4">
        <select className="portal-input w-44" value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
          <option value="">All statuses</option>
          {STATUSES.map((s) => <option key={s} value={s}>{s}</option>)}
        </select>
      </div>

      {isLoading && <div className="portal-card p-6 text-sm text-[var(--p-ink-soft)]">Loading alerts…</div>}
      {isError && <div className="portal-card p-6 text-sm text-[var(--p-danger)]">Couldn't load alerts: {error instanceof Error ? error.message : "unknown error"}</div>}
      {err && <div className="portal-card p-3 mb-4 text-[12px] text-[var(--p-danger)] bg-[var(--p-danger)]/10 border border-[var(--p-danger)]/30">{err}</div>}

      {data && (
        <div className="grid grid-cols-2 gap-3">
          {alerts.length === 0 && <div className="col-span-2 portal-card p-6 text-center text-[var(--p-ink-soft)] text-sm">No alerts found.</div>}
          {alerts.map((a) => (
            <div key={a.sos_id} className="portal-card p-4 flex gap-4">
              <div className="h-24 w-24 shrink-0 rounded-md bg-[var(--p-olive-soft)] border border-[var(--p-olive-line)] grid place-items-center text-[var(--p-danger)]">
                <Flame size={22} />
              </div>
              <div className="flex-1 min-w-0">
                <div className="flex items-center justify-between">
                  <div className="font-mono text-[11px] font-bold text-[var(--p-olive-deep)]">SOS-{a.sos_id}</div>
                  <StatusBadge status={a.status} />
                </div>
                <h4 className="portal-display text-[14px] font-bold mt-1">{a.emergency_type}</h4>
                <p className="text-[12px] text-[var(--p-ink-soft)] mt-1 line-clamp-2">{a.description ?? "No description provided."}</p>
                <div className="mt-2 flex items-center justify-between text-[11px]">
                  <span className="text-[var(--p-ink-soft)]">{a.park?.park_name ?? "—"} · {fmtDate(a.created_at)}</span>
                  {a.reporter && <span className="text-[var(--p-ink-soft)]">by {a.reporter.first_name} {a.reporter.last_name}</span>}
                </div>
                {a.status !== "Resolved" && (
                  <div className="mt-2 flex gap-1.5">
                    {a.status === "Pending" && (
                      <button className="portal-btn text-[11px] px-2" disabled={busyId === a.sos_id} onClick={() => setStatus(a.sos_id, "Responding")}>Mark responding</button>
                    )}
                    <button className="portal-btn portal-btn-ghost text-[11px] px-2" disabled={busyId === a.sos_id} onClick={() => setStatus(a.sos_id, "Resolved")}>Mark resolved</button>
                  </div>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </PortalShell>
  );
}
