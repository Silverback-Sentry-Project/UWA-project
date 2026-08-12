import { createFileRoute } from "@tanstack/react-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { PortalShell, StatusBadge } from "@/components/portal/PortalShell";
import { apiFetch, ApiError } from "@/lib/api";
import type { Paginated } from "@/lib/api-types";
import { CheckCircle2, XCircle, Banknote, FileSearch, ShieldCheck } from "lucide-react";
import { useState } from "react";

export const Route = createFileRoute("/portal/claims")({ component: Claims });

interface Claim {
  claim_id: number;
  incident_id: number;
  estimated_amount: string | number | null;
  claim_status: "Submitted" | "Under Review" | "Approved" | "Rejected" | "Paid";
  created_at: string;
  reviewed_at: string | null;
  approved_at: string | null;
  claimant?: { first_name: string; last_name: string } | null;
  incident?: {
    park?: { park_name: string } | null;
    species?: { common_name: string } | null;
  } | null;
  payment?: { amount_paid: string | number; payment_method: string; payment_date: string } | null;
}

const COLUMNS = ["Submitted", "Under Review", "Approved", "Paid", "Rejected"] as const;
const STAGE_HINT: Record<string, string> = {
  Submitted: "Awaiting first review",
  "Under Review": "Cross-checking evidence",
  Approved: "Cleared for payment",
  Paid: "Disbursed to claimant",
  Rejected: "Not eligible — reason on file",
};

function fmtUGX(n: string | number | null | undefined) {
  return `UGX ${Number(n ?? 0).toLocaleString("en-UG")}`;
}
function fmtDate(iso: string | null) {
  if (!iso) return "—";
  return new Date(iso).toLocaleString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
}

