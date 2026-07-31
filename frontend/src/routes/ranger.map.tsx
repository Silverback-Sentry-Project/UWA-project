import { createFileRoute } from "@tanstack/react-router";
import { PhoneFrame } from "@/components/PhoneFrame";
import { RangerTabBar, ScreenHeader, Pill } from "@/components/ui-prototype";
import { Layers, Navigation, Plus, Route as RouteIcon, Eye, Users } from "lucide-react";
import { useState } from "react";

export const Route = createFileRoute("/ranger/map")({ component: MapScreen });

function MapScreen() {
  const [view, setView] = useState<"3D" | "Sat">("Sat");
  return (
    <PhoneFrame>
      <div className="min-h-full flex flex-col bg-background">
        <ScreenHeader title="Field Map" subtitle="Murchison Falls · Live" back="/ranger/incident" />

        <div className="relative flex-1 mx-3 rounded-3xl overflow-hidden shadow-card" style={{ minHeight: 480 }}>
          {/* Map surface */}
          <div className="absolute inset-0" style={{
            background: view === "Sat"
              ? "radial-gradient(circle at 30% 40%, oklch(0.45 0.07 140), oklch(0.3 0.05 145) 60%, oklch(0.2 0.04 150))"
              : "linear-gradient(135deg, oklch(0.7 0.08 140), oklch(0.55 0.1 150))",
          }}>
            <svg className="absolute inset-0 w-full h-full opacity-60" viewBox="0 0 400 600" preserveAspectRatio="none">
              <path d="M 0 200 Q 100 150 200 220 T 400 180 L 400 300 Q 300 350 200 320 T 0 360 Z" fill="oklch(0.35 0.1 220 / 0.5)" />
              <path d="M 50 0 Q 80 200 120 400 T 180 600" stroke="oklch(0.85 0.04 95)" strokeWidth="2" strokeDasharray="6 6" fill="none" />
              <path d="M 280 0 Q 260 180 300 380 T 380 600" stroke="oklch(0.85 0.04 95)" strokeWidth="2" strokeDasharray="6 6" fill="none" />
              <path d="M 80 500 Q 200 400 340 460" stroke="oklch(0.75 0.18 65)" strokeWidth="3" fill="none" />
            </svg>
            {/* Markers */}
            <Marker x="40%" y="35%" color="bg-destructive" label="INC-2049" />
            <Marker x="65%" y="55%" color="bg-warning" label="Snare" />
            <Marker x="25%" y="70%" color="bg-info" label="R. Joseph" icon />
            <Marker x="75%" y="30%" color="bg-success" label="Sighting" />

            {/* Ranger position */}
            <div className="absolute" style={{ left: "50%", top: "60%", transform: "translate(-50%,-50%)" }}>
              <div className="absolute inset-0 -m-4 rounded-full bg-primary/30 animate-ping" />
              <div className="relative h-5 w-5 rounded-full bg-primary border-2 border-white shadow-lg" />
            </div>
          </div>

          {/* Top overlay: view toggle */}
          <div className="absolute top-3 left-3 right-3 flex items-start justify-between">
            <div className="bg-card/95 backdrop-blur rounded-2xl p-1 flex shadow-card">
              {(["3D", "Sat"] as const).map((v) => (
                <button key={v} onClick={() => setView(v)}
                  className={`px-3 py-1.5 rounded-xl text-xs font-bold ${view === v ? "bg-primary text-primary-foreground" : "text-foreground"}`}>{v}</button>
              ))}
            </div>
            <div className="flex flex-col gap-2">
              <button className="h-10 w-10 rounded-2xl bg-card/95 backdrop-blur shadow-card grid place-items-center"><Layers size={16} /></button>
              <button className="h-10 w-10 rounded-2xl bg-card/95 backdrop-blur shadow-card grid place-items-center"><Plus size={16} /></button>
              <button className="h-10 w-10 rounded-2xl bg-card/95 backdrop-blur shadow-card grid place-items-center"><Navigation size={16} className="text-primary" /></button>
            </div>
          </div>

          {/* Bottom action sheet */}
          <div className="absolute left-3 right-3 bottom-3 bg-card/95 backdrop-blur rounded-2xl shadow-card p-3">
            <div className="flex items-center gap-2 mb-2">
              <Pill tone="success">● Live</Pill>
              <span className="text-[11px] text-muted-foreground">Updated 4s ago</span>
              <div className="flex-1" />
              <Pill tone="info"><Users size={10} /> 4 rangers nearby</Pill>
            </div>
            <div className="grid grid-cols-3 gap-2">
              <Action icon={RouteIcon} label="Add route" />
              <Action icon={Eye} label="Add sighting" />
              <Action icon={Plus} label="Mark zone" />
            </div>
          </div>
        </div>

        <div className="px-5 pt-4 pb-2">
          <div className="bg-warning/15 rounded-2xl p-3 text-xs flex items-start gap-2">
            <Users size={16} className="text-foreground shrink-0 mt-0.5" />
            <div><span className="font-bold">Ranger Joseph</span> moved to Sector 4 — avoid overlap. Coordinate via map chat.</div>
          </div>
        </div>
        <RangerTabBar />
      </div>
    </PhoneFrame>
  );
}

function Marker({ x, y, color, label, icon }: { x: string; y: string; color: string; label: string; icon?: boolean }) {
  return (
    <div className="absolute -translate-x-1/2 -translate-y-full" style={{ left: x, top: y }}>
      <div className={`h-7 w-7 rounded-full ${color} border-2 border-white shadow-lg grid place-items-center text-white text-[10px] font-bold`}>
        {icon ? "R" : "!"}
      </div>
      <div className="absolute top-7 left-1/2 -translate-x-1/2 mt-1 bg-card/95 backdrop-blur px-2 py-0.5 rounded-md text-[9px] font-bold whitespace-nowrap shadow">{label}</div>
    </div>
  );
}
function Action({ icon: Icon, label }: { icon: any; label: string }) {
  return (
    <button className="bg-secondary rounded-xl p-2 flex flex-col items-center gap-1">
      <Icon size={16} className="text-primary" />
      <span className="text-[10px] font-semibold">{label}</span>
    </button>
  );
}