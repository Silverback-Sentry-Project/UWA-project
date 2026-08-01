import { Logo } from "@/components/portal/Logo";

/**
 * Small heartbeat-style pulsing box shown while a sign-in request is in
 * flight, replacing plain "Signing in…" text.
 */
export function HeartbeatLogo({ label = "Signing in" }: { label?: string }) {
  return (
    <div className="flex items-center justify-center gap-2 py-0.5">
      <div className="relative h-6 w-6 shrink-0 grid place-items-center">
        <span
          className="absolute inset-0 rounded-md"
          style={{ background: "var(--p-gold)", opacity: 0.35, animation: "uwa-heartbeat 1.1s ease-in-out infinite" }}
        />
        <div className="relative h-5 w-5 rounded-md bg-white overflow-hidden grid place-items-center">
          <Logo size={18} />
        </div>
      </div>
      <span className="text-[13px] font-medium">{label}…</span>
      <style>{`
        @keyframes uwa-heartbeat {
          0%, 100% { transform: scale(0.85); opacity: 0.35; }
          50% { transform: scale(1.25); opacity: 0.05; }
        }
      `}</style>
    </div>
  );
}
