import { Logo } from "@/components/portal/Logo";

/**
 * Small heartbeat-style pulsing box shown while a sign-in request is in
 * flight, replacing plain "Signing in…" text.
 */
export function HeartbeatLogo({ label = "Connecting" }: { label?: string }) {
  return (
    <div className="flex items-center justify-center gap-3">
      <div className="relative h-6 w-6 shrink-0 grid place-items-center">
        <span
          className="absolute inset-0 rounded-lg bg-[#C4A456]"
          style={{ opacity: 0.35, animation: "ww-heartbeat 1.1s ease-in-out infinite" }}
        />
        <div className="relative h-5 w-5 rounded-md bg-white overflow-hidden grid place-items-center shadow-sm">
          <Logo size={14} />
        </div>
      </div>
      <span className="text-[13px] font-black uppercase tracking-[0.1em]">{label}</span>
      <style>{`
        @keyframes ww-heartbeat {
          0%, 100% { transform: scale(0.85); opacity: 0.35; }
          50% { transform: scale(1.4); opacity: 0; }
        }
      `}</style>
    </div>
  );
}
