import { createFileRoute } from "@tanstack/react-router";
import { PhoneFrame } from "@/components/PhoneFrame";
import { CommunityTabBar, ScreenHeader } from "@/components/ui-prototype";
import { AlertOctagon, Phone, Shield, MapPin } from "lucide-react";
import { useState } from "react";
import { useUserPrefs } from "@/lib/user-prefs";

export const Route = createFileRoute("/community/sos")({ component: SOS });

function SOS() {
  const [pressed, setPressed] = useState(false);
  const prefs = useUserPrefs();
  return (
    <PhoneFrame>
      <div className="min-h-full flex flex-col bg-background">
        <ScreenHeader title="SOS Emergency" subtitle="One-tap ranger dispatch" back="/community" />
        <div className="px-5 pb-6 space-y-5 text-center">
          <p className="text-sm text-muted-foreground">Press and hold to alert {prefs.park} rangers with your live location.</p>

          <div className="relative grid place-items-center py-8">
            <div className="absolute h-64 w-64 rounded-full bg-destructive/10 animate-pulse" />
            <div className="absolute h-48 w-48 rounded-full bg-destructive/20" />
            <button
              onMouseDown={() => setPressed(true)} onMouseUp={() => setPressed(false)} onMouseLeave={() => setPressed(false)}
              onTouchStart={() => setPressed(true)} onTouchEnd={() => setPressed(false)}
              className={`relative h-36 w-36 rounded-full bg-gradient-to-br from-destructive to-[oklch(0.55_0.22_25)] text-white grid place-items-center shadow-2xl transition-transform ${pressed ? "scale-95" : ""}`}>
              <div>
                <AlertOctagon size={40} className="mx-auto" />
                <div className="font-extrabold text-lg mt-1">SOS</div>
              </div>
            </button>
          </div>

          <div className="bg-card rounded-2xl p-4 shadow-card text-left">
            <div className="flex items-center gap-2 text-xs font-semibold text-muted-foreground"><MapPin size={14} className="text-primary" />Your live location</div>
            <div className="mt-2 text-sm font-semibold">Pakwach, Buliisa District</div>
            <div className="text-[11px] text-muted-foreground">Lat 2.0421°N · Lon 31.4612°E · ±8m</div>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <button className="bg-card rounded-2xl p-4 shadow-card flex items-center gap-3">
              <div className="h-10 w-10 rounded-xl bg-primary/10 grid place-items-center"><Shield className="text-primary" size={18} /></div>
              <div className="text-left"><div className="text-sm font-semibold">UWA</div><div className="text-[11px] text-muted-foreground">Hotline</div></div>
            </button>
            <button className="bg-card rounded-2xl p-4 shadow-card flex items-center gap-3">
              <div className="h-10 w-10 rounded-xl bg-destructive/10 grid place-items-center"><Phone className="text-destructive" size={18} /></div>
              <div className="text-left"><div className="text-sm font-semibold">999</div><div className="text-[11px] text-muted-foreground">Emergency</div></div>
            </button>
          </div>
        </div>
        <CommunityTabBar />
      </div>
    </PhoneFrame>
  );
}