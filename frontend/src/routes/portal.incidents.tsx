import { createFileRoute } from "@tanstack/react-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { PortalShell, StatusBadge } from "@/components/portal/PortalShell";
import { apiFetch, ApiError } from "@/lib/api";
import type { Paginated } from "@/lib/api-types";
import { Download, MapPin, X, UserCheck, AlertTriangle, CheckCircle2 } from "lucide-react";
import { useState } from "react";

import { useAuth } from "@/lib/auth";
import { usePark } from "@/lib/park-context";

export const Route = createFileRoute("/portal/incidents")({ component: Incidents });

interface IncidentRow {
  incident_id: number;
  incident_type: string;
  status: string;
  village: string | null;
  latitude: string | number;
  longitude: string | number;
  description: string;
  created_at: string;
  park?: { park_id: number; park_name: string } | null;
  reporter?: { user_id: number; first_name: string; last_name: string } | null;
  species?: { common_name: string } | null;
  assignments?: Array<{ assignment_id: number; assignment_status: string; ranger?: { first_name: string; last_name: string } }>;
}

interface Ranger {
  user_id: number;
  first_name: string;
  last_name: string;
  active_assignments_count: number;
}

function fmtDate(iso: string) {
  return new Date(iso).toLocaleString("en-GB", { day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit" });
}

const STATUSES = ["New", "Assigned", "In Progress", "Resolved", "Escalated"];

function Incidents() {
  const [open, setOpen] = useState<number | null>(null);
  const [statusFilter, setStatusFilter] = useState("");
  const queryClient = useQueryClient();
  const { selectedParkId } = usePark();

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ["incidents", statusFilter, selectedParkId],
    queryFn: () => {
      const params = new URLSearchParams();
      if (statusFilter) params.set("status", statusFilter);
      if (selectedParkId) params.set("park_id", selectedParkId);
      const qs = params.toString();
      return apiFetch<Paginated<IncidentRow>>(`/incidents?${qs}`);
    },
  });

  const { data: rangers } = useQuery({
    queryKey: ["rangers"],
    queryFn: () => apiFetch<Ranger[]>("/rangers"),
    enabled: open !== null,
  });

  const incidents = data?.data ?? [];
  const selected = incidents.find((i) => i.incident_id === open);

  async function refreshIncidents() {
    await queryClient.invalidateQueries({ queryKey: ["incidents"] });
  }

  return (
    <PortalShell title="Incidents" subtitle={data ? `${data.total} reports` : "Loading…"}
      helpText="Click any row to open the case drawer. Assign a ranger or update status — all actions are recorded in the audit log."
      actions={<button className="portal-btn portal-btn-gold"><Download size={13} /> Export CSV</button>}>
      <div className="portal-card p-3 mb-4">
        <div className="flex flex-wrap gap-2">
          <select className="portal-input w-44" value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
            <option value="">All statuses</option>
            {STATUSES.map((s) => <option key={s} value={s}>{s}</option>)}
          </select>
        </div>
      </div>

      {isLoading && <div className="portal-card p-6 text-sm text-[var(--p-ink-soft)]">Loading incidents…</div>}
      {isError && <div className="portal-card p-6 text-sm text-[var(--p-danger)]">Couldn't load incidents: {error instanceof Error ? error.message : "unknown error"}</div>}

      <div className="portal-card overflow-hidden shadow-sm border-neutral-100 bg-white/50 backdrop-blur-sm">
        <table className="portal-table border-collapse">
          <thead>
            <tr className="bg-neutral-50/50">
              <th className="py-4 font-black">Callsign</th>
              <th className="py-4 font-black">Type</th>
              <th className="py-4 font-black">Region</th>
              <th className="py-4 font-black">Village</th>
              <th className="py-4 font-black">Reporter</th>
              <th className="py-4 font-black">Operator</th>
              <th className="py-4 font-black text-center">Status</th>
              <th className="py-4 font-black text-right pr-6">Timestamp</th>
            </tr>
          </thead>
          <tbody>
            {incidents.length === 0 && (
              <tr><td colSpan={8} className="text-center text-neutral-400 py-12 italic">No field data retrieved for the selected scope.</td></tr>
            )}
            {incidents.map((i) => {
              const assignedRanger = i.assignments?.[0]?.ranger;
              return (
                <tr key={i.incident_id} onClick={() => setOpen(i.incident_id)} className="hover:bg-white transition-colors cursor-pointer group">
                  <td className="font-mono text-[11px] font-bold text-neutral-400 py-4">WW-{i.incident_id}</td>
                  <td className="font-bold text-neutral-800">{i.incident_type}</td>
                  <td className="text-neutral-500 font-medium">{i.park?.park_name ?? "—"}</td>
                  <td className="text-neutral-500">{i.village ?? "—"}</td>
                  <td className="text-[13px] font-medium text-neutral-700">{i.reporter ? `${i.reporter.first_name} ${i.reporter.last_name}` : "—"}</td>
                  <td>
                    {assignedRanger ? (
                      <div className="flex items-center gap-2">
                        <div className="h-6 w-6 rounded-full bg-[#1A2F1A] text-white flex items-center justify-center text-[9px] font-bold">
                          {assignedRanger.first_name[0]}{assignedRanger.last_name[0]}
                        </div>
                        <span className="text-[12px] font-semibold text-neutral-700">{assignedRanger.first_name}</span>
                      </div>
                    ) : (
                      <span className="text-[11px] font-bold text-neutral-300 uppercase tracking-wider italic">Awaiting</span>
                    )}
                  </td>
                  <td className="text-center"><StatusBadge status={i.status} /></td>
                  <td className="text-right pr-6 font-bold text-neutral-400 tabular-nums text-[12px]">{fmtDate(i.created_at)}</td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      {selected && (
        <IncidentDrawer
          incident={selected}
          rangers={rangers ?? []}
          onClose={() => setOpen(null)}
          onChanged={refreshIncidents}
        />
      )}
    </PortalShell>
  );
}

function IncidentDrawer({ incident, rangers, onClose, onChanged }: {
  incident: IncidentRow; rangers: Ranger[]; onClose: () => void; onChanged: () => Promise<void>;
}) {
  const [rangerId, setRangerId] = useState("");
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  const assignedRanger = incident.assignments?.[0]?.ranger;

  async function assign() {
    if (!rangerId) return;
    setBusy(true); setErr(null);
    try {
      await apiFetch(`/incidents/${incident.incident_id}/assign`, { method: "POST", body: JSON.stringify({ ranger_id: Number(rangerId) }) });
      await onChanged();
      onClose();
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Couldn't assign ranger.");
    } finally { setBusy(false); }
  }

  async function setStatus(status: string) {
    setBusy(true); setErr(null);
    try {
      await apiFetch(`/incidents/${incident.incident_id}/status`, { method: "PATCH", body: JSON.stringify({ status }) });
      await onChanged();
      onClose();
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Couldn't update status.");
    } finally { setBusy(false); }
  }

  return (
    <div className="fixed inset-0 z-50 bg-black/30 flex justify-end" onClick={onClose}>
      <div className="w-[440px] h-full bg-white shadow-2xl overflow-auto" onClick={(e) => e.stopPropagation()}>
        <div className="p-5 border-b border-[var(--p-olive-line)] flex items-start justify-between">
          <div>
            <div className="text-[11px] uppercase tracking-wider text-[var(--p-ink-soft)] font-semibold">{incident.incident_type} · WW-{incident.incident_id}</div>
            <h3 className="portal-display text-lg font-bold mt-1">{incident.description}</h3>
            <div className="flex gap-2 mt-2"><StatusBadge status={incident.status} /></div>
          </div>
          <button onClick={onClose} className="text-[var(--p-ink-soft)] hover:text-[var(--p-ink)]"><X size={18} /></button>
        </div>
        <div className="p-5 space-y-4 text-[13px]">
          <div className="grid grid-cols-2 gap-3">
            <Info label="Park" value={incident.park?.park_name ?? "—"} />
            <Info label="Village" value={incident.village ?? "—"} />
            <Info label="Species" value={incident.species?.common_name ?? "—"} />
            <Info label="Reporter" value={incident.reporter ? `${incident.reporter.first_name} ${incident.reporter.last_name}` : "—"} />
            <Info label="Reported at" value={fmtDate(incident.created_at)} />
            <Info label="Coordinates" value={`${Number(incident.latitude).toFixed(3)}, ${Number(incident.longitude).toFixed(3)}`} />
          </div>

          <div>
            <div className="text-[11px] uppercase tracking-wider text-[var(--p-ink-soft)] font-semibold mb-1.5">Assignment</div>
            <div className="portal-card p-3">
              <div className="flex items-center justify-between mb-2">
                <div className="flex items-center gap-2"><UserCheck size={16} className="text-[var(--p-olive)]" /><span className="font-semibold">{assignedRanger ? `${assignedRanger.first_name} ${assignedRanger.last_name}` : "No personnel assigned"}</span></div>
              </div>
              <div className="flex gap-2">
                <select className="portal-input flex-1" value={rangerId} onChange={(e) => setRangerId(e.target.value)}>
                  <option value="">Select a ranger…</option>
                  {rangers.map((r) => (
                    <option key={r.user_id} value={r.user_id}>{r.first_name} {r.last_name} ({r.active_assignments_count} active)</option>
                  ))}
                </select>
                <button className="portal-btn" disabled={!rangerId || busy} onClick={assign}>{assignedRanger ? "Reassign" : "Assign"}</button>
              </div>
            </div>
          </div>

          {err && <div className="text-[12px] text-[var(--p-danger)] bg-[var(--p-danger)]/10 border border-[var(--p-danger)]/30 rounded-md px-3 py-2">{err}</div>}

          <div className="flex gap-2 pt-2">
            <button className="portal-btn portal-btn-gold flex-1 justify-center" disabled={busy} onClick={() => setStatus("Escalated")}><AlertTriangle size={13} /> Escalate</button>
            <button className="portal-btn flex-1 justify-center" disabled={busy} onClick={() => setStatus("Resolved")}><CheckCircle2 size={13} /> Mark resolved</button>
          </div>
          <div className="text-[11px] text-[var(--p-ink-soft)] italic flex items-center gap-1"><MapPin size={11} /> Coordinates shown are as originally reported.</div>
        </div>
      </div>
    </div>
  );
}

function Info({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <div className="text-[10px] uppercase tracking-wider text-[var(--p-ink-soft)] font-semibold">{label}</div>
      <div className="mt-0.5 font-medium">{value}</div>
    </div>
  );
}
