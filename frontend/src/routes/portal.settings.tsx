import { createFileRoute } from "@tanstack/react-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { PortalShell } from "@/components/portal/PortalShell";
import { apiFetch, ApiError } from "@/lib/api";
import type { Paginated } from "@/lib/api-types";
import { ShieldCheck, KeyRound, UserPlus } from "lucide-react";
import { useState } from "react";

export const Route = createFileRoute("/portal/settings")({ component: Personnel });

interface PersonRow {
  user_id: number;
  first_name: string;
  last_name: string;
  email: string | null;
  phone_number: string | null;
  account_status: "Pending" | "Active" | "Suspended";
  roles?: Array<{ role_name: string }>;
  park?: { park_id: number; park_name: string } | null;
}

interface RoleOption {
  role_id: number;
  role_name: string;
}

interface ParkOption {
  park_id: number;
  park_name: string;
}

function Personnel() {
  const queryClient = useQueryClient();
  const [inviteOpen, setInviteOpen] = useState(false);
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [email, setEmail] = useState("");
  const [roleId, setRoleId] = useState("");
  const [inviteParkId, setInviteParkId] = useState("");
  const [sending, setSending] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);

  // Filters
  const [filterPark, setFilterPark] = useState("");
  const [filterRole, setFilterRole] = useState("");

  const { data: parks } = useQuery({
    queryKey: ["parks"],
    queryFn: () => apiFetch<ParkOption[]>("/parks"),
  });
  const { data: roles } = useQuery({
    queryKey: ["roles"],
    queryFn: () => apiFetch<RoleOption[]>("/roles"),
  });

  const params = new URLSearchParams();
  if (filterPark) params.set("park_id", filterPark);
  if (filterRole) params.set("role", filterRole);
  const qs = params.toString();

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ["users", filterPark, filterRole],
    queryFn: () => apiFetch<Paginated<PersonRow>>(`/users${qs ? `?${qs}` : ""}`),
  });
  const people = data?.data ?? [];
  const active = people.filter((p) => p.account_status === "Active").length;

  function openInvite() {
    setFirstName(""); setLastName(""); setEmail(""); setRoleId(""); setInviteParkId("");
    setErr(null); setSuccessMsg(null);
    setInviteOpen(true);
  }

  const selectedRoleName = (roles ?? []).find((r) => String(r.role_id) === roleId)?.role_name;
  const parkRelevantRole = selectedRoleName && ["Ranger", "Community Wildlife Officer", "Park Warden"].includes(selectedRoleName);

  async function sendInvite() {
    setErr(null);
    if (!firstName.trim() || !lastName.trim() || !email.trim() || !roleId) {
      setErr("Fill in name, email, and role.");
      return;
    }
    setSending(true);
    try {
      const res = await apiFetch<{ mail_sent: boolean; message: string }>("/users/invite", {
        method: "POST",
        body: JSON.stringify({
          first_name: firstName, last_name: lastName, email, role_id: Number(roleId),
          park_id: inviteParkId ? Number(inviteParkId) : undefined,
        }),
      });
      await queryClient.invalidateQueries({ queryKey: ["users"] });
      setSuccessMsg(res.message);
      setFirstName(""); setLastName(""); setEmail(""); setRoleId(""); setInviteParkId("");
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Couldn't send the invite.");
    } finally {
      setSending(false);
    }
  }

  return (
    <PortalShell title="Personnel & Access" subtitle="All UWA staff accounts registered on the system."
      actions={<button className="portal-btn portal-btn-gold" onClick={openInvite}><UserPlus size={13} /> Invite personnel</button>}>
      <div className="grid grid-cols-3 gap-4 mb-4">
        <div className="portal-card p-4">
          <div className="flex items-center gap-2 text-[var(--p-olive-deep)]"><ShieldCheck size={16} /><span className="text-[12px] font-semibold uppercase tracking-wider">Active accounts</span></div>
          <div className="mt-2 portal-display text-2xl font-bold">{active}</div>
          <div className="text-[11px] text-[var(--p-ink-soft)]">of {people.length} shown</div>
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

      <div className="flex items-center gap-2 mb-3">
        <select className="portal-input w-56" value={filterPark} onChange={(e) => setFilterPark(e.target.value)}>
          <option value="">All parks</option>
          {(parks ?? []).map((p) => <option key={p.park_id} value={p.park_id}>{p.park_name}</option>)}
        </select>
        <select className="portal-input w-56" value={filterRole} onChange={(e) => setFilterRole(e.target.value)}>
          <option value="">All roles</option>
          {(roles ?? []).map((r) => <option key={r.role_id} value={r.role_name}>{r.role_name}</option>)}
        </select>
        {(filterPark || filterRole) && (
          <button className="portal-btn portal-btn-ghost text-[12px]" onClick={() => { setFilterPark(""); setFilterRole(""); }}>Clear filters</button>
        )}
      </div>

      {isLoading && <div className="portal-card p-6 text-sm text-[var(--p-ink-soft)]">Loading personnel…</div>}
      {isError && <div className="portal-card p-6 text-sm text-[var(--p-danger)]">Couldn't load personnel: {error instanceof Error ? error.message : "unknown error"}</div>}

      {data && (
        <div className="portal-card overflow-hidden">
          <div className="p-4 border-b border-[var(--p-olive-line)]">
            <h3 className="portal-display text-sm font-bold">UWA personnel</h3>
          </div>
          <table className="portal-table">
            <thead><tr><th>Name</th><th>Contact</th><th>Role</th><th>Park</th><th>Status</th></tr></thead>
            <tbody>
              {people.length === 0 && <tr><td colSpan={5} className="text-center text-[var(--p-ink-soft)] py-4">No personnel found.</td></tr>}
              {people.map((p) => (
                <tr key={p.user_id}>
                  <td className="font-semibold">{p.first_name} {p.last_name}</td>
                  <td className="text-[var(--p-ink-soft)]">{p.email ?? p.phone_number ?? "—"}</td>
                  <td>{(p.roles ?? []).map((r) => <span key={r.role_name} className="portal-chip mr-1">{r.role_name}</span>)}</td>
                  <td className="text-[var(--p-ink-soft)]">{p.park?.park_name ?? "—"}</td>
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

      {inviteOpen && (
        <div className="fixed inset-0 bg-black/40 grid place-items-center z-50 p-4" onClick={() => setInviteOpen(false)}>
          <div className="bg-white rounded-xl w-full max-w-md" onClick={(e) => e.stopPropagation()}>
            <div className="p-5 border-b border-[var(--p-olive-line)]">
              <h3 className="portal-display text-lg font-bold">Invite personnel</h3>
              <p className="text-[12px] text-[var(--p-ink-soft)] mt-0.5">They'll receive a temporary password by email and can change it after signing in.</p>
            </div>
            <div className="p-5 space-y-3">
              <div className="grid grid-cols-2 gap-2">
                <label className="block">
                  <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">First name</span>
                  <input className="portal-input mt-1" value={firstName} onChange={(e) => setFirstName(e.target.value)} />
                </label>
                <label className="block">
                  <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">Last name</span>
                  <input className="portal-input mt-1" value={lastName} onChange={(e) => setLastName(e.target.value)} />
                </label>
              </div>
              <label className="block">
                <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">Email</span>
                <input className="portal-input mt-1" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="name@uwa.go.ug" />
              </label>
              <label className="block">
                <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">Role</span>
                <select className="portal-input mt-1" value={roleId} onChange={(e) => setRoleId(e.target.value)}>
                  <option value="">Select a role…</option>
                  {(roles ?? []).map((r) => <option key={r.role_id} value={r.role_id}>{r.role_name}</option>)}
                </select>
              </label>
              {parkRelevantRole && (
                <label className="block">
                  <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">Park (optional)</span>
                  <select className="portal-input mt-1" value={inviteParkId} onChange={(e) => setInviteParkId(e.target.value)}>
                    <option value="">Not park-specific</option>
                    {(parks ?? []).map((p) => <option key={p.park_id} value={p.park_id}>{p.park_name}</option>)}
                  </select>
                </label>
              )}
              {err && <div className="text-[12px] text-[var(--p-danger)] bg-[var(--p-danger)]/10 border border-[var(--p-danger)]/30 rounded-md px-3 py-2">{err}</div>}
              {successMsg && <div className="text-[12px] text-[var(--p-olive-deep)] bg-[var(--p-olive-soft)] border border-[var(--p-olive-line)] rounded-md px-3 py-2">{successMsg}</div>}
            </div>
            <div className="p-5 border-t border-[var(--p-olive-line)] flex items-center justify-end gap-2">
              <button className="portal-btn portal-btn-ghost" onClick={() => setInviteOpen(false)}>Close</button>
              <button className="portal-btn" disabled={sending} onClick={sendInvite}>{sending ? "Sending…" : "Send invite"}</button>
            </div>
          </div>
        </div>
      )}
    </PortalShell>
  );
}
