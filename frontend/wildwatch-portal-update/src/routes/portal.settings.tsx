import { createFileRoute } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { PortalShell } from "@/components/portal/PortalShell";
import { apiFetch } from "@/lib/api";
import type { Paginated } from "@/lib/api-types";
import { ShieldCheck, KeyRound, UserPlus } from "lucide-react";

export const Route = createFileRoute("/portal/settings")({ component: Personnel });

interface PersonRow {
  user_id: number;
  first_name: string;
  last_name: string;
  email: string | null;
  phone_number: string | null;
  account_status: "Pending" | "Active" | "Suspended";
  roles?: Array<{ role_name: string }>;
}

function Personnel() {
  const { data, isLoading, isError, error } = useQuery({
    queryKey: ["users"],
    queryFn: () => apiFetch<Paginated<PersonRow>>("/users"),
  });
  const people = data?.data ?? [];
  const active = people.filter((p) => p.account_status === "Active").length;

  return (
    <PortalShell title="Personnel & Access" subtitle="All UWA staff accounts registered on the system."
      helpText="Roles and account status shown here reflect what's stored on each account. Inviting new personnel needs a role picker wired to the backend — ask me to add it once you've decided how roles should be exposed."
      actions={<button className="portal-btn portal-btn-gold" title="Not yet wired up — see help text"><UserPlus size={13} /> Invite personnel</button>}>
      <div className="grid grid-cols-3 gap-4 mb-4">
        <div className="portal-card p-4">
          <div className="flex items-center gap-2 text-[var(--p-olive-deep)]"><ShieldCheck size={16} /><span className="text-[12px] font-semibold uppercase tracking-wider">Active accounts</span></div>
          <div className="mt-2 portal-display text-2xl font-bold">{active}</div>
          <div className="text-[11px] text-[var(--p-ink-soft)]">of {people.length} total</div>
        </div>
        <div className="portal-card p-4">
          <div className="flex items-center gap-2 text-[var(--p-olive-deep)]"><KeyRound size={16} /><span className="text-[12px] font-semibold uppercase tracking-wider">Session policy</span></div>
          <div className="mt-2 portal-display text-2xl font-bold">Token-based</div>
          <div className="text-[11px] text-[var(--p-ink-soft)]">Sanctum bearer tokens</div>
        </div>
        <div className="portal-card p-4">
          <div className="flex items-center gap-2 text-[var(--p-olive-deep)]"><ShieldCheck size={16} /><span className="text-[12px] font-semibold uppercase tracking-wider">Data transport</span></div>
          <div className="mt-2 portal-display text-2xl font-bold">HTTPS</div>
          <div className="text-[11px] text-[var(--p-ink-soft)]">Recommended in production</div>
        </div>
      </div>

      {isLoading && <div className="portal-card p-6 text-sm text-[var(--p-ink-soft)]">Loading personnel…</div>}
      {isError && <div className="portal-card p-6 text-sm text-[var(--p-danger)]">Couldn't load personnel: {error instanceof Error ? error.message : "unknown error"}</div>}

      {data && (
        <div className="portal-card overflow-hidden">
          <div className="p-4 border-b border-[var(--p-olive-line)]">
            <h3 className="portal-display text-sm font-bold">UWA personnel</h3>
          </div>
          <table className="portal-table">
            <thead><tr><th>Name</th><th>Contact</th><th>Role</th><th>Status</th></tr></thead>
            <tbody>
              {people.length === 0 && <tr><td colSpan={4} className="text-center text-[var(--p-ink-soft)] py-4">No personnel found.</td></tr>}
              {people.map((p) => (
                <tr key={p.user_id}>
                  <td className="font-semibold">{p.first_name} {p.last_name}</td>
                  <td className="text-[var(--p-ink-soft)]">{p.email ?? p.phone_number ?? "—"}</td>
                  <td>{(p.roles ?? []).map((r) => <span key={r.role_name} className="portal-chip mr-1">{r.role_name}</span>)}</td>
                  <td>
                    <span className="portal-chip" style={
                      p.account_status === "Active" ? { background: "oklch(0.95 0.04 150)", color: "oklch(0.4 0.1 150)" } :
                      p.account_status === "Suspended" ? { background: "oklch(0.94 0.05 27)", color: "oklch(0.5 0.18 27)" } :
                      { background: "oklch(0.96 0.03 85)", color: "oklch(0.45 0.13 85)" }
                    }>{p.account_status}</span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </PortalShell>
  );
}
