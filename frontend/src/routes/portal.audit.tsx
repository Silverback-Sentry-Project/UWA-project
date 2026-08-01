import { createFileRoute } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { PortalShell } from "@/components/portal/PortalShell";
import { apiFetch } from "@/lib/api";
import type { Paginated } from "@/lib/api-types";
import { ShieldCheck, Download } from "lucide-react";

export const Route = createFileRoute("/portal/audit")({ component: Audit });

interface AuditEntry {
  status_history_id: number;
  incident_id: number;
  old_status: string;
  new_status: string;
  remarks: string | null;
  updated_at: string;
  updatedBy?: { first_name: string; last_name: string; roles?: Array<{ role_name: string }> } | null;
}

function fmtDate(iso: string) {
  return new Date(iso).toLocaleString("en-GB", { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" });
}

function Audit() {
  const { data, isLoading, isError, error } = useQuery({
    queryKey: ["audit"],
    queryFn: () => apiFetch<Paginated<AuditEntry>>("/audit"),
  });
  const entries = data?.data ?? [];

  return (
    <PortalShell title="Audit Log" subtitle="Record of incident status changes across the portal."
      helpText="Every status change on an incident — assignment, escalation, resolution — is recorded here with who made it."
      actions={<button className="portal-btn portal-btn-gold"><Download size={13} /> Export log</button>}>
      {isLoading && <div className="portal-card p-6 text-sm text-[var(--p-ink-soft)]">Loading audit log…</div>}
      {isError && <div className="portal-card p-6 text-sm text-[var(--p-danger)]">Couldn't load audit log: {error instanceof Error ? error.message : "unknown error"}</div>}

      {data && (
        <div className="portal-card overflow-hidden">
          <div className="px-4 py-3 border-b border-[var(--p-olive-line)] bg-[var(--p-olive-soft)] flex items-center gap-2 text-[11px] text-[var(--p-olive-deep)]">
            <ShieldCheck size={13} /> <span className="font-semibold">{entries.length} recorded status changes</span>
          </div>
          <table className="portal-table">
            <thead><tr><th>Timestamp</th><th>Actor</th><th>Incident</th><th>From</th><th>To</th><th>Remarks</th></tr></thead>
            <tbody>
              {entries.length === 0 && <tr><td colSpan={6} className="text-center text-[var(--p-ink-soft)] py-4">No status changes recorded yet.</td></tr>}
              {entries.map((a) => (
                <tr key={a.status_history_id}>
                  <td className="text-[var(--p-ink-soft)]">{fmtDate(a.updated_at)}</td>
                  <td className="font-semibold">{a.updatedBy ? `${a.updatedBy.first_name} ${a.updatedBy.last_name}` : "—"}</td>
                  <td className="font-mono text-[12px]">WW-{a.incident_id}</td>
                  <td>{a.old_status}</td>
                  <td className="font-semibold">{a.new_status}</td>
                  <td className="text-[12px] text-[var(--p-ink-soft)]">{a.remarks ?? "—"}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </PortalShell>
  );
}
