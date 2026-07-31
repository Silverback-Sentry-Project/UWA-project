import { ReactNode } from "react";

export function PhoneFrame({ children }: { children: ReactNode }) {
  return (
    <div className="min-h-screen w-full flex items-center justify-center gradient-sky py-10 px-4">
      <div className="hidden md:flex flex-col gap-3 mr-12 max-w-xs">
        <div className="text-sm font-semibold text-foreground/70 uppercase tracking-widest">Wildwatch</div>
        <h1 className="text-4xl font-extrabold text-foreground" style={{ fontFamily: "'Plus Jakarta Sans', sans-serif" }}>
          Wildlife Conservation Community Reporting
        </h1>
        <p className="text-foreground/70 text-sm leading-relaxed">
          High-fidelity prototype for Community Members and Rangers. UWA officials use the secure web portal.
        </p>
      </div>
      <div className="phone-frame">
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-7 bg-black rounded-b-2xl z-50" />
        <div className="h-full w-full overflow-y-auto scrollbar-hide bg-background">
          {children}
        </div>
      </div>
    </div>
  );
}

export function StatusBar({ dark = false }: { dark?: boolean }) {
  const color = dark ? "text-white" : "text-foreground";
  return (
    <div className={`flex items-center justify-between px-8 pt-3 pb-1 text-xs font-semibold ${color}`}>
      <span>9:41</span>
      <span className="flex items-center gap-1">
        <span>●●●●</span>
        <span>5G</span>
        <span>▮</span>
      </span>
    </div>
  );
}