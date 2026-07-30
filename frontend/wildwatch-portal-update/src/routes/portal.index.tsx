import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { Lock, Mail, ShieldCheck } from "lucide-react";
import { useState } from "react";
import { useAuth } from "@/lib/auth";
import { ApiError } from "@/lib/api";
import { Logo } from "@/components/portal/Logo";

export const Route = createFileRoute("/portal/")({ component: PortalLogin });

function PortalLogin() {
  const nav = useNavigate();
  const { login } = useAuth();
  const [email, setEmail] = useState("");
  const [pwd, setPwd] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function handleSignIn() {
    setError(null);
    setSubmitting(true);
    try {
      await login(email, pwd);
      nav({ to: "/portal/dashboard" });
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Unable to sign in. Please try again.");
    } finally {
      setSubmitting(false);
    }
  }

  const useDemo = () => { setEmail("admin@uwa.go.ug"); setPwd("Password123!"); };

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
        <div className="relative grid grid-cols-3 gap-4 text-[11px] text-white/60">
          <div><div className="text-[var(--p-gold)] font-bold text-base">5</div>National parks</div>
          <div><div className="text-[var(--p-gold)] font-bold text-base">128</div>Active rangers</div>
          <div><div className="text-[var(--p-gold)] font-bold text-base">2,431</div>Reports YTD</div>
        </div>
      </div>
      <div className="flex items-center justify-center p-8 bg-[var(--p-bg)]">
        <div className="w-full max-w-sm">
          <div className="portal-chip mb-4"><ShieldCheck size={12} /> Authorized personnel only</div>
          <h2 className="portal-display text-2xl font-bold">Sign in to UWA Portal</h2>
          <p className="text-[13px] text-[var(--p-ink-soft)] mt-1">Use your UWA-issued credentials. All sign-ins are logged.</p>
          <button onClick={useDemo} className="mt-3 w-full text-left text-[12px] bg-[var(--p-olive-soft)] border border-[var(--p-olive-line)] rounded-md px-3 py-2 hover:border-[var(--p-olive)] transition">
            <span className="font-semibold text-[var(--p-olive-deep)]">Try the demo →</span>
            <span className="text-[var(--p-ink-soft)]"> auto-fill credentials to explore the portal</span>
          </button>
          <div className="mt-6 space-y-3">
            <label className="block">
              <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">Official email</span>
              <div className="mt-1 relative">
                <Mail size={14} className="absolute left-2.5 top-1/2 -translate-y-1/2 text-[var(--p-ink-soft)]" />
                <input className="portal-input pl-8" placeholder="name@uwa.go.ug" value={email} onChange={(e) => setEmail(e.target.value)} />
              </div>
            </label>
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
              {submitting ? "Signing in…" : "Sign in securely"}
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