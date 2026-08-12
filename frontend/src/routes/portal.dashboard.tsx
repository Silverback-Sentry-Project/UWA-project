import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { PortalShell, StatCard, StatusBadge, EscalatedBadge } from "@/components/portal/PortalShell";
import { CheckCircle2, Clock4, Flame, Download, RefreshCw, ChevronRight, Zap } from "lucide-react";
import { apiFetch } from "@/lib/api";
import { useAuth } from "@/lib/auth";
import { usePark } from "@/lib/park-context";

export const Route = createFileRoute("/portal/dashboard")({ component: Dashboard });

interface DashboardStats {
  parks_count: number;
  rangers_count: number;
  incidents: {
    total: number;
    new: number;
    assigned: number;
    in_progress: number;
    resolved: number;
    escalated: number;
  };
  sos_alerts: { total: number; pending: number; responding: number };
  recent_incidents: Array<{
    incident_id: string | number;
    incident_type: string;
    status: string;
    is_escalated: boolean;
    created_at: string;
    park?: { park_name: string } | null;
  }>;
}

function fmtDate(iso: string) {
  const d = new Date(iso);
  return d.toLocaleString("en-GB", {
    day: "2-digit",
    month: "short",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function Dashboard() {
  const { user } = useAuth();
  const { selectedParkId } = usePark();
  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ["dashboard-stats", selectedParkId],
    queryFn: () =>
      apiFetch<DashboardStats>(
        `/dashboard/stats${selectedParkId ? `?park_id=${selectedParkId}` : ""}`,
      ),
  });

  const firstName = user?.full_name?.split(" ")[0] ?? "";

  return (
    <PortalShell
      title={`Welcome back${firstName ? `, ${firstName}` : ""}`}
      subtitle="Operational summary for your active mission parameters."
      helpText="Analytics are derived from field reports synced via mobile rangers and community citizens."
      actions={
        <>
          <button onClick={() => refetch()} className="portal-btn-ghost portal-btn py-2 px-3 h-10">
            <RefreshCw size={14} className={isFetching ? "animate-spin" : undefined} /> Sync Data
          </button>
          <button className="portal-btn portal-btn-gold py-2 px-4 h-10 shadow-sm">
            <Download size={14} /> System Report
          </button>
        </>
      }
    >
      {isLoading && (
        <div className="grid grid-cols-4 gap-6">
          {[1, 2, 3, 4].map((i) => (
            <div
              key={i}
              className="h-32 rounded-3xl bg-white border border-neutral-100 animate-pulse"
            />
          ))}
        </div>
      )}

      {isError && (
        <div className="portal-card p-8 border-red-100 bg-red-50/30">
          <div className="text-red-600 font-bold portal-display text-sm flex items-center gap-2">
            <Flame size={16} /> Connection Interrupted
          </div>
          <p className="text-red-500/80 text-[13px] mt-1">
            {error instanceof Error ? error.message : "The system encountered an unexpected fault."}
          </p>
        </div>
      )}

      {data && (
        <div className="space-y-8 animate-in fade-in duration-500">
          <div className="grid grid-cols-4 gap-6">
            <StatCard
              label="Active incidents"
              value={data.incidents.new + data.incidents.assigned + data.incidents.in_progress}
              tone="danger"
              icon={Zap}
              to="/portal/incidents"
              hint="Active field operations"
            />
            <StatCard
              label="Pending triage"
              value={data.incidents.new}
              tone="gold"
              icon={Clock4}
              to="/portal/incidents"
              hint="Unassigned reports"
            />
            <StatCard
              label="Resolved"
              value={data.incidents.resolved}
              tone="olive"
              icon={CheckCircle2}
              to="/portal/incidents"
              hint="Verified closed"
            />
            <StatCard
              label="SOS priority"
              value={data.sos_alerts.pending}
              tone="danger"
              icon={Flame}
              to="/portal/conflicts"
              hint="Immediate response req."
            />
          </div>

          <div className="grid grid-cols-3 gap-8">
            <div className="col-span-2 space-y-4">
              <div className="flex items-center justify-between px-2">
                <h3 className="portal-display text-lg font-black text-neutral-900 tracking-tight">
                  Recent Activity
                </h3>
                <Link
                  to="/portal/incidents"
                  className="text-[12px] font-bold text-[#1A2F1A] hover:bg-neutral-100 px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5 uppercase tracking-wider"
                >
                  Audit all cases <ChevronRight size={12} />
                </Link>
              </div>
              <div className="portal-card overflow-hidden shadow-sm border-neutral-100 bg-white/50 backdrop-blur-sm">
                <table className="portal-table border-collapse">
                  <thead>
                    <tr className="bg-neutral-50/50">
                      <th className="py-4 font-black">Callsign</th>
                      <th className="py-4 font-black">Type</th>
                      <th className="py-4 font-black">Region</th>
                      <th className="py-4 font-black text-center">Status</th>
                      <th className="py-4 font-black text-right pr-6">Observed</th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.recent_incidents.length === 0 && (
                      <tr>
                        <td colSpan={5} className="text-center text-neutral-400 py-12 italic">
                          No operational data detected.
                        </td>
                      </tr>
                    )}
                    {data.recent_incidents.map((i) => (
                      <tr
                        key={i.incident_id}
                        className="hover:bg-white transition-colors cursor-pointer group"
                      >
                        <td className="font-mono text-[11px] font-bold text-neutral-400 py-4">
                          INC-{i.incident_id}
                        </td>
                        <td className="font-bold text-neutral-800">{i.incident_type}</td>
                        <td className="text-neutral-500 font-medium">{i.park?.park_name ?? "—"}</td>
                        <td className="text-center">
                          <div className="flex items-center justify-center gap-1.5">
                            <StatusBadge status={i.status} />
                            {i.is_escalated && <EscalatedBadge />}
                          </div>
                        </td>
                        <td className="text-right pr-6 font-bold text-neutral-400 tabular-nums">
                          {fmtDate(i.created_at)}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>

            <div className="space-y-4">
              <div className="flex items-center justify-between px-2">
                <h3 className="portal-display text-lg font-black text-neutral-900 tracking-tight">
                  Mission Metrics
                </h3>
              </div>
              <div className="space-y-4">
                <div className="portal-card p-6 bg-white shadow-sm border-neutral-100">
                  <span className="text-[10px] font-black text-neutral-400 uppercase tracking-[0.2em]">
                    Regions Protected
                  </span>
                  <div className="text-4xl font-black portal-display mt-2 text-[#1A2F1A]">
                    {data.parks_count}
                  </div>
                  <div className="mt-4 h-1.5 w-full bg-neutral-100 rounded-full overflow-hidden">
                    <div className="h-full bg-[#1A2F1A] rounded-full" style={{ width: "100%" }} />
                  </div>
                </div>
                <div className="portal-card p-6 bg-white shadow-sm border-neutral-100">
                  <span className="text-[10px] font-black text-neutral-400 uppercase tracking-[0.2em]">
                    Active Rangers
                  </span>
                  <div className="text-4xl font-black portal-display mt-2 text-[#1A2F1A]">
                    {data.rangers_count}
                  </div>
                  <p className="mt-2 text-[11px] text-neutral-400 font-medium leading-relaxed">
                    Assigned personnel currently tracking field incidents across active sectors.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </PortalShell>
  );
}
