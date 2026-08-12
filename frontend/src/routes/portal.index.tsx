import { createFileRoute, useNavigate } from "@tanstack/react-router";
import { Binoculars, Lock, Mail, Leaf, PawPrint, ShieldCheck, Sun, Trees } from "lucide-react";
import { useState } from "react";
import { useAuth } from "@/lib/auth";
import { ApiError } from "@/lib/api";
import { Logo } from "@/components/portal/Logo";
import { HeartbeatLogo } from "@/components/portal/HeartbeatLogo";

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
      await login({
        email,
        password: pwd,
        accountType: "uwa",
      });
      nav({ to: "/portal/dashboard" });
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Unable to sign in. Please try again.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div
      className="portal min-h-screen w-full flex flex-col relative overflow-hidden"
      style={{ background: "var(--p-olive-deep)" }}
    >
      {/* Organic Background Elements */}
      <div className="absolute top-[-10%] left-[-5%] opacity-10 blur-3xl">
        <div className="w-[500px] h-[500px] rounded-full bg-[var(--p-gold)]" />
      </div>
      <div className="absolute bottom-[-15%] right-[-5%] opacity-5 blur-2xl">
        <div className="w-[600px] h-[600px] rounded-full bg-white" />
      </div>

      {/* Floating Leaves */}
      <Leaf
        className="absolute top-20 left-[15%] text-white/5 -rotate-12 animate-bounce duration-[5s]"
        size={120}
      />
      <Trees className="absolute bottom-10 right-[10%] text-white/5 rotate-6" size={240} />
      <Leaf className="absolute top-1/2 right-[5%] text-white/5 rotate-45" size={80} />

      {/* Top bar */}
      <header className="relative z-20 flex items-center justify-between px-6 lg:px-10 py-6">
        <div className="flex items-center gap-2">
          <div className="h-9 w-9 rounded-lg bg-white/10 grid place-items-center">
            <Logo size={20} />
          </div>
          <span className="portal-display text-white font-black tracking-tight text-lg">
            WildWatch
          </span>
        </div>
        <span className="inline-flex items-center gap-1.5 text-[11px] font-black uppercase tracking-widest text-white/60 border border-white/15 rounded-full px-4 py-2">
          <ShieldCheck size={13} />
          Authorized Personnel Only
        </span>
      </header>

      {/* Main card */}
      <main className="relative z-10 flex-1 flex items-center justify-center p-4 lg:p-8">
        <div className="w-full max-w-5xl bg-[var(--p-paper)] rounded-[40px] shadow-2xl overflow-hidden grid lg:grid-cols-2 min-h-[560px] border border-white/10">
          {/* Left Side: Auth Form */}
          <div className="p-12 lg:p-16 flex flex-col justify-center">
            <div className="space-y-2 mb-10">
              <h1 className="portal-display text-3xl font-black text-[var(--p-ink)] tracking-tight leading-tight">
                Operational Access
              </h1>
              <p className="text-[var(--p-ink-soft)] font-medium text-[15px]">
                Secure authentication for national park administrators.
              </p>
            </div>

            <div className="space-y-5">
              <label className="block group">
                <span className="text-[11px] font-black uppercase tracking-[0.2em] text-[var(--p-ink-soft)] group-focus-within:text-[var(--p-olive-deep)] transition-colors">
                  Email
                </span>
                <div className="mt-2 relative">
                  <Mail
                    size={18}
                    className="absolute left-4 top-1/2 -translate-y-1/2 text-[var(--p-olive-line)] pointer-events-none group-focus-within:text-[var(--p-olive-deep)] transition-colors"
                  />
                  <input
                    autoFocus
                    className="w-full bg-[var(--p-olive-soft)] border border-[var(--p-olive-line)] rounded-2xl pl-12 pr-4 h-14 text-[15px] font-bold text-[var(--p-ink)] placeholder:text-[var(--p-ink-soft)]/50 focus:bg-[var(--p-paper)] focus:border-[var(--p-olive-deep)] focus:ring-4 focus:ring-[var(--p-olive)]/10 outline-none transition-all"
                    placeholder="name@wildwatch.app"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                  />
                </div>
              </label>

              <label className="block group">
                <span className="text-[11px] font-black uppercase tracking-[0.2em] text-[var(--p-ink-soft)] group-focus-within:text-[var(--p-olive-deep)] transition-colors">
                  Password
                </span>
                <div className="mt-2 relative">
                  <Lock
                    size={18}
                    className="absolute left-4 top-1/2 -translate-y-1/2 text-[var(--p-olive-line)] pointer-events-none group-focus-within:text-[var(--p-olive-deep)] transition-colors"
                  />
                  <input
                    type="password"
                    className="w-full bg-[var(--p-olive-soft)] border border-[var(--p-olive-line)] rounded-2xl pl-12 pr-4 h-14 text-[15px] font-bold text-[var(--p-ink)] placeholder:text-[var(--p-ink-soft)]/50 focus:bg-[var(--p-paper)] focus:border-[var(--p-olive-deep)] focus:ring-4 focus:ring-[var(--p-olive)]/10 outline-none transition-all"
                    placeholder="••••••••"
                    value={pwd}
                    onChange={(e) => setPwd(e.target.value)}
                  />
                </div>
              </label>

              {error && (
                <div className="text-[13px] font-bold text-[var(--p-danger)] bg-[color-mix(in_oklch,var(--p-danger)_8%,white)] border border-[color-mix(in_oklch,var(--p-danger)_20%,white)] rounded-2xl px-5 py-3 animate-in shake-in duration-300">
                  {error}
                </div>
              )}

              <button
                onClick={handleSignIn}
                disabled={submitting}
                className="w-full bg-[var(--p-olive-deep)] hover:bg-[var(--p-olive)] text-white rounded-2xl h-14 font-black uppercase tracking-widest text-[13px] shadow-lg shadow-[var(--p-olive-deep)]/20 transition-all active:scale-[0.98] disabled:opacity-70 mt-4 flex items-center justify-center"
              >
                {submitting ? <HeartbeatLogo label="Authorizing" /> : "Sign In"}
              </button>
            </div>

            <p className="mt-12 text-[11px] text-[var(--p-ink-soft)] font-bold uppercase tracking-widest text-center leading-relaxed">
              Encrypted End-to-End · HQ Mission Control
            </p>
          </div>

          {/* Right Side: Nature Illustration */}
          <div className="hidden lg:flex bg-[var(--p-olive-soft)] relative items-center justify-center p-12 overflow-hidden border-l border-[var(--p-olive-line)]">
            {/* Stylized Sun */}
            <div className="absolute top-[15%] right-[15%] w-24 h-24 rounded-full bg-[var(--p-gold-deep)] shadow-[0_0_60px_color-mix(in_oklch,var(--p-gold-deep)_35%,transparent)] flex items-center justify-center text-white/20">
              <Sun size={48} className="animate-spin-slow" />
            </div>

            {/* Mountain Silhouettes (SVG) */}
            <svg
              className="absolute bottom-0 w-full"
              viewBox="0 0 500 200"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path d="M0 200L150 50L300 200H0Z" fill="var(--p-olive-deep)" fillOpacity="0.08" />
              <path
                d="M100 200L250 80L400 200H100Z"
                fill="var(--p-olive-deep)"
                fillOpacity="0.05"
              />
              <path
                d="M250 200L400 110L500 200H250Z"
                fill="var(--p-olive-deep)"
                fillOpacity="0.1"
              />
            </svg>

            {/* Central Illustration: Ranger emblem */}
            <div className="relative z-20 flex flex-col items-center text-center max-w-xs">
              <div className="w-48 h-48 rounded-full bg-[var(--p-paper)] shadow-xl flex items-center justify-center mb-8 relative">
                <div className="absolute inset-2 border-2 border-dashed border-[var(--p-gold)]/40 rounded-full animate-spin-slow" />
                <Trees size={72} className="text-[var(--p-olive-deep)] opacity-80" />
                <Binoculars
                  size={30}
                  className="absolute -bottom-1 -right-1 text-[var(--p-gold-deep)] bg-[var(--p-paper)] rounded-full p-1.5 shadow-md"
                />
                <PawPrint
                  size={22}
                  className="absolute bottom-2 -left-1 text-[var(--p-olive)] bg-[var(--p-paper)] rounded-full p-1 shadow-md rotate-[-20deg]"
                />
              </div>
              <h3 className="portal-display text-xl font-black text-[var(--p-olive-deep)] tracking-tight mb-3">
                Guardian Analytics
              </h3>
              <p className="text-[13px] text-[var(--p-ink-soft)] leading-relaxed font-medium">
                Overseeing 10 National Parks. <br />
                Monitoring real-time wildlife movement and community safety signals.
              </p>
            </div>

            {/* Data Floating Elements */}
            <div className="absolute z-30 top-1/4 left-[6%] portal-card p-3 shadow-xl animate-float">
              <div className="flex items-center gap-2">
                <div className="w-2 h-2 rounded-full bg-[var(--p-success)]" />
                <span className="text-[10px] font-black uppercase tracking-widest text-[var(--p-olive-deep)]">
                  Active Patrol
                </span>
              </div>
            </div>
            <div
              className="absolute z-30 bottom-[8%] right-[6%] portal-card p-3 shadow-xl animate-float"
              style={{ animationDelay: "1.5s" }}
            >
              <div className="flex items-center gap-2">
                <div className="w-2 h-2 rounded-full bg-[var(--p-danger)]" />
                <span className="text-[10px] font-black uppercase tracking-widest text-[var(--p-olive-deep)]">
                  Signal SOS-74
                </span>
              </div>
            </div>
            <div
              className="absolute z-30 top-[8%] left-[8%] portal-card p-3 shadow-xl animate-float"
              style={{ animationDelay: "0.7s" }}
            >
              <div className="flex items-center gap-2">
                <Leaf size={12} className="text-[var(--p-olive)]" />
                <span className="text-[10px] font-black uppercase tracking-widest text-[var(--p-olive-deep)]">
                  New Sighting
                </span>
              </div>
            </div>
          </div>
        </div>
      </main>

      {/* Footer */}
      <footer className="relative z-20 flex flex-col-reverse sm:flex-row items-center justify-between gap-4 px-6 lg:px-10 py-6 text-[11px] font-bold uppercase tracking-widest text-white/40">
        <span>
          &copy; {new Date().getUTCFullYear()} WildWatch &middot; Uganda Wildlife Authority
        </span>
        <div className="flex items-center gap-6">
          <span className="text-white/25">Privacy</span>
          <span className="text-white/25">Terms</span>
          <span className="text-white/25">Help</span>
          <span className="flex items-center gap-1.5 ml-2" aria-hidden="true">
            <span className="w-1.5 h-1.5 rounded-full bg-white/20" />
            <span className="w-1.5 h-1.5 rounded-full bg-white/20" />
            <span className="w-1.5 h-1.5 rounded-full bg-[var(--p-gold)]" />
          </span>
        </div>
      </footer>

      <style>{`
        @keyframes float {
          0%, 100% { transform: translateY(0px); }
          50% { transform: translateY(-10px); }
        }
        .animate-float {
          animation: float 4s ease-in-out infinite;
        }
        @keyframes spin-slow {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
        .animate-spin-slow {
          animation: spin-slow 12s linear infinite;
        }
        @keyframes shake {
          0%, 100% { transform: translateX(0); }
          25% { transform: translateX(-4px); }
          75% { transform: translateX(4px); }
        }
        .shake-in {
          animation: shake 0.2s ease-in-out 0s 2;
        }
      `}</style>
    </div>
  );
}
