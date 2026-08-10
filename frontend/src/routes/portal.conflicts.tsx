import { createFileRoute } from "@tanstack/react-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { PortalShell, StatusBadge } from "@/components/portal/PortalShell";
import { apiFetch, ApiError } from "@/lib/api";
import type { Paginated } from "@/lib/api-types";
import { Flame } from "lucide-react";
import { useState } from "react";

import { useAuth } from "@/lib/auth";
import { usePark } from "@/lib/park-context";

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
  return new Date(iso).toLocaleString("en-GB", {
    day: "2-digit",
    month: "short",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function Conflicts() {
  const queryClient = useQueryClient();
  const [statusFilter, setStatusFilter] = useState("");
  const [busyId, setBusyId] = useState<number | null>(null);
  const [err, setErr] = useState<string | null>(null);
  const { selectedParkId } = usePark();

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ["sos-alerts", statusFilter, selectedParkId],
    queryFn: () => {
      const params = new URLSearchParams();
      if (statusFilter) params.set("status", statusFilter);
      if (selectedParkId) params.set("park_id", selectedParkId);
      const qs = params.toString();
      return apiFetch<Paginated<SosAlert>>(`/sos-alerts?${qs}`);
    },
  });
  const alerts = data?.data ?? [];

  async function setStatus(id: number, status: string) {
    setBusyId(id);
    setErr(null);
    try {
      await apiFetch(`/sos-alerts/${id}/status`, {
        method: "PATCH",
        body: JSON.stringify({ status }),
      });
      await queryClient.invalidateQueries({ queryKey: ["sos-alerts"] });
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Couldn't update alert status.");
    } finally {
      setBusyId(null);
    }
  }

  return (
    <PortalShell
      title="Human-Wildlife Conflict / SOS Alerts"
      subtitle="Emergency reports from the field, requiring active response."
      helpText="Move an alert from Pending → Responding → Resolved as rangers act on it."
    >
      <div className="portal-card p-3 flex flex-wrap gap-2 mb-4">
        <select
          className="portal-input w-44"
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value)}
        >
          <option value="">All statuses</option>
          {STATUSES.map((s) => (
            <option key={s} value={s}>
              {s}
            </option>
          ))}
        </select>
      </div>

      {isLoading && (
        <div className="portal-card p-6 text-sm text-[var(--p-ink-soft)]">Loading alerts…</div>
      )}
      {isError && (
        <div className="portal-card p-6 text-sm text-[var(--p-danger)]">
          Couldn't load alerts: {error instanceof Error ? error.message : "unknown error"}
        </div>
      )}
      {err && (
        <div className="portal-card p-3 mb-4 text-[12px] text-[var(--p-danger)] bg-[var(--p-danger)]/10 border border-[var(--p-danger)]/30">
          {err}
        </div>
      )}

      {data && (
        <div className="grid grid-cols-2 gap-6">
          {alerts.length === 0 && (
            <div className="col-span-2 portal-card p-12 text-center bg-white shadow-sm border-neutral-100">
              <Flame className="mx-auto text-neutral-100 mb-4" size={48} />
              <p className="text-[13px] text-neutral-400 font-medium">
                No emergency signals detected in this sector.
              </p>
            </div>
          )}
          {alerts.map((a) => (
            <div
              key={a.sos_id}
              className="portal-card p-6 flex gap-6 bg-white shadow-sm hover:shadow-md transition-shadow border-neutral-100 group"
            >
              <div className="h-24 w-24 shrink-0 rounded-2xl bg-red-50 border border-red-100 grid place-items-center text-red-500 group-hover:scale-105 transition-transform">
                <Flame size={32} />
              </div>
              <div className="flex-1 min-w-0">
                <div className="flex items-center justify-between">
                  <div className="font-black text-[11px] uppercase tracking-[0.15em] text-neutral-400">
                    SIGNAL SOS-{a.sos_id}
                  </div>
                  <StatusBadge status={a.status} />
                </div>
                <h4 className="portal-display text-lg font-black text-neutral-900 mt-1">
                  {a.emergency_type}
                </h4>
                <p className="text-[13px] text-neutral-500 mt-2 line-clamp-2 leading-relaxed font-medium">
                  {a.description ?? "No telemetry provided."}
                </p>
                <div className="mt-4 pt-4 border-t border-neutral-50 flex items-center justify-between text-[11px] font-bold">
                  <span className="text-neutral-400 uppercase tracking-widest">
                    {a.park?.park_name ?? "Sector Unknown"} · {fmtDate(a.created_at)}
                  </span>
                  {a.reporter && (
                    <span className="text-[#1A2F1A] bg-emerald-50 px-2 py-0.5 rounded-md">
                      via {a.reporter.first_name}
                    </span>
                  )}
                </div>
                {a.status !== "Resolved" && (
                  <div className="mt-6 flex gap-3">
                    {a.status === "Pending" && (
                      <button
                        className="portal-btn flex-1 justify-center py-2.5 h-10 shadow-sm"
                        disabled={busyId === a.sos_id}
                        onClick={() => setStatus(a.sos_id, "Responding")}
                      >
                        Deploy Response
                      </button>
                    )}
                    <button
                      className="portal-btn portal-btn-ghost flex-1 justify-center py-2.5 h-10 border-neutral-200"
                      disabled={busyId === a.sos_id}
                      onClick={() => setStatus(a.sos_id, "Resolved")}
                    >
                      Signal Clear
                    </button>
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
