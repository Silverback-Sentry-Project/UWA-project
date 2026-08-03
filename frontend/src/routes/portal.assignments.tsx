import { createFileRoute } from "@tanstack/react-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { PortalShell, StatusBadge } from "@/components/portal/PortalShell";
import { apiFetch, ApiError } from "@/lib/api";
import type { Paginated } from "@/lib/api-types";
import { UserCheck } from "lucide-react";
import { useState } from "react";
import { useAuth } from "@/lib/auth";
import { usePark } from "@/lib/park-context";

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
  const { user } = useAuth();
  const { selectedParkId } = usePark();

  const { data: incidentsData, isLoading: loadingIncidents } = useQuery({
    queryKey: ["incidents", "New", selectedParkId],
    queryFn: () => {
        const params = new URLSearchParams({ status: "New" });
        if (selectedParkId) params.set("park_id", selectedParkId);
        return apiFetch<Paginated<IncidentRow>>(`/incidents?${params.toString()}`);
    },
  });
  const { data: inProgressData } = useQuery({
    queryKey: ["incidents", "Assigned", selectedParkId],
    queryFn: () => {
        const params = new URLSearchParams({ status: "Assigned" });
        if (selectedParkId) params.set("park_id", selectedParkId);
        return apiFetch<Paginated<IncidentRow>>(`/incidents?${params.toString()}`);
    },
  });
  const { data: rangers, isLoading: loadingRangers } = useQuery({
    queryKey: ["rangers", selectedParkId],
    queryFn: () => {
        const params = new URLSearchParams();
        if (selectedParkId) params.set("park_id", selectedParkId);
        return apiFetch<Ranger[]>(`/rangers?${params.toString()}`);
    },
  });

  const unassigned = incidentsData?.data ?? [];
  const inProgress = inProgressData?.data ?? [];

  async function assign(incidentId: number) {
    if (!rangerChoice) return;
    setErr(null);
    try {
      await apiFetch(`/gamepark/incidents/${incidentId}/assign`, { method: "POST", body: JSON.stringify({ ranger_id: Number(rangerChoice) }) });
      await queryClient.invalidateQueries({ queryKey: ["incidents"] });
      await queryClient.invalidateQueries({ queryKey: ["rangers"] });
      setAssigning(null);
      setRangerChoice("");
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Couldn't assign ranger.");
    }
  }

  return (
    <PortalShell title="Command & Dispatch" subtitle={`Strategic personnel allocation for field response teams.`}
      helpText="Select an unassigned case from the primary queue, select a field operator from the live roster, and initiate deployment. Cases transition to In Progress immediately upon assignment.">
      <div className="grid grid-cols-12 gap-8">
        <div className="col-span-4 space-y-4">
          <div className="flex items-center justify-between px-2">
            <h3 className="portal-display text-sm font-black text-neutral-900 uppercase tracking-widest">Active Queue</h3>
            <span className="text-[10px] font-black bg-red-500 text-white px-2 py-0.5 rounded-full">{unassigned.length}</span>
          </div>
          <div className="portal-card bg-white shadow-sm border-neutral-100 h-[700px] flex flex-col">
            <div className="p-4 border-b border-neutral-50 bg-neutral-50/30">
               <p className="text-[11px] text-neutral-400 font-bold uppercase tracking-widest">Awaiting Dispatch</p>
            </div>
            <div className="flex-1 overflow-auto p-3 space-y-3 custom-scrollbar">
              {loadingIncidents && <div className="text-[12px] text-neutral-400 p-2 italic animate-pulse">Scanning field frequencies…</div>}
              {!loadingIncidents && unassigned.length === 0 && <div className="text-[12px] text-neutral-300 p-2 text-center py-12 italic">Queue clear. All sectors quiet.</div>}
              {unassigned.map((i) => (
                <div key={i.incident_id} className={`p-4 rounded-2xl border transition-all duration-300 ${assigning === i.incident_id ? "border-[#1A2F1A] bg-emerald-50/20 shadow-md" : "border-neutral-100 bg-white hover:border-neutral-200 shadow-sm"}`}>
                  <div className="flex items-center justify-between">
                    <div className="font-mono text-[10px] font-black text-neutral-300">WW-{i.incident_id}</div>
                    <StatusBadge status={i.status} />
                  </div>
                  <div className="text-[13px] font-black text-neutral-800 mt-2">{i.incident_type}</div>
                  <div className="text-[11px] text-neutral-500 line-clamp-2 mt-1 leading-snug font-medium">{i.description}</div>
                  <div className="mt-3 flex items-center justify-between">
                    <span className="text-[10px] font-bold text-neutral-400 uppercase tracking-widest">{fmtDate(i.created_at)}</span>
                  </div>
                  {assigning === i.incident_id ? (
                    <div className="mt-4 pt-4 border-t border-neutral-100 space-y-2">
                      <select className="portal-input w-full text-[12px] font-bold bg-white" value={rangerChoice} onChange={(e) => setRangerChoice(e.target.value)}>
                        <option value="">Select Operator…</option>
                        {(rangers ?? []).map((r) => <option key={r.user_id} value={r.user_id}>{r.first_name} {r.last_name}</option>)}
                      </select>
                      <div className="flex gap-2">
                        <button className="portal-btn flex-1 text-[11px] h-9 shadow-sm" disabled={!rangerChoice} onClick={() => assign(i.incident_id)}>Deploy</button>
                        <button className="portal-btn portal-btn-ghost flex-1 text-[11px] h-9" onClick={() => setAssigning(null)}>Abort</button>
                      </div>
                    </div>
                  ) : (
                    <button className="portal-btn portal-btn-ghost text-[11px] w-full justify-center mt-4 h-9 font-bold border-neutral-200" onClick={() => setAssigning(i.incident_id)}><UserCheck size={14} /> Assign Operator</button>
                  )}
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className="col-span-4 space-y-4">
          <h3 className="portal-display text-sm font-black text-neutral-900 px-2 uppercase tracking-widest">Active Roster</h3>
          <div className="portal-card bg-white shadow-sm border-neutral-100 h-[700px] overflow-hidden flex flex-col">
            <div className="p-4 border-b border-neutral-50 bg-neutral-50/30">
               <p className="text-[11px] text-neutral-400 font-bold uppercase tracking-widest">Live Operator Load</p>
            </div>
            <div className="flex-1 overflow-auto p-3 space-y-2 custom-scrollbar">
              {loadingRangers && <div className="text-[12px] text-neutral-400 p-2 italic animate-pulse">Authenticating personnel IDs…</div>}
              {(rangers ?? []).map((r) => (
                <div key={r.user_id} className="p-4 rounded-2xl border border-neutral-50 bg-white flex items-center gap-4 hover:border-neutral-200 transition-colors">
                  <div className="h-11 w-11 rounded-xl bg-neutral-100 text-[#1A2F1A] flex items-center justify-center text-[13px] font-black border border-neutral-200 shadow-sm">{`${r.first_name[0] ?? ""}${r.last_name[0] ?? ""}`}</div>
                  <div className="flex-1 min-w-0">
                    <div className="text-[13px] font-black text-neutral-800 leading-tight tracking-tight">{r.first_name} {r.last_name}</div>
                    <div className="mt-1 flex items-center gap-2">
                       <div className="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
                       <span className="text-[10px] font-black text-neutral-400 uppercase tracking-widest">{r.active_assignments_count} Operations</span>
                    </div>
                  </div>
                </div>
              ))}
              {!loadingRangers && (rangers ?? []).length === 0 && <div className="text-[12px] text-neutral-300 p-6 text-center italic">Sector roster empty.</div>}
            </div>
          </div>
        </div>

        <div className="col-span-4 space-y-4">
          <h3 className="portal-display text-sm font-black text-neutral-900 px-2 uppercase tracking-widest">Mission Track</h3>
          <div className="portal-card bg-white shadow-sm border-neutral-100 h-[700px] overflow-hidden flex flex-col">
            <div className="p-4 border-b border-neutral-50 bg-neutral-50/30">
               <p className="text-[11px] text-neutral-400 font-bold uppercase tracking-widest">In Progress</p>
            </div>
            <div className="flex-1 overflow-auto p-3 space-y-3 custom-scrollbar">
              {inProgress.map((i) => (
                <div key={i.incident_id} className="p-4 rounded-2xl border border-neutral-100 bg-[#1A2F1A]/[0.02] shadow-sm">
                  <div className="flex items-center justify-between text-[11px]">
                    <div className="font-mono font-black text-neutral-300 uppercase">OPER WW-{i.incident_id}</div>
                    <StatusBadge status={i.status} />
                  </div>
                  <div className="text-[13px] font-black text-neutral-800 mt-2">{i.incident_type}</div>
                  <div className="mt-4 flex items-center gap-3 p-3 bg-white rounded-xl border border-neutral-100 shadow-sm">
                    <div className="h-7 w-7 rounded-lg bg-[#1A2F1A] text-white flex items-center justify-center text-[10px] font-black shadow-lg">
                       {i.assignments?.[0]?.ranger?.first_name[0]}{i.assignments?.[0]?.ranger?.last_name[0]}
                    </div>
                    <div className="flex-1 min-w-0">
                       <div className="text-[11px] font-black text-neutral-800 truncate uppercase tracking-tight">{i.assignments?.[0]?.ranger ? `${i.assignments[0].ranger!.first_name} ${i.assignments[0].ranger!.last_name}` : "Unassigned"}</div>
                       <div className="text-[9px] font-bold text-neutral-400 uppercase tracking-widest mt-0.5">Assigned Lead</div>
                    </div>
                  </div>
                  <div className="mt-3 text-[10px] font-bold text-neutral-300 uppercase tracking-widest">Logged {fmtDate(i.created_at)}</div>
                </div>
              ))}
              {inProgress.length === 0 && <div className="text-[12px] text-neutral-300 p-6 text-center italic">No active missions in field.</div>}
            </div>
          </div>
        </div>
      </div>
      {err && <div className="mt-6 text-[12px] font-black text-red-600 bg-red-50 border border-red-100 rounded-2xl px-4 py-3 animate-in shake-in duration-300">{err}</div>}
    </PortalShell>
  );
}
