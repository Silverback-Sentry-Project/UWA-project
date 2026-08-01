import { createFileRoute } from "@tanstack/react-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { PortalShell, StatusBadge } from "@/components/portal/PortalShell";
import { apiFetch, ApiError } from "@/lib/api";
import { Inbox, CheckCircle2, XCircle, Send, User } from "lucide-react";
import { useState } from "react";

export const Route = createFileRoute("/portal/submissions")({ component: Submissions });

interface Answer {
  answer_id: number;
  value: string | null;
  image_path: string | null;
  field: { field_id: number; label: string; field_type: string };
}

interface Submission {
  submission_id: number;
  status: "Submitted" | "Verified" | "Forwarded" | "Rejected";
  submitted_by_name: string | null;
  submitted_by_contact: string | null;
  verification_notes: string | null;
  created_at: string;
  form: { title: string };
  answers: Answer[];
}

const TABS: { key: Submission["status"] | "All"; label: string }[] = [
  { key: "Submitted", label: "Awaiting review" },
  { key: "Verified", label: "Verified" },
  { key: "Forwarded", label: "Forwarded to UWA" },
  { key: "Rejected", label: "Rejected" },
  { key: "All", label: "All" },
];

function fmtDate(iso: string) {
  return new Date(iso).toLocaleString("en-GB", { day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit" });
}

function Submissions() {
  const queryClient = useQueryClient();
  const [tab, setTab] = useState<Submission["status"] | "All">("Submitted");
  const [openId, setOpenId] = useState<number | null>(null);
  const [notes, setNotes] = useState("");
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState<string | null>(null);

  const { data: submissions, isLoading } = useQuery({
    queryKey: ["gamepark-submissions"],
    queryFn: () => apiFetch<Submission[]>("/gamepark/submissions"),
  });

  const filtered = (submissions ?? []).filter((s) => tab === "All" || s.status === tab);
  const open = (submissions ?? []).find((s) => s.submission_id === openId) ?? null;

  async function decide(id: number, decision: "verify" | "reject") {
    setBusy(true);
    setErr(null);
    try {
      await apiFetch(`/gamepark/submissions/${id}/verify`, { method: "POST", body: JSON.stringify({ decision, notes: notes || undefined }) });
      await queryClient.invalidateQueries({ queryKey: ["gamepark-submissions"] });
      setNotes("");
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Couldn't record that decision.");
    } finally {
      setBusy(false);
    }
  }

  async function forward(id: number) {
    setBusy(true);
    setErr(null);
    try {
      await apiFetch(`/gamepark/submissions/${id}/forward`, { method: "POST" });
      await queryClient.invalidateQueries({ queryKey: ["gamepark-submissions"] });
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Couldn't forward this submission.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <PortalShell title="Submissions" subtitle="Review, verify, and forward filled-in forms to the UWA portal.">
      <div className="flex items-center gap-1.5 mb-4">
        {TABS.map((t) => (
          <button
            key={t.key}
            onClick={() => setTab(t.key)}
            className={`text-[12px] font-semibold px-3 py-1.5 rounded-md transition ${tab === t.key ? "bg-[var(--p-olive-deep)] text-white" : "bg-[var(--p-olive-soft)] text-[var(--p-ink-soft)] hover:text-[var(--p-olive-deep)]"}`}
          >
            {t.label}
          </button>
        ))}
      </div>

      {isLoading && <div className="portal-card p-6 text-sm text-[var(--p-ink-soft)]">Loading submissions…</div>}
      {!isLoading && filtered.length === 0 && (
        <div className="portal-card p-8 text-center text-[13px] text-[var(--p-ink-soft)]">
          <Inbox className="mx-auto mb-2 text-[var(--p-olive)]" size={28} />
          Nothing here yet.
        </div>
      )}

      <div className="grid grid-cols-12 gap-4">
        <div className="col-span-5 space-y-2">
          {filtered.map((s) => (
            <button
              key={s.submission_id}
              onClick={() => { setOpenId(s.submission_id); setNotes(""); setErr(null); }}
              className={`w-full text-left portal-card p-3 transition ${openId === s.submission_id ? "border-[var(--p-olive)]" : ""}`}
            >
              <div className="flex items-center justify-between">
                <div className="font-semibold text-[13px]">{s.form.title}</div>
                <StatusBadge status={s.status} />
              </div>
              <div className="mt-1 flex items-center gap-1.5 text-[11px] text-[var(--p-ink-soft)]">
                <User size={11} /> {s.submitted_by_name ?? "Anonymous"}
              </div>
              <div className="mt-1 text-[10px] text-[var(--p-ink-soft)]">{fmtDate(s.created_at)}</div>
            </button>
          ))}
        </div>

        <div className="col-span-7">
          {!open && <div className="portal-card p-8 text-center text-[13px] text-[var(--p-ink-soft)]">Select a submission to review its answers.</div>}
          {open && (
            <div className="portal-card p-4">
              <div className="flex items-center justify-between">
                <h3 className="portal-display text-sm font-bold">{open.form.title}</h3>
                <StatusBadge status={open.status} />
              </div>
              <div className="mt-1 text-[11px] text-[var(--p-ink-soft)]">
                Submitted by {open.submitted_by_name ?? "Anonymous"} {open.submitted_by_contact ? `· ${open.submitted_by_contact}` : ""} · {fmtDate(open.created_at)}
              </div>

              <div className="mt-3 space-y-2">
                {open.answers.map((a) => (
                  <div key={a.answer_id} className="border border-[var(--p-olive-line)] rounded-md p-2.5">
                    <div className="text-[10px] uppercase tracking-wider text-[var(--p-ink-soft)] font-semibold">{a.field.label}</div>
                    {a.field.field_type === "image" ? (
                      a.image_path ? (
                        <img src={a.image_path} alt={a.field.label} className="mt-1 max-h-40 rounded-md" />
                      ) : (
                        <div className="text-[12px] text-[var(--p-ink-soft)] mt-0.5">No photo attached.</div>
                      )
                    ) : (
                      <div className="text-[13px] mt-0.5">{a.value || "—"}</div>
                    )}
                  </div>
                ))}
              </div>

              {open.verification_notes && (
                <div className="mt-3 text-[12px] text-[var(--p-ink-soft)] bg-[var(--p-olive-soft)] rounded-md p-2.5">
                  <span className="font-semibold">Review notes: </span>{open.verification_notes}
                </div>
              )}

              {open.status === "Submitted" && (
                <div className="mt-4 space-y-2">
                  <textarea
                    className="portal-input text-[12px]"
                    rows={2}
                    placeholder="Optional review notes…"
                    value={notes}
                    onChange={(e) => setNotes(e.target.value)}
                  />
                  <div className="flex items-center gap-2">
                    <button className="portal-btn flex-1 justify-center" disabled={busy} onClick={() => decide(open.submission_id, "verify")}><CheckCircle2 size={13} /> Verify</button>
                    <button className="portal-btn portal-btn-ghost flex-1 justify-center text-[var(--p-danger)]" disabled={busy} onClick={() => decide(open.submission_id, "reject")}><XCircle size={13} /> Reject</button>
                  </div>
                </div>
              )}

              {open.status === "Verified" && (
                <button className="portal-btn portal-btn-gold w-full justify-center mt-4" disabled={busy} onClick={() => forward(open.submission_id)}>
                  <Send size={13} /> Forward to UWA portal
                </button>
              )}

              {open.status === "Forwarded" && (
                <div className="mt-4 text-[12px] text-[var(--p-olive-deep)] bg-[var(--p-olive-soft)] rounded-md px-3 py-2">
                  Forwarded to the UWA portal for further action.
                </div>
              )}

              {err && <div className="mt-3 text-[12px] text-[var(--p-danger)] bg-[var(--p-danger)]/10 border border-[var(--p-danger)]/30 rounded-md px-3 py-2">{err}</div>}
            </div>
          )}
        </div>
      </div>
    </PortalShell>
  );
}
