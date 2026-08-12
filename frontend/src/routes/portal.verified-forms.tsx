import { createFileRoute } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { PortalShell } from "@/components/portal/PortalShell";
import { apiFetch } from "@/lib/api";
import { FileText, User } from "lucide-react";
import { useState } from "react";

export const Route = createFileRoute("/portal/verified-forms")({ component: VerifiedForms });

interface Answer {
  answer_id: number;
  value: string | null;
  image_path: string | null;
  field: { field_id: number; label: string; field_type: string };
}

interface ForwardedSubmission {
  submission_id: number;
  submitted_by_name: string | null;
  submitted_by_contact: string | null;
  forwarded_at: string;
  form: { title: string; description: string | null };
  park: { park_name: string };
  verifier: { full_name: string } | null;
  answers: Answer[];
}

function fmtDate(iso: string) {
  return new Date(iso).toLocaleString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function VerifiedForms() {
  const [openId, setOpenId] = useState<number | null>(null);

  const { data: submissions, isLoading } = useQuery({
    queryKey: ["forwarded-forms"],
    queryFn: () => apiFetch<ForwardedSubmission[]>("/forwarded-forms"),
  });

  const open = (submissions ?? []).find((s) => s.submission_id === openId) ?? null;

  return (
    <PortalShell
      title="Verified Forms"
      subtitle="Evidence forms verified by gameparks and forwarded here for further action."
    >
      {isLoading && (
        <div className="portal-card p-6 text-sm text-[var(--p-ink-soft)]">Loading…</div>
      )}
      {!isLoading && (submissions ?? []).length === 0 && (
        <div className="portal-card p-8 text-center text-[13px] text-[var(--p-ink-soft)]">
          <FileText className="mx-auto mb-2 text-[var(--p-olive)]" size={28} />
          Nothing forwarded from a gamepark yet.
        </div>
      )}

      <div className="grid grid-cols-12 gap-4">
        <div className="col-span-5 space-y-2">
          {(submissions ?? []).map((s) => (
            <button
              key={s.submission_id}
              onClick={() => setOpenId(s.submission_id)}
              className={`w-full text-left portal-card p-3 transition ${openId === s.submission_id ? "border-[var(--p-olive)]" : ""}`}
            >
              <div className="font-semibold text-[13px]">{s.form.title}</div>
              <div className="text-[11px] text-[var(--p-ink-soft)] mt-1">{s.park.park_name}</div>
              <div className="mt-1 flex items-center gap-1.5 text-[11px] text-[var(--p-ink-soft)]">
                <User size={11} /> {s.submitted_by_name ?? "Anonymous"}
              </div>
              <div className="mt-1 text-[10px] text-[var(--p-ink-soft)]">
                Forwarded {fmtDate(s.forwarded_at)}
              </div>
            </button>
          ))}
        </div>
        <div className="col-span-7">
          {!open && (
            <div className="portal-card p-8 text-center text-[13px] text-[var(--p-ink-soft)]">
              Select a forwarded form to view details.
            </div>
          )}
          {open && (
            <div className="portal-card p-4">
              <h3 className="portal-display text-sm font-bold">{open.form.title}</h3>
              <div className="text-[11px] text-[var(--p-ink-soft)] mt-1">
                {open.park.park_name} · Verified by {open.verifier?.full_name ?? "—"} · Forwarded{" "}
                {fmtDate(open.forwarded_at)}
              </div>
              <div className="mt-3 space-y-2">
                {open.answers.map((a) => (
                  <div
                    key={a.answer_id}
                    className="border border-[var(--p-olive-line)] rounded-md p-2.5"
                  >
                    <div className="text-[10px] uppercase tracking-wider text-[var(--p-ink-soft)] font-semibold">
                      {a.field.label}
                    </div>
                    {a.field.field_type === "image" ? (
                      a.image_path ? (
                        <img
                          src={a.image_path}
                          alt={a.field.label}
                          className="mt-1 max-h-40 rounded-md"
                        />
                      ) : (
                        <div className="text-[12px] text-[var(--p-ink-soft)] mt-0.5">
                          No photo attached.
                        </div>
                      )
                    ) : (
                      <div className="text-[13px] mt-0.5">{a.value || "—"}</div>
                    )}
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    </PortalShell>
  );
}
