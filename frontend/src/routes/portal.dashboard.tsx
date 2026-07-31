import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { PortalShell, StatCard, StatusBadge } from "@/components/portal/PortalShell";
import { Activity, CheckCircle2, Clock4, Flame, Download, RefreshCw, LogOut } from "lucide-react";
import { apiFetch } from "@/lib/api";
import { useAuth } from "@/lib/auth";

export const Route = createFileRoute("/portal/dashboard")({ component: Dashboard });

interface DashboardStats {
  parks_count: number;
  rangers_count: number;
  incidents: { total: number; new: number; assigned: number; in_progress: number; resolved: number; escalated: number };
  sos_alerts: { total: number; pending: number; responding: number };
  claims: {
    total: number; submitted: number; under_review: number; approved: number;
    rejected: number; paid: number; total_amount_estimated: number;
  };
  recent_incidents: Array<{
    incident_id: string | number;
    incident_type: string;
    status: string;
    created_at: string;
    park?: { park_name: string } | null;
  }>;
}

function fmtDate(iso: string) {
  const d = new Date(iso);
  return d.toLocaleString("en-GB", { day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit" });
}

function Dashboard() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ["dashboard-stats"],
    queryFn: () => apiFetch<DashboardStats>("/dashboard/stats"),
  });

  const firstName = user?.full_name?.split(" ")[0] ?? "";

  async function handleSignOut() {
    await logout();
    navigate({ to: "/portal" });
  }

  return (
    <PortalShell title={`Welcome back${firstName ? `, ${firstName}` : ""}`} subtitle="Here's what's happening across Uganda's national parks today."
      helpText="Tap any KPI card to jump to the filtered list. Use the park selector in the top bar to scope this view."
      actions={<>
        <button onClick={() => refetch()} className="portal-btn-ghost portal-btn"><RefreshCw size={13} className={isFetching ? "animate-spin" : undefined} /> Refresh</button>
        <button className="portal-btn portal-btn-gold"><Download size={13} /> Export report</button>
        <button onClick={handleSignOut} className="portal-btn-ghost portal-btn"><LogOut size={13} /> Log out</button>
      </>}>
      {isLoading && <div className="portal-card p-6 text-sm text-[var(--p-ink-soft)]">Loading dashboard…</div>}

      {isError && (
        <div className="portal-card p-6 text-sm text-[var(--p-danger)]">
          Couldn't load dashboard stats: {error instanceof Error ? error.message : "unknown error"}
        </div>
      )}

      {data && (
        <>
          <div className="grid grid-cols-4 gap-4">
            <StatCard label="Active incidents" value={data.incidents.new + data.incidents.assigned + data.incidents.in_progress}
              tone="danger" icon={Activity} to="/portal/incidents" hint="New, assigned, or in progress" />
            <StatCard label="Pending review" value={data.incidents.new}
              tone="gold" icon={Clock4} to="/portal/incidents" hint="Not yet assigned to a ranger" />
            <StatCard label="Resolved" value={data.incidents.resolved}
              tone="olive" icon={CheckCircle2} to="/portal/incidents" hint="Closed incidents" />
            <StatCard label="SOS pending" value={data.sos_alerts.pending}
              tone="danger" icon={Flame} to="/portal/conflicts" hint="Emergency alerts awaiting response" />
          </div>

          <div className="grid grid-cols-3 gap-4 mt-6">
            <div className="portal-card col-span-2">
              <div className="flex items-center justify-between p-5 pb-3">
                <h3 className="portal-display text-sm font-bold">Recent incidents</h3>
                <Link to="/portal/incidents" className="text-[12px] font-semibold text-[var(--p-olive-deep)] hover:underline">View all →</Link>
              </div>
              <table className="portal-table">
                <thead><tr><th>ID</th><th>Type</th><th>Park</th><th>Status</th><th>Reported</th></tr></thead>
                <tbody>
                  {data.recent_incidents.length === 0 && (
                    <tr><td colSpan={5} className="text-center text-[var(--p-ink-soft)] py-4">No incidents yet.</td></tr>
                  )}
                  {data.recent_incidents.map((i) => (
                    <tr key={i.incident_id}>
                      <td className="font-mono text-[12px]">{i.incident_id}</td>
                      <td>{i.incident_type}</td>
                      <td>{i.park?.park_name ?? "—"}</td>
                      <td><StatusBadge status={i.status} /></td>
                      <td className="text-[var(--p-ink-soft)]">{fmtDate(i.created_at)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div className="portal-card p-5">
              <h3 className="portal-display text-sm font-bold">Claims pipeline</h3>
              <p className="text-[11px] text-[var(--p-ink-soft)]">Compensation status</p>
              <div className="mt-4 space-y-3">
                {([
                  ["Submitted", data.claims.submitted],
                  ["Under Verification", data.claims.under_review],
                  ["Approved", data.claims.approved],
                  ["Paid", data.claims.paid],
                  ["Rejected", data.claims.rejected],
                ] as const).map(([label, n]) => (
                  <div key={label} className="flex items-center justify-between text-[12px]">
                    <StatusBadge status={label} />
                    <span className="font-bold portal-display">{n}</span>
                  </div>
                ))}
              </div>
              <Link to="/portal/claims" className="mt-4 portal-btn portal-btn-ghost w-full justify-center">Manage claims</Link>
            </div>
          </div>

          <div className="grid grid-cols-3 gap-4 mt-4 text-[12px]">
            <div className="portal-card p-4"><span className="text-[var(--p-ink-soft)]">Parks monitored</span><div className="text-xl font-bold portal-display mt-1">{data.parks_count}</div></div>
            <div className="portal-card p-4"><span className="text-[var(--p-ink-soft)]">Active rangers</span><div className="text-xl font-bold portal-display mt-1">{data.rangers_count}</div></div>
            <div className="portal-card p-4"><span className="text-[var(--p-ink-soft)]">Estimated claims value</span><div className="text-xl font-bold portal-display mt-1">UGX {Number(data.claims.total_amount_estimated ?? 0).toLocaleString("en-UG")}</div></div>
          </div>
        </>
      )}
    </PortalShell>
  );
}
