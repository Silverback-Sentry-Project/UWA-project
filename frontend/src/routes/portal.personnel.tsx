import { createFileRoute } from "@tanstack/react-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { PortalShell } from "@/components/portal/PortalShell";
import { apiFetch, ApiError } from "@/lib/api";
import { UserPlus, ShieldCheck } from "lucide-react";
import { useState } from "react";

export const Route = createFileRoute("/portal/personnel")({ component: GameparkPersonnel });

interface PersonRow {
  user_id: number;
  first_name: string;
  last_name: string;
  email: string | null;
  phone_number: string | null;
  account_status: "Pending" | "Active" | "Suspended";
  roles?: Array<{ role_name: string }>;
}

// Only these roles can be invited from the Gamepark portal — field staff for this park.
const INVITABLE_ROLES = [
  { role_name: "Ranger" },
  { role_name: "Community Wildlife Officer" },
  { role_name: "Park Warden" },
];

function GameparkPersonnel() {
  const queryClient = useQueryClient();
  const [inviteOpen, setInviteOpen] = useState(false);
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [email, setEmail] = useState("");
  const [roleName, setRoleName] = useState("");
  const [roleIdByName, setRoleIdByName] = useState<Record<string, number>>({});
  const [sending, setSending] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);

  const { data: people, isLoading } = useQuery({
    queryKey: ["gamepark-personnel"],
    queryFn: () => apiFetch<PersonRow[]>("/gamepark/personnel"),
  });

  // Roles are a shared lookup; filter down to what this portal may invite.
  const { data: allRoles } = useQuery({
    queryKey: ["roles"],
    queryFn: () => apiFetch<Array<{ role_id: number; role_name: string }>>("/roles"),
    enabled: inviteOpen,
  });

  function openInvite() {
    setFirstName(""); setLastName(""); setEmail(""); setRoleName("");
    setErr(null); setSuccessMsg(null);
    setInviteOpen(true);
  }

  const invitableRoles = (allRoles ?? []).filter((r) => INVITABLE_ROLES.some((i) => i.role_name === r.role_name));

  async function sendInvite() {
    setErr(null);
    const role = invitableRoles.find((r) => r.role_name === roleName);
    if (!firstName.trim() || !lastName.trim() || !email.trim() || !role) {
      setErr("Fill in name, email, and role.");
      return;
    }
    setSending(true);
    try {
      const res = await apiFetch<{ mail_sent: boolean; message: string }>("/gamepark/personnel/invite", {
        method: "POST",
        body: JSON.stringify({ first_name: firstName, last_name: lastName, email, role_id: role.role_id }),
      });
      await queryClient.invalidateQueries({ queryKey: ["gamepark-personnel"] });
      setSuccessMsg(res.message);
      setFirstName(""); setLastName(""); setEmail(""); setRoleName("");
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Couldn't send the invite.");
    } finally {
      setSending(false);
    }
  }

  return (
    <PortalShell title="Personnel" subtitle="Field staff working at this park."
      actions={<button className="portal-btn portal-btn-gold" onClick={openInvite}><UserPlus size={13} /> Invite personnel</button>}>
      {isLoading && <div className="portal-card p-6 text-sm text-[var(--p-ink-soft)]">Loading…</div>}
      {!isLoading && (
        <div className="portal-card overflow-hidden">
          <table className="portal-table">
            <thead><tr><th>Name</th><th>Contact</th><th>Role</th><th>Status</th></tr></thead>
            <tbody>
              {(people ?? []).length === 0 && <tr><td colSpan={4} className="text-center text-[var(--p-ink-soft)] py-4">No personnel invited yet.</td></tr>}
              {(people ?? []).map((p) => (
                <tr key={p.user_id}>
                  <td className="font-semibold">{p.first_name} {p.last_name}</td>
                  <td className="text-[var(--p-ink-soft)]">{p.email ?? p.phone_number ?? "—"}</td>
                  <td>{(p.roles ?? []).map((r) => <span key={r.role_name} className="portal-chip mr-1">{r.role_name}</span>)}</td>
                  <td><span className="portal-chip">{p.account_status}</span></td>
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
              <h3 className="portal-display text-lg font-bold">Invite park staff</h3>
              <p className="text-[12px] text-[var(--p-ink-soft)] mt-0.5 flex items-center gap-1.5">
                <ShieldCheck size={12} /> They'll be registered as working for this park, and receive a temporary password by email.
              </p>
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
                <input className="portal-input mt-1" value={email} onChange={(e) => setEmail(e.target.value)} />
              </label>
              <label className="block">
                <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">Role</span>
                <select className="portal-input mt-1" value={roleName} onChange={(e) => setRoleName(e.target.value)}>
                  <option value="">Select a role…</option>
                  {INVITABLE_ROLES.map((r) => <option key={r.role_name} value={r.role_name}>{r.role_name}</option>)}
                </select>
              </label>
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