function Claims() {
  const queryClient = useQueryClient();
  const [selected, setSelected] = useState<number | null>(null);
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  const [rejectReason, setRejectReason] = useState("");
  const [showReject, setShowReject] = useState(false);
  const [payAmount, setPayAmount] = useState("");
  const [payMethod, setPayMethod] = useState("Mobile Money");
  const [payRef, setPayRef] = useState("");
  const [showPay, setShowPay] = useState(false);

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ["claims"],
    queryFn: () => apiFetch<Paginated<Claim>>("/claims"),
  });

  const claims = data?.data ?? [];
  const claim = claims.find((c) => c.claim_id === selected) ?? claims[0];

  async function refresh() {
    await queryClient.invalidateQueries({ queryKey: ["claims"] });
  }

  async function approve() {
    if (!claim) return;
    setBusy(true);
    setErr(null);
    try {
      await apiFetch(`/claims/${claim.claim_id}/approve`, { method: "POST" });
      await refresh();
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Couldn't approve claim.");
    } finally {
      setBusy(false);
    }
  }

  async function reject() {
    if (!claim) return;
    setBusy(true);
    setErr(null);
    try {
      await apiFetch(`/claims/${claim.claim_id}/reject`, {
        method: "POST",
        body: JSON.stringify({ reason: rejectReason }),
      });
      setShowReject(false);
      setRejectReason("");
      await refresh();
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Couldn't reject claim.");
    } finally {
      setBusy(false);
    }
  }

  async function markPaid() {
    if (!claim || !payAmount) return;
    setBusy(true);
    setErr(null);
    try {
      await apiFetch(`/claims/${claim.claim_id}/mark-paid`, {
        method: "POST",
        body: JSON.stringify({
          amount_paid: Number(payAmount),
          payment_method: payMethod,
          transaction_reference: payRef || undefined,
        }),
      });
      setShowPay(false);
      setPayAmount("");
      setPayRef("");
      await refresh();
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Couldn't mark claim as paid.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <PortalShell
      title="Compensation Claim Management"
      subtitle="Verify, approve, and track compensation for community members affected by wildlife."
      helpText="Claims move left to right. Click a card to review evidence and take action — Approve, Reject (with reason), or Mark paid."
    >
      {isLoading && (
        <div className="portal-card p-6 text-sm text-[var(--p-ink-soft)]">Loading claims…</div>
      )}
      {isError && (
        <div className="portal-card p-6 text-sm text-[var(--p-danger)]">
          Couldn't load claims: {error instanceof Error ? error.message : "unknown error"}
        </div>
      )}

      {data && (
        <div className="grid grid-cols-12 gap-4">
          <div className="col-span-8 portal-card overflow-hidden">
            <div className="p-4 border-b border-[var(--p-olive-line)] flex items-center justify-between">
              <h3 className="portal-display text-sm font-bold">Claims pipeline</h3>
              <div className="text-[11px] text-[var(--p-ink-soft)]">{claims.length} claims</div>
            </div>
            <div className="grid grid-cols-5 gap-px bg-[var(--p-olive-line)]">
              {COLUMNS.map((col) => {
                const items = claims.filter((c) => c.claim_status === col);
                return (
                  <div key={col} className="bg-[var(--p-bg)] min-h-[480px]">
                    <div className="p-3 border-b border-[var(--p-olive-line)] bg-white">
                      <div className="flex items-center justify-between">
                        <StatusBadge status={col} />
                        <span className="text-[11px] font-bold text-[var(--p-ink-soft)]">
                          {items.length}
                        </span>
                      </div>
                      <div className="mt-1.5 text-[10px] text-[var(--p-ink-soft)] leading-snug">
                        {STAGE_HINT[col]}
                      </div>
                    </div>
                    <div className="p-2 space-y-2">
                      {items.map((c) => (
                        <button
                          key={c.claim_id}
                          onClick={() => setSelected(c.claim_id)}
                          className={`w-full text-left p-2.5 rounded-md border text-[11px] transition ${selected === c.claim_id ? "bg-white border-[var(--p-olive)] shadow-sm" : "bg-white/60 border-[var(--p-olive-line)] hover:border-[var(--p-olive)]"}`}
                        >
                          <div className="font-mono font-bold text-[var(--p-olive-deep)]">
                            CLM-{c.claim_id}
                          </div>
                          <div className="font-semibold mt-0.5 truncate">
                            {c.claimant ? `${c.claimant.first_name} ${c.claimant.last_name}` : "—"}
                          </div>
                          <div className="text-[var(--p-ink-soft)] truncate">
                            {c.incident?.species?.common_name ?? "—"}
                          </div>
                          <div className="font-semibold mt-1 text-[var(--p-ink)]">
                            {fmtUGX(c.estimated_amount)}
                          </div>
                        </button>
                      ))}
                      {items.length === 0 && (
                        <div className="text-[11px] text-[var(--p-ink-soft)] p-2">None here.</div>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>

          <div className="col-span-4 portal-card overflow-hidden">
            {!claim && (
              <div className="p-6 text-[12px] text-[var(--p-ink-soft)]">
                Select a claim to review it.
              </div>
            )}
            {claim && (
              <>
                <div className="p-4 border-b border-[var(--p-olive-line)]">
                  <div className="font-mono text-[11px] text-[var(--p-olive-deep)] font-bold">
                    CLM-{claim.claim_id}
                  </div>
                  <h3 className="portal-display text-lg font-bold mt-1">
                    {claim.claimant
                      ? `${claim.claimant.first_name} ${claim.claimant.last_name}`
                      : "—"}
                  </h3>
                  <div className="flex gap-2 mt-2">
                    <StatusBadge status={claim.claim_status} />
                    <span className="portal-chip">
                      {claim.incident?.species?.common_name ?? "—"}
                    </span>
                  </div>
                </div>
                <div className="p-4 space-y-4 text-[13px]">
                  <div className="grid grid-cols-2 gap-3">
                    <Info label="Amount claimed" value={fmtUGX(claim.estimated_amount)} />
                    <Info label="Park" value={claim.incident?.park?.park_name ?? "—"} />
                    <Info label="Linked incident" value={`WW-${claim.incident_id}`} />
                    <Info label="Submitted" value={fmtDate(claim.created_at)} />
                    <Info label="Reviewed" value={fmtDate(claim.reviewed_at)} />
                    <Info label="Approved" value={fmtDate(claim.approved_at)} />
                  </div>

                  {claim.payment && (
                    <div className="portal-card bg-[var(--p-olive-soft)] p-3">
                      <div className="text-[11px] uppercase tracking-wider font-semibold text-[var(--p-ink-soft)] flex items-center gap-1.5">
                        <FileSearch size={11} /> Payment
                      </div>
                      <div className="text-[12px] mt-1">
                        {fmtUGX(claim.payment.amount_paid)} via {claim.payment.payment_method} on{" "}
                        {fmtDate(claim.payment.payment_date)}.
                      </div>
                    </div>
                  )}

                  {err && (
                    <div className="text-[12px] text-[var(--p-danger)] bg-[var(--p-danger)]/10 border border-[var(--p-danger)]/30 rounded-md px-3 py-2">
                      {err}
                    </div>
                  )}

                  {showReject && (
                    <div className="portal-card p-3 space-y-2">
                      <label className="block text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">
                        Reason for rejection
                      </label>
                      <textarea
                        className="portal-input w-full"
                        rows={2}
                        value={rejectReason}
                        onChange={(e) => setRejectReason(e.target.value)}
                      />
                      <div className="flex gap-2">
                        <button
                          className="portal-btn flex-1 justify-center"
                          disabled={busy}
                          onClick={reject}
                        >
                          Confirm reject
                        </button>
                        <button
                          className="portal-btn portal-btn-ghost flex-1 justify-center"
                          onClick={() => setShowReject(false)}
                        >
                          Cancel
                        </button>
                      </div>
                    </div>
                  )}

                  {showPay && (
                    <div className="portal-card p-3 space-y-2">
                      <label className="block text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">
                        Amount paid (UGX)
                      </label>
                      <input
                        className="portal-input w-full"
                        type="number"
                        value={payAmount}
                        onChange={(e) => setPayAmount(e.target.value)}
                      />
                      <label className="block text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">
                        Method
                      </label>
                      <select
                        className="portal-input w-full"
                        value={payMethod}
                        onChange={(e) => setPayMethod(e.target.value)}
                      >
                        <option>Bank Transfer</option>
                        <option>Mobile Money</option>
                        <option>Cheque</option>
                        <option>Cash</option>
                      </select>
                      <label className="block text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">
                        Transaction reference (optional)
                      </label>
                      <input
                        className="portal-input w-full"
                        value={payRef}
                        onChange={(e) => setPayRef(e.target.value)}
                      />
                      <div className="flex gap-2">
                        <button
                          className="portal-btn flex-1 justify-center"
                          disabled={busy || !payAmount}
                          onClick={markPaid}
                        >
                          Confirm payment
                        </button>
                        <button
                          className="portal-btn portal-btn-ghost flex-1 justify-center"
                          onClick={() => setShowPay(false)}
                        >
                          Cancel
                        </button>
                      </div>
                    </div>
                  )}

                  {!showReject && !showPay && (
                    <div className="grid grid-cols-3 gap-2 pt-2">
                      <button
                        className="portal-btn justify-center"
                        disabled={
                          busy ||
                          (claim.claim_status !== "Submitted" &&
                            claim.claim_status !== "Under Review")
                        }
                        onClick={approve}
                      >
                        <CheckCircle2 size={13} /> Approve
                      </button>
                      <button
                        className="portal-btn portal-btn-ghost justify-center"
                        style={{ color: "var(--p-danger)" }}
                        disabled={busy}
                        onClick={() => setShowReject(true)}
                      >
                        <XCircle size={13} /> Reject
                      </button>
                      <button
                        className="portal-btn portal-btn-gold justify-center"
                        disabled={busy || claim.claim_status !== "Approved"}
                        onClick={() => setShowPay(true)}
                      >
                        <Banknote size={13} /> Mark paid
                      </button>
                    </div>
                  )}
                  <div className="text-[11px] text-[var(--p-ink-soft)] italic flex items-center gap-1">
                    <ShieldCheck size={11} /> Action will be audit-logged with your signature.
                  </div>
                </div>
              </>
            )}
          </div>
        </div>
      )}
    </PortalShell>
  );
}

function Info({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <div className="text-[10px] uppercase tracking-wider text-[var(--p-ink-soft)] font-semibold">
        {label}
      </div>
      <div className="mt-0.5 font-medium">{value}</div>
    </div>
  );
}
