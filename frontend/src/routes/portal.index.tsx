import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { Lock, Mail, ShieldCheck, TreePine, Landmark } from "lucide-react";
import { useEffect, useState } from "react";
import { useAuth } from "@/lib/auth";
import { ApiError, apiFetch } from "@/lib/api";
import { Logo } from "@/components/portal/Logo";
import { HeartbeatLogo } from "@/components/portal/HeartbeatLogo";

export const Route = createFileRoute("/portal/")({ component: PortalLogin });

interface PublicPark {
  park_id: string;
  park_name: string;
}

function PortalLogin() {
  const nav = useNavigate();
  const { login } = useAuth();
  const [accountType, setAccountType] = useState<"uwa" | "gamepark">("uwa");
  const [email, setEmail] = useState("");
  const [parkId, setParkId] = useState("");
  const [parks, setParks] = useState<PublicPark[]>([]);
  const [pwd, setPwd] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (accountType === "gamepark" && parks.length === 0) {
      apiFetch<PublicPark[]>("/public/parks")
        .then(setParks)
        .catch(() => setParks([]));
    }
  }, [accountType, parks.length]);

  async function handleSignIn() {
    setError(null);
    if (accountType === "gamepark" && !parkId) {
      setError("Please select a park.");
      return;
    }
    setSubmitting(true);
    try {
      await login({
        email: accountType === "uwa" ? email : undefined,
        password: pwd,
        accountType,
        parkId: accountType === "gamepark" ? parkId : undefined,
      });
      nav({ to: accountType === "gamepark" ? "/portal/dashboard" : "/portal/dashboard" });
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Unable to sign in. Please try again.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="portal min-h-screen w-full grid lg:grid-cols-2">
      <div className="hidden lg:flex flex-col justify-between p-12 text-white relative overflow-hidden" style={{ background: "linear-gradient(160deg, var(--p-olive-deep), var(--p-olive))" }}>
        <div className="absolute -bottom-32 -right-32 h-96 w-96 rounded-full" style={{ background: "var(--p-gold)", opacity: 0.15, filter: "blur(40px)" }} />
        <div className="flex items-center gap-3">
          <div className="h-11 w-11 rounded-lg bg-white overflow-hidden grid place-items-center shrink-0"><Logo size={44} /></div>
          <div>
            <div className="portal-display text-lg font-bold leading-tight">Uganda Wildlife Authority</div>
            <div className="text-xs uppercase tracking-widest text-white/60">Wildwatch Administrative Portal</div>
          </div>
        </div>
        <div className="relative">
          <h1 className="portal-display text-4xl font-bold leading-tight max-w-md">Secure command centre for wildlife protection across Uganda's national parks.</h1>
          <p className="text-white/70 mt-4 text-sm max-w-md leading-relaxed">Monitor incidents reported by community members and rangers, assign personnel, generate hotspot intelligence, and manage compensation in one auditable system.</p>
        </div>
      </div>
      <div className="flex items-center justify-center p-8 bg-[var(--p-bg)]">
        <div className="w-full max-w-sm">
          <div className="portal-chip mb-4"><ShieldCheck size={12} /> Authorized personnel only</div>

          <div className="grid grid-cols-2 gap-1.5 p-1 rounded-lg bg-[var(--p-olive-soft)] border border-[var(--p-olive-line)]">
            <button
              type="button"
              onClick={() => { setAccountType("uwa"); setError(null); }}
              className={`flex items-center justify-center gap-1.5 rounded-md py-2 text-[13px] font-semibold transition ${accountType === "uwa" ? "bg-white shadow-sm text-[var(--p-olive-deep)]" : "text-[var(--p-ink-soft)]"}`}
            >
              <Landmark size={14} /> UWA Portal
            </button>
            <button
              type="button"
              onClick={() => { setAccountType("gamepark"); setError(null); }}
              className={`flex items-center justify-center gap-1.5 rounded-md py-2 text-[13px] font-semibold transition ${accountType === "gamepark" ? "bg-white shadow-sm text-[var(--p-olive-deep)]" : "text-[var(--p-ink-soft)]"}`}
            >
              <TreePine size={14} /> Sign in as Gamepark
            </button>
          </div>

          <h2 className="portal-display text-2xl font-bold mt-4">
            {accountType === "uwa" ? "Sign in to UWA Portal" : "Sign in to Gamepark Portal"}
          </h2>
          <p className="text-[13px] text-[var(--p-ink-soft)] mt-1">
            {accountType === "uwa"
              ? "Use your UWA-issued credentials. All sign-ins are logged."
              : "Select your park and enter its password. All sign-ins are logged."}
          </p>

          <div className="mt-6 space-y-3">
            {accountType === "uwa" ? (
              <label className="block">
                <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">Official email</span>
                <div className="mt-1 relative">
                  <Mail size={14} className="absolute left-2.5 top-1/2 -translate-y-1/2 text-[var(--p-ink-soft)]" />
                  <input className="portal-input pl-8" placeholder="name@uwa.go.ug" value={email} onChange={(e) => setEmail(e.target.value)} />
                </div>
              </label>
            ) : (
              <label className="block">
                <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">Park</span>
                <div className="mt-1 relative">
                  <TreePine size={14} className="absolute left-2.5 top-1/2 -translate-y-1/2 text-[var(--p-ink-soft)] z-10" />
                  <select
                    className="portal-input pl-8"
                    value={parkId}
                    onChange={(e) => setParkId(e.target.value)}
                  >
                    <option value="">Select a park…</option>
                    {parks.map((p) => (
                      <option key={p.park_id} value={p.park_id}>{p.park_name}</option>
                    ))}
                  </select>
                </div>
              </label>
            )}
            <label className="block">
              <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">Password</span>
              <div className="mt-1 relative">
                <Lock size={14} className="absolute left-2.5 top-1/2 -translate-y-1/2 text-[var(--p-ink-soft)]" />
                <input type="password" className="portal-input pl-8" placeholder="••••••••" value={pwd} onChange={(e) => setPwd(e.target.value)} />
              </div>
            </label>
            {error && (
              <div className="text-[12px] text-[var(--p-danger)] bg-[var(--p-danger)]/10 border border-[var(--p-danger)]/30 rounded-md px-3 py-2">
                {error}
              </div>
            )}
            <button onClick={handleSignIn} disabled={submitting} className="portal-btn w-full justify-center py-3 disabled:opacity-60">
              {submitting ? <HeartbeatLogo label="Signing in" /> : "Sign in securely"}
            </button>
            <div className="text-center text-[12px] text-[var(--p-ink-soft)]"><a className="font-semibold text-[var(--p-olive-deep)] hover:underline">Need access? Contact IT Security</a></div>
          </div>
          <div className="mt-8 pt-4 border-t border-[var(--p-olive-line)] text-[11px] text-[var(--p-ink-soft)] leading-relaxed">
            This system is restricted to authorized Uganda Wildlife Authority personnel. Unauthorized access is prohibited and prosecuted under the Uganda Wildlife Act. Sessions are encrypted and audit-logged.
          </div>
          <div className="mt-4 text-center">
            <Link to="/" className="text-[11px] text-[var(--p-ink-soft)] hover:text-[var(--p-olive-deep)]">← Back to mobile app preview</Link>
          </div>
        </div>
      </div>
    </div>
  );
}
