import { createFileRoute } from "@tanstack/react-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { PortalShell, StatusBadge } from "@/components/portal/PortalShell";
import { apiFetch, ApiError } from "@/lib/api";
import type { Paginated } from "@/lib/api-types";
import { UserCheck } from "lucide-react";
import { useState } from "react";

export const Route = createFileRoute("/portal/assignments")({ component: Assignments });

interface IncidentRow {
  incident_id: number;
  incident_type: string;
  status: string;
  created_at: string;
  description: string;
  park?: { park_name: string } | null;
  assignments?: Array<{ ranger?: { first_name: string; last_name: string } }>;
}

interface Ranger {
  user_id: number;
  first_name: string;
  last_name: string;
  account_status: string;
  active_assignments_count: number;
}

function fmtDate(iso: string) {
  return new Date(iso).toLocaleString("en-GB", { day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit" });
}

function Assignments() {
  const queryClient = useQueryClient();
  const [assigning, setAssigning] = useState<number | null>(null);
  const [rangerChoice, setRangerChoice] = useState("");
  const [err, setErr] = useState<string | null>(null);

  const { data: incidentsData, isLoading: loadingIncidents } = useQuery({
    queryKey: ["incidents", "New"],
    queryFn: () => apiFetch<Paginated<IncidentRow>>("/incidents?status=New"),
  });
  const { data: inProgressData } = useQuery({
    queryKey: ["incidents", "Assigned"],
    queryFn: () => apiFetch<Paginated<IncidentRow>>("/incidents?status=Assigned"),
  });
  const { data: rangers, isLoading: loadingRangers } = useQuery({
    queryKey: ["rangers"],
    queryFn: () => apiFetch<Ranger[]>("/rangers"),
  });

  const unassigned = incidentsData?.data ?? [];
  const inProgress = inProgressData?.data ?? [];

  async function assign(incidentId: number) {
    if (!rangerChoice) return;
    setErr(null);
    try {
      await apiFetch(`/incidents/${incidentId}/assign`, { method: "POST", body: JSON.stringify({ ranger_id: Number(rangerChoice) }) });
      await queryClient.invalidateQueries({ queryKey: ["incidents"] });
      await queryClient.invalidateQueries({ queryKey: ["rangers"] });
      setAssigning(null);
      setRangerChoice("");
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Couldn't assign ranger.");
    }
  }

  return (
    <PortalShell title="Ranger & Game Warden Assignments" subtitle="Dispatch personnel to new incidents."
      helpText="Pick a case from Unassigned, choose a ranger, then confirm. The incident moves to In progress immediately.">
      <div className="grid grid-cols-12 gap-4">
        <div className="col-span-4 portal-card">
          <div className="p-4 border-b border-[var(--p-olive-line)] flex items-center justify-between">
            <h3 className="portal-display text-sm font-bold">Unassigned <span className="text-[var(--p-ink-soft)] font-normal">({unassigned.length})</span></h3>
            <span className="portal-chip" style={{ background: "oklch(0.94 0.05 27)", color: "oklch(0.5 0.18 27)" }}>Needs dispatch</span>
          </div>
          <div className="p-3 space-y-2 max-h-[600px] overflow-auto">
            {loadingIncidents && <div className="text-[12px] text-[var(--p-ink-soft)] p-2">Loading…</div>}
            {!loadingIncidents && unassigned.length === 0 && <div className="text-[12px] text-[var(--p-ink-soft)] p-2">Nothing waiting on dispatch.</div>}
            {unassigned.map((i) => (
              <div key={i.incident_id} className="border border-[var(--p-olive-line)] rounded-md p-3">
                <div className="flex items-center justify-between">
                  <div className="font-mono text-[11px] font-bold text-[var(--p-olive-deep)]">WW-{i.incident_id}</div>
                  <StatusBadge status={i.status} />
                </div>
                <div className="text-[12px] font-semibold mt-1">{i.incident_type} · {i.park?.park_name ?? "—"}</div>
                <div className="text-[11px] text-[var(--p-ink-soft)] line-clamp-1">{i.description}</div>
                <div className="mt-2 text-[10px] text-[var(--p-ink-soft)]">{fmtDate(i.created_at)}</div>
                {assigning === i.incident_id ? (
                  <div className="mt-2 flex gap-1.5">
                    <select className="portal-input flex-1 text-[11px]" value={rangerChoice} onChange={(e) => setRangerChoice(e.target.value)}>
                      <option value="">Choose ranger…</option>
                      {(rangers ?? []).map((r) => <option key={r.user_id} value={r.user_id}>{r.first_name} {r.last_name}</option>)}
                    </select>
                    <button className="portal-btn text-[11px] px-2" disabled={!rangerChoice} onClick={() => assign(i.incident_id)}>Go</button>
                  </div>
                ) : (
                  <button className="portal-btn portal-btn-ghost text-[11px] w-full justify-center mt-2" onClick={() => setAssigning(i.incident_id)}><UserCheck size={12} /> Assign</button>
                )}
              </div>
            ))}
          </div>
        </div>

        <div className="col-span-4 portal-card">
          <div className="p-4 border-b border-[var(--p-olive-line)]">
            <h3 className="portal-display text-sm font-bold">Personnel roster</h3>
            <p className="text-[11px] text-[var(--p-ink-soft)]">Active assignment load per ranger</p>
          </div>
          <div className="p-3 space-y-2 max-h-[600px] overflow-auto">
            {loadingRangers && <div className="text-[12px] text-[var(--p-ink-soft)] p-2">Loading…</div>}
            {(rangers ?? []).map((r) => (
              <div key={r.user_id} className="border border-[var(--p-olive-line)] rounded-md p-3 flex items-center gap-3">
                <div className="h-9 w-9 rounded-full bg-[var(--p-olive)] text-white grid place-items-center text-[11px] font-bold">{`${r.first_name[0] ?? ""}${r.last_name[0] ?? ""}`}</div>
                <div className="flex-1 min-w-0">
                  <div className="text-[13px] font-semibold leading-tight">{r.first_name} {r.last_name}</div>
                  <div className="mt-1 flex items-center gap-2"><StatusBadge status={r.account_status} /><span className="text-[10px] text-[var(--p-ink-soft)]">{r.active_assignments_count} active</span></div>
                </div>
              </div>
            ))}
            {!loadingRangers && (rangers ?? []).length === 0 && <div className="text-[12px] text-[var(--p-ink-soft)] p-2">No personnel with the Ranger role yet.</div>}
          </div>
        </div>

        <div className="col-span-4 portal-card">
          <div className="p-4 border-b border-[var(--p-olive-line)]">
            <h3 className="portal-display text-sm font-bold">In progress <span className="text-[var(--p-ink-soft)] font-normal">({inProgress.length})</span></h3>
            <p className="text-[11px] text-[var(--p-ink-soft)]">Assigned, awaiting resolution</p>
          </div>
          <div className="p-3 space-y-2 max-h-[600px] overflow-auto">
            {inProgress.map((i) => (
              <div key={i.incident_id} className="border border-[var(--p-olive-line)] rounded-md p-3">
                <div className="flex items-center justify-between text-[11px]">
                  <div className="font-mono font-bold text-[var(--p-olive-deep)]">WW-{i.incident_id}</div>
                  <StatusBadge status={i.status} />
                </div>
                <div className="text-[12px] font-semibold mt-1">{i.incident_type} · {i.park?.park_name ?? "—"}</div>
                <div className="mt-2 flex items-center gap-1.5 text-[11px]">
                  <UserCheck size={12} className="text-[var(--p-olive)]" />
                  <span className="font-semibold">{i.assignments?.[0]?.ranger ? `${i.assignments[0].ranger!.first_name} ${i.assignments[0].ranger!.last_name}` : "Unassigned"}</span>
                </div>
                <div className="mt-1 text-[10px] text-[var(--p-ink-soft)]">Reported {fmtDate(i.created_at)}</div>
              </div>
            ))}
            {inProgress.length === 0 && <div className="text-[12px] text-[var(--p-ink-soft)] p-2">Nothing in progress right now.</div>}
          </div>
        </div>
      </div>
      {err && <div className="mt-3 text-[12px] text-[var(--p-danger)] bg-[var(--p-danger)]/10 border border-[var(--p-danger)]/30 rounded-md px-3 py-2">{err}</div>}
    </PortalShell>
  );
}
